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

        foreach($data['data'] as $item){
            /*if ($item->entitySourceType != 'membership') {
                continue;
            }

            if (!in_array($item->amount, [39, 390])) {
                continue;
            }*/

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

            foreach ($data['data'] as $item) {
                /*if ($item->entitySourceType != 'membership') {
                    continue;
                }*/
                if (Subscription::where('_id', $item->_id)->exists()) {
                    continue;
                }
                /*if (!in_array($item->amount, [39, 390])) {
                    continue;
                }*/

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

        $contacts = Contact::skip($offset)->take($limit)->get();
        
        foreach($contacts as $contact) {
            try {
                $contactsService = new Contacts();
                $contactData = $contactsService->get($contact->contact_id);
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
            } catch (\Exception $e) {
                Log::error('Error updating contact: ' . $e->getMessage());
            }
        }

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
            'nextOffset' => $offset + $limit
        ]);
    }
}
