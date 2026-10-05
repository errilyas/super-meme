<?php
/**
 * Le Comptoir des Parfums — functions.php
 *
 * Reglages du theme, routage des gabarits sur-mesure (fiche parfum, page
 * commande), chargement des scripts et generation des metadonnees.
 *
 * Le design est celui de la maquette comptoirv3-motion.html : voir header.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Donnees produit partagees : catalogue + fiches parfum.
require_once get_template_directory() . '/produits.php';
// Confirmation des commandes sur WhatsApp (API officielle Meta), inactive tant qu'elle n'est pas reglee.
require_once get_template_directory() . '/inc/whatsapp.php';

/* ══════════════════════════════════════════════════════════════
   REGLAGES DU COMMERCE
   Les deux seules valeurs a changer au quotidien.
══════════════════════════════════════════════════════════════ */

/** Numero WhatsApp, format international sans « + ». */
function comptoir_wa_numero() {
	return '212717961180';
}

/** Frais de livraison en DH. Mettre 0 pour afficher « a confirmer ». */
function comptoir_livraison_dh() {
	return 35;
}

/**
 * Livraison offerte a partir de N articles. 0 desactive la regle.
 * Reglee a 2 : le deuxieme flacon paie sa propre livraison, et fait monter
 * le panier moyen sans un dirham de publicite.
 */
function comptoir_franco_articles() {
	return 2;
}

/**
 * Empreinte de version des photos, ajoutee a chaque adresse d'image.
 *
 * Les fichiers gardent leur nom d'une version du theme a l'autre : sans cela,
 * un visiteur deja venu continue de voir les anciennes photos, en cache dans
 * son navigateur, pendant des jours. C'est ce qui est arrive au detourage.
 * La date du fichier functions.php change a chaque televersement : elle fait
 * une empreinte fiable et gratuite.
 */
function comptoir_version_photos() {
	static $v = null;
	if ( null === $v ) {
		$v = (string) @filemtime( __FILE__ );
	}
	return $v;
}

/** L'adresse d'une photo produit, empreinte de version comprise. */
function comptoir_photo_url( $slug ) {
	return get_template_directory_uri() . '/img/produits/' . $slug . '.webp?v=' . comptoir_version_photos();
}

/** La phrase des frais de livraison, ecrite une fois pour toutes les pages. */
function comptoir_note_livraison() {
	$frais  = comptoir_livraison_dh();
	$franco = comptoir_franco_articles();
	if ( ! $frais ) {
		return 'Frais de livraison confirmés à la commande.';
	}
	if ( $franco > 1 ) {
		return sprintf(
			'%s DH de livraison, partout — offerte dès le deuxième parfum.',
			comptoir_prix_format( $frais )
		);
	}
	return comptoir_prix_format( $frais ) . ' DH de livraison, partout.';
}

/**
 * La relance « livraison offerte des le deuxieme parfum », posee LA OU LE
 * CLIENT CHOISIT et pas seulement dans le panier : quand il ouvre le panier,
 * il a deja decide combien de flacons il prend. C'est en parcourant les 177
 * fiches qu'un deuxieme flacon se decide.
 *
 * Muette si la regle est desactivee : aucune promesse ecrite en dur, elle
 * suit comptoir_franco_articles() et comptoir_livraison_dh() comme le panier.
 */
function comptoir_note_franco( $classe = '' ) {
	$franco = comptoir_franco_articles();
	$frais  = comptoir_livraison_dh();
	if ( $franco < 2 || ! $frais ) {
		return;
	}
	printf(
		'<p class="franco-band%s">'
		. '<svg viewBox="0 0 24 24" aria-hidden="true">'
		. '<path d="M1.5 16.5V6.5h12v10M13.5 9.5h4l3 3.5v3.5h-7"/>'
		. '<circle cx="6" cy="17.5" r="2"/><circle cx="17" cy="17.5" r="2"/></svg>'
		. '<span><b>Livraison offerte dès le deuxième parfum.</b> '
		. 'Sinon %s DH, partout au Maroc.</span></p>',
		$classe ? ' ' . esc_attr( $classe ) : '',
		esc_html( comptoir_prix_format( $frais ) )
	);
}

/**
 * Adresse du script Google qui recoit les commandes dans une feuille.
 * Vide = pas d'envoi. Le code du script est dans tools/feuille-commandes.gs,
 * et la marche a suivre dans le README.
 *
 * La commande part aux DEUX endroits : le registre WordPress et la feuille.
 * L'un se consulte depuis l'admin, l'autre depuis un telephone, et deux
 * copies valent mieux qu'une quand c'est le carnet de commandes.
 */
function comptoir_feuille_google() {
	return 'https://script.google.com/macros/s/AKfycbyFzwj_Su7A2M2liMBpW9qd7WeZhlIHPSzsII_-XX9hgKE6pPWPF35l9EXt7sJgTfo35w/exec';
}

/** Numero de telephone, format national. Vide = pas de lien telephone. */
function comptoir_telephone() {
	return '0717961180';
}

/** Le meme, en lien tel: international. */
function comptoir_telephone_href() {
	$n = preg_replace( '/\D/', '', comptoir_telephone() );
	if ( '' === $n ) {
		return '';
	}
	return 'tel:+212' . ltrim( $n, '0' );
}

/** Et en version lisible : 0717 961 180. */
function comptoir_telephone_affiche() {
	$n = preg_replace( '/\D/', '', comptoir_telephone() );
	if ( 10 !== strlen( $n ) ) {
		return comptoir_telephone();
	}
	// 4-3-3, comme on ecrit un numero au Maroc. chunk_split() par 4 donnait
	// « 0717 9611 80 » : un decoupage que personne ne lit, et qu'il faut
	// relire deux fois pour composer.
	return substr( $n, 0, 4 ) . ' ' . substr( $n, 4, 3 ) . ' ' . substr( $n, 7 );
}

/**
 * Identifiants de mesure. Une valeur vide = la balise n'est pas posee.
 *
 * Le Pixel Meta n'est pas ici : il est deja injecte par WordPress.com, et le
 * theme se contente d'accrocher ses evenements au fbq qui en decoule. Son
 * double cote serveur (Conversions API) se regle a part, dans comptoir_capi()
 * juste en dessous.
 *   ga4     : identifiant de flux Google Analytics 4, de la forme G-XXXXXXXXXX
 *   clarity : identifiant de projet Microsoft Clarity, 10 caracteres
 */
function comptoir_mesure() {
	return array(
		'ga4'     => '',
		'clarity' => 'yhsizn6qv8',
	);
}

/**
 * Reglages Meta Conversions API (CAPI). Vide = aucun envoi, comme les autres
 * identifiants ci-dessus.
 *
 * C'est le doublon serveur du Pixel, pour l'achat que le pixel seul rate
 * souvent : bloqueur de pub, Safari qui coupe les cookies tiers, ou l'appli
 * WhatsApp qui tue l'onglet avant que fbq n'ait fini de partir. WordPress
 * renvoie le meme achat depuis le serveur, avec la meme reference de
 * commande comme event_id (voir comptoir_capi_achat) : Meta reconnait le
 * doublon et ne le compte qu'une fois.
 *
 *   pixel_id : identifiant du Pixel deja pose par WordPress.com — Gestionnaire
 *              d'evenements Meta > cette source de donnees, en haut de la page.
 *   token    : ne vit plus dans le code. La constante COMPTOIR_CAPI_TOKEN de
 *              wp-config.php si le site en definit une, sinon l'option posee
 *              depuis Reglages > Suivi Meta. Un fichier de theme se lit trop
 *              facilement — depot git, sauvegarde, export — et ce jeton
 *              autorise a ecrire des evenements sur le Pixel.
 *   test     : le code affiche pendant un test dans l'onglet « Tester les
 *              evenements », pose au meme endroit et retire apres coup.
 */
function comptoir_capi() {
	return array(
		'pixel_id' => '1405784658130035',
		'token'    => comptoir_capi_jeton(),
		'test'     => (string) get_option( 'comptoir_capi_test', '' ),
	);
}

/**
 * Le jeton d'acces CAPI, par ordre de preference : la constante si elle
 * existe, sinon l'option de la base. Vide = comptoir_capi_achat() ressort
 * sans rien envoyer, exactement comme avant que la CAPI soit activee.
 */
function comptoir_capi_jeton() {
	if ( defined( 'COMPTOIR_CAPI_TOKEN' ) && COMPTOIR_CAPI_TOKEN ) {
		return (string) COMPTOIR_CAPI_TOKEN;
	}
	return (string) get_option( 'comptoir_capi_token', '' );
}

/**
 * Le numero deja valide par comptoir_recoit_commande(), au format que Meta
 * demande pour le hachage : indicatif pays sans + ni zero initial.
 */
function comptoir_capi_tel_e164( $tel ) {
	$n = preg_replace( '/[^0-9]/', '', (string) $tel );
	if ( 0 === strpos( $n, '00212' ) ) {
		$n = substr( $n, 2 );
	} elseif ( 0 === strpos( $n, '0' ) && 0 !== strpos( $n, '212' ) ) {
		$n = '212' . substr( $n, 1 );
	}
	return $n;
}

/* ══════════════════════════════════════════════════════════════
   REGLAGES DU SUIVI META
   Le jeton d'acces vit en base, pas dans le fichier du theme : un theme
   se copie, se sauvegarde et se versionne, un jeton ne devrait pas suivre.
══════════════════════════════════════════════════════════════ */

add_action( 'admin_menu', function () {
	add_options_page(
		'Suivi Meta',
		'Suivi Meta',
		'manage_options',
		'comptoir-suivi-meta',
		'comptoir_page_suivi_meta'
	);
} );

/**
 * Un ecran, deux champs. Le jeton n'est jamais reaffiche : on dit seulement
 * s'il est pose, et un champ laisse vide ne l'ecrase pas.
 */
