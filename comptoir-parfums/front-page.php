<?php
/**
 * Le Comptoir des Parfums — front-page.php (page d'accueil)
 *
 * Sept blocs depuis la v3.6 (il y en avait quinze, extensions comprises) :
 *   1. heros            4. preuves clients
 *   2. ruban des maisons 5. le testeur explique
 *   3. catalogue         6. commander : etapes, appel final, garanties
 *                        7. questions frequentes
 * Comptoir Boutique ajoute « Nos duos » juste apres le catalogue, et le quiz
 * en une ligne dans son en-tete. Retires : la selection par famille (le
 * catalogue a les memes filtres), « Notre approche » et « Ce que vous
 * trouverez » (dits par le heros et le bloc testeur), les chiffres cles
 * (deja dans le heros), les engagements (repris dans « Commander »).
 * Le catalogue est rendu cote serveur, depuis produits.php : il est
 * indexable, lisible sans JavaScript, et aucun chiffre affiche ne peut
 * mentir. Le script du pied de page se contente de le cabler.
 */

get_header();

$cp_wa = 'https://wa.me/' . comptoir_wa_numero();

// Les compteurs affiches a quatre endroits de la page. Rendus ici, pas
// ecrits en dur : ajouter ou retirer un parfum ne peut plus laisser un
// chiffre faux a l'ecran, meme si le JavaScript ne charge pas.
$cp_nb_refs    = count( comptoir_produits() );
$cp_nb_maisons = count( comptoir_catalogue_par_maison() );
?>
<main id="cp-main">

