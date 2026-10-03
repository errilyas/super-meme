<?php
/**
 * Comptoir Boutique — avis clients verifies.
 *
 * Seul un acheteur peut laisser un avis : le lien du formulaire est signe
 * (numero de commande + empreinte wp_hash) et part dans le message WhatsApp
 * « demander un avis » du carnet de commandes. Chaque avis arrive « en
 * attente » ; il n'apparait sur la fiche qu'une fois publie dans
 * l'administration (menu « Avis clients »). Rien n'est affiche tant qu'aucun
 * avis n'est publie : pas de note inventee, pas d'etoiles vides.
 *
 * @package Comptoir_Boutique
 */

defined( 'ABSPATH' ) || exit;

/* ══════════════════════════════════════════════════════════════
   TYPE DE CONTENU
══════════════════════════════════════════════════════════════ */
add_action( 'init', function () {
	register_post_type( 'cp_avis', array(
		'labels'          => array(
			'name'          => 'Avis clients',
			'singular_name' => 'Avis client',
			'menu_name'     => 'Avis clients',
			'all_items'     => 'Tous les avis',
			'edit_item'     => 'Modifier l’avis',
			'search_items'  => 'Chercher un avis',
			'not_found'     => 'Aucun avis pour l’instant.',
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'menu_position'   => 26,
		'menu_icon'       => 'dashicons-star-filled',
		'supports'        => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		'capability_type' => 'post',
		'map_meta_cap'    => true,
		'show_in_rest'    => true,
		'rest_base'       => 'cp-avis',
	) );
	// Champs lisibles et modifiables par l'API (editeurs seulement) : un avis
	// recu sur WhatsApp peut ainsi etre saisi depuis l'administration ou un
	// outil connecte, avec les memes champs que le formulaire.
	$auth = function () {
		return current_user_can( 'edit_posts' );
	};
	foreach ( array( 'cpa_note' => 'integer', 'cpa_parfum' => 'string', 'cpa_prenom' => 'string', 'cpa_ville' => 'string', 'cpa_verifie' => 'string', 'cpa_source' => 'string' ) as $k => $type ) {
		register_post_meta( 'cp_avis', $k, array(
			'type'          => $type,
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => $auth,
		) );
	}
} );

/** Empreinte du lien d'avis d'une commande. */
function cpb_avis_cle( $commande ) {
	return substr( wp_hash( 'cpb_avis|' . (int) $commande ), 0, 20 );
}

/** Lien signe vers le formulaire d'avis d'une commande. */
function cpb_url_avis( $commande ) {
	return add_query_arg( array( 'avis' => (int) $commande, 'k' => cpb_avis_cle( $commande ) ), home_url( '/' ) );
}

/** Parfums d'une commande : slug => nom, lus dans le recapitulatif enregistre. */
function cpb_avis_parfums_commande( $commande ) {
	$post = get_post( $commande );
	$out  = array();
	if ( ! $post || 'cp_commande' !== $post->post_type ) {
		return $out;
	}
	if ( preg_match_all( '/\(([a-z0-9-]+--[a-z0-9-]+)\)/', $post->post_content, $m ) ) {
		foreach ( array_unique( $m[1] ) as $slug ) {
			$p = function_exists( 'comptoir_produit_by_slug' ) ? comptoir_produit_by_slug( $slug ) : null;
			if ( $p ) {
				$out[ $slug ] = $p['b'] . ' ' . $p['n'];
			}
		}
	}
	return $out;
}

/** Avis deja laisse pour ce parfum de cette commande (tous etats sauf corbeille). */
function cpb_avis_existant( $commande, $slug ) {
	$q = get_posts( array(
		'post_type'      => 'cp_avis',
		'post_status'    => array( 'pending', 'publish', 'draft', 'private' ),
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array( 'key' => 'cpa_commande', 'value' => (int) $commande ),
			array( 'key' => 'cpa_parfum', 'value' => $slug ),
		),
	) );
	return $q ? (int) $q[0] : 0;
}

