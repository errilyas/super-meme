<?php
/**
 * Plugin Name: Comptoir OzonExpress
 * Description: Branche les commandes du Comptoir sur OzonExpress : création du colis dès la confirmation WhatsApp, n° de suivi dans la commande, suivi automatique des statuts et du livreur, page de suivi pour le client, messages WhatsApp de suivi et achat Meta compté à la livraison.
 * Version: 1.0.7
 * Author: Le Comptoir des Parfums
 * Requires PHP: 7.4
 * Text Domain: comptoir-ozon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'COX_VER', '1.0.7' );
define( 'COX_FILE', __FILE__ );
define( 'COX_DIR', plugin_dir_path( __FILE__ ) );
define( 'COX_PT', 'cp_commande' );
define( 'COX_API', 'https://api.ozonexpress.ma' );

require_once COX_DIR . 'inc/core.php';
require_once COX_DIR . 'inc/ozon.php';
require_once COX_DIR . 'inc/sync.php';
require_once COX_DIR . 'inc/notify.php';
require_once COX_DIR . 'inc/admin.php';
require_once COX_DIR . 'inc/public.php';

register_activation_hook( __FILE__, 'cox_activate' );
register_deactivation_hook( __FILE__, 'cox_deactivate' );

function cox_activate() {
	if ( ! wp_next_scheduled( 'cox_sync' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'cox_sync' );
	}
}

function cox_deactivate() {
	wp_clear_scheduled_hook( 'cox_sync' );
}

// Garde-fou : si l'événement planifié a disparu (migration, cache…), on le recrée.
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'cox_sync' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'cox_sync' );
	}
} );
