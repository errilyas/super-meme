LE COMPTOIR DES PARFUMS — thème WordPress sur-mesure
=====================================================

VERSIONS 3.x — ÉDITION MAISON
=============================
Même identité — aubergine, or du sceau, Cormorant Garamond + Jost, sceau
CP — avec un fini de maison de parfum. Tout le visuel nouveau vit dans UN
bloc, en fin de style-site.css (« ÉDITION MAISON — COUCHE DE FINITION ») :
le retirer rend exactement la v2.

VERSION 3.1 — FICHE PARFUM ET PAGE COMMANDE
  Fiche parfum
    - Nouvel ordre : l'achat, puis LE PARFUM (son récit en grande citation)
      et SA PYRAMIDE (tête, cœur, fond, reliés par un fil d'or, avec
      « les premières minutes / après une heure / des heures durant »),
      puis la fiche, les preuves, le testeur expliqué, la même maison.
      Avant, le client lisait les preuves et l'explication du testeur avant
      de découvrir le parfum qu'il regardait.
    - Les notes principales dès le bloc d'achat, avec un lien vers la
      pyramide complète.
    - Photo dans un écrin (halo doré, ombre au sol) ; sur grand écran, c'est
      elle qui suit le défilement, plus le bloc d'achat.
  Page commande
    - Grand écran : formulaire à gauche, récapitulatif à droite, collé à
      l'écran pendant la saisie. Téléphone : panier puis livraison, comme
      avant.
    - Les suggestions « deuxième parfum » passent sous le panier au lieu
      de couper le formulaire entre l'adresse et le bouton.
    - Étapes en frise numérotée ; champs plus hauts avec focus doré.

VERSION 3.0
Ce qui change à l'œil
  - Texte courant en Jost 300 au lieu de 200 (le 200 se délavait sur le
    fond sombre), gris secondaires remontés, libellés en capitales un peu
    plus grands et moins espacés.
  - Un seul or, celui du sceau, en dégradé sur TOUS les boutons d'achat :
    accueil, fiche parfum, tiroir du panier, page commande, barre mobile.
    Reflet qui traverse le bouton au survol.
  - En-tête : panier avec icône de sac et pastille ; sur téléphone, le sac
    seul et sa pastille, pour dégager la barre.
  - Héros allégé : les trois chiffres (références, maisons, 0 DH) quittent
    le héros — ils sont en grand dans le bandeau « Chiffres clés » plus bas.
    La vitrine de flacons s'estompe sur les bords au lieu d'être coupée net.
  - Preuves clients : même format pour toutes (4:3), coins d'écran de
    téléphone, trois de front sur grand écran.
  - Cartes produit : filet d'or qui se trace sous la carte au survol,
    nom et prix plus présents.
  - Pied de page complet : marque et devise, la maison, nos engagements,
    nous écrire (WhatsApp, téléphone), réseaux. Traduit en arabe.

Défauts corrigés (présents en v2, y compris sur le site en ligne)
  - « Voir plus de parfums (185) » : bouton au fond gris du navigateur,
    texte crème illisible.
  - Bandeau des chiffres : deux bandes grises sur les côtés.
  - Héros : les chiffres passaient sous l'indicateur « Défiler ».
  - Tiroir du panier fermé : son ombre dépassait sur le bord droit de
    chaque page (bande grise en thème clair).
  - ARABE : la page faisait 10 000 px de large (lien d'évitement rangé à
    left:-9999px, qui devient une zone défilable en lecture droite-gauche).
    Sur téléphone, la page glissait de côté dans le vide.

Vérifié : accueil, fiche parfum et commande, en français et en arabe, en
thème sombre et clair, sans débordement horizontal de 375 à 1 920 px,
aucune erreur JavaScript, panier (ajout, tiroir, suggestions) fonctionnel.


INSTALLATION
1. Apparence > Thèmes > Ajouter > Téléverser un thème.
2. Envoyer le dossier zippé (comptoir-parfums.zip).
3. Activer le thème.
4. La page d'accueil s'affiche toute seule (front-page.php). Aucune page
   à créer dans l'admin, ni pour les fiches parfum, ni pour la commande.


