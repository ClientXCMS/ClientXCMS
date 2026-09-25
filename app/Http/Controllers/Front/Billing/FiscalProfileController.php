<?php

namespace App\Http\Controllers\Front\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\FiscalProfileService;
use Illuminate\Http\Request;

class FiscalProfileController extends Controller
{
    public function update(Request $request, FiscalProfileService $service)
    {
        $service->update($request->user(), $request->all());

        return redirect($this->safeReturnTo($request->input('return_to')))
            ->with('success', __('einvoicing.profile.saved'));
    }

    private function safeReturnTo(?string $url): string
    {
        if ($url && parse_url($url, PHP_URL_HOST) === parse_url(url('/'), PHP_URL_HOST)) {
            return $url;
        }

        return route('front.profile.index', ['tab' => 'einvoicing']);
    }
}
