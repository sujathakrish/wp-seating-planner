import React, { useEffect, useRef, useState } from 'react';
import { Stage, Layer, Rect, Circle, Text, Group } from 'react-konva';
import { api } from '../api';

type Table = { id:number; name:string; shape:'round'|'rect'; capacity:number; x:number; y:number; rotation:number; width?:number; height?:number };

function seatPositions(t:Table){
  const w = t.width || (t.shape==='round'?160:160);
  const h = t.height || (t.shape==='round'?160:100);
  const seats = t.capacity;
  const pos: {x:number,y:number,angle:number,seat:number}[] = [];
  if (t.shape==='round'){
    const r = Math.min(w,h)/2 + 18;
    for (let i=0;i<seats;i++){
      const angle = (Math.PI*2) * (i/seats) - Math.PI/2;
      pos.push({ x: t.x + w/2 + r*Math.cos(angle), y: t.y + h/2 + r*Math.sin(angle), angle, seat: i+1 });
    }
  } else {
    const perSide = Math.ceil(seats/4);
    const pad = 12;
    for (let i=0;i<seats;i++){
      const side = Math.floor(i/perSide);
      const idx = i%perSide;
      const spacingX = (w - 2*pad) / Math.max(1,perSide-1);
      const spacingY = (h - 2*pad) / Math.max(1,perSide-1);
      let x=t.x, y=t.y;
      if (side===0){ x = t.x + pad + idx*spacingX; y = t.y - 22; }            // top
      if (side===1){ x = t.x + w + 22; y = t.y + pad + idx*spacingY; }         // right
      if (side===2){ x = t.x + pad + idx*spacingX; y = t.y + h + 22; }         // bottom
      if (side===3){ x = t.x - 22; y = t.y + pad + idx*spacingY; }             // left
      pos.push({x,y,angle:0,seat:i+1});
    }
  }
  return pos;
}

export default function Canvas({eventId,rev}:{eventId:number;rev:number}){
  const [tables,setTables]=useState<Table[]>([]);
  const stageRef=useRef<any>(null);
  useEffect(()=>{ api(`events/${eventId}/tables`).then(setTables); },[eventId,rev]);

  const onDrop = async (ev:any)=>{
    ev.preventDefault();
    const data=JSON.parse(ev.dataTransfer.getData('text'));
    const pos = stageRef.current.getPointerPosition();
    // find nearest seat
    let chosen:{table:Table, seat:number, dist:number}|null=null;
    tables.forEach(t=>{
      const seats = seatPositions(t);
      seats.forEach(s=>{
        const d = Math.hypot(pos.x - s.x, pos.y - s.y);
        if (d<24 && (!chosen || d < chosen.dist)){
          chosen = { table:t, seat:s.seat, dist:d };
        }
      });
    });
    if (chosen){
      await api(`events/${eventId}/assign`,{method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({guest_id:data.guest_id, table_id:chosen.table.id, seat_number:chosen.seat})});
      alert(`Assigned to ${chosen.table.name} seat #${chosen.seat}`);
    } else {
      alert('Drop near a seat circle to assign');
    }
  };

  return (
    <div className="sp-canvas" onDragOver={e=>e.preventDefault()} onDrop={onDrop}>
      <Stage width={1000} height={700} ref={stageRef}>
        <Layer>
          {tables.map(t=>{
            const w = t.width || (t.shape==='round'?160:160);
            const h = t.height || (t.shape==='round'?160:100);
            const seats = seatPositions(t);
            return (
              <Group key={t.id}>
                {t.shape==='round' ? <Circle x={t.x+w/2} y={t.y+h/2} radius={Math.min(w,h)/2} stroke="black" /> : <Rect x={t.x} y={t.y} width={w} height={h} stroke="black" />}
                <Text x={t.x} y={t.y-20} text={`${t.name} (${t.capacity})`} />
                {seats.map(s=>(
                  <Group key={s.seat}>
                    <Circle x={s.x} y={s.y} radius={10} stroke="black" />
                    <Text x={s.x-6} y={s.y-6} text={String(s.seat)} fontSize={10} />
                  </Group>
                ))}
              </Group>
            );
          })}
        </Layer>
      </Stage>
    </div>
  );
}
