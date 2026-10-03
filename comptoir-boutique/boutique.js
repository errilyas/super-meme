/* ══════════════════════════════════════════════════════════════
   COMPTOIR BOUTIQUE — boutique.js
   Les outils des grandes boutiques de parfum, poses sur le theme :
     1. bandeau d'annonce (rendu par PHP, anime ici) ;
     2. recherche instantanee, depuis l'en-tete de chaque page ;
     3. quiz « Trouver mon parfum » ;
     4. favoris (coeur sur la fiche, tiroir) ;
     5. fiche parfum : « Dans le meme esprit » et « Vus recemment ».

   Une seule source : window.PRODUITS, injecte par le theme depuis
   produits.php. Le panier passe par window.Panier (panier.js du theme) :
   aucun calcul de prix ou de livraison n'est refait ici.

   Ecrit sans syntaxe recente (var, function) : une bonne part de la
   clientele navigue sur des telephones Android d'entree de gamme.
══════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  var CFG = window.CPB || {};
  var P = window.Panier;
  var LISTE = window.PRODUITS || [];
  /* Sans le panier ou le catalogue du theme, il n'y a rien a brancher. */
  if (!P || !LISTE.length) { return; }

  var AR = false;
  try { AR = typeof window.CP_LANGUE === 'function' && window.CP_LANGUE() === 'ar'; } catch (e) {}

  var REDUIT = false;
  try { REDUIT = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) {}

  /* ══════════════════════════════════════════════════════════════
     TEXTES — francais en clef, arabe en valeur
     Les noms de maisons et de parfums ne passent jamais par ici : ils
     restent tels qu'ecrits sur le flacon.
  ══════════════════════════════════════════════════════════════ */
  var AR_TXT = {
    'Rechercher un parfum': 'ابحث عن عطر',
    'Un parfum, une maison, une note…': 'عطر، دار عطور، أو نوتة…',
    'Fermer': 'إغلاق',
    'Vus récemment': 'شاهدتها مؤخرًا',
    'Les plus demandés': 'الأكثر طلبًا',
    '1 parfum': 'عطر واحد',
    '{n} parfums': '{n} عطر',
    'Affinez avec une note ou une maison pour voir les autres.': 'أضف نوتة أو اسم دار لرؤية الباقي.',
    'Aucun parfum ne correspond à « {q} ».': 'لا يوجد عطر يطابق « {q} ».',
    'Essayez une maison (Dior), une note (vanille) ou une famille (boisé).': 'جرّب اسم دار (Dior)، أو نوتة (فانيليا)، أو عائلة (خشبي).',
    'Trouver mon parfum en 4 questions': 'اكتشف عطرك في 4 أسئلة',
    'Mes favoris': 'مفضّلاتي',
    'Mes favoris ({n})': 'مفضّلاتي ({n})',
    'Recherches rapides': 'بحث سريع',
    'Ajouter à mes favoris': 'أضف إلى مفضّلاتي',
    'Dans vos favoris': 'في مفضّلاتك',
    'Retirer des favoris': 'إزالة من المفضّلات',
    'Ajouter {nom} à mes favoris': 'أضف {nom} إلى مفضّلاتي',
    'Retirer {nom} de mes favoris': 'إزالة {nom} من مفضّلاتي',
    'Aucun favori pour l’instant.': 'لا توجد مفضّلات بعد.',
    'Touchez le cœur d’une fiche parfum pour la garder ici.': 'اضغط على القلب في صفحة العطر لتحفظه هنا.',
    'Parcourir le catalogue': 'تصفّح الكتالوج',
    'Ajouter au panier': 'أضف إلى السلة',
    'Ajouté · voir le panier': 'أُضيف · عرض السلة',
    'Retirer': 'حذف',
    'Tout ajouter au panier': 'أضف الكل إلى السلة',
    'Voir la fiche': 'التفاصيل',
    'Trouver mon parfum': 'اكتشف عطرك',
    'Conseil personnalisé': 'نصيحة على مقاسك',
    'Vous hésitez ? Trouvez votre parfum en 4 questions.': 'محتار؟ اكتشف عطرك في 4 أسئلة.',
    'Dites-nous pour qui, quelle ambiance et quelles notes vous aimez : nous choisissons dans le catalogue les flacons qui vous ressemblent.': 'قل لنا لمن العطر، وأي أجواء تحب، وأي نوتات: نختار لك من الكتالوج القوارير التي تشبهك.',
    'Commencer le quiz': 'ابدأ الاختبار',
    'Pour qui ?': 'لمن العطر؟',
    'Quelle ambiance ?': 'أي أجواء؟',
    'Quel moment ?': 'متى تضعه؟',
    'Vos notes préférées': 'نوتاتك المفضّلة',
    'Question {i} sur {n}': 'السؤال {i} من {n}',
    'Votre sélection': 'اختيارك',
    'Pour qui est ce parfum ?': 'لمن هذا العطر؟',
    'Pour elle': 'لها',
    'Pour lui': 'له',
    'Peu importe': 'لا يهم',
    'Féminins et mixtes': 'نسائية وللجنسين',
    'Masculins et mixtes': 'رجالية وللجنسين',
    'Tout le catalogue': 'كل الكتالوج',
    'Quelle ambiance vous attire ?': 'أي أجواء تجذبك؟',
    'Frais et lumineux': 'منعش ومشرق',
    'Agrumes, lavande, notes marines': 'حمضيات، خزامى، نوتات بحرية',
    'Floral': 'زهري',
    'Rose, jasmin, fleur d’oranger': 'ورد، ياسمين، زهر البرتقال',
    'Gourmand et vanillé': 'حلو وبالفانيليا',
    'Vanille, caramel, fève tonka': 'فانيليا، كراميل، تونكا',
    'Boisé et oriental': 'خشبي وشرقي',
    'Oud, santal, ambre, cuir': 'عود، صندل، عنبر، جلد',
    'Quand le porterez-vous ?': 'متى ستضعه؟',
    'En journée, au bureau': 'في النهار، في العمل',
    'Léger, propre, jamais envahissant': 'خفيف، نظيف، غير مزعج',
    'Le soir, pour les occasions': 'في المساء والمناسبات',
    'Intense, qui laisse un sillage': 'قوي، يترك أثرًا',
    'Tous les jours, partout': 'كل يوم، في كل مكان',
    'Polyvalent, du matin au soir': 'متعدد الاستعمال، من الصباح إلى المساء',
    'Des notes que vous aimez ?': 'نوتات تحبها؟',
    'Plusieurs choix possibles. Rien ne vous parle ? Passez.': 'يمكنك اختيار أكثر من واحدة. لا شيء يناسبك؟ تابع.',
    'Vanille': 'فانيليا',
    'Rose': 'ورد',
    'Oud': 'عود',
    'Agrumes': 'حمضيات',
    'Musc': 'مسك',
    'Ambre': 'عنبر',
    'Café et cacao': 'قهوة وكاكاو',
    'Fruits': 'فواكه',
    'Fleur d’oranger': 'زهر البرتقال',
    'Lavande': 'خزامى',
    'Bois': 'أخشاب',
    'Épices': 'توابل',
    'Retour': 'رجوع',
    'Voir mes parfums': 'شاهد عطوري',
    'Passer cette question': 'تخطَّ هذا السؤال',
    'Trois parfums pour vous.': 'ثلاثة عطور لك.',
    'Sélectionnés parmi les {n} références du catalogue, d’après vos réponses.': 'مختارة من بين {n} مرجعًا في الكتالوج، حسب أجوبتك.',
    'Le plus proche': 'الأقرب إليك',
    'Pourquoi :': 'لماذا:',
    'Un deuxième avis ? Un conseiller vous répond sur WhatsApp.': 'تريد رأيًا ثانيًا؟ مستشارنا يجيبك على واتساب.',
    'Demander conseil': 'اطلب النصيحة',
    'Recommencer': 'أعد الاختبار',
    'Voir tout le catalogue': 'شاهد الكتالوج كاملاً',
    'Dans le même esprit': 'بنفس الروح',
    'Commander en 30 secondes': 'اطلب في 30 ثانية',
    'Rien à payer maintenant : vous réglez en espèces au livreur.': 'لا شيء تدفعه الآن: تدفع نقدًا لعامل التوصيل.',
    '1 flacon': 'قارورة واحدة',
    '2 flacons': 'قارورتان',
    'Vos coordonnées de la dernière fois sont reprises.': 'معلوماتك من المرة الماضية معبأة مسبقًا.',
    'Effacer': 'مسح',
    'Informations': 'معلومات',
    'Bonjour, j’ai passé la commande {ref}. J’aimerais y ajouter {p} ({prix}), avec la livraison offerte. Merci !': 'السلام عليكم، درت الطلب {ref}. بغيت نزيد معاه {p} ({prix})، والتوصيل مجاني. شكرا!',
    'Un deuxième parfum ?': 'عطر ثانٍ؟',
    'Avant l’expédition': 'قبل الإرسال',
    'Ajoutez un deuxième parfum : la livraison devient offerte.': 'أضف عطرًا ثانيًا: يصبح التوصيل مجانيًا.',
    'Votre colis n’est pas encore parti. Un message suffit, vous économisez {liv}.': 'طردك لم يُرسل بعد. رسالة واحدة تكفي، وتوفّر {liv}.',
    'Ajouter à ma commande': 'أضف إلى طلبي',
    'En confirmant, vous acceptez nos': 'بتأكيد الطلب، فأنت توافق على',
    'conditions de vente': 'شروط البيع',
    'Livraison estimée : entre {a} et {b}': 'التوصيل المتوقع: بين {a} و{b}',
    '2 parfums': 'عطران',
    '+ un 2e parfum au choix': '+ عطر ثانٍ من اختيارك',
    'Choisissez votre 2e parfum': 'اختر عطرك الثاني',
    'Chercher un autre parfum…': 'ابحث عن عطر آخر…',
    'Choisissez votre deuxième parfum dans la liste.': 'اختر عطرك الثاني من القائمة.',
    'Aucun parfum trouvé.': 'لم يتم العثور على أي عطر.',
    '2e parfum : {nom}': 'العطر الثاني: {nom}',
    'Livraison offerte': 'التوصيل مجاني',
    'Téléphone': 'الهاتف',
    'Nom complet': 'الاسم الكامل',
    'Ville': 'المدينة',
    'Choisissez votre ville': 'اختر مدينتك',
    'Autre ville…': 'مدينة أخرى…',
    'Laquelle ?': 'أي مدينة؟',
    'Adresse complète': 'العنوان الكامل',
    'Rue, numéro, immeuble, étage': 'الشارع، الرقم، العمارة، الطابق',
    'Nom de votre ville': 'اسم مدينتك',
    'Numéro marocain attendu, par exemple 06 12 34 56 78.': 'رقم مغربي، مثلًا 06 12 34 56 78.',
    'Merci d’indiquer votre nom.': 'المرجو كتابة اسمك.',
    'Choisissez votre ville dans la liste.': 'اختر مدينتك من القائمة.',
    'Indiquez le nom de votre ville.': 'اكتب اسم مدينتك.',
    'C’est cette adresse que le livreur suivra.': 'هذا هو العنوان الذي سيتبعه عامل التوصيل.',
    'Confirmer la commande': 'تأكيد الطلب',
    'Livraison : {l}': 'التوصيل: {l}',
    'offerte': 'مجاني',
    '+ {n} parfum(s) déjà dans votre panier': '+ {n} عطر موجود في سلتك',
    'Total à payer au livreur : {t}': 'المبلغ الذي تدفعه لعامل التوصيل: {t}',
    'WhatsApp s’ouvre ensuite avec votre commande déjà écrite.': 'سيُفتح واتساب بعد ذلك وطلبك مكتوب.',
    'En commun :': 'مشترك:',
    'Vous hésitez ?': 'محتار؟',
    'Trouvez votre parfum en 4 questions': 'اكتشف عطرك في 4 أسئلة',
    'pour elle': 'لها',
    'pour lui': 'له',
    'pour tous': 'للجميع',
    'notes : {l}': 'النوتات: {l}',
    'Bonjour, j’ai fait le quiz « Trouver mon parfum » ({r}). Il me propose : {l}. Pouvez-vous me conseiller ?': 'السلام عليكم، أجبت على اختبار « اكتشف عطرك » ({r}). اقترح عليّ: {l}. هل يمكنكم نصحي؟'
  };

  function t(fr, r) {
    var s = (AR && AR_TXT[fr] != null) ? AR_TXT[fr] : fr;
    if (r) {
      Object.keys(r).forEach(function (k) { s = s.split('{' + k + '}').join(String(r[k])); });
    }
    return s;
  }

  /* ══════════════════════════════════════════════════════════════
     OUTILS
  ══════════════════════════════════════════════════════════════ */
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  /* Cle de recherche : minuscules, sans accents. Meme regle que
     cleRecherche() du theme : « hermes » trouve « Hermès ». */
  function cle(s) {
    s = String(s == null ? '' : s);
    if (s.normalize) { s = s.normalize('NFD').replace(/[̀-ͯ]/g, ''); }
    return s.toLowerCase();
  }

  var INDEX = {};
  LISTE.forEach(function (p) { if (p && p.s) { INDEX[p.s] = p; } });
  function produit(s) { return Object.prototype.hasOwnProperty.call(INDEX, s) ? INDEX[s] : null; }

  function prixTexte(p) { return P.fmt(P.prix(p)); }

  /* Meme adresse que celle du panier, empreinte comprise : le navigateur
     reutilise l'image deja en cache. */
  function photo(p) {
    if (!P.aPhoto(p.s)) { return ''; }
    var c = P.cfg || {};
    return (c.imgBase || '') + p.s + '.webp' + (c.imgVer ? '?v=' + c.imgVer : '');
  }

  var VIDE = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='4' height='5'%3E%3C/svg%3E";

  function vignette(p, classe, l, h) {
    var src = photo(p);
    return '<img class="' + classe + (src ? '' : ' is-empty') + '" alt="" width="' + l + '" height="' + h +
      '" loading="lazy" decoding="async" src="' + esc(src || VIDE) + '">';
  }

  /* En apercu, les liens internes gardent ?apercu=boutique. */
  function avecSuffixe(u) {
    if (!CFG.suffixe) { return u; }
    var ancre = '', i = u.indexOf('#');
    if (i > -1) { ancre = u.slice(i); u = u.slice(0, i); }
    return u + (u.indexOf('?') > -1 ? '&' : '?') + CFG.suffixe + ancre;
  }
  function urlFiche(s) {
    var base = window.CP_PARFUM_BASE || ((CFG.home || '/') + '?parfum=');
    return avecSuffixe(base + encodeURIComponent(s));
  }
  function urlAccueil(ancre) { return avecSuffixe((CFG.home || '/') + (ancre || '')); }

  /* Les noms propres portent .pnr-brand / .pnr-name : la bascule arabe du
     theme ne traduit jamais ce qui est dans ces zones. */
  function maisonHTML(p) { return '<span class="pnr-brand cpb-maison">' + esc(p.b) + '</span>'; }
  function nomHTML(p) { return '<span class="pnr-name cpb-nom">' + esc(p.n) + '</span>'; }

  function notesDe(p) { return [].concat(p.t || [], p.c || [], p.f || []); }
  function famillePrincipale(p) { return String(p.fam || '').trim().split(/\s+/)[0] || ''; }

  function lireLS(k, defaut) {
    try { var v = JSON.parse(localStorage.getItem(k)); return v == null ? defaut : v; } catch (e) { return defaut; }
  }
  function ecrireLS(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }

  function dansPanier(s) {
    return P.items().some(function (x) { return x.s === s; });
  }

  var SVG = {
    loupe: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m20 20-4.6-4.6"/></svg>',
    coeur: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.3s-7.6-4.6-9.2-9.4C1.7 7.4 3.9 4 7.4 4c2 0 3.4 1.1 4.6 2.7C13.2 5.1 14.6 4 16.6 4c3.5 0 5.7 3.4 4.6 6.9-1.6 4.8-9.2 9.4-9.2 9.4z"/></svg>',
    croix: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>',
    etincelle: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6"/></svg>',
    camion: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.5 16.5V6.5h12v10M13.5 9.5h4l3 3.5v3.5h-7"/><circle cx="6" cy="17.5" r="2"/><circle cx="17" cy="17.5" r="2"/></svg>',
    wa: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>'
  };

  /* Une photo qui echoue garde sa case : on retire juste le src, la
     silhouette doree (.is-empty) prend le relais. */
  document.addEventListener('error', function (e) {
    var im = e.target;
    if (im && im.tagName === 'IMG' && im.closest && im.closest('.cpb') && im.getAttribute('src') !== VIDE) {
      im.setAttribute('src', VIDE);
      im.classList.add('is-empty');
    }
  }, true);

  /* ══════════════════════════════════════════════════════════════
     CALQUES — un seul ouvert a la fois, modal, focus piege
  ══════════════════════════════════════════════════════════════ */
  var ouvert = null, dernierFocus = null;
  var FOCUSABLES = 'a[href],button:not([disabled]),input:not([disabled]),select,textarea,[tabindex]:not([tabindex="-1"])';

  function creeCalque(classe, libelle) {
    var d = document.createElement('div');
    d.className = 'cpb cpb-calque ' + classe;
    d.hidden = true;
    d.setAttribute('role', 'dialog');
    d.setAttribute('aria-modal', 'true');
    d.setAttribute('aria-label', libelle);
    d.addEventListener('click', function (e) { if (e.target === d) { fermer(); } });
    document.body.appendChild(d);
    return d;
  }

  function visibles(racine) {
    return [].slice.call(racine.querySelectorAll(FOCUSABLES)).filter(function (el) {
      return el.offsetWidth || el.offsetHeight || el.getClientRects().length;
    });
  }

  function ouvrir(calque, cible) {
    if (ouvert && ouvert !== calque) { fermer(true); }
    if (P.isOpen && P.isOpen()) { P.close(); }
    if (!dernierFocus) { dernierFocus = document.activeElement; }
    calque.hidden = false;
    document.documentElement.classList.add('cpb-lock');
    void calque.offsetWidth;            /* le fondu part de l'etat ferme */
    calque.classList.add('is-open');
    ouvert = calque;
    var f = cible ? calque.querySelector(cible) : null;
    if (!f) { f = visibles(calque)[0]; }
    if (f) { try { f.focus({ preventScroll: true }); } catch (e) { f.focus(); } }
  }

  /* garderFocus : on enchaine sur un autre calque, le focus reviendra plus
     tard a l'element qui a ouvert le premier. */
  function fermer(garderFocus) {
    if (!ouvert) { return; }
    var c = ouvert;
    ouvert = null;
    c.classList.remove('is-open');
    c.hidden = true;
    document.documentElement.classList.remove('cpb-lock');
    if (garderFocus) { return; }
    var f = dernierFocus;
    dernierFocus = null;
    if (f && document.contains(f) && f.focus) { try { f.focus(); } catch (e) {} }
  }

  document.addEventListener('keydown', function (e) {
    if (ouvert) {
      if (e.key === 'Escape' || e.key === 'Esc') { e.preventDefault(); fermer(); return; }
      if (e.key === 'Tab') {
        var f = visibles(ouvert);
        if (!f.length) { e.preventDefault(); return; }
        var premier = f[0], dernier = f[f.length - 1];
        if (!ouvert.contains(document.activeElement)) { e.preventDefault(); premier.focus(); }
        else if (e.shiftKey && document.activeElement === premier) { e.preventDefault(); dernier.focus(); }
        else if (!e.shiftKey && document.activeElement === dernier) { e.preventDefault(); premier.focus(); }
      }
      return;
    }
    /* « / » ou Ctrl+K ouvrent la recherche, hors d'un champ de saisie. */
    var cible = e.target, tag = cible && cible.tagName;
    var saisie = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || (cible && cible.isContentEditable);
    if ((e.key === '/' && !saisie && !e.ctrlKey && !e.metaKey && !e.altKey) ||
        ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K'))) {
      if (P.isOpen && P.isOpen()) { return; }
      e.preventDefault();
      ouvrirRecherche();
    }
  });

  /* ══════════════════════════════════════════════════════════════
     1. BANDEAU D'ANNONCE
  ══════════════════════════════════════════════════════════════ */
  function bandeau() {
    var b = document.getElementById('cpb-bandeau');
    if (!b) { return; }
    var nav = document.getElementById('nav');

    /* La navigation est fixe : elle se colle au bas du bandeau, et remonte a
       mesure qu'il sort de l'ecran. On lit sa position reelle plutot que sa
       hauteur : la barre d'administration de WordPress, quand on est
       connecte, decale tout de 32 ou 46 px. */
    if (nav) {
      var attente = false;
      var place = function () {
        attente = false;
        nav.style.top = Math.max(0, Math.round(b.getBoundingClientRect().bottom)) + 'px';
      };
      var demande = function () {
        if (attente) { return; }
        attente = true;
        (window.requestAnimationFrame || setTimeout)(place);
      };
      window.addEventListener('scroll', demande, { passive: true });
      window.addEventListener('resize', demande);
      place();
    }

    /* Sur telephone, une promesse a la fois. */
    var msgs = b.querySelectorAll('.cpb-bandeau-msg');
    if (msgs.length < 2) { return; }
    /* Le minuteur n'est jamais arrete : quand la page part dans le cache
       « precedent / suivant » du navigateur, il est gele avec elle et
       repart tout seul au retour. */
    var i = 0, pause = false;
    var etroit = window.matchMedia ? window.matchMedia('(max-width: 1239px)') : null;
    var tourne = function () {
      if (pause || document.hidden || (etroit && !etroit.matches)) { return; }
      msgs[i].classList.remove('is-on');
      i = (i + 1) % msgs.length;
      msgs[i].classList.add('is-on');
    };
    setInterval(tourne, 4200);
    b.addEventListener('mouseenter', function () { pause = true; });
    b.addEventListener('mouseleave', function () { pause = false; });
    b.addEventListener('focusin', function () { pause = true; });
    b.addEventListener('focusout', function () { pause = false; });
  }

  /* ══════════════════════════════════════════════════════════════
     2. FAVORIS
  ══════════════════════════════════════════════════════════════ */
  var CLE_FAV = 'cpb-favoris-v1';
  var favMem = null;
  var tiroirFav = null;

  function favoris() {
    if (favMem === null) {
      var a = lireLS(CLE_FAV, []);
      favMem = Array.isArray(a) ? a.filter(function (s, i) {
        return typeof s === 'string' && produit(s) && a.indexOf(s) === i;
      }).slice(0, 60) : [];
    }
    return favMem.slice();
  }
  function estFavori(s) { return favoris().indexOf(s) > -1; }

  function basculeFavori(s) {
    var a = favoris(), i = a.indexOf(s);
    if (i > -1) { a.splice(i, 1); } else { a.unshift(s); }
    favMem = a.slice(0, 60);
    ecrireLS(CLE_FAV, favMem);
    majFavoris();
    return i === -1;
  }

  function libelleCoeur(btn, s) {
    var p = produit(s), on = estFavori(s);
    btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    if (btn.classList.contains('cpb-coeur-rond')) {
      btn.setAttribute('aria-label', on ? t('Retirer {nom} de mes favoris', { nom: p.n }) : t('Ajouter {nom} à mes favoris', { nom: p.n }));
    } else {
      var txt = btn.querySelector('.cpb-coeur-txt');
      if (txt) { txt.textContent = on ? t('Dans vos favoris') : t('Ajouter à mes favoris'); }
    }
  }

  function majFavoris() {
    var n = favoris().length;
    [].forEach.call(document.querySelectorAll('[data-cpb-fav-count]'), function (el) {
      el.textContent = n;
      if (n) { el.removeAttribute('data-vide'); } else { el.setAttribute('data-vide', ''); }
    });
    [].forEach.call(document.querySelectorAll('[data-cpb-coeur]'), function (btn) {
      var s = btn.getAttribute('data-cpb-coeur');
      if (produit(s)) { libelleCoeur(btn, s); }
    });
    if (tiroirFav && ouvert === tiroirFav) { rendTiroirFav(); }
  }

  window.addEventListener('storage', function (e) {
    if (e.key === CLE_FAV || e.key === null) { favMem = null; majFavoris(); }
  });
  /* Retour par le bouton « precedent » : la page sort du cache telle
     qu'elle etait, sans les favoris ajoutes entre-temps sur une autre. */
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) { favMem = null; majFavoris(); }
  });

  function coeurHTML(s, rond) {
    var p = produit(s);
    if (rond) {
      return '<button type="button" class="cpb-coeur cpb-coeur-rond" data-cpb-coeur="' + esc(s) + '" aria-pressed="false" aria-label="' +
        esc(t('Ajouter {nom} à mes favoris', { nom: p.n })) + '">' + SVG.coeur + '</button>';
    }
    return '<button type="button" class="cpb-coeur" data-cpb-coeur="' + esc(s) + '" aria-pressed="false">' + SVG.coeur +
      '<span class="cpb-coeur-txt">' + esc(t('Ajouter à mes favoris')) + '</span></button>';
  }

  function ouvrirFavoris() {
    if (!tiroirFav) {
      tiroirFav = creeCalque('cpb-favoris', t('Mes favoris'));
      tiroirFav.innerHTML =
        '<div class="cpb-panneau">' +
          '<div class="cpb-tiroir-tete"><h2>' + esc(t('Mes favoris')) + '</h2>' +
          '<button type="button" class="cpb-fermer" data-cpb-fermer aria-label="' + esc(t('Fermer')) + '">' + SVG.croix + '</button></div>' +
          '<div class="cpb-tiroir-corps"></div>' +
          '<div class="cpb-tiroir-pied" hidden></div>' +
        '</div>';
      tiroirFav.addEventListener('click', function (e) {
        var b = e.target.closest('button');
        if (!b) { return; }
        if (b.hasAttribute('data-cpb-fermer')) { fermer(); return; }
        if (b.hasAttribute('data-cpb-retire')) {
          basculeFavori(b.getAttribute('data-cpb-retire'));
          var reste = visibles(tiroirFav);
          if (reste.length) { reste[0].focus(); }
          return;
        }
        if (b.hasAttribute('data-cpb-tout')) {
          /* On ferme d'abord : le focus retourne au bouton qui a ouvert les
             favoris, et c'est lui que le panier rendra a sa fermeture. Un
             AddToCart par flacon ajoute, comme un ajout un par un. */
          var deja = {};
          P.items().forEach(function (x) { deja[x.s] = 1; });
          fermer();
          favoris().forEach(function (s) { if (!deja[s]) { P.add(s, 1, true); } });
          P.open();
        }
      });
    }
    rendTiroirFav();
    ouvrir(tiroirFav, '[data-cpb-fermer]');
  }

  function rendTiroirFav() {
    var corps = tiroirFav.querySelector('.cpb-tiroir-corps');
    var pied = tiroirFav.querySelector('.cpb-tiroir-pied');
    var liste = favoris().map(produit).filter(Boolean);
    if (!liste.length) {
      corps.innerHTML = '<div class="cpb-vide">' + SVG.coeur + esc(t('Aucun favori pour l’instant.')) + '<br>' +
        esc(t('Touchez le cœur d’une fiche parfum pour la garder ici.')) + '<br><br>' +
        '<a href="' + esc(urlAccueil('#catalogue')) + '" data-cpb-va-catalogue>' + esc(t('Parcourir le catalogue')) + '</a></div>';
      pied.hidden = true;
      pied.innerHTML = '';
      return;
    }
    corps.innerHTML = liste.map(function (p) {
      return '<div class="cpb-fav">' +
        '<a href="' + esc(urlFiche(p.s)) + '" tabindex="-1" aria-hidden="true">' + vignette(p, 'cpb-vignette', 64, 80) + '</a>' +
        '<div>' +
          '<a href="' + esc(urlFiche(p.s)) + '">' +
            '<span class="cpb-ligne-maison">' + maisonHTML(p) + '</span>' +
            '<span class="cpb-ligne-nom">' + nomHTML(p) + '</span>' +
          '</a>' +
          '<span class="cpb-ligne-prix">' + prixTexte(p) + '</span>' +
          '<div class="cpb-fav-actions">' +
            boutonAjout(p) +
            '<button type="button" class="cpb-lien-btn" data-cpb-retire="' + esc(p.s) + '">' + esc(t('Retirer')) + '</button>' +
          '</div>' +
        '</div>' +
      '</div>';
    }).join('');
    pied.hidden = false;
    pied.innerHTML = '<button type="button" class="cpb-btn-plein" data-cpb-tout>' + esc(t('Tout ajouter au panier')) + '</button>';
  }

  /* ══════════════════════════════════════════════════════════════
     AJOUT AU PANIER depuis un calque
     Discret : le panier ne s'ouvre pas par-dessus le quiz ou les favoris,
     le toast du theme confirme. Un second appui ouvre le panier.
  ══════════════════════════════════════════════════════════════ */
  function boutonAjout(p) {
    var deja = dansPanier(p.s);
    return '<button type="button" class="cpb-btn-ajout' + (deja ? ' is-fait' : '') + '" data-cpb-ajout="' + esc(p.s) + '">' +
      esc(deja ? t('Ajouté · voir le panier') : t('Ajouter au panier')) + '</button>';
  }

  document.addEventListener('click', function (e) {
    var b = e.target.closest ? e.target.closest('[data-cpb-ajout]') : null;
    if (b) {
      e.preventDefault();
      var s = b.getAttribute('data-cpb-ajout');
      if (!produit(s)) { return; }
      /* Deja au panier : on l'ouvre. Le calque se ferme d'abord et rend le
         focus a ce qui l'avait ouvert, pour que le panier le retrouve. */
      if (dansPanier(s)) { fermer(); P.open(); return; }
      P.add(s, 1, true);
      b.classList.add('is-fait');
      b.textContent = t('Ajouté · voir le panier');
      return;
    }
    var c = e.target.closest ? e.target.closest('[data-cpb-coeur]') : null;
    if (c) {
      e.preventDefault();
      var slug = c.getAttribute('data-cpb-coeur');
      if (!produit(slug)) { return; }
      basculeFavori(slug);
      if (!REDUIT) {
        c.classList.remove('is-bat');
        void c.offsetWidth;
        c.classList.add('is-bat');
        setTimeout(function () { c.classList.remove('is-bat'); }, 320);
      }
      return;
    }
    var q = e.target.closest ? e.target.closest('[data-cpb-quiz]') : null;
    if (q) { e.preventDefault(); ouvrirQuiz(); return; }
    var f = e.target.closest ? e.target.closest('[data-cpb-favoris]') : null;
    if (f) { e.preventDefault(); ouvrirFavoris(); return; }
    var r = e.target.closest ? e.target.closest('[data-cpb-recherche]') : null;
    if (r) { e.preventDefault(); ouvrirRecherche(); return; }
    /* Un lien vers le catalogue, depuis un calque ouvert sur l'accueil :
       on ferme et on y descend, au lieu de recharger la page. */
    var cat = e.target.closest ? e.target.closest('[data-cpb-va-catalogue]') : null;
    if (cat && document.getElementById('catalogue')) {
      e.preventDefault();
      fermer();
      document.getElementById('catalogue').scrollIntoView({ behavior: REDUIT ? 'auto' : 'smooth', block: 'start' });
    }
  });

  /* Le panier a change ailleurs (tiroir, autre onglet) : les boutons
     « Ajoute » d'un calque ouvert suivent. */
  P.onChange(function () {
    if (!ouvert) { return; }
    [].forEach.call(ouvert.querySelectorAll('[data-cpb-ajout]'), function (b) {
      var s = b.getAttribute('data-cpb-ajout'), deja = dansPanier(s);
      b.classList.toggle('is-fait', deja);
      b.textContent = deja ? t('Ajouté · voir le panier') : t('Ajouter au panier');
    });
  });

  /* ══════════════════════════════════════════════════════════════
     3. RECHERCHE INSTANTANEE
  ══════════════════════════════════════════════════════════════ */

  /* L'arabe tape au clavier : on ramene les mots les plus cherches vers le
     catalogue, ecrit en lettres latines. */
  var AR_REQ = {
    'شانيل': 'chanel', 'ديور': 'dior', 'توم فورد': 'tom ford', 'فورد': 'ford', 'كريد': 'creed', 'فرساتشي': 'versace',
    'فيرساتشي': 'versace', 'ارماني': 'armani', 'اماني': 'armani', 'ايف سان لوران': 'yves saint laurent',
    'سان لوران': 'saint laurent', 'غوتشي': 'gucci', 'قوتشي': 'gucci', 'جوتشي': 'gucci', 'برادا': 'prada',
    'فالنتينو': 'valentino', 'هوغو بوس': 'hugo boss', 'هوجو بوس': 'hugo boss', 'بوس': 'boss',
    'جان بول غوتييه': 'jean paul gaultier', 'غوتييه': 'gaultier', 'لانكوم': 'lancome', 'كارولينا': 'carolina',
    'زيرجوف': 'xerjoff', 'كسيرجوف': 'xerjoff', 'بربري': 'burberry', 'بيربري': 'burberry', 'كيالي': 'kayali',
    'باكارات': 'baccarat', 'سوفاج': 'sauvage', 'سوفاچ': 'sauvage', 'جيفنشي': 'givenchy', 'جيفانشي': 'givenchy',
    'غيرلان': 'guerlain', 'هيرميس': 'hermes', 'ايرميس': 'hermes', 'دولتشي': 'dolce', 'دولشي': 'dolce',
    'نارسيسو': 'narciso', 'مونت بلانك': 'montblanc', 'مونبلان': 'montblanc', 'لاكوست': 'lacoste',
    'رابان': 'rabanne', 'باكو رابان': 'rabanne', 'ون مليون': '1 million', 'مارلي': 'marly', 'نيشان': 'nishane',
    'كوكو': 'coco', 'ليبر': 'libre', 'بلاك اوبيوم': 'black opium', 'ايروس': 'eros', 'لو مال': 'le male',
    'فانيليا': 'vanille', 'فانيلا': 'vanille', 'عود': 'oud', 'ورد': 'rose', 'مسك': 'musc', 'عنبر': 'ambre',
    'ياسمين': 'jasmin', 'صندل': 'santal', 'قهوه': 'cafe', 'كاكاو': 'cacao', 'خزامى': 'lavande', 'لافندر': 'lavande',
    'جلد': 'cuir', 'باتشولي': 'patchouli', 'برغموت': 'bergamote', 'ليمون': 'citron', 'تونكا': 'tonka',
    'زهر البرتقال': 'fleur d oranger', 'زهري': 'floral', 'خشبي': 'boise', 'شرقي': 'oriental', 'منعش': 'frais',
    'نسائي': 'femme', 'رجالي': 'homme', 'للجنسين': 'mixte', 'نساء': 'femme', 'رجال': 'homme'
  };
  var AR_CLES = Object.keys(AR_REQ).map(normAr).sort(function (a, b) { return b.length - a.length; });
  var AR_VAL = {};
  Object.keys(AR_REQ).forEach(function (k) { AR_VAL[normAr(k)] = AR_REQ[k]; });

  function normAr(s) {
    return String(s).replace(/[ً-ٰٟـ]/g, '')
      .replace(/[أإآ]/g, 'ا').replace(/ى/g, 'ي').replace(/ة/g, 'ه');
  }
  /* Mot entier seulement : « فورد » (Ford) contient « ورد » (rose), et
     un remplacement au milieu d'un mot envoyait Tom Ford vers les roses.
     L'article « ال » colle au mot (« العود ») est accepte. Les clefs les
     plus longues passent d'abord : « توم فورد » avant « فورد ». */
  function requeteLatine(q) {
    if (!/[؀-ۿ]/.test(q)) { return q; }
    var s = ' ' + normAr(q).replace(/\s+/g, ' ') + ' ';
    AR_CLES.forEach(function (k) {
      [k, 'ال' + k].forEach(function (forme) {
        var motif = ' ' + forme + ' ';
        while (s.indexOf(motif) > -1) { s = s.replace(motif, ' ' + AR_VAL[k] + ' '); }
      });
    });
    return s.replace(/[؀-ۿ]+/g, ' ');
  }

  var ENTREES = null;
  function entrees() {
    if (ENTREES) { return ENTREES; }
    ENTREES = LISTE.map(function (p) {
      return {
        p: p,
        n: cle(p.n),
        b: cle(p.b),
        r: cle([p.fam, p.x, p.g].concat(notesDe(p)).join(' ')),
        photo: P.aPhoto(p.s)
      };
    });
    return ENTREES;
  }

  function debutDeMot(texte, mot) {
    var i = texte.indexOf(mot);
    while (i > -1) {
      if (i === 0 || /[\s'’\-.&]/.test(texte.charAt(i - 1))) { return true; }
      i = texte.indexOf(mot, i + 1);
    }
    return false;
  }

  function cherche(q) {
    var mots = cle(requeteLatine(q)).replace(/[’']/g, ' ').split(/\s+/).filter(Boolean);
    if (!mots.length) { return []; }
    var res = [];
    entrees().forEach(function (e) {
      var total = 0;
      for (var i = 0; i < mots.length; i++) {
        var m = mots[i], s = 0;
        if (debutDeMot(e.n, m)) { s += 10; } else if (m.length > 2 && e.n.indexOf(m) > -1) { s += 5; }
        if (debutDeMot(e.b, m)) { s += 8; } else if (m.length > 2 && e.b.indexOf(m) > -1) { s += 4; }
        if (!s) {
          if (debutDeMot(e.r, m)) { s += 3; } else if (m.length > 3 && e.r.indexOf(m) > -1) { s += 1; }
        }
        if (!s) { return; }          /* chaque mot doit trouver quelque chose */
        total += s;
      }
      res.push({ p: e.p, s: total + (e.photo ? 0.5 : 0) });
    });
    res.sort(function (a, b) { return b.s - a.s || (a.p.b + a.p.n).localeCompare(b.p.b + b.p.n); });
    return res.map(function (r) { return r.p; });
  }

  /* Le mot tape, surligne dans le nom quand on peut le situer sans risque :
     la cle sans accents a la meme longueur que le nom affiche. */
  function nomSurligne(p, q) {
    var mots = cle(requeteLatine(q)).split(/\s+/).filter(function (m) { return m.length > 1; });
    var c = cle(p.n);
    if (!mots.length || c.length !== p.n.length) { return nomHTML(p); }
    var i = -1, m = '';
    for (var k = 0; k < mots.length && i < 0; k++) { i = c.indexOf(mots[k]); m = mots[k]; }
    if (i < 0) { return nomHTML(p); }
    return '<span class="pnr-name cpb-nom">' + esc(p.n.slice(0, i)) + '<mark>' + esc(p.n.slice(i, i + m.length)) + '</mark>' +
      esc(p.n.slice(i + m.length)) + '</span>';
  }

  function ligneHTML(p, q) {
    return '<li><a class="cpb-ligne" href="' + esc(urlFiche(p.s)) + '">' +
      vignette(p, 'cpb-vignette', 56, 56) +
      '<span class="cpb-ligne-txt">' +
        '<span class="cpb-ligne-maison">' + maisonHTML(p) + '</span>' +
        '<span class="cpb-ligne-nom">' + (q ? nomSurligne(p, q) : nomHTML(p)) + '</span>' +
        '<span class="cpb-ligne-fam">' + esc(p.fam) + '</span>' +
      '</span>' +
      '<span class="cpb-ligne-prix">' + prixTexte(p) + '</span>' +
    '</a></li>';
  }

  var rech = null, champ = null, corpsRech = null, statut = null;
  var MAX_RES = 8;

  function construitRecherche() {
    rech = creeCalque('cpb-recherche', t('Rechercher un parfum'));
    rech.innerHTML =
      '<div class="cpb-panneau">' +
        '<div class="cpb-rech-tete">' + SVG.loupe +
          '<input type="search" class="cpb-rech-champ" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" enterkeyhint="search"' +
          ' aria-label="' + esc(t('Rechercher un parfum')) + '" placeholder="' + esc(t('Un parfum, une maison, une note…')) + '">' +
          '<button type="button" class="cpb-fermer" data-cpb-fermer aria-label="' + esc(t('Fermer')) + '">' + SVG.croix + '</button>' +
        '</div>' +
        '<p class="cpb-sr" aria-live="polite"></p>' +
        '<div class="cpb-rech-corps"></div>' +
      '</div>';
    champ = rech.querySelector('.cpb-rech-champ');
    corpsRech = rech.querySelector('.cpb-rech-corps');
    statut = rech.querySelector('.cpb-sr');

    rech.querySelector('[data-cpb-fermer]').addEventListener('click', function () { fermer(); });
    champ.addEventListener('input', rendRecherche);
    champ.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') {
        var l = corpsRech.querySelector('.cpb-ligne');
        if (l) { e.preventDefault(); l.focus(); }
      } else if (e.key === 'Enter') {
        var premier = corpsRech.querySelector('.cpb-ligne');
        if (premier && champ.value.trim()) { e.preventDefault(); location.href = premier.getAttribute('href'); }
      }
    });
    corpsRech.addEventListener('keydown', function (e) {
      if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') { return; }
      var liens = [].slice.call(corpsRech.querySelectorAll('.cpb-ligne'));
      var i = liens.indexOf(document.activeElement);
      if (i < 0) { return; }
      e.preventDefault();
      if (e.key === 'ArrowDown' && i < liens.length - 1) { liens[i + 1].focus(); }
      else if (e.key === 'ArrowUp') { if (i > 0) { liens[i - 1].focus(); } else { champ.focus(); } }
    });
    corpsRech.addEventListener('click', function (e) {
      var b = e.target.closest('[data-cpb-mot]');
      if (!b) { return; }
      champ.value = b.getAttribute('data-cpb-mot');
      rendRecherche();
      champ.focus();
    });
  }

  function rendRecherche() {
    var q = champ.value.trim();
    if (!q) { rendRechercheVide(); statut.textContent = ''; return; }
    var res = cherche(q);
    if (!res.length) {
      corpsRech.innerHTML = '<div class="cpb-rech-vide"><strong>' + esc(t('Aucun parfum ne correspond à « {q} ».', { q: q })) + '</strong><br>' +
        esc(t('Essayez une maison (Dior), une note (vanille) ou une famille (boisé).')) + '</div>' +
        '<div class="cpb-rech-raccourcis"><button type="button" class="cpb-puce cpb-puce-or" data-cpb-quiz>' + SVG.etincelle +
        esc(t('Trouver mon parfum en 4 questions')) + '</button></div>';
      statut.textContent = t('Aucun parfum ne correspond à « {q} ».', { q: q });
      return;
    }
    var n = res.length;
    var compte = n === 1 ? t('1 parfum') : t('{n} parfums', { n: n });
    corpsRech.innerHTML = '<p class="cpb-rech-compte">' + esc(compte) + '</p>' +
      '<ul class="cpb-liste">' + res.slice(0, MAX_RES).map(function (p) { return ligneHTML(p, q); }).join('') + '</ul>' +
      (n > MAX_RES ? '<p class="cpb-rech-compte" style="margin-top:14px">' + esc(t('Affinez avec une note ou une maison pour voir les autres.')) + '</p>' : '');
    statut.textContent = compte;
  }

  function rendRechercheVide() {
    var html = '';
    var vusListe = vusRecemment().slice(0, 4).map(produit).filter(Boolean);
    if (vusListe.length) {
      html += '<div class="cpb-rech-bloc"><p class="cpb-titre-petit">' + esc(t('Vus récemment')) + '</p><ul class="cpb-liste">' +
        vusListe.map(function (p) { return ligneHTML(p, ''); }).join('') + '</ul></div>';
    }
    var dejaVus = {};
    vusListe.forEach(function (p) { dejaVus[p.s] = 1; });
    var pop = (CFG.populaires || []).filter(function (s) { return produit(s) && !dejaVus[s]; }).slice(0, vusListe.length ? 3 : 5).map(produit);
    if (pop.length) {
      html += '<div class="cpb-rech-bloc"><p class="cpb-titre-petit">' + esc(t('Les plus demandés')) + '</p><ul class="cpb-liste">' +
        pop.map(function (p) { return ligneHTML(p, ''); }).join('') + '</ul></div>';
    }
    var mots = AR
      ? ['فانيليا', 'عود', 'ورد', 'مسك', 'Dior', 'Tom Ford']
      : ['Vanille', 'Oud', 'Rose', 'Musc', 'Dior', 'Tom Ford'];
    var nf = favoris().length;
    html += '<div class="cpb-rech-raccourcis" aria-label="' + esc(t('Recherches rapides')) + '">' +
      '<button type="button" class="cpb-puce cpb-puce-or" data-cpb-quiz>' + SVG.etincelle + esc(t('Trouver mon parfum en 4 questions')) + '</button>' +
      (nf ? '<button type="button" class="cpb-puce" data-cpb-favoris>' + SVG.coeur + esc(t('Mes favoris ({n})', { n: nf })) + '</button>' : '') +
      mots.map(function (m) { return '<button type="button" class="cpb-puce" data-cpb-mot="' + esc(m) + '">' + esc(m) + '</button>'; }).join('') +
      '</div>';
    corpsRech.innerHTML = html;
  }

  function ouvrirRecherche() {
    if (!rech) { construitRecherche(); }
    rendRecherche();
    ouvrir(rech, '.cpb-rech-champ');
    try { champ.select(); } catch (e) {}
  }

  /* ══════════════════════════════════════════════════════════════
     4. QUIZ « TROUVER MON PARFUM »
  ══════════════════════════════════════════════════════════════ */

  /* Groupes de notes, compares a la cle de chaque note (sans accents).
     Ancres en debut de mot : « poivre rose » n'est pas une rose, et
     « poudre » ne contient pas d'oud. */
  var GROUPES = {
    vanille: /(^|\s)vanill/,
    rose: /^rose\b/,
    oud: /(^|[\s'’])oud\b/,
    agrumes: /(^|\s)(bergamote|citron|mandarine|pamplemousse|orange|cedrat|yuzu|lime|agrume|petit grain|clementine)/,
    musc: /(^|\s)musc/,
    ambre: /(^|\s)(ambre|ambrox|ambrette|labdanum|benjoin)/,
    cafe: /(^|\s)(cafe|cacao|chocolat|espresso|praline)/,
    fruits: /(^|\s)(poire|pomme|cassis|litchi|fruit|framboise|fraise|peche|abricot|prune|ananas|cerise|griotte|mure|myrtille|pasteque|figue|mangue|coing|baie|groseille|melon|grenade)/,
    oranger: /(fleur d.?oranger|neroli)/,
    lavande: /(^|\s)lavand/,
    bois: /(^|\s)(santal|cedre|vetiver|bois|gaiac|patchouli|mousse de chene)/,
    epices: /(^|\s)(cardamome|cannelle|poivre|safran|gingembre|muscade|girofle|piment|cumin|anis|epice)/,
    marin: /(^|\s)(notes marines|marin|aquatique|sel|algue|menthe|notes vertes|the vert)/,
    floral: /(^|\s)(rose|jasmin|pivoine|tubereuse|iris|violette|muguet|magnolia|freesia|gardenia|ylang|orchidee|heliotrope|lys|fleur|neroli|osmanthus|mimosa|frangipanier|lilas|geranium)/,
    gourmand: /(^|\s)(vanill|caramel|tonka|praline|cacao|chocolat|cafe|amande|miel|toffee|sucre|marshmallow|pistache|meringue|lait|creme|biscuit|panettone|dragee|guimauve|noix de coco|coco)/,
    oriental: /(^|[\s'’])(oud|santal|encens|cuir|ambre|patchouli|safran|myrrhe|tabac|benjoin|labdanum|oliban|styrax)/
  };

  var UNIVERS = {
    frais: { fam: /(hesperide|aromatique|aquatique|marin|frais|vert|fougere|metallique)/, notes: [GROUPES.agrumes, GROUPES.lavande, GROUPES.marin] },
    floral: { fam: /(floral|fleur|rose|blanc|poudre|capiteux)/, notes: [GROUPES.floral] },
    gourmand: { fam: /(gourmand|vanille|lacte)/, notes: [GROUPES.gourmand] },
    boise: { fam: /(boise|ambre|oriental|cuir|epice|tabac|mineral)/, notes: [GROUPES.oriental, GROUPES.bois] }
  };

  var CHIPS = [
    ['vanille', 'Vanille'], ['rose', 'Rose'], ['oud', 'Oud'], ['agrumes', 'Agrumes'],
    ['musc', 'Musc'], ['ambre', 'Ambre'], ['cafe', 'Café et cacao'], ['fruits', 'Fruits'],
    ['oranger', 'Fleur d’oranger'], ['lavande', 'Lavande'], ['bois', 'Bois'], ['epices', 'Épices']
  ];

  var QUESTIONS = [
    { cle: 'genre', court: 'Pour qui ?', titre: 'Pour qui est ce parfum ?', options: [
      ['elle', 'Pour elle', 'Féminins et mixtes'],
      ['lui', 'Pour lui', 'Masculins et mixtes'],
      ['tous', 'Peu importe', 'Tout le catalogue']
    ] },
    { cle: 'univers', court: 'Quelle ambiance ?', titre: 'Quelle ambiance vous attire ?', options: [
      ['frais', 'Frais et lumineux', 'Agrumes, lavande, notes marines'],
      ['floral', 'Floral', 'Rose, jasmin, fleur d’oranger'],
      ['gourmand', 'Gourmand et vanillé', 'Vanille, caramel, fève tonka'],
      ['boise', 'Boisé et oriental', 'Oud, santal, ambre, cuir']
    ] },
    { cle: 'moment', court: 'Quel moment ?', titre: 'Quand le porterez-vous ?', options: [
      ['jour', 'En journée, au bureau', 'Léger, propre, jamais envahissant'],
      ['soir', 'Le soir, pour les occasions', 'Intense, qui laisse un sillage'],
      ['toujours', 'Tous les jours, partout', 'Polyvalent, du matin au soir']
    ] },
    { cle: 'notes', court: 'Vos notes préférées', titre: 'Des notes que vous aimez ?', multi: true }
  ];

  var etat = { etape: 0, genre: null, univers: null, moment: null, notes: [] };

  function notesCles(p) { return notesDe(p).map(cle); }
  function correspond(liste, re) {
    var hits = [];
    liste.forEach(function (n, i) { if (re.test(n)) { hits.push(i); } });
    return hits;
  }

  /* Le classement : un score par parfum, a partir des reponses (r : genre,
     univers, moment, notes). Aucun parfum n'est « pousse » : seuls le
     genre, la famille, la concentration et les notes ecrites dans
     produits.php comptent. Fonction pure : elle ne touche pas a l'etat
     du quiz. */
  function classement(r) {
    var g = r.genre;
    var u = r.univers ? UNIVERS[r.univers] : null;
    var notesVoulues = r.notes || [];
    var cands = LISTE.filter(function (p) {
      if (g === 'elle') { return p.g === 'Femme' || p.g === 'Mixte'; }
      if (g === 'lui') { return p.g === 'Homme' || p.g === 'Mixte'; }
      return true;
    });
    var notees = cands.map(function (p) {
      var nc = notesCles(p), fam = cle(p.fam), x = cle(p.x), s = 0, raisons = [];
      var lourd = /(ambre|oriental|gourmand|cuir|tabac|vanille)/.test(fam);
      var frais = /(hesperide|aromatique|aquatique|marin|frais|vert)/.test(fam);

      if (u) {
        if (u.fam.test(cle(famillePrincipale(p)))) { s += 6; }
        else if (u.fam.test(fam)) { s += 3; }
        var nUni = 0;
        u.notes.forEach(function (re) {
          correspond(nc, re).forEach(function (i) {
            nUni++;
            if (raisons.indexOf(notesDe(p)[i]) < 0 && raisons.length < 3) { raisons.push(notesDe(p)[i]); }
          });
        });
        s += Math.min(nUni, 3);
      }

      var fort = /(elixir|extrait|intense|absolu)/.test(x) || x === 'parfum' || x === 'esprit de parfum';
      var leger = /(eau de toilette|eau fraiche|eau pour la nuit)/.test(x);
      if (r.moment === 'jour') {
        if (leger) { s += 2; } else if (x === 'eau de parfum') { s += 1; }
        if (fort) { s -= 1.5; }
        if (lourd) { s -= 1; }
        if (frais) { s += 1; }
      } else if (r.moment === 'soir') {
        if (fort) { s += 2; } else if (x === 'eau de parfum') { s += 0.75; }
        if (leger) { s -= 1; }
        if (lourd) { s += 1; }
        if (frais) { s -= 0.75; }
      } else if (r.moment === 'toujours') {
        if (x === 'eau de parfum') { s += 1; }
      }

      notesVoulues.forEach(function (k) {
        if (!GROUPES[k]) { return; }
        var h = correspond(nc, GROUPES[k]);
        if (h.length) {
          s += 2.5 + (h.length > 1 ? 0.5 : 0);
          var nom = notesDe(p)[h[0]];
          if (raisons.indexOf(nom) < 0) { raisons.unshift(nom); }
        }
      });

      if ((g === 'elle' && p.g === 'Femme') || (g === 'lui' && p.g === 'Homme')) { s += 0.3; }
      if (P.aPhoto(p.s)) { s += 1; }
      if (!raisons.length) { raisons = notesDe(p).slice(0, 3); }
      return { p: p, s: s, raisons: raisons.slice(0, 3) };
    });
    notees.sort(function (a, b) { return b.s - a.s || (a.p.s < b.p.s ? -1 : 1); });

    /* Trois maisons differentes quand c'est possible : trois flacons de la
       meme maison, c'est un seul choix presente trois fois. */
    var choisis = [], maisons = {};
    notees.forEach(function (n) {
      if (choisis.length < 3 && !maisons[n.p.b]) { choisis.push(n); maisons[n.p.b] = 1; }
    });
    notees.forEach(function (n) {
      if (choisis.length < 3 && choisis.indexOf(n) < 0) { choisis.push(n); }
    });
    return choisis;
  }

  /* Un choix fait avance d'une question apres un court temps de lecture.
     Pendant ce temps, les autres appuis sont ignores : un double appui
     sautait sinon la question suivante sans qu'on l'ait vue. */
  var avanceEnCours = false;

  var quiz = null, corpsQuiz = null;
  function construitQuiz() {
    quiz = creeCalque('cpb-quiz', t('Trouver mon parfum'));
    quiz.innerHTML =
      '<div class="cpb-panneau">' +
        '<div class="cpb-quiz-tete">' +
          '<div class="cpb-quiz-progres"><span class="cpb-quiz-etape"></span><div class="cpb-quiz-barre" aria-hidden="true"><span></span></div></div>' +
          '<button type="button" class="cpb-fermer" data-cpb-fermer aria-label="' + esc(t('Fermer')) + '">' + SVG.croix + '</button>' +
        '</div>' +
        '<div class="cpb-quiz-corps"></div>' +
      '</div>';
    corpsQuiz = quiz.querySelector('.cpb-quiz-corps');
    quiz.querySelector('[data-cpb-fermer]').addEventListener('click', function () { fermer(); });
    corpsQuiz.addEventListener('click', function (e) {
      var o = e.target.closest('[data-cpb-rep]');
      if (o) {
        if (avanceEnCours) { return; }
        var q = QUESTIONS[etat.etape];
        if (!q) { return; }
        var v = o.getAttribute('data-cpb-rep');
        if (q.multi) {
          var i = etat.notes.indexOf(v);
          if (i > -1) { etat.notes.splice(i, 1); } else { etat.notes.push(v); }
          o.setAttribute('aria-pressed', i > -1 ? 'false' : 'true');
          majPiedNotes();
          return;
        }
        etat[q.cle] = v;
        [].forEach.call(corpsQuiz.querySelectorAll('[data-cpb-rep]'), function (b) {
          b.setAttribute('aria-pressed', b === o ? 'true' : 'false');
        });
        var pas = etat.etape;
        avanceEnCours = true;
        setTimeout(function () {
          avanceEnCours = false;
          if (etat.etape === pas) { etat.etape++; rendQuiz(); }
        }, REDUIT ? 0 : 180);
        return;
      }
      if (avanceEnCours) { return; }
      var a = e.target.closest('[data-cpb-act]');
      if (!a) { return; }
      var act = a.getAttribute('data-cpb-act');
      if (act === 'retour') { etat.etape = Math.max(0, etat.etape - 1); rendQuiz(); }
      else if (act === 'passer') { etat.notes = []; etat.etape = QUESTIONS.length; rendQuiz(); }
      else if (act === 'voir') { etat.etape = QUESTIONS.length; rendQuiz(); }
      else if (act === 'recommencer') { etat = { etape: 0, genre: null, univers: null, moment: null, notes: [] }; rendQuiz(); }
      else if (act === 'catalogue') {
        fermer();
        var cat = document.getElementById('catalogue');
        if (cat) { cat.scrollIntoView({ behavior: REDUIT ? 'auto' : 'smooth', block: 'start' }); }
        else { location.href = urlAccueil('#catalogue'); }
      }
    });
  }

  function majPiedNotes() {
    var b = corpsQuiz.querySelector('[data-cpb-act="voir"]');
    if (b) { b.hidden = !etat.notes.length; }
    var p = corpsQuiz.querySelector('[data-cpb-act="passer"]');
    if (p) { p.hidden = !!etat.notes.length; }
  }

  function rendQuiz() {
    var n = QUESTIONS.length;
    var etapeTxt = quiz.querySelector('.cpb-quiz-etape');
    var barre = quiz.querySelector('.cpb-quiz-barre span');

    if (etat.etape >= n) { rendResultats(etapeTxt, barre); return; }

    var q = QUESTIONS[etat.etape];
    etapeTxt.textContent = t('Question {i} sur {n}', { i: etat.etape + 1, n: n }) + ' · ' + t(q.court);
    barre.style.width = ((etat.etape) / n * 100) + '%';

    var html = '<h2 class="cpb-quiz-q" tabindex="-1">' + esc(t(q.titre)) + '</h2>';
    if (q.multi) {
      html += '<p class="cpb-quiz-aide">' + esc(t('Plusieurs choix possibles. Rien ne vous parle ? Passez.')) + '</p>' +
        '<div class="cpb-notes" role="group" aria-label="' + esc(t(q.titre)) + '">' +
        CHIPS.map(function (c) {
          return '<button type="button" class="cpb-option" data-cpb-rep="' + c[0] + '" aria-pressed="' + (etat.notes.indexOf(c[0]) > -1) + '">' +
            '<span class="cpb-option-titre">' + esc(t(c[1])) + '</span></button>';
        }).join('') + '</div>';
    } else {
      html += '<div class="cpb-choix' + (q.options.length === 3 ? ' cpb-choix-3' : '') + '" role="group" aria-label="' + esc(t(q.titre)) + '">' +
        q.options.map(function (o) {
          /* Les univers ont leur photo d'ambiance quand elle est livree. */
          var vis = q.cle === 'univers' && CFG.visuels && CFG.visuels[o[0]];
          return '<button type="button" class="cpb-option' + (vis ? ' cpb-option-photo' : '') + '" data-cpb-rep="' + o[0] + '" aria-pressed="' + (etat[q.cle] === o[0]) + '">' +
            (vis ? '<img class="cpb-option-img" src="' + esc(vis) + '" alt="" width="600" height="600" decoding="async">' : '') +
            '<span class="cpb-option-titre">' + esc(t(o[1])) + '</span>' +
            '<span class="cpb-option-sous">' + esc(t(o[2])) + '</span></button>';
        }).join('') + '</div>';
    }
    html += '<div class="cpb-quiz-pied">' +
      (etat.etape > 0 ? '<button type="button" class="cpb-lien-btn" data-cpb-act="retour">← ' + esc(t('Retour')) + '</button>' : '<span></span>') +
      (q.multi
        ? '<button type="button" class="cpb-lien-btn" data-cpb-act="passer">' + esc(t('Passer cette question')) + '</button>' +
          '<button type="button" class="cpb-btn-plein" data-cpb-act="voir">' + esc(t('Voir mes parfums')) + '</button>'
        : '') +
      '</div>';
    corpsQuiz.innerHTML = html;
    corpsQuiz.scrollTop = 0;
    if (q.multi) { majPiedNotes(); }
    var h = corpsQuiz.querySelector('.cpb-quiz-q');
    if (h) { try { h.focus({ preventScroll: true }); } catch (e) { h.focus(); } }
  }

  function libelleReponse(qCle, v) {
    var q = QUESTIONS.filter(function (x) { return x.cle === qCle; })[0];
    var o = q && q.options ? q.options.filter(function (x) { return x[0] === v; })[0] : null;
    return o ? t(o[1]) : '';
  }

  function rendResultats(etapeTxt, barre) {
    var choix = classement(etat);
    etapeTxt.textContent = t('Votre sélection');
    barre.style.width = '100%';

    var resume = [];
    resume.push(etat.genre === 'elle' ? t('pour elle') : etat.genre === 'lui' ? t('pour lui') : t('pour tous'));
    if (etat.univers) { resume.push(libelleReponse('univers', etat.univers).toLowerCase()); }
    if (etat.moment) { resume.push(libelleReponse('moment', etat.moment).toLowerCase()); }
    if (etat.notes.length) {
      resume.push(t('notes : {l}', { l: etat.notes.map(function (k) {
        var c = CHIPS.filter(function (x) { return x[0] === k; })[0];
        return c ? t(c[1]).toLowerCase() : k;
      }).join(', ') }));
    }
    var msg = t('Bonjour, j’ai fait le quiz « Trouver mon parfum » ({r}). Il me propose : {l}. Pouvez-vous me conseiller ?', {
      r: resume.join(', '),
      l: choix.map(function (r) { return r.p.b + ' ' + r.p.n; }).join(', ')
    });
    var wa = 'https://wa.me/' + encodeURIComponent((P.cfg && P.cfg.wa) || '') + '?text=' + encodeURIComponent(msg);

    var html = '<h2 class="cpb-quiz-q" tabindex="-1">' + esc(t('Trois parfums pour vous.')) + '</h2>' +
      '<p class="cpb-quiz-aide">' + esc(t('Sélectionnés parmi les {n} références du catalogue, d’après vos réponses.', { n: LISTE.length })) + '</p>' +
      '<div class="cpb-resultats">' +
      choix.map(function (r, i) {
        var p = r.p;
        return '<article class="cpb-res' + (i === 0 ? ' cpb-res-une' : '') + '">' +
          '<div class="cpb-res-photo">' +
            (i === 0 ? '<span class="cpb-res-ruban">' + esc(t('Le plus proche')) + '</span>' : '') +
            coeurHTML(p.s, true) +
            '<a href="' + esc(urlFiche(p.s)) + '" tabindex="-1" aria-hidden="true">' + vignette(p, 'cpb-vignette', 400, 500) + '</a>' +
          '</div>' +
          '<div class="cpb-res-txt">' +
            '<a href="' + esc(urlFiche(p.s)) + '">' +
              '<div class="cpb-res-maison">' + maisonHTML(p) + '</div>' +
              '<div class="cpb-res-nom">' + nomHTML(p) + '</div>' +
            '</a>' +
            '<div class="cpb-res-fam">' + esc(p.fam) + '</div>' +
            '<div class="cpb-res-pourquoi"><b>' + esc(t('Pourquoi :')) + '</b> <span>' + esc(r.raisons.join(' · ')) + '</span></div>' +
            '<div class="cpb-res-bas"><span class="cpb-res-prix">' + prixTexte(p) + '</span></div>' +
            boutonAjout(p) +
            '<a class="cpb-res-fiche" href="' + esc(urlFiche(p.s)) + '">' + esc(t('Voir la fiche')) + '</a>' +
          '</div>' +
        '</article>';
      }).join('') +
      '</div>' +
      (P.cfg && P.cfg.wa
        ? '<div class="cpb-quiz-conseil"><span>' + esc(t('Un deuxième avis ? Un conseiller vous répond sur WhatsApp.')) + '</span>' +
          '<a href="' + esc(wa) + '" target="_blank" rel="noopener">' + SVG.wa + esc(t('Demander conseil')) + '</a></div>'
        : '') +
      '<div class="cpb-quiz-pied">' +
        '<button type="button" class="cpb-lien-btn" data-cpb-act="recommencer">↺ ' + esc(t('Recommencer')) + '</button>' +
        '<button type="button" class="cpb-lien-btn" data-cpb-act="catalogue">' + esc(t('Voir tout le catalogue')) + ' →</button>' +
      '</div>';
    corpsQuiz.innerHTML = html;
    corpsQuiz.scrollTop = 0;
    majFavoris();
    var h = corpsQuiz.querySelector('.cpb-quiz-q');
    if (h) { try { h.focus({ preventScroll: true }); } catch (e) { h.focus(); } }
  }

  function ouvrirQuiz() {
    if (!quiz) { construitQuiz(); }
    if (etat.etape > QUESTIONS.length) { etat.etape = QUESTIONS.length; }
    rendQuiz();
    ouvrir(quiz, '.cpb-quiz-q');
  }

  /* L'appel au quiz sur la page d'accueil, juste avant le catalogue. */
  function appelQuiz() {
    if (!CFG.accueil) { return; }
    var cat = document.getElementById('catalogue');
    if (!cat || !cat.parentNode) { return; }
    var s = document.createElement('section');
    s.className = 'cpb cpb-quiz-appel';
    s.id = 'trouver-mon-parfum';
    s.setAttribute('aria-label', t('Trouver mon parfum'));
    var ban = CFG.visuels && CFG.visuels.banniere;
    s.innerHTML =
      '<div class="cpb-qa-carte' + (ban ? ' cpb-qa-photo' : '') + '">' +
        (ban ? '<img class="cpb-qa-fond" src="' + esc(ban) + '" alt="" width="1600" height="900" loading="lazy" decoding="async">' : '') +
        '<div>' +
          '<div class="sect-kicker"><span>' + esc(t('Conseil personnalisé')) + '</span></div>' +
          '<h2 class="sect-h2">' + esc(t('Vous hésitez ? Trouvez votre parfum en 4 questions.')) + '</h2>' +
          '<p>' + esc(t('Dites-nous pour qui, quelle ambiance et quelles notes vous aimez : nous choisissons dans le catalogue les flacons qui vous ressemblent.')) + '</p>' +
          /* Un lien et non un <button> : le theme remet a transparent le fond
             de tout button.btn-a, et le bouton dore devenait invisible. */
          '<a href="#trouver-mon-parfum" class="btn-a" role="button" data-cpb-quiz>' + esc(t('Commencer le quiz')) + '</a>' +
        '</div>' +
        '<ol class="cpb-qa-etapes">' +
          QUESTIONS.map(function (q) { return '<li>' + esc(t(q.court)) + '</li>'; }).join('') +
        '</ol>' +
      '</div>';
    cat.parentNode.insertBefore(s, cat);
  }

  /* ══════════════════════════════════════════════════════════════
     VISUELS D'AMBIANCE DU RESTE DU SITE
     Decoratifs (alt vide), ajoutes seulement si l'image est livree :
       - « Commander maintenant » (accueil) : fond derriere le texte ;
       - « Le meme parfum. Sans le prix boutique. » : bandeau d'image ;
       - page introuvable (404) : la banniere du quiz.
  ══════════════════════════════════════════════════════════════ */
  function visuelsSite() {
    var V = CFG.visuels || {};
    var img = function (src, classe, l, h) {
      return '<img class="' + classe + '" src="' + esc(src) + '" alt="" width="' + l + '" height="' + h + '" loading="lazy" decoding="async">';
    };
    var fin = document.querySelector('section.finale');
    if (fin && V.finale && !fin.querySelector('.cpb-fin-fond')) {
      fin.classList.add('cpb', 'cpb-fin-photo');
      fin.insertAdjacentHTML('afterbegin', img(V.finale, 'cpb-fin-fond', 1600, 900) + '<span class="cpb-fin-voile" aria-hidden="true"></span>');
    }
    var comp = document.querySelector('.distinction .dist-compare');
    if (comp && V.distinction && !document.querySelector('.cpb-dist-visuel')) {
      var f = document.createElement('figure');
      f.className = 'cpb cpb-dist-visuel';
      f.setAttribute('aria-hidden', 'true');
      f.innerHTML = img(V.distinction, 'cpb-dist-img', 1600, 900);
      comp.parentNode.insertBefore(f, comp);
    }
    /* La 404 seule (pas la recherche ni les archives) : c'est elle qui
       porte le grand « 404 » decoratif .cp-code. */
    var p404 = document.querySelector('main.cp-secours');
    var actions = p404 && p404.querySelector('.cp-code') ? p404.querySelector('.cp-actions') : null;
    if (actions && V.banniere && !p404.querySelector('.cpb-404-visuel')) {
      var g = document.createElement('figure');
      g.className = 'cpb cpb-404-visuel';
      g.setAttribute('aria-hidden', 'true');
      g.innerHTML = img(V.banniere, 'cpb-404-img', 1600, 900);
      actions.parentNode.insertBefore(g, actions.nextSibling);
    }
  }

  /* ══════════════════════════════════════════════════════════════
     5. FICHE PARFUM
  ══════════════════════════════════════════════════════════════ */
  var CLE_VUS = 'cpb-vus-v1';
  function vusRecemment() {
    var a = lireLS(CLE_VUS, []);
    return Array.isArray(a) ? a.filter(function (s, i) { return typeof s === 'string' && produit(s) && a.indexOf(s) === i; }) : [];
  }
  function noteVu(s) {
    var a = vusRecemment().filter(function (x) { return x !== s; });
    a.unshift(s);
    ecrireLS(CLE_VUS, a.slice(0, 12));
  }

  /* Parfums proches : notes partagees (le fond compte plus que la tete,
     c'est lui qui reste sur la peau), meme famille, public compatible. */
  function proches(ref, n) {
    var poids = {};
    (ref.t || []).forEach(function (x) { poids[cle(x)] = 0.75; });
    (ref.c || []).forEach(function (x) { poids[cle(x)] = 1; });
    (ref.f || []).forEach(function (x) { poids[cle(x)] = 1.25; });
    var famRef = cle(famillePrincipale(ref)), famRefComplete = cle(ref.fam);

    var notes = LISTE.filter(function (p) {
      /* La meme maison a deja son bloc, juste en dessous : la montrer ici
         ferait apparaitre deux fois le meme flacon sur la fiche. */
      if (p.s === ref.s || p.b === ref.b) { return false; }
      if (ref.g === 'Femme') { return p.g === 'Femme' || p.g === 'Mixte'; }
      if (ref.g === 'Homme') { return p.g === 'Homme' || p.g === 'Mixte'; }
      return true;
    }).map(function (p) {
      var s = 0, communes = [];
      notesDe(p).forEach(function (x) {
        var k = cle(x);
        if (poids[k] && communes.indexOf(x) < 0) { s += poids[k]; communes.push(x); }
      });
      if (cle(famillePrincipale(p)) === famRef) { s += 2; }
      if (cle(p.fam) === famRefComplete) { s += 1.5; }
      if (P.aPhoto(p.s)) { s += 0.5; }
      return { p: p, s: s, communes: communes };
    }).filter(function (r) { return r.s > 2.5; });

    notes.sort(function (a, b) { return b.s - a.s || (a.p.s < b.p.s ? -1 : 1); });
    var out = [], maisons = {};
    notes.forEach(function (r) {
      if (out.length < n && !maisons[r.p.b]) { out.push(r); maisons[r.p.b] = 1; }
    });
    return out;
  }

  function carteSib(p, communes) {
    return '<a class="pf-sib" href="' + esc(urlFiche(p.s)) + '">' +
      '<span class="pf-sib-photo">' + vignette(p, 'pf-sib-img', 400, 300) + '</span>' +
      '<div class="pf-sib-brand">' + maisonHTML(p) + '</div>' +
      '<div class="pf-sib-name">' + nomHTML(p) + '</div>' +
      (communes && communes.length
        ? '<span class="cpb-commun"><b>' + esc(t('En commun :')) + '</b> <span>' + esc(communes.slice(0, 3).join(' · ')) + '</span></span>'
        : '') +
      '<div class="pf-sib-price">' + prixTexte(p) + '</div>' +
    '</a>';
  }

  function blocSibs(titre, cartes) {
    var s = document.createElement('section');
    s.className = 'pf-block cpb';
    s.setAttribute('aria-label', titre);
    s.innerHTML = '<div class="pf-block-tete"><h2>' + esc(titre) + '</h2></div><div class="pf-sibs">' + cartes + '</div>';
    return s;
  }

  function fiche() {
    var ref = CFG.slug ? produit(CFG.slug) : null;
    var main = document.querySelector('main.pf');
    if (!ref || !main) { return; }

    var anciens = vusRecemment().filter(function (s) { return s !== ref.s; });
    noteVu(ref.s);

    /* Le coeur, et l'appel au quiz pour qui hesite encore — sous la phrase
       « Rien a payer maintenant », qui doit rester collee au bouton. */
    var ancre = main.querySelector('.pf-rassure-cta') || main.querySelector('.pf-actions');
    if (ancre) {
      var zone = document.createElement('div');
      zone.className = 'cpb';
      zone.innerHTML = coeurHTML(ref.s, false) +
        '<p class="cpb-bloc-quiz">' + esc(t('Vous hésitez ?')) + ' <button type="button" data-cpb-quiz>' +
        esc(t('Trouvez votre parfum en 4 questions')) + '</button></p>';
      ancre.parentNode.insertBefore(zone, ancre.nextSibling);
    }

    /* « Dans le meme esprit » passe avant « Dans la meme maison ». */
    var pr = proches(ref, 4);
    if (pr.length) {
      var bloc = blocSibs(t('Dans le même esprit'), pr.map(function (r) { return carteSib(r.p, r.communes); }).join(''));
      var sibs = main.querySelector('.pf-block-sibs');
      if (sibs) { main.insertBefore(bloc, sibs); } else { main.appendChild(bloc); }
    }

    /* « Vus recemment » ferme la fiche : c'est le chemin du retour. Sans
       les flacons deja montres plus haut, ni ceux de la meme maison, que
       le bloc « Dans la meme maison » du theme affiche deja. */
    var deja = {};
    pr.forEach(function (r) { deja[r.p.s] = 1; });
    var vus = anciens.map(produit).filter(function (p) {
      return p && !deja[p.s] && p.b !== ref.b;
    }).slice(0, 4);
    if (vus.length) {
      main.appendChild(blocSibs(t('Vus récemment'), vus.map(function (p) { return carteSib(p, null); }).join('')));
    }
  }

  /* ══════════════════════════════════════════════════════════════
     6. COMMANDE EXPRESS SUR LA FICHE PARFUM
     Le formulaire de la page commande, pose sous le prix : en paiement a la
     livraison, chaque page de plus entre l'envie et l'adresse coute des
     commandes. L'envoi suit EXACTEMENT le chemin de commande.js (theme) :
       Panier.depose()  -> carnet WordPress, feuille Google, CAPI, WhatsApp
       mesure « Lead »  -> meme reference que la commande
       cp_commande_faite + ?commander=1&merci=1 -> la page de remerciement du
       theme ouvre WhatsApp et compte l'achat (Purchase) une seule fois.
     Rien n'est recalcule ici : le serveur recalcule le total depuis le
     catalogue, comme pour toute commande.
  ══════════════════════════════════════════════════════════════ */
  var VILLES_FR = ['Agadir', 'Al Hoceïma', 'Berkane', 'Berrechid', 'Béni Mellal', 'Casablanca', 'Dakhla',
    'El Jadida', 'Errachidia', 'Essaouira', 'Fès', 'Guelmim', 'Ifrane', 'Khouribga', 'Kénitra', 'Larache',
    'Laâyoune', 'Marrakech', 'Meknès', 'Mohammedia', 'Nador', 'Ouarzazate', 'Oujda', 'Rabat', 'Safi', 'Salé',
    'Settat', 'Sidi Slimane', 'Tanger', 'Taza', 'Témara', 'Tétouan'];

  /* Meme regle que commande.js : 0X + 8 chiffres, ou 212 + 9. */
  function telValide(v) {
    var n = String(v).replace(/[\s().-]/g, '').replace(/^(?:\+|00)/, '');
    return /^212[5-7][0-9]{8}$/.test(n) || /^0[5-7][0-9]{8}$/.test(n);
  }

  /* Ce que sera le panier une fois ce flacon pris en quantite q, sans le
     modifier : total et livraison affiches avant l'envoi. Meme regle que
     Panier.fraisLivraison(). */
  function apercuPanier(voulus) {
    var c = P.cfg || {}, lignes = {}, autres = 0, n = 0, sous = 0;
    P.items().forEach(function (x) { lignes[x.s] = x.q; });
    voulus.forEach(function (s) { if (!lignes[s]) { lignes[s] = 1; } });
    Object.keys(lignes).forEach(function (k) {
      var p = produit(k);
      if (!p) { return; }
      n += lignes[k];
      sous += P.prix(p) * lignes[k];
      if (voulus.indexOf(k) < 0) { autres += lignes[k]; }
    });
    var frais = !n ? 0 : (c.franco && n >= c.franco ? 0 : (c.livraison || 0));
    return { n: n, autres: autres, sous: sous, frais: frais, total: sous + frais };
  }

  function expressFiche() {
    var ref = CFG.slug ? produit(CFG.slug) : (CFG.vedette ? produit(CFG.vedette) : null);
    var buy = document.querySelector('main.pf .pf-buy') || (CFG.vedette ? document.querySelector('.lp-vedette .pf-buy') : null);
    if (!ref || !buy || !P.depose || !P.reference) { return; }
    var ancre = buy.querySelector('.pf-rassure-cta') || buy.querySelector('.pf-actions');
    if (!ancre) { return; }
    var c = P.cfg || {};
    var deuxOffert = c.franco === 2 && c.livraison > 0;
    var ville = function (fr) { return window.CP_VILLE ? window.CP_VILLE(fr) : fr; };

    var bloc = document.createElement('form');
    bloc.className = 'cpb cpb-express';
    bloc.setAttribute('novalidate', '');
    bloc.setAttribute('aria-label', t('Commander en 30 secondes'));
    bloc.innerHTML =
      '<p class="cpb-titre-petit">' + esc(t('Commander en 30 secondes')) + '</p>' +
      '<div class="cpb-x-qte" role="radiogroup" aria-label="' + esc(t('Commander en 30 secondes')) + '">' +
        '<label class="cpb-x-q"><input type="radio" name="cpb_q" value="1" checked><span>' + esc(t('1 flacon')) +
          '<b>' + P.fmt(P.prix(ref)) + '</b></span></label>' +
        '<label class="cpb-x-q"><input type="radio" name="cpb_q" value="2"><span>' + esc(t('2 parfums')) +
          '<b class="cpb-x-q2">' + esc(t('+ un 2e parfum au choix')) + '</b>' +
          (deuxOffert ? '<em>' + esc(t('Livraison offerte')) + '</em>' : '') + '</span></label>' +
      '</div>' +
      /* Le deuxieme flacon est un AUTRE parfum, choisi ici : proches du
         premier par les notes, ou trouve par la recherche. Jamais le meme. */
      '<div class="cpb-x-second" hidden>' +
        '<p class="cpb-x-sous-titre">' + esc(t('Choisissez votre 2e parfum')) + '</p>' +
        '<input type="search" class="cpb-x-cherche" autocomplete="off" enterkeyhint="search" placeholder="' + esc(t('Chercher un autre parfum…')) + '" aria-label="' + esc(t('Chercher un autre parfum…')) + '">' +
        '<div class="cpb-x-props" role="group" aria-label="' + esc(t('Choisissez votre 2e parfum')) + '"></div>' +
        '<small class="cpb-x-err" hidden>' + esc(t('Choisissez votre deuxième parfum dans la liste.')) + '</small>' +
      '</div>' +
      '<label class="cpb-x-champ" data-f="tel"><span>' + esc(t('Téléphone')) + ' *</span>' +
        '<input type="tel" name="tel" autocomplete="tel" inputmode="tel" enterkeyhint="next" placeholder="06 12 34 56 78" required>' +
        '<small>' + esc(t('Numéro marocain attendu, par exemple 06 12 34 56 78.')) + '</small></label>' +
      '<label class="cpb-x-champ" data-f="nom"><span>' + esc(t('Nom complet')) + ' *</span>' +
        '<input type="text" name="nom" autocomplete="name" enterkeyhint="next" required>' +
        '<small>' + esc(t('Merci d’indiquer votre nom.')) + '</small></label>' +
      '<label class="cpb-x-champ" data-f="ville"><span>' + esc(t('Ville')) + ' *</span>' +
        '<select name="ville" autocomplete="address-level2" required><option value="">' + esc(t('Choisissez votre ville')) + '</option>' +
        VILLES_FR.map(function (v) { return '<option value="' + esc(v) + '">' + esc(ville(v)) + '</option>'; }).join('') +
        '<option value="autre">' + esc(t('Autre ville…')) + '</option></select>' +
        '<small>' + esc(t('Choisissez votre ville dans la liste.')) + '</small></label>' +
      '<label class="cpb-x-champ" data-f="ville_autre" hidden><span>' + esc(t('Laquelle ?')) + '</span>' +
        '<input type="text" name="ville_autre" autocomplete="address-level2" placeholder="' + esc(t('Nom de votre ville')) + '">' +
        '<small>' + esc(t('Indiquez le nom de votre ville.')) + '</small></label>' +
      '<label class="cpb-x-champ" data-f="adresse"><span>' + esc(t('Adresse complète')) + ' *</span>' +
        '<input type="text" name="adresse" autocomplete="street-address" enterkeyhint="send" placeholder="' + esc(t('Rue, numéro, immeuble, étage')) + '" required>' +
        '<small>' + esc(t('C’est cette adresse que le livreur suivra.')) + '</small></label>' +
      '<p class="cpb-x-recap" aria-live="polite"></p>' +
      '<button type="submit" class="cpb-btn-plein cpb-x-go">' + SVG.wa + '<span>' + esc(t('Confirmer la commande')) + '</span><b class="cpb-x-total"></b></button>' +
      '<p class="cpb-x-note">' + esc(t('Rien à payer maintenant : vous réglez en espèces au livreur.')) + ' ' +
        esc(t('WhatsApp s’ouvre ensuite avec votre commande déjà écrite.')) + '</p>';
    ancre.parentNode.insertBefore(bloc, ancre.nextSibling);
    /* Un seul chemin principal : le bouton « Commander » du theme menait a la
       page commande, ce formulaire la remplace sur la fiche. « Ajouter au
       panier » reste, pour qui veut un autre parfum. */
    buy.classList.add('cpb-express-on');

    var champ = function (n) { return bloc.querySelector('[name="' + n + '"]'); };
    var selVille = champ('ville'), blocAutre = bloc.querySelector('[data-f="ville_autre"]');
    var qte = function () { var r = bloc.querySelector('[name="cpb_q"]:checked'); return r ? parseInt(r.value, 10) : 1; };
    var second = null;
    var panneau2 = bloc.querySelector('.cpb-x-second'), props = bloc.querySelector('.cpb-x-props');
    var cherche2 = bloc.querySelector('.cpb-x-cherche'), err2 = bloc.querySelector('.cpb-x-err');
    /* Ce que la commande va contenir, en plus de ce qui est deja au panier. */
    var voulus = function () { return qte() === 2 && second ? [ref.s, second] : [ref.s]; };

    /* Les propositions : sans recherche, les parfums proches du premier
       (« Dans le meme esprit »), completes par des flacons du meme public
       a prix voisin ; avec recherche, le moteur de la loupe. Jamais le
       parfum de la fiche. */
    function propositions(q) {
      if (q) { return cherche(q).filter(function (p) { return p.s !== ref.s; }).slice(0, 6); }
      var out = proches(ref, 6).map(function (r) { return r.p; }), vus = {};
      vus[ref.s] = 1;
      out.forEach(function (p) { vus[p.s] = 1; });
      LISTE.filter(function (p) {
        return !vus[p.s] && P.aPhoto(p.s) && (p.g === ref.g || p.g === 'Mixte' || ref.g === 'Mixte');
      }).sort(function (a, b) {
        return Math.abs(P.prix(a) - P.prix(ref)) - Math.abs(P.prix(b) - P.prix(ref)) || (a.s < b.s ? -1 : 1);
      }).forEach(function (p) { if (out.length < 6) { out.push(p); } });
      return out;
    }
    function rendProps() {
      var q = cherche2.value.trim();
      var liste = propositions(q);
      /* Le parfum choisi reste visible en tete, meme hors des resultats. */
      if (second && !liste.some(function (p) { return p.s === second; })) { liste.unshift(produit(second)); }
      props.innerHTML = liste.length ? liste.map(function (p) {
        return '<button type="button" class="cpb-x-prop" data-cpb-second="' + esc(p.s) + '" aria-pressed="' + (p.s === second) + '">' +
          vignette(p, 'cpb-vignette', 48, 48) +
          '<span class="cpb-x-prop-txt"><span class="cpb-ligne-maison">' + maisonHTML(p) + '</span>' +
          '<span class="cpb-x-prop-nom">' + nomHTML(p) + '</span></span>' +
          '<span class="cpb-x-prop-prix">' + prixTexte(p) + '</span></button>';
      }).join('') : '<p class="cpb-x-vide">' + esc(t('Aucun parfum trouvé.')) + '</p>';
      /* Petites vignettes dans une liste qui defile : chargees tout de suite,
         sinon les cases du bas restent vides jusqu'au defilement. */
      [].forEach.call(props.querySelectorAll('img'), function (im) { im.loading = 'eager'; });
    }
    props.addEventListener('click', function (e) {
      var b = e.target.closest('[data-cpb-second]');
      if (!b) { return; }
      second = b.getAttribute('data-cpb-second');
      err2.hidden = true;
      panneau2.classList.remove('err');
      [].forEach.call(props.querySelectorAll('[data-cpb-second]'), function (x) {
        x.setAttribute('aria-pressed', x === b ? 'true' : 'false');
      });
      recap();
    });
    cherche2.addEventListener('input', rendProps);
    /* Entree dans la recherche ne doit pas envoyer la commande. */
    cherche2.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); } });

    function recap() {
      var a = apercuPanier(voulus());
      var lignes = [];
      var manque = qte() === 2 && !second;
      if (qte() === 2 && second) { lignes.push(t('2e parfum : {nom}', { nom: produit(second).b + ' ' + produit(second).n })); }
      if (a.autres) { lignes.push(t('+ {n} parfum(s) déjà dans votre panier', { n: a.autres })); }
      if (manque) {
        lignes.push(t('Choisissez votre 2e parfum'));
      } else {
        lignes.push(t('Livraison : {l}', { l: a.frais ? P.fmt(a.frais) : t('offerte') }));
        lignes.push(t('Total à payer au livreur : {t}', { t: P.fmt(a.total) }));
      }
      bloc.querySelector('.cpb-x-recap').textContent = lignes.join(' · ');
      bloc.querySelector('.cpb-x-total').textContent = manque ? '' : P.fmt(a.total);
    }
    recap();
    bloc.addEventListener('change', function (e) {
      if (e.target === selVille) {
        blocAutre.hidden = selVille.value !== 'autre';
        if (!blocAutre.hidden) { champ('ville_autre').focus(); }
      }
      if (e.target.name === 'cpb_q') {
        panneau2.hidden = qte() !== 2;
        if (!panneau2.hidden && !props.children.length) { rendProps(); }
      }
      recap();
    });
    P.onChange(recap);
    bloc.addEventListener('input', function (e) {
      var f = e.target.closest('.cpb-x-champ');
      if (f) { f.classList.remove('err'); }
    });

    /* InitiateCheckout, une fois : au premier champ touche, comme la page
       commande le mesure a son ouverture. */
    var debut = false;
    bloc.addEventListener('focusin', function () {
      if (debut) { return; }
      debut = true;
      var a = apercuPanier(voulus());
      P.mesure('InitiateCheckout', { value: a.total, currency: 'MAD', num_items: a.n, content_type: 'product', content_ids: voulus() },
        P.idEvenement ? P.idEvenement('ic') : '');
    });

    function invalide(n) {
      var el = champ(n), v = el ? el.value.trim() : '';
      if (n === 'tel') { return !telValide(v); }
      if (n === 'adresse') { return v.length < 8; }
      if (n === 'ville') { return !v || (v === 'autre' && !champ('ville_autre').value.trim()); }
      return !v;
    }

    var envoi = false;
    bloc.addEventListener('submit', function (e) {
      e.preventDefault();
      if (envoi) { return; }
      var premier = null;
      ['tel', 'nom', 'ville', 'adresse'].forEach(function (n) {
        var bad = invalide(n);
        var f = bloc.querySelector('[data-f="' + n + '"]');
        if (n === 'ville' && champ('ville').value === 'autre') { f = blocAutre; }
        if (f) { f.classList.toggle('err', bad); }
        if (bad && !premier) { premier = f && f.querySelector('input,select'); }
      });
      var manque2 = qte() === 2 && !second;
      err2.hidden = !manque2;
      panneau2.classList.toggle('err', manque2);
      if (manque2) {
        panneau2.scrollIntoView({ block: 'center', behavior: REDUIT ? 'auto' : 'smooth' });
        var p1 = props.querySelector('[data-cpb-second]');
        if (p1) { try { p1.focus({ preventScroll: true }); } catch (x) { p1.focus(); } }
        return;
      }
      if (premier) { premier.focus(); return; }

      envoi = true;
      /* Chaque parfum voulu entre au panier s'il n'y est pas (AddToCart part
         comme pour un ajout normal), puis la commande suit commande.js. */
      var dans = {};
      P.items().forEach(function (x) { dans[x.s] = 1; });
      voulus().forEach(function (s) { if (!dans[s]) { P.add(s, 1, true); } });
      if (!P.count()) { envoi = false; return; }

      var data = {
        nom: champ('nom').value.trim(),
        tel: champ('tel').value.trim(),
        ville: champ('ville').value === 'autre' ? champ('ville_autre').value.trim() : champ('ville').value,
        adresse: champ('adresse').value.trim()
      };
      data.ref = P.reference();
      var montant = P.total(), articles = P.count(), lien = P.waHref(data);
      var lignes = P.items().map(function (x) {
        return { s: x.p.s, nom: x.p.b + ' ' + x.p.n, q: x.q, prix: P.prix(x.p) * x.q };
      });
      var confirmation = {
        ref: data.ref, prenom: data.nom.split(/\s+/)[0], ville: data.ville, lien: lien,
        montant: montant, articles: articles, lignes: lignes,
        livraison: P.fraisLivraison(), sousTotal: P.subtotal(), t: Date.now()
      };

      P.depose(data);
      P.mesure('Lead', {
        value: montant, currency: 'MAD', num_items: articles, content_type: 'product',
        content_ids: lignes.map(function (l) { return l.s; })
      }, data.ref);

      var garde = false;
      try { localStorage.setItem('cp_commande_faite', JSON.stringify(confirmation)); garde = true; } catch (err) {}
      var cible = c.commander || '?commander=1';
      if (!garde) {
        /* Stockage bloque : la page de remerciement ne pourrait rien relire.
           On ouvre WhatsApp tout de suite, dans le geste du clic. */
        P.clear();
        location.href = lien;
        return;
      }
      P.clear();
      location.href = cible + (cible.indexOf('?') >= 0 ? '&' : '?') + 'merci=1';
    });
  }

  /* ══════════════════════════════════════════════════════════════
     MOINS DE FRICTION A LA COMMANDE
     1. Coordonnees retenues : un client qui revient (deuxieme commande,
        ou commande interrompue) retrouve son telephone, son nom, sa ville
        et son adresse deja remplis, dans la commande express comme sur la
        page commande. Gardees sur son telephone seulement, effacables.
     2. Date de livraison estimee, selon la ville et la promesse affichee
        par le theme : Casablanca 24 a 48 h, ailleurs 2 a 4 jours ouvrables
        (le dimanche ne compte pas).
     3. Fiche parfum : la barre fixe « Commander » menait a la page
        commande, un second parcours plus long, et restait par-dessus le
        formulaire express. Elle y mene maintenant, et s'efface pendant
        qu'il est a l'ecran.
  ══════════════════════════════════════════════════════════════ */
  var COORD = 'cpb_coord', COORD_CHAMPS = ['tel', 'nom', 'ville', 'ville_autre', 'adresse'];
  var COORD_DUREE = 180 * 864e5;

  function coordLues() {
    try {
      var o = JSON.parse(localStorage.getItem(COORD) || 'null');
      if (o && typeof o === 'object' && Date.now() - (o.t || 0) < COORD_DUREE) { return o; }
    } catch (e) {}
    return null;
  }
  function coordEcrites(o) {
    try { localStorage.setItem(COORD, JSON.stringify(o)); } catch (e) {}
  }
  function coordOubliees() {
    try { localStorage.removeItem(COORD); } catch (e) {}
  }

  /* La meme ville peut etre ecrite en francais (commande express) ou en
     arabe (liste du theme traduite) : on compare les deux formes. */
  function memeVille(a, b) {
    if (!a || !b) { return false; }
    if (a === b) { return true; }
    var tr = window.CP_VILLE;
    return !!tr && (tr(a) === b || tr(b) === a);
  }
  function choisitVille(sel, v) {
    for (var i = 0; i < sel.options.length; i++) {
      if (sel.options[i].value && memeVille(sel.options[i].value, v)) { sel.selectedIndex = i; return true; }
    }
    return false;
  }

  function retientCoordonnees(form) {
    if (!form || form.getAttribute('data-cpb-coord')) { return; }
    form.setAttribute('data-cpb-coord', '1');
    var champ = function (n) { return form.querySelector('[name="' + n + '"]'); };
    var o = coordLues(), repris = false, efface = false;

    if (o) {
      COORD_CHAMPS.forEach(function (n) {
        var el = champ(n), v = o[n];
        if (!el || !v || typeof v !== 'string' || el.value) { return; }
        if (el.tagName === 'SELECT') {
          if (!choisitVille(el, v)) { return; }
        } else {
          el.value = v;
        }
        repris = true;
        /* Comme une saisie : la liste « autre ville », le recapitulatif et
           les controles du formulaire suivent. */
        ['input', 'change'].forEach(function (type) {
          var ev;
          try { ev = new Event(type, { bubbles: true }); } catch (e) { ev = document.createEvent('Event'); ev.initEvent(type, true, true); }
          el.dispatchEvent(ev);
        });
      });
    }

    if (repris) {
      var note = document.createElement('p');
      note.className = 'cpb-coord-note';
      note.innerHTML = esc(t('Vos coordonnées de la dernière fois sont reprises.')) +
        ' <button type="button" class="cpb-lien-btn">' + esc(t('Effacer')) + '</button>';
      var premier = form.querySelector('[name="tel"]');
      var place = premier && (premier.closest('label') || premier);
      if (place && place.parentNode) { place.parentNode.insertBefore(note, place); }
      note.querySelector('button').addEventListener('click', function () {
        coordOubliees();
        efface = true;
        COORD_CHAMPS.forEach(function (n) {
          var el = champ(n);
          if (!el) { return; }
          if (el.tagName === 'SELECT') { el.selectedIndex = 0; } else { el.value = ''; }
          var ev;
          try { ev = new Event('change', { bubbles: true }); } catch (e) { ev = document.createEvent('Event'); ev.initEvent('change', true, true); }
          el.dispatchEvent(ev);
        });
        efface = false;
        note.parentNode.removeChild(note);
        if (premier) { premier.focus(); }
      });
    }

    var garde = function (e) {
      var n = e.target && e.target.name;
      if (efface || COORD_CHAMPS.indexOf(n) < 0) { return; }
      var cur = coordLues() || {};
      cur[n] = String(e.target.value || '').slice(0, 400);
      cur.t = Date.now();
      coordEcrites(cur);
    };
    form.addEventListener('input', garde);
    form.addEventListener('change', garde);
  }

  /* Jours ouvrables : du lundi au samedi. */
  function plusJoursOuvrables(d, n) {
    var r = new Date(d.getTime());
    while (n > 0) {
      r.setDate(r.getDate() + 1);
      if (r.getDay() !== 0) { n--; }
    }
    return r;
  }
  function dateCourte(d) {
    try {
      return d.toLocaleDateString(AR ? 'ar-MA' : 'fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });
    } catch (e) {
      return d.getDate() + '/' + (d.getMonth() + 1);
    }
  }
  function fenetreLivraison(ville) {
    var casa = memeVille(ville, 'Casablanca');
    var auj = new Date();
    return [plusJoursOuvrables(auj, casa ? 1 : 2), plusJoursOuvrables(auj, casa ? 2 : 4)];
  }

  function estimeLivraison(form, avant) {
    if (!form || form.querySelector('.cpb-estime')) { return; }
    var sel = form.querySelector('select[name="ville"]');
    if (!sel) { return; }
    var p = document.createElement('p');
    p.className = 'cpb-estime';
    p.setAttribute('aria-live', 'polite');
    p.hidden = true;
    if (avant && avant.parentNode) { avant.parentNode.insertBefore(p, avant); } else { form.appendChild(p); }
    var maj = function () {
      var v = sel.value === 'autre' ? '' : sel.value;
      if (!v) { p.hidden = true; return; }
      var f = fenetreLivraison(v);
      p.innerHTML = SVG.camion + '<span>' + esc(t('Livraison estimée : entre {a} et {b}', { a: dateCourte(f[0]), b: dateCourte(f[1]) })) + '</span>';
      p.hidden = false;
    };
    sel.addEventListener('change', maj);
    maj();
  }

  function barreFixeVersExpress(x) {
    var bar = document.querySelector('.cta-fixe');
    var lien = bar && bar.querySelector('.cta-fixe-principal');
    if (!bar || !lien) { return; }
    /* En capture : passe avant le panier du theme, qui ajouterait le flacon
       et changerait de page. Panier deja rempli : la barre garde son role
       (« Commander · N parfums » vers la page commande). */
    document.addEventListener('click', function (e) {
      if (!e.target.closest || !e.target.closest('.cta-fixe-principal') || P.count()) { return; }
      e.preventDefault();
      e.stopPropagation();
      x.scrollIntoView({ block: 'start', behavior: REDUIT ? 'auto' : 'smooth' });
      var vide = null;
      ['tel', 'nom', 'ville', 'adresse'].some(function (n) {
        var el = x.querySelector('[name="' + n + '"]');
        if (el && !el.value) { vide = el; return true; }
        return false;
      });
      var cible = vide || x.querySelector('.cpb-x-go');
      if (cible) { setTimeout(function () { try { cible.focus({ preventScroll: true }); } catch (err) { cible.focus(); } }, REDUIT ? 0 : 450); }
    }, true);
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (es) {
        bar.classList.toggle('cpb-cache', es[0].isIntersecting);
      }, { rootMargin: '0px 0px -10% 0px' }).observe(x);
    }
  }

  function moinsDeFriction() {
    var x = document.querySelector('.cpb-express');
    if (x) {
      estimeLivraison(x, x.querySelector('.cpb-x-recap'));
      retientCoordonnees(x);
      barreFixeVersExpress(x);
    }
    var ck = document.getElementById('ck-form');
    if (ck) {
      estimeLivraison(ck, null);
      retientCoordonnees(ck);
    }
  }

  /* ══════════════════════════════════════════════════════════════
     REMERCIEMENT : UN DEUXIEME PARFUM AVANT L'EXPEDITION
     La commande d'un seul flacon paie 35 DH de livraison ; a deux, elle
     est offerte. Sur la page de remerciement, tant que le colis n'est pas
     parti, on propose trois parfums proches (d'autres maisons). Le bouton
     ouvre WhatsApp avec la reference de la commande et le parfum a
     ajouter : c'est le meme echange que la confirmation, rien d'automatique.
  ══════════════════════════════════════════════════════════════ */
  function merciSecond() {
    var bloc = document.getElementById('ck-merci');
    if (!bloc || !/[?&]merci=1/.test(location.search) || bloc.querySelector('.cpb-merci-second')) { return; }
    var cmd = null;
    try { cmd = JSON.parse(localStorage.getItem('cp_commande_faite') || 'null'); } catch (e) {}
    if (!cmd || !cmd.t || Date.now() - cmd.t > 6 * 3600 * 1000 || !cmd.ref || !cmd.lignes || !cmd.lignes.length) { return; }
    var c = P.cfg || {};
    if (cmd.articles !== 1 || !(cmd.livraison > 0) || !c.wa) { return; }
    var ref = produit(cmd.lignes[0].s);
    if (!ref) { return; }
    var choix = proches(ref, 3).map(function (r) { return r.p; });
    if (!choix.length) { return; }

    var lien = function (p) {
      var msg = t('Bonjour, j’ai passé la commande {ref}. J’aimerais y ajouter {p} ({prix}), avec la livraison offerte. Merci !',
        { ref: cmd.ref, p: p.b + ' ' + p.n, prix: prixTexte(p) });
      return 'https://wa.me/' + c.wa + '?text=' + encodeURIComponent(msg);
    };
    var s = document.createElement('section');
    s.className = 'cpb cpb-merci-second';
    s.setAttribute('aria-label', t('Un deuxième parfum ?'));
    s.innerHTML =
      '<p class="cpb-titre-petit">' + esc(t('Avant l’expédition')) + '</p>' +
      '<h3 class="cpb-ms-titre">' + esc(t('Ajoutez un deuxième parfum : la livraison devient offerte.')) + '</h3>' +
      '<p class="cpb-ms-sous">' + esc(t('Votre colis n’est pas encore parti. Un message suffit, vous économisez {liv}.', { liv: P.fmt(cmd.livraison) })) + '</p>' +
      '<div class="cpb-ms-liste">' + choix.map(function (p) {
        return '<div class="cpb-ms-carte">' +
          '<a class="cpb-ms-photo" href="' + esc(urlFiche(p.s)) + '">' + vignette(p, 'cpb-vignette', 120, 150) + '</a>' +
          '<div class="cpb-ms-txt"><span class="cpb-ligne-maison">' + maisonHTML(p) + '</span>' +
            '<span class="cpb-ligne-nom">' + nomHTML(p) + '</span>' +
            '<span class="cpb-ligne-prix">' + prixTexte(p) + '</span></div>' +
          '<a class="cpb-ms-go" href="' + esc(lien(p)) + '" target="_blank" rel="noopener">' + SVG.wa + '<span>' + esc(t('Ajouter à ma commande')) + '</span></a>' +
        '</div>';
      }).join('') + '</div>';
    var apres = bloc.querySelector('.ck-merci-detail') || bloc.querySelector('.ck-merci-suite');
    if (apres && apres.parentNode) { apres.parentNode.insertBefore(s, apres.nextSibling); } else { bloc.appendChild(s); }
  }

  /* Recherche arrivee de /?s=… (renvoyee par PHP vers /#chercher=…). */
  function rechercheDepuisAdresse() {
    var m = /^#chercher=(.*)$/.exec(location.hash || '');
    if (!m) { return; }
    var q = '';
    try { q = decodeURIComponent(m[1].replace(/\+/g, ' ')); } catch (e) { q = m[1]; }
    try { history.replaceState(null, '', location.pathname + location.search); } catch (e) {}
    setTimeout(function () {
      ouvrirRecherche();
      if (champ && q) {
        champ.value = q;
        var ev;
        try { ev = new Event('input', { bubbles: true }); } catch (e) { ev = document.createEvent('Event'); ev.initEvent('input', true, true); }
        champ.dispatchEvent(ev);
      }
    }, 300);
  }

  /* ══════════════════════════════════════════════════════════════
     LIENS D'INFORMATION
     Pied de page : A propos, Contact, Conditions de vente, Retours,
     Confidentialite (seulement les pages publiees, fournies par PHP).
     Formulaires de commande : renvoi aux conditions de vente.
  ══════════════════════════════════════════════════════════════ */
  function pagesInfo() {
    var pages = CFG.pages || [];
    if (!pages.length) { return; }
    var lien = function (p) {
      return '<a href="' + esc(p.url) + '">' + esc(AR ? p.ar : p.fr) + '</a>';
    };
    var bas = document.querySelector('.foot-bas .foot-inner');
    if (bas && !bas.querySelector('.cpb-foot-info')) {
      var nav = document.createElement('nav');
      nav.className = 'cpb-foot-info';
      nav.setAttribute('aria-label', t('Informations'));
      nav.innerHTML = pages.map(lien).join('');
      bas.appendChild(nav);
    }
    var cgv = null;
    pages.forEach(function (p) { if (p.cle === 'conditions-de-vente') { cgv = p; } });
    if (!cgv) { return; }
    var mention = function (apres) {
      if (!apres || !apres.parentNode || apres.parentNode.querySelector('.cpb-cgv')) { return; }
      var m = document.createElement('p');
      m.className = 'cpb-cgv';
      m.innerHTML = esc(t('En confirmant, vous acceptez nos')) + ' ' + lien({ url: cgv.url, fr: t('conditions de vente'), ar: t('conditions de vente') }) + '.';
      apres.parentNode.insertBefore(m, apres.nextSibling);
    };
    mention(document.querySelector('.cpb-express .cpb-x-note'));
    mention(document.querySelector('.ck-mini'));
  }

  /* ══════════════════════════════════════════════════════════════
     ANIMATIONS
     L'accueil est deja mis en scene par le theme (GSAP). Ici : la fiche
     parfum et les blocs de l'extension, qui apparaissaient d'un coup.
     Regles : transform et opacity seulement (fluide sur un telephone
     d'entree de gamme), rien ne cache ce qui est deja a l'ecran au
     chargement (pas de flash, pas de retard sur la photo principale),
     et rien du tout si le telephone demande moins d'animations.
  ══════════════════════════════════════════════════════════════ */
  var A_REVELER = [
    'main.pf .pf-block', 'main.pf .pf-histoire', '.cpb-quiz-appel', '.cpb-express',
    '.cpb-dist-visuel', '.cpb-404-visuel', '.pf-sibs .pf-sib'
  ];

  function animations() {
    if (REDUIT || !('IntersectionObserver' in window) || !document.documentElement.classList) { return; }
    var racine = document.documentElement;
    racine.classList.add('cpb-anim');

    /* 1. Apparition au defilement, en cascade dans une rangee de cartes. */
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        if (!e.isIntersecting) { return; }
        e.target.classList.add('cpb-vu');
        io.unobserve(e.target);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.01 });
    var bas = window.innerHeight || 700;
    A_REVELER.forEach(function (sel) {
      var rang = 0, parent = null;
      document.querySelectorAll(sel).forEach(function (el) {
        if (el.getBoundingClientRect().top < bas) { return; }   /* deja visible : on n'y touche pas */
        if (el.parentNode !== parent) { parent = el.parentNode; rang = 0; }
        el.classList.add('cpb-r');
        el.style.setProperty('--cpb-i', String(Math.min(rang++, 6)));
        io.observe(el);
      });
    });

    /* 2. Fiche parfum : un halo dore respire derriere le flacon. */
    var vis = document.querySelector('main.pf .pf-visual, .lp-vedette .pf-visual');
    if (vis && !vis.querySelector('.cpb-halo')) {
      var halo = document.createElement('span');
      halo.className = 'cpb-halo';
      halo.setAttribute('aria-hidden', 'true');
      vis.insertBefore(halo, vis.firstChild);
    }

    /* 3. Panier : le compteur saute quand un parfum entre. */
    var avant = P.count();
    P.onChange(function () {
      var n = P.count();
      if (n > avant) {
        document.querySelectorAll('[data-panier-count]').forEach(function (c) {
          var pill = c.closest('.nav-pill') || c;
          pill.classList.remove('cpb-saut');
          void pill.offsetWidth;   /* relance l'animation */
          pill.classList.add('cpb-saut');
        });
      }
      avant = n;
    });

    /* 4. Favori : le coeur eclot quand on l'allume. */
    document.addEventListener('click', function (e) {
      var c = e.target.closest && e.target.closest('[data-cpb-coeur]');
      if (!c) { return; }
      setTimeout(function () {
        if (c.getAttribute('aria-pressed') !== 'true') { return; }
        c.classList.remove('cpb-eclot');
        void c.offsetWidth;
        c.classList.add('cpb-eclot');
      }, 0);
    });
  }

  /* ══════════════════════════════════════════════════════════════
     BOUTONS DE LA NAVIGATION
  ══════════════════════════════════════════════════════════════ */
  function boutonsNav() {
    var droite = document.querySelector('#nav .nav-right');
    if (!droite) { return; }
    var avant = droite.querySelector('.nav-pill') || droite.firstChild;

    var loupe = document.createElement('button');
    loupe.type = 'button';
    loupe.className = 'cpb-nav-btn cpb-nav-loupe';
    loupe.setAttribute('data-cpb-recherche', '');
    loupe.setAttribute('aria-label', t('Rechercher un parfum'));
    loupe.setAttribute('aria-haspopup', 'dialog');
    loupe.innerHTML = SVG.loupe;

    var coeur = document.createElement('button');
    coeur.type = 'button';
    coeur.className = 'cpb-nav-btn cpb-nav-fav';
    coeur.setAttribute('data-cpb-favoris', '');
    coeur.setAttribute('aria-label', t('Mes favoris'));
    coeur.setAttribute('aria-haspopup', 'dialog');
    coeur.innerHTML = SVG.coeur + '<span class="cpb-pastille" data-cpb-fav-count data-vide aria-hidden="true">0</span>';

    droite.insertBefore(loupe, avant);
    droite.insertBefore(coeur, avant);
  }

  /* ══════════════════════════════════════════════════════════════
     DEMARRAGE — chaque module isole : une erreur dans l'un ne prive
     pas le visiteur des autres, ni surtout du panier.
  ══════════════════════════════════════════════════════════════ */
  function demarre() {
    [bandeau, boutonsNav, appelQuiz, visuelsSite, fiche, expressFiche, moinsDeFriction, majFavoris, pagesInfo, merciSecond, rechercheDepuisAdresse, animations].forEach(function (f) {
      try { f(); } catch (e) { if (window.console) { console.warn('[Comptoir Boutique]', e); } }
    });
    /* Lien partageable vers le quiz : /#trouver-mon-parfum (bio Instagram,
       message WhatsApp). */
    if (location.hash === '#trouver-mon-parfum') {
      setTimeout(function () { try { ouvrirQuiz(); } catch (e) {} }, 300);
    }
    /* Points d'entree, pour un bouton ajoute plus tard dans une page (ou
       [data-cpb-quiz], [data-cpb-recherche], [data-cpb-favoris]) et pour
       les tests. Aucun ne modifie l'etat du visiteur. */
    window.CPB_API = { recherche: ouvrirRecherche, quiz: ouvrirQuiz, favoris: ouvrirFavoris, cherche: cherche, proches: proches, classement: classement };
  }

  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', demarre); }
  else { demarre(); }
})();
