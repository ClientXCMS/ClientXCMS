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


namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Account\EmailMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        if ($search != null) {
            $emails = EmailMessage::where('recipient_id', auth()->user()->id)->where('subject', 'LIKE', "%{$search}%")->orderBy('id', 'DESC')->paginate(10);

            return view('front.emails.index', compact('emails', 'search'));
        }
        $emails = EmailMessage::where('recipient_id', auth()->user()->id)->orderBy('id', 'DESC')->paginate(10);

        return view('front.emails.index', compact('emails'));
    }

    public function show(EmailMessage $email)
    {
        if ($email->recipient_id != auth()->user()->id) {
            abort(404);
        }

        return new Response($email->content, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
