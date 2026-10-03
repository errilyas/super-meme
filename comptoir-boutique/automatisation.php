<?php
/**
 * Comptoir Boutique — automatisation du suivi de commande.
 *
 * S'appuie sur le module WhatsApp du theme (inc/whatsapp.php, Reglages >
 * Confirmation WhatsApp) : memes identifiants Meta, meme webhook, memes
 * modeles. Ce fichier ajoute seulement :
 *
 * 1. La synchronisation des deux cotes :
 *    - le client confirme ou annule sur WhatsApp (theme) -> l'etape du suivi
 *      passe a « Confirmee » / « Annulee » ;
 *    - vous confirmez ou annulez a la main (suivi) -> le theme arrete ses
 *      relances WhatsApp pour cette commande.
 * 2. « Expediee » -> le rappel de livraison du theme part tout seul ;
 *    « Livree » -> modele de demande d'avis (bouton lien), si active.
 * 3. Societe de livraison : une adresse (webhook) a donner au livreur ;
 *    chaque statut qu'il envoie met l'etape a jour (et declenche 2).
 * 4. Sans API WhatsApp : un clic sur l'etape ouvre directement WhatsApp
 *    avec le bon message.
 *
 * Journal des 50 derniers evenements dans Reglages > Automatisation.
 *
 * @package Comptoir_Boutique
 */

defined( 'ABSPATH' ) || exit;

/* ══════════════════════════════════════════════════════════════
   REGLAGES
══════════════════════════════════════════════════════════════ */
function cpb_auto_reglages() {
	$r = get_option( 'cpb_auto', array() );
	$r = is_array( $r ) ? $r : array();
	return array_merge( array(
		'expediee'  => 1,
		'livree'    => 0,
		'tpl_avis'  => 'cp_demande_avis',
		'liv_cle'   => '',
	), $r );
}

/** Cle du livreur, creee une fois. */
function cpb_auto_cles() {
	$r = cpb_auto_reglages();
	if ( '' === $r['liv_cle'] ) {
		$r['liv_cle'] = wp_generate_password( 32, false );
		update_option( 'cpb_auto', $r, false );
	}
	return $r;
}

/** Le module WhatsApp du theme est-il pret a envoyer ? */
function cpb_auto_wa_pret() {
	return function_exists( 'comptoir_wa_pret' ) && comptoir_wa_pret();
}

function cpb_auto_log( $msg ) {
	$j = get_option( 'cpb_auto_journal', array() );
	$j = is_array( $j ) ? $j : array();
	array_unshift( $j, array( time(), wp_strip_all_tags( (string) $msg ) ) );
	update_option( 'cpb_auto_journal', array_slice( $j, 0, 50 ), false );
}

/* ══════════════════════════════════════════════════════════════
   1. SYNCHRONISATION AVEC LE MODULE WHATSAPP DU THEME
══════════════════════════════════════════════════════════════ */

/* Le theme ecrit cp_etat = confirmee / annulee quand le client repond. */
function cpb_auto_etat_theme( $meta_id, $id, $cle, $valeur ) {
	if ( 'cp_etat' !== $cle || 'cp_commande' !== get_post_type( $id ) || ! function_exists( 'cpb_suivi_etat' ) ) {
		return;
	}
	$e = cpb_suivi_etat( $id );
	if ( 'confirmee' === $valeur && 'recue' === $e ) {
		cpb_suivi_change( $id, 'confirmee' );
		cpb_auto_log( get_post_meta( $id, 'cp_ref', true ) . ' : confirmée par le client sur WhatsApp.' );
	} elseif ( 'annulee' === $valeur && in_array( $e, array( 'recue', 'confirmee' ), true ) ) {
		cpb_suivi_change( $id, 'annulee' );
		cpb_auto_log( get_post_meta( $id, 'cp_ref', true ) . ' : annulée par le client sur WhatsApp.' );
	}
}
add_action( 'updated_post_meta', 'cpb_auto_etat_theme', 10, 4 );
add_action( 'added_post_meta', 'cpb_auto_etat_theme', 10, 4 );