<section id="hero" aria-label="Présentation">

  <?php /* Eclairage fixe : grain, ligne de balayage, noms de maisons en
       filigrane et anneaux en orbite sont retires en v3.6 */ ?>
  <div class="hero-canvas" aria-hidden="true">
    <div class="hc-light-key"></div>
    <div class="hc-light-fill"></div>
    <div class="hc-light-rim"></div>
    <div class="hc-vignette"></div>
  </div>

  <?php /* Split grid */ ?>
  <div class="hero-inner">

    <?php /* LEFT: TEXT */ ?>
    <div class="hero-text">

      <div class="h-eyebrow" id="he">
        <div class="h-eye-line" aria-hidden="true"></div>
        <span class="h-eye-text">Maroc · Livraison 24h · Paiement à réception</span>
      </div>

      <?php /* Accroche : line-by-line clip reveal. Un paragraphe depuis la 3.8 :
           le titre de la page pour Google est la pastille ci-dessous, qui dit
           ce que l'on vend. Meme classe, donc meme rendu. */ ?>
      <p class="hero-h1" id="hh">
        <span class="h1-line"><span class="h1-inner" data-delay="280">Le parfum</span></span>
        <span class="h1-line"><span class="h1-inner" data-delay="420">que vous voulez.</span></span>
        <span class="h1-line"><span class="h1-inner" data-delay="560"><em>Au prix qui reste.</em></span></span>
      </p>

      <?php /* Value badge = H1 de la page (3.8) : « testeurs de parfum originaux
           au Maroc », la recherche que la boutique veut gagner. */ ?>
      <div class="h-badge" id="hbadge">
        <div class="h-badge-dot" aria-hidden="true"></div>
        <h1 class="h-badge-text">Testeurs de parfum originaux au Maroc</h1>
      </div>

      <p class="hero-sub" id="hs">
        Les grands parfums de Chanel, Dior, Tom Ford, Xerjoff — livrés chez vous en 24 à 72h, partout au Maroc. Vous payez à la réception. Pas avant.
      </p>

      <div class="hero-actions" id="ha">
        <a href="#catalogue" class="btn-a" data-cursor-label="Voir le catalogue">Voir le catalogue</a>
        <a href="#commander" class="btn-b">Comment commander</a>
      </div>

      <?php comptoir_gages( 'gages-hero' ); ?>

      <?php /* Trust strip */ ?>
      <div class="hero-trust" id="ht" role="list">
        <div class="ht-item" role="listitem">
          <strong data-refs-total><?php echo (int) $cp_nb_refs; ?></strong>
          <span>références</span>
        </div>
        <div class="ht-div" aria-hidden="true"></div>
        <div class="ht-item" role="listitem">
          <strong data-maisons-total><?php echo (int) $cp_nb_maisons; ?></strong>
          <span>maisons</span>
        </div>
        <div class="ht-div" aria-hidden="true"></div>
        <div class="ht-item" role="listitem">
          <strong>0 DH</strong>
          <span>avant livraison</span>
        </div>
      </div>

    </div><?php /* /hero-text */ ?>

    <?php /* RIGHT: VISUAL STAGE */ ?>
    <div class="hero-visual" id="hv" aria-hidden="true">

      <?php /* Atmosphere glows */ ?>
      <div class="f-glow-outer"></div>
      <div class="f-glow-inner"></div>

      <?php /* Logo 3D — flacon-sceau « CP » (WebGL, cf. bloc script en bas de page) */ ?>
      <div class="flacon-stage" id="flacon-stage">
        <div class="flacon3d" id="flacon3d">
          <svg class="flacon3d-fallback" viewBox="0 0 96 96" aria-hidden="true"><use href="#cp-seal-full"/></svg>
        </div>
        <?php /* Aperçu : vraie photo produit de la maison survolée/centrée dans le ruban */ ?>
        <div class="hero-shot" id="hero-shot" aria-hidden="true">
          <span class="hero-shot-floor"></span>
          <img alt="" decoding="async" loading="lazy" width="600" height="600">
        </div>
      </div><?php /* /flacon-stage */ ?>

      <?php /* VITRINE — anneau de flacons (WebGL). Occupe toute la largeur du héros,
           pas la scène étroite du flacon : une vitrine se regarde en largeur. */ ?>
      <div class="vitrine" id="vitrine" aria-hidden="true">
        <div class="vitrine-cap">
          <span class="vitrine-maison"></span>
          <span class="vitrine-hint">Glissez pour tourner la vitrine</span>
        </div>
      </div>

      <?php /* Reflection at base */ ?>
      <div class="f-reflect"></div>
      <div class="f-shadow"></div>

      <?php /* Floating note cards — nom et prix lus dans produits.php, comme
           partout ailleurs : un prix ecrit en dur finit par mentir. Une
           reference retiree du catalogue fait simplement disparaitre sa carte. */ ?>
      <?php
      $cp_cartes = array( 'hnc1' => 'dior--sauvage-elixir', 'hnc2' => 'chanel--coco-mademoiselle' );
      $cp_n      = 0;
      foreach ( $cp_cartes as $cp_id => $cp_slug ) :
      	$cp_n++;
      	$cp_p = comptoir_produit_by_slug( $cp_slug );
      	if ( ! $cp_p ) {
      		continue;
      	}
      	?>
      <div class="h-note-card h-nc-<?php echo (int) $cp_n; ?>" id="<?php echo esc_attr( $cp_id ); ?>">
        <div class="h-note-card-brand"><?php echo esc_html( $cp_p['b'] ); ?></div>
        <div class="h-note-card-name"><?php echo esc_html( $cp_p['n'] ); ?></div>
        <div class="h-note-card-price"><?php echo esc_html( $cp_p['pr'] ); ?></div>
      </div>
      <?php endforeach; ?>

    </div><?php /* /hero-visual */ ?>

  </div><?php /* /hero-inner */ ?>

  <?php /* Gradient edge into next section */ ?>
  <div class="hero-edge" aria-hidden="true"></div>

</section>

<?php /* ════════════════════════════════════════
     2 · RUBAN DES MAISONS
════════════════════════════════════════ */ ?>
<nav class="marquee-sect" aria-label="Maisons représentées — raccourci vers le catalogue">
  <div class="marquee-track" id="mq-track"></div>
</nav>

