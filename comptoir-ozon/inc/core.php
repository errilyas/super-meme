<?php
/**
 * Réglages, lecture des commandes du thème, détection des champs, déclencheur « commande confirmée ».
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Réglages                                                            */
/* ------------------------------------------------------------------ */

function cox_defaults() {
	return array(
		'customer_id'    => '',
		'api_key'        => '',
		'auto_create'    => 1,
		'trigger_key'    => '',
		'trigger_values' => 'confirmee, confirme, confirmé, confirmée, confirmed',
		'etat_key'       => 'cpb_suivi',
		'colis_key'      => 'cpb_colis',
		'phone_key'      => 'cp_tel',
		'address_key'    => 'cp_adresse',
		'nature'         => 'Parfum',
		'parcel_open'    => 1,
		'parcel_fragile' => 1,
		'city_overrides' => '',
		'wa_enable'      => 0,
		'wa_tpl_expedie' => 'cp_suivi_colis',
		'wa_tpl_livreur' => 'cp_livreur_affecte',
		'wa_lang'        => 'ar',
		'wa_phone_id'    => '',
		'wa_token'       => '',
		'capi_enable'    => 0,
		'pixel_id'       => '',
		'capi_token'     => '',
		'shop_whatsapp'  => '212717961180',
	);
}

function cox_settings() {
	$s = get_option( 'cox_settings', array() );
	$d = cox_defaults();
	$s = wp_parse_args( is_array( $s ) ? $s : array(), $d );
	// Champs du thème : une valeur vide reprend la valeur connue.
	foreach ( array( 'etat_key', 'colis_key', 'phone_key', 'address_key', 'wa_tpl_expedie', 'wa_tpl_livreur', 'wa_lang' ) as $k ) {
		if ( '' === trim( (string) $s[ $k ] ) ) {
			$s[ $k ] = $d[ $k ];
		}
	}
	// Ancien nom de modèle, refusé par Meta et remplacé par cp_suivi_colis.
	if ( 'cp_colis_expedie' === $s['wa_tpl_expedie'] ) {
		$s['wa_tpl_expedie'] = 'cp_suivi_colis';
	}
	return $s;
}

function cox_update_setting( $key, $value ) {
	$s         = cox_settings();
	$s[ $key ] = $value;
	update_option( 'cox_settings', $s, false );
}

/* ------------------------------------------------------------------ */
/* Petits outils                                                       */
/* ------------------------------------------------------------------ */

function cox_norm( $str ) {
	$str = remove_accents( (string) $str );
	$str = strtolower( $str );
	return preg_replace( '/[^a-z0-9]/', '', $str );
}

/**
 * Le pendant de cox_norm() pour l'arabe, que cox_norm() efface : sans voyelles
 * courtes ni tatouil, alifs et ta marbouta unifies, espaces retires.
 * « بن سليمان » et « بنسليمان » donnent la meme cle.
 */
function cox_norm_ar( $str ) {
	$str = preg_replace( '/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', (string) $str );
	$str = strtr( $str, array( 'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ى' => 'ي', 'ة' => 'ه' ) );
	return (string) preg_replace( '/[^\x{0621}-\x{064A}]/u', '', $str );
}

/** Numéro marocain au format international sans « + » : 212XXXXXXXXX */
function cox_phone_intl( $phone ) {
	$d = preg_replace( '/\D/', '', (string) $phone );
	if ( 0 === strpos( $d, '00212' ) ) {
		$d = substr( $d, 2 );
	}
	if ( 0 === strpos( $d, '212' ) && strlen( $d ) === 12 ) {
		return $d;
	}
	if ( 0 === strpos( $d, '0' ) && strlen( $d ) === 10 ) {
		return '212' . substr( $d, 1 );
	}
	if ( strlen( $d ) === 9 ) {
		return '212' . $d;
	}
	return $d;
}

/** Numéro marocain au format local 0XXXXXXXXX (celui qu'attend le livreur). */
function cox_phone_local( $phone ) {
	$i = cox_phone_intl( $phone );
	if ( 0 === strpos( $i, '212' ) && strlen( $i ) === 12 ) {
		return '0' . substr( $i, 3 );
	}
	return preg_replace( '/\D/', '', (string) $phone );
}

