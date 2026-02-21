<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SMSController;

Route::post('/sms/filter', [SMSController::class, 'store']);

