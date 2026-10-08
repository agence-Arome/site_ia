const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname,'..');
const pages = JSON.parse(fs.readFileSync(path.join(root,'tools/content.json'),'utf8'));
const paths = new Set(pages.map(p=>'/'+(p.parent?p.parent+'/':'')+p.slug+'/'));
paths.add('/');
const titles=new Set(),descriptions=new Set();
for(const p of pages){
 assert(p.seo, p.slug+' missing SEO');
 assert(!titles.has(p.seo.title),p.slug+' duplicate title'); titles.add(p.seo.title);
 assert(!descriptions.has(p.description),p.slug+' duplicate description'); descriptions.add(p.description);
 assert(p.description===p.seo.description);
 assert(p.seo.title.length<=70,p.slug+' long title');
 assert(p.description.length<=170,p.slug+' long description');
 assert(!/opusdomus/i.test(p.content),p.slug+' competitor');
 const h1=(p.content.match(/<h1\b/g)||[]).length;
 assert.equal(h1,p.slug==='accueil'?1:0,p.slug+' duplicate/missing content H1');
 for(const m of p.content.matchAll(/href="(\/[^"?#]*)(?:[?#][^"]*)?"/g))assert(paths.has(m[1]),p.slug+' broken internal link '+m[1]);
 const stack=[];
 for(const m of p.content.matchAll(/<!-- (\/?wp:)([\w/-]+)([\s\S]*?)-->/g)){
  if(m[1]==='/wp:') assert.equal(stack.pop(),m[2],p.slug+' invalid block nesting');
  else if(!/\/\s*$/.test(m[3]))stack.push(m[2]);
 }
 assert.equal(stack.length,0,p.slug+' unclosed blocks');
}
console.log(`${pages.length} contenus : métadonnées uniques, liens internes et structure des blocs vérifiés.`);
