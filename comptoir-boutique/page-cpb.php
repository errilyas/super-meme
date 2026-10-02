<?php
/**
 * Comptoir Boutique — gabarit des pages (A propos, Contact, Conditions de
 * vente, Retours, Confidentialite).
 *
 * Le theme n'a pas de page.php : son index.php affichait une page comme une
 * liste d'articles, titre « Journal » et resume tronque. Ce gabarit garde
 * l'en-tete et le pied du theme et affiche la page en entier.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<main id="cp-main" class="cp-secours cpb-page">
	<?php while ( have_posts() ) : the_post(); ?>
	<article <?php post_class( 'cpb-page-article' ); ?>>
		<h1><?php the_title(); ?></h1>
		<div class="cpb-page-corps">
			<?php the_content(); ?>
		</div>
		<?php if ( get_the_modified_date() ) : ?>
		<p class="cpb-page-maj">Mis à jour le <?php echo esc_html( get_the_modified_date( 'j F Y' ) ); ?></p>
		<?php endif; ?>
	</article>
	<?php endwhile; ?>
</main>
<?php
get_footer();
