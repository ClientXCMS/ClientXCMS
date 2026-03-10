@extends('admin.layouts.app')

@section('title', 'Helpdesk Bridge')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Helpdesk Bridge</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.helpdesk-bridge.settings.store') }}">
                    @csrf

                    @include('admin/shared/input', ['name' => 'helpdesk_reply_mailbox', 'label' => 'Boîte mail de réponse (local-part)', 'value' => setting('helpdesk_reply_mailbox', 'support-reply'), 'help' => 'Ex: support-reply pour support-reply+token@votre-domaine'])
                    @include('admin/shared/password', ['name' => 'helpdesk_inbound_webhook_token', 'label' => 'Token webhook inbound email', 'value' => setting('helpdesk_inbound_webhook_token', ''), 'help' => 'À configurer côté provider email entrant'])

                    <hr>

                    @include('admin/shared/checkbox', ['name' => 'helpdesk_smtp_enable', 'label' => 'Activer SMTP dédié Helpdesk', 'value' => setting('helpdesk_smtp_enable', false)])
                    @include('admin/shared/input', ['name' => 'helpdesk_mail_fromaddress', 'label' => 'From address', 'value' => setting('helpdesk_mail_fromaddress', '')])
                    @include('admin/shared/input', ['name' => 'helpdesk_mail_fromname', 'label' => 'From name', 'value' => setting('helpdesk_mail_fromname', '')])
                    @include('admin/shared/input', ['name' => 'helpdesk_mail_smtp_host', 'label' => 'SMTP host', 'value' => setting('helpdesk_mail_smtp_host', '')])
                    @include('admin/shared/input', ['name' => 'helpdesk_mail_smtp_port', 'label' => 'SMTP port', 'value' => setting('helpdesk_mail_smtp_port', '')])
                    @include('admin/shared/input', ['name' => 'helpdesk_mail_smtp_username', 'label' => 'SMTP username', 'value' => setting('helpdesk_mail_smtp_username', '')])
                    @include('admin/shared/password', ['name' => 'helpdesk_mail_smtp_password', 'label' => 'SMTP password', 'value' => setting('helpdesk_mail_smtp_password', '')])
                    @include('admin/shared/select', ['name' => 'helpdesk_mail_smtp_encryption', 'label' => 'SMTP encryption', 'value' => setting('helpdesk_mail_smtp_encryption', ''), 'options' => ['' => 'Aucune', 'tls' => 'TLS', 'ssl' => 'SSL']])

                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </form>
            </div>
        </div>
    </div>
@endsection