function cox_looks_like_phone( $v ) {
	if ( ! is_scalar( $v ) ) {
		return false;
	}
	$d = preg_replace( '/\D/', '', (string) $v );
	return (bool) preg_match( '/^(00212|212|0)?[5-7]\d{8}$/', $d );
}

/** Masque une valeur pour l'écran de diagnostic (on montre la forme, pas la donnée). */
function cox_mask( $v ) {
	if ( is_array( $v ) || is_object( $v ) ) {
		return '[' . gettype( $v ) . ']';
	}
	$v = (string) $v;
	if ( in_array( strtolower( $v ), cox_known_states(), true ) || strlen( $v ) <= 3 ) {
		return $v;
	}
	if ( cox_looks_like_phone( $v ) ) {
		return substr( preg_replace( '/\D/', '', $v ), 0, 3 ) . '•••••••';
	}
	return mb_substr( $v, 0, 3 ) . '… (' . mb_strlen( $v ) . ' car.)';
}

function cox_known_states() {
	return array( 'recue', 'confirmee', 'expediee', 'livree', 'annulee' );
}

/* ------------------------------------------------------------------ */
/* Journal par commande                                                */
/* ------------------------------------------------------------------ */

function cox_log( $order_id, $msg ) {
	$log = get_post_meta( $order_id, '_cox_log', true );
	$log = is_array( $log ) ? $log : array();
	$log[] = array( 't' => time(), 'm' => $msg );
	if ( count( $log ) > 30 ) {
		$log = array_slice( $log, -30 );
	}
	update_post_meta( $order_id, '_cox_log', $log );
}

/* ------------------------------------------------------------------ */
/* Lecture d'une commande du thème                                     */
/* ------------------------------------------------------------------ */

/**
 * Le titre des commandes suit le format « CP-280930-9338 · Nom · Ville · 404 DH ».
 */
function cox_parse_title( $title ) {
	$parts = array_map( 'trim', explode( '·', wp_strip_all_tags( html_entity_decode( $title ) ) ) );
	$out   = array( 'ref' => '', 'name' => '', 'city' => '', 'total' => 0 );
	if ( isset( $parts[0] ) ) {
		$out['ref'] = $parts[0];
	}
	if ( isset( $parts[1] ) ) {
		$out['name'] = $parts[1];
	}
	if ( isset( $parts[2] ) ) {
		$out['city'] = $parts[2];
	}
	foreach ( $parts as $p ) {
		if ( preg_match( '/([\d\s.,]+)\s*(DH|MAD|dhs?)\b/i', $p, $m ) ) {
			$out['total'] = (float) str_replace( array( ' ', ',' ), array( '', '.' ), $m[1] );
		}
	}
	return $out;
}

function cox_meta_flat( $order_id ) {
	$all  = get_post_meta( $order_id );
	$flat = array();
	foreach ( (array) $all as $k => $vals ) {
		$flat[ $k ] = maybe_unserialize( is_array( $vals ) ? reset( $vals ) : $vals );
	}
	return $flat;
}

/** Trouve la clé méta qui contient l'étape de suivi (recue/confirmee/…). */
function cox_detect_etat_key( $order_id = 0 ) {
	$s = cox_settings();
	if ( $s['etat_key'] ) {
		return $s['etat_key'];
	}
	$ids = $order_id ? array( $order_id ) : get_posts( array( 'post_type' => COX_PT, 'numberposts' => 20, 'fields' => 'ids', 'post_status' => 'any' ) );
	foreach ( $ids as $id ) {
		foreach ( cox_meta_flat( $id ) as $k => $v ) {
			if ( 0 === strpos( $k, '_cox_' ) ) {
				continue;
			}
			if ( is_string( $v ) && in_array( strtolower( $v ), cox_known_states(), true ) ) {
				cox_update_setting( 'etat_key', $k );
				return $k;
			}
		}
	}
	return '';
}

