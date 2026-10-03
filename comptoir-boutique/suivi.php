<?php
/**
 * Comptoir Boutique — suivi de commande.
 *
 * Cote boutique : sous chaque commande (Commandes), des liens d'un clic
 * « Confirmée », « Expédiée », « Livrée » (ou « Annulée »), et un message
 * WhatsApp pret a envoyer au client avec son lien de suivi.
 * Cote client : /?suivi=1, reference + telephone, et les etapes datees.
 * Le lien envoye sur WhatsApp (/?suivi=<ref>&k=<cle>) s'ouvre sans rien
 * saisir.
 *
 * Les etapes vivent dans des metas a part (cpb_suivi, cpb_suivi_dates) :
 * l'« etat » du theme (a-rappeler, a-verifier) reste un repere interne.
 *
 * @package Comptoir_Boutique
 */

defined( 'ABSPATH' ) || exit;

/** Les etapes, dans l'ordre : cle => [francais, arabe]. */
function cpb_suivi_etapes() {
	return array(
		'recue'     => array( 'Commande reçue', 'تم استلام الطلب' ),
		'confirmee' => array( 'Confirmée', 'تم التأكيد' ),
		'expediee'  => array( 'Expédiée', 'تم الإرسال' ),
		'livree'    => array( 'Livrée', 'تم التوصيل' ),
	);
}

/** Etape courante d'une commande ('recue' par defaut, ou 'annulee'). */
function cpb_suivi_etat( $id ) {
	$e = (string) get_post_meta( $id, 'cpb_suivi', true );
	return ( isset( cpb_suivi_etapes()[ $e ] ) || 'annulee' === $e ) ? $e : 'recue';
}

/** Dates des etapes : cle => horodatage. La reception = date de la commande. */
function cpb_suivi_dates( $id ) {
	$d = get_post_meta( $id, 'cpb_suivi_dates', true );
	$d = is_array( $d ) ? $d : array();
	if ( empty( $d['recue'] ) ) {
		$d['recue'] = (int) get_post_time( 'U', true, $id );
	}
	return $d;
}

function cpb_suivi_change( $id, $etat ) {
	if ( ! isset( cpb_suivi_etapes()[ $etat ] ) && 'annulee' !== $etat ) {
		return;
	}
	$d          = cpb_suivi_dates( $id );
	$d[ $etat ] = time();
	// Revenir en arriere efface les dates des etapes suivantes.
	$cles = array_keys( cpb_suivi_etapes() );
	$pos  = array_search( $etat, $cles, true );
	if ( false !== $pos ) {
		foreach ( array_slice( $cles, $pos + 1 ) as $k ) {
			unset( $d[ $k ] );
		}
		unset( $d['annulee'] );
	}
	update_post_meta( $id, 'cpb_suivi', $etat );
	update_post_meta( $id, 'cpb_suivi_dates', $d );
	do_action( 'cpb_suivi_change', $id, $etat );
}

/** Cle du lien de suivi direct. */
function cpb_suivi_cle( $id ) {
	return substr( wp_hash( 'cpb_suivi|' . (int) $id ), 0, 16 );
}

function cpb_url_suivi( $id = 0 ) {
	if ( ! $id ) {
		return add_query_arg( 'suivi', '1', home_url( '/' ) );
	}
	return add_query_arg( array( 'suivi' => (string) get_post_meta( $id, 'cp_ref', true ), 'k' => cpb_suivi_cle( $id ) ), home_url( '/' ) );
}

/** Telephone ramene a 9 chiffres (sans 0 ni 212) pour comparer. */
function cpb_suivi_tel( $tel ) {
	$t = preg_replace( '/\D/', '', (string) $tel );
	$t = preg_replace( '/^(00212|212|0)/', '', $t );
	return substr( $t, -9 );
}

