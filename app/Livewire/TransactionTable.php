<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionTable extends Component
{
    use WithPagination;

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
        'search' => ['except' => ''],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => '']
    ];

    public function mount()
    {
        $this->source = ['funnel'];
        $this->status = ['succeeded','refunded','failed'];
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->sourceNames = Transaction::select('entity_resource_name')
            ->distinct()
            ->pluck('entity_resource_name')
            ->toArray();
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
        $query = Transaction::query();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->status)) {
            $query->whereIn('status', $this->status);
        }

        if (!empty($this->source)) {
            $query->whereIn('source_type', $this->source);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('create_time', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay()
            ]);
        }

        return $query;
    }

    protected function getPaginatedTransactions()
    {
        return $this->getFilteredTransactions()
            ->offset(($this->page - 1) * $this->perPage)
            ->limit($this->perPage)
            ->get();
    }

    public function calculateTotalPages()
    {
        $this->totalPages = ceil($this->getFilteredTransactions()->count() / $this->perPage);
    }

    public function render()
    {
        $this->calculateTotalPages();
        $transactions = $this->getPaginatedTransactions();
        
        $totalAmount = $this->getFilteredTransactions()
            ->where('status', 'succeeded')
            ->sum('amount');

        $this->noResultsMessage = $transactions->isEmpty() 
            ? 'No se encontraron registros con los filtros aplicados.' 
            : '';

        return view('livewire.transaction-table', [
            'transactions' => $transactions,
            'totalAmount' => $totalAmount,
            'sourceNames' => $this->sourceNames,
            'noResultsMessage' => $this->noResultsMessage
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
