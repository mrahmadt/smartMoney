<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SMSController;
use App\Http\Controllers\DeviceTokenController;

Route::post('/sms/filter', [SMSController::class, 'store']);
Route::post('/device-token', [DeviceTokenController::class, 'store']);