function cox_detect_phone( $order_id, $flat ) {
	$s = cox_settings();
	if ( $s['phone_key'] && ! empty( $flat[ $s['phone_key'] ] ) ) {
		return (string) $flat[ $s['phone_key'] ];
	}
	foreach ( $flat as $k => $v ) {
		if ( 0 === strpos( $k, '_cox_' ) ) {
			continue;
		}
		if ( cox_looks_like_phone( $v ) ) {
			if ( ! $s['phone_key'] ) {
				cox_update_setting( 'phone_key', $k );
			}
			return (string) $v;
		}
	}
	return '';
}

function cox_detect_address( $flat ) {
	$s = cox_settings();
	if ( $s['address_key'] && ! empty( $flat[ $s['address_key'] ] ) ) {
		return (string) $flat[ $s['address_key'] ];
	}
	foreach ( $flat as $k => $v ) {
		if ( is_string( $v ) && preg_match( '/adres|address|quartier/i', $k ) && strlen( trim( $v ) ) > 3 ) {
			return $v;
		}
	}
	return '';
}

/** Toutes les infos utiles d'une commande pour OzonExpress. */
function cox_order( $order_id ) {
	$post = get_post( $order_id );
	if ( ! $post || COX_PT !== $post->post_type ) {
		return null;
	}
	$t     = cox_parse_title( $post->post_title );
	$flat  = cox_meta_flat( $order_id );
	$items = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $post->post_content ) ) );
	// Champs du thème (cp_*) en priorité, le titre « REF · Nom · Ville · Montant » en secours.
	$get = function ( $k, $fallback ) use ( $flat ) {
		return ( isset( $flat[ $k ] ) && is_scalar( $flat[ $k ] ) && '' !== trim( (string) $flat[ $k ] ) ) ? trim( (string) $flat[ $k ] ) : $fallback;
	};
	$total = (float) str_replace( array( ' ', ',' ), array( '', '.' ), $get( 'cp_total', $t['total'] ) );
	return array(
		'id'      => $order_id,
		'ref'     => $get( 'cp_ref', $t['ref'] ? $t['ref'] : ( 'CP-' . $order_id ) ),
		'name'    => $get( 'cp_nom', $t['name'] ),
		'city'    => $get( 'cp_ville', $t['city'] ),
		'total'   => $total,
		'phone'   => $get( 'cp_tel', cox_detect_phone( $order_id, $flat ) ),
		'address' => $get( 'cp_adresse', cox_detect_address( $flat ) ),
		'items'   => preg_replace( '/\s*\([a-z0-9-]+\)/', '', $items ),
	);
}

/* ------------------------------------------------------------------ */
/* Étape de suivi du thème (Comptoir Boutique)                         */
/* ------------------------------------------------------------------ */

function cox_get_etat( $order_id ) {
	$k = cox_detect_etat_key( $order_id );
	return $k ? (string) get_post_meta( $order_id, $k, true ) : '';
}

/** Fait avancer l'étape (jamais de retour en arrière, sauf annulation). */
function cox_set_etat( $order_id, $etat ) {
	$k = cox_detect_etat_key( $order_id );
	if ( ! $k ) {
		cox_log( $order_id, 'Étape non mise à jour : champ d\'étape introuvable (voir Diagnostic).' );
		return false;
	}
	$order = array( 'recue' => 0, 'confirmee' => 1, 'expediee' => 2, 'livree' => 3 );
	$cur   = (string) get_post_meta( $order_id, $k, true );
	if ( 'annulee' === $cur || 'livree' === $cur ) {
		return false;
	}
	if ( 'annulee' !== $etat && isset( $order[ $cur ], $order[ $etat ] ) && $order[ $etat ] <= $order[ $cur ] ) {
		return false;
	}
	$GLOBALS['cox_internal_write'] = true;
	update_post_meta( $order_id, $k, $etat );
	// Date de l'étape, affichée dans l'encadré « Suivi de la commande » (tableau étape => horodatage).
	$dates = get_post_meta( $order_id, $k . '_dates', true );
	if ( '' === $dates || ( is_array( $dates ) && ( ! $dates || array_intersect( array_keys( $dates ), cox_known_states() ) ) ) ) {
		$dates          = is_array( $dates ) ? $dates : array();
		$dates[ $etat ] = time();
		update_post_meta( $order_id, $k . '_dates', $dates );
	}
	$GLOBALS['cox_internal_write'] = false;
	cox_log( $order_id, 'Étape du suivi → ' . $etat );
	do_action( 'cox_etat_changed', $order_id, $etat, $cur );
	return true;
}

