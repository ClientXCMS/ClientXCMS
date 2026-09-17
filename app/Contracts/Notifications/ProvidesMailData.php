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

namespace App\Contracts\Notifications;

/**
 * A model that can hand a mail template the data it is allowed to read.
 *
 * Separate from HasNotifiableVariablesInterface on purpose: that one declares the
 * percent-wrapped variables a staff member may drop into a mass mailing, which is
 * a different question from what a transactional template may read. A model can
 * answer one without answering the other.
 *
 * Two rules make this contract worth having:
 *
 * - Scalars, lists and nested arrays only. Handing back a model would put a
 *   template one property away from everything that model can reach.
 * - Formatting happens here, not in the template. Prices, dates and translations
 *   depend on the recipient's locale, which a template has no way to know.
 */
interface ProvidesMailData
{
    /**
     * @param  string|null  $locale  the locale the template is written in
     * @return array<string, mixed>
     */
    public function toMailData(?string $locale = null): array;
}
