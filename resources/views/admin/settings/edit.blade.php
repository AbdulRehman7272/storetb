@extends('layouts.admin')
@section('title', 'Settings')
@section('content')
<form class="panel form-panel" method="post" action="{{ route('admin.settings.update') }}">@csrf
<div class="form-grid">@foreach(['store_name','main_logo','mobile_logo','favicon','whatsapp','general_email','support_email','address','primary_color','accent_color','default_product_image','currency','shipping_charge','free_shipping_threshold','footer_credit','footer_credit_url'] as $key)<label>{{ str($key)->headline() }}<input name="{{ $key }}" value="{{ old($key, $settings[$key] ?? '') }}"></label>@endforeach</div><button class="btn"><i data-lucide="save"></i>Save Settings</button></form>
@endsection
