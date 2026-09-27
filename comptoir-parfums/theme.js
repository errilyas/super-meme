/* ══════════════════════════════════════════════════════════════
   LE COMPTOIR DES PARFUMS — SCRIPT DU SITE
   Source unique, partagee par la maquette et le theme WordPress :
     - comptoirv3-motion.html  (maquette de reference)
     - front-page.php          (meme design, catalogue rendu par PHP)

   Ce fichier etait auparavant recopie en trois blocs <script> inline dans
   la maquette ; le sortir evite d'avoir a le maintenir en double.

   A CHARGER APRES : three.js, gsap, ScrollTrigger, panier.js.
   Sans GSAP, fallbackMode() prend le relais : le site reste utilisable.

   Plan :
     I.   Flacon 3D + vitrine WebGL du heros
     II.  Interface (§0 a §18 : catalogue, FAQ, reveals, nav, marquee…)
     III. Bascule de theme clair / sombre
══════════════════════════════════════════════════════════════ */

/* ══════════════════════════════════════════════════════════════
   I. FLACON 3D — sceau « CP » en WebGL dans le heros,
      et VITRINE — anneau de flacons pilote par le ruban de maisons.
══════════════════════════════════════════════════════════════ */
window.addEventListener('load', function(){
(function(){
  'use strict';
  var THREE = window.THREE;
  var host  = document.getElementById('flacon3d');
  if(!host) return;
  var REDUCED = matchMedia('(prefers-reduced-motion:reduce)').matches;
  var MOBILE  = window.innerWidth < 760;

  if(!THREE){ host.classList.add('is-fallback'); return; }

  var W = host.clientWidth || 300, H = host.clientHeight || 520;

  var renderer;
  try{
    renderer = new THREE.WebGLRenderer({ antialias:true, alpha:true, preserveDrawingBuffer:true });
  }catch(e){ host.classList.add('is-fallback'); return; }
  if(!renderer.getContext()){ host.classList.add('is-fallback'); return; }
  renderer.setPixelRatio(Math.min(window.devicePixelRatio||1, MOBILE ? 1.75 : 2));
  renderer.setSize(W, H);
  renderer.outputEncoding = THREE.sRGBEncoding;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 0.92;
  host.appendChild(renderer.domElement);

  var scene  = new THREE.Scene();
  var camera = new THREE.PerspectiveCamera(26, W/H, 0.1, 100);
  camera.position.set(0, 0.10, 7.7);
  camera.lookAt(0, 0.04, 0);

  /* environnement procédural : dégradé aubergine → or → crème, matière à refléter */
  (function(){
    var c = document.createElement('canvas'); c.width = 32; c.height = 128;
    var ctx = c.getContext('2d');
    var g = ctx.createLinearGradient(0,0,0,128);
    g.addColorStop(0.00,'#e9e2d4'); g.addColorStop(0.30,'#8a7c66');
    g.addColorStop(0.55,'#2a2622'); g.addColorStop(0.78,'#100e0c'); g.addColorStop(1.00,'#050403');
    ctx.fillStyle = g; ctx.fillRect(0,0,32,128);
    ctx.fillStyle = 'rgba(255,248,232,.75)'; ctx.fillRect(5,16,6,66);
    ctx.fillStyle = 'rgba(230,192,120,.5)';  ctx.fillRect(23,40,3,40);
    var tex = new THREE.CanvasTexture(c);
    tex.mapping = THREE.EquirectangularReflectionMapping;
    tex.encoding = THREE.sRGBEncoding;
    var pmrem = new THREE.PMREMGenerator(renderer);
    scene.environment = pmrem.fromEquirectangular(tex).texture;
    tex.dispose(); pmrem.dispose();
  })();

  /* matières */
  var glass = new THREE.MeshPhysicalMaterial({
    color:0x050505, metalness:0.05, roughness:0.03,
    transmission:0.06, thickness:3.6, ior:1.54,
    clearcoat:1, clearcoatRoughness:0.03,
    attenuationColor:new THREE.Color(0x020202), attenuationDistance:0.25,
    envMapIntensity:1.0, transparent:true, opacity:1
  });
  var liquid = new THREE.MeshPhysicalMaterial({
    color:0x060409, metalness:0, roughness:0.2,
    transmission:0.12, thickness:3.4, ior:1.45,
    attenuationColor:new THREE.Color(0x030206), attenuationDistance:0.35,
    clearcoat:0.7, envMapIntensity:1.2, transparent:true, opacity:1
  });
  var goldBright = new THREE.MeshStandardMaterial({ color:0xe9c37a, metalness:1, roughness:0.18, envMapIntensity:1.5 });
  var goldDeep   = new THREE.MeshStandardMaterial({ color:0xb07f38, metalness:1, roughness:0.34, envMapIntensity:1.3 });

  var rig = new THREE.Group(); scene.add(rig);

  var bw = 1.28, bh = 1.9, ch = 0.30;
  function facetShape(w,h,cut){
    var x=w/2, y=h/2, s=new THREE.Shape();
    s.moveTo(-x+cut,-y); s.lineTo(x-cut,-y); s.lineTo(x,-y+cut); s.lineTo(x,y-cut);
    s.lineTo(x-cut,y);   s.lineTo(-x+cut,y); s.lineTo(-x,y-cut); s.lineTo(-x,-y+cut); s.lineTo(-x+cut,-y);
    return s;
  }
  var bodyGeo = new THREE.ExtrudeGeometry(facetShape(bw,bh,ch), { depth:0.74, bevelEnabled:true, bevelThickness:0.09, bevelSize:0.09, bevelSegments:4, curveSegments:2 });
  bodyGeo.center();
  rig.add(new THREE.Mesh(bodyGeo, glass));

  var liqGeo = new THREE.ExtrudeGeometry(facetShape(bw-0.16, bh-0.14, ch-0.03), { depth:0.5, bevelEnabled:true, bevelThickness:0.04, bevelSize:0.04, bevelSegments:3, curveSegments:2 });
  liqGeo.center();
  var liq = new THREE.Mesh(liqGeo, liquid);
  liq.scale.y = 0.6; liq.position.y = -bh*0.5 + (bh*0.6)/2 + 0.02; rig.add(liq);

  var neck = new THREE.Mesh(new THREE.CylinderGeometry(0.24, 0.30, 0.22, 24), goldDeep);
  neck.position.y = bh/2 + 0.02; rig.add(neck);

  var capBand = new THREE.Mesh(new THREE.BoxGeometry(bw*0.86, 0.30, 0.66), goldBright);
  capBand.position.y = bh/2 + 0.37; rig.add(capBand);
  var capTop = new THREE.Mesh(new THREE.BoxGeometry(bw*0.58, 0.26, 0.46), goldDeep);
  capTop.position.y = capBand.position.y + 0.28; rig.add(capTop);
  [capBand, capTop].forEach(function(m){
    var e = new THREE.LineSegments(new THREE.EdgesGeometry(m.geometry), new THREE.LineBasicMaterial({ color:0xf3e0b0, transparent:true, opacity:0.5 }));
    e.position.copy(m.position); rig.add(e);
  });

  /* monogramme CP en relief, coulé dans l'or, sur la face avant */
  var mono = new THREE.Group();
  var faceZ = 0.74/2 + 0.09;
  var med = new THREE.Mesh(new THREE.CylinderGeometry(0.56, 0.56, 0.07, 56), goldDeep);
  med.rotation.x = Math.PI/2; med.position.z = faceZ + 0.005; mono.add(med);
  var medRim = new THREE.Mesh(new THREE.TorusGeometry(0.55, 0.022, 12, 64), goldBright); medRim.position.z = faceZ + 0.04; mono.add(medRim);
  var medBead = new THREE.Mesh(new THREE.TorusGeometry(0.45, 0.006, 8, 96), goldBright); medBead.position.z = faceZ + 0.05; mono.add(medBead);
  (function(){
    var cv = document.createElement('canvas'); cv.width = cv.height = 256;
    var c = cv.getContext('2d');
    function paint(){
      c.clearRect(0,0,256,256);
      var grd = c.createLinearGradient(0,60,0,200);
      grd.addColorStop(0,'#f6e3b0'); grd.addColorStop(.5,'#e2b877'); grd.addColorStop(1,'#b98f43');
      c.fillStyle = grd;
      c.font = "italic 500 150px 'Cormorant Garamond', Georgia, serif";
      c.textAlign = 'center'; c.textBaseline = 'middle';
      c.fillText('CP', 132, 140);
    }
    paint();
    var tex = new THREE.CanvasTexture(cv); tex.anisotropy = 4; tex.encoding = THREE.sRGBEncoding;
    var plaque = new THREE.Mesh(new THREE.PlaneGeometry(0.86, 0.86), new THREE.MeshBasicMaterial({ map:tex, transparent:true }));
    plaque.position.z = faceZ + 0.085; mono.add(plaque);
    var shadow = plaque.clone();
    shadow.material = new THREE.MeshBasicMaterial({ map:tex, transparent:true, color:0x2a1a08, opacity:0.5 });
    shadow.position.z = faceZ + 0.07; shadow.position.x = 0.012; shadow.position.y = -0.012; mono.add(shadow);
    if(document.fonts && document.fonts.ready) document.fonts.ready.then(function(){ paint(); tex.needsUpdate = true; render1(); });
  })();
  mono.position.y = 0.06; rig.add(mono);

  /* brume dorée montant du col (les anneaux en orbite restent gérés en CSS autour de la scène) */
  var COUNT = MOBILE ? 42 : 90, mist = null, pg = null, spd = null;
  if(!REDUCED){
    pg = new THREE.BufferGeometry();
    var pos = new Float32Array(COUNT*3); spd = new Float32Array(COUNT);
    for(var i=0;i<COUNT;i++){
      var a = Math.random()*Math.PI*2, r = Math.random()*0.22;
      pos[i*3] = Math.cos(a)*r; pos[i*3+1] = bh/2 + Math.random()*2.2; pos[i*3+2] = Math.sin(a)*r + 0.2;
      spd[i] = 0.15 + Math.random()*0.3;
    }
    pg.setAttribute('position', new THREE.BufferAttribute(pos,3));
    mist = new THREE.Points(pg, new THREE.PointsMaterial({ color:0xe4bd7a, size:0.032, transparent:true, opacity:0.4, blending:THREE.AdditiveBlending, depthWrite:false }));
    scene.add(mist);
  }

  /* lumières : clé or, contre-jour crème, remplissage rouge maison, liserés dorés */
  scene.add(new THREE.AmbientLight(0x2b2622, 0.5));
  scene.add(new THREE.HemisphereLight(0xf3e6cf, 0x1c1712, 0.35));
  var key = new THREE.PointLight(0xffd9a0, 1.6, 30); key.position.set(4.0, 2.4, 3.4); scene.add(key);
  var rim = new THREE.PointLight(0xfff2e0, 1.5, 30); rim.position.set(-3.6, 1.8, -3.4); scene.add(rim);
  var fill = new THREE.PointLight(0x7f202d, 0.45, 22); fill.position.set(-1.4, -2.6, 2.6); scene.add(fill);
  var edge = new THREE.PointLight(0xf0c882, 1.7, 26); edge.position.set(-2.9, 0.4, 3.0); scene.add(edge);
  var edge2 = new THREE.PointLight(0xffe9c4, 0.9, 24); edge2.position.set(2.4, -1.2, 3.2); scene.add(edge2);
  var top = new THREE.SpotLight(0xffe6bf, 0.85, 20, 0.7, 0.6, 1.2); top.position.set(1.2, 5.5, 2.2); top.target = rig; scene.add(top);

  /* interaction : glisser pour tourner + parallaxe douce */
  var REST_Y = -0.16, REST_X = -0.04;
  var targetRot = { x:REST_X, y:REST_Y }, curRot = { x:REST_X, y:REST_Y };
  var dragging = false, lastX = 0, lastY = 0, freeUntil = 0;
  function down(e){ dragging = true; var p = e.touches ? e.touches[0] : e; lastX = p.clientX; lastY = p.clientY; }
  function move(e){
    if(!dragging) return;
    var p = e.touches ? e.touches[0] : e;
    targetRot.y += (p.clientX - lastX)*0.008;
    targetRot.x += (p.clientY - lastY)*0.006;
    targetRot.x = Math.max(-0.6, Math.min(0.6, targetRot.x));
    lastX = p.clientX; lastY = p.clientY;
  }
  function up(){ dragging = false; freeUntil = performance.now() + 2600; }
  host.style.cursor = 'grab';
  host.addEventListener('mousedown', down); window.addEventListener('mousemove', move); window.addEventListener('mouseup', up);
  host.addEventListener('touchstart', down, {passive:true}); window.addEventListener('touchmove', move, {passive:true}); window.addEventListener('touchend', up);
  var px = 0, py = 0;
  if(!MOBILE) window.addEventListener('mousemove', function(e){ px = e.clientX/window.innerWidth - 0.5; py = e.clientY/window.innerHeight - 0.5; });

  function render1(){ renderer.render(scene, camera); }

  /* boucle — suspendue hors écran */
  var t0 = performance.now(), raf = 0, running = false;
  function tick(now){
    raf = requestAnimationFrame(tick);
    var t = (now - t0)/1000;
    /* au repos : léger balancement — le monogramme « CP » reste toujours lisible */
    if(!dragging && !REDUCED && now > freeUntil){
      targetRot.y = REST_Y + Math.sin(t*0.30)*0.40;
      targetRot.x = REST_X + Math.sin(t*0.23)*0.05;
    }
    curRot.y += (targetRot.y - curRot.y)*0.07;
    curRot.x += (targetRot.x - curRot.x)*0.07;
    rig.rotation.y = curRot.y + (dragging ? 0 : px*0.22);
    rig.rotation.x = curRot.x + (dragging ? 0 : py*0.14);
    rig.position.y = Math.sin(t*0.6)*0.03;
    if(mist){
      var p = pg.attributes.position.array;
      for(var i=0;i<COUNT;i++){
        p[i*3+1] += spd[i]*0.010;
        p[i*3]   += Math.sin(t*0.7 + i)*0.0006;
        if(p[i*3+1] > bh/2 + 2.6){ p[i*3+1] = bh/2; }
      }
      pg.attributes.position.needsUpdate = true;
      mist.material.opacity = 0.22 + Math.sin(t*0.9)*0.12;
    }
    key.intensity = 1.6 + Math.sin(t*1.3)*0.22;
    render1();
  }
  /* Suspendu aussi quand une photo « vitrine » recouvre le logo 3D. */
  var heroVis = document.querySelector('.hero-visual');
  function shotHidesIt(){ return heroVis && heroVis.classList.contains('shot-on'); }
  function start(){ if(running || shotHidesIt()) return; running = true; t0 = performance.now(); raf = requestAnimationFrame(tick); }
  function stop(){ running = false; cancelAnimationFrame(raf); render1(); }

  if(REDUCED){
    render1();
  }else{
    start();
    var hero = document.getElementById('hero');
    if(hero && 'IntersectionObserver' in window){
      new IntersectionObserver(function(en){
        en.forEach(function(x){ x.isIntersecting && !shotHidesIt() ? start() : stop(); });
      }, { threshold:0.01 }).observe(hero);
    }
    document.addEventListener('visibilitychange', function(){ document.hidden ? stop() : start(); });
    if(heroVis && 'MutationObserver' in window){
      new MutationObserver(function(){
        shotHidesIt() ? stop() : (document.hidden ? 0 : start());
      }).observe(heroVis, { attributes:true, attributeFilter:['class'] });
    }
  }

  /* redimension */
  var rzt = 0;
  window.addEventListener('resize', function(){
    clearTimeout(rzt);
    rzt = setTimeout(function(){
      W = host.clientWidth || 300; H = host.clientHeight || 520;
      camera.aspect = W/H; camera.updateProjectionMatrix();
      renderer.setSize(W, H);
      render1();
    }, 160);
  });
})();
});