/* ══════════════════════════════════════════════════════════════
   LECTURE : AVIS PUBLIES D'UN PARFUM
══════════════════════════════════════════════════════════════ */
function cpb_avis_parfum( $slug ) {
	static $cache = array();
	if ( isset( $cache[ $slug ] ) ) {
		return $cache[ $slug ];
	}
	$avis  = array();
	$somme = 0;
	foreach ( get_posts( array(
		'post_type'      => 'cp_avis',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_key'       => 'cpa_parfum', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'     => $slug, // phpcs:ignore WordPress.DB.SlowDBQuery
	) ) as $p ) {
		$note = max( 1, min( 5, (int) get_post_meta( $p->ID, 'cpa_note', true ) ) );
		$somme += $note;
		$avis[] = array(
			'note'   => $note,
			'texte'  => trim( wp_strip_all_tags( $p->post_content ) ),
			'prenom' => (string) get_post_meta( $p->ID, 'cpa_prenom', true ),
			'ville'  => (string) get_post_meta( $p->ID, 'cpa_ville', true ),
			'date'   => get_the_date( 'Y-m-d', $p ),
			'photo'  => (int) get_post_thumbnail_id( $p ),
			// Les avis du formulaire viennent forcement d'une commande ; ceux
			// saisis a la main le sont si la case est cochee.
			'verifie' => '0' !== (string) get_post_meta( $p->ID, 'cpa_verifie', true ),
			'source'  => (string) get_post_meta( $p->ID, 'cpa_source', true ),
		);
	}
	$n               = count( $avis );
	$cache[ $slug ] = array(
		'n'    => $n,
		'moy'  => $n ? round( $somme / $n, 1 ) : 0,
		'avis' => $avis,
	);
	return $cache[ $slug ];
}

/** Note au format francais : 4,8. */
function cpb_avis_note_txt( $v ) {
	return str_replace( '.', ',', (string) ( floor( $v ) == $v ? (int) $v : $v ) ); // phpcs:ignore Universal.Operators.StrictComparisons
}

/** Cinq etoiles dont la part remplie suit la note. */
function cpb_avis_etoiles( $note, $classe = 'cpb-etoiles' ) {
	$pct = max( 0, min( 100, $note / 5 * 100 ) );
	return '<span class="' . esc_attr( $classe ) . '" aria-hidden="true"><span>★★★★★</span><span class="cpb-etoiles-pleines" style="width:' . esc_attr( $pct ) . '%">★★★★★</span></span>';
}

