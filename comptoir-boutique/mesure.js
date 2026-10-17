/* Comptoir Boutique : mesure Google Analytics 4.
   Charge AVANT panier.js (voir comptoir-boutique.php) : la vue de fiche part
   des le chargement du panier et l'achat des l'ouverture du remerciement, ils
   doivent trouver l'enveloppe en place. Le panier et le catalogue n'existent
   pas encore ici : on les lit au moment de traduire. */
(function () {
  'use strict';
  var INDEX = null;
  function produit(s) {
    if (!INDEX) { INDEX = {}; (window.PRODUITS || []).forEach(function (p) { if (p && p.s) { INDEX[p.s] = p; } }); }
    return Object.prototype.hasOwnProperty.call(INDEX, s) ? INDEX[s] : null;
  }
  function prix(p) { var P = window.Panier; return P && P.prix ? P.prix(p) : parseInt(String(p.pr).replace(/\D/g, ''), 10) || 0; }

  /* ══════════════════════════════════════════════════════════════
     GOOGLE ANALYTICS 4 : DES VENTES QU'ANALYTICS COMPREND
     Le theme envoie a gtag les memes donnees qu'a Meta (content_ids,
     value) et nomme l'achat « Purchase » : GA4 ne reconnait que
     « purchase » avec une liste items[]. Ici on traduit au passage,
     sans toucher au pixel Meta : chaque evenement du theme recoit ses
     items (nom, maison, prix, quantite), l'achat devient « purchase »
     avec la reference en transaction_id (GA4 ignore alors un doublon si
     la page de remerciement est rechargee).
  ══════════════════════════════════════════════════════════════ */
  var GA4_NOMS = { Purchase: 'purchase', AddToCart: 'add_to_cart', InitiateCheckout: 'begin_checkout', Lead: 'generate_lead', ViewContent: 'view_item' };
  function articleGA(p, q) {
    return { item_id: p.s, item_name: p.n, item_brand: p.b, item_category: 'Parfum', price: prix(p), quantity: q || 1 };
  }
  function commandeFaite() {
    try { return JSON.parse(localStorage.getItem('cp_commande_faite') || 'null'); } catch (e) { return null; }
  }
  function traduitGA(nom, d) {
    nom = GA4_NOMS[nom] || nom;
    if (!d || !d.content_ids || d.items) { return [nom, d]; }
    var qte = {}, cmd = null;
    if (nom === 'purchase' || nom === 'generate_lead') {
      cmd = commandeFaite();
      if (cmd && cmd.lignes) { cmd.lignes.forEach(function (l) { if (l.s) { qte[l.s] = l.q; } }); }
    }
    if (nom === 'begin_checkout' && window.Panier) { window.Panier.items().forEach(function (x) { qte[x.s] = x.q; }); }
    var items = [];
    [].concat(d.content_ids).forEach(function (s) {
      var p = produit(s);
      if (p) { items.push(articleGA(p, qte[s] || (nom === 'add_to_cart' && d.value ? Math.max(1, Math.round(d.value / (prix(p) || d.value))) : 1))); }
    });
    var o = { currency: d.currency || 'MAD', value: d.value, items: items };
    if (nom === 'purchase' && cmd && cmd.ref) {
      o.transaction_id = cmd.ref;
      if (cmd.livraison) { o.shipping = cmd.livraison; }
    }
    return [nom, o];
  }
  function mesureGA4() {
    var g = window.gtag;
    if (typeof g !== 'function' || g.cpb) { return; }
    var enveloppe = function (type, nom, d) {
      if (type === 'event' && typeof nom === 'string') {
        try { var t2 = traduitGA(nom, d); nom = t2[0]; d = t2[1]; } catch (e) {}
        return arguments.length > 3 ? g.call(this, type, nom, d, arguments[3]) : g.call(this, type, nom, d);
      }
      return g.apply(this, arguments);
    };
    enveloppe.cpb = true;
    window.gtag = enveloppe;
  }

  try { mesureGA4(); } catch (e) {}
})();
