/**
 * Le Comptoir des Parfums — carnet de commandes dans une feuille Google.
 *
 * Une ligne par commande, dans l'onglet « Commandes », dans l'ordre du
 * travail de tous les jours :
 *   Date · Référence · Client · Téléphone (lien WhatsApp) · Ville · Adresse ·
 *   Produits · À encaisser · État · Colis · À faire · Note
 *
 * Le site écrit la ligne à la commande, puis met à jour État, Colis et
 * À faire à chaque changement (confirmation WhatsApp, colis OzonExpress,
 * livraison, annulation). La colonne Note est à vous : le site n'y touche pas.
 *
 * MARCHE À SUIVRE (une fois)
 * ──────────────────────────
 *  1. Dans la feuille : Extensions > Apps Script. Collez ce fichier à la place
 *     du contenu, enregistrez.
 *  2. Lancez la fonction reprendre() une fois (menu en haut, puis Exécuter) :
 *     elle crée l'onglet « Commandes », y recopie les commandes de l'ancien
 *     onglet et renomme les anciens onglets en « Archive – … ». Rien n'est
 *     supprimé.
 *  3. Déployer > Gérer les déploiements > crayon > Version : nouvelle version
 *     > Déployer. L'adresse /exec ne change pas : le site continue d'écrire.
 *  4. Dans WordPress, liste des commandes : « Mettre la feuille à jour ». Le
 *     message affiche une clé : posez-la ici dans Paramètres du projet >
 *     Propriétés du script, propriété CLE_MAJ, puis relancez la mise à jour.
 *
 * Après chaque modification du script, il faut REDÉPLOYER (étape 3), sinon
 * l'ancienne version continue de répondre.
 */

/** Identifiant de la feuille, lu dans son adresse docs.google.com/spreadsheets/d/…/edit */
var FEUILLE_ID = '1obJSnPYBgtQ9nGKUAIqosRg12hXmc5LUSFK9pqiuCJ0';

/** L'onglet de travail. */
var ONGLET = 'Commandes';

var COLONNES = [
  'Date', 'Référence', 'Client', 'Téléphone', 'Ville', 'Adresse',
  'Produits', 'À encaisser (DH)', 'État', 'Colis', 'À faire', 'Note'
];
var COL = { date: 1, ref: 2, client: 3, tel: 4, ville: 5, adresse: 6, produits: 7,
            total: 8, etat: 9, colis: 10, afaire: 11, note: 12 };

function classeur() {
  return FEUILLE_ID ? SpreadsheetApp.openById(FEUILLE_ID) : SpreadsheetApp.getActiveSpreadsheet();
}

/** L'onglet « Commandes », créé et mis en forme s'il n'existe pas encore. */
function onglet() {
  var c = classeur();
  var f = c.getSheetByName(ONGLET);
  if (!f) {
    f = c.insertSheet(ONGLET, 0);
    f.getRange(1, 1, 1, COLONNES.length).setValues([COLONNES]);
    miseEnForme(f);
  }
  return f;
}

function texte(t) {
  return ContentService.createTextOutput(t);
}

function doPost(e) {
  // Le site envoie chaque commande deux fois (navigateur, puis serveur) : le
  // verrou évite que les deux s'écrivent en même temps.
  var verrou = LockService.getScriptLock();
  try {
    verrou.waitLock(20000);
  } catch (err) {
    return texte('occupe');
  }
  try {
    var p = (e && e.parameter) || {};
    var f = onglet();
    var ref = String(p.ref || '').trim();

    if (p.action === 'maj') {
      // La clé de WordPress, rangée dans les propriétés du script (CLE_MAJ),
      // pas ici : ce fichier est public. Sans elle, n'importe qui connaissant
      // l'adresse pourrait changer l'état d'une ligne. L'écriture d'une
      // commande, elle, reste ouverte : le navigateur du client l'envoie aussi.
      var cle = PropertiesService.getScriptProperties().getProperty('CLE_MAJ');
      if (!cle || String(p.cle || '') !== cle) return texte('refus');
      return texte(majLigne(f, ref, p));
    }

    // Déjà là ? La référence fait foi : on ne réécrit pas la ligne.
    if (ref && ligneDe(f, ref)) return texte('doublon');

    f.appendRow([
      new Date(), ref, String(p.nom || ''), '', String(p.ville || ''),
      String(p.adresse || ''), produits(p.panier), Number(p.total || 0),
      'À confirmer', '', 'Confirmer', ''
    ]);
    var ligne = f.getLastRow();
    // appendRow remet la date au format long : on remet celui de la colonne.
    f.getRange(ligne, COL.date).setNumberFormat('dd/mm hh:mm');
    telephone(f, ligne, p.tel);
    return texte('ok');
  } catch (err) {
    // On répond quand même : une erreur ici ne doit jamais se voir côté client.
    return texte('erreur');
  } finally {
    verrou.releaseLock();
  }
}

