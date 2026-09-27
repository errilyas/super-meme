<?php
/**
 * Le Comptoir des Parfums — header.php
 *
 * Portage fidele de la maquette comptoirv3-motion.html (design de reference,
 * voir README-INSTALLATION.txt). Le CSS du design vit ici en inline, comme
 * dans la maquette : une seule requete, aucun flash de page non stylee.
 * style.css ne sert qu'a declarer le theme aupres de WordPress.
 *
 * Ce qui differe de la maquette, et pourquoi :
 *  - <title>, canonical, description et Open Graph sont produits par
 *    functions.php (ils dependent de la page servie), jamais ecrits en dur ;
 *  - les ancres de navigation passent par home_url() : « #catalogue » depuis
 *    une fiche parfum doit ramener a l'accueil, pas rester sans effet ;
 *  - prechargeur, curseur sur-mesure et menu mobile ne sont poses que sur la
 *    page d'accueil — c'est deja le choix de parfum.html cote maquette.
 */

$cp_home    = home_url( '/' );
$cp_wa      = 'https://wa.me/' . comptoir_wa_numero();
$cp_is_home = comptoir_est_accueil();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#110814">
<script>/* langue memorisee, posee avant le rendu : sans cela la page s'affiche
   en francais puis saute en arabe sous les yeux du visiteur. */
try{var cpL=new URLSearchParams(location.search).get('lang')||localStorage.getItem('cp_langue');
if(cpL==='ar'){document.documentElement.setAttribute('data-lang','ar');document.documentElement.setAttribute('lang','ar');document.documentElement.setAttribute('dir','rtl');}}catch(e){}</script>
<script>/* theme memorise, applique avant le rendu pour eviter tout flash */try{if(localStorage.getItem('cp_theme')==='light'){document.documentElement.setAttribute('data-theme','light');}}catch(e){}</script>
<script>/* Filet de securite : le hero de l'accueil se revele via GSAP
   (theme.js, initHeroSequence), qui attend le prechargeur puis anime chaque
   bloc. Si GSAP est lent a charger (CDN, connexion 4G) ou echoue, ces blocs
   restent a opacity:0 indefiniment — un ecran quasi vide, sans qu'aucune
   erreur ne le signale. Ce minuteur, independant de GSAP, force leur
   affichage passe un delai raisonnable : le pire des cas devient "sans
   animation", jamais "invisible". */
setTimeout(function(){document.documentElement.classList.add('force-reveal');},1200);</script>

<?php /* connect.facebook.net sert le pixel, dont le PageView decide de la
   metrique « vue de page » cote Ads Manager : sans preconnect, il faut une
   resolution DNS et une poignee de main TLS de plus avant qu'il parte.
   cdnjs sert GSAP, dont depend la revelation du hero. */ ?>
<link rel="preconnect" href="https://connect.facebook.net" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php /* Les fontes ne bloquent plus le premier rendu : elles portent deja
   display=swap, le texte s'affichait donc de toute facon en police de repli
   avant de basculer. Autant que ce premier affichage arrive plus tot. */ ?>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Jost:wght@200;300;400;500&family=Tajawal:wght@300;400;500;700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Jost:wght@200;300;400;500&family=Tajawal:wght@300;400;500;700&display=swap"></noscript>
<?php /* Le design est dans style-site.css, charge par functions.php (wp_enqueue_style). */ ?>
<noscript><style>
  /* Sans JavaScript : retirer le préchargeur et garder le contenu lisible. */
  #loader{display:none!important}
  .r,.r-left,.r-right,
  .h-eyebrow,.hero-h1 .h1-inner,.h-badge,.hero-sub,.hero-actions,.hero-trust,.hero-visual,
  .h-note-card{opacity:1!important;transform:none!important}
  #cat-list::after{
    content:'Activez JavaScript pour parcourir le catalogue complet, ou commandez directement sur WhatsApp.';
    display:block;padding:28px 0;color:var(--smoke);font-size:14px;line-height:1.7
  }
