<?php
/**
 * Confirmation des commandes sur WhatsApp — API officielle WhatsApp Cloud (Meta).
 *
 * Pourquoi : sur dix colis, un refus et deux clients injoignables. Un message
 * juste apres la commande (« je confirme / annuler »), une relance sans
 * reponse, puis un rappel le jour de la livraison : on n'expedie que ce qui
 * est confirme, et le client sait que le livreur va l'appeler.
 *
 * Parcours :
 *   1. commande enregistree -> modele « confirmation » avec deux boutons ;
 *   2. pas de reponse apres N heures (hors nuit) -> modele « relance » ;
 *   3. toujours rien 24 h apres la relance -> statut « sans reponse » ;
 *   4. le jour de l'expedition, depuis la liste des commandes -> modele
 *      « rappel livraison ».
 * Les reponses arrivent par le webhook /wp-json/comptoir/v1/whatsapp : elles
 * changent le statut de la commande et le client recoit un accuse.
 *
 * Rien ne part tant que le module n'est pas active dans Reglages > Confirmation
 * WhatsApp : sans reglages, le site se comporte exactement comme avant.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const COMPTOIR_WA_API = 'https://graph.facebook.com/v21.0/';

/* ══════════════════════════════════════════════════════════════
   REGLAGES
══════════════════════════════════════════════════════════════ */

/** Reglages non secrets, avec leurs valeurs par defaut. */
function comptoir_wa_reglages() {
	$r = get_option( 'comptoir_wa', array() );
	return wp_parse_args( is_array( $r ) ? $r : array(), array(
		'actif'         => 0,
		'phone_id'      => '',
		'langue'        => 'fr',
		'tpl_confirm'   => 'cp_confirmation_commande',
		'tpl_relance'   => 'cp_relance_confirmation',
		'tpl_livraison' => 'cp_rappel_livraison',
		'relance_h'     => 2,
		'alerte_annule' => 1,
	) );
}

/** Jeton d'acces : constante de wp-config.php d'abord, sinon la base. */
function comptoir_wa_jeton() {
	if ( defined( 'COMPTOIR_WA_TOKEN' ) && COMPTOIR_WA_TOKEN ) {
		return (string) COMPTOIR_WA_TOKEN;
	}
	return (string) get_option( 'comptoir_wa_token', '' );
}

/** Cle secrete de l'application Meta : sert a verifier que le webhook vient bien de Meta. */
function comptoir_wa_secret() {
	if ( defined( 'COMPTOIR_WA_APP_SECRET' ) && COMPTOIR_WA_APP_SECRET ) {
		return (string) COMPTOIR_WA_APP_SECRET;
	}
	return (string) get_option( 'comptoir_wa_secret', '' );
}

/** Jeton de verification du webhook, cree une fois et affiche dans les reglages. */
function comptoir_wa_verify_token() {
	$v = (string) get_option( 'comptoir_wa_verify', '' );
	if ( '' === $v ) {
		$v = 'cp_' . wp_generate_password( 24, false, false );
		update_option( 'comptoir_wa_verify', $v, false );
	}
	return $v;
}

/** Le module peut-il envoyer ? */
function comptoir_wa_pret() {
	$r = comptoir_wa_reglages();
	return ! empty( $r['actif'] ) && $r['phone_id'] && comptoir_wa_jeton();
}

/* ══════════════════════════════════════════════════════════════
   ENVOI
══════════════════════════════════════════════════════════════ */

/**
 * Nettoie un texte pour un parametre de modele : WhatsApp refuse les retours a
 * la ligne, les tabulations et plus de quatre espaces d'affilee.
 */
function comptoir_wa_param( $s, $max = 200 ) {
	$s = preg_replace( '/[\r\n\t]+/', ' · ', (string) $s );
	$s = preg_replace( '/ {2,}/', ' ', $s );
	$s = trim( $s );
	return '' === $s ? '-' : comptoir_coupe( $s, $max );
}

/**
 * Appel a l'API. Renvoie l'identifiant du message, ou un WP_Error lisible.
 */
function comptoir_wa_appel( array $corps ) {
	$r = comptoir_wa_reglages();
	$reponse = wp_remote_post( COMPTOIR_WA_API . rawurlencode( $r['phone_id'] ) . '/messages', array(
		'timeout' => 10,
		'headers' => array(
			'Authorization' => 'Bearer ' . comptoir_wa_jeton(),
			'Content-Type'  => 'application/json',
		),
		'body'    => wp_json_encode( array_merge( array( 'messaging_product' => 'whatsapp' ), $corps ) ),
	) );
	if ( is_wp_error( $reponse ) ) {
		return $reponse;
	}
	$code = (int) wp_remote_retrieve_response_code( $reponse );
	$json = json_decode( wp_remote_retrieve_body( $reponse ), true );
	if ( $code >= 200 && $code < 300 && ! empty( $json['messages'][0]['id'] ) ) {
		return (string) $json['messages'][0]['id'];
	}
	$msg = isset( $json['error']['message'] ) ? $json['error']['message'] : 'HTTP ' . $code;
	if ( isset( $json['error']['error_data']['details'] ) ) {
		$msg .= ' — ' . $json['error']['error_data']['details'];
	}
	return new WP_Error( 'cp_wa', comptoir_coupe( $msg, 240 ) );
}