/** Met à jour État, Colis et À faire d'une commande. Note n'est jamais touchée. */
function majLigne(f, ref, p) {
  var ligne = ref ? ligneDe(f, ref) : 0;
  if (!ligne) return 'absente';
  if (p.etat !== undefined) f.getRange(ligne, COL.etat).setValue(String(p.etat));
  if (p.colis !== undefined) f.getRange(ligne, COL.colis).setValue(String(p.colis));
  if (p.afaire !== undefined) f.getRange(ligne, COL.afaire).setValue(String(p.afaire));
  return 'ok';
}

/** Numéro de ligne d'une référence (colonne B), ou 0. */
function ligneDe(f, ref) {
  var n = f.getLastRow();
  if (n < 2) return 0;
  var refs = f.getRange(2, COL.ref, n - 1, 1).getValues();
  for (var i = refs.length - 1; i >= 0; i--) {
    if (String(refs[i][0]).trim() === ref) return i + 2;
  }
  return 0;
}

/**
 * Le téléphone, lisible (06 12 34 56 78) et cliquable : un toucher ouvre
 * WhatsApp sur ce numéro. Un lien posé sur le texte, pas une formule : une
 * formule HYPERLINK dépend de la langue de la feuille (« ; » en français) et
 * donnait #ERROR!.
 */
function telephone(f, ligne, tel) {
  var d = String(tel || '').replace(/\D/g, '');
  if (d.indexOf('212') === 0) d = '0' + d.slice(3);
  var cellule = f.getRange(ligne, COL.tel);
  if (!/^0[5-7]\d{8}$/.test(d)) {
    cellule.setValue("'" + String(tel || ''));
    return;
  }
  var lisible = d.replace(/(\d{2})(?=\d)/g, '$1 ');
  cellule.setRichTextValue(SpreadsheetApp.newRichTextValue()
    .setText(lisible).setLinkUrl('https://wa.me/212' + d.slice(1)).build());
}

/**
 * Le panier du site (« 1× Maison Parfum (slug) — 349 DH », une ligne par
 * parfum) devient « 1× Maison Parfum », une ligne par parfum.
 */
function produits(panier) {
  return String(panier || '').split('\n').map(function (l) {
    return l.replace(/\s*\([a-z0-9-]+\)\s*—.*$/, '').trim();
  }).filter(String).join('\n');
}

/** Remet la mise en forme de l'onglet. À lancer depuis l'éditeur si elle a été abîmée. */
function mettreEnForme() {
  miseEnForme(onglet());
}

/** En-têtes figés, filtre, largeurs, alignements, couleurs par état. */
function miseEnForme(f) {
  var n = COLONNES.length;
  f.setFrozenRows(1);
  f.getRange(1, 1, 1, n).setFontWeight('bold').setBackground('#2b1a2e').setFontColor('#f5f0e8');
  var largeurs = [130, 120, 150, 120, 110, 220, 260, 135, 110, 200, 110, 220];
  for (var i = 0; i < n; i++) f.setColumnWidth(i + 1, largeurs[i]);
  f.getRange('A:A').setNumberFormat('dd/mm hh:mm');
  f.getRange('F:G').setWrap(true);
  f.getRange(1, 1, f.getMaxRows(), n).setVerticalAlignment('middle');
  // Texte à gauche, même un nom saisi au clavier arabe ou une adresse en chiffres.
  f.getRange('B:G').setHorizontalAlignment('left');
  f.getRange('I:L').setHorizontalAlignment('left');
  if (!f.getFilter()) f.getRange(1, 1, Math.max(2, f.getMaxRows()), n).createFilter();

  var etats = f.getRange('I2:I');
  var couleur = function (mot, fond) {
    return SpreadsheetApp.newConditionalFormatRule().whenTextEqualTo(mot)
      .setBackground(fond).setRanges([etats]).build();
  };
  var afaire = f.getRange('K2:K');
  // Pas de règle à formule : sa syntaxe dépend de la langue de la feuille. La
  // première règle qui correspond l'emporte : « — » en gris, le reste en rouge.
  f.setConditionalFormatRules([
    couleur('À confirmer', '#fde7c2'),
    couleur('Sans réponse', '#f8c9c4'),
    couleur('Confirmée', '#dbe8fb'),
    couleur('Expédiée', '#e3dcf7'),
    couleur('Livrée', '#d4ecd5'),
    couleur('Annulée', '#e6e6e6'),
    SpreadsheetApp.newConditionalFormatRule().whenTextEqualTo('—').setFontColor('#9e9e9e').setRanges([afaire]).build(),
    SpreadsheetApp.newConditionalFormatRule().whenCellNotEmpty()
      .setBold(true).setFontColor('#b3261e').setRanges([afaire]).build()
  ]);
}