/* Chaque changement d'etape. */
add_action( 'cpb_suivi_change', function ( $id, $etat ) {
	$wa = (string) get_post_meta( $id, 'cp_wa_statut', true );
	// Confirmee / annulee a la main : le theme cesse de relancer le client.
	if ( 'confirmee' === $etat && 'envoye' === $wa ) {
		update_post_meta( $id, 'cp_wa_statut', 'confirme' );
	} elseif ( 'annulee' === $etat && in_array( $wa, array( 'envoye', 'confirme' ), true ) ) {
		update_post_meta( $id, 'cp_wa_statut', 'annule' );
	}
	if ( in_array( $etat, array( 'expediee', 'livree' ), true ) && cpb_auto_wa_pret() ) {
		// Envoi differe : le clic dans l'admin ou l'appel du livreur n'attend pas Meta.
		wp_schedule_single_event( time(), 'cpb_auto_wa_tache', array( (int) $id, (string) $etat ) );
		if ( function_exists( 'spawn_cron' ) ) {
			spawn_cron();
		}
	}
}, 10, 2 );

add_action( 'cpb_auto_wa_tache', 'cpb_auto_wa_envoie', 10, 2 );

/** Expediee -> rappel de livraison du theme ; Livree -> demande d'avis. */
function cpb_auto_wa_envoie( $id, $etape ) {
	$r   = cpb_auto_reglages();
	$ref = (string) get_post_meta( $id, 'cp_ref', true );
	if ( ! cpb_auto_wa_pret() ) {
		return false;
	}
	if ( 'expediee' === $etape ) {
		if ( empty( $r['expediee'] ) || get_post_meta( $id, 'cp_wa_livraison', true ) || ! function_exists( 'comptoir_wa_rappel_livraison' ) ) {
			return false;
		}
		$res = comptoir_wa_rappel_livraison( $id );
	} elseif ( 'livree' === $etape ) {
		if ( empty( $r['livree'] ) || get_post_meta( $id, 'cpb_wa_avis', true ) || ! function_exists( 'comptoir_wa_appel' ) ) {
			return false;
		}
		$c    = comptoir_wa_commande( $id );
		$lien = function_exists( 'cpb_avis_cle' ) ? $id . '_' . cpb_avis_cle( $id ) : (string) $id;
		$res  = comptoir_wa_appel( array(
			'to'       => $c['to'],
			'type'     => 'template',
			'template' => array(
				'name'       => $r['tpl_avis'],
				'language'   => array( 'code' => comptoir_wa_reglages()['langue'] ),
				'components' => array(
					array( 'type' => 'body', 'parameters' => array( array( 'type' => 'text', 'text' => comptoir_wa_param( $c['prenom'] ) ), array( 'type' => 'text', 'text' => comptoir_wa_param( $c['ref'] ) ) ) ),
					// Bouton lien du modele : https://<site>/?avis={{1}}
					array( 'type' => 'button', 'sub_type' => 'url', 'index' => '0', 'parameters' => array( array( 'type' => 'text', 'text' => $lien ) ) ),
				),
			),
		) );
		if ( ! is_wp_error( $res ) ) {
			update_post_meta( $id, 'cpb_wa_avis', time() );
			if ( function_exists( 'comptoir_wa_trace' ) ) {
				comptoir_wa_trace( $id, $res );
			}
		}
	} else {
		return false;
	}
	if ( is_wp_error( $res ) ) {
		cpb_auto_log( "$ref : échec WhatsApp « $etape » — " . $res->get_error_message() );
		return false;
	}
	if ( false === $res ) {
		return false;
	}
	cpb_auto_log( "$ref : WhatsApp « " . ( 'expediee' === $etape ? 'rappel de livraison' : 'demande d’avis' ) . ' » envoyé.' );
	return true;
}

