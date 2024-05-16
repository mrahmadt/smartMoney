@extends('layouts.app')
@section('title', 'Alerts')
@section('content')
<div class="bg-gray-200 pt-2 w-full">

@php
  $colors = [
    'transaction' => 'purple',
    'success' => 'green',
    'info' => 'blue',
    'warning' => 'yellow',
    'danger' => 'red',
];

foreach($alerts as $index => $alert){
$color = $colors[$alert->type] ?? 'gray';
if(App\Helpers\func::isRtl($alerts[$index]->message)) {
    $alerts[$index]->direction = 'rtl';
}else{
    $alerts[$index]->direction = 'ltr';
}
@endphp
<div class="shadow mx-2 mb-2 rounded-lg border border-gray-300 bg-white px-2 py-2">
        <div>
          <span class="text-sm font-bold text-gray-800 whitespace-nowrap">{{$alert->title}}</span>
          <span class="me-2 float-end text-xs inline-block py-1 px-2 rounded-full text-{{$color}}-600 bg-{{$color}}-200 last:mr-0 mr-1">{{$alert->type}}</span>
        </div>
        <div dir="{{$alert->direction}}" class="text-sx @if($alert->direction == 'rtl') text-right @endif">{!! nl2br($alert->message) !!}</div>
        <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($alert->created_at)->diffForhumans() }}</div>
</div>
@php
}
@endphp
</div>
@include('misc.refresh')

@endsection
