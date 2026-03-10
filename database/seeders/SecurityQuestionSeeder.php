<?php

/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace Database\Seeders;

use App\Models\Admin\SecurityQuestion;
use App\Models\Personalization\Translation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SecurityQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (SecurityQuestion::count() > 0) {
            return;
        }

        $questions = [
            [
                'question' => 'What was the name of your first pet?',
                'sort_order' => 1,
                'translations' => [
                    'en' => 'What was the name of your first pet?',
                    'fr' => 'Quel était le nom de votre premier animal de compagnie ?',
                    'es' => '¿Cuál era el nombre de tu primera mascota?',
                ],
            ],
            [
                'question' => 'In which city were you born?',
                'sort_order' => 2,
                'translations' => [
                    'en' => 'In which city were you born?',
                    'fr' => 'Dans quelle ville êtes-vous né(e) ?',
                    'es' => '¿En qué ciudad naciste?',
                ],
            ],
            [
                'question' => 'What is your mother\'s maiden name?',
                'sort_order' => 3,
                'translations' => [
                    'en' => 'What is your mother\'s maiden name?',
                    'fr' => 'Quel est le nom de jeune fille de votre mère ?',
                    'es' => '¿Cuál es el apellido de soltera de tu madre?',
                ],
            ],
            [
                'question' => 'What was the name of your first school?',
                'sort_order' => 4,
                'translations' => [
                    'en' => 'What was the name of your first school?',
                    'fr' => 'Quel était le nom de votre première école ?',
                    'es' => '¿Cuál era el nombre de tu primera escuela?',
                ],
            ],
            [
                'question' => 'What is your favorite movie?',
                'sort_order' => 5,
                'translations' => [
                    'en' => 'What is your favorite movie?',
                    'fr' => 'Quel est votre film préféré ?',
                    'es' => '¿Cuál es tu película favorita?',
                ],
            ],
            [
                'question' => 'What was your childhood nickname?',
                'sort_order' => 6,
                'translations' => [
                    'en' => 'What was your childhood nickname?',
                    'fr' => 'Quel était votre surnom d\'enfance ?',
                    'es' => '¿Cuál era tu apodo de infancia?',
                ],
            ],
        ];

        foreach ($questions as $question) {
            $securityQuestion = SecurityQuestion::create([
                'question' => $question['question'],
                'is_active' => true,
                'sort_order' => $question['sort_order'],
            ]);

            if (Schema::hasTable('translations')) {
                foreach ($question['translations'] as $locale => $content) {
                    Translation::updateOrCreate([
                        'model' => SecurityQuestion::class,
                        'model_id' => $securityQuestion->id,
                        'key' => 'question',
                        'locale' => $locale,
                    ], [
                        'content' => $content,
                    ]);
                }
            }
        }
    }
}
