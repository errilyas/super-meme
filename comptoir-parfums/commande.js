/* ══════════════════════════════════════════════════════════════
   LE COMPTOIR DES PARFUMS — PAGE COMMANDE
   Source unique, partagee par :
     - commander.html            (maquette)
     - template-commander.php    (theme WordPress)

   Prerequis : panier.js charge avant ce fichier.
   Aucun paiement en ligne : le formulaire ne fait qu'ouvrir WhatsApp avec
   la commande et les coordonnees pre-remplies.
══════════════════════════════════════════════════════════════ */
(function(){
  'use strict';
  var P = window.Panier;
  if(!P) return;                     /* panier.js absent : rien a piloter */
  var WA  = P.cfg.wa;
  /* Traduction, avec repli sur le francais si langue.js manque. */
  var T = function (fr, r) { return window.CP_T ? window.CP_T(fr, r) : fr; };
  var IMG = P.cfg.imgBase || 'img/produits/';
  /* Meme empreinte que partout ailleurs : sans elle le navigateur ressert
     la photo qu'il a en cache, fond blanc compris. */
  var IMG_V = P.cfg.imgVer ? '?v=' + P.cfg.imgVer : '';

  /* thème (bouton absent ici mais on garde la mémoire) */
  var root=document.documentElement;
  var meta=document.querySelector('meta[name="theme-color"]');
  if(root.getAttribute('data-theme')==='light' && meta) meta.setAttribute('content','#f8f3ea');

  var full=document.getElementById('ck-full'), empty=document.getElementById('ck-empty');
  var itemsEl=document.getElementById('ck-items'), sumsEl=document.getElementById('ck-sums');
  var form=document.getElementById('ck-form');

  if(!full||!empty||!itemsEl||!sumsEl||!form) return;

  var ask = document.getElementById('ck-ask');
  if(ask) ask.href =
    'https://wa.me/'+WA+'?text='+encodeURIComponent('Bonjour Le Comptoir des Parfums, j’ai une question avant de commander.');

  function esc(s){return String(s).replace(/[&<>"]/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];});}

  var checkoutMesure = false;

  function draw(){
    var items = P.items();
    full.hidden = !items.length;
    empty.hidden = !!items.length;
    if(!items.length) return;
    if(!checkoutMesure){
      checkoutMesure = true;
      P.mesure('InitiateCheckout', {
        value: P.total(), currency: 'MAD',
        num_items: P.count(), content_type: 'product',
        content_ids: items.map(function(x){ return x.p.s; })
      }, P.idEvenement ? P.idEvenement('ic') : '');
    }

    itemsEl.innerHTML = items.map(function(x){
      return '<div class="ck-item" data-s="'+esc(x.s)+'">'+
        (P.aPhoto && P.aPhoto(x.s) !== false
          ? '<img class="is-empty" alt="" loading="lazy" src="'+IMG+esc(x.s)+'.webp'+IMG_V+'" data-thumb>'
          : '<img class="is-empty" alt="">')+
        '<div><div class="ck-ib">'+esc(x.p.b)+'</div><div class="ck-in">'+esc(x.p.n)+'</div>'+
          '<div class="ck-qty"><button type="button" data-a="dec" aria-label="'+esc(T('Retirer un'))+'">−</button><span>'+x.q+'</span><button type="button" data-a="inc" aria-label="'+esc(T('Ajouter un'))+'">+</button></div>'+
        '</div>'+
        '<div><div class="ck-ip">'+P.fmt(P.prix(x.p)*x.q)+'</div>'+
          '<button class="ck-del" type="button" data-a="del">' + T('Retirer') + '</button></div>'+
      '</div>';
    }).join('');

    /* La vignette garde sa colonne dans la grille : si la photo echoue on
       efface le src, on ne retire jamais l'element — sinon le texte glissait
       dans la colonne de l'image. */
    itemsEl.querySelectorAll('[data-thumb]').forEach(function(im){
      im.addEventListener('load',function(){ im.classList.remove('is-empty'); });
      im.addEventListener('error',function(){ im.removeAttribute('src'); });
      if(im.complete && im.naturalWidth>0){ im.classList.remove('is-empty'); }
    });
    itemsEl.querySelectorAll('.ck-item').forEach(function(row){
      var s=row.getAttribute('data-s');
      var cur=function(){var it=P.items().find(function(y){return y.s===s;});return it?it.q:0;};
      row.querySelector('[data-a="dec"]').onclick=function(){P.setQty(s,cur()-1);};
      row.querySelector('[data-a="inc"]').onclick=function(){P.setQty(s,cur()+1);};
      row.querySelector('[data-a="del"]').onclick=function(){P.remove(s);};
    });

    var liv = P.livraisonOfferte() ? T('offerte')
      : (P.cfg.livraison ? P.fmt(P.cfg.livraison) : T('à confirmer'));
    sumsEl.innerHTML =
      '<div class="ck-sum"><span>'+T('Sous-total')+'</span><span>'+P.fmt(P.subtotal())+'</span></div>'+
      '<div class="ck-sum"><span>'+T('Livraison')+'</span><span>'+liv+'</span></div>'+
      '<div class="ck-sum tot"><span>'+T('Total')+'</span><b>'+P.fmt(P.total())+'</b></div>';

    /* Il ne manque qu'un flacon pour la livraison offerte : c'est le moment
       de le dire, panier sous les yeux, avant de remplir l'adresse. */
    var franco = document.getElementById('ck-franco');
    if(franco){
      var manque = P.manquePourFranco();
      franco.hidden = manque !== 1;
      /* Trois flacons voisins a ajouter sans quitter la page (Panier
         .suggestionsHTML) : le lien vers le catalogue faisait sortir du
         formulaire au moment le plus fragile. Il reste, pour qui veut choisir. */
      franco.innerHTML = manque === 1
        ? (P.suggestionsHTML ? P.suggestionsHTML(3) : '')
          + '<a class="sug-cat" href="'+P.cfg.home+'#catalogue">'+T('Voir le catalogue')+'</a>'
        : '';
    }

    /* Le meme total sur le bouton : le client sait ce qu'il tendra au livreur
       avant de partir sur WhatsApp, et ne le decouvre pas a la porte. */
    document.querySelectorAll('[data-ck-total]').forEach(function(el){
      el.textContent = P.fmt(P.total());
    });
  }

  P.onChange(draw);
  draw();

  /* on efface le rouge dès que l'utilisateur corrige */
  form.addEventListener('input', function(e){
    var f = e.target.closest('.ck-field'); if(f) f.classList.remove('err');
  });

  /* Un numéro marocain : 0X suivi de 8 chiffres, ou +212 / 212 suivi de 9.
     L'ancien test « au moins 8 chiffres » laissait passer un numéro tronqué,
     et un numéro faux, c'est une livraison perdue. */
  function telValide(v){
    var n = v.replace(/[\s().-]/g,'').replace(/^(?:\+|00)/,'');
    if(/^212[5-7][0-9]{8}$/.test(n)) return true;   /* 212 6 12 34 56 78 */
    if(/^0[5-7][0-9]{8}$/.test(n))   return true;   /* 06 12 34 56 78    */
    return false;
  }

  function champInvalide(inp){
    var v = inp.value.trim();
    if(!v) return true;
    if(inp.name === 'tel')     return !telValide(v);
    if(inp.name === 'adresse') return v.length < 8;   /* « rue » seul ne suffit pas */
    if(inp.name === 'ville')   return v === 'autre' && !villeAutre();
    return false;
  }

  /* Ville hors liste : le champ texte n'apparait que si on la demande. */
  var selVille  = form.querySelector('[name="ville"]');
  var blocAutre = document.getElementById('ck-ville-autre');
  var inpAutre  = form.querySelector('[name="ville_autre"]');

  function villeAutre(){ return inpAutre ? inpAutre.value.trim() : ''; }

  if(selVille && blocAutre){
    selVille.addEventListener('change', function(){
      blocAutre.hidden = selVille.value !== 'autre';
      if(!blocAutre.hidden && inpAutre) inpAutre.focus();
    });
  }

  /* Ecran de remerciement. Il prend la place du formulaire : le panier est
     vide a ce moment-la, sinon un retour en arriere le montrerait encore
     plein et laisserait croire que rien n'est parti. */
  function merci(data, c){
    var bloc = document.getElementById('ck-merci');
    if(!bloc) return;
    data = data || {};
    c = c || {};

    /* « Merci Youssef » plutot que « Merci » : le prenom suffit, le nom
       complet sur un ecran de confirmation fait formulaire administratif. */
    var titre = document.getElementById('ck-merci-titre');
    var prenom = data.prenom || (data.nom || '').trim().split(/\s+/)[0];
    if(titre) titre.textContent = prenom
      ? T('Merci {prenom}, c\'est noté.', {prenom: prenom})
      : T('Merci, c\'est noté.');

    var ref = document.getElementById('ck-merci-ref');
    if(ref && data.ref) ref.textContent = T('Référence {ref}', {ref: data.ref});

    /* Le detail de ce qui a ete commande : le panier est vide juste apres,
       c'est la seule trace qui reste a l'ecran. */
    var det = document.getElementById('ck-merci-lignes');
    if(det && c.lignes && c.lignes.length){
      var h = c.lignes.map(function(l){
        return '<div class="ck-ml"><span>' + (l.q > 1 ? l.q + '× ' : '') + esc(l.nom)
          + '</span><span>' + P.fmt(l.prix) + '</span></div>';
      });
      h.push('<div class="ck-ml"><span>' + T('Sous-total') + '</span><span>' + P.fmt(c.sousTotal) + '</span></div>');
      /* La ville s'affiche dans la langue du client ; ce qui est parti au
         carnet de commandes reste le nom francais (cf. CP_VILLE). */
      var villeVue = data.ville && window.CP_VILLE ? window.CP_VILLE(data.ville) : data.ville;
      h.push('<div class="ck-ml"><span>' + T('Livraison') + (villeVue ? ' — ' + esc(villeVue) : '')
        + '</span><span>' + (c.livraison ? P.fmt(c.livraison) : T('offerte')) + '</span></div>');
      h.push('<div class="ck-ml tot"><span>' + T('Total à payer au livreur') + '</span><b>' + P.fmt(c.montant) + '</b></div>');
      det.innerHTML = h.join('');
    }

    var wa = document.getElementById('ck-merci-wa');
    if(wa){ wa.href = c.lien || 'https://wa.me/' + WA; wa.hidden = !c.lien; }

    /* Le fil d'etapes passe a la confirmation. */
    var fil = document.getElementById('ck-steps');
    if(fil){
      fil.querySelectorAll('[data-etape]').forEach(function(e){
        e.classList.toggle('en-cours', e.getAttribute('data-etape') === '3');
        e.classList.toggle('faite', e.getAttribute('data-etape') !== '3');
      });
    }

    full.hidden = true;
    empty.hidden = true;
    bloc.hidden = false;

    /* Le titre et l'introduction du formulaire n'ont plus rien a dire ici :
       « Remplissez vos coordonnees » au-dessus d'une commande deja passee. */
    var titrePage = document.querySelector('h1.ck-h');
    var intro = document.querySelector('.ck-intro');
    if(titrePage) titrePage.hidden = true;
    if(intro) intro.hidden = true;

    window.scrollTo({ top: 0 });

    /* L'achat, au sens des regies publicitaires. Mesure une seule fois par
       commande : la page de confirmation peut etre rechargee. L'event_id
       (la reference) part aussi au serveur (CAPI, voir comptoir_capi_achat
       dans functions.php) : Meta reconnait le doublon et ne compte l'achat
       qu'une fois. */
    if(c.montant && data.ref && !dejaFait('cp_achat_' + data.ref)){
      marqueFait('cp_achat_' + data.ref);
      /* content_ids manquait : content_type etait annonce sans dire de quels
         produits il s'agissait, alors que la copie serveur (CAPI) les envoyait.
         Les commandes mises de cote avant cette version n'ont pas de slug dans
         leurs lignes : on n'envoie alors pas le parametre plutot qu'une liste
         de valeurs vides. */
      var donneesAchat = {
        value: c.montant, currency: 'MAD',
        num_items: c.articles, content_type: 'product'
      };
      var idsAchat = (c.lignes || []).map(function(l){ return l.s; }).filter(Boolean);
      if (idsAchat.length) { donneesAchat.content_ids = idsAchat; }
      P.mesure('Purchase', donneesAchat, data.ref);
    }
  }

  /* « Deja fait » pour tout le navigateur, pas pour un seul onglet. Le retour
     depuis WhatsApp rouvre souvent la confirmation dans un NOUVEL onglet, ou
     sessionStorage repart vide : l'achat repartait vers Meta (5 a 6 fois sur
     les 28 premieres commandes) et WhatsApp se rouvrait. localStorage d'abord,
     sessionStorage en repli quand il est bloque. */
  function dejaFait(key){
    try { if (localStorage.getItem(key)) return true; } catch(e){}
    try { return !!sessionStorage.getItem(key); } catch(e){ return false; }
  }
  function marqueFait(key){
    try { localStorage.setItem(key, '1'); return; } catch(e){}
    try { sessionStorage.setItem(key, '1'); } catch(e){}
  }

  /* Page de confirmation. Elle se charge comme une page normale, et se
     remplit avec la commande mise de cote juste avant. Un rafraichissement, un
     retour depuis WhatsApp ou le bouton « precedent » retombent ici. */
  if(/[?&]merci=1/.test(location.search)){
    var enregistree = null;
    try{
      var brut = localStorage.getItem('cp_commande_faite');
      if(brut){
        enregistree = JSON.parse(brut);
        /* Au-dela de six heures, la commande n'est plus « celle qu'on vient de
           passer » : on affiche le remerciement sans le detail. */
        if(!enregistree.t || Date.now() - enregistree.t > 6*3600*1000) enregistree = null;
      }
    }catch(e){}

    merci(enregistree || {}, enregistree || {});

    /* WhatsApp part une fois la page bien en place, et une seule fois : si la
       bascule emporte l'onglet, la confirmation est deja chargee derriere. */
    if(enregistree && enregistree.lien && !dejaFait('cp_wa_' + enregistree.ref)){
      marqueFait('cp_wa_' + enregistree.ref);
      setTimeout(function(){ window.open(enregistree.lien, '_blank', 'noopener'); }, 900);
    }
  }

  /* validation + envoi */
  var submitting = false;
  function submitOrder(e){
    if(e) e.preventDefault();
    if(submitting) return;
    var data={}, ok=true;
    form.querySelectorAll('.ck-field').forEach(function(f){
      if(f.hidden) return;                       /* « autre ville » replie */
      var inp=f.querySelector('input,select');
      if(!inp) return;
      var v=inp.value.trim();
      data[inp.name]=v;
      var bad = champInvalide(inp);
      f.classList.toggle('err', bad);
      if(bad) ok=false;
    });
    /* La ville envoyee est celle qu'on livre, pas le mot « autre ». */
    if(data.ville === 'autre') data.ville = villeAutre();
    delete data.ville_autre;

    if(!ok){
      var premier = form.querySelector('.ck-field.err input, .ck-field.err select');
      if(premier){ premier.focus(); premier.scrollIntoView({block:'center',behavior:'smooth'}); }
      return;
    }
    if(!P.count()) return;

    /* Dans cet ordre, et pas un autre : on depose la commande, on mesure,
       puis on ouvre WhatsApp — l'ouverture doit rester dans le geste du
       clic, sinon le navigateur la bloque. */
    submitting = true;
    data.ref = P.reference();
    var montant = P.total(), articles = P.count(), lien = P.waHref(data);
    /* s : le slug du produit. Il ne sert a aucun affichage, mais la page de
       confirmation en a besoin pour content_ids — le panier est vide quand
       elle s'ouvre, elle ne peut plus le deduire. */
    var lignes = P.items().map(function(x){
      return { s: x.p.s, nom: x.p.b + ' ' + x.p.n, q: x.q, prix: P.prix(x.p) * x.q };
    });
    var fraisLiv = P.fraisLivraison(), sousTotal = P.subtotal();

    P.depose(data);
    P.mesure('Lead', {
      value: montant, currency: 'MAD',
      num_items: articles, content_type: 'product',
      content_ids: lignes.map(function(l){ return l.s; })
    }, data.ref);

    /* LA CONFIRMATION EST UNE VRAIE PAGE, pas un changement d'affichage.
       Deux tentatives ont echoue avant celle-ci : afficher l'ecran apres avoir
       ouvert WhatsApp, puis avant. Sur telephone, la bascule vers l'application
       emporte l'onglet, et un ecran pose en JavaScript disparait avec lui.
       On memorise donc la commande, on quitte la page pour de bon, et le
       navigateur charge /?commander=1&merci=1 comme n'importe quelle page :
       elle existe alors vraiment, et le retour de WhatsApp la retrouve. */
    var confirmation = {
        ref: data.ref, prenom: (data.nom||'').trim().split(/\s+/)[0],
        ville: data.ville, lien: lien, montant: montant, articles: articles,
        lignes: lignes, livraison: fraisLiv, sousTotal: sousTotal, t: Date.now()
    };
    var saved = false;
    try{
      localStorage.setItem('cp_commande_faite', JSON.stringify(confirmation));
      saved = true;
    }catch(e){}
    if(!saved){
      P.clear();
      merci(confirmation, confirmation);
      return;
    }

    P.clear();
    location.href = (P.cfg.commander || '?commander=1')
      + (String(P.cfg.commander||'').indexOf('?') >= 0 ? '&' : '?') + 'merci=1';
  }
  document.getElementById('ck-go').addEventListener('click', submitOrder);
  form.addEventListener('submit', submitOrder);
})();