/** Commande d'apres sa reference (CP-…). */
function cpb_suivi_trouve( $ref ) {
	$ref = strtoupper( trim( (string) $ref ) );
	if ( ! preg_match( '/^CP-[0-9A-Z-]{4,20}$/', $ref ) ) {
		return 0;
	}
	$q = get_posts( array(
		'post_type'      => 'cp_commande',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => 'cp_ref', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'     => $ref, // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	return $q ? (int) $q[0] : 0;
}

/* ══════════════════════════════════════════════════════════════
   ADMINISTRATION
══════════════════════════════════════════════════════════════ */
add_filter( 'manage_cp_commande_posts_columns', function ( $cols ) {
	$out = array();
	foreach ( $cols as $k => $v ) {
		$out[ $k ] = $v;
		if ( 'title' === $k ) {
			$out['cpb_suivi'] = 'Suivi';
		}
	}
	if ( ! isset( $out['cpb_suivi'] ) ) {
		$out['cpb_suivi'] = 'Suivi';
	}
	return $out;
}, 30 );

add_action( 'manage_cp_commande_posts_custom_column', function ( $col, $id ) {
	if ( 'cpb_suivi' !== $col ) {
		return;
	}
	$e = cpb_suivi_etat( $id );
	$n = 'annulee' === $e ? 'Annulée' : cpb_suivi_etapes()[ $e ][0];
	$c = array( 'recue' => '#b26200', 'confirmee' => '#2271b1', 'expediee' => '#7b3fa0', 'livree' => '#00813a', 'annulee' => '#8a2424' );
	printf( '<strong style="color:%s">%s</strong>', esc_attr( $c[ $e ] ), esc_html( $n ) );
}, 10, 2 );

/** Lien d'action signe pour passer une commande a une etape. */
function cpb_suivi_lien_admin( $id, $etat ) {
	return wp_nonce_url( add_query_arg( array( 'action' => 'cpb_suivi', 'commande' => (int) $id, 'etat' => $etat ), admin_url( 'admin-post.php' ) ), 'cpb_suivi_' . $id );
}

/** Message WhatsApp au client, selon l'etape. */
function cpb_suivi_lien_wa( $id ) {
	$tel = cpb_suivi_tel( get_post_meta( $id, 'cp_tel', true ) );
	if ( ! preg_match( '/^[5-7]\d{8}$/', $tel ) ) {
		return '';
	}
	$e      = cpb_suivi_etat( $id );
	$nom    = trim( (string) get_post_meta( $id, 'cp_nom', true ) );
	$prenom = $nom ? ' ' . preg_split( '/\s+/u', $nom )[0] : '';
	$ref    = (string) get_post_meta( $id, 'cp_ref', true );
	$textes = array(
		'recue'     => array( "Bonjour%s, c'est Le Comptoir des Parfums. Nous avons bien reçu votre commande %s.", 'السلام%s، توصلنا بطلبك %s.' ),
		'confirmee' => array( 'Bonjour%s, votre commande %s est confirmée. Nous la préparons.', 'السلام%s، طلبك %s تأكد. كنوجدوه دابا.' ),
		'expediee'  => array( 'Bonjour%s, votre commande %s est partie ! Le livreur vous appellera. Paiement à la réception.', 'السلام%s، طلبك %s خرج! الموزع غادي يتاصل بيك. الدفع عند الاستلام.' ),
		'livree'    => array( 'Bonjour%s, votre commande %s est livrée. Merci pour votre confiance !', 'السلام%s، طلبك %s توصل. شكرا على ثقتك!' ),
		'annulee'   => array( 'Bonjour%s, votre commande %s a été annulée.', 'السلام%s، طلبك %s تلغى.' ),
	);
	$t   = $textes[ $e ];
	$msg = sprintf( $t[0], $prenom, $ref ) . "\n" . sprintf( $t[1], $prenom, $ref );
	if ( 'annulee' !== $e && 'livree' !== $e ) {
		$msg .= "\n\nSuivre votre commande / تتبع طلبك :\n" . cpb_url_suivi( $id );
	}
	return 'https://wa.me/212' . $tel . '?text=' . rawurlencode( $msg );
}

add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( 'cp_commande' !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
		return $actions;
	}
	$e     = cpb_suivi_etat( $post->ID );
	$cles  = array_keys( cpb_suivi_etapes() );
	$pos   = array_search( $e, $cles, true );
	$suite = ( false !== $pos && isset( $cles[ $pos + 1 ] ) ) ? $cles[ $pos + 1 ] : '';
	if ( $suite ) {
		$actions['cpb_suivi'] = sprintf( '<a href="%s"><strong>→ %s</strong></a>', esc_url( cpb_suivi_lien_admin( $post->ID, $suite ) ), esc_html( cpb_suivi_etapes()[ $suite ][0] ) );
	}
	$wa = cpb_suivi_lien_wa( $post->ID );
	if ( $wa ) {
		$actions['cpb_suivi_wa'] = sprintf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( $wa ), esc_html( 'WhatsApp : prévenir le client (' . ( 'annulee' === $e ? 'Annulée' : cpb_suivi_etapes()[ $e ][0] ) . ')' ) );
	}
	return $actions;
}, 25, 2 );

