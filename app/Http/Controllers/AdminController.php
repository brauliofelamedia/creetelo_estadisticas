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
        
        // Filter contacts by date range if filter is applied
        $currentContacts = $request->has('monthYearStart') || $request->has('monthYearEnd')
            ? Contact::whereBetween('date_added', [$startDate, $endDate])->count()
            : Contact::count();
            
        $currentUsers = User::count();

        // Filter transactions by date range for current period
        $filteredTransactions = collect($transactionsSucceded)
            ->filter(function ($item) use ($startDate, $endDate) {
                $itemDate = Carbon::parse($item->create_time);
                return $itemDate->between($startDate, $endDate);
            });
            
        $totalCurrentPeriod = $filteredTransactions->sum('amount');

        // Calculate cancellation rate
        $allFilteredTransactions = collect($transactions)
            ->filter(function ($item) use ($startDate, $endDate) {
                $itemDate = Carbon::parse($item->create_time);
                return $itemDate->between($startDate, $endDate) && $item->livemode === '1';
            });
        
        $cancelledTransactions = $allFilteredTransactions->filter(function($item) {
            return $item->status === 'cancelled' || $item->status === 'failed';
        });
        
        $totalTransactions = $allFilteredTransactions->count();
        $cancelledCount = $cancelledTransactions->count();
        $cancellationRate = $totalTransactions > 0 ? ($cancelledCount / $totalTransactions) * 100 : 0;

        // Get best month within filter period
        $bestMonth = collect($transactionsSucceded)
            ->filter(function ($item) use ($startDate, $endDate) {
                $itemDate = Carbon::parse($item->create_time);
                return $itemDate->between($startDate, $endDate);
            })
            ->groupBy(function ($item) {
                return Carbon::parse($item->create_time)->month;
            })
            ->map(function ($items, $month) {
                return [
                    'month' => $this->monthSpanish($month),
                    'amount' => $items->sum('amount')
                ];
            })
            ->sortBy('amount')
            ->last() ?: ['month' => '', 'amount' => 0];

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
            
        // Create variables for filtered data display
        $filteredPeriod = $this->monthSpanish($startDate->month) . ' ' . $startDate->year . ' - ' . 
                 $this->monthSpanish($endDate->month) . ' ' . $endDate->year;

        return view('admin.index', compact(
            'stripeCount', 'paypalCount', 'paypalTotal', 'stripeTotal',
            'currentContacts', 'currentUsers', 'userDifference', 'latestUsers',
            'totalCurrentYear', 'bestMonth', 'totalLastYear', 'totalCurrentMonth',
            'monthlyAmounts', 'lastYearMonthlyAmounts', 'paypal', 'stripe',
            'weeklyAmounts', 'currentWeekAmount', 'dailyAmounts', 'filteredPeriod',
            'totalCurrentPeriod', 'cancellationRate', 'cancelledCount', 'totalTransactions'
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