/* ══════════════════════════════════════════════════════════════
   2. SOCIETE DE LIVRAISON : /wp-json/cpb/v1/livraison?cle=…
══════════════════════════════════════════════════════════════ */
add_action( 'rest_api_init', function () {
	register_rest_route( 'cpb/v1', '/livraison', array(
		'methods'             => array( 'POST', 'GET' ),
		'permission_callback' => '__return_true',
		'callback'            => 'cpb_auto_livraison',
	) );
} );

/** Statut du livreur -> etape (francais, anglais, majuscules ou non). */
function cpb_auto_statut_etape( $statut ) {
	$s = strtolower( remove_accents( (string) $statut ) );
	if ( preg_match( '/\b(retour|retourne|returned|refus|refuse|annul|cancel)/', $s ) ) {
		return 'annulee';
	}
	if ( preg_match( '/\b(livre|livree|delivered|paye)\b/', $s ) ) {
		return 'livree';
	}
	if ( preg_match( '/(expedi|ramass|pick|transit|shipped|en cours|en route|distribution|sorti|recu par|remis)/', $s ) ) {
		return 'expediee';
	}
	return '';
}

function cpb_auto_livraison( WP_REST_Request $q ) {
	$r = cpb_auto_cles();
	if ( ! hash_equals( $r['liv_cle'], (string) $q->get_param( 'cle' ) ) ) {
		return new WP_REST_Response( array( 'erreur' => 'cle' ), 403 );
	}
	$params = $q->get_json_params();
	$params = is_array( $params ) && $params ? $params : $q->get_params();
	unset( $params['cle'] );
	$plat = (string) wp_json_encode( $params );
	// Reference CP-… n'importe ou dans l'envoi, sinon numero de suivi, sinon telephone.
	$id = 0;
	if ( preg_match( '/CP-[0-9A-Z]{4,8}-[0-9A-Z]{2,8}/i', $plat, $m ) ) {
		$id = cpb_suivi_trouve( $m[0] );
	}
	$suivi_n = '';
	foreach ( array( 'tracking', 'tracking_number', 'code', 'colis', 'barcode', 'awb', 'numero' ) as $k ) {
		if ( ! empty( $params[ $k ] ) && is_scalar( $params[ $k ] ) ) {
			$suivi_n = sanitize_text_field( (string) $params[ $k ] );
			break;
		}
	}
	if ( ! $id && $suivi_n ) {
		$t  = get_posts( array( 'post_type' => 'cp_commande', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => 'cpb_colis', 'meta_value' => $suivi_n ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$id = $t ? (int) $t[0] : 0;
	}
	if ( ! $id ) {
		foreach ( array( 'phone', 'telephone', 'tel', 'mobile', 'customer_phone' ) as $k ) {
			if ( ! empty( $params[ $k ] ) && is_scalar( $params[ $k ] ) ) {
				$tel = cpb_suivi_tel( $params[ $k ] );
				foreach ( get_posts( array( 'post_type' => 'cp_commande', 'post_status' => 'any', 'posts_per_page' => 30, 'fields' => 'ids' ) ) as $c ) {
					if ( cpb_suivi_tel( get_post_meta( $c, 'cp_tel', true ) ) === $tel && ! in_array( cpb_suivi_etat( $c ), array( 'livree', 'annulee' ), true ) ) {
						$id = (int) $c;
						break;
					}
				}
				break;
			}
		}
	}
	$statut = '';
	foreach ( array( 'status', 'statut', 'state', 'etat', 'status_name', 'last_status' ) as $k ) {
		if ( ! empty( $params[ $k ] ) && is_scalar( $params[ $k ] ) ) {
			$statut = sanitize_text_field( (string) $params[ $k ] );
			break;
		}
	}
	if ( ! $id ) {
		cpb_auto_log( 'Livreur : commande introuvable (' . substr( $plat, 0, 160 ) . ')' );
		return new WP_REST_Response( array( 'ok' => false, 'erreur' => 'commande introuvable' ), 200 );
	}
	if ( $suivi_n ) {
		update_post_meta( $id, 'cpb_colis', $suivi_n );
	}
	$etape = cpb_auto_statut_etape( $statut );
	$ref   = (string) get_post_meta( $id, 'cp_ref', true );
	$avant = cpb_suivi_etat( $id );
	$ordre = array( 'recue' => 0, 'confirmee' => 1, 'expediee' => 2, 'livree' => 3, 'annulee' => 4 );
	// On n'avance que vers l'avant ; une commande livree ne bouge plus.
	if ( $etape && $etape !== $avant && 'livree' !== $avant && $ordre[ $etape ] > $ordre[ $avant ] ) {
		cpb_suivi_change( $id, $etape );
		cpb_auto_log( "$ref : livreur « $statut » → $etape." );
	} else {
		cpb_auto_log( "$ref : livreur « $statut » (sans changement)." );
	}
	return new WP_REST_Response( array( 'ok' => true, 'commande' => $ref, 'etape' => cpb_suivi_etat( $id ) ), 200 );
}

/* ══════════════════════════════════════════════════════════════
   3. PAGE DE REGLAGES : Réglages > Automatisation
══════════════════════════════════════════════════════════════ */
add_action( 'admin_menu', function () {
	add_options_page( 'Automatisation des commandes', 'Automatisation', 'manage_options', 'cpb-automatisation', 'cpb_auto_page' );
} );

add_action( 'admin_post_cpb_auto_enregistre', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Action non autorisée.' );
	}
	check_admin_referer( 'cpb_auto' );
	$r             = cpb_auto_cles();
	$r['expediee'] = empty( $_POST['expediee'] ) ? 0 : 1;
	$r['livree']   = empty( $_POST['livree'] ) ? 0 : 1;
	if ( isset( $_POST['tpl_avis'] ) ) {
		$r['tpl_avis'] = sanitize_key( wp_unslash( $_POST['tpl_avis'] ) );
	}
	if ( ! empty( $_POST['liv_nouvelle_cle'] ) ) {
		$r['liv_cle'] = wp_generate_password( 32, false );
	}
	update_option( 'cpb_auto', $r, false );
	wp_safe_redirect( admin_url( 'options-general.php?page=cpb-automatisation&enregistre=1' ) );
	exit;
} );

