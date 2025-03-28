<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Services\Transactions;
use Carbon\Carbon;
use App\Models\Contact;
use Illuminate\Support\Facades\Cache;

class TransactionController extends Controller
{
    public function index()
    {
        return view('admin.transactions.index');
    }

    public function update()
    {
        try {
            $contacts = Contact::all();
            $transactions = new Transactions();
            $transactionsCreated = 0;
            $errors = [];

            foreach($contacts as $contact) {
                try {
                    $response = $transactions->get(0, $contact->lead_id);
                    
                    $data = response()->json([
                        'data' => $response,
                    ]);

                    $dataFinal = json_decode(json_encode($data->getData()), true);
                    $transactionTotal = $dataFinal['data']['original']['totalCount'];
                    $transactionsData = $dataFinal['data']['original']['data'];

                    foreach($transactionsData as $data) {
                        $contactExist = Contact::where('lead_id', $data['contactId'])->first();
                    
                        if($contactExist) {
                            if(in_array($data['entitySourceName'], ['Únete a Créetelo Mensual', 'Únete a Créetelo Anual'])) {
                                $transitionCheck = Transaction::where('charge_id', $data['chargeId'])->first();

                                if(!$transitionCheck) {
                                    $transaction = new Transaction();
                                    $transaction->currency = $data['currency'];
                                    $transaction->amount = $data['amount'];
                                    $transaction->status = $data['status'];
                                    $transaction->livemode = $data['liveMode'];
                                    $transaction->entity_type = $data['entityType'];
                                    $transaction->entity_source_type = $data['entitySourceType'];
                                    $transaction->entity_id = $data['entityId'];
                                    $transaction->subscription_id = $data['subscriptionId'];
                                    $transaction->charge_id = $data['chargeId'];
                                    $transaction->summary = 'summary';
                                    $transaction->entitySourceName = $data['entitySourceName'];
                                    $transaction->create_time = Carbon::parse($data['createdAt'])->toDateTimeString();
                                    $transaction->contact_id = $contactExist->id;
                                    $transaction->save();
                                    
                                    $transactionsCreated++;
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'contact_id' => $contact->id,
                        'lead_id' => $contact->lead_id,
                        'error' => $e->getMessage()
                    ];
                }
            }

            $response = [
                'success' => true,
                'message' => 'Proceso completado',
                'data' => [
                    'transactions_created' => $transactionsCreated,
                    'contacts_processed' => $contacts->count(),
                    'errors' => $errors
                ]
            ];

            return response()->json($response, 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en el proceso',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