CE QUE VEND LE SITE
===================
Des TESTEURS ORIGINAUX, et rien d'autre. Un testeur est un flacon
AUTHENTIQUE de la maison — Chanel, Dior, Tom Ford — qu'elle fabrique pour
faire sentir ses parfums en boutique : même jus, même concentration, même
tenue que le flacon du rayon. Ce qui change est l'emballage, un flacon de
démonstration au lieu du coffret de luxe, et c'est de là, et de nulle part
ailleurs, que vient l'écart de prix.

Le site a longtemps annoncé « dupes haute fidélité ET testeurs », avec un
champ 'pres' censé distinguer les deux fiche par fiche. C'était faux : un
dupe est une imitation fabriquée par un tiers, et il ne s'en vend pas ici.
Le champ n'avait d'ailleurs jamais été renseigné sur une seule des 209
références. Il a été retiré, ainsi que toute la mécanique qui allait avec,
plutôt que corrigé — il ne reste rien qui puisse remettre le mot sur une
fiche un jour.

La pastille des cartes montre donc la FAMILLE OLFACTIVE, seule chose qui
distingue une fiche d'une autre, et la ligne « Présentation » de chaque
fiche dit « Testeur original, flacon neuf ».

Le message de la page d'accueil — « Le même parfum. Sans le prix
boutique. » — doit le rester : les prix du catalogue (309 à 1 469 DH) ne
sont crédibles que pour cette offre-là.


ARCHITECTURE — UNE SEULE SOURCE PAR CHOSE
=========================================
Le point important de cette version : plus rien n'existe en double entre
la maquette et le thème. Les deux chargent LES MÊMES fichiers.

  style-site.css   TOUT le CSS du site (1 730 lignes) — accueil,
                   fiche parfum, page commande, gabarit de secours
  theme.js         toute l'interface : héros 3D, vitrine, catalogue, FAQ,
                   révélations au défilement, ruban, onglets, thème clair
  panier.js        panier client (localStorage) + tiroir latéral
  commande.js      page commande (récapitulatif, formulaire, WhatsApp)
  produits.php     les 209 fiches — source du thème WordPress
  produits.js      les 209 fiches — source de la maquette statique
                   (strictement identiques, vérifié champ par champ)

Modifier le design ou le comportement = modifier un seul fichier, et les
deux supports suivent.

  MAQUETTE : comptoirv3-motion.html, parfum.html, commander.html
  THÈME WP : header.php, front-page.php, footer.php, functions.php,
             template-parfum.php, template-commander.php, index.php

Le thème est le PORTAGE de la maquette : c'est lui qui part en ligne.
En cas de doute sur le rendu attendu, la maquette fait référence.


LE CATALOGUE
============
Une grille de cartes photo, pas une liste. On voit le flacon d'abord — c'est
ce qu'on achète. Cinq colonnes sur grand écran, deux sur téléphone.

Cinq filtres, tous instantanés, sans rechargement :
      recherche (parfum ou maison, accents indifférents)
      maison (37, avec le nombre de références)
      pour (Femme / Homme / Mixte)
      famille olfactive (les 67 familles fines du catalogue se ramènent
        à une dizaine de grandes familles : Floral, Ambré, Boisé…)
      tri (maison, prix croissant, prix décroissant, nom)

La maison choisie se retrouve dans l'URL (#catalogue?maison=chanel) : le lien
reste partageable, et les noms du ruban défilant y mènent toujours.

Sur la carte : photo, maison, nom, concentration, destinataire, prix. Rien
d'autre — voir ci-dessous.

CE QUE LA CARTE N'AFFICHE PAS, ET POURQUOI
------------------------------------------
Deux informations manquent dans produits.php, et je ne les ai pas inventées :

  LE VOLUME (100 ML). Aucune référence ne porte sa contenance. Pour
  l'afficher, ajouter un champ à chaque entrée, par exemple 'ml' => '100'.

  LE PRIX BARRÉ (279 DH au lieu de 899 DH). Afficher un prix barré sans
  connaître le vrai prix de référence, ce serait annoncer une remise
  inventée. C'est trompeur pour le client et c'est encadré par la loi.
  Si vous avez les prix boutique réels, ajoutez 'pr_ref' => '899 DH' et je
  branche l'affichage.

