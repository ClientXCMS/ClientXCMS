<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('local')) {
            // \App\Models\Account\Customer::factory(30)->create();
            // \App\Models\Store\Group::factory(10)->create();
            // \App\Models\Store\Pricing::factory(20)->create();

            $this->call([
                ServerSeeder::class,
                // AdminSeeder::class,
                ModuleSeeder::class,
                StoreSeeder::class,
            ]);
            // InvoiceItem::factory(30)->create();
        }
        $this->call([
            EmailTemplateSeeder::class,
            CancellationReasonSeeder::class,
            GatewaySeeder::class,
            ThemeSeeder::class,
            SupportDepartmentSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);
        $seeders = (app('extension')->getSeeders());
        foreach ($seeders as $seeder) {
            if (! class_exists($seeder) || ! is_subclass_of($seeder, Seeder::class)) {
                $this->command->error("Seeder class $seeder not found or not a subclass of ".Seeder::class);

                continue;
            }
            $this->call($seeder);
        }
    }
}