/**
 * Envoie un modele approuve.
 *
 * @param string $to       Numero au format 2126XXXXXXXX.
 * @param string $modele   Nom du modele dans WhatsApp Manager.
 * @param array  $textes   Valeurs de {{1}}, {{2}}… dans l'ordre.
 * @param array  $boutons  Charges utiles des boutons de reponse rapide, dans l'ordre.
 */
function comptoir_wa_modele( $to, $modele, array $textes, array $boutons = array() ) {
	$r = comptoir_wa_reglages();
	$composants = array();
	if ( $textes ) {
		$composants[] = array(
			'type'       => 'body',
			'parameters' => array_map( function ( $t ) {
				return array( 'type' => 'text', 'text' => comptoir_wa_param( $t, 300 ) );
			}, array_values( $textes ) ),
		);
	}
	foreach ( array_values( $boutons ) as $i => $charge ) {
		$composants[] = array(
			'type'       => 'button',
			'sub_type'   => 'quick_reply',
			'index'      => (string) $i,
			'parameters' => array( array( 'type' => 'payload', 'payload' => $charge ) ),
		);
	}
	return comptoir_wa_appel( array(
		'to'       => $to,
		'type'     => 'template',
		'template' => array(
			'name'       => $modele,
			'language'   => array( 'code' => $r['langue'] ),
			'components' => $composants,
		),
	) );
}

/** Message libre — seulement dans les 24 h qui suivent un message du client. */
function comptoir_wa_texte( $to, $texte ) {
	return comptoir_wa_appel( array(
		'to'   => $to,
		'type' => 'text',
		'text' => array( 'preview_url' => false, 'body' => $texte ),
	) );
}

/** Les elements d'une commande qui servent aux messages. */
function comptoir_wa_commande( $id ) {
	$nom    = trim( (string) get_post_meta( $id, 'cp_nom', true ) );
	$prenom = $nom ? strtok( $nom, ' ' ) : '';
	$post   = get_post( $id );
	$lignes = array();
	foreach ( preg_split( '/\n/', $post ? (string) $post->post_content : '' ) as $l ) {
		// « 1× Jean Paul Gaultier Le Male Elixir (slug) — 319 DH » -> « 1× Jean Paul Gaultier Le Male Elixir »
		$l = trim( preg_replace( '/\s*\([a-z0-9-]+\)\s*—.*$/u', '', $l ) );
		if ( '' !== $l ) {
			$lignes[] = $l;
		}
	}
	$ville   = (string) get_post_meta( $id, 'cp_ville', true );
	$adresse = (string) get_post_meta( $id, 'cp_adresse', true );
	return array(
		'ref'     => (string) get_post_meta( $id, 'cp_ref', true ),
		'to'      => comptoir_capi_tel_e164( get_post_meta( $id, 'cp_tel', true ) ),
		'prenom'  => $prenom ? $prenom : 'et bienvenue',
		'panier'  => $lignes ? implode( ' · ', $lignes ) : 'votre commande',
		'total'   => (int) get_post_meta( $id, 'cp_total', true ) . ' DH',
		'adresse' => trim( $adresse . ( $ville ? ', ' . $ville : '' ) ),
	);
}

/** Garde la trace de chaque message envoye pour une commande (le webhook les retrouve ainsi). */
function comptoir_wa_trace( $id, $msg_id ) {
	add_post_meta( $id, 'cp_wa_msg', $msg_id );
}

/** Message 1 — juste apres la commande. */
function comptoir_wa_confirmation( $id ) {
	if ( ! comptoir_wa_pret() || get_post_meta( $id, 'cp_wa_statut', true ) ) {
		return false;
	}
	$r = comptoir_wa_reglages();
	$c = comptoir_wa_commande( $id );
	$res = comptoir_wa_modele(
		$c['to'],
		$r['tpl_confirm'],
		array( $c['prenom'], $c['ref'], $c['panier'], $c['total'], $c['adresse'] ),
		array( 'CP_OK|' . $c['ref'], 'CP_NO|' . $c['ref'] )
	);
	if ( is_wp_error( $res ) ) {
		update_post_meta( $id, 'cp_wa_statut', 'echec' );
		update_post_meta( $id, 'cp_wa_erreur', $res->get_error_message() );
		return false;
	}
	comptoir_wa_trace( $id, $res );
	update_post_meta( $id, 'cp_wa_statut', 'envoye' );
	update_post_meta( $id, 'cp_wa_envoye', time() );
	delete_post_meta( $id, 'cp_wa_erreur' );
	return true;
}

