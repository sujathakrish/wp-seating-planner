import React, { useState } from 'react';
import { api } from '../api';
import ImportModal from './ImportModal';

export default function Toolbar({events,eventId,onEventChange,onRefresh}:{events:any[],eventId?:number,onEventChange:(n:number)=>void,onRefresh:()=>void}){
  const [title,setTitle]=useState('');
  const [group,setGroup]=useState<'table'|'first_name'|'last_name'>('table');
  const [showImport,setShowImport]=useState(false);

  const create=async()=>{ if(!title.trim()) return; await api('events',{method:'POST',body:JSON.stringify({title}),headers:{'Content-Type':'application/json'}}); onRefresh(); setTitle(''); };

  const exportXlsx=()=> window.open(`${(window as any).SPCFG.root}events/${eventId}/export?type=xlsx&group=${group}`,'_blank');
  const exportCsv=()=>  window.open(`${(window as any).SPCFG.root}events/${eventId}/export?type=csv&group=${group}`,'_blank');
  const printPdf=()=>   window.open(`${(window as any).SPCFG.root}events/${eventId}/print?event_id=${eventId}`,'_blank');

  const autoSeat=async()=>{ if(!eventId) return; await api(`events/${eventId}/auto-seat`,{method:'POST'}); alert('Auto-seating complete'); onRefresh(); };

  return (
    <div className="sp-toolbar">
      <select onChange={e=>onEventChange(parseInt(e.target.value))} value={eventId||''}>
        <option value="">– Select event –</option>
        {events.map(e=> <option key={e.id} value={e.id}>{e.title}</option>)}
      </select>
      <input placeholder="New event title" value={title} onChange={e=>setTitle(e.target.value)} />
      <button onClick={create}>Create</button>

      <button onClick={()=>setShowImport(true)} disabled={!eventId}>Import CSV…</button>

      <select value={group} onChange={e=>setGroup(e.target.value as any)}>
        <option value="table">Group by table</option>
        <option value="first_name">Group by first letter (first)</option>
        <option value="last_name">Group by first letter (last)</option>
      </select>
      <button onClick={exportXlsx} disabled={!eventId}>Export Excel</button>
      <button onClick={exportCsv} disabled={!eventId}>Export CSV</button>
      <button onClick={printPdf} disabled={!eventId}>Print PDF</button>
      <button onClick={autoSeat} disabled={!eventId}>Auto-seat by party</button>

      {showImport && <ImportModal eventId={eventId!} onClose={()=>setShowImport(false)} onImported={onRefresh} />}
    </div>
  );
}
