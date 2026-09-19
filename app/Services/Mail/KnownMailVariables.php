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

namespace App\Services\Mail;

use App\Models\Account\Customer;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;

/**
 * The percent-wrapped variables a message can actually carry. Asking the models
 * rather than keeping a copy means removing a variable from a model removes it
 * here too, which is what makes the audit tell the truth.
 */
class KnownMailVariables
{
    /** Substituted by EmailTemplate::replacePlaceholders, which knows no model. */
    private const RECIPIENT_PLACEHOLDERS = ['%firstname%', '%lastname%', '%email%', '%fullname%'];

    /** @var list<class-string> models implementing the notifiable variables contract */
    private const SOURCES = [Customer::class, Service::class, Server::class];

    /**
     * @return list<string>
     */
    public function all(): array
    {
        $variables = self::RECIPIENT_PLACEHOLDERS;
        foreach (self::SOURCES as $model) {
            $variables = array_merge($variables, $model::getNotificationContextVariables());
        }

        return array_values(array_unique($variables));
    }

    /**
     * @return list<string> variables the content uses that nothing will ever fill
     */
    public function unknownIn(string $content): array
    {
        preg_match_all('/%[a-z_]+%/i', $content, $matches);

        return array_values(array_unique(array_diff($matches[0], $this->all())));
    }
}
