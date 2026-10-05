<?php
/**
 * Client de l'API OzonExpress : villes, création de colis, suivi.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Appel authentifié : POST https://api.ozonexpress.ma/customers/{id}/{clé}/{action} */
function cox_api( $action, $body = array() ) {
	$s = cox_settings();
	if ( ! $s['customer_id'] || ! $s['api_key'] ) {
		return new WP_Error( 'cox_creds', 'Identifiants OzonExpress manquants (Réglages › OzonExpress).' );
	}
	$url = COX_API . '/customers/' . rawurlencode( trim( $s['customer_id'] ) ) . '/' . rawurlencode( trim( $s['api_key'] ) ) . '/' . $action;
	$res = wp_remote_post(
		$url,
		array(
			'timeout' => 25,
			'body'    => $body,
			'headers' => array( 'Accept' => 'application/json' ),
		)
	);
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$code = wp_remote_retrieve_response_code( $res );
	$raw  = wp_remote_retrieve_body( $res );
	$json = json_decode( $raw, true );
	if ( null === $json ) {
		return new WP_Error( 'cox_http', 'Réponse OzonExpress illisible (HTTP ' . $code . ') : ' . mb_substr( wp_strip_all_tags( $raw ), 0, 200 ) );
	}
	return $json;
}

/** Cherche une clé (insensible à la casse, tirets/espaces ignorés) n'importe où dans la réponse. */
function cox_find( $data, $names ) {
	if ( ! is_array( $data ) ) {
		return null;
	}
	$names = array_map( 'cox_norm', (array) $names );
	foreach ( $data as $k => $v ) {
		if ( in_array( cox_norm( $k ), $names, true ) && ! is_array( $v ) && '' !== (string) $v ) {
			return $v;
		}
	}
	foreach ( $data as $v ) {
		if ( is_array( $v ) ) {
			$r = cox_find( $v, $names );
			if ( null !== $r ) {
				return $r;
			}
		}
	}
	return null;
}

/** Message d'erreur renvoyé par OzonExpress, s'il y en a un. */
function cox_api_error( $json ) {
	$result = cox_find( $json, array( 'RESULT' ) );
	if ( $result && 'SUCCESS' !== strtoupper( (string) $result ) ) {
		$msg = cox_find( $json, array( 'MESSAGE', 'ERROR', 'ERRORS' ) );
		return $msg ? (string) $msg : (string) $result;
	}
	return '';
}

/* ------------------------------------------------------------------ */
/* Villes                                                              */
/* ------------------------------------------------------------------ */

function cox_cities() {
	$c = get_transient( 'cox_cities' );
	if ( is_array( $c ) && $c ) {
		return $c;
	}
	$res = wp_remote_get( COX_API . '/cities', array( 'timeout' => 25 ) );
	if ( is_wp_error( $res ) ) {
		return array();
	}
	$json = json_decode( wp_remote_retrieve_body( $res ), true );
	$list = isset( $json['CITIES'] ) && is_array( $json['CITIES'] ) ? $json['CITIES'] : array();
	$c    = array();
	foreach ( $list as $row ) {
		if ( isset( $row['ID'], $row['NAME'] ) ) {
			$c[ (int) $row['ID'] ] = array(
				'name'  => (string) $row['NAME'],
				'price' => isset( $row['DELIVERED-PRICE'] ) ? (float) $row['DELIVERED-PRICE'] : 0,
			);
		}
	}
	if ( $c ) {
		set_transient( 'cox_cities', $c, WEEK_IN_SECONDS );
	}
	return $c;
}

/** Correspondances saisies à la main : « Ville du site = ID Ozon », une par ligne. */
function cox_city_overrides() {
	$s   = cox_settings();
	$map = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $s['city_overrides'] ) as $line ) {
		if ( strpos( $line, '=' ) === false ) {
			continue;
		}
		list( $a, $b ) = array_map( 'trim', explode( '=', $line, 2 ) );
		if ( $a && ctype_digit( $b ) ) {
			$map[ cox_norm( $a ) ] = (int) $b;
		}
	}
	return $map;
}

