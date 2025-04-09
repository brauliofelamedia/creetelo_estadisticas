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
            'selectedSourceTypes' => $selectedSourceTypes
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

        return view('admin.filters.transactionsForDay', ['transactions' => $dailyTotals]);
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

        return view('admin.filters.transactionsForMonth', ['subscriptions' => $monthlyTotals]);
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
        
        // Get the month period from request (default: 1 - current month)
        $monthPeriod = (int) $request->input('month_period', 1);
        
        // Should we use simulated future data? Default to true for projection periods > 1
        $useSimulatedData = $request->input('use_simulated', $monthPeriod > 1);
        
        // Check if we should show only current month charges - default to true if not explicitly set
        $currentMonthCharges = $request->has('current_month_charges') 
            ? (bool)$request->input('current_month_charges') 
            : true;
        
        // Filter subscriptions by source and type
        $filteredSubscriptions = $subscriptions->filter(function($subscription) use ($selectedSources, $selectedSourceTypes) {
            $sourceMatch = in_array($subscription->entity_resource_name, $selectedSources);
            $typeMatch = in_array($subscription->source_type, $selectedSourceTypes);
            return $sourceMatch && $typeMatch;
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
            'activeOnly' => true // Always true since we only include active subscriptions
        ]);
    }

    /**
     * Generate simulated future subscriptions based on past data patterns
     * 
     * @param \Illuminate\Support\Collection $subscriptions
     * @param int $monthPeriod
     * @return \Illuminate\Support\Collection
     */
    private function getSimulatedFutureSubscriptions($subscriptions, $monthPeriod)
    {
        $now = Carbon::now();
        $endOfPeriod = $now->copy()->addMonths($monthPeriod)->endOfMonth();
        $simulatedSubscriptions = collect([]);
        
        // Get real subscriptions ending in the current period
        $realSubscriptions = $subscriptions->filter(function($subscription) use ($now, $endOfPeriod) {
            if (empty($subscription->end_date)) {
                return false;
            }
            
            $endDate = Carbon::parse($subscription->end_date);
            return $endDate->between($now, $endOfPeriod);
        });
        
        // Mark these as real, not simulated
        foreach ($realSubscriptions as $subscription) {
            $subscription->is_simulated = false;
            $simulatedSubscriptions->push($subscription);
        }
        
        // Group subscriptions by source for better projection
        $subscriptionsBySource = $subscriptions->groupBy('entity_resource_name');
        
        foreach ($subscriptionsBySource as $sourceName => $sourceSubscriptions) {
            // Get count of real subscriptions ending this month for this source
            $currentMonthKey = $now->format('Y-m');
            $realCountThisMonth = $realSubscriptions
                ->where('entity_resource_name', $sourceName)
                ->filter(function($sub) use ($currentMonthKey) {
                    return Carbon::parse($sub->end_date)->format('Y-m') === $currentMonthKey;
                })
                ->count();
            
            // Only generate future simulations for month 2 onwards
            for ($i = 1; $i < $monthPeriod; $i++) {
                $targetMonth = $now->copy()->addMonths($i);
                $monthKey = $targetMonth->format('Y-m');
                
                // How many active subscriptions to simulate this month
                // Base this on the current active count for this source
                $currentActiveCount = $sourceSubscriptions->count();
                
                // Apply a slight random growth/decline factor
                $growthFactor = 1 + (rand(-5, 10) / 100); // -5% to +10% monthly change
                $simulateCount = max(1, ceil($currentActiveCount * $growthFactor));
                
                // Generate simulated subscriptions
                for ($j = 0; $j < $simulateCount; $j++) {
                    // Use an existing subscription as template
                    $template = $sourceSubscriptions->random();
                    
                    // Clone the subscription with new dates
                    $simulatedSub = clone $template;
                    $simulatedSub->id = 'sim_' . $sourceName . '_' . $i . '_' . $j;
                    $simulatedSub->status = 'active'; // Ensure the status is always active
                    
                    // Set end date to somewhere in the target month
                    $day = rand(1, $targetMonth->daysInMonth);
                    $simulatedSub->end_date = $targetMonth->copy()->setDay($day)->format('Y-m-d');
                    
                    // Set start date based on typical subscription length
                    $typicalLength = 30; // Default to monthly
                    
                    if (strpos(strtolower($sourceName), 'mensual') !== false) {
                        $typicalLength = 30;
                    } elseif (strpos(strtolower($sourceName), 'anual') !== false) {
                        $typicalLength = 365;
                    } elseif (strpos(strtolower($sourceName), 'trimestral') !== false) {
                        $typicalLength = 90;
                    } elseif (strpos(strtolower($sourceName), 'semestral') !== false) {
                        $typicalLength = 180;
                    }
                    
                    $simulatedSub->start_date = Carbon::parse($simulatedSub->end_date)->subDays($typicalLength)->format('Y-m-d');
                    $simulatedSub->is_simulated = true; // Mark as simulated
                    
                    // Add to collection
                    $simulatedSubscriptions->push($simulatedSub);
                }
            }
        }
        
        return $simulatedSubscriptions;
    }
    
    /**
     * Calculate monthly average number of subscriptions
     * 
     * @param \Illuminate\Support\Collection $subscriptions
     * @return array
     */
    private function calculateMonthlyAverages($subscriptions)
    {
        $monthlySubscriptions = $subscriptions->groupBy(function($subscription) {
            return Carbon::parse($subscription->end_date)->format('Y-m');
        });
        
        $averages = [];
        foreach ($monthlySubscriptions as $month => $subs) {
            $averages[$month] = $subs->count();
        }
        
        // If we have multiple months, calculate moving average
        if (count($averages) > 1) {
            $total = array_sum($averages);
            $count = count($averages);
            $monthlyAverage = ceil($total / $count);
            
            // Use this average for projection with a slight random variance
            $now = Carbon::now();
            for ($i = 0; $i < 12; $i++) {
                $futureMonth = $now->copy()->addMonths($i)->format('Y-m');
                // Add variance of ±20%
                $variance = rand(-20, 20) / 100;
                $averages[$futureMonth] = max(1, ceil($monthlyAverage * (1 + $variance)));
            }
        } else {
            // If only one month, use that count for future months
            $count = reset($averages) ?: 1;
            $now = Carbon::now();
            for ($i = 0; $i < 12; $i++) {
                $futureMonth = $now->copy()->addMonths($i)->format('Y-m');
                // Add variance of ±20%
                $variance = rand(-20, 20) / 100;
                $averages[$futureMonth] = max(1, ceil($count * (1 + $variance)));
            }
        }
        
        return $averages;
    }

    public function calculateProjection(Request $request)
    {
        $allTransactions = collect(config('app.transactions.data'));
        $selectedSources = array_map('urldecode', $request->input('sources', []));

        $this->projectionPeriod = $request->period ?? 3;
        //$this->calculateTransactions();
        
        return view('admin.filters.projection', [
            'historicalData' => $this->transactionData,
            'projectedData' => $this->projectedData,
            'projectionPeriod' => $this->projectionPeriod,
            'selectedSources' => $selectedSources,
            'sources' => $allTransactions->pluck('entity_resource_name')->unique()->values()->toArray()
        ]);
    }

    public function subscriptions(Request $request)
    {
        $subscriptionsAll = Subscription::with('contact')->get();
        $sources = $subscriptionsAll->pluck('entity_resource_name')->unique()->values()->toArray();
        $typeSources = $subscriptionsAll->pluck('source_type')->unique()->values()->toArray();
        $selectedSources = $request->input('sources') ? array_map('urldecode', $request->input('sources', [])) : array('M. Créetelo Mensual Activas');
        $selectedSourceTypes = $request->input('source_types') ? array_map('urldecode', $request->input('source_types', [])) : $typeSources;
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
            $byStatus = $group->groupBy('status');
            
            $summary = [
                'active' => [
                    'subscriptions' => $byStatus->get('active', collect())->values(),
                    'count' => $byStatus->get('active', collect())->count(),
                    'total_amount' => $byStatus->get('active', collect())->sum('amount'),
                    'avg_duration' => $byStatus->get('active', collect())->avg('duration')
                ],
                'incomplete_expired' => [
                    'subscriptions' => $byStatus->get('incomplete_expired', collect())->values(),
                    'count' => $byStatus->get('incomplete_expired', collect())->count(),
                    'total_amount' => $byStatus->get('incomplete_expired', collect())->sum('amount'),
                    'avg_duration' => $byStatus->get('incomplete_expired', collect())->avg('duration')
                ],
                'canceled' => [
                    'subscriptions' => $byStatus->get('canceled', collect())->values(),
                    'count' => $byStatus->get('canceled', collect())->count(),
                    'total_amount' => $byStatus->get('canceled', collect())->sum('amount'),
                    'avg_duration' => $byStatus->get('canceled', collect())->avg('duration')
                ],
                'past_due' => [
                    'subscriptions' => $byStatus->get('past_due', collect())->values(),
                    'count' => $byStatus->get('past_due', collect())->count(),
                    'total_amount' => $byStatus->get('past_due', collect())->sum('amount'),
                    'avg_duration' => $byStatus->get('past_due', collect())->avg('duration')
                ],
                'summary' => [
                    'total_count' => $group->count(),
                    'total_amount' => $group->where('status', 'active')->sum('amount'),
                    'avg_duration' => $group->avg('duration')
                ]
            ];

            return $summary;
        });

        // Calcular totales globales
        $totalStats = [
            'active_count' => $subscriptions->where('status', 'active')->count(),
            'incomplete_expired_count' => $subscriptions->where('status', 'incomplete_expired')->count(),
            'canceled_count' => $subscriptions->where('status', 'canceled')->count(),
            'past_due_count' => $subscriptions->where('status', 'past_due')->count(),
            'total_amount' => $subscriptions->where('status', 'active')->sum('amount'),
            'churn_rate' => $subscriptions->count() > 0 
                ? ($subscriptions->where('status', 'canceled')->count() / $subscriptions->count()) * 100
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
            'totalStats' => $totalStats
        ]);
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
