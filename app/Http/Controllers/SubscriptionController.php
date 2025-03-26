<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Models\Contact;
use App\Services\Subscriptions;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = config('app.subscriptions.data');
        return view('admin.subscriptions.index', compact('subscriptions'));
    }
    public function get()
    {
        try {
            $transactions = new Subscriptions();
            $transactionsCreated = 0;
            $existingTransactions = 0;  // Nuevo contador
            $errors = [];
            $allSubscriptions = [];

            $response = $transactions->get(0);
            
            $data = response()->json([
                'data' => $response,
            ]);

            $dataFinal = json_decode(json_encode($data->getData()), true);
            $subscriptionTotal = $dataFinal['data']['original']['totalCount'];
            $numberPage = ceil($subscriptionTotal / 100);

            // Primero recolectamos todos los datos
            for ($i = 0; $i <= $numberPage; $i++) {
                $response = $transactions->get($i);
                $dataTotal = json_decode(json_encode(response()->json(['data' => $response])->getData()), true);
                $allSubscriptions = array_merge($allSubscriptions, $dataTotal['data']['original']['data']);
            }

            // Procesamos todos los datos recolectados
            foreach ($allSubscriptions as $data) {
                try {
                    // Verificar si la suscripción ya existe
                    $existingSubscription = Subscription::where('subscription_id', $data['subscriptionId'] ?? null)->first();
                    if ($existingSubscription) {
                        $existingTransactions++;  // Incrementar contador
                        continue;
                    }

                    $subscription = new Subscription();
                    $subscription->fill([
                        'email' => $data['contactEmail'] ?? '',
                        'currency' => $data['currency'] ?? '',
                        'amount' => floatval($data['amount'] ?? 0),
                        'status' => $data['status'] ?? '',
                        'livemode' => $data['liveMode'] ?? false,
                        'entityType' => $data['entityType'] ?? '',
                        'entityId' => $data['entityId'] ?? '',
                        'providerType' => $data['paymentProviderType'] ?? '',
                        'sourceType' => $data['entitySourceType'] ?? '',
                        'subscription_id' => $data['subscriptionId'] ?? '',
                        'create_time' => Carbon::parse($data['createdAt'] ?? null)
                    ]);

                    if ($subscription->save()) {
                        $transactionsCreated++;
                    }

                } catch (Exception $e) {
                    Log::error('Error al guardar suscripción:', [
                        'contact_id' => $data['contactId'] ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    $errors[] = [
                        'contact_id' => $data['contactId'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'total_subscriptions_found' => count($allSubscriptions),
                'transactions_created' => $transactionsCreated,
                'existing_transactions' => $existingTransactions,  // Añadir al response
                'total_processed' => $transactionsCreated + $existingTransactions,  // Total procesado
                'errors' => $errors
            ]);

        } catch (Exception $e) {
            Log::error('Error general en proceso de suscripciones:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error en el proceso',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}