function comptoir_page_suivi_meta() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$avis = '';

	if ( isset( $_POST['comptoir_suivi_meta'] ) ) {
		check_admin_referer( 'comptoir_suivi_meta' );

		if ( isset( $_POST['oublier'] ) ) {
			delete_option( 'comptoir_capi_token' );
			$avis = 'Jeton efface. La CAPI n\'envoie plus rien tant qu\'un nouveau n\'est pas pose.';
		} else {
			$jeton = isset( $_POST['jeton'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['jeton'] ) ) ) : '';
			if ( $jeton ) {
				update_option( 'comptoir_capi_token', $jeton, false );
			}
			$test = isset( $_POST['test'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['test'] ) ) ) : '';
			if ( $test ) {
				update_option( 'comptoir_capi_test', $test, false );
			} else {
				delete_option( 'comptoir_capi_test' );
			}
			$avis = 'Reglages enregistres.';
		}
	}

	$constante = defined( 'COMPTOIR_CAPI_TOKEN' ) && COMPTOIR_CAPI_TOKEN;
	$pose      = '' !== (string) get_option( 'comptoir_capi_token', '' );
	$test      = (string) get_option( 'comptoir_capi_test', '' );

	echo '<div class="wrap"><h1>Suivi Meta</h1>';

	if ( $avis ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $avis ) . '</p></div>';
	}

	if ( $constante ) {
		echo '<div class="notice notice-info"><p>Le jeton vient de la constante <code>COMPTOIR_CAPI_TOKEN</code> de wp-config.php, qui passe avant ce qui est enregistre ici.</p></div>';
	} elseif ( $pose ) {
		echo '<p>Un jeton est enregistre. Il n\'est jamais reaffiche : laissez le champ vide pour le conserver.</p>';
	} else {
		echo '<div class="notice notice-warning"><p>Aucun jeton : la Conversions API n\'envoie rien. Seul le Pixel navigateur mesure, et il rate les achats bloques ou partis avec l\'onglet.</p></div>';
	}

	echo '<form method="post">';
	wp_nonce_field( 'comptoir_suivi_meta' );
	echo '<input type="hidden" name="comptoir_suivi_meta" value="1">';
	echo '<table class="form-table" role="presentation"><tbody>';

	echo '<tr><th scope="row"><label for="cp-jeton">Jeton d\'acces</label></th><td>';
	echo '<input type="password" id="cp-jeton" name="jeton" value="" class="large-text" autocomplete="off" spellcheck="false">';
	echo '<p class="description">Gestionnaire d\'evenements &gt; votre source de donnees &gt; onglet API Conversions &gt; Generer un jeton d\'acces.</p>';
	echo '</td></tr>';

	echo '<tr><th scope="row"><label for="cp-test">Code de test</label></th><td>';
	echo '<input type="text" id="cp-test" name="test" value="' . esc_attr( $test ) . '" class="regular-text" autocomplete="off" spellcheck="false">';
	echo '<p class="description">Onglet Tester les evenements. A retirer une fois la verification faite, sinon les achats restent dans le test et ne comptent pas.</p>';
	echo '</td></tr>';

	echo '</tbody></table>';
	submit_button( 'Enregistrer' );

	if ( $pose && ! $constante ) {
		echo '<p><button type="submit" name="oublier" value="1" class="button button-link-delete">Effacer le jeton enregistre</button></p>';
	}

	echo '</form></div>';
}

/* ══════════════════════════════════════════════════════════════
   MISE EN PLACE DU THEME
══════════════════════════════════════════════════════════════ */

function comptoir_parfums_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );

	register_nav_menus( array(
		'primary' => __( 'Menu principal', 'comptoir-parfums' ),
	) );
}
add_action( 'after_setup_theme', 'comptoir_parfums_setup' );

/* ══════════════════════════════════════════════════════════════
   ROUTAGE DES GABARITS SUR-MESURE
   Aucune page a creer dans l'admin : une URL qui porte ?parfum= ou
   ?commander= sert directement le bon gabarit.
══════════════════════════════════════════════════════════════ */

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'parfum';
	$vars[] = 'commander';
	$vars[] = 'maison';
	return $vars;
} );

/**
 * Slug demande via ?parfum=<slug>.
 * Les slugs sont du type « maison--reference » : on garde le double tiret,
 * donc pas de sanitize_title() qui le reduirait a un seul.
 */
function comptoir_parfum_slug_demande() {
	$slug = get_query_var( 'parfum' );
	if ( '' === $slug && isset( $_GET['parfum'] ) ) {
		$slug = wp_unslash( $_GET['parfum'] );
	}
	return is_scalar( $slug ) ? preg_replace( '/[^a-z0-9-]/', '', strtolower( (string) $slug ) ) : '';
}

/** Vrai sur /?commander=1. */
function comptoir_commander_demande() {
	return (bool) get_query_var( 'commander' ) || isset( $_GET['commander'] );
}

/**
 * Vrai sur /?vente=1 — la landing page dediee au trafic publicitaire.
 * Meme mecanique que commander/parfum : un query var, un gabarit, jamais de
 * page WordPress a creer ou a perdre en cas de reinstallation du theme.
 */
function comptoir_vente_demande() {
	return (bool) get_query_var( 'vente' ) || isset( $_GET['vente'] );
}

/**
 * Vrai sur la page d'accueil « pleine » : celle qui merite le prechargeur,
 * le curseur sur-mesure et le menu mobile. Une fiche parfum ou la page
 * commande n'en veulent pas, meme si elles sont servies depuis l'accueil.
 */
function comptoir_est_accueil() {
	if ( comptoir_parfum_slug_demande() || comptoir_commander_demande() || comptoir_vente_demande() ) {
		return false;
	}
	return is_front_page();
}

// Aiguille vers le gabarit fiche parfum, page commande OU landing page vente.
add_filter( 'template_include', function ( $template ) {
	if ( comptoir_parfum_slug_demande() ) {
		$t = locate_template( 'template-parfum.php' );
		if ( $t ) {
			return $t;
		}
	}
	if ( comptoir_commander_demande() ) {
		$t = locate_template( 'template-commander.php' );
		if ( $t ) {
			return $t;
		}
	}
	if ( comptoir_vente_demande() ) {
		$t = locate_template( 'template-vente.php' );
		if ( $t ) {
			return $t;
		}
	}
	return $template;
}, 99 );

/**
 * Statut HTTP des gabarits sur-mesure.
 *
 * Un slug inconnu doit repondre 404, sinon Google indexe autant de pages
 * « Parfum introuvable » qu'il existe d'URL fantaisistes. Un slug valide,
 * lui, doit repondre 200 meme si WordPress avait prevu un 404 (il n'existe
 * aucun contenu derriere ?parfum=).
 */
add_action( 'template_redirect', function () {
	$slug = comptoir_parfum_slug_demande();
	if ( $slug ) {
		if ( comptoir_produit_by_slug( $slug ) ) {
			status_header( 200 );
			global $wp_query;
			$wp_query->is_404 = false;
		} else {
			status_header( 404 );
			nocache_headers();
		}
		return;
	}
	if ( comptoir_commander_demande() || comptoir_vente_demande() ) {
		status_header( 200 );
		global $wp_query;
		$wp_query->is_404 = false;
	}
}, 1 );

/*
 * La bulle « Demander a l'IA » et le bouton flottant qui l'accompagne ne
 * viennent pas du theme : un module « brand-agent » que WordPress.com pose
 * sur le site (script obscurci, heberge chez un tiers, qui coupe la console).
 * Ils recouvraient les boutons d'achat sur telephone, jusqu'au message de
 * livraison offerte du panier. Retires ici, tant qu'ils ne sont pas
 * desactives a la source.
 */
function comptoir_retire_bulle_ia() {
	if ( function_exists( 'wp_dequeue_script_module' ) ) {
		wp_dequeue_script_module( 'brand-agent-frontend' );
	}
	wp_dequeue_script( 'brand-agent-frontend' );
}
add_action( 'wp_enqueue_scripts', 'comptoir_retire_bulle_ia', 999 );
add_action( 'wp_footer', 'comptoir_retire_bulle_ia', 1 );

// Classes <body> — servent au CSS et aux scripts pour reconnaitre le gabarit.
add_filter( 'body_class', function ( $classes ) {
	if ( comptoir_parfum_slug_demande() || comptoir_vente_demande() ) {
		$classes[] = 'tpl-parfum';   // meme effet : pas d'intro, pas de vitrine 3D
	}
	if ( comptoir_vente_demande() ) {
		$classes[] = 'tpl-vente';
	}
	if ( comptoir_commander_demande() ) {
		$classes[] = 'tpl-parfum';   // meme effet : pas d'intro
		$classes[] = 'tpl-commander';
	} else {
		// La barre d'achat fixe sort sur toutes les pages sauf la commande :
		// elle y pointerait vers la page qu'on est deja en train de remplir.
		// La landing page vente la garde : c'est exactement la ou elle sert.
		$classes[] = 'a-cta-fixe';
	}
	return $classes;
} );

/**
 * Balises de mesure dans <head>. Rien n'est imprime tant que comptoir_mesure()
 * ne porte pas d'identifiant : pas de requete, pas de bandeau, pas de dette.
 */
