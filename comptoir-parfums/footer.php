<?php
/**
 * Le Comptoir des Parfums — footer.php
 *
 * Pied de page de la maquette comptoirv3-motion.html.
 * Les scripts (three.js, GSAP, ScrollTrigger, panier.js, theme.js) sont
 * charges par functions.php via wp_enqueue_script() : ils sortent ici,
 * dans wp_footer(), dans l'ordre impose par leurs dependances.
 */

$cp_wa = 'https://wa.me/' . comptoir_wa_numero();
?>

<footer>
  <div class="foot-inner">
    <span class="foot-logo">
      <svg class="foot-seal" viewBox="0 0 96 96" aria-hidden="true"><use href="#cp-seal"/></svg>
      <span class="foot-wm">
        <span class="foot-wm-serif">Le Comptoir</span>
        <span class="foot-wm-sans">des Parfums</span>
      </span>
    </span>
    <span class="foot-copy">© <?php echo esc_html( gmdate( 'Y' ) ); ?> — <?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></span>
    <nav class="foot-links" aria-label="Liens réseaux sociaux">
      <a href="<?php echo esc_url( $cp_wa ); ?>" target="_blank" rel="noopener">WhatsApp</a>
      <?php if ( comptoir_telephone_href() ) : ?>
      <!-- Tout le monde n'ecrit pas sur WhatsApp : un numero appelable leve le
           doute de celui qui se demande s'il y a quelqu'un derriere le site. -->
      <a href="<?php echo esc_url( comptoir_telephone_href() ); ?>"><?php echo esc_html( comptoir_telephone_affiche() ); ?></a>
      <?php endif; ?>
      <a href="https://www.instagram.com/le_comptoir_parfums" target="_blank" rel="noopener">Instagram</a>
      <a href="https://www.facebook.com/profile.php?id=61573721267555" target="_blank" rel="noopener">Facebook</a>
      <!-- TikTok : remplacer le href par l'URL reelle quand le compte existe.
           Tant qu'il vaut « # » il reste volontairement retire du rendu
           (data-social-todo, voir theme.js) plutot que d'offrir un lien mort. -->
      <a href="#" data-social-todo hidden>TikTok</a>
    </nav>
  </div>
</footer>

<?php if ( ! comptoir_commander_demande() ) : ?>
<!-- Barre d'achat fixe, telephone seulement. Elle sort une fois le heros
     passe et s'efface devant le bloc « Commander maintenant » : voir theme.js. -->
<div class="cta-fixe<?php echo comptoir_est_accueil() ? ' repli' : ''; ?>"<?php comptoir_cta_fixe_attributs(); ?>>
  <a class="cta-fixe-principal" href="<?php echo esc_url( home_url( '/?commander=1' ) ); ?>">
    <span class="cta-fixe-texte">Commander maintenant</span>
    <span class="cta-fixe-note">Paiement à la livraison</span>
  </a>
  <!-- WhatsApp reste a portee de pouce, pour les QUESTIONS : la commande,
       elle, passe par le formulaire, seul chemin qui l'enregistre et la
       mesure. -->
  <a class="cta-fixe-wa" href="<?php echo esc_url( $cp_wa ); ?>" target="_blank" rel="noopener"
     aria-label="Une question ? Écrivez-nous sur WhatsApp"><?php comptoir_icone_whatsapp(); ?></a>
</div>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
