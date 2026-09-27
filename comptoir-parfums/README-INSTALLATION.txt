LE COMPTOIR DES PARFUMS — thème WordPress sur-mesure
=====================================================

VERSIONS 3.x — ÉDITION MAISON
=============================
Même identité — aubergine, or du sceau, Cormorant Garamond + Jost, sceau
CP — avec un fini de maison de parfum. Le CSS nouveau vit en fin de
style-site.css, dans les blocs « ÉDITION MAISON » (un par version et par
page). Les gabarits ont aussi évolué (pied de page, fiche, commande, vente,
404, menu) : le détail est ci-dessous, et l'historique git garde la v2.

VERSION 3.5.2 — MESSAGES EN DARIJA
  Modèles, réponses automatiques et message de test en darija (modèles en
  langue « Arabe » chez Meta) ; plus de façons de répondre comprises
  (« واخا », « صافي », « wa5a », « ما بغيتش », « لالا »…). Guide de
  branchement réécrit, avec la coexistence (garder l'application WhatsApp
  Business sur le même numéro).

VERSION 3.5.1 — RELECTURE DE LA CONFIRMATION WHATSAPP
  - Message de test (CP-TEST) : un appui sur « Je confirme » renvoie
    « ✅ Test réussi » sans toucher aucune commande (avant : pas de réponse,
    et le clic pouvait s'appliquer à une vraie commande du même numéro).
  - Réponses tapées à la main : elles ne comptent que pour une commande qui
    attend sa confirmation. Un « non » écrit plus tard, en réponse à autre
    chose, n'annule plus un colis déjà confirmé (les boutons restent décisifs).
  - « Oui » compris seulement dans un message court : « oui mais je veux
    changer de ville » part en « message à lire » au lieu de confirmer.

VERSION 3.5.0 — CONFIRMATION DES COMMANDES SUR WHATSAPP
  Après chaque commande, le client reçoit un message WhatsApp avec deux
  boutons « ✅ Je confirme » / « ❌ Annuler ». Sans réponse après 2 h (jamais
  la nuit), une relance part ; 24 h après, la commande passe « sans réponse ».
  Le jour de l'expédition, un rappel « votre colis arrive aujourd'hui » part
  depuis la liste des commandes. Les réponses mettent à jour la nouvelle
  colonne « WhatsApp » des commandes. API officielle de Meta (WhatsApp Cloud) :
  pas de risque de blocage du numéro, pas d'abonnement.
  Inactif tant que Réglages > Confirmation WhatsApp n'est pas rempli. Voir la
  partie « CONFIRMATION WHATSAPP — MISE EN ROUTE
=====================================
Environ 30 minutes, une seule fois, depuis un ordinateur. Tout se fait dans
le business Meta « maroc.ea » (celui du compte publicitaire).

0. QUEL NUMÉRO ? (à décider d'abord)
   A. Votre numéro WhatsApp Business actuel, en « coexistence » (conseillé si
      Meta le propose) : vous continuez à répondre aux clients depuis
      l'application WhatsApp Business sur votre téléphone, comme aujourd'hui,
      et le site envoie les confirmations depuis le même numéro. Les clients
      ne voient qu'une seule conversation.
      Conditions : application WhatsApp Business (pas WhatsApp classique) à
      jour, utilisée depuis quelques jours au moins, et ouverte au moins une
      fois toutes les deux semaines pour garder le lien actif.
   B. Un deuxième numéro, réservé au site : le plus simple si la coexistence
      n'est pas proposée. Il ne doit être installé sur aucune application
      WhatsApp. Une carte SIM à 20 DH suffit (il faut juste recevoir le code
      par SMS ou appel une seule fois).

1. CRÉER L'APPLICATION META
   developers.facebook.com > Mes apps > Créer une app. Cas d'usage : « Se
   connecter avec les clients via WhatsApp » ; portefeuille business :
   maroc.ea. Meta ouvre alors l'écran « Démarrage rapide WhatsApp ».

2. AJOUTER LE NUMÉRO
   WhatsApp > Démarrage rapide (ou Configuration de l'API) > « Ajouter un
   numéro de téléphone ».
   - Choix A : quand Meta propose « Connecter votre application WhatsApp
     Business existante », choisissez-le, entrez votre numéro, puis
     confirmez depuis l'application sur le téléphone (un message ou un QR
     code apparaît dans WhatsApp Business > Paramètres). Acceptez le partage
     de l'historique si on vous le demande (facultatif).
   - Choix B : entrez le nouveau numéro, recevez le code par SMS.
   Nom affiché : « Le Comptoir des Parfums » ; catégorie : Commerce de détail.
   Notez l'« Identifiant du numéro de téléphone » (Phone number ID) affiché
   dans Configuration de l'API : il ira dans WordPress.

3. MOYEN DE PAIEMENT
   business.facebook.com > WhatsApp Manager > Paramètres du compte >
   Paiement : ajoutez une carte. Meta facture chaque message de modèle
   (quelques centimes). Les réponses aux clients dans les 24 h qui suivent
   leur message sont gratuites, et vos conversations normales dans
   l'application restent gratuites.

4. JETON PERMANENT
   business.facebook.com > Paramètres > Utilisateurs > Utilisateurs système >
   Ajouter : nom « site », rôle Admin. Puis « Attribuer des éléments » :
   l'app (contrôle total) et le compte WhatsApp (contrôle total).
   « Générer un jeton » : choisissez l'app, expiration « Jamais »,
   autorisations whatsapp_business_messaging et whatsapp_business_management.
   Copiez le jeton tout de suite (il ne sera plus réaffiché).

5. LES TROIS MODÈLES — EN DARIJA
   WhatsApp Manager > Modèles de message > Créer un modèle.
   Catégorie « Utilitaire », langue « Arabe » (Meta n'a pas de langue
   « darija » : la darija s'écrit dans un modèle arabe). Les {{1}}, {{2}}…
   sont des variables ; Meta demande un exemple pour chacune.

   ── Modèle 1 : cp_confirmation_commande ──
   Corps :
     السلام {{1}} 👋
     شكرا على الطلبية ديالك {{2}} عند Le Comptoir des Parfums.

     🧴 {{3}}
     💵 غادي تخلص لمول التوصيل: {{4}}
     📍 العنوان: {{5}}

     واش نصيفطو ليك الطلبية؟
   Boutons « Réponse rapide » ×2, dans cet ordre :
     ✅ أكد الطلبية
     ❌ ألغي
   Exemples : {{1}} Salma · {{2}} CP-260930-1234 · {{3}} 1× Jean Paul
     Gaultier Le Male Elixir · {{4}} 354 درهم · {{5}} 3 av. Mohammed V, Rabat

   ── Modèle 2 : cp_relance_confirmation ──
   Corps :
     السلام {{1}}، الطلبية ديالك {{2}} ({{3}}) كتسنى الجواب ديالك باش تخرج.
     غير ضغط على واحد من الأزرار 👇
   Boutons « Réponse rapide » ×2, dans cet ordre :
     ✅ أكد الطلبية
     ❌ ألغي
   Exemples : {{1}} Salma · {{2}} CP-260930-1234 · {{3}} 354 درهم

   ── Modèle 3 : cp_rappel_livraison ──
   Corps :
     السلام {{1}} 📦 الكوليّة ديالك {{2}} جاية اليوم.
     خلّي التيليفون شاعل، مول التوصيل غادي يعيط ليك.
     المبلغ لي غادي تخلص: {{3}}. شكرا 🙏
   Bouton « Réponse rapide » ×1 :
     👍 مفهوم
   Exemples : {{1}} Salma · {{2}} CP-260930-1234 · {{3}} 354 درهم

   Sans prénom, le site met « عليكم » : le client lit « السلام عليكم ».
   Le premier bouton est toujours la confirmation. Validation : de quelques
   minutes à 24 h. Si un modèle est refusé, envoyez-moi le motif.

   Les réponses automatiques du site sont aussi en darija :
   - après « أكد الطلبية » : « ✅ شكرا Salma، تأكدات الطلبية ديالك CP-… غادي
     نصيفطوها فأقرب وقت، ومول التوصيل غادي يعيط ليك قبل ما يجي. المبلغ لي
     غادي تخلص فالاستلام: 354 درهم. مرحبا بيك 🙏 »
   - après « ألغي » : « تلغات الطلبية ديالك CP-…  إلا كانت غلطة، غير جاوبنا
     هنا ونرجعوها ليك 🙏 »
   Le client peut aussi taper sa réponse : « واخا », « صافي », « wakha »,
   « 1 », « oui »… pour confirmer ; « لا », « ما بغيتش », « 2 », « non »…
   pour annuler. Toute autre phrase arrive en « message à lire ».

6. LE WEBHOOK (pour recevoir les réponses)
   developers.facebook.com > votre app > WhatsApp > Configuration > Webhook >
   Modifier : collez l'« URL du webhook » et le « Jeton de vérification »
   affichés dans WordPress > Réglages > Confirmation WhatsApp, puis
   « Vérifier et enregistrer ». Ensuite « Gérer » : abonnez-vous au champ
   « messages ».
   Récupérez la « Clé secrète » de l'app : Paramètres de l'app > Général >
   Clé secrète > Afficher.
   Passez l'app en mode « Live » (bouton en haut de la page de l'app) ;
   Meta demande une adresse de politique de confidentialité : l'URL de la
   page « Politique de confidentialité » de votre site.

7. DANS WORDPRESS : Réglages > Confirmation WhatsApp
   Identifiant du numéro, jeton permanent, clé secrète ; langue : ar.
   Mettez votre propre numéro dans « Envoyer un test » et enregistrez : le
   message de confirmation (commande fictive CP-TEST) doit arriver sur votre
   téléphone. Appuyez sur « ✅ أكد الطلبية » : la réponse « ✅ التجربة نجحات »
   doit revenir, preuve que le webhook fonctionne. Aucune commande n'est
   touchée. Cochez alors « Activer ».

AU QUOTIDIEN
   - Commandes > colonne WhatsApp : « ✓ confirmée » = à expédier ;
     « en attente » / « sans réponse » = à appeler avant d'expédier ;
     « ✕ annulée » = ne pas expédier (vous recevez aussi un e-mail) ;
     « message à lire » = le client a écrit une question.
   - Le jour de l'expédition : cochez les commandes, Actions groupées >
     « WhatsApp : rappel livraison ». C'est ce message qui réduit le plus
     les colis « pas de réponse » à la livraison.
   - En coexistence, les messages du site et leurs réponses apparaissent
     aussi dans l'application WhatsApp Business : vous pouvez répondre à la
     main à une question, comme d'habitude.
   - Les relances passent par la tâche planifiée de WordPress, qui tourne
     quand le site reçoit des visites. Pour une heure précise, demandez à
     l'hébergeur une tâche cron qui appelle https://votre-site/wp-cron.php
     toutes les 15 minutes.
