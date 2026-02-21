<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;


// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/', function () {
    return redirect('/budgets');
})->middleware(['auth', 'verified'])->name('home');

Route::get('/dashboard', function () {
    return redirect('/budgets');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::get('/budgets/{budget_id}/{start?}/{end?}', [App\Http\Controllers\BudgetController::class, 'show'])->middleware(['auth', 'verified']);
Route::get('/budgets', [App\Http\Controllers\BudgetController::class, 'index'])->middleware(['auth', 'verified'])->name('budgets');;
Route::get('/transactions', [App\Http\Controllers\TransactionController::class, 'listTransactions'])->middleware(['auth', 'verified'])->name('transactions');

Route::get('/profile/autoRefreshAPIToken', [App\Http\Controllers\ProfileController::class, 'autoRefreshAPIToken'])->middleware(['auth', 'verified']);
Route::get('/profile/generateAPIToken', [App\Http\Controllers\ProfileController::class, 'generateAPIToken'])->middleware(['auth', 'verified']);
Route::get('/profile/generateAlertToken', [App\Http\Controllers\ProfileController::class, 'generateAlertToken'])->middleware(['auth', 'verified']);

Route::get('/alerts', [App\Http\Controllers\AlertController::class, 'index'])->middleware(['auth', 'verified'])->name('alerts');
Route::get('/alerts/show/{id}', [App\Http\Controllers\AlertController::class, 'show'])->middleware(['auth', 'verified']);


require __DIR__.'/auth.php';
