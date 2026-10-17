<?php
/**
 * Administration : réglages, diagnostic, encadré dans la commande, colonne et actions groupées.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Page Réglages › OzonExpress                                         */
/* ------------------------------------------------------------------ */

add_action(
	'admin_menu',
	function () {
		add_options_page( 'OzonExpress', 'OzonExpress', 'manage_options', 'comptoir-ozon', 'cox_settings_page' );
	}
);

add_action( 'admin_post_cox_save', 'cox_save_settings' );

function cox_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Accès refusé.' );
	}
	check_admin_referer( 'cox_save' );
	$in  = wp_unslash( $_POST );
	$cur = cox_settings();
	$new = $cur;
	$txt = array( 'customer_id', 'trigger_key', 'trigger_values', 'etat_key', 'colis_key', 'phone_key', 'address_key', 'nature', 'wa_tpl_expedie', 'wa_tpl_livreur', 'wa_lang', 'wa_phone_id', 'pixel_id', 'shop_whatsapp' );
	foreach ( $txt as $k ) {
		if ( isset( $in[ $k ] ) ) {
			$new[ $k ] = sanitize_text_field( $in[ $k ] );
		}
	}
	// Secrets : on ne remplace que si un nouveau est saisi.
	foreach ( array( 'api_key', 'wa_token', 'capi_token' ) as $k ) {
		if ( ! empty( $in[ $k ] ) ) {
			$new[ $k ] = sanitize_text_field( $in[ $k ] );
		}
	}
	foreach ( array( 'auto_create', 'parcel_open', 'parcel_fragile', 'wa_enable', 'capi_enable' ) as $k ) {
		$new[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
	}
	if ( isset( $in['city_overrides'] ) ) {
		$new['city_overrides'] = sanitize_textarea_field( $in['city_overrides'] );
	}
	update_option( 'cox_settings', $new, false );
	wp_safe_redirect( add_query_arg( array( 'page' => 'comptoir-ozon', 'tab' => isset( $in['tab'] ) ? sanitize_key( $in['tab'] ) : 'reglages', 'ok' => 1 ), admin_url( 'options-general.php' ) ) );
	exit;
}

add_action( 'admin_post_cox_test', 'cox_test_connection' );

function cox_test_connection() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Accès refusé.' );
	}
	check_admin_referer( 'cox_test' );
	delete_transient( 'cox_cities' );
	$cities = cox_cities();
	$json   = cox_api( 'tracking', array( 'tracking-number' => 'TEST-CONNEXION' ) );
	if ( is_wp_error( $json ) ) {
		$msg = 'Erreur : ' . $json->get_error_message();
	} else {
		$err = cox_api_error( $json );
		if ( $err && preg_match( '/api ?key|cl[ée]|customer|client/i', $err ) ) {
			$msg = '❌ OzonExpress refuse les identifiants : « ' . $err . ' ». Vérifiez l\'ID client et la clé API (Mon compte › Afficher la clé API).';
		} else {
			// Un colis « TEST-CONNEXION » n'existe pas : une réponse propre (même « introuvable ») prouve que la clé est acceptée.
			$msg = '✅ Connexion OzonExpress OK. ' . ( $err ? 'Réponse au colis fictif : « ' . $err . ' » (normal).' : '' );
		}
	}
	$msg .= ' Villes chargées : ' . count( $cities ) . '.';
	set_transient( 'cox_test_msg', $msg, 120 );
	wp_safe_redirect( admin_url( 'options-general.php?page=comptoir-ozon&tab=reglages' ) );
	exit;
}

function cox_field( $label, $html, $help = '' ) {
	echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . $html . ( $help ? '<p class="description">' . wp_kses_post( $help ) . '</p>' : '' ) . '</td></tr>';
}