/* ══════════════════════════════════════════════════════════════
   II. INTERFACE — catalogue, FAQ, reveals au defilement, navigation,
       ruban de maisons, onglets, curseur, prechargeur.
══════════════════════════════════════════════════════════════ */
(function(){
'use strict';

/* ══════════════════════════════════════════════════════════════
   §0  CONFIGURATION & FEATURE DETECTION
══════════════════════════════════════════════════════════════ */
const REDUCED  = matchMedia('(prefers-reduced-motion:reduce)').matches;
const HAS_HOVER = matchMedia('(hover:hover)').matches;
const IS_MOBILE = window.innerWidth < 860;

/* ── Memoire de session, tolerante ──────────────────────────────
   sessionStorage LEVE une exception quand le stockage est bloque : Safari en
   navigation privee, navigateur configure pour refuser les cookies, certaines
   webviews. Les appels nus se trouvaient au niveau superieur de ce bloc, donc
   la moindre levee emportait tout : prechargeur jamais retire, aucune section
   revelee, ecran noir. On encapsule une fois pour toutes.
   Perdre la memoire de l'intro est sans consequence — elle rejoue, voila tout. */
function memLire(cle){
  try{ return sessionStorage.getItem(cle); }catch(e){ return null; }
}
function memEcrire(cle,val){
  try{ sessionStorage.setItem(cle,val); }catch(e){}
}

/* Le prechargeur est une mise en scene : il n'a de sens que pour qui arrive
   par la porte d'entree. Il dure 3,2 s et memLire() s'appuie sur
   sessionStorage, donc un visiteur venu d'une publicite le subirait a chaque
   session, alors qu'il a deja attendu le reseau et qu'il vient pour un flacon,
   pas pour un rideau. Meme chose pour un lien qui pointe droit sur une maison.

   Seul endroit ou cette decision se prend : deux tests concurrents ont
   coexiste un temps, l'un ici, l'autre sur alreadyPlayed. */
/* Sur telephone, il saute toujours : la clientele arrive en 4G, souvent
   depuis Instagram ou WhatsApp, et 3,2 s d'ecran noir avant le premier
   flacon est le moment ou l'on perd le plus de visiteurs. Il reste joue sur
   grand ecran, ou il a le temps et la place d'etre vu. */
function cpSautePrechargeur(){
  try{
    if(window.matchMedia && window.matchMedia('(max-width: 820px)').matches) return true;
    return /[?&](fbclid|gclid|ttclid|utm_source|utm_medium|utm_campaign|maison)=/.test(location.search);
  }
  catch(e){ return false; }
}

/* ── GSAP guard ── */

/* ══════════════════════════════════════════════════════════════
   CONTENU — construit AVANT le garde-fou GSAP.
   Le catalogue et la FAQ sont le produit : ils ne doivent jamais
   dependre du chargement d'un CDN tiers.
══════════════════════════════════════════════════════════════ */
function animOpen(el){
  if(!el) return;
  if(window.gsap){ gsap.to(el,{height:'auto',duration:.42,ease:'power2.out'}); }
  else { el.style.height = el.scrollHeight + 'px'; }
}
function animClose(el){
  if(!el) return;
  if(window.gsap){ gsap.to(el,{height:0,duration:.38,ease:'power2.inOut'}); }
  else { el.style.height = '0px'; }
}
/* Nom de fichier stable et prévisible : maison--reference.webp
   Accents retirés, minuscules, tout le reste en tirets. */
function slugify(str){
  return String(str)
    .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
    .toLowerCase()
    .replace(/['’]/g,'')
    .replace(/[^a-z0-9]+/g,'-')
    .replace(/^-+|-+$/g,'');
}
/* Vignette produit — silhouette de flacon dorée sur fond sombre, en data URI :
   elle est posée en src DÈS LE DÉPART, ne déclenche aucune requête et ne peut
   pas échouer. Les cartes dont la photo existe reçoivent directement leur src,
   avec loading="lazy" : le navigateur ne télécharge que ce qui approche de
   l'écran. Aucun <img> ne pointe vers un fichier absent, donc aucun 404 et
   jamais le glyphe d'image cassée. */
/* Transparent : la case (fond + silhouette dorée) est peinte par
   .pc-img.is-empty en CSS, donc thématisée clair/sombre. L'<img> ne sert que
   d'emplacement stable dans la grille. */
const THUMB_FALLBACK="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='56' height='56'%3E%3C/svg%3E";
/* Photos produit : manifeste des fichiers réellement présents dans img/produits/
   (nommage : maison--reference.webp, voir slugify). Seules ces vignettes sont
   chargées ; les autres gardent leur silhouette dorée et ne déclenchent AUCUNE
   requête — donc pas de 404 dans la console. Pour ajouter une photo : déposer
   le .webp dans img/produits/ puis ajouter son nom de fichier ici. */
const PRODUCT_IMAGES = new Set(window.CP_PRODUCT_IMAGES || [
  "azzaro--forever-wanted-elixir.webp",
  "burberry--goddess.webp",
  "burberry--her-intense.webp",
  "chanel--allure-homme-sport.webp",
  "chanel--bleu-de-chanel-lexclusif.webp",
  "chanel--bleu-de-chanel-parfum.webp",
  "chanel--chance-eau-fraiche.webp",
  "chanel--chance-eau-tendre.webp",
  "chanel--coco-mademoiselle.webp",
  "chanel--coco-noir.webp",
  "chanel--n-5-eau-de-parfum.webp",
  "chloe--love-story.webp",
  "creed--absolu-aventus.webp",
  "creed--aventus.webp",
  "dior--ambre-nuit-esprit-de-parfum.webp",
  "dior--dior-homme-intense.webp",
  "dior--fahrenheit-le-parfum.webp",
  "dior--hypnotic-poison-edp.webp",
  "dior--joy-by-dior-intense.webp",
  "dior--miss-dior-eau-de-parfum.webp",
  "dior--miss-dior-rose-essence.webp",
  "dior--oud-ispahan-esprit-de-parfum.webp",
  "dior--sauvage-eau-de-parfum.webp",
  "dior--sauvage-elixir.webp",
  "dior--sauvage-parfum.webp",
  "dolce-gabbana--devotion-pour-homme.webp",
  "dolce-gabbana--king-edt.webp",
  "dolce-gabbana--limperatrice-royale.webp",
  "dolce-gabbana--light-blue-capri-in-love.webp",
  "dolce-gabbana--light-blue-eau-de-toilette.webp",
  "dolce-gabbana--my-devotion-edp-intense.webp",
  "dolce-gabbana--q-by-dolce-gabbana.webp",
  "emporio-armani--stronger-with-you-absolutely.webp",
  "emporio-armani--stronger-with-you-amber-edp.webp",
  "emporio-armani--stronger-with-you-intensely.webp",
  "emporio-armani--stronger-with-you-oud-edp.webp",
  "emporio-armani--stronger-with-you-parfum.webp",
  "emporio-armani--stronger-with-you-sandalwood.webp",
  "emporio-armani--stronger-with-you-tobacco.webp",
  "giardini-di-toscana--bianco-latte.webp",
  "giorgio-armani--acqua-di-gio-edp.webp",
  "giorgio-armani--armani-prive-bleu-lazuli-edp.webp",
  "giorgio-armani--armani-prive-rouge-malachite.webp",
  "giorgio-armani--my-way-edp.webp",
  "giorgio-armani--my-way-intense.webp",
  "giorgio-armani--my-way-ylang.webp",
  "giorgio-armani--si-fiori.webp",
  "giorgio-armani--si-parfum.webp",
  "giorgio-armani--si-passione.webp",
  "giorgio-armani--si-passione-eclat-de-parfum.webp",
  "givenchy--gentleman-reserve-privee.webp",
  "givenchy--irresistible-edt.webp",
  "givenchy--linterdit-absolu-edp-intense.webp",
  "givenchy--linterdit-edp-rouge.webp",
  "givenchy--linterdit-edp-rouge-ultime.webp",
  "givenchy--linterdit-tubereuse-noire.webp",
  "gucci--flora-gorgeous-gardenia.webp",
  "gucci--flora-gorgeous-gardenia-intense.webp",
  "gucci--flora-gorgeous-jasmine.webp",
  "gucci--flora-gorgeous-magnolia.webp",
  "gucci--gucci-bloom-intense.webp",
  "gucci--moonlight-serenade.webp",
  "guerlain--habit-rouge-rouge-prive.webp",
  "guerlain--la-petite-robe-noire-edp.webp",
  "guerlain--mon-guerlain.webp",
  "hermes--terre-dhermes-edp-intense.webp",
  "hugo-boss--boss-bottled-elixir.webp",
  "hugo-boss--boss-bottled-unlimited.webp",
  "hugo-boss--bottled.webp",
  "hugo-boss--bottled-night.webp",
  "jean-paul-gaultier--gaultier-divine-edp.webp",
  "jean-paul-gaultier--la-belle-le-parfum.webp",
  "jean-paul-gaultier--le-beau-le-parfum.webp",
  "jean-paul-gaultier--le-male-elixir.webp",
  "jean-paul-gaultier--le-male-elixir-absolu.webp",
  "jean-paul-gaultier--le-male-le-parfum.webp",
  "jean-paul-gaultier--le-male-lover.webp",
  "jean-paul-gaultier--scandal-edp-pour-femme.webp",
  "jean-paul-gaultier--scandal-le-parfum.webp",
  "jean-paul-gaultier--scandal-pour-homme-absolu.webp",
  "jean-paul-gaultier--scandal-pour-homme-intense.webp",
  "kayali--capri-lemon-sugar-14.webp",
  "kayali--deja-vu-white-flower-57.webp",
  "kayali--eden-juicy-apple-01.webp",
  "kayali--eden-sparkling-lychee-39.webp",
  "kayali--lovefest-burning-cherry-48.webp",
  "kayali--marrakesh-orange-blossom-24.webp",
  "kayali--oudgasm-rose-oud-16-intense.webp",
  "kayali--vanilla-28.webp",
  "kayali--vanilla-candy-rock-sugar-42.webp",
  "kayali--yum-boujee-marshmallow-81.webp",
  "kayali--yum-pistachio-gelato-33.webp",
  "kayali--utopia-vanilla-coco-21.webp",
  "lacoste--booster-edt.webp",
  "lacoste--essential-edt.webp",
  "lancome--idole-lintense.webp",
  "lancome--la-nuit-tresor-le-parfum.webp",
  "lancome--la-vie-est-belle-lextrait.webp",
  "lancome--tresor-in-love-edp.webp",
  "louis-vuitton--imagination.webp",
  "maison-francis-kurkdjian--baccarat-rouge-540.webp",
  "maison-francis-kurkdjian--oud-silk-mood-extrait-de-parfum.webp",
  "moschino--toy-2-bubble-gum.webp",
  "moschino--toy-boy.webp",
  "narciso-rodriguez--fleur-musc-for-her.webp",
  "narciso-rodriguez--for-her.webp",
  "narciso-rodriguez--for-her-edp.webp",
  "narciso-rodriguez--musc-noir-for-her.webp",
  "narciso-rodriguez--musc-noir-rose-for-her.webp",
  "narciso-rodriguez--narciso-edp-poudree.webp",
  "narciso-rodriguez--narciso-edp-rouge.webp",
  "nishane--hacivat.webp",
  "parfums-de-marly--delina-exclusif.webp",
  "parfums-de-marly--layton.webp",
  "parfums-de-marly--oriana-royal-essence.webp",
  "parfums-de-marly--palatine.webp",
  "prada--carbon-luna-rossa-edt.webp",
  "prada--paradigme.webp",
  "prada--paradoxe-intense.webp",
  "prada--paradoxe-radical-essence.webp",
  "rabanne--1-million-parfum.webp",
  "rabanne--1-million-royal.webp",
  "stephane-humbert-lucas-777--god-of-fire.webp",
  "tom-ford--bitter-peach.webp",
  "tom-ford--black-orchid.webp",
  "tom-ford--fabulous.webp",
  "tom-ford--lost-cherry.webp",
  "tom-ford--neroli-portofino.webp",
  "tom-ford--oud-minerale.webp",
  "tom-ford--oud-wood.webp",
  "tom-ford--rose-de-russie.webp",
  "tom-ford--rose-prick-edp.webp",
  "tom-ford--tobacco-vanille.webp",
  "tom-ford--vanilla-sex.webp",
  "valentino--donna-born-in-roma-intense.webp",
  "valentino--uomo-born-in-roma-green-stravaganza.webp",
  "valentino--uomo-born-in-roma-intense.webp",
  "valentino--uomo-intense.webp",
  "versace--bright-crystal.webp",
  "versace--crystal-noir.webp",
  "versace--eros-edp.webp",
  "versace--eros-energy-pour-homme-edp.webp",
  "versace--eros-flame.webp",
  "versace--man-eau-fraiche-extreme.webp",
  "victorias-secret--bombshell.webp",
  "victorias-secret--bombshell-seduction-edp.webp",
  "viktor-rolf--spicebomb-extreme.webp",
  "xerjoff--accento.webp",
  "xerjoff--erba-pura.webp",
  "xerjoff--naxos.webp",
  "xerjoff--perseveranza.webp",
  "xerjoff--wardasina.webp",
  "yves-saint-laurent--babycat-raw-bourbon.webp",
  "yves-saint-laurent--black-opium-glitter.webp",
  "yves-saint-laurent--black-opium-over-red.webp",
  "yves-saint-laurent--la-nuit-de-lhomme-bleu-electrique.webp",
  "yves-saint-laurent--la-nuit-de-lhomme-edp.webp",
  "yves-saint-laurent--la-nuit-de-lhomme-le-parfum.webp",
  "yves-saint-laurent--libre-edp-intense.webp",
  "yves-saint-laurent--libre-labsolu-platine.webp",
  "yves-saint-laurent--libre-leau-nue.webp",
  "yves-saint-laurent--libre-le-parfum.webp",
  "yves-saint-laurent--mon-paris.webp",
  "yves-saint-laurent--myslf-edp.webp",
  "yves-saint-laurent--myslf-labsolu.webp",
  "yves-saint-laurent--myslf-le-parfum.webp",
  "yves-saint-laurent--y-edp.webp",
  "yves-saint-laurent--y-eau-fraiche.webp",
  "yves-saint-laurent--y-le-parfum.webp",
  "zadig-voltaire--this-is-her.webp"
]);
/* ══════════════════════════════════════════════════════════════
   §14  CATALOGUE — GRILLE + FILTRES
   L'accordéon par maison est remplacé par une grille de cartes photo.
   Le balisage peut venir de deux endroits : construit ici (maquette) ou
   rendu par PHP (thème WordPress). Dans les deux cas, le filtrage ci-dessous
   travaille sur le DOM déjà en place — une seule logique pour les deux.
══════════════════════════════════════════════════════════════ */

/* Grande famille olfactive : le premier mot. Même règle que
   comptoir_famille_principale() en PHP. */
/* La liste des photos réellement présentes est republiée sur window : panier.js
   et commande.js s'en servent pour ne PAS demander une photo absente. Sans ça,
   une référence sans photo dans le panier déclenchait un 404. */
try { window.CP_PRODUCT_IMAGES = [...PRODUCT_IMAGES]; } catch(e){}

/* Clé de recherche : minuscules ET sans accents. Personne ne tape « Hermès »,
   « Lancôme » ou « Chloé » avec l'accent sur un clavier de téléphone. La clé
   stockée et la saisie passent par la même fonction, donc les deux
   orthographes trouvent la même chose. Pendant PHP : comptoir_cle_recherche(). */
function cleRecherche(s){
  return String(s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
}

function familleDe(fam){ return String(fam||'').trim().split(/\s+/)[0] || ''; }
function prixDe(pr){ return parseInt(String(pr).replace(/[^0-9]/g,''),10) || 0; }

const catList  = document.getElementById('cat-list');
const catCount = document.getElementById('cat-count');
const catVide  = document.getElementById('cat-vide');

/* Catalogue en mémoire, dans l'ordre du fichier (maison par maison). */
const CAT = window.PRODUITS || [];
const TOTAL_REFS = CAT.length;
const MAISONS = (function(){
  const vues = [], vu = {};
  CAT.forEach(p => { if(!vu[p.b]){ vu[p.b]=1; vues.push(p.b); } });
  return vues;
})();

/* Les chiffres affichés à quatre endroits de la page viennent de la donnée.
   Garde-fou : sans donnée on laisse ce que le HTML annonce déjà, plutôt que
   d'afficher « 0 parfums · 0 maisons ». */
if(TOTAL_REFS > 0){
  document.querySelectorAll('[data-refs-total]').forEach(el => el.textContent = TOTAL_REFS);
  document.querySelectorAll('[data-maisons-total]').forEach(el => el.textContent = MAISONS.length);
  const tous = CAT.map(p => prixDe(p.pr)).filter(v => v > 0);
  if(tous.length){
    const f = v => String(v).replace(/\B(?=(\d{3})+(?!\d))/g,' ');
    document.querySelectorAll('[data-prix-min]').forEach(el => el.textContent = f(Math.min.apply(null,tous)));
    document.querySelectorAll('[data-prix-max]').forEach(el => el.textContent = f(Math.max.apply(null,tous)));
  }
}

/* Slugs de maison réels — servent aux liens « maison → catalogue filtré ». */
const MAISON_SLUGS = new Set(MAISONS.map(slugify));
const MAISON_ALIAS = { 'armani':'giorgio-armani', 'mfk':'maison-francis-kurkdjian' };
function maisonSlug(nom){
  const s = slugify(nom), a = MAISON_ALIAS[s] || s;
  return MAISON_SLUGS.has(a) ? a : null;
}

/* Ruban de marques : chaque nom qui correspond à une vraie maison devient un
   lien vers le catalogue filtré. La 2ᵉ copie du ruban (le défilement en
   boucle) est masquée aux lecteurs d'écran. */
function marqueeHTML(brands){
  const items = dup => brands.map(b => {
    const slug = maisonSlug(b);
    const inner = `<span class="mq-name">${b}</span><span class="mq-dot" aria-hidden="true"></span>`;
    const hid = dup ? ' aria-hidden="true" tabindex="-1"' : '';
    return slug
      ? `<a class="mq-item" href="#catalogue?maison=${slug}"${hid}>${inner}</a>`
      : `<span class="mq-item"${dup ? ' aria-hidden="true"' : ''}>${inner}</span>`;
  }).join('');
  return items(false) + items(true);
}

/* ── Photo « vitrine » d'une maison (héros) ──────────────────────
   Variantes détourées produites par tools/build-heros.py, déclarées dans
   img/heros/manifest.js (maquette) ou injectées par functions.php (thème).
   En WebP uniquement : voir la note sur les formats dans le README.
   Une maison absente garde le flacon-sceau CP : jamais de photo à fond blanc
   posée telle quelle sur le fond aubergine. */
function maisonShotSrc(slug){
  const h = window.HERO_SHOTS && window.HERO_SHOTS[slug];
  return h ? (h.webp || h.png || null) : null;
}

/* ── Construction de la grille (maquette seulement) ────────────── */
function carteHTML(p){
  const photo = PRODUCT_IMAGES.has(p.s + '.webp') ? (IMG_BASE + p.s + '.webp' + IMG_VER) : '';
  const badge = badgeDe(p);
  return '<a class="pc" href="' + PARFUM_BASE + encodeURIComponent(p.s) + '" role="listitem"' +
    ' data-n="' + esc(cleRecherche(p.n)) + '"' +
    ' data-b="' + esc(cleRecherche(p.b)) + '"' +
    ' data-maison="' + esc(slugify(p.b)) + '"' +
    ' data-genre="' + esc(p.g) + '"' +
    ' data-famille="' + esc(familleDe(p.fam)) + '"' +
    ' data-prix="' + prixDe(p.pr) + '">' +
    '<span class="pc-photo">' +
      '<img class="pc-img' + (photo ? '' : ' is-empty') + '" alt="" width="400" height="500"' +
      ' loading="lazy" decoding="async" src="' + (photo || THUMB_FALLBACK) + '">' +
      '<span class="pc-badge">' + esc(badge) + '</span>' +
    '</span>' +
    '<span class="pc-maison">' + esc(p.b) + '</span>' +
    '<span class="pc-nom">' + esc(p.n) + '</span>' +
    '<span class="pc-meta">' + esc(p.x + ' · ' + p.g) + '</span>' +
    '<span class="pc-prix">' + esc(p.pr) + '</span>' +
  '</a>';
}

/* La pastille montre la famille olfactive. Le site ne vend que des testeurs
   originaux : un badge « Testeur original » répété sur les 209 cartes ne
   distinguerait rien, alors que la famille, si. La nature de l'offre est dite
   par la page d'accueil et par la fiche. Même règle que comptoir_badge()
   en PHP, d'où l'ancien champ 'pres' a été retiré. */
function badgeDe(p){
  return p.fam;
}

function esc(s){
  return String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
}

const IMG_BASE    = (window.CP_PANIER && window.CP_PANIER.imgBase) || 'img/produits/';
/* Empreinte de version : les photos gardent leur nom d'une mise en ligne a
   l'autre, et sans elle le navigateur ressert celles qu'il a en cache. */
const IMG_VER     = (window.CP_PANIER && window.CP_PANIER.imgVer) ? '?v=' + window.CP_PANIER.imgVer : '';
const PARFUM_BASE = window.CP_PARFUM_BASE || 'parfum.html?p=';

if(catList && !catList.querySelector('.pc') && CAT.length){
  /* Rien dans la grille : c'est la maquette, on la construit. Sous WordPress
     PHP l'a déjà rendue et on ne touche à rien. */
  catList.innerHTML = CAT.map(carteHTML).join('');
}

/* ── Remplissage des listes déroulantes (maquette seulement) ───── */
function remplirSelect(el, valeurs){
  if(!el || el.options.length > 1) return;   /* déjà rendu par PHP */
  const frag = document.createDocumentFragment();
  valeurs.forEach(v => {
    const o = document.createElement('option');
    o.value = v.valeur;
    o.textContent = v.texte;
    frag.appendChild(o);
  });
  el.appendChild(frag);
}

const selMaison  = document.getElementById('cat-maison');
const selGenre   = document.getElementById('cat-genre');
const selFamille = document.getElementById('cat-famille');
const selTri     = document.getElementById('cat-tri');
const champQ     = document.getElementById('cat-search');
const btnReset   = document.getElementById('cat-reset');

if(CAT.length){
  const parMaison = {};
  CAT.forEach(p => { parMaison[p.b] = (parMaison[p.b] || 0) + 1; });
  remplirSelect(selMaison, MAISONS.map(m => ({ valeur: slugify(m), texte: m + ' (' + parMaison[m] + ')' })));

  const genresVus = [];
  ['Femme','Homme','Mixte'].forEach(g => { if(CAT.some(p => p.g === g)) genresVus.push(g); });
  CAT.forEach(p => { if(genresVus.indexOf(p.g) === -1) genresVus.push(p.g); });
  remplirSelect(selGenre, genresVus.map(g => ({ valeur: g, texte: g })));

  const cptFam = {};
  CAT.forEach(p => { const f = familleDe(p.fam); if(f) cptFam[f] = (cptFam[f] || 0) + 1; });
  const familles = Object.keys(cptFam).sort((a,b) => cptFam[b] - cptFam[a]);
  remplirSelect(selFamille, familles.map(f => ({ valeur: f, texte: f })));
}

/* ── Filtrage ─────────────────────────────────────────────────── */
function cartes(){ return catList ? [...catList.querySelectorAll('.pc')] : []; }

/* PAGINATION. Les 209 cartes s'affichaient d'un bloc : sur telephone, 36 000
   pixels de grille, et tout ce qui suit — comment commander, garanties,
   questions, bouton final — commencait apres 41 000 pixels, la ou personne
   ne va. On montre 24 cartes, puis « Voir plus ». La recherche et les
   filtres portent toujours sur les 209 : seule l'affichage est decoupe. */
const PAGE_CAT = 24;
let limiteCat = PAGE_CAT;
let btnPlus = null;
function boutonPlus(){
  if(btnPlus || !catList) return btnPlus;
  const bloc = document.createElement('div');
  bloc.className = 'cat-plus';
  btnPlus = document.createElement('button');
  btnPlus.type = 'button';
  btnPlus.className = 'btn-b cat-plus-btn';
  btnPlus.addEventListener('click', () => {
    limiteCat += PAGE_CAT;
    appliquerFiltres({ sansURL: true, garderPage: true });
  });
  bloc.appendChild(btnPlus);
  catList.insertAdjacentElement('afterend', bloc);
  return btnPlus;
}
function paginer(){
  let rang = 0;
  cartes().forEach(c => {
    if(c.classList.contains('hidden')){ c.classList.remove('hors-page'); return; }
    rang++;
    c.classList.toggle('hors-page', rang > limiteCat);
  });
  const reste = Math.max(0, rang - limiteCat);
  const b = boutonPlus();
  if(!b) return;
  b.parentNode.hidden = reste === 0;
  const tr = window.CP_T || (s => s);
  b.textContent = tr('Voir plus de parfums') + ' (' + reste + ')';
}

function appliquerFiltres(opts){
  if(!catList) return;
  opts = opts || {};
  if(!opts.garderPage) limiteCat = PAGE_CAT;
  const q    = (champQ    ? cleRecherche(champQ.value.trim()) : '');
  const mai  = (selMaison ? selMaison.value : '');
  const gen  = (selGenre  ? selGenre.value  : '');
  const fam  = (selFamille? selFamille.value: '');
  const tri  = (selTri    ? selTri.value    : 'defaut');

  let vues = 0;
  cartes().forEach(c => {
    const ok =
      (!q   || c.dataset.n.indexOf(q) !== -1 || c.dataset.b.indexOf(q) !== -1) &&
      (!mai || c.dataset.maison  === mai) &&
      (!gen || c.dataset.genre   === gen) &&
      (!fam || c.dataset.famille === fam);
    c.classList.toggle('hidden', !ok);
    if(ok) vues++;
  });

  /* Le tri ne redéplace le DOM que s'il a réellement changé. Le relancer à
     chaque frappe faisait 177 appendChild par caractère tapé — invisible sur
     un ordinateur, sensible sur un téléphone d'entrée de gamme. */
  if(tri !== triCourant){
    triCourant = tri;
    trier(tri);
  }

  paginer();

  if(catCount){
    catCount.textContent = vues + (vues === 1 ? ' parfum' : ' parfums');
  }
  if(catVide) catVide.hidden = vues > 0;

  const filtre = !!(q || mai || gen || fam) || tri !== 'defaut';
  if(btnReset) btnReset.hidden = !filtre;

  /* L'URL garde la maison choisie : le lien reste partageable. */
  if(!opts.sansURL){
    const cible = mai ? '#catalogue?maison=' + mai : '#catalogue';
    if(location.hash !== cible && (mai || location.hash.indexOf('maison=') > -1)){
      history.replaceState(null, '', cible);
    }
  }
}

let ordreInitial = null;
let triCourant  = 'defaut';
function trier(mode){
  if(!catList) return;
  if(!ordreInitial) ordreInitial = cartes();
  let liste = ordreInitial.slice();
  if(mode === 'prix-asc')  liste.sort((a,b) => (+a.dataset.prix) - (+b.dataset.prix));
  if(mode === 'prix-desc') liste.sort((a,b) => (+b.dataset.prix) - (+a.dataset.prix));
  if(mode === 'nom')       liste.sort((a,b) => a.dataset.n.localeCompare(b.dataset.n, 'fr'));
  /* appendChild déplace sans recréer : les images déjà chargées le restent. */
  const frag = document.createDocumentFragment();
  liste.forEach(c => frag.appendChild(c));
  catList.appendChild(frag);
}

function reinitialiser(){
  if(champQ)     champQ.value = '';
  if(selMaison)  selMaison.value = '';
  if(selGenre)   selGenre.value = '';
  if(selFamille) selFamille.value = '';
  if(selTri)     selTri.value = 'defaut';
  appliquerFiltres();   /* remet l'ordre d'origine, triCourant s'en charge */
}

[champQ, selMaison, selGenre, selFamille, selTri].forEach(el => {
  if(el) el.addEventListener('input', () => appliquerFiltres());
});
if(btnReset) btnReset.addEventListener('click', () => { reinitialiser(); scrollToCatalogue(); });
document.querySelectorAll('.cat-vide-reset').forEach(b => b.addEventListener('click', reinitialiser));

/* Entrées rapides Femme / Homme / Mixte / tout, au-dessus des filtres fins :
   pilotent le même select #cat-genre qu'un choix manuel, rien de dupliqué. */
document.querySelectorAll('[data-genre-tile]').forEach(a => {
  a.addEventListener('click', e => {
    e.preventDefault();
    if(selGenre) selGenre.value = a.dataset.genreTile;
    appliquerFiltres();
    scrollToCatalogue();
  });
});

/* ── Défilé vers le catalogue ──────────────────────────────────
   Après stabilisation de la mise en page : un scrollIntoView lancé trop tôt
   est annulé par le reflow de la grille. */
function scrollToCatalogue(){
  const cat = document.getElementById('catalogue');
  if(!cat) return;
  const navEl = document.getElementById('nav');
  const cible = () => Math.max(0, cat.getBoundingClientRect().top + window.scrollY - (navEl ? navEl.offsetHeight : 0) - 16);
  setTimeout(() => {
    window.scrollTo({ top: cible(), behavior: REDUCED ? 'auto' : 'smooth' });
    /* Recalages tant que les images decalent la page ; on s'arrete des que le visiteur fait defiler lui-meme (ajout 2026-09-21). */
    let toucheParVisiteur = false;
    const stop = () => { toucheParVisiteur = true; };
    ['wheel','touchstart','keydown'].forEach(ev => window.addEventListener(ev, stop, { once: true, passive: true }));
    const recale = () => {
      if(toucheParVisiteur) return;
      const y = cible();
      if(Math.abs(window.scrollY - y) > 120) window.scrollTo(0, y);
    };
    [420, 900, 1600, 2600, 4000].forEach(d => setTimeout(recale, d));
    if(document.readyState !== 'complete') window.addEventListener('load', () => setTimeout(recale, 50), { once: true });
  }, 140);
}

/* ── Maison demandée par l'URL (ruban, lien partagé) ───────────── */
function maisonFromURL(){
  const q = new URLSearchParams(location.search).get('maison');
  if(q) return slugify(q);
  const m = (location.hash || '').match(/[?&]maison=([^&]+)/);
  if(!m) return null;
  try { return slugify(decodeURIComponent(m[1])); }
  catch(e) { return null; }
}

function syncMaisonFromURL(scroll){
  const slug = maisonFromURL();
  if(!selMaison) return;
  if(slug && MAISON_SLUGS.has(slug)){
    selMaison.value = slug;
    appliquerFiltres({ sansURL: true });
    if(scroll) scrollToCatalogue();
  } else if(!slug && selMaison.value){
    selMaison.value = '';
    appliquerFiltres({ sansURL: true });
  }
}

if(catList){
  appliquerFiltres({ sansURL: true });
  syncMaisonFromURL(false);
  if(maisonFromURL()) setTimeout(scrollToCatalogue, 800);
  window.addEventListener('hashchange', () => syncMaisonFromURL(true));
}

/* ══════════════════════════════════════════════════════════════
   §15  FAQ — GSAP ACCORDION
══════════════════════════════════════════════════════════════ */
document.querySelectorAll('.faq-item').forEach((item,idx)=>{
  const btn=item.querySelector('.faq-btn');
  const ans=item.querySelector('.faq-ans');
  if(!btn||!ans) return;
  const aid='faq-ans-'+idx;
  ans.id=aid;
  ans.setAttribute('role','region');
  btn.setAttribute('aria-controls',aid);
  ans.style.height='0';
  ans.style.overflow='hidden';

  btn.addEventListener('click',()=>{
    const open=item.classList.contains('open');
    /* Close all */
    document.querySelectorAll('.faq-item.open').forEach(i=>{
      i.classList.remove('open');
      i.querySelector('.faq-btn').setAttribute('aria-expanded','false');
      animClose(i.querySelector('.faq-ans'));
    });
    if(!open){
      item.classList.add('open');
      btn.setAttribute('aria-expanded','true');
      animOpen(ans);
    }
  });
});

/* GSAP et ScrollTrigger sont DEUX requetes CDN distinctes : l'une peut aboutir
   sans l'autre. Ne tester que gsap laissait passer le cas ou ScrollTrigger
   manque — et la ligne suivante levait alors une ReferenceError qui tuait tout
   ce bloc : le prechargeur n'etait jamais retire et aucune section n'etait
   revelee. Le site devenait un ecran noir avec un bouton « Passer » inerte.
   Il faut les deux pour prendre ce chemin ; sinon fallbackMode() suffit. */
if(typeof gsap==='undefined' || typeof ScrollTrigger==='undefined'){fallbackMode();return}

gsap.registerPlugin(ScrollTrigger);
/* Mobile : ne pas recalculer les déclencheurs quand la barre d'URL se rétracte
   (resize fantôme) — supprime le à-coup au scroll sur iOS/Android. */
ScrollTrigger.config({ ignoreMobileResize:true });
document.documentElement.classList.add('mo');

/* Global defaults — luxury easing: fast attack, gentle decay */
gsap.defaults({ ease:'power3.out', duration:0.8, overwrite:'auto' });
ScrollTrigger.defaults({ start:'top 85%', toggleActions:'play none none none' });

/* Reduced motion — set everything to end-state instantly */
if(REDUCED){
  gsap.globalTimeline.timeScale(200);
  document.querySelectorAll('.r,.r-left,.r-right').forEach(el=>{el.classList.add('vis')});
  document.querySelectorAll('.h-eyebrow,.h1-inner,.h-badge,.hero-sub,.hero-actions,.hero-trust,.hero-visual').forEach(el=>el.classList.add('in'));
}

/* ══════════════════════════════════════════════════════════════
   §1  SCROLL PROGRESS BAR
══════════════════════════════════════════════════════════════ */
const progressBar = document.getElementById('scroll-progress');
if(progressBar){
  gsap.to(progressBar,{
    scaleX:1, ease:'none',
    scrollTrigger:{
      trigger:document.documentElement,
      start:'top top', end:'bottom bottom',
      scrub:0.3
    }
  });
  /* Show after hero exit */
  ScrollTrigger.create({
    trigger:'#hero', start:'bottom 60%',
    onEnter:()=>progressBar.classList.add('active'),
    onLeaveBack:()=>progressBar.classList.remove('active'),
  });
}

/* ══════════════════════════════════════════════════════════════
   §2  CUSTOM CURSOR — MAGNETIC LAYERED SYSTEM
══════════════════════════════════════════════════════════════ */
const cur=document.getElementById('cur'), ring=document.getElementById('cur-r');
const curLabel=document.getElementById('cur-label');
if(HAS_HOVER && cur && ring){
  let mx=0,my=0, cx=0,cy=0, rx=0,ry=0;
  let pressing=false;

  document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY},{passive:true});
  document.addEventListener('mousedown',()=>{pressing=true;gsap.to(ring,{scale:.85,duration:.15,ease:'power2.in'})});
  document.addEventListener('mouseup',()=>{pressing=false;gsap.to(ring,{scale:1,duration:.4,ease:'elastic.out(1,.4)'})});

  gsap.ticker.add(()=>{
    /* Dot follows immediately, ring lerps behind */
    cx+=(mx-cx)*.22; cy+=(my-cy)*.22;
    rx+=(mx-rx)*.09; ry+=(my-ry)*.09;
    gsap.set(cur, {x:cx-3, y:cy-3});
    gsap.set(ring,{x:rx-17,y:ry-17});
    if(curLabel) gsap.set(curLabel,{x:rx, y:ry+32});
  });

  /* Link hover */
  document.querySelectorAll('a:not(.btn-a):not(.btn-fin):not(.nav-pill),button,.scent-card,.pc,.proc-step,.stab').forEach(el=>{
    el.addEventListener('mouseenter',()=>document.body.classList.add('ch-link'));
    el.addEventListener('mouseleave',()=>document.body.classList.remove('ch-link'));
  });
  /* CTA hover — bigger ring + label */
  document.querySelectorAll('.btn-a,.nav-pill,.btn-fin').forEach(el=>{
    el.addEventListener('mouseenter',()=>{
      document.body.classList.remove('ch-link');
      document.body.classList.add('ch-cta');
      if(curLabel) curLabel.textContent=el.dataset.cursorLabel||'Commander';
    });
    el.addEventListener('mouseleave',()=>document.body.classList.remove('ch-cta'));
  });
}

/* ══════════════════════════════════════════════════════════════
   §3  MAGNETIC BUTTONS
══════════════════════════════════════════════════════════════ */
if(HAS_HOVER && !REDUCED){
  document.querySelectorAll('.btn-a,.btn-fin,.nav-pill,.wa-cta').forEach(el=>{
    const strength = el.classList.contains('nav-pill') ? 0.25 : 0.35;
    el.addEventListener('mousemove',e=>{
      const r=el.getBoundingClientRect();
      const dx=e.clientX-(r.left+r.width/2);
      const dy=e.clientY-(r.top+r.height/2);
      gsap.to(el,{x:dx*strength, y:dy*strength, duration:0.4, ease:'power3.out'});
    });
    el.addEventListener('mouseleave',()=>{
      gsap.to(el,{x:0,y:0, duration:0.7, ease:'elastic.out(1,0.3)'});
    });
  });
}

/* ══════════════════════════════════════════════════════════════
   §4  PRELOADER SEQUENCE
══════════════════════════════════════════════════════════════ */
const loader = document.getElementById('loader');
const alreadyPlayed = memLire('cp_v3');
let loaderKilled = false;

function killLoader(){
  if(!loader || loaderKilled) return;   /* une seule sortie, un seul initHeroSequence */
  loaderKilled = true;
  document.getElementById('ldr-skip')?.setAttribute('disabled','');
  const tl = gsap.timeline({
    onComplete:()=>{ loader.remove(); initHeroSequence(); }
  });
  tl.to('#ldr-mark',  {opacity:0, y:-12, scale:.96, duration:.5, ease:'power2.in'},0)
    .to('#ldr-name',   {opacity:0, y:-8, duration:.4, ease:'power2.in'},0.05)
    .to('#ldr-rule',   {scaleX:0, duration:.4, ease:'power2.in'},0.08)
    .to('#ldr-bar',    {opacity:0, duration:.3},0.1)
    .to(loader,        {opacity:0, duration:.65, ease:'power2.inOut'},0.25);
  memEcrire('cp_v3','1');
}

if(alreadyPlayed || cpSautePrechargeur()){
  if(loader) loader.remove();
  initHeroSequence();
} else if(loader){
  /* Entry timeline */
  const entry = gsap.timeline({delay:0.1});
  entry.fromTo('#ldr-mark',
    {opacity:0, y:10, filter:'blur(4px)'},
    {opacity:1, y:0, filter:'blur(0px)', duration:0.9, ease:'power3.out'}, 0)
  .fromTo('#ldr-rule',
    {width:0},
    {width:52, duration:0.7, ease:'power2.out'}, 0.55)
  .fromTo('#ldr-name',
    {opacity:0, y:6},
    {opacity:1, y:0, duration:0.6, ease:'power3.out'}, 0.85)
  .fromTo('#ldr-bar',
    {width:'0%'},
    {width:'100%', duration:0.85, ease:'power1.inOut'}, 0.3)
  .call(killLoader,null,1.25);

  /* Skip button */
  document.getElementById('ldr-skip')?.addEventListener('click',()=>{
    entry.progress(1).kill();
    killLoader();
  });
} else {
  initHeroSequence();
}

/* ══════════════════════════════════════════════════════════════
   §5  HERO CINEMATIC ENTRY
══════════════════════════════════════════════════════════════ */
function initHeroSequence(){
  if(REDUCED) return; /* already visible via CSS */

  const master = gsap.timeline({delay:0.05});

  /* Eyebrow — le conteneur apparait, la ligne se trace, puis le texte glisse.
     #he doit etre anime explicitement : son etat CSS de base est opacity:0 et
     seule la classe .in le revele, or le chemin GSAP ne l'ajoute pas. */
  master.fromTo('#he',{opacity:0,x:-12},{opacity:1,x:0,duration:.6,ease:'power2.out'},0)
        .fromTo('.h-eye-line',{scaleX:0,transformOrigin:'left'},{scaleX:1,duration:.55,ease:'power2.out'},0)
        .fromTo('.h-eye-text',{opacity:0,x:-14},{opacity:1,x:0,duration:.6},0.2);

  /* H1 — line-by-line clip reveal (the .h1-inner spans)
     y:0 est explicite dans LES DEUX etats : sans lui GSAP conserve, en plus
     de yPercent, le translateY(105%) du CSS de base (soit y:48.7px), et les
     lignes restent clippees hors de leur .h1-line (overflow:hidden) —
     autrement dit le titre du hero ne s'affichait jamais. */
  master.fromTo('.h1-inner',
    {yPercent:105, y:0, opacity:0},
    {yPercent:0, y:0, opacity:1, duration:1, stagger:0.14, ease:'power3.out'},
    0.18);

  /* Value badge */
  master.fromTo('#hbadge',
    {opacity:0,y:12,scale:.96},
    {opacity:1,y:0,scale:1,duration:.7},0.72);

  /* Subtitle — slight blur-in */
  master.fromTo('#hs',
    {opacity:0,y:14,filter:'blur(3px)'},
    {opacity:1,y:0,filter:'blur(0px)',duration:.85},0.88);

  /* Action buttons */
  master.fromTo('#ha',
    {opacity:0,y:12},
    {opacity:1,y:0,duration:.7},1.02);

  /* Trust strip — border draws then items stagger */
  master.fromTo('#ht',
    {opacity:0,y:8},
    {opacity:1,y:0,duration:.7},1.18);

  /* Visual stage — smooth opacity emergence */
  master.fromTo('#hv',
    {opacity:0},
    {opacity:1,duration:1.4,ease:'power2.out'},0.5);

  /* Floating note cards — glide in from sides */
  /* y:0 explicite, comme pour le H1 : sans lui GSAP conserve le
     translateY(8px) que .h-nc-1 / .h-nc-2 portent en CSS (leur etat d'avant
     reveal), et les deux cartes restent 8px trop bas pour toujours — la
     classe .visible qui les remonterait n'est ajoutee que par fallbackMode(). */
  master.fromTo('#hnc1',
    {opacity:0,x:-24,y:8},
    {opacity:1,x:0,y:0,duration:.6},1.4);
  master.fromTo('#hnc2',
    {opacity:0,x:24,y:8},
    {opacity:1,x:0,y:0,duration:.6},1.7);

  /* Particles */
  spawnParticles();

  /* Start parallax & tilt after hero reveals */
  master.call(()=>{initHeroParallax()},null,0.6);
}

/* ══════════════════════════════════════════════════════════════
   §6  HERO PARALLAX — SCROLL-LINKED DEPTH
══════════════════════════════════════════════════════════════ */
function initHeroParallax(){
  if(REDUCED||IS_MOBILE) return;
  const hero=document.getElementById('hero');
  if(!hero) return;

  /* Text exits upward faster — creates depth */
  gsap.to('.hero-text',{
    y:120, ease:'none',
    scrollTrigger:{ trigger:hero, start:'top top', end:'bottom top', scrub:true }
  });

  /* Flacon rises slightly — counters text, feels floating */
  gsap.to('#flacon-stage',{
    y:-50, ease:'none',
    scrollTrigger:{ trigger:hero, start:'top top', end:'bottom top', scrub:true }
  });

  /* Atmospheric glow scales subtly */
  gsap.to('.f-glow-outer,.f-glow-inner',{
    scale:1.15, ease:'none',
    scrollTrigger:{ trigger:hero, start:'top top', end:'bottom top', scrub:true }
  });

  /* Ghost brand names — different parallax speeds */
  document.querySelectorAll('.h-brand-float').forEach((el,i)=>{
    gsap.to(el,{
      y: 40 + i*25, ease:'none',
      scrollTrigger:{ trigger:hero, start:'top top', end:'bottom top', scrub:true }
    });
  });

  /* Scroll indicator fades out quickly */
  gsap.to('.scroll-hint',{
    opacity:0, y:-10,
    scrollTrigger:{ trigger:hero, start:'5% top', end:'20% top', scrub:true }
  });

  /* Vignette darkens on scroll */
  gsap.to('.hc-vignette',{
    opacity:1, ease:'none',
    scrollTrigger:{ trigger:hero, start:'top top', end:'bottom top', scrub:true }
  });
}

/* §7 — L'ancien tilt CSS du flacon SVG est remplacé par la rotation/parallaxe
   propre à la scène WebGL du logo 3D (voir le bloc « LOGO 3D » en bas de page). */

/* ══════════════════════════════════════════════════════════════
   §8  PARTICLE SYSTEM
══════════════════════════════════════════════════════════════ */
function spawnParticles(){
  const c=document.getElementById('f-particles');
  if(!c||REDUCED) return;
  for(let i=0;i<16;i++){
    const p=document.createElement('div');
    p.className='f-particle';
    const sz=1+Math.random()*2.5;
    const x=20+Math.random()*60;
    const startY=55+Math.random()*35;
    p.style.cssText=`width:${sz}px;height:${sz}px;left:${x}%;top:${startY}%`;
    c.appendChild(p);
    gsap.fromTo(p,
      {y:0,opacity:0,scale:1},
      {y:-(80+Math.random()*100),opacity:0,scale:.3,
       duration:5+Math.random()*7, repeat:-1, delay:Math.random()*5,
       ease:'none',
       keyframes:{
         '0%':{opacity:0},'15%':{opacity:.15+Math.random()*.2},'75%':{opacity:.08},'100%':{opacity:0}
       }
      }
    );
  }
}

/* ══════════════════════════════════════════════════════════════
   §9  NAV — STICKY WITH SMOOTH TRANSITION
══════════════════════════════════════════════════════════════ */
const nav=document.getElementById('nav');
ScrollTrigger.create({
  start:80, end:999999,
  toggleClass:{targets:nav,className:'stuck'},
  onUpdate:self=>{
    /* Progressive blur amount based on scroll */
  }
});

/* ══════════════════════════════════════════════════════════════
   §10  MOBILE MENU — GSAP TIMELINE
══════════════════════════════════════════════════════════════ */
const ham=document.getElementById('nav-ham');
const mobMenu=document.getElementById('mob-menu');
let menuOpen=false, menuTl=null;

function buildMenuTimeline(){
  const tl=gsap.timeline({paused:true});
  tl.set(mobMenu,{display:'flex'})
    .fromTo(mobMenu,{opacity:0},{opacity:1,duration:.35,ease:'power2.out'})
    .fromTo('.mob-link',{opacity:0,x:-16},{opacity:1,x:0,duration:.45,stagger:.06,ease:'power3.out'},'-=.15');
  return tl;
}

if(ham && mobMenu){
  menuTl=buildMenuTimeline();
  function toggleMenu(){
    menuOpen=!menuOpen;
    ham.setAttribute('aria-expanded',menuOpen);
    document.body.style.overflow=menuOpen?'hidden':'';
    const spans=ham.querySelectorAll('span');
    if(menuOpen){
      menuTl.play();
      gsap.to(spans[0],{rotation:45,y:6,duration:.3});
      gsap.to(spans[1],{opacity:0,duration:.2});
      gsap.to(spans[2],{rotation:-45,y:-6,duration:.3});
    } else {
      menuTl.reverse();
      gsap.to(spans[0],{rotation:0,y:0,duration:.3});
      gsap.to(spans[1],{opacity:1,duration:.2});
      gsap.to(spans[2],{rotation:0,y:0,duration:.3});
    }
  }
  ham.addEventListener('click',toggleMenu);
  document.querySelectorAll('[data-close]').forEach(a=>a.addEventListener('click',()=>{if(menuOpen)toggleMenu()}));
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&menuOpen)toggleMenu()});
}

