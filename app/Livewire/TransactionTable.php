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
    public $sourceTypes = [];
    public $sourceTypeNames = [];
    public $sourceType = []; 
    public $filteredSourceNames = []; // New property to store filtered sourceNames

    protected $queryString = [
        'search' => ['except' => ''],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => '']
    ];

    public function mount()
    {
        $this->sourceNames = Transaction::select('entity_resource_name')
            ->distinct()
            ->whereNotNull('entity_resource_name')
            ->pluck('entity_resource_name')
            ->toArray();
            
        $this->sourceTypeNames = Transaction::select('entity_source_type')
            ->distinct()
            ->whereNotNull('entity_source_type')
            ->pluck('entity_source_type')
            ->toArray();
            
        $this->source = $this->sourceNames;
        $this->sourceType = $this->sourceTypeNames;
        $this->filteredSourceNames = $this->sourceNames; // Initialize with all source names
        $this->status = ['succeeded','refunded','failed'];
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
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
        
        if ($propertyName === 'sourceType') {
            $this->updateFilteredSourceNames();
            // Reset source selection when sourceType changes
            $this->source = array_intersect($this->source, $this->filteredSourceNames);
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

        // Solo aplicar el filtro de fuentes si hay alguna seleccionada
        if (!empty($this->source)) {
            $query->whereIn('entity_resource_name', $this->source);
        }
        
        // Aplicar filtro de tipos de fuente si hay alguno seleccionado
        if (!empty($this->sourceType)) {
            $query->whereIn('entity_source_type', $this->sourceType);
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

    // New method to update filteredSourceNames based on selected sourceTypes
    public function updateFilteredSourceNames()
    {
        if (empty($this->sourceType)) {
            $this->filteredSourceNames = []; // If no sourceType selected, show no sources
            return;
        }
        
        // Get sourceNames that match the selected sourceTypes
        $this->filteredSourceNames = Transaction::select('entity_resource_name')
            ->distinct()
            ->whereNotNull('entity_resource_name')
            ->whereIn('entity_source_type', $this->sourceType)
            ->pluck('entity_resource_name')
            ->toArray();
    }

    public function render()
    {
        $this->calculateTotalPages();
        $transactions = $this->getPaginatedTransactions();
        
        // Update filteredSourceNames before rendering
        $this->updateFilteredSourceNames();
        
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
            'filteredSourceNames' => $this->filteredSourceNames,
            'sourceTypeNames' => $this->sourceTypeNames,
            'noResultsMessage' => $this->noResultsMessage
        ]);
    }

    public function updating($name)
    {
        $this->resetPage();
    }

    public function selectAllSources()
    {
        $this->source = $this->filteredSourceNames; // Use filtered sources instead of all sources
        $this->render();
    }

    public function deselectAllSources()
    {
        $this->source = [];
        $this->render();
    }
    
    public function selectAllSourceTypes()
    {
        $this->sourceType = $this->sourceTypeNames;
        $this->render();
    }

    public function deselectAllSourceTypes()
    {
        $this->sourceType = [];
        $this->render();
    }
}
