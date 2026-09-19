@extends('layouts.front')

@section('title', $pageTitle ?? __('faq::messages.client.title'))

@section('content')
@include('faq::widget', [
'group' => $group ?? null,
'product' => $product ?? null,
'faqs' => $faqs ?? collect(),
'title' => $pageTitle ?? __('faq::messages.client.title'),
'description' => $pageDescription ?? __('faq::messages.client.description'),
])

@if(!empty($ctaUrl))
<div class="max-w-3xl mx-auto text-center px-4 pb-16">
  <p class="text-lg text-gray-700 dark:text-gray-300">
    {{ $ctaText ?? __('faq::messages.client.cta_text') }}
  </p>
  <a href="{{ $ctaUrl }}" class="btn btn-primary mt-4 inline-flex items-center justify-center gap-2 rounded-2xl px-6 py-3 text-base shadow-lg">
    {{ $ctaButton ?? __('faq::messages.client.cta_button') }}
  </a>
</div>
@endif
@endsection