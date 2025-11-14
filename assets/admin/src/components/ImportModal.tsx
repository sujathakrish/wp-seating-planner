import React, { useEffect, useState } from 'react';

const FIELDS = ['first_name','last_name','party','is_child','meal','notes'] as const;
type Field = typeof FIELDS[number];

export default function ImportModal({eventId,onClose,onImported}:{eventId:number;onClose:()=>void;onImported:()=>void}){
  const [file,setFile]=useState<File|null>(null);
  const [headers,setHeaders]=useState<string[]>([]);
  const [map,setMap]=useState<Record<string,string>>({});

  useEffect(()=>{ if(!file) return;
    const r=new FileReader();
    r.onload=()=>{
      const text = String(r.result||'');
      const firstLine = text.split(/\r?\n/)[0]||'';
      const cols = firstLine.split(',').map(s=>s.trim().replace(/^"|"$/g,''));
      setHeaders(cols);
      const defaults:Record<string,string>={};
      // naive auto-match
      cols.forEach(c=>{
        const lc = c.toLowerCase();
        if (lc.includes('first')) defaults['first_name']=c;
        if (lc.includes('last')) defaults['last_name']=c;
        if (lc.includes('party')||lc.includes('group')||lc.includes('family')) defaults['party']=c;
        if (lc.includes('child')||lc.includes('kid')) defaults['is_child']=c;
        if (lc.includes('meal')||lc.includes('diet')) defaults['meal']=c;
        if (lc.includes('note')) defaults['notes']=c;
      });
      setMap(m=>({...defaults,...m}));
    };
    r.readAsText(file);
  },[file]);

  const upload=async()=>{
    if(!file) return;
    const fd=new FormData();
    fd.append('file', file);
    fd.append('map[first_name]', map['first_name']||'first_name');
    fd.append('map[last_name]', map['last_name']||'last_name');
    if (map['party']) fd.append('map[party]', map['party']);
    if (map['is_child']) fd.append('map[is_child]', map['is_child']);
    if (map['meal']) fd.append('map[meal]', map['meal']);
    if (map['notes']) fd.append('map[notes]', map['notes']);
    await fetch((window as any).SPCFG.root+`events/${eventId}/import`,{method:'POST', headers:{'X-WP-Nonce':(window as any).SPCFG.nonce}, body:fd});
    onImported(); onClose();
  };

  return (
    <div className="sp-modal">
      <div className="sp-modal-card">
        <div className="sp-modal-title">Import CSV</div>
        <input type="file" accept=".csv" onChange={e=>setFile(e.target.files?.[0]||null)} />
        {headers.length>0 && (
          <div className="sp-mapper">
            {FIELDS.map(f=>(
              <div className="sp-map-row" key={f}>
                <label>{f}</label>
                <select value={map[f]||''} onChange={e=>setMap({...map,[f]:e.target.value})}>
                  <option value="">— not mapped —</option>
                  {headers.map(h=> <option key={h} value={h}>{h}</option>)}
                </select>
              </div>
            ))}
          </div>
        )}
        <div className="sp-modal-actions">
          <button onClick={onClose}>Cancel</button>
          <button onClick={upload} disabled={!file}>Import</button>
        </div>
      </div>
    </div>
  );
}