/* ══════════════════════════════════════════════════════════════
   FICHE PARFUM : SECTION « AVIS CLIENTS » ET DONNEES STRUCTUREES
══════════════════════════════════════════════════════════════ */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'cpb_actif' ) || ! cpb_actif() || ! function_exists( 'comptoir_parfum_slug_demande' ) ) {
		return;
	}
	$slug = comptoir_parfum_slug_demande();
	if ( ! $slug ) {
		return;
	}
	$r = cpb_avis_parfum( $slug );
	if ( ! $r['n'] ) {
		return;
	}
	echo '<section class="pf-block cpb-avis" id="avis" aria-labelledby="cpb-avis-titre">';
	echo '<h2 id="cpb-avis-titre" data-cpb-ar="آراء الزبناء">Avis clients</h2>';
	echo '<p class="cpb-avis-moy">' . cpb_avis_etoiles( $r['moy'] ) . ' <strong>' . esc_html( cpb_avis_note_txt( $r['moy'] ) ) . '</strong>/5 · '
		. '<span data-cpb-ar="' . esc_attr( $r['n'] . ( $r['n'] > 1 ? ' آراء موثقة' : ' رأي موثق' ) ) . '">' . esc_html( $r['n'] . ( $r['n'] > 1 ? ' avis vérifiés' : ' avis vérifié' ) ) . '</span></p>';
	echo '<ul class="cpb-avis-liste">';
	foreach ( array_slice( $r['avis'], 0, 20 ) as $a ) {
		echo '<li class="cpb-avis-item">';
		echo '<p class="cpb-avis-note">' . cpb_avis_etoiles( $a['note'] ) . '<span class="screen-reader-text">' . esc_html( $a['note'] ) . '/5</span></p>';
		if ( '' !== $a['texte'] ) {
			echo '<blockquote class="cpb-avis-texte"><p>' . esc_html( $a['texte'] ) . '</p></blockquote>';
		}
		if ( $a['photo'] ) {
			$grande = wp_get_attachment_image_url( $a['photo'], 'large' );
			$img    = wp_get_attachment_image( $a['photo'], 'medium', false, array(
				'class'    => 'cpb-avis-img',
				'loading'  => 'lazy',
				'decoding' => 'async',
				'alt'      => ( 'whatsapp' === $a['source'] ? 'Conversation WhatsApp avec ' : 'Photo envoyée par ' ) . ( $a['prenom'] ? $a['prenom'] : 'le client' ),
			) );
			if ( $img ) {
				echo '<a class="cpb-avis-photo" href="' . esc_url( $grande ) . '" target="_blank" rel="noopener">' . $img . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput -- balise produite par WordPress.
			}
		}
		$qui = trim( $a['prenom'] . ( $a['ville'] ? ' · ' . $a['ville'] : '' ) );
		echo '<p class="cpb-avis-qui">' . esc_html( $qui ) . ( $qui ? ' · ' : '' )
			. '<time datetime="' . esc_attr( $a['date'] ) . '">' . esc_html( date_i18n( 'j F Y', strtotime( $a['date'] ) ) ) . '</time>'
			. ( $a['verifie'] ? ' · <span class="cpb-avis-verifie" data-cpb-ar="شراء موثق">Achat vérifié</span>' : '' ) . '</p>';
		if ( 'whatsapp' === $a['source'] ) {
			echo '<p class="cpb-avis-source"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>'
				. '<span data-cpb-ar="رأي توصلنا به على واتساب">Avis reçu sur WhatsApp</span></p>';
		}
		echo '</li>';
	}
	echo '</ul></section>';
}, 4 );

/** Note moyenne et avis dans le JSON-LD Product (appele par cpb_enrichit_ld). */
function cpb_avis_ld( $d ) {
	if ( ! function_exists( 'comptoir_parfum_slug_demande' ) ) {
		return $d;
	}
	$slug = comptoir_parfum_slug_demande();
	if ( ! $slug ) {
		return $d;
	}
	$r = cpb_avis_parfum( $slug );
	if ( ! $r['n'] ) {
		return $d;
	}
	$d['aggregateRating'] = array(
		'@type'       => 'AggregateRating',
		'ratingValue' => $r['moy'],
		'reviewCount' => $r['n'],
		'bestRating'  => 5,
		'worstRating' => 1,
	);
	$d['review'] = array();
	foreach ( array_slice( $r['avis'], 0, 5 ) as $a ) {
		$rv = array(
			'@type'         => 'Review',
			'reviewRating'  => array( '@type' => 'Rating', 'ratingValue' => $a['note'], 'bestRating' => 5, 'worstRating' => 1 ),
			'author'        => array( '@type' => 'Person', 'name' => $a['prenom'] ? $a['prenom'] : 'Client vérifié' ),
			'datePublished' => $a['date'],
		);
		if ( '' !== $a['texte'] ) {
			$rv['reviewBody'] = $a['texte'];
		}
		$d['review'][] = $rv;
	}
	return $d;
}

