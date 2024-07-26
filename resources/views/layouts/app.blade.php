<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
    <META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user_id" content="{{ Auth::user()->id }}">
    @if(Session::has('apiToken'))
    <meta name="apiToken" content="{{Session::get('apiToken')}}">
    @else
    <meta name="apiToken" content="">
    @endif
    <meta name="VAPID_PUBLIC_KEY" content="{{ config('webpush.vapid.public_key') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="manifest" href="/manifest.json">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <title>@yield('title')</title>
</head>
<body x-data="{modelOpen: false}">
<div class="h-screen bg-gray-200">
@yield('content')
@include('misc.nav')
</div>
<script src="/main.js?@php echo time(); @endphp"></script>
</body>
</html>