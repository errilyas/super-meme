<?php
/**
 * Plugin Name:       Comptoir Boutique
 * Description:       Les outils des grandes boutiques de parfum, branchés sur le thème Le Comptoir des Parfums : bandeau d'annonce, recherche instantanée, quiz « Trouver mon parfum », favoris, parfums du même esprit et parfums vus récemment. Aucune donnée en double : tout est lu dans le catalogue du thème (produits.php).
 * Version:           1.20.0
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

define( 'CPB_VERSION', '1.20.0' );

/**
 * Mode de diffusion.
 *   'apercu'   : rien ne change pour les visiteurs ; les nouveautes ne
 *                s'affichent qu'avec ?apercu=boutique dans l'adresse.
 *   'en-ligne' : pour tout le monde.
 * On passe d'abord en apercu, on verifie sur le vrai site, puis on bascule.
 */
define( 'CPB_MODE', 'en-ligne' );

require_once __DIR__ . '/avis.php';

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

	// Mesure GA4 (mesure.js) : avant panier.js, qui envoie la vue de fiche des
	// son chargement (et commande.js l'achat, a l'ouverture du remerciement).
	wp_register_script( 'comptoir-boutique-mesure', $url . 'mesure.js', array(), CPB_VERSION . '.' . (int) @filemtime( $abs . 'mesure.js' ), true );
	$panier = wp_scripts()->query( 'comptoir-panier', 'registered' );
	if ( $panier && ! in_array( 'comptoir-boutique-mesure', $panier->deps, true ) ) {
		$panier->deps[] = 'comptoir-boutique-mesure';
	}

	$cfg_accueil = function_exists( 'comptoir_est_accueil' ) ? comptoir_est_accueil() : is_front_page();
	$cfg_vedette = function_exists( 'comptoir_vente_demande' ) && comptoir_vente_demande();
	$cfg = array(
		'slug'       => $parfum,
		'avis'       => function_exists( 'cpb_avis_resume_cfg' ) ? cpb_avis_resume_cfg() : null,
		// Page Vente ouverte sur un parfum (publicite) : il recoit lui aussi
		// la commande express.
		'vedette'    => ( ! $parfum && function_exists( 'comptoir_vente_demande' ) && comptoir_vente_demande() && function_exists( 'comptoir_vente_vedette' ) && comptoir_vente_vedette() ) ? comptoir_vente_vedette()['s'] : '',
		'accueil'    => function_exists( 'comptoir_est_accueil' ) ? comptoir_est_accueil() : is_front_page(),
		'home'       => home_url( '/' ),
		'populaires' => cpb_populaires(),
		'visuels'    => cpb_visuels(),
		'maisons'    => ( $parfum || ! empty( $cfg_vedette ) || ! empty( $cfg_accueil ) ) ? cpb_maisons_cfg() : array(),
		'url_maisons' => ( ! empty( $cfg_accueil ) && ( $pm = get_page_by_path( 'parfums' ) ) && 'publish' === $pm->post_status ) ? get_permalink( $pm ) : '',
		'guides'     => ( ! empty( $cfg_accueil ) || $parfum ) ? cpb_guides( 12 ) : array(),
		'pages'      => array_map( function ( $p ) { return array( 'fr' => $p[0], 'ar' => $p[1], 'url' => $p[2], 'cle' => $p[3] ); }, cpb_pages_info() ),
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

/* ══════════════════════════════════════════════════════════════
   IMAGE DE PARTAGE (Facebook, WhatsApp)
   Jusqu'au theme 3.5.2, chaque fiche annoncait og:image 1200 x 630 pour
   une photo de 720 x 900 : la taille n'etait pas lue a cause de
   l'empreinte « ?v=… ». Le theme 3.5.3 le corrige ; en attendant qu'il
   soit installe, on remet ici les vraies dimensions. Avec le theme
   corrige, les valeurs sont deja justes et rien ne change.
══════════════════════════════════════════════════════════════ */
function cpb_corrige_og( $html ) {
	if ( ! preg_match( '#<meta property="og:image" content="([^"]+)">#', $html, $m ) ) {
		return $html;
	}
	$url = strtok( html_entity_decode( $m[1], ENT_QUOTES ), '?' );
	$uri = get_template_directory_uri();
	if ( 0 !== strpos( $url, $uri . '/' ) ) {
		return $html;
	}
	$fichier = get_template_directory() . substr( $url, strlen( $uri ) );
	$taille  = ( false === strpos( $fichier, '..' ) && is_file( $fichier ) ) ? @getimagesize( $fichier ) : false;
	if ( ! $taille ) {
		return $html;
	}
	$html = preg_replace( '#<meta property="og:image:width" content="\d+">#', '<meta property="og:image:width" content="' . (int) $taille[0] . '">', $html, 1 );
	return preg_replace( '#<meta property="og:image:height" content="\d+">#', '<meta property="og:image:height" content="' . (int) $taille[1] . '">', $html, 1 );
}

add_action( 'wp_head', function () {
	if ( function_exists( 'comptoir_meta_page' ) && ! is_feed() ) {
		$GLOBALS['cpb_og_tampon'] = ob_start();
	}
}, 0 );

add_action( 'wp_head', function () {
	if ( empty( $GLOBALS['cpb_og_tampon'] ) ) {
		return;
	}
	$GLOBALS['cpb_og_tampon'] = false;
	echo cpb_description_page( cpb_enrichit_ld( cpb_corrige_og( (string) ob_get_clean() ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- sortie du theme, deja echappee.
}, 3 ); // apres les donnees structurees du theme (priorite 2)

/* ══════════════════════════════════════════════════════════════
   DONNEES STRUCTUREES (Google)
   Le theme decrit chaque fiche comme un produit avec son prix. Google
   demande aussi, pour les fiches marchandes, l'etat du produit, la
   livraison et la politique de retour : sans elles, la Search Console
   signale des champs manquants et les fiches peuvent perdre leurs
   informations enrichies. On les ajoute, tirees des reglages du theme
   et des pages publiees, sans rien promettre de plus que le site.
══════════════════════════════════════════════════════════════ */
function cpb_enrichit_ld( $html ) {
	return preg_replace_callback( '#<script type="application/ld\+json">(.*?)</script>#s', function ( $m ) {
		$d = json_decode( $m[1], true );
		if ( ! is_array( $d ) || empty( $d['@type'] ) ) {
			return $m[0];
		}
		if ( 'Product' === $d['@type'] && ! empty( $d['offers'] ) && is_array( $d['offers'] ) ) {
			if ( empty( $d['sku'] ) && function_exists( 'comptoir_parfum_slug_demande' ) ) {
				$d['sku'] = comptoir_parfum_slug_demande();
			}
			$o                  = $d['offers'];
			$o['itemCondition'] = 'https://schema.org/NewCondition';
			$o['seller']        = array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ) );
			$frais              = function_exists( 'comptoir_livraison_dh' ) ? (int) comptoir_livraison_dh() : 0;
			if ( $frais > 0 ) {
				$o['shippingDetails'] = array(
					'@type'               => 'OfferShippingDetails',
					'shippingRate'        => array( '@type' => 'MonetaryAmount', 'value' => $frais, 'currency' => 'MAD' ),
					'shippingDestination' => array( '@type' => 'DefinedRegion', 'addressCountry' => 'MA' ),
					'deliveryTime'        => array(
						'@type'        => 'ShippingDeliveryTime',
						'handlingTime' => array( '@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 1, 'unitCode' => 'DAY' ),
						'transitTime'  => array( '@type' => 'QuantitativeValue', 'minValue' => 1, 'maxValue' => 4, 'unitCode' => 'DAY' ),
					),
				);
			}
			$retours = get_page_by_path( 'retours-et-remboursement' );
			if ( $retours && 'publish' === $retours->post_status ) {
				$o['hasMerchantReturnPolicy'] = array(
					'@type'                => 'MerchantReturnPolicy',
					'applicableCountry'    => 'MA',
					'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
					'merchantReturnDays'   => 7,
					'returnMethod'         => 'https://schema.org/ReturnByMail',
					'url'                  => get_permalink( $retours ),
				);
			}
			$d['offers'] = $o;
			if ( function_exists( 'cpb_avis_ld' ) ) {
				$d = cpb_avis_ld( $d );
			}
		} elseif ( 'Store' === $d['@type'] && empty( $d['sameAs'] ) ) {
			$d['sameAs'] = array( 'https://www.instagram.com/le_comptoir_parfums', 'https://www.facebook.com/profile.php?id=61573721267555' );
		}
		return '<script type="application/ld+json">' . wp_json_encode( $d ) . '</script>';
	}, $html );
}

/** Nom de maison => adresse de sa page, pour celles qui en ont une. */
function cpb_maisons_cfg() {
	$out = array();
	if ( function_exists( 'comptoir_catalogue_par_maison' ) ) {
		$maisons = comptoir_catalogue_par_maison();
		uksort( $maisons, function ( $a, $b ) use ( $maisons ) {
			return count( $maisons[ $b ] ) - count( $maisons[ $a ] ) ?: strcmp( $a, $b );
		} );
		foreach ( array_keys( $maisons ) as $maison ) {
			$url = cpb_url_maison( $maison );
			if ( $url ) {
				$out[ $maison ] = $url;
			}
		}
	}
	return $out;
}

/**
 * Description pour Google des pages et des guides : le theme n'en ecrit
 * que pour l'accueil, les fiches et ses propres pages. On reprend la
 * description SEO saisie (Jetpack) ou, a defaut, le resume.
 */
function cpb_description_page( $html ) {
	if ( ! is_singular( array( 'page', 'post' ) ) || false !== stripos( $html, '<meta name="description"' ) ) {
		return $html;
	}
	$id   = get_queried_object_id();
	$desc = trim( (string) get_post_meta( $id, 'advanced_seo_description', true ) );
	if ( '' === $desc ) {
		$desc = trim( wp_strip_all_tags( get_the_excerpt( $id ) ) );
	}
	if ( '' === $desc ) {
		return $html;
	}
	$GLOBALS['cpb_description_ecrite'] = true;
	return '<meta name="description" content="' . esc_attr( wp_html_excerpt( $desc, 300, '…' ) ) . '">' . "\n" . $html;
}

/* Jetpack (outils SEO) : pas de seconde description si on vient d'en ecrire une. */
add_filter( 'jetpack_seo_meta_tags', function ( $tags ) {
	if ( ! empty( $GLOBALS['cpb_description_ecrite'] ) && is_array( $tags ) ) {
		unset( $tags['description'] );
	}
	return $tags;
} );

/**
 * Les guides publies (categorie « conseils »), du plus recent au plus
 * ancien : titre, resume, adresse, slug. Pour la fiche et l'accueil.
 */
function cpb_guides( $n = 6 ) {
	$cat = get_category_by_slug( 'conseils' );
	if ( ! $cat ) {
		return array();
	}
	$out = array();
	foreach ( get_posts( array( 'category' => $cat->term_id, 'numberposts' => $n, 'post_status' => 'publish' ) ) as $post ) {
		$out[] = array(
			'titre' => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
			'resume' => wp_strip_all_tags( get_the_excerpt( $post ) ),
			'url'   => get_permalink( $post ),
			'slug'  => $post->post_name,
		);
	}
	return $out;
}

/* ══════════════════════════════════════════════════════════════
   PAGES DU SITE ET LIENS D'INFORMATION
   - Les pages WordPress s'affichent en entier (gabarit page-cpb.php) :
     le theme n'en a pas et les montrait comme une liste d'articles.
   - Le pied de page gagne les liens vers les pages d'information qui
     existent (publiees), et les formulaires de commande renvoient aux
     conditions de vente.
══════════════════════════════════════════════════════════════ */
add_filter( 'template_include', function ( $template ) {
	$page    = is_page() && ! locate_template( array( 'page.php' ) ) && ! is_page_template();
	$article = is_singular( 'post' ) && ! locate_template( array( 'single.php' ) );
	if ( ( $page || $article ) && function_exists( 'comptoir_produits' ) ) {
		$t = plugin_dir_path( __FILE__ ) . 'page-cpb.php';
		if ( file_exists( $t ) ) {
			return $t;
		}
	}
	return $template;
}, 20 );

/** Archive des guides : la categorie « conseils », ou l'accueil a defaut. */
function cpb_url_conseils() {
	$cat = get_category_by_slug( 'conseils' );
	return $cat ? get_category_link( $cat ) : home_url( '/' );
}

/* Titre d'archive sans prefixe : « Conseils », pas « Categorie : Conseils ». */
add_filter( 'get_the_archive_title_prefix', function ( $prefix ) {
	return is_category() ? '' : $prefix;
} );

/**
 * Les parfums d'une selection : par slugs (dans l'ordre donne), par maison
 * ou par public (Homme, Femme, Mixte). Ordre du catalogue sinon.
 */
function cpb_selection( $atts ) {
	if ( ! function_exists( 'comptoir_produits' ) ) {
		return array();
	}
	$out = array();
	if ( '' !== trim( (string) $atts['slugs'] ) ) {
		foreach ( array_filter( array_map( 'trim', explode( ',', (string) $atts['slugs'] ) ) ) as $slug ) {
			$p = comptoir_produit_by_slug( preg_replace( '/[^a-z0-9-]/', '', strtolower( $slug ) ) );
			if ( $p ) {
				$out[] = $p;
			}
		}
		return $out;
	}
	$maison = trim( (string) $atts['maison'] );
	$genre  = trim( (string) $atts['genre'] );
	if ( '' === $maison && '' === $genre ) {
		return array();
	}
	foreach ( comptoir_produits() as $p ) {
		if ( '' !== $maison && comptoir_maison_slug( $p['b'] ) !== comptoir_maison_slug( html_entity_decode( $maison, ENT_QUOTES, 'UTF-8' ) ) ) {
			continue;
		}
		if ( '' !== $genre && strtolower( $p['g'] ) !== strtolower( $genre ) ) {
			continue;
		}
		$out[] = $p;
	}
	return $out;
}

/**
 * [parfums slugs="dior--sauvage-elixir,chanel--coco-mademoiselle"]
 * [parfums maison="Dior"]  ·  [parfums genre="Homme"]
 * Les cartes du catalogue (memes cartes que l'accueil : photo, maison,
 * nom, prix, lien vers la fiche) dans un guide ou une page de collection.
 */
add_shortcode( 'parfums', function ( $atts ) {
	if ( ! function_exists( 'comptoir_carte_produit' ) ) {
		return '';
	}
	$liste = cpb_selection( shortcode_atts( array( 'slugs' => '', 'maison' => '', 'genre' => '' ), $atts, 'parfums' ) );
	if ( ! $liste ) {
		return '';
	}
	ob_start();
	foreach ( $liste as $p ) {
		comptoir_carte_produit( $p );
	}
	return '<div class="cat-grille cpb-guide-grille" role="list">' . ob_get_clean() . '</div>';
} );

/**
 * [collection maison="Dior"] : une phrase tiree du catalogue, toujours a
 * jour (« 18 parfums, de 349 a 429 DH »), pour l'en-tete d'une page.
 */
add_shortcode( 'collection', function ( $atts ) {
	$liste = cpb_selection( shortcode_atts( array( 'slugs' => '', 'maison' => '', 'genre' => '' ), $atts, 'collection' ) );
	if ( ! $liste ) {
		return '';
	}
	$prix = array();
	foreach ( $liste as $p ) {
		$prix[] = (int) preg_replace( '/[^0-9]/', '', $p['pr'] );
	}
	$n   = count( $liste );
	$txt = sprintf(
		'%d %s, de %s à %s DH. Testeurs originaux, payés en espèces à la livraison, partout au Maroc.',
		$n,
		$n > 1 ? 'parfums' : 'parfum',
		number_format_i18n( min( $prix ) ),
		number_format_i18n( max( $prix ) )
	);
	return '<p class="cpb-collection-resume">' . esc_html( $txt ) . '</p>';
} );

/** Adresse de la page d'une maison (/parfums/<maison>/) si elle est publiee. */
function cpb_url_maison( $maison ) {
	static $cache = array();
	$slug = comptoir_maison_slug( $maison );
	if ( ! array_key_exists( $slug, $cache ) ) {
		$page           = get_page_by_path( 'parfums/' . $slug );
		$cache[ $slug ] = ( $page && 'publish' === $page->post_status ) ? get_permalink( $page ) : '';
	}
	return $cache[ $slug ];
}

/**
 * [maisons] : toutes les maisons du catalogue, avec leur nombre de parfums.
 * Lien vers la page de la maison si elle existe, vers le catalogue filtre
 * sinon.
 */
add_shortcode( 'maisons', function () {
	if ( ! function_exists( 'comptoir_catalogue_par_maison' ) ) {
		return '';
	}
	$html = '<ul class="cpb-maisons">';
	foreach ( comptoir_catalogue_par_maison() as $maison => $liste ) {
		$url   = cpb_url_maison( $maison );
		$url   = $url ? $url : home_url( '/#catalogue?maison=' . comptoir_maison_slug( $maison ) );
		$html .= sprintf( '<li><a href="%s"><span class="pnr-brand">%s</span> <small>%d</small></a></li>', esc_url( $url ), esc_html( $maison ), count( $liste ) );
	}
	return $html . '</ul>';
} );

/**
 * Les pages d'information publiees, dans l'ordre du pied de page.
 * @return array liste de array( titre_fr, titre_ar, url, cle ).
 */
function cpb_pages_info() {
	static $out = null;
	if ( null !== $out ) {
		return $out;
	}
	$out   = array();
	$liste = array(
		'parfums'                   => array( 'Toutes les maisons', 'كل الدور' ),
		'a-propos'                  => array( 'À propos', 'من نحن' ),
		'contact'                   => array( 'Contact', 'اتصل بنا' ),
		'conditions-de-vente'       => array( 'Conditions de vente', 'شروط البيع' ),
		'retours-et-remboursement'  => array( 'Retours et remboursement', 'الإرجاع والاسترداد' ),
		'confidentialite'           => array( 'Confidentialité', 'الخصوصية' ),
	);
	$cat = get_category_by_slug( 'conseils' );
	if ( $cat && $cat->count > 0 ) {
		$out[] = array( 'Conseils', 'نصائح', get_category_link( $cat ), 'conseils' );
	}
	foreach ( $liste as $slug => $t ) {
		$page = get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			$out[] = array( $t[0], $t[1], get_permalink( $page ), $slug );
		}
	}
	return $out;
}

/* ══════════════════════════════════════════════════════════════
   DEMANDE D'AVIS (administration, liste des commandes)
   Un lien « Demander un avis » sur chaque commande : il ouvre WhatsApp
   avec le message deja ecrit, au numero du client. Aucun envoi
   automatique, aucune API : c'est vous qui envoyez, quand le colis est
   livre. Les vrais retours (et photos) alimentent ensuite le site.
══════════════════════════════════════════════════════════════ */
function cpb_lien_avis( $id ) {
	$tel = preg_replace( '/\D/', '', (string) get_post_meta( $id, 'cp_tel', true ) );
	if ( preg_match( '/^0([5-7]\d{8})$/', $tel, $m ) ) {
		$tel = '212' . $m[1];
	} elseif ( preg_match( '/^00212(\d{9})$/', $tel, $m ) ) {
		$tel = '212' . $m[1];
	}
	if ( ! preg_match( '/^212[5-7]\d{8}$/', $tel ) ) {
		return '';
	}
	$nom    = trim( (string) get_post_meta( $id, 'cp_nom', true ) );
	$prenom = $nom ? preg_split( '/\s+/u', $nom )[0] : '';
	$lien   = function_exists( 'cpb_url_avis' ) && cpb_avis_parfums_commande( $id ) ? cpb_url_avis( $id ) : '';
	if ( $lien ) {
		$msg = sprintf(
			"Bonjour%s, c'est Le Comptoir des Parfums. Votre parfum vous plaît ? Votre avis aide les prochains clients à choisir (30 secondes). Merci !\n\nالسلام%s، عجبك العطر؟ رأيك كيعاون الزبناء الآخرين (30 ثانية). شكرا بزاف!\n\n%s",
			$prenom ? ' ' . $prenom : '',
			$prenom ? ' ' . $prenom : '',
			$lien
		);
	} else {
		$msg = sprintf(
			"Bonjour%s, c'est Le Comptoir des Parfums. Votre parfum vous plaît ? Un petit mot (et une photo si vous voulez) nous aiderait beaucoup. Merci !\n\nالسلام%s، عجبك العطر؟ عطينا رأيك (وتصويرة إلا بغيتي). شكرا بزاف!",
			$prenom ? ' ' . $prenom : '',
			$prenom ? ' ' . $prenom : ''
		);
	}
	return 'https://wa.me/' . $tel . '?text=' . rawurlencode( $msg );
}

add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( 'cp_commande' !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
		return $actions;
	}
	$url = cpb_lien_avis( $post->ID );
	if ( $url ) {
		$actions['cpb_avis'] = sprintf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( $url ), esc_html__( 'WhatsApp : demander un avis', 'comptoir-boutique' ) );
	}
	return $actions;
}, 20, 2 );

