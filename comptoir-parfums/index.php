<?php
/**
 * Le Comptoir des Parfums — index.php
 *
 * Gabarit de secours : tout ce qui n'est ni la page d'accueil, ni une fiche
 * parfum, ni la page commande (article, recherche, archive, page 404).
 * Le site n'a pas vocation a en afficher — il est la pour qu'aucune URL ne
 * tombe sur une page nue, et il reprend l'habillage du reste du site.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php /* Le CSS de cette page vit dans style-site.css, charge par
        functions.php : une seule feuille pour tout le site. */ ?>

<main id="cp-main" class="cp-secours">
	<?php if ( have_posts() ) : ?>

		<h1><?php
			if ( is_search() ) {
				/* translators: %s : les mots recherches */
				printf( esc_html__( 'Résultats pour « %s »', 'comptoir-parfums' ), esc_html( get_search_query() ) );
			} elseif ( is_archive() ) {
				echo esc_html( get_the_archive_title() );
			} else {
				echo esc_html__( 'Journal', 'comptoir-parfums' );
			}
		?></h1>

		<?php while ( have_posts() ) : the_post(); ?>
		<article <?php post_class(); ?>>
			<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
			<?php the_excerpt(); ?>
		</article>
		<?php endwhile; ?>

		<?php the_posts_pagination(); ?>

	<?php elseif ( is_search() ) : ?>

		<?php /* Une recherche sans résultat n'est pas une page manquante : le dire
		         autrement évitait d'annoncer à tort « cette page n'existe pas ». */ ?>
		<h1>Aucun résultat pour « <?php echo esc_html( get_search_query() ); ?> ».</h1>
		<p class="cp-intro">Essayez le nom de la maison plutôt que celui du parfum —
		ou parcourez directement le catalogue, il se cherche aussi.</p>

	<?php else : ?>

		<h1>Cette page n'existe pas.</h1>
		<p class="cp-intro">Le lien est peut-être incomplet, ou la page a été retirée.
		Le catalogue, lui, est toujours là&nbsp;: <?php echo (int) count( comptoir_produits() ); ?> parfums,
		<?php echo (int) count( comptoir_catalogue_par_maison() ); ?> maisons, livrés partout au Maroc.</p>

	<?php endif; ?>

	<a class="cp-retour" href="<?php echo esc_url( home_url( '/' ) ); ?>#catalogue">Voir le catalogue &rarr;</a>
</main>

<?php get_footer(); ?>