/** Retourne l'ID OzonExpress de la ville, ou 0 si introuvable. */
function cox_city_id( $city ) {
	$n = cox_norm( $city );
	if ( ! $n ) {
		return 0;
	}
	$ov = cox_city_overrides();
	if ( isset( $ov[ $n ] ) ) {
		return $ov[ $n ];
	}
	$aliases = array(
		'casa'        => 'casablanca',
		'darbeida'    => 'casablanca',
		'eljadida'    => 'eljadida',
		'jadida'      => 'eljadida',
		'tanja'       => 'tanger',
		'tangier'     => 'tanger',
		'fez'         => 'fes',
		'marrakesh'   => 'marrakech',
		'kenitra'     => 'kenitra',
		'sale'        => 'sale',
		'laayoune'    => 'laayoune',
		'elayoun'     => 'laayoune',
		'beni mellal' => 'benimellal',
	);
	if ( isset( $aliases[ $n ] ) ) {
		$n = $aliases[ $n ];
	}
	$cities = cox_cities();
	$best   = 0;
	$bestd  = 99;
	foreach ( $cities as $id => $c ) {
		$cn = cox_norm( $c['name'] );
		if ( $cn === $n ) {
			return (int) $id;
		}
		$d = levenshtein( $cn, $n );
		if ( $d < $bestd ) {
			$bestd = $d;
			$best  = (int) $id;
		}
	}
	// Une petite faute de frappe (≤ 2 lettres sur un nom assez long) est acceptée.
	return ( $bestd <= 2 && strlen( $n ) >= 5 ) ? $best : 0;
}

/* ------------------------------------------------------------------ */
/* Création du colis                                                   */
/* ------------------------------------------------------------------ */

add_action( 'cox_create_parcel', 'cox_create_parcel' );

function cox_create_parcel( $order_id ) {
	$order_id = (int) $order_id;
	if ( get_post_meta( $order_id, '_cox_tracking', true ) ) {
		return get_post_meta( $order_id, '_cox_tracking', true );
	}
	// Verrou anti-doublon (deux déclencheurs à quelques secondes d'intervalle).
	if ( get_transient( 'cox_lock_' . $order_id ) ) {
		return new WP_Error( 'cox_lock', 'Création déjà en cours.' );
	}
	set_transient( 'cox_lock_' . $order_id, 1, 120 );

	$o = cox_order( $order_id );
	if ( ! $o ) {
		delete_transient( 'cox_lock_' . $order_id );
		return new WP_Error( 'cox_order', 'Commande introuvable.' );
	}
	if ( ! in_array( get_post_status( $order_id ), array( 'publish', 'private' ), true ) ) {
		delete_transient( 'cox_lock_' . $order_id );
		return new WP_Error( 'cox_status', 'Commande en brouillon ou à la corbeille : pas de colis.' );
	}
	$s       = cox_settings();
	$city_id = cox_city_id( $o['city'] );
	$errors  = array();
	if ( ! $o['phone'] ) {
		$errors[] = 'téléphone introuvable';
	}
	if ( ! $city_id ) {
		$errors[] = 'ville « ' . $o['city'] . ' » inconnue chez OzonExpress (ajoutez-la dans Réglages › OzonExpress › Villes)';
	}
	if ( $o['total'] <= 0 ) {
		$errors[] = 'montant introuvable';
	}
	if ( $errors ) {
		$msg = 'Colis non créé : ' . implode( ', ', $errors ) . '.';
		update_post_meta( $order_id, '_cox_error', $msg );
		cox_log( $order_id, $msg );
		delete_transient( 'cox_lock_' . $order_id );
		return new WP_Error( 'cox_data', $msg );
	}

	// Colis déjà saisi à la main chez OzonExpress avec la référence de la commande : on le relie, pas de doublon.
	$ref = cox_tracking_from_ref( $o['ref'] );
	if ( $ref && cox_parcel_exists( $ref ) ) {
		delete_transient( 'cox_lock_' . $order_id );
		cox_link_parcel( $order_id, $ref, 'Colis déjà présent chez OzonExpress : relié à la commande (' . $ref . ').' );
		cox_sync_order( $order_id );
		return $ref;
	}

	$address = $o['address'] ? $o['address'] : $o['city'] . ' (adresse à confirmer par téléphone)';
	$body    = array(
		'tracking-number' => $ref,
		'parcel-receiver' => $o['name'],
		'parcel-phone'    => cox_phone_local( $o['phone'] ),
		'parcel-city'     => $city_id,
		'parcel-address'  => $address,
		'parcel-note'     => mb_substr( $o['ref'] . ' — ' . $o['items'], 0, 250 ),
		'parcel-price'    => $o['total'],
		'parcel-nature'   => mb_substr( $o['items'] ? $o['items'] : $s['nature'], 0, 120 ),
		'parcel-stock'    => 0,
		'parcel-open'     => $s['parcel_open'] ? 1 : 2, // OzonExpress : 1 = ouvrir, 2 = ne pas ouvrir.
		'parcel-fragile'  => $s['parcel_fragile'] ? 1 : 0,
		'parcel-replace'  => 0,
	);
	$body = apply_filters( 'cox_parcel_body', $body, $o );

	$json = cox_api( 'add-parcel', $body );
	delete_transient( 'cox_lock_' . $order_id );

	if ( is_wp_error( $json ) ) {
		update_post_meta( $order_id, '_cox_error', $json->get_error_message() );
		cox_log( $order_id, 'Échec création colis : ' . $json->get_error_message() );
		return $json;
	}
	$err      = cox_api_error( $json );
	$tracking = cox_find( $json, array( 'TRACKING-NUMBER', 'TRACKING_NUMBER', 'TRACKING' ) );
	if ( $err || ! $tracking ) {
		$msg = 'OzonExpress a refusé le colis : ' . ( $err ? $err : 'pas de n° de suivi dans la réponse' );
		update_post_meta( $order_id, '_cox_error', $msg );
		cox_log( $order_id, $msg );
		return new WP_Error( 'cox_api', $msg );
	}

	$fee = cox_find( $json, array( 'DELIVERED-PRICE' ) );
	if ( null !== $fee ) {
		update_post_meta( $order_id, '_cox_fee', (float) $fee );
	}
	cox_link_parcel( $order_id, $tracking, 'Colis créé chez OzonExpress : ' . $tracking );
	return $tracking;
}