function cpb_auto_page() {
	$r  = cpb_auto_cles();
	$ch = function ( $k ) use ( $r ) {
		return checked( ! empty( $r[ $k ] ), true, false );
	};
	echo '<div class="wrap"><h1>Automatisation des commandes</h1>';
	if ( isset( $_GET['enregistre'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success"><p>Réglages enregistrés.</p></div>';
	}

	echo '<h2>1. WhatsApp automatique</h2>';
	if ( cpb_auto_wa_pret() ) {
		echo '<p><strong style="color:#00813a">● Actif</strong> — via <a href="' . esc_url( admin_url( 'options-general.php?page=comptoir-whatsapp' ) ) . '">Réglages → Confirmation WhatsApp</a>.</p>';
		echo '<ul style="list-style:disc;margin-left:20px"><li>Nouvelle commande : demande de confirmation avec boutons, relance si pas de réponse (module du thème).</li><li>Réponse du client : la commande passe à Confirmée ou Annulée toute seule.</li></ul>';
	} else {
		echo '<p><strong style="color:#b26200">● Pas encore branché</strong> — en attendant, cliquer sur une étape dans Commandes ouvre WhatsApp avec le message prêt : vous appuyez sur Envoyer.</p>';
		echo '<p>Pour tout automatiser : <a class="button button-primary" href="' . esc_url( admin_url( 'options-general.php?page=comptoir-whatsapp' ) ) . '">Brancher WhatsApp (Réglages → Confirmation WhatsApp)</a></p>';
	}
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'cpb_auto' );
	echo '<input type="hidden" name="action" value="cpb_auto_enregistre">';
	echo '<table class="form-table" role="presentation">';
	printf( '<tr><th>À « Expédiée »</th><td><label><input type="checkbox" name="expediee" value="1"%s> Envoyer le rappel de livraison (modèle « rappel livraison » du thème)</label></td></tr>', $ch( 'expediee' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	printf( '<tr><th>À « Livrée »</th><td><label><input type="checkbox" name="livree" value="1"%s> Envoyer la demande d’avis</label> — modèle <input name="tpl_avis" type="text" value="%s"><p class="description">À créer d’abord dans WhatsApp Manager (voir en bas). Laissez décoché tant qu’il n’est pas approuvé.</p></td></tr>', $ch( 'livree' ), esc_attr( $r['tpl_avis'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</table>';

	echo '<h2>2. Société de livraison</h2>';
	$url_liv = add_query_arg( 'cle', $r['liv_cle'], rest_url( 'cpb/v1/livraison' ) );
	printf( '<p>Donnez cette adresse à votre livreur (« webhook », « URL de notification » ou « callback » des statuts) :</p><p><input type="text" readonly class="large-text code" value="%s" onclick="this.select()"></p>', esc_attr( $url_liv ) );
	echo '<p class="description">Mettez la référence CP-… dans le champ « référence » ou « commentaire » du colis quand vous le créez chez le livreur. Sinon, le site retrouve la commande par le numéro de suivi (champ « N° de suivi du livreur » de la commande), puis par le téléphone. Statuts compris : ramassé / expédié / en cours / en transit → Expédiée ; livré → Livrée ; retour / refusé / annulé → Annulée.</p>';
	echo '<p><label><input type="checkbox" name="liv_nouvelle_cle" value="1"> Changer la clé (l’ancienne adresse ne marchera plus)</label></p>';
	submit_button( 'Enregistrer' );
	echo '</form>';

	echo '<h2>Journal</h2>';
	$j = get_option( 'cpb_auto_journal', array() );
	if ( ! $j ) {
		echo '<p>Rien pour l’instant.</p>';
	} else {
		echo '<table class="widefat striped" style="max-width:900px"><tbody>';
		foreach ( (array) $j as $l ) {
			printf( '<tr><td style="width:140px">%s</td><td>%s</td></tr>', esc_html( wp_date( 'j M H:i', (int) $l[0] ) ), esc_html( $l[1] ) );
		}
		echo '</tbody></table>';
	}

	echo '<h2>Modèle de demande d’avis à créer (WhatsApp Manager, catégorie « Utilité »)</h2>';
	printf( '<p><strong>%s</strong> — Corps (darija) : « السلام {{1}}، الطلبية ديالك {{2}} توصلات. شكرا على الثقة! عجبك العطر؟ رأيك كيعاون الزبناء الآخرين 🙏 » — Bouton « Lien » : <em>عطينا رأيك</em>, URL dynamique <code>%s?avis={{1}}</code>.</p>', esc_html( $r['tpl_avis'] ), esc_html( home_url( '/' ) ) );
	echo '</div>';
}

/* ══════════════════════════════════════════════════════════════
   4. SANS API : UN CLIC PAR ETAPE (l'etape change, WhatsApp s'ouvre)
══════════════════════════════════════════════════════════════ */
add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( 'cp_commande' !== $post->post_type || empty( $actions['cpb_suivi'] ) || cpb_auto_wa_pret() ) {
		return $actions;
	}
	$actions['cpb_suivi'] = preg_replace( '/^<a /', '<a target="_blank" rel="noopener" onclick="setTimeout(function(){location.reload()},1500)" ', str_replace( 'action=cpb_suivi', 'action=cpb_suivi&amp;wa=1', $actions['cpb_suivi'] ) );
	return $actions;
}, 40, 2 );

add_filter( 'wp_redirect', function ( $loc ) {
	if ( ! is_admin() || empty( $_GET['wa'] ) || ! isset( $_GET['action'], $_GET['commande'] ) || 'cpb_suivi' !== $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification
		return $loc;
	}
	$wa = function_exists( 'cpb_suivi_lien_wa' ) ? cpb_suivi_lien_wa( absint( $_GET['commande'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	return $wa ? $wa : $loc;
} );

add_filter( 'allowed_redirect_hosts', function ( $h ) {
	$h[] = 'wa.me';
	return $h;
} );