<?php /* ════════════════════════════════════════
     3 · CATALOGUE
════════════════════════════════════════ */ ?>
<section class="catalogue" id="catalogue" aria-label="Catalogue complet">
  <div class="cat-inner">
    <div class="r">
      <div class="sect-kicker"><span>Catalogue complet</span></div>
      <h2 class="sect-h2"><span data-refs-total><?php echo (int) $cp_nb_refs; ?></span> parfums · <span data-maisons-total><?php echo (int) $cp_nb_maisons; ?></span> maisons.</h2>
      <p class="sect-body">Filtrez par maison, par destinataire ou par famille olfactive. Chaque flacon a sa fiche : description, pyramide olfactive, prix.</p>
      <?php comptoir_note_franco(); ?>
    </div>
    <?php
    /* Entrée rapide avant les filtres fins : un clic sur un genre plutôt que
       d'ouvrir le menu déroulant. data-genre-tile est lu par theme.js, qui
       pilote le même filtre que le select #cat-genre — aucune logique de
       filtrage dupliquée. */
    ?>
    <div class="cat-quick" role="list">
      <a class="cat-quick-item" href="#catalogue" data-genre-tile="Femme" role="listitem">
        <span class="cat-quick-count"><?php echo (int) comptoir_compte_genre( 'Femme' ); ?></span>
        <span class="cat-quick-label">Femme</span>
      </a>
      <a class="cat-quick-item" href="#catalogue" data-genre-tile="Homme" role="listitem">
        <span class="cat-quick-count"><?php echo (int) comptoir_compte_genre( 'Homme' ); ?></span>
        <span class="cat-quick-label">Homme</span>
      </a>
      <a class="cat-quick-item" href="#catalogue" data-genre-tile="Mixte" role="listitem">
        <span class="cat-quick-count"><?php echo (int) comptoir_compte_genre( 'Mixte' ); ?></span>
        <span class="cat-quick-label">Mixte</span>
      </a>
      <a class="cat-quick-item cat-quick-item-all" href="#catalogue" data-genre-tile="" role="listitem">
        <span class="cat-quick-count"><?php echo (int) $cp_nb_refs; ?></span>
        <span class="cat-quick-label">Tout le catalogue</span>
      </a>
    </div>
    <div class="cat-filtres" role="search">
      <p class="cat-champ cat-champ-q">
        <label for="cat-search">Rechercher</label>
        <input type="search" id="cat-search" autocomplete="off" placeholder="Un parfum, une maison…">
      </p>
      <?php /* Telephone (3.9) : les quatre listes se replient derriere ce
               bouton, la recherche reste visible. Cache sur ordinateur. */ ?>
      <button type="button" class="cat-filtres-btn" id="cat-filtres-btn" aria-expanded="false">Filtrer et trier <span class="cat-filtres-nb" id="cat-filtres-nb"></span></button>
      <p class="cat-champ">
        <label for="cat-maison">Maison</label>
        <select id="cat-maison"><option value="">Toutes les maisons</option>
<?php foreach ( comptoir_catalogue_par_maison() as $cp_m => $cp_l ) : ?>
          <option value="<?php echo esc_attr( comptoir_maison_slug( $cp_m ) ); ?>"><?php echo esc_html( $cp_m ); ?> (<?php echo (int) count( $cp_l ); ?>)</option>
<?php endforeach; ?>
        </select>
      </p>
      <p class="cat-champ">
        <label for="cat-genre">Pour</label>
        <select id="cat-genre"><option value="">Tous</option>
<?php foreach ( comptoir_genres() as $cp_g ) : ?>
          <option value="<?php echo esc_attr( $cp_g ); ?>"><?php echo esc_html( $cp_g ); ?></option>
<?php endforeach; ?>
        </select>
      </p>
      <p class="cat-champ">
        <label for="cat-famille">Famille</label>
        <select id="cat-famille"><option value="">Toutes les familles</option>
<?php foreach ( comptoir_familles() as $cp_f ) : ?>
          <option value="<?php echo esc_attr( $cp_f ); ?>"><?php echo esc_html( $cp_f ); ?></option>
<?php endforeach; ?>
        </select>
      </p>
      <p class="cat-champ">
        <label for="cat-tri">Trier par</label>
        <select id="cat-tri">
          <option value="defaut">Maison</option>
          <option value="prix-asc">Prix croissant</option>
          <option value="prix-desc">Prix décroissant</option>
          <option value="nom">Nom du parfum</option>
        </select>
      </p>
    </div>

    <div class="cat-barre">
      <span class="cat-count" id="cat-count" aria-live="polite" aria-atomic="true"><?php echo (int) $cp_nb_refs; ?> parfums</span>
      <button type="button" class="cat-reset" id="cat-reset" hidden>Tout afficher</button>
    </div>

    <?php /* 24 cartes dans la page au lieu de 209 : l'accueil pesait 550 Ko
             de HTML et plus de 3 000 elements a analyser sur un telephone
             d'entree de gamme. theme.js construit les autres (data-partiel)
             au premier filtre, tri, recherche ou « Voir plus », sinon quand
             le navigateur est libre. Chaque fiche reste dans le plan du site.
             data-vedettes : la selection du moment (ses trois blocs), premier
             rayon du catalogue sur telephone (3.9). */
    $cp_vedettes = array();
    foreach ( comptoir_selection() as $cp_bloc ) {
    	foreach ( $cp_bloc['parfums'] as $cp_p ) {
    		$cp_vedettes[] = $cp_p['s'];
    	}
    }
    ?>
    <div class="cat-grille" id="cat-list" role="list" data-partiel="24" data-vedettes="<?php echo esc_attr( implode( ',', $cp_vedettes ) ); ?>">
