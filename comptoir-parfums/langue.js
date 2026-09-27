/* ══════════════════════════════════════════════════════════════
   LE COMPTOIR DES PARFUMS — bascule français / arabe
   ══════════════════════════════════════════════════════════════

   POURQUOI PAS UNE EXTENSION. Les extensions de traduction lisent le HTML
   envoyé par le serveur. Or le panier, le tunnel de commande, la page de
   remerciement et le message WhatsApp sont écrits en JavaScript : ils
   n'existent pas encore à ce moment-là et seraient restés en français —
   c'est-à-dire exactement les écrans où l'on paie.

   COMMENT ÇA MARCHE. Un dictionnaire de PHRASES, pas de sélecteurs CSS. On
   parcourt les nœuds de texte et on remplace ceux dont le contenu figure au
   dictionnaire. Une phrase déplacée dans le gabarit reste donc traduite ;
   un sélecteur, lui, aurait cessé de correspondre en silence.

   CE QUI RESTE EN LETTRES LATINES. Les noms de parfums et de maisons :
   « Bleu de Chanel » n'a pas de traduction, c'est ce qui est écrit sur le
   flacon que le client recevra. La frontière est posée par ZONES.

   CE QUI SE TRADUIT QUAND MÊME. Les familles olfactives, les concentrations
   et les 236 notes du catalogue : « Floral gourmand », « Eau de Parfum »,
   « Fève tonka » sont du vocabulaire, pas des noms propres. Les familles
   sont recomposées mot à mot, ce qui couvre les 67 combinaisons du
   catalogue avec une trentaine d'entrées.

   LE JAVASCRIPT appelle window.CP_T(), qui retombe sur le français si ce
   fichier n'a pas chargé.
══════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  var CLE = 'cp_langue';

  /* Les noms propres. Un nom de parfum traduit devient introuvable. */
  var ZONES = [
    '.pc-nom', '.pc-maison', '.scent-card-name', '.scent-card-brand',
    '.pf-name', '.pf-brand', '.pnr-name', '.pnr-brand', '.ck-in', '.ck-ib',
    '.h-note-card-name', '.h-note-card-brand', '.h-brand-float',
    '.marquee-sect', '.vitrine-maison', '#cat-maison', '.dist-col-tag',
    '.nl-serif', '.nl-sans', '.pf-signature-maison', '.ldr-serif', '.ldr-sans',
    '.foot-wm-serif', '.foot-wm-sans', '.fin-mark-serif', '.fin-mark-sans',
    '.crumb span', '.foot-copy', 'script', 'style',
    /* Les options sont traitees a part : leur texte EST leur valeur
       quand l'attribut manque, et l'ordre compte. */
    'option'
  ].join(',');

  /* ── Les phrases de l'interface ─────────────────────────────────── */
  var T = {
    /* Navigation, menu, pied de page */
    'Aller au contenu': 'انتقل إلى المحتوى',
    'Notre approche': 'طريقتنا',
    'Sélection': 'المختارات',
    'Catalogue': 'الكتالوج',
    'Commander': 'اطلب الآن',
    'Panier': 'السلة',
    'Panier ·': 'السلة ·',
    'Panier (': 'السلة (',
    'WhatsApp →': 'واتساب ←',
    'Passer': 'تخطّي',
    /* Pied de page, édition v3 */
    "L'art du parfum, sans le prix de la vitrine.": 'فنّ العطر، بلا ثمن الواجهة.',
    'Testeurs originaux des grandes maisons, livrés partout au Maroc. Vous payez à la réception.': 'تستر أصلي من كبرى دور العطور، يوصلك لأي مدينة في المغرب. تدفع عند الاستلام.',
    'La maison': 'الدار',
    'Nous écrire': 'راسلنا',
    'Réponse en moins de 2h': 'نجيبك في أقل من ساعتين',
    'Défiler': 'مرّر للأسفل',
    'Glissez pour tourner la vitrine': 'اسحب لتدوير الواجهة',
    'Paiement à la livraison': 'الدفع عند الاستلام',

    /* Heros. Trois lignes revelees l'une apres l'autre : elles doivent tenir ensemble. */
    'Maroc · Livraison 24h · Paiement à réception': 'المغرب · التوصيل خلال 24 ساعة · الدفع عند الاستلام',
    'Le parfum': 'العطر',
    'que vous voulez.': 'الذي تريده.',
    'Au prix qui reste.': 'بثمن يناسبك.',
    'Testeurs originaux des grandes maisons': 'تستر أصلية من كبريات دور العطور',
    'Les grands parfums de Chanel, Dior, Tom Ford, Xerjoff — livrés chez vous en 24 à 72h, partout au Maroc. Vous payez à la réception. Pas avant.': 'أشهر عطور Chanel و Dior و Tom Ford و Xerjoff — تصلك خلال 24 إلى 72 ساعة، في كل المغرب. تدفع عند الاستلام، لا قبل ذلك.',
    'Voir le catalogue': 'تصفّح الكتالوج',

    /* Heros de la landing page vente (template-vente.php) — meme registre que
       celui de l'accueil ci-dessus, mais un texte a part : elle n'a pas de
       prechargeur pour l'y raccorder par IDs, chaque phrase doit donc avoir
       sa propre entree ici. */
    'Testeurs originaux · Livraison partout au Maroc': 'تستر أصلي · التوصيل إلى كل المغرب',
    'Le parfum que vous voulez.': 'العطر الذي تريده.',
    'Au prix qui vous convient.': 'بثمن يناسبك.',
    'Chanel, Dior, Tom Ford, Xerjoff — testeurs 100 % originaux, le même jus qu\'en boutique, sans le prix de la vitrine. Livrés en 24 à 72h partout au Maroc. Vous payez à la réception, jamais avant.': 'Chanel و Dior و Tom Ford و Xerjoff — تستر 100% أصلية، نفس السائل الذي تجده في المتجر، بلا ثمن الواجهة. تصلك خلال 24 إلى 72 ساعة في كل المغرب. تدفع عند الاستلام، لا قبل ذلك.',
    'Voir la sélection': 'تصفّح الاختيار',
    'Un aperçu du catalogue.': 'لمحة عن الكتالوج.',
    'Comment commander': 'طريقة الطلب',
    'références': 'عطر',
    'maisons': 'دار عطور',
    'avant livraison': 'قبل التوصيل',

    /* Distinction — le testeur face au coffret de boutique */
    'Acheter un parfum au Maroc': 'شراء عطر في المغرب',
    'Le même parfum.': 'نفس العطر.',
    'Sans le prix boutique.': 'بلا ثمن المتجر.',
    'Une seule chose, et elle est authentique : des testeurs officiels. Le flacon que la maison fabrique pour ses propres comptoirs — même jus, même concentration, même tenue que celui du rayon. Ce que vous ne payez pas, c\'est le coffret. Et pas un dirham avant d\'avoir le colis en main.': 'شيء واحد لا غير، وهو أصلي: تستر رسمية. القارورة التي تصنعها الدار لمنافذ بيعها — نفس السائل، نفس التركيز، نفس الثبات كقارورة الرف. ما لا تدفعه هو ثمن العلبة. ولا درهم واحد قبل أن يصل الطرد إلى يدك.',
    'Le flacon de la maison, le jus exact du rayon': 'قارورة الدار نفسها، وسائل الرف عينه',
    'DH, le prix est sur chaque fiche': 'درهم، والسعر مكتوب على كل بطاقة',
    'Vous payez le livreur en main propre, en cash': 'تدفع لعامل التوصيل يدًا بيد، نقدًا',
    'Livré en 24–72 h, de Tanger à Agadir': 'يصلك خلال 24 إلى 72 ساعة، من طنجة إلى أكادير',
    'Le colis ne vous convient pas ? Refusé sans frais, ou remboursé': 'الطرد لا يناسبك؟ ترفضه دون أي رسوم، أو تسترجع مالك',
    'Un doute avant de commander ? Réponse sur WhatsApp': 'عندك شك قبل الطلب؟ الجواب على واتساب',
    'Boutique officielle': 'المتجر الرسمي',
    'Coffret de boutique': 'علبة المتجر',
    'Le même jus, dans un coffret cartonné': 'نفس السائل، داخل علبة كرتونية',
    '1 200 – 4 000 DH le flacon': 'من 1 200 إلى 4 000 درهم للقارورة',
    'En boutique, dans quelques villes seulement': 'في المتجر، وفي بضع مدن فقط',
    'En ligne : avance, douane, semaines d\'attente': 'عبر الإنترنت: تسبيق وجمارك وأسابيع انتظار',
    'Retour rarement accepté une fois ouvert': 'الإرجاع نادرًا ما يُقبل بعد الفتح',
    'Les références rares n\'arrivent pas jusqu\'ici': 'العطور النادرة لا تصل إلى هنا',
    'La vraie question : payez-vous le jus, ou la vitrine ?': 'السؤال الحقيقي: هل تدفع ثمن العطر أم ثمن الواجهة؟',
    'Voir les': 'شاهد',
    'parfums': 'عطر',

    /* Notre selection — les quatre promesses */
    'Ce que vous trouverez': 'ما ستجده هنا',
    'Une sélection, pas un catalogue au hasard.': 'اختيار مدروس، لا كتالوج عشوائي.',
    'Du classique à la niche': 'من الكلاسيكي إلى النيش',
    'Chanel, Dior, Tom Ford, Armani — et Kurkdjian, Xerjoff, Nishane, Parfums de Marly, que presque personne n\'importe au Maroc.': 'Chanel و Dior و Tom Ford و Armani — و Kurkdjian و Xerjoff و Nishane و Parfums de Marly، التي لا يكاد أحد يستوردها إلى المغرب.',
    'Pour elle, pour lui, mixtes': 'لها، له، وللجنسين',
    'Floral, ambré, boisé, aromatique, chypré : toutes les familles sont là, du frais de bureau au sillage de soirée.': 'زهري، عنبري، خشبي، أروماتي، شيبر: كل العائلات موجودة، من المنعش للعمل إلى أثر السهرة.',
    'Le prix du jus': 'ثمن العطر نفسه',
    'Le même parfum qu\'un flacon vendu 1 200 à 4 000 DH. Ce que vous ne payez pas, c\'est la vitrine.': 'نفس العطر الذي تعطيه قارورة بـ1 200 إلى 4 000 درهم. ما لا تدفعه هو ثمن الواجهة.',
    '100 % testeurs': 'كلّها تستر أصلية',
    'Le parfum, pas une approche': 'العطر نفسه، لا ما يشبهه',
    'Chaque flacon sort de la maison qui l\'a créé. Pas une imitation, pas une interprétation : le parfum lui-même, dans son flacon de démonstration.': 'كل قارورة تخرج من الدار التي صنعت العطر. لا تقليد ولا اجتهاد: العطر نفسه، في قارورة العرض.',

    /* Selection du moment */
    'Sélection du moment': 'أبرز العطور',
    'Parcourez par univers olfactif.': 'تصفّح حسب العالم العطري.',
    'Familles olfactives': 'العائلات العطرية',
    'Hommes': 'رجال',
    'Femmes': 'نساء',
    'Niche': 'نيش',
    'Voir la fiche': 'التفاصيل',
    'Voir tous les parfums hommes': 'كل عطور الرجال',
    'Voir tous les parfums femmes': 'كل عطور النساء',
    'Voir toute la niche': 'كل عطور النيش',

    /* Catalogue et filtres */
    'Livraison offerte dès le deuxième parfum.': 'التوصيل مجاني ابتداءً من العطر الثاني.',
    'Catalogue complet': 'الكتالوج الكامل',
    'Tout le catalogue': 'كل الكتالوج',
    'parfums ·': 'عطر ·',
    'maisons.': 'دار عطور.',
    'Filtrez par maison, par destinataire ou par famille olfactive. Chaque flacon a sa fiche : description, pyramide olfactive, prix.': 'صفِّ حسب الدار أو حسب من سيرتديه أو حسب العائلة العطرية. لكل قارورة بطاقتها: الوصف، الهرم العطري، السعر.',
    'DH': 'درهم',
    'Rechercher': 'بحث',
    'Maison': 'الدار',
    'Toutes les maisons': 'كل الدور',
    'Pour': 'لِمَن',
    'Tous': 'الكل',
    'Famille': 'العائلة',
    'Toutes les familles': 'كل العائلات',
    'Trier par': 'ترتيب حسب',
    'Prix croissant': 'السعر تصاعديًا',
    'Prix décroissant': 'السعر تنازليًا',
    'Nom du parfum': 'اسم العطر',
    'Tout afficher': 'عرض الكل',
    'Aucun parfum ne correspond. Essayez le nom de la maison, ou': 'لا يوجد عطر مطابق. جرّب اسم الدار، أو',
    'affichez tout le catalogue': 'اعرض الكتالوج كاملاً',
    'Femme': 'نسائي',
    'Homme': 'رجالي',
    'Mixte': 'للجنسين',

    /* Les quatre etapes */
    'Simple comme un message.': 'بسيط كرسالة.',
    'Choisissez sur WhatsApp': 'اختر عبر واتساب',
    'Parcourez le catalogue et écrivez-nous le parfum voulu. Hésitation ? Dites-nous ce que vous portez déjà.': 'تصفّح الكتالوج واكتب لنا العطر الذي تريده. متردّد؟ قل لنا ما ترتديه الآن.',
    'Confirmation sous 1h': 'تأكيد خلال ساعة',
    'Nous vérifions la disponibilité et confirmons le prix. Aucune avance demandée à cette étape.': 'نتحقّق من التوفّر ونؤكّد السعر. لا تسبيق مطلوب في هذه المرحلة.',
    'Livraison 24–72h': 'التوصيل خلال 24 إلى 72 ساعة',
    'Casablanca, Rabat, Marrakech, Tanger et toutes les villes. Transporteur partenaire suivi.': 'الدار البيضاء، الرباط، مراكش، طنجة وكل المدن. شركة توصيل شريكة مع تتبّع.',
    'Cash à réception': 'الدفع عند الاستلام',
    'Vous payez le livreur en main propre. Si le colis ne convient pas, vous le refusez — et si vous changez d\'avis ensuite, vous êtes remboursé.': 'تدفع لعامل التوصيل يدًا بيد. إذا لم يناسبك الطرد ترفضه — وإذا غيّرت رأيك بعد ذلك، تسترجع مالك.',

    /* Engagements */
    'Nos engagements': 'التزاماتنا',
    'Nos garanties': 'ضماناتنا',
    'Ce que vous ne remettrez jamais en question.': 'ما لن تشكّ فيه أبدًا.',
    'Cash on delivery, sans exception': 'الدفع عند الاستلام، دون استثناء',
    'Aucun virement, aucun acompte. Vous payez uniquement quand le colis est entre vos mains.': 'لا تحويل ولا عربون. تدفع فقط حين يكون الطرد بين يديك.',
    'Satisfait ou remboursé': 'راضٍ أو تسترجع مالك',
    'Refusez le colis au livreur, ou changez d\'avis après l\'avoir ouvert : nous vous remboursons. Sans discussion.': 'ارفض الطرد عند عامل التوصيل، أو غيّر رأيك بعد فتحه: نعيد لك مالك. دون نقاش.',
    'Livraison partout au Maroc': 'التوصيل إلى كل المغرب',
    'De Tanger à Dakhla, du centre-ville aux zones rurales — nos transporteurs couvrent tout le Royaume.': 'من طنجة إلى الداخلة، من وسط المدينة إلى القرى — شركاؤنا في التوصيل يغطّون كل المملكة.',
    'Conseil olfactif offert': 'استشارة عطرية مجانية',
    'Dites-nous ce que vous portez. Nous vous orientons vers le parfum le plus proche de votre sensibilité — gratuitement.': 'قل لنا ما ترتديه. نوجّهك إلى العطر الأقرب إلى ذوقك — مجانًا.',
    'Vous ne savez pas quoi choisir ?': 'لا تعرف ماذا تختار؟',
    'Impossible de sentir un parfum à travers un écran. C\'est pourquoi nous avons mis en place un conseil par messages : dites-nous vos parfums habituels, vos matières préférées (oud, vanille, agrumes, floral, boisé) et nous vous proposons deux ou trois options adaptées.': 'لا يمكن شمّ عطر عبر شاشة. لذلك وضعنا استشارة بالرسائل: قل لنا عطورك المعتادة وموادك المفضّلة (العود، الفانيليا، الحمضيات، الزهري، الخشبي) ونقترح عليك خيارين أو ثلاثة تناسبك.',
    'Tous nos flacons sont des': 'كل قواريرنا',
    'testeurs officiels': 'تستر رسمية',
    ', donc le jus est celui de la boutique, à l\'identique. Notre conseil porte sur le choix du parfum, jamais sur sa fidélité — il n\'y a rien à rapprocher.': '، وبالتالي فالسائل هو سائل المتجر نفسه، بلا فرق. نصيحتنا تنصبّ على اختيار العطر، لا على مدى مطابقته — لا شيء يُقارن.',
    'Écrire sur WhatsApp': 'راسلنا على واتساب',

    /* Chiffres cles */
    'Chiffres clés': 'أرقام أساسية',
    'Références en stock': 'عطر متوفّر',
    'Maisons représentées': 'دار عطور',
    '24h': '24 ساعة',
    'Livraison grandes villes': 'التوصيل في المدن الكبرى',
    'Avance requise': 'تسبيق مطلوب',

    /* Questions frequentes */
    'Questions': 'أسئلة شائعة',
    'Questions fréquentes': 'الأسئلة الشائعة',
    'Ce qu\'on nous demande le plus.': 'أكثر ما يُسأل عنه.',
    'Une réponse manque ? Écrivez-nous directement.': 'لم تجد جوابك؟ راسلنا مباشرة.',
    'Notre équipe répond sur WhatsApp en moins de 2h — conseils, disponibilités, commandes.': 'فريقنا يجيب على واتساب في أقل من ساعتين — نصائح، توفّر، طلبات.',
    'Poser une question': 'اطرح سؤالك',
    'Qu\'est-ce qu\'un testeur, exactement ?': 'ما هو التستر بالضبط؟',
    'Un flacon': 'قارورة',
    'authentique': 'أصلية',
    'de la maison — Chanel, Dior, Tom Ford — qu\'elle fabrique pour faire sentir ses parfums en boutique. Même jus, même concentration, même tenue que le flacon du rayon. Vous recevez le flacon plein et neuf, avec son bouchon, dans le même volume qu\'en boutique. Ce qui change est l\'emballage : une boîte blanche marquée « Tester » au lieu du coffret de luxe. C\'est tout, et c\'est là qu\'est l\'écart de prix.': 'من الدار — Chanel، Dior، Tom Ford — تصنعها لتشميم عطورها في المتجر. نفس السائل، نفس التركيز، نفس الثبات كقارورة الرف. تتوصّل بالقارورة ممتلئة وجديدة، بغطائها، وبنفس الحجم الموجود في المتجر. ما يختلف هو التغليف: علبة بيضاء مكتوب عليها «Tester» بدل علبة الفخامة. هذا كل شيء، ومن هنا يأتي فارق الثمن.',
    'Le parfum est-il identique à celui de la boutique ?': 'هل العطر مطابق لعطر المتجر؟',
    'Oui. Ce n\'est ni une imitation ni une interprétation : c\'est le parfum de la maison, issu de la même production. Même concentration, même tenue, même sillage. Nous ne vendons aucun dupe — si un flacon n\'est pas un testeur officiel, il n\'entre pas au catalogue.': 'نعم. ليس تقليدًا ولا اجتهادًا: هو عطر الدار نفسه، من الإنتاج ذاته. نفس التركيز، نفس الثبات، نفس الأثر. لا نبيع أي عطر مطابق — وإن لم تكن القارورة تستر رسميًا، فلا مكان لها في الكتالوج.',
    'Et si le parfum ne me convient pas ?': 'وإذا لم يعجبني العطر؟',
    'Deux cas. À la porte : vous refusez le colis, il repart avec le livreur, vous ne payez rien. Après l\'avoir ouvert : écrivez-nous et nous vous remboursons. Le risque reste de notre côté jusqu\'à ce que vous soyez satisfait — c\'est ce que veut dire « satisfait ou remboursé ».': 'حالتان. عند الباب: ترفض الطرد فيعود مع عامل التوصيل ولا تدفع شيئًا. بعد فتحه: راسلنا ونعيد لك مالك. المخاطرة تبقى علينا حتى ترضى — هذا معنى «راضٍ أو تسترجع مالك».',
    'Comment choisir sans pouvoir sentir le parfum ?': 'كيف أختار دون أن أشمّ العطر؟',
    'Écrivez-nous les parfums que vous portez actuellement ou que vous aimez. Nous vous orientons vers des testeurs dont les notes partagent le même ADN olfactif, en expliquant les différences. Ce service est gratuit et sans engagement.': 'اكتب لنا العطور التي ترتديها حاليًا أو التي تحبّها. نوجّهك إلى عطور مطابقة أو تستر تشترك نوتاتها في نفس البصمة العطرية، مع شرح الفروق. هذه الخدمة مجانية وبلا التزام.',
    'Livrez-vous partout au Maroc ?': 'هل توصّلون إلى كل المغرب؟',
    'Oui — de Tanger à Dakhla, en passant par toutes les villes et zones rurales. Casablanca, Rabat, Marrakech et les grandes agglomérations sont livrées en 24 à 48h. Le reste du Royaume en 2 à 4 jours ouvrables via nos transporteurs partenaires.': 'نعم — من طنجة إلى الداخلة، مرورًا بكل المدن والقرى. الدار البيضاء والرباط ومراكش والمدن الكبرى خلال 24 إلى 48 ساعة، وباقي المملكة خلال 2 إلى 4 أيام عمل عبر شركائنا في التوصيل.',
    'Les prix affichés sont-ils définitifs ?': 'هل الأسعار المعروضة نهائية؟',
    'Les prix du catalogue sont indicatifs et régulièrement mis à jour. Le prix ferme est confirmé par message au moment de votre commande, selon disponibilité et volume. Aucune surprise à la livraison.': 'أسعار الكتالوج إرشادية وتُحدَّث بانتظام. السعر النهائي يُؤكَّد برسالة عند الطلب، حسب التوفّر والحجم. لا مفاجآت عند التوصيل.',

    /* Bloc final */
    'Commander maintenant': 'اطلب الآن',
    'ou commandez directement sur WhatsApp': 'أو اطلب مباشرة عبر واتساب',
    'Un grand parfum livré chez vous demain.': 'عطر كبير يصلك غدًا.',
    'Choisissez vos flacons, laissez votre adresse, confirmez d\'un message — et payez seulement quand le colis est entre vos mains, en 24 à 72h.': 'اختر قواريرك، اترك عنوانك، أكّد برسالة — وادفع فقط حين يصل الطرد إلى يديك، خلال 24 إلى 72 ساعة.',
    'Parcourir le catalogue': 'تصفّح الكتالوج',
    'Parcourir le catalogue →': 'تصفّح الكتالوج ←',
    'Tout le Maroc': 'كل المغرب',
    'Garanties': 'الضمانات',
    'Liens réseaux sociaux': 'روابط التواصل',

    /* Fiche parfum */
    'Parfum introuvable': 'العطر غير موجود',
    'Retour au catalogue': 'العودة إلى الكتالوج',
    'Fil d\'Ariane': 'مسار التصفّح',
    'Accueil': 'الرئيسية',
    'Le catalogue': 'الكتالوج',
    'Ajouter au panier': 'أضف إلى السلة',
    'Commander sur WhatsApp': 'اطلب عبر واتساب',
    'Confirmation immédiate par WhatsApp': 'تأكيد فوري عبر واتساب',
    'Flacon neuf, jamais utilisé': 'قارورة جديدة، لم تُستعمل قط',
    'Testeur original · 100 % authentique': 'تستر أصلي · أصلي 100%',
    'Rien à payer maintenant · réglez en espèces à la livraison': 'لا شيء تدفعه الآن · ادفع نقدًا عند الاستلام',
    'Le même jus qu\'en boutique, sans le coffret': 'نفس العطر الموجود في المتجر، بدون العلبة',
    'Livré en 24 à 72 h, partout au Maroc': 'يصلك خلال 24 إلى 72 ساعة، في كل المغرب',
    'Une question sur ce parfum ? Écrivez-nous': 'سؤال حول هذا العطر؟ راسلنا',
    'Choisir mon parfum': 'اختر عطري',
    'Un deuxième parfum ? La livraison devient offerte.': 'عطر ثانٍ؟ يصبح التوصيل مجانيًا.',
    'Un deuxième parfum ?': 'عطر ثانٍ؟',
    '+ Ajouter': '+ أضف',
    'Voir plus de parfums': 'شاهد عطورًا أخرى',
    /* Testeur : ce que vous recevez, et l'explication complete (testeur.html) */
    'Ce que vous recevez': 'ما تتوصّل به',
    'Le flacon original, plein et neuf, avec son bouchon': 'القارورة الأصلية، ممتلئة وجديدة، بغطائها',
    'Le même volume que le flacon du rayon': 'نفس حجم قارورة الرف',
    'Une boîte blanche marquée « Tester », au lieu du coffret': 'علبة بيضاء مكتوب عليها «Tester»، بدل علبة الفخامة',
    'Le code de lot de la maison, vérifiable en ligne': 'رمز الدفعة الخاص بالدار، قابل للتحقق عبر الإنترنت',
    'Pourquoi un testeur coûte moins cher': 'لماذا التستر أرخص',
    'Un testeur, c\'est quoi ?': 'ما هو التستر؟',
    'Qu\'est-ce qu\'un testeur ?': 'ما هو التستر؟',
    'Le flacon que la maison fabrique pour faire sentir son parfum en boutique. Même maison, même jus, même flacon. Seul l\'emballage change, et c\'est lui qui fait l\'écart de prix.': 'القارورة التي تصنعها الدار لتشميم عطرها في المتجر. نفس الدار، نفس السائل، نفس القارورة. وحده التغليف يختلف، ومنه يأتي فارق الثمن.',
    'Identique au flacon du rayon': 'مطابق لقارورة الرف',
    'Le jus : même concentration, même tenue, même sillage': 'السائل: نفس التركيز، نفس الثبات، نفس الأثر',
    'Le flacon original, plein et neuf, jamais vaporisé': 'القارورة الأصلية، ممتلئة وجديدة، لم تُرشّ قط',
    'Son bouchon': 'غطاؤها',
    'Le même volume qu\'en boutique': 'نفس الحجم الموجود في المتجر',
    'Le code de lot de la maison': 'رمز الدفعة الخاص بالدار',
    'Ce qui change': 'ما يختلف',
    'Une boîte blanche, sans décor, au lieu du coffret de luxe': 'علبة بيضاء بدون زخرفة، بدل علبة الفخامة',
    'La mention « Tester » sur la boîte ou le flacon': 'عبارة «Tester» على العلبة أو القارورة',
    'Le prix :': 'الثمن:',
    'à': 'إلى',
    'DH, au lieu de 1 200 à 4 000 DH en boutique': 'درهم، بدل 1 200 إلى 4 000 درهم في المتجر',
    'Pourquoi moins cher ?': 'لماذا أرخص؟',
    'Dans le prix d\'un parfum de luxe, vous payez aussi le coffret, la publicité et la vitrine. Un testeur n\'a ni coffret ni vitrine : vous ne payez que le parfum.': 'في ثمن عطر فاخر، تدفع أيضًا العلبة والإشهار والواجهة. التستر بلا علبة فاخرة ولا واجهة: تدفع ثمن العطر فقط.',
    'Comment vérifier ?': 'كيف تتحقق؟',
    'Chaque flacon porte le code de lot de la maison. Tapez-le sur un site de vérification comme CheckFresh : il vous donne sa date de fabrication.': 'كل قارورة تحمل رمز الدفعة الخاص بالدار. اكتبه في موقع تحقق مثل CheckFresh: يعطيك تاريخ صنعها.',
    'Et si ça ne va pas ?': 'وإذا كان هناك مشكل؟',
    'Vous payez à la livraison, jamais avant. Un souci après ouverture ? Écrivez-nous, nous vous remboursons.': 'تدفع عند الاستلام، أبدًا قبل ذلك. مشكل بعد الفتح؟ راسلنا ونعيد لك مالك.',
    'Comment savoir que c\'est un vrai ?': 'كيف أعرف أنه أصلي؟',
    'Chaque flacon porte le code de lot de la maison. Tapez-le sur un site de vérification comme CheckFresh : il vous donne sa date de fabrication. Et vous ne payez qu\'à la livraison, colis en main.': 'كل قارورة تحمل رمز الدفعة الخاص بالدار. اكتبه في موقع تحقق مثل CheckFresh: يعطيك تاريخ صنعها. ولا تدفع إلا عند الاستلام، والطرد بين يديك.',
    'Ils ont reçu leur parfum': 'توصّلوا بعطرهم',
    'Messages et colis de nos clients, tels quels.': 'رسائل وطرود زبائننا، كما هي.',
    'Notes et fiche complète': 'المكوّنات والبطاقة الكاملة',
    'Vous aimerez aussi.': 'قد يعجبك أيضًا.',
    '1 parfum': 'عطر واحد',
    '{n} parfums': '{n} عطور',
    'La pyramide olfactive': 'الهرم العطري',
    'Notes': 'النوتات',
    'Les premières minutes': 'الدقائق الأولى',
    'Après une heure': 'بعد ساعة',
    'Des heures durant': 'لساعات طويلة',
    'Tête': 'المقدّمة',
    'Cœur': 'القلب',
    'Fond': 'القاعدة',
    'La fiche': 'البطاقة',
    'Famille olfactive': 'العائلة العطرية',
    'Concentration': 'التركيز',
    'Genre': 'النوع',
    'Présentation': 'التقديم',
    'Prix': 'السعر',
    'Testeur original': 'تستر أصلي',
    'Testeur original, flacon neuf': 'تستر أصلي، قارورة جديدة',
    'Dans la même maison': 'من الدار نفسها',
    'Voir tout le catalogue': 'شاهد الكتالوج كاملاً',

    /* Tiroir du panier */
    'Votre panier': 'سلّتك',
    'Fermer le panier': 'إغلاق السلة',
    'Ouvrir le panier': 'فتح السلة',
    'Ouvrir le menu': 'فتح القائمة',
    'Basculer en mode clair': 'التبديل إلى الوضع الفاتح',
    'Basculer en mode sombre': 'التبديل إلى الوضع الداكن',
    'Navigation principale': 'التنقّل الرئيسي',
    'Menu': 'القائمة',
    'Chargement': 'جارٍ التحميل',
    'Retirer un': 'إنقاص واحد',
    'Ajouter un': 'إضافة واحد',
    'Votre panier est vide.': 'سلّتك فارغة.',

    /* Tunnel de commande */
    'Coordonnées': 'معلوماتك',
    'Confirmation': 'التأكيد',
    'Votre commande': 'طلبك',
    'Remplissez vos coordonnées. Vous ne payez rien maintenant : le règlement se fait en espèces, au livreur, à la réception de votre colis.': 'املأ معلوماتك. لا تدفع شيئًا الآن: الدفع نقدًا لعامل التوصيل، عند استلام طردك.',
    '＋ Ajouter un autre parfum': '＋ أضف عطرًا آخر',
    'Livraison': 'التوصيل',
    'Téléphone *': 'الهاتف *',
    'Numéro marocain attendu, par exemple 06 12 34 56 78.': 'رقم مغربي، مثال: 06 12 34 56 78.',
    'Nom complet *': 'الاسم الكامل *',
    'Merci d\'indiquer votre nom.': 'المرجو كتابة اسمك.',
    'Ville *': 'المدينة *',
    'Choisissez votre ville': 'اختر مدينتك',
    'Autre ville…': 'مدينة أخرى…',
    'Choisissez votre ville dans la liste.': 'اختر مدينتك من القائمة.',
    'Laquelle ?': 'أيّ مدينة؟',
    'Indiquez le nom de votre ville.': 'اكتب اسم مدينتك.',
    'Adresse complète *': 'العنوان الكامل *',
    'C\'est cette adresse que le livreur suivra.': 'هذا هو العنوان الذي سيقصده عامل التوصيل.',
    'Paiement à la livraison.': 'الدفع عند الاستلام.',
    'Vous réglez en espèces au livreur, à la réception de votre colis. Ce site ne demande aucune carte bancaire.': 'تدفع نقدًا لعامل التوصيل عند استلام طردك. هذا الموقع لا يطلب أي بطاقة بنكية.',
    'Casablanca en 24 à 48 heures.': 'الدار البيضاء خلال 24 إلى 48 ساعة.',
    'Confirmer la commande': 'أكّد الطلب',
    'WhatsApp s\'ouvre avec votre commande et votre adresse déjà écrites. Un dernier envoi et c\'est confirmé.': 'يفتح واتساب ومعه طلبك وعنوانك مكتوبان مسبقًا. إرسال أخير ويكون الطلب مؤكَّدًا.',
    'Une question avant ?': 'سؤال قبل ذلك؟',
    'Écrivez-nous': 'راسلنا',
    'Dites-nous ce que vous cherchez, on s\'occupe du reste — ou choisissez vos parfums dans le catalogue.': 'قل لنا ما تبحث عنه ونتكفّل بالباقي — أو اختر عطورك من الكتالوج.',

    /* Page de remerciement */
    'Merci, c\'est noté.': 'شكرًا، تمّ تسجيل طلبك.',
    'Votre commande est arrivée chez nous. Un message WhatsApp vous attend : un appui et nous préparons votre colis. Vous ne l\'avez pas reçu ? Le bouton ci-dessous fait la même chose.': 'وصلنا طلبك. رسالة واتساب في انتظارك: ضغطة واحدة ونبدأ تحضير طردك. لم تصلك؟ الزر أسفله يقوم بنفس الشيء.',
    'Ce que vous avez commandé': 'ما طلبته',
    'Vous confirmez sur WhatsApp': 'تؤكّد عبر واتساب',
    '— le message est déjà écrit, un appui suffit.': '— الرسالة مكتوبة سلفًا، ضغطة واحدة تكفي.',
    'Nous expédions': 'نرسل الطرد',
    '— Casablanca en 24 à 48 h, le reste du Maroc en 2 à 4 jours.': '— الدار البيضاء خلال 24 إلى 48 ساعة، وباقي المغرب خلال 2 إلى 4 أيام.',
    'Vous payez à la remise': 'تدفع عند التسليم',
    '— en espèces, au livreur. Rien n\'est payé à l\'avance.': '— نقدًا لعامل التوصيل. لا شيء يُدفع مسبقًا.',
    'Votre récapitulatif est prêt. Envoyez le message sur WhatsApp pour confirmer votre demande et vérifier la disponibilité.': 'ملخص طلبك جاهز. أرسل الرسالة عبر واتساب لتأكيد طلبك والتحقق من توفر العطر.',
    'WhatsApp ne s\'est pas ouvert ? Le bouton ci-dessus renvoie le même message. Pour vérifier la réception de votre demande, contactez-nous —': 'لم يفتح واتساب؟ الزر أعلاه يرسل نفس الرسالة. للتأكد من استلام طلبك، اتصل بنا —',
    'Renvoyer la commande sur WhatsApp': 'أعد إرسال الطلب عبر واتساب',
    'WhatsApp ne s\'est pas ouvert ? Le bouton ci-dessus renvoie le même message. Votre commande est déjà chez nous dans tous les cas —': 'لم يفتح واتساب؟ الزر أعلاه يرسل نفس الرسالة. طلبك وصلنا على كل حال —',

    /* Concentrations, lues sur le flacon */
    'Eau de Parfum': 'أو دو بارفان',
    'Eau de Toilette': 'أو دو تواليت',
    'Parfum': 'بارفان',
    'Eau de Parfum Intense': 'أو دو بارفان إنتنس',
    'Extrait de Parfum': 'إكستريه دو بارفان',
    'Elixir': 'إليكسير',
    'Esprit de Parfum': 'إسبري دو بارفان',
    'Parfum Intense': 'بارفان إنتنس',
    'Eau Fraîche': 'أو فريش',
    'Eau pour la nuit': 'عطر الليل',
    'Eau de Toilette Intense': 'أو دو تواليت إنتنس'
  };

  /* ── Les notes olfactives, telles qu'elles sortent de produits.php ── */
  var NOTES = {
    'Bergamote':'برغموت', 'Vanille':'فانيليا', 'Musc':'مسك', 'Patchouli':'باتشولي',
    'Fève tonka':'حبّة التونكا', 'Fleur d\'oranger':'زهر البرتقال', 'Jasmin':'ياسمين',
    'Ambre':'عنبر', 'Rose':'ورد', 'Bois de santal':'خشب الصندل',
    'Poivre rose':'فلفل وردي', 'Cèdre':'أرز', 'Bois ambré':'خشب عنبري',
    'Lavande':'خزامى', 'Cardamome':'هيل', 'Vétiver':'فيتيفر', 'Néroli':'نيرولي',
    'Musc blanc':'مسك أبيض', 'Poire':'كمثرى', 'Santal':'صندل', 'Mandarine':'مندرين',
    'Iris':'سوسن', 'Gingembre':'زنجبيل', 'Cassis':'كشمش أسود', 'Cuir':'جلد',
    'Cannelle':'قرفة', 'Sauge':'مريمية', 'Bois de cèdre':'خشب الأرز', 'Poivre':'فلفل',
    'Tubéreuse':'توبيروز', 'Citron':'ليمون', 'Encens':'لبان',
    'Pamplemousse':'جريب فروت', 'Géranium':'إبرة الراعي', 'Safran':'زعفران',
    'Menthe':'نعناع', 'Bois blond':'خشب أشقر', 'Pivoine':'فاوانيا', 'Miel':'عسل',
    'Benjoin':'لبان جاوي', 'Caramel':'كراميل', 'Notes marines':'نفحات بحرية',
    'Ambre gris':'عنبر رمادي', 'Oud':'عود', 'Jasmin sambac':'ياسمين سامباك',
    'Pomme':'تفاح', 'Bois de gaïac':'خشب الغاياك', 'Muguet':'زنبق الوادي',
    'Ambroxan':'أمبروكسان', 'Cacao':'كاكاو', 'Amande':'لوز',
    'Ylang-ylang':'إيلنغ إيلنغ', 'Orange':'برتقال', 'Tonka':'تونكا',
    'Ananas':'أناناس', 'Mousse de chêne':'طحلب البلوط',
    'Feuille de violette':'ورقة البنفسج', 'Notes vertes':'نفحات خضراء',
    'Orange sanguine':'برتقال أحمر', 'Genièvre':'عرعر', 'Châtaigne':'كستناء',
    'Tabac':'تبغ', 'Vanille de Madagascar':'فانيليا مدغشقر', 'Litchi':'ليتشي',
    'Noix de coco':'جوز الهند', 'Prune':'برقوق', 'Rose de Grasse':'ورد غراس',
    'Magnolia':'ماغنوليا', 'Bois de rose':'خشب الورد', 'Café':'قهوة',
    'Bois de cachemire':'خشب الكشمير', 'Freesia':'فريزيا', 'Poivre noir':'فلفل أسود',
    'Gardénia':'غاردينيا', 'Praline':'برالين', 'Rose de Turquie':'ورد تركي',
    'Toffee':'توفي', 'Cédrat':'أترج', 'Rose de Mai':'ورد مايو',
    'Réglisse':'عرق السوس', 'Bergamote de Calabre':'برغموت كالابريا',
    'Poivre de Sichuan':'فلفل سيشوان', 'Citron de Sicile':'ليمون صقلية',
    'Violette':'بنفسج', 'Framboise':'توت العليق', 'Vanille bourbon':'فانيليا بوربون',
    'Cerise noire':'كرز أسود', 'Rose de Damas':'ورد دمشقي', 'Rhum':'روم',
    'Fruits rouges':'فواكه حمراء', 'Labdanum':'لادانوم', 'Aldéhydes':'ألدهيدات',
    'Bouleau':'بتولا', 'Ambrette':'أمبريت', 'Cèdre de Virginie':'أرز فرجينيا',
    'Muscade':'جوزة الطيب', 'Accord panettone':'نفحة البانيتوني',
    'Rose blanche':'ورد أبيض', 'Héliotrope':'هليوتروب', 'Suède':'جلد شمواه',
    'Mandarine verte':'مندرين أخضر', 'Romarin':'إكليل الجبل',
    'Fleur de gardénia':'زهرة الغاردينيا', 'Frangipanier':'فرانجيباني',
    'Thé noir':'شاي أسود', 'Pomme verte':'تفاح أخضر', 'Bois santal':'خشب الصندل',
    'Absinthe':'أفسنتين', 'Fleur de cerisier':'زهر الكرز',
    'Bois blonds':'أخشاب شقراء', 'Rose de Bulgarie':'ورد بلغاري',
    'Osmanthus':'أوسمانثوس', 'Musc noir':'مسك أسود', 'Datura':'داتورا',
    'Épices':'توابل', 'Amande amère':'لوز مرّ', 'Fleur de vanille':'زهرة الفانيليا',
    'Notes glacées':'نفحات مثلّجة', 'Bois':'أخشاب', 'Cumin':'كمّون',
    'Fleur de gingembre':'زهرة الزنجبيل', 'Liqueur de gingembre':'خلاصة الزنجبيل',
    'Vanille caramel':'فانيليا كراميل', 'Vanille absolue':'خلاصة الفانيليا',
    'Myrtille':'توت أزرق', 'Mûre':'توت أسود', 'Jacinthe d\'eau':'ياقوتية الماء',
    'Bois de teck':'خشب الساج', 'Coing':'سفرجل', 'Jacinthe':'ياقوتية',
    'Narcisse':'نرجس', 'Rose de Taïf':'ورد الطائف', 'Styrax':'ميعة', 'Abricot':'مشمش',
    'Carvi':'كراوية', 'Anis étoilé':'يانسون نجمي', 'Piment':'فلفل حارّ',
    'Pastèque':'بطّيخ أحمر', 'Fleurs blanches':'زهور بيضاء',
    'Pomme Granny Smith':'تفاح غراني سميث', 'Cloche de bruyère':'زهرة الخلنج',
    'Bambou':'خيزران', 'Cerise griotte':'كرز حامض', 'Lait':'حليب', 'Sucre':'سكّر',
    'Jasmin indien':'ياسمين هندي', 'Nectar de cassis':'رحيق الكشمش الأسود',
    'Cardamome noire':'هيل أسود', 'Lys ginger':'زنبق الزنجبيل',
    'Bois noirs':'أخشاب داكنة', 'Sucre brun':'سكّر بنّي',
    'Fleur de chèvrefeuille':'زهرة صريمة الجدي', 'Racine d\'iris':'جذر السوسن',
    'Lavande de Carla':'خزامى كارلا', 'Vanille de Tahiti':'فانيليا تاهيتي',
    'Coumarine':'كومارين', 'Silex':'حجر الصوّان', 'Œillet':'قرنفل',
    'Fleur de sel':'زهرة الملح', 'Meringue':'مرينغ', 'Accord tiaré':'نفحة التياري',
    'Gingembre confit':'زنجبيل مسكّر', 'Accord metal chaud':'نفحة معدن ساخن',
    'Fleur de miel':'زهرة العسل', 'Cire d\'abeille':'شمع العسل',
    'Sucre glace':'سكّر ناعم', 'Pomme rouge':'تفاح أحمر',
    'Fleur de café':'زهرة البنّ', 'Ambre brun':'عنبر بنّي', 'Sucre candi':'سكّر نبات',
    'Guimauve':'مارشميلو', 'Pistache':'فستق', 'Crème glacée':'مثلّجات',
    'Fleur de tiaré':'زهرة التياري', 'Framboise noire':'توت عليق أسود',
    'Cactus':'صبّار', 'Vanille noire':'فانيليا سوداء',
    'Accord ambre gris':'نفحة العنبر الرمادي', 'Résine de sapin':'راتنج التنّوب',
    'Chewing-gum':'علكة', 'Pétales de rose':'بتلات الورد',
    'Baies roses':'حبّ الفلفل الوردي', 'Bois précieux':'أخشاب ثمينة',
    'Fleur de datura':'زهرة الداتورا', 'Fleur de tubéreuse':'زهرة التوبيروز',
    'Rose bulgare':'ورد بلغاري', 'Nèfle':'زعرور', 'Rose turque':'ورد تركي',
    'Bois de oud':'خشب العود', 'Cachemire':'كشمير', 'Rhubarbe':'راوند',
    'Fleur de coton':'زهرة القطن', 'Accord métallique':'نفحة معدنية',
    'Menthe glaciale':'نعناع مثلّج', 'Bois aromatiques':'أخشاب عطرية',
    'Ambre doré':'عنبر ذهبي', 'Résines':'راتنجات', 'Pêche sanguine':'خوخ أحمر',
    'Cognac':'كونياك', 'Truffe':'كمأة', 'Orchidée noire':'أوركيد أسود',
    'Fleurs de fruits':'أزهار الفاكهة', 'Accord cuir':'نفحة جلدية',
    'Liqueur de cerise':'خلاصة الكرز', 'Baume du Pérou':'بلسم البيرو',
    'Baies rouges':'توت أحمر',
    'Chèvrefeuille':'زهر العسل',
    'Clémentine':'كليمنتين',
    'Cuir blond':'جلد فاتح',
    'Cyclamen':'بخور مريم',
    'Figue':'تين',
    'Iris poudré':'سوسن بودري',
    'Mandarine de Sicile':'مندرين صقلّي',
    'Mandarine rouge':'مندرين أحمر',
    'Menthe poivrée':'نعناع فلفلي',
    'Musc rouge':'مسك أحمر',
    'Noisette':'بندق',
    'Notes aquatiques':'نفحات مائية',
    'Pamplemousse rose':'جريب فروت وردي',
    'Papyrus':'بردي',
    'Poivre du Sichuan':'فلفل سيشوان',
    'Bois flotté':'خشب طافٍ', 'Sel':'ملح', 'Palissandre':'خشب الورد الهندي',
    'Feuille de tabac':'ورق التبغ', 'Fruits secs':'فواكه مجفّفة',
    'Bois secs':'أخشاب جافّة', 'Feuilles de figuier':'أوراق التين', 'Myrte':'آس',
    'Grenade':'رمّان', 'Yuzu':'يوزو', 'Lotus':'لوتس',
    'Bois d\'acajou':'خشب الماهوغني', 'Cèdre bleu':'أرز أزرق',
    'Fruit de la passion':'فاكهة الباشن', 'Baies de Goji':'توت غوجي',
    'Orchidée vanille':'أوركيد الفانيليا', 'Nectarine':'نكتارين',
    'Fruits siciliens':'فواكه صقلية', 'Fruits blancs':'فواكه بيضاء',
    'Notes gourmandes':'نفحات حلوة', 'Galbanum':'جلبانوم',
    'Notes pétillantes':'نفحات فوّارة', 'Cerise':'كرز',
    'Lavande glacée':'خزامى مثلّجة', 'Bois clairs':'أخشاب فاتحة',
    'Vanille ambrée':'فانيليا عنبرية', 'Fraise':'فراولة',
    'Bois de patchouli':'خشب الباتشولي', 'Sauge sclarée':'مريمية مسكية',
    'Marron glacé':'كستناء مسكّرة', 'Notes lactées':'نفحات حليبية'
  };

  /* ── Les mots dont sont faites les familles olfactives ──────────────
     « Floral fruité » n'est pas une entrée : c'est « Floral » + « fruité ».
     Trente mots couvrent les soixante-sept familles du catalogue, et la
     soixante-huitième se traduira toute seule. */
  var FAM = {
    'Floral':'زهري', 'Ambré':'عنبري', 'Boisé':'خشبي', 'Aromatique':'أروماتي',
    'Hespéridé':'حمضي', 'Chypré':'شيبر', 'Fruité':'فاكهي', 'Gourmand':'حلواني',
    'Oriental':'شرقي', 'Cuir':'جلدي', 'floral':'زهري', 'ambré':'عنبري',
    'boisé':'خشبي', 'aromatique':'أروماتي', 'fruité':'فاكهي', 'épicé':'توابلي',
    'gourmand':'حلواني', 'vanillé':'فانيلي', 'musqué':'مسكي', 'oriental':'شرقي',
    'blanc':'أبيض', 'aquatique':'مائي', 'fougère':'فوجير', 'tabac':'تبغي',
    'chypré':'شيبر', 'aldéhydé':'ألدهيدي', 'rosé':'وردي', 'lacté':'حليبي',
    'capiteux':'كثيف', 'minéral':'معدني', 'vert':'أخضر', 'métallique':'حديدي',
    'miel':'عسلي', 'frais':'منعش', 'poudré':'بودري', 'marin':'بحري', 'cuir':'جلدي'
  };

  /* ── Les villes de la liste déroulante ─────────────────────────────
     Le nom français reste la VALEUR envoyée au carnet de commandes : le
     registre ne doit pas changer de langue selon le client qui commande. */
  var VILLES = {
    'Agadir':'أكادير', 'Al Hoceïma':'الحسيمة', 'Berkane':'بركان', 'Berrechid':'برشيد',
    'Béni Mellal':'بني ملال', 'Casablanca':'الدار البيضاء', 'Dakhla':'الداخلة',
    'El Jadida':'الجديدة', 'Errachidia':'الرشيدية', 'Essaouira':'الصويرة',
    'Fès':'فاس', 'Guelmim':'كلميم', 'Ifrane':'إفران', 'Khouribga':'خريبكة',
    'Kénitra':'القنيطرة', 'Larache':'العرائش', 'Laâyoune':'العيون',
    'Marrakech':'مراكش', 'Meknès':'مكناس', 'Mohammedia':'المحمدية', 'Nador':'الناظور',
    'Ouarzazate':'ورزازات', 'Oujda':'وجدة', 'Rabat':'الرباط', 'Safi':'آسفي',
    'Salé':'سلا', 'Settat':'سطات', 'Sidi Slimane':'سيدي سليمان', 'Tanger':'طنجة',
    'Taza':'تازة', 'Témara':'تمارة', 'Tétouan':'تطوان'
  };

  /* ── Textes d'attributs : champs vides et libellés d'accessibilité ── */
  var ATTRS = {
    'Un parfum, une maison…': 'عطر أو دار عطور…',
    '06 12 34 56 78': '06 12 34 56 78',
    'Rue, numéro, immeuble, étage': 'الشارع، الرقم، العمارة، الطابق',
    'Nom de votre ville': 'اسم مدينتك',
    'Le parfum que vous voulez. Au prix qui reste.': 'العطر الذي تريده. بثمن يناسبك.',
    'Le Comptoir des Parfums — accueil': 'Le Comptoir des Parfums — الرئيسية',
    'Maisons représentées — raccourci vers le catalogue': 'دور العطور — اختصار نحو الكتالوج',
    'Sélection par famille olfactive': 'اختيار حسب العائلة العطرية'
  };

  /* ── Les phrases fabriquées par le JavaScript, via window.CP_T() ──── */
  var MOTS = {
    'iris':'سوسن',
    'tendre':'رقيق',
    'Sous-total': 'المجموع الفرعي',
    'Livraison': 'التوصيل',
    'Total': 'المجموع',
    'Total à payer au livreur': 'المبلغ الذي تدفعه لعامل التوصيل',
    'offerte': 'مجاني',
    'à confirmer': 'يُحدَّد لاحقًا',
    'Retirer': 'حذف',
    'Paiement à la livraison, en espèces. Aucune carte bancaire.': 'الدفع عند الاستلام، نقدًا. لا حاجة لبطاقة بنكية.',
    'Ajoutez un second parfum : la livraison passe à 0 DH.': 'أضف عطرًا ثانيًا ويصبح التوصيل مجانيًا.',
    'Ajoutez un second parfum et la livraison passe à <b>0 DH</b>. ': 'أضف عطرًا ثانيًا ويصبح التوصيل مجانيًا. ',
    'Voir le catalogue': 'تصفّح الكتالوج',
    'Votre panier est vide.': 'سلّتك فارغة.',
    'Parcourir le catalogue': 'تصفّح الكتالوج',
    'DH': 'درهم',
    'Livraison offerte.': 'التوصيل مجاني.',
    '{nom} — ajouté au panier': '{nom} — أُضيف إلى السلة',
    'Merci {prenom}, c\'est noté.': 'شكرًا {prenom}، تمّ تسجيل طلبك.',
    'Merci, c\'est noté.': 'شكرًا، تمّ تسجيل طلبك.',
    'Référence {ref}': 'رقم الطلب {ref}',
    'Bonjour Le Comptoir des Parfums, je souhaite commander :': 'السلام عليكم، أريد الطلب من Le Comptoir des Parfums:',
    'Commande': 'الطلب',
    'Nom': 'الاسم',
    'Téléphone': 'الهاتف',
    'Ville': 'المدينة',
    'Adresse': 'العنوان',
    'à préciser lors de l\'appel de confirmation': 'يُحدَّد أثناء مكالمة التأكيد',
    'Paiement à la livraison.': 'الدفع عند الاستلام.'
  };

  /* ── Les phrases où un chiffre change ──────────────────────────────
     On traduit autour du nombre, jamais le nombre. Écrire « 35 DH » en dur
     dans une traduction, c'est mentir le jour où les frais changent. */
  var PATRONS = [
    [/^(.+) DH — paiement à la réception$/, '$1 درهم — الدفع عند الاستلام'],
    [/^(.*\d) DH$/, '$1 درهم'],
    [/^Sinon (\d+) DH, partout au Maroc\.$/, 'وإلا $1 درهم، في كل المغرب.'],
    [/^(\d+) parfums?$/, '$1 عطر'],
    [/^Voir les (\d+) parfums$/, 'شاهد $1 عطر'],
    [/^(\d+) maisons$/, '$1 دار عطور'],
    [/^(\d+) parmi (\d+)$/, '$1 من $2'],
    [/^(\d+) maisons, de (.+) à (.+) DH\. Chaque flacon a sa fiche complète : pyramide olfactive, prix, disponibilité\.$/, '$1 دار عطور، من $2 إلى $3 درهم. لكل قارورة بطاقتها الكاملة: الهرم العطري، السعر، التوفر.'],
    [/^Le reste du Maroc en 2 à 4 jours ouvrables\. (\d+) DH de livraison, partout — offerte dès le deuxième parfum\.$/, 'باقي المغرب خلال 2 إلى 4 أيام عمل. التوصيل بـ$1 درهم في كل المغرب — مجاني ابتداءً من العطر الثاني.'],
    [/^(\d+) DH de livraison, partout\.$/, 'التوصيل بـ$1 درهم في كل المغرب.'],
    [/^Frais de livraison confirmés à la commande\.$/, 'تُحدَّد رسوم التوصيل عند الطلب.']
  ];

  /* ══════════════════════════════════════════════════════════════
     ÉTAT
  ══════════════════════════════════════════════════════════════ */
  function lire() {
    var url = new URLSearchParams(location.search).get('lang');
    if (url === 'ar' || url === 'fr') { ecrire(url); return url; }
    try { return localStorage.getItem(CLE) === 'ar' ? 'ar' : 'fr'; } catch (e) { return 'fr'; }
  }
  function ecrire(l) { try { localStorage.setItem(CLE, l); } catch (e) {} }

  var langue = lire();

  window.CP_LANGUE = function () { return langue; };

  /* La ville, pour l'AFFICHAGE seulement. Ce qui part au carnet de commandes
     et sur WhatsApp reste le nom français : le registre ne change pas de
     langue selon le client, sinon deux lignes « Casablanca » et
     « الدار البيضاء » ne se trient plus ensemble. */
  window.CP_VILLE = function (fr) {
    return (langue === 'ar' && VILLES[fr]) ? VILLES[fr] : fr;
  };
  window.CP_T = function (fr, remplacements) {
    var t = (langue === 'ar' && MOTS[fr]) ? MOTS[fr] : fr;
    if (remplacements) {
      Object.keys(remplacements).forEach(function (k) {
        t = t.replace('{' + k + '}', remplacements[k]);
      });
    }
    return t;
  };

  /* ══════════════════════════════════════════════════════════════
     TRADUCTION D'UNE CHAÎNE
     Cinq essais, du plus précis au plus général. null = on ne touche pas.
  ══════════════════════════════════════════════════════════════ */
  function famille(s) {
    var mots = s.split(' ');
    var out = [];
    for (var i = 0; i < mots.length; i++) {
      if (!FAM[mots[i]]) { return null; }
      out.push(FAM[mots[i]]);
    }
    return out.join(' ');
  }

  function traduit(s, prefereFamille) {
    if (prefereFamille && FAM[s]) { return FAM[s]; }
    if (T[s] != null) { return T[s]; }
    if (NOTES[s] != null) { return NOTES[s]; }

    /* « Eau de Parfum · Femme », « Bergamote · Vanille · Musc » : chaque
       segment se traduit seul, et l'ensemble ne passe que s'ils passent tous
       — une liste à moitié traduite serait pire que pas traduite du tout. */
    if (s.indexOf(' · ') > 0) {
      var bouts = s.split(' · ');
      var faits = [];
      for (var i = 0; i < bouts.length; i++) {
        var b = traduit(bouts[i]);
        if (b == null) { return null; }
        faits.push(b);
      }
      return faits.join(' · ');
    }

    var f = famille(s);
    if (f) { return f; }

    for (var j = 0; j < PATRONS.length; j++) {
      if (PATRONS[j][0].test(s)) { return s.replace(PATRONS[j][0], PATRONS[j][1]); }
    }
    return null;
  }

  /* ══════════════════════════════════════════════════════════════
     UN NOMBRE NE SE LIT PAS DE DROITE A GAUCHE
     « 1 399 » et « 0717 961 180 » sont ecrits par groupes. Les espaces qui
     les separent sont neutres : dans une phrase arabe ils retombent au sens
     de la phrase, et chaque groupe repart de son cote. Le prix s'affiche
     « 399 1 », le total « 798 2 », le telephone « 961 0717 180 ».
     Mesure sur la page, pas suppose — et vu par un client avant nous.
     On isole donc le nombre entier en lecture latine (U+2066 … U+2069).
  ══════════════════════════════════════════════════════════════ */
  var GROUPES = /\d+(?:[\u0020\u00a0\u202f]\d+)+/g;
  function isole(s) {
    if (s.indexOf('\u2066') >= 0) { return s; }   /* deja isole */
    return s.replace(GROUPES, function (nb) { return '\u2066' + nb + '\u2069'; });
  }
  /* Pour les prix que le JavaScript fabrique : panier.js les passe par ici. */
  window.CP_NOMBRE = function (s) {
    return langue === 'ar' ? isole(String(s)) : String(s);
  };

  /* L'espace insécable et les retours à la ligne du gabarit ne font pas
     partie de la phrase : on les met de côté, on traduit, on les remet. */
  var BORDS = /^(\s*)([\s\S]*?)(\s*)$/;
  function traduitNoeud(n) {
    var m = BORDS.exec(n.nodeValue);
    if (!m || !m[2]) { return; }
    /* \s couvre l'espace insécable : « Panier ·  » et « Panier · »
       arrivent tous deux au dictionnaire sous la même clef. */
    var cle = m[2].replace(/\s+/g, ' ');
    var ar = traduit(cle);
    if (ar != null) { n.nodeValue = m[1] + isole(ar) + m[3]; return; }
    /* Rien a traduire, mais le texte peut porter un nombre a isoler : le
       telephone du pied de page n'est dans aucun dictionnaire. On reecrit
       alors m[2] tel quel, pas la clef normalisee, pour ne pas ecraser les
       espaces d'origine d'un texte qu'on ne traduit pas. */
    var iso = isole(m[2]);
    if (iso !== m[2]) { n.nodeValue = m[1] + iso + m[3]; }
  }

  /* ══════════════════════════════════════════════════════════════
     TRADUCTION D'UN MORCEAU DE PAGE
  ══════════════════════════════════════════════════════════════ */
  function traduire(racine) {
    if (langue !== 'ar' || !racine) { return; }

    if (racine.nodeType === 3) {
      var pere = racine.parentElement;
      if (!pere || !pere.closest(ZONES)) { traduitNoeud(racine); }
      return;
    }
    if (racine.nodeType !== 1 && racine.nodeType !== 9 && racine.nodeType !== 11) { return; }

    if (racine.querySelectorAll) { attributs(racine); }

    var marcheur = document.createTreeWalker(racine, NodeFilter.SHOW_TEXT, {
      acceptNode: function (n) {
        if (!n.nodeValue || !n.nodeValue.trim()) { return NodeFilter.FILTER_REJECT; }
        var p = n.parentElement;
        if (p && p.closest(ZONES)) { return NodeFilter.FILTER_REJECT; }
        return NodeFilter.FILTER_ACCEPT;
      }
    });

    /* On collecte avant d'écrire : modifier l'arbre pendant qu'on le
       parcourt, c'est s'exposer à le parcourir deux fois. */
    var noeuds = [], n;
    while ((n = marcheur.nextNode())) { noeuds.push(n); }
    noeuds.forEach(traduitNoeud);
  }

  function attributs(racine) {
    racine.querySelectorAll('[placeholder]').forEach(function (el) {
      var v = ATTRS[el.placeholder] || T[el.placeholder];
      if (v) { el.placeholder = v; }
    });
    racine.querySelectorAll('[aria-label]').forEach(function (el) {
      var a = el.getAttribute('aria-label');
      var v = ATTRS[a] != null ? ATTRS[a] : T[a];
      if (v) { el.setAttribute('aria-label', v); }
    });

    /* Les options méritent un passage à part : le texte d'une option EST sa
       valeur quand l'attribut manque. Traduire « Casablanca » sans fixer la
       valeur ferait entrer « الدار البيضاء » dans le carnet de commandes,
       et le registre changerait de langue selon le client. */
    var famSel = racine.querySelector ? racine.querySelector('#cat-famille') : null;
    racine.querySelectorAll('option').forEach(function (o) {
      var brut = o.textContent.trim();
      if (!brut) { return; }
      if (!o.hasAttribute('value')) { o.value = brut; }
      var v = VILLES[brut];
      if (v == null) { v = traduit(brut, famSel ? famSel.contains(o) : false); }
      if (v != null) { o.textContent = v; }
    });
  }

  /* ══════════════════════════════════════════════════════════════
     MISE EN PLACE
  ══════════════════════════════════════════════════════════════ */
  function appliquer() {
    document.documentElement.setAttribute('data-lang', langue);
    document.documentElement.setAttribute('lang', langue === 'ar' ? 'ar' : 'fr-FR');
    document.documentElement.setAttribute('dir', langue === 'ar' ? 'rtl' : 'ltr');
    if (langue !== 'ar') { return; }

    traduire(document.body);
    description();

    /* Le panier, le tunnel et la page de remerciement se dessinent après
       coup : on retraduit ce qui vient d'apparaître, et rien d'autre. */
    if (!window.MutationObserver) { return; }
    new MutationObserver(function (mutations) {
      mutations.forEach(function (m) {
        m.addedNodes.forEach(traduire);
      });
    }).observe(document.body, { childList: true, subtree: true });
  }

  /* La description du parfum vient de langue-parfums.js, chargé sur les
     seules fiches. Elle est repérée par le slug du bouton « Ajouter au
     panier » : le texte français, lui, changerait à la première relecture. */
  function description() {
    var el = document.querySelector('.pf-desc');
    var bouton = document.querySelector('[data-panier-add]');
    if (!el || !bouton || !window.CP_DESCRIPTIONS) { return; }
    var ar = window.CP_DESCRIPTIONS[bouton.getAttribute('data-panier-add')];
    if (ar) { el.textContent = ar; }
  }

  function brancher() {
    document.querySelectorAll('[data-langue-bascule]').forEach(function (b) {
      b.textContent = langue === 'ar' ? 'FR' : 'ع';
      b.setAttribute('aria-label', langue === 'ar' ? 'Passer en français' : 'التبديل إلى العربية');
      b.addEventListener('click', function () {
        ecrire(langue === 'ar' ? 'fr' : 'ar');
        /* Rechargement plutôt que bascule à chaud : le panier, le tunnel et
           la confirmation se reconstruisent proprement, sans état bâtard. */
        var u = new URL(location.href);
        u.searchParams.delete('lang');
        location.href = u.toString();
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { appliquer(); brancher(); });
  } else {
    appliquer();
    brancher();
  }
})();