</style></noscript>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- ─── MARQUE — sceau CP, défini une fois, réutilisé (nav, préchargeur, pied, flacon) ─── -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs>
  <linearGradient id="cpAu" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#f4e0ac"/><stop offset=".38" stop-color="#e0b268"/>
    <stop offset=".7" stop-color="#c08f3f"/><stop offset="1" stop-color="#8f6a2c"/>
  </linearGradient>
  <linearGradient id="cpAuF" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#d9b673"/><stop offset="1" stop-color="#8f6a2c"/></linearGradient>
  <symbol id="cp-seal" viewBox="0 0 96 96">
    <circle cx="48" cy="48" r="45" fill="none" stroke="url(#cpAu)" stroke-width="1.4"/>
    <circle cx="48" cy="48" r="41.5" fill="none" stroke="url(#cpAuF)" stroke-width=".6" opacity=".55"/>
    <circle cx="48" cy="48" r="36.5" fill="none" stroke="url(#cpAu)" stroke-width="1.35" stroke-linecap="round" stroke-dasharray="0.5 4.1"/>
    <text x="49" y="64" text-anchor="middle" font-family="'Cormorant Garamond',Georgia,serif"
          font-style="italic" font-weight="500" font-size="42" letter-spacing="-1.5" fill="url(#cpAu)">CP</text>
  </symbol>
  <symbol id="cp-mono" viewBox="0 0 96 96">
    <text x="49" y="70" text-anchor="middle" font-family="'Cormorant Garamond',Georgia,serif"
          font-style="italic" font-weight="500" font-size="58" letter-spacing="-2" fill="url(#cpAu)">CP</text>
  </symbol>
</defs></svg>

<!-- ─── SCROLL PROGRESS ─── -->
<a class="skip-link" href="#cp-main">Aller au contenu</a>
<div id="scroll-progress" aria-hidden="true"></div>

<!-- ─── CURSOR ─── -->
<div id="cur" aria-hidden="true"></div>
<div id="cur-r" aria-hidden="true"></div>
<div id="cur-label" aria-hidden="true">Commander</div>
<?php if ( $cp_is_home ) : ?>

<!-- ─── PRELOADER ─── -->
<div id="loader" role="status" aria-label="Chargement">
  <div id="ldr-mark"><svg viewBox="0 0 96 96" aria-hidden="true"><use href="#cp-seal"/></svg></div>
  <div id="ldr-rule"></div>
  <div id="ldr-name">
    <span class="ldr-serif">Le Comptoir</span>
    <span class="ldr-sans">des Parfums</span>
  </div>
  <div id="ldr-bar"></div>
  <button id="ldr-skip">Passer</button>
</div>

<!-- ─── MOBILE MENU ─── -->
<div id="mob-menu" role="dialog" aria-modal="true" aria-label="Menu">
  <nav class="mob-nav" aria-label="Menu principal">
    <a href="<?php echo esc_url( $cp_home ); ?>#scents" class="mob-link" data-close><span class="mob-num" aria-hidden="true">01</span>Sélection</a>
    <a href="<?php echo esc_url( $cp_home ); ?>#distinction" class="mob-link" data-close><span class="mob-num" aria-hidden="true">02</span>Notre approche</a>
    <a href="<?php echo esc_url( $cp_home ); ?>#catalogue" class="mob-link" data-close><span class="mob-num" aria-hidden="true">03</span>Catalogue</a>
    <a href="<?php echo esc_url( $cp_home ); ?>#process" class="mob-link" data-close><span class="mob-num" aria-hidden="true">04</span>Commander</a>
    <a href="#" class="mob-link" data-close data-panier-open><span class="mob-num" aria-hidden="true">05</span>Panier (<span data-panier-count>0</span>)</a>
  </nav>
  <!-- Le pied du menu : la voie directe (WhatsApp) et la promesse, a portee
       de pouce, la ou le visiteur hesite. -->
  <div class="mob-pied">
    <a href="<?php echo esc_url( $cp_wa ); ?>" class="mob-link mob-wa" target="_blank" rel="noopener" data-close>WhatsApp →</a>
    <p class="mob-gages">Paiement à la livraison · Livraison partout au Maroc</p>
  </div>
</div>