/** Resume pour le JavaScript (lien « ★ 4,8 · 12 avis » sous le nom). */
function cpb_avis_resume_cfg() {
	if ( ! function_exists( 'comptoir_parfum_slug_demande' ) || ! comptoir_parfum_slug_demande() ) {
		return null;
	}
	$r = cpb_avis_parfum( comptoir_parfum_slug_demande() );
	return $r['n'] ? array( 'moy' => $r['moy'], 'n' => $r['n'] ) : null;
}

/* ══════════════════════════════════════════════════════════════
   FORMULAIRE : /?avis=<commande>&k=<cle>
══════════════════════════════════════════════════════════════ */
function cpb_avis_demande() {
	if ( empty( $_GET['avis'] ) || empty( $_GET['k'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return 0;
	}
	$id  = absint( $_GET['avis'] ); // phpcs:ignore WordPress.Security.NonceVerification
	$cle = sanitize_text_field( wp_unslash( $_GET['k'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	return ( $id && hash_equals( cpb_avis_cle( $id ), $cle ) && cpb_avis_parfums_commande( $id ) ) ? $id : -1;
}

add_action( 'template_redirect', function () {
	$id = cpb_avis_demande();
	if ( ! $id ) {
		return;
	}
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow' );
	add_filter( 'wp_robots', function ( $r ) {
		$r['noindex']  = true;
		$r['nofollow'] = true;
		return $r;
	} );
	add_filter( 'pre_get_document_title', function () {
		return 'Votre avis — ' . get_bloginfo( 'name' );
	}, 99 );
	add_filter( 'body_class', function ( $c ) {
		$c[] = 'cpb-page-avis';
		return $c;
	} );

	$etat    = '';
	$parfums = $id > 0 ? cpb_avis_parfums_commande( $id ) : array();
	if ( $id > 0 && 'POST' === $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$etat = cpb_avis_enregistre( $id, $parfums );
	}
	status_header( $id > 0 ? 200 : 404 );
	get_header();
	cpb_avis_page( $id, $parfums, $etat );
	get_footer();
	exit;
}, 1 );

/** Enregistre les avis envoyes. Renvoie 'merci', 'vide' ou 'erreur'. */
function cpb_avis_enregistre( $id, $parfums ) {
	if ( ! isset( $_POST['cpb_avis_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cpb_avis_nonce'] ) ), 'cpb_avis_' . $id ) ) {
		return 'erreur';
	}
	if ( ! empty( $_POST['site_web'] ) ) { // champ piege, invisible aux humains
		return 'merci';
	}
	$prenom = isset( $_POST['prenom'] ) ? sanitize_text_field( wp_unslash( $_POST['prenom'] ) ) : '';
	$prenom = function_exists( 'mb_substr' ) ? mb_substr( $prenom, 0, 30 ) : substr( $prenom, 0, 30 );
	$ville  = (string) get_post_meta( $id, 'cp_ville', true );
	$notes  = isset( $_POST['note'] ) && is_array( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$textes = isset( $_POST['texte'] ) && is_array( $_POST['texte'] ) ? wp_unslash( $_POST['texte'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$n      = 0;
	foreach ( $parfums as $slug => $nom ) {
		$note = isset( $notes[ $slug ] ) ? (int) $notes[ $slug ] : 0;
		if ( $note < 1 || $note > 5 ) {
			continue;
		}
		$texte = isset( $textes[ $slug ] ) ? sanitize_textarea_field( $textes[ $slug ] ) : '';
		$texte = function_exists( 'mb_substr' ) ? mb_substr( $texte, 0, 800 ) : substr( $texte, 0, 800 );
		$deja  = cpb_avis_existant( $id, $slug );
		$post  = array(
			'post_type'    => 'cp_avis',
			'post_status'  => 'pending',
			'post_title'   => sprintf( '%s/5 — %s — %s', $note, $nom, $prenom ? $prenom : 'Client' ),
			'post_content' => $texte,
		);
		if ( $deja ) {
			$post['ID'] = $deja;
			$pid        = wp_update_post( $post, true );
		} else {
			$pid = wp_insert_post( $post, true );
		}
		if ( is_wp_error( $pid ) || ! $pid ) {
			continue;
		}
		update_post_meta( $pid, 'cpa_note', $note );
		update_post_meta( $pid, 'cpa_parfum', $slug );
		update_post_meta( $pid, 'cpa_prenom', $prenom );
		update_post_meta( $pid, 'cpa_ville', $ville );
		update_post_meta( $pid, 'cpa_commande', $id );
		update_post_meta( $pid, 'cpa_verifie', '1' );
		update_post_meta( $pid, 'cpa_source', 'formulaire' );
		$n++;
	}
	return $n ? 'merci' : 'vide';
}

/** La page du formulaire. */
function cpb_avis_page( $id, $parfums, $etat ) {
	echo '<main id="cp-main" class="cp-secours cpb-page cpb-avis-page">';
	if ( $id < 1 ) {
		echo '<h1 data-cpb-ar="هذا الرابط غير صالح">Ce lien n’est plus valable</h1>';
		echo '<p data-cpb-ar="اكتب لنا على واتساب وسنرسل لك رابطًا جديدًا.">Écrivez-nous sur WhatsApp, nous vous enverrons un nouveau lien.</p>';
		echo '</main>';
		return;
	}
	if ( 'merci' === $etat ) {
		echo '<h1 data-cpb-ar="شكرًا على رأيك!">Merci pour votre avis !</h1>';
		echo '<p data-cpb-ar="سيظهر على الموقع بعد مراجعته.">Il apparaîtra sur le site après une rapide relecture.</p>';
		echo '<p><a class="cpb-avis-retour" href="' . esc_url( home_url( '/' ) ) . '" data-cpb-ar="العودة إلى المتجر">Retour à la boutique</a></p>';
		echo '</main>';
		return;
	}
	$nom    = trim( (string) get_post_meta( $id, 'cp_nom', true ) );
	$prenom = $nom ? preg_split( '/\s+/u', $nom )[0] : '';
	echo '<p class="cpb-titre-petit" data-cpb-ar="رأيك يهمنا">Votre avis compte</p>';
	echo '<h1 data-cpb-ar="كيف وجدت عطرك؟">Votre parfum vous plaît ?</h1>';
	echo '<p class="cpb-avis-intro" data-cpb-ar="ثلاثون ثانية تكفي: نقطة، وكلمة إن أردت. يُنشر رأيك باسمك الأول ومدينتك فقط.">Trente secondes suffisent : une note, et un mot si vous le souhaitez. Votre avis est publié avec votre prénom et votre ville seulement.</p>';
	if ( 'vide' === $etat ) {
		echo '<p class="cpb-avis-erreur" role="alert" data-cpb-ar="اختر عدد النجوم لعطر واحد على الأقل.">Choisissez un nombre d’étoiles pour au moins un parfum.</p>';
	} elseif ( 'erreur' === $etat ) {
		echo '<p class="cpb-avis-erreur" role="alert" data-cpb-ar="انتهت صلاحية الصفحة، أعد المحاولة.">La page a expiré, merci de réessayer.</p>';
	}
	echo '<form class="cpb-avis-form" method="post" action="' . esc_url( cpb_url_avis( $id ) ) . '" data-clarity-mask="true">';
	wp_nonce_field( 'cpb_avis_' . $id, 'cpb_avis_nonce' );
	echo '<p class="cpb-avis-piege" aria-hidden="true"><label>Site web <input type="text" name="site_web" tabindex="-1" autocomplete="off"></label></p>';
	$i = 0;
	foreach ( $parfums as $slug => $nom_p ) {
		$i++;
		$p = function_exists( 'comptoir_produit_by_slug' ) ? comptoir_produit_by_slug( $slug ) : null;
		echo '<fieldset class="cpb-avis-parfum">';
		echo '<legend><span class="cpb-avis-maison">' . esc_html( $p ? $p['b'] : '' ) . '</span> <span class="cpb-avis-nom">' . esc_html( $p ? $p['n'] : $nom_p ) . '</span></legend>';
		echo '<div class="cpb-avis-choix" role="radiogroup" aria-label="' . esc_attr( 'Note pour ' . $nom_p ) . '">';
		for ( $n = 5; $n >= 1; $n-- ) {
			$fid = 'cpb-n-' . $i . '-' . $n;
			echo '<input type="radio" id="' . esc_attr( $fid ) . '" name="note[' . esc_attr( $slug ) . ']" value="' . (int) $n . '">';
			echo '<label for="' . esc_attr( $fid ) . '" title="' . (int) $n . '/5"><span aria-hidden="true">★</span><span class="screen-reader-text">' . (int) $n . ' / 5</span></label>';
		}
		echo '</div>';
		echo '<label class="cpb-avis-lab" for="cpb-t-' . (int) $i . '" data-cpb-ar="كلمة (اختياري)">Un mot (facultatif)</label>';
		echo '<textarea id="cpb-t-' . (int) $i . '" name="texte[' . esc_attr( $slug ) . ']" rows="3" maxlength="800" placeholder="Tenue, sillage, ce que vous en pensez…" data-cpb-ar-ph="الثبات، الفوحان، رأيك…"></textarea>';
		echo '</fieldset>';
	}
	echo '<label class="cpb-avis-lab" for="cpb-prenom" data-cpb-ar="الاسم الأول">Prénom</label>';
	echo '<input id="cpb-prenom" name="prenom" type="text" maxlength="30" value="' . esc_attr( $prenom ) . '" autocomplete="given-name">';
	echo '<button type="submit" class="cpb-avis-go" data-cpb-ar="إرسال رأيي">Envoyer mon avis</button>';
	echo '</form></main>';
}

/* ══════════════════════════════════════════════════════════════
   ADMINISTRATION : COLONNES, ET LIEN DANS LE MESSAGE WHATSAPP
══════════════════════════════════════════════════════════════ */
add_filter( 'manage_cp_avis_posts_columns', function ( $c ) {
	return array(
		'cb'        => $c['cb'],
		'title'     => 'Avis',
		'cpa_note'  => 'Note',
		'cpa_texte' => 'Texte',
		'cpa_cmd'   => 'Commande',
		'date'      => $c['date'],
	);
} );
add_action( 'manage_cp_avis_posts_custom_column', function ( $col, $id ) {
	if ( 'cpa_note' === $col ) {
		echo esc_html( str_repeat( '★', (int) get_post_meta( $id, 'cpa_note', true ) ) );
	} elseif ( 'cpa_texte' === $col ) {
		echo esc_html( wp_trim_words( get_post_field( 'post_content', $id ), 30 ) );
	} elseif ( 'cpa_cmd' === $col ) {
		$c = (int) get_post_meta( $id, 'cpa_commande', true );
		echo $c ? '<a href="' . esc_url( get_edit_post_link( $c ) ) . '">' . esc_html( get_post_meta( $c, 'cp_ref', true ) ) . '</a>' : '';
	}
}, 10, 2 );

/* Pastille du nombre d'avis en attente, a cote du menu. */
add_action( 'admin_menu', function () {
	global $menu;
	$n = (int) wp_count_posts( 'cp_avis' )->pending;
	if ( ! $n || ! is_array( $menu ) ) {
		return;
	}
	foreach ( $menu as $k => $item ) {
		if ( isset( $item[2] ) && 'edit.php?post_type=cp_avis' === $item[2] ) {
			$menu[ $k ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . $n . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}
}, 99 );

/* ══════════════════════════════════════════════════════════════
   SAISIE A LA MAIN : UN AVIS RECU SUR WHATSAPP
   « Avis clients > Ajouter » : parfum, note, prenom, ville, achat
   verifie, et la photo du client en « image mise en avant ». Le titre se
   remplit tout seul s'il est laisse vide.
══════════════════════════════════════════════════════════════ */
// « Image mise en avant » pour les avis (photo du client), meme si le theme
// ne l'active pas ailleurs.
add_action( 'after_setup_theme', function () {
	add_theme_support( 'post-thumbnails', array( 'cp_avis' ) );
}, 20 );

add_action( 'add_meta_boxes_cp_avis', function () {
	add_meta_box( 'cpb-avis-champs', 'Détails de l’avis', 'cpb_avis_boite', 'cp_avis', 'normal', 'high' );
} );

function cpb_avis_boite( $post ) {
	wp_nonce_field( 'cpb_avis_boite', 'cpb_avis_boite_nonce' );
	$v = function ( $k ) use ( $post ) {
		return (string) get_post_meta( $post->ID, $k, true );
	};
	$slug    = $v( 'cpa_parfum' );
	$note    = (int) $v( 'cpa_note' );
	$verifie = '0' !== $v( 'cpa_verifie' );
	echo '<p><label for="cpa_parfum"><strong>Parfum</strong></label><br><select id="cpa_parfum" name="cpa_parfum" style="max-width:100%"><option value="">— Choisir —</option>';
	if ( function_exists( 'comptoir_produits' ) ) {
		foreach ( comptoir_produits() as $p ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $p['s'] ), selected( $slug, $p['s'], false ), esc_html( $p['b'] . ' — ' . $p['n'] ) );
		}
	}
	echo '</select></p>';
	echo '<p><strong>Note</strong><br>';
	for ( $i = 5; $i >= 1; $i-- ) {
		printf( '<label style="margin-right:14px"><input type="radio" name="cpa_note" value="%1$d"%2$s> %3$s</label>', (int) $i, checked( $note, $i, false ), esc_html( str_repeat( '★', $i ) ) );
	}
	echo '</p>';
	printf( '<p><label for="cpa_prenom"><strong>Prénom</strong> (publié)</label><br><input id="cpa_prenom" name="cpa_prenom" type="text" maxlength="30" value="%s"></p>', esc_attr( $v( 'cpa_prenom' ) ) );
	printf( '<p><label for="cpa_ville"><strong>Ville</strong> (publiée)</label><br><input id="cpa_ville" name="cpa_ville" type="text" maxlength="40" value="%s"></p>', esc_attr( $v( 'cpa_ville' ) ) );
	printf( '<p><label><input type="checkbox" name="cpa_verifie" value="1"%s> Achat vérifié (le client a bien commandé chez nous)</label></p>', checked( $verifie, true, false ) );
	$source = $v( 'cpa_source' ) ? $v( 'cpa_source' ) : ( 'auto-draft' === $post->post_status ? 'whatsapp' : '' );
	printf( '<p><label><input type="checkbox" name="cpa_source_wa" value="1"%s> Avis reçu sur WhatsApp (affiche la mention « Avis reçu sur WhatsApp »)</label></p>', checked( 'whatsapp', $source, false ) );
	echo '<p class="description">Le texte de l’avis va dans la grande zone ci-dessus (les mots du client, sans les modifier). La photo du client, ou la capture de la conversation WhatsApp avec le numéro masqué : « Image mise en avant », à droite. Ne publiez qu’avec l’accord du client, et jamais une photo où l’on voit un numéro ou un visage sans son accord.</p>';
}

add_action( 'save_post_cp_avis', function ( $id, $post ) {
	if ( ! isset( $_POST['cpb_avis_boite_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cpb_avis_boite_nonce'] ) ), 'cpb_avis_boite' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	$slug = isset( $_POST['cpa_parfum'] ) ? sanitize_key( wp_unslash( $_POST['cpa_parfum'] ) ) : '';
	if ( $slug && function_exists( 'comptoir_produit_by_slug' ) && comptoir_produit_by_slug( $slug ) ) {
		update_post_meta( $id, 'cpa_parfum', $slug );
	}
	$note = isset( $_POST['cpa_note'] ) ? (int) $_POST['cpa_note'] : 0;
	if ( $note >= 1 && $note <= 5 ) {
		update_post_meta( $id, 'cpa_note', $note );
	}
	foreach ( array( 'cpa_prenom' => 30, 'cpa_ville' => 40 ) as $k => $max ) {
		if ( isset( $_POST[ $k ] ) ) {
			$val = sanitize_text_field( wp_unslash( $_POST[ $k ] ) );
			update_post_meta( $id, $k, function_exists( 'mb_substr' ) ? mb_substr( $val, 0, $max ) : substr( $val, 0, $max ) );
		}
	}
	update_post_meta( $id, 'cpa_verifie', empty( $_POST['cpa_verifie'] ) ? '0' : '1' );
	if ( ! empty( $_POST['cpa_source_wa'] ) ) {
		update_post_meta( $id, 'cpa_source', 'whatsapp' );
	} elseif ( 'whatsapp' === get_post_meta( $id, 'cpa_source', true ) ) {
		update_post_meta( $id, 'cpa_source', 'manuel' );
	}
	// Titre automatique, pour s'y retrouver dans la liste.
	if ( '' === trim( $post->post_title ) || 'Brouillon auto' === $post->post_title ) {
		$p = function_exists( 'comptoir_produit_by_slug' ) && $slug ? comptoir_produit_by_slug( $slug ) : null;
		remove_all_actions( 'save_post_cp_avis' );
		wp_update_post( array(
			'ID'         => $id,
			'post_title' => sprintf( '%s/5 — %s — %s', $note ? $note : '?', $p ? $p['b'] . ' ' . $p['n'] : 'Parfum', get_post_meta( $id, 'cpa_prenom', true ) ? get_post_meta( $id, 'cpa_prenom', true ) : 'Client' ),
		) );
	}
}, 10, 2 );

/* Un avis sans parfum ou sans note ne s'affiche nulle part : on le dit. */
add_action( 'admin_notices', function () {
	$ecran = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $ecran || 'cp_avis' !== $ecran->post_type || 'post' !== $ecran->base ) {
		return;
	}
	global $post;
	if ( $post && 'publish' === $post->post_status && ( ! get_post_meta( $post->ID, 'cpa_parfum', true ) || ! get_post_meta( $post->ID, 'cpa_note', true ) ) ) {
		echo '<div class="notice notice-warning"><p>Cet avis est publié mais n’a pas de parfum ou de note : il ne s’affiche sur aucune fiche.</p></div>';
	}
} );

/* ══════════════════════════════════════════════════════════════
   « ILS ONT RECU LEUR PARFUM » : LES CAPTURES DES AVIS WHATSAPP
   La rangee du theme lit les images de son dossier img/preuves/. Les
   avis publies marques « recu sur WhatsApp » et munis d'une image s'y
   ajoutent en tete, les plus recents d'abord (boutique.js), sans
   toucher au theme.
══════════════════════════════════════════════════════════════ */
function cpb_avis_preuves() {
	$out = array();
	foreach ( get_posts( array(
		'post_type'      => 'cp_avis',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_key'       => 'cpa_source', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'     => 'whatsapp', // phpcs:ignore WordPress.DB.SlowDBQuery
	) ) as $p ) {
		$img = (int) get_post_thumbnail_id( $p );
		if ( ! $img ) {
			continue;
		}
		$src = wp_get_attachment_image_src( $img, 'large' );
		if ( ! $src ) {
			continue;
		}
		$out[] = array(
			'src' => $src[0],
			'w'   => (int) $src[1],
			'h'   => (int) $src[2],
		);
	}
	return $out;
}
