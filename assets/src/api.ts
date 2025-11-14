const root = (window as any).SPCFG.root as string;
const nonce = (window as any).SPCFG.nonce as string;
export async function api(path: string, opts: RequestInit = {}){
  const res = await fetch(root + path, { ...opts, headers: { 'X-WP-Nonce': nonce, ...(opts.headers||{}) } });
  if (!res.ok) throw new Error(await res.text());
  const ct = res.headers.get('content-type') || '';
  return ct.includes('application/json') ? res.json() : res.text();
}
