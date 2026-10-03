/* ══════════════════════════════════════════════════════════════
   LE COMPTOIR DES PARFUMS — les 177 descriptions, en arabe
   ══════════════════════════════════════════════════════════════

   Ce fichier n'est chargé QUE sur une fiche parfum. Il pèse le poids d'un
   catalogue entier de phrases, et la page d'accueil n'en affiche aucune :
   l'y joindre aurait fait payer à chaque visiteur un texte qu'il ne lira
   jamais — cher, pour une clientèle majoritairement en 4G.

   La clef est le slug, pas la phrase française. Corriger une virgule dans
   produits.php ne doit pas faire disparaître la traduction en silence.

   langue.js lit ce tableau et remplace le paragraphe de la fiche. Absent,
   la description reste en français : rien ne casse.
══════════════════════════════════════════════════════════════ */
window.CP_DESCRIPTIONS = {
  /* Azzaro */
  'azzaro--forever-wanted-elixir': 'توت العليق المنعش فوق الخزامى والهيل، على قاعدة من الجلد ونجيل الهند: عطر قوي وأنيق.',
  'azzaro--the-most-wanted': 'زنجبيل مُعتَّق يذوب في كراميل خشبي، دافئ ولا تملّ منه.',

  /* Burberry */
  'burberry--goddess': 'ثلاث فانيليات متراكبة، تلطّفها خزامى حليبية ولمسة كاكاو.',
  'burberry--her-intense': 'عاصفة من الفواكه السوداء المحلّاة على قاعدة عنبرية وباتشولي.',

  /* Carolina Herrera */
  'carolina-herrera--good-girl-blush': 'زهر برتقال مشمس يستقرّ على لوز كريمي وأخشاب فاتحة.',

  /* Chanel */
  'chanel--allure-homme-sport': 'خشبي صافٍ ومضيء، حمضيات وفلفل على أثر مسكي هادئ.',
  'chanel--bleu-de-chanel-lexclusif': 'بصمة Bleu في أقصى درجاتها: لبان وصندل وتونكا بتركيز عالٍ.',
  'chanel--bleu-de-chanel-parfum': 'خشبي أروماتي عميق يغمرك، صندل كريمي وقاعدة تونكا.',
  'chanel--chance-eau-fraiche': 'أكثر إصدارات Chance خضرةً وحيوية، حمضيات متفجّرة على خشب فاتح.',
  'chanel--chance-eau-tendre': 'زهري فاكهي ناعم وبودري، جريب فروت غضّ ومسك حريري.',
  'chanel--coco-mademoiselle': 'الشيبر العصري بامتياز: ورد حيّ، باتشولي صافٍ، وأثر جريء.',
  'chanel--coco-noir': 'شرقي داكن وساتان، زهور ملفوفة بالباتشولي واللبان.',
  'chanel--n-5-eau-de-parfum': 'أشهر باقة مجرّدة في العالم، ألدهيدات وزهور بيضاء بودرية.',

  /* Chloé */
  'chloe--love-story': 'زهري أبيض مضيء ونظيف، زهر برتقال على قاعدة أرز ناعمة.',

  /* Creed */
  'creed--absolu-aventus': 'Aventus أكثف وأكثر راتنجية، الأناناس المميّز ملفوف بالعنبر والفانيليا.',
  'creed--aventus': 'أشهر أناناس مدخّن في عالم العطور، رجولي وفاكهي ومعدني.',

  /* Dior */
  'dior--ambre-nuit-esprit-de-parfum': 'وردة متبّلة تذوب في عنبر رمادي معدني ومدخّن.',
  'dior--dior-homme-intense': 'سوسن بودري يكاد يكون مساحيق تجميل، يشدّه أرز عميق وخيط كاكاو.',
  'dior--fahrenheit-le-parfum': 'بنفسج Fahrenheit البترولي في نسخة أكثر تدخينًا وجلدية وراتنجية.',
  'dior--hypnotic-poison-edp': 'لوز وفانيليا مُنوّمان وحليبيان، داكنان ومُحتضِنان في آن.',
  'dior--joy-by-dior-intense': 'باقة من غراس متوهّجة، يلطّفها صندل كريمي ومسك.',
  'dior--miss-dior-eau-de-parfum': 'قلب من الورد الغضّ والفاوانيا، عصري ونظيف ومضيء.',
  'dior--miss-dior-parfum': 'وردة Miss Dior يثقلها التوبيروز وتستقرّ على قاعدة فانيلية.',
  'dior--miss-dior-rose-essence': 'وردة خضراء لاذعة، كأنها قُطفت في الحديقة عند الفجر.',
  'dior--oud-ispahan-esprit-de-parfum': 'وردة داكنة وعود مدخّن، فخمان يكادان يكونان طقسيَّين.',
  'dior--sauvage-eau-de-parfum': 'خزامى وأمبروكسان Sauvage في نسخة أنعم وأكثر استدارة وفانيلية.',
  'dior--sauvage-elixir': 'تركيز كثيف ومفلفل، خزامى مُعتَّقة على أخشاب عميقة.',
  'dior--sauvage-parfum': 'الوجه الأدفأ لـSauvage: تونكا وصندل وفانيليا، بأثر مكتوم.',

  /* Dolce & Gabbana */
  'dolce-gabbana--devotion-pour-homme': 'حمضيات حيّة تنزلق نحو نفحة بريوش وقهوة بالفانيليا.',
  'dolce-gabbana--king-edt': 'فوجير مشمس ورجولي، حمضيات غضّة على أخشاب جافّة وفيتيفر.',
  'dolce-gabbana--limperatrice-royale': 'بطّيخ L\'Imperatrice الشهير، أكثف وأكثر زهرية وثباتًا.',
  'dolce-gabbana--light-blue-capri-in-love': 'Light Blue كما عرفته، بدرجة أنضج وأكثر احتضانًا.',
  'dolce-gabbana--light-blue-eau-de-toilette': 'عطر الصيف المتوسطي: ليمون وتفاح أخضر وخشب أشقر.',
  'dolce-gabbana--light-blue-pour-homme-edt': 'النسخة الرجالية من Light Blue، حمضيات منعشة على أخشاب مفلفلة.',
  'dolce-gabbana--my-devotion-edp-intense': 'حلوى إيطالية في قارورة: بريوش مسكّر وزهر برتقال وفانيليا.',
  'dolce-gabbana--q-by-dolce-gabbana': 'كرز حامض ولذيذ على قلب من الياسمين، شابّ ونابض.',

  /* Emporio Armani */
  'emporio-armani--stronger-with-you-absolutely': 'الوجه الأشدّ في هذه العائلة: قهوة مُرّة وتوفي وخشب عنبري.',
  'emporio-armani--stronger-with-you-amber-edp': 'عنبر عسلي ودافئ، هيل في المقدّمة وكستناء محمّصة في القاعدة.',
  'emporio-armani--stronger-with-you-intensely': 'Stronger With You الأصلي مُثقَّل بالتوفي والقرفة والتونكا.',
  'emporio-armani--stronger-with-you-oud-edp': 'عود هادئ ونظيف، زعفران في المقدّمة وفانيليا وكستناء تُدوّره.',
  'emporio-armani--stronger-with-you-parfum': 'نسخة البارفان، أكثر جفافًا وخشبية، كستناء وجلد شمواه.',
  'emporio-armani--stronger-with-you-sandalwood': 'صندل كريمي في القلب، يحيط به الهيل والفانيليا الناعمة.',
  'emporio-armani--stronger-with-you-tobacco': 'تبغ عسلي وحلو، دافئ أكثر منه داكن، على قاعدة فانيلية.',

  /* Giardini di Toscana */
  'giardini-di-toscana--bianco-latte': 'نفحة حليب وسكّر تبعث على الطمأنينة، كبشرة نظيفة ومحلّاة.',

  /* Giorgio Armani */
  'giorgio-armani--acqua-di-gio-edp': 'المائي المرجعي في نسخة أعمق، ملح البحر على خشب ولبان.',
  'giorgio-armani--armani-prive-bleu-lazuli-edp': 'زهر برتقال متبّل ومشمس يستقرّ على لبان جاوي عنبري.',
  'giorgio-armani--armani-prive-rouge-malachite': 'خشبي أخضر وحيّ، حمضيات مقرمشة على باتشولي صافٍ ولبان جاوي.',
  'giorgio-armani--my-way-edp': 'زهري أبيض عصري وصافٍ، زهر برتقال وتوبيروز على مسك.',
  'giorgio-armani--my-way-intense': 'My Way الأصلي تدفّئه الفانيليا والتونكا، أكثر إثارة في السهرة.',
  'giorgio-armani--my-way-ylang': 'تنويعة حول الإيلنغ إيلنغ، كريمية وفيها لمسة موز زهرية.',
  'giorgio-armani--si-fiori': 'أكثر إصدارات Sì زهرية، ورد ونيرولي على قاعدة كشمش أسود.',
  'giorgio-armani--si-parfum': 'كشمش Sì المُعتَّق بتركيز أقصى، مستدير مع باتشولي.',
  'giorgio-armani--si-passione': 'زهري فاكهي نابض، وردة متوهّجة على كمثرى غضّة وفانيليا.',
  'giorgio-armani--si-passione-eclat-de-parfum': 'Sì Passione بنكهة أكثر فوّارية، توت عليق حامض وفاوانيا غضّة.',

  /* Givenchy */
  'givenchy--gentleman-reserve-privee': 'فانيليا بوربون مدخّنة، كاكاو مُرّ وجلد، داكنة وأنيقة.',
  'givenchy--irresistible-edt': 'وردة غضّة وكمثرى مقرمشة، نظيفتان وسهلتا الارتداء.',
  'givenchy--linterdit-absolu-edp-intense': 'زهور L\'Interdit البيضاء مُثقَّلة بالفانيليا والتونكا.',
  'givenchy--linterdit-edp-rouge': 'توبيروز يعضّه الزنجبيل والقرفة، دافئ وفيه شيء من السمّ.',
  'givenchy--linterdit-edp-rouge-ultime': 'L\'Interdit Rouge مدفوعًا أبعد، زعفران ولبان على زهور متوهّجة.',
  'givenchy--linterdit-tubereuse-noire': 'توبيروز داكن وراتنجي، ليليّ تقريبًا، على أخشاب مدخّنة.',

  /* Gucci */
  'gucci--flora-gorgeous-gardenia': 'غاردينيا محلّاة وبودرية، أنثوية جدًا، على لمسة سكّر بنّي.',
  'gucci--flora-gorgeous-gardenia-intense': 'Gardenia في نسخة أكثف وأكثر فانيلية، بأثر أدفأ.',
  'gucci--flora-gorgeous-jasmine': 'ياسمين صافٍ وفوّار، لا يثقل أبدًا، على قاعدة صندل ناعمة.',
  'gucci--flora-gorgeous-magnolia': 'ماغنوليا منعشة وفاكهية بالكشمش الأسود، شابّة ومضيئة.',
  'gucci--gucci-bloom-intense': 'جدار من الزهور البيضاء الخضراء المُسكِرة، توبيروز في المقدّمة.',
  'gucci--moonlight-serenade': 'ورد وبرقوق مخمليّان وليليّان، على باتشولي فانيلي.',

  /* Guerlain */
  'guerlain--habit-rouge-rouge-prive': 'أناقة الجلد والفانيليا في Habit Rouge، متبّلة بالزعفران والقرفة.',
  'guerlain--la-petite-robe-noire-edp': 'كرز أسود مُعتَّق وبرالين، أنيق ومشاغب.',
  'guerlain--mon-guerlain': 'خزامى ملطّفة وفانيليا تاهيتي كريمية، عصري ومشمس.',

  /* Hermès */
  'hermes--terre-dhermes-edp-intense': 'نفحة التراب والحمضيات والصوّان في Terre d\'Hermès، أكثف وأكثر فيتيفر.',

  /* Hugo Boss */
  'hugo-boss--boss-bottled-elixir': 'Boss Bottled كما تعرفه، مشدود ومفلفل، بأثر خشبي جافّ.',
  'hugo-boss--boss-bottled-unlimited': 'تفاح وقرفة Boss Bottled ينشّطهما زنجبيل فوّار.',
  'hugo-boss--bottled': 'خشبي التفاح المثالي للعمل: صافٍ ودافئ ويرضي الجميع.',
  'hugo-boss--bottled-night': 'النسخة الليلية من Bottled، أكثر جفافًا وجلدية بالبتولا.',

  /* Jean Paul Gaultier */
  'jean-paul-gaultier--gaultier-divine-edp': 'زهري ألدهيدي سماوي يذوب في مرينغ مالح ومسك ناعم.',
  'jean-paul-gaultier--la-belle-le-parfum': 'La Belle مُدفَّأة: كمثرى مُعتَّقة وفانيليا كثيفة على أخشاب محمّصة.',
  'jean-paul-gaultier--le-beau-le-parfum': 'جوز هند خشبي ومشمس، رجولي، على أرز عميق.',
  'jean-paul-gaultier--le-male-elixir': 'حرارة متوهّجة تلتقي فيها الخزامى الذائبة بالعسل والتبغ.',
  'jean-paul-gaultier--le-male-elixir-absolu': 'إليكسير مغناطيسي بنوتات عميقة وحلوة، عسل وتبغ في القاعدة.',
  'jean-paul-gaultier--le-male-le-parfum': 'نعناع وفانيليا Le Male تتحوّل إلى عنبر ناعم وهيل.',
  'jean-paul-gaultier--le-male-lover': 'زنجبيل مسكّر وغضّ ينزلق نحو فانيليا خشبية لا تُقاوَم.',
  'jean-paul-gaultier--scandal-edp-pour-femme': 'عسل زهري فخم، باتشولي صافٍ، وأثر قويّ ومحلّى.',
  'jean-paul-gaultier--scandal-le-parfum': 'Scandal متمركزًا حول الفانيليا والكراميل، أكثر استدارة وأقلّ عسلية.',
  'jean-paul-gaultier--scandal-pour-homme-absolu': 'أروماتي بين البرودة والدفء، أعشاب خضراء في المقدّمة وفانيليا وتونكا في القاعدة.',
  'jean-paul-gaultier--scandal-pour-homme-intense': 'Scandal الرجالي مُثقَّل بالعسل والقرفة، أدكن وأحلى.',

  /* Kayali */
  'kayali--capri-lemon-sugar-14': 'ليمون مسكّر ومحلّى، حلو لكنه مضيء، كليمونتشيلو مثلّج.',
  'kayali--deja-vu-white-flower-57': 'باقة زهور بيضاء كريمية على عنبر ناعم ومسكي.',
  'kayali--eden-juicy-apple-01': 'تفاح أحمر مقرمش وغضّ على قلب زهري هادئ.',
  'kayali--eden-sparkling-lychee-39': 'ليتشي فوّار ووردي، منعش ولذيذ، سهل الارتداء جدًا.',
  'kayali--lovefest-burning-cherry-48': 'كرز أسود مدخّن وبرالين، داكن ولا يُقاوَم، وفيه شيء من السمّ.',
  'kayali--marrakesh-orange-blossom-24': 'زهر برتقال فخم وعسلي، دافئ كمساء مغربي.',
  'kayali--oudgasm-rose-oud-16-intense': 'وردة دمشقية داكنة تلتفّ حول عود بالزعفران والعنبر.',
  'kayali--vanilla-28': 'فانيليا Kayali الشهيرة: خشبية، فيها لمسة كحولية، ولا تُتخم أبدًا.',
  'kayali--vanilla-candy-rock-sugar-42': 'حلوى بالفانيليا وسكّر النبات، حلوة بلا مواربة.',
  'kayali--yum-boujee-marshmallow-81': 'مارشميلو طريّ ومحلّى، دفء حلو منثور عليه الفانيليا.',
  'kayali--yum-pistachio-gelato-33': 'مثلّجات فستق كريمية ومالحة قليلًا، حلوى في قارورة.',
  'kayali--utopia-vanilla-coco-21': 'جوز هند كريمي ومشمس على فانيليا خشبية برائحة العطلة.',

  /* Lacoste */
  'lacoste--booster-edt': 'ضربة نعناع منعش وتفاح أخضر، نشيطة وبلا مقدّمات.',
  'lacoste--essential-edt': 'توت عليق أسود خشبي وفيه خضرة، هادئ ونظيف.',

  /* Lancôme */
  'lancome--idole-lintense': 'وردة Idôle النظيفة تدفّئها فانيليا كثيفة وباتشولي صافٍ.',
  'lancome--la-nuit-tresor-le-parfum': 'وردة سوداء بالبرالين ومُعتَّقة، شديدة التركيز، أثر ليليّ.',
  'lancome--la-vie-est-belle-lextrait': 'سوسن وبرالين La Vie est Belle في أقصى درجات الكثافة.',
  'lancome--tresor-in-love-edp': 'زهري ناعم وربيعي، ورد وفاوانيا على كمثرى غضّة.',

  /* Louis Vuitton */
  'louis-vuitton--imagination': 'حمضيات وشاي مضيئان وأنيقان، زنجبيل حيّ على عنبر حريري.',

  /* Maison Francis Kurkdjian */
  'maison-francis-kurkdjian--baccarat-rouge-540': 'أشهر نفحة حلوة معدنية اليوم، زعفران وخشب عنبري.',
  'maison-francis-kurkdjian--oud-silk-mood-extrait-de-parfum': 'ورد على ورد وعود ناعم كالحرير، فخمان وهادئان.',

  /* Moschino */
  'moschino--toy-2-bubble-gum': 'نفحة علكة صريحة، فاكهية وردية وطفولية، لا تأخذ نفسها على محمل الجدّ.',
  'moschino--toy-boy': 'وردة مفلفلة وخشبية، أنيقة وجريئة، يرتديها رجل.',

  /* Narciso Rodriguez */
  'narciso-rodriguez--fleur-musc-for-her': 'وردة مسكية وبودرية، دافئة على البشرة، هادئة وثابتة.',
  'narciso-rodriguez--for-her': 'مسك Narciso الشيبري المميّز، مثير وداكن ومدخّن قليلًا.',
  'narciso-rodriguez--for-her-edp': 'نسخة البارفان من المسك الشهير، أكثف وأكثر زهرية وعمقًا.',
  'narciso-rodriguez--musc-noir-for-her': 'مسك أسود أكثر امتلاءً وفاكهية بالبرقوق، دافئ ومُخدِّر قليلًا.',
  'narciso-rodriguez--musc-noir-rose-for-her': 'Musc Noir في نسخة ورد وكمثرى، أكثر زهرية وإضاءة.',
  'narciso-rodriguez--narciso-edp-poudree': 'توبيروز بودري وكريمي، ناعم جدًا، يكاد يكون مستحضر تجميل.',
  'narciso-rodriguez--narciso-edp-rouge': 'مسك أكثر خشبية وبنية، ورد وأرز، بأثر واضح.',

  /* Nishane */
  'nishane--hacivat': 'أناناس خشبي وراقٍ، في روح Aventus لكن أكثر خضرة وجفافًا.',

  /* Parfums de Marly */
  'parfums-de-marly--delina-exclusif': 'ورد وليتشي Delina في نسخة أكثر فانيلية واحتضانًا، شديدة التركيز.',
  'parfums-de-marly--layton': 'تفاح وخزامى أنيقان على فانيليا كريمية، متعدّد الاستعمال وراقٍ.',
  'parfums-de-marly--oriana-royal-essence': 'فاكهي وردي وحليبي، عصري ومريح، حول زهرة القطن.',
  'parfums-de-marly--palatine': 'توبيروز وبرقوق فخمان، شيبر عصري بأثر أرستقراطي.',

  /* Prada */
  'prada--carbon-luna-rossa-edt': 'أروماتي بارد ومعدني، خزامى على نفحة معدن وباتشولي.',
  'prada--paradigme': 'زهري مشمس ونظيف، نيرولي مضيء ملفوف بمسك ناعم.',
  'prada--paradoxe-intense': 'زهر برتقال Paradoxe مُثقَّل بالفانيليا والعنبر، أكثر ليلية.',
  'prada--paradoxe-radical-essence': 'نيرولي صافٍ ومسكي، بسيط ونظيف، أثر كبشرة ثانية.',

  /* Rabanne */
  'rabanne--1-million-parfum': '1 Million الأصلي في نسخة أكثر جلدية وتوابل، بأثر ضخم.',
  'rabanne--1-million-royal': 'نعناع مثلّج يصطدم بعنبر ذهبي ضخم، منعش ثم دافئ.',

  /* Stéphane Humbert Lucas 777 */
  'stephane-humbert-lucas-777--god-of-fire': 'كشمش وكمثرى فخمان يذوبان في صندل عنبري بالغ الكريمية.',

  /* Tom Ford */
  'tom-ford--bitter-peach': 'خوخ مُعتَّق وكحولي، فخم ولزج، Tom Ford بامتياز.',
  'tom-ford--black-orchid': 'زهري داكن وسامّ، كمأة وشوكولاتة، أيقونة الليل.',
  'tom-ford--fabulous': 'جلد كريمي ولوز، دافئ وحيواني، أنيق ومستفزّ.',
  'tom-ford--lost-cherry': 'كرز مسكّر وخلاصته، برالين وداكن، لا يُقاوَم أبدًا.',
  'tom-ford--neroli-portofino': 'نيرولي وحمضيات في قمّة النظافة، البحر المتوسط في قارورة فاخرة.',
  'tom-ford--oud-minerale': 'عود يودي ومالح، عكس المتوقّع: الصحراء تلتقي المحيط.',
  'tom-ford--oud-wood': 'باب الدخول إلى عود النيش: مدخّن لكنه ناعم، تونكا وفانيليا تروّضانه.',
  'tom-ford--rose-de-russie': 'وردة جلدية ومفلفلة، كثيفة ومُسكِرة، داكنة وشهوانية.',
  'tom-ford--rose-prick-edp': 'ثلاث ورود شائكة، مفلفلة وترابية، حديقة برّية بأشواكها.',
  'tom-ford--tobacco-vanille': 'تبغ أشقر غارق في الفانيليا والفواكه المسكّرة، دفء صالون جلدي.',
  'tom-ford--vanilla-sex': 'فانيليا بجلد الشمواه وبودرية، دافئة وشهوانية، بشرة ثانية بلا مواربة.',

  /* Valentino */
  'valentino--born-in-roma-extradose': 'Born in Roma النسائي مشبَّع بالفانيليا والياسمين، بأثر كثيف.',
  'valentino--donna-born-in-roma-intense': 'ياسمين وكشمش مضيئان ينزلقان نحو فانيليا بوربون كريمية.',
  'valentino--uomo-born-in-roma-green-stravaganza': 'أخضر حيّ ونباتي، تين ونعناع على فيتيفر منعش.',
  'valentino--uomo-born-in-roma-intense': 'Born in Roma الرجالي مشدودًا حول فانيليا خشبية رجولية.',
  'valentino--uomo-intense': 'سوسن وجلد دافئان وبودريان، أناقة إيطالية فيها شيء من الحلاوة.',

  /* Versace */
  'versace--bright-crystal': 'رمّان منعش وفوّار على قلب فاوانيا شفّاف.',
  'versace--crystal-noir': 'غاردينيا متبّلة وكريمية، شرقي ناعم بأثر ملحّ.',
  'versace--eros-edp': 'نعناع وفانيليا Eros الأزرق، أكثف في نسخة البارفان، أثر السهرات.',
  'versace--eros-energy-pour-homme-edp': 'Eros أكثر انتعاشًا وبحرية، حمضيات مالحة على أمبروكسان مضيء.',
  'versace--eros-flame': 'الوجه الدافئ والحمضي لـEros، برتقال أحمر على أخشاب فانيلية.',
  'versace--man-eau-fraiche-extreme': 'أزرق Man Eau Fraîche البحري مركَّزًا، أثبت وأكثر خشبية.',

  /* Victoria's Secret */
  'victorias-secret--bombshell': 'فاكهي زهري مشمس وصاخب، فاكهة الباشن والفاوانيا، بصمة أمريكية.',
  'victorias-secret--bombshell-seduction-edp': 'Bombshell في نسخة أحلى وأنعم، نكتارين وبرالين.',

  /* Viktor&Rolf */
  'viktor-rolf--spicebomb-extreme': 'قنبلة توابل دافئة: فلفل وقرفة وتبغ على فانيليا كثيفة.',

  /* Xerjoff */
  'xerjoff--accento': 'فاكهي زهري فوّار وراقٍ، أناناس غضّ على ورود غراس.',
  'xerjoff--erba-pura': 'كوكتيل حمضيات وفواكه غضّة على عنبر ومسك لا يُقاوَمان.',
  'xerjoff--fierezza': 'شيبر أخضر كلاسيكي وشامخ، جلبانوم لاذع على طحلب البلوط.',
  'xerjoff--naxos': 'تبغ بالعسل فخم، حلو وراتنجي، تحفة النيش الحلوانية.',
  'xerjoff--perseveranza': 'خشبي جلدي متبّل وكثيف، ورد وعود يشدّهما صندل عميق.',
  'xerjoff--wardasina': 'وردة فاكهية بتوت العليق والكشمش، متوهّجة وسخيّة.',

  /* Yves Saint Laurent */
  'yves-saint-laurent--babycat-raw-bourbon': 'جلد كحولي بالروم وفانيليا البوربون، دافئ وحيواني وفاخر.',
  'yves-saint-laurent--black-opium-glitter': 'قهوة وفانيليا Black Opium تنعشهما كمثرى فوّارة ومحلّاة.',
  'yves-saint-laurent--black-opium-over-red': 'Black Opium يعضّه الكرز الأسود، أكثر فاكهية وجرأة.',
  'yves-saint-laurent--la-nuit-de-lhomme-bleu-electrique': 'هيل La Nuit de L\'Homme ينعشه نَفَس مثلّج.',
  'yves-saint-laurent--la-nuit-de-lhomme-edp': 'إغراء الهيل والأرز في نسخة البارفان، أكثر استدارة وثباتًا.',
  'yves-saint-laurent--la-nuit-de-lhomme-le-parfum': 'La Nuit de L\'Homme يدفّئها العنبر والباتشولي، أثر المساء.',
  'yves-saint-laurent--libre-edp-intense': 'خزامى وزهر برتقال Libre مُثقَّلان بالفانيليا، أكثف وأدكن.',
  'yves-saint-laurent--libre-labsolu-platine': 'Libre أبرد وأكثر معدنية، خزامى مثلّجة على فانيليا جافّة.',
  'yves-saint-laurent--libre-leau-nue': 'النسخة الشفّافة من Libre، خزامى منعشة وبشرة نظيفة.',
  'yves-saint-laurent--libre-le-parfum': 'خزامى وزهر برتقال على فانيليا عنبرية، أدفأ إصدارات Libre.',
  'yves-saint-laurent--mon-paris': 'فاكهي أحمر حامض على باتشولي صافٍ، شيبر عصري وروائي.',
  'yves-saint-laurent--myslf-edp': 'زهر برتقال رجولي وعصري، نظيف، يشدّه عنبر وباتشولي.',
  'yves-saint-laurent--myslf-labsolu': 'MYSLF مركَّزًا وتدفّئه الفانيليا، أكثر إثارة وليلية.',
  'yves-saint-laurent--myslf-le-parfum': 'زهر برتقال MYSLF تلطّفه كمثرى متبّلة وتونكا.',
  'yves-saint-laurent--y-edp': 'خشبي أروماتي للعمل: مريمية منعشة، أرز صافٍ، وتونكا هادئة.',
  'yves-saint-laurent--y-eau-fraiche': 'النسخة البحرية الخفيفة من Y، مريمية وملح على أخشاب فاتحة.',
  'yves-saint-laurent--y-elixir': 'Y مدفوعًا نحو الجلد والباتشولي، أدكن وأكثف وأكثر نضجًا.',
  'yves-saint-laurent--y-le-parfum': 'أدفأ إصدارات Y: تفاح ومريمية على تونكا فانيلية.',

  /* Zadig & Voltaire */
  'zadig-voltaire--this-is-her': 'كستناء كريمية وحليبية على صندل فانيلي، دفء أنيق.'
};
