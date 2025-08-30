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


namespace App\DTO\Core;

use Http;

class WebhookDTO
{
    const URL_KEY = '__url';

    public string $event;

    public ?string $url = null;

    public ?string $message = null;

    /**
     * @var callable
     */
    private $variables;

    private array $metadata;

    /**
     * @var callable
     */
    private $webhook;

    private function disable()
    {
        $this->url = null;
        $this->message = null;
    }

    public function __construct(string $event, callable $webhook, callable $variables, ?string $url = null, array $metadata = [])
    {
        if (! $url) {
            $this->disable();
        }
        $this->event = $event;
        $this->url = $url;
        $this->variables = $variables;
        $this->metadata = $metadata;
        $this->webhook = $webhook;
    }

    public function isDisabled(): bool
    {
        return ! $this->url;
    }

    public function getVariables(array $params = [])
    {
        return call_user_func_array($this->variables, $params);
    }

    public function send(array $params = [])
    {
        $variables = $this->getVariables($params);
        if (empty($variables)) {
            return;
        }
        $variables['%appname%'] = config('app.name');
        $variables['%appurl%'] = setting('app.url');
        $data = call_user_func($this->webhook, $variables);
        $data = $this->remplaceInArray($data, $variables);
        try {
            Http::post($this->url, $data)->json();
        } catch (\Exception $e) {
        }
    }

    private function isMessageJson(): bool
    {
        return json_decode($this->message) !== null;
    }

    private function getEmbed()
    {
        return $this->isMessageJson() ? json_decode($this->message) : ['embeds' => []];
    }

    private function remplaceInArray(array $array, array $variables): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->remplaceInArray($value, $variables);
            } else {
                $array[$key] = str_replace(array_keys($variables), array_values($variables), $value);
            }
        }

        return $array;
    }
}