/** N° de suivi à partir de la référence de la commande (même format que les colis saisis à la main : CP-AAMMJJ-XXXX). */
function cox_tracking_from_ref( $ref ) {
	$ref = strtoupper( preg_replace( '/[^A-Za-z0-9-]/', '', (string) $ref ) );
	return apply_filters( 'cox_tracking_number', $ref );
}

/** Vrai si OzonExpress connaît déjà ce n° de suivi. */
function cox_parcel_exists( $tracking ) {
	$json = cox_api( 'parcel-info', array( 'tracking-number' => $tracking ) );
	if ( is_wp_error( $json ) || cox_api_error( $json ) ) {
		return false;
	}
	return (bool) cox_find( $json, array( 'RECEIVER', 'PHONE', 'CITY_ID', 'CITY_NAME', 'PRICE' ) );
}

/** Enregistre le colis sur la commande (création ou rattachement d'un colis existant). */
function cox_link_parcel( $order_id, $tracking, $log ) {
	update_post_meta( $order_id, '_cox_tracking', sanitize_text_field( $tracking ) );
	update_post_meta( $order_id, '_cox_created', time() );
	if ( ! get_post_meta( $order_id, '_cox_status', true ) ) {
		update_post_meta( $order_id, '_cox_status', 'Nouveau colis' );
	}
	delete_post_meta( $order_id, '_cox_error' );
	if ( ! get_post_meta( $order_id, '_cox_token', true ) ) {
		update_post_meta( $order_id, '_cox_token', strtolower( wp_generate_password( 14, false, false ) ) );
	}
	cox_set_colis_field( $order_id, $tracking );
	cox_log( $order_id, $log );
	do_action( 'cox_parcel_created', $order_id, $tracking );
}

/* ------------------------------------------------------------------ */
/* Suivi                                                               */
/* ------------------------------------------------------------------ */

/**
 * Interroge OzonExpress et renvoie :
 * [ 'status' => 'Livré', 'time' => '…', 'history' => [ [statut, date, commentaire], … ], 'livreur' => '…', 'livreur_tel' => '…' ]
 */