/** Recopie le n° OzonExpress dans le champ « N° de suivi du livreur » du thème, si on le connaît. */
function cox_set_colis_field( $order_id, $tracking ) {
	$s = cox_settings();
	if ( ! $s['colis_key'] ) {
		return;
	}
	$GLOBALS['cox_internal_write'] = true;
	update_post_meta( $order_id, $s['colis_key'], $tracking );
	$GLOBALS['cox_internal_write'] = false;
}

/* ------------------------------------------------------------------ */
/* Déclencheur : la commande vient d'être confirmée                    */
/* ------------------------------------------------------------------ */

add_action( 'added_post_meta', 'cox_on_meta', 10, 4 );
add_action( 'updated_post_meta', 'cox_on_meta', 10, 4 );

function cox_on_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
	if ( 0 === strpos( $meta_key, '_cox_' ) || '_edit_lock' === $meta_key || '_edit_last' === $meta_key ) {
		return;
	}
	if ( COX_PT !== get_post_type( $object_id ) ) {
		return;
	}
	cox_meta_journal( $object_id, $meta_key, $meta_value );

	if ( ! empty( $GLOBALS['cox_internal_write'] ) ) {
		return;
	}
	$s = cox_settings();
	if ( ! $s['auto_create'] || get_post_meta( $object_id, '_cox_tracking', true ) ) {
		return;
	}
	if ( ! is_scalar( $meta_value ) ) {
		return;
	}
	$val    = cox_norm( $meta_value );
	$wanted = array_filter( array_map( 'cox_norm', explode( ',', $s['trigger_values'] ) ) );
	$match  = false;
	if ( $s['trigger_key'] ) {
		$match = ( $meta_key === $s['trigger_key'] && in_array( $val, $wanted, true ) );
	} else {
		// Sans réglage : l'étape du suivi passe à « confirmee », ou un champ WhatsApp passe à « confirmé ».
		$etat_key = cox_detect_etat_key( $object_id );
		if ( $etat_key && $meta_key === $etat_key && 'confirmee' === $val ) {
			$match = true;
		} elseif ( preg_match( '/wa|whatsapp|etat|statut/i', $meta_key ) && in_array( $val, $wanted, true ) ) {
			// Réponse « je confirme » sur WhatsApp, ou commande confirmée par téléphone (champ cp_etat).
			$match = true;
		}
	}
	if ( $match && ! wp_next_scheduled( 'cox_create_parcel', array( (int) $object_id ) ) ) {
		cox_log( $object_id, 'Commande confirmée (' . $meta_key . ') : colis programmé chez OzonExpress.' );
		wp_schedule_single_event( time() + 20, 'cox_create_parcel', array( (int) $object_id ) );
	}
}

/** Journal des changements de champs sur les commandes, pour l'écran Diagnostic. */
function cox_meta_journal( $order_id, $key, $value ) {
	$j   = get_option( 'cox_meta_journal', array() );
	$j   = is_array( $j ) ? $j : array();
	$j[] = array(
		't'   => time(),
		'id'  => (int) $order_id,
		'key' => $key,
		'val' => cox_mask( $value ),
		'who' => is_admin() ? 'admin' : ( wp_doing_cron() ? 'cron' : ( defined( 'REST_REQUEST' ) && REST_REQUEST ? 'rest/webhook' : 'site' ) ),
	);
	if ( count( $j ) > 80 ) {
		$j = array_slice( $j, -80 );
	}
	update_option( 'cox_meta_journal', $j, false );
}
