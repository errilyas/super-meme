<?php
/**
 * Le Comptoir des Parfums — front-page.php (page d'accueil)
 *
 * Portage des sections 1 a 10 de la maquette comptoirv3-motion.html.
 * Deux blocs ne sont PAS recopies mais generes depuis produits.php, pour
 * qu'aucun chiffre affiche ne puisse mentir :
 *   - « Selection du moment » (les 9 fiches mises en avant) ;
 *   - le catalogue complet (177 references / 37 maisons).
 * Le catalogue est rendu cote serveur : il est indexable et reste lisible
 * sans JavaScript. Le script du pied de page detecte le balisage deja
 * present et se contente de le cabler (accordeon, recherche, filtre maison).
 */

get_header();

$cp_wa = 'https://wa.me/' . comptoir_wa_numero();

// Les compteurs affiches a quatre endroits de la page. Rendus ici, pas
// ecrits en dur : ajouter ou retirer un parfum ne peut plus laisser un
// chiffre faux a l'ecran, meme si le JavaScript ne charge pas.
$cp_nb_refs         = count( comptoir_produits() );
$cp_nb_maisons      = count( comptoir_catalogue_par_maison() );
list( $cp_prix_min, $cp_prix_max ) = comptoir_bornes_prix();
?>
<main id="cp-main">

<section id="hero" aria-label="Présentation">

  <!-- Atmospheric canvas -->
  <div class="hero-canvas" aria-hidden="true">
    <div class="hc-light-key"></div>
    <div class="hc-light-fill"></div>
    <div class="hc-light-rim"></div>
    <div class="hc-vignette"></div>
    <div class="hc-grain"></div>
    <div class="hc-scan"></div>
    <!-- Ghost brand typography in background -->
    <div class="h-brand-float" aria-hidden="true" style="font-size:clamp(60px,8vw,120px);bottom:8%;left:-2%;opacity:1">Chanel</div>
    <div class="h-brand-float" aria-hidden="true" style="font-size:clamp(40px,5vw,80px);top:12%;right:5%;opacity:1">Dior</div>
    <div class="h-brand-float" aria-hidden="true" style="font-size:clamp(30px,3.5vw,56px);top:55%;right:2%;opacity:1">Xerjoff</div>
  </div>

  <!-- Split grid -->
  <div class="hero-inner">

    <!-- LEFT: TEXT -->
    <div class="hero-text">

      <div class="h-eyebrow" id="he">
        <div class="h-eye-line" aria-hidden="true"></div>
        <span class="h-eye-text">Maroc · Livraison 24h · Paiement à réception</span>
      </div>

      <!-- H1: line-by-line clip reveal -->
      <h1 class="hero-h1" id="hh" aria-label="Le parfum que vous voulez. Au prix qui reste.">
        <span class="h1-line"><span class="h1-inner" data-delay="280">Le parfum</span></span>
        <span class="h1-line"><span class="h1-inner" data-delay="420">que vous voulez.</span></span>
        <span class="h1-line"><span class="h1-inner" data-delay="560"><em>Au prix qui reste.</em></span></span>
      </h1>

      <!-- Value badge -->
      <div class="h-badge" id="hbadge">
        <div class="h-badge-dot" aria-hidden="true"></div>
        <span class="h-badge-text">Testeurs originaux des grandes maisons</span>
      </div>

      <p class="hero-sub" id="hs">
        Les grands parfums de Chanel, Dior, Tom Ford, Xerjoff — livrés chez vous en 24 à 72h, partout au Maroc. Vous payez à la réception. Pas avant.
      </p>

      <div class="hero-actions" id="ha">
        <a href="#catalogue" class="btn-a" data-cursor-label="Voir le catalogue">Voir le catalogue</a>
        <a href="#process" class="btn-b">Comment commander</a>
      </div>

      <?php comptoir_gages( 'gages-hero' ); ?>

      <!-- Trust strip -->
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

    </div><!-- /hero-text -->

    <!-- RIGHT: VISUAL STAGE -->
    <div class="hero-visual" id="hv" aria-hidden="true">

      <!-- Atmosphere glows -->
      <div class="f-glow-outer"></div>
      <div class="f-glow-inner"></div>

      <!-- Orbit rings -->
      <div class="f-orbit f-orbit-1"></div>
      <div class="f-orbit f-orbit-2"></div>
      <div class="f-orbit f-orbit-3"></div>

      <!-- Logo 3D — flacon-sceau « CP » (WebGL, cf. bloc script en bas de page) -->
      <div class="flacon-stage" id="flacon-stage">
        <div class="flacon3d" id="flacon3d">
          <svg class="flacon3d-fallback" viewBox="0 0 96 96" aria-hidden="true"><use href="#cp-seal"/></svg>
        </div>
        <!-- Aperçu : vraie photo produit de la maison survolée/centrée dans le ruban -->
        <div class="hero-shot" id="hero-shot" aria-hidden="true">
          <span class="hero-shot-floor"></span>
          <img alt="" decoding="async" loading="lazy" width="600" height="600">
        </div>
      </div><!-- /flacon-stage -->

      <!-- VITRINE — anneau de flacons (WebGL). Occupe toute la largeur du héros,
           pas la scène étroite du flacon : une vitrine se regarde en largeur. -->
      <div class="vitrine" id="vitrine" aria-hidden="true">
        <div class="vitrine-cap">
          <span class="vitrine-maison"></span>
          <span class="vitrine-hint">Glissez pour tourner la vitrine</span>
        </div>
      </div>

      <!-- Reflection at base -->
      <div class="f-reflect"></div>
      <div class="f-shadow"></div>

      <!-- Floating note cards — nom et prix lus dans produits.php, comme
           partout ailleurs : un prix ecrit en dur finit par mentir. Une
           reference retiree du catalogue fait simplement disparaitre sa carte. -->
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

      <!-- Particles container -->
      <div class="f-particles" id="f-particles" aria-hidden="true"></div>

    </div><!-- /hero-visual -->

  </div><!-- /hero-inner -->

  <!-- Gradient edge into next section -->
  <div class="hero-edge" aria-hidden="true"></div>

  <!-- Scroll indicator -->
  <div class="scroll-hint" aria-hidden="true">
    <div class="scroll-hint-line"></div>
    <span class="scroll-hint-text">Défiler</span>
  </div>