Tant que ces champs sont absents, la carte dit uniquement ce qui est vrai.


CE QUE LE THÈME FAIT MIEUX QUE LA MAQUETTE
==========================================
- La grille du catalogue est rendue par PHP, pas construite en JavaScript :
  les 209 fiches sont indexables par Google et lisibles sans JS. Le script
  détecte le balisage déjà présent et se contente de le câbler.
- Les fiches parfum sont rendues par PHP (la maquette les construit en JS,
  donc invisibles pour un moteur de recherche ou un partage WhatsApp).
- La liste des photos disponibles vient de file_exists(), pas d'une liste
  tenue à la main qui peut se désynchroniser du disque.
- Les prix affichés dans « Notre distinction » et « Sélection du moment »
  sont lus dans produits.php : ils ne peuvent plus mentir.
- Titre, description, canonical, Open Graph et données structurées
  (Product avec prix / Store) sont générés par functions.php selon la page.
- Un ?parfum=slug inconnu renvoie un vrai 404, pas une page 200 vide.


LE CARNET DE COMMANDES
======================
Une commande est enregistrée AVANT que WhatsApp ne s'ouvre, à deux endroits :

  1. Dans WordPress, menu « Commandes » : téléphone cliquable, ville, panier,
     total, et un état qui vaut « à rappeler » par défaut. Rien à installer,
     c'est le thème qui crée le menu.

  2. Dans une feuille Google, consultable du téléphone et exportable en Excel
     pour le transporteur. Le script est dans tools/feuille-commandes.gs et la
     marche à suivre est écrite en tête de ce fichier ; il n'y a ensuite qu'à
     coller l'URL du déploiement dans comptoir_feuille_google(), en haut de
     functions.php.

     Le lien de partage de la feuille NE SUFFIT PAS : un site ne peut pas
     écrire dans une feuille Google avec une simple URL. C'est le script
     déployé en application web qui sert de porte, et c'est son adresse en
     /exec qu'attend le thème.

Pourquoi avant, et pas après : le client qui remplit le formulaire puis
n'appuie pas sur « Envoyer » dans WhatsApp laisse quand même son numéro. C'est
lui qu'on rappelle, et c'est la moitié du métier en paiement à la livraison.

CHAQUE COMMANDE PART DEUX FOIS VERS LA FEUILLE : une fois depuis le navigateur
du client, une fois depuis WordPress. C'est volontaire. Le navigateur peut
échouer — réseau qui coupe au changement d'application, extension qui bloque
les requêtes vers Google, onglet fermé trop vite — et le serveur, lui, ne
connaît aucun de ces aléas. La feuille reconnaît les doublons à la référence
et n'écrit jamais deux fois la même commande.

Si les deux échouent, la commande est marquée dans l'admin (colonne Feuille,
symbole ⟳) et WordPress la renvoie tout seul, chaque heure, jusqu'à ce qu'elle
passe. Une commande ne peut donc pas se perdre entre le site et la feuille.

LA PANNE LA PLUS COURANTE : le déploiement n'est pas ouvert. Dans Apps Script,
« Qui a accès » doit valoir « Tout le monde » — et non « Tout le monde
disposant d'un compte Google », qui ne laisse pas passer le site. Le symptôme
est net : toutes les commandes portent ⟳, et le motif affiché au survol
mentionne une réponse de Google au lieu de « ok ». Google répond alors sa page
de connexion, parfois avec un code 200 : c'est pourquoi le thème exige la
réponse du script lui-même, « ok » ou « doublon », et ne se fie pas au code
HTTP.

Une ligne peut arriver marquée « à vérifier » : le jeton de sécurité était
périmé, ce qui arrive quand l'hébergeur sert une page mise en cache. On
enregistre quand même — perdre une vraie commande coûterait plus cher.

Après la commande, le client voit un écran de remerciement avec le montant à
régler au livreur et les trois étapes qui suivent. Le panier est vidé à ce
moment-là.