add_action( 'wp_head', function () {
	$m = comptoir_mesure();

	if ( ! empty( $m['ga4'] ) ) {
		$id = preg_replace( '/[^A-Za-z0-9\-]/', '', $m['ga4'] );
		printf(
			'<script async src="https://www.googletagmanager.com/gtag/js?id=%1$s"></script>' .
			'<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}' .
			'gtag("js",new Date());gtag("config","%1$s");</script>',
			esc_attr( $id )
		);
	}

	if ( ! empty( $m['clarity'] ) ) {
		$id = preg_replace( '/[^A-Za-z0-9]/', '', $m['clarity'] );
		printf(
			'<script>(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};' .
			't=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;' .
			'y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window,document,"clarity","script","%s");</script>',
			esc_attr( $id )
		);
	}

	// Le Pixel navigateur : sans lui window.fbq n'existe jamais, et
	// AddToCart / InitiateCheckout / Lead / Purchase dans panier.js et
	// commande.js (mesure()) restent des appels dans le vide, silencieux
	// par conception. C'est aussi lui qui pose les cookies _fbp / _fbc que
	// comptoir_capi_achat() relit pour le doublon serveur — sans lui, ce
	// doublon part avec un appariement plus faible. Meme pixel_id que
	// comptoir_capi(), une seule source pour les deux.
	$capi = comptoir_capi();
	if ( ! empty( $capi['pixel_id'] ) ) {
		$id = preg_replace( '/[^0-9]/', '', $capi['pixel_id'] );
		printf(
			'<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?' .
			'n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;' .
			'n.push=n;n.loaded=!0;n.version="2.0";n.queue=[];t=b.createElement(e);t.async=!0;' .
			't.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}' .
			'(window,document,"script","https://connect.facebook.net/en_US/fbevents.js");' .
			'fbq("init","%1$s");fbq("track","PageView");</script>' .
			'<noscript><img height="1" width="1" style="display:none" alt=""' .
			' src="https://www.facebook.com/tr?id=%1$s&amp;ev=PageView&amp;noscript=1"></noscript>',
			esc_attr( $id )
		);
	}
}, 5 );

/* ══════════════════════════════════════════════════════════════
   REGISTRE DES COMMANDES
   Le tunnel se termine sur WhatsApp : si le client n'appuie pas sur
   « Envoyer », la commande n'a jamais existe et personne ne le sait. On la
   depose donc ici AVANT la bascule, pour pouvoir rappeler.
══════════════════════════════════════════════════════════════ */

add_action( 'init', function () {
	register_post_type( 'cp_commande', array(
		'labels'          => array(
			'name'          => __( 'Commandes', 'comptoir-parfums' ),
			'singular_name' => __( 'Commande', 'comptoir-parfums' ),
			'menu_name'     => __( 'Commandes', 'comptoir-parfums' ),
		),
		'public'          => false,
		'show_ui'         => true,
		'menu_icon'       => 'dashicons-cart',
		'menu_position'   => 26,
		'capability_type' => 'post',
		'map_meta_cap'    => true,
		'supports'        => array( 'title', 'editor' ),
	) );
} );

/**
 * Coupe une chaine a N caracteres sans briser un accent.
 * mb_substr n'est pas garanti sur un hebergement mutualise : meme garde que
 * comptoir_minuscules() plus haut.
 */
function comptoir_coupe( $s, $max ) {
	return function_exists( 'mb_substr' ) ? mb_substr( $s, 0, $max, 'UTF-8' ) : substr( $s, 0, $max );
}

/** Depot d'une commande. Repond toujours en JSON, ne fait jamais echouer la vente. */
function comptoir_recoit_commande() {
	// Garde-fou anti-flood : un visiteur ne depose pas dix commandes a l'heure.
	$cle = 'cp_cmd_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
	$vus = (int) get_transient( $cle );
	if ( $vus >= 10 ) {
		wp_send_json_error( 'trop de depots', 429 );
	}
	set_transient( $cle, $vus + 1, HOUR_IN_SECONDS );

	// Le jeton ne REJETTE pas : sur un hebergement qui met les pages en cache,
	// il arrive perime chez un vrai client, et perdre sa commande couterait
	// plus cher que d'enregistrer une ligne douteuse. Elle est marquee, pas
	// jetee — c'est tout l'interet de ce registre.
	$jeton_ok = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cp_commande' );

	$champ = function ( $cle, $max = 200 ) {
		return isset( $_POST[ $cle ] ) && is_scalar( $_POST[ $cle ] )
			? comptoir_coupe( sanitize_text_field( wp_unslash( $_POST[ $cle ] ) ), $max )
			: '';
	};

	$tel = preg_replace( '/[\s().-]/', '', $champ( 'tel', 40 ) );
	if ( ! preg_match( '/^(?:0|\+?212|00212)[5-7][0-9]{8}$/', $tel ) ) {
		wp_send_json_error( 'telephone', 400 );
	}

	$panier = isset( $_POST['panier'] ) && is_string( $_POST['panier'] )
		? comptoir_coupe( sanitize_textarea_field( wp_unslash( $_POST['panier'] ) ), 2000 )
		: '';

	if ( ! $champ( 'nom', 80 ) || ! $champ( 'ville', 60 ) || strlen( $champ( 'adresse', 400 ) ) < 8 ) {
		wp_send_json_error( 'coordonnees', 400 );
	}
	// Recalcul depuis le catalogue, jamais depuis les montants du navigateur.
	$lignes = null;
	if ( isset( $_POST['lignes'] ) && is_string( $_POST['lignes'] ) ) {
		$lignes = json_decode( wp_unslash( $_POST['lignes'] ), true );
	} else {
		// Compatibilite avec les pages deja en cache avant cette mise a jour.
		$lignes = array();
		foreach ( explode( "\n", $panier ) as $ligne ) {
			if ( preg_match( '/^(\d+)× .*\(([a-z0-9-]+)\) — /u', $ligne, $m ) ) {
				$lignes[] = array( 's' => $m[2], 'q' => (int) $m[1] );
			} else {
				wp_send_json_error( 'panier', 400 );
			}
		}
	}
	if ( ! is_array( $lignes ) || ! count( $lignes ) || count( $lignes ) > count( comptoir_produits() ) ) {
		wp_send_json_error( 'panier', 400 );
	}
	$articles = 0;
	$total = 0;
	$resume = array();
	foreach ( $lignes as $ligne ) {
		if ( ! is_array( $ligne ) || ! isset( $ligne['s'], $ligne['q'] ) || ! is_string( $ligne['s'] ) || ! is_int( $ligne['q'] ) || $ligne['q'] < 1 || $ligne['q'] > 999 ) {
			wp_send_json_error( 'panier', 400 );
		}
		$p = comptoir_produit_by_slug( $ligne['s'] );
		if ( ! $p ) { wp_send_json_error( 'produit', 400 ); }
		$articles += $ligne['q'];
		$total += comptoir_prix_entier( $p ) * $ligne['q'];
		$resume[] = $ligne['q'] . '× ' . $p['b'] . ' ' . $p['n'] . ' (' . $p['s'] . ') — ' . $p['pr'];
	}
	$livraison = comptoir_franco_articles() > 0 && $articles >= comptoir_franco_articles() ? 0 : comptoir_livraison_dh();
	$total += $livraison;
	$panier = implode( "\n", $resume );

	$id = wp_insert_post( array(
		'post_type'    => 'cp_commande',
		'post_status'  => 'publish',
		// Le titre est ce qu'on lit dans la liste de l'admin : qui, ou, combien.
		'post_title'   => sprintf(
			'%s · %s · %s · %s DH',
			$champ( 'ref', 24 ) ? $champ( 'ref', 24 ) : 'sans référence',
			$champ( 'nom', 80 ) ? $champ( 'nom', 80 ) : 'Sans nom',
			$champ( 'ville', 60 ),
			$total
		),
		'post_content' => $panier,
		'meta_input'   => array(
			'cp_ref'     => $champ( 'ref', 24 ),
			'cp_tel'     => $tel,
			'cp_nom'     => $champ( 'nom', 80 ),
			'cp_ville'   => $champ( 'ville', 60 ),
			'cp_adresse' => $champ( 'adresse', 400 ),
			'cp_articles'  => $articles,
			'cp_livraison' => $livraison,
			'cp_total'   => $total,
			'cp_etat'    => $jeton_ok ? 'a-rappeler' : 'a-verifier',
			/* Provenance du visiteur. Elle n'entre dans aucun calcul : elle sert a
			   relire une commande plus tard et savoir si elle venait d'une publicite,
			   sans dependre de ce que Meta a su attribuer. */
			'cp_fbclid'       => $champ( 'fbclid', 255 ),
			'cp_utm_source'   => $champ( 'utm_source', 100 ),
			'cp_utm_medium'   => $champ( 'utm_medium', 100 ),
			'cp_utm_campaign' => $champ( 'utm_campaign', 200 ),
			'cp_referent'     => esc_url_raw( $champ( 'referent', 400 ) ),
			/* Identifiants Meta recus avec la commande. Ils partent deja a la
			   CAPI ; on les garde ici pour savoir, commande par commande, si le
			   clic publicitaire a survecu jusqu'a l'achat. La couverture que Meta
			   publie donne un pourcentage, pas la liste. */
			'cp_fbc'          => $champ( 'fbc', 255 ),
			'cp_fbp'          => $champ( 'fbp', 100 ),
		),
	), true );

	if ( is_wp_error( $id ) ) {
		wp_send_json_error( 'enregistrement', 500 );
	}

	// Le client n'attend pas cette reponse (sendBeacon), le relais peut donc
	// prendre son temps sans rien ralentir a l'ecran.
	$feuille = comptoir_envoie_feuille( $id );
	comptoir_capi_achat( $id, $lignes );
	// Message WhatsApp « je confirme / annuler » (sans effet si le module n'est pas regle).
	comptoir_wa_confirmation( $id );

	wp_send_json_success( array( 'id' => $id, 'feuille' => $feuille ) );
}
add_action( 'wp_ajax_cp_commande', 'comptoir_recoit_commande' );
add_action( 'wp_ajax_nopriv_cp_commande', 'comptoir_recoit_commande' );

/**
 * Relais vers la feuille Google, cote serveur.
 *
 * Le navigateur envoie deja la commande a la feuille, mais il peut echouer :
 * reseau qui coupe au changement d'application, extension qui bloque les
 * requetes vers Google, onglet ferme trop vite. WordPress refait donc l'envoi
 * depuis le serveur, ou rien de tout cela n'arrive. La feuille reconnait les
 * doublons a la reference et ne les ecrit pas deux fois.
 *
 * @return bool Vrai si la feuille a repondu.
 */