/** Message 2 — relance, avec les memes boutons. */
function comptoir_wa_relance( $id ) {
	if ( ! comptoir_wa_pret() ) {
		return false;
	}
	$r = comptoir_wa_reglages();
	$c = comptoir_wa_commande( $id );
	$res = comptoir_wa_modele(
		$c['to'],
		$r['tpl_relance'],
		array( $c['prenom'], $c['ref'], $c['total'] ),
		array( 'CP_OK|' . $c['ref'], 'CP_NO|' . $c['ref'] )
	);
	update_post_meta( $id, 'cp_wa_relance', time() );
	if ( is_wp_error( $res ) ) {
		update_post_meta( $id, 'cp_wa_erreur', $res->get_error_message() );
		return false;
	}
	comptoir_wa_trace( $id, $res );
	return true;
}

/** Message 3 — le jour de la livraison, declenche depuis l'admin. */
function comptoir_wa_rappel_livraison( $id ) {
	if ( ! comptoir_wa_pret() ) {
		return new WP_Error( 'cp_wa', 'Module WhatsApp non configuré.' );
	}
	$r = comptoir_wa_reglages();
	$c = comptoir_wa_commande( $id );
	$res = comptoir_wa_modele(
		$c['to'],
		$r['tpl_livraison'],
		array( $c['prenom'], $c['ref'], $c['total'] ),
		array( 'CP_VU|' . $c['ref'] )
	);
	if ( is_wp_error( $res ) ) {
		update_post_meta( $id, 'cp_wa_erreur', $res->get_error_message() );
		return $res;
	}
	comptoir_wa_trace( $id, $res );
	update_post_meta( $id, 'cp_wa_livraison', time() );
	return true;
}

/* ══════════════════════════════════════════════════════════════
   RELANCES AUTOMATIQUES
══════════════════════════════════════════════════════════════ */

/** Heure au Maroc : on ne reveille personne pour une confirmation. */
function comptoir_wa_heure_maroc( $ts = null ) {
	$d = new DateTime( '@' . ( null === $ts ? time() : (int) $ts ) );
	$d->setTimezone( new DateTimeZone( 'Africa/Casablanca' ) );
	return (int) $d->format( 'G' );
}

function comptoir_wa_passe_relances() {
	if ( ! comptoir_wa_pret() ) {
		return;
	}
	$r       = comptoir_wa_reglages();
	$maint   = time();
	$heure   = comptoir_wa_heure_maroc( $maint );
	$de_jour = $heure >= 9 && $heure < 22;

	$attente = get_posts( array(
		'post_type'      => 'cp_commande',
		'posts_per_page' => 50,
		'fields'         => 'ids',
		'date_query'     => array( array( 'after' => '4 days ago' ) ),
		'meta_query'     => array( array( 'key' => 'cp_wa_statut', 'value' => 'envoye' ) ),
	) );
	foreach ( $attente as $id ) {
		$envoye  = (int) get_post_meta( $id, 'cp_wa_envoye', true );
		$relance = (int) get_post_meta( $id, 'cp_wa_relance', true );
		if ( ! $relance ) {
			if ( $de_jour && $maint - $envoye >= max( 1, (int) $r['relance_h'] ) * HOUR_IN_SECONDS ) {
				comptoir_wa_relance( $id );
			}
		} elseif ( $maint - $relance >= DAY_IN_SECONDS ) {
			update_post_meta( $id, 'cp_wa_statut', 'sans_reponse' );
			update_post_meta( $id, 'cp_etat', 'sans-reponse-whatsapp' );
		}
	}
}
add_action( 'cp_wa_relances', 'comptoir_wa_passe_relances' );

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'cp_wa_relances' ) ) {
		wp_schedule_event( time() + 10 * MINUTE_IN_SECONDS, 'hourly', 'cp_wa_relances' );
	}
} );

/* ══════════════════════════════════════════════════════════════
   WEBHOOK — reponses des clients et accuses de lecture
══════════════════════════════════════════════════════════════ */

add_action( 'rest_api_init', function () {
	register_rest_route( 'comptoir/v1', '/whatsapp', array(
		array(
			'methods'             => 'GET',
			'callback'            => 'comptoir_wa_webhook_verifie',
			'permission_callback' => '__return_true',
		),
		array(
			'methods'             => 'POST',
			'callback'            => 'comptoir_wa_webhook_recoit',
			'permission_callback' => '__return_true',
		),
	) );
} );