</section>

<!-- ════════════════════════════════════════
     SECTION 2 · MARQUEE
════════════════════════════════════════ -->
<nav class="marquee-sect" aria-label="Maisons représentées — raccourci vers le catalogue">
  <div class="marquee-track" id="mq-track"></div>
</nav>

<!-- ════════════════════════════════════════
     SECTION 3 · SCENT FAMILIES
════════════════════════════════════════ -->
<section class="scents" id="scents" aria-label="Sélection par famille olfactive">
  <div class="scents-inner">
    <div class="r">
      <div class="sect-kicker"><span>Sélection du moment</span></div>
      <h2 class="sect-h2">Parcourez par univers olfactif.</h2>
    </div>
    <div class="scent-tabs" role="tablist" aria-label="Familles olfactives">
      <button class="stab active" role="tab" aria-selected="true" aria-controls="sp-homme" data-tab="homme">Hommes</button>
      <button class="stab" role="tab" aria-selected="false" aria-controls="sp-femme" data-tab="femme">Femmes</button>
      <button class="stab" role="tab" aria-selected="false" aria-controls="sp-niche" data-tab="niche">Niche</button>
    </div>
    <div class="scent-panels">
<?php foreach ( comptoir_selection() as $cp_key => $cp_bloc ) : ?>
      <div class="scent-panel<?php echo $cp_bloc['actif'] ? ' active' : ''; ?>" id="sp-<?php echo esc_attr( $cp_key ); ?>" role="tabpanel">
        <?php foreach ( $cp_bloc['parfums'] as $p ) : ?>
        <a href="<?php echo esc_url( comptoir_parfum_url( $p['s'] ) ); ?>" class="scent-card">
          <span class="scent-card-photo"><?php echo comptoir_vignette_img( $p, 'scent-card-img', 400, 300 ); ?></span>
          <div class="scent-card-brand"><?php echo esc_html( $p['b'] ); ?></div>
          <div class="scent-card-name"><?php echo esc_html( $p['n'] ); ?></div>
          <div class="scent-card-note"><?php echo esc_html( comptoir_notes_courtes( $p ) ); ?></div>
          <div class="scent-card-price"><?php echo esc_html( $p['pr'] ); ?></div>
          <span class="scent-card-wa"><?php comptoir_icone_whatsapp(); ?>Voir la fiche</span>
        </a>
        <?php endforeach; ?>
        <div class="scent-panel-cta"><a href="#catalogue" class="btn-b"><?php echo esc_html( $cp_bloc['cta'] ); ?></a></div>
      </div>
<?php endforeach; ?>
    </div>
  </div>
</section>

<?php /* Preuves clients juste apres la selection : le visiteur vient de voir
         les flacons, il voit ensuite qu'ils arrivent vraiment. */ ?>
