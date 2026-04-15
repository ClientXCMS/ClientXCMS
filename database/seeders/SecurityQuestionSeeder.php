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
use Illuminate\Database\Seeder;

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
            ['question' => __('install.security_questions.pet_name'), 'sort_order' => 1],
            ['question' => __('install.security_questions.birth_city'), 'sort_order' => 2],
            ['question' => __('install.security_questions.mother_maiden_name'), 'sort_order' => 3],
            ['question' => __('install.security_questions.first_school'), 'sort_order' => 4],
            ['question' => __('install.security_questions.favorite_movie'), 'sort_order' => 5],
            ['question' => __('install.security_questions.childhood_nickname'), 'sort_order' => 6],
        ];

        foreach ($questions as $question) {
            SecurityQuestion::create([
                'question' => $question['question'],
                'is_active' => true,
                'sort_order' => $question['sort_order'],
            ]);
        }
    }
}
