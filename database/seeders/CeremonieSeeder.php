<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\EtapeCeremonie;
use App\Models\Lecture;
use App\Models\Chant;
use App\Models\Priere;
use App\Models\Remerciement;
use App\Models\MemoryCard;

class CeremonieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Pour PostgreSQL, nous utilisons une approche différente pour vider les tables
        // sans désactiver les contraintes de clés étrangères
        
        // Supprimer les enregistrements dans l'ordre inverse des relations
        // pour éviter les violations de contraintes de clés étrangères
        DB::table('memory_cards')->delete();
        DB::table('prieres')->delete();
        DB::table('chants')->delete();
        DB::table('lectures')->delete();
        DB::table('etape_ceremonies')->delete();
        DB::table('remerciements')->delete();
        
        // Réinitialiser les séquences d'auto-incrémentation
        DB::statement("ALTER SEQUENCE memory_cards_id_seq RESTART WITH 1");
        DB::statement("ALTER SEQUENCE prieres_id_seq RESTART WITH 1");
        DB::statement("ALTER SEQUENCE chants_id_seq RESTART WITH 1");
        DB::statement("ALTER SEQUENCE lectures_id_seq RESTART WITH 1");
        DB::statement("ALTER SEQUENCE etape_ceremonies_id_seq RESTART WITH 1");
        DB::statement("ALTER SEQUENCE remerciements_id_seq RESTART WITH 1");

        // === 1. Création du message de remerciements ===
        Remerciement::create([
            'titre' => 'Remerciements',
            'contenu' => <<<'EOT'
Nous rendons grâce pour toutes les personnes que Dieu a placées sur notre chemin.
À travers une présence, une écoute, un conseil, une prière ou un geste, chacun a contribué, à sa manière, à nous accompagner dans ce chemin de vie et de foi.
Parfois, nous savions demander.
Parfois, nous ne savions pas comment.
Mais nous avons toujours trouvé une main tendue, un regard bienveillant, une parole juste.
Merci d'avoir cru en nous, même lorsque rien ne semblait gagné d'avance.
Aujourd'hui, nous témoignons que l'amour, l'acceptation et la compréhension, soutenus par la foi, ont été plus forts.
Merci d'être ici, de prier avec nous et de partager ce moment de grâce.
« L'amour prend patience, l'amour rend service… L'amour ne passera jamais. » (1 Co 13, 4-8)
EOT,
            'signatures' => 'Maëva & Gilles',
        ]);

        // === 2. Création des étapes de la cérémonie ===
        $etape1 = EtapeCeremonie::create(['ordre' => 1, 'titre' => 'OUVERTURE DE LA CÉRÉMONIE', 'icone' => 'fa-solid fa-door-open', 'termine' => true]);
        $etape2 = EtapeCeremonie::create(['ordre' => 2, 'titre' => 'DIEU NOUS PARLE', 'icone' => 'fa-solid fa-book-bible', 'en_cours' => true]);
        $etape3 = EtapeCeremonie::create(['ordre' => 3, 'titre' => 'DIEU NOUS UNIT', 'icone' => 'fa-solid fa-hands-praying']);
        $etape4 = EtapeCeremonie::create(['ordre' => 4, 'titre' => 'TOUTE UNE VIE POUR S\'AIMER', 'icone' => 'fa-solid fa-heart']);
        $etape5 = EtapeCeremonie::create(['ordre' => 5, 'titre' => 'REMERCIEMENTS', 'icone' => 'fa-solid fa-champagne-glasses']);

        // === 3. Création des lectures, chants et prières associés ===

        // --- Étape 1: OUVERTURE ---
        // Chant 1: Psaumes de la création
        $etape1->chants()->create([
            'titre' => 'Psaumes de la création', 
            'auteur' => 'FUTUR MARIÉ', 
            'paroles' => "Procession d'entrée\n1er couplet\nPar les cieux devant toi, splendeur et majesté\nPar l'infiniment grand, l'infiniment petit\nEt par le firmament, ton manteau étoilé,\nEt par frère soleil, je veux crier :\nRefrain\nMon Dieu, tu es grand, tu es beau,\nDieu vivant, Dieu très haut, tu es le Dieu d'amour ;\nMon Dieu, tu es grand, tu es beau,\nDieu vivant, Dieu très haut,\nDieu présent, en toute création.\n5e couplet\nPar cette main tendue qui invite à la danse\nPar ce baiser jailli d'un élan d'espérance\nPar ce regard d'amour qui relève et réchauffe\nPar le pain et le vin, je veux crier :"
        ]);
        
        // Chant 2: Alleluia des prêtres
        $etape1->chants()->create([
            'titre' => 'Alleluia des prêtres', 
            'auteur' => 'FUTUR MARIÉ', 
            'paroles' => "Procession d'entrée\n(Titre original : *Alleluia ! De Cohen)\n1er couplet\nPour notre terre hospitalière\nEt pour nos mères si nourricières\nJe chante la gloire du Seigneur\nPour les rivières et pour les fleuves\nEt pour le vent et les embruns\nJe chante Allé ! Alléluia !\n2e couplet\nPour les enfants et les vieillards\nPour le sourire et la tendresse\nNous chantons la gloire du Seigneur\nPour l'espérance et pour la paix\nPour le pardon et pour l'amour\nNous chantons Allé Alléluia !\nRefrain\nAlleluia ! Alleluia !\nAlleluia ! Alleluia !"
        ]);
        
        // Chant 3: Que tes œuvres sont belles
        $etape1->chants()->create([
            'titre' => 'Que tes œuvres sont belles', 
            'auteur' => 'Chant d\'entrée', 
            'paroles' => "Que tes œuvres sont belles,\nQue tes œuvres sont grandes !\nSeigneur, Seigneur, tu nous combles de joie.\nQue tes œuvres sont belles,\nQue tes œuvres sont grandes !\nSeigneur, Seigneur, tu nous combles de joie.\n\n1\nC'est toi, le Dieu qui nous as faits, qui nous as pétris de la terre !\nTout homme est une histoire sacrée, l'homme est à l'image de Dieu !\nTon amour nous a façonnés, tirés du ventre de la terre !\nTout homme est une histoire sacrée, l'homme est à l'image de Dieu !"
        ]);

        // --- Étape 2: DIEU NOUS PARLE ---
        // Lecture 1: Genèse
        $etape2->lectures()->create([
            'titre' => 'Première Lecture',
            'reference' => 'GENÈSE 1,26-28.31a',
            'contenu' => "L E C T U R E D U L I V R E D E L A G E N È S E\nAu commencement,\nDieu dit:\n« Faisons l'homme à notre image,\nselon notre ressemblance.\nQu'il soit le maître\ndes poissons de la mer,\ndes oiseaux du ciel,\ndes bestiaux,\nde toutes les bêtes sauvages\net de toutes les bestioles qui vont et viennent sur la terre »\nDieu créa l'homme à son image,\nà l'image de Dieu il créa,\nil les créa homme femme.\nDieu les bénit et leur dit :\n« Soyez féconds et multipliez-vous,\nremplissez la terre et soumettez-la.\nSoyez les maitres\ndes poissons de la mer,\ndes oiseaux du ciel,\net de tous les animaux qui vont et viennent sur la terre »\nEt Dieu vit tout ce qu'il avait fait :\nc'était très bon."
        ]);
        
        // Chant 1: Psaume 148
        $etape2->chants()->create([
            'titre' => 'Psaume 148', 
            'auteur' => 'Psaume', 
            'paroles' => "refrain\nAlléluia, louez le Seigneur, alléluia, alléluia !\nLouez le Seigneur du haut des cieux,\nlouez-le dans les hauteurs.\nVous, tous ses anges, louez-le,\nlouez-le, tous les univers.\nLouez-le, soleil et lune,\nlouez-le, tous les astres de lumière ;\nvous, cieux des cieux, louez-le,\net les eaux des hauteurs des cieux.\nLes rois de la terre et tous les peuples,\nles princes et tous les juges de la terre ;\ntous les jeunes gens et jeunes filles,\nles vieillards comme les enfants.\nQu'ils louent le nom du Seigneur,\nle seul au-dessus de tout nom;\nsur le ciel et sur la terre, sa splendeur :\nil accroît la vigueur de son peuple.\nrefrain"
        ]);
        
        // Chant 2: Alléluia é
        $etape2->chants()->create([
            'titre' => 'Alléluia é', 
            'auteur' => 'Acclamation', 
            'paroles' => "Alleluia é, Allelluia é é é (bis) Louez le\nseigneur allelou, alleluia, allelluia é é é"
        ]);
        
        // Lecture 2: Évangile selon Saint Jean
        $etape2->lectures()->create([
            'titre' => 'Évangile',
            'reference' => 'JEAN 17,20-26',
            'contenu' => "ÉVANGILE DE JÉSUS CHRIST SELON SAINT JEAN\nE 13\nJEAN 17,20-26\n\nÀ l'heure où Jésus passait de ce monde à son père,\nil leva les yeux au ciel et pria ainsi :\n« Père, je ne prie pas seulement pour ceux qui sont là,\nmais encore pour ceux qui accueilleront leur parole\net croiront en moi :\nQue tous, ils soient un,\ncomme toi, Père, tu es en moi,\net moi en toi.\nQu'ils soient un en nous, eux aussi,\npour que le monde croie\nque tu m'as envoyé.\nEt moi, je leur ai donné la gloire que tu m'as donnée,\npour qu'ils soient un\ncomme nous sommes un :\nmoi en eux,\net toi en moi.\nQue leur unité soit parfaite ;\nainsi, le monde saura\nque tu m'as envoyé\net que tu les as aimés\ncomme tu m'as aimé.\n__________________________________________\n[Père,\nle monde ne t'a pas connu,\nmais moi, je t'ai connu,\net ils ont reconnu, eux aussi,\nque tu m'as envoyé.\nJe leur ai fait connaître ton nom,\net je le ferai connaître encore:\npour qu'ils aient en eux l'amour dont tu m'as aimé,\net que moi aussi, je sois en eux.\nparce que tu m'as aimé avant même la création du monde.\nPère juste,\nle monde ne t'a pas connu,\nmais moi, je t'ai connu,\net ils ont reconnu, eux aussi,\nque tu m'as envoyé.\nJe leur ai fait connaître ton nom,\net je le ferai connaître encore:\npour qu'ils aient en eux l'amour dont tu m'as aimé,\net que moi aussi, je sois en eux."
        ]);
        
        // Chant 3: Viens Esprit du Dieu vivant
        $etape2->chants()->create([
            'titre' => 'Viens Esprit du Dieu vivant', 
            'auteur' => 'Chant à l\'esprit saint', 
            'paroles' => "VIENS ESPRIT DU DIEU VIVANT\nViens esprit du dieu vivant, esprit d'amour\nLumière Bienheureuse nous t'attendons\nViens esprit de vérité, souffle de feu\nEmbrase-nous, purifie-nous et guéris-nous\n\nViens Esprit Saint, Onction céleste\nRemplis nos cœurs de ta présence\nRévèle nous l'amour du Père\nAgis en nous transforme nous"
        ]);

        // --- Étape 3: DIEU NOUS UNIT ---
        // Prière 1: Dialogue initial
        $etape3->prieres()->create([
            'titre' => 'Dialogue initial',
            'contenu' => "Le prêtre : Gilles et Maëva, vous avez écouté la parole de Dieu\nqui révèle la grandeur de l'amour humain et du mariage.\nVous allez vous engager l'un envers l'autre. Est-ce librement et sans\ncontrainte ?\nLes futurs époux (séparément) : Oui.\nLe prêtre : En vous engageant dans la voie du mariage, vous vous\npromettez\namour mutuel et respect. Est-ce pour toute votre vie ?\nLes futurs époux (séparément) : Oui (pour toute notre vie).\nLe prêtre : Êtes-vous prêts à accueillir les enfants que Dieu vous donne\net à les éduquer selon l'Évangile du Christ et dans la foi de l'Église ?\nLes futurs époux (séparément) : Oui.\nLe prêtre : Êtes-vous disposés à assumer ensemble votre mission de\nchrétiens\ndans le monde et dans l'Église ?\nLes futurs époux (ensemble) : Oui."
        ]);
        
        // Prière 2: Echange des consentements
        $etape3->prieres()->create([
            'titre' => 'Echange des consentements',
            'contenu' => "Gilles : Maëva veux-tu être ma femme ?\nMaëva : Oui je le veux.\nEt toi, Gilles veux-tu être mon mari ?\nGilles : Oui je le veux. Moi, Gilles je te reçois Maëva\ncomme femme et je serai ton mari Gilles.\nJe promets de t'aimer fidèlement dans le bonheur et\ndans les épreuves tout au long de notre vie.\nMaëva : Moi, Maëva je te reçois Gilles comme mari\net je serai ta femme Maëva.\nJe promets de t'aimer fidèlement dans le bonheur et\ndans les épreuves tout au long de notre vie."
        ]);
        
        // Prière 3: Bénédiction et échange des alliances
        $etape3->prieres()->create([
            'titre' => 'Bénédiction et échange des alliances',
            'contenu' => "Gilles : Maëva reçois cette alliance, signe de mon amour\net de ma fidélité. (Au nom du Père, et du Fils, et du Saint-Esprit.)\nMaëva : Gilles reçois cette alliance, signe de mon amour\net de ma fidélité. (Au nom du Père, et du Fils, et du Saint-Esprit.)"
        ]);
        
        // Prière 4: Bénédiction Nuptiale
        $etape3->prieres()->create([
            'titre' => 'Bénédiction Nuptiale',
            'contenu' => "Père très saint, créateur du monde, toi qui as\nfait l'homme et la femme à ton image, toi qui as\nvoulu leur union et qui l'as bénie, nous te prions\nhumblement pour et .. qui sont\nunis aujourd'hui par le sacrement de mariage.\nQue ta bénédiction descende en abondance sur eux.\nQue la force de l'Esprit Saint les enflamme de ton\namour; Qu'ils trouvent le bonheur en se donnant\nl'un à l'autre ; [Que des enfants viennent embellir\nleur foyer et que l'Église en soit enrichie.] Dans la joie,\nqu'ils sachent te remercier; dans la tristesse,\nqu'ils se tournent vers toi ; que ta présence les aide\ndans leur travail; qu'ils te trouvent à leur côté\ndans l'épreuve pour alléger leur fardeau.\nQu'ils participent à la prière de ton Église\net témoignent de toi dans le monde.\nEnfin, après avoir vécu longtemps heureux,\nqu'ils parviennent, entourés de leurs amis,\ndans le Royaume des cieux.\nPar Jésus, le Christ, notre Seigneur.\nTous : Amen."
        ]);
        
        // Chant 1: Comment ne pas te louer
        $etape3->chants()->create([
            'titre' => 'Comment ne pas te louer', 
            'auteur' => 'Chant de louange', 
            'paroles' => "COMMENT NE PAS TE LOUER\nComment ne pas te louer-er-er\nComment ne pas te louer-er-er\nComment ne pas te louer-er-er Seigneur Jésus !\nComment ? Comment ?\n1.Quand je regarde autour de moi Je vois ta gloire,\nSeigneur Jésus, je te bénis. Comment ne pas te louer-erer, Seigneur Jésus ! Comment ? Comment ?\n2. Quand je regarde autour de moi Je vois mes frères,\nSeigneur Jésus, merci pour eux. Comment ne pas te louer-er-er, Seigneur Jésus ! Comment ? Comment ?"
        ]);
        
        // Prière 5: Prière des époux
        $etape3->prieres()->create([
            'titre' => 'Prière des époux',
            'contenu' => "Seigneur notre Dieu, tu nous as conduits\njusqu'à ce jour de bonheur :\nnous te disons notre reconnaissance.\nTu nous as confiés l'un à l'autre : maintenant,\nensemble, nous te confions notre amour.\nNous te demandons, Seigneur, de nous tenir unis,\nde nous garder dans ta paix.\nProtège notre mariage.\nDonne-nous d'accueillir des enfants.\nOuvre nos coeurs aux autres.\nDonne-nous d'être fidèles tout au long de notre vie.\nAccueille-nous un jour au Royaume de ton amour,\noù nous pourrons te louer dans le bonheur et dans la paix.\nAmen."
        ]);

        // --- Étape 4: TOUTE UNE VIE POUR S'AIMER ---
        // Prière 1: Prière Universelle
        $etape4->prieres()->create([
            'titre' => 'Prière Universelle',
            'contenu' => "Refrain\nOh Jésus, Gilles et Maëva se confient à toi seigneur écoute les\nOh Jésus, Gilles et Maëva se confient à toi seigneur exauce les\nGilles et Maëva sont désormais unis devant Dieu !\nQue leur amour soit signe d'un amour plus grand,\nl'amour même qui vient de toi, notre Dieu…\nRefrain\nGilles et Maëva sont appelés à la responsabilité de parents !\nQu'ils sachent éveiller leurs enfants au sens du partage et de la\nprière…\nRefrain\nGilles et Maëva ont dit « oui » devant nous tous, parents et\namis !\nQue leur engagement de ce jour fortifie notre foi et nourrisse\nnotre espérance…\nRefrain\nGilles et Maëva nous rappellent que tout amour doit s'ouvrir\nsur le monde !\nQue nous sachions être présents à nos frères et sœurs,\nen particulier aux malades et aux laissés-pour-compte…\nRefrain"
        ]);
        
        // Prière 2: Notre Père
        $etape4->prieres()->create([
            'titre' => 'Notre Père',
            'contenu' => "Notre Père qui est aux cieux,\nQue Ton Nom soit sanctifié,\nQue Ton règne vienne,\nQue Ta volonté soit faite\nSur la terre comme au ciel.\nDonne-nous aujourd'hui\nNotre pain de ce jour.\nPardonne-nous nos offenses\nComme nous pardonnons aussi à ceux\nQui nous ont offensés.\nEt ne nous laisse pas entrer en tentation,\nMais délivre-nous du mal.\nAmen"
        ]);

        // --- Étape 5: REMERCIEMENTS ---
        // Prière 1: Bénédiction Finale
        $etape5->prieres()->create([
            'titre' => 'Bénédiction Finale',
            'contenu' => "Seigneur notre Dieu,\nregarde avec bonté ces nouveaux époux\net daigne répandre sur eux tes bénédictions :\nQu'ils soient unis dans un même amour\net avancent vers une même sainteté.\nQu'ils aient la joie de participer à ton amour créateur\net puissent ensemble éduquer leurs enfants.\nQu'ils vivent dans la justice et la charité\npour montrer ta lumière à ceux qui te cherchent.\nQu'ils mettent leur foyer au service du monde\net répondent aux appels de leur prochain.\nQu'ils soient fortifiés par les sacrifices et les joies\nde leur vie et sachent témoigner de l'Évangile.\nQu'ils vivent longtemps sans malheur ni maladie\net que leur travail à tous deux soit béni.\nQu'ils voient grandir en paix leurs enfants\net qu'ils aient le soutien d'une famille heureuse.\nQu'ils parviennent enfin,\navec tous ceux qui les ont précédés,\ndans ta demeure où leur amour ne finira jamais.\nGilles et Maëva,\net vous tous ici présents,\nque Dieu tout-puissant vous bénisse :\nle Père, le Fils et le Saint-Esprit.\nTous : Amen !"
        ]);
        
        // Chant 1: Je te promets
        $etape5->chants()->create([
            'titre' => 'Je te promets', 
            'auteur' => 'Chant registre des signatures et quête', 
            'paroles' => "JE TE PROMETS\nDevant nos familles devant\nDieu je prends ta main et je\ndis oui Je te vois là Tu me\nsouris Aujourd'hui je\nt'épouse Pour la vie\nJe te promets fidélité Je te\npromets tendresse Je te promets\nd'être là Dans la joie et la peine\nJe te promets mon amour Je te\npromets ma force Je te promets\nun foyer Rempli de bonheur\nCette alliance au doigt Symbole\nd'union Brillera comme Mon\namour pour toi Tu es mon choix\nTu es ma voie Aujourd'hui je\nm'engage Auprès de toi"
        ]);
        
        // Chant 2: Abrite-moi
        $etape5->chants()->create([
            'titre' => 'Abrite-moi', 
            'auteur' => 'Chant registre des signatures et quête', 
            'paroles' => "ABRITE-MOI\nAbrite-moi sous tes ailes. Couvre-moi par ta main puissante\n\nMême si les océans se déchaînent, je les traverserai avec toi\nPère, tu domines les tempêtes, je suis tranquille, car tu es là.\n\nEn Jésus seul, je me confie. Il me donne, force, calme et puissance.\n\nMême si les océans se déchaînent, je les traverserai avec toi\nPère, tu domines les tempêtes, je suis tranquille, car tu es là."
        ]);
        
        // Chant 3: Ave Maria
        $etape5->chants()->create([
            'titre' => 'Ave Maria', 
            'auteur' => 'Consécration à la vierge', 
            'paroles' => "Ave,\nAve,\nAve,\nAve Maria\nAve Mari a\nAve Maria\nAve,\nAve Maria\nAve Maria\nAaaaa ve Mariia\nAaaa ve Aave,\nAve,\nAve,\nAve,\nAve Maria\nAve Maria\nAve Maria\nAve Maria\nAaave Maaaaria\nAave Maaaaria\nAaaave,\nAaaaa ve,\nAve"
        ]);
        
        // Chant 4: Que ma bouche chante tes louanges
        $etape5->chants()->create([
            'titre' => 'Que ma bouche chante tes louanges', 
            'auteur' => 'Chant d\'envoi', 
            'paroles' => "Sortie : Meddley\nQue ma bouche chante tes louanges\nDe toi, Seigneur, nous attendons la vie,\nQue ma bouche chante ta louange.\nTu es pour nous un rempart, un appui,\nQue ma bouche chante ta louange.\nLa joie du cœur vient de toi ô Seigneur,\nQue ma bouche chante ta louange.\nNotre confiance est dans ton nom très saint !\nQue ma bouche chante ta louange.\n\nSois loué Seigneur, pour ta grandeur,\nSois loué pour tous tes bienfaits.\nGloire à toi Seigneur, tu es vainqueur,\nTon amour inonde nos cœurs.\nQue ma bouche chante ta louange."
        ]);
        
        // Chant 5: Evenou Shalom
        $etape5->chants()->create([
            'titre' => 'Evenou Shalom', 
            'auteur' => 'Chant d\'envoi', 
            'paroles' => "Evenou shalom alerhem !\nEvenou shalom alerhem !\nEvenou shalom alerhem !\nEvenou shalom, shalom, shalom alerhem !\n\n1 - Nous vous annonçons la paix. (Ter)\nNous vous annonçons la paix, la paix, la paix de Jésus !\n2 - Nous vous annonçons la joie...\n3 - Nous vous annonçons l'amour..."
        ]);

        // === 4. Création des paires de cartes pour le jeu Memory ===
        $memoryCards = [
            ['titre' => 'Gilles', 'pair_id' => 'pair-1', 'image_path' => 'memory-cards/gilles.jpg'],
            ['titre' => 'Gilles', 'pair_id' => 'pair-1', 'image_path' => 'memory-cards/gilles.jpg'],
            ['titre' => 'Les Alliances', 'pair_id' => 'pair-2', 'image_path' => 'memory-cards/anneaux.jpg'],
            ['titre' => 'Les Alliances', 'pair_id' => 'pair-2', 'image_path' => 'memory-cards/anneaux.jpg'],
            ['titre' => 'Le Baiser', 'pair_id' => 'pair-3', 'image_path' => 'memory-cards/baiser.jpg'],
            ['titre' => 'Le Baiser', 'pair_id' => 'pair-3', 'image_path' => 'memory-cards/baiser.jpg'],
            ['titre' => 'Le Livret', 'pair_id' => 'pair-4', 'image_path' => 'memory-cards/livret.jpg'],
            ['titre' => 'Le Livret', 'pair_id' => 'pair-4', 'image_path' => 'memory-cards/livret.jpg'],
            ['titre' => 'Maëva', 'pair_id' => 'pair-5', 'image_path' => 'memory-cards/maeva.jpg'],
            ['titre' => 'Maëva', 'pair_id' => 'pair-5', 'image_path' => 'memory-cards/maeva.jpg'],
            ['titre' => 'Eglise', 'pair_id' => 'pair-6', 'image_path' => 'memory-cards/eglise.jpg'],
            ['titre' => 'Eglise', 'pair_id' => 'pair-6', 'image_path' => 'memory-cards/eglise.jpg'],
            ['titre' => 'Mairie', 'pair_id' => 'pair-7', 'image_path' => 'memory-cards/mairie.jpg'],
            ['titre' => 'Mairie', 'pair_id' => 'pair-7', 'image_path' => 'memory-cards/mairie.jpg'],
            ['titre' => 'Photo', 'pair_id' => 'pair-8', 'image_path' => 'memory-cards/photo.jpg'],
            ['titre' => 'Photo', 'pair_id' => 'pair-8', 'image_path' => 'memory-cards/photo.jpg'],
        ];

        foreach ($memoryCards as $cardData) {
            MemoryCard::create([
                'titre' => $cardData['titre'],
                'description' => "Carte pour le jeu Memory de Maëva et Gilles.",
                'pair_id' => $cardData['pair_id'],
                'image_path' => $cardData['image_path'],
                'actif' => true,
            ]);
        }
    }
}