<div class="preuves-home"><?php comptoir_bloc_preuves(); ?></div>

<!-- ════════════════════════════════════════
     SECTION 4 · DISTINCTION
════════════════════════════════════════ -->
<section class="distinction" id="distinction" aria-label="Notre approche">
  <div class="dist-inner">
    <div class="dist-header r">
      <div class="sect-kicker"><span>Acheter un parfum au Maroc</span></div>
      <h2 class="sect-h2">Le même parfum.<br>Sans le prix boutique.</h2>
      <p class="sect-body">Une seule chose, et elle est authentique : des testeurs officiels. Le flacon que la maison fabrique pour ses propres comptoirs — même jus, même concentration, même tenue que celui du rayon. Ce que vous ne payez pas, c'est le coffret. Et pas un dirham avant d'avoir le colis en main.</p>
    </div>
    <div class="dist-compare r">
      <div class="dist-col">
        <div class="dist-icon">
          <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        </div>
        <div class="dist-col-tag">Le Comptoir des Parfums</div>
        <div class="dist-col-name">Testeur original</div>
        <ul class="dist-list">
          <li>Le flacon de la maison, le jus exact du rayon</li>
          <li><span data-prix-min><?php echo esc_html( $cp_prix_min ); ?></span> – <span data-prix-max><?php echo esc_html( $cp_prix_max ); ?></span> DH, le prix est sur chaque fiche</li>
          <li>Vous payez le livreur en main propre, en cash</li>
          <li>Livré en 24–72 h, de Tanger à Agadir</li>
          <li>Le colis ne vous convient pas ? Refusé sans frais, ou remboursé</li>
          <li>Un doute avant de commander ? Réponse sur WhatsApp</li>
        </ul>
      </div>
      <div class="dist-col">
        <div class="dist-icon">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
        </div>
        <div class="dist-col-tag">Boutique officielle</div>
        <div class="dist-col-name">Coffret de boutique</div>
        <ul class="dist-list">
          <li>Le même jus, dans un coffret cartonné</li>
          <li>1 200 – 4 000 DH le flacon</li>
          <li>En boutique, dans quelques villes seulement</li>
          <li>En ligne : avance, douane, semaines d'attente</li>
          <li>Retour rarement accepté une fois ouvert</li>
          <li>Les références rares n'arrivent pas jusqu'ici</li>
        </ul>
      </div>
    </div>
    <div class="dist-cta">
      <p>La vraie question : payez-vous le jus, ou la vitrine ?</p>
      <a href="#catalogue" class="btn-b">Voir les <?php echo (int) $cp_nb_refs; ?> parfums</a>
    </div>
  </div>
</section>

<div class="testeur-home"><?php comptoir_bloc_testeur(); ?></div>

<!-- ════════════════════════════════════════
     SECTION 5 · NOTRE SÉLECTION
════════════════════════════════════════ -->
<section class="pick" aria-label="Ce que vous trouverez">
  <div class="pick-inner">
    <div class="pick-head">
      <div class="sect-kicker"><span>Ce que vous trouverez</span></div>
      <h2 class="sect-h2">Une sélection, pas un catalogue au hasard.</h2>
    </div>
    <div class="pick-grid">
      <div class="pick-item">
        <div class="pick-fig"><?php echo (int) $cp_nb_maisons; ?> maisons</div>
        <h3>Du classique à la niche</h3>
        <p>Chanel, Dior, Tom Ford, Armani — et Kurkdjian, Xerjoff, Nishane, Parfums de Marly, que presque personne n'importe au Maroc.</p>
      </div>
      <div class="pick-item">
        <div class="pick-fig"><?php echo (int) comptoir_compte_genre( 'Femme' ); ?> · <?php echo (int) comptoir_compte_genre( 'Homme' ); ?> · <?php echo (int) comptoir_compte_genre( 'Mixte' ); ?></div>
        <h3>Pour elle, pour lui, mixtes</h3>
        <p>Floral, ambré, boisé, aromatique, chypré : toutes les familles sont là, du frais de bureau au sillage de soirée.</p>
      </div>
      <div class="pick-item">
        <div class="pick-fig"><?php echo esc_html( $cp_prix_min ); ?> – <?php echo esc_html( $cp_prix_max ); ?> DH</div>
        <h3>Le prix du jus</h3>
        <p>Le même parfum qu'un flacon vendu 1 200 à 4 000 DH. Ce que vous ne payez pas, c'est la vitrine.</p>
      </div>
      <div class="pick-item">
        <div class="pick-fig">100 % testeurs</div>
        <h3>Le parfum, pas une approche</h3>
        <p>Chaque flacon sort de la maison qui l'a créé. Pas une imitation, pas une interprétation : le parfum lui-même, dans son flacon de démonstration.</p>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════
     SECTION 6 · CATALOGUE