/* ══════════════════════════════════════════════════════════════
   §11  MARQUEE — SCROLL-VELOCITY RESPONSIVE
══════════════════════════════════════════════════════════════ */
const mBrands=['Chanel','Dior','Tom Ford','Xerjoff','Yves Saint Laurent','Armani','Hermès','Creed','MFK','Parfums de Marly','Prada','Versace','Givenchy','Kayali','Louis Vuitton','Valentino','Lancôme','Gucci','Jean Paul Gaultier','Narciso Rodriguez'];
const mqTrack=document.getElementById('mq-track');
if(mqTrack){
  mqTrack.innerHTML=marqueeHTML(mBrands);
  mqTrack.style.animation='none'; /* on pilote en JS (ou pas du tout en reduced-motion) */
  const mqHost=mqTrack.parentElement;

  /* ── Aperçu héros piloté par le ruban ──────────────────────────
     Survol/focus (desktop) ou maison centrée (tactile) → crossfade du
     héros vers la vraie photo produit de cette maison. Le clic (filtre
     catalogue) n'est jamais touché. */
  const heroVisual=document.querySelector('.hero-visual');
  const heroShotImg=document.querySelector('#hero-shot img');
  const slugOf=a=>(((a&&a.getAttribute('href'))||'').match(/maison=([^&]+)/)||[])[1];
  let wantShot=null, shotOffT=0, spinDir=1, swapTl=null, idleTween=null, settleT=0,
      hoverT=0, pendingSlug=null, vitrineAPI=null;

  function markActive(a){
    const cur=mqTrack.querySelector('.mq-item.is-active');
    if(cur&&cur!==a) cur.classList.remove('is-active');
    if(a) a.classList.add('is-active');
  }
  /* Respiration au repos : le héros n'est jamais complètement figé. */
  function killIdle(){ if(idleTween){ idleTween.kill(); idleTween=null; } }
  function startIdle(){
    if(REDUCED||!heroShotImg||!heroVisual.classList.contains('shot-on')) return;
    killIdle();
    idleTween=gsap.to(heroShotImg,{y:-7,scale:1.015,duration:3.4,ease:'sine.inOut',yoyo:true,repeat:-1});
  }
  /* Bascule « vitrine » : le flacon sortant pivote, le suivant arrive de biais. */
  function swapTo(src){
    heroVisual.classList.add('shot-on');
    if(REDUCED){                                   /* mouvement réduit : simple fondu */
      killIdle(); if(swapTl) swapTl.kill();
      gsap.set(heroShotImg,{clearProps:'transform'});
      heroShotImg.src=src;
      return;
    }
    killIdle();
    if(swapTl) swapTl.kill();
    const first=!heroShotImg.getAttribute('src');
    swapTl=gsap.timeline({onComplete:startIdle});
    if(!first){
      swapTl.to(heroShotImg,{opacity:0,rotateY:-15*spinDir,scale:.93,y:0,duration:.17,ease:'power2.in'});
    }
    /* La photo est posée au démarrage de la phase entrante (opacité 0 à cet
       instant : aucun flash) — jamais dans un callback isolé, qui sauterait
       si la timeline était tuée par une bascule plus récente. */
    swapTl.fromTo(heroShotImg,
      {opacity:0,rotateY:19*spinDir,scale:.93,y:0},
      {opacity:1,rotateY:0,scale:1,y:0,duration:.54,ease:'power3.out',
       onStart:()=>{ heroShotImg.src=src; }});
  }
  /* Petit délai avant de basculer : en balayant le ruban à la souris on ne
     déclenche qu'une bascule, celle de la maison sur laquelle on s'arrête. */
  function preview(slug){
    if(!heroVisual||!slug) return;
    /* Vitrine WebGL disponible : c'est l'anneau qui tourne, pas une photo
       isolée qu'on remplace. Le repli photo ne sert que sans WebGL. */
    if(vitrineAPI && vitrineAPI.goTo(slug)){
      clearTimeout(shotOffT);
      heroVisual.classList.add('vitrine-on');
      return;
    }
    if(!heroShotImg) return;
    clearTimeout(shotOffT);
    /* Une même maison déjà en file d'attente ne réarme pas le minuteur :
       sans ça, le flot d'événements du ruban qui défile le repousse sans fin. */
    if(slug===pendingSlug) return;
    pendingSlug=slug;
    clearTimeout(hoverT);
    hoverT=setTimeout(()=>{
      pendingSlug=null;                            /* file vidée : un nouveau survol peut relancer */
      const src=maisonShotSrc(slug);
      if(!src){ killIdle(); heroVisual.classList.remove('shot-on'); wantShot=null; return; } /* ex. Carolina Herrera */
      if(src===wantShot){ heroVisual.classList.add('shot-on'); return; }
      wantShot=src;
      const im=new Image();                        /* précharge : pas de flash pendant la bascule */
      im.onload=()=>{ if(wantShot===src) swapTo(src); };
      im.onerror=()=>{ if(wantShot===src){ wantShot=null; killIdle(); heroVisual.classList.remove('shot-on'); } };
      im.src=src;
    },90);
  }
  function endPreview(){
    clearTimeout(shotOffT); clearTimeout(hoverT); pendingSlug=null;
    /* La vitrine, elle, reste : c'est le contenu du héros une fois lancée,
       pas un aperçu fugace. On ne revient pas au flacon en quittant le ruban. */
    if(vitrineAPI && heroVisual.classList.contains('vitrine-on')) return;
    shotOffT=setTimeout(()=>{
      killIdle();
      if(swapTl) swapTl.kill();
      if(heroShotImg&&!REDUCED) gsap.to(heroShotImg,{rotateY:0,scale:1,y:0,duration:.4,ease:'power2.out'});
      if(heroVisual) heroVisual.classList.remove('shot-on');
      wantShot=null; markActive(null);
    },220);
  }
  /* Inclinaison pilotée par le geste : glisser le ruban fait tourner le flacon. */
  function spinBy(delta){
    if(REDUCED||!heroShotImg||!heroVisual.classList.contains('shot-on')) return;
    if(swapTl&&swapTl.isActive()) return;
    killIdle();
    const tilt=Math.max(-17,Math.min(17,delta*0.85));
    gsap.to(heroShotImg,{rotateY:tilt,duration:.22,ease:'power2.out',overwrite:'auto'});
    clearTimeout(settleT);
    settleT=setTimeout(()=>{
      gsap.to(heroShotImg,{rotateY:0,duration:.75,ease:'power3.out',onComplete:startIdle});
    },170);
  }
  /* Sens de rotation : on pivote du côté d'où arrive la nouvelle maison. */
  function aimAt(a){
    const cur=mqTrack.querySelector('.mq-item.is-active');
    if(cur&&a&&cur!==a){
      spinDir = (cur.compareDocumentPosition(a) & Node.DOCUMENT_POSITION_FOLLOWING) ? 1 : -1;
    }
  }
  if(HAS_HOVER){
    mqTrack.addEventListener('mouseover',e=>{
      const a=e.target.closest('a.mq-item'); if(!a||!slugOf(a)) return;
      clearTimeout(shotOffT); aimAt(a); preview(slugOf(a)); markActive(a);
    });
    if(mqHost) mqHost.addEventListener('mouseleave',endPreview);
  }
  mqTrack.addEventListener('focusin',e=>{
    const a=e.target.closest('a.mq-item'); if(!a||!slugOf(a)) return;
    clearTimeout(shotOffT); aimAt(a); preview(slugOf(a)); markActive(a);
  });
  mqTrack.addEventListener('focusout',e=>{ if(!mqTrack.contains(e.relatedTarget)) endPreview(); });

  /* ── Vitrine automatique ────────────────────────────────────────
     Sans elle, l'effet est invisible : au chargement le héros montre le
     flacon 3D, et il faut deviner qu'il faut survoler le ruban (situé plus
     bas) pour qu'il se passe quelque chose. La vitrine défile donc seule
     jusqu'au premier geste de l'utilisateur, qui reprend la main pour de bon. */
  let autoTimer=0, autoIdx=0, autoStopped=REDUCED;   /* mouvement réduit : pas de défilé auto */
  const autoItems=()=>[...mqTrack.querySelectorAll('a.mq-item')]
    .filter(a=>!a.hasAttribute('aria-hidden') && maisonShotSrc(slugOf(a)));
  function autoStop(){
    if(autoStopped) return;
    autoStopped=true; clearInterval(autoTimer); autoTimer=0;
  }
  function autoStart(){
    if(autoStopped||autoTimer) return;
    const list=autoItems();
    if(!list.length) return;
    const step=()=>{
      if(autoStopped) return;
      const a=list[autoIdx++ % list.length];
      aimAt(a); preview(slugOf(a)); markActive(a);
    };
    step();
    autoTimer=setInterval(step,2600);
  }
  /* Le premier geste réel sur le ruban arrête le défilé pour de bon. */
  ['mouseover','pointerdown','touchstart','wheel','focusin'].forEach(ev=>{
    mqHost&&mqHost.addEventListener(ev,autoStop,{passive:true});
  });
  /* Ne tourne que quand le héros est à l'écran. */
  if(!REDUCED){
    const heroSec=document.getElementById('hero');
    if(heroSec&&'IntersectionObserver' in window){
      new IntersectionObserver(en=>{
        en.forEach(x=>{ x.isIntersecting ? setTimeout(autoStart,1200) : (clearInterval(autoTimer),autoTimer=0); });
      },{threshold:0.25}).observe(heroSec);
    } else { setTimeout(autoStart,1200); }
  }

  /* ══ VITRINE — anneau de flacons en WebGL ════════════════════════
     Le code vit dans vitrine.js. Il ne peut tourner que sur l'accueil : il
     lui faut three.js, chargé là seulement, et #vitrine, qui n'existe que
     dans le héros. functions.php met donc ce fichier en file sous la même
     condition que three.js, au lieu de l'envoyer sur chaque fiche parfum,
     la page commande et la 404, où il ne s'exécutera jamais.
     Fichier absent, pas de WebGL, moins de trois maisons en photo : la
     fabrique rend null et le héros garde l'aperçu photo simple. C'est le
     repli prévu, pas une panne — d'où l'absence de message d'erreur. */
  window.addEventListener('load',function(){
    if(REDUCED) return;
    if(!window.CP_VITRINE) return;
    vitrineAPI=window.CP_VITRINE({
      MAISONS:MAISONS, maisonSlug:maisonSlug, maisonShotSrc:maisonShotSrc,
      mqTrack:mqTrack, markActive:markActive, autoStop:autoStop
    });
  });

  /* ── Défilement du ruban : 3 modes ── */
  if(HAS_HOVER && REDUCED){
    /* Desktop + reduced-motion : rangée statique centrée (le survol pilote l'aperçu). */
    mqTrack.classList.add('is-static');
    mqTrack.querySelectorAll('[aria-hidden="true"].mq-item').forEach(el=>el.remove());

  } else if(!HAS_HOVER){
    /* Tactile : bande qu'on fait glisser au doigt (scroll-snap), pas de
       défilement auto — l'aperçu héros suit la maison au centre. Fonctionne
       aussi en reduced-motion (le glissé est piloté par l'utilisateur). */
    if(mqHost) mqHost.classList.add('is-swipe');
    mqTrack.classList.add('is-swipe');
    mqTrack.querySelectorAll('[aria-hidden="true"].mq-item').forEach(el=>el.remove());
    const items=()=>[...mqTrack.querySelectorAll('a.mq-item')];
    function centeredItem(){
      const box=mqHost.getBoundingClientRect(), mid=box.left+box.width/2;
      let best=null,bd=Infinity;
      items().forEach(a=>{ const r=a.getBoundingClientRect(); const d=Math.abs(r.left+r.width/2-mid); if(d<bd){ bd=d; best=a; } });
      return best;
    }
    let mqRaf=0, mqReady=false, lastSL=0;
    /* L'aperçu ne démarre qu'au 1er geste réel de l'utilisateur — le
       centrage initial et le calage scroll-snap ne déclenchent rien. */
    const mqPrime=()=>{ mqReady=true; };
    mqHost.addEventListener('pointerdown',mqPrime,{passive:true});
    mqHost.addEventListener('touchstart',mqPrime,{passive:true});
    mqHost.addEventListener('wheel',mqPrime,{passive:true});
    mqHost.addEventListener('scroll',()=>{
      if(!mqReady) return;
      if(mqRaf) return;
      mqRaf=requestAnimationFrame(()=>{
        mqRaf=0;
        const sl=mqHost.scrollLeft, dv=sl-lastSL; lastSL=sl;
        if(dv) spinDir = dv>0 ? 1 : -1;
        spinBy(dv);                                  /* le flacon suit le geste */
        const a=centeredItem();
        if(a&&slugOf(a)){ preview(slugOf(a)); markActive(a); }
      });
    },{passive:true});

    /* « Faire tourner la vitrine » à la souris/au stylet : glisser la bande.
       Le tactile garde l'inertie native du navigateur, plus fluide qu'un
       polyfill. Un vrai glissé n'ouvre pas le lien, un simple tap si. */
    let drag=false, dragX=0, dragFrom=0, moved=0;
    mqHost.addEventListener('pointerdown',e=>{
      if(e.pointerType==='touch') return;
      drag=true; moved=0; dragX=e.clientX; dragFrom=mqHost.scrollLeft;
      mqHost.style.cursor='grabbing';
    });
    mqHost.addEventListener('pointermove',e=>{
      if(!drag) return;
      const d=e.clientX-dragX;
      moved=Math.max(moved,Math.abs(d));
      mqHost.scrollLeft=dragFrom-d;
    });
    const dragEnd=()=>{
      if(!drag) return;
      drag=false; mqHost.style.cursor='';
      if(moved>6) mqHost.addEventListener('click',ev=>{ ev.preventDefault(); ev.stopPropagation(); },{capture:true,once:true});
    };
    mqHost.addEventListener('pointerup',dragEnd);
    mqHost.addEventListener('pointercancel',dragEnd);
    mqHost.addEventListener('pointerleave',dragEnd);

    /* Départ : on centre la bande sur une maison médiane, flacon 3D conservé. */
    requestAnimationFrame(()=>{
      const list=items(), t=list[Math.floor(list.length/2)];
      if(t) mqHost.scrollLeft=t.offsetLeft-(mqHost.clientWidth-t.clientWidth)/2;
      lastSL=mqHost.scrollLeft;
    });

  } else {
    /* Desktop : défilement auto (GSAP), pause au survol/focus. */
    const trackW=mqTrack.scrollWidth/2;
    const marqueeTween=gsap.to(mqTrack,{ x:-trackW, duration:30, ease:'none', repeat:-1 });
    if(!IS_MOBILE){
      let lastST=window.scrollY, scrollTimer, ticking=false;
      window.addEventListener('scroll',()=>{
        if(ticking) return; ticking=true;
        requestAnimationFrame(()=>{
          const st=window.scrollY, v=st-lastST; lastST=st;
          const speed=1+Math.min(Math.abs(v)*0.015,3);
          gsap.to(marqueeTween,{timeScale: v<0 ? -speed : speed, duration:0.5, ease:'power2.out', overwrite:true});
          clearTimeout(scrollTimer);
          scrollTimer=setTimeout(()=>gsap.to(marqueeTween,{timeScale:1,duration:1.2,ease:'power2.out'}),150);
          ticking=false;
        });
      },{passive:true});
    }
    const halt=()=>gsap.to(marqueeTween,{timeScale:0,duration:0.4});
    const go  =()=>gsap.to(marqueeTween,{timeScale:1,duration:0.8});
    if(mqHost){
      mqHost.addEventListener('mouseenter',halt);
      mqHost.addEventListener('mouseleave',go);
      mqHost.addEventListener('focusin',halt);
      mqHost.addEventListener('focusout',go);
    }
  }
}

