<?php
/**
 * Le Comptoir des Parfums — template-commander.php
 *
 * Page commande, servie par functions.php sur /?commander=1.
 * Meme design et meme parcours que commander.html cote maquette :
 * recapitulatif du panier, coordonnees, puis ouverture de WhatsApp avec la
 * commande pre-remplie. Aucun paiement en ligne : tout se regle en especes
 * a la livraison.
 *
 * Le panier lui-meme (etat, totaux, message WhatsApp) vient de panier.js,
 * partage avec la maquette.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cp_home = home_url( '/' );
$cp_wa   = 'https://wa.me/' . comptoir_wa_numero();

get_header();
?>

<?php /* Le CSS de cette page vit dans style-site.css, charge par
        functions.php : une seule copie pour la maquette et le theme. */ ?>

<main id="cp-main" class="ck-root">

  <div class="steps" id="ck-steps">
    <span data-etape="1" class="en-cours"><b>01</b>Panier</span><span data-etape="2"><b>02</b>Coordonnées</span><span data-etape="3"><b>03</b>Confirmation</span>
  </div>
  <h1 class="ck-h">Votre commande</h1>
  <p class="ck-intro">Remplissez vos coordonnées. Vous ne payez rien maintenant : le règlement se fait en espèces, au livreur, à la réception de votre colis.</p>

  <?php comptoir_gages( 'gages-ck' ); ?>


  <!-- Ecran de remerciement : il remplace le formulaire une fois la commande
       deposee. Le panier est vide a ce moment-la, d'ou son propre bloc. -->
  <!-- La commande EST passee quand cet ecran s'affiche : il le dit d'abord,
       puis donne la reference et la suite (un appel). L'ancien ecran
       demandait d'« envoyer le message WhatsApp pour confirmer et verifier
       la disponibilite » : le client croyait sa commande en suspens.
       WhatsApp reste possible, en lien discret, pour une question. -->
  <section class="ck-merci" id="ck-merci" hidden aria-live="polite">
    <h2 id="ck-merci-titre">Commande reçue ✓</h2>
    <p class="ck-merci-ref" id="ck-merci-ref"></p>
    <p class="ck-merci-sous">Nous vous appelons sous peu pour confirmer votre commande et l'adresse de livraison.</p>
    <div class="ck-merci-detail">
      <p class="ck-merci-detail-titre">Ce que vous avez commandé</p>
      <div id="ck-merci-lignes"></div>
    </div>
    <ol class="ck-merci-suite">
      <li><b>Nous vous appelons</b> — pour confirmer la commande et noter l'adresse de livraison.</li>
      <li><b>Nous expédions</b> — Casablanca en 24 à 48 h, le reste du Maroc en 2 à 4 jours.</li>
      <li><b>Vous payez à la remise</b> — en espèces, au livreur. Rien n'est payé à l'avance.</li>
    </ol>
    <div class="ck-merci-actions">
      <a class="ck-merci-retour" href="<?php echo esc_url( home_url( '/#catalogue' ) ); ?>">Retour au catalogue</a>
      <a class="ck-merci-retour" id="ck-merci-wa" href="#" target="_blank" rel="noopener" hidden>Une question sur votre commande ? Écrivez-nous sur WhatsApp</a>
    </div>
    <p class="ck-mini">Un empêchement ? Appelez-nous — <a href="<?php echo esc_url( comptoir_telephone_href() ); ?>"><?php echo esc_html( comptoir_telephone_affiche() ); ?></a>.</p>
  </section>

  <div id="ck-full" hidden>
    <div class="ck-grid">

      <section class="ck-carte ck-carte-panier">
        <h2>Votre panier</h2>
        <div id="ck-items"></div>
        <div id="ck-sums"></div>
        <!-- Les suggestions « deuxieme parfum » vivent avec le panier, pas au
             milieu du formulaire : on les lit en regardant ce qu'on achete,
             sans interrompre la saisie de l'adresse. -->
        <div class="ck-franco" id="ck-franco" hidden></div>
        <a class="ck-add" href="<?php echo esc_url( $cp_home ); ?>#catalogue">＋ Ajouter un autre parfum</a>
      </section>

      <section class="ck-carte">
        <h2>Livraison</h2>
        <form id="ck-form" novalidate>
          <!-- Le telephone d'abord : c'est le champ qui vaut de l'argent. Un
               numero sans adresse se rappelle, une adresse sans numero ne sert
               a rien. -->
          <div class="ck-duo">
            <label class="ck-field" data-f="tel"><span>Téléphone *</span>
              <input type="tel" name="tel" autocomplete="tel" inputmode="tel" enterkeyhint="next" placeholder="06 12 34 56 78" required>
              <small class="fe">Numéro marocain attendu, par exemple 06 12 34 56 78.</small></label>
            <label class="ck-field" data-f="nom"><span>Nom complet *</span>
              <input type="text" name="nom" autocomplete="name" enterkeyhint="next" required>
              <small class="fe">Merci d'indiquer votre nom.</small></label>
          </div>
          <!-- La ville en liste : « Casa », « casablanca », « CASABLANCA » sont
               trois villes differentes pour un transporteur, et une faute de
               saisie coute un colis qui revient. -->
          <label class="ck-field" data-f="ville"><span>Ville *</span>
            <select name="ville" autocomplete="address-level2" required>
              <option value="">Choisissez votre ville</option>
              <option>Agadir</option>
              <option>Al Hoceïma</option>
              <option>Berkane</option>
              <option>Berrechid</option>
              <option>Béni Mellal</option>
              <option>Casablanca</option>
              <option>Dakhla</option>
              <option>El Jadida</option>
              <option>Errachidia</option>
              <option>Essaouira</option>
              <option>Fès</option>
              <option>Guelmim</option>
              <option>Ifrane</option>
              <option>Khouribga</option>
              <option>Kénitra</option>
              <option>Larache</option>
              <option>Laâyoune</option>
              <option>Marrakech</option>
              <option>Meknès</option>
              <option>Mohammedia</option>
              <option>Nador</option>
              <option>Ouarzazate</option>
              <option>Oujda</option>
              <option>Rabat</option>
              <option>Safi</option>
              <option>Salé</option>
              <option>Settat</option>
              <option>Sidi Slimane</option>
              <option>Tanger</option>
              <option>Taza</option>
              <option>Témara</option>
              <option>Tétouan</option>
              <option value="autre">Autre ville…</option>
            </select>
            <small class="fe">Choisissez votre ville dans la liste.</small></label>
          <label class="ck-field" data-f="ville-autre" id="ck-ville-autre" hidden><span>Laquelle ?</span>
            <input type="text" name="ville_autre" autocomplete="address-level2" placeholder="Nom de votre ville">
            <small class="fe">Indiquez le nom de votre ville.</small></label>
          <label class="ck-field" data-f="adresse"><span>Adresse complète *</span>
            <input type="text" name="adresse" autocomplete="street-address" enterkeyhint="send" placeholder="Rue, numéro, immeuble, étage" required>
            <small class="fe">C'est cette adresse que le livreur suivra.</small></label>
        </form>

        <div class="ck-rassure">
          <span class="ck-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24"><rect x="2.5" y="6" width="19" height="13" rx="2"/><path d="M2.5 10h19M6 15h4"/></svg>
          </span>
          <p><b>Paiement à la livraison.</b> Vous réglez en espèces au livreur, à la réception de votre colis. Ce site ne demande aucune carte bancaire.</p>
        </div>
        <div class="ck-rassure">
          <span class="ck-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M1.5 16.5V6.5h12v10M13.5 9.5h4l3 3.5v3.5h-7"/><circle cx="6" cy="17.5" r="2"/><circle cx="17" cy="17.5" r="2"/></svg>
          </span>
          <p><b>Casablanca en 24 à 48 heures.</b> Le reste du Maroc en 2 à 4 jours ouvrables. <?php echo esc_html( comptoir_note_livraison() ); ?></p>
        </div>

        <!-- Un sac, pas la bulle WhatsApp : ce bouton passe la commande, il
             n'ouvre pas une discussion. Meme pictogramme que la commande
             express de la fiche (Comptoir Boutique). -->
        <button class="ck-go" id="ck-go" type="submit" form="ck-form">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1.2 12.1a1 1 0 0 1-1 .9H7.2a1 1 0 0 1-1-.9L5 8z"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8"/></svg>
          <span>Envoyer ma commande</span>
          <!-- Le montant du au livreur, sur le bouton : rien a decouvrir a la porte. -->
          <b class="ck-go-total" data-ck-total></b>
        </button>
        <p class="ck-mini">Rien à payer maintenant. Nous vous appelons pour confirmer, livraison en 24 à 72 h partout au Maroc.<br>
        Une question avant ? <a href="#" id="ck-ask">Écrivez-nous</a></p>
      </section>

    </div>

    <!-- Telephone : le bouton de confirmation est a plus d'un ecran du haut.
         Cette barre le garde sous le pouce tant qu'il est plus bas ; elle se
         retire pendant la saisie (clavier ouvert) et des qu'il est visible.
         Formulaire incomplet : le toucher mene au premier champ a remplir. -->
    <div class="ck-fixe repli" id="ck-fixe" inert>
      <button class="ck-go ck-fixe-go" id="ck-fixe-go" type="button">
        <span>Envoyer ma commande</span>
        <b class="ck-go-total" data-ck-total></b>
      </button>
    </div>
  </div>

  <!-- Panier vide : jusqu'ici la page n'offrait qu'un retour au catalogue.
       Quelqu'un qui arrive ici veut commander MAINTENANT — le renvoyer en
       arriere sans autre issue, c'est le perdre. On lui laisse ecrire. -->
  <div id="ck-empty" class="ck-empty" hidden>
    <p>Votre panier est vide.</p>
    <p class="ck-empty-sous">Dites-nous ce que vous cherchez, on s'occupe du reste — ou choisissez vos parfums dans le catalogue.</p>
    <div class="ck-empty-actions">
      <a class="ck-empty-cta" href="<?php echo esc_url( $cp_wa ); ?>" target="_blank" rel="noopener">
        <?php comptoir_icone_whatsapp(); ?>Commander sur WhatsApp</a>
      <a class="ck-empty-cta ck-empty-cta-g" href="<?php echo esc_url( $cp_home ); ?>#catalogue">Parcourir le catalogue →</a>
    </div>
  </div>

</main>

<?php get_footer(); ?>
