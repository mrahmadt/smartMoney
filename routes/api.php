<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SMSController;
use App\Http\Controllers\TransactionController;


Route::post('/sms/filter', [SMSController::class, 'store']);


Route::get('/transaction/{id}', [TransactionController::class, 'index'])->middleware('auth:sanctum');

Route::post('/alerts', [App\Http\Controllers\AlertController::class, 'create'])->middleware(['auth:sanctum']);
Route::get('/notifications/send', [App\Http\Controllers\NotificationManagerController::class, 'send'])->middleware(['auth:sanctum']);

Route::post('/notifications/subscribe', [App\Http\Controllers\NotificationManagerController::class, 'subscribe'])->middleware(['auth:sanctum']);
Route::post('/notifications/unsubscribe', [App\Http\Controllers\NotificationManagerController::class, 'unsubscribe'])->middleware(['auth:sanctum']);


Route::post('/webhook/checkTransactionAmount', [App\Http\Controllers\TransactionController::class, 'checkTransactionAmount']);

