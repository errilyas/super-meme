<?php
/**
 * Plugin Name: Comptoir SEO
 * Description: Référencement du Comptoir des Parfums : plan du site des 209 fiches parfum (avec images) pour Google, textes alternatifs des photos produits, titres de pages orientés « testeur original » et « prix au Maroc », pages allégées (catalogue chargé une seule fois dans un fichier mis en cache), version arabe de l'accueil et des fiches visible par Google (hreflang, titres et descriptions en arabe), accès immédiat au site (sans l'écran de chargement de 3 secondes), photos à la bonne taille (WebP) et pixel Meta / Clarity chargés après l'affichage.
 * Version: 1.4.1
 * Author: Le Comptoir des Parfums
 * Requires PHP: 7.4
 * Text Domain: comptoir-seo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CSEO_VER', '1.4.1' );
define( 'CSEO_SITEMAP', 'sitemap-parfums.xml' );
define( 'CSEO_IMG_DIR', '/wp-content/themes/comptoir-parfums/img/produits/' );

/* ------------------------------------------------------------------ */
/* Catalogue : lu dans les données du thème (comptoir_produits) ; à    */
/* défaut, dans les cartes de la page d'accueil, mis en cache 12 h.    */
/* ------------------------------------------------------------------ */

/**
 * Les 209 fiches, lues dans les données du thème — sans requête HTTP.
 * Depuis le thème 3.7.0, l'accueil ne rend plus que 24 cartes dans sa page
 * (les autres sont construites par le navigateur) : lire les cartes ne
 * donnait plus qu'une partie du catalogue, et le plan du site serait tombé
 * de 209 fiches à 24 au premier renouvellement du cache.
 */
function cseo_catalogue_theme() {
	static $out = null;
	if ( null !== $out ) {
		return $out;
	}
	$out = array();
	if ( ! function_exists( 'comptoir_produits' ) ) {
		return $out;
	}
	foreach ( (array) comptoir_produits() as $p ) {
		if ( empty( $p['s'] ) ) {
			continue;
		}
		$slug    = strtolower( $p['s'] );
		$a_photo = function_exists( 'comptoir_a_photo' ) ? comptoir_a_photo( $slug ) : true;
		$nom     = trim( ( isset( $p['b'] ) ? $p['b'] : '' ) . ' ' . ( isset( $p['n'] ) ? $p['n'] : '' ) );
		$out[ $slug ] = array(
			'nom'  => '' !== $nom ? $nom : cseo_nom_depuis_slug( $slug ),
			'img'  => $a_photo ? home_url( CSEO_IMG_DIR . $slug . '.webp' ) : '',
			'prix' => isset( $p['pr'] ) ? (int) preg_replace( '/\D/', '', (string) $p['pr'] ) : 0,
		);
	}
	return $out;
}

/** Retourne [ slug => [ 'nom' => 'Dior Sauvage …', 'img' => URL, 'prix' => 349 ] ]. */
function cseo_catalogue( $force = false ) {
	$theme = cseo_catalogue_theme();
	if ( $theme ) {
		return $theme;
	}
	$cat = get_transient( 'cseo_catalogue' );
	if ( ! $force && is_array( $cat ) && $cat ) {
		return $cat;
	}
	$res = wp_remote_get( home_url( '/' ), array( 'timeout' => 20, 'headers' => array( 'Cache-Control' => 'no-cache' ) ) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		$old = get_option( 'cseo_catalogue_backup', array() );
		return is_array( $old ) ? $old : array();
	}
	$cat = cseo_parse_cards( wp_remote_retrieve_body( $res ) );
	if ( $cat ) {
		set_transient( 'cseo_catalogue', $cat, 12 * HOUR_IN_SECONDS );
		update_option( 'cseo_catalogue_backup', $cat, false );
	}
	return $cat;
}

/** Lit les cartes produit d'une page HTML. */
function cseo_parse_cards( $html ) {
	$out = array();
	if ( ! preg_match_all( '#<a\b[^>]*class="pc[\s"][^>]*>.*?</a>#is', $html, $cards ) ) {
		return $out;
	}
	foreach ( $cards[0] as $card ) {
		if ( ! preg_match( '#[?&]parfum=([a-z0-9-]+)#i', $card, $m ) ) {
			continue;
		}
		$slug   = strtolower( $m[1] );
		$maison = preg_match( '#class="pc-maison"[^>]*>([^<]+)<#i', $card, $a ) ? trim( html_entity_decode( $a[1], ENT_QUOTES, 'UTF-8' ) ) : '';
		$nom    = preg_match( '#class="pc-nom"[^>]*>([^<]+)<#i', $card, $b ) ? trim( html_entity_decode( $b[1], ENT_QUOTES, 'UTF-8' ) ) : '';
		$img    = preg_match( '#<img\b[^>]*\ssrc="([^"]+)"#i', $card, $c ) ? html_entity_decode( $c[1], ENT_QUOTES, 'UTF-8' ) : '';
		$prix   = preg_match( '#class="pc-prix"[^>]*>([^<]+)<#i', $card, $d ) ? (int) preg_replace( '/\D/', '', $d[1] ) : 0;
		$out[ $slug ] = array(
			'nom' => trim( $maison . ' ' . $nom ) ? trim( $maison . ' ' . $nom ) : cseo_nom_depuis_slug( $slug ),
			'img'  => $img ? strtok( $img, '?' ) : home_url( CSEO_IMG_DIR . $slug . '.webp' ),
			'prix' => $prix,
		);
	}
	return $out;
}

