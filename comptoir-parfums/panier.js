/* Identifiants Meta (ajout 2026-09-21) : garde le clic pub (fbclid) pour que
   l'achat serveur (CAPI) soit rattache a la pub, meme si le cookie _fbc manque. */
(function(){
  try {
    var clid = new URLSearchParams(location.search).get('fbclid');
    if (clid) {
      var fbc = 'fb.1.' + Date.now() + '.' + clid;
      localStorage.setItem('cp_fbc', JSON.stringify({ v: fbc, t: Date.now() }));
      if (!/(^|;)\s*_fbc=/.test(document.cookie)) {
        document.cookie = '_fbc=' + fbc + ';path=/;max-age=7776000;SameSite=Lax';
      }
    }
  } catch (e) {}
  /* Provenance (ajout) : d'ou vient ce visiteur. Sans elle, une commande que
     Meta n'attribue pas est indistinguable d'une commande venue d'ailleurs, et
     on en est reduit a deduire. Memorisee des la premiere page vue : la
     commande se passe souvent plusieurs pages plus loin. */
  try {
    var q = new URLSearchParams(location.search);
    var src = {
      fbclid:       q.get('fbclid') || '',
      utm_source:   q.get('utm_source') || '',
      utm_medium:   q.get('utm_medium') || '',
      utm_campaign: q.get('utm_campaign') || '',
      referent:     document.referrer || ''
    };
    var porteUneCampagne = src.fbclid || src.utm_source || src.utm_medium || src.utm_campaign;
    var deja = null;
    try { deja = JSON.parse(localStorage.getItem('cp_provenance') || 'null'); } catch (e) {}
    /* On n'ecrase une provenance connue que si la nouvelle porte un identifiant
       de campagne : une navigation interne ne doit pas effacer le clic pub qui
       a amene le visiteur. */
    if (porteUneCampagne || !deja) {
      localStorage.setItem('cp_provenance', JSON.stringify({ v: src, t: Date.now() }));
    }
  } catch (e) {}

  window.cpProvenance = function(){
    try {
      var s = JSON.parse(localStorage.getItem('cp_provenance') || 'null');
      if (s && s.v && Date.now() - s.t < 7776000000) { return s.v; }
    } catch (e) {}
    return {};
  };

  window.cpIdsMeta = function(){
    var lire = function(n){ var m = document.cookie.match('(^|;)\\s*' + n + '=([^;]+)'); return m ? m[2] : ''; };
    var fbc = lire('_fbc');
    if (!fbc) { try { var s = JSON.parse(localStorage.getItem('cp_fbc') || 'null'); if (s && Date.now() - s.t < 7776000000) fbc = s.v; } catch (e) {} }
    return { fbc: fbc, fbp: lire('_fbp') };
  };
})();