════════════════════════════════════════ -->
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

    <div class="cat-grille" id="cat-list" role="list">
<?php comptoir_rendu_catalogue(); ?>
    </div>
    <p class="cat-vide" id="cat-vide" hidden>Aucun parfum ne correspond. Essayez le nom de la maison, ou <button type="button" class="cat-vide-reset">affichez tout le catalogue</button>.</p>
  </div>
</section>

<!-- ════════════════════════════════════════
     SECTION 7 · PROCESS
════════════════════════════════════════ -->
<section class="process" id="process" aria-label="Comment commander">
  <div class="proc-inner">
    <div class="r">
      <div class="sect-kicker"><span>Comment commander</span></div>
      <h2 class="sect-h2">Simple comme un message.</h2>
    </div>
    <div class="proc-steps">
      <div class="proc-step r d1">
        <div class="proc-icon"><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
        <div class="proc-num" aria-hidden="true">01</div>
        <h3>Choisissez sur WhatsApp</h3>
        <p>Parcourez le catalogue et écrivez-nous le parfum voulu. Hésitation ? Dites-nous ce que vous portez déjà.</p>
      </div>
      <div class="proc-step r d2">
        <div class="proc-icon"><svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        <div class="proc-num" aria-hidden="true">02</div>
        <h3>Confirmation sous 1h</h3>
        <p>Nous vérifions la disponibilité et confirmons le prix. Aucune avance demandée à cette étape.</p>
      </div>
      <div class="proc-step r d3">
        <div class="proc-icon"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
        <div class="proc-num" aria-hidden="true">03</div>
        <h3>Livraison 24–72h</h3>
        <p>Casablanca, Rabat, Marrakech, Tanger et toutes les villes. Transporteur partenaire suivi.</p>
      </div>
      <div class="proc-step r d4">
        <div class="proc-icon"><svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
        <div class="proc-num" aria-hidden="true">04</div>
        <h3>Cash à réception</h3>
        <p>Vous payez le livreur en main propre. Si le colis ne convient pas, vous le refusez — et si vous changez d'avis ensuite, vous êtes remboursé.</p>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════
     SECTION 8 · TRUST
════════════════════════════════════════ -->
<section class="trust" id="trust" aria-label="Nos engagements">
  <div class="trust-inner">
    <div class="r-left">
      <div class="sect-kicker"><span>Nos engagements</span></div>
      <h2 class="sect-h2">Ce que vous ne remettrez jamais en question.</h2>
      <div class="g-list">
        <div class="g-item">
          <div class="g-num">01</div>
          <div class="g-body"><h4>Cash on delivery, sans exception</h4><p>Aucun virement, aucun acompte. Vous payez uniquement quand le colis est entre vos mains.</p></div>
        </div>
        <div class="g-item">
          <div class="g-num">02</div>
          <div class="g-body"><h4>Satisfait ou remboursé</h4><p>Refusez le colis au livreur, ou changez d'avis après l'avoir ouvert : nous vous remboursons. Sans discussion.</p></div>
        </div>
        <div class="g-item">
          <div class="g-num">03</div>
          <div class="g-body"><h4>Livraison partout au Maroc</h4><p>De Tanger à Dakhla, du centre-ville aux zones rurales — nos transporteurs couvrent tout le Royaume.</p></div>
        </div>
        <div class="g-item">
          <div class="g-num">04</div>
          <div class="g-body"><h4>Conseil olfactif offert</h4><p>Dites-nous ce que vous portez. Nous vous orientons vers le parfum le plus proche de votre sensibilité — gratuitement.</p></div>
        </div>
      </div>
    </div>
    <div class="r-right">
      <div class="g-card">
        <h4>Vous ne savez pas quoi choisir ?</h4>
        <p>Impossible de sentir un parfum à travers un écran. C'est pourquoi nous avons mis en place un conseil par messages : dites-nous vos parfums habituels, vos matières préférées (oud, vanille, agrumes, floral, boisé) et nous vous proposons deux ou trois options adaptées.</p>
        <p>Tous nos flacons sont des <span class="hl">testeurs officiels</span>, donc le jus est celui de la boutique, à l'identique. Notre conseil porte sur le choix du parfum, jamais sur sa fidélité — il n'y a rien à rapprocher.</p>
        <a href="<?php echo esc_url( $cp_wa ); ?>" class="wa-cta" target="_blank" rel="noopener">Écrire sur WhatsApp</a>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════
     SECTION 9 · NUMBERS
