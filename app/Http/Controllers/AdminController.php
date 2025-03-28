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

    public function index()
    {
        $contacts = Contact::paginate(30);
        $transactions = Transaction::all();
        
        //Contactos activos
        $currentContacts = Contact::count();
        $currentUsers = User::count();

        //Transacciones
        $transactionsSucceded = collect($transactions)->filter(function($item) {
            return $item->status === 'succeeded' && $item->livemode === '1';
        })->values()->all();

        $totalCurrentMonth = collect($transactionsSucceded)
            ->filter(function ($item) {
            return Carbon::parse($item->create_time)->year === Carbon::now()->year &&
                   Carbon::parse($item->create_time)->month === Carbon::now()->month;
            })
            ->sum('amount');

            $bestMonth = collect($transactionsSucceded)
            ->filter(function ($item) {
                return Carbon::parse($item->create_time)->year === Carbon::now()->year;
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
            ->last();

        
        
            //Ingresos semanal
        $weeklyAmounts = collect($transactionsSucceded)
            ->filter(function ($item) {
            $date = Carbon::parse($item->create_time);
                return $date->isCurrentWeek();
            })
            ->groupBy(function ($item) {
                return Carbon::parse($item->create_time)->dayOfWeek;
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
        
        //Ingresos totales año actual
        $totalCurrentYear = collect($transactionsSucceded)
            ->filter(function ($item) {
                return Carbon::parse($item->create_time)->year === Carbon::now()->year;
            })
            ->sum('amount');


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

        //Ingresos año pasado
        $totalLastYear = collect($transactionsSucceded)
            ->filter(function ($item) {
                return Carbon::parse($item->create_time)->year === Carbon::now()->subYear(1)->year;
            })
            ->sum('amount');

        //Pagos por Stripe y Paypal
        $stripe = collect($transactionsSucceded)->filter(function ($item) {
            return in_array($item->payment_provider, ['stripe']);
        })->count();

        $paypal = collect($transactionsSucceded)->filter(function ($item) {
            return in_array($item->payment_provider, ['paypal']);
        })->count();
    
        $now = Carbon::now();
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $usersThirtyDaysAgo = User::where('created_at', '<=', $thirtyDaysAgo)->count();
        $userDifference = $currentContacts - $usersThirtyDaysAgo;
        $latestUsers = User::orderBy('created_at', 'desc')->take(5)->get();

        return view('admin.index', compact('currentContacts','currentUsers','userDifference', 'latestUsers','totalCurrentYear','bestMonth','totalLastYear','totalCurrentMonth','monthlyAmounts','paypal','stripe','weeklyAmounts','currentWeekAmount'));
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
