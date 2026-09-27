<?php
/**
 * Le Comptoir des Parfums — template-vente.php
 *
 * Landing page dediee au trafic publicitaire (campagne Meta « CP | SALES »),
 * servie sur /?vente=1. Meme mecanique de routage que template-parfum.php et
 * template-commander.php : un query var lu par functions.php, aucune page
 * WordPress a creer ni a perdre en cas de reinstallation du theme.
 *
 * Pourquoi une page a part plutot que d'envoyer les pubs vers l'accueil :
 *  - l'accueil est pense pour la decouverte (vitrine 3D, defile long, menu
 *    complet) — un clic publicitaire coute de l'argent a chaque seconde de
 *    chargement, et cette page saute le WebGL et les animations GSAP les
 *    plus lourdes (header.php les desactive deja hors accueil) ;
 *  - un seul message, un seul chemin : la promesse de la pub (testeur
 *    original, prix, livraison) est la premiere chose lue, sans navigation
 *    pour s'en distraire ;
 *  - indexable=false (comptoir_meta_page) : elle ne doit pas concurrencer
 *    l'accueil dans les resultats de recherche, elle n'existe que pour le
 *    trafic paye.
 *
 * Le contenu reutilise les memes fonctions et le meme texte que l'accueil
 * (comptoir_gages, comptoir_carte_produit, les reponses FAQ) : une promesse
 * qui change de mots d'une page a l'autre ne rassure plus personne.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cp_wa = 'https://wa.me/' . comptoir_wa_numero();
list( $cp_prix_min, $cp_prix_max ) = comptoir_bornes_prix();
$cp_nb_refs    = count( comptoir_produits() );
$cp_nb_maisons = count( comptoir_catalogue_par_maison() );

// Des references reconnaissables, choisies a la main : le catalogue en a 209,
// mais une landing page montre une preuve immediate, pas un choix a faire.
$cp_vitrine_slugs = array(
	'dior--sauvage-eau-de-parfum',
	'chanel--coco-mademoiselle',
	'jean-paul-gaultier--le-male-elixir',
	'maison-francis-kurkdjian--baccarat-rouge-540',
	'yves-saint-laurent--black-opium',
	'versace--eros-edp',
	'emporio-armani--stronger-with-you-absolutely',
	'valentino--uomo-born-in-roma-intense',
);

// Le parfum de la publicite, s'il est donne (?p=slug) : il passe en tete et
// sort de la grille, pour ne pas apparaitre deux fois.
$cp_vedette = comptoir_vente_vedette();
if ( $cp_vedette ) {
	$cp_vitrine_slugs = array_values( array_diff( $cp_vitrine_slugs, array( $cp_vedette['s'] ) ) );
	$cp_vitrine_slugs = array_slice( $cp_vitrine_slugs, 0, 6 );
}

get_header();
?>

<main class="wrap lp" id="cp-main">

  <?php if ( $cp_vedette ) : ?>
  <!-- ════════════════════════════════════════
       VEDETTE — le flacon de la publicite, avec son prix et le bouton,
       dans le premier ecran. Meme bloc d'achat que la fiche parfum.
  ════════════════════════════════════════ -->
  <section class="lp-vedette" aria-label="<?php echo esc_attr( $cp_vedette['b'] . ' ' . $cp_vedette['n'] ); ?>">
    <div class="pf-top">
      <div class="pf-visual<?php echo comptoir_a_photo( $cp_vedette['s'] ) ? '' : ' is-empty'; ?>">
        <?php if ( comptoir_a_photo( $cp_vedette['s'] ) ) : ?>
        <img src="<?php echo esc_url( comptoir_photo_url( $cp_vedette['s'] ) ); ?>" alt="<?php echo esc_attr( $cp_vedette['b'] . ' ' . $cp_vedette['n'] ); ?>" width="600" height="600" fetchpriority="high" decoding="async">
        <?php endif; ?>
      </div>
      <div class="pf-buy">
        <div class="pf-brand"><?php echo esc_html( $cp_vedette['b'] ); ?></div>
        <h1 class="pf-name"><?php echo esc_html( $cp_vedette['n'] ); ?></h1>
        <div class="pf-meta"><?php echo esc_html( $cp_vedette['x'] . ' · ' . $cp_vedette['g'] ); ?></div>
        <a class="pf-notes-cle" href="<?php echo esc_url( comptoir_parfum_url( $cp_vedette['s'] ) ); ?>#pf-histoire"><span class="pf-notes-lib">Notes</span><span><?php echo esc_html( comptoir_notes_courtes( $cp_vedette ) ); ?></span></a>
        <div class="pf-price">
          <span class="now"><?php echo esc_html( $cp_vedette['pr'] ); ?></span>
          <span class="pf-price-note">Testeur original · 100 % authentique</span>
        </div>
        <div class="pf-actions">
          <button class="pf-cta pf-cta-achat" type="button" data-panier-acheter="<?php echo esc_attr( $cp_vedette['s'] ); ?>">
            <span>Commander</span><span class="pf-cta-prix"><?php echo esc_html( $cp_vedette['pr'] ); ?></span>
          </button>
          <button class="pf-cta pf-cta-2" type="button" data-panier-add="<?php echo esc_attr( $cp_vedette['s'] ); ?>">Ajouter au panier</button>
        </div>
        <p class="pf-rassure-cta">Rien à payer maintenant · réglez en espèces à la livraison</p>
        <?php comptoir_note_franco( 'franco-pf' ); ?>
        <?php comptoir_gages(); ?>
        <a class="pf-all" href="<?php echo esc_url( comptoir_parfum_url( $cp_vedette['s'] ) ); ?>">Notes et fiche complète</a>
      </div>
    </div>
  </section>
  <?php else : ?>
  <!-- ════════════════════════════════════════
       HERO — une promesse, un chemin. Raccourci : sur telephone, les
       premiers flacons de la selection doivent apparaitre sans defiler.
       WhatsApp n'y est plus un bouton de commande (voir template-parfum.php).
  ════════════════════════════════════════ -->
  <section class="lp-hero" aria-label="Présentation">
    <div class="lp-hero-inner r">
      <div class="sect-kicker"><span>Testeurs originaux · Livraison partout au Maroc</span></div>
      <h1 class="hero-h1">Le parfum que vous voulez.<br><em>Au prix qui vous convient.</em></h1>
      <p class="hero-sub">Chanel, Dior, Tom Ford — testeurs 100&nbsp;% originaux, le même jus qu'en boutique. Vous payez à la réception, jamais avant.</p>
      <div class="hero-actions">
        <a href="#lp-produits" class="btn-a" data-cursor-label="Voir la sélection">Voir la sélection</a>
      </div>
      <?php comptoir_gages( 'gages-hero' ); ?>
    </div>
    <?php
    /* Trois flacons detoures, en eventail sous la promesse : ce que la pub
       a montre, on le retrouve en arrivant. Visuels de la vitrine de
       l'accueil (img/heros/), deja au format WebP et a fond transparent ;
       une maison dont le visuel manque est simplement omise. */
    $cp_trio = array( 'dior' => 'Dior', 'tom-ford' => 'Tom Ford', 'parfums-de-marly' => 'Parfums de Marly' );
    $cp_trio = array_filter(
    	$cp_trio,
    	static function ( $m ) {
    		return file_exists( get_template_directory() . '/img/heros/' . $m . '.webp' );
    	},
    	ARRAY_FILTER_USE_KEY
    );
    if ( $cp_trio ) :
    	?>
    <div class="lp-trio" aria-hidden="true">
      <?php foreach ( $cp_trio as $cp_m => $cp_nom ) : ?>
      <img src="<?php echo esc_url( get_template_directory_uri() . '/img/heros/' . $cp_m . '.webp' ); ?>" alt="" width="900" height="900" decoding="async">
      <?php endforeach; ?>
      <span class="lp-trio-sol"></span>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <!-- ════════════════════════════════════════
       SÉLECTION — preuve immédiate, quelques flacons connus
  ════════════════════════════════════════ -->
  <section class="pick lp-produits" id="lp-produits" aria-label="Sélection">
    <div class="pick-inner">
      <div class="pick-head r">
        <div class="sect-kicker"><span><?php echo (int) count( $cp_vitrine_slugs ); ?> parmi <?php echo (int) $cp_nb_refs; ?></span></div>
        <h2 class="sect-h2"><?php echo $cp_vedette ? 'Vous aimerez aussi.' : 'Un aperçu du catalogue.'; ?></h2>
        <p class="sect-body"><?php echo (int) $cp_nb_maisons; ?> maisons, de <span data-prix-min><?php echo esc_html( $cp_prix_min ); ?></span> à <span data-prix-max><?php echo esc_html( $cp_prix_max ); ?></span> DH. Chaque flacon a sa fiche complète : pyramide olfactive, prix, disponibilité.</p>
      </div>
      <div class="cat-grille" role="list">
        <?php
        foreach ( $cp_vitrine_slugs as $cp_slug ) {
        	$cp_p = comptoir_produit_by_slug( $cp_slug );
        	if ( $cp_p ) {
        		comptoir_carte_produit( $cp_p );
        	}
        }
        ?>
      </div>
      <div class="lp-cat-cta">
        <a href="<?php echo esc_url( home_url( '/#catalogue' ) ); ?>" class="btn-b">Voir les <?php echo (int) $cp_nb_refs; ?> parfums</a>
      </div>
    </div>
  </section>

  <?php comptoir_bloc_preuves( 'preuves-lp' ); ?>

  <?php comptoir_bloc_testeur( 'testeur-lp' ); ?>

  <!-- ════════════════════════════════════════
       POURQUOI CE PRIX
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
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════
       FAQ — les trois objections qui comptent avant d'acheter
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
          <button class="faq-btn" aria-expanded="false">Et si le parfum ne me convient pas ?<span class="faq-ico" aria-hidden="true"></span></button>
          <div class="faq-ans"><p>Deux cas. À la porte : vous refusez le colis, il repart avec le livreur, vous ne payez rien. Après l'avoir ouvert : écrivez-nous et nous vous remboursons. Le risque reste de notre côté jusqu'à ce que vous soyez satisfait — c'est ce que veut dire « satisfait ou remboursé ».</p></div>
        </div>
        <div class="faq-item" role="listitem">
          <button class="faq-btn" aria-expanded="false">Livrez-vous partout au Maroc ?<span class="faq-ico" aria-hidden="true"></span></button>
          <div class="faq-ans"><p>Oui — de Tanger à Dakhla, en passant par toutes les villes et zones rurales. Casablanca, Rabat, Marrakech et les grandes agglomérations sont livrées en 24 à 48h. Le reste du Royaume en 2 à 4 jours ouvrables via nos transporteurs partenaires.</p></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════
       CTA FINAL
  ════════════════════════════════════════ -->
  <section class="finale" aria-label="Commander">
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
        <a href="<?php echo esc_url( home_url( '/#catalogue' ) ); ?>" class="btn-fin-g">Parcourir le catalogue</a>
      </div>
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
