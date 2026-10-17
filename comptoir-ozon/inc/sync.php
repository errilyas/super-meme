<?php
/**
 * Synchronisation des statuts OzonExpress (toutes les heures + à la demande).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'cox_sync', 'cox_sync_all' );

function cox_sync_all() {
	$ids = get_posts(
		array(
			'post_type'   => COX_PT,
			'post_status' => 'any',
			'numberposts' => 60,
			'fields'      => 'ids',
			'orderby'     => 'date',
			'order'       => 'DESC',
			'meta_query'  => array(
				array( 'key' => '_cox_tracking', 'compare' => 'EXISTS' ),
				array( 'key' => '_cox_final', 'compare' => 'NOT EXISTS' ),
			),
		)
	);
	foreach ( $ids as $id ) {
		cox_sync_order( $id );
	}
	update_option( 'cox_last_sync', time(), false );

	// Colis restés sans création (erreur réseau…) : on réessaie une fois par heure pendant 24 h.
	$retry = get_posts(
		array(
			'post_type'   => COX_PT,
			'post_status' => 'publish',
			'numberposts' => 20,
			'fields'      => 'ids',
			'date_query'  => array( array( 'after' => '1 day ago' ) ),
			'meta_query'  => array(
				array( 'key' => '_cox_error', 'compare' => 'EXISTS' ),
				array( 'key' => '_cox_tracking', 'compare' => 'NOT EXISTS' ),
			),
		)
	);
	foreach ( $retry as $id ) {
		$err = (string) get_post_meta( $id, '_cox_error', true );
		// On ne réessaie que les erreurs techniques, pas les données manquantes (ville, téléphone).
		if ( false === strpos( $err, 'Colis non créé' ) ) {
			cox_create_parcel( $id );
		}
	}
}

/** Met à jour une commande depuis OzonExpress. Retourne true si quelque chose a changé. */
function cox_sync_order( $order_id ) {
	$tracking = get_post_meta( $order_id, '_cox_tracking', true );
	if ( ! $tracking ) {
		return false;
	}
	$t = cox_fetch_tracking( $tracking );
	if ( is_wp_error( $t ) ) {
		cox_log( $order_id, 'Suivi indisponible : ' . $t->get_error_message() );
		return false;
	}
	update_post_meta( $order_id, '_cox_checked', time() );
	$changed = false;

	if ( $t['history'] ) {
		update_post_meta( $order_id, '_cox_history', $t['history'] );
	}

	$old_status = (string) get_post_meta( $order_id, '_cox_status', true );
	if ( $t['status'] && $t['status'] !== $old_status ) {
		update_post_meta( $order_id, '_cox_status', $t['status'] );
		update_post_meta( $order_id, '_cox_status_at', $t['time'] ? $t['time'] : current_time( 'mysql' ) );
		cox_log( $order_id, 'OzonExpress : ' . $t['status'] );
		$changed = true;

		list( $etat, $final, $label ) = cox_map_status( $t['status'] );
		update_post_meta( $order_id, '_cox_label', $label );
		if ( $etat ) {
			cox_set_etat( $order_id, $etat );
		}
		if ( $final ) {
			update_post_meta( $order_id, '_cox_final', 1 );
		}
		do_action( 'cox_status_changed', $order_id, $t['status'], $old_status, $etat );
	}

	$old_tel = (string) get_post_meta( $order_id, '_cox_livreur_tel', true );
	if ( $t['livreur_tel'] && cox_phone_local( $t['livreur_tel'] ) !== cox_phone_local( $old_tel ) ) {
		update_post_meta( $order_id, '_cox_livreur', $t['livreur'] );
		update_post_meta( $order_id, '_cox_livreur_tel', $t['livreur_tel'] );
		cox_log( $order_id, 'Livreur affecté : ' . ( $t['livreur'] ? $t['livreur'] . ' — ' : '' ) . cox_phone_local( $t['livreur_tel'] ) );
		$changed = true;
		do_action( 'cox_livreur_assigned', $order_id, $t['livreur'], $t['livreur_tel'] );
	}
	return $changed;
}