/** « dior--sauvage-eau-de-parfum » → « Dior Sauvage Eau De Parfum » (secours si la carte n'est pas trouvée). */
function cseo_nom_depuis_slug( $slug ) {
	$txt = str_replace( array( '--', '-' ), array( ' ', ' ' ), $slug );
	return ucwords( trim( $txt ) );
}

/* ------------------------------------------------------------------ */
/* 1. Plan du site des fiches parfum : /sitemap-parfums.xml            */
/* ------------------------------------------------------------------ */

// Après une mise à jour de l'extension, on relit le catalogue (nouvelles informations, ex. prix).
add_action(
	'init',
	function () {
		if ( CSEO_VER !== get_option( 'cseo_ver' ) ) {
			delete_transient( 'cseo_catalogue' );
			update_option( 'cseo_ver', CSEO_VER, false );
		}
	},
	0
);

add_action( 'init', 'cseo_maybe_sitemap', 1 );

function cseo_maybe_sitemap() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( '/' . CSEO_SITEMAP !== rtrim( (string) $path, '/' ) ) {
		return;
	}
	$cat = cseo_catalogue();
	status_header( $cat ? 200 : 503 );
	header( 'Content-Type: application/xml; charset=UTF-8' );
	header( 'X-Robots-Tag: noindex, follow' );
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
	$urls = array( '' => null );
	foreach ( $cat as $slug => $p ) {
		$urls[ $slug ] = $p;
	}
	foreach ( $urls as $slug => $p ) {
		$fr = '' === $slug ? home_url( '/' ) : add_query_arg( 'parfum', $slug, home_url( '/' ) );
		$ar = add_query_arg( 'lang', 'ar', $fr );
		$alt = "\t\t<xhtml:link rel=\"alternate\" hreflang=\"fr\" href=\"" . esc_url( $fr ) . "\"/>\n"
			. "\t\t<xhtml:link rel=\"alternate\" hreflang=\"ar\" href=\"" . esc_url( $ar ) . "\"/>\n"
			. "\t\t<xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"" . esc_url( $fr ) . "\"/>\n";
		foreach ( array( $fr, $ar ) as $loc ) {
			echo "\t<url>\n\t\t<loc>" . esc_url( $loc ) . "</loc>\n" . $alt;
			echo "\t\t<changefreq>weekly</changefreq>\n";
			if ( $p && ! empty( $p['img'] ) ) {
				echo "\t\t<image:image><image:loc>" . esc_url( $p['img'] ) . '</image:loc><image:title>' . esc_html( 'Testeur ' . $p['nom'] ) . "</image:title></image:image>\n";
			}
			echo "\t</url>\n";
		}
	}
	echo "</urlset>\n";
	exit;
}

// Le plan du site des fiches est annoncé dans robots.txt, à côté de celui de Jetpack.
add_filter(
	'robots_txt',
	function ( $out, $public ) {
		if ( $public ) {
			$out = rtrim( $out ) . "\nSitemap: " . home_url( '/' . CSEO_SITEMAP ) . "\n";
		}
		return $out;
	},
	20,
	2
);

/* ------------------------------------------------------------------ */
/* 2. Textes alternatifs des photos produits                           */
/* ------------------------------------------------------------------ */

add_action(
	'template_redirect',
	function () {
		if ( is_admin() || is_feed() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_customize_preview() ) {
			return;
		}
		ob_start( 'cseo_filter_html' );
	},
	99
);

function cseo_filter_html( $html ) {
	if ( ! is_string( $html ) || false === stripos( $html, '</head>' ) ) {
		return $html;
	}
	$html = cseo_langues( $html );
	$html = cseo_vitrine_async( $html );
	$html = cseo_traceurs_plus_tard( $html );
	$html = cseo_alt_produits( $html );
	$html = cseo_images_legeres( $html );
	return $html;
}

/** Texte alternatif des photos produits qui n'en ont pas. */
function cseo_alt_produits( $html ) {
	if ( false === stripos( $html, '<img' ) || false === stripos( $html, '/produits/' ) ) {
		return $html;
	}
	// Noms exacts lus dans les cartes de la page elle-même, sinon dans le catalogue en cache.
	$noms = array();
	foreach ( cseo_parse_cards( $html ) as $slug => $p ) {
		$noms[ $slug ] = $p['nom'];
	}
	$cache = cseo_catalogue_theme();
	if ( ! $cache ) {
		$cache = get_transient( 'cseo_catalogue' );
	}
	if ( is_array( $cache ) ) {
		foreach ( $cache as $slug => $p ) {
			if ( ! isset( $noms[ $slug ] ) ) {
				$noms[ $slug ] = $p['nom'];
			}
		}
	}
	$new = preg_replace_callback(
		'#<img\b[^>]*>#i',
		function ( $m ) use ( $noms ) {
			$tag = $m[0];
			if ( ! preg_match( '#/produits/([a-z0-9-]+)\.(?:webp|jpe?g|png|avif)#i', $tag, $s ) ) {
				return $tag;
			}
			// On ne touche pas à un texte alternatif déjà rempli.
			if ( preg_match( '#\salt\s*=\s*"([^"]+)"#i', $tag ) ) {
				return $tag;
			}
			$slug = strtolower( $s[1] );
			$nom  = isset( $noms[ $slug ] ) ? $noms[ $slug ] : cseo_nom_depuis_slug( $slug );
			$alt  = esc_attr( 'Testeur ' . $nom . ' – parfum original' );
			if ( preg_match( '#\salt\s*=\s*""#i', $tag ) ) {
				return preg_replace( '#\salt\s*=\s*""#i', ' alt="' . $alt . '"', $tag, 1 );
			}
			return preg_replace( '#^<img\b#i', '<img alt="' . $alt . '"', $tag, 1 );
		},
		$html
	);
	return is_string( $new ) ? $new : $html;
}

