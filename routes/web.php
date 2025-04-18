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

/*Route::get('importContacts', function() {
    $contacts = new Contacts();
    $contacts = $contacts->get();
    $total = $contacts['total'];
    
    $pagination = ceil($total / 100);
    $searchAfter = null;

    for($i = 0; $i < $pagination; $i++){
        $contacts = new Contacts();
        $data = $contacts->get($i, $searchAfter);
        foreach ($data['contacts'] as $key => $item) {

            if($key > 0 && $key % 99 == 0) {
                $searchAfter = $item['searchAfter'];
            }
            
            // Skip if lead_id already exists
            if (Contact::where('lead_id', $item['id'])->exists()) {
                continue;
            }
            
            $con = new Contact();
            $con->lead_id = $item['id'] ?? '';
            $con->country = $item['country'] ?? null;
            $con->source = $item['source'] ?? null;
            $con->type = $item['type'] ?? null;
            $con->locationId = $item['locationId'] ?? null;
            $con->dateAdded = isset($item['dateAdded']) ? Carbon::parse($item['dateAdded']) : null;
            $con->dateUpdated = isset($item['dateUpdated']) ? Carbon::parse($item['dateUpdated']) : null;
            $con->firstNameLowerCase = $item['firstNameLowerCase'] ?? null;
            $con->lastNameLowerCase = $item['lastNameLowerCase'] ?? null;
            $con->email = $item['email'] ?? null;
            $con->save();
        }
    }
})->name('import.contacts');

Route::get('importTransactionsNEW', function(){
    $transactions = new Transactions();
    $transactions = $transactions->get(0);
    $data = collect($transactions->getData());
    $total = $data['totalCount'];

    $limit = 100;
    $offset = ceil($total / 100);
    
    for ($offset = 0; $offset < $total; $offset += 100) {
        $currentBatch = floor($offset / $limit) + 1;

        $transactions = new Transactions();
        $transactions = $transactions->get($offset);
        $data = collect($transactions->getData());

        foreach($data['data'] as $item){

            if ($item->entitySourceType != 'membership') {
                continue;
            }

            if (!in_array($item->amount, [39, 390])) {
                continue;
            }

            // Skip if transaction already exists
            if (Transaction::where('_id', $item->_id)->exists()) {
                continue;
            }
            
            $trans = new Transaction();
            $trans->_id = $item->_id ?? '';
            $trans->contactId = $item->contactId ?? '';
            $trans->name = $item->contactName ?? '';
            $trans->email = $item->contactEmail ?? '';
            $trans->currency = $item->currency ?? '';
            $trans->amount = $item->amount ?? 0;
            $trans->status = $item->status ?? '';
            $trans->livemode = $item->liveMode ?? false;
            $trans->entity_resource_name = $item->entitySourceName ?? '';
            $trans->entity_type = $item->entityType ?? '';
            $trans->entity_source_type = $item->entitySourceType ?? '';
            $trans->entity_id = $item->entityId ?? '';
            $trans->subscription_id = $item->subscriptionId ?? '';
            $trans->charge_id = $item->subscriptionId ?? '';
            $trans->source_type = $item->entitySourceType ?? '';
            $trans->payment_provider = $item->paymentProviderType ?? '';
            $trans->create_time = Carbon::parse($item->createdAt)->format('Y-m-d');
            $trans->contact_id = $contact->id ?? null;
            $trans->save();
        }
    }
})->name('import.transactions');

Route::get('importSubscriptions', function() {
    $subscriptions = new Subscriptions();
    $subscriptions = $subscriptions->get();
    $data = $subscriptions->getData();
    $total = $data->totalCount;
    
    $limit = 100;
    $offset = ceil($total / 100);
    
    for ($offset = 0; $offset < $total; $offset += 100) {
        $currentBatch = floor($offset / $limit) + 1;

        $subscriptions = new Subscriptions();
        $subscriptions = $subscriptions->get($offset);
        $data = collect($subscriptions->getData());

        foreach ($data['data'] as $item) {

            if ($item->entitySourceType != 'membership') {
                continue;
            }

            // Skip if subscription already exists
            if (Subscription::where('_id', $item->_id)->exists()) {
                continue;
            }
            
            // Skip if amount is not 39 or 390
            if (!in_array($item->amount, [39, 390])) {
                continue;
            }

            // Check if contact exists, if not create new one
            $contact = Contact::where('contact_id', $item->contactId)->first();
            if(!$contact){
                $contact = new Contact();
                $contact->contact_id = $item->contactId ?? '';
                $contact->email = $item->contactEmail ?? '';
                $contact->phone = $item->contactPhone ?? '';
                $contact->save();
            }

            $sub = new Subscription();
            $sub->_id = $item->_id ?? '';
            $sub->contactId = $item->contactId ?? '';
            $sub->currency = $item->currency ?? '';
            $sub->amount = $item->amount ?? 0;
            $sub->start_date = @$item->subscriptionStartDate ? Carbon::parse($item->subscriptionStartDate)->format('Y-m-d') : null;
            $sub->end_date = @$item->subscriptionEndDate ? Carbon::parse($item->subscriptionEndDate)->format('Y-m-d') : null;
            $sub->status = $item->status ?? '';
            $sub->entity_resource_name = $item->entitySourceName ?? '';
            $sub->livemode = $item->liveMode ?? false;
            $sub->entity_type = $item->entityType ?? '';
            $sub->entity_id = $item->entityId ?? '';
            $sub->provider_type = $item->paymentProviderType ?? '';
            $sub->source_type = $item->entitySourceType ?? '';
            $sub->subscription_id = $item->subscriptionId ?? '';
            $sub->create_time = Carbon::parse($item->createdAt) ?? '';
            $sub->contact_id = $contact->id ?? '';
            $sub->save();
        }
    }
})->name('import.contacts');

Route::get('updateContacts', function() {
    $contacts = Contact::all();
    
    foreach($contacts as $contact){

        $contacts = new Contacts();
        $contactData = $contacts->get($contact->contact_id);
        $data = collect($contactData['contacts']);

        $countryName = !empty($data[0]['country']) ? Country::where('iso2', $data[0]['country'])->value('name') : null;
        $contact->country = $countryName ?? $data[0]['country'] ?? null;
        $contact->source = $data[0]['source'] ?? null;
        $contact->type = $data[0]['type'] ?? null;
        $contact->address = $data[0]['address'] ?? null;
        $contact->tags = $data[0]['tags'] ?? null;
        $contact->location_id = $data[0]['locationId'] ?? null;
        $contact->date_added = isset($data[0]['dateAdded']) ? Carbon::parse($data[0]['dateAdded']) : null;
        $contact->date_update = isset($data[0]['dateUpdated']) ? Carbon::parse($data[0]['dateUpdated']) : null;
        $contact->first_name = $data[0]['firstNameLowerCase'] ?? null;
        $contact->last_name = $data[0]['lastNameLowerCase'] ?? null;
        $contact->email = $data[0]['email'] ?? null;
        $contact->website = $data[0]['website'] ?? null;
        $contact->dnd = $data[0]['dnd'] ?? null;
        $contact->state = $data[0]['state'] ?? null;
        $contact->city = $data[0]['city'] ?? null;
        $contact->company_name = $data[0]['companyName'] ?? null;
        $contact->date_of_birth = isset($data[0]['dateOfBirth']) ? Carbon::parse($data[0]['dateOfBirth']) : null;
        $contact->postal_code = $data[0]['postalCode'] ?? null;
        $contact->business_name = $data[0]['businessName'] ?? null;
        $contact->save();
    }
    
})->name('update.contacts');
*/

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

    //Create storage and optimize
    Route::get('/create-storage-and-optimize', function () {
        Artisan::call('storage:link');
        Artisan::call('optimize:clear');
        return 'Storage link creado exitosamente y limpiado el cache';
    });

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
        Route::get('sourcestype',[FilterController::class,'getSourcesByType'])->name('get.sources.type');
        Route::get('/',[FilterController::class,'filters'])->name('filters');
        Route::get('day',[FilterController::class,'comparationForDay'])->name('filters.day');
        Route::get('month',[FilterController::class,'comparationForMonth'])->name('filters.month');
        Route::get('projection',[FilterController::class,'projection'])->name('filters.projection');
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
});



