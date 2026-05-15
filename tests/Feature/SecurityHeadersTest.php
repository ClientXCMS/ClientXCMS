<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_global_security_headers_are_set_on_html_responses(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
        $response->assertHeader('Permissions-Policy');
    }

    public function test_global_security_headers_are_set_on_admin_login(): void
    {
        $response = $this->get('/admin/login');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_hsts_only_set_on_https_requests(): void
    {
        $response = $this->get('/');
        $this->assertNull($response->headers->get('Strict-Transport-Security'), 'HSTS must not leak on plain HTTP responses');
    }
}
