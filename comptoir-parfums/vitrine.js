/* ══════════════════════════════════════════════════════════════
   LE COMPTOIR DES PARFUMS — VITRINE, l'anneau de flacons en WebGL
   ══════════════════════════════════════════════════════════════

   Les photos produit sont posées en textures sur des plans disposés en arc
   (coverflow) : on en voit plusieurs à la fois, celle du centre de face, les
   voisines inclinées et estompées. Glisser fait tourner l'anneau (inertie +
   calage), cliquer le flacon central filtre le catalogue.

   POURQUOI CE FICHIER EXISTE, ET PAS UN BLOC DE theme.js
   La vitrine ne peut tourner que sur la page d'accueil : elle a besoin de
   three.js, que functions.php ne charge que là, et de #vitrine, qui n'existe
   que dans le héros. Restée dans theme.js, elle partait quand même sur
   chaque fiche parfum, sur la page commande et sur la 404 — du code qui ne
   s'exécutera jamais, téléchargé par une clientèle majoritairement en 4G.
   Elle a sa propre balise, mise en file avec three.js et sous la même
   condition.

   CONTRAT
   theme.js appelle CP_VITRINE une fois la page chargée et lui passe ce dont
   elle a besoin ; elle rend l'API que le ruban et le défilé automatique
   utilisent, ou null si elle ne peut pas démarrer (pas de WebGL, pas de
   #vitrine, moins de trois maisons en photo). Un null n'est pas une panne :
   le héros garde l'aperçu photo simple, exactement comme avant.

     entrées  MAISONS, maisonSlug(), maisonShotSrc(), mqTrack,
              markActive(), autoStop()
     sortie   { goTo(slug) }  ou  null

   Le garde « mouvement réduit » reste chez l'appelant : c'est lui qui décide
   de ne pas animer, ce fichier n'a pas à connaître la préférence système.
══════════════════════════════════════════════════════════════ */