<?php comptoir_rendu_catalogue( 24 ); ?>
    </div>
    <p class="cat-vide" id="cat-vide" hidden>Aucun parfum ne correspond. Essayez le nom de la maison, ou <button type="button" class="cat-vide-reset">affichez tout le catalogue</button>.</p>
  </div>
</section>

<?php /* Preuves clients juste apres le catalogue et les duos : le visiteur
         vient de voir les flacons, il voit ensuite qu'ils arrivent vraiment. */ ?>
<div class="preuves-home"><?php comptoir_bloc_preuves(); ?></div>

<div class="testeur-home"><?php comptoir_bloc_testeur(); ?></div>

<?php /* ════════════════════════════════════════
     6 · COMMANDER — les etapes, puis l'appel final.
     v3.6 : reunit « Comment commander », « Nos engagements » et
     « Commander maintenant », qui disaient trois fois la meme chose
     (paiement a la livraison, partout au Maroc, satisfait ou rembourse :
     c'est dans les etapes 03 et 04).
════════════════════════════════════════ */ ?>
<section class="commande-home" id="commander" aria-label="Commander">
  <div class="proc-inner">
    <div class="r">
      <div class="sect-kicker"><span>Comment commander</span></div>
      <h2 class="sect-h2">Commandez en 30 secondes, payez à la livraison.</h2>
    </div>
    <div class="proc-steps">
      <div class="proc-step r d1">
        <div class="proc-icon"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
        <div class="proc-num" aria-hidden="true" data-n="01"></div>
        <h3>Choisissez votre parfum</h3>
        <p>Parcourez le catalogue ou faites le quiz en 4 questions. Vous hésitez ? Dites-nous sur WhatsApp ce que vous portez déjà, nous vous conseillons.</p>
      </div>
      <div class="proc-step r d2">
        <div class="proc-icon"><svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        <div class="proc-num" aria-hidden="true" data-n="02"></div>
        <h3>Envoyez votre commande</h3>
        <p>Laissez votre nom, votre téléphone et votre adresse. Rien à payer en ligne : nous vous appelons pour confirmer la commande.</p>
      </div>
      <div class="proc-step r d3">
        <div class="proc-icon"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
        <div class="proc-num" aria-hidden="true" data-n="03"></div>
        <h3>Livraison 24–72h</h3>
        <p>Casablanca, Rabat, Marrakech, Tanger et toutes les villes. Transporteur partenaire suivi.</p>
      </div>
      <div class="proc-step r d4">
        <div class="proc-icon"><svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
        <div class="proc-num" aria-hidden="true" data-n="04"></div>
        <h3>Cash à réception</h3>
        <p>Vous payez le livreur en main propre. Si le colis ne convient pas, vous le refusez — et si vous changez d'avis ensuite, vous êtes remboursé.</p>
      </div>
    </div>
  </div>
  <div class="fin-inner r">
    <h3 class="fin-h2">Un grand parfum livré chez vous demain.</h3>
    <p class="fin-body">Choisissez vos flacons, laissez votre nom et votre téléphone — et payez seulement quand le colis est entre vos mains, en 24 à 72h.</p>
    <div class="fin-actions">
      <a href="<?php echo esc_url( home_url( '/?commander=1' ) ); ?>" class="btn-fin">Commander maintenant</a>
      <a href="#catalogue" class="btn-fin-g">Parcourir le catalogue</a>
    </div>
    <?php /* Un seul chemin pour commander : le formulaire, qui inscrit la
         commande au carnet et la fait compter par le pixel. WhatsApp sert aux
         questions (3.9.17) : « ou commandez directement sur WhatsApp » ouvrait
         un second chemin, qui faisait hesiter et que Meta ne voyait pas. */ ?>
    <p class="fin-ou">
      <a href="<?php echo esc_url( $cp_wa ); ?>" target="_blank" rel="noopener">
        <?php comptoir_icone_whatsapp(); ?>Un doute avant de commander ? Réponse sur WhatsApp</a>
    </p>
  </div>
</section>

