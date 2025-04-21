<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\Transactions;
use App\Services\Subscriptions;
use App\Services\Contacts;
use Nnjeim\World\Models\Country;

class FilterController extends Controller
{
    protected $transactionData = [];
    protected $projectedData = [];
    protected $projectionPeriod = '';
    protected $availableTags = [];

    public function __construct()
    {
        // Load available tags
        $this->loadAvailableTags();
    }

    protected function loadAvailableTags()
    {
        // Use a fixed list of specific tags
        $this->availableTags = [
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
    }

    protected function filterByTags($subscription, $selectedTags)
    {
        // If no tags are selected or contact doesn't exist, don't filter by tags
        if (empty($selectedTags) || !$subscription->contact) {
            return true;
        }
        
        // If contact has no tags, it doesn't match
        if (empty($subscription->contact->tags)) {
            return false;
        }
        
        $contactTags = $subscription->contact->tags;
        
        // Check if tags is a JSON string
        if (is_string($contactTags) && is_array(json_decode($contactTags, true))) {
            $contactTagsArray = json_decode($contactTags, true);
            // Check if any of the selected tags match
            foreach ($selectedTags as $tag) {
                if (in_array($tag, $contactTagsArray)) {
                    return true;
                }
            }
        } 
        // Check if tags is a comma-separated string
        else if (is_string($contactTags)) {
            $contactTagsArray = explode(',', $contactTags);
            // Check if any of the selected tags match
            foreach ($selectedTags as $tag) {
                if (in_array($tag, $contactTagsArray)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    public function filters(Request $request)
    {
        // Obtener datos y parámetros
        $allTransactions = Transaction::all();
        $sourcesTypes = $allTransactions->pluck('entity_source_type')->unique()->values()->toArray();
        
        // Agrupamos las fuentes por tipo para usarlas en el frontend
        $sourcesByType = [];
        foreach ($sourcesTypes as $type) {
            $sourcesByType[$type] = $allTransactions
                ->where('entity_source_type', $type)
                ->pluck('entity_resource_name')
                ->unique()
                ->values()
                ->toArray();
        }
        
        // Todas las fuentes para tener el listado completo
        $sources = $allTransactions->pluck('entity_resource_name')->unique()->values()->toArray();
        
        // Procesar parámetros del request
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            $now = Carbon::now();
            $startDate = $now->startOfWeek()->format('Y-m-d');
            $endDate = $now->endOfWeek()->format('Y-m-d');
        }

        $selectedSources = $request->input('sources') ? array_map('urldecode', $request->input('sources', [])) : array('Únete a Créetelo Mensual');
        $selectedSourceTypes = $request->input('source_types') ? array_map('urldecode', $request->input('source_types', [])) : $sourcesTypes;
        $selectedTags = $request->input('tags') ? array_map('urldecode', $request->input('tags', [])) : $this->availableTags;
        
        // Aplicar filtros combinados en una sola pasada
        $filteredTransactions = $allTransactions->filter(function($transaction) use ($selectedSources, $selectedSourceTypes, $startDate, $endDate) {
            // Filtro por fuentes seleccionadas
            if (!empty($selectedSources)) {
                if (!in_array($transaction->entity_resource_name, $selectedSources)) {
                    return false;
                }
            }
            
            // Filtro por tipos de fuentes
            if (!empty($selectedSourceTypes)) {
                if (!in_array($transaction->entity_source_type, $selectedSourceTypes)) {
                    return false;
                }
            }
            
            // Filtro por rango de fechas
            if ($startDate && $endDate) {
                $transactionDate = Carbon::parse($transaction->create_time);
                $start = Carbon::parse($startDate)->startOfDay();
                $end = Carbon::parse($endDate)->endOfDay();
                
                if (!$transactionDate->between($start, $end)) {
                    return false;
                }
            }
            
            return true;
        });

        // Apply tags filter to transactions if they have associated contacts
        if (!empty($selectedTags)) {
            $filteredTransactions = $filteredTransactions->filter(function($transaction) use ($selectedTags) {
                // Skip tag filtering if no contact relation exists
                if (!isset($transaction->contact) || !$transaction->contact) {
                    return false;
                }
                
                return $this->filterByTags($transaction, $selectedTags);
            });
        }
        
        // Procesamiento de resultados
        $resume = $filteredTransactions->groupBy(function($transaction) {
            return Carbon::parse($transaction->create_time)->format('Y-m-d');
        })->map(function($dailyTransactions, $date) use ($selectedSources) {
            // Initialize status counters
            $succeededCount = 0;
            $failedCount = 0;
            $refundedCount = 0;
            
            // Initialize source-specific counters
            $sourceData = [];
            
            // Count transactions by status and source
            foreach ($dailyTransactions as $transaction) {
                // Initialize source data if not exists
                $sourceName = $transaction->entity_resource_name ?? 'unknown';
                if (!isset($sourceData[$sourceName])) {
                    $sourceData[$sourceName] = [
                        'count' => 0,
                        'amount' => 0,
                        'succeeded' => 0,
                        'failed' => 0,
                        'refunded' => 0
                    ];
                }
                
                // Increment source-specific counters
                $sourceData[$sourceName]['count']++;
                $sourceData[$sourceName]['amount'] += $transaction->amount ?? 0;
                
                // Count by status
                if (isset($transaction->status)) {
                    if ($transaction->status === 'succeeded') {
                        $succeededCount++;
                        $sourceData[$sourceName]['succeeded']++;
                    } elseif ($transaction->status === 'failed') {
                        $failedCount++;
                        $sourceData[$sourceName]['failed']++;
                    } elseif ($transaction->status === 'refunded') {
                        $refundedCount++;
                        $sourceData[$sourceName]['refunded']++;
                    }
                }
            }
            
            return [
                'createdAt' => $date,
                'count' => $dailyTransactions->count(),
                'amount' => $dailyTransactions->sum('amount') ?? 0,
                'succeeded' => $succeededCount,
                'failed' => $failedCount,
                'refunded' => $refundedCount,
                'sources' => $sourceData
            ];
        })->values()->sortBy('createdAt')->toArray();
    
        $resume = is_array($resume) ? $resume : [];
    
        return view('admin.filters.transactions', [
            'transactions' => $resume,
            'sources' => $sources,
            'sourcesTypes' => $sourcesTypes,
            'sourcesByType' => $sourcesByType,
            'selectedSources' => $selectedSources,
            'selectedSourceTypes' => $selectedSourceTypes,
            'availableTags' => $this->availableTags,
            'selectedTags' => $selectedTags
        ]);
    }

    public function comparationForDay()
    {
        $transactions = config('app.transactions.data');
        $startDate = request()->get('start_date');
        $endDate = request()->get('end_date');

        if ($startDate && $endDate) {
            $transactions = array_filter($transactions->toArray(), function($transaction) use ($startDate, $endDate) {
                $date = Carbon::parse($transaction->createdAt)->format('Y-m-d');
                return $date >= $startDate && $date <= $endDate;
            });
        }

        $selectedTags = request()->input('tags') ? array_map('urldecode', request()->input('tags', [])) : $this->availableTags;

        // Group transactions by date and calculate totals
        $dailyTotals = [];
        foreach ($transactions as $transaction) {
            $date = Carbon::parse($transaction->createdAt)->format('Y-m-d');
            
            if (!isset($dailyTotals[$date])) {
                $dailyTotals[$date] = [
                    'createdAt' => $date,
                    'count' => 0,
                    'amount' => 0,
                    'succeeded' => 0,
                    'failed' => 0,
                    'refunded' => 0,
                    'sources' => []
                ];
            }
            
            $dailyTotals[$date]['count']++;
            $dailyTotals[$date]['amount'] += $transaction->amount ?? 0;
            
            // Count by status
            if (isset($transaction->status)) {
                if ($transaction->status === 'succeeded') {
                    $dailyTotals[$date]['succeeded']++;
                } elseif ($transaction->status === 'failed') {
                    $dailyTotals[$date]['failed']++;
                } elseif ($transaction->status === 'refunded') {
                    $dailyTotals[$date]['refunded']++;
                }
            }
            
            // Track source-specific data
            $sourceName = $transaction->entity_resource_name ?? 'unknown';
            if (!isset($dailyTotals[$date]['sources'][$sourceName])) {
                $dailyTotals[$date]['sources'][$sourceName] = [
                    'count' => 0,
                    'amount' => 0,
                    'succeeded' => 0,
                    'failed' => 0,
                    'refunded' => 0
                ];
            }
            
            $dailyTotals[$date]['sources'][$sourceName]['count']++;
            $dailyTotals[$date]['sources'][$sourceName]['amount'] += $transaction->amount ?? 0;
            
            if (isset($transaction->status)) {
                if ($transaction->status === 'succeeded') {
                    $dailyTotals[$date]['sources'][$sourceName]['succeeded']++;
                } elseif ($transaction->status === 'failed') {
                    $dailyTotals[$date]['sources'][$sourceName]['failed']++;
                } elseif ($transaction->status === 'refunded') {
                    $dailyTotals[$date]['sources'][$sourceName]['refunded']++;
                }
            }
        }

        // Convert to array and sort by date
        $dailyTotals = array_values($dailyTotals);
        usort($dailyTotals, function($a, $b) {
            return strcmp($a['createdAt'], $b['createdAt']);
        });

        return view('admin.filters.transactionsForDay', [
            'transactions' => $dailyTotals,
            'availableTags' => $this->availableTags,
            'selectedTags' => $selectedTags
        ]);
    }

    public function comparationForMonth()
    {
        $subscriptions = config('app.subscriptions.data');
        $startDate = request()->get('start_date');
        $endDate = request()->get('end_date');

        if ($startDate && $endDate) {
            $subscriptions = array_filter($subscriptions->toArray(), function($subscription) use ($startDate, $endDate) {
                $date = Carbon::parse($subscription->createdAt)->format('Y-m');
                return $date >= substr($startDate, 0, 7) && $date <= substr($endDate, 0, 7);
            });
        }

        $selectedTags = request()->input('tags') ? array_map('urldecode', request()->input('tags', [])) : $this->availableTags;

        // Group subscriptions by month and calculate totals
        $monthlyTotals = [];
        foreach ($subscriptions as $subscription) {
            $month = Carbon::parse($subscription->createdAt)->format('Y-m');
            
            if (!isset($monthlyTotals[$month])) {
                $monthlyTotals[$month] = [
                    'createdAt' => $month,
                    'count' => 0,
                    'amount' => 0
                ];
            }
            
            $monthlyTotals[$month]['count']++;
            $monthlyTotals[$month]['amount'] += $subscription->amount ?? 0;
        }

        // Convert to array and sort by month
        $monthlyTotals = array_values($monthlyTotals);
        usort($monthlyTotals, function($a, $b) {
            return strcmp($a['createdAt'], $b['createdAt']);
        });

        return view('admin.filters.transactionsForMonth', [
            'subscriptions' => $monthlyTotals,
            'availableTags' => $this->availableTags,
            'selectedTags' => $selectedTags
        ]);
    }

    public function projection(Request $request)
    {           
        // Start with a base query for subscriptions, joining with memberships
        $query = Subscription::with('contact');
        
        // Always filter for active subscriptions only
        $query = $query->where('status', 'active');
        
        // Get the subscriptions
        $subscriptions = $query->get();
        
        // For demo purposes, let's simulate last payment dates for some subscriptions
        // Remove this code in production and use actual payment data
        foreach ($subscriptions as $key => $subscription) {
            // Randomly assign last payment dates to some subscriptions
            if (rand(0, 1) == 1) {
                $today = Carbon::now();
                $daysAgo = rand(1, 28); // Random day in current month
                $subscription->last_payment_date = $today->copy()->subDays($daysAgo)->format('Y-m-d');
            }
            
            // Randomly mark some subscriptions as canceled (only a small percentage)
            if (rand(1, 10) == 1) {
                $subscription->status = 'canceled';
            }
        }
        
        $sources = $subscriptions->pluck('entity_resource_name')->unique()->values()->toArray();
        $typeSources = $subscriptions->pluck('source_type')->unique()->values()->toArray();
        
        $selectedSources = $request->input('sources') ? array_map('urldecode', $request->input('sources', [])) : $sources;
        $selectedSourceTypes = $request->input('source_types') ? array_map('urldecode', $request->input('source_types', [])) : $typeSources;
        $selectedTags = $request->input('tags') ? array_map('urldecode', $request->input('tags', [])) : $this->availableTags;
        
        // Get the month period from request (default: 1 - current month)
        $monthPeriod = (int) $request->input('month_period', 1);
        
        // Should we use simulated future data? Default to true for projection periods > 1
        $useSimulatedData = $request->input('use_simulated', $monthPeriod > 1);
        
        // Check if we should show only current month charges - default to true if not explicitly set
        $currentMonthCharges = $request->has('current_month_charges') 
            ? (bool)$request->input('current_month_charges') 
            : true;
        
        // Filter subscriptions by source, type and tags
        $filteredSubscriptions = $subscriptions->filter(function($subscription) use ($selectedSources, $selectedSourceTypes, $selectedTags) {
            $sourceMatch = in_array($subscription->entity_resource_name, $selectedSources);
            $typeMatch = in_array($subscription->source_type, $selectedSourceTypes);
            $tagMatch = $this->filterByTags($subscription, $selectedTags);
            
            return $sourceMatch && $typeMatch && $tagMatch;
        });
        
        // Get actual subscriptions or add simulated ones if requested
        $upcomingSubscriptions = collect([]);
        
        if ($useSimulatedData) {
            // Get simulated future subscriptions based on past data
            $upcomingSubscriptions = $this->getSimulatedFutureSubscriptions($filteredSubscriptions, $monthPeriod);
        } else {
            // Filter actual subscriptions by end date (expiring in the selected period)
            $now = Carbon::now();
            $upcomingSubscriptions = $filteredSubscriptions->filter(function($subscription) use ($now, $monthPeriod, $currentMonthCharges) {
                if (empty($subscription->end_date)) {
                    return false;
                }
                
                $endDate = Carbon::parse($subscription->end_date);
                
                // If current month charges filter is active, only show subscriptions expiring this month
                if ($currentMonthCharges) {
                    return $endDate->year === $now->year && $endDate->month === $now->month;
                }
                
                // Otherwise, check if subscription expires within the selected period
                $endOfPeriod = $now->copy()->addMonths($monthPeriod)->endOfMonth();
                return $endDate->between($now, $endOfPeriod);
            });
        }
        
        // Include all canceled subscriptions from the current month
        $now = Carbon::now();
        $canceledThisMonth = $filteredSubscriptions->filter(function($subscription) use ($now) {
            return $subscription->status === 'canceled' && 
                   (!empty($subscription->end_date) && 
                    Carbon::parse($subscription->end_date)->month === $now->month &&
                    Carbon::parse($subscription->end_date)->year === $now->year);
        });
        
        // Merge canceled subscriptions with upcoming ones
        $upcomingSubscriptions = $upcomingSubscriptions->merge($canceledThisMonth);
        
        // Group subscriptions by month of expiration
        $groupedByMonth = $upcomingSubscriptions->groupBy(function($subscription) {
            return Carbon::parse($subscription->end_date)->format('Y-m');
        });
        
        // Format data for view
        $expiringData = [];
        foreach ($groupedByMonth as $month => $subscriptions) {
            $expiringData[$month] = [
                'month' => $month,
                'month_name' => Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y'),
                'count' => $subscriptions->count(),
                'total_amount' => $subscriptions->sum('amount'),
                'subscriptions' => $subscriptions
            ];
        }
        
        // Get source types for filtering
        $allSourceTypes = $subscriptions->pluck('source_type')->unique()->values()->toArray();
        
        // Get sources by type for dynamic filtering
        $sourcesByType = [];
        foreach ($allSourceTypes as $type) {
            $sourcesByType[$type] = $subscriptions
                ->where('source_type', $type)
                ->pluck('entity_resource_name')
                ->unique()
                ->values()
                ->toArray();
        }

        return view('admin.filters.projection', [
            'expiringData' => collect($expiringData)->sortBy('month')->values()->toArray(),
            'monthPeriod' => $monthPeriod,
            'selectedSources' => $selectedSources,
            'selectedSourceTypes' => $selectedSourceTypes,
            'sources' => $sources,
            'sourceTypes' => $allSourceTypes,
            'sourcesByType' => $sourcesByType,
            'useSimulatedData' => $useSimulatedData,
            'currentMonthCharges' => $currentMonthCharges,
            'activeOnly' => true,
            'availableTags' => $this->availableTags,
            'selectedTags' => $selectedTags
        ]);
    }

    public function subscriptions(Request $request)
    {
        $subscriptionsAll = Subscription::with('contact')->get();
        $sources = $subscriptionsAll->pluck('entity_resource_name')->unique()->values()->toArray();
        $typeSources = $subscriptionsAll->pluck('source_type')->unique()->values()->toArray();
        $selectedSources = $request->input('sources') ? array_map('urldecode', $request->input('sources', [])) : array('M. Créetelo Mensual Activas');
        $selectedSourceTypes = $request->input('source_types') ? array_map('urldecode', $request->input('source_types', [])) : $typeSources;
        $selectedTags = $request->input('tags') ? array_map('urldecode', $request->input('tags', [])) : $this->availableTags;
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $subscriptions = $subscriptionsAll;

        if ($startDate && $endDate) {
            $subscriptions = $subscriptions->filter(function($subscription) use ($startDate, $endDate) {
            $subDate = Carbon::parse($subscription->start_date)->format('Y-m-d');
            return $subDate >= $startDate && $subDate <= $endDate;
            });
        }

        if (!empty($selectedSources)) {
            $subscriptions = $subscriptions->filter(function($subscription) use ($selectedSources) {
                return in_array($subscription->entity_resource_name, $selectedSources);
            });
        }

        if (!empty($selectedSourceTypes)) {
            $subscriptions = $subscriptions->filter(function($subscription) use ($selectedSourceTypes) {
                return in_array($subscription->source_type, $selectedSourceTypes);
            });
        }

        // Apply tag filtering
        if (!empty($selectedTags)) {
            $subscriptions = $subscriptions->filter(function($subscription) use ($selectedTags) {
                return $this->filterByTags($subscription, $selectedTags);
            });
        }

        // Eliminar el statusMap y modificar directamente el map de subscriptions
        $subscriptions = $subscriptions->map(function($subscription) {
            $startDate = Carbon::parse($subscription->start_date);
            
            if (!empty($subscription->end_date)) {
                $endDate = Carbon::parse($subscription->end_date);
                $subscription->duration = $startDate->diffInDays($endDate);
            } else {
                $subscription->duration = $startDate->diffInDays(now());
            }
            
            // El status ya viene como string, no necesitamos mapearlo
            $subscription->status = in_array($subscription->status, ['active', 'incomplete_expired', 'canceled', 'past_due']) 
                ? $subscription->status 
                : 'unknown';
            
            return $subscription;
        });

        $grouped = $subscriptions->groupBy('entity_resource_name')->map(function($group) {
            // Filter memberships only for summary statistics
            $membershipsOnly = $group->filter(function($sub) {
                return $sub->source_type === 'membership';
            });
            
            $byStatus = $group->groupBy('status');
            $membershipsByStatus = $membershipsOnly->groupBy('status');
            
            $summary = [
                'active' => [
                    'subscriptions' => $byStatus->get('active', collect())->values(),
                    'count' => $membershipsByStatus->get('active', collect())->count(),
                    'total_amount' => $byStatus->get('active', collect())->sum('amount'), // Use all source types for amount
                    'avg_duration' => $membershipsByStatus->get('active', collect())->avg('duration')
                ],
                'incomplete_expired' => [
                    'subscriptions' => $byStatus->get('incomplete_expired', collect())->values(),
                    'count' => $membershipsByStatus->get('incomplete_expired', collect())->count(),
                    'total_amount' => $byStatus->get('incomplete_expired', collect())->sum('amount'), // Use all source types for amount
                    'avg_duration' => $membershipsByStatus->get('incomplete_expired', collect())->avg('duration')
                ],
                'canceled' => [
                    'subscriptions' => $byStatus->get('canceled', collect())->values(),
                    'count' => $membershipsByStatus->get('canceled', collect())->count(),
                    'total_amount' => $byStatus->get('canceled', collect())->sum('amount'), // Use all source types for amount
                    'avg_duration' => $membershipsByStatus->get('canceled', collect())->avg('duration')
                ],
                'past_due' => [
                    'subscriptions' => $byStatus->get('past_due', collect())->values(),
                    'count' => $membershipsByStatus->get('past_due', collect())->count(),
                    'total_amount' => $byStatus->get('past_due', collect())->sum('amount'), // Use all source types for amount
                    'avg_duration' => $membershipsByStatus->get('past_due', collect())->avg('duration')
                ],
                'summary' => [
                    'total_count' => $membershipsOnly->count(),
                    'total_amount' => $group->where('status', 'active')->sum('amount'), // Use all source types for total amount
                    'avg_duration' => $membershipsOnly->avg('duration')
                ]
            ];

            return $summary;
        });

        // Calcular totales globales - counts solo para memberships, pero amounts para todos
        $memberships = $subscriptions->filter(function($sub) {
            return $sub->source_type === 'membership';
        });
        
        $totalStats = [
            'active_count' => $memberships->where('status', 'active')->count(),
            'incomplete_expired_count' => $memberships->where('status', 'incomplete_expired')->count(),
            'canceled_count' => $memberships->where('status', 'canceled')->count(),
            'past_due_count' => $memberships->where('status', 'past_due')->count(),
            'total_amount' => $subscriptions->where('status', 'active')->sum('amount'), // Use all subscriptions for total amount
            'churn_rate' => $memberships->count() > 0 
                ? ($memberships->where('status', 'canceled')->count() / $memberships->count()) * 100
                : 0
        ];

        return view('admin.filters.subscriptions', [
            'grouped' => $grouped,
            'selectedSources' => $selectedSources,
            'selectedSourceTypes' => $selectedSourceTypes,
            'allSources' => $subscriptionsAll->pluck('entity_resource_name')->unique()->values()->toArray(),
            'typeSources' => $typeSources,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalStats' => $totalStats,
            'availableTags' => $this->availableTags,
            'selectedTags' => $selectedTags
        ]);
    }

    /**
     * Get available tags for filtering
     */
    public function getAvailableTags()
    {
        return response()->json(['tags' => $this->availableTags]);
    }

    private function updateContacts()
    {
        try {
            $filePath = storage_path('app/contacts.json');
            $contactsService = new Contacts();
            $response = $contactsService->get(0);
            $responseData = json_decode(json_encode(response()->json(['data' => $response])->getData()), true);
            $totalCount = $responseData['data']['total'];
            $numberPage = (int)ceil($totalCount / 100);
            $countriesData = Country::all();

            if (!file_exists(storage_path('app'))) {
                mkdir(storage_path('app'), 0755, true);
            }

            $handle = fopen($filePath . '.temp', 'w');
            fwrite($handle, "[");

            $firstItem = true;
            for ($i = 0; $i < $numberPage; $i++) {
                $response = $contactsService->get($i);
                $pageData = json_decode(json_encode(response()->json(['data' => $response])->getData()), true);

                if (isset($pageData['data']['contacts']) && !empty($pageData['data']['contacts'])) {
                    $contactsCollect = collect($pageData['data']['contacts']);
                    $batch = $contactsCollect->map(function($contact) use ($countriesData) {
                        $country = collect($countriesData)->firstWhere('iso2', $contact['country']);
                        $contact['countryName'] = isset($country['name']) ? $country['name'] : $contact['country'];
                        return $contact;
                    })->toArray();

                    foreach ($batch as $contact) {
                        if (!$firstItem) {
                            fwrite($handle, ",\n");
                        } else {
                            $firstItem = false;
                        }
                        fwrite($handle, json_encode($contact, JSON_PRETTY_PRINT));
                    }

                    fflush($handle);
                    unset($batch);
                }
            }

            fwrite($handle, "\n]");
            fclose($handle);

            // Atomic rename of the temp file to the final file
            rename($filePath . '.temp', $filePath);

            // Al finalizar, eliminar la marca de cache
            cache()->forget('generating_contacts_json');
        } catch (\Exception $e) {
            // En caso de error, también eliminar la marca
            cache()->forget('generating_contacts_json');
            throw $e;
        }
    }

    private function updateSubscriptions()
    {
        try {
            $filePath = storage_path('app/subscriptions.json');
            $subscriptions = new Subscriptions();
            
            $response = $subscriptions->get(0);
            $responseData = json_decode(json_encode(response()->json(['data' => $response])->getData()), true);
            $totalCount = $responseData['data']['original']['totalCount'];
            $numberPage = (int)ceil($totalCount / 100);
    
            if (!file_exists(storage_path('app'))) {
                mkdir(storage_path('app'), 0755, true);
            }
    
            $handle = fopen($filePath . '.temp', 'w');
            fwrite($handle, "[");
    
            $firstItem = true;
            for ($i = 0; $i < $numberPage; $i++) {
                $response = $subscriptions->get($i);
                $pageData = json_decode(json_encode(response()->json(['data' => $response])->getData()), true);
    
                if (isset($pageData['data']['original']['data']) && !empty($pageData['data']['original']['data'])) {
                    $batch = collect($pageData['data']['original']['data'])->toArray();
    
                    foreach ($batch as $transaction) {
                        if (!$firstItem) {
                            fwrite($handle, ",\n");
                        } else {
                            $firstItem = false;
                        }
                        fwrite($handle, json_encode($transaction, JSON_PRETTY_PRINT));
                    }
    
                    fflush($handle);
                    unset($batch);
                }
            }
    
            fwrite($handle, "\n]");
            fclose($handle);
    
            // Atomic rename of the temp file to the final file
            rename($filePath . '.temp', $filePath);
    
            // Remove cache flag when finished
            cache()->forget('generating_subscriptions_json');
        } catch (\Exception $e) {
            // Remove cache flag in case of error
            cache()->forget('generating_subscriptions_json');
            throw $e;
        }
    }

    private function updateTransactions()
    {
        try {
            $filePath = storage_path('app/transactions.json');
            $transactions = new Transactions();
            
            $response = $transactions->get(0);
            $responseData = json_decode(json_encode(response()->json(['data' => $response])->getData()), true);
            $totalCount = $responseData['data']['original']['totalCount'];
            $numberPage = (int)ceil($totalCount / 100);

            if (!file_exists(storage_path('app'))) {
                mkdir(storage_path('app'), 0755, true);
            }

            $handle = fopen($filePath . '.temp', 'w');
            fwrite($handle, "[");

            $firstItem = true;
            for ($i = 0; $i < $numberPage; $i++) {
                $response = $transactions->get($i);
                $pageData = json_decode(json_encode(response()->json(['data' => $response])->getData()), true);

                if (isset($pageData['data']['original']['data']) && !empty($pageData['data']['original']['data'])) {
                    $batch = collect($pageData['data']['original']['data'])->toArray();

                    foreach ($batch as $transaction) {
                        if (!$firstItem) {
                            fwrite($handle, ",\n");
                        } else {
                            $firstItem = false;
                        }
                        fwrite($handle, json_encode($transaction, JSON_PRETTY_PRINT));
                    }

                    fflush($handle);
                    unset($batch);
                }
            }

            fwrite($handle, "\n]");
            fclose($handle);

            // Atomic rename of the temp file to the final file
            rename($filePath . '.temp', $filePath);

            // Remove cache flag when finished
            cache()->forget('generating_transactions_json');
        } catch (\Exception $e) {
            // Remove cache flag in case of error
            cache()->forget('generating_transactions_json');
            throw $e;
        }
    }

    /**
     * Obtener fuentes filtradas por tipo de fuente
     */
    public function getSourcesByType(Request $request)
    {
        $sourceTypes = $request->input('source_types', []);
        $allSubscriptions = Subscription::all();
        
        if (!empty($sourceTypes)) {
            // If source types are provided as array, filter by those types
            if (is_array($sourceTypes)) {
                // Decode URL encoded values if needed
                $decodedTypes = array_map('urldecode', $sourceTypes);
                
                $sources = $allSubscriptions
                    ->whereIn('source_type', $decodedTypes)
                    ->pluck('entity_resource_name')
                    ->unique()
                    ->values()
                    ->toArray();
            } else {
                // If it's a single value, convert to array
                $decodedType = urldecode($sourceTypes);
                
                $sources = $allSubscriptions
                    ->where('source_type', $decodedType)
                    ->pluck('entity_resource_name')
                    ->unique()
                    ->values()
                    ->toArray();
            }
        } else {
            // If no source type specified, return all sources
            $sources = $allSubscriptions
                ->pluck('entity_resource_name')
                ->unique()
                ->values()
                ->toArray();
        }
        
        return response()->json(['sources' => $sources]);
    }
}