/**
 * Recherche WordPress (/?s=…) : elle cherchait dans les articles du blog,
 * qui sont vides, et repondait « aucun resultat » a « dior ». On renvoie
 * vers l'accueil, ou la recherche instantanee s'ouvre avec les memes mots
 * et cherche dans le catalogue.
 */
add_action( 'template_redirect', function () {
	if ( ! is_search() || is_admin() || ! function_exists( 'comptoir_produits' ) ) {
		return;
	}
	$q = trim( (string) get_search_query( false ) );
	wp_safe_redirect( home_url( '/' ) . ( '' !== $q ? '#chercher=' . rawurlencode( $q ) : '' ), 302 );
	exit;
}, 2 );

/**
 * Fil d'Ariane pour Google (BreadcrumbList) : fiche parfum
 * (Accueil > maison > parfum), pages sous /parfums/, guides.
 */
add_action( 'wp_head', function () {
	if ( ! function_exists( 'comptoir_produits' ) || is_admin() ) {
		return;
	}
	$fil = array( array( 'Accueil', home_url( '/' ) ) );
	$slug = function_exists( 'comptoir_parfum_slug_demande' ) ? comptoir_parfum_slug_demande() : '';
	$p    = $slug ? comptoir_produit_by_slug( $slug ) : null;
	if ( $p ) {
		$m = cpb_url_maison( $p['b'] );
		if ( $m ) {
			$fil[] = array( $p['b'], $m );
		}
		$fil[] = array( $p['b'] . ' ' . $p['n'], comptoir_parfum_url( $p['s'] ) );
	} elseif ( is_page() ) {
		$post = get_queried_object();
		if ( $post && $post->post_parent ) {
			$fil[] = array( html_entity_decode( get_the_title( $post->post_parent ), ENT_QUOTES, 'UTF-8' ), get_permalink( $post->post_parent ) );
		}
		$fil[] = array( html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ), get_permalink( $post ) );
	} elseif ( is_singular( 'post' ) ) {
		$cat = get_category_by_slug( 'conseils' );
		if ( $cat ) {
			$fil[] = array( 'Conseils', get_category_link( $cat ) );
		}
		$fil[] = array( html_entity_decode( get_the_title(), ENT_QUOTES, 'UTF-8' ), get_permalink() );
	} else {
		return;
	}
	$items = array();
	foreach ( $fil as $i => $e ) {
		$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $e[0], 'item' => $e[1] );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items ) ) . '</script>' . "\n";
}, 4 );

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