/* ══════════════════════════════════════════════════════════════
   §12  SCROLL CHOREOGRAPHY — PER-SECTION REVEALS
══════════════════════════════════════════════════════════════ */

/* Sur mobile, les révélations « glissé horizontal » (colonnes, encarts) font
   dépasser un élément pleine largeur hors de la fenêtre le temps de l'animation
   (rogné par overflow-x:clip, mais scintillement possible). On les passe en
   fondu simple sous 860 px — le desktop garde le mouvement. */
const SLX = IS_MOBILE ? 0 : 1;

if(!REDUCED){

  /* ── DISTINCTION SECTION ── */
  const distHeader = document.querySelector('.dist-header');
  if(distHeader){
    const dtl = gsap.timeline({scrollTrigger:{trigger:'.distinction',start:'top 78%'}});
    dtl.fromTo('.dist-header .sect-kicker',{opacity:0,x:-12},{opacity:1,x:0,duration:.55},0);
    dtl.fromTo('.dist-header .sect-h2',{opacity:0,y:24},{opacity:1,y:0,duration:.75},0.15);
    dtl.fromTo('.dist-header .sect-body',{opacity:0,y:16},{opacity:1,y:0,duration:.7},0.35);
    distHeader.classList.add('vis');
  }

  /* Comparison columns — slide from sides */
  const distCompare = document.querySelector('.dist-compare');
  if(distCompare){
    const cols = distCompare.querySelectorAll('.dist-col');
    const ctl = gsap.timeline({scrollTrigger:{trigger:distCompare,start:'top 80%'}});
    if(cols[0]) ctl.fromTo(cols[0],{opacity:0,x:-35*SLX,y:24*(1-SLX)},{opacity:1,x:0,y:0,duration:.85},0);
    if(cols[1]) ctl.fromTo(cols[1],{opacity:0,x:35*SLX,y:24*(1-SLX)},{opacity:1,x:0,y:0,duration:.85},0.1);
    /* List items stagger */
    cols.forEach(col=>{
      ctl.fromTo(col.querySelectorAll('.dist-list li'),
        {opacity:0,y:12},{opacity:1,y:0,duration:.5,stagger:.06},0.35);
    });
    distCompare.classList.add('vis');
  }

  /* ── SCENT FAMILIES ── */
  const scentsInner = document.querySelector('.scents-inner');
  if(scentsInner){
    const stl = gsap.timeline({scrollTrigger:{trigger:'.scents',start:'top 78%'}});
    stl.fromTo('.scents-inner .sect-kicker',{opacity:0,x:-12},{opacity:1,x:0,duration:.55},0);
    stl.fromTo('.scents-inner .sect-h2',{opacity:0,y:20},{opacity:1,y:0,duration:.7},0.1);
    stl.fromTo('.stab',{opacity:0,y:10},{opacity:1,y:0,duration:.5,stagger:.06},0.3);
    stl.fromTo('.scent-panel.active .scent-card',{opacity:0,y:24,scale:.97},{opacity:1,y:0,scale:1,duration:.65,stagger:.08},0.5);
    scentsInner.querySelector('.r')?.classList.add('vis');
  }

  /* ── CATALOGUE ── */
  const catInner = document.querySelector('.cat-inner');
  if(catInner){
    const ctl2 = gsap.timeline({scrollTrigger:{trigger:'.catalogue',start:'top 78%'}});
    ctl2.fromTo('.cat-inner .sect-kicker',{opacity:0,x:-12},{opacity:1,x:0,duration:.55},0);
    ctl2.fromTo('.cat-inner .sect-h2',{opacity:0,y:20},{opacity:1,y:0,duration:.7},0.1);
    ctl2.fromTo('.cat-inner .sect-body',{opacity:0,y:14},{opacity:1,y:0,duration:.65},0.25);
    ctl2.fromTo('.cat-filtres',{opacity:0,y:12},{opacity:1,y:0,duration:.6},0.4);
    catInner.querySelector('.r')?.classList.add('vis');
  }

  /* Grille du catalogue — un seul déclencheur pour toute la grille.
     Un par carte ferait 177 ScrollTrigger sur une grille qui se filtre et se
     retrie en permanence : coûteux, et les cartes masquées fausseraient les
     mesures. */
  const grille = document.getElementById('cat-list');
  if(grille){
    gsap.fromTo(grille,
      {opacity:0,y:14},
      {opacity:1,y:0,duration:.6,ease:'power3.out',
       scrollTrigger:{trigger:grille,start:'top 92%'}});
  }

  /* ── PROCESS STEPS ── */
  const procSteps = document.querySelectorAll('.proc-step');
  if(procSteps.length){
    procSteps.forEach((step,i)=>{
      const stl2 = gsap.timeline({
        scrollTrigger:{trigger:step,start:'top 82%'},
        onComplete:()=>step.classList.add('drawn')
      });
      stl2.fromTo(step,{opacity:0,y:40},{opacity:1,y:0,duration:.75},0);
      /* Number countup 00 → 0X */
      const numEl = step.querySelector('.proc-num');
      if(numEl){
        const target = parseInt(numEl.textContent) || 0;
        const obj={v:0};
        stl2.to(obj,{v:target,duration:.8,ease:'power2.out',snap:{v:1},
          onUpdate:()=>{numEl.textContent=String(obj.v).padStart(2,'0')}
        },0.2);
      }
      /* Le trait d'accent bas se révèle via la classe .drawn ajoutée onComplete */
    });
    /* Remove old .r classes */
    procSteps.forEach(s=>{s.classList.add('vis');s.classList.remove('r')});
    document.querySelector('.proc-inner .r')?.classList.add('vis');
  }

  /* ── TRUST / GUARANTEES ── */
  const trustLeft = document.querySelector('.trust-inner .r-left');
  const trustRight = document.querySelector('.trust-inner .r-right');
  if(trustLeft){
    const ttl = gsap.timeline({scrollTrigger:{trigger:'.trust',start:'top 78%'}});
    ttl.fromTo(trustLeft,{opacity:0,x:-40*SLX,y:22*(1-SLX)},{opacity:1,x:0,y:0,duration:.9},0);
    ttl.fromTo('.g-item',{opacity:0,y:18},{opacity:1,y:0,duration:.6,stagger:.1},0.25);
    trustLeft.classList.add('vis');
  }
  if(trustRight){
    gsap.fromTo(trustRight,
      {opacity:0,x:40*SLX,y:22*(1-SLX)},{opacity:1,x:0,y:0,duration:.9,
      scrollTrigger:{trigger:'.trust',start:'top 72%'}
    });
    trustRight.classList.add('vis');
  }

  /* ── NUMBERS WALL — COUNTER ANIMATION ── */
  document.querySelectorAll('.nb-item').forEach((item,i)=>{
    const numEl = item.querySelector('.nb-num');
    if(!numEl) return;

    const raw = numEl.textContent.trim();
    /* Parse number and suffix: "179" → 179,"" | "24h" → 24,"h" | "0 DH" → 0," DH"
       Le groupe des chiffres s'arrete sur un CHIFFRE : l'espace qui suit
       appartient au suffixe. Ecrit autrement, il etait avale et le compteur
       finissait sur « 24ساعة » — en francais « 24h » n'en montrait rien,
       l'unite y etant collee d'origine. */
    const match = raw.match(/^([\d\s]*\d)(.*)$/);
    if(!match) return;
    const target = parseInt(match[1].replace(/\s/g,'')) || 0;
    const suffix = match[2] || '';

    const tl3 = gsap.timeline({scrollTrigger:{trigger:item,start:'top 88%'}});
    tl3.fromTo(item,{opacity:0,y:20,scale:.96},{opacity:1,y:0,scale:1,duration:.7},0);

    if(target > 0){
      const obj={v:0};
      tl3.to(obj,{
        v:target, duration:2, ease:'power2.out', snap:{v:1},
        onUpdate:()=>{ numEl.textContent = obj.v + suffix; }
      },0.15);
    }
    item.classList.add('vis');item.classList.remove('r');
  });

  /* ── FAQ SECTION ── */
  const faqSidebar = document.querySelector('.faq-sidebar');
  if(faqSidebar){
    gsap.fromTo(faqSidebar,{opacity:0,x:-30*SLX,y:20*(1-SLX)},{opacity:1,x:0,y:0,duration:.85,
      scrollTrigger:{trigger:'.faq',start:'top 78%'}
    });
    faqSidebar.classList.add('vis');
  }
  document.querySelectorAll('.faq-item').forEach((item,i)=>{
    gsap.fromTo(item,{opacity:0,y:16},{opacity:1,y:0,duration:.55,
      scrollTrigger:{trigger:item, start:'top 90%'}
    });
  });

  /* ── FINALE SECTION ── */
  const finInner = document.querySelector('.fin-inner');
  if(finInner){
    const ftl = gsap.timeline({scrollTrigger:{trigger:'.finale',start:'top 75%'}});
    ftl.fromTo('.fin-kicker',{opacity:0,y:14},{opacity:1,y:0,duration:.6},0);
    ftl.fromTo('.fin-h2',{opacity:0,y:28},{opacity:1,y:0,duration:.85},0.12);
    ftl.fromTo('.fin-body',{opacity:0,y:16,filter:'blur(2px)'},{opacity:1,y:0,filter:'blur(0px)',duration:.75},0.35);
    ftl.fromTo('.fin-actions > *',{opacity:0,y:14},{opacity:1,y:0,duration:.55,stagger:.1},0.55);
    ftl.fromTo('.fin-trust-item',{opacity:0,y:10},{opacity:1,y:0,duration:.45,stagger:.08},0.75);
    finInner.classList.add('vis');finInner.classList.remove('r');
  }

  /* Footer */
  gsap.fromTo('.foot-top,.foot-inner',{opacity:0,y:14},{opacity:1,y:0,duration:.6,
    scrollTrigger:{trigger:'footer',start:'top 92%'}
  });

  /* Filet de sécurité : après le chargement complet (polices, images), on
     recale les déclencheurs ; puis, s'il reste un élément déjà à l'écran mais
     encore masqué (onglet ouvert en arrière-plan, rafraîchissement bloqué…),
     on le révèle. Aucune section ne peut donc rester invisible. */
  const REVEAL_SEL='.sect-kicker,.sect-h2,.sect-body,.dist-col,.dist-list li,.stab,'+
    '.scent-card,.cat-filtres,.cat-grille,.proc-step,.g-item,.nb-item,.faq-item,'+
    '.faq-sidebar,.trust-inner>*,.fin-kicker,.fin-h2,.fin-body,.fin-actions>*,'+
    '.fin-trust-item,.foot-top,.foot-inner';
  function revealStuck(){
    ScrollTrigger.refresh();
    document.querySelectorAll(REVEAL_SEL).forEach(el=>{
      const r=el.getBoundingClientRect();
      if(r.top<innerHeight+120 && parseFloat(getComputedStyle(el).opacity)<0.99){
        gsap.set(el,{clearProps:'opacity,transform,filter'});
      }
    });
    document.querySelectorAll('.proc-step').forEach(s=>{
      if(parseFloat(getComputedStyle(s).opacity)>0.99) s.classList.add('drawn');
    });
  }
  addEventListener('load',()=>{ setTimeout(revealStuck,2500); });
  document.addEventListener('visibilitychange',()=>{ if(!document.hidden) setTimeout(revealStuck,300); });

  /* ── Recalage des declencheurs quand la page change de hauteur ──────
     ScrollTrigger mesure la position de chaque section UNE fois. Si le
     document se reajuste ensuite — c'est le cas au chargement des deux
     polices, qui raccourcissent la page d'un bon millier de pixels — les
     positions memorisees pointent au-dela du bas reel et plus rien ne se
     declenche : chiffres, FAQ et appel final restent a opacity:0.
     Le filet a 2,5 s ne suffit pas (une police lente, une image sans
     dimensions, un onglet en arriere-plan et il passe trop tot).
     On recale donc sur les evenements qui causent vraiment le decalage. */
  if(document.fonts && document.fonts.ready){
    document.fonts.ready.then(()=>ScrollTrigger.refresh()).catch(()=>{});
  }
  if(window.ResizeObserver){
    let hauteur=document.documentElement.scrollHeight, minuteur=null;
    new ResizeObserver(()=>{
      const h=document.documentElement.scrollHeight;
      /* Les accordeons du catalogue changent la hauteur a chaque ouverture :
         on ne recale que sur un ecart notable, et jamais plus d'une fois
         par salve. */
      if(Math.abs(h-hauteur)<200) return;
      hauteur=h;
      clearTimeout(minuteur);
      minuteur=setTimeout(()=>ScrollTrigger.refresh(),200);
    }).observe(document.body);
  }

} /* end !REDUCED */

