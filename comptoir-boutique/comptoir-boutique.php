<?php
/**
 * Plugin Name:       Comptoir Boutique
 * Description:       Les outils des grandes boutiques de parfum, branchés sur le thème Le Comptoir des Parfums : bandeau d'annonce, recherche instantanée, quiz « Trouver mon parfum », favoris, parfums du même esprit et parfums vus récemment. Aucune donnée en double : tout est lu dans le catalogue du thème (produits.php).
 * Version:           1.5.0
 * Requires at least: 5.9
 * Requires PHP:      7.0
 * Author:            Le Comptoir des Parfums
 * Text Domain:       comptoir-boutique
 *
 * POURQUOI UNE EXTENSION ET PAS UNE VERSION DU THEME. Le site se met a jour
 * depuis le connecteur WordPress.com, qui televerse des extensions mais pas
 * des themes. Une extension a aussi l'avantage d'etre reversible d'un clic :
 * la desactiver rend le site exactement tel qu'il etait.
 *
 * DEPENDANCE. Tout passe par les fonctions du theme (comptoir_produits,
 * comptoir_selection…) et par son panier (window.Panier). Sans le theme,
 * l'extension ne fait rien : ni erreur, ni bandeau orphelin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CPB_VERSION', '1.5.0' );

/**
 * Mode de diffusion.
 *   'apercu'   : rien ne change pour les visiteurs ; les nouveautes ne
 *                s'affichent qu'avec ?apercu=boutique dans l'adresse.
 *   'en-ligne' : pour tout le monde.
 * On passe d'abord en apercu, on verifie sur le vrai site, puis on bascule.
 */
define( 'CPB_MODE', 'en-ligne' );

/** Vrai si l'extension doit agir sur la page servie. */
function cpb_actif() {
	static $actif = null;
	if ( null !== $actif ) {
		return $actif;
	}
	$actif = false;
	if ( is_admin() || wp_doing_ajax() || ! function_exists( 'comptoir_produits' ) ) {
		return $actif;
	}
	if ( 'en-ligne' === CPB_MODE ) {
		$actif = true;
	} else {
		$actif = isset( $_GET['apercu'] ) && 'boutique' === $_GET['apercu']; // phpcs:ignore WordPress.Security.NonceVerification
	}
	return $actif;
}

/** Les slugs mis en avant par le theme (« Selection du moment »). */
function cpb_populaires() {
	$out = array();
	if ( ! function_exists( 'comptoir_selection' ) ) {
		return $out;
	}
	foreach ( comptoir_selection() as $bloc ) {
		foreach ( $bloc['parfums'] as $p ) {
			$out[] = $p['s'];
		}
	}
	return $out;
}

/* ══════════════════════════════════════════════════════════════
   SCRIPTS ET STYLES
   Charges apres ceux du theme (priorite 20) et dependants d'eux :
   sans le panier du theme, WordPress n'imprime tout simplement pas
   le script de l'extension.
══════════════════════════════════════════════════════════════ */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! cpb_actif() ) {
		return;
	}
	$url = plugin_dir_url( __FILE__ );
	$abs = plugin_dir_path( __FILE__ );

	$deps_css = wp_style_is( 'comptoir-site', 'registered' ) ? array( 'comptoir-site' ) : array();
	wp_enqueue_style( 'comptoir-boutique', $url . 'boutique.css', $deps_css, CPB_VERSION . '.' . (int) @filemtime( $abs . 'boutique.css' ) );

	wp_enqueue_script( 'comptoir-boutique', $url . 'boutique.js', array( 'comptoir-panier' ), CPB_VERSION . '.' . (int) @filemtime( $abs . 'boutique.js' ), true );

	$slug   = function_exists( 'comptoir_parfum_slug_demande' ) ? comptoir_parfum_slug_demande() : '';
	$parfum = ( $slug && function_exists( 'comptoir_produit_by_slug' ) && comptoir_produit_by_slug( $slug ) ) ? $slug : '';

	$cfg = array(
		'slug'       => $parfum,
		'accueil'    => function_exists( 'comptoir_est_accueil' ) ? comptoir_est_accueil() : is_front_page(),
		'home'       => home_url( '/' ),
		'populaires' => cpb_populaires(),
		'visuels'    => cpb_visuels(),
		// En apercu, les liens internes gardent le parametre : sans lui, la page
		// suivante s'ouvrirait sans les nouveautes et le parcours serait coupe.
		'suffixe'    => 'apercu' === CPB_MODE ? 'apercu=boutique' : '',
	);
	wp_add_inline_script( 'comptoir-boutique', 'window.CPB=' . wp_json_encode( $cfg ) . ';', 'before' );
}, 20 );