LA GARANTIE ANNONCÉE
====================
Le site promet « satisfait ou remboursé » : le client refuse le colis à la
porte, ou se fait rembourser après l'avoir ouvert. Cette phrase est écrite
dans comptoir_gages() et répétée au même sens dans les engagements, l'étape 04
du parcours, la FAQ, la comparaison et le pied de page.

Si la promesse change un jour, elle change PARTOUT : une garantie qui varie
d'une page à l'autre ne rassure plus personne, et c'est la première chose
qu'un acheteur qui hésite remarque.


RÉGLAGES DU QUOTIDIEN
=====================
Tout est en haut de functions.php :
      comptoir_wa_numero()    numéro WhatsApp (format 212…, sans +)
      comptoir_livraison_dh() frais de livraison (0 = « à confirmer »)

Les neuf parfums mis en avant sur la page d'accueil : fonction
comptoir_selection() dans functions.php — on n'y met que des slugs, tout
le reste (nom, prix, notes, photo) vient de produits.php et d'img/produits/.
Un slug dont la photo manque montre la silhouette de flacon, jamais une
image cassée : autant en choisir un qui en a une.


LE PARCOURS DE COMMANDE
=======================
Le bouton « Commander maintenant » du bas de page mène à /?commander=1, pas
directement à WhatsApp : le client voit d'abord son panier et laisse ses
coordonnées.

La page est en deux volets : le panier et ses totaux à gauche, la livraison à
droite. Quatre champs, tous obligatoires — nom, téléphone, ville, ADRESSE
COMPLÈTE. L'adresse est demandée ici, ce qui évite l'appel de confirmation
qu'il fallait passer avant pour l'obtenir.

Le téléphone est vérifié au format marocain (06/07 suivi de 8 chiffres, ou
+212). L'ancien test « au moins 8 chiffres » laissait passer un numéro
tronqué, et un numéro faux, c'est une livraison perdue. La ville propose les
24 principales villes en suggestion, pour éviter les fautes de frappe qui
égarent un colis.

« Confirmer la commande » ouvre WhatsApp avec le détail, les totaux ET
l'adresse déjà écrits. Il ne reste qu'à envoyer.


URLS, SANS AUCUNE PAGE À CRÉER
==============================
  /?parfum=<slug>     fiche parfum      ex. /?parfum=tom-ford--oud-wood
  /?commander=1       page commande
  /?maison=<slug>     catalogue filtré  ex. /?maison=tom-ford#catalogue

functions.php intercepte ?parfum= et ?commander= et sert le bon gabarit.
Le filtre par maison se fait dans le navigateur : le catalogue complet est
déjà dans la page, il n'y a rien à redemander au serveur.


PHOTOS — TOUT EST EN WEBP
=========================
img/produits/<slug>.webp      cartes du catalogue et visuel des fiches
                              209 présentes sur 209
img/heros/<maison>.webp       visuels DÉTOURÉS de la vitrine du héros
                              39 maisons sur 39

Une photo déposée est prise en compte toute seule, rien d'autre à modifier.
ELLE DOIT ÊTRE EN .webp. Pour convertir un lot de JPEG :

    python3 - <<'EOF'
    from PIL import Image, ImageOps
    import glob, os
    for p in glob.glob('img/produits/*.jpg'):
        im = ImageOps.exif_transpose(Image.open(p))
        if max(im.size) > 900:
            r = 900 / max(im.size)
            im = im.resize((round(im.width*r), round(im.height*r)), Image.LANCZOS)
        im.convert('RGB').save(p[:-4] + '.webp', 'WEBP', quality=82, method=6)
        os.remove(p)
    EOF

POURQUOI WEBP SEUL, SANS REPLI JPEG
-----------------------------------
Les photos pesaient 10,7 Mo et les visuels de la vitrine 11 Mo en PNG, pour
un thème de 22 Mo que beaucoup d'hébergements refusent de téléverser. En
WebP, redimensionné à 900 px — la fiche n'affiche jamais plus de 420 px, soit
840 px sur un écran retina — l'ensemble tombe à 6,9 Mo.

