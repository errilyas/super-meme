<?php
/**
 * Messages WhatsApp de suivi (colis expédié, livreur affecté) et achat Meta compté à la livraison.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Identifiants WhatsApp : on réutilise ceux de « Confirmation WhatsApp »   */
/* ------------------------------------------------------------------ */

function cox_wa_creds() {
	$s = cox_settings();
	if ( $s['wa_phone_id'] && $s['wa_token'] ) {
		return array( $s['wa_phone_id'], $s['wa_token'] );
	}
	foreach ( array( 'comptoir_wa', 'comptoir_whatsapp', 'cp_whatsapp', 'cp_wa' ) as $opt ) {
		$o = get_option( $opt );
		if ( is_array( $o ) && ! empty( $o['phone_id'] ) && ! empty( $o['token'] ) ) {
			return array( $o['phone_id'], $o['token'] );
		}
	}
	return array( '', '' );
}

function cox_tracking_url( $order_id ) {
	$token = get_post_meta( $order_id, '_cox_token', true );
	return $token ? add_query_arg( 'colis', $token, home_url( '/' ) ) : '';
}

function cox_first_name( $name ) {
	$p = preg_split( '/\s+/', trim( (string) $name ) );
	return $p && $p[0] ? $p[0] : $name;
}

/** Envoie un modèle WhatsApp. $params = textes {{1}}, {{2}}… ; le bouton lien reçoit le jeton de suivi. */
function cox_wa_send( $order_id, $template, $params ) {
	list( $phone_id, $token ) = cox_wa_creds();
	$o = cox_order( $order_id );
	if ( ! $phone_id || ! $token || ! $o || ! $o['phone'] ) {
		cox_log( $order_id, 'WhatsApp non envoyé (identifiants ou téléphone manquants).' );
		return false;
	}
	$s          = cox_settings();
	$components = array(
		array(
			'type'       => 'body',
			'parameters' => array_map(
				function ( $t ) {
					return array( 'type' => 'text', 'text' => (string) $t );
				},
				$params
			),
		),
	);
	$tok = get_post_meta( $order_id, '_cox_token', true );
	if ( $tok ) {
		$components[] = array(
			'type'       => 'button',
			'sub_type'   => 'url',
			'index'      => '0',
			'parameters' => array( array( 'type' => 'text', 'text' => $tok ) ),
		);
	}
	$res = wp_remote_post(
		'https://graph.facebook.com/v21.0/' . rawurlencode( $phone_id ) . '/messages',
		array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'messaging_product' => 'whatsapp',
					'to'                => cox_phone_intl( $o['phone'] ),
					'type'              => 'template',
					'template'          => array(
						'name'       => $template,
						'language'   => array( 'code' => $s['wa_lang'] ),
						'components' => $components,
					),
				)
			),
		)
	);
	if ( is_wp_error( $res ) ) {
		cox_log( $order_id, 'WhatsApp « ' . $template . ' » : ' . $res->get_error_message() );
		return false;
	}
	$code = wp_remote_retrieve_response_code( $res );
	if ( $code >= 300 ) {
		$j = json_decode( wp_remote_retrieve_body( $res ), true );
		cox_log( $order_id, 'WhatsApp « ' . $template . ' » refusé : ' . ( isset( $j['error']['message'] ) ? $j['error']['message'] : 'HTTP ' . $code ) );
		return false;
	}
	cox_log( $order_id, 'WhatsApp « ' . $template . ' » envoyé au client.' );
	return true;
}

/** Pas de message entre 22 h et 9 h (heure du Maroc) : on décale au matin. */
function cox_wa_queue( $order_id, $kind ) {
	$s = cox_settings();
	if ( ! $s['wa_enable'] ) {
		return;
	}
	$tz   = new DateTimeZone( 'Africa/Casablanca' );
	$now  = new DateTime( 'now', $tz );
	$hour = (int) $now->format( 'G' );
	if ( $hour >= 22 || $hour < 9 ) {
		$at = new DateTime( 'today 09:05', $tz );
		if ( $hour >= 22 ) {
			$at->modify( '+1 day' );
		}
		wp_schedule_single_event( $at->getTimestamp(), 'cox_wa_deferred', array( (int) $order_id, $kind ) );
		cox_log( $order_id, 'WhatsApp « ' . $kind . ' » programmé pour 9 h.' );
		return;
	}
	cox_wa_dispatch( $order_id, $kind );
}

add_action( 'cox_wa_deferred', 'cox_wa_dispatch', 10, 2 );