/* ══════════════════════════════════════════════════════════════
   FLUX PRODUITS : GOOGLE MERCHANT CENTER ET CATALOGUE META
   https://<site>/?flux=produits : le catalogue au format RSS 2.0 de
   Google (espace g:), que Meta Commerce Manager lit aussi. Une seule
   source (produits.php) : un prix change sur le site change dans le
   flux. Les parfums sans photo sont ecartes (l'image est obligatoire).
   Le titre dit « Testeur » : c'est ce qui est vendu, et les deux regies
   refusent une annonce qui ne correspond pas a la fiche.
══════════════════════════════════════════════════════════════ */
function cpb_flux_produits() {
	if ( ! function_exists( 'comptoir_produits' ) ) {
		return '';
	}
	$genres = array( 'Homme' => array( 'male', 'homme' ), 'Femme' => array( 'female', 'femme' ), 'Mixte' => array( 'unisex', 'mixte' ) );
	$liv    = function_exists( 'comptoir_livraison_dh' ) ? (int) comptoir_livraison_dh() : 35;
	$x      = function ( $v ) {
		return htmlspecialchars( wp_strip_all_tags( html_entity_decode( (string) $v, ENT_QUOTES, 'UTF-8' ) ), ENT_XML1 | ENT_QUOTES, 'UTF-8' );
	};
	$out  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$out .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"><channel>' . "\n";
	$out .= '<title>' . $x( get_bloginfo( 'name' ) ) . '</title><link>' . esc_url( home_url( '/' ) ) . '</link>';
	$out .= '<description>' . $x( 'Parfums de grandes maisons, testeurs originaux, livrés partout au Maroc.' ) . '</description>' . "\n";
	foreach ( comptoir_produits() as $p ) {
		$prix = function_exists( 'comptoir_prix_entier' ) ? comptoir_prix_entier( $p ) : (int) preg_replace( '/\D/', '', $p['pr'] );
		if ( empty( $p['s'] ) || $prix <= 0 || ! function_exists( 'comptoir_a_photo' ) || ! comptoir_a_photo( $p['s'] ) ) {
			continue;
		}
		$g     = isset( $p['g'], $genres[ $p['g'] ] ) ? $genres[ $p['g'] ] : null;
		$titre = $p['b'] . ' ' . $p['n'] . ' – Testeur' . ( ! empty( $p['x'] ) ? ' ' . $p['x'] : '' );
		$notes = array();
		foreach ( array( 't' => 'Tête', 'c' => 'Cœur', 'f' => 'Fond' ) as $k => $nom ) {
			if ( ! empty( $p[ $k ] ) ) {
				$notes[] = $nom . ' : ' . implode( ', ', (array) $p[ $k ] );
			}
		}
		$desc = trim( ( isset( $p['d'] ) ? $p['d'] : '' ) . ( $notes ? ' Notes — ' . implode( ' ; ', $notes ) . '.' : '' ) )
			. ' Testeur original de la maison ' . $p['b'] . ' : même parfum que le flacon de boutique, sans le coffret. Paiement à la livraison partout au Maroc.';
		$out .= '<item>';
		$out .= '<g:id>' . $x( $p['s'] ) . '</g:id>';
		$out .= '<g:title>' . $x( $titre ) . '</g:title>';
		$out .= '<g:description>' . $x( $desc ) . '</g:description>';
		$out .= '<g:link>' . esc_url( comptoir_parfum_url( $p['s'] ) ) . '</g:link>';
		$out .= '<g:image_link>' . esc_url( comptoir_photo_url( $p['s'] ) ) . '</g:image_link>';
		$out .= '<g:availability>in_stock</g:availability>';
		$out .= '<g:price>' . $prix . '.00 MAD</g:price>';
		$out .= '<g:brand>' . $x( $p['b'] ) . '</g:brand>';
		$out .= '<g:condition>new</g:condition>';
		$out .= '<g:identifier_exists>no</g:identifier_exists>';
		$out .= '<g:google_product_category>479</g:google_product_category>';
		$out .= '<g:product_type>' . $x( 'Parfums > ' . ( $g ? ucfirst( $g[1] ) : 'Tous' ) . ' > ' . $p['b'] ) . '</g:product_type>';
		if ( $g ) {
			$out .= '<g:gender>' . $g[0] . '</g:gender><g:age_group>adult</g:age_group>';
		}
		$out .= '<g:shipping><g:country>MA</g:country><g:price>' . $liv . '.00 MAD</g:price></g:shipping>';
		$out .= "</item>\n";
	}
	return $out . '</channel></rss>';
}