/** Verification de l'abonnement : Meta envoie hub.challenge, on le renvoie tel quel. */
function comptoir_wa_webhook_verifie( $req ) {
	$mode  = (string) $req->get_param( 'hub_mode' );
	$jeton = (string) $req->get_param( 'hub_verify_token' );
	$defi  = (string) $req->get_param( 'hub_challenge' );
	if ( 'subscribe' === $mode && '' !== $jeton && hash_equals( comptoir_wa_verify_token(), $jeton ) ) {
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo preg_replace( '/[^0-9A-Za-z_-]/', '', $defi ); // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}
	return new WP_REST_Response( 'refusé', 403 );
}

/** Retrouve une commande a partir d'un message, d'une reference ou d'un numero. */
function comptoir_wa_trouve_commande( $ref = '', $msg_id = '', $wa_id = '' ) {
	$cherche = function ( $meta ) {
		$ids = get_posts( array(
			'post_type'      => 'cp_commande',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => array( $meta ),
		) );
		return $ids ? (int) $ids[0] : 0;
	};
	if ( $ref ) {
		$id = $cherche( array( 'key' => 'cp_ref', 'value' => $ref ) );
		if ( $id ) {
			return $id;
		}
	}
	if ( $msg_id ) {
		$id = $cherche( array( 'key' => 'cp_wa_msg', 'value' => $msg_id ) );
		if ( $id ) {
			return $id;
		}
	}
	if ( $wa_id ) {
		// Derniere commande en attente de ce numero (le numero est enregistre en 06…, +212… ou 00212…).
		$national = '0' . substr( $wa_id, 3 );
		$ids = get_posts( array(
			'post_type'      => 'cp_commande',
			'posts_per_page' => 5,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'date_query'     => array( array( 'after' => '10 days ago' ) ),
			'meta_query'     => array( array( 'key' => 'cp_tel', 'value' => array( $national, $wa_id, '+' . $wa_id, '00' . $wa_id ), 'compare' => 'IN' ) ),
		) );
		foreach ( $ids as $id ) {
			if ( in_array( get_post_meta( $id, 'cp_wa_statut', true ), array( 'envoye', 'sans_reponse' ), true ) ) {
				return (int) $id;
			}
		}
		return $ids ? (int) $ids[0] : 0;
	}
	return 0;
}

/** Lit l'intention d'un message tape a la main. */
function comptoir_wa_intention( $texte ) {
	$t = comptoir_minuscules( trim( (string) $texte ) );
	$t = trim( preg_replace( '/\s+/u', ' ', preg_replace( '/[!.?،,;:]+/u', ' ', $t ) ) );
	// Confirmation : un message court seulement. « oui mais je veux changer de
	// ville » n'est pas un feu vert pour expedier : il part en « message a lire ».
	if ( preg_match( '/^(1|oui|ok|okay|d.accord|je confirme|confirm\w*|wakha|wah|iyeh|ayeh|نعم|واخا|اه|آه|أكيد|مؤكد|✅|👍)( merci| svp| stp| je confirme| c.est bon| شكرا| الله يخليك)?( ?[✅👍🙏]+)?$/u', $t ) ) {
		return 'ok';
	}
	// Annulation : le message entier doit etre un refus. « la commande arrive
	// quand ? » commence par « la » (non, en darija) et ne doit rien annuler.
	if ( preg_match( '/^(2|non|annul\w*|la|lla|لا|ألغي|الغي|❌)( merci| svp| stp| la commande| الطلب)?$/u', $t ) ) {
		return 'no';
	}
	return '';
}

/** Applique une reponse a une commande et accuse reception au client. */
function comptoir_wa_applique( $id, $intention, $wa_id ) {
	$c = comptoir_wa_commande( $id );
	update_post_meta( $id, 'cp_wa_reponse', time() );
	if ( 'ok' === $intention ) {
		if ( 'confirme' === get_post_meta( $id, 'cp_wa_statut', true ) ) {
			return;
		}
		update_post_meta( $id, 'cp_wa_statut', 'confirme' );
		update_post_meta( $id, 'cp_etat', 'confirmee' );
		comptoir_wa_texte( $wa_id, sprintf(
			"✅ Merci %s, votre commande %s est confirmée.\nElle part au plus vite ; le livreur vous appellera avant de passer.\nÀ payer à la réception : %s.\n\nتأكدات الطلبية ديالك، شكرا 🙏",
			$c['prenom'], $c['ref'], $c['total']
		) );
	} elseif ( 'no' === $intention ) {
		if ( 'annule' === get_post_meta( $id, 'cp_wa_statut', true ) ) {
			return;
		}
		update_post_meta( $id, 'cp_wa_statut', 'annule' );
		update_post_meta( $id, 'cp_etat', 'annulee' );
		comptoir_wa_texte( $wa_id, sprintf(
			"Votre commande %s est annulée. Si c'est une erreur, répondez simplement à ce message.\nتلغات الطلبية. إلا كانت غلطة، جاوبنا هنا.",
			$c['ref']
		) );
		$r = comptoir_wa_reglages();
		if ( ! empty( $r['alerte_annule'] ) ) {
			wp_mail(
				get_option( 'admin_email' ),
				'Commande annulée sur WhatsApp : ' . $c['ref'],
				sprintf( "La commande %s (%s, %s) a été annulée par le client sur WhatsApp.\n\n%s", $c['ref'], $c['panier'], $c['total'], admin_url( 'post.php?post=' . (int) $id . '&action=edit' ) )
			);
		}
	} elseif ( 'vu' === $intention ) {
		update_post_meta( $id, 'cp_wa_livraison_vu', time() );
	}
}

function comptoir_wa_webhook_recoit( $req ) {
	$brut   = (string) $req->get_body();
	$secret = comptoir_wa_secret();
	$sig    = (string) $req->get_header( 'x_hub_signature_256' );
	if ( '' === $secret || '' === $sig || ! hash_equals( 'sha256=' . hash_hmac( 'sha256', $brut, $secret ), $sig ) ) {
		return new WP_REST_Response( array( 'ok' => false ), 401 );
	}
	$json = json_decode( $brut, true );
	if ( ! is_array( $json ) || empty( $json['entry'] ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}
	foreach ( (array) $json['entry'] as $entree ) {
		foreach ( isset( $entree['changes'] ) ? (array) $entree['changes'] : array() as $chg ) {
			$v = isset( $chg['value'] ) && is_array( $chg['value'] ) ? $chg['value'] : array();

			// Reponses des clients.
			foreach ( isset( $v['messages'] ) ? (array) $v['messages'] : array() as $m ) {
				$mid = isset( $m['id'] ) ? (string) $m['id'] : '';
				// Meta renvoie un meme evenement s'il n'a pas eu son 200 a temps.
				if ( $mid && get_transient( 'cp_wa_vu_' . md5( $mid ) ) ) {
					continue;
				}
				if ( $mid ) {
					set_transient( 'cp_wa_vu_' . md5( $mid ), 1, DAY_IN_SECONDS );
				}
				$wa_id  = isset( $m['from'] ) ? preg_replace( '/[^0-9]/', '', (string) $m['from'] ) : '';
				$ctx    = isset( $m['context']['id'] ) ? (string) $m['context']['id'] : '';
				$charge = '';
				$texte  = '';
				$type   = isset( $m['type'] ) ? $m['type'] : '';
				if ( 'button' === $type ) {
					$charge = isset( $m['button']['payload'] ) ? (string) $m['button']['payload'] : '';
					$texte  = isset( $m['button']['text'] ) ? (string) $m['button']['text'] : '';
				} elseif ( 'interactive' === $type && isset( $m['interactive']['button_reply']['id'] ) ) {
					$charge = (string) $m['interactive']['button_reply']['id'];
				} elseif ( 'text' === $type ) {
					$texte = isset( $m['text']['body'] ) ? (string) $m['text']['body'] : '';
				}

				$intention = '';
				$ref       = '';
				if ( preg_match( '/^CP_(OK|NO|VU)\|(.{1,40})$/', $charge, $mm ) ) {
					$intention = strtolower( $mm[1] );
					$ref       = $mm[2];
				} else {
					$intention = comptoir_wa_intention( $texte );
				}

				// Message de test envoye depuis les reglages : on accuse reception
				// sans toucher a aucune commande.
				if ( 'CP-TEST' === $ref ) {
					comptoir_wa_texte( $wa_id, 'ok' === $intention
						? '✅ Test réussi : la confirmation arrive bien sur le site. Commande fictive CP-TEST, rien ne sera expédié.'
						: 'Test réussi : l\'annulation arrive bien sur le site. Commande fictive CP-TEST.' );
					continue;
				}

				$id = comptoir_wa_trouve_commande( $ref, $ctx, $wa_id );
				if ( ! $id ) {
					continue;
				}
				// Reponse tapee a la main (pas un bouton) : elle ne compte que pour
				// une commande qui attend encore sa confirmation. Un « non » ecrit
				// plus tard, en reponse a autre chose, n'annule pas un colis parti.
				if ( ! $ref && $intention && ! in_array( get_post_meta( $id, 'cp_wa_statut', true ), array( 'envoye', 'sans_reponse' ), true ) ) {
					$intention = '';
				}
				if ( $texte ) {
					update_post_meta( $id, 'cp_wa_dernier', comptoir_coupe( sanitize_text_field( $texte ), 300 ) );
				}
				if ( $intention ) {
					comptoir_wa_applique( $id, $intention, $wa_id );
				} else {
					// Question ou message libre : a lire a la main, on le signale dans la liste.
					update_post_meta( $id, 'cp_wa_a_lire', 1 );
				}
			}

			// Accuses : envoye, recu, lu, echec.
			foreach ( isset( $v['statuses'] ) ? (array) $v['statuses'] : array() as $s ) {
				$id = isset( $s['id'] ) ? comptoir_wa_trouve_commande( '', (string) $s['id'] ) : 0;
				if ( ! $id ) {
					continue;
				}
				$etat = isset( $s['status'] ) ? (string) $s['status'] : '';
				$rang = array( 'sent' => 1, 'delivered' => 2, 'read' => 3 );
				$avant = (string) get_post_meta( $id, 'cp_wa_lu', true );
				if ( isset( $rang[ $etat ] ) && ( ! isset( $rang[ $avant ] ) || $rang[ $etat ] > $rang[ $avant ] ) ) {
					update_post_meta( $id, 'cp_wa_lu', $etat );
				}
				if ( 'failed' === $etat ) {
					$err = isset( $s['errors'][0]['title'] ) ? (string) $s['errors'][0]['title'] : 'échec';
					if ( isset( $s['errors'][0]['code'] ) ) {
						$err .= ' (' . (int) $s['errors'][0]['code'] . ')';
					}
					update_post_meta( $id, 'cp_wa_erreur', comptoir_coupe( $err, 200 ) );
					if ( 'envoye' === get_post_meta( $id, 'cp_wa_statut', true ) ) {
						update_post_meta( $id, 'cp_wa_statut', 'echec' );
					}
				}
			}
		}
	}
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/* ══════════════════════════════════════════════════════════════
   ADMIN — liste des commandes
══════════════════════════════════════════════════════════════ */

add_filter( 'manage_cp_commande_posts_columns', function ( $cols ) {
	$out = array();
	foreach ( $cols as $k => $v ) {
		$out[ $k ] = $v;
		if ( 'cp_etat' === $k ) {
			$out['cp_wa'] = 'WhatsApp';
		}
	}
	if ( ! isset( $out['cp_wa'] ) ) {
		$out['cp_wa'] = 'WhatsApp';
	}
	return $out;
}, 20 );

add_action( 'manage_cp_commande_posts_custom_column', function ( $col, $id ) {
	if ( 'cp_wa' !== $col ) {
		return;
	}
	$statut = (string) get_post_meta( $id, 'cp_wa_statut', true );
	$libelles = array(
		'envoye'       => array( '#8a6d1f', 'en attente' ),
		'confirme'     => array( '#227a4b', '✓ confirmée' ),
		'annule'       => array( '#b32d2e', '✕ annulée' ),
		'sans_reponse' => array( '#b35a00', 'sans réponse' ),
		'echec'        => array( '#b32d2e', 'échec d\'envoi' ),
	);
	if ( ! $statut ) {
		echo '<span style="color:#999">—</span>';
		return;
	}
	list( $couleur, $texte ) = isset( $libelles[ $statut ] ) ? $libelles[ $statut ] : array( '#555', $statut );
	$infos = array();
	if ( get_post_meta( $id, 'cp_wa_relance', true ) ) {
		$infos[] = 'relancé';
	}
	$lu = (string) get_post_meta( $id, 'cp_wa_lu', true );
	if ( $lu ) {
		$infos[] = array( 'sent' => 'envoyé', 'delivered' => 'reçu', 'read' => 'lu' )[ $lu ] ?? $lu;
	}
	if ( get_post_meta( $id, 'cp_wa_livraison', true ) ) {
		$infos[] = 'rappel livraison ' . ( get_post_meta( $id, 'cp_wa_livraison_vu', true ) ? 'vu 👍' : 'envoyé' );
	}
	$titre = (string) get_post_meta( $id, 'cp_wa_erreur', true );
	$dernier = (string) get_post_meta( $id, 'cp_wa_dernier', true );
	if ( $dernier ) {
		$titre .= ( $titre ? ' — ' : '' ) . 'Dernier message : « ' . $dernier . ' »';
	}
	printf( '<b style="color:%s" title="%s">%s</b>', esc_attr( $couleur ), esc_attr( $titre ), esc_html( $texte ) );
	if ( get_post_meta( $id, 'cp_wa_a_lire', true ) ) {
		echo ' <span title="' . esc_attr( $dernier ) . '" style="background:#b32d2e;color:#fff;border-radius:8px;padding:0 6px;font-size:11px">message à lire</span>';
	}
	if ( $infos ) {
		echo '<br><small style="color:#777">' . esc_html( implode( ' · ', $infos ) ) . '</small>';
	}
}, 10, 2 );

/** Actions sur une ligne : rappel de livraison, renvoi de la confirmation. */
add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( 'cp_commande' !== $post->post_type || ! comptoir_wa_pret() ) {
		return $actions;
	}
	$lien = function ( $quoi, $texte ) use ( $post ) {
		return sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cp_wa_' . $quoi . '&id=' . $post->ID ), 'cp_wa_' . $quoi . '_' . $post->ID ) ), esc_html( $texte ) );
	};
	$actions['cp_wa_livraison'] = $lien( 'livraison', 'WhatsApp : rappel livraison' );
	if ( in_array( get_post_meta( $post->ID, 'cp_wa_statut', true ), array( '', 'echec', 'sans_reponse' ), true ) ) {
		$actions['cp_wa_renvoi'] = $lien( 'renvoi', 'WhatsApp : renvoyer la confirmation' );
	}
	if ( get_post_meta( $post->ID, 'cp_wa_a_lire', true ) ) {
		$actions['cp_wa_lu'] = $lien( 'lu', 'Message lu' );
	}
	return $actions;
}, 10, 2 );

foreach ( array( 'livraison', 'renvoi', 'lu' ) as $cp_wa_quoi ) {
	add_action( 'admin_post_cp_wa_' . $cp_wa_quoi, function () use ( $cp_wa_quoi ) {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			wp_die( 'Accès refusé.' );
		}
		check_admin_referer( 'cp_wa_' . $cp_wa_quoi . '_' . $id );
		$msg = 'ok';
		if ( 'livraison' === $cp_wa_quoi ) {
			$res = comptoir_wa_rappel_livraison( $id );
			$msg = is_wp_error( $res ) ? 'echec' : 'livraison';
		} elseif ( 'renvoi' === $cp_wa_quoi ) {
			delete_post_meta( $id, 'cp_wa_statut' );
			delete_post_meta( $id, 'cp_wa_relance' );
			$msg = comptoir_wa_confirmation( $id ) ? 'renvoi' : 'echec';
		} else {
			delete_post_meta( $id, 'cp_wa_a_lire' );
		}
		wp_safe_redirect( add_query_arg( 'cp_wa', $msg, admin_url( 'edit.php?post_type=cp_commande' ) ) );
		exit;
	} );
}