/* ══════════════════════════════════════════════════════════════
   §13  SCENT TABS — ANIMATED CROSSFADE
══════════════════════════════════════════════════════════════ */
/* Un panneau inactif n'etait qu'en opacity:0 : ses liens restaient
   atteignables au clavier et annonces par les lecteurs d'ecran. On le retire
   vraiment de l'ordre de tabulation avec l'attribut hidden, et on cable le
   couple aria-controls / aria-labelledby que le balisage n'avait pas. */
function setScentPanel(panel,on){
  if(!panel) return;
  panel.classList.toggle('active',on);
  if(on) panel.removeAttribute('hidden'); else panel.setAttribute('hidden','');
}
document.querySelectorAll('.stab').forEach((tab,i)=>{
  const panel=document.getElementById(tab.getAttribute('aria-controls')||'');
  if(!tab.id) tab.id='stab-'+(tab.dataset.tab||i);
  if(panel){
    panel.setAttribute('aria-labelledby',tab.id);
    panel.setAttribute('tabindex','0');
    setScentPanel(panel, panel.classList.contains('active'));
  }
  /* Fleches gauche/droite entre onglets — comportement attendu d'un tablist. */
  tab.setAttribute('tabindex', tab.classList.contains('active') ? '0' : '-1');
  tab.addEventListener('keydown',e=>{
    if(e.key!=='ArrowRight' && e.key!=='ArrowLeft') return;
    const tabs=[...document.querySelectorAll('.stab')];
    const at=tabs.indexOf(tab);
    const to=tabs[(at+(e.key==='ArrowRight'?1:tabs.length-1))%tabs.length];
    e.preventDefault(); to.focus(); to.click();
  });
});
document.querySelectorAll('.stab').forEach(tab=>{
  tab.addEventListener('click',()=>{
    const key=tab.dataset.tab;
    const current=document.querySelector('.scent-panel.active');
    const next=document.getElementById('sp-'+key);
    if(!next||current===next) return;

    /* Update tab states */
    document.querySelectorAll('.stab').forEach(t=>{
      t.classList.remove('active');
      t.setAttribute('aria-selected','false');
      t.setAttribute('tabindex','-1');
    });
    tab.classList.add('active');tab.setAttribute('aria-selected','true');
    tab.setAttribute('tabindex','0');

    if(REDUCED){
      setScentPanel(current,false);
      setScentPanel(next,true);
      return;
    }

    /* Animate out */
    gsap.to(current?.querySelectorAll('.scent-card')||[],{
      opacity:0,y:12,duration:.28,stagger:.04,ease:'power2.in',
      onComplete:()=>{
        setScentPanel(current,false);
        setScentPanel(next,true);
        /* Animate in */
        gsap.fromTo(next.querySelectorAll('.scent-card'),
          {opacity:0,y:22,scale:.97},
          {opacity:1,y:0,scale:1,duration:.5,stagger:.07,ease:'power3.out'}
        );
      }
    });
  });
});

