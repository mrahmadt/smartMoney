@extends('layouts.app')
@section('title', 'Transactions')
@section('content')
    @include('cards.range')
    @include('cards.transactions')
    @include('misc.refresh')
    @include('misc.modal-transaction')
@endsection