Aucun repli JPEG n'est conservé, et c'est volontaire : le site utilise déjà
aspect-ratio (Safari 15, 2021), backdrop-filter et ResizeObserver. WebP est
reconnu depuis Safari 14 (2020) et Chrome 32. Autrement dit, tout navigateur
capable d'afficher la mise en page sait lire du WebP — un repli JPEG ne
protégerait personne et doublerait le poids.

UN POINT À VÉRIFIER UNE FOIS EN LIGNE
-------------------------------------
L'aperçu de partage d'une fiche parfum (og:image) pointe maintenant vers un
.webp. WhatsApp et Facebook l'acceptent, mais si un partage montrait un
aperçu sans photo, dites-le-moi : il suffira de faire pointer og:image vers
og-image.jpg, qui reste en JPEG.
Une photo absente ne provoque jamais d'image cassée ni de 404 : la place
est tenue par une silhouette de flacon dorée.

Les visuels du héros se régénèrent avec :
      python3 tools/build-heros.py
Seules les variantes détourées y entrent — jamais une photo à fond blanc
posée telle quelle sur le fond aubergine.


VOIR LE THÈME SANS INSTALLER WORDPRESS
======================================
Cette machine n'a pas de PHP. Pour regarder quand même le rendu réel des
gabarits :

      python3 tools/apercu-theme.py
      python3 -m http.server 8899
      puis http://localhost:8899/wp-accueil.html

Le script exécute header.php / front-page.php / footer.php et produit
wp-accueil.html, wp-parfum.html et wp-commande.html. Ces trois fichiers
sont des artefacts : hors dépôt, à regénérer après chaque modification.

Pour la maquette :
      python3 -m http.server 8899
      puis http://localhost:8899/comptoirv3-motion.html
(affichage figé sur une ancienne version ? Cmd+Shift+R)


CE QUI A ÉTÉ VÉRIFIÉ
====================
- Page d'accueil, fiche parfum et page commande rendues et parcourues dans
  un navigateur, en thème clair ET sombre.
- Aucun bloc du site ne reste invisible après défilement complet.
- Aucun débordement horizontal de 375 px à 1 920 px.
- Contraste du texte conforme AA (4,5:1) dans les deux thèmes.
- Aucun lien mort dans le rendu.
- Panier : ajout, quantités, retrait, totaux, message WhatsApp, y compris
  avec une référence sans photo.
