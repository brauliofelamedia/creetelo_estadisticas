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
        
        // Aplicar filtros combinados en una sola pasada
        $filteredTransactions = $allTransactions->filter(function($transaction) use ($selectedSources, $startDate, $endDate) {
            // Filtro por fuentes seleccionadas
            if (!empty($selectedSources)) {
                if (!in_array($transaction->entity_resource_name, $selectedSources)) {
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
            'selectedSources' => $selectedSources
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
        $transactions = Transaction::all();
        $sources = $transactions->pluck('entity_resource_name')->unique()->values()->toArray();
        $selectedSources = $request->input('sources') ? array_map('urldecode', $request->input('sources', [])) : $sources;

        //$subscriptions = config('app.subscriptions.data');
        $projectionPeriod = $this->projectionPeriod;

        $this->projectionPeriod = $request->period ?? 3;
        $this->calculateTransactions();

        return view('admin.filters.projection', [
            'historicalData' => $this->transactionData,
            'projectedData' => $this->projectedData,
            'projectionPeriod' => $this->projectionPeriod,
            'selectedSources' => $selectedSources,
            'sources' => $transactions->pluck('entity_resource_name')->unique()->values()->toArray()
        ]);

        //return view('admin.filters.projection',compact('transactions','projectionPeriod','sources','selectedSources'));
    }

    public function calculateProjection(Request $request)
    {
        $allTransactions = collect(config('app.transactions.data'));
        $selectedSources = array_map('urldecode', $request->input('sources', []));

        $this->projectionPeriod = $request->period ?? 3;
        $this->calculateTransactions();
        
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
        $selectedSources = $request->input('sources') ? array_map('urldecode', $request->input('sources', [])) : array('M. Créetelo Mensual Activas');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $subscriptions = $subscriptionsAll;

        if ($startDate && $endDate) {
            $subscriptions = $subscriptions->filter(function($subscription) use ($startDate, $endDate) {
                $subStart = Carbon::parse($subscription->start_date)->format('Y-m-d');
                $subEnd = !empty($subscription->start_date) 
                    ? Carbon::parse($subscription->end_date)->format('Y-m-d')
                    : null;

                return ($subStart >= $startDate && $subStart <= $endDate) ||
                       ($subEnd && $subEnd >= $startDate && $subEnd <= $endDate) ||
                       ($subStart <= $startDate && (!$subEnd || $subEnd >= $endDate));
            });
        }

        if (!empty($selectedSources)) {
            $subscriptions = $subscriptions->filter(function($subscription) use ($selectedSources) {
                return in_array($subscription->entity_resource_name, $selectedSources);
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
            'allSources' => $subscriptionsAll->pluck('entity_resource_name')->unique()->values()->toArray(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalStats' => $totalStats
        ]);
    }

    private function calculateProjections($historicalData)
    {
        if (empty($historicalData)) return [];

        // Obtener los últimos 6 meses para calcular la tendencia
        $recentData = array_slice($historicalData, -6);
        
        // Calcular el promedio de crecimiento mensual
        $growthRates = [];
        $totals = array_column($recentData, 'total');
        
        for ($i = 1; $i < count($totals); $i++) {
            if ($totals[$i-1] > 0) {
                $growthRates[] = ($totals[$i] - $totals[$i-1]) / $totals[$i-1];
            }
        }
        
        // Si no hay datos suficientes, usar un crecimiento base del 5%
        $avgGrowthRate = !empty($growthRates) 
            ? max(array_sum($growthRates) / count($growthRates), 0.05)
            : 0.05;

        $monthsToProject = match((int)$this->projectionPeriod) {
            3 => 3,
            6 => 6,
            12 => 12,
            24 => 24,
            default => 3,
        };

        $projections = [];
        $lastData = end($historicalData);
        $lastMonth = Carbon::parse($lastData['month']);
        
        $previousData = $lastData;
        
        // Valores base para proyección
        $lastTotal = $lastData['total'];
        $lastSucceeded = $lastData['succeeded'];
        $lastFailed = $lastData['failed'];
        $lastRefunded = $lastData['refunded'];
        $lastAmount = $lastData['total_amount'];

        for ($i = 1; $i <= $monthsToProject; $i++) {
            $growthFactor = pow(1 + $avgGrowthRate, $i);
            
            $currentProjection = [
                'month' => $lastMonth->copy()->addMonth($i)->format('Y-m'),
                'total' => round($lastTotal * $growthFactor),
                'succeeded' => round($lastSucceeded * $growthFactor),
                'failed' => round($lastFailed * $growthFactor),
                'refunded' => round($lastRefunded * $growthFactor),
                'total_amount' => round($lastAmount * $growthFactor, 2),
                'is_projection' => true,
                'succeeded_growth' => $this->calculateGrowthPercentage(
                    round($lastSucceeded * $growthFactor),
                    $previousData['succeeded']
                ),
                'failed_growth' => $this->calculateGrowthPercentage(
                    round($lastFailed * $growthFactor),
                    $previousData['failed']
                ),
                'refunded_growth' => $this->calculateGrowthPercentage(
                    round($lastRefunded * $growthFactor),
                    $previousData['refunded']
                )
            ];
            
            $projections[] = $currentProjection;
            $previousData = $currentProjection;
        }

        return $projections;
    }

    private function calculateGrowthPercentage($current, $previous)
    {
        if ($previous == 0) return 0;
        return (($current - $previous) / $previous) * 100;
    }

    private function calculateTransactions()
    {
        $rawDataBySource = [];
        $transactions = Transaction::all();
        $selectedSources = request()->input('sources', []);

        foreach ($transactions as $transaction) {
            $month = date('Y-m', strtotime($transaction->create_time));
            $sourceName = $transaction->entity_resource_name ?? 'unknown';
            
            if (!empty($selectedSources) && !in_array(urlencode($sourceName), $selectedSources)) {
                continue;
            }

            if (!isset($rawDataBySource[$sourceName])) {
                $rawDataBySource[$sourceName] = [];
            }
            
            if (!isset($rawDataBySource[$sourceName][$month])) {
                $rawDataBySource[$sourceName][$month] = [
                    'month' => $month,
                    'total' => 0,
                    'succeeded' => 0,
                    'failed' => 0,
                    'refunded' => 0,
                    'total_amount' => 0,
                    'source' => $sourceName
                ];
            }
            
            $rawDataBySource[$sourceName][$month]['total']++;
            
            switch ($transaction->status) {
                case 'succeeded':
                    $rawDataBySource[$sourceName][$month]['succeeded']++;
                    $rawDataBySource[$sourceName][$month]['total_amount'] += $transaction->amount;
                    break;
                case 'failed':
                    $rawDataBySource[$sourceName][$month]['failed']++;
                    break;
                case 'refunded':
                    $rawDataBySource[$sourceName][$month]['refunded']++;
                    break;
            }
        }

        $this->transactionData = [];
        $this->projectedData = [];

        foreach ($rawDataBySource as $source => $rawData) {
            $sourceData = array_values($rawData);
            usort($sourceData, fn($a, $b) => strcmp($a['month'], $b['month']));

            // Calcular porcentajes de crecimiento
            $processedData = array_map(function($data, $index) use ($sourceData) {
                $previousMonth = $index > 0 ? $sourceData[$index - 1] : null;
                
                return array_merge($data, [
                    'succeeded_growth' => $previousMonth ? $this->calculateGrowthPercentage($data['succeeded'], $previousMonth['succeeded']) : 0,
                    'failed_growth' => $previousMonth ? $this->calculateGrowthPercentage($data['failed'], $previousMonth['failed']) : 0,
                    'refunded_growth' => $previousMonth ? $this->calculateGrowthPercentage($data['refunded'], $previousMonth['refunded']) : 0,
                ]);
            }, $sourceData, array_keys($sourceData));

            $this->transactionData[$source] = $processedData;
            
            if (!empty($processedData)) {
                $this->projectedData[$source] = $this->calculateProjections($processedData);
            }
        }
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
}