/* ------------------------------------------------------------------ */
/* 3. Titres de page                                                   */
/* ------------------------------------------------------------------ */

/**
 * Fiches parfum (?parfum=…) : « Dior Sauvage Elixir – 349 DH au Maroc, testeur original ».
 * Le prix d'abord (1.4.0) : les fiches arrivaient en 5e-9e position sur le
 * nom exact du parfum (« dior sauvage elixir », « la vie est belle
 * l'extrait »), sans presque aucun clic. Le prix au Maroc est ce que la
 * recherche veut savoir, et ce qui distingue la fiche des grandes enseignes.
 */
add_filter(
	'pre_get_document_title',
	function ( $title ) {
		if ( empty( $_GET['parfum'] ) || ! is_string( $title ) || '' === $title ) {
			return $title;
		}
		$nom = trim( preg_replace( '/\s+[—–|-]\s+.*$/u', '', $title ) );
		if ( '' === $nom ) {
			return $title;
		}
		$slug = strtolower( preg_replace( '/[^A-Za-z0-9-]/', '', (string) wp_unslash( $_GET['parfum'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$cat  = cseo_catalogue_theme();
		if ( $cat && ! isset( $cat[ $slug ] ) ) {
			return $title; // Fiche inconnue : le thème a déjà écrit « Parfum introuvable ».
		}
		$prix = isset( $cat[ $slug ]['prix'] ) ? (int) $cat[ $slug ]['prix'] : 0;
		if ( $prix ) {
			$titres = array(
				$nom . ' – ' . $prix . ' DH au Maroc, testeur original',
				$nom . ' – ' . $prix . ' DH au Maroc',
				$nom . ' – ' . $prix . ' DH',
			);
			foreach ( $titres as $t ) {
				if ( mb_strlen( $t ) <= 65 ) {
					return $t;
				}
			}
			return end( $titres );
		}
		$long  = $nom . ' – Testeur original, prix au Maroc';
		$court = $nom . ' – Testeur original au Maroc';
		return mb_strlen( $long ) <= 65 ? $long : ( mb_strlen( $court ) <= 70 ? $court : $nom . ' – Testeur original' );
	},
	99
);

/** Pages maisons et familles (enfants de /parfums/) : « Parfums Dior au Maroc : testeurs originaux ». */
add_filter(
	'document_title_parts',
	function ( $parts ) {
		if ( ! is_page() ) {
			return $parts;
		}
		$page = get_queried_object();
		if ( ! $page || empty( $page->post_parent ) ) {
			return $parts;
		}
		$parent = get_post( $page->post_parent );
		if ( ! $parent || 'parfums' !== $parent->post_name ) {
			return $parts;
		}
		$titre = get_the_title( $page );
		if ( 0 !== stripos( $titre, 'parfum' ) ) {
			$titre = 'Parfums ' . $titre;
		}
		$parts['title'] = $titre . ' au Maroc : testeurs originaux';
		$parts['site']  = 'Comptoir des Parfums';
		return $parts;
	},
	99
);

/* ------------------------------------------------------------------ */
/* 4. Pages allégées : le catalogue (≈ 88 Ko) sort du HTML              */
/*                                                                    */
/* Le thème écrit sur chaque page, avant panier.js, un script avec     */
/* window.PRODUITS, CP_PRODUCT_IMAGES et HERO_SHOTS (identiques partout)*/
/* et CP_PANIER (qui contient un jeton de sécurité, différent selon le  */
/* moment). On place les trois premiers dans un fichier statique,       */
/* gardé en cache par le navigateur et le CDN ; CP_PANIER et            */
/* CP_PARFUM_BASE restent dans la page. Le nom du fichier change dès    */
/* que le catalogue change (prix, nouveaux parfums).                    */
/* ------------------------------------------------------------------ */

define( 'CSEO_STATIC_VARS', 'PRODUITS|CP_PRODUCT_IMAGES|HERO_SHOTS' );

add_action( 'wp_enqueue_scripts', 'cseo_externalise_catalogue', 9999 );
add_action( 'wp_print_scripts', 'cseo_externalise_catalogue', 1 );
add_action( 'wp_print_footer_scripts', 'cseo_externalise_catalogue', 1 );

function cseo_externalise_catalogue() {
	static $done = false;
	if ( $done || is_admin() || apply_filters( 'cseo_disable_catalogue_file', false ) ) {
		return;
	}
	$ws     = wp_scripts();
	$handle = 'comptoir-panier';
	if ( empty( $ws->registered[ $handle ] ) ) {
		return;
	}
	$before = $ws->get_data( $handle, 'before' );
	if ( empty( $before ) || ! is_array( $before ) ) {
		return;
	}
	$code = implode( "\n", array_filter( $before, 'is_string' ) );
	if ( strlen( $code ) < 20000 ) {
		return; // Rien de lourd à déplacer.
	}
	$segments = cseo_split_assignments( $code );
	if ( ! $segments ) {
		return;
	}
	$static = '';
	$keep   = array();
	foreach ( $segments as $seg ) {
		if ( $seg['name'] && preg_match( '/^(?:' . CSEO_STATIC_VARS . ')$/', $seg['name'] ) && false === stripos( $seg['code'], 'nonce' ) ) {
			$static .= rtrim( $seg['code'] ) . "\n";
		} else {
			$keep[] = $seg['code'];
		}
	}
	if ( strlen( $static ) < 20000 ) {
		return;
	}
	$url = cseo_write_static_file( $static );
	if ( ! $url ) {
		return; // Écriture impossible : on laisse la page telle quelle.
	}
	$done      = true;
	$in_footer = ! empty( $ws->registered[ $handle ]->extra['group'] );
	wp_register_script( 'cseo-catalogue', $url, array(), null, $in_footer );
	if ( ! in_array( 'cseo-catalogue', $ws->registered[ $handle ]->deps, true ) ) {
		$ws->registered[ $handle ]->deps[] = 'cseo-catalogue';
	}
	$keep = array_values( array_filter( array_map( 'trim', $keep ) ) );
	if ( $keep ) {
		$ws->registered[ $handle ]->extra['before'] = $keep;
	} else {
		unset( $ws->registered[ $handle ]->extra['before'] );
	}
}

/**
 * Découpe « window.A=[…];window.B={…};… » en affectations.
 * Lecture caractère par caractère : on ne coupe qu'aux « ; » situés hors des chaînes et hors des [] {} ().
 */
function cseo_split_assignments( $code ) {
	$out   = array();
	$len   = strlen( $code );
	$depth = 0;
	$in    = false; // Dans une chaîne ?
	$q     = '';
	$start = 0;
	for ( $i = 0; $i < $len; $i++ ) {
		$c = $code[ $i ];
		if ( $in ) {
			if ( '\\' === $c ) {
				$i++;
			} elseif ( $c === $q ) {
				$in = false;
			}
			continue;
		}
		if ( '"' === $c || "'" === $c || '`' === $c ) {
			$in = true;
			$q  = $c;
		} elseif ( '[' === $c || '{' === $c || '(' === $c ) {
			$depth++;
		} elseif ( ']' === $c || '}' === $c || ')' === $c ) {
			$depth--;
		} elseif ( ';' === $c && 0 === $depth ) {
			$out[] = substr( $code, $start, $i - $start + 1 );
			$start = $i + 1;
		}
	}
	if ( $in || 0 !== $depth ) {
		return array(); // Structure inattendue : on ne touche à rien.
	}
	if ( '' !== trim( substr( $code, $start ) ) ) {
		$out[] = substr( $code, $start );
	}
	$res = array();
	foreach ( $out as $stmt ) {
		$stmt = trim( $stmt );
		if ( '' === $stmt ) {
			continue;
		}
		$name  = preg_match( '/^window\.([A-Za-z_$][\w$]*)\s*=/', $stmt, $m ) ? $m[1] : '';
		$res[] = array( 'name' => $name, 'code' => $stmt );
	}
	return $res;
}

/** Écrit le fichier statique (une fois par version du catalogue) et renvoie son URL. */
function cseo_write_static_file( $js ) {
	$hash = substr( md5( $js ), 0, 12 );
	$opt  = get_option( 'cseo_catalogue_file' );
	if ( is_array( $opt ) && $hash === $opt['hash'] && ! empty( $opt['url'] ) && file_exists( $opt['path'] ) ) {
		return $opt['url'];
	}
	$up = wp_upload_dir( null, false );
	if ( ! empty( $up['error'] ) ) {
		return '';
	}
	$dir = trailingslashit( $up['basedir'] ) . 'comptoir-seo';
	if ( ! wp_mkdir_p( $dir ) ) {
		return '';
	}
	$file = $dir . '/catalogue-' . $hash . '.js';
	if ( ! file_exists( $file ) ) {
		$ok = file_put_contents( $file, "/* Catalogue Le Comptoir des Parfums — généré par Comptoir SEO */\n" . $js, LOCK_EX );
		if ( false === $ok ) {
			return '';
		}
		// On supprime les anciennes versions du catalogue.
		foreach ( (array) glob( $dir . '/catalogue-*.js' ) as $old ) {
			if ( $old && $old !== $file ) {
				@unlink( $old ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			}
		}
	}
	$url = set_url_scheme( trailingslashit( $up['baseurl'] ) . 'comptoir-seo/catalogue-' . $hash . '.js' );
	update_option( 'cseo_catalogue_file', array( 'hash' => $hash, 'url' => $url, 'path' => $file ), false );
	return $url;
}

/* ------------------------------------------------------------------ */
/* 5. Version arabe visible par Google                                 */
/*                                                                    */
/* Le thème traduit l'accueil et les fiches en arabe dans le          */
/* navigateur (?lang=ar), mais leur balise canonique renvoyait vers la */
/* version française : Google ignorait donc l'arabe. On annonce les   */
/* deux langues (hreflang), l'URL ?lang=ar devient sa propre canonique,*/
/* et le titre, la description et la langue de la page sont en arabe   */
/* dès le serveur. Seulement là où la traduction est complète :       */
/* l'accueil et les fiches parfum.                                     */
/* ------------------------------------------------------------------ */

function cseo_langues( $html ) {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( '/' !== $path ) {
		return $html; // Accueil et fiches (?parfum=) uniquement.
	}
	if ( ! preg_match( '#<link\b[^>]*\brel=["\']canonical["\'][^>]*>#i', $html, $tag ) || ! preg_match( '#\bhref=["\']([^"\']+)["\']#i', $tag[0], $h ) ) {
		return $html;
	}
	$fr = remove_query_arg( 'lang', html_entity_decode( $h[1], ENT_QUOTES, 'UTF-8' ) );
	$ar = add_query_arg( 'lang', 'ar', $fr );
	$en_arabe = isset( $_GET['lang'] ) && 'ar' === $_GET['lang']; // phpcs:ignore WordPress.Security.NonceVerification

	$links = '<link rel="alternate" hreflang="fr" href="' . esc_url( $fr ) . '" />' . "\n"
		. '<link rel="alternate" hreflang="ar" href="' . esc_url( $ar ) . '" />' . "\n"
		. '<link rel="alternate" hreflang="x-default" href="' . esc_url( $fr ) . '" />' . "\n";

	if ( $en_arabe ) {
		// Canonique de la page arabe = elle-même.
		$html = str_replace( $tag[0], '<link rel="canonical" href="' . esc_url( $ar ) . '" />', $html );
		// Langue et sens de lecture dès le serveur.
		$html = preg_replace( '#<html\b([^>]*)\blang=["\'][^"\']*["\']#i', '<html$1lang="ar" dir="rtl"', $html, 1 );
		// Titre et description en arabe.
		list( $titre, $desc ) = cseo_textes_arabes();
		if ( $titre ) {
			$html = preg_replace( '#<title>[^<]*</title>#i', '<title>' . esc_html( $titre ) . '</title>', $html, 1 );
			$html = preg_replace( '#(<meta\b[^>]*property=["\']og:title["\'][^>]*content=["\'])[^"\']*#i', '${1}' . esc_attr( $titre ), $html, 1 );
		}
		if ( $desc ) {
			$html = preg_replace( '#(<meta\b[^>]*name=["\']description["\'][^>]*content=["\'])[^"\']*#i', '${1}' . esc_attr( $desc ), $html, 1 );
			$html = preg_replace( '#(<meta\b[^>]*property=["\']og:description["\'][^>]*content=["\'])[^"\']*#i', '${1}' . esc_attr( $desc ), $html, 1 );
		}
		$html = preg_replace( '#(<meta\b[^>]*property=["\']og:url["\'][^>]*content=["\'])[^"\']*#i', '${1}' . esc_url( $ar ), $html, 1 );
		$html = preg_replace( '#(<meta\b[^>]*property=["\']og:locale["\'][^>]*content=["\'])[^"\']*#i', '${1}ar_MA', $html, 1 );
	}
	return preg_replace( '#</head>#i', $links . '</head>', $html, 1 );
}

/**
 * Sur ordinateur, le thème chargeait three.js (≈ 600 Ko) et vitrine.js avec document.write dans
 * l'en-tête : la page restait blanche tant qu'ils n'étaient pas téléchargés. Les deux ne servent
 * qu'après l'événement « load » (flacon 3D et anneau de flacons) : on les charge sans bloquer
 * l'affichage, dans le même ordre, et ils sont toujours prêts avant « load ».
 */
function cseo_vitrine_async( $html ) {
	if ( ! apply_filters( 'cseo_vitrine_async', true ) || ! preg_match( '#(<script\b[^>]*id=["\']cp-vitrine-chargeur-js-after["\'][^>]*>)(.*?)(</script>)#is', $html, $m ) ) {
		return $html;
	}
	if ( false === strpos( $m[2], 'document.write' ) ) {
		return $html;
	}
	$plain = str_replace( '\/', '/', $m[2] );
	if ( ! preg_match_all( '#https?://[^"\'\s\\\\<>]+#', $plain, $u ) ) {
		return $html;
	}
	$u[0] = array_values( array_filter( $u[0], function ( $x ) {
		return (bool) preg_match( '#\.js(?:\?[\w.=&-]*)?$#', $x );
	} ) );
	if ( ! $u[0] ) {
		return $html;
	}
	$js = 'if(window.matchMedia&&matchMedia("(min-width:901px)").matches){';
	foreach ( array_unique( $u[0] ) as $url ) {
		$js .= '(function(){var s=document.createElement("script");s.src=' . wp_json_encode( $url ) . ';s.async=false;document.head.appendChild(s);})();';
	}
	$js .= '}';
	return str_replace( $m[0], $m[1] . $js . $m[3], $html );
}

/** Titre et description arabes de la page demandée. */
function cseo_textes_arabes() {
	$livraison = 'توصيل خلال 24 إلى 72 ساعة إلى كل مدن المغرب والدفع عند الاستلام.';
	$slug      = isset( $_GET['parfum'] ) ? strtolower( preg_replace( '/[^A-Za-z0-9-]/', '', (string) wp_unslash( $_GET['parfum'] ) ) ) : ''; // sanitize_title() fusionnerait les « -- » du slug. // phpcs:ignore WordPress.Security.NonceVerification
	if ( '' === $slug ) {
		return array(
			'Le Comptoir des Parfums — تسترات عطور أصلية مع التوصيل لكل المغرب',
			'تسترات أصلية لأكبر دور العطور: ديور، شانيل، إيف سان لوران، توم فورد وغيرها. نفس العطر الذي يباع في المتاجر بدون علبة. ' . $livraison,
		);
	}
	$cat = cseo_catalogue_theme();
	if ( ! $cat ) {
		$cat = get_transient( 'cseo_catalogue' );
	}
	if ( ! is_array( $cat ) ) {
		$cat = get_option( 'cseo_catalogue_backup', array() );
	}
	$p    = is_array( $cat ) && isset( $cat[ $slug ] ) ? $cat[ $slug ] : array();
	$nom  = ! empty( $p['nom'] ) ? $p['nom'] : cseo_nom_depuis_slug( $slug );
	$prix = ! empty( $p['prix'] ) ? (int) $p['prix'] : 0;
	return array(
		// Le prix d'abord, comme le titre français (1.4.0).
		$prix ? $nom . ' – ' . $prix . ' درهم، تستر أصلي في المغرب' : $nom . ' – تستر أصلي بأفضل ثمن في المغرب',
		$nom . ' تستر أصلي' . ( $prix ? ' بـ' . $prix . ' درهم' : '' ) . '، نفس العطر الذي يباع في المتاجر بدون علبة. ' . $livraison,
	);
}

/* ------------------------------------------------------------------ */
/* 6. Accès immédiat : plus d'écran de chargement de 3 secondes        */
/*                                                                    */
/* À chaque nouvelle visite (mémoire de session vide), le thème        */
/* affichait un écran de chargement animé pendant 3,2 s, puis 0,65 s  */
/* de fondu, avant de montrer l'accueil. Le thème sait déjà le sauter  */
/* quand la mémoire « cp_v3 » est posée : on la pose avant son script, */
/* et on masque l'écran dès le premier affichage.                     */
/* Pour le remettre : add_filter( 'cseo_skip_loader', '__return_false' ). */
/* ------------------------------------------------------------------ */

add_action(
	'wp_head',
	function () {
		if ( is_admin() || ! apply_filters( 'cseo_skip_loader', true ) ) {
			return;
		}
		echo "<style id=\"cseo-sans-chargement\">#loader{display:none!important}</style>\n";
		echo "<script id=\"cseo-sans-chargement-js\">try{sessionStorage.setItem('cp_v3','1')}catch(e){}</script>\n";
	},
	1
);

/* ------------------------------------------------------------------ */
/* 7. Photos à la bonne taille (1.3.1)                                 */
/*                                                                    */
/* Les photos du thème (720 × 900 px, preuves en JPG 900 px) étaient   */
/* envoyées en taille réelle, même affichées en 150 px sur téléphone.  */
/* On ajoute un srcset servi par le CDN d'images de WordPress.com      */
/* (i0.wp.com, WebP automatique) : le navigateur prend la taille utile.*/
/* Le src d'origine ne change pas (Google, plan du site, scripts).     */
/* ------------------------------------------------------------------ */

function cseo_opt( $name ) {
	$o = get_option( 'cseo_opts', array() );
	return ! is_array( $o ) || ! isset( $o[ $name ] ) ? true : (bool) $o[ $name ];
}

/** Largeur affichée de chaque type de photo (mesurée sur téléphone, tablette et ordinateur). */
function cseo_img_sizes( $class, $rel ) {
	$regles = array(
		'pc-img'         => '(max-width: 600px) 42vw, 240px',
		'scent-card-img' => '(max-width: 600px) 80vw, (max-width: 900px) 86vw, 320px',
		'pf-sib-img'     => '(max-width: 600px) 38vw, (max-width: 900px) 44vw, 240px',
		'cpb-pack-img'   => '170px',
	);
	foreach ( $regles as $cl => $s ) {
		if ( false !== strpos( $class, ' ' . $cl . ' ' ) ) {
			return $s;
		}
	}
	if ( 0 === strpos( $rel, 'preuves/' ) ) {
		return '(max-width: 600px) 100vw, (max-width: 900px) 420px, 300px';
	}
	return (string) apply_filters( 'cseo_img_sizes', '', $class, $rel );
}

function cseo_images_legeres( $html ) {
	if ( ! apply_filters( 'cseo_images_cdn', cseo_opt( 'images' ) ) || false === stripos( $html, '<img' ) ) {
		return $html;
	}
	$base_url = trailingslashit( get_stylesheet_directory_uri() ) . 'img/';
	$base_dir = trailingslashit( get_stylesheet_directory() ) . 'img/';
	$rel_path = (string) wp_parse_url( $base_url, PHP_URL_PATH );
	$site     = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	if ( '' === $rel_path || '' === $site || false === strpos( $html, $rel_path ) ) {
		return $html;
	}
	$dims    = get_transient( 'cseo_imgdims' );
	$dims    = is_array( $dims ) ? $dims : array();
	$changed = false;

	$new = preg_replace_callback(
		'#<img\b[^>]*>#i',
		function ( $m ) use ( $rel_path, $base_dir, $site, &$dims, &$changed ) {
			$tag = $m[0];
			if ( preg_match( '#\s(?:srcset|data-no-cdn)\s*=#i', $tag ) || ! preg_match( '#\ssrc\s*=\s*"([^"]+)"#i', $tag, $s ) ) {
				return $tag;
			}
			$src  = html_entity_decode( $s[1], ENT_QUOTES, 'UTF-8' );
			$path = (string) wp_parse_url( $src, PHP_URL_PATH );
			$host = strtolower( (string) wp_parse_url( $src, PHP_URL_HOST ) );
			if ( 0 !== strpos( $path, $rel_path ) || ( '' !== $host && $host !== $site ) ) {
				return $tag;
			}
			$rel = substr( $path, strlen( $rel_path ) );
			if ( ! preg_match( '#^[a-z0-9/_.-]+\.(?:webp|jpe?g|png)$#i', $rel ) || false !== strpos( $rel, '..' ) ) {
				return $tag;
			}
			// Photos produit : pas de CDN (1.4.1). i0.wp.com garde une photo
			// d'apres son chemin et ignore le ?v= de version : une photo
			// remplacee restait l'ancienne sur les telephones, sans limite de
			// temps. Servies telles quelles (720 px, WebP), elles changent des
			// que le theme est mis a jour. Les preuves clients gardent le CDN.
			if ( 0 === strpos( $rel, 'produits/' ) ) {
				return $tag;
			}
			$class = preg_match( '#\sclass\s*=\s*"([^"]*)"#i', $tag, $c ) ? ' ' . $c[1] . ' ' : '';
			$sizes = cseo_img_sizes( $class, $rel );
			if ( '' === $sizes ) {
				return $tag;
			}
			if ( ! isset( $dims[ $rel ] ) ) {
				$f             = $base_dir . $rel;
				$wh            = is_readable( $f ) ? @getimagesize( $f ) : false; // phpcs:ignore
				$dims[ $rel ]  = $wh ? (int) $wh[0] : 0;
				$changed       = true;
			}
			$ow = (int) $dims[ $rel ];
			if ( $ow < 200 ) {
				return $tag;
			}
			$q      = (string) wp_parse_url( $src, PHP_URL_QUERY );
			$photon = 'https://i0.wp.com/' . $site . $path . '?' . ( '' !== $q ? $q . '&' : '' );
			$set    = array();
			foreach ( array( 160, 240, 320, 480, 640, 800 ) as $w ) {
				if ( $w < $ow - 40 ) {
					$set[] = $photon . 'w=' . $w . '&ssl=1 ' . $w . 'w';
				}
			}
			$set[] = $photon . 'w=' . $ow . '&ssl=1 ' . $ow . 'w';
			$attr  = ' srcset="' . esc_attr( implode( ', ', $set ) ) . '" sizes="' . esc_attr( $sizes ) . '"';
			return preg_replace( '#\s*(/?)>$#', $attr . '$1>', $tag, 1 );
		},
		$html
	);
	if ( $changed ) {
		set_transient( 'cseo_imgdims', $dims, DAY_IN_SECONDS );
	}
	return is_string( $new ) ? $new : $html;
}

/* ------------------------------------------------------------------ */
/* 8. Pixel Meta et Clarity chargés juste après l'affichage (1.3.1)    */
/*                                                                    */
/* Ces deux outils prenaient ~0,5 s de calcul pendant l'ouverture de   */
/* la page sur téléphone. Leur code est gardé tel quel ; seul le       */
/* téléchargement de leur fichier attend le premier geste du visiteur  */
/* (toucher, défilement, clic) ou 1,5 s après le chargement. Les       */
/* événements (PageView, ajout au panier…) sont mis en file d'attente  */
/* par Meta et envoyés dès que le fichier arrive : rien n'est perdu.   */
/* 1.3.2 : le fichier est ajouté dans <head> (le thème retire certains */
/* de ses scripts, et le pixel Meta visait l'un d'eux).               */
/* ------------------------------------------------------------------ */

function cseo_traceurs_plus_tard( $html ) {
	if ( ! apply_filters( 'cseo_delay_trackers', cseo_opt( 'traceurs' ) ) ) {
		return $html;
	}
	if ( false === strpos( $html, 'connect.facebook.net' ) && false === strpos( $html, 'clarity.ms/tag' ) ) {
		return $html;
	}
	$new = preg_replace_callback(
		'#(<script\b[^>]*>)(.*?)(</script>)#is',
		function ( $m ) {
			$code = $m[2];
			if ( false !== stripos( $m[1], ' src=' ) || false !== strpos( $code, 'cseoPlusTard' ) ) {
				return $m[0];
			}
			if ( false === strpos( $code, 'connect.facebook.net' ) && false === strpos( $code, 'clarity.ms/tag' ) ) {
				return $m[0];
			}
			$code2 = preg_replace(
				'#\b([a-z])\.parentNode\.insertBefore\(\s*([a-z])\s*,\s*\1\s*\)#',
				'(window.cseoPlusTard||function(f){f()})(function(){(document.head||document.documentElement).appendChild($2)})',
				$code,
				1
			);
			return $m[1] . ( is_string( $code2 ) ? $code2 : $code ) . $m[3];
		},
		$html
	);
	return is_string( $new ) ? $new : $html;
}

add_action(
	'wp_head',
	function () {
		if ( is_admin() ) {
			return;
		}
		if ( apply_filters( 'cseo_images_cdn', cseo_opt( 'images' ) ) ) {
			echo "<link rel=\"dns-prefetch\" href=\"//i0.wp.com\">\n";
		}
		if ( apply_filters( 'cseo_delay_trackers', cseo_opt( 'traceurs' ) ) ) {
			echo "<script id=\"cseo-plus-tard\">(function(w){var q=[],ok=0,ev=['pointerdown','keydown','touchstart','scroll','wheel','mousemove'];function go(){if(ok)return;ok=1;ev.forEach(function(e){w.removeEventListener(e,go,true)});var f;while((f=q.shift())){try{f()}catch(x){}}}ev.forEach(function(e){w.addEventListener(e,go,{capture:true,passive:true})});w.addEventListener('load',function(){setTimeout(go,1500)});w.cseoPlusTard=function(f){ok?f():q.push(f)};})(window);</script>\n";
		}
	},
	2
);

// Galerie Jetpack (carrousel, mosaïque) inutile sur l'accueil et les fiches parfum : on ne la charge pas.
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_front_page() || ! apply_filters( 'cseo_trim_scripts', true ) ) {
			return;
		}
		foreach ( array( 'jetpack-carousel', 'tiled-gallery' ) as $h ) {
			wp_dequeue_script( $h );
		}
		foreach ( array( 'jetpack-carousel', 'tiled-gallery', 'jetpack-carousel-swiper-css' ) as $h ) {
			wp_dequeue_style( $h );
		}
	},
	100
);

/* ------------------------------------------------------------------ */
/* Réglage : page d'information + bouton pour rafraîchir le catalogue  */
/* ------------------------------------------------------------------ */

add_action(
	'admin_menu',
	function () {
		add_options_page( 'Comptoir SEO', 'Comptoir SEO', 'manage_options', 'comptoir-seo', 'cseo_page' );
	}
);

add_action(
	'admin_post_cseo_refresh',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Accès refusé.' );
		}
		check_admin_referer( 'cseo_refresh' );
		$cat = cseo_catalogue( true );
		set_transient( 'cseo_msg', count( $cat ) . ' fiches parfum trouvées.', 60 );
		wp_safe_redirect( admin_url( 'options-general.php?page=comptoir-seo' ) );
		exit;
	}
);