/* ══════════════════════════════════════════════════════════════
   BARRE D'ACHAT FIXE — téléphone
   Elle sort une fois le héros passé, et s'efface devant le bloc
   « Commander maintenant » : deux fois le même bouton à l'écran, c'est un
   bouton de moins qui se remarque. Sur une fiche parfum, où il n'y a pas de
   héros, elle est là tout de suite.
══════════════════════════════════════════════════════════════ */
const ctaFixe = document.querySelector('.cta-fixe');
if(ctaFixe){
  const heros  = document.querySelector('#hero');
  /* Sur une fiche parfum, les boutons de la fiche jouent le role du bloc
     final : tant qu'ils sont a l'ecran, la barre n'a rien a ajouter. */
  const finale = document.querySelector('.finale') || document.querySelector('.pf-actions');
  let horsHeros = !heros, surFinale = false;
  const majCta = () => ctaFixe.classList.toggle('repli', !horsHeros || surFinale);

  if('IntersectionObserver' in window){
    if(heros){
      new IntersectionObserver(([e]) => {
        horsHeros = e.intersectionRatio < 0.35;   /* le héros n'occupe plus l'écran */
        majCta();
      }, {threshold:[0, 0.35, 1]}).observe(heros);
    }
    if(finale){
      new IntersectionObserver(([e]) => { surFinale = e.isIntersecting; majCta(); },
        {rootMargin:'0px 0px -25% 0px'}).observe(finale);
    }
  }else{
    horsHeros = true;   /* sans IntersectionObserver, on la laisse sortie */
  }
  majCta();
}

