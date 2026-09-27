<?php
/**
 * Le Comptoir des Parfums — template-parfum.php
 *
 * Fiche parfum. Servie automatiquement par functions.php des qu'une URL porte
 * ?parfum=<slug> : aucune page a creer dans l'admin.
 *
 * Ordre (v3.1) : achat, puis le parfum et sa pyramide, la fiche, les
 * preuves, le testeur explique, la meme maison. La maquette parfum.html
 * garde l'ordre v2.
 *
 * Autre difference avec la maquette : ici tout est rendu par PHP. La
 * maquette construit la fiche en JavaScript, donc son contenu est invisible
 * pour les moteurs de recherche et pour un partage WhatsApp. Sur le site
 * reel, c'est le contraire qui compte : la fiche doit exister dans le HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cp_slug   = comptoir_parfum_slug_demande();
$cp_parfum = $cp_slug ? comptoir_produit_by_slug( $cp_slug ) : null;
$cp_home   = home_url( '/' );

get_header();
?>

<?php /* Le CSS de cette page vit dans style-site.css, charge par
        functions.php : une seule copie pour la maquette et le theme. */ ?>

<?php if ( ! $cp_parfum ) : ?>

<main class="wrap pf-404" id="cp-main">
  <h1>Parfum introuvable</h1>
  <p>Cette référence n'existe pas ou a été retirée.
     <a href="<?php echo esc_url( $cp_home ); ?>#catalogue">Retour au catalogue</a></p>
</main>

<?php else :

	// La photo n'est servie que si le fichier existe : pas de 404, pas de
	// glyphe d'image cassee. Sinon la silhouette doree de .is-empty suffit.
	$cp_a_photo = comptoir_a_photo( $cp_parfum['s'] );
	$cp_img     = comptoir_photo_url( $cp_parfum['s'] );

	// WhatsApp sert aux QUESTIONS, plus a la commande : une commande passee
	// par message n'entrait ni dans le registre ni dans la mesure Meta, qui
	// n'apprenait donc rien des ventes les plus nombreuses.
	$cp_wa_href = 'https://wa.me/' . comptoir_wa_numero() . '?text=' . rawurlencode(
		'Bonjour, j\'ai une question sur ' . $cp_parfum['n'] . ' (' . $cp_parfum['b'] . ').'
	);

	// Autres references de la meme maison (4 au plus).
	$cp_sibs = array();
	foreach ( comptoir_produits() as $cp_p ) {
		if ( $cp_p['b'] === $cp_parfum['b'] && $cp_p['s'] !== $cp_parfum['s'] ) {
			$cp_sibs[] = $cp_p;
		}
	}
	$cp_sibs = array_slice( $cp_sibs, 0, 4 );
	?>

