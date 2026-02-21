@extends('layouts.app')
@section('title', 'Alert')
@section('content')
<?php
$colors = [
    'transaction' => 'purple',
    'success' => 'green',
    'info' => 'blue',
    'warning' => 'yellow',
    'danger' => 'red',
];
$color = $colors[$alert->type] ?? 'gray';
if(App\Helpers\func::isRtl($alert->message)) {
    $alert->direction = 'rtl';
}else{
    $alert->direction = 'ltr';
}
?>
<div class="shadow-lg mx-2 my-2 rounded-md border border-gray-300 bg-white px-2 py-2">
        <div>
          <span class="text-xs font-bold text-gray-800 whitespace-nowrap">{{$alert->title}}</span>
          <span class="me-2 float-end text-xs inline-block py-1 px-2 rounded-full text-{{$color}}-600 bg-{{$color}}-200 last:mr-0 mr-1">{{$alert->type}}</span>
        </div>
        <div dir="{{$alert->direction}}" class="text-sx @if($alert->direction == 'rtl') text-right @endif">{!! nl2br($alert->message) !!}</div>
        <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($alert->date)->diffForhumans() }}</div>
</div>
@endsection