function comptoir_envoie_feuille( $id ) {
	$url = comptoir_feuille_google();
	if ( ! $url ) {
		return false;
	}

	$post = get_post( $id );
	if ( ! $post ) {
		return false;
	}

	$reponse = wp_remote_post( $url, array(
		'timeout'     => 8,
		'blocking'    => true,
		// Apps Script repond 302 vers script.googleusercontent.com. WordPress
		// suivrait la redirection en renvoyant un POST, et Google repond alors
		// une page d'erreur HTML : on la suit nous-memes, en GET, juste dessous.
		'redirection' => 0,
		'body'        => array(
			'ref'       => get_post_meta( $id, 'cp_ref', true ),
			'tel'       => get_post_meta( $id, 'cp_tel', true ),
			'nom'       => get_post_meta( $id, 'cp_nom', true ),
			'ville'     => get_post_meta( $id, 'cp_ville', true ),
			'adresse'   => get_post_meta( $id, 'cp_adresse', true ),
			'articles'  => get_post_meta( $id, 'cp_articles', true ),
			'livraison' => get_post_meta( $id, 'cp_livraison', true ),
			'total'     => get_post_meta( $id, 'cp_total', true ),
			'panier'    => $post->post_content,
			'source'    => 'wordpress',
		),
	) );

	// Le script a deja ecrit la ligne lors du POST ; sa reponse (« ok » ou
	// « doublon ») se lit a l'adresse de redirection.
	$code = is_wp_error( $reponse ) ? 0 : (int) wp_remote_retrieve_response_code( $reponse );
	if ( in_array( $code, array( 301, 302, 303, 307, 308 ), true ) ) {
		$suite = wp_remote_retrieve_header( $reponse, 'location' );
		if ( $suite ) {
			$reponse = wp_remote_get( $suite, array( 'timeout' => 8 ) );
		}
	}

	// Le code HTTP ne suffit PAS a conclure. Si le deploiement n'est pas ouvert
	// a tout le monde, Google repond 200 : c'est sa page de connexion, et la
	// commande n'a ete ecrite nulle part. On exige donc la reponse du script
	// lui-meme — « ok » quand la ligne est ecrite, « doublon » quand elle y
	// etait deja, ce qui est un succes aussi.
	$corps = is_wp_error( $reponse ) ? '' : trim( wp_remote_retrieve_body( $reponse ) );
	$ok    = in_array( $corps, array( 'ok', 'doublon' ), true );

	update_post_meta( $id, 'cp_feuille', $ok ? 'ok' : 'echec' );
	if ( ! $ok ) {
		update_post_meta( $id, 'cp_feuille_motif', $corps ? comptoir_coupe( $corps, 120 ) : 'aucune réponse' );
	}
	return $ok;
}

/**
 * Evenement Purchase envoye au serveur Meta (Conversions API) juste apres
 * l'enregistrement de la commande. Le client n'attend pas cette reponse : la
 * vente est deja passee (WhatsApp s'ouvre de son cote), donc un echec ici ne
 * doit jamais se voir a l'ecran ni ralentir quoi que ce soit.
 *
 * @param int   $id     Post cp_commande deja enregistre.
 * @param array $lignes Lignes de la commande, telles que validees plus haut
 *                       (chacune avec 's' et 'q') — pour donner a Meta les
 *                       references achetees, utiles au ciblage dynamique.
 */
function comptoir_capi_achat( $id, $lignes ) {
	$reglages = comptoir_capi();
	if ( ! $reglages['pixel_id'] || ! $reglages['token'] ) {
		return false;
	}

	$tel = get_post_meta( $id, 'cp_tel', true );
	$content_ids = array();
	if ( is_array( $lignes ) ) {
		foreach ( $lignes as $ligne ) {
			if ( isset( $ligne['s'] ) ) {
				$content_ids[] = $ligne['s'];
			}
		}
	}

	$user_data = array(
		'client_ip_address' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		'client_user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
	);
	if ( $tel ) {
		$user_data['ph'] = array( hash( 'sha256', comptoir_capi_tel_e164( $tel ) ) );
	}
	// _fbp / _fbc : deposes par le Pixel Meta cote navigateur (WordPress.com),
	// relus ici pour que le meme visiteur se recoupe des deux cotes.
	if ( isset( $_COOKIE['_fbp'] ) ) {
		$user_data['fbp'] = sanitize_text_field( wp_unslash( $_COOKIE['_fbp'] ) );
	}
	if ( isset( $_COOKIE['_fbc'] ) ) {
		$user_data['fbc'] = sanitize_text_field( wp_unslash( $_COOKIE['_fbc'] ) );
	}
	// Filet de securite (ajout) : _fbc / _fbp transmis par panier.js avec la
	// commande, quand le navigateur n'a pas envoye le cookie (in-app, ITP...).
	if ( empty( $user_data['fbc'] ) && ! empty( $_POST['fbc'] ) ) {
		$fbc_post = sanitize_text_field( wp_unslash( $_POST['fbc'] ) );
		if ( preg_match( '/^fb\.\d\.\d+\.[A-Za-z0-9_\-]+$/', $fbc_post ) ) {
			$user_data['fbc'] = $fbc_post;
		}
	}
	if ( empty( $user_data['fbp'] ) && ! empty( $_POST['fbp'] ) ) {
		$user_data['fbp'] = sanitize_text_field( wp_unslash( $_POST['fbp'] ) );
	}
	// Donnees d'appariement supplementaires (hachees) : prenom, ville, pays, id.
	$cp_nom = isset( $_POST['nom'] ) ? sanitize_text_field( wp_unslash( $_POST['nom'] ) ) : '';
	$cp_prenom = strtok( trim( $cp_nom ), ' ' );
	if ( $cp_prenom ) {
		$user_data['fn'] = array( hash( 'sha256', mb_strtolower( $cp_prenom, 'UTF-8' ) ) );
	}
	$cp_ville = isset( $_POST['ville'] ) ? sanitize_text_field( wp_unslash( $_POST['ville'] ) ) : '';
	$cp_ville = preg_replace( '/[^a-z]/', '', strtolower( remove_accents( $cp_ville ) ) );
	if ( $cp_ville ) {
		$user_data['ct'] = array( hash( 'sha256', $cp_ville ) );
	}
	$user_data['country'] = array( hash( 'sha256', 'ma' ) );
	$user_data['external_id'] = array( hash( 'sha256', 'cp-' . (string) $id ) );

	$evenement = array(
		'event_name'       => 'Purchase',
		'event_time'       => time(),
		// Meme reference que l'event_id du pixel navigateur (commande.js) :
		// c'est ce rapprochement qui evite de compter l'achat deux fois.
		'event_id'         => get_post_meta( $id, 'cp_ref', true ),
		'action_source'    => 'website',
		'event_source_url' => home_url( '/?commander=1&merci=1' ),
		'user_data'        => $user_data,
		'custom_data'      => array(
			'currency'     => 'MAD',
			'value'        => (float) get_post_meta( $id, 'cp_total', true ),
			'num_items'    => (int) get_post_meta( $id, 'cp_articles', true ),
			'content_type' => 'product',
			'content_ids'  => $content_ids,
		),
	);

	$corps = array( 'data' => array( $evenement ) );
	if ( $reglages['test'] ) {
		$corps['test_event_code'] = $reglages['test'];
	}

	wp_remote_post(
		sprintf(
			'https://graph.facebook.com/v21.0/%s/events?access_token=%s',
			rawurlencode( $reglages['pixel_id'] ),
			rawurlencode( $reglages['token'] )
		),
		array(
			'timeout'  => 8,
			'blocking' => false,
			'headers'  => array( 'Content-Type' => 'application/json' ),
			'body'     => wp_json_encode( $corps ),
		)
	);
	return true;
}

/**
 * Deuxieme chance, toutes les heures : les commandes que la feuille n'a pas
 * recues sont renvoyees. Une commande perdue coute un client, une ligne en
 * double ne coute rien — la feuille les reconnait a la reference.
 */
add_action( 'cp_relance_feuille', function () {
	if ( ! comptoir_feuille_google() ) {
		return;
	}
	$echecs = get_posts( array(
		'post_type'      => 'cp_commande',
		'posts_per_page' => 20,
		// « != ok » seul ignorerait les commandes enregistrees AVANT que la
		// feuille ne soit branchee : elles n'ont pas la cle du tout.
		'meta_query'     => array(
			'relation' => 'OR',
			array( 'key' => 'cp_feuille', 'value' => 'ok', 'compare' => '!=' ),
			array( 'key' => 'cp_feuille', 'compare' => 'NOT EXISTS' ),
		),
	) );
	foreach ( $echecs as $p ) {
		comptoir_envoie_feuille( $p->ID );
	}
} );

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'cp_relance_feuille' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'cp_relance_feuille' );
	}
} );

/**
 * Si l'adresse de la feuille manque, on le dit dans l'admin : sinon le trou se
 * decouvre le jour ou on cherche une commande qui n'y est pas.
 */
add_action( 'admin_notices', function () {
	$ecran = get_current_screen();
	if ( ! $ecran || 'cp_commande' !== $ecran->post_type || comptoir_feuille_google() ) {
		return;
	}
	echo '<div class="notice notice-warning"><p>'
		. esc_html__( 'Les commandes ne partent pas vers la feuille Google : collez l\'adresse du script dans comptoir_feuille_google(), en haut de functions.php. La marche à suivre est dans tools/feuille-commandes.gs.', 'comptoir-parfums' )
		. '</p></div>';
} );

