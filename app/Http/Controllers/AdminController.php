<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\Contacts;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\Subscriptions;
use App\Models\Contact;

class AdminController extends Controller
{

    public function index(Request $request)
    {
        // Get filter dates from request
        $startDate = $request->has('monthYearStart') 
            ? Carbon::createFromFormat('Y-m', $request->monthYearStart)->startOfMonth() 
            : Carbon::now()->startOfMonth();
            
        $endDate = $request->has('monthYearEnd') 
            ? Carbon::createFromFormat('Y-m', $request->monthYearEnd)->endOfMonth() 
            : Carbon::now()->endOfMonth();
        
        $contacts = Contact::paginate(30);
        $transactions = Transaction::all();
        
        // Get transactions filtered by date range and success status
        $transactionsSucceded = collect($transactions)->filter(function($item) {
            return $item->status === 'succeeded' && $item->livemode === '1';
        })->values()->all();

        // Filter transactions by date range for current period
        $filteredTransactions = collect($transactionsSucceded)
            ->filter(function ($item) use ($startDate, $endDate) {
                $itemDate = Carbon::parse($item->create_time);
                return $itemDate->between($startDate, $endDate);
            });
            
        $totalCurrentPeriod = $filteredTransactions->sum('amount');
        
        // Count contacts by subscription status - Refactor to fix filtering issues
        if ($request->has('monthYearStart') || $request->has('monthYearEnd')) {
            // Create a base query first
            $contactsBaseQuery = Contact::whereBetween('date_added', [$startDate, $endDate]);
            
            // Total contacts in the date range
            $currentContacts = (clone $contactsBaseQuery)->count();
            
            // Active subscriptions in the date range
            $activeSubscriptions = (clone $contactsBaseQuery)
                ->whereHas('subscription', function($query) {
                    $query->where('status', 'active');
                })->count();
            
            // Canceled subscriptions in the date range
            $canceledSubscriptions = (clone $contactsBaseQuery)
                ->whereHas('subscription', function($query) {
                    $query->where('status', 'canceled');
                })->count();
            
            // Trialing subscriptions in the date range
            $trialingSubscriptions = (clone $contactsBaseQuery)
                ->whereHas('subscription', function($query) {
                    $query->where('status', 'trialing');
                })->count();
            
            // Paused subscriptions in the date range
            $pausedSubscriptions = (clone $contactsBaseQuery)
                ->whereHas('subscription', function($query) {
                    $query->where('status', 'paused');
                })->count();
            
            // Past due subscriptions in the date range
            $pastDueSubscriptions = (clone $contactsBaseQuery)
                ->whereHas('subscription', function($query) {
                    $query->where('status', 'past_due');
                })->count();
            
            // Incomplete expired subscriptions in the date range
            $incompleteExpiredSubscriptions = (clone $contactsBaseQuery)
                ->whereHas('subscription', function($query) {
                    $query->where('status', 'incomplete_expired');
                })->count();
            
            // Total contacts with subscriptions in the date range
            $totalSubscriptions = (clone $contactsBaseQuery)
                ->whereHas('subscription')->count();
                
            // Contacts with canceled subscriptions in the date range
            $cancelledCount = (clone $contactsBaseQuery)
                ->whereHas('subscription', function($query) {
                    $query->where('status', 'canceled');
                })->count();
        } else {
            // Total contacts
            $currentContacts = Contact::count();
            
            // Active subscriptions
            $activeSubscriptions = Contact::whereHas('subscription', function($query) {
                $query->where('status', 'active');
            })->count();
            
            // Canceled subscriptions
            $canceledSubscriptions = Contact::whereHas('subscription', function($query) {
                $query->where('status', 'canceled');
            })->count();
            
            // Trialing subscriptions
            $trialingSubscriptions = Contact::whereHas('subscription', function($query) {
                $query->where('status', 'trialing');
            })->count();
            
            // Paused subscriptions
            $pausedSubscriptions = Contact::whereHas('subscription', function($query) {
                $query->where('status', 'paused');
            })->count();
            
            // Past due subscriptions
            $pastDueSubscriptions = Contact::whereHas('subscription', function($query) {
                $query->where('status', 'past_due');
            })->count();
            
            // Incomplete expired subscriptions
            $incompleteExpiredSubscriptions = Contact::whereHas('subscription', function($query) {
                $query->where('status', 'incomplete_expired');
            })->count();
            
            // Total contacts with subscriptions
            $totalSubscriptions = Contact::whereHas('subscription')->count();
                
            // Total contacts with canceled subscriptions
            $cancelledCount = Contact::whereHas('subscription', function($query) {
                $query->where('status', 'canceled');
            })->count();
        }   
            
        $currentUsers = User::count();

        // Mejoras para garantizar que se muestre correctamente el año en el mejor mes
        $bestMonth = collect($transactionsSucceded)
            ->filter(function ($item) use ($startDate, $endDate) {
                $itemDate = Carbon::parse($item->create_time);
                return $itemDate->between($startDate, $endDate);
            })
            ->groupBy(function ($item) {
                $date = Carbon::parse($item->create_time);
                return $date->format('Y-m'); // Format as YYYY-MM to include year
            })
            ->map(function ($items, $yearMonth) {
                $dateParts = explode('-', $yearMonth);
                $year = $dateParts[0];
                $month = (int)$dateParts[1];
                
                return [
                    'month' => $this->monthSpanish($month),
                    'year' => $year,
                    'amount' => $items->sum('amount')
                ];
            })
            ->sortBy('amount')
            ->last() ?: ['month' => '', 'year' => '', 'amount' => 0];
            
        // Keep original monthly calculations for comparison charts
        $totalCurrentMonth = collect($transactionsSucceded)
            ->filter(function ($item) {
                return Carbon::parse($item->create_time)->year === Carbon::now()->year &&
                    Carbon::parse($item->create_time)->month === Carbon::now()->month;
            })
            ->sum('amount');
            
        // Original year calculations
        $totalCurrentYear = collect($transactionsSucceded)
            ->filter(function ($item) {
                return Carbon::parse($item->create_time)->year === Carbon::now()->year;
            })
            ->sum('amount');
            
        $totalLastYear = collect($transactionsSucceded)
            ->filter(function ($item) {
                return Carbon::parse($item->create_time)->year === Carbon::now()->subYear(1)->year;
            })
            ->sum('amount');
        
        // Get payment methods from filtered transactions
        $stripe = $filteredTransactions->filter(function ($item) {
            return in_array($item->payment_provider, ['stripe']);
        });
        $stripeCount = $stripe->count();
        $stripeTotal = $stripe->sum('amount');
        
        $paypal = $filteredTransactions->filter(function ($item) {
            return in_array($item->payment_provider, ['paypal']);
        });
        $paypalCount = $paypal->count();
        $paypalTotal = $paypal->sum('amount');
        
        // Continue with existing chart data
        $monthlyAmounts = collect($transactionsSucceded)
            ->filter(function ($item) {
                return Carbon::parse($item->create_time)->year === Carbon::now()->year;
            })
            ->groupBy(function ($item) {
                return Carbon::parse($item->create_time)->month;
            })
            ->map(function ($items) {
                return $items->sum('amount');
            })
            ->pipe(function ($collection) {
                return collect(range(1, 12))->mapWithKeys(function ($month) use ($collection) {
                    return [$month => $collection->get($month, 0)];
                });
            })
            ->values();
            
        $lastYearMonthlyAmounts = collect($transactionsSucceded)
            ->filter(function ($item) {
                return Carbon::parse($item->create_time)->year === Carbon::now()->subYear(1)->year;
            })
            ->groupBy(function ($item) {
                return Carbon::parse($item->create_time)->month;
            })
            ->map(function ($items) {
                return $items->sum('amount');
            })
            ->pipe(function ($collection) {
                return collect(range(1, 12))->mapWithKeys(function ($month) use ($collection) {
                    return [$month => $collection->get($month, 0)];
                });
            })
            ->values();
            
        $now = Carbon::now();
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $usersThirtyDaysAgo = User::where('created_at', '<=', $thirtyDaysAgo)->count();
        $userDifference = $currentContacts - $usersThirtyDaysAgo;
        $latestUsers = User::orderBy('created_at', 'desc')->take(5)->get();

        // Añadir desglose por día con información del año y mes correctos
        $dailyAmounts = collect($transactionsSucceded)
            ->filter(function ($item) {
                return Carbon::parse($item->create_time)->month === Carbon::now()->month
                    && Carbon::parse($item->create_time)->year === Carbon::now()->year;
            })
            ->groupBy(function ($item) {
                return Carbon::parse($item->create_time)->day;
            })
            ->map(function ($items) {
                return $items->sum('amount');
            })
            ->pipe(function ($collection) {
                return collect(range(1, Carbon::now()->daysInMonth))->map(function ($day) use ($collection) {
                    return $collection->get($day, 0);
                });
            });
            
        // Calculate weekly amounts for the current year
        $weeklyAmounts = collect($transactionsSucceded)
            ->filter(function ($item) {
                return Carbon::parse($item->create_time)->year === Carbon::now()->year;
            })
            ->groupBy(function ($item) {
                return Carbon::parse($item->create_time)->week;
            })
            ->map(function ($items) {
                return $items->sum('amount');
            })
            ->pipe(function ($collection) {
                return collect(range(1, 52))->map(function ($week) use ($collection) {
                    return $collection->get($week, 0);
                });
            });
            
        // Calculate current week amount
        $currentWeekAmount = collect($transactionsSucceded)
            ->filter(function ($item) {
                return Carbon::parse($item->create_time)->week === Carbon::now()->week
                    && Carbon::parse($item->create_time)->year === Carbon::now()->year;
            })
            ->sum('amount');
            
        // Calculate daily amounts by payment provider
        $dailyStripeAmounts = collect($transactionsSucceded)
            ->filter(function ($item) {
                return Carbon::parse($item->create_time)->month === Carbon::now()->month 
                    && Carbon::parse($item->create_time)->year === Carbon::now()->year
                    && $item->payment_provider === 'stripe';
            })
            ->groupBy(function ($item) {
                return Carbon::parse($item->create_time)->day;
            })
            ->map(function ($items) {
                return $items->sum('amount');
            })
            ->pipe(function ($collection) {
                return collect(range(1, Carbon::now()->daysInMonth))->map(function ($day) use ($collection) {
                    return $collection->get($day, 0);
                });
            });
            
        $dailyPaypalAmounts = collect($transactionsSucceded)
            ->filter(function ($item) {
                return Carbon::parse($item->create_time)->month === Carbon::now()->month 
                    && Carbon::parse($item->create_time)->year === Carbon::now()->year
                    && $item->payment_provider === 'paypal';
            })
            ->groupBy(function ($item) {
                return Carbon::parse($item->create_time)->day;
            })
            ->map(function ($items) {
                return $items->sum('amount');
            })
            ->pipe(function ($collection) {
                return collect(range(1, Carbon::now()->daysInMonth))->map(function ($day) use ($collection) {
                    return $collection->get($day, 0);
                });
            });
            
        // Create variables for filtered data display
        $filteredPeriod = $this->monthSpanish($startDate->month) . ' ' . $startDate->year . ' - ' . 
                          $this->monthSpanish($endDate->month) . ' ' . $endDate->year;
        
        // Debug information to help troubleshoot
        $debugInfo = [
            'startDate' => $startDate->toDateTimeString(),
            'endDate' => $endDate->toDateTimeString(),
            'hasFilter' => $request->has('monthYearStart') || $request->has('monthYearEnd'),
            'totalFilteredTransactions' => $filteredTransactions->count()
        ];

        // Calculate total transactions (for cancellation rate)
        $totalTransactions = $totalSubscriptions > 0 ? $totalSubscriptions : 1;  // Prevent division by zero
        
        // Calculate cancellation rate
        $cancellationRate = $totalTransactions > 0 ? ($cancelledCount / $totalTransactions) * 100 : 0;
                          
        // Check if activeMemberships is being used in the view
        $activeMemberships = $activeSubscriptions; // Assuming this is what was intended
        
        // Nueva lógica para agrupar membresías por status y source_type
        if ($request->has('monthYearStart') || $request->has('monthYearEnd')) {
            // Membresías por tipo de fuente y estado dentro del rango de fechas
            $membershipsBySourceAndStatus = DB::table('contacts')
                ->join('subscriptions', 'contacts.id', '=', 'subscriptions.contact_id')
                ->whereBetween('contacts.date_added', [$startDate, $endDate])
                ->select('subscriptions.status', 'subscriptions.source_type', DB::raw('count(*) as total'))
                ->groupBy('subscriptions.status', 'subscriptions.source_type')
                ->get();
        } else {
            // Todas las membresías agrupadas por tipo de fuente y estado
            $membershipsBySourceAndStatus = DB::table('contacts')
                ->join('subscriptions', 'contacts.id', '=', 'subscriptions.contact_id')
                ->select('subscriptions.status', 'subscriptions.source_type', DB::raw('count(*) as total'))
                ->groupBy('subscriptions.status', 'subscriptions.source_type')
                ->get();
        }
        
        // Preparar datos para la gráfica
        $sourceTypes = ['payment_link', 'funnel', 'membership', 'other'];
        $statuses = ['active', 'canceled', 'trialing', 'paused', 'past_due', 'incomplete_expired'];
        
        // Inicializar array para datos de la gráfica
        $membershipChartData = [];
        foreach ($statuses as $status) {
            $membershipChartData[$status] = [];
            foreach ($sourceTypes as $sourceType) {
                $membershipChartData[$status][$sourceType] = 0;
            }
        }
        
        // Poblar datos de la gráfica
        foreach ($membershipsBySourceAndStatus as $item) {
            $status = $item->status;
            $sourceType = $item->source_type ?? 'other';
            
            // Si el source_type no está en nuestra lista, lo consideramos como "other"
            if (!in_array($sourceType, $sourceTypes)) {
                $sourceType = 'other';
            }
            
            if (isset($membershipChartData[$status][$sourceType])) {
                $membershipChartData[$status][$sourceType] = $item->total;
            }
        }
        
        // Preparar series para el gráfico de barras apiladas
        $membershipBarSeries = [];
        foreach ($sourceTypes as $sourceType) {
            $data = [];
            foreach ($statuses as $status) {
                $data[] = $membershipChartData[$status][$sourceType] ?? 0;
            }
            
            $membershipBarSeries[] = [
                'name' => ucfirst($sourceType),
                'data' => $data
            ];
        }

        // Crear traducción de estados para la gráfica
        $statusesTranslated = [
            'active' => 'Activo',
            'canceled' => 'Cancelado',
            'trialing' => 'En prueba',
            'paused' => 'Pausado',
            'past_due' => 'Vencido',
            'incomplete_expired' => 'Expirado'
        ];

        // Calcular el ticket promedio por mes dentro del rango de fecha seleccionado
        $averageTicketByMonth = collect($transactionsSucceded)
            ->filter(function ($item) use ($startDate, $endDate) {
                $itemDate = Carbon::parse($item->create_time);
                return $itemDate->between($startDate, $endDate);
            })
            ->groupBy(function ($item) {
                return Carbon::parse($item->create_time)->format('Y-m');
            })
            ->map(function ($items) {
                $totalAmount = $items->sum('amount');
                $totalTransactions = $items->count();
                $averageTicket = $totalTransactions > 0 ? $totalAmount / $totalTransactions : 0;
                
                // Correctly parse the datetime string
                $date = Carbon::parse($items->first()->create_time);
                $spanishMonth = $this->monthSpanish($date->month);
                
                return [
                    'month' => $spanishMonth . ' ' . $date->year,
                    'average' => $averageTicket,
                    'count' => $totalTransactions
                ];
            })
            ->values()
            ->toArray();
            
        // Ticket promedio global en el rango seleccionado
        $totalAmountInRange = $filteredTransactions->sum('amount');
        $totalTransactionsInRange = $filteredTransactions->count();
        $averageTicket = $totalTransactionsInRange > 0 ? $totalAmountInRange / $totalTransactionsInRange : 0;

        return view('admin.index', compact(
            'stripeCount', 'paypalCount', 'paypalTotal', 'stripeTotal',
            'currentContacts', 'currentUsers', 'userDifference', 'latestUsers',
            'totalCurrentYear', 'bestMonth', 'totalLastYear', 'totalCurrentMonth',
            'monthlyAmounts', 'lastYearMonthlyAmounts', 'paypal', 'stripe',
            'weeklyAmounts', 'currentWeekAmount', 'dailyAmounts', 'filteredPeriod',
            'totalCurrentPeriod', 'cancellationRate', 'cancelledCount', 'totalTransactions',
            'activeSubscriptions', 'canceledSubscriptions', 'trialingSubscriptions',
            'pausedSubscriptions', 'pastDueSubscriptions', 'incompleteExpiredSubscriptions',
            'dailyStripeAmounts', 'dailyPaypalAmounts', 'activeMemberships', 'debugInfo',
            'membershipBarSeries', 'statuses', 'statusesTranslated',
            'averageTicketByMonth', 'averageTicket', 'totalTransactionsInRange'
        ));
    }
    
    public function config()
    {
        return view('admin.config');
    }

    private function monthSpanish($month)
    {
        $spanishMonths = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];

        return $spanishMonths[$month];
    }
}
