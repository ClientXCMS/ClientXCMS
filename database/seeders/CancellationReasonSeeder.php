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
 * Year: 2025
 */
namespace Database\Seeders;

use App\Models\Provisioning\CancellationReason;
use Illuminate\Database\Seeder;

class CancellationReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (CancellationReason::count() > 0) {
            return;
        }

        $reasons = [
            [
                'reason' => 'Je ne suis pas satisfait',
            ],
            [
                'reason' => 'Je n\'ai plus besoin de ce service',
            ],
            [
                'reason' => 'Je n\'ai pas reçu le service',
            ],
            [
                'reason' => 'Je n\'ai plus les moyens de payer',
            ],
            [
                'reason' => 'Autres (préciser)',
            ],
        ];
        for ($i = 0; $i < count($reasons); $i++) {
            CancellationReason::create($reasons[$i]);
        }
    }
}