/** Colonnes de la liste des commandes : telephone et etat, tout de suite. */
add_filter( 'manage_cp_commande_posts_columns', function ( $cols ) {
	$out = array( 'cb' => $cols['cb'], 'title' => __( 'Commande', 'comptoir-parfums' ) );
	$out['cp_tel']   = __( 'Téléphone', 'comptoir-parfums' );
	$out['cp_etat']  = __( 'État', 'comptoir-parfums' );
	$out['cp_source'] = __( 'Source', 'comptoir-parfums' );
	$out['cp_clic']   = __( 'Clic', 'comptoir-parfums' );
	$out['cp_feuille'] = __( 'Feuille', 'comptoir-parfums' );
	$out['date']     = $cols['date'];
	return $out;
} );
add_action( 'manage_cp_commande_posts_custom_column', function ( $col, $id ) {
	if ( 'cp_tel' === $col ) {
		$tel = get_post_meta( $id, 'cp_tel', true );
		printf( '<a href="tel:%s">%s</a>', esc_attr( $tel ), esc_html( $tel ) );
	}
	if ( 'cp_etat' === $col ) {
		echo esc_html( get_post_meta( $id, 'cp_etat', true ) );
	}
	if ( 'cp_source' === $col ) {
		$fbclid = get_post_meta( $id, 'cp_fbclid', true );
		$utm    = get_post_meta( $id, 'cp_utm_source', true );
		$ref    = get_post_meta( $id, 'cp_referent', true );
		if ( $fbclid ) {
			echo esc_html__( 'Publicité', 'comptoir-parfums' );
		} elseif ( $utm ) {
			echo esc_html( $utm );
		} elseif ( $ref ) {
			$hote = wp_parse_url( $ref, PHP_URL_HOST );
			echo esc_html( $hote ? $hote : 'référent' );
		} else {
			echo esc_html__( 'direct', 'comptoir-parfums' );
		}
	}
	if ( 'cp_clic' === $col ) {
		/* Le fbc a la forme fb.1.<millisecondes du clic>.<identifiant>. Sa
		   presence dit si Meta peut rattacher cet achat a une publicite ;
		   l'horodatage qu'il porte dit combien de temps le client a mis
		   entre le clic et la commande. */
		$fbc = get_post_meta( $id, 'cp_fbc', true );
		if ( ! $fbc ) {
			printf(
				'<span style="color:#b32d2e" title="%s">%s</span>',
				esc_attr__( 'Aucun identifiant de clic. Meta ne peut pas rattacher cet achat a une publicite.', 'comptoir-parfums' ),
				esc_html__( 'aucun', 'comptoir-parfums' )
			);
		} else {
			$bouts = explode( '.', $fbc );
			$ms    = isset( $bouts[2] ) ? (int) $bouts[2] : 0;
			$delai = $ms ? ( (int) get_post_time( 'U', true, $id ) - (int) floor( $ms / 1000 ) ) : -1;
			if ( $delai < 0 || $delai > 7776000 ) {
				$texte = __( 'oui', 'comptoir-parfums' );
			} elseif ( $delai < 3600 ) {
				$texte = sprintf( '%d min', max( 1, (int) round( $delai / 60 ) ) );
			} elseif ( $delai < 86400 ) {
				$texte = sprintf( '%dh', (int) floor( $delai / 3600 ) );
			} else {
				$texte = sprintf( '%dj', (int) floor( $delai / 86400 ) );
			}
			printf(
				'<span style="color:#227a4b" title="%s">%s</span>',
				esc_attr__( 'Identifiant de clic present : delai entre le clic sur la publicite et la commande.', 'comptoir-parfums' ),
				esc_html( $texte )
			);
		}
	}
	if ( 'cp_feuille' === $col ) {
		$f = get_post_meta( $id, 'cp_feuille', true );
		if ( 'ok' === $f ) {
			echo '<span style="color:#227a4b" title="Écrite dans la feuille">✓</span>';
		} else {
			$motif = get_post_meta( $id, 'cp_feuille_motif', true );
			printf(
				'<span style="color:#b32d2e" title="%s">⟳</span>',
				esc_attr( 'Sera renvoyée dans l\'heure. Réponse de Google : ' . ( $motif ? $motif : 'aucune' ) )
			);
		}
	}
}, 10, 2 );

/* ══════════════════════════════════════════════════════════════
   SCRIPTS ET DONNEES
══════════════════════════════════════════════════════════════ */

function comptoir_parfums_assets() {
	$dir = get_template_directory_uri();
	$abs = get_template_directory();

	// style.css : l'en-tete de theme exige par WordPress, rien de plus.
	wp_enqueue_style( 'comptoir-parfums-style', get_stylesheet_uri(), array(), '2.0' );

	// Le design — MEME fichier que celui charge par la maquette. En fichier
	// externe plutot qu'en inline : une seule copie a maintenir, et le
	// navigateur le garde en cache d'une page a l'autre au lieu de retelecharger
	// 70 Ko de CSS sur chaque fiche parfum.
	$ver_css = file_exists( $abs . '/style-site.css' ) ? filemtime( $abs . '/style-site.css' ) : '2.0';
	wp_enqueue_style( 'comptoir-site', $dir . '/style-site.css', array( 'comptoir-parfums-style' ), $ver_css );

	// Bibliotheques d'animation — memes versions que la maquette de reference.
	wp_enqueue_script( 'cp-gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js', array(), '3.12.5', true );
	wp_enqueue_script( 'cp-scrolltrigger', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js', array( 'cp-gsap' ), '3.12.5', true );

	// three.js ne sert QUE au flacon 3D et a la vitrine du heros, donc a la
	// seule page d'accueil : le bloc WebGL de theme.js sort immediatement si
	// #flacon3d est absent. Le charger partout ajoutait 600 Ko inutiles a
	// chaque fiche parfum, a la page commande et a la page 404 — cher pour
	// une clientele majoritairement en 4G.
	$deps_theme = array( 'cp-gsap', 'cp-scrolltrigger', 'comptoir-panier' );
	// Sur telephone (97 % des visites, surtout en 4G), three.js (600 Ko) et la
	// vitrine ne sont PLUS telecharges : le heros garde l'apercu photo, prevu
	// comme repli (theme.js teste window.THREE et window.CP_VITRINE). Sur
	// grand ecran, un petit chargeur les ecrit dans la page AVANT theme.js,
	// en scripts bloquants : l'ordre three -> vitrine -> theme est conserve.
	if ( comptoir_est_accueil() ) {
		$ver_vit = file_exists( $abs . '/vitrine.js' ) ? filemtime( $abs . '/vitrine.js' ) : '2.0';
		$cp_src  = array(
			'https://cdnjs.cloudflare.com/ajax/libs/three.js/0.140.0/three.min.js',
			$dir . '/vitrine.js?ver=' . $ver_vit,
		);
		$cp_tags = '';
		foreach ( $cp_src as $u ) {
			$cp_tags .= '<script src="' . esc_url( $u ) . '"></script>';
		}
		wp_register_script( 'cp-vitrine-chargeur', false, array(), $ver_vit, true );
		wp_enqueue_script( 'cp-vitrine-chargeur' );
		wp_add_inline_script(
			'cp-vitrine-chargeur',
			'if(window.matchMedia&&matchMedia("(min-width:901px)").matches){document.write(' . wp_json_encode( $cp_tags ) . ');}'
		);
		$deps_theme[] = 'cp-vitrine-chargeur';
	}

	// Bascule francais / arabe. Chargee AVANT le panier : elle expose CP_T(),
	// dont panier.js et commande.js se servent pour leurs propres textes.
	$ver_lang = file_exists( $abs . '/langue.js' ) ? filemtime( $abs . '/langue.js' ) : '2.0';
	wp_enqueue_script( 'comptoir-langue', $dir . '/langue.js', array(), $ver_lang, true );

	// Les 177 descriptions traduites, sur les seules fiches parfum : elles ne
	// s'affichent nulle part ailleurs, et 18 Ko de texte que personne ne lit
	// coutent cher a une clientele majoritairement en 4G.
	if ( comptoir_parfum_slug_demande() ) {
		$ver_desc = file_exists( $abs . '/langue-parfums.js' ) ? filemtime( $abs . '/langue-parfums.js' ) : '2.0';
		wp_enqueue_script( 'comptoir-langue-parfums', $dir . '/langue-parfums.js', array( 'comptoir-langue' ), $ver_desc, true );
	}

	// Panier client.
	$ver_panier = file_exists( $abs . '/panier.js' ) ? filemtime( $abs . '/panier.js' ) : '2.0';
	wp_enqueue_script( 'comptoir-panier', $dir . '/panier.js', array( 'comptoir-langue' ), $ver_panier, true );

	// ViewContent, sur la seule fiche parfum : c'etait le seul des cinq
	// evenements standards (avec AddToCart, InitiateCheckout, Lead, Purchase)
	// jamais code, ni cote pixel ni cote CAPI. Meme forme de donnees que
	// AddToCart dans panier.js, pour que le catalogue Meta rapproche les
	// deux. 'after' : le code doit s'executer une fois panier.js charge,
	// sinon window.Panier n'existe pas encore.
	$cp_vc_slug = comptoir_parfum_slug_demande();
	if ( $cp_vc_slug ) {
		$cp_vc_p = comptoir_produit_by_slug( $cp_vc_slug );
		if ( $cp_vc_p ) {
			wp_add_inline_script(
				'comptoir-panier',
				'window.Panier && window.Panier.mesure("ViewContent",' . wp_json_encode( array(
					'content_ids'   => array( $cp_vc_p['s'] ),
					'content_type'  => 'product',
					'content_name'  => $cp_vc_p['b'] . ' ' . $cp_vc_p['n'],
					'value'         => comptoir_prix_entier( $cp_vc_p ),
					'currency'      => 'MAD',
				) ) . ');',
				'after'
			);
		}
	}

	// Script du site — MEME fichier que celui charge par la maquette.
	// Les dependances imposent l'ordre : sans gsap deja defini, theme.js
	// bascule en mode degrade alors que la bibliotheque etait disponible.
	$ver_theme = file_exists( $abs . '/theme.js' ) ? filemtime( $abs . '/theme.js' ) : '2.0';
	wp_enqueue_script( 'comptoir-theme', $dir . '/theme.js', $deps_theme, $ver_theme, true );

	// Page commande uniquement : le pilotage du recapitulatif et du formulaire.
	// Meme fichier que celui charge par commander.html cote maquette.
	if ( comptoir_commander_demande() ) {
		$ver_cmd = file_exists( $abs . '/commande.js' ) ? filemtime( $abs . '/commande.js' ) : '2.0';
		wp_enqueue_script( 'comptoir-commande', $dir . '/commande.js', array( 'comptoir-panier' ), $ver_cmd, true );
	}

	// Donnees injectees AVANT panier.js : le catalogue, les fiches, le panier
	// et le ruban de maisons lisent tous la meme source, produits.php.
	// On n'injecte QUE le tableau ordonne. L'index par slug s'en deduit en une
	// ligne cote navigateur (panier.js le fait, comme produits.js cote
	// maquette) : l'envoyer aussi doublait la charge utile, 135 Ko au lieu de
	// 66 sur chaque page.
	$produits = comptoir_produits();

	$cfg = array(
		'ajax'      => admin_url( 'admin-ajax.php' ),
		'nonce'     => wp_create_nonce( 'cp_commande' ),
		'feuille'   => comptoir_feuille_google(),
		'wa'        => comptoir_wa_numero(),
		'livraison' => comptoir_livraison_dh(),
		'franco'    => comptoir_franco_articles(),
		'commander' => home_url( '/?commander=1' ),
		'home'      => home_url( '/' ),
		'imgBase'   => $dir . '/img/produits/',
		'imgVer'    => comptoir_version_photos(),
	);

	wp_add_inline_script(
		'comptoir-panier',
		'window.PRODUITS=' . wp_json_encode( $produits ) . ';'
		. 'window.CP_PANIER=' . wp_json_encode( $cfg ) . ';'
		. 'window.CP_PRODUCT_IMAGES=' . wp_json_encode( comptoir_photos_produits() ) . ';'
		. 'window.HERO_SHOTS=' . wp_json_encode( comptoir_hero_shots() ) . ';'
		. 'window.CP_PARFUM_BASE=' . wp_json_encode( home_url( '/?parfum=' ) ) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'comptoir_parfums_assets' );

/**
 * Noms de fichiers reellement presents dans img/produits/.
 *
 * Cote maquette cette liste est tenue a la main dans le HTML et peut donc
 * se desynchroniser du disque en silence. Ici, c'est le disque qui repond.
 */
function comptoir_photos_produits() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	$dir   = get_template_directory() . '/img/produits/';
	foreach ( comptoir_produits() as $p ) {
		if ( file_exists( $dir . $p['s'] . '.webp' ) ) {
			$cache[] = $p['s'] . '.webp';
		}
	}
	return $cache;
}

/** Vrai si la photo produit <slug>.webp existe. */
function comptoir_a_photo( $slug ) {
	// Index par nom de fichier : comptoir_rendu_catalogue() pose la question
	// pour les 177 references, et un in_array() balayait a chaque fois les
	// 170 entrees de la liste.
	static $index = null;
	if ( null === $index ) {
		$index = array_flip( comptoir_photos_produits() );
	}
	return isset( $index[ $slug . '.webp' ] );
}

/**
 * Minuscules sur une chaine accentuee.
 * mb_strtolower n'est pas garanti : l'extension mbstring peut manquer sur un
 * hebergeur mutualise, et l'appeler sans garde tuerait la page d'accueil.
 * WordPress fait le meme test dans son propre code.
 */
function comptoir_minuscules( $s ) {
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $s, 'UTF-8' ) : strtolower( $s );
}