/**
 * Visuels d'ambiance livres avec l'extension (img/*.webp) : les quatre
 * univers du quiz et la banniere de l'appel au quiz. Seuls ceux presents sur
 * le disque sont annonces, avec une empreinte de version contre le cache.
 */
function cpb_visuels() {
	$out = array();
	$abs = plugin_dir_path( __FILE__ ) . 'img/';
	$url = plugin_dir_url( __FILE__ ) . 'img/';
	foreach ( array( 'frais', 'floral', 'gourmand', 'boise', 'banniere', 'finale', 'distinction' ) as $nom ) {
		if ( file_exists( $abs . $nom . '.webp' ) ) {
			$out[ $nom ] = $url . $nom . '.webp?v=' . (int) filemtime( $abs . $nom . '.webp' );
		}
	}
	return $out;
}

/* ══════════════════════════════════════════════════════════════
   PLAN DU SITE (wp-sitemap.xml)
   Le plan de WordPress ne connait que les articles et les pages : les
   fiches parfum (/?parfum=…), qui sont le catalogue, n'y figuraient pas,
   et Google devait les trouver seul, lien par lien. On les y ajoute, avec
   la meme adresse que la balise canonical du theme.
   Le plan des auteurs est retire : il publiait l'identifiant de connexion
   de l'administrateur (/author/<identifiant>/) et ne sert a rien ici.
══════════════════════════════════════════════════════════════ */
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2 );

add_action( 'init', function () {
	if ( ! function_exists( 'comptoir_produits' ) || ! function_exists( 'wp_register_sitemap_provider' ) || ! class_exists( 'WP_Sitemaps_Provider' ) ) {
		return;
	}
	if ( ! class_exists( 'CPB_Plan_Parfums' ) ) {
		/** Les fiches parfum, une URL par parfum du catalogue du theme. */
		class CPB_Plan_Parfums extends WP_Sitemaps_Provider {
			public function __construct() {
				$this->name        = 'parfums';
				$this->object_type = 'parfums';
			}
			public function get_url_list( $page_num, $object_subtype = '' ) {
				$par_page = wp_sitemaps_get_max_urls( $this->object_type );
				$out      = array();
				foreach ( array_slice( comptoir_produits(), ( $page_num - 1 ) * $par_page, $par_page ) as $p ) {
					$out[] = array( 'loc' => home_url( '/?parfum=' . $p['s'] ) );
				}
				return $out;
			}
			public function get_max_num_pages( $object_subtype = '' ) {
				return (int) ceil( count( comptoir_produits() ) / wp_sitemaps_get_max_urls( $this->object_type ) );
			}
		}
	}
	wp_register_sitemap_provider( 'parfums', new CPB_Plan_Parfums() );
} );

/**
 * Pages d'auteur (/author/…, /?author=1) : vides sur une boutique, et elles
 * donnaient a n'importe qui l'identifiant de connexion de l'administrateur,
 * la moitie de ce qu'il faut pour tenter de deviner un mot de passe.
 * Retour a l'accueil.
 */
