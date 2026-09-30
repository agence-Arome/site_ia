/* Run only against an isolated local WordPress with seeded content. */
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const base=process.env.WP_TEST_URL||'http://127.0.0.1:8080';
if(!['127.0.0.1','localhost'].includes(new URL(base).hostname))throw Error('Local test URL required');
const artifacts=process.env.TEST_ARTIFACTS||path.resolve('test-results');fs.mkdirSync(artifacts,{recursive:true});
let checks=0;
function check(condition,label){assert.ok(condition,label);checks++;console.log('PASS: '+label);}
(async()=>{
 const browser=await chromium.launch({headless:true,...(process.env.PLAYWRIGHT_EXECUTABLE?{executablePath:process.env.PLAYWRIGHT_EXECUTABLE}:{})});
 try{
 const context=await browser.newContext({viewport:{width:1440,height:1000}});
 const page=await context.newPage();const errors=[];const a11y=[];
 page.on('pageerror',e=>errors.push(e.message));
 await page.addInitScript(()=>{window.ewpConversions=0;window.addEventListener('expertwp:lead-recorded',()=>window.ewpConversions++);});
 const axe=process.env.AXE_PATH||require.resolve('axe-core/axe.min.js');
 for(const route of ['/','/services/','/devis/','/contact/','/services/creation-site-wordpress/']){
  const response=await page.goto(base+route,{waitUntil:'networkidle'});
  check(response.status()===200,'Page accessible '+route);
  check(await page.locator('h1').count()===1,'Titre H1 unique '+route);
  await page.addScriptTag({path:axe});
  const report=await page.evaluate(async()=>await axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa','wcag22aa']}}));
  a11y.push({route,violations:report.violations.map(v=>({id:v.id,impact:v.impact,nodes:v.nodes.map(n=>n.target)}))});
 }
 fs.writeFileSync(path.join(artifacts,'accessibility.json'),JSON.stringify(a11y,null,2));
 check(a11y.every(x=>x.violations.length===0),'Aucune violation axe sur les cinq pages');
 await page.goto(base+'/',{waitUntil:'networkidle'});
 await page.screenshot({path:path.join(artifacts,'accueil-desktop.png'),fullPage:true});
 for(const width of [320,390,768,1440]){
  await page.setViewportSize({width,height:900});
  check(!(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth)),'Pas de débordement à '+width+' px');
 }
 await page.setViewportSize({width:390,height:844});
 await page.screenshot({path:path.join(artifacts,'accueil-mobile.png'),fullPage:true});
 await page.locator('.wp-block-navigation__responsive-container-open').click();
 check(await page.locator('.wp-block-navigation__responsive-container.is-menu-open').count()===1,'Menu mobile ouvert');
 await page.keyboard.press('Escape');
 check(await page.locator('.wp-block-navigation__responsive-container.is-menu-open').count()===0,'Menu mobile fermé par Échap');
 await page.goto(base+'/devis/',{waitUntil:'networkidle'});
 await page.screenshot({path:path.join(artifacts,'devis-mobile.png'),fullPage:true});
 for(const service of ['creation','refonte','depannage','securite','maintenance','developpement']){
  await page.selectOption('[name=service]',service);
  check((await page.locator('[data-question-label]').innerText()).length>15,'Question conditionnelle '+service);
 }
 await page.selectOption('[name=service]','creation');
 await page.fill('[name=details]','Créer une présence pour une entreprise fictive de test.');
 await page.fill('[name=name]','Test navigateur');
 await page.fill('[name=email]','browser-fixture@example.test');
 await page.fill('[name=message]','Demande de test locale, à supprimer après vérification.');
 await page.check('[name=privacy]');
 const payload=await page.locator('.ewp-form').evaluate(f=>Object.fromEntries(new FormData(f)));
 await Promise.all([page.waitForURL('**/merci/'),page.locator('button[type=submit]').click()]);
 check((await page.locator('body').innerText()).includes('Votre demande a bien été enregistrée.'),'Envoi réel puis confirmation');
 check(await page.evaluate(()=>window.ewpConversions)===1,'Un événement après enregistrement');
 await page.reload({waitUntil:'networkidle'});
 check(await page.evaluate(()=>window.ewpConversions)===0,'Pas de conversion au rechargement');
 const repeat=await context.request.post(base+'/wp-admin/admin-post.php',{form:payload,maxRedirects:0});
 check(repeat.status()===303,'Renvoi idempotent du même formulaire');
 await page.goto(base+'/merci/',{waitUntil:'networkidle'});
 check(await page.evaluate(()=>window.ewpConversions)===0,'Pas de conversion au double envoi');
 const denied=await context.request.post(base+'/wp-admin/admin-post.php',{form:{...payload,token:'forged'},maxRedirects:0});
 check(denied.status()===403,'Jeton falsifié rejeté par HTTP');
 const fresh=await (await context.request.get(base+'/wp-json/expert-wp/v1/token')).json();
 const invalid=await context.request.post(base+'/wp-admin/admin-post.php',{form:{...payload,token:fresh.token,email:'invalid'},maxRedirects:0});
 check(invalid.status()===422,'Validation serveur indépendante du navigateur');
 const spam=await context.request.post(base+'/wp-admin/admin-post.php',{form:{...payload,token:fresh.token,fax:'bot'},maxRedirects:0});
 check(spam.status()===400,'Champ piège rejeté');
 const rest=await context.request.get(base+'/wp-json/wp/v2/ewp_lead');
 check(rest.status()===404,'Aucune route REST publique des leads');
 const nojs=await browser.newContext({javaScriptEnabled:false,reducedMotion:'reduce'});
 const plain=await nojs.newPage();await plain.goto(base+'/contact/');
 await plain.fill('[name=name]','Test sans JavaScript');await plain.fill('[name=email]','nojs-fixture@example.test');
 await plain.fill('[name=message]','Cette demande vérifie le fonctionnement sans JavaScript.');await plain.check('[name=privacy]',{force:true});
 await Promise.all([plain.waitForURL('**/merci/'),plain.locator('button[type=submit]').click({force:true})]);
 check((await plain.locator('body').innerText()).includes('Votre demande a bien été enregistrée.'),'Envoi sans JavaScript');
 await nojs.close();
 const password=process.env.WP_TEST_PASSWORD||(process.env.WP_TEST_PASSWORD_FILE?fs.readFileSync(process.env.WP_TEST_PASSWORD_FILE,'utf8').trim():'');
 if(password){
  await page.goto(base+'/wp-login.php');await page.fill('#user_login',process.env.WP_TEST_USER||'local-admin');await page.fill('#user_pass',password);
  await Promise.all([page.waitForURL('**/wp-admin/**'),page.click('#wp-submit')]);
  await page.goto(base+'/wp-admin/admin.php?page=ewp-leads');
  check((await page.locator('body').innerText()).includes('browser-fixture@example.test'),'Lead visible dans l’administration');
  await page.locator('tbody tr').filter({hasText:'browser-fixture@example.test'}).getByRole('link').click();
  await page.selectOption('[name=status]','qualified');
  await Promise.all([page.waitForNavigation(),page.locator('button[value=status]').click()]);
  check(await page.locator('[name=status]').inputValue()==='qualified','Changement de statut');
  const leadId=await page.locator('[name=lead]').inputValue();
  const noNonce=await context.request.post(base+'/wp-admin/admin-post.php',{form:{action:'ewp_status',lead:leadId,operation:'status',status:'won'},maxRedirects:0});
  check(noNonce.status()===403,'Mutation admin sans nonce refusée');
  await page.goto(base+'/wp-admin/post-new.php?post_type=page',{waitUntil:'networkidle'});
  await page.waitForFunction(()=>window.wp?.blocks?.parse);
  const contents=JSON.parse(fs.readFileSync(path.resolve(__dirname,'../tools/content.json'),'utf8'));
  const invalidBlocks=await page.evaluate(items=>{
   const results=[];function walk(blocks,slug){blocks.forEach(b=>{if(b.isValid===false)results.push({slug,name:b.name});walk(b.innerBlocks||[],slug);});}
   items.forEach(item=>walk(wp.blocks.parse(item.content),item.slug));return results;
  },contents);
  fs.writeFileSync(path.join(artifacts,'gutenberg.json'),JSON.stringify(invalidBlocks,null,2));
  check(invalidBlocks.length===0,'Tous les contenus valides dans Gutenberg');
 }
 check(errors.length===0,'Aucune erreur JavaScript');
 fs.writeFileSync(path.join(artifacts,'browser-summary.json'),JSON.stringify({checks,errors,testedAt:new Date().toISOString()},null,2));
 console.log(checks+' vérifications navigateur réussies.');
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});