/**
 * Cle de recherche : minuscules ET sans accents.
 * Personne ne tape « Hermès », « Lancôme » ou « Chloé » avec l'accent sur un
 * clavier de telephone. En stockant la cle sans accent et en retirant aussi
 * les accents de la saisie, les deux orthographes trouvent la meme chose.
 * cleRecherche() fait exactement la meme chose cote navigateur.
 */
function comptoir_cle_recherche( $s ) {
	return comptoir_minuscules( remove_accents( (string) $s ) );
}

/**
 * Visuels detoures du heros (anneau « vitrine »), un par maison.
 * Produits par tools/build-heros.py dans img/heros/ : <slug-maison>.webp.
 * Une maison sans visuel garde le flacon-sceau CP — jamais de photo a fond
 * blanc posee telle quelle sur le fond aubergine.
 *
 * L'empreinte de version est INDISPENSABLE, comme pour les photos produit :
 * l'hebergement sert les fichiers du theme avec un cache de dix ans. Une
 * maison qui change de flacon garde le meme nom de fichier, donc la meme
 * adresse — sans le ?v= le cache continue de servir l'ancien visuel, et la
 * vitrine montre encore le flacon d'avant longtemps apres la mise en ligne.
 */
function comptoir_hero_shots() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	$abs   = get_template_directory() . '/img/heros/';
	$uri   = get_template_directory_uri() . '/img/heros/';
	$ver   = comptoir_version_photos();
	foreach ( array_keys( comptoir_catalogue_par_maison() ) as $maison ) {
		$slug = comptoir_maison_slug( $maison );
		if ( ! file_exists( $abs . $slug . '.webp' ) ) {
			continue;
		}
		$cache[ $slug ] = array(
			'maison' => $maison,
			'mode'   => 'cutout',
			'webp'   => $uri . $slug . '.webp?v=' . $ver,
		);
	}
	return $cache;
}

/* ══════════════════════════════════════════════════════════════
   SELECTION DU MOMENT
   Les neuf fiches mises en avant sur la page d'accueil. On ne stocke
   que des slugs : nom, maison, prix et notes viennent de produits.php,
   donc un prix modifie la-bas est modifie ici aussi.
══════════════════════════════════════════════════════════════ */

function comptoir_selection() {
	$blocs = array(
		'homme' => array(
			'actif' => true,
			'cta'   => 'Voir tous les parfums hommes',
			'slugs' => array( 'valentino--uomo-born-in-roma-intense', 'jean-paul-gaultier--le-male-elixir', 'emporio-armani--stronger-with-you-absolutely' ),
		),
		'femme' => array(
			'actif' => false,
			'cta'   => 'Voir tous les parfums femmes',
			'slugs' => array( 'chanel--coco-mademoiselle', 'dior--miss-dior-parfum', 'yves-saint-laurent--libre-le-parfum' ),
		),
		'niche' => array(
			'actif' => false,
			'cta'   => 'Voir toute la niche',
			'slugs' => array( 'maison-francis-kurkdjian--baccarat-rouge-540', 'xerjoff--erba-pura', 'nishane--hacivat' ),
		),
	);

	$out = array();
	foreach ( $blocs as $cle => $bloc ) {
		$parfums = array();
		foreach ( $bloc['slugs'] as $slug ) {
			$p = comptoir_produit_by_slug( $slug );
			// Un slug retire de produits.php disparait simplement de la selection,
			// il ne casse pas la page.
			if ( $p ) {
				$parfums[] = $p;
			}
		}
		$out[ $cle ] = array(
			'actif'   => $bloc['actif'],
			'cta'     => $bloc['cta'],
			'parfums' => $parfums,
		);
	}
	return $out;
}

/** Trois a quatre notes marquantes, pour la ligne d'accroche d'une carte. */
function comptoir_notes_courtes( $p ) {
	$notes = array_merge( (array) $p['t'], (array) $p['c'], (array) $p['f'] );
	$notes = array_values( array_unique( $notes ) );
	return implode( ' · ', array_slice( $notes, 0, 4 ) );
}

/* ══════════════════════════════════════════════════════════════
   CE QUE VEND LE SITE : DES TESTEURS ORIGINAUX, RIEN D'AUTRE
   Un testeur est un flacon AUTHENTIQUE de la maison — Chanel, Dior, Tom
   Ford — produit par elle pour la demonstration en boutique. Meme jus, meme
   concentration, meme tenue que le flacon du rayon. Ce qui change est
   l'emballage : flacon de demonstration au lieu du coffret de luxe. L'ecart
   de prix vient de la, et de nulle part ailleurs.

   Le site annoncait « dupes haute fidelite ET testeurs », avec un champ
   'pres' cense distinguer les deux fiche par fiche. Ce champ n'a jamais ete
   renseigne sur une seule des 209 references, et pour cause : l'offre est
   uniforme. Un dupe est une imitation fabriquee par un tiers — ce n'est pas
   ce qui est vendu ici, et l'ecrire etait faux. Le champ et sa mecanique ont
   donc ete retires plutot que corriges : il ne reste rien qui puisse, plus
   tard, remettre le mot sur une fiche.
══════════════════════════════════════════════════════════════ */

/** Pastille au-dessus du nom : la famille olfactive.
 *  Elle distingue les fiches entre elles, ce qu'un badge « Testeur original »
 *  repete 209 fois ne ferait pas. La nature de l'offre est dite par la page
 *  d'accueil et par la ligne « Presentation » de chaque fiche. */
function comptoir_badge( $p ) {
	return $p['fam'];
}

/** Ligne « Presentation » de la fiche. */
function comptoir_presentation( $p ) {
	return 'Testeur original, flacon neuf';
}

/** Le pictogramme WhatsApp, defini une fois. */
function comptoir_icone_whatsapp() {
	echo '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>';
}

/* ══════════════════════════════════════════════════════════════
   CATALOGUE — GRILLE RENDUE COTE SERVEUR
   Le meme balisage que celui construit en JS dans la maquette, pour que
   theme.js se contente de le cabler. L'interet : la grille est indexable
   et reste lisible sans JavaScript.
══════════════════════════════════════════════════════════════ */

/** Prix en entier, pour le tri et les bornes. */
function comptoir_prix_entier( $p ) {
	return (int) preg_replace( '/[^0-9]/', '', $p['pr'] );
}