/* Liens sociaux : masques tant que leur href vaut « # », affiches des qu'une
   vraie URL est renseignee dans le HTML. Un lien mort coute plus cher qu'un
   lien absent. */
document.querySelectorAll('[data-social-todo]').forEach(a=>{
  const h=a.getAttribute('href')||'';
  if(h && h!=='#'){ a.hidden=false; a.target='_blank'; a.rel='noopener'; }
});

/* ══════════════════════════════════════════════════════════════
   §16  SMOOTH ANCHOR SCROLLING — défilement natif + passage du focus
   ScrollToPlugin n'est pas chargé : on s'appuie sur le défilement natif
   (scroll-behavior:smooth) et on passe le focus à la cible pour que le
   clavier et les lecteurs d'écran reprennent au bon endroit.
══════════════════════════════════════════════════════════════ */
document.querySelectorAll('a[href^="#"]').forEach(a=>{
  a.addEventListener('click',e=>{
    const raw=a.getAttribute('href');
    if(!raw||raw==='#')return;
    /* Les liens « #catalogue?maison=… » sont pris en charge par le filtre
       catalogue (écouteur hashchange) — on laisse la navigation native. */
    if(/[?&]maison=/.test(raw))return;
    const id=raw.split(/[?&]/)[0];
    if(id.length<2)return;
    let target=null;
    try{ target=document.querySelector(id); }catch(_){ return; }
    if(!target)return;
    e.preventDefault();
    const y=target.getBoundingClientRect().top+window.scrollY-(nav?.offsetHeight||0)-20;
    window.scrollTo({top:y,behavior:REDUCED?'auto':'smooth'});
    target.setAttribute('tabindex','-1');
    target.focus({preventScroll:true});
  });
});

