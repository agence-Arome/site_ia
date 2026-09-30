/* Additional HTTP checks; test fixture contains no real personal data. */
const assert=require('node:assert/strict');
const base=process.env.WP_TEST_URL||'http://127.0.0.1:8080';
if(!['127.0.0.1','localhost'].includes(new URL(base).hostname))throw Error('Local URL required');
(async()=>{
for(const slug of ['creation-site-wordpress','refonte-site-wordpress','depannage-site-pirate','audit-securite-wordpress','maintenance-wordpress','developpement-wordpress-sur-mesure']){
 const res=await fetch(base+'/services/'+slug+'/');assert.equal(res.status,200);const html=await res.text();assert.ok(html.includes('application/ld+json'));assert.equal((html.match(/rel=['"]canonical['"]/g)||[]).length,1);console.log('PASS: service, schéma et canonique '+slug);
}
const page=await fetch(base+'/contact/');const cookies=page.headers.getSetCookie().map(c=>c.split(';')[0]).join('; ');const html=await page.text();const token=html.match(/name="token" value="([^"]+)"/)[1];
const data=new URLSearchParams({action:'ewp_submit',mode:'contact',token,name:'Test HTTP',email:'browser-fixture@example.test',message:'Test de la frontière HTTP, sans donnée réelle.',privacy:'1'});
const cross=await fetch(base+'/wp-json/expert-wp/v1/token',{headers:{Origin:'https://untrusted.example.test',Cookie:cookies}});assert.equal(cross.status,403);console.log('PASS: émission de jeton refusée à une autre origine');
const bad=await fetch(base+'/wp-admin/admin-post.php',{method:'POST',body:data,headers:{Origin:'https://untrusted.example.test',Cookie:cookies},redirect:'manual'});assert.equal(bad.status,403);console.log('PASS: POST externe refusé');
const good=await fetch(base+'/wp-admin/admin-post.php',{method:'POST',body:data,headers:{Origin:base,Cookie:cookies},redirect:'manual'});assert.equal(good.status,303);console.log('PASS: POST de même origine accepté');
const thanks=await fetch(base+'/merci/');const thankHtml=await thanks.text();assert.ok(thankHtml.includes('noindex'));assert.ok(!thankHtml.includes('Votre demande a bien été enregistrée.'));console.log('PASS: confirmation non indexable et non simulable par URL seule');
})().catch(e=>{console.error(e);process.exitCode=1;});