/** Action groupee : rappel de livraison pour toutes les commandes cochees. */
add_filter( 'bulk_actions-edit-cp_commande', function ( $a ) {
	if ( comptoir_wa_pret() ) {
		$a['cp_wa_livraison'] = 'WhatsApp : rappel livraison';
	}
	return $a;
} );
add_filter( 'handle_bulk_actions-edit-cp_commande', function ( $url, $action, $ids ) {
	if ( 'cp_wa_livraison' !== $action ) {
		return $url;
	}
	$ok = 0;
	foreach ( (array) $ids as $id ) {
		if ( current_user_can( 'edit_post', $id ) && true === comptoir_wa_rappel_livraison( (int) $id ) ) {
			$ok++;
		}
	}
	return add_query_arg( array( 'cp_wa' => 'lot', 'cp_wa_n' => $ok, 'cp_wa_t' => count( (array) $ids ) ), $url );
}, 10, 3 );

add_action( 'admin_notices', function () {
	$ecran = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $ecran || 'edit-cp_commande' !== $ecran->id ) {
		return;
	}
	if ( ! comptoir_wa_pret() ) {
		printf(
			'<div class="notice notice-info"><p>La confirmation automatique sur WhatsApp n\'est pas active. <a href="%s">Réglages &gt; Confirmation WhatsApp</a></p></div>',
			esc_url( admin_url( 'options-general.php?page=comptoir-whatsapp' ) )
		);
	}
	$m = isset( $_GET['cp_wa'] ) ? sanitize_key( $_GET['cp_wa'] ) : '';
	$textes = array(
		'livraison' => 'Rappel de livraison envoyé sur WhatsApp.',
		'renvoi'    => 'Confirmation renvoyée sur WhatsApp.',
		'echec'     => 'Échec de l\'envoi WhatsApp : survolez le statut de la commande pour voir la raison.',
		'ok'        => 'Fait.',
	);
	if ( 'lot' === $m ) {
		printf( '<div class="notice notice-success is-dismissible"><p>Rappel de livraison envoyé à %d commande(s) sur %d.</p></div>', isset( $_GET['cp_wa_n'] ) ? absint( $_GET['cp_wa_n'] ) : 0, isset( $_GET['cp_wa_t'] ) ? absint( $_GET['cp_wa_t'] ) : 0 );
	} elseif ( isset( $textes[ $m ] ) ) {
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', 'echec' === $m ? 'error' : 'success', esc_html( $textes[ $m ] ) );
	}
} );

