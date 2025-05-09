<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\Contacts;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Subscription;
use App\Models\Contact;

class AdminController extends Controller
{
    public function index(Request $request)
    {   
        try {
            // Get filter dates from request with validation
            $startDate = $request->has('monthYearStart') 
                ? Carbon::createFromFormat('Y-m', $request->monthYearStart)->startOfMonth() 
                : Carbon::now()->startOfMonth();
                
            $endDate = $request->has('monthYearEnd') 
                ? Carbon::createFromFormat('Y-m', $request->monthYearEnd)->endOfMonth() 
                : Carbon::now()->endOfMonth();
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['date' => 'Invalid date format']);
        }
        
        $contacts = Contact::paginate(30);
        $transactions = Transaction::whereBetween('create_time', [$startDate, $endDate])
            ->where('livemode', '1')
            ->orderBy('create_time', 'desc')
            ->get();

        // Get transactions filtered by date range and success status
        $transactionsSucceded = collect($transactions)->filter(function($item) {
            return $item->status === 'succeeded';
        })->values()->all();

        $totalAmount = collect($transactionsSucceded)->sum('amount');

        // Optimize transaction filtering with a single pass
        $filteredTransactions = collect($transactionsSucceded)->filter(function ($item) use ($startDate, $endDate) {
            $itemDate = Carbon::parse($item->create_time);
            return $itemDate->between($startDate, $endDate);
        });
            
        $totalCurrentPeriod = $filteredTransactions->sum('amount');

        $subscriptions = Subscription::whereBetween('start_date', [$startDate, $endDate])->get();
        
        $canceledSubscriptions = collect($subscriptions->where('status', 'canceled'));
        $activeSubscriptions = collect($subscriptions->where('status', 'active'));
        $trialingSubscriptions = collect($subscriptions->where('status', 'trialing'));
        $pausedSubscriptions = collect($subscriptions->where('status', 'paused'));
        $pastDueSubscriptions = collect($subscriptions->where('status', 'past_due'));
        $incompleteExpiredSubscriptions = collect($subscriptions->where('status', 'incomplete_expired'));
        $totalSubscriptions = $subscriptions->count();
        $stripeCount = $subscriptions->where('provider_type', 'stripe')->count();
        $paypalCount = $subscriptions->where('provider_type', 'paypal')->count();
        $stripeTotal = $filteredTransactions->where('payment_provider', 'stripe')->sum('amount');
        $paypalTotal = $filteredTransactions->where('payment_provider', 'paypal')->sum('amount');

        $currentContacts = Contact::whereBetween('date_added', [$startDate, $endDate])->count();
            
        $currentUsers = User::count();

        // Mejoras para garantizar que se muestre correctamente el año en el mejor mes
        $bestMonth = collect($transactionsSucceded)
            ->filter(function ($item) use ($startDate, $endDate) {
                $itemDate = Carbon::parse($item->start_date);
                return $itemDate->between($startDate, $endDate);
            })
            ->groupBy(function ($item) {
                $date = Carbon::parse($item->start_date);
                return $date->format('Y-m');
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
                return Carbon::parse($item->start_date)->year === Carbon::now()->year &&
                    Carbon::parse($item->start_date)->month === Carbon::now()->month;
            })
            ->sum('amount');
        
        // Get payment methods from filtered transactions
        $paymentProviderStats = $filteredTransactions->groupBy('payment_provider')
            ->map(function ($items) {
                return [
                    'count' => $items->count(),
                    'total' => $items->sum('amount')
                ];
            });

        $stripeStats = $paymentProviderStats['stripe'] ?? ['count' => 0, 'total' => 0];
        $paypalStats = $paymentProviderStats['paypal'] ?? ['count' => 0, 'total' => 0];
        
        // Continue with existing chart data
        $transactions = Transaction::where('livemode', '1')->orderBy('create_time', 'desc')->get();