/** Prix d'une liste de parfums, en entiers, les valeurs illisibles ecartees. */
function comptoir_prix_numeriques( $parfums ) {
	$n = array();
	foreach ( $parfums as $p ) {
		$v = comptoir_prix_entier( $p );
		if ( $v > 0 ) {
			$n[] = $v;
		}
	}
	return $n;
}

/** 1399 -> « 1 399 » (espace comme separateur de milliers, comme la maquette). */
function comptoir_prix_format( $v ) {
	return number_format( $v, 0, ',', ' ' );
}

/** Prix le plus bas / le plus haut de tout le catalogue, deja formates. */
function comptoir_bornes_prix() {
	$n = comptoir_prix_numeriques( comptoir_produits() );
	if ( ! $n ) {
		return array( '', '' );
	}
	return array( comptoir_prix_format( min( $n ) ), comptoir_prix_format( max( $n ) ) );
}

/**
 * Grande famille olfactive : le premier mot de la famille detaillee.
 * « Floral fruite », « Floral ambre »… -> « Floral ». Les 67 familles fines
 * du catalogue se ramenent a une dizaine, ce qui fait un filtre utilisable.
 */
function comptoir_famille_principale( $p ) {
	$mots = preg_split( '/\s+/', trim( $p['fam'] ) );
	return $mots ? $mots[0] : '';
}

/** Les grandes familles presentes, de la plus fournie a la moins fournie. */
function comptoir_familles() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$n = array();
	foreach ( comptoir_produits() as $p ) {
		$f = comptoir_famille_principale( $p );
		if ( '' !== $f ) {
			$n[ $f ] = isset( $n[ $f ] ) ? $n[ $f ] + 1 : 1;
		}
	}
	arsort( $n );
	$cache = array_keys( $n );
	return $cache;
}

/** Les genres presents, dans un ordre stable. */
/** Combien de references pour un destinataire donne (Femme, Homme, Mixte). */
function comptoir_compte_genre( $genre ) {
	$n = 0;
	foreach ( comptoir_produits() as $p ) {
		if ( $p['g'] === $genre ) {
			$n++;
		}
	}
	return $n;
}

function comptoir_genres() {
	$ordre = array( 'Femme', 'Homme', 'Mixte' );
	$vus   = array();
	foreach ( comptoir_produits() as $p ) {
		$vus[ $p['g'] ] = true;
	}
	$out = array();
	foreach ( $ordre as $g ) {
		if ( isset( $vus[ $g ] ) ) {
			$out[] = $g;
			unset( $vus[ $g ] );
		}
	}
	return array_merge( $out, array_keys( $vus ) );
}

/**
 * Vignette de secours : un SVG transparent en data URI. Il ne declenche
 * aucune requete et ne peut pas echouer, donc aucun <img> ne subit de 404
 * et le navigateur n'affiche jamais son glyphe d'image cassee. Le decor
 * (fond + silhouette de flacon doree) est peint par .pc-img.is-empty en CSS,
 * donc il suit le theme clair comme le sombre.
 */
function comptoir_vignette_vide() {
	return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='4' height='5'%3E%3C/svg%3E";
}

/**
 * L'<img> d'une vignette produit : la photo du parfum, ou la silhouette de
 * flacon (.is-empty en CSS) quand la reference n'en a pas. Meme balisage pour
 * la grille du catalogue et pour les cartes de la selection : seuls la classe
 * et le cadrage changent, jamais la regle de secours.
 */
function comptoir_vignette_img( $p, $classe, $largeur, $hauteur ) {
	$photo = comptoir_a_photo( $p['s'] ) ? comptoir_photo_url( $p['s'] ) : '';
	return sprintf(
		'<img class="%s%s" alt="" width="%d" height="%d" loading="lazy" decoding="async" src="%s">',
		esc_attr( $classe ),
		$photo ? '' : ' is-empty',
		(int) $largeur,
		(int) $hauteur,
		$photo ? esc_url( $photo ) : esc_attr( comptoir_vignette_vide() )
	);
}

/**
 * Les trois garanties, en pastilles.
 *
 * Ecrites ici et nulle part ailleurs : elles sont repetees sur l'accueil, la
 * fiche parfum, la page commande et la barre fixe du telephone, et une
 * promesse qui change d'une page a l'autre ne rassure plus personne.
 */
function comptoir_gages( $classe = '' ) {
	$gages = array(
		array( 'Paiement à la livraison',    'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z' ),
		array( 'Livraison partout au Maroc', 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z' ),
		array( 'Satisfait ou remboursé',     'M20 6 9 17l-5-5' ),
	);
	echo '<ul class="gages' . ( $classe ? ' ' . esc_attr( $classe ) : '' ) . '" aria-label="Nos garanties">';
	foreach ( $gages as $g ) {
		printf(
			'<li class="gage"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="%s"/></svg>%s</li>',
			esc_attr( $g[1] ),
			esc_html( $g[0] )
		);
	}
	echo '</ul>';
}

/**
 * Landing page vente : le parfum mis en avant, lu sur ?p=<slug>. Une
 * publicite Valentino pointe sur /?vente=1&p=valentino--… et le visiteur
 * retrouve EN PREMIER le flacon qu'il vient de voir, avec son prix et le
 * bouton — au lieu d'une phrase generale suivie d'une grille a parcourir.
 * Slug inconnu ou absent : null, et la page reste la page generale.
 */
function comptoir_vente_vedette() {
	if ( empty( $_GET['p'] ) || ! is_string( $_GET['p'] ) ) {
		return null;
	}
	// Meme nettoyage que comptoir_parfum_slug_demande(). Pas sanitize_title() :
	// il reduit « -- » a « - », et « valentino--uomo… » ne trouvait plus rien.
	$slug = preg_replace( '/[^a-z0-9-]/', '', strtolower( (string) wp_unslash( $_GET['p'] ) ) );
	return $slug ? comptoir_produit_by_slug( $slug ) : null;
}

/**
 * Preuves clients : captures WhatsApp et photos de colis, deposees telles
 * quelles dans img/preuves/ (webp, jpg ou png). L'ordre suit le nom de
 * fichier : 01-…, 02-… pour choisir lesquelles passent devant.
 * Dossier vide = aucun bloc affiche, jamais un cadre vide.
 */
function comptoir_preuves() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	$dir   = get_template_directory() . '/img/preuves/';
	if ( is_dir( $dir ) ) {
		foreach ( (array) scandir( $dir ) as $f ) {
			if ( preg_match( '/^[^.].*\.(webp|jpe?g|png)$/i', $f ) ) {
				$cache[] = $f;
			}
		}
	}
	return $cache;
}

/**
 * Le bloc « Ils ont recu leur parfum » : une rangee qui defile au doigt sur
 * telephone. Les captures parlent d'elles-memes, on n'y ajoute pas de faux
 * avis ecrits : ce sont de vrais messages, ou rien.
 */
function comptoir_bloc_preuves( $classe = '' ) {
	$preuves = comptoir_preuves();
	if ( ! $preuves ) {
		return;
	}
	$base = get_template_directory_uri() . '/img/preuves/';
	echo '<section class="preuves' . ( $classe ? ' ' . esc_attr( $classe ) : '' ) . '" aria-label="Clients livrés">';
	echo '<div class="preuves-head"><h2>Ils ont reçu leur parfum</h2>'
		. '<p>Messages et colis de nos clients, tels quels.</p></div>';
	// tabindex + nom : la rangee defile, et une zone qui defile sans rien de
	// focalisable ne se parcourt pas au clavier.
	echo '<div class="preuves-rang" role="region" tabindex="0" aria-label="Captures de clients, faites défiler">';
	foreach ( array_slice( $preuves, 0, 12 ) as $f ) {
		printf(
			'<figure class="preuve"><img src="%s" alt="Message ou colis d\'un client" loading="lazy" decoding="async"></figure>',
			esc_url( $base . rawurlencode( $f ) . '?v=' . comptoir_version_photos() )
		);
	}
	echo '</div></section>';
}

/**
 * « Un testeur, c'est quoi ? » — la reponse a la question que se pose tout
 * visiteur avant d'acheter, et qui n'existait qu'au fond de la FAQ de
 * l'accueil. Le texte vit dans testeur.html, une seule copie pour l'accueil,
 * la landing page et les fiches (et pour l'apercu local).
 */
function comptoir_bloc_testeur( $classe = '' ) {
	$f = get_template_directory() . '/testeur.html';
	if ( ! is_readable( $f ) ) {
		return;
	}
	list( $lo, $hi ) = comptoir_bornes_prix();
	$html = preg_replace( '/<!--.*?-->\s*/s', '', (string) file_get_contents( $f ) );
	echo str_replace(
		array( '{CLASSE}', '{PRIX_MIN}', '{PRIX_MAX}' ),
		array( $classe ? ' ' . esc_attr( $classe ) : '', esc_html( $lo ), esc_html( $hi ) ),
		$html
	);
}

/**
 * Sur une fiche parfum (et sur la landing page ouverte sur un parfum), la
 * barre d'achat fixe achete CE flacon (panier.js, majCtaFixe) au lieu
 * d'ouvrir une page commande vide. Ailleurs, rien.
 */
function comptoir_cta_fixe_attributs() {
	$slug = comptoir_parfum_slug_demande();
	$p    = $slug ? comptoir_produit_by_slug( $slug ) : null;
	if ( ! $p && comptoir_vente_demande() ) {
		$p = comptoir_vente_vedette();   // landing page ouverte sur un parfum
	}
	if ( $p ) {
		printf( ' data-cta-slug="%s" data-cta-prix="%s"', esc_attr( $p['s'] ), esc_attr( $p['pr'] ) );
	}
}