function cox_settings_page() {
	$s   = cox_settings();
	$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'reglages';
	echo '<div class="wrap"><h1>OzonExpress</h1>';
	echo '<p>Les commandes confirmées partent chez OzonExpress, le n° de suivi est enregistré dans la commande et le statut (et le livreur) est mis à jour toutes les heures.</p>';
	if ( ! empty( $_GET['ok'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Réglages enregistrés.</p></div>';
	}
	$t = get_transient( 'cox_test_msg' );
	if ( $t ) {
		delete_transient( 'cox_test_msg' );
		echo '<div class="notice notice-info"><p>' . esc_html( $t ) . '</p></div>';
	}
	echo '<h2 class="nav-tab-wrapper">';
	foreach ( array( 'reglages' => 'Réglages', 'villes' => 'Villes', 'diagnostic' => 'Diagnostic' ) as $k => $l ) {
		echo '<a class="nav-tab' . ( $tab === $k ? ' nav-tab-active' : '' ) . '" href="' . esc_url( admin_url( 'options-general.php?page=comptoir-ozon&tab=' . $k ) ) . '">' . esc_html( $l ) . '</a>';
	}
	echo '</h2>';

	if ( 'diagnostic' === $tab ) {
		cox_diagnostic_tab( $s );
		echo '</div>';
		return;
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'cox_save' );
	echo '<input type="hidden" name="action" value="cox_save"><input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';
	echo '<table class="form-table" role="presentation">';

	if ( 'villes' === $tab ) {
		$cities = cox_cities();
		cox_field( 'Correspondances', '<textarea name="city_overrides" rows="8" cols="60" class="large-text code" placeholder="Casa = 1&#10;Hay Hassani = 1">' . esc_textarea( $s['city_overrides'] ) . '</textarea>', 'Une ligne par ville du site qui ne correspond pas exactement à OzonExpress : <code>Ville saisie par le client = ID OzonExpress</code>. Les villes sont reconnues automatiquement (majuscules, accents et petites fautes ignorés).' );
		echo '</table>';
		submit_button( 'Enregistrer' );
		echo '</form>';
		// Villes des 40 dernières commandes et leur correspondance.
		$ids  = get_posts( array( 'post_type' => COX_PT, 'post_status' => 'any', 'numberposts' => 40, 'fields' => 'ids' ) );
		$seen = array();
		echo '<h2>Villes de vos dernières commandes</h2><table class="widefat striped" style="max-width:700px"><thead><tr><th>Ville saisie</th><th>Ville OzonExpress</th><th>Tarif livraison</th></tr></thead><tbody>';
		foreach ( $ids as $id ) {
			$c = cox_parse_title( get_the_title( $id ) )['city'];
			if ( ! $c || isset( $seen[ cox_norm( $c ) ] ) ) {
				continue;
			}
			$seen[ cox_norm( $c ) ] = 1;
			$cid                     = cox_city_id( $c );
			echo '<tr><td>' . esc_html( $c ) . '</td><td>' . ( $cid && isset( $cities[ $cid ] ) ? esc_html( $cities[ $cid ]['name'] . ' (ID ' . $cid . ')' ) : '<strong style="color:#b32d2e">À renseigner</strong>' ) . '</td><td>' . ( $cid && isset( $cities[ $cid ] ) ? esc_html( $cities[ $cid ]['price'] . ' DH' ) : '—' ) . '</td></tr>';
		}
		echo '</tbody></table>';
		echo '<details style="margin-top:16px"><summary>Toutes les villes OzonExpress (' . count( $cities ) . ')</summary><p style="columns:4">';
		foreach ( $cities as $id => $c ) {
			echo esc_html( $c['name'] . ' — ' . $id ) . '<br>';
		}
		echo '</p></details></div>';
		return;
	}

	$pw = function ( $name, $set ) {
		return '<input type="password" name="' . esc_attr( $name ) . '" class="regular-text" autocomplete="new-password" placeholder="' . ( $set ? '•••••••• (enregistré — laisser vide pour garder)' : '' ) . '">';
	};
	echo '<tr><th colspan="2"><h2>Compte OzonExpress</h2></th></tr>';
	cox_field( 'ID client', '<input type="text" name="customer_id" class="regular-text" value="' . esc_attr( $s['customer_id'] ) . '">', 'Espace client OzonExpress › API (ou Paramètres) : « Customer ID ».' );
	cox_field( 'Clé API', $pw( 'api_key', (bool) $s['api_key'] ), 'Même page : « API Key ».' );
	cox_field( 'Création automatique', '<label><input type="checkbox" name="auto_create" value="1" ' . checked( $s['auto_create'], 1, false ) . '> Créer le colis dès que la commande est confirmée (WhatsApp ou étape « Confirmée »)</label>' );
	cox_field( 'Colis', '<label><input type="checkbox" name="parcel_open" value="1" ' . checked( $s['parcel_open'], 1, false ) . '> Le client peut ouvrir le colis avant de payer</label><br><label><input type="checkbox" name="parcel_fragile" value="1" ' . checked( $s['parcel_fragile'], 1, false ) . '> Fragile (flacons en verre)</label>' );
	cox_field( 'Nature par défaut', '<input type="text" name="nature" class="regular-text" value="' . esc_attr( $s['nature'] ) . '">' );

	echo '<tr><th colspan="2"><h2>WhatsApp de suivi</h2></th></tr>';
	list( $pid, $ptok ) = cox_wa_creds();
	cox_field( 'Activer', '<label><input type="checkbox" name="wa_enable" value="1" ' . checked( $s['wa_enable'], 1, false ) . '> Prévenir le client quand le colis part et quand le livreur est affecté</label>', $pid && $ptok ? 'Identifiants WhatsApp repris de « Confirmation WhatsApp » ✔' : '<strong>Identifiants WhatsApp introuvables</strong> : renseignez-les ci-dessous.' );
	cox_field( 'Modèle « colis expédié »', '<input type="text" name="wa_tpl_expedie" class="regular-text" value="' . esc_attr( $s['wa_tpl_expedie'] ) . '">', 'Variables : {{1}} prénom, {{2}} n° de commande ; bouton lien : https://comptoirparfums.com/?colis={{1}}' );
	cox_field( 'Modèle « livreur affecté »', '<input type="text" name="wa_tpl_livreur" class="regular-text" value="' . esc_attr( $s['wa_tpl_livreur'] ) . '">', 'Variables : {{1}} prénom, {{2}} n° de commande, {{3}} nom du livreur, {{4}} téléphone du livreur ; même bouton lien.' );
	cox_field( 'Langue des modèles', '<input type="text" name="wa_lang" class="small-text" value="' . esc_attr( $s['wa_lang'] ) . '">' );
	cox_field( 'Phone number ID (facultatif)', '<input type="text" name="wa_phone_id" class="regular-text" value="' . esc_attr( $s['wa_phone_id'] ) . '">', 'Seulement si les identifiants ne sont pas repris automatiquement.' );
	cox_field( 'Jeton WhatsApp (facultatif)', $pw( 'wa_token', (bool) $s['wa_token'] ) );
	cox_field( 'WhatsApp de la boutique', '<input type="text" name="shop_whatsapp" class="regular-text" value="' . esc_attr( $s['shop_whatsapp'] ) . '">', 'Affiché sur la page de suivi du client.' );

	echo '<tr><th colspan="2"><h2>Achat Meta à la livraison</h2></th></tr>';
	cox_field( 'Activer', '<label><input type="checkbox" name="capi_enable" value="1" ' . checked( $s['capi_enable'], 1, false ) . '> Envoyer l\'événement Purchase à Meta quand le colis est livré</label>', 'À activer seulement si le thème n\'envoie pas déjà l\'achat à la livraison (sinon il serait compté deux fois).' );
	cox_field( 'ID du pixel / jeu de données', '<input type="text" name="pixel_id" class="regular-text" value="' . esc_attr( $s['pixel_id'] ) . '">' );
	cox_field( 'Jeton API Conversions', $pw( 'capi_token', (bool) $s['capi_token'] ) );

	echo '<tr><th colspan="2"><h2>Champs des commandes</h2><p class="description" style="font-weight:normal">Détectés automatiquement ; à corriger seulement si l\'onglet Diagnostic le demande.</p></th></tr>';
	cox_field( 'Champ « étape du suivi »', '<input type="text" name="etat_key" class="regular-text code" value="' . esc_attr( $s['etat_key'] ) . '">' );
	cox_field( 'Champ « N° de suivi du livreur »', '<input type="text" name="colis_key" class="regular-text code" value="' . esc_attr( $s['colis_key'] ) . '">' );
	cox_field( 'Champ téléphone', '<input type="text" name="phone_key" class="regular-text code" value="' . esc_attr( $s['phone_key'] ) . '">' );
	cox_field( 'Champ adresse', '<input type="text" name="address_key" class="regular-text code" value="' . esc_attr( $s['address_key'] ) . '">' );
	cox_field( 'Déclencheur (champ)', '<input type="text" name="trigger_key" class="regular-text code" value="' . esc_attr( $s['trigger_key'] ) . '">', 'Vide = automatique (étape « Confirmée » ou réponse WhatsApp « confirmé »).' );
	cox_field( 'Déclencheur (valeurs)', '<input type="text" name="trigger_values" class="large-text code" value="' . esc_attr( $s['trigger_values'] ) . '">' );
	echo '</table>';
	submit_button( 'Enregistrer' );
	echo '</form>';

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'cox_test' );
	echo '<input type="hidden" name="action" value="cox_test">';
	submit_button( 'Tester la connexion OzonExpress', 'secondary', 'submit', false );
	$last = (int) get_option( 'cox_last_sync' );
	echo ' <span class="description">Dernière synchronisation : ' . ( $last ? esc_html( human_time_diff( $last ) ) . ' ' : 'jamais' ) . ( $last ? '' : '' ) . '</span>';
	echo '</form></div>';
}

function cox_diagnostic_tab( $s ) {
	// Outil : interroger OzonExpress sur un n° de suivi existant pour voir la réponse brute (téléphones masqués).
	echo '<h2>Tester un n° de suivi</h2><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'cox_probe' );
	echo '<input type="hidden" name="action" value="cox_probe"><input type="text" name="tracking" class="regular-text" placeholder="ex. CP-260930-9338"> ';
	submit_button( 'Interroger OzonExpress', 'secondary', 'submit', false );
	echo '<p class="description">Affiche ce que renvoie l\'API (suivi + infos colis) pour vérifier le statut et les infos du livreur. Rien n\'est modifié.</p></form>';
	$probe = get_transient( 'cox_probe_' . get_current_user_id() );
	if ( $probe ) {
		$t = cox_fetch_tracking( $probe['tracking'] );
		echo '<h3>Résultat pour <code>' . esc_html( $probe['tracking'] ) . '</code></h3>';
		if ( ! is_wp_error( $t ) ) {
			echo '<p><strong>Lu par l\'extension :</strong> statut « ' . esc_html( $t['status'] ) . ' » → étape <code>' . esc_html( (string) cox_map_status( $t['status'] )[0] ) . '</code> · livreur : ' . esc_html( $t['livreur'] ? $t['livreur'] : '—' ) . ' · tél. livreur : ' . esc_html( $t['livreur_tel'] ? cox_mask( $t['livreur_tel'] ) : 'non trouvé' ) . '</p>';
		}
		echo '<pre style="max-width:900px;max-height:420px;overflow:auto;background:#fff;border:1px solid #ccd0d4;padding:10px;font-size:12px">' . esc_html( $probe['raw'] ) . '</pre>';
	}

	$etat = cox_detect_etat_key();
	echo '<h2>Champs détectés</h2><table class="widefat striped" style="max-width:700px"><tbody>';
	$rows = array(
		'Étape du suivi'          => $etat,
		'N° de suivi du livreur'  => $s['colis_key'],
		'Téléphone'               => $s['phone_key'],
		'Adresse'                 => $s['address_key'],
		'Déclencheur'             => $s['trigger_key'] ? $s['trigger_key'] . ' = ' . $s['trigger_values'] : 'automatique',
	);
	foreach ( $rows as $l => $v ) {
		echo '<tr><td>' . esc_html( $l ) . '</td><td><code>' . esc_html( $v ? $v : '—' ) . '</code></td></tr>';
	}
	echo '</tbody></table>';

	$last = get_posts( array( 'post_type' => COX_PT, 'post_status' => 'any', 'numberposts' => 1 ) );
	if ( $last ) {
		$o = cox_order( $last[0]->ID );
		echo '<h2>Dernière commande telle que lue par l\'extension</h2><table class="widefat striped" style="max-width:700px"><tbody>';
		foreach ( array( 'ref' => 'Référence', 'name' => 'Nom', 'city' => 'Ville', 'total' => 'Montant', 'phone' => 'Téléphone', 'address' => 'Adresse', 'items' => 'Articles' ) as $k => $l ) {
			$v = 'phone' === $k ? cox_mask( $o[ $k ] ) : $o[ $k ];
			echo '<tr><td>' . esc_html( $l ) . '</td><td>' . esc_html( (string) $v ) . '</td></tr>';
		}
		$cid = cox_city_id( $o['city'] );
		echo '<tr><td>Ville OzonExpress</td><td>' . ( $cid ? esc_html( 'ID ' . $cid ) : '<strong style="color:#b32d2e">non reconnue</strong>' ) . '</td></tr>';
		echo '</tbody></table>';
		echo '<h3>Champs enregistrés sur cette commande (valeurs masquées)</h3><table class="widefat striped" style="max-width:700px"><tbody>';
		foreach ( cox_meta_flat( $last[0]->ID ) as $k => $v ) {
			echo '<tr><td><code>' . esc_html( $k ) . '</code></td><td>' . esc_html( cox_mask( $v ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	// Tous les champs utilisés sur l'ensemble des commandes (y compris corbeille), avec les valeurs courtes rencontrées.
	global $wpdb;
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT pm.meta_key, pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = %s AND pm.meta_key NOT LIKE %s LIMIT 5000",
			COX_PT,
			$wpdb->esc_like( '_cox_' ) . '%'
		)
	);
	$keys = array();
	foreach ( (array) $rows as $r ) {
		$k = $r->meta_key;
		if ( ! isset( $keys[ $k ] ) ) {
			$keys[ $k ] = array( 'n' => 0, 'vals' => array() );
		}
		$keys[ $k ]['n']++;
		$v = (string) $r->meta_value;
		if ( strlen( $v ) <= 14 && ! cox_looks_like_phone( $v ) && ! preg_match( '/\d{5,}/', $v ) && count( $keys[ $k ]['vals'] ) < 8 ) {
			$keys[ $k ]['vals'][ $v ] = 1;
		}
	}
	ksort( $keys );
	echo '<h2>Tous les champs des commandes</h2><table class="widefat striped" style="max-width:900px"><thead><tr><th>Champ</th><th>Commandes</th><th>Valeurs courtes rencontrées</th></tr></thead><tbody>';
	foreach ( $keys as $k => $info ) {
		echo '<tr><td><code>' . esc_html( $k ) . '</code></td><td>' . (int) $info['n'] . '</td><td>' . esc_html( implode( ' · ', array_keys( $info['vals'] ) ) ) . '</td></tr>';
	}
	echo '</tbody></table>';

	$j = array_reverse( (array) get_option( 'cox_meta_journal', array() ) );
	echo '<h2>Derniers changements sur les commandes</h2><p class="description">Sert à repérer quel champ change quand un client confirme sur WhatsApp ou quand vous changez l\'étape.</p>';
	echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th>Quand</th><th>Commande</th><th>Champ</th><th>Valeur</th><th>Origine</th></tr></thead><tbody>';
	foreach ( array_slice( $j, 0, 40 ) as $r ) {
		echo '<tr><td>' . esc_html( human_time_diff( $r['t'] ) ) . '</td><td>#' . (int) $r['id'] . '</td><td><code>' . esc_html( $r['key'] ) . '</code></td><td>' . esc_html( $r['val'] ) . '</td><td>' . esc_html( $r['who'] ) . '</td></tr>';
	}
	echo '</tbody></table>';
}

/* ------------------------------------------------------------------ */
/* Encadré « OzonExpress » dans la commande                            */
/* ------------------------------------------------------------------ */

add_action(
	'add_meta_boxes_' . COX_PT,
	function () {
		add_meta_box( 'cox-box', 'OzonExpress', 'cox_metabox', COX_PT, 'side', 'high' );
	}
);

function cox_action_url( $action, $order_id ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=' . $action . '&commande=' . (int) $order_id ), $action . '_' . (int) $order_id );
}

function cox_metabox( $post ) {
	$tr  = get_post_meta( $post->ID, '_cox_tracking', true );
	$err = get_post_meta( $post->ID, '_cox_error', true );
	if ( ! $tr ) {
		if ( $err ) {
			echo '<p style="color:#b32d2e">' . esc_html( $err ) . '</p>';
		}
		$o   = cox_order( $post->ID );
		$cid = $o ? cox_city_id( $o['city'] ) : 0;
		echo '<p>Pas encore de colis.' . ( $cid ? '' : ' <strong>Ville non reconnue</strong> (Réglages › OzonExpress › Villes).' ) . '</p>';
		echo '<p><a class="button button-primary" href="' . esc_url( cox_action_url( 'cox_create', $post->ID ) ) . '">Envoyer / relier à OzonExpress</a></p><p class="description">Si le colis existe déjà chez OzonExpress avec le n° de la commande, il est relié au lieu d\'être recréé.</p>';
	} else {
		$status  = get_post_meta( $post->ID, '_cox_status', true );
		$liv     = get_post_meta( $post->ID, '_cox_livreur', true );
		$livtel  = get_post_meta( $post->ID, '_cox_livreur_tel', true );
		$checked = (int) get_post_meta( $post->ID, '_cox_checked', true );
		echo '<p><strong>N° de suivi :</strong> <code>' . esc_html( $tr ) . '</code></p>';
		echo '<p><strong>Statut :</strong> ' . esc_html( $status ? $status : '—' ) . ( $checked ? '<br><span class="description">vérifié il y a ' . esc_html( human_time_diff( $checked ) ) . '</span>' : '' ) . '</p>';
		if ( $livtel ) {
			echo '<p><strong>Livreur :</strong> ' . esc_html( $liv ) . ' <a href="tel:' . esc_attr( cox_phone_local( $livtel ) ) . '">' . esc_html( cox_phone_local( $livtel ) ) . '</a></p>';
		}
		$url = cox_tracking_url( $post->ID );
		if ( $url ) {
			echo '<p><strong>Page de suivi client :</strong><br><input type="text" readonly class="widefat" value="' . esc_attr( $url ) . '" onclick="this.select()"></p>';
		}
		echo '<p><a class="button" href="' . esc_url( cox_action_url( 'cox_refresh', $post->ID ) ) . '">Actualiser le suivi</a></p>';
		$h = get_post_meta( $post->ID, '_cox_history', true );
		if ( is_array( $h ) && $h ) {
			echo '<details><summary>Historique OzonExpress</summary><ul>';
			foreach ( array_reverse( $h ) as $row ) {
				echo '<li><strong>' . esc_html( $row['statut'] ) . '</strong> — ' . esc_html( $row['date'] ) . ( $row['note'] ? '<br><em>' . esc_html( $row['note'] ) . '</em>' : '' ) . '</li>';
			}
			echo '</ul></details>';
		}
	}
	$log = get_post_meta( $post->ID, '_cox_log', true );
	if ( is_array( $log ) && $log ) {
		echo '<details><summary>Journal</summary><ul style="font-size:12px">';
		foreach ( array_reverse( $log ) as $l ) {
			echo '<li>' . esc_html( wp_date( 'd/m H:i', $l['t'] ) . ' — ' . $l['m'] ) . '</li>';
		}
		echo '</ul></details>';
	}
}

add_action( 'admin_post_cox_probe', 'cox_handle_probe' );

function cox_handle_probe() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Accès refusé.' );
	}
	check_admin_referer( 'cox_probe' );
	$tr  = isset( $_POST['tracking'] ) ? sanitize_text_field( wp_unslash( $_POST['tracking'] ) ) : '';
	$out = array();
	foreach ( array( 'tracking', 'parcel-info' ) as $a ) {
		$json      = cox_api( $a, array( 'tracking-number' => $tr ) );
		$out[ $a ] = is_wp_error( $json ) ? 'Erreur : ' . $json->get_error_message() : cox_mask_phones( $json );
	}
	set_transient(
		'cox_probe_' . get_current_user_id(),
		array( 'tracking' => $tr, 'raw' => wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ),
		600
	);
	wp_safe_redirect( admin_url( 'options-general.php?page=comptoir-ozon&tab=diagnostic' ) );
	exit;
}

/** Masque les numéros de téléphone dans une réponse (on garde la structure et les noms de champs). */
function cox_mask_phones( $data ) {
	if ( is_array( $data ) ) {
		return array_map( 'cox_mask_phones', $data );
	}
	if ( is_string( $data ) ) {
		return preg_replace_callback( '/(?:\+?212[\s.-]?|0)[5-7](?:[\s.-]?\d){8}/', function ( $m ) {
			return substr( $m[0], 0, 4 ) . '••••' . substr( $m[0], -2 );
		}, $data );
	}
	return $data;
}

add_action( 'admin_post_cox_create', 'cox_handle_create' );
add_action( 'admin_post_cox_refresh', 'cox_handle_refresh' );

function cox_handle_create() {
	$id = isset( $_GET['commande'] ) ? (int) $_GET['commande'] : 0;
	check_admin_referer( 'cox_create_' . $id );
	if ( ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'Accès refusé.' );
	}
	delete_post_meta( $id, '_cox_error' );
	cox_create_parcel( $id );
	wp_safe_redirect( get_edit_post_link( $id, 'url' ) );
	exit;
}

