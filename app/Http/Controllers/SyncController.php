<?php

namespace App\Http\Controllers;

use App\Jobs\UpdateTransactionsJob;
use Illuminate\Http\Request;
use App\Services\Transactions;
use App\Services\Subscriptions;
use App\Services\Contacts;
use App\Models\Transaction;
use Carbon\Carbon;
use App\Models\Subscription;
use App\Models\Contact;
use Nnjeim\World\Models\Country;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    public function startSync()
    {
        return redirect()->route('update.transactions');
    }

    public function updateTransaction()
    {
        $transactions = new Transactions();
        $transactions = $transactions->get(0);
        $data = collect($transactions->getData());
        $total = $data['totalCount'];
        
        return view('sync.progress', [
            'total' => $total,
            'current_process' => 'transactions',
            'next_process' => 'subscriptions'
        ]);
    }

    public function processTransactions(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = 100;
        $total = $request->input('total');

        $transactions = new Transactions();
        $transactions = $transactions->get($offset);
        $data = collect($transactions->getData());
        
        // Get all transaction IDs in this batch for more efficient checking
        $transactionIds = collect($data['data'])->pluck('_id')->filter()->toArray();
        $existingIds = Transaction::whereIn('_id', $transactionIds)->pluck('_id')->toArray();
        $existingIdsSet = array_flip($existingIds); // Convert to hash map for O(1) lookups

        foreach($data['data'] as $item){
            // Skip quickly using hash map lookup instead of database query for each item
            if (isset($existingIdsSet[$item->_id])) {
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

        $progress = min(100, round(($offset + $limit) * 100 / $total));
        $isDone = ($offset + $limit) >= $total;

        if ($isDone) {
            return response()->json([
                'progress' => 100,
                'isDone' => true,
                'redirect' => route('update.subscriptions')
            ]);
        }

        return response()->json([
            'progress' => $progress,
            'isDone' => $isDone,
            'nextOffset' => $offset + $limit
        ]);
    }

    public function updateSubscriptions()
    {
        $subscriptions = new Subscriptions();
        $subscriptions = $subscriptions->get();
        $data = $subscriptions->getData();
        $total = $data->totalCount;
        
        return view('sync.progress', [
            'total' => $total,
            'current_process' => 'subscriptions',
            'next_process' => 'contacts'
        ]);
    }

    public function processSubscriptions(Request $request)
    {
        try {
            $offset = $request->input('offset', 0);
            $limit = 100;
            $total = $request->input('total');

            if (!$total) {
                throw new \Exception('El total no puede estar vacío');
            }

            $subscriptions = new Subscriptions();
            $subscriptions = $subscriptions->get($offset);
            
            if (!$subscriptions) {
                throw new \Exception('No se pudieron obtener las suscripciones');
            }

            $data = collect($subscriptions->getData());

            if (!isset($data['data'])) {
                throw new \Exception('Formato de datos inválido');
            }
            
            // Get all subscription IDs in this batch for efficient checking
            $subscriptionIds = collect($data['data'])->pluck('_id')->filter()->toArray();
            $existingSubIds = Subscription::whereIn('_id', $subscriptionIds)->pluck('_id')->toArray();
            $existingSubIdsSet = array_flip($existingSubIds);
            
            // Collect contact IDs for contacts we'll need
            $contactIds = collect($data['data'])->pluck('contactId')->filter()->unique()->toArray();
            $existingContacts = Contact::whereIn('contact_id', $contactIds)
                ->get(['id', 'contact_id'])
                ->keyBy('contact_id');

            foreach ($data['data'] as $item) {
                // Skip if subscription already exists
                if (isset($existingSubIdsSet[$item->_id])) {
                    continue;
                }

                // Check if contact exists, if not create new one
                $contact = null;
                if (!empty($item->contactId)) {
                    $contact = $existingContacts->get($item->contactId);
                    
                    if (!$contact) {
                        $contact = new Contact();
                        $contact->contact_id = $item->contactId ?? '';
                        $contact->email = $item->contactEmail ?? '';
                        $contact->phone = $item->contactPhone ?? '';
                        $contact->save();
                        
                        // Add to our cache to avoid duplicate creation
                        $existingContacts->put($item->contactId, $contact);
                    }
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
                $sub->provider_type = $item->paymentProviderType ?? '';
                $sub->source_type = $item->entitySourceType ?? '';
                $sub->subscription_id = $item->subscriptionId ?? '';
                $sub->create_time = Carbon::parse($item->createdAt) ?? '';
                $sub->cancelled_at = @$item->cancelledAt ? Carbon::parse($item->cancelledAt)->format('Y-m-d') : null;
                $sub->contact_id = $contact ? $contact->id : null;
                $sub->save();
            }

            $progress = min(100, round(($offset + $limit) * 100 / $total));
            $isDone = ($offset + $limit) >= $total;

            if ($isDone) {
                return response()->json([
                    'progress' => 100,
                    'isDone' => true,
                    'redirect' => route('update.contacts')
                ]);
            }

            return response()->json([
                'progress' => $progress,
                'isDone' => $isDone,
                'nextOffset' => $offset + $limit
            ]);

        } catch (\Exception $e) {
            Log::error('Error en processSubscriptions: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateContacts()
    {
        $total = Contact::count();
        return view('sync.progress', [
            'total' => $total,
            'current_process' => 'contacts',
            'next_process' => null
        ]);
    }

    public function processContacts(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = 100;
        $total = $request->input('total');
        
        // Get the start time to measure performance
        $startTime = microtime(true);
        
        // Define a threshold for recent updates (e.g., last 24 hours)
        $recentThreshold = Carbon::now()->subHours(24);
        
        // Get contacts in batch, excluding recently updated ones
        $contacts = Contact::skip($offset)
                          ->take($limit)
                          ->where(function($query) use ($recentThreshold) {
                              $query->whereNull('date_update')
                                   ->orWhere('date_update', '<', $recentThreshold);
                          })
                          ->get();
        
        // Count total contacts that were skipped due to recent updates
        $skippedCount = $limit - $contacts->count();
        
        // If no contacts to process at this offset, complete the process
        if ($contacts->isEmpty()) {
            return response()->json([
                'progress' => 100,
                'isDone' => true,
                'redirect' => route('admin.index'),
                'message' => 'Sincronización completada exitosamente'
            ]);
        }
        
        // Track successful updates
        $updatedCount = 0;
        $contactsService = new Contacts();
        
        // Get all contact IDs for batch processing
        $contactIds = $contacts->pluck('contact_id')->filter()->toArray();
        $batchSize = 10; // Process in smaller batches to avoid API limits
        
        // Process contacts in smaller batches
        foreach(array_chunk($contactIds, $batchSize) as $contactIdsBatch) {
            foreach($contacts->whereIn('contact_id', $contactIdsBatch) as $contact) {
                try {
                    // Skip if contact_id is empty
                    if (empty($contact->contact_id)) {
                        Log::warning('Contact without contact_id found, skipping.', ['contact_id' => $contact->id]);
                        continue;
                    }
                    
                    $contactData = $contactsService->get($contact->contact_id);
                    
                    // Skip if no contacts returned from API
                    if (empty($contactData['contacts'])) {
                        Log::warning('No contact data returned from API', ['contact_id' => $contact->contact_id]);
                        continue;
                    }
                    
                    $data = collect($contactData['contacts']);
                    
                    // Check if API data is newer than our last update
                    if (isset($data[0]['dateUpdated']) && $contact->date_update) {
                        $apiUpdateDate = Carbon::parse($data[0]['dateUpdated']);
                        if ($apiUpdateDate <= $contact->date_update) {
                            // Skip if our data is already up to date
                            continue;
                        }
                    }
                
                    $email_explode = explode('@', $data[0]['email']);
                    $countryName = !empty($data[0]['country']) ? Country::where('iso2', $data[0]['country'])->value('name') : null;
                    $contact->country = $countryName ?? $data[0]['country'] ?? null;
                    $contact->source = $data[0]['source'] ?? null;
                    $contact->type = $data[0]['type'] ?? null;
                    $contact->address = $data[0]['address'] ?? null;
                    $contact->tags = $data[0]['tags'] ?? null;
                    $contact->location_id = $data[0]['locationId'] ?? null;
                    $contact->date_added = isset($data[0]['dateAdded']) ? Carbon::parse($data[0]['dateAdded']) : null;
                    $contact->date_update = isset($data[0]['dateUpdated']) ? Carbon::parse($data[0]['dateUpdated']) : null;
                    $contact->first_name = ucfirst($data[0]['firstNameLowerCase']) ?? $email_explode[0];
                    $contact->last_name = ucfirst($data[0]['lastNameLowerCase']) ?? null;
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
                    
                    $updatedCount++;
                } catch (\Exception $e) {
                    Log::error('Error updating contact: ' . $e->getMessage(), [
                        'contact_id' => $contact->contact_id,
                        'exception' => $e->getTraceAsString()
                    ]);
                }
            }
            // Add small delay between batches to prevent API rate limits
            if (count($contactIdsBatch) >= 5) {
                usleep(200000); // 200ms pause between batches
            }
        }
        
        // Calculate processing time
        $processingTime = round(microtime(true) - $startTime, 2);
        Log::info("Processed batch of $updatedCount contacts in $processingTime seconds", [
            'batch_size' => $contacts->count(),
            'skipped' => $skippedCount,
            'offset' => $offset,
            'processing_time' => $processingTime
        ]);
        
        $progress = min(100, round(($offset + $limit) * 100 / $total));
        $isDone = ($offset + $limit) >= $total;
        
        if ($isDone) {
            return response()->json([
                'progress' => 100,
                'isDone' => true,
                'redirect' => route('admin.index'),
                'message' => 'Sincronización completada exitosamente'
            ]);
        }
        
        return response()->json([
            'progress' => $progress,
            'isDone' => $isDone,
            'nextOffset' => $offset + $limit,
            'processed' => $updatedCount,
            'skipped' => $skippedCount,
            'processing_time' => $processingTime
        ]);
    }
}