/** Une carte produit de la grille. Meme balisage que la version JS. */
function comptoir_carte_produit( $p ) {
	?>
        <a class="pc" href="<?php echo esc_url( comptoir_parfum_url( $p['s'] ) ); ?>" role="listitem"
           data-n="<?php echo esc_attr( comptoir_cle_recherche( $p['n'] ) ); ?>"
           data-b="<?php echo esc_attr( comptoir_cle_recherche( $p['b'] ) ); ?>"
           data-maison="<?php echo esc_attr( comptoir_maison_slug( $p['b'] ) ); ?>"
           data-genre="<?php echo esc_attr( $p['g'] ); ?>"
           data-famille="<?php echo esc_attr( comptoir_famille_principale( $p ) ); ?>"
           data-prix="<?php echo (int) comptoir_prix_entier( $p ); ?>">
          <span class="pc-photo">
            <?php echo comptoir_vignette_img( $p, 'pc-img', 400, 500 ); ?>
            <span class="pc-badge"><?php echo esc_html( comptoir_badge( $p ) ); ?></span>
          </span>
          <span class="pc-maison"><?php echo esc_html( $p['b'] ); ?></span>
          <span class="pc-nom"><?php echo esc_html( $p['n'] ); ?></span>
          <span class="pc-meta"><?php echo esc_html( $p['x'] . ' · ' . $p['g'] ); ?></span>
          <span class="pc-prix"><?php echo esc_html( $p['pr'] ); ?></span>
        </a>
	<?php
}

/** La grille complete, dans l'ordre du fichier (maison par maison). */
function comptoir_rendu_catalogue() {
	foreach ( comptoir_produits() as $p ) {
		comptoir_carte_produit( $p );
	}
}

/* ══════════════════════════════════════════════════════════════
   METADONNEES — titre, description, canonical, Open Graph, JSON-LD
   Ecrites ici et nulle part ailleurs : elles dependent de la page servie.
══════════════════════════════════════════════════════════════ */

/** Titre, description et image de la page courante, en un seul endroit. */
function comptoir_meta_page() {
	// Appelee trois fois par requete (titre, balises meta, JSON-LD) : on ne
	// refait pas le travail, d'autant qu'elle lit tout le catalogue.
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$nom  = get_bloginfo( 'name' );
	$slug = comptoir_parfum_slug_demande();

	if ( $slug ) {
		$p = comptoir_produit_by_slug( $slug );
		if ( $p ) {
			$desc = sprintf(
				'%s (%s) — %s Tête : %s. Cœur : %s. Fond : %s. %s, livraison partout au Maroc, paiement à la réception.',
				$p['n'], $p['b'], $p['d'],
				implode( ', ', $p['t'] ), implode( ', ', $p['c'] ), implode( ', ', $p['f'] ), $p['pr']
			);
			$cache = array(
				'titre'     => $p['b'] . ' ' . $p['n'] . ' — ' . $nom,
				'desc'      => $desc,
				'url'       => comptoir_parfum_url( $p['s'] ),
				'image'     => comptoir_a_photo( $p['s'] ) ? comptoir_photo_url( $p['s'] ) : '',
				'type'      => 'product',
				'indexable' => true,
			);
			return $cache;
		}
		$cache = array(
			'titre'     => __( 'Parfum introuvable', 'comptoir-parfums' ) . ' — ' . $nom,
			'desc'      => 'Cette référence n\'existe pas ou a été retirée du catalogue.',
			'url'       => '',
			'image'     => '',
			'type'      => 'website',
			'indexable' => false,
		);
		return $cache;
	}

	if ( comptoir_commander_demande() ) {
		$cache = array(
			'titre'     => 'Commander — ' . $nom,
			'desc'      => 'Finalisez votre commande. Paiement à la livraison, en espèces, partout au Maroc. Aucune carte bancaire.',
			'url'       => home_url( '/?commander=1' ),
			'image'     => '',
			'type'      => 'website',
			'indexable' => false,
		);
		return $cache;
	}

	if ( comptoir_vente_demande() ) {
		list( $lo, $hi ) = comptoir_bornes_prix();
		// indexable a false : cette page n'existe que pour le trafic publicitaire,
		// elle ne doit pas concurrencer l'accueil dans les resultats de recherche.
		$cache = array(
			'titre'     => 'Testeurs originaux, livrés partout au Maroc — ' . $nom,
			'desc'      => sprintf(
				'Chanel, Dior, Tom Ford, Xerjoff — testeurs 100%% originaux, de %s à %s DH. Livraison 24 à 72h partout au Maroc, paiement à la réception.',
				$lo, $hi
			),
			'url'       => home_url( '/?vente=1' ),
			'image'     => '',
			'type'      => 'website',
			'indexable' => false,
		);
		return $cache;
	}

	if ( is_front_page() ) {
		list( $lo, $hi ) = comptoir_bornes_prix();
		$cache = array(
			'titre'     => $nom . ' — Testeurs originaux, livrés partout au Maroc',
			'desc'      => sprintf(
				'Testeurs originaux des plus grandes maisons, de %s à %s DH. Même jus qu\'en boutique, sans le coffret. Livraison 24-72h partout au Maroc, paiement à la réception.',
				$lo, $hi
			),
			'url'       => home_url( '/' ),
			'image'     => '',
			'type'      => 'website',
			'indexable' => true,
		);
		return $cache;
	}

	$cache = array(
		'titre'     => '',
		'desc'      => '',
		'url'       => '',
		'image'     => '',
		'type'      => 'website',
		'indexable' => true,
	);
	return $cache;
}

add_filter( 'pre_get_document_title', function ( $title ) {
	$meta = comptoir_meta_page();
	return $meta['titre'] ? $meta['titre'] : $title;
} );

add_action( 'wp_head', function () {
	$meta = comptoir_meta_page();
	$nom  = get_bloginfo( 'name' );
	$img  = $meta['image'] ? $meta['image'] : get_template_directory_uri() . '/og-image.jpg';
	$w    = 1200;
	$h    = 630;

	if ( $meta['image'] ) {
		$fichier = str_replace( get_template_directory_uri(), get_template_directory(), $meta['image'] );
		$taille  = @getimagesize( $fichier );
		if ( $taille ) {
			$w = $taille[0];
			$h = $taille[1];
		}
	}

	if ( $meta['desc'] ) {
		echo '<meta name="description" content="' . esc_attr( $meta['desc'] ) . '">' . "\n";
	}
	if ( $meta['url'] ) {
		echo '<link rel="canonical" href="' . esc_url( $meta['url'] ) . '">' . "\n";
	}
	echo '<meta name="robots" content="' . ( $meta['indexable'] ? 'index,follow,max-image-preview:large' : 'noindex,follow' ) . '">' . "\n";

	echo '<meta property="og:type" content="' . esc_attr( $meta['type'] ) . '">' . "\n";
	echo '<meta property="og:locale" content="fr_MA">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( $nom ) . '">' . "\n";
	if ( $meta['titre'] ) {
		echo '<meta property="og:title" content="' . esc_attr( $meta['titre'] ) . '">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $meta['titre'] ) . '">' . "\n";
	}
	if ( $meta['desc'] ) {
		echo '<meta property="og:description" content="' . esc_attr( $meta['desc'] ) . '">' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $meta['desc'] ) . '">' . "\n";
	}
	if ( $meta['url'] ) {
		echo '<meta property="og:url" content="' . esc_url( $meta['url'] ) . '">' . "\n";
	}
	echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
	echo '<meta property="og:image:width" content="' . (int) $w . '">' . "\n";
	echo '<meta property="og:image:height" content="' . (int) $h . '">' . "\n";
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url( $img ) . '">' . "\n";
}, 1 );

// Favicon — sceau CP, utilise seulement si aucune icone de site n'est reglee
// dans Reglages > General.
add_action( 'wp_head', function () {
	if ( has_site_icon() ) {
		return;
	}
	// Sceau compact vectorise (favicon.svg), lisible a 16 px ; l'ancien
	// favicon ecrivait « CP » en Georgia faute de police chargee.
	$ico = get_template_directory_uri() . '/favicon.svg';
	echo '<link rel="icon" type="image/svg+xml" href="' . esc_url( $ico ) . '">' . "\n";
}, 1 );

/**
 * Donnees structurees.
 * Accueil : la boutique. Fiche parfum : le produit, avec son prix et sa
 * disponibilite — c'est ce qui permet a Google d'afficher le prix.
 */
add_action( 'wp_head', function () {
	$slug = comptoir_parfum_slug_demande();

	if ( $slug && ( $p = comptoir_produit_by_slug( $slug ) ) ) {
		$data = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Product',
			'name'        => $p['b'] . ' ' . $p['n'],
			'brand'       => array( '@type' => 'Brand', 'name' => $p['b'] ),
			'category'    => $p['fam'],
			'description' => $p['d'],
			'url'         => comptoir_parfum_url( $p['s'] ),
			'offers'      => array(
				'@type'         => 'Offer',
				'price'         => (int) preg_replace( '/[^0-9]/', '', $p['pr'] ),
				'priceCurrency' => 'MAD',
				'availability'  => 'https://schema.org/InStock',
				'url'           => comptoir_parfum_url( $p['s'] ),
			),
		);
		if ( comptoir_a_photo( $p['s'] ) ) {
			$data['image'] = comptoir_photo_url( $p['s'] );
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $data ) . '</script>' . "\n";
		return;
	}

	if ( ! is_front_page() || comptoir_commander_demande() ) {
		return;
	}

	$data = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Store',
		'name'        => get_bloginfo( 'name' ),
		'url'         => home_url( '/' ),
		'image'       => get_template_directory_uri() . '/og-image.jpg',
		'description' => 'Testeurs originaux des plus grandes maisons de parfum — même jus qu\'en boutique, sans le coffret.',
		'areaServed'  => array( '@type' => 'Country', 'name' => 'Maroc' ),
		'telephone'   => '+' . comptoir_wa_numero(),
		'priceRange'  => implode( ' – ', comptoir_bornes_prix() ) . ' MAD',
		'currenciesAccepted' => 'MAD',
		'paymentAccepted'    => 'Espèces à la livraison',
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $data ) . '</script>' . "\n";
}, 2 );
