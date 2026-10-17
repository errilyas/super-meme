/* ══════════════════════════════════════════════════════════════
   CSS DU PREMIER ECRAN — fiche parfum et page commande (theme 3.9.13)

   A coller dans la console d'une page du site (Studio ou production), puis :
     const r = await cpCritique();          // ~2 min
     r.fiche, r.commande                    // CSS a enregistrer
     r.ecarts                               // doit etre vide

   Ce que fait l'outil :
   1. lit style-site.css et boutique.css tels qu'ecrits (texte source, pas le
      CSSOM du navigateur, qui jette les proprietes qu'il ne connait pas et
      dont Safari a parfois besoin) ;
   2. ouvre chaque page dans des cadres de 360, 412, 768 et 1366 px de large,
      en sombre et en clair, en francais et en arabe, une fois JavaScript
      coupe (le premier affichage, avant les scripts differes) et une fois
      apres que boutique.js a pose le formulaire express, puis garde toute
      regle dont le
      selecteur touche un element du premier ecran, un element qui le
      precede dans le document, un enfant d'un element visible, ou un
      element positionne (fixe, absolu, colle) : sans sa regle, un tiroir ou
      une barre cachee retomberait dans le flux et pousserait la page ;
   3. verifie : dans chaque cadre, il remplace les feuilles completes par le
      CSS extrait et compare la position, la taille et l'aspect de chaque
      element visible. Tout ecart est liste dans r.ecarts.

   functions.php n'imprime ce CSS que si les empreintes notees en tete du
   fichier correspondent aux feuilles en place : une feuille modifiee sans
   relancer l'outil ramene la fiche a la feuille bloquante, sans casse.
══════════════════════════════════════════════════════════════ */
window.cpCritique = async function (o) {
  o = Object.assign({
    origine: location.origin,
    concurrence: 3,
    attente: 2500,
    marge: 1.25,          // zone retenue : 125 % de la hauteur d'ecran
    largeurs: [[360, 780], [412, 915], [768, 1024], [1366, 900]],
    fiches: ['valentino--uomo-born-in-roma-intense', 'emporio-armani--stronger-with-you-intensely', 'carolina-herrera--good-girl-blush'],
  }, o || {});
  const THEME = o.origine + '/wp-content/themes/comptoir-parfums/';
  const SOURCES = [THEME + 'style-site.css', o.origine + '/wp-content/plugins/comptoir-boutique/boutique.css'];

  /* ── 1. Lecture du CSS source ───────────────────────────────── */
  function analyse(txt) {
    let i = 0;
    const n = txt.length;
    const chaine = (q) => { let j = i + 1; while (j < n && txt[j] !== q) { if (txt[j] === '\\') j++; j++; } return j + 1; };
    const commentaire = () => { const e = txt.indexOf('*/', i + 2); i = e < 0 ? n : e + 2; };
    /* Lit jusqu'a un des arrets, hors chaines, commentaires et parentheses. */
    function lit(arrets) {
      let out = '', prof = 0;
      while (i < n) {
        const c = txt[i];
        if (c === '/' && txt[i + 1] === '*') { commentaire(); out += ' '; continue; }
        if (c === '"' || c === "'") { const j = chaine(c); out += txt.slice(i, j); i = j; continue; }
        if (c === '(' || c === '[') prof++;
        else if (c === ')' || c === ']') prof--;
        else if (prof === 0 && arrets.includes(c)) return [out, c];
        out += c; i++;
      }
      return [out, ''];
    }
    /* Contenu brut jusqu'a l'accolade fermante correspondante. */
    function jusquaFermeture() {
      let out = '', prof = 1;
      while (i < n) {
        const c = txt[i];
        if (c === '/' && txt[i + 1] === '*') { commentaire(); out += ' '; continue; }
        if (c === '"' || c === "'") { const j = chaine(c); out += txt.slice(i, j); i = j; continue; }
        if (c === '{') prof++;
        else if (c === '}' && --prof === 0) { i++; return out; }
        out += c; i++;
      }
      return out;
    }
    function bloc() {
      const noeuds = [];
      for (;;) {
        while (i < n && /\s/.test(txt[i])) i++;
        if (txt.startsWith('/*', i)) { commentaire(); continue; }
        if (i >= n) return noeuds;
        if (txt[i] === '}') { i++; return noeuds; }
        const [pre, arret] = lit('{;}');
        if (arret === ';') { i++; noeuds.push({ t: 'a', pre: pre.trim(), corps: null }); continue; }
        if (arret !== '{') { if (arret === '}') i++; return noeuds; }
        i++;
        const p = pre.trim();
        if (/^@(media|supports|layer|container)\b/i.test(p)) noeuds.push({ t: 'g', pre: p, kids: bloc() });
        else if (p[0] === '@') noeuds.push({ t: 'a', pre: p, corps: jusquaFermeture() });
        else noeuds.push({ t: 'r', sel: p, corps: jusquaFermeture() });
      }
    }
    return bloc();
  }

  /* Decoupe au niveau 0 (hors chaines, parentheses, crochets). */
  function decoupe(s, sep) {
    const out = [];
    let cur = '', prof = 0, q = '';
    for (let i = 0; i < s.length; i++) {
      const c = s[i];
      if (q) { cur += c; if (c === '\\') { cur += s[++i] || ''; } else if (c === q) q = ''; continue; }
      if (c === '"' || c === "'") { q = c; cur += c; continue; }
      if (c === '(' || c === '[') prof++;
      else if (c === ')' || c === ']') prof--;
      if (c === sep && prof === 0) { out.push(cur); cur = ''; continue; }
      cur += c;
    }
    out.push(cur);
    return out;
  }
  /* Applique f aux seuls morceaux hors chaines. */
  function horsChaines(s, f) {
    let out = '', i = 0, debut = 0;
    while (i < s.length) {
      const c = s[i];
      if (c === '"' || c === "'") {
        out += f(s.slice(debut, i));
        let j = i + 1; while (j < s.length && s[j] !== c) { if (s[j] === '\\') j++; j++; }
        out += s.slice(i, j + 1); i = debut = j + 1; continue;
      }
      i++;
    }
    return out + f(s.slice(debut));
  }
  const serre = (s) => horsChaines(s, (x) => x.replace(/\s+/g, ' ')).trim();
  const selMin = (s) => decoupe(serre(s), ',').map((x) => horsChaines(x.trim(), (y) => y.replace(/\s*([>~+])\s*/g, '$1'))).join(',');
  const corpsMin = (c) => decoupe(c, ';').map((d) => {
    const k = d.indexOf(':');
    if (k < 0) return '';
    return d.slice(0, k).trim() + ':' + horsChaines(serre(d.slice(k + 1)), (y) => y.replace(/\s*,\s*/g, ',').replace(/\s*!\s*important/i, '!important'));
  }).filter(Boolean).join(';');

  const textes = await Promise.all(SOURCES.map((u) => fetch(u + '?critique=' + Date.now(), { cache: 'no-store' }).then((r) => r.text())));
  const empreinte = async (t) => {
    const h = await crypto.subtle.digest('SHA-1', new TextEncoder().encode(t));
    return Array.from(new Uint8Array(h)).map((b) => b.toString(16).padStart(2, '0')).join('');
  };
  const empreintes = await Promise.all(textes.map(empreinte));
  /* Toutes les regles, a plat, dans l'ordre du document. */
  const arbres = textes.map(analyse);
  const regles = [];
  (function aplatit(noeuds) {
    for (const nd of noeuds) {
      if (nd.t === 'r') { nd.id = regles.length; regles.push(nd); }
      else if (nd.t === 'g') aplatit(nd.kids);
    }
  })([].concat(...arbres));

  /* ── 2. Correspondance dans une page ouverte ────────────────── */
  const PSEUDO_EL = /::?(before|after|first-line|first-letter|placeholder|selection|marker|backdrop|file-selector-button|-webkit-[\w-]+|-moz-[\w-]+|-ms-[\w-]+)(\([^()]*\))?/gi;
  const ETAT = /:(hover|focus-visible|focus-within|focus|active|visited|link|any-link|target)(?![\w-])/gi;
  function pourTest(sel) {
    let s = horsChaines(sel, (x) => x.replace(PSEUDO_EL, '').replace(ETAT, ''));
    s = s.replace(/:(not|is|where)\(\s*\)/g, '').trim();
    if (!s || /[>+~]$/.test(s)) s += '*';
    return s;
  }
  const tests = regles.map((r) => decoupe(r.sel, ',').map((p) => ({ brut: p, test: pourTest(p) })));

  /* Les elements dont les regles comptent pour le premier ecran. « dernier »
     ne regarde que le flux : la barre d'achat fixee en bas de l'ecran est
     imprimee en fin de page, et la compter aurait fait entrer toute la page.
     Les enfants d'un element visible ne comptent que dans une grille ou une
     boite flex, ou un frere cache peut encore prendre de la place. */
  function elementsUtiles(doc, w, h) {
    h = h * o.marge;
    const vue = doc.defaultView;
    const tous = Array.from(doc.querySelectorAll('*'));
    const Q = new Set();
    const horsFlux = new Set();
    const fixes = new Set();
    let dernier = -1;
    tous.forEach((el, k) => {
      const cs = vue.getComputedStyle(el);
      const pos = cs.position;
      if (pos === 'fixed') { el.classList.forEach((c) => fixes.add('.' + c)); if (el.id) fixes.add('#' + el.id); }
      if (pos === 'fixed' || pos === 'absolute' || horsFlux.has(el.parentElement)) horsFlux.add(el);
      if (pos === 'fixed' || pos === 'absolute' || pos === 'sticky') Q.add(el);
      const r = el.getBoundingClientRect();
      const visible = (r.width > 0 || r.height > 0) && r.bottom > 0 && r.top < h && r.right > 0 && r.left < w;
      if (!visible) return;
      Q.add(el);
      if (!horsFlux.has(el)) dernier = k;
      if (/flex|grid/.test(cs.display)) for (const c of el.children) Q.add(c);
    });
    for (let k = 0; k <= dernier; k++) Q.add(tous[k]);
    Q.fixes = fixes;
    return Q;
  }
  /* Un element fixe (barre d'achat, en-tete, tiroir) change d'etat par
     classes posees au defilement (.repli, .cpb-cache, .open…) : ses regles
     d'etat ne correspondent a rien au moment de l'extraction. On garde toute
     regle dont le sujet (dernier maillon du selecteur, hors :not()) porte
     une classe ou l'id d'un element fixe. */
  function sujetFixe(sel, fixes) {
    const nu = sel.replace(/:not\([^()]*\)/g, '').replace(/\[[^\]]*\]/g, '').replace(/\([^()]*\)/g, '');
    const maillons = nu.split(/[\s>+~]+/).filter(Boolean);
    const sujet = maillons[maillons.length - 1] || '';
    return (sujet.match(/[.#][\w-]+/g) || []).some((x) => fixes.has(x));
  }
  function retenues(doc, Q, garde) {
    regles.forEach((r, k) => {
      if (garde.has(k)) return;
      for (const p of tests[k]) {
        if (sujetFixe(p.brut, Q.fixes)) { garde.add(k); return; }
        let els;
        try { els = doc.querySelectorAll(p.test); } catch (e) {
          if (/-moz-|-ms-/.test(p.brut)) continue;
          garde.add(k); return;
        }
        for (const el of els) if (Q.has(el)) { garde.add(k); return; }
      }
    });
  }

  /* ── Scenarios ─────────────────────────────────────────────── */
  /* Paniers : un article (la page propose un deuxieme parfum), deux
     (livraison offerte, remise duo), trois. */
  const paniers = [
    JSON.stringify([{ s: o.fiches[0], q: 1 }]),
    JSON.stringify([{ s: o.fiches[0], q: 1 }, { s: o.fiches[1], q: 1 }]),
    JSON.stringify([{ s: o.fiches[0], q: 1 }, { s: o.fiches[1], q: 2 }]),
  ];
  /* Une commande recente (moins de six heures) : sinon l'ecran merci
     s'affiche sans le detail des lignes. */
  const merci = JSON.stringify({
    ref: 'CP-TEST-0000', prenom: 'Test', ville: 'Casablanca', montant: 778, articles: 2,
    lignes: [{ s: o.fiches[0], nom: 'Valentino Uomo Born in Roma Intense', q: 1, prix: 389 }, { s: o.fiches[1], nom: 'Emporio Armani Stronger With You Intensely', q: 1, prix: 389 }],
    livraison: 0, sousTotal: 778, remise: 0, t: Date.now(),
  });
  const scenarios = [];
  for (const theme of ['dark', 'light']) {
    for (const lang of ['fr', 'ar']) {
      for (const [w, h] of o.largeurs) {
        o.fiches.forEach((s, k) => {
          if (k > 0 && w !== 412) return;   // les autres fiches : une largeur suffit
          scenarios.push({ page: 'fiche', theme, lang, w, h, url: '/?parfum=' + s + '&lang=' + lang });
        });
        if (w === 412) scenarios.push({ page: 'fiche', theme, lang, w, h, url: '/?parfum=' + o.fiches[2] + '&lang=' + lang, panier: paniers[0] });
        paniers.concat('[]').forEach((pn) => scenarios.push({ page: 'commande', theme, lang, w, h, url: '/?commander=1&lang=' + lang, panier: pn }));
        scenarios.push({ page: 'commande', theme, lang, w, h, url: '/?commander=1&merci=1&lang=' + lang, panier: '[]', merci });
        /* Le premier affichage a lieu avant les scripts differes : meme page,
           JavaScript coupe. C'est l'etat ou .pf-x-place reserve la place du
           formulaire express ; une fois le formulaire pose, plus rien ne
           correspond a cette regle. */
        o.fiches.forEach((s, k) => {
          if (k > 0 && w !== 412) return;
          scenarios.push({ page: 'fiche', theme, lang, w, h, url: '/?parfum=' + s + '&lang=' + lang, sansJs: true });
        });
        scenarios.push({ page: 'commande', theme, lang, w, h, url: '/?commander=1&lang=' + lang, sansJs: true });
      }
    }
  }

  /* localStorage est partage entre les cadres : on charge par lots de meme
     etat (theme, panier, remerciement). */
  const cle = (s) => [s.theme, s.panier || '', s.merci || '', s.sansJs ? 'sansJs' : ''].join('|');
  const lots = {};
  scenarios.forEach((s) => { (lots[cle(s)] = lots[cle(s)] || []).push(s); });

  function ouvre(s) {
    return new Promise((ok) => {
      const f = document.createElement('iframe');
      f.style.cssText = 'position:fixed;left:0;top:0;width:' + s.w + 'px;height:' + s.h + 'px;border:0;opacity:0.01;pointer-events:none;z-index:-1';
      /* Sans JavaScript, les deux scripts du <head> (langue, theme) ne
         tournent pas : on pose leurs attributs a leur place. */
      if (s.sansJs) f.sandbox = 'allow-same-origin';
      f.onload = () => setTimeout(() => {
        if (s.sansJs) {
          const de = f.contentDocument.documentElement;
          if (s.theme === 'light') de.setAttribute('data-theme', 'light');
          if (s.lang === 'ar') { de.setAttribute('data-lang', 'ar'); de.setAttribute('lang', 'ar'); de.setAttribute('dir', 'rtl'); }
        }
        ok(f);
      }, o.attente);
      f.src = o.origine + s.url + '&critique=' + Math.random().toString(36).slice(2);
      document.body.appendChild(f);
    });
  }
  async function parLots(liste, travail) {
    const res = [];
    for (const k of Object.keys(lots)) {
      const lot = liste.filter((s) => cle(s) === k);
      if (!lot.length) continue;
      const s0 = lot[0];
      localStorage.setItem('cp_theme_choisi', s0.theme);
      localStorage.setItem('cp-panier-v1', s0.panier || '[]');
      if (s0.merci) localStorage.setItem('cp_commande_faite', s0.merci); else localStorage.removeItem('cp_commande_faite');
      for (let i = 0; i < lot.length; i += o.concurrence) {
        const tranche = lot.slice(i, i + o.concurrence);
        const cadres = await Promise.all(tranche.map(ouvre));
        tranche.forEach((s, j) => { try { res.push(travail(s, cadres[j].contentDocument)); } catch (e) { res.push({ s, erreur: String(e) }); } cadres[j].remove(); });
      }
    }
    return res;
  }

  const garde = { fiche: new Set(), commande: new Set() };
  await parLots(scenarios, (s, doc) => retenues(doc, elementsUtiles(doc, s.w, s.h), garde[s.page]));

  /* ── Assemblage ─────────────────────────────────────────────── */
  function assemble(g) {
    const animations = new Set();
    regles.forEach((r, k) => {
      if (!g.has(k)) return;
      const m = r.corps.match(/animation(-name)?\s*:[^;]+/gi);
      if (m) m.forEach((d) => d.split(':')[1].split(/[\s,]+/).forEach((x) => animations.add(x)));
    });
    const sortie = (noeuds) => noeuds.map((nd) => {
      if (nd.t === 'r') return g.has(nd.id) ? selMin(nd.sel) + '{' + corpsMin(nd.corps) + '}' : '';
      if (nd.t === 'g') { const dedans = sortie(nd.kids); return dedans ? serre(nd.pre) + '{' + dedans + '}' : ''; }
      const kf = nd.pre.match(/^@(-webkit-)?keyframes\s+([\w-]+)/i);
      if (kf && animations.has(kf[2])) return serre(nd.pre) + '{' + serre(nd.corps).replace(/\s*([{};:,])\s*/g, '$1') + '}';
      if (/^@font-face/i.test(nd.pre)) return '@font-face{' + corpsMin(nd.corps) + '}';
      return '';
    }).join('');
    return sortie([].concat(...arbres));
  }
  const css = { fiche: assemble(garde.fiche), commande: assemble(garde.commande) };

  /* ── 3. Verification : feuilles completes contre CSS extrait ── */
  const PROPS = ['display', 'opacity', 'visibility', 'color', 'background-color', 'font-family', 'font-size', 'font-weight', 'line-height', 'transform'];
  function chemin(el) {
    const p = [];
    for (let e = el; e && e.nodeType === 1 && p.length < 4; e = e.parentElement) p.unshift(e.tagName.toLowerCase() + (e.id ? '#' + e.id : (e.classList[0] ? '.' + e.classList[0] : '')));
    return p.join('>');
  }
  function verifie(s, doc) {
    const vue = doc.defaultView;
    /* Le CSS deja imprime par le theme fausserait la comparaison. */
    const deja = doc.getElementById('cp-critique');
    if (deja) deja.remove();
    const fige = doc.createElement('style');
    fige.textContent = '*,*::before,*::after{transition:none!important;animation-play-state:paused!important}';
    doc.head.appendChild(fige);
    const tous = Array.from(doc.body.querySelectorAll('*'));
    const mesure = () => tous.map((el) => {
      const r = el.getBoundingClientRect();
      const cs = vue.getComputedStyle(el);
      return { r: [r.left, r.top, r.width, r.height].map(Math.round), c: PROPS.map((p) => cs.getPropertyValue(p)).join('|') };
    });
    const avant = mesure();
    const feuilles = Array.from(doc.querySelectorAll('link[rel="stylesheet"]')).filter((l) => /style-site\.css|boutique\.css|comptoir-parfums\/style\.css/.test(l.href));
    const crit = doc.createElement('style');
    crit.textContent = css[s.page];
    doc.head.insertBefore(crit, doc.head.firstChild);
    feuilles.forEach((l) => { l.disabled = true; });
    const apres = mesure();
    /* Ce qui compte pour le CLS : un element a l'ecran (dans un rendu ou
       l'autre) qui change de place ou de largeur, ou d'aspect. Une hauteur
       qui change sous le bas de l'ecran, ou le tiroir du panier range hors
       champ, ne se voient pas. */
    const ecarts = [];
    const vu = (x) => x.r[2] > 0 && x.r[3] > 0 && x.r[1] < s.h && x.r[1] + x.r[3] > 0 && x.r[0] < s.w && x.r[0] + x.r[2] > 0;
    tous.forEach((el, k) => {
      const a = avant[k], b = apres[k];
      if (!vu(a) && !vu(b)) return;
      const diffR = [0, 1, 2].some((j) => Math.abs(a.r[j] - b.r[j]) > 1);
      if (diffR || a.c !== b.c) {
        const pa = a.c.split('|'), pb = b.c.split('|');
        ecarts.push({ el: chemin(el), r: diffR ? [a.r, b.r] : undefined, props: PROPS.filter((p, j) => pa[j] !== pb[j]) });
      }
    });
    return { s: s.page + ' ' + s.theme + ' ' + s.lang + ' ' + s.w + ' ' + s.url + (s.sansJs ? ' sans JS' : ''), ecarts: ecarts.slice(0, 12), total: ecarts.length };
  }
  const controle = await parLots(scenarios, verifie);

  return {
    fiche: css.fiche,
    commande: css.commande,
    tailles: { fiche: css.fiche.length, commande: css.commande.length },
    entete: '/* critique ' + SOURCES.map((u, k) => u.split('/').pop() + '=' + empreintes[k]).join(' ') + ' */',
    ecarts: controle.filter((c) => c.erreur || c.total),
    scenarios: scenarios.length,
  };
};