════════════════════════════════════════ -->
<div class="numbers" aria-label="Chiffres clés">
  <div class="numbers-inner">
    <div class="nb-item r"><span class="nb-num" data-refs-total><?php echo (int) $cp_nb_refs; ?></span><span class="nb-label">Références en stock</span></div>
    <div class="nb-item r d1"><span class="nb-num" data-maisons-total><?php echo (int) $cp_nb_maisons; ?></span><span class="nb-label">Maisons représentées</span></div>
    <div class="nb-item r d2"><span class="nb-num">24h</span><span class="nb-label">Livraison grandes villes</span></div>
    <div class="nb-item r d3"><span class="nb-num">0 DH</span><span class="nb-label">Avance requise</span></div>
  </div>
</div>

<!-- ════════════════════════════════════════
     SECTION 10 · FAQ
════════════════════════════════════════ -->
<section class="faq" id="faq" aria-label="Questions fréquentes">
  <div class="faq-inner">
    <div class="faq-sidebar r-left">
      <div class="sect-kicker"><span>Questions</span></div>
      <h2 class="sect-h2">Ce qu'on nous demande le plus.</h2>
      <p class="sect-body">Une réponse manque ? Écrivez-nous directement.</p>
      <div class="wa-block">
        <p>Notre équipe répond sur WhatsApp en moins de 2h — conseils, disponibilités, commandes.</p>
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
        <div class="faq-ans"><p>Chaque flacon porte le code de lot de la maison. Tapez-le sur un site de vérification comme CheckFresh : il vous donne sa date de fabrication. Et vous ne payez qu'à la livraison, colis en main.</p></div>
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
        <div class="faq-ans"><p>Oui — de Tanger à Dakhla, en passant par toutes les villes et zones rurales. Casablanca, Rabat, Marrakech et les grandes agglomérations sont livrées en 24 à 48h. Le reste du Royaume en 2 à 4 jours ouvrables via nos transporteurs partenaires.</p></div>
      </div>
      <div class="faq-item" role="listitem">
        <button class="faq-btn" aria-expanded="false">Les prix affichés sont-ils définitifs ?<span class="faq-ico" aria-hidden="true"></span></button>
        <div class="faq-ans"><p>Les prix du catalogue sont indicatifs et régulièrement mis à jour. Le prix ferme est confirmé par message au moment de votre commande, selon disponibilité et volume. Aucune surprise à la livraison.</p></div>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════
     SECTION 11 · FINAL CTA
════════════════════════════════════════ -->
<section class="finale" id="commander" aria-label="Commander">
  <div class="fin-inner r">
    <span class="fin-mark" aria-hidden="true">
      <svg viewBox="0 0 96 96"><use href="#cp-seal"/></svg>
      <span class="fin-mark-serif">Le Comptoir</span>
      <span class="fin-mark-sans">des Parfums</span>
    </span>
    <div class="fin-kicker">Commander maintenant</div>
    <h2 class="fin-h2">Un grand parfum livré chez vous demain.</h2>
    <p class="fin-body">Choisissez vos flacons, laissez votre adresse, confirmez d'un message — et payez seulement quand le colis est entre vos mains, en 24 à 72h.</p>
    <div class="fin-actions">
      <a href="<?php echo esc_url( home_url( '/?commander=1' ) ); ?>" class="btn-fin">Commander maintenant</a>
      <a href="#catalogue" class="btn-fin-g">Parcourir le catalogue</a>
    </div>
    <!-- Le formulaire reste le chemin principal : c'est lui qui inscrit la
         commande au carnet. Mais on ne force personne a le remplir. -->
    <p class="fin-ou">
      <a href="<?php echo esc_url( $cp_wa ); ?>" target="_blank" rel="noopener">
        <?php comptoir_icone_whatsapp(); ?>ou commandez directement sur WhatsApp</a>
    </p>
    <div class="fin-trust" aria-label="Garanties">
      <span class="fin-trust-item">
        <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Cash à réception
      </span>
      <span class="fin-trust-item">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Livraison 24–72h
      </span>
      <span class="fin-trust-item">
        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        Tout le Maroc
      </span>
      <span class="fin-trust-item">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        Satisfait ou remboursé
      </span>
    </div>
  </div>
</section>

</main>
<?php get_footer(); ?>
