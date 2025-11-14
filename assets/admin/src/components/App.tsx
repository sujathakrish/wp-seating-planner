import React, { useEffect, useState } from 'react';
import { api } from '../api';
import Toolbar from './Toolbar';
import GuestList from './GuestList';
import Canvas from './Canvas';

type EventRow = { id:number; title:string };

export default function App(){
  const [events,setEvents]=useState<EventRow[]>([]);
  const [eventId,setEventId]=useState<number|undefined>();
  const [rev,setRev]=useState(0);
  useEffect(()=>{ api('events').then(setEvents); },[]);
  return (
    <div className="sp-grid">
      <Toolbar events={events} eventId={eventId} onEventChange={setEventId} onRefresh={()=>setRev(v=>v+1)} />
      {eventId ? <div className="sp-main"><GuestList eventId={eventId} rev={rev} /><Canvas eventId={eventId} rev={rev} /></div> : <div className="sp-empty">Select or create an event</div>}
    </div>
  );
}