window.CP_VITRINE = function (ctx) {
  const MAISONS       = ctx.MAISONS,
        maisonSlug    = ctx.maisonSlug,
        maisonShotSrc = ctx.maisonShotSrc,
        mqTrack       = ctx.mqTrack,
        markActive    = ctx.markActive,
        autoStop      = ctx.autoStop;

  const host = document.getElementById('vitrine');
  const T = window.THREE;
  if (!host || !T) return null;

  /* Une entree par MAISON, pas par parfum : MAISONS porte les noms dans
     l'ordre du catalogue. CAT contenait autrefois des groupes {brand, p[]}
     et la vitrine lisait b.brand ; depuis que CAT est la liste plate des
     parfums, b.brand valait undefined, DATA sortait vide et l'anneau
     ne s'initialisait plus du tout. */
  const DATA=MAISONS.map(function(nom){
    const slug=maisonSlug(nom);
    const src=slug?maisonShotSrc(slug):null;
    return src?{slug:slug,name:nom,src:src}:null;
  }).filter(Boolean);
  if(DATA.length<3) return null;

  let W=host.clientWidth||480, H=host.clientHeight||520;
  let renderer;
  try{ renderer=new T.WebGLRenderer({antialias:true,alpha:true,preserveDrawingBuffer:true}); }
  catch(e){ return null; }
  if(!renderer.getContext()) return null;
  renderer.setPixelRatio(Math.min(window.devicePixelRatio||1,2));
  renderer.setSize(W,H);
  if('outputEncoding' in renderer) renderer.outputEncoding=T.sRGBEncoding;
  host.insertBefore(renderer.domElement, host.firstChild);

  const scene=new T.Scene();
  const camera=new T.PerspectiveCamera(32,W/H,0.1,60);
  camera.position.set(0,0,6.2);

  const CARD=2.0, SPAN=3.4;                  /* cartes visibles de chaque côté */
  const loader=new T.TextureLoader();
  const maxAniso=renderer.capabilities&&renderer.capabilities.getMaxAnisotropy?renderer.capabilities.getMaxAnisotropy():1;
  const cards=DATA.map(function(d){
    const mat=new T.MeshBasicMaterial({transparent:true,opacity:0,depthWrite:false});
    const mesh=new T.Mesh(new T.PlaneGeometry(CARD,CARD),mat);
    mesh.visible=false; scene.add(mesh);
    return {d:d,mesh:mesh,mat:mat,ax:1,ay:1,loaded:false,loading:false};
  });
  /* Chargement à la demande : seules les cartes proches du centre
     téléchargent leur photo. Rien d'externe, tout vient d'img/produits/. */
  function ensureTexture(c){
    if(c.loaded||c.loading) return;
    c.loading=true;
    loader.load(c.d.src,function(t){
      if('encoding' in t) t.encoding=T.sRGBEncoding;
      t.anisotropy=Math.min(4,maxAniso);
      const im=t.image;
      if(im&&im.width&&im.height){
        const a=im.width/im.height;
        c.ax=a>=1?1:a; c.ay=a>=1?1/a:1;
      }
      c.mat.map=t; c.mat.needsUpdate=true; c.loaded=true;
    },undefined,function(){ c.loading=false; });
  }

  const n=cards.length;
  let pos=0, vel=0, seekTo=0, seeking=false, dragging=false,
      lastX=0, moved=0, raf=0, running=false;

  function wrap(p){ p=((p%n)+n)%n; return p>n/2?p-n:p; }
  function centreIndex(){ return ((Math.round(pos)%n)+n)%n; }

  function layout(){
    for(let i=0;i<n;i++){
      const c=cards[i], p=wrap(i-pos), ap=Math.abs(p);
      if(ap>SPAN){ c.mesh.visible=false; continue; }
      c.mesh.visible=true;
      ensureTexture(c);
      const dir=p<0?-1:(p>0?1:0);
      const s=1-Math.min(ap,SPAN)*0.13;
      c.mesh.position.set(dir*Math.pow(ap,0.86)*1.28, 0, -ap*0.95);
      c.mesh.rotation.y=-Math.max(-1,Math.min(1,p*0.42));
      c.mesh.scale.set(c.ax*s, c.ay*s, 1);
      c.mat.opacity=c.loaded?Math.max(0,1-ap*0.26):0;
      c.mesh.renderOrder=100-Math.round(ap*10);
    }
  }

  const capName=host.querySelector('.vitrine-maison');
  let lastCentre=-1;
  function caption(){
    const i=centreIndex();
    if(i===lastCentre) return;
    lastCentre=i;
    if(capName) capName.textContent=DATA[i].name;
    const link=mqTrack.querySelector('a.mq-item[href*="maison='+DATA[i].slug+'"]:not([aria-hidden])');
    if(link) markActive(link);
  }

  function frame(){
    raf=requestAnimationFrame(frame);
    if(!dragging){
      if(seeking){
        pos+=(seekTo-pos)*0.12;
        if(Math.abs(seekTo-pos)<0.002){ pos=seekTo; seeking=false; }
      } else if(Math.abs(vel)>0.0005){
        pos+=vel; vel*=0.93;
      } else {
        const t=Math.round(pos);
        pos+=(t-pos)*0.14;
        if(Math.abs(t-pos)<0.001) pos=t;
      }
    }
    layout(); caption();
    renderer.render(scene,camera);
  }
  function start(){ if(running) return; running=true; raf=requestAnimationFrame(frame); }
  function stop(){ running=false; cancelAnimationFrame(raf); }

  /* Toucher la vitrine arrête le défilé automatique, comme toucher le ruban :
     sinon le pas suivant écrase le geste de l'utilisateur 2,6 s plus tard. */
  ['pointerdown','touchstart','wheel'].forEach(function(ev){
    host.addEventListener(ev,autoStop,{passive:true});
  });

  /* ── Glisser pour tourner ── */
  host.addEventListener('pointerdown',function(e){
    dragging=true; moved=0; lastX=e.clientX; vel=0; seeking=false;
    host.classList.add('is-grabbing','has-moved');
    if(host.setPointerCapture) try{ host.setPointerCapture(e.pointerId); }catch(_){}
  });
  host.addEventListener('pointermove',function(e){
    if(!dragging) return;
    const dx=e.clientX-lastX; lastX=e.clientX;
    moved+=Math.abs(dx);
    const d=-dx/110;
    pos+=d; vel=d;
  });
  function release(e){
    if(!dragging) return;
    dragging=false;
    host.classList.remove('is-grabbing');
    if(moved<6) pick(e);                     /* geste court = clic */
  }
  host.addEventListener('pointerup',release);
  host.addEventListener('pointercancel',function(){ dragging=false; host.classList.remove('is-grabbing'); });

  /* ── Clic : le flacon central ouvre sa maison, un voisin vient au centre ── */
  const ray=new T.Raycaster(), ptr=new T.Vector2();
  function pick(e){
    const r=host.getBoundingClientRect();
    ptr.x=((e.clientX-r.left)/r.width)*2-1;
    ptr.y=-((e.clientY-r.top)/r.height)*2+1;
    ray.setFromCamera(ptr,camera);
    const hits=ray.intersectObjects(cards.filter(c=>c.mesh.visible).map(c=>c.mesh));
    if(!hits.length) return;
    const idx=cards.findIndex(c=>c.mesh===hits[0].object);
    if(idx<0) return;
    if(idx===centreIndex()) location.hash='#catalogue?maison='+DATA[idx].slug;
    else goIndex(idx);
  }
  function goIndex(i){ seekTo=pos+wrap(i-pos); seeking=true; vel=0; }

  /* ── API utilisée par le ruban et le défilé automatique ── */
  const api={
    goTo:function(slug){
      const i=DATA.findIndex(function(d){ return d.slug===slug; });
      if(i<0) return false;
      host.classList.add('has-moved');
      goIndex(i);
      return true;
    }
  };

  /* Ne tourne que quand le héros est visible. */
  const heroSec=document.getElementById('hero');
  if(heroSec&&'IntersectionObserver' in window){
    new IntersectionObserver(function(en){
      en.forEach(function(x){ x.isIntersecting?start():stop(); });
    },{threshold:0.05}).observe(heroSec);
  } else start();
  document.addEventListener('visibilitychange',function(){ document.hidden?stop():start(); });

  let rz=0;
  window.addEventListener('resize',function(){
    clearTimeout(rz);
    rz=setTimeout(function(){
      W=host.clientWidth||480; H=host.clientHeight||520;
      camera.aspect=W/H; camera.updateProjectionMatrix(); renderer.setSize(W,H);
      layout(); renderer.render(scene,camera);
    },160);
  });

  return api;
};
