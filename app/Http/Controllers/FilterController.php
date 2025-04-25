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
    protected $availablePaymentProviders = ['stripe', 'paypal'];

    public function __construct()
    {
        // Load available tags
        $this->loadAvailableTags();
    }

    protected function loadAvailableTags()
    {
        // Define initial tags
        $initialTags = [
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
        
        // Filter out tags that start with "M. 💎" but keep those that start with "M."
        $this->availableTags = array_filter($initialTags, function($tag) {
            return strpos($tag, 'M. 💎') !== 0;
        });
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

    protected function hasCreeteloMensualTag($subscription)
    {
        if (!isset($subscription->contact) || !$subscription->contact) {
            return false;
        }
        
        $contactTags = $subscription->contact->tags;
        
        // Check if tags is a JSON string
        if (is_string($contactTags) && is_array(json_decode($contactTags, true))) {
            $contactTagsArray = json_decode($contactTags, true);
            return in_array('creetelo_mensual', $contactTagsArray);
        } 
        // Check if tags is already an array
        else if (is_array($contactTags)) {
            return in_array('creetelo_mensual', $contactTags);
        }
        // Check if tags is a comma-separated string
        else if (is_string($contactTags)) {
            $contactTagsArray = explode(',', $contactTags);
            return in_array('creetelo_mensual', $contactTagsArray);
        }
        
        return false;
    }

    protected function hasCreeteloTagCancelado($subscription)
    {
        if (!isset($subscription->contact) || !$subscription->contact) {
            return false;
        }
        
        $contactTags = $subscription->contact->tags;
        
        // Check if tags is a JSON string
        if (is_string($contactTags) && is_array(json_decode($contactTags, true))) {
            $contactTagsArray = json_decode($contactTags, true);
            return in_array('creetelo_cancelado', $contactTagsArray);
        } 
        // Check if tags is already an array
        else if (is_array($contactTags)) {
            return in_array('creetelo_cancelado', $contactTags);
        }
        // Check if tags is a comma-separated string
        else if (is_string($contactTags)) {
            $contactTagsArray = explode(',', $contactTags);
            return in_array('creetelo_cancelado', $contactTagsArray);
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
        
        // Filtrar las fuentes que comienzan con "M."
        $mSources = array_filter($sources, function($source) {
            return strpos($source, 'M.') === 0;
        });
        
        // Get unique payment providers from transactions
        $paymentProviders = $allTransactions->pluck('payment_provider')->unique()->filter()->values()->toArray();
        if (empty($paymentProviders)) {
            $paymentProviders = $this->availablePaymentProviders;
        }
        
        // Procesar parámetros del request
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            $now = Carbon::now();
            $startDate = $now->startOfWeek()->format('Y-m-d');
            $endDate = $now->endOfWeek()->format('Y-m-d');
        }

        $selectedSources = $request->input('sources') ? array_map('urldecode', $request->input('sources', [])) : (!empty($mSources) ? $mSources : array('Únete a Créetelo Mensual','Únete a Créetelo Anual'));
        $selectedSourceTypes = $request->input('source_types') ? array_map('urldecode', $request->input('source_types', [])) : $sourcesTypes;
        $selectedTags = $request->input('tags') ? array_map('urldecode', $request->input('tags', [])) : [];
        $selectedPaymentProviders = $request->input('payment_providers') ? array_map('urldecode', $request->input('payment_providers', [])) : $paymentProviders;
        
        // Aplicar filtros combinados en una sola pasada
        $filteredTransactions = $allTransactions->filter(function($transaction) use ($selectedSources, $selectedSourceTypes, $selectedPaymentProviders, $startDate, $endDate) {
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
            
            // Filtro por proveedor de pago
            if (!empty($selectedPaymentProviders)) {
                $provider = $transaction->payment_provider ?? ''; 
                if (!in_array($provider, $selectedPaymentProviders)) {
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
            'selectedTags' => $selectedTags,
            'paymentProviders' => $paymentProviders,
            'selectedPaymentProviders' => $selectedPaymentProviders
        ]);
    }

    public function actives(Request $request)
    {
        // Get date parameters or set defaults
        $startMonth1 = $request->get('start_month1', date('n'));
        $startYear1 = $request->get('start_year1', date('Y'));
        $endMonth1 = $request->get('end_month1', date('n'));
        $endYear1 = $request->get('end_year1', date('Y'));
        
        // Create date ranges for the period
        $startDate1 = Carbon::createFromDate($startYear1, $startMonth1, 1)->startOfMonth();
        $endDate1 = Carbon::createFromDate($endYear1, $endMonth1, 1)->endOfMonth();
        
        // Also get the month before the period for comparison
        $previousPeriod1Start = $startDate1->copy()->subMonth();
        $previousPeriod1End = $endDate1->copy()->subMonth();
        
        // Fetch all subscriptions
        $allSubscriptions = Subscription::with('contact')->get();
        
        // Process subscriptions for both current period and previous period
        $period1Data = $this->getDetailedSubscriptionData($allSubscriptions, $startDate1, $endDate1);
        $previousPeriod1Data = $this->getDetailedSubscriptionData($allSubscriptions, $previousPeriod1Start, $previousPeriod1End);
        
        // Calculate growth metrics
        $period1Growth = $this->calculateGrowthMetrics($period1Data, $previousPeriod1Data);
        
        return view('admin.filters.actives', [
            'period1Data' => $period1Data,
            'period1Growth' => $period1Growth,
            'startMonth1' => $startMonth1,
            'startYear1' => $startYear1,
            'endMonth1' => $endMonth1,
            'endYear1' => $endYear1,
            'startDate1' => $startDate1->format('Y-m-d'),
            'endDate1' => $endDate1->format('Y-m-d'),
            'previousPeriod1' => $previousPeriod1Start->format('M Y') . ' - ' . $previousPeriod1End->format('M Y'),
        ]);
    }

    private function getDetailedSubscriptionData($subscriptions, $startDate, $endDate)
    {
        $startDateObj = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $endDateObj = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
        
        // Initialize result structure
        $result = [
            'period' => $startDateObj->format('M Y') . ' - ' . $endDateObj->format('M Y'),
            'start_date' => $startDateObj->format('Y-m-d'),
            'end_date' => $endDateObj->format('Y-m-d'),
            'total' => [
                'total_count' => 0,
                'active_count' => 0,
                'to_be_charged' => 0,
                'canceled' => 0,
                'total_amount' => 0,
            ],
            'by_source' => [],
            'daily_data' => [],
        ];
        
        // Filter active subscriptions
        $activeSubscriptions = $subscriptions->filter(function($subscription) {
            return $subscription->status === 'active';
        });
        
        $result['total']['active_count'] = $activeSubscriptions->count();
        
        // Create a day-by-day analysis through the date range
        $currentDate = $startDateObj->copy();
        while ($currentDate->lte($endDateObj)) {
            $dayKey = $currentDate->format('Y-m-d');
            $dayData = [
                'date' => $dayKey,
                'formatted_date' => $currentDate->format('d M Y'),
                'to_be_charged' => 0,
                'to_be_charged_amount' => 0,
                'canceled' => 0,
                'canceled_amount' => 0,
                'sources' => []
            ];
            
            // Check each subscription
            foreach ($activeSubscriptions as $subscription) {
                // Determine if subscription is due for charge on this day
                if ($this->isSubscriptionDueOnDate($subscription, $currentDate)) {
                    $dayData['to_be_charged']++;
                    $dayData['to_be_charged_amount'] += $subscription->amount ?: 0;
                    $result['total']['to_be_charged']++;
                    $result['total']['total_amount'] += $subscription->amount ?: 0;
                    
                    // Track by source
                    $sourceName = $subscription->entity_resource_name ?: 'Unknown';
                    if (!isset($dayData['sources'][$sourceName])) {
                        $dayData['sources'][$sourceName] = [
                            'to_be_charged' => 0,
                            'to_be_charged_amount' => 0,
                            'canceled' => 0,
                            'canceled_amount' => 0,
                        ];
                    }
                    
                    $dayData['sources'][$sourceName]['to_be_charged']++;
                    $dayData['sources'][$sourceName]['to_be_charged_amount'] += $subscription->amount ?: 0;
                    
                    // Also update source totals
                    if (!isset($result['by_source'][$sourceName])) {
                        $result['by_source'][$sourceName] = [
                            'to_be_charged' => 0,
                            'to_be_charged_amount' => 0,
                            'canceled' => 0, 
                            'canceled_amount' => 0,
                        ];
                    }
                    $result['by_source'][$sourceName]['to_be_charged']++;
                    $result['by_source'][$sourceName]['to_be_charged_amount'] += $subscription->amount ?: 0;
                }
                
                // Check if subscription was canceled on this day
                if ($this->wasSubscriptionCanceledOnDate($subscription, $currentDate)) {
                    $dayData['canceled']++;
                    $dayData['canceled_amount'] += $subscription->amount ?: 0;
                    $result['total']['canceled']++;
                    
                    // Track by source
                    $sourceName = $subscription->entity_resource_name ?: 'Unknown';
                    if (!isset($dayData['sources'][$sourceName])) {
                        $dayData['sources'][$sourceName] = [
                            'to_be_charged' => 0,
                            'to_be_charged_amount' => 0,
                            'canceled' => 0,
                            'canceled_amount' => 0,
                        ];
                    }
                    
                    $dayData['sources'][$sourceName]['canceled']++;
                    $dayData['sources'][$sourceName]['canceled_amount'] += $subscription->amount ?: 0;
                    
                    // Also update source totals
                    if (!isset($result['by_source'][$sourceName])) {
                        $result['by_source'][$sourceName] = [
                            'to_be_charged' => 0,
                            'to_be_charged_amount' => 0,
                            'canceled' => 0,
                            'canceled_amount' => 0,
                        ];
                    }
                    $result['by_source'][$sourceName]['canceled']++;
                    $result['by_source'][$sourceName]['canceled_amount'] += $subscription->amount ?: 0;
                }
            }
            
            $result['daily_data'][$dayKey] = $dayData;
            $currentDate->addDay();
        }
        
        // Calculate total count (includes all subscriptions that had activity in the period)
        $result['total']['total_count'] = $result['total']['to_be_charged'] + $result['total']['canceled'];
        
        return $result;
    }

    private function calculateGrowthMetrics($currentPeriodData, $previousPeriodData)
    {
        $growth = [
            'total_count' => [
                'value' => $currentPeriodData['total']['total_count'] - $previousPeriodData['total']['total_count'],
                'percentage' => 0,
            ],
            'to_be_charged' => [
                'value' => $currentPeriodData['total']['to_be_charged'] - $previousPeriodData['total']['to_be_charged'],
                'percentage' => 0,
            ],
            'canceled' => [
                'value' => $currentPeriodData['total']['canceled'] - $previousPeriodData['total']['canceled'],
                'percentage' => 0,
            ],
            'total_amount' => [
                'value' => $currentPeriodData['total']['total_amount'] - $previousPeriodData['total']['total_amount'],
                'percentage' => 0,
            ],
            'by_source' => [],
        ];
        
        // Calculate percentages
        if ($previousPeriodData['total']['total_count'] > 0) {
            $growth['total_count']['percentage'] = ($growth['total_count']['value'] / $previousPeriodData['total']['total_count']) * 100;
        }
        
        if ($previousPeriodData['total']['to_be_charged'] > 0) {
            $growth['to_be_charged']['percentage'] = ($growth['to_be_charged']['value'] / $previousPeriodData['total']['to_be_charged']) * 100;
        }
        
        if ($previousPeriodData['total']['canceled'] > 0) {
            $growth['canceled']['percentage'] = ($growth['canceled']['value'] / $previousPeriodData['total']['canceled']) * 100;
        }
        
        if ($previousPeriodData['total']['total_amount'] > 0) {
            $growth['total_amount']['percentage'] = ($growth['total_amount']['value'] / $previousPeriodData['total']['total_amount']) * 100;
        }
        
        // Calculate growth by source
        $allSources = array_unique(array_merge(
            array_keys($currentPeriodData['by_source']),
            array_keys($previousPeriodData['by_source'])
        ));
        
        foreach ($allSources as $source) {
            $currentSourceData = $currentPeriodData['by_source'][$source] ?? [
                'to_be_charged' => 0, 
                'to_be_charged_amount' => 0,
                'canceled' => 0,
                'canceled_amount' => 0
            ];
            
            $previousSourceData = $previousPeriodData['by_source'][$source] ?? [
                'to_be_charged' => 0, 
                'to_be_charged_amount' => 0,
                'canceled' => 0,
                'canceled_amount' => 0
            ];
            
            $growth['by_source'][$source] = [
                'to_be_charged' => [
                    'value' => $currentSourceData['to_be_charged'] - $previousSourceData['to_be_charged'],
                    'percentage' => $previousSourceData['to_be_charged'] > 0 
                        ? (($currentSourceData['to_be_charged'] - $previousSourceData['to_be_charged']) / $previousSourceData['to_be_charged']) * 100
                        : 0,
                ],
                'to_be_charged_amount' => [
                    'value' => $currentSourceData['to_be_charged_amount'] - $previousSourceData['to_be_charged_amount'],
                    'percentage' => $previousSourceData['to_be_charged_amount'] > 0 
                        ? (($currentSourceData['to_be_charged_amount'] - $previousSourceData['to_be_charged_amount']) / $previousSourceData['to_be_charged_amount']) * 100
                        : 0,
                ],
                'canceled' => [
                    'value' => $currentSourceData['canceled'] - $previousSourceData['canceled'],
                    'percentage' => $previousSourceData['canceled'] > 0 
                        ? (($currentSourceData['canceled'] - $previousSourceData['canceled']) / $previousSourceData['canceled']) * 100
                        : 0,
                ],
            ];
        }
        
        return $growth;
    }

    private function isSubscriptionDueOnDate($subscription, $date)
    {
        // If subscription has no start date, we cannot calculate
        if (empty($subscription->start_date)) {
            return false;
        }
        
        // If subscription is not active, it's not due
        if ($subscription->status !== 'active') {
            return false;
        }
        
        $subscriptionStart = Carbon::parse($subscription->start_date);
        $checkDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        
        // Get the billing interval (monthly or yearly)
        $interval = $this->determineBillingInterval($subscription);
        
        // If subscription has an end date, check if it falls on our check date
        if (!empty($subscription->end_date)) {
            $endDate = Carbon::parse($subscription->end_date);
            if ($endDate->isSameDay($checkDate)) {
                return true;
            }
        }
        
        // Calculate next renewal date based on subscription start
        $nextRenewal = $subscriptionStart->copy();
        
        // Find the next renewal date that's on or after our subscription start
        while ($nextRenewal->lt($checkDate)) {
            if ($interval === 'month') {
                $nextRenewal->addMonth();
            } else {
                $nextRenewal->addYear();
            }
        }
        
        // If the calculated next renewal date matches our check date
        return $nextRenewal->isSameDay($checkDate);
    }

    /**
     * Determine if a subscription has monthly or yearly billing.
     *
     * @param object $subscription The subscription to analyze
     * @return string 'month' or 'year' depending on subscription type
     */
    private function determineBillingInterval($subscription)
    {
        // Check subscription name/title for indications of billing period
        $name = strtolower($subscription->entity_resource_name ?? '');
        $description = strtolower($subscription->description ?? '');
        
        // Check for specific terms indicating annual billing
        if (
            strpos($name, 'anual') !== false || 
            strpos($name, 'annual') !== false ||
            strpos($name, 'yearly') !== false ||
            strpos($description, 'anual') !== false ||
            strpos($description, 'annual') !== false ||
            strpos($description, 'yearly') !== false
        ) {
            return 'year';
        }
        
        // Check if associated contact has yearly tag
        if (isset($subscription->contact) && $subscription->contact) {
            $contactTags = $subscription->contact->tags;
            
            // Parse tags from different formats
            $tagArray = [];
            if (is_string($contactTags) && is_array(json_decode($contactTags, true))) {
                $tagArray = json_decode($contactTags, true);
            } elseif (is_array($contactTags)) {
                $tagArray = $contactTags;
            } elseif (is_string($contactTags)) {
                $tagArray = explode(',', $contactTags);
            }
            
            // Check for annual tags
            foreach ($tagArray as $tag) {
                if (
                    stripos($tag, 'anual') !== false || 
                    stripos($tag, 'annual') !== false ||
                    stripos($tag, 'yearly') !== false
                ) {
                    return 'year';
                }
            }
        }
        
        // Default to monthly billing if no yearly indicators found
        return 'month';
    }

    private function wasSubscriptionCanceledOnDate($subscription, $date)
    {
        // If subscription is not canceled or has no canceled_at date, return false
        if ($subscription->status !== 'canceled' || empty($subscription->canceled_at)) {
            return false;
        }
        
        $canceledDate = Carbon::parse($subscription->canceled_at);
        $checkDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        
        return $canceledDate->isSameDay($checkDate);
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

        $selectedTags = request()->input('tags') ? array_map('urldecode', request()->input('tags', [])) : [];

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

        $selectedTags = request()->input('tags') ? array_map('urldecode', request()->input('tags', [])) : [];

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
        $selectedTags = $request->input('tags') ? array_map('urldecode', $request->input('tags', [])) : [];
        
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
        $mSources = array_filter($sources, function($source) {
            return strpos($source, 'M.') === 0 && strpos($source, 'M. 💎') !== 0;
        });
        
        $defaultSources = array_merge($mSources, ['Únete a Créetelo Mensual', 'Únete a Créetelo Anual']);
        
        $selectedSources = $request->input('sources') ? array_map('urldecode', $request->input('sources', [])) : $defaultSources;
        $selectedSourceTypes = $request->input('source_types') ? array_map('urldecode', $request->input('source_types', [])) : $typeSources;
        $selectedTags = $request->input('tags') ? array_map('urldecode', $request->input('tags', [])) : [];
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
            // Filter memberships only for summary statistics (removed this filtering)
            // We'll use all subscriptions for the statistics instead
            
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

        // Calculate cancelations with creetelo_cancelado tag
        $canceledWithCreeteloTag = $subscriptions->filter(function($subscription) {
            return $subscription->status === 'canceled' && $this->hasCreeteloTagCancelado($subscription);
        });
        
        // Calculate global totals - use all subscriptions instead of just memberships
        $totalStats = [
            'active_count' => $subscriptions->where('status', 'active')->count(),
            'incomplete_expired_count' => $subscriptions->where('status', 'incomplete_expired')->count(),
            'canceled_count' => $subscriptions->where('status', 'canceled')->count(),
            'past_due_count' => $subscriptions->where('status', 'past_due')->count(),
            'total_amount' => $subscriptions->where('status', 'active')->sum('amount'),
            'churn_rate' => $subscriptions->count() > 0 
                ? ($subscriptions->where('status', 'canceled')->count() / $subscriptions->count()) * 100
                : 0,
            'canceled_creetelo_count' => $canceledWithCreeteloTag->count(),
            'canceled_creetelo_rate' => $canceledWithCreeteloTag->count() > 0 && $subscriptions->count() > 0
                ? ($canceledWithCreeteloTag->count() / $subscriptions->count()) * 100
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
            'selectedTags' => $selectedTags,
            'tags' => $this->availableTags  // Adding the tags here
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