/* ══════════════════════════════════════════════════════════════
   LE COMPTOIR DES PARFUMS — PANIER
   Panier client (localStorage) + tiroir latéral, dans l'esprit
   du site mimi-cocoon. Fonctionne tel quel sur :
     - comptoirv3-motion.html (maquette)
     - parfum.html            (fiche parfum)
     - commander.html         (page commande)
     - le thème WordPress (mêmes fichiers)

   Prérequis : window.PRODUITS défini AVANT ce fichier (produits.js côté
   maquette, injection PHP côté WordPress). window.PRODUITS_BY_SLUG est
   utilisé s'il existe, sinon déduit.

   API globale :
     Panier.add(slug[, qty])   Panier.remove(slug)   Panier.setQty(slug, q)
     Panier.items()  ->  [{s, q, p}]   (p = fiche produit)
     Panier.count()  Panier.subtotal()  Panier.total()  Panier.clear()
     Panier.open()   Panier.close()
     Panier.onChange(fn)

   Câblage automatique dans la page :
     [data-panier-open]      -> ouvre le tiroir
     [data-panier-count]     -> reçoit le nombre d'articles (badge)
     [data-panier-add="slug"]-> bouton « ajouter au panier »

   Réglages : window.CP_PANIER = { wa:'2126…', livraison:35, commander:'commander.html' }
══════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  /* Traduction. Repli sur le francais si langue.js n'est pas charge :
     la maquette statique ne le charge pas, et le panier doit marcher. */
  var T = function (fr, r) { return window.CP_T ? window.CP_T(fr, r) : fr; };

  var CFG = Object.assign({
    ajax: '',                      // point de dépôt des commandes (WordPress)
    nonce: '',                     // jeton de sécurité qui va avec
    feuille: '',                   // script Google qui écrit dans la feuille
    franco: 2,                     // livraison offerte à partir de N articles
    wa: '212717961180',            // numéro WhatsApp (sans +)
    livraison: 35,                 // frais de livraison, DH — 0 = « à confirmer »
    commander: 'commander.html',   // URL de la page commande
    home: 'comptoirv3-motion.html',// URL de l'accueil (pour le lien catalogue)
    imgBase: 'img/produits/',      // base des photos (WordPress : URL absolue du thème)
    imgVer: '',                    // empreinte de version, contre le cache navigateur
    key: 'cp-panier-v1'
  }, window.CP_PANIER || {});

  var listeners = [];

  /* Vrai si la photo <slug>.webp est réellement sur le disque. Sans la liste
     on tente la requête : le pire cas reste l'ancien comportement. */
  function aPhoto(slug) {
    var l = window.CP_PRODUCT_IMAGES;
    return l ? l.indexOf(slug + '.webp') !== -1 : true;
  }

  /* Index par slug. produits.js le fournit deja (maquette) ; sinon on le
     construit depuis window.PRODUITS. Le theme WordPress n'injecte que le
     tableau ordonne : envoyer aussi l'index, c'etait 70 Ko des memes donnees
     sur chaque page. */
  var index = null;
  var byslug = function (s) {
    if (!index) {
      index = window.PRODUITS_BY_SLUG;
      if (!index) {
        index = {};
        (window.PRODUITS || []).forEach(function (p) { index[p.s] = p; });
        window.PRODUITS_BY_SLUG = index;
      }
    }
    return Object.prototype.hasOwnProperty.call(index, s) ? index[s] : null;
  };
  var priceNum = function (p) { return p ? parseInt(String(p.pr).replace(/[^0-9]/g, ''), 10) || 0 : 0; };
  /* L'unite passe par T() : en arabe c'est « درهم », et pas seulement par
     gout — « 279 DH » place dans une phrase arabe ressort « DH 279 ». */
  var fmt = function (n) {
    var s = String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ' + T('DH');
    /* « 1 399 » est coupe par son espace dans une phrase arabe et ressort
       « 399 1 ». CP_NOMBRE isole le nombre ; en francais il ne fait rien. */
    return window.CP_NOMBRE ? window.CP_NOMBRE(s) : s;
  };
  var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };

  /* ── état ── */
  var memoryCart = [], storageFailed = false;
  function cleanCart(a) {
    var rows = [];
    if (!Array.isArray(a)) return rows;
    a.forEach(function (x) {
      if (!x || typeof x.s !== 'string') return;
      var p = byslug(x.s), q = Number(x.q);
      if (!p || !p.s || !Number.isSafeInteger(q) || q < 1 || q > 999) return;
      var row = rows.find(function (y) { return y.s === x.s; });
      if (row) row.q = Math.min(999, row.q + q);
      else rows.push({ s: x.s, q: q });
    });
    return rows;
  }
  function read() {
    try { if (!storageFailed) memoryCart = cleanCart(JSON.parse(localStorage.getItem(CFG.key))); }
    catch (e) { /* Keep this page's cart when storage is unavailable. */ }
    return memoryCart.map(function (x) { return { s: x.s, q: x.q }; });
  }
  function write(a) {
    memoryCart = cleanCart(a);
    try { localStorage.setItem(CFG.key, JSON.stringify(memoryCart)); storageFailed = false; } catch (e) { storageFailed = true; }
    emit();
  }
  function emit() {
    var c = P.count();
    document.querySelectorAll('[data-panier-count]').forEach(function (el) {
      el.textContent = c;
      el.toggleAttribute('data-empty', c === 0);
    });
    render();
    listeners.forEach(function (fn) { try { fn(P.items()); } catch (e) {} });
  }

  /* ── Mesure ───────────────────────────────────────────────────────
     Un seul point de sortie pour les evenements de conversion. Meta et
     Google recoivent chacun le nom qu'ils comprennent ; si aucune des deux
     balises n'est chargee, la fonction ne fait rien et ne casse rien.
     Les balises, elles, se posent dans functions.php > comptoir_mesure(). */
  var NOM_GA4 = {
    AddToCart: 'add_to_cart',
    InitiateCheckout: 'begin_checkout',
    Lead: 'generate_lead',
    ViewContent: 'view_item'
  };

  /* eventId (optionnel) : passe en troisieme argument a fbq pour que Meta
     rapproche cet evenement pixel de son double envoye par le serveur
     (Conversions API, comptoir_capi_achat dans functions.php) au lieu de
     compter l'achat deux fois. */
  /* Identifiant d'evenement (ajout) : AddToCart, InitiateCheckout et Lead
     partaient sans, donc le double serveur pose par WordPress.com comptait
     un second evenement. Une reference commune suffit a ce que Meta les
     rapproche, exactement comme Purchase le fait deja avec cp_ref. */
  function idEvenement(prefixe) {
    var r = '';
    try { if (window.crypto && window.crypto.randomUUID) r = window.crypto.randomUUID(); } catch (e) {}
    if (!r) r = Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
    return prefixe + '.' + r;
  }

  function mesure(nom, donnees, eventId) {
    donnees = donnees || {};
    try {
      if (typeof window.fbq === 'function') {
        if (eventId) window.fbq('track', nom, donnees, { eventID: String(eventId) });
        else window.fbq('track', nom, donnees);
      }
      if (typeof window.gtag === 'function') window.gtag('event', NOM_GA4[nom] || nom, donnees);
    } catch (e) { /* la mesure ne doit jamais empecher une vente */ }
  }

  var P = {
    mesure: mesure,
    idEvenement: idEvenement,

    add: function (slug, qty, discret) {
      var p = byslug(slug);
      qty = qty === undefined ? 1 : Number(qty);
      if (!p || !Number.isSafeInteger(qty) || qty < 1 || qty > 999) return;
      var a = read(), row = a.find(function (x) { return x.s === slug; });
      if (row) row.q = Math.min(999, row.q + qty); else a.push({ s: slug, q: qty || 1 });
      /* discret : ajout depuis une suggestion, sans ouvrir le tiroir — sur la
         page commande, il couvrirait le formulaire qu'on est en train de
         remplir. Le message « ajoute au panier » suffit. */
      write(a); if (!discret) P.open(); flash(slug);
      mesure('AddToCart', {
        content_ids: [slug], content_type: 'product',
        content_name: p.b + ' ' + p.n,
        value: priceNum(p) * (qty || 1), currency: 'MAD'
      }, idEvenement('atc'));
    },
    /* Achat direct depuis une fiche : le parfum regarde entre au panier (une
       fois, pas une de plus s'il y est deja) et le client part droit au
       formulaire, sans passer par le tiroir. C'est le chemin principal :
       « Ajouter au panier » reste la pour qui veut un deuxieme flacon. */
    acheter: function (slug) {
      var p = byslug(slug);
      if (!p) return;
      var a = read();
      if (!a.some(function (x) { return x.s === slug; })) {
        a.push({ s: slug, q: 1 });
        write(a);
        mesure('AddToCart', {
          content_ids: [slug], content_type: 'product',
          content_name: p.b + ' ' + p.n,
          value: priceNum(p), currency: 'MAD'
        }, idEvenement('atc'));
      }
      location.href = CFG.commander;
    },
    /* Le deuxieme flacon, propose la ou il se decide. La livraison devient
       offerte au deuxieme parfum, mais le panier se contentait de le DIRE :
       il fallait repartir au catalogue pour en choisir un. Trois flacons
       proches du premier — meme public (homme, femme ou mixte), prix voisin,
       photo disponible — a ajouter d'un appui. */
    suggestions: function (n) {
      var items = P.items();
      if (!items.length) return [];
      var ref = items[0].p, dans = {};
      items.forEach(function (x) { dans[x.s] = true; });
      var prixRef = priceNum(ref), maisons = {};
      /* Une maison par suggestion : trois Tom Ford a cote d'un Tom Ford, c'est
         un seul choix presente trois fois. */
      return (window.PRODUITS || []).filter(function (p) {
        return !dans[p.s] && aPhoto(p.s) && (p.g === ref.g || p.g === 'Mixte' || ref.g === 'Mixte');
      }).sort(function (a, b) {
        return Math.abs(priceNum(a) - prixRef) - Math.abs(priceNum(b) - prixRef) || (a.s < b.s ? -1 : 1);
      }).filter(function (p) {
        if (maisons[p.b]) return false;
        maisons[p.b] = true;
        return true;
      }).slice(0, n || 3);
    },
    suggestionsHTML: function (n) {
      var s = P.suggestions(n);
      if (!s.length) return '';
      return '<div class="sug" aria-label="' + esc(T('Un deuxième parfum ?')) + '">' +
        '<div class="sug-titre">' + T('Un deuxième parfum ? La livraison devient offerte.') + '</div>' +
        '<div class="sug-rang">' + s.map(function (p) {
          var img = CFG.imgBase + p.s + '.webp' + (CFG.imgVer ? '?v=' + CFG.imgVer : '');
          return '<div class="sug-carte">' +
            '<img src="' + img + '" alt="" loading="lazy" decoding="async">' +
            '<div class="sug-b">' + esc(p.b) + '</div>' +
            '<div class="sug-n">' + esc(p.n) + '</div>' +
            '<div class="sug-p">' + fmt(priceNum(p)) + '</div>' +
            '<button type="button" class="sug-add" data-panier-plus="' + esc(p.s) + '">' + T('+ Ajouter') + '</button>' +
          '</div>';
        }).join('') + '</div></div>';
    },
    remove: function (slug) { write(read().filter(function (x) { return x.s !== slug; })); },
    setQty: function (slug, q) {
      q = Number(q);
      if (!Number.isSafeInteger(q) || q < 0 || q > 999) return;
      if (q === 0) return P.remove(slug);
      var a = read(), row = a.find(function (x) { return x.s === slug; });
      if (row) { row.q = q; write(a); }
    },
    items: function () {
      return read().map(function (x) { return { s: x.s, q: x.q, p: byslug(x.s) }; })
        .filter(function (x) { return x.p; });
    },
    count: function () { return P.items().reduce(function (n, x) { return n + x.q; }, 0); },
    subtotal: function () { return P.items().reduce(function (n, x) { return n + priceNum(x.p) * x.q; }, 0); },
    /* Livraison offerte des le deuxieme flacon. Une seule fonction decide,
       le tiroir, la page commande et le message WhatsApp la consultent. */
    fraisLivraison: function () {
      var n = P.count();
      if (!n) return 0;
      if (CFG.franco && n >= CFG.franco) return 0;
      return CFG.livraison;
    },
    livraisonOfferte: function () { return P.count() >= CFG.franco && CFG.franco > 0; },
    /* Ce qu'il manque pour l'obtenir — 0 quand elle est acquise. */
    manquePourFranco: function () {
      if (!CFG.franco || !P.count()) return 0;
      return Math.max(0, CFG.franco - P.count());
    },
    total: function () { return P.subtotal() + P.fraisLivraison(); },
    clear: function () { write([]); },
    onChange: function (fn) { listeners.push(fn); },
    open: function () {
      if (!drawer) return;
      lastFocus = document.activeElement;
      drawer.classList.add('is-open');
      drawer.removeAttribute('inert');
      drawer.removeAttribute('aria-hidden');
      document.documentElement.classList.add('pnr-lock');
      /* Le focus entre dans le tiroir, sinon le clavier reste bloque derriere
         le voile sans aucun moyen d'atteindre « Commander ». */
      var first = drawer.querySelector('.pnr-x');
      if (first) first.focus();
    },
    close: function () {
      if (!drawer) return;
      var wasOpen = drawer.classList.contains('is-open');
      drawer.classList.remove('is-open');
      drawer.setAttribute('inert', '');
      drawer.setAttribute('aria-hidden', 'true');
      document.documentElement.classList.remove('pnr-lock');
      /* On rend le focus a l'element qui a ouvert le tiroir. */
      if (wasOpen && lastFocus && document.contains(lastFocus)) {
        try { lastFocus.focus(); } catch (e) {}
      }
      lastFocus = null;
    },
    isOpen: function () { return !!drawer && drawer.classList.contains('is-open'); },
    cfg: CFG,
    fmt: fmt,
    /* Prix numerique d'une fiche produit — expose pour que la page commande
       n'ait pas a re-analyser « 279 DH » de son cote. */
    prix: priceNum,
    /* Vrai si la photo du produit existe — meme service pour commande.js. */
    aPhoto: function (slug) { return aPhoto(slug); },

    /* Reference de commande : CP-AAMMJJ-XXXX. Elle sert de langage commun
       entre le client, le message WhatsApp, le registre et la feuille — sans
       elle, « la commande de Youssef » ne designe rien au telephone. */
    reference: function () {
      var d = new Date(), p = function (n) { return ('0' + n).slice(-2); };
      var jour = String(d.getFullYear()).slice(2) + p(d.getMonth() + 1) + p(d.getDate());
      var suffixe = ('000' + Math.floor(Math.random() * 9000 + 1000)).slice(-4);
      return 'CP-' + jour + '-' + suffixe;
    },

    /* Message WhatsApp — repris par commander.html */
    waMessage: function (client) {
      client = client || {};
      var L = [T('Bonjour Le Comptoir des Parfums, je souhaite commander :'), ''];
      if (client.ref) { L.push(T('Commande') + ' ' + client.ref, ''); }
      P.items().forEach(function (x) {
        /* Le prix repasse par fmt() : ecrit tel quel, il gardait le « DH »
           de produits.php au milieu d'un message arabe dont les totaux,
           eux, disent « درهم ». En francais le resultat est identique. */
        L.push('• ' + x.q + '× ' + x.p.b + ' ' + x.p.n + ' — ' + fmt(priceNum(x.p)));
      });
      L.push('', T('Sous-total') + ' : ' + fmt(P.subtotal()));
      L.push(T('Livraison') + ' : ' + (P.livraisonOfferte() ? T('offerte')
        : (CFG.livraison ? fmt(CFG.livraison) : T('à confirmer'))));
      L.push(T('Total') + ' : ' + fmt(P.total()));
      if (client) {
        L.push('', T('Nom') + ' : ' + (client.nom || ''), T('Téléphone') + ' : ' + (client.tel || ''),
          T('Ville') + ' : ' + (client.ville || ''),
          T('Adresse') + ' : ' + (client.adresse || T('à préciser lors de l\'appel de confirmation')));
      }
      L.push('', T('Paiement à la livraison.'));
      return L.join('\n');
    },
    waHref: function (client) {
      return 'https://wa.me/' + CFG.wa + '?text=' + encodeURIComponent(P.waMessage(client));
    },

    /* Depose la commande AVANT d'ouvrir WhatsApp : un client qui n'appuie pas
       sur « Envoyer » laisse quand meme son numero, et c'est lui qu'on rappelle.
       sendBeacon part sans bloquer et survit au changement d'application ;
       en cas d'echec on ne dit rien et on laisse la vente se faire. */
    depose: function (client) {
      if (!CFG.ajax && !CFG.feuille) return false;
      var envoye = false;
      try {
        var panier = P.items().map(function (x) {
          return x.q + '× ' + x.p.b + ' ' + x.p.n + ' (' + x.p.s + ') — ' + x.p.pr;
        }).join('\n');

        var remplir = function (d, avecJeton) {
          if (avecJeton) { d.append('action', 'cp_commande'); d.append('nonce', CFG.nonce); }
          d.append('ref', client.ref || '');
          d.append('nom', client.nom || '');
          d.append('tel', client.tel || '');
          d.append('ville', client.ville || '');
          d.append('adresse', client.adresse || '');
          d.append('total', P.total());
          d.append('livraison', P.fraisLivraison());
          d.append('articles', P.count());
          d.append('panier', panier);
          d.append('lignes', JSON.stringify(P.items().map(function(x){ return {s:x.s, q:x.q}; })));
          try {
            var ids = window.cpIdsMeta ? window.cpIdsMeta() : {};
            if (ids.fbc) d.append('fbc', ids.fbc);
            if (ids.fbp) d.append('fbp', ids.fbp);
          } catch (e) {}
          /* La provenance n'entre dans aucun calcul : elle sert a relire une
             commande des semaines plus tard et savoir d'ou elle venait. */
          try {
            var prov = window.cpProvenance ? window.cpProvenance() : {};
            ['fbclid', 'utm_source', 'utm_medium', 'utm_campaign', 'referent'].forEach(function (k) {
              if (prov[k]) d.append(k, prov[k]);
            });
          } catch (e) {}
          return d;
        };

        var envoie = function (url, d) {
          if (!url) return false;
          try { if (navigator.sendBeacon && navigator.sendBeacon(url, d)) return true; } catch (e) {}
          fetch(url, { method: 'POST', body: d, keepalive: true, mode: 'no-cors' }).catch(function () {});
          return true;
        };

        if (CFG.ajax)    envoye = envoie(CFG.ajax, remplir(new FormData(), true)) || envoye;
        if (!CFG.ajax && CFG.feuille) envoye = envoie(CFG.feuille, remplir(new FormData(), false)) || envoye;
      } catch (e) { /* jamais au detriment de la vente */ }
      return envoye;
    }
  };
  window.Panier = P;
  window.addEventListener('storage', function(e){
    if (e.key === CFG.key || e.key === null) emit();
  });

  /* ── styles (thémables via --pnr-*) ── */
  var css = document.createElement('style');
  css.textContent = [
    ':root{',
    '  --pnr-bg:#170c1b; --pnr-panel:#201125; --pnr-fg:#f5f0e8; --pnr-dim:rgba(245,240,232,.6);',
    '  --pnr-line:rgba(245,240,232,.12); --pnr-accent:#d6a251; --pnr-ink:#150a12;',
    '}',
    'html.pnr-lock{overflow:hidden}',
    '.pnr-scrim{position:fixed;inset:0;z-index:1400;background:rgba(0,0,0,.5);opacity:0;pointer-events:none;transition:opacity .3s}',
    '.pnr-drawer.is-open ~ .pnr-scrim,.pnr-drawer.is-open + .pnr-scrim{opacity:1;pointer-events:auto}',
    '.pnr-drawer{position:fixed;top:0;right:0;bottom:0;z-index:1401;width:min(420px,92vw);',
    '  background:var(--pnr-bg);color:var(--pnr-fg);transform:translateX(102%);',
    '  transition:transform .38s cubic-bezier(.16,1,.3,1);display:flex;flex-direction:column;',
    '  font-family:var(--pnr-sans,system-ui,sans-serif);box-shadow:-24px 0 60px rgba(0,0,0,.4)}',
    '.pnr-drawer.is-open{transform:none}',
    '.pnr-head{display:flex;align-items:center;justify-content:space-between;padding:22px 24px;border-bottom:1px solid var(--pnr-line)}',
    '.pnr-head h2{font-family:var(--pnr-serif,Georgia,serif);font-weight:400;font-size:20px;margin:0;letter-spacing:.01em}',
    '.pnr-x{background:none;border:none;color:var(--pnr-dim);cursor:pointer;font-size:22px;line-height:1;padding:0;min-width:44px;min-height:44px;display:inline-flex;align-items:center;justify-content:center;margin:-4px -10px -4px 0}',
    '.pnr-x:hover{color:var(--pnr-fg)}',
    '.pnr-body{flex:1;overflow-y:auto;padding:8px 24px}',
    '.pnr-empty{padding:60px 0;text-align:center;color:var(--pnr-dim);font-size:14px}',
    '.pnr-empty a{color:var(--pnr-accent)}',
    '.pnr-row{display:grid;grid-template-columns:56px 1fr auto;gap:14px;padding:16px 0;border-bottom:1px solid var(--pnr-line);align-items:center}',
    '.pnr-thumb{width:56px;height:56px;border-radius:3px;object-fit:cover;background:var(--pnr-panel);border:1px solid var(--pnr-line)}',
    '.pnr-thumb.is-empty{background:var(--pnr-panel) center/20px 28px no-repeat url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 22 30\'%3E%3Cg fill=\'none\' stroke=\'%23ba913c\' stroke-width=\'1.1\' opacity=\'.5\'%3E%3Crect x=\'4.5\' y=\'9.5\' width=\'13\' height=\'19\' rx=\'1.5\'/%3E%3Crect x=\'8\' y=\'3.5\' width=\'6\' height=\'4\' rx=\'.8\'/%3E%3C/g%3E%3C/svg%3E")}',
    '.pnr-info{min-width:0}',
    '.pnr-brand{font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:var(--pnr-dim)}',
    '.pnr-name{font-family:var(--pnr-serif,Georgia,serif);font-size:15px;line-height:1.25;margin:2px 0 6px}',
    '.pnr-qty{display:inline-flex;align-items:center;gap:0;border:1px solid var(--pnr-line);border-radius:2px}',
    '.pnr-qty button{background:none;border:none;color:var(--pnr-fg);width:44px;height:44px;cursor:pointer;font-size:15px;line-height:1;display:inline-flex;align-items:center;justify-content:center}',
    '.pnr-qty button:hover{color:var(--pnr-accent)}',
    '.pnr-qty span{min-width:30px;text-align:center;font-size:13px}',
    '.pnr-line-price{font-size:13px;color:var(--pnr-accent);white-space:nowrap;text-align:right}',
    '.pnr-del{display:block;margin-top:4px;background:none;border:none;color:var(--pnr-dim);font-size:11px;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;padding:12px 0;text-align:right;width:100%;min-height:44px}',
    '.pnr-del:hover{color:var(--pnr-fg)}',
    '.pnr-foot{border-top:1px solid var(--pnr-line);padding:20px 24px 24px}',
    '.pnr-sum{display:flex;justify-content:space-between;font-size:13px;color:var(--pnr-dim);margin-bottom:6px}',
    '.pnr-sum.total{color:var(--pnr-fg);font-size:15px;margin:12px 0 16px}',
    '.pnr-sum.total b{font-family:var(--pnr-serif,Georgia,serif);font-weight:400}',
    '.pnr-cta{display:block;width:100%;text-align:center;text-decoration:none;background:var(--pnr-accent);color:var(--pnr-ink);',
    '  font-size:12px;font-weight:500;letter-spacing:.14em;text-transform:uppercase;padding:15px;border-radius:2px;border:none;cursor:pointer}',
    '.pnr-franco{font-size:12px;line-height:1.5;margin:0 0 14px;padding:9px 11px;border:1px solid var(--pnr-accent);color:var(--pnr-fg);border-radius:2px}',
    '.pnr-franco.acquis{border-style:dashed;color:var(--pnr-dim)}',
    '.pnr-cta:hover{filter:brightness(1.08)}',
    '.pnr-note{font-size:11px;color:var(--pnr-dim);text-align:center;margin-top:12px;line-height:1.6}',
    '.pnr-toast{position:fixed;left:50%;bottom:28px;transform:translateX(-50%) translateY(20px);z-index:1402;',
    '  background:var(--pnr-panel);color:var(--pnr-fg);border:1px solid var(--pnr-line);padding:12px 20px;border-radius:3px;',
    '  font-size:13px;opacity:0;pointer-events:none;transition:opacity .25s,transform .25s}',
    '.pnr-toast.show{opacity:1;transform:translateX(-50%) translateY(0)}',
    '@media(max-width:768px){.pnr-drawer{width:100vw}}'
  ].join('\n');

  /* ── DOM ── */
  var drawer, toast, lastFocus = null;
  function build() {
    document.head.appendChild(css);
    drawer = document.createElement('aside');
    drawer.className = 'pnr-drawer';
    drawer.setAttribute('role', 'dialog');
    drawer.setAttribute('aria-modal', 'true');
    drawer.setAttribute('aria-label', 'Panier');
    drawer.innerHTML =
      '<div class="pnr-head"><h2>Votre panier</h2><button class="pnr-x" type="button" aria-label="Fermer le panier">×</button></div>' +
      '<div class="pnr-body"></div>' +
      '<div class="pnr-foot" hidden></div>';
    var scrim = document.createElement('div');
    scrim.className = 'pnr-scrim';
    scrim.setAttribute('aria-hidden', 'true');
    document.body.appendChild(drawer);
    document.body.appendChild(scrim);
    toast = document.createElement('div');
    toast.className = 'pnr-toast';
    toast.setAttribute('role', 'status');
    document.body.appendChild(toast);

    /* Ferme au depart : hors de l'ordre de tabulation et hors de l'arbre
       d'accessibilite, pas seulement pousse hors de l'ecran. */
    drawer.setAttribute('inert', '');
    drawer.setAttribute('aria-hidden', 'true');

    drawer.querySelector('.pnr-x').addEventListener('click', P.close);
    scrim.addEventListener('click', P.close);
    document.addEventListener('keydown', function (e) {
      if (!P.isOpen()) return;
      if (e.key === 'Escape') { e.preventDefault(); P.close(); return; }
      if (e.key !== 'Tab') return;
      /* Piege a focus : le tiroir est modal, Tab n'en sort pas. */
      var f = drawer.querySelectorAll('a[href],button:not([disabled]),input,select,textarea,[tabindex]:not([tabindex="-1"])');
      if (!f.length) return;
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      else if (!drawer.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
    });

    document.addEventListener('click', function (e) {
      var o = e.target.closest('[data-panier-open]');
      if (o) { e.preventDefault(); P.open(); }
      var a = e.target.closest('[data-panier-add]');
      if (a) { e.preventDefault(); P.add(a.getAttribute('data-panier-add')); }
      var s = e.target.closest('[data-panier-plus]');
      if (s) { e.preventDefault(); P.add(s.getAttribute('data-panier-plus'), 1, true); }
      var b = e.target.closest('[data-panier-acheter]');
      if (b) { e.preventDefault(); P.acheter(b.getAttribute('data-panier-acheter')); }
      var f = e.target.closest('.cta-fixe-principal');
      if (f) ctaFixeClic(e, f);
    });
    P.onChange(majCtaFixe);
    majCtaFixe();

    render();
    emit();
  }

  /* BARRE D'ACHAT FIXE (telephone). Elle menait toujours a la page commande,
     panier vide ou non : un visiteur venu d'une publicite y trouvait « Votre
     panier est vide », et sur une fiche parfum le flacon qu'il regardait
     n'etait meme pas ajoute. Elle dit maintenant ce qu'elle fera :
       panier plein       -> « Commander · N parfums », vers le formulaire
       fiche, panier vide -> « Commander · 369 DH », achat direct du flacon
       ailleurs, vide     -> « Choisir mon parfum », vers le catalogue
     Sans JavaScript, le lien d'origine reste valable. */
  function ctaFixeCible(lien) {
    if (P.count()) return 'commande';
    var bar = lien.closest('.cta-fixe');
    if (bar && bar.getAttribute('data-cta-slug')) return 'achat';
    return 'catalogue';
  }
  function majCtaFixe() {
    var lien = document.querySelector('.cta-fixe-principal');
    if (!lien) return;
    var bar = lien.closest('.cta-fixe');
    var texte = lien.querySelector('.cta-fixe-texte');
    var note = lien.querySelector('.cta-fixe-note');
    var cible = ctaFixeCible(lien);
    if (!texte) return;
    if (cible === 'commande') {
      var n = P.count();
      texte.textContent = T('Commander') + ' · ' + (n > 1 ? T('{n} parfums', { n: n }) : T('1 parfum'));
      if (note) note.textContent = fmt(P.total()) + ' · ' + T('Paiement à la livraison');
    } else if (cible === 'achat') {
      texte.textContent = T('Commander') + ' · ' + (bar.getAttribute('data-cta-prix') || '');
      if (note) note.textContent = T('Paiement à la livraison');
    } else {
      texte.textContent = T('Choisir mon parfum');
      if (note) note.textContent = T('Paiement à la livraison');
    }
  }
  function ctaFixeClic(e, lien) {
    var cible = ctaFixeCible(lien);
    if (cible === 'commande') return;           /* le lien suffit */
    e.preventDefault();
    if (cible === 'achat') { P.acheter(lien.closest('.cta-fixe').getAttribute('data-cta-slug')); return; }
    var cat = document.getElementById('lp-produits') || document.getElementById('catalogue');
    if (cat) cat.scrollIntoView({ behavior: 'smooth', block: 'start' });
    else location.href = CFG.home + '#catalogue';
  }

  function flash(slug) {
    var p = byslug(slug);
    if (!toast || !p) return;
    /* Le nom du parfum reste latin, la phrase passe en arabe : les deux
       morceaux arrivent construits, l'un dans l'autre. */
    toast.textContent = T('{nom} — ajouté au panier', { nom: p.n });
    toast.classList.add('show');
    clearTimeout(flash._t);
    flash._t = setTimeout(function () { toast.classList.remove('show'); }, 2200);
  }

  function render() {
    if (!drawer) return;
    var body = drawer.querySelector('.pnr-body');
    var foot = drawer.querySelector('.pnr-foot');
    var items = P.items();

    if (!items.length) {
      body.innerHTML = '<div class="pnr-empty">' + T('Votre panier est vide.') + '<br>' +
        '<a href="' + esc(CFG.home) + '#catalogue">' + T('Parcourir le catalogue') + '</a></div>';
      foot.hidden = true;
      return;
    }

    body.innerHTML = items.map(function (x) {
      /* On ne demande la photo que si elle existe vraiment : sinon la requête
         part quand même et le navigateur journalise un 404. La liste est
         fournie par WordPress (functions.php) ou republiée par theme.js. */
      var img = aPhoto(x.s) ? (CFG.imgBase + x.s + '.webp' + (CFG.imgVer ? '?v=' + CFG.imgVer : '')) : '';
      return '<div class="pnr-row" data-s="' + esc(x.s) + '">' +
        '<img class="pnr-thumb is-empty" alt="" loading="lazy"' +
        (img ? ' src="' + img + '" data-thumb' : '') + '>' +
        '<div class="pnr-info">' +
          '<div class="pnr-brand">' + esc(x.p.b) + '</div>' +
          '<div class="pnr-name">' + esc(x.p.n) + '</div>' +
          '<div class="pnr-qty">' +
            '<button type="button" data-act="dec" aria-label="Retirer un">−</button>' +
            '<span>' + x.q + '</span>' +
            '<button type="button" data-act="inc" aria-label="Ajouter un">+</button>' +
          '</div>' +
        '</div>' +
        '<div><div class="pnr-line-price">' + fmt(priceNum(x.p) * x.q) + '</div>' +
        '<button class="pnr-del" type="button" data-act="del">' + T('Retirer') + '</button></div>' +
      '</div>';
    }).join('');

    /* La vignette garde sa place dans la grille quoi qu'il arrive : si la photo
       charge on retire .is-empty, si elle echoue on efface juste le src pour
       ne pas afficher le glyphe d'image cassee. Jamais this.remove() : la
       colonne disparaitrait et toute la ligne se decalerait. */
    body.querySelectorAll('[data-thumb]').forEach(function (im) {
      im.addEventListener('load', function () { im.classList.remove('is-empty'); });
      im.addEventListener('error', function () { im.removeAttribute('src'); });
      if (im.complete && im.naturalWidth > 0) { im.classList.remove('is-empty'); }
    });

    body.querySelectorAll('.pnr-row').forEach(function (row) {
      var s = row.getAttribute('data-s');
      row.querySelector('[data-act="dec"]').onclick = function () { P.setQty(s, qty(s) - 1); };
      row.querySelector('[data-act="inc"]').onclick = function () { P.setQty(s, qty(s) + 1); };
      row.querySelector('[data-act="del"]').onclick = function () { P.remove(s); };
    });

    var liv = P.livraisonOfferte() ? T('offerte')
      : (CFG.livraison ? fmt(CFG.livraison) : T('à confirmer'));
    var manque = P.manquePourFranco();
    /* La relance ne s'affiche que lorsqu'elle est utile : il manque un flacon,
       et le client a le tiroir sous les yeux. */
    var relance = manque === 1
      ? '<div class="pnr-franco">' + T('Ajoutez un second parfum : la livraison passe à 0 DH.') + '</div>'
      : (P.livraisonOfferte() ? '<div class="pnr-franco acquis">' + T('Livraison offerte.') + '</div>' : '');
    if (manque === 1) body.insertAdjacentHTML('beforeend', P.suggestionsHTML(3));
    foot.hidden = false;
    foot.innerHTML =
      '<div class="pnr-sum"><span>' + T('Sous-total') + '</span><span>' + fmt(P.subtotal()) + '</span></div>' +
      '<div class="pnr-sum"><span>' + T('Livraison') + '</span><span>' + liv + '</span></div>' +
      '<div class="pnr-sum total"><span>' + T('Total') + '</span><b>' + fmt(P.total()) + '</b></div>' +
      relance +
      '<a class="pnr-cta" href="' + esc(CFG.commander) + '">' + T('Commander') + '</a>' +
      '<div class="pnr-note">' + T('Paiement à la livraison, en espèces. Aucune carte bancaire.') + '</div>';
  }

  function qty(s) { var r = read().find(function (x) { return x.s === s; }); return r ? r.q : 0; }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', build);
  else build();
})();