<main class="pf wrap" id="cp-main">

  <nav class="crumb" aria-label="Fil d'Ariane">
    <a href="<?php echo esc_url( $cp_home ); ?>">Accueil</a> ·
    <a href="<?php echo esc_url( $cp_home ); ?>#catalogue">Le catalogue</a> ·
    <span><?php echo esc_html( $cp_parfum['n'] ); ?></span>
  </nav>

  <div class="pf-top">
    <div class="pf-visual<?php echo $cp_a_photo ? '' : ' is-empty'; ?>">
		<?php if ( $cp_a_photo ) : ?>
      <img src="<?php echo esc_url( $cp_img ); ?>" alt="<?php echo esc_attr( $cp_parfum['b'] . ' ' . $cp_parfum['n'] ); ?>" width="600" height="600" decoding="async">
		<?php endif; ?>
    </div>

    <div class="pf-buy">
      <span class="pf-badge"><?php echo esc_html( comptoir_badge( $cp_parfum ) ); ?></span>
      <div class="pf-brand"><?php echo esc_html( $cp_parfum['b'] ); ?></div>
      <h1 class="pf-name"><?php echo esc_html( $cp_parfum['n'] ); ?></h1>
      <div class="pf-meta"><?php echo esc_html( $cp_parfum['x'] . ' · ' . $cp_parfum['g'] ); ?></div>
      <?php /* Les notes, dites tout de suite : c'est ce qu'on cherche en
               ouvrant une fiche. La pyramide complete suit sous le bloc. */ ?>
      <?php if ( comptoir_notes_courtes( $cp_parfum ) ) : ?>
      <a class="pf-notes-cle" href="#pf-histoire"><span class="pf-notes-lib">Notes</span><span><?php echo esc_html( comptoir_notes_courtes( $cp_parfum ) ); ?></span></a>
      <?php endif; ?>
      <div class="pf-price">
        <span class="now"><?php echo esc_html( $cp_parfum['pr'] ); ?></span>
        <span class="pf-price-note">Testeur original · 100 % authentique</span>
      </div>

      <?php /* UN chemin principal : le flacon entre au panier et le client part
               droit au formulaire (Panier.acheter). « Ajouter au panier » reste
               en second, pour qui veut un deuxieme flacon. */ ?>
      <div class="pf-actions">
        <button class="pf-cta pf-cta-achat" type="button" data-panier-acheter="<?php echo esc_attr( $cp_parfum['s'] ); ?>">
          <span>Commander</span><span class="pf-cta-prix"><?php echo esc_html( $cp_parfum['pr'] ); ?></span>
        </button>
        <button class="pf-cta pf-cta-2" type="button" data-panier-add="<?php echo esc_attr( $cp_parfum['s'] ); ?>">Ajouter au panier</button>
      </div>

      <p class="pf-rassure-cta">Rien à payer maintenant · réglez en espèces à la livraison</p>

      <?php comptoir_note_franco( 'franco-pf' ); ?>

      <?php comptoir_gages(); ?>

      <?php /* Ce que le client recoit, dit AVANT qu'il se le demande : c'est
               la question qui retient un achat de testeur. Faits confirmes par
               la boutique ; l'explication complete est plus bas (#testeur). */ ?>
      <div class="pf-recu">
        <div class="pf-recu-titre">Ce que vous recevez</div>
        <ul class="pf-reassure">
          <li>Le flacon original, plein et neuf, avec son bouchon</li>
          <li>Le même volume que le flacon du rayon</li>
          <li>Une boîte blanche marquée « Tester », au lieu du coffret</li>
          <li>Le code de lot de la maison, vérifiable en ligne</li>
        </ul>
        <a class="pf-recu-lien" href="#testeur">Pourquoi un testeur coûte moins cher</a>
      </div>

      <a class="pf-question" href="<?php echo esc_url( $cp_wa_href ); ?>" target="_blank" rel="noopener">
        <?php comptoir_icone_whatsapp(); ?>
        <span>Une question sur ce parfum ? Écrivez-nous</span>
      </a>
    </div>
  </div>

  <?php /* Le parfum d'abord : son histoire et sa pyramide sont ce qui fait
           rever, elles passent avant les preuves et l'explication du
           testeur, qui rassurent ensuite. */ ?>
  <section class="pf-histoire" id="pf-histoire">
    <div class="pf-histoire-recit">
      <h2 class="pf-kicker">Le parfum</h2>
      <p class="pf-desc"><?php echo esc_html( $cp_parfum['d'] ); ?></p>
      <p class="pf-signature"><span class="pf-signature-maison"><?php echo esc_html( $cp_parfum['b'] ); ?></span> <span aria-hidden="true">—</span> <?php echo esc_html( $cp_parfum['fam'] ); ?></p>
    </div>

    <div class="pf-histoire-pyr">
      <h2 class="pf-kicker">La pyramide olfactive</h2>
      <ol class="pyr2">
		<?php
		$cp_etages = array(
			array( 'Tête', 'Les premières minutes', $cp_parfum['t'] ),
			array( 'Cœur', 'Après une heure', $cp_parfum['c'] ),
			array( 'Fond', 'Des heures durant', $cp_parfum['f'] ),
		);
		foreach ( $cp_etages as $cp_e ) :
			?>
        <li class="pyr2-etage">
          <div class="pyr2-tete"><span class="pyr2-nom"><?php echo esc_html( $cp_e[0] ); ?></span><span class="pyr2-quand"><?php echo esc_html( $cp_e[1] ); ?></span></div>
          <ul class="pyr2-notes">
			<?php foreach ( (array) $cp_e[2] as $cp_n ) : ?>
            <li><?php echo esc_html( $cp_n ); ?></li>
			<?php endforeach; ?>
          </ul>
        </li>
		<?php endforeach; ?>
      </ol>
    </div>
  </section>

  <section class="pf-block">
    <h2>La fiche</h2>
    <dl class="fiche">
      <div><dt>Maison</dt><dd><?php echo esc_html( $cp_parfum['b'] ); ?></dd></div>
      <div><dt>Famille olfactive</dt><dd><?php echo esc_html( $cp_parfum['fam'] ); ?></dd></div>
      <div><dt>Concentration</dt><dd><?php echo esc_html( $cp_parfum['x'] ); ?></dd></div>
      <div><dt>Genre</dt><dd><?php echo esc_html( $cp_parfum['g'] ); ?></dd></div>
      <div><dt>Présentation</dt><dd><?php echo esc_html( comptoir_presentation( $cp_parfum ) ); ?></dd></div>
      <div><dt>Prix</dt><dd><?php echo esc_html( $cp_parfum['pr'] ); ?> — paiement à la réception</dd></div>
    </dl>
  </section>

  <?php comptoir_bloc_preuves( 'preuves-pf' ); ?>

  <?php comptoir_bloc_testeur( 'testeur-pf' ); ?>

	<?php if ( $cp_sibs ) : ?>
  <section class="pf-block pf-block-sibs">
    <div class="pf-block-tete">
      <h2>Dans la même maison</h2>
      <a class="pf-all" href="<?php echo esc_url( $cp_home ); ?>#catalogue">Voir tout le catalogue</a>
    </div>
    <div class="pf-sibs">
		<?php foreach ( $cp_sibs as $cp_p ) : ?>
      <a class="pf-sib" href="<?php echo esc_url( comptoir_parfum_url( $cp_p['s'] ) ); ?>">
        <span class="pf-sib-photo"><?php echo comptoir_vignette_img( $cp_p, 'pf-sib-img', 400, 300 ); ?></span>
        <div class="pf-sib-brand"><?php echo esc_html( $cp_p['b'] ); ?></div>
        <div class="pf-sib-name"><?php echo esc_html( $cp_p['n'] ); ?></div>
        <div class="pf-sib-price"><?php echo esc_html( $cp_p['pr'] ); ?></div>
      </a>
		<?php endforeach; ?>
    </div>
  </section>
	<?php endif; ?>

</main>

<?php endif; ?>

<?php get_footer(); ?>