- DEUX PANNES SIMULÉES, parce qu'elles rendaient le site totalement noir :
    · ScrollTrigger absent alors que GSAP a chargé (deux fichiers CDN
      distincts, l'un peut échouer seul) ;
    · sessionStorage qui lève une exception (Safari navigation privée,
      cookies refusés).
  Dans les deux cas le site fonctionne maintenant : préchargeur retiré,
  catalogue complet, bascule de thème, rien d'invisible.


POIDS DES PAGES
===============
Après la revue :
  accueil        297 Ko -> 224 Ko
  fiche parfum   167 Ko ->  94 Ko   (et 600 Ko de three.js en moins)
  page commande  166 Ko ->  93 Ko   (idem)

three.js ne sert qu'au flacon 3D et à la vitrine du héros : il n'est plus
chargé que sur la page d'accueil. Et l'index produit par slug, qui doublait
les 66 Ko de catalogue injectés dans chaque page, est déduit côté navigateur
au lieu d'être envoyé une deuxième fois.


IL RESTE À FAIRE — CÔTÉ CONTENU
===============================
1. LE LIEN TIKTOK. Branchés à ce jour :
       WhatsApp   +212 717 961 180
       Instagram  https://www.instagram.com/le_comptoir_parfums
       Facebook   https://www.facebook.com/profile.php?id=61573721267555
   Il reste TikTok — remplacer son href="#" dans TROIS fichiers :
   footer.php (le thème), comptoirv3-motion.html et parfum.html (la maquette).

   L'adresse Facebook est en profile.php?id=… parce que la page n'a pas
   encore de nom d'utilisateur. Si vous en choisissez un dans les réglages
   de la page, l'adresse devient https://www.facebook.com/<votre-nom> —
   plus lisible et plus facile à dicter. Dites-le-moi et je la remplacerai.

   Tant qu'un lien vaut « # » il est volontairement masqué : un lien mort
   coûte plus cher qu'un lien absent. Dès qu'une vraie URL est posée, le
   lien réapparaît tout seul, avec target="_blank" et rel="noopener".

   Attention aux liens copiés depuis un QR code ou un bouton de partage :
   ils traînent des paramètres comme ?utm_source=qr et &stkn=…  Le premier
   ferait passer tout le trafic du site pour des scans de QR code dans vos
   statistiques, le second est un jeton personnel. Ne garder que l'adresse
   du profil.

2. QUATRE PHOTOS À REFAIRE. Les 209 références ont toutes leur photo et
   les 39 maisons ont toutes leur visuel de vitrine. Quatre restent
   faibles :
       Zadig & Voltaire · le flacon est coupé au bord de la seule photo
       Lacoste          · 493 px, trop peu défini pour un écran retina
       Montblanc        · 581 px, idem
       Burberry Goddess · le détourage garde une tablette blanche sous le
                          flacon, invisible en thème clair, voyante sur le
                          fond aubergine
   Une photo du flacon sur une surface claire suffit. Relancer ensuite
   tools/build-heros.py : il signale de lui-même ce qui reste insuffisant.

3. LES PYRAMIDES OLFACTIVES. Elles viennent des compositions publiques
   connues. À relire maison par maison avant la mise en ligne.

4. LES FRAIS DE LIVRAISON. 35 DH partout pour l'instant
   (comptoir_livraison_dh). À confirmer, ou à passer à 0 pour afficher
   « à confirmer » et les annoncer au téléphone.


UNE SEULE FEUILLE DE STYLE
==========================
style-site.css est désormais le SEUL endroit où vit du CSS. Les neuf pages du
projet — les trois de la maquette et les six gabarits WordPress — la chargent.
Plus aucune page ne porte de copie locale des couleurs, du bandeau ou du pied
de page.

Le fichier est organisé en sections repérables :
      socle (jetons, remise à zéro, bandeau, pied de page, mode clair)
      page d'accueil (héros, vitrine, catalogue, FAQ…)
      FICHE PARFUM
      PAGE COMMANDE
      GABARIT DE SECOURS

Chaque section de page est rattachée à ses propres classes (.pf-*, .ck-*,
.cp-secours) : une règle de la fiche ne peut pas atteindre l'accueil.

Les deux seules exceptions, volontaires : le bloc <noscript><style> de
comptoirv3-motion.html et celui de header.php. Ils doivent rester en ligne,
puisqu'ils servent justement quand rien d'autre ne se charge.

Conséquence pratique : changer une couleur, une police ou une mesure se fait
à un seul endroit, et les neuf pages suivent. Vérifié en mesurant la fiche
parfum et la page commande dans les deux supports — elles se rendent au pixel
près.


STRUCTURE
=========
comptoir-parfums/
├── style.css                en-tête de thème (exigé par WordPress)
├── style-site.css           LE DESIGN — partagé avec la maquette
├── theme.js                 L'INTERFACE — partagée avec la maquette
├── panier.js                panier client + tiroir — partagé
├── commande.js              page commande — partagé
├── produits.php             209 fiches (thème)
├── produits.js              209 fiches (maquette) — identiques
├── functions.php            réglages, routage, scripts, métadonnées
├── header.php               <head>, préchargeur, menu mobile, bandeau
├── front-page.php           accueil — sections 1 à 10
├── template-parfum.php      fiche parfum      (/?parfum=…)
├── template-commander.php   page commande     (/?commander=1)
├── footer.php               pied de page
├── index.php                gabarit de secours (404, recherche, archives)
├── og-image.jpg / .svg      aperçu de partage
├── img/produits/            <slug>.webp — vignettes et fiches
├── img/heros/               <maison>.webp — vitrine détourée
└── tools/
    ├── build-heros.py       génère les visuels détourés de la vitrine
    └── apercu-theme.py      rend le thème en HTML, sans PHP