function cox_handle_refresh() {
	$id = isset( $_GET['commande'] ) ? (int) $_GET['commande'] : 0;
	check_admin_referer( 'cox_refresh_' . $id );
	if ( ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'Accès refusé.' );
	}
	cox_sync_order( $id );
	wp_safe_redirect( get_edit_post_link( $id, 'url' ) );
	exit;
}

/* ------------------------------------------------------------------ */
/* Colonne « OzonExpress » + action groupée                            */
/* ------------------------------------------------------------------ */

add_filter(
	'manage_' . COX_PT . '_posts_columns',
	function ( $cols ) {
		$new = array();
		foreach ( $cols as $k => $v ) {
			$new[ $k ] = $v;
			if ( 'title' === $k ) {
				$new['cox'] = 'OzonExpress';
			}
		}
		if ( ! isset( $new['cox'] ) ) {
			$new['cox'] = 'OzonExpress';
		}
		return $new;
	},
	20
);

add_action(
	'manage_' . COX_PT . '_posts_custom_column',
	function ( $col, $id ) {
		if ( 'cox' !== $col ) {
			return;
		}
		$tr = get_post_meta( $id, '_cox_tracking', true );
		if ( ! $tr ) {
			$err = get_post_meta( $id, '_cox_error', true );
			echo $err ? '<span style="color:#b32d2e" title="' . esc_attr( $err ) . '">⚠ à vérifier</span>' : '—';
			return;
		}
		$st  = get_post_meta( $id, '_cox_status', true );
		$tel = get_post_meta( $id, '_cox_livreur_tel', true );
		echo '<code>' . esc_html( $tr ) . '</code><br>' . esc_html( $st );
		if ( $tel ) {
			echo '<br>🛵 ' . esc_html( cox_phone_local( $tel ) );
		}
	},
	10,
	2
);

