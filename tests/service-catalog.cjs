/* Read-only verification against a seeded local WordPress installation. */
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const base=process.env.WP_TEST_URL||'http://127.0.0.1:8080';
if(!['127.0.0.1','localhost'].includes(new URL(base).hostname))throw Error('Local URL required');
const items=JSON.parse(fs.readFileSync(path.join(__dirname,'../tools/content.json'),'utf8'));
(async()=>{
 let pages=0,quotes=0;
 for(const item of items.filter(p=>p.parent==='services'||p.slug==='services')){
  const route='/'+(item.parent?item.parent+'/':'')+item.slug+'/';
  const response=await fetch(base+route);assert.equal(response.status,200,route);
  const html=await response.text();assert.equal((html.match(/<h1\b/g)||[]).length,1,route+' unique H1');
  assert.match(html,/<meta name="description" content="[^"]+"/);
  assert.ok(html.includes('Sous-menu Nos autres services'));pages++;
  for(const match of item.content.matchAll(/\/devis\/\?service=([a-z-]+)/g)){
   const quote=await (await fetch(base+'/devis/?service='+match[1])).text();
   assert.match(quote,new RegExp('<option\\b[^>]*value="'+match[1]+'"[^>]*\\bselected='),match[1]+' selected quote service');quotes++;
  }
 }
 console.log(JSON.stringify({pages,quoteLinks:quotes,result:'passed'}));
})().catch(error=>{console.error(error);process.exitCode=1;});
