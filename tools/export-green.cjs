/* Separate colour study: never overwrites the original blue export. */
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict'),crypto=require('node:crypto'),vm=require('node:vm');
const source=path.join(__dirname,'../index.html');const original=fs.readFileSync(source,'utf8');
const hash=crypto.createHash('sha256').update(original).digest('hex');
const match=original.match(/const pages=(.*);const frame=/);assert.ok(match);
const pages=JSON.parse(match[1]);
function recolour(css){return css.replace(/#([0-9a-f]{6}|[0-9a-f]{3})([0-9a-f]{2})?\b/gi,(full,hex,alpha='')=>{
 if(hex.length===3)hex=hex.split('').map(c=>c+c).join('');
 const [r,g,b]=[0,2,4].map(i=>parseInt(hex.slice(i,i+2),16));
 if(!(b>r*1.09&&b>g*1.025))return full;
 const brightness=(r+g+b)/3;
 const colour=brightness>225?'f1f5e9':brightness>165?'dce8c0':brightness>115?'81936d':brightness>65?'365128':'101410';
 return '#'+colour+alpha;
 });}
const override=`
:root{--wp--preset--color--primary:#315b20;--wp--preset--color--contrast:#111310}
body{color:#161914;background:#fff}a{color:#315b20}
.site-header{background:#fafbf6}.site-header .wp-block-site-title a,.site-header .wp-block-navigation a{color:#111310}
.hero{background:#0e100d;color:#fff}.hero h1 em{color:#ddff56}.hero .eyebrow,.hero a:not(.wp-block-button__link){color:#ddff56}.hero .intro,.hero-footnote,.visual-caption{color:#c4cdbb}
.wp-block-button__link,.nav-cta a,.ewp-form button[type=submit]{background:#ddff56!important;color:#10130b!important}
.hero .wp-block-button.is-style-outline>.wp-block-button__link{background:transparent!important;color:#ddff56!important;border-color:#ddff56}
.blueprint{background:radial-gradient(circle,#ddff5625,transparent 66%),linear-gradient(#ddff560c 1px,transparent 1px),linear-gradient(90deg,#ddff560c 1px,transparent 1px);background-size:auto,36px 36px,36px 36px;border-color:#ddff5625}
.blueprint-core{background:linear-gradient(135deg,#e8ff8c,#b9dc30);color:#111;box-shadow:0 0 90px #ddff5625}.blueprint-tag{background:#1c2418;color:#edf4e1;border-color:#53643b}.blueprint-orbit{border-color:#9cae6b66}
.site-header .wp-block-site-title a:before{background-color:#ddff56!important;background-image:none!important;color:#111!important}
.eyebrow,.service-index,.step-number{color:#315b20!important}.hero .eyebrow{color:#ddff56!important}
.service-card:hover{border-color:#7e9b2a}.expertise-icon,.expertise-other .expertise-icon,.expertise-woocommerce .expertise-icon{background:#42642d!important}
.expertise-other,.method,.contact-project{background:#f0f5e5!important}.callout{background:#ddff56;color:#111}.callout h2,.callout p,.callout .eyebrow{color:#19210c!important}.callout .wp-block-button__link{background:#111!important;color:#ddff56!important}
.site-footer,.contact-emergency{background:#10130e;color:#e5ecdc}.site-footer a,.contact-emergency a,.contact-emergency h2{color:#fff}.contact-emergency .eyebrow{color:#ddff56!important}
.contact-phone a{color:#315b20}.contact-emergency .contact-phone a{color:#ddff56}.offline-note{background:#edf5d8!important;color:#29341b!important;border-color:#d5e0bc!important}
.site-header .wp-block-navigation__submenu-container,.site-header nav.wp-block-navigation .is-menu-open .wp-block-navigation__submenu-container,.site-header .wp-block-navigation__container > li.services-mega > ul{background:#10130e!important;border-color:#39432d!important}
.site-header .wp-block-navigation__submenu-container a{color:#f6faef!important}.site-header .wp-block-navigation__submenu-container a:hover{background:#28331d!important}.site-header .service-menu-entry .wp-block-navigation-item__description,.site-header .service-menu-heading{color:#c1cbb5!important}.site-header li.services-mega .service-menu-icon,.service-menu-icon{background:#ddff56}
:focus-visible{outline-color:#698b00}
`;
for(const route of Object.keys(pages))pages[route]=pages[route].replace(/<style\b([^>]*)>([\s\S]*?)<\/style>/gi,(_,a,css)=>'<style'+a+'>'+recolour(css)+'</style>').replace('</head>','<style>'+override+'</style></head>');
const json=JSON.stringify(pages).replaceAll('<','\\u003c');
const output=original.replace(match[0],'const pages='+json+';const frame=').replace('<title>Les Experts Wordpress — Aperçu hors ligne</title>','<title>Les Experts Wordpress — Version vert et noir</title>');
for(const page of Object.values(pages))for(const script of page.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/gi))new vm.Script(script[1]);
assert.equal(crypto.createHash('sha256').update(fs.readFileSync(source)).digest('hex'),hash);
fs.writeFileSync(path.join(__dirname,'../index-vert-noir.html'),output);
console.log(JSON.stringify({pages:Object.keys(pages).length,blueOriginalUnchanged:true,bytes:Buffer.byteLength(output)}));
