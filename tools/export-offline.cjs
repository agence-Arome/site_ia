const fs=require('node:fs');
const path=require('node:path');
const base=(process.env.WP_PREVIEW_URL||'http://127.0.0.1:8080').replace(/\/$/,'');
const content=JSON.parse(fs.readFileSync(path.join(__dirname,'content.json'),'utf8'));
const paths=['/',...content.filter(p=>p.slug!=='accueil'&&p.status!=='draft').map(p=>'/'+(p.parent?p.parent+'/':'')+p.slug+'/')];
const cssCache=new Map();
async function css(url){if(!cssCache.has(url)){const r=await fetch(url);if(!r.ok)throw Error(url);cssCache.set(url,await r.text());}return cssCache.get(url);}
const pageScript=`
document.querySelectorAll('form').forEach(function(form){
 form.removeAttribute('action');
 form.addEventListener('submit',function(e){e.preventDefault();var status=form.querySelector('[role="status"]');if(status){status.textContent='Aperçu hors ligne : aucune demande n’est envoyée ni enregistrée.';}else{alert('Cette fonction nécessite le site WordPress installé.');}});
 var button=form.querySelector('[type="submit"]');if(button&&form.classList.contains('ewp-form'))button.textContent='Formulaire de démonstration';
 var service=form.querySelector('[name="service"]');if(service){service.addEventListener('change',function(){var option=service.options[service.selectedIndex];form.querySelector('[data-question-label]').textContent=(option.dataset.question||'Précisez votre besoin pour la prestation choisie.')+' *';});}
});
function closeMenu(){document.querySelectorAll('.wp-block-navigation__responsive-container').forEach(function(menu){menu.classList.remove('is-menu-open','has-modal-open');});document.body.style.overflow='';document.querySelectorAll('.wp-block-navigation__responsive-container-open').forEach(function(b){b.setAttribute('aria-expanded','false');});}
document.querySelectorAll('.wp-block-navigation__responsive-container-open').forEach(function(button){button.addEventListener('click',function(){var nav=button.closest('nav');var menu=nav.querySelector('.wp-block-navigation__responsive-container');menu.classList.add('is-menu-open','has-modal-open');button.setAttribute('aria-expanded','true');document.body.style.overflow='hidden';menu.querySelector('button').focus();});});
document.querySelectorAll('.wp-block-navigation__responsive-container-close').forEach(function(b){b.addEventListener('click',closeMenu);});
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeMenu();});
document.addEventListener('click',function(e){var a=e.target.closest('a');if(!a)return;var raw=a.getAttribute('href');if(!raw||raw.startsWith('#'))return;var u=new URL(raw,'https://offline.example');if(u.origin==='https://offline.example'||u.hostname==='127.0.0.1'){e.preventDefault();parent.postMessage({kind:'expert-wp-offline-route',route:u.pathname+u.search},'*');}});
var requested='__OFFLINE_SERVICE__';var select=document.querySelector('[name="service"]');if(requested&&select){select.value=requested;select.dispatchEvent(new Event('change'));}
`;
(async()=>{
 const pages={};
 for(const route of paths){
  const r=await fetch(base+route);if(!r.ok)throw Error('HTTP '+r.status+' '+route);let html=await r.text();
  html=html.replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi,'');
  const links=[...html.matchAll(/<link\b[^>]*>/gi)];
  for(const [tag]of links){if(/rel=['"]stylesheet['"]/.test(tag)){const url=tag.match(/href=['"]([^'"]+)/)?.[1].replaceAll('&#038;','&').replaceAll('&amp;','&');html=html.replace(tag,'<style>'+await css(new URL(url,base).href)+'</style>');}else html=html.replace(tag,'');}
  html=html.replace(/<input\b[^>]*type="hidden"[^>]*>/gi,'');
  html=html.replace(/(<form\b[^>]*?)action="[^"]*"/gi,'$1action="#"');
  html=html.replace('<head>','<head><meta http-equiv="Content-Security-Policy" content="connect-src \'none\'; form-action \'none\'">');
  html=html.replace(/ data-wp-[\w:-]+(?:="[^"]*")?/g,'');
  html=html.replaceAll(base,'https://offline.example');
  html=html.replace('</head>','<style>.offline-note{padding:9px 18px;background:#eef3ff;color:#172338;font:12px/1.5 system-ui;text-align:center;border-bottom:1px solid #cdd8ee}.offline-note a{color:#2049c5}.ewp-form-status{font-weight:600}</style></head>');
  html=html.replace(/(<body\b[^>]*>)/i,'$1<div class="offline-note">Version de consultation hors ligne · Navigation disponible · Formulaires sans envoi</div>');
  html=html.replace('</body>','<script>'+pageScript+'</script></body>');
  pages[route]=html;
 }
 const legal='<html lang="fr"><meta charset="utf-8"><title>Document à compléter</title><style>body{font:18px/1.6 system-ui;max-width:760px;margin:80px auto;padding:24px;color:#101e35}a{color:#2454e8}</style><h1>Document à compléter</h1><p>Les informations légales et la politique de confidentialité sont en préparation. Cette copie est uniquement destinée à consulter la maquette ; elle ne collecte ni ne transmet de données.</p><p><a href="/">Retour à l’accueil</a></p><script>'+pageScript+'</script></html>';
 pages['/mentions-legales/']=legal;pages['/confidentialite/']=legal;
 const json=JSON.stringify(pages).replaceAll('<','\\u003c');
 const output=`<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Expert WP — Aperçu hors ligne</title><style>html,body{margin:0;height:100%;overflow:hidden}iframe{border:0;width:100%;height:100%;display:block}</style></head><body><iframe id="site" title="Aperçu du site Expert WP"></iframe><script>
const pages=${json};const frame=document.getElementById('site');
function show(){var route=decodeURI(location.hash.slice(1)||'/');var key=route.split('?')[0];if(!key.endsWith('/'))key+='/';var service=new URLSearchParams(route.split('?')[1]||'').get('service')||'';if(!/^(creation|refonte|depannage|securite|maintenance|developpement)$/.test(service))service='';frame.srcdoc=(pages[key]||pages['/']).replaceAll('__OFFLINE_SERVICE__',service);}
window.addEventListener('message',function(e){if(e.source!==frame.contentWindow||e.data?.kind!=='expert-wp-offline-route')return;var route=e.data.route;if(typeof route==='string'&&route.startsWith('/')){if(location.hash.slice(1)===route)show();else location.hash=route;}});window.addEventListener('hashchange',show);show();
</script><noscript>Activez JavaScript dans votre navigateur pour consulter les pages de cette copie hors ligne.</noscript></body></html>`;
 fs.writeFileSync(path.join(__dirname,'../index.html'),output);console.log(JSON.stringify({pages:Object.keys(pages).length,bytes:Buffer.byteLength(output)}));
})();
