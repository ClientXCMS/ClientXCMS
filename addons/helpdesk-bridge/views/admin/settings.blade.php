@extends('admin.settings.sidebar')
@section('title', __('helpdesk-bridge::helpdesk_bridge.admin.settings.title'))

@section('setting')
    <div class="card">
        <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400 mb-4">
            {{ __('helpdesk-bridge::helpdesk_bridge.admin.settings.title') }}
        </h4>

        <form method="POST" action="{{ route('admin.helpdesk_bridge.update') }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    @include('admin/shared/input', [
                        'name' => 'helpdesk_reply_mailbox',
                        'label' => __('helpdesk-bridge::helpdesk_bridge.admin.settings.fields.reply_mailbox'),
                        'value' => old('helpdesk_reply_mailbox', setting('helpdesk_reply_mailbox', 'support')),
                        'help' => __('helpdesk-bridge::helpdesk_bridge.admin.settings.fields.reply_mailbox_help'),
                    ])
                </div>

                <div>
                    @include('admin/shared/input', [
                        'name' => 'helpdesk_inbound_webhook_token',
                        'label' => __('helpdesk-bridge::helpdesk_bridge.admin.settings.fields.webhook_token'),
                        'value' => old('helpdesk_inbound_webhook_token', setting('helpdesk_inbound_webhook_token', '')),
                        'help' => __('helpdesk-bridge::helpdesk_bridge.admin.settings.fields.webhook_token_help'),
                    ])
                </div>
            </div>

            <div class="mt-4">
                @include('admin/shared/checkbox', [
                    'name' => 'helpdesk_bridge_create_ticket_from_inbound',
                    'label' => __('helpdesk-bridge::helpdesk_bridge.admin.settings.fields.create_ticket_from_inbound'),
                    'checked' => old('helpdesk_bridge_create_ticket_from_inbound', setting('helpdesk_bridge_create_ticket_from_inbound', true)),
                ])
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">{{ __('global.save') }}</button>
            </div>
        </form>
    </div>
@endsection
