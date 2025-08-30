<?php

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
