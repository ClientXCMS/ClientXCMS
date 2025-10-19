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

namespace App\Console\Commands\Migrate;

use App\Helpers\EnvEditor;
use App\Models\Account\Customer;
use App\Models\Billing\CustomItem;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Helpdesk\SupportDepartment;
use App\Models\Helpdesk\SupportMessage;
use App\Models\Helpdesk\SupportTicket;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Models\Store\Group;
use App\Models\Store\Pricing;
use App\Models\Store\Product;
use App\Modules\Plesk\Models\PleskConfigModel;
use App\Modules\Proxmox\Models\ProxmoxConfigModel;
use App\Modules\Proxmox\Models\ProxmoxIPAM;
use App\Modules\Proxmox\Models\ProxmoxOS;
use App\Modules\Proxmox\Models\ProxmoxTemplates;
use App\Modules\Pterodactyl\Models\PterodactylConfig;
use App\Modules\Wisp\Models\WispConfig;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:v1-migrate {--skip-pending=true} {--dbname=clientxcms} {--host=localhost} {--username=root} {--password=root} {--port=3306} {--force}  {--all=false} {--products} {--support} {--groups} {--servers} {--services} {--socialauth} {--wisp} {--invoices} {--clients} {--pterodactyl} {--proxmox} {--oses} {--templates} {--plesk} {--proxmox_ipam}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate data from clientxcmsv1 to NEXT GEN (warning: this will delete all data in the tables)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $host = $this->option('host') ?? $this->ask('host');
        $dbname = $this->option('dbname') ?? $this->ask('database');
        $username = $this->option('username') ?? $this->ask('username');
        $password = $this->option('password') ?? $this->secret('password');
        $port = $this->option('port') ?? $this->ask('port');
        $all = $this->hasOption('all');
        config()->set('database.connections.migrate.host', $host);
        config()->set('database.connections.migrate.database', $dbname);
        config()->set('database.connections.migrate.username', $username);
        config()->set('database.connections.migrate.password', $password);
        config()->set('database.connections.migrate.port', $port);
        try {
            DB::connection('migrate')->getPdo();
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return;
        }
        $this->info('Connection to the database established');
        $this->info('Checking tables');
        $tables = ['servers', 'users', 'products', 'shop_group',
            'transactions', 'transactions_items', 'services',
            'support_messages', 'support_tickets', 'support_departments',
        ];
        foreach ($tables as $table) {
            if (! \DB::connection('migrate')->getSchemaBuilder()->hasTable($table)) {
                $this->error(sprintf('Table %s not found', $table));

                return;
            }
        }
        $this->info('Tables found');
        $this->info('Starting migration at '.now()->format('Y-m-d H:i:s'));
        if ($this->option('force')) {
            if (app()->runningInConsole()) {
                $this->ask('This will delete all data in the tables, are you sure you want to continue ?');
            }
            $this->info('Deleting all data in the tables');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            if ($all || $this->option('servers')) {
                Server::truncate();
            }
            if ($all || $this->option('clients')) {
                Customer::truncate();
            }
            if ($all || $this->option('groups')) {
                Group::truncate();
            }
            if ($all || $this->option('products')) {
                Product::truncate();
                Pricing::truncate();
            }
            if ($all || $this->option('services')) {
                Service::truncate();
            }
            if ($all || $this->option('invoices')) {
                Invoice::truncate();
                InvoiceItem::truncate();
            }
            if ($all || $this->option('support')) {
                SupportMessage::truncate();
                SupportDepartment::truncate();
                SupportTicket::truncate();
            }
            if ($all && class_exists(ProxmoxOS::class) || $this->option('oses') && class_exists(ProxmoxOS::class)) {
                ProxmoxOS::truncate();
            }
            if ($all && class_exists(ProxmoxTemplates::class) || $this->option('templates') && class_exists(ProxmoxOS::class)) {
                ProxmoxTemplates::truncate();
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->info('Data deleted');
        }
        if ($this->hasOption('all')) {
            $this->info('Migrating all tables');
        }
        if ($all || $this->option('servers')) {
            $this->migrateServers();
        }
        if ($all || $this->option('clients')) {
            $this->migrateCustomers();
        }
        if ($all || $this->option('groups')) {
            $this->migrateGroups();
        }
        if ($all || $this->option('products')) {
            $this->migrateProducts();
        }
        if ($all || $this->option('invoices')) {
            $this->migrateInvoices($this->option('skip-pending') == 'true');
        }
        if ($all || $this->option('services')) {
            $this->migrateServices($this->option('skip-pending') == 'true');
        }
        if ($all || $this->option('pterodactyl')) {
            $this->migratePterodactylConfig();
        }
        if ($all || $this->option('wisp')) {
            $this->migrateWispConfig();
        }
        if ($all || $this->option('proxmox')) {
            $this->migrateProxmoxConfig();
        }
        if ($all || $this->option('oses')) {
            $this->migrateProxmoxOses();
        }
        if ($all || $this->option('templates')) {
            $this->migrateProxmoxTemplates();
        }
        if ($all || $this->option('plesk')) {
            $this->migratePleskTable();
        }
        if ($all || $this->option('support')) {
            $this->migrateSupport();
        }
        if ($all || $this->option('socialauth')) {
            $this->migrateSocialAuth();
        }
        if ($all || $this->option('proxmox_ipam')) {
            $this->migrateProxmoxIPAM();
        }
        EnvEditor::updateEnv([
            'HASH_DRIVER' => 'argon',
        ]);
        $this->info('Changing hash driver to argon2i...');

        $this->info('Migration completed at '.now()->format('Y-m-d H:i:s'));
    }

    public function migrateServers()
    {
        if (Server::count() != 0) {
            $this->info('[Servers] Skipped because servers table is not empty');

            return;
        }

        $results = \DB::connection('migrate')->table('servers')->get();
        foreach ($results as $result) {
            try {
                $server = Server::create([
                    'id' => $result->id,
                    'name' => $this->decode($result->name),
                    'port' => $this->port($result->type),
                    'username' => encrypt('username'),
                    'password' => encrypt('password'),
                    'type' => $result->type,
                    'address' => $result->ipaddress,
                    'status' => $result->hidden ? 'hidden' : 'active',
                    'hostname' => $result->ipaddress,
                    'maxaccounts' => $result->maxaccounts,
                    'created_at' => now(),
                ]);
                $server->id = $result->id;
                $server->created_at = $result->created_at;
                $server->save();
                $this->info(sprintf('[%d] : Server %s migrated ', $result->id, $result->name));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] : %s', $result->id, $e->getMessage()));
            }
            $this->info('Please reconnect your server credentials in the settings');
        }
    }

    private function migrateProducts()
    {
        if (Product::count() != 0) {
            $this->info('[Products] Skipped because products table is not empty');

            return;
        }
        $results = \DB::connection('migrate')->table('products')->get();
        foreach ($results as $result) {
            try {
                $type = $result->type;
                if ($type == 'proxmox.lxc' || $type == 'proxmox.kvm') {
                    $type = 'proxmox';
                }
                if ($type == 'plesk.reseller' || $type == 'plesk.hosting') {
                    $type = 'plesk';
                }
                $id = Product::insert([
                    'id' => $result->id,
                    'name' => $this->decode($result->name),
                    'description' => $this->decode($result->description),
                    'status' => $result->hidden ? 'hidden' : 'active',
                    'sort_order' => $result->sort,
                    'pinned' => $result->pinned,
                    'stock' => $result->stock,
                    'type' => $type,
                    'group_id' => $result->group_id,
                    'created_at' => now(),
                ]);
                if ($result->stockcontrol) {
                    Product::find($result->id)->attachMetadata('disabled_stock', true);
                }
                $pricing = json_decode($result->pricing);
                foreach ($pricing as $k => $v) {
                    if ($v == 0) {
                        $pricing->$k = null;
                    }
                }
                if ($pricing == null) {
                    $this->error(sprintf('[%d] Pricing not found', $result->id));

                    continue;
                }
                Pricing::insert([
                    'related_id' => $result->id,
                    'currency' => 'EUR',
                    'onetime' => $pricing->onetimeprice ?? null,
                    'monthly' => $pricing->monthlyprice,
                    'quarterly' => $pricing->quarterlyprice,
                    'semiannually' => $pricing->semiannuallyprice,
                    'annually' => $pricing->annuallyprice,
                    'biennially' => null,
                    'triennially' => null,
                    'setup_onetime' => $pricing->onetimesetupfee ?? null,
                    'setup_monthly' => $pricing->monthlysetupfee,
                    'setup_quarterly' => $pricing->quarterlysetupfee,
                    'setup_semiannually' => $pricing->semiannuallysetupfee,
                    'setup_annually' => $pricing->annuallysetupfee,
                    'setup_biennially' => null,
                    'setup_triennially' => null,
                ]);

                $this->info(sprintf('[%d] : Products %s migrated ', $result->id, $result->name));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] : %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function migratePterodactylConfig()
    {
        app('extension')->autoload(app());
        if (! class_exists(PterodactylConfig::class)) {

            $this->info('[Pterodactyl] Skipped because modules is not enabled');

            return;
        }
        if (PterodactylConfig::count() != 0) {
            $this->info('[Pterodactyl] Skipped because pterodactyl config table is not empty');

            return;
        }
        $results = \DB::connection('migrate')->table('pterodactyl_config')->get();
        foreach ($results as $result) {
            try {
                PterodactylConfig::insert([
                    'id' => $result->id,
                    'product_id' => $result->product_id,
                    'memory' => $result->memory / 1024,
                    'cpu' => $result->cpu,
                    'disk' => $result->disk / 1024,
                    'io' => $result->io,
                    'port_range' => $result->port_range,
                    'image' => $result->image,
                    'startup' => $result->startup,
                    'server_name' => $result->servername,
                    'location_id' => $result->location_id,
                    'dedicated_ip' => $result->dedicatedip ?? false,
                    'oom_kill' => false,
                    'node_id' => null,
                    'eggs' => $result->eggs,
                    'databases' => $result->db,
                    'swap' => $result->swap,
                    'backups' => $result->backups,
                    'allocations' => $result->allocations,
                    'created_at' => now(),
                    'server_id' => $result->server_id,
                ]);
                $this->info(sprintf('[%d] : Pterodactyl config %d migrated ', $result->id, $result->id));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] : %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function migrateWispConfig()
    {
        app('extension')->autoload(app());
        if (! class_exists(WispConfig::class)) {

            $this->info('[Wisp] Skipped because modules is not enabled');

            return;
        }
        if (WispConfig::count() != 0) {
            $this->info('[Wisp] Skipped because wisp config table is not empty');

            return;
        }
        $results = \DB::connection('migrate')->table('wisp_config')->get();
        foreach ($results as $result) {
            try {
                WispConfig::insert([
                    'id' => $result->id,
                    'product_id' => $result->product_id,
                    'memory' => $result->memory / 1024,
                    'cpu' => $result->cpu,
                    'disk' => $result->disk / 1024,
                    'io' => $result->io,
                    'port_range' => $result->port_range,
                    'image' => $result->image,
                    'startup' => $result->startup,
                    'server_name' => $result->servername,
                    'location_id' => $result->location_id,
                    'dedicated_ip' => $result->dedicatedip ?? false,
                    'oom_kill' => false,
                    'node_id' => null,
                    'eggs' => $result->eggs,
                    'databases' => $result->db,
                    'swap' => $result->swap,
                    'backups' => $result->backups,
                    'allocations' => $result->allocations,
                    'created_at' => now(),
                    'server_id' => $result->server_id,
                ]);
                $this->info(sprintf('[%d] : Wisp config %d migrated ', $result->id));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] : %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function migrateGroups()
    {
        if (Group::count() != 0) {
            $this->info('[Groups] Skipped because groups table is not empty');

            return;
        }
        $results = \DB::connection('migrate')->table('shop_group')->get();
        foreach ($results as $result) {
            try {
                Group::insert([
                    'id' => $result->id,
                    'name' => $this->decode($result->name),
                    'slug' => $result->slug,
                    'description' => $this->decode($result->headline),
                    'status' => $result->hidden ? 'hidden' : 'active',
                    'sort_order' => $result->sort,
                    'parent_id' => $result->parent_id == '0' ? null : $result->parent_id,
                    'created_at' => $result->created_at,
                    'updated_at' => $result->updated_at,
                ]);
                $this->info(sprintf('[%d] : Group %s migrated ', $result->id, $result->name));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] : %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function migrateCustomers()
    {
        if (Customer::count() != 0) {
            $this->info('[Customers] Skipped because users table is not empty');

            return;
        }
        $results = \DB::connection('migrate')->table('users')->get();
        foreach ($results as $result) {
            try {
                $customer = Customer::forceCreate([
                    'id' => $result->id,
                    'firstname' => $this->decode($result->firstname),
                    'lastname' => $this->decode($result->lastname),
                    'email' => $result->email,
                    'phone' => ! empty($result->phonenumber) ? $result->phonenumber : 'Not defined',
                    'city' => $result->city ?? 'Not defined',
                    'password' => $result->password,
                    'is_confirmed' => $result->status == 1,
                    'balance' => $result->wallet,
                    'country' => $this->decode($result->country ?? 'FR'),
                    'address' => $this->decode($result->address ?? 'Not defined'),
                    'address2' => $this->decode($result->address2 ?? 'Not defined'),
                    'region' => $this->decode($result->state ?? 'Not defined'),
                    'zipcode' => $result->postcode ?? '00000',
                    'is_deleted' => $result->is_deleted,
                    'notes' => $result->note,
                    'confirmation_token' => $result->confirmation_token,
                    'last_login' => $this->normalizeDateTime($result->last_sign_at),
                    'last_ip' => $result->last_sign_ip,
                    'created_at' => $result->created_at,
                    'updated_at' => $result->updated_at,
                    'email_verified_at' => $result->confirmed_at,
                ]);
                $customer->id = $result->id;
                $customer->created_at = $result->created_at;
                $customer->updated_at = $result->updated_at;
                $customer->save();
                $this->info(sprintf('[%d] : User %s migrated ', $result->id, $result->email));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] : %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function decode(string $string)
    {
        return html_entity_decode($string, ENT_QUOTES);
    }

    private function normalizeDateTime($value)
    {
        try {
            if ($value === null) {
                return null;
            }
            $string = is_string($value) ? trim($value) : (string) $value;
            if ($string === '' || str_contains($string, '0000-00-00') || $string === '-0001-11-30 00:00:00') {
                return null;
            }
            $dt = \Carbon\Carbon::parse($string);
            return $dt;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Utilisé dans la migration WHMCS
     *
     * @return int
     */
    public static function port(string $type)
    {
        switch ($type) {
            case 'proxmox':
                return 8006;
            case 'pterodactyl':
            case 'wisp':
                return 443;
            case 'plesk':
                return 8443;
            default: return 80;
        }
    }

    private function migrateServices(bool $skipPending)
    {
        /*DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('services')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');*/
        if (Service::count() != 0) {
            $this->info('[Services] Skipped because services table is not empty');

            return;
        }

        $results = \DB::connection('migrate')->table('services')->get();
        foreach ($results as $result) {
            try {
                if ($skipPending && $result->state == 'pending') {
                    continue;
                }
                $type = $result->type;
                // type unique pour proxmox sur la v2
                if ($type == 'proxmox.lxc' || $type == 'proxmox.kvm') {
                    $type = 'proxmox';
                }
                if ($type == 'plesk.reseller' || $type == 'plesk.hosting') {
                    $type = 'plesk';
                }
                $service = Service::create([
                    'id' => $result->id,
                    'name' => $this->decode($result->name),
                    'customer_id' => $result->user_id,
                    'product_id' => $result->product_id,
                    'type' => $type,
                    'price' => $result->price,
                    'billing' => strtolower($result->recurringname),
                    'currency' => 'EUR',
                    'initial_price' => $result->price,
                    'server_id' => $result->server_id,
                    'status' => $this->formatServiceStatus($result->state),
                    'expires_at' => $result->expire_at,
                    'suspended_at' => $result->suspend_at,
                    'cancelled_at' => $result->cancel_at,
                    'created_at' => $result->created_at,
                    'updated_at' => $result->updated_at,
                ]);
                $service->id = $result->id;
                $service->created_at = $result->created_at;
                $service->updated_at = $result->updated_at;
                $service->save();
                $this->info(sprintf('[%d] : Service %s migrated ', $result->id, $result->id));
            } catch (\Exception $e) {
                if (Product::find($result->product_id) == null) {
                    $this->error(sprintf('[%d] : Product %d not found, Service not imported', $result->id, $result->product_id));

                    continue;
                }
                $this->error(sprintf('[%d] : %s', $result->id, $e->getMessage()));
            }
        }
        $this->migrateProxmoxService();
    }

    private function migrateInvoices(bool $skipPending)
    {
        /*DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('invoices')->truncate();
        DB::table('invoice_items')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');*/
        if (Invoice::count() != 0) {
            $this->info('[Invoices] Skipped because invoices table is not empty');

            return;
        }
        $results = \DB::connection('migrate')->table('transactions')->get();
        foreach ($results as $result) {
            try {
                if ($skipPending && $result->state == 'Pending') {
                    continue;
                }
                $items = \DB::connection('migrate')->table('transactions_items')->where('transaction_id', $result->id)->get();

                $subtotal = collect($items)->sum('price');
                $setup = collect($items)->sum('setupfee');
                $tax = collect($items)->sum('tax');
                $year = Carbon::createFromFormat('Y-m-d H:i:s', $result->created_at)->format('Y');
                $month = Carbon::createFromFormat('Y-m-d H:i:s', $result->created_at)->format('m');
                $invoice_number = Invoice::generateInvoiceNumber($year.'-'.$month);
                $invoice = Invoice::create([
                    'id' => $result->id,
                    'customer_id' => $result->user_id,
                    'status' => $this->formatInvoiceStatus($result->state),
                    'currency' => $result->currency,
                    'total' => $result->price,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'setupfees' => $setup,
                    'discount' => '[]',
                    'notes' => 'Imported from clientxcms v1 on '.date('Y-m-d H:i:s'),
                    'due_date' => Carbon::createFromFormat('Y-m-d H:i:s', $result->created_at)->addDays(7),
                    'external_id' => empty($result->transaction_id) ? null : $result->transaction_id,
                    'created_at' => $result->created_at,
                    'updated_at' => $result->updated_at,
                    'paymethod' => $this->formatGatewayname($result->payment_type),
                    'invoice_number' => $invoice_number,
                    'paid_at' => $result->created_at,
                ]);
                $invoice->id = $result->id;
                $invoice->created_at = $result->created_at;
                $invoice->updated_at = $result->updated_at;
                $invoice->save();
                $items = $items->toArray();
                foreach ($items as $i => $item) {
                    $discount_price = 0;
                    $discount_setupfee = 0;
                    if (isset($items[$i + 1])) {
                        if ($items[$i + 1]->tablename == 'discounts' || $items[$i + 1]->tablename == 'coupons') {
                            $discount = $items[$i + 1]->price;
                            $discount_setupfees = $items[$i + 1]->setupfee;
                        }
                    }
                    if ($item->tablename == 'discounts' || $item->tablename == 'coupons') {
                        continue;
                    }
                    if ($item->tablename == 'products') {
                        $type = 'service';
                    }
                    if ($item->tablename == 'services') {
                        $type = 'renewal';
                    }
                    if ($item->tablename == 'custom_items') {
                        $type = CustomItem::CUSTOM_ITEM;
                        CustomItem::firstOrCreate([
                            'id' => $item->type_id,
                            'name' => $item->name,
                            'description' => '',
                            'unit_price' => $item->price / $item->quantity,
                            'unit_setupfees' => $item->setupfee / $item->quantity,
                        ]);
                    }
                    $invoiceItem = InvoiceItem::create([
                        'invoice_id' => $result->id,
                        'name' => $item->name,
                        'description' => $item->description ?? '',
                        'quantity' => $item->quantity,
                        'unit_price_ht' => ($item->price / $item->quantity) - $discount_price,
                        'unit_price_ttc' => $item->price / $item->quantity,
                        'unit_setup_ht' => ($item->setupfee / $item->quantity) - $discount_setupfee,
                        'unit_setup_ttc' => $item->setupfee / $item->quantity,
                        'data' => json_encode([]),
                        'created_at' => $result->created_at,
                        'updated_at' => $result->updated_at,
                        'type' => $type,
                        'related_id' => $item->type_id,
                        'delivered_at' => $item->delivered_at,
                        'refunded_at' => $item->refunded_at,
                    ]);
                    $invoiceItem->id = $item->id;
                    $invoiceItem->created_at = $result->created_at;
                    $invoiceItem->updated_at = $result->updated_at;
                    $invoiceItem->save();
                    if ($discount_setupfee != 0 || $discount_price != 0) {
                        Invoice::find($result->id)->recalculate();
                    }
                    $this->info(sprintf('[%d] : Invoice %s migrated ', $result->id, $result->id));
                }
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] : %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function migrateProxmoxTable()
    {
        $this->migrateProxmoxOses();
        $this->migrateProxmoxTemplates();
        $this->migrateProxmoxConfig();
        $this->migrateProxmoxIPAM();
    }

    private function formatServiceStatus($status)
    {
        switch ($status) {
            case 'Pending':
                return 'pending';
            case 'Online':
                return 'active';
            case 'Suspended':
                return 'suspended';
            case 'Cancelled':
                return 'cancelled';
            case 'Terminated':
            case 'Expired':
                return 'expired';
            default:
                return 'pending';
        }
    }

    private function formatInvoiceStatus($state)
    {
        switch ($state) {
            case 'Pending':
                return 'pending';
            case 'Completed':
                return 'paid';
            case 'Cancelled':
                return 'cancelled';
            case 'Refunded':
                return 'refunded';
            case 'Draft':
                return 'draft';
            default:
                return 'pending';
        }
    }

    private function formatGatewayname(string $name)
    {
        switch ($name) {
            case 'paypal':
                return 'paypal_express_checkout';
            case 'stripe':
                return 'stripe';
            case 'wallet':
                return 'balance';
            default:
                return 'stripe';
        }
    }

    private function migrateProxmoxOses()
    {
        if (! DB::connection('migrate')->getSchemaBuilder()->hasTable('proxmox_oses')) {
            $this->info('[Proxmox] Skipped because proxmox oses table not found');

            return;
        }
        if (! class_exists(ProxmoxOS::class)) {
            $this->info('[Proxmox] Skipped because proxmox oses model not found');

            return;
        }
        if (ProxmoxOS::count() != 0) {
            $this->info('[Proxmox] Skipped because proxmox oses table is not empty');

            return;
        }
        $results = \DB::connection('migrate')->table('proxmox_oses')->get();
        foreach ($results as $result) {
            try {
                $oses = array_values($result->osnames);
                $current = current($oses);
                $server = array_keys($result->osnames);
                $server = current($server);
                $oses = json_encode([$server => ['pve' => $current]]);
                ProxmoxOS::insert([
                    'id' => $result->id,
                    'name' => $result->name,
                    'osnames' => $oses,
                    'created_at' => Carbon::now(),
                ]);
                $this->info(sprintf('[%d] : Proxmox OS %s migrated ', $result->id, $result->name));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] : %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function migrateProxmoxTemplates()
    {
        if (! DB::connection('migrate')->getSchemaBuilder()->hasTable('proxmox_templates')) {
            $this->info('[Proxmox] Skipped because proxmox templates table not found');

            return;
        }
        if (! class_exists(ProxmoxTemplates::class)) {
            $this->info('[Proxmox] Skipped because proxmox templates model not found');

            return;
        }
        if (ProxmoxTemplates::count() != 0) {
            $this->info('[Proxmox] Skipped because proxmox templates table is not empty');

            return;
        }
        $results = \DB::connection('migrate')->table('proxmox_templates')->get();
        foreach ($results as $result) {
            try {
                $vmids = array_values($result->vmids);
                $current = current($vmids);
                $server = array_keys($result->vmids);
                $server = current($server);
                $vmids = json_encode([$server => ['pve' => $current]]);
                ProxmoxTemplates::insert([
                    'id' => $result->id,
                    'name' => $result->name,
                    'vmids' => $vmids,
                    'created_at' => Carbon::now(),
                ]);
                $this->info(sprintf('[%d] : Proxmox Template %s migrated ', $result->id, $result->name));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] : %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function migrateProxmoxConfig()
    {
        if (! DB::connection('migrate')->getSchemaBuilder()->hasTable('proxmox_config')) {
            $this->info('[Proxmox] Skipped because proxmox config table not found');

            return;
        }
        if (! class_exists(ProxmoxConfigModel::class)) {
            $this->info('[Proxmox] Skipped because proxmox config model not found');

            return;
        }
        if (ProxmoxConfigModel::count() != 0) {
            $this->info('[Proxmox] Skipped because proxmox config table is not empty');

            return;
        }
        $results = \DB::connection('migrate')->table('proxmox_config')->get();
        foreach ($results as $result) {
            try {
                ProxmoxConfigModel::insert([
                    'id' => $result->id,
                    'product_id' => $result->product_id,
                    'memory' => $result->memory,
                    'disk' => $result->disk,
                    'type' => $result->type == 'lxc' ? 'lxc' : 'qemu',
                    'node' => $result->node,
                    'storage' => $result->storage,
                    'cores' => $result->cores,
                    'sockets' => $result->sockets,
                    'templates' => $result->templates == 'null' ? '[]' : $result->templates,
                    'oses' => $result->oses == 'null' ? '[]' : $result->oses,
                    'server_id' => $result->server_id ?? Server::where('type', 'proxmox')->first()->id,
                    'rate' => $result->bwlimit,
                    'max_reinstall' => $result->maxreinstall,
                    'max_backups' => $result->maxbackup,
                    'max_snapshots' => $result->maxsnapshot,
                    'created_at' => Carbon::now(),
                ]);
                $this->info(sprintf('[%d] : Proxmox Config %s migrated ', $result->id, $result->id));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] : %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function migrateProxmoxService()
    {
        $services = Service::where('type', 'proxmox')->get();
        foreach ($services as $service) {
            if ($service->getMetadata('vmid') == null) {
                $proxmox = DB::connection('migrate')->table('proxmox_vps')->where('service_id', $service->id)->first();
                if ($proxmox == null) {
                    $this->error(sprintf('[%d] : Proxmox VPS not found', $service->id));

                    continue;
                }
                $service->attachMetadata('vmid', $proxmox->vmid);
                $service->attachMetadata('node', $proxmox->node);
                $service->attachMetadata('type', $proxmox->type);
                $service->attachMetadata('config', ProxmoxConfigModel::where('product_id', $service->product_id)->first())->toArray();
                $this->info(sprintf('[%d] : Proxmox VPS %s migrated ', $service->id, $proxmox->vmid));
            }
        }
    }

    private function migratePleskTable()
    {
        $this->migratePleskConfig();
        $this->migratePleskService();
    }

    private function migratePleskConfig()
    {

        if (! DB::connection('migrate')->getSchemaBuilder()->hasTable('plesk_configs')) {
            $this->info('[Plesk] Skipped because plesk config table not found');

            return;
        }
        if (! class_exists(PleskConfigModel::class)) {
            $this->info('[Plesk] Skipped because plesk config model not found');

            return;
        }
        if (PleskConfigModel::count() != 0) {
            $this->info('[Plesk] Skipped because plesk config table is not empty');

            return;
        }
        $results = \DB::connection('migrate')->table('plesk_configs')->get();
        foreach ($results as $result) {
            try {
                PleskConfigModel::insert([
                    'id' => $result->id,
                    'product_id' => $result->product_id,
                    'planname' => $result->planname,
                    'type' => $result->type,
                    'server_id' => $result->server_id,
                ]);
                $this->info(sprintf('[%d] : Plesk Config %s migrated ', $result->id, $result->planname));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] : %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function migratePleskService()
    {
        $services = Service::where('type', 'plesk.hosting')->get();
        foreach ($services as $service) {
            if ($service->getMetadata('webspace_id') == null) {
                $plesk = DB::connection('migrate')->table('plesk_hosting')->where('service_id', $service->id)->first();
                if ($plesk == null) {
                    $this->error(sprintf('[%d] : Plesk Webspace not found', $service->id));

                    continue;
                }
                $service->attachMetadata('webspace_id', $plesk->webspace_id);
                $service->attachMetadata('domain', $plesk->domain);
                $service->attachMetadata('type', 'hosting');
                $this->info(sprintf('[%d] : Plesk webspace %s migrated ', $service->id, $plesk->webspace_id));
            }
        }
        $services = Service::where('type', 'plesk.reseller')->get();
        foreach ($services as $service) {
            if ($service->getMetadata('reseller_id') == null) {
                $plesk = DB::connection('migrate')->table('plesk_resellers')->where('service_id', $service->id)->first();
                if ($plesk == null) {
                    $this->error(sprintf('[%d] : Plesk Reseller not found', $service->id));

                    continue;
                }
                $service->attachMetadata('reseller_id', $plesk->reseller_id);
                $service->attachMetadata('login', 'Not defined');
                $service->attachMetadata('type', 'reseller');
                $this->info(sprintf('[%d] : Plesk Reseller %s migrated ', $service->id, $plesk->reseller_id));
            }
        }
    }

    private function migrateProxmoxIPAM()
    {
        if (! DB::connection('migrate')->getSchemaBuilder()->hasTable('proxmox_addresses')) {
            $this->info('[Proxmox] Skipped because proxmox addresses table not found');

            return;
        }
        if (! class_exists(ProxmoxIPAM::class)) {
            $this->info('[Proxmox] Skipped because proxmox ipam model not found');

            return;
        }
        if (ProxmoxIPAM::count() != 0) {
            $this->info('[Proxmox] Skipped because proxmox ipam table is not empty');

            return;
        }
        $results = \DB::connection('migrate')->table('proxmox_addresses')->get();
        foreach ($results as $result) {
            try {
                $address = new \App\Modules\Proxmox\Models\ProxmoxIPAM;
                [$address->ip, $address->netmask] = explode('/', $result->ip);
                $address->gateway = $result->gw;
                $address->ipv6_gateway = $result->gw6;
                $address->mac = $result->hwaddr;
                $address->is_primary = 1;
                $address->service_id = $result->service_id;
                $address->status = ($result->service_id == null) ? 'available' : 'used';
                $address->save();
                $this->info(sprintf('[%d] : Proxmox IPAM %s migrated ', $result->id, $result->ip));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] : %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function migrateSupport()
    {
        $this->migrateSupportDepartment();
        $this->migrateSupportTicket();
    }

    private function migrateSupportDepartment()
    {
        if (! DB::connection('migrate')->getSchemaBuilder()->hasTable('support_departments')) {
            $this->info('[Support] Skipped because support departments table not found');

            return;
        }
        if (! class_exists(SupportDepartment::class)) {
            $this->info('[Support] Skipped because support departments model not found');

            return;
        }
        if (SupportDepartment::count() != 0) {
            $this->info('[Support] Skipped because support departments table is not empty');

            return;
        }
        $results = \DB::connection('migrate')->table('support_departments')->get();
        foreach ($results as $result) {
            try {
                SupportDepartment::insert([
                    'id' => $result->id,
                    'name' => $this->decode($result->name),
                    'description' => $this->decode($result->description),
                    'icon' => $result->icon,
                    'created_at' => Carbon::now(),
                ]);
                $this->info(sprintf('[%d] : Support Department %s migrated ', $result->id, $result->name));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] : %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function migrateSupportTicket()
    {
        if (! DB::connection('migrate')->getSchemaBuilder()->hasTable('support_tickets')) {
            $this->info('[Support] Skipped because support tickets table not found');

            return;
        }
        if (! class_exists(SupportTicket::class)) {
            $this->info('[Support] Skipped because support tickets model not found');

            return;
        }
        if (SupportTicket::count() != 0) {
            $this->info('[Support] Skipped because support tickets table is not empty');

            return;
        }
        $results = \DB::connection('migrate')->table('support_tickets')->get();
        foreach ($results as $result) {
            try {
                $ticket = SupportTicket::create([
                    'id' => $result->id,
                    'customer_id' => $result->account_id,
                    'department_id' => $result->department_id,
                    'subject' => $this->decode($result->subject),
                    'related_id' => $result->related_id,
                    'related_type' => $result->related_table,
                    'status' => $result->state == 'Closed' ? 'closed' : 'open',
                    'priority' => strtolower($result->priority),
                    'created_at' => $result->created_at,
                    'updated_at' => $result->updated_at,
                ]);
                $ticket->id = $result->id;
                $ticket->created_at = $result->created_at;
                $ticket->updated_at = $result->updated_at;
                $ticket->save();

                $messages = \DB::connection('migrate')->table('support_messages')->where('ticket_id', $result->id)->get();
                foreach ($messages as $message) {
                    $_message = SupportMessage::create([
                        'id' => $message->id,
                        'ticket_id' => $message->ticket_id,
                        'customer_id' => $message->account_id,
                        'admin_id' => $message->admin_id,
                        'message' => $this->decode($message->content),
                        'created_at' => $message->created_at,
                        'updated_at' => $message->updated_at,
                    ]);
                    $_message->id = $message->id;
                    $_message->created_at = $message->created_at;
                    $_message->updated_at = $message->updated_at;
                    $_message->save();
                }
                $this->info(sprintf('[%d] : Support Ticket %s migrated ', $result->id, $result->subject));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] : %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function migrateSocialAuth()
    {
        if (! DB::connection('migrate')->getSchemaBuilder()->hasTable('socialauth_users')) {
            $this->info('[Social Auth] Skipped because social auth table not found');

            return;
        }
        $results = \DB::connection('migrate')->table('socialauth_users')->get();
        foreach ($results as $result) {
            try {
                /** @var Customer $user */
                $user = Customer::where('id', $result->user_id)->first();
                if ($user == null) {
                    $this->error(sprintf('[%d] : User %s not found', $result->id, $result->id));

                    continue;
                }
                $user->attachMetadata('signup_social', true);
                $user->attachMetadata('social_'.$result->provider, $result->provider_id);
                $user->attachMetadata('social_'.$result->provider.'_id', 'empty');
                $user->attachMetadata('social_'.$result->provider.'_refresh_token', $result->refresh_token);
                $this->info(sprintf('[%d] : Social Auth %d migrated ', $result->id, $result->id));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] : %s', $result->id, $e->getMessage()));
            }
        }
    }
}