function cox_fetch_tracking( $tracking ) {
	$json = cox_api( 'tracking', array( 'tracking-number' => $tracking ) );
	if ( is_wp_error( $json ) ) {
		return $json;
	}
	$err = cox_api_error( $json );
	if ( $err ) {
		return new WP_Error( 'cox_api', $err );
	}
	$out = array( 'status' => '', 'time' => '', 'history' => array(), 'livreur' => '', 'livreur_tel' => '' );

	// Historique : liste d'étapes avec STATUT / TIME_STR / COMMENT (les noms exacts varient, on reste souple).
	$hist = cox_find_list( $json, array( 'HISTORY', 'HISTORIQUE' ) );
	foreach ( (array) $hist as $h ) {
		if ( ! is_array( $h ) ) {
			continue;
		}
		$out['history'][] = array(
			'statut' => (string) cox_find( $h, array( 'STATUT', 'STATUS', 'NAME' ) ),
			'date'   => cox_find_date( $h ),
			'note'   => cox_clean_note( (string) cox_find( $h, array( 'COMMENT', 'COMMENTAIRE', 'NOTE' ) ) ),
		);
	}
	$last = cox_find_list( $json, array( 'LAST_TRACKING', 'LAST-TRACKING' ) );
	if ( is_array( $last ) ) {
		$out['status'] = (string) cox_find( $last, array( 'STATUT', 'STATUS' ) );
		$out['time']   = cox_find_date( $last );
	}
	if ( ! $out['status'] && $out['history'] ) {
		$lastrow       = end( $out['history'] );
		$out['status'] = $lastrow['statut'];
		$out['time']   = $lastrow['date'];
	}
	if ( ! $out['status'] ) {
		$out['status'] = (string) cox_find( $json, array( 'STATUT', 'STATUS' ) );
	}

	// Livreur : on cherche dans le suivi, puis dans les commentaires du suivi, puis dans la fiche colis.
	list( $out['livreur'], $out['livreur_tel'] ) = cox_extract_livreur( $json );
	if ( ! $out['livreur_tel'] ) {
		list( $out['livreur'], $out['livreur_tel'] ) = cox_livreur_from_history( $out['history'], $out['livreur'] );
	}
	if ( ! $out['livreur_tel'] ) {
		$info = cox_api( 'parcel-info', array( 'tracking-number' => $tracking ) );
		if ( ! is_wp_error( $info ) ) {
			list( $n, $t ) = cox_extract_livreur( $info );
			$out['livreur']     = $out['livreur'] ? $out['livreur'] : $n;
			$out['livreur_tel'] = $t;
		}
	}
	return $out;
}

function cox_find_list( $data, $names ) {
	if ( ! is_array( $data ) ) {
		return null;
	}
	$names = array_map( 'cox_norm', (array) $names );
	foreach ( $data as $k => $v ) {
		if ( in_array( cox_norm( $k ), $names, true ) && is_array( $v ) ) {
			return $v;
		}
	}
	foreach ( $data as $v ) {
		if ( is_array( $v ) ) {
			$r = cox_find_list( $v, $names );
			if ( null !== $r ) {
				return $r;
			}
		}
	}
	return null;
}

/** Nom et téléphone du livreur, quelle que soit la forme de la réponse. */
function cox_extract_livreur( $json ) {
	$name = '';
	$tel  = '';
	$walk = function ( $node, $inside ) use ( &$walk, &$name, &$tel ) {
		if ( ! is_array( $node ) ) {
			return;
		}
		foreach ( $node as $k => $v ) {
			$nk  = cox_norm( $k );
			$hit = $inside || (bool) preg_match( '/livreur|deliveryman|deliverer|driver|courier|coursier/', $nk );
			if ( is_array( $v ) ) {
				$walk( $v, $hit );
				continue;
			}
			if ( ! $hit || '' === (string) $v ) {
				continue;
			}
			if ( ! $tel && ( preg_match( '/phone|tel|mobile|gsm/', $nk ) || cox_looks_like_phone( $v ) ) && cox_looks_like_phone( $v ) ) {
				$tel = (string) $v;
			} elseif ( ! $name && ! cox_looks_like_phone( $v ) && preg_match( '/name|nom|livreur|deliveryman|driver/', $nk ) ) {
				$name = (string) $v;
			}
		}
	};
	$walk( $json, false );
	return array( sanitize_text_field( $name ), sanitize_text_field( $tel ) );
}

