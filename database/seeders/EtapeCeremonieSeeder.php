<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EtapeCeremonie;

class EtapeCeremonieSeeder extends Seeder
{
    public function run(): void
    {
        $etapes = [
            [
                'titre' => 'Chant d\'Entrée & Procession',
                'description' => '**Chant :** <span class="hymn-title">"Hymne à l\'Amour"</span> (Edith Piaf, version instrumentale).<br><br>Entrée des familles, des témoins, et enfin, l\'entrée de la Mariée au bras de son père.',
                'icone' => 'fa-solid fa-music',
                'ordre' => 1,
            ],
            [
                'titre' => 'Première Lecture (par [Témoin/Lecteur])',
                'description' => '**Texte :** L\'Épître aux Corinthiens (Chapitre 13, Le Chant de l\'Amour).<br><br><p class="reading-text">"L\'amour est patient, l\'amour est serviable. Il n\'est pas jaloux, il ne se vante pas, il ne se gonfle pas d\'orgueil..."</p>',
                'icone' => 'fa-solid fa-book-open',
                'ordre' => 2,
            ],
            [
                'titre' => 'Le Moment Solennel : Vœux & Alliances',
                'description' => 'Échange des consentements personnels, bénédiction et échange des alliances.<br><br><p class="hymn-title">Musique de Fond : "Canon en Ré Majeur" (Pachelbel).</p><p class="fw-bold mt-3"><i class="fa-solid fa-kiss-wink-heart me-2 text-dore-accent"></i> Le moment tant attendu : Le Baiser !</p>',
                'icone' => 'fa-solid fa-hand-holding-heart',
                'ordre' => 3,
            ],
            [
                'titre' => 'Mots Personnels (Famille & Amis)',
                'description' => 'Discours émouvants de la Mère de la Mariée et du Témoin du Marié.',
                'icone' => 'fa-solid fa-comment-dots',
                'ordre' => 4,
            ],
            [
                'titre' => 'Signature des Registres & Sortie',
                'description' => '**Chant de Sortie :** <span class="hymn-title">"Happy"</span> (Pharrell Williams) - pour la joie !<br><br>Félicitez les mariés à la sortie de l\'église (riz et confettis sont autorisés !).',
                'icone' => 'fa-solid fa-dove',
                'ordre' => 5,
            ],
        ];

        foreach ($etapes as $etape) {
            EtapeCeremonie::create($etape);
        }
    }
}