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
?>

<div class="text-center py-4">
    <button type="submit" class="flex items-center mb-3 justify-center w-full py-3 text-sm font-semibold text-white bg-primary rounded-lg hover:bg-primary/80 focus:outline-none focus:ring focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-blue-400">
        <i class="bi bi-credit-card me-2"></i>
        {{ __('client.payment-methods.add_method_with', ['method' => 'Mollie']) }}
    </button>
</div>