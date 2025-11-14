export type Table = { id:number; name:string; shape:'round'|'rect'; capacity:number; x:number; y:number; rotation:number; width?:number; height?:number };
export type Guest = { id:number; first_name:string; last_name:string; party?:string; is_child?:number; meal?:string; notes?:string; table_id?:number; seat_number?:number };