<!-- ─── NAV ─── -->
<nav id="nav" role="navigation" aria-label="Navigation principale">
  <a href="<?php echo esc_url( $cp_home ); ?>" class="nav-logo" aria-label="Le Comptoir des Parfums — accueil">
    <svg class="nl-seal" viewBox="0 0 96 96" aria-hidden="true"><use href="#cp-seal"/></svg>
    <span class="nl-txt">
      <span class="nl-serif">Le Comptoir</span>
      <span class="nl-sans">des Parfums</span>
    </span>
  </a>
  <ul class="nav-links" role="list">
    <li><a href="<?php echo esc_url( $cp_home ); ?>#scents">Sélection</a></li>
    <li><a href="<?php echo esc_url( $cp_home ); ?>#distinction">Notre approche</a></li>
    <li><a href="<?php echo esc_url( $cp_home ); ?>#catalogue">Catalogue</a></li>
    <li><a href="<?php echo esc_url( $cp_home ); ?>#process">Commander</a></li>
  </ul>
  <div class="nav-right">
    <button type="button" class="nav-pill" data-panier-open aria-label="Ouvrir le panier"><svg class="np-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1.2 12.2a1 1 0 0 1-1 .8H7.2a1 1 0 0 1-1-.8z"/><path d="M9 10V6.5a3 3 0 0 1 6 0V10"/></svg><span class="np-txt">Panier</span><span data-panier-count>0</span></button>
    <button id="nav-ham" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="mob-menu">
      <span></span><span></span><span></span>
    </button>
    <button type="button" class="langue-bascule" data-langue-bascule aria-label="التبديل إلى العربية">ع</button>
    <button id="theme-toggle" class="theme-toggle" type="button" aria-label="Basculer en mode clair" aria-pressed="false">
      <svg class="ico-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2.6M12 18.9v2.6M4.6 4.6l1.9 1.9M17.5 17.5l1.9 1.9M2.5 12h2.6M18.9 12h2.6M4.6 19.4l1.9-1.9M17.5 6.5l1.9-1.9"/></svg>
      <svg class="ico-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 14.5A8.2 8.2 0 0 1 9.5 4a8.2 8.2 0 1 0 10.5 10.5z"/></svg>
    </button>
  </div>
</nav>
<?php else : ?>

<!-- ─── NAV ─── -->
<nav id="nav" role="navigation" aria-label="Navigation principale">
  <a href="<?php echo esc_url( $cp_home ); ?>" class="nav-logo" aria-label="Le Comptoir des Parfums — accueil">
    <svg class="nl-seal" viewBox="0 0 96 96" aria-hidden="true"><use href="#cp-seal"/></svg>
    <span class="nl-txt">
      <span class="nl-serif">Le Comptoir</span>
      <span class="nl-sans">des Parfums</span>
    </span>
  </a>
  <ul class="nav-links" role="list">
    <li><a href="<?php echo esc_url( $cp_home ); ?>#catalogue">Catalogue</a></li>
  </ul>
  <div class="nav-right">
    <button type="button" class="nav-pill" data-panier-open aria-label="Ouvrir le panier"><svg class="np-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1.2 12.2a1 1 0 0 1-1 .8H7.2a1 1 0 0 1-1-.8z"/><path d="M9 10V6.5a3 3 0 0 1 6 0V10"/></svg><span class="np-txt">Panier</span><span data-panier-count>0</span></button>
    <button type="button" class="langue-bascule" data-langue-bascule aria-label="التبديل إلى العربية">ع</button>
    <button id="theme-toggle" class="theme-toggle" type="button" aria-label="Basculer en mode clair" aria-pressed="false">
      <svg class="ico-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2.6M12 18.9v2.6M4.6 4.6l1.9 1.9M17.5 17.5l1.9 1.9M2.5 12h2.6M18.9 12h2.6M4.6 19.4l1.9-1.9M17.5 6.5l1.9-1.9"/></svg>
      <svg class="ico-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 14.5A8.2 8.2 0 0 1 9.5 4a8.2 8.2 0 1 0 10.5 10.5z"/></svg>
    </button>
  </div>
</nav>
<?php endif; ?>
