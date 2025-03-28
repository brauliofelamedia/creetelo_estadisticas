<?php

namespace App\Livewire;

use App\Models\Transaction;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class SubscriptionTable extends Component
{
    use WithPagination;

    public $country = '*';
    public $date_filter = '*';
    public $search = '';
    public $perPage = 10;
    public $page = 1;
    public $totalPages;
    public $noResultsMessage = '';
    public $status = [];
    public $source = [];
    public $startDate = '';
    public $endDate = '';
    public $sourceNames = [];

    protected $queryString = [
        'country' => ['except' => '*'],
        'date_filter' => ['except' => '*'],
        'search' => ['except' => ''],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => '']
    ];

    public function mount()
    {
        $this->status = ['succeeded'];
        $this->source = Transaction::pluck('entity_resource_name')->unique()->values()->toArray();
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->sourceNames = $this->source;
        $this->calculateTotalPages();
    }

    public function nextPage()
    {
        if ($this->page < $this->totalPages) {
            $this->page++;
        }
    }

    public function previousPage()
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function updatedPerPage()
    {
        $this->page = 1;
        $this->calculateTotalPages();
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['status', 'source'])) {
            $this->dispatch('select2:updated');
        }
        $this->resetPage();
    }

    protected function getFilteredTransactions()
    {
        $transactions = Transaction::all();

        if ($this->search !== '') {
            $transactions = $transactions->filter(function ($transaction) {
                return str_contains(strtolower($transaction->name), strtolower($this->search));
            });
        }

        if (!empty($this->status)) {
            $transactions = $transactions->filter(function ($transaction) {
                return in_array($transaction->status, $this->status);
            });
        }

        if (!empty($this->source)) {
            $transactions = $transactions->filter(function ($transaction) {
                return in_array($transaction->entity_resource_name, $this->source);
            });
        }

        if ($this->startDate && $this->endDate) {
            $transactions = $transactions->filter(function ($transaction) {
                $transactionDate = Carbon::parse($transaction->create_time);
                $startDate = Carbon::parse($this->startDate)->startOfDay();
                $endDate = Carbon::parse($this->endDate)->endOfDay();
                return $transactionDate->between($startDate, $endDate);
            });
        }

        return $transactions;
    }

    protected function getPaginatedTransactions()
    {
        return $this->getFilteredTransactions()
            ->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage);
    }

    public function calculateTotalPages()
    {
        $filteredTransactions = $this->getFilteredTransactions();
        $this->totalPages = ceil($filteredTransactions->count() / $this->perPage);
    }

    public function render()
    {
        $this->calculateTotalPages();
        $transactions = $this->getPaginatedTransactions();
        $totalAmount = $this->getFilteredTransactions()
            ->filter(function ($transaction) {
                return $transaction->status === 'succeeded';
            })
            ->sum('amount');
        
        $this->noResultsMessage = $transactions->isEmpty() 
            ? 'No se encontraron registros con los filtros aplicados.' 
            : '';

        return view('livewire.transaction-table', [
            'transactions' => $transactions,
            'totalAmount' => $totalAmount,
            'countries' => collect(collect(Config('app.transactions.data'))->pluck('countryName')->unique()->values())
                ->sort(function ($a, $b) {
                    if ($a === 'United States') return -1;
                    if ($b === 'United States') return 1;
                    return $a <=> $b;
                })->values()->toArray(),
            'noResultsMessage' => $this->noResultsMessage,
            'sourceNames' => collect(collect(Config('app.transactions.data'))->pluck('entitySourceName')->unique()->values())->sort()->values()->toArray()
        ]);
    }

    public function updating($name)
    {
        $this->resetPage();
    }

    public function selectAllSources()
    {
        $this->source = $this->sourceNames;
        $this->render();
    }

    public function deselectAllSources()
    {
        $this->source = [];
        $this->render();
    }
}