add_filter(
	'bulk_actions-edit-' . COX_PT,
	function ( $a ) {
		$a['cox_send']    = 'Envoyer / relier à OzonExpress';
		$a['cox_refresh'] = 'Actualiser le suivi OzonExpress';
		return $a;
	}
);

add_filter(
	'handle_bulk_actions-edit-' . COX_PT,
	function ( $redirect, $action, $ids ) {
		if ( 'cox_send' !== $action && 'cox_refresh' !== $action ) {
			return $redirect;
		}
		$n = 0;
		foreach ( $ids as $id ) {
			if ( 'cox_send' === $action && ! get_post_meta( $id, '_cox_tracking', true ) ) {
				$r = cox_create_parcel( $id );
				$n += is_wp_error( $r ) ? 0 : 1;
			} elseif ( 'cox_refresh' === $action ) {
				cox_sync_order( $id );
				$n++;
			}
		}
		return add_query_arg( 'cox_done', $n, $redirect );
	},
	10,
	3
);

add_action(
	'admin_notices',
	function () {
		if ( isset( $_GET['cox_done'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>OzonExpress : ' . (int) $_GET['cox_done'] . ' commande(s) traitée(s).</p></div>';
		}
		$s = cox_settings();
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'edit-' . COX_PT === $screen->id && ( ! $s['customer_id'] || ! $s['api_key'] ) ) {
			echo '<div class="notice notice-warning"><p>OzonExpress n\'est pas encore branché : <a href="' . esc_url( admin_url( 'options-general.php?page=comptoir-ozon' ) ) . '">ajoutez votre ID client et votre clé API</a>.</p></div>';
		}
	}
);
