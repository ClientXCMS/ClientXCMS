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
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Billing\InvoiceLog;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Models\Store\Group;
use App\Models\Store\Pricing;
use App\Models\Store\Product;
use App\Modules\Pterodactyl\Models\PterodactylConfig;
use App\Modules\Wisp\Models\WispConfig;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WhmcsMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:whmcs-migrate {--skip-pending=true} {--all=false} {--dbname=whmcs} {--host=localhost} {--username=root} {--password=root} {--port=3306} {--force} {--key=whmcs} {--wisp} {--products} {--groups} {--servers} {--services} {--clients} {--pterodactyl} {--invoices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate WHMCS data to ClientXCMS';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $host = $this->option('host');
        $dbname = $this->option('dbname');
        $username = $this->option('username');
        $password = $this->option('password');
        $port = $this->option('port');
        $key = $this->option('key');
        $all = $this->option('all') == 'true';
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
        $tables = ['tblclients', 'tblproducts', 'tblpaymethods', 'tblhosting', 'tblinvoices', 'tblusers', 'tblservers', 'tblpricing', 'tblcurrencies'];
        foreach ($tables as $table) {
            if (! DB::connection('migrate')->getSchemaBuilder()->hasTable($table)) {
                $this->error("Table $table not found");

                return;
            }
        }
        $this->info('Tables found');
        $this->info('starting migration at '.now());
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
                InvoiceLog::truncate();
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->info('Data deleted');
        }
        if ($all || $this->option('servers')) {
            $this->migrateServers($key);
        }
        if ($all || $this->option('clients')) {
            $this->migrateCustomers();
        }
        if ($all || $this->option('groups')) {
            $this->migrateGroups();
        }
        if ($all || $this->option('products')) {
            $this->migrateProducts();
            $this->migratePricings();
        }
        if ($all || $this->option('pterodactyl')) {
            $this->migratePterodactylConfig();
        }
        if ($all || $this->option('wisp')) {
            $this->migrateWispConfig();
        }
        if ($all || $this->option('invoices')) {
            $this->migrateInvoices();
        }
        if ($all || $this->option('services')) {
            $this->migrateServices($key);
        }
        $this->info('Migration completed at '.now());
    }

    private function migrateServers(string $key)
    {
        if (Server::count() > 0) {
            $this->info('[Servers] Skipped because servers table is not empty');

            return;
        }
        $results = DB::connection('migrate')->table('tblservers')->get();
        foreach ($results as $result) {
            try {
                try {
                    $username = $this->decode($result->username, $key);
                    $password = $this->decode($result->password, $key);
                } catch (\Exception $e) {
                    $username = 'not defined';
                    $password = 'not defined';
                }
                $server = Server::create([
                    'id' => $result->id,
                    'name' => $result->name,
                    'hostname' => $result->hostname,
                    'type' => $this->productType($result->type),
                    'address' => empty($result->ipaddress) ? $result->hostname : $result->ipaddress,
                    'port' => $this->port($result->port, $result->type),
                    'maxaccounts' => $result->maxaccounts,
                    'username' => encrypt($username),
                    'password' => encrypt($password),
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $server->id = $result->id;
                $server->save();
                $this->info(sprintf('[%d] : Server %s migrated ', $result->id, $result->name));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function migrateCustomers()
    {
        if (Customer::count() > 0) {
            $this->info('[Customers] Skipped because customers table is not empty');

            return;
        }
        $clients = DB::connection('migrate')->table('tblclients')->get();
        $users = DB::connection('migrate')->table('tblusers')->get();
        EnvEditor::updateEnv([
            'HASH_DRIVER' => 'bcrypt',
        ]);
        $this->info('Hash driver set to bcrypt');
        foreach ($clients as $client) {
            try {
                $user = $users->where('email', $client->email)->first();
                if (! $user) {
                    $this->error(sprintf('[%d] User %s not found', $client->id, $client->email));

                    continue;
                }
                try {
                    $lastLogin = $client->lastlogin ? Carbon::createFromTimestamp($client->lastlogin) : null;
                } catch (\Exception $e) {
                    $lastLogin = null;
                }

                $customer = Customer::create([
                    'id' => $user->id,
                    'firstname' => $client->firstname,
                    'lastname' => $client->lastname,
                    'email' => $client->email,
                    'region' => $client->country,
                    'password' => $user->password,
                    'balance' => $client->credit,
                    'phone' => $client->phonenumber,
                    'address' => $client->address1,
                    'city' => $client->city,
                    'state' => $client->state,
                    'country' => $client->country,
                    'zipcode' => $client->postcode,
                    'last_login' => $lastLogin,
                    'last_ip' => $client->ip,
                    'email_verified_at' => $user->email_verified_at,
                    'is_confirmed' => $user->email_verified_at ? 1 : 0,
                    'created_at' => $client->created_at,
                    'updated_at' => now(),
                ]);
                $customer->id = $user->id;
                $customer->created_at = $this->formatDate($client->created_at);
                $customer->save();
                $this->info(sprintf('[%d] : User %s migrated ', $client->id, $client->email));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] %s', $client->id, $e->getMessage()));
            }
        }
    }

    private function formatDate($date)
    {
        try {
            if (str_contains($date, '0000-00-00') || str_contains($date, '1970-01-01') || $date == null || str_contains($date, '0001-11-30')) {
                return now()->format('Y-m-d H:i:s');
            }
            if ($date == '0000-00-00 00:00:00' || $date == '-0001-11-30 00:00:00' || $date == '1970-01-01 00:00:00') {
                return now()->format('Y-m-d H:i:s');
            }

            return $date;
        } catch (\Exception $e) {
            return now();
        }
    }

    private function migrateGroups()
    {
        if (Group::count() > 0) {
            $this->info('[Groups] Skipped because groups table is not empty');

            return;
        }
        $results = DB::connection('migrate')->table('tblproductgroups')->get();
        foreach ($results as $result) {
            try {
                Group::insert([
                    'id' => $result->id,
                    'name' => $result->name,
                    'description' => $result->headline,
                    'status' => $result->hidden ? 'hidden' : 'active',
                    'slug' => $result->slug,
                    'sort_order' => $result->order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info(sprintf('[%d] : Group %s migrated ', $result->id, $result->name));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function migratePricings()
    {
        if (Pricing::count() > 0) {
            $this->info('[Pricings] Skipped because pricings table is not empty');

            return;
        }

        $currencies = DB::connection('migrate')->table('tblcurrencies')->get();

        foreach ($currencies as $currency) {
            $results = DB::connection('migrate')->table('tblpricing')->where('type', 'product')->where('currency', $currency->id)->get();
            foreach ($results as $result) {
                try {
                    if (Product::find($result->relid) == null) {
                        $this->info(sprintf('[%d] : Product %s deleted ', $result->relid, $result->type));

                        continue;
                    }
                    Pricing::insert([
                        'id' => $result->id,
                        'related_id' => $result->relid,
                        'currency' => $currency->code,
                        'onetime' => null,
                        'setup_onetime' => null,
                        'monthly' => $result->monthly == -1 ? null : $result->monthly,
                        'setup_monthly' => $result->msetupfee == -1 ? null : $result->msetupfee,
                        'quarterly' => $result->quarterly == -1 ? null : $result->quarterly,
                        'setup_quarterly' => $result->qsetupfee == -1 ? null : $result->qsetupfee,
                        'semiannually' => $result->semiannually == -1 ? null : $result->semiannually,
                        'setup_semiannually' => $result->ssetupfee == -1 ? null : $result->ssetupfee,
                        'annually' => $result->annually == -1 ? null : $result->annually,
                        'setup_annually' => $result->asetupfee == -1 ? null : $result->asetupfee,
                        'biennially' => $result->biennially == -1 ? null : $result->biennially,
                        'setup_biennially' => $result->bsetupfee == -1 ? null : $result->bsetupfee,
                        'triennially' => $result->triennially == -1 ? null : $result->triennially,
                        'setup_triennially' => $result->tsetupfee == -1 ? null : $result->tsetupfee,
                    ]);
                    $this->info(sprintf('[%d] : Pricing %s migrated ', $result->id, $result->type));
                } catch (\Exception $e) {
                    $this->error(sprintf('[%d] %s', $result->id, $e->getMessage()));
                }
            }
        }
    }

    private function migrateProducts()
    {
        if (Product::count() > 0) {
            $this->info('[Products] Skipped because products table is not empty');

            return;
        }
        $results = DB::connection('migrate')->table('tblproducts')->get();
        foreach ($results as $result) {
            try {
                Product::insert([
                    'id' => $result->id,
                    'name' => $result->name,
                    'description' => $result->description,
                    'group_id' => $result->gid,
                    'type' => $this->productType($result->servertype),
                    'status' => $result->hidden ? 'hidden' : 'active',
                    'stock' => $result->stockcontrol ? $result->qty : -1,
                    'sort_order' => $result->order,
                    'pinned' => $result->is_featured ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info(sprintf('[%d] : Product %s migrated ', $result->id, $result->name));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function migratePterodactylConfig()
    {
        if (! class_exists(PterodactylConfig::class)) {
            $this->info('[Pterodactyl] Skipped because Pterodactyl module is not installed');

            return;
        }
        if (PterodactylConfig::count() > 0) {
            $this->info('[Pterodactyl] Skipped because Pterodactyl table is not empty');

            return;
        }
        $results = DB::connection('migrate')->table('tblproducts')->where('servertype', 'pterodactyl')->get();
        $server = Server::where('type', 'pterodactyl')->first();
        foreach ($results as $result) {
            try {
                if (! $server) {
                    $this->error('Pterodactyl server not found');

                    return;
                }
                if (empty($result->configoption7) || empty($result->configoption8)) {
                    $this->error(sprintf('[%d] : Pterodactyl %s missing eggs', $result->id, $result->name));

                    continue;
                }
                PterodactylConfig::insert([
                    'product_id' => $result->id,
                    'eggs' => json_encode([implode(PterodactylConfig::DELIMITER, [$result->configoption7, $result->configoption8])]),
                    'dedicated_ip' => $result->configoption6 == 'on' ? 1 : 0,
                    'memory' => $result->configoption3 / 1024,
                    'disk' => $result->configoption2 / 1024,
                    'io' => $result->configoption9,
                    'cpu' => $result->configoption1,
                    'swap' => empty($result->configoption4) ? 0 : $result->configoption4,
                    'location_id' => empty($result->configoption5) ? 0 : $result->configoption5,
                    'server_id' => $server->id,
                    'backups' => empty($result->configoption17) ? 0 : $result->configoption17,
                    'image' => empty($result->configoption10) ? null : $result->configoption10,
                    'startup' => empty($result->configoption12) ? null : $result->configoption12,
                    'databases' => empty($result->configoption14) ? 0 : $result->configoption14,
                    'port_range' => $result->configoption11,
                    'server_name' => htmlentities($result->configoption15),
                    'oom_kill' => $result->configoption16 == 'on' ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info(sprintf('[%d] : Pterodactyl %s migrated ', $result->id, $result->name));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function migrateWispConfig()
    {
        if (! class_exists(WispConfig::class)) {
            $this->info('[Wisp] Skipped because Wisp module is not installed');

            return;
        }
        if (WispConfig::count() > 0) {
            $this->info('[Wisp] Skipped because Wisp table is not empty');

            return;
        }
        $results = DB::connection('migrate')->table('tblproducts')->where('servertype', 'pterodactyl')->get();
        $server = Server::where('type', 'wisp')->first();
        foreach ($results as $result) {
            try {
                if (! $server) {
                    $this->error('Wisp server not found');

                    return;
                }
                if (empty($result->configoption7) || empty($result->configoption8)) {
                    $this->error(sprintf('[%d] : Wisp %s missing eggs', $result->id, $result->name));

                    continue;
                }
                WispConfig::insert([
                    'product_id' => $result->id,
                    'eggs' => json_encode([implode(PterodactylConfig::DELIMITER, [$result->configoption7, $result->configoption8])]),
                    'dedicated_ip' => $result->configoption6 == 'on' ? 1 : 0,
                    'memory' => $result->configoption3 / 1024,
                    'disk' => $result->configoption2 / 1024,
                    'io' => $result->configoption9,
                    'cpu' => $result->configoption1,
                    'swap' => empty($result->configoption4) ? 0 : $result->configoption4,
                    'location_id' => empty($result->configoption5) ? 0 : $result->configoption5,
                    'server_id' => $server->id,
                    'backups' => empty($result->configoption17) ? 0 : $result->configoption17,
                    'image' => empty($result->configoption10) ? null : $result->configoption10,
                    'startup' => empty($result->configoption12) ? null : $result->configoption12,
                    'databases' => $result->configoption14 ?? 0,
                    'port_range' => $result->configoption11,
                    'server_name' => htmlentities($result->configoption15),
                    'oom_kill' => $result->configoption16 == 'on' ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info(sprintf('[%d] : Wisp %s migrated ', $result->id, $result->name));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function decode(string $str, string $key): string
    {
        if (empty($str) || empty($key)) {
            return 'Empty string or key';
        }
        $y = base64_decode($str);
        $x = null;

        $key = sha1(md5(md5($key)).md5($key));
        $temp_key = null;
        for ($i = 0; $i < strlen($key); $i += 2) {
            $temp_key .= chr(hexdec($key[$i].$key[$i + 1]));
        }
        $key = $temp_key;
        $key_length = strlen($key);

        $key_seed = substr($y, 0, $key_length);
        $y = substr($y, $key_length, strlen($y) - $key_length);

        $z = null;
        for ($i = 0; $i < $key_length; $i++) {
            $z .= chr(ord($key_seed[$i]) ^ ord($key[$i]));
        }

        for ($i = 0; $i < strlen($y); $i++) {
            if ($i != 0 && $i % $key_length == 0) {
                $temp = sha1($z.substr($x, $i - $key_length, $key_length));
                $z = null;
                for ($j = 0; $j < strlen($temp); $j += 2) {
                    $z .= chr(hexdec($temp[$j].$temp[$j + 1]));
                }
            }

            $x .= chr(ord($z[$i % $key_length]) ^ ord($y[$i]));
        }

        return $x;
    }

    private function port(?int $port, $type)
    {
        if ($port != null) {
            return $port;
        }

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

    private function migrateServices(string $key)
    {
        if (Service::count() > 0) {
            $this->info('[Services] Skipped because services table is not empty');

            return;
        }
        $results = DB::connection('migrate')->table('tblhosting')->get();
        $currency = DB::connection('migrate')->table('tblcurrencies')->where('default', 1)->first();
        foreach ($results as $result) {
            try {
                $product = Product::find($result->packageid);
                if (! $product) {
                    $this->error(sprintf('[%d] Product #%d not found', $result->id, $result->packageid));

                    continue;
                }
                $customer = Customer::find($result->userid);
                if (! $customer) {
                    $this->error(sprintf('[%d] Customer #%d not found', $result->id, $result->userid));

                    continue;
                }
                if ($result->server != null) {
                    $server = Server::where('id', $result->server)->first();
                    if (! $server) {
                        $this->error(sprintf('[%d] Server #%s not found', $result->id, $result->server));

                        continue;
                    }
                    $serverId = $server->id;
                } else {
                    $serverId = null;
                }
                $service = Service::create([
                    'id' => $result->id,
                    'product_id' => $result->packageid,
                    'customer_id' => $result->userid,
                    'server_id' => $serverId,
                    'type' => $product->type,
                    'currency' => $currency->code,
                    'initial' => $result->firstpaymentamount,
                    'billing' => strtolower($result->billingcycle),
                    'name' => $product->name,
                    'username' => $result->username,
                    'password' => $result->password,
                    'status' => $this->formatStatus(strtolower($result->domainstatus)),
                    'suspend_reason' => $result->suspendreason,
                    'price' => $result->amount,
                    'expires_at' => $this->formatDate($result->nextduedate),
                    'notes' => $result->notes,
                    'created_at' => $this->formatDate($result->regdate),
                    'updated_at' => now(),
                ]);
                $service->id = $result->id;
                $service->created_at = $this->formatDate($result->regdate);
                $service->save();
                dump($this->formatDate($result->regdate), $result->regdate);
                $this->info(sprintf('[%d] : Service %s migrated ', $result->id, $result->id));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function formatStatus(string $status): string
    {
        switch ($status) {
            case 'active':
                return Service::STATUS_ACTIVE;
            case 'suspended':
                return Service::STATUS_SUSPENDED;
            case 'terminated':
                return Service::STATUS_EXPIRED;
            case 'pending':
                return Service::STATUS_PENDING;
            case 'fraud':
                return Service::STATUS_SUSPENDED;
            case 'cancelled':
                return Service::STATUS_CANCELLED;
            default:
                return 'pending';
        }
    }

    private function migrateInvoices()
    {
        if (Invoice::count() > 0) {
            $this->info('[Invoices] Skipped because invoices table is not empty');

            return;
        }
        $results = DB::connection('migrate')->table('tblinvoices')->get();
        $currency = DB::connection('migrate')->table('tblcurrencies')->where('default', 1)->first();
        foreach ($results as $result) {
            try {
                $customer = Customer::find($result->userid);
                if (! $customer) {
                    $this->error(sprintf('[%d] Customer #%d not found', $result->id, $result->userid));

                    continue;
                }
                $account = DB::connection('migrate')->table('tblaccounts')->where('invoiceid', $result->id)->first();
                if (! $account) {
                    $gateway = null;
                    $transid = 'WHMCS-'.$result->id;
                    $fees = 0;

                    continue;
                } else {
                    $gateway = $this->formattedGateway($account->gateway);
                    $transid = $account->transid;
                    $fees = $account->fees;
                }
                if ($transid == null) {
                    $transid = 'WHMCS-'.$result->id;
                }
                $invoice_number = $result->invoicenum;
                if (empty($invoice_number)) {
                    $year = Carbon::createFromFormat('Y-m-d', $result->date)->format('Y');
                    $month = Carbon::createFromFormat('Y-m-d', $result->date)->format('m');
                    $invoice_number = Invoice::generateInvoiceNumber($year.'-'.$month);
                }
                $invoice = Invoice::create([
                    'id' => $result->id,
                    'customer_id' => $result->userid,
                    'currency' => $currency->code,
                    'total' => $result->total,
                    'subtotal' => $result->subtotal,
                    'tax' => $result->tax,
                    'setupfees' => 0,
                    'external_id' => $transid,
                    'fees' => $fees,
                    'notes' => $result->notes.' Imported from WHMCS Gateway'.$account->gateway ?? 'none',
                    'paymethod' => $gateway,
                    'due_date' => $result->duedate,
                    'status' => $this->formatInvoiceStatus($result->status),
                    'created_at' => $result->date,
                    'updated_at' => now(),
                    'invoice_number' => $invoice_number,
                ]);
                $invoice->id = $result->id;
                $invoice->created_at = $result->date;
                $invoice->save();
                $items = DB::connection('migrate')->table('tblinvoiceitems')->where('invoiceid', $result->id)->get();
                foreach ($items as $item) {
                    $_item = InvoiceItem::create([
                        'invoice_id' => $result->id,
                        'description' => $item->description,
                        'name' => '',
                        'type' => $this->getInvoiceItemType($item->type),
                        'related_id' => $item->relid,
                        'data' => json_encode([]),
                        'quantity' => 1,
                        'unit_price_ht' => $item->amount,
                        'unit_price_ttc' => $item->amount,
                        'unit_setup_ht' => $item->taxed,
                        'unit_setup_ttc' => $item->taxed,
                        'delivered_at' => $result->datepaid != '0000-00-00 00:00:00' ? $result->datepaid : null,
                        'cancelled_at' => $result->date_cancelled != '0000-00-00 00:00:00' ? $result->date_cancelled : null,
                        'refunded_at' => $result->date_refunded != '0000-00-00 00:00:00' ? $result->date_refunded : null,
                        'created_at' => $result->date,
                        'updated_at' => now(),
                    ]);

                    $_item->created_at = $result->date;
                    $_item->save();
                }
                $this->info(sprintf('[%d] : Invoice %s migrated ', $result->id, $result->id));
            } catch (\Exception $e) {
                $this->error(sprintf('[%d] %s', $result->id, $e->getMessage()));
            }
        }
    }

    private function formatInvoiceStatus($status)
    {
        switch ($status) {
            case 'Paid':
                return Invoice::STATUS_PAID;
            case 'Cancelled':
                return Invoice::STATUS_CANCELLED;
            case 'Refunded':
                return Invoice::STATUS_REFUNDED;
            default:
                return Invoice::STATUS_PENDING;
        }
    }

    private function formattedGateway(string $gateway)
    {
        switch ($gateway) {
            case 'paypal':
            case 'paypalcheckout':
            case 'paypal_ppcpv':
                return 'paypal_express_checkout';
            default:
                return 'none';
        }
    }

    private function productType(string $type)
    {
        switch ($type) {
            case '':
                return 'none';
            case 'ProxmoxVPS':
                return 'proxmox';
            case 'plesk':
            case 'PleskExtended':
                return 'plesk';
            default: return $type;
        }
    }

    private function getInvoiceItemType($type)
    {
        switch ($type) {
            case 'Hosting':
                return 'service';
            case 'PromoHosting':
                return 'promotion';
            case 'Upgrade':
                return 'upgrade';
            default:
                return 'none';
        }
    }
}