add_action(
	'admin_post_cseo_opts',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Accès refusé.' );
		}
		check_admin_referer( 'cseo_opts' );
		update_option(
			'cseo_opts',
			array(
				'images'   => ! empty( $_POST['cseo_images'] ),
				'traceurs' => ! empty( $_POST['cseo_traceurs'] ),
			),
			false
		);
		set_transient( 'cseo_msg', 'Réglages enregistrés.', 60 );
		wp_safe_redirect( admin_url( 'options-general.php?page=comptoir-seo' ) );
		exit;
	}
);

function cseo_page() {
	$cat = cseo_catalogue();
	$url = home_url( '/' . CSEO_SITEMAP );
	echo '<div class="wrap"><h1>Comptoir SEO</h1>';
	$msg = get_transient( 'cseo_msg' );
	if ( $msg ) {
		delete_transient( 'cseo_msg' );
		echo '<div class="notice notice-success"><p>' . esc_html( $msg ) . '</p></div>';
	}
	echo '<table class="widefat striped" style="max-width:800px"><tbody>';
	echo '<tr><td><strong>Plan du site des fiches parfum</strong></td><td><a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $url ) . '</a><br><span class="description">' . (int) count( $cat ) . ' fiches. À ajouter une fois dans Google Search Console › Sitemaps.</span></td></tr>';
	echo '<tr><td><strong>Textes alternatifs</strong></td><td>Ajoutés automatiquement aux photos produits qui n\'en ont pas : « Testeur Dior Sauvage … – parfum original ».</td></tr>';
	$f = get_option( 'cseo_catalogue_file' );
	echo '<tr><td><strong>Pages allégées</strong></td><td>' . ( is_array( $f ) ? 'Catalogue servi depuis un fichier en cache : <code>' . esc_html( basename( $f['path'] ) ) . '</code>' : 'Le fichier du catalogue sera créé à la prochaine visite du site.' ) . '</td></tr>';
	echo '<tr><td><strong>Version arabe</strong></td><td>Accueil et fiches parfum annoncés à Google en français et en arabe (?lang=ar), avec titre et description en arabe.</td></tr>';
	echo '<tr><td><strong>Accès immédiat</strong></td><td>L\'écran de chargement de 3 secondes est supprimé ; la galerie Jetpack n\'est plus chargée sur l\'accueil et les fiches.</td></tr>';
	echo '<tr><td><strong>Titres</strong></td><td>Fiches : « Nom du parfum – Testeur original, prix au Maroc ». Pages maisons et familles : « Parfums Dior au Maroc : testeurs originaux ».</td></tr>';
	echo '</tbody></table>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:16px">';
	wp_nonce_field( 'cseo_refresh' );
	echo '<input type="hidden" name="action" value="cseo_refresh">';
	submit_button( 'Relire le catalogue maintenant', 'secondary', 'go', false );
	echo ' <span class="description">Le catalogue est relu tout seul toutes les 12 heures (nouveaux parfums compris).</span></form>';

	echo '<h2 style="margin-top:28px">Vitesse</h2>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'cseo_opts' );
	echo '<input type="hidden" name="action" value="cseo_opts">';
	echo '<p><label><input type="checkbox" name="cseo_images" value="1"' . checked( cseo_opt( 'images' ), true, false ) . '> <strong>Photos à la bonne taille</strong> — chaque téléphone reçoit la photo à sa taille, en WebP, via le CDN d\'images de WordPress.com.</label></p>';
	echo '<p><label><input type="checkbox" name="cseo_traceurs" value="1"' . checked( cseo_opt( 'traceurs' ), true, false ) . '> <strong>Pixel Meta et Clarity après l\'affichage</strong> — chargés au premier geste du visiteur ou 1,5 s après l\'ouverture ; les événements sont gardés en attente puis envoyés.</label></p>';
	submit_button( 'Enregistrer', 'primary', 'go2', false );
	echo '</form></div>';
}
