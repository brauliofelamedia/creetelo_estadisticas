<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\GoHighLevelController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Mail\WelcomeMail;
use App\Services\Subscriptions;
use App\Services\Transactions;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('login', [AuthController::class, 'loginForm'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.attempt');
Route::get('forgot-password', [AuthController::class, 'forgotForm'])->name('forgot.password.form');
Route::post('forgot-password', [AuthController::class, 'forgot'])->name('forgot.password');
Route::get('magic', [AuthController::class, 'magicLoginForm'])->name('magic.login');
Route::post('magic/generate', [AuthController::class, 'magicGenerateToken'])->name('magic.generate.token');
Route::get('magic/login/{token}', [AuthController::class, 'magicLogin'])->name('magic.login.token');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/email/preview', function () {
    $user = User::first();
    return new WelcomeMail($user);
});

Route::middleware('auth')->prefix('dashboard')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');

    //Config
    Route::post('config/media', [ConfigController::class, 'media'])->name('config.media');
    Route::post('config/tags', [ConfigController::class, 'updateTags'])->name('config.tags');
    Route::resource('config', ConfigController::class);

    //Profile
    Route::post('profile/updatepassword', [ProfileController::class, 'updatePassword'])->name('profile.update.password');
    Route::resource('profile', ProfileController::class);

    //Users
    Route::resource('users', UserController::class);

    Route::get('token',[GoHighLevelController::class,'token'])->name('token');
    Route::get('connect',[GoHighLevelController::class,'connect'])->name('connect');
    Route::get('renewToken',[GoHighLevelController::class,'renewToken'])->name('renewToken');
    Route::get('finish',[GoHighLevelController::class,'finish'])->name('finish');
    Route::get('authorization',[GoHighLevelController::class,'authorization'])->name('authorization');
    Route::get('getToken',[GoHighLevelController::class,'getToken'])->name('get.token');

    //Contacts
    Route::prefix('contacts')->group(function () {
        Route::get('/',[ContactController::class,'index'])->name('contacts.index');
        Route::get('insert',[ContactController::class,'insert'])->name('contacts.insert');
    });

    //Opportunities
    Route::prefix('opportunities')->group(function () {
        Route::get('get',[OpportunityController::class,'get'])->name('opportunity.get');
    });

    //Payments
    Route::prefix('transactions')->group(function () {
        Route::get('/',[TransactionController::class,'index'])->name('transactions.index');
        Route::get('update',[TransactionController::class,'update'])->name('transactions.update');
    });

    //Subscriptions
    Route::prefix('subscriptions')->group(function () {
        Route::get('/',[SubscriptionController::class,'index'])->name('subscriptions.index');
        Route::get('get',[SubscriptionController::class,'get'])->name('subscriptions.update');
    });

    //Filters
    Route::prefix('filters')->group(function () {
        Route::get('/',[FilterController::class,'filters'])->name('filters');
        Route::get('day',[FilterController::class,'comparationForDay'])->name('filters.day');
        Route::get('month',[FilterController::class,'comparationForMonth'])->name('filters.month');
        Route::get('projection',[FilterController::class,'projection'])->name('filters.projection');
        Route::get('subscriptions',[FilterController::class,'subscriptions'])->name('filters.subscriptions');
        Route::get('updateAllJson',[FilterController::class,'updateAllJSON'])->name('update.json');
    });
});