/* ══════════════════════════════════════════════════════════════
   ADMIN — page de reglages
══════════════════════════════════════════════════════════════ */

add_action( 'admin_menu', function () {
	add_options_page( 'Confirmation WhatsApp', 'Confirmation WhatsApp', 'manage_options', 'comptoir-whatsapp', 'comptoir_wa_page' );
} );

function comptoir_wa_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$avis = '';
	$type = 'success';
	if ( isset( $_POST['comptoir_wa'] ) ) {
		check_admin_referer( 'comptoir_wa' );
		$txt = function ( $k, $def = '' ) {
			return isset( $_POST[ $k ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) ) : $def;
		};
		$r = comptoir_wa_reglages();
		$r['actif']         = empty( $_POST['actif'] ) ? 0 : 1;
		$r['phone_id']      = preg_replace( '/[^0-9]/', '', $txt( 'phone_id' ) );
		$r['langue']        = preg_replace( '/[^A-Za-z_]/', '', $txt( 'langue', 'fr' ) ) ?: 'fr';
		$r['tpl_confirm']   = sanitize_key( $txt( 'tpl_confirm', 'cp_confirmation_commande' ) );
		$r['tpl_relance']   = sanitize_key( $txt( 'tpl_relance', 'cp_relance_confirmation' ) );
		$r['tpl_livraison'] = sanitize_key( $txt( 'tpl_livraison', 'cp_rappel_livraison' ) );
		$r['relance_h']     = max( 1, min( 12, absint( $txt( 'relance_h', '2' ) ) ) );
		$r['alerte_annule'] = empty( $_POST['alerte_annule'] ) ? 0 : 1;
		update_option( 'comptoir_wa', $r, false );
		if ( $txt( 'token' ) ) {
			update_option( 'comptoir_wa_token', $txt( 'token' ), false );
		}
		if ( $txt( 'secret' ) ) {
			update_option( 'comptoir_wa_secret', $txt( 'secret' ), false );
		}
		$avis = 'Réglages enregistrés.';

		$test = preg_replace( '/[^0-9]/', '', $txt( 'test_tel' ) );
		if ( $test ) {
			$test = comptoir_capi_tel_e164( $test );
			$res  = ( $r['phone_id'] && comptoir_wa_jeton() )
				? comptoir_wa_modele( $test, $r['tpl_confirm'], array( 'Test', 'CP-TEST', '1× Jean Paul Gaultier Le Male Elixir', '354 DH', '3 av. Mohammed V, Rabat' ), array( 'CP_OK|CP-TEST', 'CP_NO|CP-TEST' ) )
				: new WP_Error( 'cp_wa', 'Identifiant du numéro et jeton requis.' );
			if ( is_wp_error( $res ) ) {
				$avis = 'Réglages enregistrés, mais le message de test a échoué : ' . $res->get_error_message();
				$type = 'error';
			} else {
				$avis = 'Réglages enregistrés. Message de test envoyé au +' . $test . '.';
			}
		}
	}

	$r      = comptoir_wa_reglages();
	$webhook = rest_url( 'comptoir/v1/whatsapp' );
	echo '<div class="wrap"><h1>Confirmation WhatsApp</h1>';
	if ( $avis ) {
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $type ), esc_html( $avis ) );
	}
	echo '<p>Après chaque commande, le client reçoit un message WhatsApp avec deux boutons (je confirme / annuler). Sans réponse, il est relancé ; le jour de la livraison, un rappel part depuis la liste des commandes. Les réponses mettent à jour la colonne « WhatsApp » des commandes.</p>';
	echo '<p>La marche à suivre complète (compte WhatsApp Business, modèles à faire valider, webhook) est dans <code>README-INSTALLATION.txt</code>, partie « Confirmation WhatsApp ».</p>';

	echo '<form method="post">';
	wp_nonce_field( 'comptoir_wa' );
	echo '<input type="hidden" name="comptoir_wa" value="1"><table class="form-table" role="presentation"><tbody>';
	$ligne = function ( $label, $html, $aide = '' ) {
		echo '<tr><th scope="row">' . $label . '</th><td>' . $html . ( $aide ? '<p class="description">' . $aide . '</p>' : '' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	};
	$ligne( 'Activer', '<label><input type="checkbox" name="actif" value="1"' . checked( $r['actif'], 1, false ) . '> Envoyer les messages de confirmation</label>', 'À cocher une fois les trois modèles approuvés par Meta.' );
	$ligne( '<label for="cp-wa-pid">Identifiant du numéro</label>', '<input id="cp-wa-pid" name="phone_id" class="regular-text" value="' . esc_attr( $r['phone_id'] ) . '">', 'WhatsApp Manager ou developers.facebook.com &gt; votre app &gt; WhatsApp &gt; Configuration de l\'API : « Phone number ID ».' );
	$ligne( '<label for="cp-wa-tok">Jeton d\'accès permanent</label>', '<input id="cp-wa-tok" type="password" name="token" class="large-text" value="" autocomplete="off" placeholder="' . ( comptoir_wa_jeton() ? 'Enregistré — laisser vide pour le garder' : '' ) . '">', 'Jeton d\'un utilisateur système (Business Suite &gt; Paramètres &gt; Utilisateurs système), avec les autorisations whatsapp_business_messaging et whatsapp_business_management.' );
	$ligne( '<label for="cp-wa-sec">Clé secrète de l\'app</label>', '<input id="cp-wa-sec" type="password" name="secret" class="large-text" value="" autocomplete="off" placeholder="' . ( comptoir_wa_secret() ? 'Enregistrée — laisser vide pour la garder' : '' ) . '">', 'developers.facebook.com &gt; votre app &gt; Paramètres &gt; Général : « Clé secrète ». Sert à vérifier que les réponses viennent bien de Meta.' );
	$ligne( 'URL du webhook', '<code>' . esc_html( $webhook ) . '</code>', 'À coller dans votre app Meta &gt; WhatsApp &gt; Configuration &gt; Webhook, puis s\'abonner au champ <b>messages</b>.' );
	$ligne( 'Jeton de vérification', '<code>' . esc_html( comptoir_wa_verify_token() ) . '</code>', 'À coller au même endroit, champ « Vérifier le jeton ».' );
	$ligne( 'Modèles', 'Confirmation <input name="tpl_confirm" value="' . esc_attr( $r['tpl_confirm'] ) . '"> Relance <input name="tpl_relance" value="' . esc_attr( $r['tpl_relance'] ) . '"> Livraison <input name="tpl_livraison" value="' . esc_attr( $r['tpl_livraison'] ) . '"> Langue <input name="langue" size="4" value="' . esc_attr( $r['langue'] ) . '">', 'Noms exacts des modèles créés dans WhatsApp Manager, et leur code de langue.' );
	$ligne( 'Relance après', '<input type="number" min="1" max="12" name="relance_h" value="' . (int) $r['relance_h'] . '"> heures sans réponse', 'Jamais entre 22 h et 9 h (heure du Maroc) : elle part alors le matin.' );
	$ligne( 'Alerte', '<label><input type="checkbox" name="alerte_annule" value="1"' . checked( $r['alerte_annule'], 1, false ) . '> M\'envoyer un e-mail quand un client annule</label>' );
	$ligne( '<label for="cp-wa-test">Envoyer un test</label>', '<input id="cp-wa-test" name="test_tel" class="regular-text" placeholder="06 12 34 56 78">', 'Facultatif : envoie le modèle de confirmation (commande fictive CP-TEST) à ce numéro en enregistrant.' );
	echo '</tbody></table>';
	submit_button( 'Enregistrer' );
	echo '</form></div>';
}
