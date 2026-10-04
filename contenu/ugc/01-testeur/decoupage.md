# UGC 01 · « Le testeur, c'est quoi ? »

La méthode de la capture : un découpage plan par plan, un prompt autonome par
plan, des plans de 8 s, le même style en tête de chaque prompt, puis la voix et
le montage.

Ce qui change par rapport à la capture :
- **Génération des plans :** Arcads « Omni Flash », le même modèle que Google Flow.
- **Voix off :** synthèse vocale Arcads en darija, à la place d'ElevenLabs.
- **Montage :** ffmpeg, à la place de CapCut.

Format : vertical 9:16, 4 plans de 8 s, voix off en darija, sous-titres.

**Honnêteté :** les flacons ne portent aucune marque, et personne dans la vidéo
ne se présente comme un client. La voix est celle de la boutique, qui explique
ce qu'est un testeur. Ce n'est pas un faux avis. À la publication, activer
l'étiquette « Info IA » sur Instagram et Meta.

## Style commun (en tête de chaque prompt)

Authentic UGC smartphone footage, vertical 9:16, handheld with slight natural
shake, filmed on an iPhone in a modern Moroccan apartment in Casablanca, warm
evening daylight mixed with soft lamp light, realistic skin texture, natural
colours with a warm amber tone, shallow depth of field, no on-screen text, no
logos, no brand names, all perfume bottles and boxes completely unbranded,
no dialogue, ambient room sound only.

## Plans

| Plan | Durée | Image | Voix off (darija) |
|---|---|---|---|
| 1 | 8 s | Porte d'appartement, un livreur tend un petit colis, une main paie en espèces | شحال من واحد كيسولنا: واش التيستور أصلي؟ وما كتخلص حتى يوصلك الكوليس لعندك |
| 2 | 8 s | Sur un lit, on ouvre le colis : une boîte blanche, on sort un flacon plein | التيستور هو القنينة اللي كتصاوبها الماركة باش الناس يشمو فالبوتيك. نفس العطر، غير بلا العلبة |
| 3 | 8 s | Gros plan : un doigt incline le flacon, un code gravé sous la base | تحت القنينة كاين الكود. دخلو ف CheckFresh وتعرف تاريخ الصنع |
| 4 | 8 s | Devant un miroir, on vaporise le cou et on attrape sa veste pour sortir | كوموندي من comptoirparfums.com، والخلاص عند الاستلام |

## Résultat

- **`ugc-01-testeur.mp4` :** la vidéo finale de 24 s en 720×1280, avec voix off et sous-titres en darija intégrés à l'image.
- **`plans/p1.mp4` à `p4.mp4` :** les plans bruts de 8 s générés par Omni Flash, à remonter autrement si besoin.
- **`voix.wav` :** la voix off (voix « Khalid », Arcads).
- **`subs.ass` :** les sous-titres, à corriger puis à réincruster si besoin.

Montage : les plans 1 à 3 sont pris de 1,5 s à 7,5 s, le plan 4 de 0 à 6 s.
La voix off démarre à 1 s, et le son d'ambiance est gardé à 20 %.

Coût : 4 plans × 190 crédits + 8 crédits de voix, soit 768 crédits Arcads.