/* ══════════════════════════════════════════════════════════════
   §17  FALLBACK — NO GSAP LOADED
══════════════════════════════════════════════════════════════ */
function fallbackMode(){
  /* Replicate original behavior without GSAP */
  const loader=document.getElementById('loader');
  const played=memLire('cp_v3');
  function endLoader(){
    if(loader){loader.style.opacity='0';loader.style.visibility='hidden';setTimeout(()=>loader.remove(),900)}
    memEcrire('cp_v3','1');
    startHeroFB();
  }
  if(played || cpSautePrechargeur()){if(loader)loader.remove();startHeroFB()}
  else if(loader){
    requestAnimationFrame(()=>{loader.classList.add('go');setTimeout(endLoader,1200)});
    document.getElementById('ldr-skip')?.addEventListener('click',endLoader);
  } else { startHeroFB() }

  function startHeroFB(){
    if(REDUCED){
      ['he','hbadge','hs','ha','ht','hv'].forEach(id=>document.getElementById(id)?.classList.add('in'));
      document.querySelectorAll('.h1-inner').forEach(el=>el.classList.add('in'));
      document.getElementById('hnc1')?.classList.add('visible');
      document.getElementById('hnc2')?.classList.add('visible');
      return;
    }
    setTimeout(()=>document.getElementById('he')?.classList.add('in'),80);
    document.querySelectorAll('.h1-inner').forEach(el=>{
      setTimeout(()=>el.classList.add('in'),parseInt(el.dataset.delay)||300);
    });
    setTimeout(()=>document.getElementById('hbadge')?.classList.add('in'),780);
    setTimeout(()=>document.getElementById('hs')?.classList.add('in'),920);
    setTimeout(()=>document.getElementById('ha')?.classList.add('in'),1060);
    setTimeout(()=>document.getElementById('ht')?.classList.add('in'),1180);
    setTimeout(()=>document.getElementById('hv')?.classList.add('in'),600);
    setTimeout(()=>document.getElementById('hnc1')?.classList.add('visible'),1500);
    setTimeout(()=>document.getElementById('hnc2')?.classList.add('visible'),1800);
  }

  /* Nav sticky */
  const nav=document.getElementById('nav');
  window.addEventListener('scroll',()=>nav?.classList.toggle('stuck',window.scrollY>80),{passive:true});

  /* Marquee (défilement CSS, liens maison actifs) */
  const mqBrands=['Chanel','Dior','Tom Ford','Xerjoff','Yves Saint Laurent','Armani','Hermès','Creed','MFK','Parfums de Marly','Prada','Versace','Givenchy','Kayali','Louis Vuitton','Valentino','Lancôme','Gucci','Jean Paul Gaultier','Narciso Rodriguez'];
  const mq=document.getElementById('mq-track');
  if(mq){
    mq.innerHTML=marqueeHTML(mqBrands);
    if(REDUCED){
      mq.classList.add('is-static');
      mq.style.animation='none';
      mq.querySelectorAll('[aria-hidden="true"].mq-item').forEach(el=>el.remove());
    }
  }

  /* Scroll reveal */
  if('IntersectionObserver' in window){
    const ro=new IntersectionObserver(entries=>{
      entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('vis');ro.unobserve(e.target)}});
    },{threshold:.1,rootMargin:'0px 0px -40px 0px'});
    document.querySelectorAll('.r,.r-left,.r-right').forEach(el=>ro.observe(el));
  } else {
    document.querySelectorAll('.r,.r-left,.r-right').forEach(el=>el.classList.add('vis'));
  }

  /* Tabs */
  document.querySelectorAll('.stab').forEach(tab=>{
    tab.addEventListener('click',()=>{
      document.querySelectorAll('.stab').forEach(t=>{t.classList.remove('active');t.setAttribute('aria-selected','false')});
      document.querySelectorAll('.scent-panel').forEach(p=>p.classList.remove('active'));
      tab.classList.add('active');tab.setAttribute('aria-selected','true');
      document.getElementById('sp-'+tab.dataset.tab)?.classList.add('active');
    });
  });

  /* Mobile menu */
  const ham=document.getElementById('nav-ham');
  const mob=document.getElementById('mob-menu');
  let mOpen=false;
  if(ham&&mob){
    ham.addEventListener('click',()=>{
      mOpen=!mOpen;
      mob.classList.toggle('open',mOpen);
      ham.setAttribute('aria-expanded',mOpen);
      document.body.style.overflow=mOpen?'hidden':'';
    });
    document.querySelectorAll('[data-close]').forEach(a=>a.addEventListener('click',()=>{if(mOpen){mOpen=false;mob.classList.remove('open');ham.setAttribute('aria-expanded','false');document.body.style.overflow=''}}));
  }

  /* Smooth anchors */
  document.querySelectorAll('a[href^="#"]').forEach(a=>{
    a.addEventListener('click',e=>{
      const id=a.getAttribute('href');if(id==='#')return;
      const t=document.querySelector(id);
      if(t){e.preventDefault();const y=t.getBoundingClientRect().top+window.scrollY-(nav?.offsetHeight||0)-16;window.scrollTo({top:y,behavior:'smooth'})}
    });
  });
}

})();

/* ══════════════════════════════════════════════════════════════
   III. BASCULE DE THEME — clair / sombre, memorisee
══════════════════════════════════════════════════════════════ */
(function(){
  var root=document.documentElement;
  var btn=document.getElementById('theme-toggle');
  var meta=document.querySelector('meta[name="theme-color"]');
  function apply(light){
    if(light) root.setAttribute('data-theme','light');
    else root.removeAttribute('data-theme');
    if(btn){
      btn.setAttribute('aria-pressed', light?'true':'false');
      btn.setAttribute('aria-label', light?'Basculer en mode sombre':'Basculer en mode clair');
    }
    if(meta) meta.setAttribute('content', light?'#f8f3ea':'#110814');
    try{ localStorage.setItem('cp_theme', light?'light':'dark'); }catch(e){}
  }
  var isLight = root.getAttribute('data-theme')==='light';
  apply(isLight);
  if(btn) btn.addEventListener('click',function(){ isLight=!isLight; apply(isLight); });
})();