/**
 * Traduit le statut OzonExpress en étape du site.
 * Retourne [ étape du suivi ou '', final (bool), libellé client ]
 */
function cox_map_status( $status ) {
	$n = cox_norm( $status );
	if ( '' === $n ) {
		return array( '', false, '' );
	}
	if ( preg_match( '/^livre|^delivered|livree$/', $n ) ) {
		return array( 'livree', true, 'Livrée' );
	}
	if ( preg_match( '/retourne|retour(?!ner)|returned/', $n ) ) {
		return array( 'annulee', true, 'Retournée' );
	}
	if ( preg_match( '/annule|cancel/', $n ) ) {
		return array( 'annulee', true, 'Annulée' );
	}
	if ( preg_match( '/refuse/', $n ) ) {
		// Un refus n'est pas encore définitif : on attend « Retourné » pour clôturer.
		return array( '', false, 'Refusée par le client' );
	}
	if ( preg_match( '/distribution|encoursdelivraison|enlivraison|outfordelivery|affecte|livreur/', $n ) ) {
		return array( 'expediee', false, 'En cours de livraison' );
	}
	if ( preg_match( '/ramasse|expedie|transit|recu|envoye|depart|arrive|pickup|picked/', $n ) ) {
		return array( 'expediee', false, 'Expédiée' );
	}
	if ( preg_match( '/reporte|pasdereponse|injoignable|nerepondpas|programme/', $n ) ) {
		return array( '', false, 'Livraison reportée' );
	}
	return array( '', false, $status );
}

/** Commentaire OzonExpress lisible : sans balises HTML ni séparateurs en double. */
function cox_clean_note( $txt ) {
	$txt = preg_replace( '/<br\s*\/?>/i', ' | ', (string) $txt );
	$txt = wp_strip_all_tags( $txt );
	$txt = preg_replace( '/\s*\|\s*(\|\s*)+/', ' | ', $txt );
	return trim( preg_replace( '/\s+/', ' ', $txt ), " |\t\n\r" );
}

/**
 * Cherche le livreur dans les étapes du suivi, de la plus récente à la plus ancienne.
 * Format OzonExpress : « Livreur: mourad tamar | Commentaire: Livreur: mourad tamar | Téléphone: 06xxxxxxxx ».
 */
function cox_livreur_from_history( $history, $name = '' ) {
	$phone_re = '/(?:\+?212[\s.-]?|0)[5-7](?:[\s.-]?\d){8}/';
	$tel      = '';
	foreach ( array_reverse( (array) $history ) as $row ) {
		$txt = cox_clean_note( $row['statut'] . ' | ' . $row['note'] );
		if ( ! $name && preg_match( '/livreur\s*:\s*([^|:\d]{3,40}?)\s*(?:\||t[ée]l[ée]phone|commentaire|$)/iu', $txt, $n ) ) {
			$name = trim( $n[1] );
		}
		if ( ! $tel && preg_match( '/livreur|liveur|distribution|affect|coursier|t[ée]l[ée]phone/iu', $txt ) && preg_match( $phone_re, $txt, $m ) ) {
			$tel = preg_replace( '/[^\d+]/', '', $m[0] );
		}
		if ( $name && $tel ) {
			break;
		}
	}
	return array( $name, $tel );
}

/** Date lisible d'une étape : TIME_STR en priorité (TIME est un horodatage brut). */
function cox_find_date( $row ) {
	foreach ( array( 'TIME_STR', 'DATE', 'CREATED_AT', 'TIME' ) as $k ) {
		$v = cox_find( $row, array( $k ) );
		if ( null !== $v && '' !== (string) $v ) {
			if ( ctype_digit( (string) $v ) && strlen( (string) $v ) >= 9 ) {
				return wp_date( 'Y-m-d H:i', (int) $v );
			}
			return (string) $v;
		}
	}
	return '';
}
