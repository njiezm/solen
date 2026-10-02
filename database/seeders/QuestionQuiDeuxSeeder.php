<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuestionQuiDeux;

class QuestionQuiDeuxSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            // Vos nouvelles questions
            [
                'question' => 'Qui ronfle le plus ?',
                'bonne_reponse' => 'Maëva', // Remplacez par 'Gilles' ou 'Maëva'
                'active' => true,
            ],
            [
                'question' => 'Qui mange le plus ?',
                'bonne_reponse' => 'Maëva', // Remplacez par 'Gilles' ou 'Maëva'
                'active' => true,
            ],
            [
                'question' => 'Qui est le plus câlin ?',
                'bonne_reponse' => 'Maëva', // Remplacez par 'Gilles' ou 'Maëva'
                'active' => true,
            ],
            [
                'question' => 'Qui est le plus bordélique ?',
                'bonne_reponse' => 'Maëva', // Remplacez par 'Gilles' ou 'Maëva'
                'active' => true,
            ],
            [
                'question' => 'Qui s\'endort le plus devant la TV ?',
                'bonne_reponse' => 'Gilles', // Remplacez par 'Gilles' ou 'Maëva'
                'active' => true,
            ],
            [
                'question' => 'Qui oublie le plus souvent les choses ?',
                'bonne_reponse' => 'Gilles', // Remplacez par 'Gilles' ou 'Maëva'
                'active' => true,
            ],
            [
                'question' => 'Qui fait le plus souvent le ménage ?',
                'bonne_reponse' => 'Gilles', // Remplacez par 'Gilles' ou 'Maëva'
                'active' => true,
            ],
            [
                'question' => 'Qui est le plus accro à son téléphone ?',
                'bonne_reponse' => 'Maëva', // Remplacez par 'Gilles' ou 'Maëva'
                'active' => true,
            ],
            [
                'question' => 'Qui est le plus organisé ?',
                'bonne_reponse' => 'Maëva', // Remplacez par 'Gilles' ou 'Maëva'
                'active' => true,
            ],
            [
                'question' => 'Qui parle le plus ?',
                'bonne_reponse' => 'Maëva', // Remplacez par 'Gilles' ou 'Maëva'
                'active' => true,
            ],
            [
                'question' => 'Qui est le plus maniaque ?',
                'bonne_reponse' => 'Gilles', // Remplacez par 'Gilles' ou 'Maëva'
                'active' => true,
            ],
            [
                'question' => 'Qui cuisine le plus ?',
                'bonne_reponse' => 'Gilles', // Remplacez par 'Gilles' ou 'Maëva'
                'active' => true,
            ],
            // Vos anciennes questions (si vous voulez les garder)
            /*
            [
                'question' => 'Qui est plus du matin ?',
                'bonne_reponse' => 'Gilles',
                'active' => true,
            ],
            [
                'question' => 'Qui aime plus le chocolat ?',
                'bonne_reponse' => 'Maëva',
                'active' => true,
            ],
            [
                'question' => 'Qui est plus du sport ?',
                'bonne_reponse' => 'Gilles',
                'active' => true,
            ],
            [
                'question' => 'Qui cuisine le mieux ?',
                'bonne_reponse' => 'Maëva',
                'active' => true,
            ],
            [
                'question' => 'Qui est plus organisé(e) ?',
                'bonne_reponse' => 'Maëva',
                'active' => true,
            ],
            */
        ];

        foreach ($questions as $question) {
            QuestionQuiDeux::create($question);
        }
    }
}