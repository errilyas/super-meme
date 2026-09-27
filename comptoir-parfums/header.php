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
  <!-- Sceau v3.4 : lettres et légende vectorisées (Cormorant Garamond, Jost),
       plus aucune dépendance à la police chargée. #cp-seal = version compacte,
       lisible dès 30 px ; #cp-seal-full = sceau complet avec légende, pour les
       grandes tailles. Les arrêts .cpg0–3 s'assombrissent en thème clair. -->
  <linearGradient id="cpAu2" x1="0" y1="0" x2="0" y2="96" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#f4e0ac" class="cpg0"/><stop offset=".38" stop-color="#e0b268" class="cpg1"/><stop offset=".7" stop-color="#c08f3f" class="cpg2"/><stop offset="1" stop-color="#8f6a2c" class="cpg3"/></linearGradient>
  <symbol id="cp-seal" viewBox="0 0 96 96"><circle cx="48" cy="48" r="45" fill="none" stroke="url(#cpAu2)" stroke-width="2.6"/><circle cx="48" cy="48" r="39.2" fill="none" stroke="url(#cpAu2)" stroke-width="1.2"/><path fill="url(#cpAu2)" d="M36.92 65.85Q32.3 65.85 29.38 64.31Q26.47 62.78 25.02 60.28Q23.56 57.79 23.22 54.82Q22.88 51.86 23.4 48.95Q24.13 44.94 26.03 41.82Q27.93 38.7 30.63 36.55Q33.34 34.39 36.51 33.27Q39.68 32.15 42.96 32.15Q45.09 32.15 47.3 32.59Q49.51 33.04 50.7 33.66Q51.07 33.87 51.12 34.08Q51.17 34.28 51.12 34.86L50.29 41.82Q50.29 41.98 50.03 41.98Q49.77 41.98 49.72 41.82Q48.83 37.98 46.52 35.77Q44.2 33.56 40.82 33.56Q37.86 33.56 35.44 35.19Q33.02 36.83 31.44 39.87Q29.85 42.92 29.23 47.23Q28.71 50.77 29.02 53.94Q29.33 57.11 30.37 59.56Q31.41 62 33.15 63.4Q34.9 64.81 37.24 64.81Q39.84 64.81 42.54 62.88Q45.24 60.96 48.26 56.9Q48.42 56.7 48.65 56.83Q48.88 56.96 48.83 57.06L47.43 63.04Q47.27 63.66 47.12 63.9Q46.96 64.13 46.49 64.24Q43.89 65.12 41.42 65.48Q38.95 65.85 36.92 65.85ZM45.52 65.22Q45.42 65.22 45.42 64.91Q45.42 64.6 45.52 64.6Q46.93 64.6 47.68 64.34Q48.44 64.08 48.8 63.3Q49.16 62.52 49.42 61.01L53.69 36.94Q54.1 34.65 53.61 34Q53.12 33.35 51.14 33.35Q50.98 33.35 50.98 33.04Q50.98 32.72 51.14 32.72Q52.28 32.72 53.71 32.8Q55.14 32.88 56.81 32.88Q57.85 32.88 59.67 32.72Q61.49 32.57 63.05 32.57Q66.27 32.57 68.64 33.56Q71 34.54 72.15 36.57Q73.29 38.6 72.72 41.67Q72.25 44.32 70.85 46.19Q69.44 48.06 67.57 49.26Q65.7 50.46 63.67 51.03Q61.64 51.6 59.88 51.6Q59.3 51.6 58.78 51.55Q58.26 51.5 57.74 51.34Q57.64 51.29 57.74 50.77Q57.85 50.25 58.06 50.3Q58.42 50.4 58.81 50.46Q59.2 50.51 59.56 50.51Q61.23 50.51 62.76 49.7Q64.3 48.9 65.44 47.18Q66.58 45.46 67 42.71Q67.47 39.8 66.84 37.79Q66.22 35.79 64.95 34.73Q63.67 33.66 62.06 33.66Q60.97 33.66 60.34 33.92Q59.72 34.18 59.38 34.91Q59.04 35.64 58.73 37.04L54.52 61.01Q54.16 63.25 54.65 63.92Q55.14 64.6 57.07 64.6Q57.22 64.6 57.22 64.91Q57.22 65.22 57.07 65.22Q55.92 65.22 54.42 65.17Q52.91 65.12 51.19 65.12Q49.58 65.12 48.12 65.17Q46.67 65.22 45.52 65.22Z"/></symbol>
  <symbol id="cp-seal-full" viewBox="0 0 96 96"><circle cx="48" cy="48" r="46.4" fill="none" stroke="url(#cpAu2)" stroke-width="1.5"/><circle cx="48" cy="48" r="35.4" fill="none" stroke="url(#cpAu2)" stroke-width="1"/><path fill="url(#cpAu2)" d="M6.26 38.18 10.39 39.25 11 36.92 10.37 36.75 9.95 38.39 6.44 37.48ZM11.64 35.01 12.41 32.99 11.82 32.77 11.05 34.78ZM8.25 33.71 9.01 31.7 8.42 31.47 7.65 33.48ZM9.8 34.3 10.52 32.4 9.94 32.18 9.22 34.08ZM7.5 33.9 11.48 35.42 11.74 34.76 7.75 33.24ZM13.03 27.08Q12.64 26.83 12.47 26.48Q12.29 26.13 12.31 25.75Q12.33 25.37 12.54 25.04Q12.7 24.78 12.9 24.62Q13.1 24.45 13.32 24.36Q13.54 24.27 13.77 24.24L13.05 23.78Q12.72 23.88 12.45 24.08Q12.18 24.28 11.93 24.68Q11.68 25.08 11.6 25.51Q11.52 25.94 11.61 26.35Q11.7 26.76 11.95 27.12Q12.2 27.48 12.61 27.74Q13.02 28 13.45 28.07Q13.88 28.15 14.3 28.06Q14.71 27.96 15.06 27.71Q15.41 27.45 15.66 27.05Q15.92 26.66 15.99 26.33Q16.06 26 16.01 25.66L15.29 25.2Q15.36 25.42 15.37 25.66Q15.38 25.9 15.31 26.15Q15.25 26.4 15.08 26.66Q14.88 26.99 14.54 27.16Q14.2 27.34 13.81 27.33Q13.41 27.32 13.03 27.08ZM16.19 22.51Q15.86 22.22 15.72 21.86Q15.58 21.49 15.65 21.11Q15.71 20.74 15.99 20.42Q16.27 20.09 16.63 19.97Q16.99 19.85 17.37 19.94Q17.76 20.02 18.09 20.31Q18.42 20.6 18.56 20.96Q18.7 21.33 18.63 21.7Q18.57 22.08 18.29 22.4Q18.02 22.72 17.65 22.84Q17.29 22.96 16.91 22.88Q16.53 22.8 16.19 22.51ZM15.69 23.09Q16.06 23.4 16.47 23.54Q16.89 23.68 17.31 23.65Q17.73 23.62 18.12 23.42Q18.52 23.22 18.83 22.86Q19.15 22.49 19.28 22.08Q19.42 21.67 19.39 21.24Q19.35 20.82 19.15 20.43Q18.95 20.04 18.59 19.73Q18.22 19.41 17.81 19.28Q17.4 19.14 16.98 19.17Q16.56 19.21 16.17 19.4Q15.78 19.6 15.46 19.96Q15.16 20.32 15.02 20.74Q14.88 21.15 14.91 21.58Q14.93 22 15.13 22.39Q15.33 22.77 15.69 23.09ZM20.39 17.39 22.81 18.23 22.38 15.71 24.28 17.5 24.85 17.02 21.51 13.98 22.04 17.32 18.83 16.24 21.28 20.04 21.85 19.56ZM24.04 12.43 26.32 16.04 26.93 15.66 24.65 12.04ZM24.74 12.74 25.47 12.28Q25.78 12.08 26.06 12.11Q26.35 12.13 26.53 12.42Q26.71 12.71 26.62 12.98Q26.52 13.25 26.2 13.45L25.48 13.91L25.82 14.44L26.54 13.99Q26.94 13.74 27.14 13.42Q27.35 13.1 27.35 12.74Q27.35 12.39 27.13 12.04Q26.92 11.7 26.6 11.55Q26.27 11.4 25.9 11.45Q25.52 11.49 25.13 11.74L24.4 12.2ZM28.3 10.65 29.32 10.15 30.88 13.4 31.53 13.09 29.96 9.84 30.98 9.35 30.69 8.75 28.01 10.05ZM33.82 9.78Q33.68 9.37 33.75 8.98Q33.82 8.59 34.07 8.31Q34.32 8.02 34.72 7.89Q35.13 7.75 35.5 7.83Q35.87 7.92 36.16 8.19Q36.44 8.46 36.58 8.87Q36.71 9.29 36.65 9.67Q36.58 10.06 36.33 10.35Q36.08 10.64 35.68 10.77Q35.28 10.9 34.9 10.82Q34.53 10.73 34.25 10.47Q33.96 10.2 33.82 9.78ZM33.1 10.02Q33.25 10.48 33.53 10.81Q33.82 11.15 34.19 11.34Q34.57 11.53 35.01 11.56Q35.45 11.59 35.9 11.44Q36.36 11.29 36.69 11Q37.02 10.72 37.21 10.34Q37.4 9.96 37.43 9.52Q37.46 9.08 37.31 8.63Q37.16 8.17 36.87 7.84Q36.59 7.51 36.21 7.33Q35.83 7.14 35.4 7.11Q34.96 7.08 34.5 7.23Q34.06 7.37 33.72 7.66Q33.39 7.95 33.2 8.32Q33 8.7 32.97 9.13Q32.95 9.57 33.1 10.02ZM38.82 6.12 39.7 10.3 40.42 10.15 39.54 5.97ZM43.04 7.66 44.61 9.54 45.47 9.44 43.81 7.58ZM41.91 5.54 42.38 9.79 43.1 9.71 42.62 5.46ZM42.4 6.11 43.26 6.02Q43.51 5.99 43.7 6.06Q43.89 6.12 44.01 6.27Q44.13 6.41 44.16 6.64Q44.18 6.87 44.1 7.04Q44.01 7.21 43.84 7.31Q43.66 7.42 43.42 7.44L42.56 7.54L42.63 8.13L43.51 8.03Q43.98 7.98 44.3 7.78Q44.62 7.58 44.78 7.26Q44.94 6.94 44.89 6.54Q44.85 6.14 44.62 5.87Q44.4 5.6 44.04 5.47Q43.69 5.35 43.22 5.4L42.33 5.5ZM50.32 5.16 49.93 9.41 50.66 9.48 51.06 5.23ZM51.3 9.54Q51.95 9.6 52.47 9.38Q52.98 9.16 53.3 8.71Q53.62 8.26 53.68 7.62Q53.74 6.97 53.51 6.47Q53.28 5.97 52.81 5.66Q52.35 5.35 51.7 5.29L50.77 5.2L50.7 5.88L51.62 5.96Q51.93 5.99 52.19 6.11Q52.45 6.22 52.63 6.42Q52.81 6.62 52.9 6.91Q52.98 7.19 52.95 7.55Q52.92 7.91 52.78 8.17Q52.64 8.43 52.43 8.59Q52.21 8.76 51.94 8.82Q51.66 8.89 51.34 8.86L50.43 8.78L50.37 9.45ZM55.65 10.16 57.75 10.63 57.89 10.01 55.79 9.54ZM56.44 6.61 58.54 7.08 58.68 6.46 56.58 5.99ZM56.08 8.23 58.06 8.67 58.2 8.07 56.22 7.62ZM56.15 5.89 55.21 10.06 55.9 10.21 56.84 6.05ZM60.33 9.96 59.7 10.14Q59.74 10.46 59.88 10.77Q60.02 11.09 60.28 11.34Q60.53 11.59 60.88 11.71Q61.14 11.8 61.41 11.8Q61.67 11.8 61.92 11.72Q62.16 11.63 62.35 11.45Q62.54 11.26 62.64 10.98Q62.74 10.71 62.71 10.48Q62.69 10.25 62.6 10.06Q62.5 9.86 62.35 9.7Q62.21 9.54 62.04 9.41Q61.76 9.18 61.6 9Q61.44 8.82 61.39 8.66Q61.33 8.51 61.39 8.35Q61.45 8.18 61.63 8.1Q61.81 8.01 62.09 8.11Q62.29 8.18 62.41 8.31Q62.54 8.45 62.61 8.62Q62.68 8.79 62.71 8.96L63.36 8.85Q63.34 8.6 63.22 8.34Q63.11 8.08 62.89 7.85Q62.67 7.63 62.31 7.51Q61.95 7.38 61.62 7.42Q61.29 7.45 61.03 7.64Q60.78 7.83 60.67 8.15Q60.57 8.43 60.61 8.67Q60.65 8.9 60.76 9.1Q60.88 9.29 61.04 9.45Q61.19 9.6 61.32 9.7Q61.54 9.89 61.7 10.05Q61.87 10.21 61.92 10.39Q61.98 10.56 61.91 10.78Q61.82 11.02 61.6 11.11Q61.37 11.2 61.09 11.1Q60.87 11.02 60.72 10.86Q60.56 10.69 60.47 10.47Q60.38 10.24 60.33 9.96ZM68.64 10.4 66.47 14.08 67.09 14.45 69.26 10.77ZM68.68 11.16 69.42 11.6Q69.74 11.79 69.85 12.05Q69.96 12.32 69.78 12.61Q69.61 12.91 69.33 12.94Q69.04 12.98 68.72 12.79L67.98 12.36L67.66 12.9L68.4 13.34Q68.8 13.58 69.18 13.61Q69.56 13.65 69.87 13.49Q70.19 13.33 70.4 12.97Q70.61 12.62 70.59 12.27Q70.57 11.92 70.36 11.6Q70.15 11.29 69.74 11.05L69 10.62ZM71.44 15.94 73.22 17.32 73.49 16.76 71.92 15.54ZM73.52 15.07 73.09 16.74 72.99 16.89 72.65 18.27 73.3 18.77 74.41 13.93 70 16.21 70.64 16.71 71.92 16.02 72.03 15.9ZM76.49 19.02 76 21.42 76.62 22.04 77.04 19.57ZM77.45 16.82 74.42 19.83 74.93 20.34 77.96 17.33ZM77.31 17.56 77.92 18.17Q78.09 18.35 78.16 18.54Q78.23 18.73 78.19 18.91Q78.15 19.1 77.99 19.26Q77.83 19.42 77.64 19.46Q77.45 19.5 77.26 19.43Q77.08 19.36 76.9 19.18L76.29 18.57L75.87 18.99L76.5 19.62Q76.83 19.96 77.19 20.08Q77.55 20.21 77.89 20.13Q78.24 20.06 78.53 19.78Q78.81 19.49 78.89 19.15Q78.96 18.8 78.84 18.44Q78.71 18.09 78.38 17.75L77.75 17.12ZM80.75 21.34 81.93 22.84 82.44 22.44 81.26 20.94ZM79.42 22.39 80.56 23.85 81.06 23.46 79.91 22ZM80.98 20.59 77.63 23.24 78.07 23.79 81.42 21.15ZM83.8 24.37 81.35 25.86Q81.05 26.04 80.87 26.29Q80.7 26.53 80.64 26.81Q80.58 27.08 80.64 27.38Q80.7 27.68 80.88 27.97Q81.05 28.26 81.29 28.46Q81.53 28.65 81.81 28.72Q82.08 28.8 82.38 28.75Q82.67 28.71 82.98 28.52L85.42 27.03L85.05 26.41L82.62 27.89Q82.29 28.09 81.97 28.04Q81.66 27.98 81.44 27.63Q81.23 27.28 81.32 26.97Q81.42 26.66 81.75 26.46L84.18 24.98ZM85.41 30.7 83.89 32.76 86.43 33.1 84.15 34.38 84.44 35.07 88.34 32.78 84.99 32.29 86.97 29.55 82.62 30.76 82.91 31.45ZM86.35 36.67 85.85 36.25Q85.6 36.46 85.42 36.76Q85.24 37.06 85.18 37.41Q85.12 37.76 85.22 38.11Q85.29 38.38 85.44 38.6Q85.59 38.82 85.8 38.98Q86.01 39.13 86.27 39.18Q86.53 39.23 86.82 39.16Q87.09 39.08 87.27 38.94Q87.44 38.79 87.55 38.6Q87.66 38.41 87.71 38.2Q87.76 37.98 87.77 37.78Q87.8 37.42 87.86 37.18Q87.92 36.94 88.02 36.81Q88.12 36.68 88.28 36.64Q88.45 36.59 88.62 36.69Q88.79 36.79 88.87 37.08Q88.92 37.29 88.89 37.46Q88.85 37.64 88.75 37.79Q88.65 37.95 88.52 38.07L88.98 38.54Q89.17 38.39 89.32 38.14Q89.47 37.9 89.53 37.6Q89.59 37.29 89.5 36.92Q89.4 36.55 89.18 36.3Q88.96 36.04 88.66 35.94Q88.37 35.84 88.04 35.93Q87.75 36 87.57 36.17Q87.4 36.34 87.31 36.54Q87.21 36.75 87.17 36.96Q87.13 37.18 87.12 37.35Q87.1 37.63 87.05 37.86Q87.01 38.08 86.9 38.23Q86.79 38.37 86.57 38.43Q86.32 38.5 86.12 38.37Q85.92 38.23 85.84 37.95Q85.78 37.72 85.83 37.5Q85.88 37.28 86.01 37.08Q86.15 36.87 86.35 36.67ZM35.92 86.16 36.57 88.63 38.43 86.86 38.01 89.44 38.72 89.64 39.37 85.17 36.9 87.48 35.99 84.22 34.22 88.38 34.93 88.58ZM41.65 89.03 43.88 89.31 43.83 88.69 41.86 88.44ZM43 87.22 43.48 88.88 43.47 89.06 43.88 90.42 44.69 90.52 43.18 85.79 40.54 89.99 41.35 90.1 42.11 88.85 42.14 88.69ZM47.66 88.56 49.02 90.6 49.89 90.59 48.43 88.56ZM46.77 86.33 46.78 90.6 47.5 90.6 47.49 86.33ZM47.2 86.95 48.06 86.95Q48.31 86.95 48.49 87.04Q48.67 87.12 48.78 87.28Q48.88 87.44 48.88 87.67Q48.88 87.9 48.78 88.06Q48.68 88.22 48.49 88.3Q48.31 88.38 48.06 88.39L47.2 88.39L47.2 88.99L48.09 88.98Q48.56 88.98 48.9 88.82Q49.25 88.65 49.44 88.35Q49.63 88.05 49.63 87.65Q49.63 87.25 49.43 86.95Q49.24 86.66 48.9 86.49Q48.56 86.33 48.09 86.33L47.2 86.33ZM52.22 88.27Q52.16 87.84 52.3 87.47Q52.44 87.1 52.74 86.86Q53.03 86.62 53.45 86.56Q53.88 86.5 54.23 86.65Q54.58 86.8 54.81 87.11Q55.04 87.43 55.1 87.86Q55.16 88.3 55.02 88.66Q54.89 89.03 54.59 89.27Q54.3 89.51 53.87 89.57Q53.46 89.63 53.11 89.48Q52.76 89.33 52.52 89.02Q52.29 88.71 52.22 88.27ZM51.47 88.38Q51.54 88.85 51.76 89.23Q51.98 89.61 52.31 89.87Q52.65 90.12 53.08 90.23Q53.5 90.34 53.97 90.27Q54.46 90.2 54.83 89.98Q55.21 89.76 55.46 89.42Q55.71 89.08 55.82 88.66Q55.93 88.23 55.86 87.76Q55.79 87.28 55.57 86.9Q55.35 86.53 55.01 86.28Q54.67 86.03 54.25 85.92Q53.83 85.81 53.35 85.87Q52.89 85.94 52.51 86.16Q52.13 86.39 51.87 86.72Q51.61 87.06 51.51 87.48Q51.4 87.9 51.47 88.38ZM58.32 87.14Q58.2 86.7 58.3 86.32Q58.4 85.94 58.66 85.67Q58.93 85.39 59.3 85.29Q59.59 85.2 59.85 85.21Q60.11 85.21 60.34 85.29Q60.56 85.37 60.75 85.49L60.51 84.67Q60.2 84.53 59.86 84.5Q59.53 84.48 59.08 84.61Q58.63 84.74 58.28 85.01Q57.94 85.28 57.74 85.65Q57.53 86.02 57.49 86.46Q57.44 86.89 57.58 87.36Q57.71 87.82 57.99 88.17Q58.26 88.51 58.63 88.71Q59 88.91 59.44 88.95Q59.87 88.99 60.32 88.86Q60.77 88.73 61.04 88.53Q61.31 88.33 61.5 88.04L61.26 87.22Q61.17 87.43 61.02 87.61Q60.87 87.8 60.66 87.95Q60.44 88.09 60.15 88.18Q59.77 88.29 59.4 88.2Q59.03 88.11 58.74 87.84Q58.45 87.57 58.32 87.14ZM7.40 46.75L8.30 48.00L7.40 49.25L6.50 48.00ZM88.60 46.75L89.50 48.00L88.60 49.25L87.70 48.00Z"/><path fill="url(#cpAu2)" d="M38.79 63.76Q34.91 63.76 32.49 62.46Q30.07 61.16 28.84 59.05Q27.61 56.94 27.32 54.41Q27.04 51.88 27.48 49.37Q28.09 46.07 29.68 43.45Q31.26 40.83 33.55 39.01Q35.84 37.18 38.5 36.21Q41.16 35.24 43.89 35.24Q45.69 35.24 47.54 35.64Q49.39 36.04 50.4 36.61Q50.71 36.78 50.75 36.96Q50.8 37.14 50.75 37.62L50.14 43.16Q50.14 43.34 49.92 43.34Q49.7 43.34 49.65 43.16Q49.04 39.86 47.06 38.1Q45.08 36.34 42.13 36.34Q39.53 36.34 37.38 37.75Q35.22 39.16 33.79 41.78Q32.36 44.4 31.79 48Q31.31 51.04 31.61 53.75Q31.92 56.45 32.87 58.52Q33.81 60.59 35.38 61.78Q36.94 62.96 39.05 62.96Q41.34 62.96 43.67 61.34Q46 59.71 48.33 56.32Q48.47 56.14 48.66 56.25Q48.86 56.36 48.82 56.45L47.59 61.42Q47.41 61.95 47.3 62.15Q47.19 62.35 46.79 62.44Q44.64 63.18 42.57 63.47Q40.5 63.76 38.79 63.76ZM46.03 63.23Q45.94 63.23 45.94 62.96Q45.94 62.7 46.03 62.7Q47.31 62.7 47.97 62.48Q48.63 62.26 48.96 61.6Q49.29 60.94 49.51 59.66L53.11 39.29Q53.47 37.36 53.03 36.81Q52.59 36.26 50.78 36.26Q50.65 36.26 50.65 35.99Q50.65 35.73 50.78 35.73Q51.71 35.73 52.89 35.79Q54.08 35.86 55.45 35.86Q56.33 35.86 57.84 35.73Q59.36 35.6 60.77 35.6Q63.32 35.6 65.26 36.43Q67.19 37.27 68.16 38.96Q69.13 40.66 68.6 43.3Q68.21 45.54 67.04 47.12Q65.87 48.71 64.29 49.72Q62.71 50.73 60.99 51.22Q59.27 51.7 57.78 51.7Q57.25 51.7 56.83 51.66Q56.41 51.61 55.97 51.48Q55.84 51.44 55.93 51.04Q56.02 50.64 56.19 50.69Q56.5 50.78 56.83 50.82Q57.16 50.86 57.47 50.86Q58.97 50.86 60.42 50.16Q61.87 49.46 62.95 47.98Q64.03 46.51 64.38 44.18Q64.82 41.67 64.25 39.95Q63.67 38.24 62.51 37.33Q61.34 36.43 59.89 36.43Q58.83 36.43 58.24 36.63Q57.65 36.83 57.34 37.47Q57.03 38.1 56.77 39.38L53.2 59.66Q52.89 61.56 53.33 62.13Q53.77 62.7 55.53 62.7Q55.67 62.7 55.67 62.96Q55.67 63.23 55.53 63.23Q54.57 63.23 53.33 63.18Q52.1 63.14 50.69 63.14Q49.37 63.14 48.16 63.18Q46.95 63.23 46.03 63.23Z"/></symbol>
  <symbol id="cp-mono" viewBox="0 0 96 96"><path fill="url(#cpAu2)" d="M36.92 65.85Q32.3 65.85 29.38 64.31Q26.47 62.78 25.02 60.28Q23.56 57.79 23.22 54.82Q22.88 51.86 23.4 48.95Q24.13 44.94 26.03 41.82Q27.93 38.7 30.63 36.55Q33.34 34.39 36.51 33.27Q39.68 32.15 42.96 32.15Q45.09 32.15 47.3 32.59Q49.51 33.04 50.7 33.66Q51.07 33.87 51.12 34.08Q51.17 34.28 51.12 34.86L50.29 41.82Q50.29 41.98 50.03 41.98Q49.77 41.98 49.72 41.82Q48.83 37.98 46.52 35.77Q44.2 33.56 40.82 33.56Q37.86 33.56 35.44 35.19Q33.02 36.83 31.44 39.87Q29.85 42.92 29.23 47.23Q28.71 50.77 29.02 53.94Q29.33 57.11 30.37 59.56Q31.41 62 33.15 63.4Q34.9 64.81 37.24 64.81Q39.84 64.81 42.54 62.88Q45.24 60.96 48.26 56.9Q48.42 56.7 48.65 56.83Q48.88 56.96 48.83 57.06L47.43 63.04Q47.27 63.66 47.12 63.9Q46.96 64.13 46.49 64.24Q43.89 65.12 41.42 65.48Q38.95 65.85 36.92 65.85ZM45.52 65.22Q45.42 65.22 45.42 64.91Q45.42 64.6 45.52 64.6Q46.93 64.6 47.68 64.34Q48.44 64.08 48.8 63.3Q49.16 62.52 49.42 61.01L53.69 36.94Q54.1 34.65 53.61 34Q53.12 33.35 51.14 33.35Q50.98 33.35 50.98 33.04Q50.98 32.72 51.14 32.72Q52.28 32.72 53.71 32.8Q55.14 32.88 56.81 32.88Q57.85 32.88 59.67 32.72Q61.49 32.57 63.05 32.57Q66.27 32.57 68.64 33.56Q71 34.54 72.15 36.57Q73.29 38.6 72.72 41.67Q72.25 44.32 70.85 46.19Q69.44 48.06 67.57 49.26Q65.7 50.46 63.67 51.03Q61.64 51.6 59.88 51.6Q59.3 51.6 58.78 51.55Q58.26 51.5 57.74 51.34Q57.64 51.29 57.74 50.77Q57.85 50.25 58.06 50.3Q58.42 50.4 58.81 50.46Q59.2 50.51 59.56 50.51Q61.23 50.51 62.76 49.7Q64.3 48.9 65.44 47.18Q66.58 45.46 67 42.71Q67.47 39.8 66.84 37.79Q66.22 35.79 64.95 34.73Q63.67 33.66 62.06 33.66Q60.97 33.66 60.34 33.92Q59.72 34.18 59.38 34.91Q59.04 35.64 58.73 37.04L54.52 61.01Q54.16 63.25 54.65 63.92Q55.14 64.6 57.07 64.6Q57.22 64.6 57.22 64.91Q57.22 65.22 57.07 65.22Q55.92 65.22 54.42 65.17Q52.91 65.12 51.19 65.12Q49.58 65.12 48.12 65.17Q46.67 65.22 45.52 65.22Z"/></symbol>
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
  <div id="ldr-mark"><svg viewBox="0 0 96 96" aria-hidden="true"><use href="#cp-seal-full"/></svg></div>
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
