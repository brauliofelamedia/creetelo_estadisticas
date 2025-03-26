<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\Subscriptions;

class AdminController extends Controller
{

    public function index()
    {
        $contacts = config('app.contacts.data');
        $transactions = config('app.transactions.data');
        
        $transactionsSucceded = collect($transactions)->filter(function($item) {
            return $item->status === 'succeeded';
        })->values()->all();

        $bestMonth = collect($transactionsSucceded)
            ->filter(function ($item) {
                return Carbon::parse($item->createdAt)->year === Carbon::now()->year;
            })
            ->groupBy(function ($item) {
            return Carbon::parse($item->createdAt)->month;
            })
            ->map(function ($items, $month) {
                return [
                    'month' => $this->monthSpanish($month),
                    'amount' => $items->sum('amount')
                ];
            })
            ->sortBy('amount')
            ->last();

        $weeklyAmounts = collect($transactionsSucceded)
            ->filter(function ($item) {
            $date = Carbon::parse($item->createdAt);
                return $date->isCurrentWeek();
            })
            ->groupBy(function ($item) {
                return Carbon::parse($item->createdAt)->dayOfWeek;
            })
            ->map(function ($items) {
                return $items->sum('amount');
            })
            ->pipe(function ($collection) {
                return collect(range(0, 6))->map(function ($day) use ($collection) {
                    return $collection->get($day, 0);
                });
            });

        $currentWeekAmount = $weeklyAmounts->sum();
        $stripe = collect($transactionsSucceded)->filter(function ($item) {
            return in_array($item->paymentProviderType, ['stripe']);
        })->count();
        $paypal = collect($transactionsSucceded)->filter(function ($item) {
            return in_array($item->paymentProviderType, ['paypal']);
        })->count();
        $totalCurrentYear = collect($transactionsSucceded)
            ->filter(function ($item) {
                return Carbon::parse($item->createdAt)->year === Carbon::now()->year;
            })
            ->sum('amount');

        $monthlyAmounts = collect($transactionsSucceded)
            ->filter(function ($item) {
                return Carbon::parse($item->createdAt)->year === Carbon::now()->year;
            })
            ->groupBy(function ($item) {
                return Carbon::parse($item->createdAt)->month;
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

        $totalLastYear = collect($transactionsSucceded)
            ->filter(function ($item) {
                $itemDate = Carbon::parse($item->createdAt);
                $lastYear = Carbon::now()->subYear()->year;
                return $itemDate->year === $lastYear;
            })
            ->sum('amount');

        $totalCurrentMonth = collect($transactionsSucceded)
            ->filter(function ($item) {
            return Carbon::parse($item->createdAt)->year === Carbon::now()->year &&
                   Carbon::parse($item->createdAt)->month === Carbon::now()->month;
            })
            ->sum('amount');

        $currentUsers = $contacts->count();
        $now = Carbon::now();
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $usersThirtyDaysAgo = User::where('created_at', '<=', $thirtyDaysAgo)->count();
        $userDifference = $currentUsers - $usersThirtyDaysAgo;
        $latestUsers = User::orderBy('created_at', 'desc')->take(5)->get();

        return view('admin.index', compact('currentUsers', 'userDifference', 'latestUsers','totalCurrentYear','bestMonth','totalLastYear','totalCurrentMonth','monthlyAmounts','paypal','stripe','weeklyAmounts','currentWeekAmount'));
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
