import React, { useEffect, useState } from 'react';
import { api } from '../api';

export default function GuestList({eventId,rev}:{eventId:number;rev:number}){
  const [guests,setGuests]=useState<any[]>([]);
  useEffect(()=>{ api(`events/${eventId}/guests`).then(setGuests); },[eventId,rev]);
  const unassigned = guests.filter(g=>!g.table_id);
  return (
    <div className="sp-guestlist">
      <div className="sp-guestlist-title">Unassigned ({unassigned.length})</div>
      {unassigned.map(g=> (
        <div key={g.id} className="sp-guest" draggable onDragStart={(e)=>e.dataTransfer.setData('text/plain', JSON.stringify({guest_id:g.id}))}>
          {g.first_name} {g.last_name} {g.party?`· ${g.party}`:''} {g.is_child? '· Child':''}
        </div>
      ))}
    </div>
  );
}