/**
 * Une fois : crée « Commandes », y recopie les commandes de l'ancien onglet
 * (« Feuille 1 », colonnes Date · Référence · Téléphone · Nom · Ville ·
 * Adresse · Articles · Panier · Livraison · Total · État), puis renomme les
 * anciens onglets en « Archive – … ». Relancée, elle n'ajoute que les
 * références absentes. L'état exact (confirmée, livrée…) arrive ensuite du
 * site (bouton « Mettre la feuille à jour » de la liste des commandes).
 */
function reprendre() {
  var c = classeur();
  var ancien = c.getSheetByName('Feuille 1') || c.getSheetByName('Archive – Feuille 1');
  var f = onglet();
  if (!ancien || ancien.getLastRow() < 2) return 'rien à reprendre';

  var deja = {};
  if (f.getLastRow() > 1) {
    f.getRange(2, COL.ref, f.getLastRow() - 1, 1).getValues().forEach(function (r) { deja[String(r[0]).trim()] = 1; });
  }
  var lignes = ancien.getRange(2, 1, ancien.getLastRow() - 1, 11).getValues();
  var nouvelles = [];
  var tels = [];
  lignes.forEach(function (r) {
    var ref = String(r[1] || '').trim();
    if (!ref || deja[ref]) return;
    deja[ref] = 1;
    var ancienEtat = String(r[10] || '').replace('√†', 'à').trim();
    var etat = /rappeler/i.test(ancienEtat) || !ancienEtat ? 'À confirmer' : ancienEtat;
    nouvelles.push([r[0], ref, r[3], '', r[4], r[5], produits(r[7]), Number(r[9] || 0), etat, '',
                    etat === 'À confirmer' ? 'Confirmer' : '—', '']);
    tels.push(r[2]);
  });
  if (nouvelles.length) {
    var debut = f.getLastRow() + 1;
    f.getRange(debut, 1, nouvelles.length, COLONNES.length).setValues(nouvelles);
    for (var i = 0; i < tels.length; i++) telephone(f, debut + i, tels[i]);
    // Du plus ancien au plus récent, comme les nouvelles commandes qui s'ajoutent en bas.
    f.getRange(2, 1, f.getLastRow() - 1, COLONNES.length).sort({ column: COL.date, ascending: true });
  }
  if (ancien.getName() === 'Feuille 1') ancien.setName('Archive – Feuille 1');
  var conf = c.getSheetByName('Confirmation');
  if (conf) conf.setName('Archive – Confirmation');
  c.setActiveSheet(f);
  return nouvelles.length + ' commande(s) reprise(s)';
}

/**
 * Ouvrez l'adresse du déploiement dans une fenêtre privée : ce texte prouve
 * que le script est joignable SANS connexion, condition pour que le site
 * puisse écrire.
 */
function doGet() {
  var etat;
  try {
    var f = onglet();
    etat = 'onglet « ' + ONGLET + ' » atteint, ' + Math.max(0, f.getLastRow() - 1) + ' commande(s).';
  } catch (err) {
    etat = 'FEUILLE INACCESSIBLE — vérifiez FEUILLE_ID en haut du script.';
  }
  return texte('Carnet de commandes du Comptoir — prêt. ' + etat);
}
