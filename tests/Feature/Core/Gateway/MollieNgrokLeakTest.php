<?php

namespace Tests\Feature\Core\Gateway;

use Tests\TestCase;

class MollieNgrokLeakTest extends TestCase
{
    public function test_mollie_gateway_does_not_leak_dev_tunnel_url(): void
    {
        $src = file_get_contents(app_path('Core/Gateway/MollieType.php'));
        $this->assertStringNotContainsString(
            'ngrok',
            $src,
            'MollieType must not hardcode any developer ngrok tunnel - redirects/webhooks would land on the dev box (or 404 once the tunnel is down)'
        );
        $this->assertStringNotContainsString(
            'a25ab4d977b1',
            $src,
            'MollieType must not hardcode the ngrok subdomain'
        );
    }
}