add_action( 'admin_post_cpb_suivi', function () {
	$id   = isset( $_GET['commande'] ) ? absint( $_GET['commande'] ) : 0;
	$etat = isset( $_GET['etat'] ) ? sanitize_key( wp_unslash( $_GET['etat'] ) ) : '';
	if ( ! $id || 'cp_commande' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'Action non autorisée.' );
	}
	check_admin_referer( 'cpb_suivi_' . $id );
	cpb_suivi_change( $id, $etat );
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=cp_commande' ) );
	exit;
} );

/* Sur la fiche d'une commande : toutes les etapes, et l'annulation. */
add_action( 'add_meta_boxes_cp_commande', function () {
	add_meta_box( 'cpb-suivi', 'Suivi de la commande', function ( $post ) {
		$e = cpb_suivi_etat( $post->ID );
		$d = cpb_suivi_dates( $post->ID );
		echo '<ol style="margin:0 0 10px 18px">';
		foreach ( cpb_suivi_etapes() as $k => $n ) {
			$fait = ! empty( $d[ $k ] ) && 'annulee' !== $e;
			printf(
				'<li style="margin:4px 0">%s%s — <a href="%s">%s</a></li>',
				$fait ? '<strong>' . esc_html( $n[0] ) . '</strong>' : esc_html( $n[0] ),
				$fait ? ' <span style="color:#646970">(' . esc_html( wp_date( 'j M, H:i', $d[ $k ] ) ) . ')</span>' : '',
				esc_url( cpb_suivi_lien_admin( $post->ID, $k ) ),
				$k === $e ? 'étape actuelle' : 'passer à cette étape'
			);
		}
		echo '</ol>';
		if ( 'annulee' === $e ) {
			echo '<p><strong style="color:#8a2424">Commande annulée.</strong></p>';
		} else {
			printf( '<p><a href="%s" style="color:#b32d2e">Annuler la commande</a></p>', esc_url( cpb_suivi_lien_admin( $post->ID, 'annulee' ) ) );
		}
		$wa = cpb_suivi_lien_wa( $post->ID );
		if ( $wa ) {
			printf( '<p><a class="button" href="%s" target="_blank" rel="noopener">WhatsApp : prévenir le client</a></p>', esc_url( $wa ) );
		}
		printf( '<p>Lien de suivi du client :<br><input type="text" readonly style="width:100%%" value="%s" onclick="this.select()"></p>', esc_attr( cpb_url_suivi( $post->ID ) ) );
		wp_nonce_field( 'cpb_colis', 'cpb_colis_nonce' );
		printf( '<p><label for="cpb_colis">N° de suivi du livreur</label><br><input id="cpb_colis" name="cpb_colis" type="text" style="width:100%%" value="%s"></p>', esc_attr( get_post_meta( $post->ID, 'cpb_colis', true ) ) );
	}, 'cp_commande', 'side', 'high' );
} );

