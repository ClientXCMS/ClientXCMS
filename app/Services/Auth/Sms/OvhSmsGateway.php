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

namespace App\Services\Auth\Sms;

use App\Contracts\Auth\SmsGatewayContract;

class OvhSmsGateway implements SmsGatewayContract
{
    private const ENDPOINTS = [
        'ovh-eu' => 'https://eu.api.ovh.com/1.0',
        'ovh-ca' => 'https://ca.api.ovh.com/1.0',
        'ovh-us' => 'https://api.us.ovhcloud.com/1.0',
    ];

    public function send(string $to, string $message): void
    {
        $endpoint = (string) setting('mfa_sms_ovh_endpoint', 'ovh-eu');
        $applicationKey = (string) setting('mfa_sms_ovh_application_key');
        $applicationSecret = (string) setting('mfa_sms_ovh_application_secret');
        $consumerKey = (string) setting('mfa_sms_ovh_consumer_key');
        $serviceName = (string) setting('mfa_sms_ovh_service_name');
        $sender = (string) setting('mfa_sms_ovh_sender');

        if ($applicationKey === '' || $applicationSecret === '' || $consumerKey === '' || $serviceName === '' || $sender === '') {
            throw new \RuntimeException('OVH SMS gateway is missing credentials.');
        }

        $baseUrl = self::ENDPOINTS[$endpoint] ?? null;
        if ($baseUrl === null) {
            throw new \RuntimeException('OVH SMS gateway endpoint is invalid.');
        }

        $timestamp = $this->timestamp($baseUrl);
        $path = sprintf('/sms/%s/jobs', rawurlencode($serviceName));
        $url = $baseUrl.$path;
        $payload = [
            'receivers' => [$to],
            'message' => $message,
            'sender' => $sender,
            'noStopClause' => true,
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $response = \Http::withHeaders([
            'X-Ovh-Application' => $applicationKey,
            'X-Ovh-Consumer' => $consumerKey,
            'X-Ovh-Timestamp' => (string) $timestamp,
            'X-Ovh-Signature' => $this->signature($applicationSecret, $consumerKey, 'POST', $url, $body, $timestamp),
        ])
            ->timeout(8)
            ->withBody($body, 'application/json')
            ->post($url);

        if (! $response->successful()) {
            throw new \RuntimeException('OVH SMS send failed: HTTP '.$response->status());
        }
    }

    public function name(): string
    {
        return 'ovh';
    }

    public function rules(array $data): array
    {
        return [
            'mfa_sms_ovh_endpoint' => 'required|string|in:ovh-eu,ovh-ca,ovh-us',
            'mfa_sms_ovh_application_key' => 'required|string',
            'mfa_sms_ovh_application_secret' => 'required|string',
            'mfa_sms_ovh_consumer_key' => 'required|string',
            'mfa_sms_ovh_service_name' => 'required|string',
            'mfa_sms_ovh_sender' => 'required|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function endpoints(): array
    {
        return [
            'ovh-eu' => 'OVHcloud Europe',
            'ovh-ca' => 'OVHcloud Canada',
            'ovh-us' => 'OVHcloud US',
        ];
    }

    private function timestamp(string $baseUrl): int
    {
        $response = \Http::timeout(8)->get($baseUrl.'/auth/time');

        if (! $response->successful()) {
            throw new \RuntimeException('OVH SMS time sync failed: HTTP '.$response->status());
        }

        return (int) $response->body();
    }

    private function signature(string $applicationSecret, string $consumerKey, string $method, string $url, string $body, int $timestamp): string
    {
        return '$1$'.sha1($applicationSecret.'+'.$consumerKey.'+'.$method.'+'.$url.'+'.$body.'+'.$timestamp);
    }
}
