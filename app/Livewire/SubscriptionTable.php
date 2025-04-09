<?php

namespace App\Livewire;

use App\Models\Subscription;
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
    public $source_type = [];
    public $startDate = '';
    public $endDate = '';
    public $sourceNames = [];
    public $sourceTypes = [];
    public $filteredSourceNames = [];

    protected $queryString = [
        'country' => ['except' => '*'],
        'date_filter' => ['except' => '*'],
        'search' => ['except' => ''],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'source_type' => ['except' => []]
    ];

    public function mount()
    {
        $this->sourceTypes = Subscription::select('source_type')
            ->distinct()
            ->whereNotNull('source_type')
            ->pluck('source_type')
            ->toArray();
        
        $this->sourceNames = Subscription::select('entity_resource_name')
            ->distinct()
            ->whereNotNull('entity_resource_name')
            ->pluck('entity_resource_name')
            ->toArray();
        
        $this->filteredSourceNames = $this->sourceNames;
        $this->source = $this->sourceNames;
        $this->source_type = $this->sourceTypes;
        $this->status = ['active', 'canceled', 'incomplete_expired', 'past_due'];
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->calculateTotalPages();
    }

    // New method to filter source names based on selected source types
    public function getFilteredSourceNames()
    {
        if (empty($this->source_type)) {
            return [];
        }

        return Subscription::select('entity_resource_name')
            ->distinct()
            ->whereNotNull('entity_resource_name')
            ->whereIn('source_type', $this->source_type)
            ->pluck('entity_resource_name')
            ->toArray();
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
        if (in_array($propertyName, ['status', 'source', 'source_type'])) {
            $this->dispatch('select2:updated');
            
            // Update filtered source names when source_type changes
            if ($propertyName === 'source_type') {
                $this->filteredSourceNames = $this->getFilteredSourceNames();
                
                // Update source to only include valid options
                $this->source = array_intersect($this->source, $this->filteredSourceNames);
                if (empty($this->source) && !empty($this->filteredSourceNames)) {
                    $this->source = $this->filteredSourceNames;
                }
            }
        }
        $this->resetPage();
    }

    protected function getFilteredSubscriptions()
    {
        $query = Subscription::query();

        if ($this->search !== '') {
            $query->whereHas('contact', function($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('last_name', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->status)) {
            $query->whereIn('status', $this->status);
        }

        if (!empty($this->source)) {
            $query->whereIn('entity_resource_name', $this->source);
        } else {
            // Si no hay fuentes seleccionadas, no mostrar resultados
            $query->where('id', 0);
        }

        if (!empty($this->source_type)) {
            $query->whereIn('source_type', $this->source_type);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('create_time', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay()
            ]);
        }

        return $query;
    }

    protected function getPaginatedSubscriptions()
    {
        return $this->getFilteredSubscriptions()
            ->paginate($this->perPage, ['*'], 'page', $this->page);
    }

    public function calculateTotalPages()
    {
        $this->totalPages = ceil($this->getFilteredSubscriptions()->count() / $this->perPage);
    }

    public function render()
    {
        $this->calculateTotalPages();
        $subscriptions = $this->getPaginatedSubscriptions();
        
        $totalAmount = $this->getFilteredSubscriptions()
            ->where('status', 'active')
            ->sum('amount');
        
        $this->noResultsMessage = $subscriptions->isEmpty() 
            ? 'No se encontraron registros con los filtros aplicados.' 
            : '';

        // Get filtered source names based on selected source types
        $this->filteredSourceNames = $this->getFilteredSourceNames();

        return view('livewire.subscription-table', [
            'subscriptions' => $subscriptions,
            'totalAmount' => $totalAmount,
            'noResultsMessage' => $this->noResultsMessage,
            'filteredSourceNames' => $this->filteredSourceNames,
            'sourceNames' => $this->sourceNames,
            'sourceTypes' => Subscription::select('source_type')
                ->distinct()
                ->whereNotNull('source_type')
                ->orderBy('source_type')
                ->pluck('source_type')
                ->values()
                ->toArray()
        ]);
    }

    public function updating($name)
    {
        $this->resetPage();
    }

    public function selectAllSources()
    {
        $this->source = $this->filteredSourceNames;
        $this->resetPage();
    }

    public function deselectAllSources()
    {
        $this->source = [];
        $this->resetPage();
    }

    public function selectAllSourceTypes()
    {
        $this->source_type = $this->sourceTypes;
        $this->resetPage();
    }

    public function deselectAllSourceTypes()
    {
        $this->source_type = [];
        $this->resetPage();
    }
}