/* ══════════════════════════════════════════════════════════════
   PAGE CLIENT : /?suivi=1  (ou /?suivi=<ref>&k=<cle>)
══════════════════════════════════════════════════════════════ */
add_action( 'template_redirect', function () {
	if ( ! isset( $_GET['suivi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
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
		return 'Suivre ma commande — ' . get_bloginfo( 'name' );
	}, 99 );

	$id      = 0;
	$erreur  = '';
	$ref_vue = '';
	$brut    = sanitize_text_field( wp_unslash( $_GET['suivi'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	// Lien direct (WhatsApp) : reference + cle, en ?suivi=REF&k=CLE ou en
	// ?suivi=REF_CLE (un seul parametre, pour les boutons des modeles Meta).
	$cle_lien = isset( $_GET['k'] ) ? sanitize_text_field( wp_unslash( $_GET['k'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$ref_lien = $brut;
	if ( '' === $cle_lien && preg_match( '/^(CP-[0-9A-Z-]+)_([a-f0-9]{16})$/i', $brut, $mm ) ) {
		$ref_lien = $mm[1];
		$cle_lien = $mm[2];
	}
	if ( '1' !== $brut && '' !== $cle_lien ) {
		$c = cpb_suivi_trouve( $ref_lien );
		if ( $c && hash_equals( cpb_suivi_cle( $c ), $cle_lien ) ) {
			$id = $c;
		}
	}
	// Formulaire : reference + telephone.
	if ( ! $id && isset( $_POST['ref'], $_POST['tel'] ) ) {
		$ref_vue = strtoupper( sanitize_text_field( wp_unslash( $_POST['ref'] ) ) );
		$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$cle_ip  = 'cpb_suivi_' . md5( $ip );
		$essais  = (int) get_transient( $cle_ip );
		if ( ! isset( $_POST['cpb_suivi_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cpb_suivi_nonce'] ) ), 'cpb_suivi_form' ) ) {
			$erreur = 'expire';
		} elseif ( $essais >= 15 ) {
			$erreur = 'trop';
		} else {
			set_transient( $cle_ip, $essais + 1, HOUR_IN_SECONDS );
			$c   = cpb_suivi_trouve( $ref_vue );
			$tel = cpb_suivi_tel( sanitize_text_field( wp_unslash( $_POST['tel'] ) ) );
			if ( $c && strlen( $tel ) === 9 && hash_equals( cpb_suivi_tel( get_post_meta( $c, 'cp_tel', true ) ), $tel ) ) {
				$id = $c;
			} else {
				$erreur = 'introuvable';
			}
		}
	} elseif ( ! $id && isset( $_GET['ref'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$ref_vue = strtoupper( sanitize_text_field( wp_unslash( $_GET['ref'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	get_header();
	cpb_suivi_page( $id, $erreur, $ref_vue );
	get_footer();
	exit;
}, 1 );

function cpb_suivi_page( $id, $erreur, $ref_vue ) {
	echo '<main id="cp-main" class="cp-secours cpb-page cpb-suivi-page">';
	echo '<p class="cpb-titre-petit" data-cpb-ar="تتبع الطلب">Suivi de commande</p>';
	if ( ! $id ) {
		echo '<h1 data-cpb-ar="أين وصل طلبي؟">Où en est ma commande ?</h1>';
		echo '<p class="cpb-suivi-intro" data-cpb-ar="أدخل رقم الطلب (يبدأ بـ CP-) ورقم الهاتف الذي استعملته في الطلب.">Entrez la référence de votre commande (elle commence par CP-) et le téléphone utilisé pour commander.</p>';
		$msg = array(
			'introuvable' => array( 'Aucune commande ne correspond à cette référence et à ce téléphone. Vérifiez-les, ou écrivez-nous sur WhatsApp.', 'لا يوجد طلب بهذا الرقم وهذا الهاتف. تأكد منهما أو راسلنا على واتساب.' ),
			'trop'        => array( 'Trop d’essais. Réessayez dans une heure, ou écrivez-nous sur WhatsApp.', 'محاولات كثيرة. أعد بعد ساعة أو راسلنا على واتساب.' ),
			'expire'      => array( 'La page a expiré, merci de réessayer.', 'انتهت صلاحية الصفحة، أعد المحاولة.' ),
		);
		if ( isset( $msg[ $erreur ] ) ) {
			printf( '<p class="cpb-avis-erreur" role="alert" data-cpb-ar="%s">%s</p>', esc_attr( $msg[ $erreur ][1] ), esc_html( $msg[ $erreur ][0] ) );
		}
		echo '<form class="cpb-avis-form cpb-suivi-form" method="post" action="' . esc_url( cpb_url_suivi() ) . '" data-clarity-mask="true">';
		wp_nonce_field( 'cpb_suivi_form', 'cpb_suivi_nonce' );
		printf( '<label class="cpb-avis-lab" for="cpb-s-ref" data-cpb-ar="رقم الطلب">Référence</label><input id="cpb-s-ref" name="ref" type="text" required maxlength="24" autocomplete="off" autocapitalize="characters" placeholder="CP-261003-1234" value="%s">', esc_attr( $ref_vue ) );
		echo '<label class="cpb-avis-lab" for="cpb-s-tel" data-cpb-ar="الهاتف">Téléphone</label><input id="cpb-s-tel" name="tel" type="tel" required maxlength="20" inputmode="tel" autocomplete="tel" placeholder="06 12 34 56 78">';
		echo '<button type="submit" class="cpb-avis-go" data-cpb-ar="تتبع طلبي">Suivre ma commande</button>';
		echo '<p class="cpb-suivi-aide" data-cpb-ar="لا تجد الرقم؟ تجده في رسالة واتساب الخاصة بطلبك.">Pas de référence sous la main ? Elle figure dans le message WhatsApp de votre commande.</p>';
		echo '</form></main>';
		return;
	}
	$ref    = (string) get_post_meta( $id, 'cp_ref', true );
	$e      = cpb_suivi_etat( $id );
	$dates  = cpb_suivi_dates( $id );
	$ville  = (string) get_post_meta( $id, 'cp_ville', true );
	$post   = get_post( $id );
	printf( '<h1><span data-cpb-ar="الطلب">Commande</span> %s</h1>', esc_html( $ref ) );
	if ( 'annulee' === $e ) {
		echo '<p class="cpb-avis-erreur" data-cpb-ar="تم إلغاء هذا الطلب. لأي سؤال، راسلنا على واتساب.">Cette commande a été annulée. Pour toute question, écrivez-nous sur WhatsApp.</p>';
	} else {
		$cles = array_keys( cpb_suivi_etapes() );
		$pos  = array_search( $e, $cles, true );
		echo '<ol class="cpb-suivi-etapes">';
		foreach ( cpb_suivi_etapes() as $k => $n ) {
			$i     = array_search( $k, $cles, true );
			$etat  = $i < $pos ? 'faite' : ( $i === $pos ? 'actuelle' : 'avenir' );
			$quand = ( 'avenir' !== $etat && ! empty( $dates[ $k ] ) ) ? '<time>' . esc_html( wp_date( 'j F, H:i', $dates[ $k ] ) ) . '</time>' : '';
			printf(
				'<li class="cpb-suivi-%1$s"%4$s><span class="cpb-suivi-point" aria-hidden="true"></span><span class="cpb-suivi-nom" data-cpb-ar="%2$s">%3$s</span>%5$s</li>',
				esc_attr( $etat ),
				esc_attr( $n[1] ),
				esc_html( $n[0] ),
				'actuelle' === $etat ? ' aria-current="step"' : '',
				$quand
			);
		}
		echo '</ol>';
		if ( 'expediee' === $e ) {
			$casa = function_exists( 'remove_accents' ) && 'casablanca' === strtolower( remove_accents( $ville ) );
			echo '<p class="cpb-suivi-note" data-cpb-ar="' . esc_attr( $casa ? 'التوصيل المتوقع خلال 1 إلى 2 أيام عمل. الموزع غادي يتاصل بيك.' : 'التوصيل المتوقع خلال 2 إلى 4 أيام عمل. الموزع غادي يتاصل بيك.' ) . '">'
				. esc_html( $casa ? 'Livraison prévue sous 1 à 2 jours ouvrés. Le livreur vous appellera.' : 'Livraison prévue sous 2 à 4 jours ouvrés. Le livreur vous appellera.' ) . '</p>';
		} elseif ( 'livree' === $e ) {
			echo '<p class="cpb-suivi-note" data-cpb-ar="شكرا على ثقتك! عجبك العطر؟ قل لنا رأيك على واتساب.">Merci pour votre confiance ! Votre parfum vous plaît ? Dites-le-nous sur WhatsApp.</p>';
		} else {
			echo '<p class="cpb-suivi-note" data-cpb-ar="سنتصل بك لتأكيد الطلب، ثم نرسله. الدفع عند الاستلام.">Nous vous contactons pour confirmer, puis nous expédions. Vous payez à la réception.</p>';
		}
	}
	if ( $post && $post->post_content ) {
		echo '<div class="cpb-suivi-colis"><p class="cpb-avis-lab" data-cpb-ar="في الطرد">Dans le colis</p><ul>';
		foreach ( preg_split( '/\r?\n/', trim( $post->post_content ) ) as $l ) {
			$l = trim( preg_replace( '/\s*\([a-z0-9-]+--[a-z0-9-]+\)/', '', $l ) );
			if ( '' !== $l ) {
				echo '<li>' . esc_html( $l ) . '</li>';
			}
		}
		$total = (int) get_post_meta( $id, 'cp_total', true );
		echo '</ul>';
		if ( $total ) {
			printf( '<p><span data-cpb-ar="المبلغ عند الاستلام">À payer à la réception</span> : <strong>%s DH</strong></p>', esc_html( number_format_i18n( $total ) ) );
		}
		echo '</div>';
	}
	echo '</main>';
}

add_action( 'save_post_cp_commande', function ( $id ) {
	if ( ! isset( $_POST['cpb_colis_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cpb_colis_nonce'] ) ), 'cpb_colis' ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	if ( isset( $_POST['cpb_colis'] ) ) {
		update_post_meta( $id, 'cpb_colis', sanitize_text_field( wp_unslash( $_POST['cpb_colis'] ) ) );
	}
} );
