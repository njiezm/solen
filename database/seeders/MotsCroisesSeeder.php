<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MotsCroises;
use App\Models\MotCroise;

class MotsCroisesSeeder extends Seeder
{
    public function run()
    {
        // Vider les tables
        MotCroise::truncate();
        MotsCroises::truncate();

        $motsCroises = MotsCroises::create([
            'titre' => 'Le Mariage de Maëva & Gilles',
            'description' => 'Une grille sur le thème de l\'amour, de la foi et de l\'engagement.',
            'taille' => 12,
            'actif' => true,
        ]);

        $listeMots = [
            // Horizontaux
            ['mot'=>'MARIAGE','definition'=>'Union de deux personnes.','position_x'=>0,'position_y'=>1,'direction'=>'horizontal'],
            ['mot'=>'LOVE','definition'=>'Amour en anglais.','position_x'=>3,'position_y'=>3,'direction'=>'horizontal'],
            ['mot'=>'MAEVA','definition'=>'Prénom de la mariée.','position_x'=>1,'position_y'=>4,'direction'=>'horizontal'],
            ['mot'=>'FLEUR','definition'=>'Symbole de beauté et de vie.','position_x'=>7,'position_y'=>5,'direction'=>'horizontal'],
            ['mot'=>'COEUR','definition'=>'Siège des sentiments et de l\'amour.','position_x'=>6,'position_y'=>6,'direction'=>'horizontal'],
            ['mot'=>'EPOUX','definition'=>'Nom du marié.','position_x'=>6,'position_y'=>10,'direction'=>'horizontal'],

            // Verticaux
            ['mot'=>'GILLES','definition'=>'Prénom du marié.','position_x'=>3,'position_y'=>0,'direction'=>'vertical'],
            ['mot'=>'ALLIANCE','definition'=>'Symbole de fidélité.','position_x'=>2,'position_y'=>4,'direction'=>'vertical'],
            ['mot'=>'AMOUR','definition'=>'Sentiment profond.','position_x'=>10,'position_y'=>2,'direction'=>'vertical'],
            ['mot'=>'FOI','definition'=>'Confiance et foi en Dieu.','position_x'=>7,'position_y'=>5,'direction'=>'vertical'],
        ];

        foreach ($listeMots as $motData) {
            $motsCroises->mots()->create($motData);
        }
    }
}
