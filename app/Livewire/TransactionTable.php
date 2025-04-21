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
    public $provider_type = [];
    public $sourceNames = [];
    public $sourceTypes = [];
    public $sourceTypeNames = [];
    public $sourceType = []; 
    public $filteredSourceNames = [];
    public $mainSources = []; // New property for main sources (start with M)
    public $secondarySources = []; // New property for secondary sources
    public $filteredMainSources = []; // New property for filtered main sources
    public $filteredSecondarySources = []; // New property for filtered secondary sources
    public $availableTags = [];
    public $selectedTags = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'provider_type' => ['except' => []],
        'selectedTags' => ['except' => []]
    ];

    public function mount()
    {
        $this->sourceNames = Transaction::select('entity_resource_name')
            ->distinct()
            ->whereNotNull('entity_resource_name')
            ->pluck('entity_resource_name')
            ->toArray();

        $this->provider_type = ['stripe', 'paypal'];
            
        $this->sourceTypeNames = Transaction::select('entity_source_type')
            ->distinct()
            ->whereNotNull('entity_source_type')
            ->pluck('entity_source_type')
            ->toArray();
        
        // Divide sources into main and secondary
        $this->mainSources = array_filter($this->sourceNames, function($source) {
            return str_starts_with($source, 'M');
        });
        
        $this->secondarySources = array_filter($this->sourceNames, function($source) {
            return !str_starts_with($source, 'M');
        });
        
        $this->source = $this->sourceNames;
        $this->sourceType = $this->sourceTypeNames;
        $this->filteredSourceNames = $this->sourceNames; // Initialize with all source names
        $this->filteredMainSources = $this->mainSources; // Initialize with all main sources
        $this->filteredSecondarySources = $this->secondarySources; // Initialize with all secondary sources
        $this->status = ['succeeded','refunded','failed'];
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        
        // Initialize available tags
        $this->loadAvailableTags();
        $this->selectedTags = $this->availableTags; // Default to all tags selected
        
        $this->calculateTotalPages();
    }

    protected function loadAvailableTags()
    {
        // Use a fixed list of specific tags instead of retrieving from database
        $this->availableTags = [
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
        if (in_array($propertyName, ['status', 'source', 'provider_type', 'selectedTags'])) {
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

        if (!empty($this->provider_type)) {
            $query->whereIn('payment_provider', $this->provider_type);
        } else {
            // Si no hay provider types seleccionados, no mostrar resultados
            $query->where('id', 0);
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
        
        // Apply tag filtering to contacts associated with transactions
        if (!empty($this->selectedTags)) {
            $query->whereHas('contact', function($q) {
                $q->where(function ($subQ) {
                    foreach ($this->selectedTags as $tag) {
                        // Use JSON_CONTAINS for MySQL or PostgreSQL
                        $subQ->orWhereRaw('JSON_CONTAINS(tags, ?)', ['"' . $tag . '"']);
                    }
                });
            });
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('create_time', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay()
            ]);
        }

        // Add eager loading of the contact relationship
        $query->with('contact');

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

    // Update the updateFilteredSourceNames method to also update main and secondary sources
    public function updateFilteredSourceNames()
    {
        if (empty($this->sourceType)) {
            $this->filteredSourceNames = []; // If no sourceType selected, show no sources
            $this->filteredMainSources = [];
            $this->filteredSecondarySources = [];
            return;
        }
        
        // Get sourceNames that match the selected sourceTypes
        $this->filteredSourceNames = Transaction::select('entity_resource_name')
            ->distinct()
            ->whereNotNull('entity_resource_name')
            ->whereIn('entity_source_type', $this->sourceType)
            ->pluck('entity_resource_name')
            ->toArray();
        
        // Divide filtered sources into main and secondary
        $this->filteredMainSources = array_filter($this->filteredSourceNames, function($source) {
            return str_starts_with($source, 'M');
        });
        
        $this->filteredSecondarySources = array_filter($this->filteredSourceNames, function($source) {
            return !str_starts_with($source, 'M');
        });
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
            'filteredMainSources' => $this->filteredMainSources,
            'filteredSecondarySources' => $this->filteredSecondarySources,
            'sourceTypeNames' => $this->sourceTypeNames,
            'noResultsMessage' => $this->noResultsMessage,
            'availableTags' => $this->availableTags
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

    public function selectAllProviderTypes()
    {
        $this->provider_type = ['stripe', 'paypal'];
        $this->resetPage();
    }

    public function deselectAllSourceTypes()
    {
        $this->sourceType = [];
        $this->render();
    }

    public function selectAllTags()
    {
        $this->selectedTags = $this->availableTags;
        $this->render();
    }

    public function deselectAllTags()
    {
        $this->selectedTags = [];
        $this->render();
    }

    public function deselectAllProviderTypes()
    {
        $this->provider_type = [];
        $this->resetPage();
    }

    // Add methods to select/deselect main sources
    public function selectAllMainSources()
    {
        $this->source = array_unique(array_merge($this->source, $this->filteredMainSources));
        $this->render();
    }

    public function deselectAllMainSources()
    {
        $this->source = array_values(array_diff($this->source, $this->filteredMainSources));
        $this->render();
    }

    // Add methods to select/deselect secondary sources
    public function selectAllSecondarySources()
    {
        $this->source = array_unique(array_merge($this->source, $this->filteredSecondarySources));
        $this->render();
    }

    public function deselectAllSecondarySources()
    {
        $this->source = array_values(array_diff($this->source, $this->filteredSecondarySources));
        $this->render();
    }
}