<?php /* ════════════════════════════════════════
     7 · QUESTIONS FREQUENTES
════════════════════════════════════════ */ ?>
<section class="faq" id="faq" aria-label="Questions fréquentes">
  <div class="faq-inner">
    <div class="faq-sidebar r-left">
      <div class="sect-kicker"><span>Questions</span></div>
      <h2 class="sect-h2">Ce qu'on nous demande le plus.</h2>
      <p class="sect-body">Une réponse manque ? Écrivez-nous directement.</p>
      <div class="wa-block">
        <p>Notre équipe répond sur WhatsApp en moins de 2h — conseils, disponibilités, suivi de commande.</p>
        <a href="<?php echo esc_url( $cp_wa ); ?>" class="wa-cta" target="_blank" rel="noopener">Poser une question</a>
      </div>
    </div>
    <div class="faq-list" role="list">
      <div class="faq-item" role="listitem">
        <button class="faq-btn" aria-expanded="false">Qu'est-ce qu'un testeur, exactement ?<span class="faq-ico" aria-hidden="true"></span></button>
        <div class="faq-ans"><p>Un flacon <strong>authentique</strong> de la maison — Chanel, Dior, Tom Ford — qu'elle fabrique pour faire sentir ses parfums en boutique. Même jus, même concentration, même tenue que le flacon du rayon. Vous recevez le flacon plein et neuf, avec son bouchon, dans le même volume qu'en boutique. Ce qui change est l'emballage : une boîte blanche marquée « Tester » au lieu du coffret de luxe. C'est tout, et c'est là qu'est l'écart de prix.</p></div>
      </div>
      <div class="faq-item" role="listitem">
        <button class="faq-btn" aria-expanded="false">Comment savoir que c'est un vrai ?<span class="faq-ico" aria-hidden="true"></span></button>
        <div class="faq-ans"><p>Chaque flacon porte le code de lot de la maison. Tapez-le sur un site de vérification comme <a href="https://www.checkfresh.com/" target="_blank" rel="noopener">CheckFresh</a> : il vous donne sa date de fabrication. Et vous ne payez qu'à la livraison, colis en main.</p></div>
      </div>
      <div class="faq-item" role="listitem">
        <button class="faq-btn" aria-expanded="false">Le parfum est-il identique à celui de la boutique ?<span class="faq-ico" aria-hidden="true"></span></button>
        <div class="faq-ans"><p>Oui. Ce n'est ni une imitation ni une interprétation : c'est le parfum de la maison, issu de la même production. Même concentration, même tenue, même sillage. Nous ne vendons aucun dupe — si un flacon n'est pas un testeur officiel, il n'entre pas au catalogue.</p></div>
      </div>
      <div class="faq-item" role="listitem">
        <button class="faq-btn" aria-expanded="false">Et si le parfum ne me convient pas ?<span class="faq-ico" aria-hidden="true"></span></button>
        <div class="faq-ans"><p>Deux cas. À la porte : vous refusez le colis, il repart avec le livreur, vous ne payez rien. Après l'avoir ouvert : écrivez-nous et nous vous remboursons. Le risque reste de notre côté jusqu'à ce que vous soyez satisfait — c'est ce que veut dire « satisfait ou remboursé ».</p></div>
      </div>
      <div class="faq-item" role="listitem">
        <button class="faq-btn" aria-expanded="false">Comment choisir sans pouvoir sentir le parfum ?<span class="faq-ico" aria-hidden="true"></span></button>
        <div class="faq-ans"><p>Écrivez-nous les parfums que vous portez actuellement ou que vous aimez. Nous vous orientons vers des testeurs dont les notes partagent le même ADN olfactif, en expliquant les différences. Ce service est gratuit et sans engagement.</p></div>
      </div>
      <div class="faq-item" role="listitem">
        <button class="faq-btn" aria-expanded="false">Livrez-vous partout au Maroc ?<span class="faq-ico" aria-hidden="true"></span></button>
        <div class="faq-ans"><p>Oui — de Tanger à Dakhla, toutes les villes et les zones rurales sont livrées en 24 à 72 h par nos transporteurs partenaires.</p></div>
      </div>
      <div class="faq-item" role="listitem">
        <button class="faq-btn" aria-expanded="false">Les prix affichés sont-ils définitifs ?<span class="faq-ico" aria-hidden="true"></span></button>
        <div class="faq-ans"><p>Oui. Le prix affiché est celui que vous payez : pas d'acompte, pas de frais cachés. Seule la livraison s'ajoute, offerte dès le deuxième parfum, et le total exact s'affiche avant l'envoi de votre commande. Vous le réglez au livreur, colis en main.</p></div>
      </div>
    </div>
  </div>
</section>

</main>
<?php get_footer(); ?>
