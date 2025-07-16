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
namespace App\Console\Commands;

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use Illuminate\Console\Command;

class CreateApiKeyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:create-api-key {name} {email} {--type=application} {--permissions=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an API key for use in CLIENTXCMS';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('type') === 'application') {
            $admin = Admin::where('email', $this->argument('email'))->first();
            if (! $admin) {
                $this->error('Admin not found');

                return;
            }
            $token = $admin->createToken(
                $this->argument('name'),
                ! empty($this->option('permissions')) ? $this->option('permissions') : ['*']
            );
        } else {
            $customer = Customer::where('email', $this->argument('email'))->first();
            if (! $customer) {
                $this->error('Customer not found');

                return;
            }
            $token = $customer->createToken(
                $this->argument('name'),
                ! empty($this->option('permissions')) ? $this->option('permissions') : ['customer:*']
            );
        }
        $this->info('API key created successfully');
        $this->info('Name: '.$this->argument('name'));
        $this->info('Email: '.$this->argument('email'));
        $this->info('Token: '.$token->plainTextToken);
        $this->info('Documentation: '.route('l5-swagger.application.api'));
    }
}