        // Original year calculations
        $totalCurrentYear = collect($transactions)
            ->filter(function ($item) {
                return Carbon::parse($item->start_date)->year === Carbon::now()->year;
            })
            ->sum('amount');
            
        $totalLastYear = collect($transactions)
            ->filter(function ($item) {
                return Carbon::parse($item->create_time)->year === Carbon::now()->subYear(1)->year;
            })
            ->sum('amount');

        $monthlyAmounts = collect($transactions)
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
            
        $lastYearMonthlyAmounts = collect($transactions)
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
                return Carbon::parse($item->start_date)->month === Carbon::now()->month
                    && Carbon::parse($item->start_date)->year === Carbon::now()->year;
            })
            ->groupBy(function ($item) {
                return Carbon::parse($item->start_date)->day;
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
                return Carbon::parse($item->start_date)->year === Carbon::now()->year;
            })
            ->groupBy(function ($item) {
                return Carbon::parse($item->start_date)->week;
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
                return Carbon::parse($item->start_date)->week === Carbon::now()->week
                    && Carbon::parse($item->start_date)->year === Carbon::now()->year;
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

        // Calculate total transactions (for cancellation rate)
        $totalTransactions = $totalSubscriptions > 0 ? $totalSubscriptions : 1;

        // Calculate cancellation rate
        $cancellationRate = $totalSubscriptions > 0 ? ($canceledSubscriptions->count() / $totalSubscriptions) * 100 : 0;
                          
        // Check if activeMemberships is being used in the view
        $activeMemberships = $activeSubscriptions; // Assuming this is what was intended
        
        // Nueva lógica para agrupar membresías por status y source_type
        $membershipsBySourceAndStatus = DB::table('contacts')
            ->join('subscriptions', 'contacts.id', '=', 'subscriptions.contact_id')
            ->whereBetween('contacts.date_added', [$startDate, $endDate])
            ->select('subscriptions.status', 'subscriptions.source_type', DB::raw('count(*) as total'))
            ->groupBy('subscriptions.status', 'subscriptions.source_type')
            ->get();

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
        $averageTicketByMonth = $filteredTransactions
            ->groupBy(function ($item) {
                return Carbon::parse($item->create_time)->format('Y-m');
            })
            ->map(function ($items) {
                $totalAmount = $items->sum('amount');
                $count = $items->count();
                $date = Carbon::parse($items->first()->create_time);
                return [
                    'month' => $this->monthSpanish($date->month) . ' ' . $date->year,
                    'average' => $count > 0 ? $totalAmount / $count : 0,
                    'count' => $count
                ];
            })
            ->values()
            ->toArray();

        // Ticket promedio global en el rango seleccionado
        $totalAmountInRange = $filteredTransactions->sum('amount');
        $totalTransactionsInRange = $filteredTransactions->count();
        $averageTicket = $totalTransactionsInRange > 0 ? $totalAmountInRange / $totalTransactionsInRange : 0;

        return view('admin.index', compact(
            'stripeStats', 'paypalStats', 'stripeCount', 'paypalCount', 'stripeTotal','paypalTotal',
            'currentContacts', 'currentUsers', 'userDifference', 'latestUsers',
            'totalCurrentYear', 'bestMonth', 'totalLastYear', 'totalCurrentMonth',
            'monthlyAmounts', 'lastYearMonthlyAmounts',
            'weeklyAmounts', 'currentWeekAmount', 'dailyAmounts', 'filteredPeriod',
            'totalCurrentPeriod', 'cancellationRate', 'totalTransactions',
            'activeSubscriptions', 'canceledSubscriptions', 'trialingSubscriptions',
            'pausedSubscriptions', 'pastDueSubscriptions', 'incompleteExpiredSubscriptions',
            'dailyStripeAmounts', 'dailyPaypalAmounts', 'activeMemberships',
            'membershipBarSeries', 'statuses', 'statusesTranslated', 'totalAmount',
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
