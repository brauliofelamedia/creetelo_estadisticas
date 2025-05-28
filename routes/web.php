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
use App\Http\Controllers\SyncController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Mail\WelcomeMail;
use App\Models\Contact;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\Contacts;
use App\Services\Subscriptions;
use App\Services\Transactions;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use Nnjeim\World\Models\Country;

Route::get('/', function(){
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

/*Route::get('/email/preview', function () {
    $user = User::first();
    return new WelcomeMail($user);
});*/

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
    Route::get('renewToken',[GoHighLevelController::class,'renewToken'])->name('renew.token');
    Route::get('finish',[GoHighLevelController::class,'finish'])->name('finish');
    Route::get('authorization',[GoHighLevelController::class,'authorization'])->name('authorization');
    Route::get('getToken',[GoHighLevelController::class,'getToken'])->name('get.token');

    //Contacts
    Route::prefix('contacts')->group(function () {
        Route::get('/',[ContactController::class,'index'])->name('contacts.index');
        Route::get('insert',[ContactController::class,'insert'])->name('contacts.insert');
        Route::get('export', [ContactController::class, 'export'])->name('contacts.export');
    });

    //Opportunities
    Route::prefix('opportunities')->group(function () {
        Route::get('get',[OpportunityController::class,'get'])->name('opportunity.get');
    });

    //Payments
    Route::prefix('transactions')->group(function () {
        Route::get('/',[TransactionController::class,'index'])->name('transactions.index');
        Route::get('update',[TransactionController::class,'update'])->name('transactions.update');
        Route::get('export', [TransactionController::class, 'export'])->name('contacts.export');
    });

    //Subscriptions
    Route::prefix('subscriptions')->group(function () {
        Route::get('/',[SubscriptionController::class,'index'])->name('subscriptions.index');
        Route::get('get',[SubscriptionController::class,'get'])->name('subscriptions.update');
        Route::get('getById/{id}',[SubscriptionController::class,'getById'])->name('subscriptions.getById');
        Route::get('export', [SubscriptionController::class, 'export'])->name('subscriptions.export');
        Route::post('change', [SubscriptionController::class, 'change_status'])->name('subscriptions.change');
    });

    //Filters
    Route::prefix('filters')->group(function () {
        Route::get('sourcestype',[FilterController::class,'getSourcesByType'])->name('get.sources.type');
        Route::get('/',[FilterController::class,'filters'])->name('filters');
        Route::get('day',[FilterController::class,'comparationForDay'])->name('filters.day');
        Route::get('month',[FilterController::class,'comparationForMonth'])->name('filters.month');
        Route::get('projection',[FilterController::class,'projection'])->name('filters.projection');
        Route::get('actives',[FilterController::class,'actives'])->name('filters.actives');
        Route::get('subscriptions',[FilterController::class,'subscriptions'])->name('filters.subscriptions');
        Route::get('updateAllJson',[FilterController::class,'updateAllJSON'])->name('update.json');
        Route::get('sources-by-type', [FilterController::class, 'getSourcesByType'])->name('filters.sources-by-type');
    });

    //Sync
    Route::prefix('sync')->group(function () {
        Route::get('transactions',[SyncController::class,'updateTransaction'])->name('update.transactions');
        Route::get('subscriptions',[SyncController::class,'updateSubscriptions'])->name('update.subscriptions');
        Route::get('contacts',[SyncController::class,'updateContacts'])->name('update.contacts');
        Route::post('process-transactions', [SyncController::class, 'processTransactions'])->name('process.transactions');
        Route::post('process-subscriptions', [SyncController::class, 'processSubscriptions'])->name('process.subscriptions');
        Route::post('process-contacts', [SyncController::class, 'processContacts'])->name('process.contacts');
        Route::get('start', [SyncController::class, 'startSync'])->name('sync.start');
    });

    // Storage link route inside auth middleware
    Route::get('/storage-link', function() {
        Artisan::call('storage:link');
        
        return "El enlace simbólico de almacenamiento ha sido creado correctamente.";
    })->name('storage.link');

    // Migrations route inside auth middleware
    Route::get('/run-migrations', function() {
        Artisan::call('migrate');
        
        return "Las migraciones se han ejecutado correctamente.";
    })->name('run.migrations');
});

// Add the missing route for filtering sources by type
Route::get('/admin/filters/get-sources-by-type', [App\Http\Controllers\FilterController::class, 'getSourcesByType'])->name('get.sources.by.type');

// Add this route if it doesn't exist
Route::get('/transactions/export', [TransactionController::class, 'export'])->name('transactions.export');

// New route to clear cache without middleware (keeping this outside auth as requested)
Route::get('/clear-cache', function() {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    
    return "Toda la caché ha sido eliminada correctamente.";
})->name('clear.cache');

Route::post('webhook/update-cancellation-date-subscription', [WebhookController::class, 'updateCancellationDateSubscription'])->name('webhook.subscription.update.date');