function cox_wa_dispatch( $order_id, $kind ) {
	$s    = cox_settings();
	$o    = cox_order( $order_id );
	$sent = get_post_meta( $order_id, '_cox_wa', true );
	$sent = is_array( $sent ) ? $sent : array();
	if ( ! $o || 'annulee' === cox_get_etat( $order_id ) || get_post_meta( $order_id, '_cox_final', true ) ) {
		return;
	}
	if ( 'expedie' === $kind && empty( $sent['expedie'] ) ) {
		if ( cox_wa_send( $order_id, $s['wa_tpl_expedie'], array( cox_first_name( $o['name'] ), $o['ref'] ) ) ) {
			$sent['expedie'] = time();
		}
	}
	if ( 'livreur' === $kind ) {
		$tel = cox_phone_local( get_post_meta( $order_id, '_cox_livreur_tel', true ) );
		$nom = get_post_meta( $order_id, '_cox_livreur', true );
		if ( $tel && ( empty( $sent['livreur'] ) || $sent['livreur'] !== $tel ) ) {
			if ( cox_wa_send( $order_id, $s['wa_tpl_livreur'], array( cox_first_name( $o['name'] ), $o['ref'], $nom ? $nom : 'OzonExpress', $tel ) ) ) {
				$sent['livreur'] = $tel;
			}
		}
	}
	update_post_meta( $order_id, '_cox_wa', $sent );
}

add_action(
	'cox_status_changed',
	function ( $order_id, $status, $old, $etat ) {
		if ( 'expediee' === $etat ) {
			cox_wa_queue( $order_id, 'expedie' );
		}
	},
	10,
	4
);

add_action(
	'cox_livreur_assigned',
	function ( $order_id ) {
		cox_wa_queue( $order_id, 'livreur' );
	}
);

/* ------------------------------------------------------------------ */
/* Achat Meta (API Conversions) à la livraison                         */
/* ------------------------------------------------------------------ */

add_action(
	'cox_etat_changed',
	function ( $order_id, $etat ) {
		if ( 'livree' === $etat ) {
			cox_capi_purchase( $order_id );
		}
	},
	10,
	2
);

// Une commande marquée « Livrée » à la main dans le suivi compte aussi.
add_action(
	'updated_post_meta',
	function ( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( COX_PT !== get_post_type( $object_id ) || ! empty( $GLOBALS['cox_internal_write'] ) ) {
			return;
		}
		$k = cox_settings()['etat_key'];
		if ( $k && $meta_key === $k && 'livree' === $meta_value ) {
			cox_capi_purchase( $object_id );
		}
	},
	20,
	4
);

function cox_capi_purchase( $order_id ) {
	$s = cox_settings();
	if ( ! $s['capi_enable'] || ! $s['pixel_id'] || ! $s['capi_token'] || get_post_meta( $order_id, '_cox_capi', true ) ) {
		return false;
	}
	$o = cox_order( $order_id );
	if ( ! $o || $o['total'] <= 0 ) {
		return false;
	}
	$h    = function ( $v ) {
		return hash( 'sha256', strtolower( trim( (string) $v ) ) );
	};
	$user = array( 'country' => array( $h( 'ma' ) ) );
	if ( $o['phone'] ) {
		$user['ph'] = array( $h( cox_phone_intl( $o['phone'] ) ) );
	}
	if ( $o['name'] ) {
		$user['fn'] = array( $h( remove_accents( cox_first_name( $o['name'] ) ) ) );
	}
	if ( $o['city'] ) {
		$user['ct'] = array( $h( preg_replace( '/[^a-z]/', '', strtolower( remove_accents( $o['city'] ) ) ) ) );
	}
	$user['external_id'] = array( $h( $o['ref'] ) );

	$payload = array(
		'data' => array(
			array(
				'event_name'       => 'Purchase',
				'event_time'       => time(),
				'event_id'         => 'livree-' . $o['ref'],
				'action_source'    => 'system_generated',
				'event_source_url' => home_url( '/' ),
				'user_data'        => $user,
				'custom_data'      => array(
					'currency' => 'MAD',
					'value'    => (float) $o['total'],
					'order_id' => $o['ref'],
				),
			),
		),
	);
	$res = wp_remote_post(
		'https://graph.facebook.com/v21.0/' . rawurlencode( $s['pixel_id'] ) . '/events?access_token=' . rawurlencode( $s['capi_token'] ),
		array(
			'timeout' => 20,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $payload ),
		)
	);
	if ( is_wp_error( $res ) || wp_remote_retrieve_response_code( $res ) >= 300 ) {
		$m = is_wp_error( $res ) ? $res->get_error_message() : wp_remote_retrieve_body( $res );
		cox_log( $order_id, 'Achat Meta non envoyé : ' . mb_substr( wp_strip_all_tags( $m ), 0, 200 ) );
		return false;
	}
	update_post_meta( $order_id, '_cox_capi', time() );
	cox_log( $order_id, 'Achat Meta envoyé (' . $o['total'] . ' MAD).' );
	return true;
}
