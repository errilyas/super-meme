<?php
/**
 * Page de suivi pour le client : https://comptoirparfums.com/?colis=JETON
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'query_vars',
	function ( $v ) {
		$v[] = 'colis';
		return $v;
	}
);

add_action( 'template_redirect', 'cox_tracking_page', 1 );

function cox_tracking_page() {
	$token = isset( $_GET['colis'] ) ? sanitize_key( wp_unslash( $_GET['colis'] ) ) : '';
	if ( ! $token ) {
		return;
	}
	$ids = get_posts(
		array(
			'post_type'   => COX_PT,
			'post_status' => 'any',
			'numberposts' => 1,
			'fields'      => 'ids',
			'meta_key'    => '_cox_token',
			'meta_value'  => $token,
		)
	);
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow', true );
	if ( ! $ids ) {
		status_header( 404 );
		cox_render_page( null );
		exit;
	}
	$id = (int) $ids[0];
	// Actualisation à la consultation, au plus toutes les 15 minutes.
	$checked = (int) get_post_meta( $id, '_cox_checked', true );
	if ( ! get_post_meta( $id, '_cox_final', true ) && time() - $checked > 900 ) {
		cox_sync_order( $id );
	}
	cox_render_page( $id );
	exit;
}

function cox_render_page( $id ) {
	$s    = cox_settings();
	$shop = preg_replace( '/\D/', '', $s['shop_whatsapp'] );
	header( 'Content-Type: text/html; charset=utf-8' );
	$o       = $id ? cox_order( $id ) : null;
	$etat    = $id ? cox_get_etat( $id ) : '';
	$status  = $id ? (string) get_post_meta( $id, '_cox_status', true ) : '';
	$label   = $id ? (string) get_post_meta( $id, '_cox_label', true ) : '';
	$tr      = $id ? (string) get_post_meta( $id, '_cox_tracking', true ) : '';
	$liv     = $id ? (string) get_post_meta( $id, '_cox_livreur', true ) : '';
	$livtel  = $id ? cox_phone_local( get_post_meta( $id, '_cox_livreur_tel', true ) ) : '';
	$hist    = $id ? get_post_meta( $id, '_cox_history', true ) : array();
	$hist    = is_array( $hist ) ? array_reverse( $hist ) : array();
	$final   = $id ? (bool) get_post_meta( $id, '_cox_final', true ) : false;
	$en_cours = ( 'En cours de livraison' === $label );

	$steps = array(
		'confirmee' => array( 'تأكدات', 'Confirmée' ),
		'expediee'  => array( 'خرجات', 'Expédiée' ),
		'route'     => array( 'مع الليفرور', 'En livraison' ),
		'livree'    => array( 'توصلات', 'Livrée' ),
	);
	$rank = 0;
	if ( 'confirmee' === $etat || $tr ) {
		$rank = 1;
	}
	if ( 'expediee' === $etat ) {
		$rank = 2;
	}
	if ( $en_cours || $livtel ) {
		$rank = max( $rank, 3 );
	}
	if ( 'livree' === $etat ) {
		$rank = 4;
	}
	?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>تتبع الكوليّة · Le Comptoir des Parfums</title>
<style>
:root{--aub:#1d0e23;--aub2:#2c1535;--gold:#d4b06a;--gold2:#f0d9a2;--cream:#f5ecdc;--dim:#bfa9c2;--ok:#7fd1a1;--bad:#e88a8a}
*{box-sizing:border-box;margin:0;padding:0}
body{background:radial-gradient(ellipse 120% 70% at 50% 0%,#4a2556 0%,#2a1333 45%,#140916 100%) fixed;color:var(--cream);font-family:"Segoe UI",Tahoma,Arial,sans-serif;min-height:100vh;padding:24px 16px 48px}
.wrap{max-width:520px;margin:0 auto}
.brand{text-align:center;letter-spacing:.3em;text-transform:uppercase;color:var(--gold);font:500 13px Georgia,serif;margin-bottom:22px;direction:ltr}
.card{background:rgba(20,9,24,.55);border:1px solid rgba(212,176,106,.45);border-radius:20px;padding:22px 20px;margin-bottom:16px}
h1{font-size:24px;color:var(--gold2);text-align:center;margin-bottom:6px}
.sub{text-align:center;color:var(--dim);font-size:14px;direction:ltr}
.status{text-align:center;font-size:20px;font-weight:700;margin:14px 0 4px}
.status small{display:block;font-weight:400;font-size:13px;color:var(--dim);margin-top:4px;direction:ltr}
.steps{display:flex;justify-content:space-between;position:relative;margin:22px 4px 6px}
.steps:before{content:"";position:absolute;top:13px;left:8%;right:8%;height:2px;background:rgba(212,176,106,.25)}
.st{position:relative;z-index:1;text-align:center;width:25%;font-size:12px;color:var(--dim)}
.st i{display:block;width:28px;height:28px;border-radius:50%;margin:0 auto 6px;border:2px solid rgba(212,176,106,.45);background:var(--aub)}
.st.on{color:var(--cream)}.st.on i{background:var(--gold);border-color:var(--gold)}
.st b{display:block;font-size:11px;font-weight:400;opacity:.7;direction:ltr}
.liv{display:flex;align-items:center;gap:14px}
.liv .ic{font-size:30px}
.liv a.call{margin-inline-start:auto;background:linear-gradient(180deg,var(--gold2),var(--gold));color:var(--aub);text-decoration:none;font-weight:700;padding:10px 16px;border-radius:999px;white-space:nowrap}
.hist li{list-style:none;padding:10px 0;border-bottom:1px solid rgba(212,176,106,.15);font-size:14px}
.hist li:last-child{border:0}
.hist span{display:block;color:var(--dim);font-size:12px;direction:ltr;text-align:right}
.btn{display:block;text-align:center;background:#25d366;color:#fff;text-decoration:none;font-weight:700;padding:14px;border-radius:14px;margin-top:6px}
.cod{text-align:center;color:var(--gold2);font-size:15px;margin-top:10px}
.muted{color:var(--dim);font-size:13px;text-align:center}
</style>
</head>
<body><div class="wrap">
<div class="brand">Le Comptoir des Parfums</div>
<?php if ( ! $o ) : ?>
	<div class="card"><h1>ما لقيناش هاد الكوليّة</h1><p class="sub">Lien de suivi introuvable ou expiré.</p></div>
	<?php if ( $shop ) : ?><a class="btn" href="https://wa.me/<?php echo esc_attr( $shop ); ?>">سولنا فالواتساب</a><?php endif; ?>
<?php else : ?>
	<div class="card">
		<h1>تتبع الكوموند ديالك</h1>
		<p class="sub"><?php echo esc_html( $o['ref'] ); ?><?php echo ( $tr && strtoupper( $tr ) !== strtoupper( $o['ref'] ) ) ? ' · OzonExpress ' . esc_html( $tr ) : ''; ?></p>
		<div class="steps">
			<?php $i = 0; foreach ( $steps as $k => $l ) : $i++; ?>
				<div class="st<?php echo $i <= $rank ? ' on' : ''; ?>"><i></i><?php echo esc_html( $l[0] ); ?><b><?php echo esc_html( $l[1] ); ?></b></div>
			<?php endforeach; ?>
		</div>
		<div class="status">
			<?php
			if ( 'livree' === $etat ) {
				echo 'توصلات الكوموند ديالك 🎉 بالصحة!';
			} elseif ( 'annulee' === $etat ) {
				echo 'هاد الكوموند تلغات';
			} elseif ( $rank >= 3 ) {
				echo 'الكوليّة عند الليفرور 🛵';
			} elseif ( 2 === $rank ) {
				echo 'الكوليّة فالطريق ليك 🚚';
			} else {
				echo 'كنوجدو الكوليّة ديالك 📦';
			}
			?>
			<?php if ( $status ) : ?><small><?php echo esc_html( $status ); ?></small><?php endif; ?>
		</div>
		<?php if ( ! $final && 'livree' !== $etat && 'annulee' !== $etat ) : ?>
			<p class="cod">💵 خلّص غير ملي توصلك الكوليّة</p>
		<?php endif; ?>
	</div>

	<?php if ( $livtel && ! $final ) : ?>
	<div class="card liv">
		<div class="ic">🛵</div>
		<div><strong>الليفرور ديالك</strong><br><?php echo esc_html( $liv ? $liv : 'OzonExpress' ); ?><br><span class="muted" style="direction:ltr;display:inline-block"><?php echo esc_html( $livtel ); ?></span></div>
		<a class="call" href="tel:<?php echo esc_attr( $livtel ); ?>">📞 عيط ليه</a>
	</div>
	<?php endif; ?>

	<?php if ( $hist ) : ?>
	<div class="card"><ul class="hist">
		<?php foreach ( $hist as $h ) : ?>
			<li><?php echo esc_html( $h['statut'] ); ?><span><?php echo esc_html( $h['date'] ); ?></span></li>
		<?php endforeach; ?>
	</ul></div>
	<?php endif; ?>

	<?php if ( $shop ) : ?>
		<a class="btn" href="https://wa.me/<?php echo esc_attr( $shop ); ?>?text=<?php echo rawurlencode( 'سلام، بغيت نسول على الكوموند ' . $o['ref'] ); ?>">💬 عندك سؤال؟ كتب لينا فالواتساب</a>
	<?php endif; ?>
	<p class="muted" style="margin-top:14px">Mis à jour automatiquement · comptoirparfums.com</p>
<?php endif; ?>
</div></body></html>
	<?php
}
