<?php

namespace App\Http\Controllers;

use App\Exports\SubscriptionExport;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Models\Contact;
use App\Services\Subscriptions;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = $request->input('perPage', 15);
        // Set all statuses active by default when none are provided
        $status = $request->has('status') ? $request->input('status') : ['active', 'canceled', 'incomplete_expired', 'past_due'];
        $allSources = Subscription::select('entity_resource_name')
            ->whereNotNull('entity_resource_name')
            ->distinct()
            ->pluck('entity_resource_name')
            ->toArray();

        $prioritySources = array_merge(
            Subscription::select('entity_resource_name')
            ->where('entity_resource_name', 'like', 'M.%')
            ->where('entity_resource_name', 'not like', 'M. 💎%')
            ->distinct()
            ->pluck('entity_resource_name')
            ->toArray(),
            ['Únete a Créetelo Mensual', 'Únete a Créetelo Anual']
        );

        $otherSources = array_diff($allSources, $prioritySources);
        // Changed to use all sources as default
        $source = $request->input('source', $allSources);
        $source_type = $request->input('source_type', ['funnel','membership','payment_link']);
        $provider_type = $request->input('provider_type', ['stripe', 'paypal']);
        $selectedTags = $request->input('tags', []);
        $startDate = $request->input('startDate', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('endDate', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        // Convert array inputs from string if they come from query string
        if (is_string($status)) {
            $status = explode(',', $status);
        }
        if (is_string($source)) {
            $source = explode(',', $source);
        }
        if (is_string($source_type)) {
            $source_type = explode(',', $source_type);
        }
        if (is_string($provider_type)) {
            $provider_type = explode(',', $provider_type);
        }
        if (is_string($selectedTags)) {
            $selectedTags = explode(',', $selectedTags);
        }
        
        // Start building the query
        $query = Subscription::query()->with('contact');
        
        // Apply filters
        if (!empty($search)) {
            $query->whereHas('contact', function($q) use ($search) {
            $q->where('first_name', 'like', '%' . $search . '%')
              ->orWhere('last_name', 'like', '%' . $search . '%')
              ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if (!empty($status)) {
            $query->whereIn('status', $status);
        }

        if (!empty($source)) {
            $query->whereIn('entity_resource_name', $source);
        }

        if (!empty($source_type)) {
            $query->whereIn('source_type', $source_type);
        }

        if (!empty($provider_type)) {
            $query->whereIn('provider_type', $provider_type);
        }

        if ($startDate && $endDate) {
            $query->where(function($q) use ($startDate, $endDate, $status) {
            if (in_array('canceled', $status)) {
                // If status includes 'canceled', filter by cancelled_at date
                $q->where(function($subQ) use ($startDate, $endDate) {
                $subQ->where('status', 'canceled')
                     ->whereBetween('cancelled_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                     ]);
                });
            }
            
            // For all other statuses, filter by start_date
            $q->orWhere(function($subQ) use ($startDate, $endDate, $status) {
                $subQ->whereNotIn('status', ['canceled'])
                 ->whereBetween('start_date', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                 ]);
            });
            });
        }
        
        // Filter by tags
        if (!empty($selectedTags)) {
            $query->whereHas('contact', function($q) use ($selectedTags) {
                $q->where(function($subQuery) use ($selectedTags) {
                    foreach ($selectedTags as $tag) {
                        // Handle JSON format
                        $subQuery->orWhere(function($jsonQuery) use ($tag) {
                            $jsonQuery->whereRaw('JSON_CONTAINS(tags, ?)', ['"' . $tag . '"'])
                                     ->orWhere('tags', 'like', '%"' . $tag . '"%');
                        });
                        
                        // Handle comma-separated format
                        $subQuery->orWhere(function($csvQuery) use ($tag) {
                            $csvQuery->orWhere('tags', '=', $tag)
                                   ->orWhere('tags', 'like', $tag . ',%')
                                   ->orWhere('tags', 'like', '%,' . $tag . ',%')
                                   ->orWhere('tags', 'like', '%,' . $tag);
                        });
                    }
                });
            });
        }
        
        // Calculate total amount for active subscriptions
        $totalAmount = (clone $query)->where('status', 'active')->sum('amount');
        
        // Apply pagination using Laravel's native pagination
        $subscriptions = $query->paginate($perPage)->appends($request->except('page'));
        
        // Get unique source types for filtering
        $sourceTypes = Subscription::select('source_type')
            ->distinct()
            ->whereNotNull('source_type')
            ->orderBy('source_type')
            ->pluck('source_type')
            ->toArray();
            
        // Get unique entity resource names
        $sourceNames = Subscription::select('entity_resource_name')
            ->distinct()
            ->whereNotNull('entity_resource_name')
            ->pluck('entity_resource_name')
            ->toArray();
            
        // Get filtered source names based on selected source types
        $filteredSourceNames = [];
        if (!empty($source_type)) {
            $filteredSourceNames = Subscription::select('entity_resource_name')
                ->distinct()
                ->whereNotNull('entity_resource_name')
                ->whereIn('source_type', $source_type)
                ->pluck('entity_resource_name')
                ->toArray();
        } else {
            $filteredSourceNames = $sourceNames;
        }
        
        // Available tags
        $availableTags = [
            'wowfriday_plan mensual',
            'wowfriday_plan anual',
            'creetelo_mensual',
            'créetelo_mensual',
            'creetelo_anual',
            'créetelo_anual',
            'bj25_compro_anual',
            'bj25_compro_mensual',
            'creetelo_cancelado'
        ];
        
        // No results message
        $noResultsMessage = $subscriptions->isEmpty() ? 'No se encontraron registros con los filtros aplicados.' : '';
        
        // Pass data to view
        return view('admin.subscriptions.index', compact(
            'subscriptions',
            'sourceTypes',
            'sourceNames',
            'availableTags',
            'filteredSourceNames',
            'search',
            'perPage',
            'status',
            'source',
            'source_type',
            'provider_type',
            'selectedTags',
            'startDate',
            'endDate',
            'totalAmount',
            'noResultsMessage',
            'prioritySources',
            'otherSources'
        ));
    }

    public function get()
    {
        try {
            $transactions = new Subscriptions();
            $transactionsCreated = 0;
            $existingTransactions = 0;
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
            for ($i = 0; $i <= $umberPage; $i++) {
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
                        'start_date' => Carbon::parse($data['createdAt'] ?? null)
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

    public function getById($id)
    {
        try {
            $transactions = new Subscriptions();
            $response = $transactions->getById($id);
            
            return response()->json([
                'success' => true,
                'data' => $response
            ]);
        } catch (Exception $e) {
            Log::error('Error getting subscription:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error getting subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $search = $request->input('search', '');
        $status = $request->has('status') ? $request->input('status') : ['active', 'canceled', 'incomplete_expired', 'past_due'];
        $source = $request->input('source', []);
        $source_type = $request->input('source_type', ['funnel','membership','payment_link']);
        $provider_type = $request->input('provider_type', ['stripe', 'paypal']);
        $selectedTags = $request->input('tags', []);
        $startDate = $request->input('startDate', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('endDate', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        // Convert array inputs from string if they come from query string
        if (is_string($status)) {
            $status = explode(',', $status);
        }
        if (is_string($source)) {
            $source = explode(',', $source);
        }
        if (is_string($source_type)) {
            $source_type = explode(',', $source_type);
        }
        if (is_string($provider_type)) {
            $provider_type = explode(',', $provider_type);
        }
        if (is_string($selectedTags)) {
            $selectedTags = explode(',', $selectedTags);
        }
        
        // Start building the query
        $query = Subscription::query()->with('contact');
        
        // Apply filters
        if (!empty($search)) {
            $query->whereHas('contact', function($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                  ->orWhere('last_name', 'like', '%' . $search . '%');
            });
        }

        if (!empty($status)) {
            $query->whereIn('status', $status);
        }

        if (!empty($source)) {
            $query->whereIn('entity_resource_name', $source);
        }

        if (!empty($source_type)) {
            $query->whereIn('source_type', $source_type);
        }

        if (!empty($provider_type)) {
            $query->whereIn('provider_type', $provider_type);
        }

        if ($startDate && $endDate) {
            $query->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])->orWhereBetween('cancelled_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            });
        }
        
        // Filter by tags
        if (!empty($selectedTags)) {
            $query->whereHas('contact', function($q) use ($selectedTags) {
                $q->where(function($subQuery) use ($selectedTags) {
                    foreach ($selectedTags as $tag) {
                        // Handle JSON format
                        $subQuery->orWhere(function($jsonQuery) use ($tag) {
                            $jsonQuery->whereRaw('JSON_CONTAINS(tags, ?)', ['"' . $tag . '"'])
                                     ->orWhere('tags', 'like', '%"' . $tag . '"%');
                        });
                        
                        // Handle comma-separated format
                        $subQuery->orWhere(function($csvQuery) use ($tag) {
                            $csvQuery->orWhere('tags', '=', $tag)
                                   ->orWhere('tags', 'like', $tag . ',%')
                                   ->orWhere('tags', 'like', '%,' . $tag . ',%')
                                   ->orWhere('tags', 'like', '%,' . $tag);
                        });
                    }
                });
            });
        }
        
        $filename = 'subscriptions_export_' . Carbon::now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(new SubscriptionExport($query), $filename);
    }

    public function change(Request $request)
    {
        $contactId = $request->input('contact_id');
        $subscriptionId = $request->input('subscription_id');
        $contact = Contact::where('contact_id',$contactId)->with('subscription')->first();
        
        if (!$contact) {
            return redirect()->back()->with('error', 'El contacto no existe');
        }

        $subscription = Subscription::where('contactId', $contactId)->where('id',$subscriptionId)->first();
        
        if (!$subscription) {
            return redirect()->back()->with('error', 'La subscripción no existe');
        }

        // Update related subscription status and cancelled date
        if ($subscription) {
            $subscription->status = 'canceled';
            $subscription->cancelled_at = Carbon::parse($request->cancellation_date)->format('Y-m-d');
            $subscription->save();
        }

        return redirect()->back()->with('success', 'Se ha cancelado la subscripción del usuario');
    }

    //API Routes
    public function change_status(Request $request)
    {
        // Log the incoming request data to debug what's being received
        Log::info('Webhook request received', [
            'all' => $request->all(),
            'headers' => $request->headers->all(),
            'content' => $request->getContent()
        ]);
        
        // Get the contact ID from the request
        $contactEmail = $request->input('email');
        
        if (!$contactEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Email de contacto no proporcionado',
                'request_data' => $request->all()
            ], 400);
        }
        
        $contact = Contact::where('email', $contactEmail)->first();
        
        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'El contacto no existe',
                'contact_id' => $contactEmail
            ], 404);
        }
        
        $subscription = Subscription::where('contactId', $contact->contact_id)
            ->orderBy('created_at', 'desc')
            ->first();
            
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'La subscripción no existe',
                'contact_id' => $contact->contact_id
            ], 404);
        }
        
        $subscription->status = 'canceled';
        $subscription->cancelled_at = Carbon::now()->format('Y-m-d');
        $subscription->save();

        return response()->json([
            'success' => true,
            'message' => 'Se ha cancelado la subscripción del usuario',
            'subscription' => $subscription
        ]);
    }
}