add_action( 'template_redirect', function () {
	// Avant redirect_canonical (priorite 10), qui sinon envoie d'abord
	// /?author=1 vers /author/<identifiant>/ et le revele dans l'en-tete.
	if ( is_author() || isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}, 1 );

/**
 * Meme fuite par l'API : /wp-json/wp/v2/users listait l'identifiant a tout
 * visiteur. Les outils connectes (application WordPress, Jetpack) passent
 * authentifies et gardent l'acces.
 */
add_filter( 'rest_endpoints', function ( $routes ) {
	if ( ! is_user_logged_in() ) {
		unset( $routes['/wp/v2/users'], $routes['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $routes;
} );

/** Classe <body> : le CSS du bandeau s'y accroche. */
add_filter( 'body_class', function ( $classes ) {
	if ( cpb_actif() ) {
		$classes[] = 'cpb-a';
	}
	return $classes;
} );

/* ══════════════════════════════════════════════════════════════
   BANDEAU D'ANNONCE
   Rendu par PHP, juste apres <body> : il est la des le premier affichage,
   donc rien ne saute quand le JavaScript arrive. Chaque promesse vient des
   reglages du theme (frais de livraison, livraison offerte) : un chiffre
   change la-bas change ici, aucune promesse n'est ecrite en dur.
   Les deux langues sont dans la page ; le CSS montre celle que la bascule
   du theme a posee sur <html data-lang>, avant le rendu.
══════════════════════════════════════════════════════════════ */

/**
 * La promesse de livraison, calquee sur la regle du panier
 * (Panier.fraisLivraison dans panier.js) : livraison offerte des que le
 * panier compte au moins comptoir_franco_articles() flacons, sinon
 * comptoir_livraison_dh(). Frais a 0 = « a confirmer » cote panier :
 * on ne promet alors rien.
 *
 * @return array|null array( francais, arabe ), ou null s'il n'y a rien a dire.
 */
function cpb_promesse_livraison() {
	$frais  = function_exists( 'comptoir_livraison_dh' ) ? (int) comptoir_livraison_dh() : 0;
	$franco = function_exists( 'comptoir_franco_articles' ) ? (int) comptoir_franco_articles() : 0;

	if ( $frais <= 0 ) {
		return null;
	}
	if ( 1 === $franco ) {
		return array( 'Livraison offerte, partout au Maroc', 'التوصيل مجاني، في كل المغرب' );
	}
	if ( 2 === $franco ) {
		return array( 'Livraison offerte dès le deuxième parfum', 'التوصيل مجاني ابتداءً من العطر الثاني' );
	}
	if ( $franco > 2 ) {
		return array(
			sprintf( 'Livraison offerte dès %d parfums', $franco ),
			sprintf( 'التوصيل مجاني ابتداءً من %d عطور', $franco ),
		);
	}
	return array(
		sprintf( 'Livraison %d DH, partout au Maroc', $frais ),
		sprintf( 'التوصيل بـ%d درهم في كل المغرب', $frais ),
	);
}

/** Les messages du bandeau, chacun en francais et en arabe. */
function cpb_messages_bandeau() {
	$m = array(
		array( 'Paiement à la livraison, partout au Maroc', 'الدفع عند الاستلام، في كل المغرب' ),
	);
	$livraison = cpb_promesse_livraison();
	if ( $livraison ) {
		$m[] = $livraison;
	}
	$m[] = array( 'Testeurs 100 % originaux · Satisfait ou remboursé', 'تستر أصلي 100% · راضٍ أو تسترجع مالك' );
	return $m;
}

add_action( 'wp_body_open', function () {
	if ( ! cpb_actif() ) {
		return;
	}
	echo '<div class="cpb-bandeau" id="cpb-bandeau" role="region" aria-label="Informations"><div class="cpb-bandeau-rang">';
	foreach ( cpb_messages_bandeau() as $i => $msg ) {
		printf(
			'<p class="cpb-bandeau-msg%s"><span class="cpb-fr">%s</span><span class="cpb-ar" lang="ar" dir="rtl">%s</span></p>',
			0 === $i ? ' is-on' : '',
			esc_html( $msg[0] ),
			esc_html( $msg[1] )
		);
	}
	echo '</div></div>';
}, 5 );