add_action( 'template_redirect', function () {
	if ( ! isset( $_GET['flux'] ) || 'produits' !== $_GET['flux'] ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$xml = cpb_flux_produits();
	if ( '' === $xml ) {
		return;
	}
	status_header( 200 );
	header( 'Content-Type: application/xml; charset=UTF-8' );
	header( 'X-Robots-Tag: noindex' );
	header( 'Cache-Control: public, max-age=3600' );
	echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput -- echappe element par element ci-dessus.
	exit;
}, 0 );

/* ══════════════════════════════════════════════════════════════
   MICROSOFT CLARITY : UNE SEULE BALISE
   Le theme pose deja Clarity (comptoir_mesure()). L'extension
   « Microsoft Clarity », active elle aussi, en ajoutait une seconde pour
   le meme projet : deux scripts, deux enregistrements concurrents. Quand
   les deux identifiants sont les memes, on retire celle de l'extension
   (son tableau de bord dans l'admin reste disponible). S'ils different,
   ce sont deux projets distincts : on ne touche a rien.
══════════════════════════════════════════════════════════════ */
add_action( 'wp', function () {
	if ( is_admin() || ! function_exists( 'clarity_add_script_to_header' ) || ! function_exists( 'comptoir_mesure' ) ) {
		return;
	}
	$m      = comptoir_mesure();
	$theme  = isset( $m['clarity'] ) ? strtolower( trim( (string) $m['clarity'] ) ) : '';
	$plugin = strtolower( trim( (string) get_option( 'clarity_project_id' ) ) );
	if ( '' !== $theme && $theme === $plugin ) {
		remove_action( 'wp_head', 'clarity_add_script_to_header' );
	}
} );
