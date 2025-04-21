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
    public $provider_type = [];
    public $startDate = '';
    public $endDate = '';
    public $sourceNames = [];
    public $sourceTypes = [];
    public $filteredSourceNames = [];
    public $availableTags = [];
    public $selectedTags = []; // Changed from $tags to $selectedTags for consistency
    public $debug = false; // Add debug flag to help troubleshoot the query

    protected $queryString = [
        'country' => ['except' => '*'],
        'date_filter' => ['except' => '*'],
        'search' => ['except' => ''],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'source_type' => ['except' => []],
        'provider_type' => ['except' => []],
        'selectedTags' => ['except' => []] // Updated from tags to selectedTags
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
        $this->provider_type = ['stripe', 'paypal'];
        $this->status = ['active', 'canceled', 'incomplete_expired', 'past_due'];
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
        if (in_array($propertyName, ['status', 'source', 'source_type', 'provider_type', 'selectedTags'])) {
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
        $query = Subscription::query()->with('contact');

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

        if (!empty($this->provider_type)) {
            $query->whereIn('provider_type', $this->provider_type);
        } else {
            // Si no hay provider types seleccionados, no mostrar resultados
            $query->where('id', 0);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('create_time', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay()
            ]);
        }
        
        // Filter by tags - improved version
        if (!empty($this->selectedTags)) {
            $query->whereHas('contact', function($q) {
                $q->where(function($subQuery) {
                    foreach ($this->selectedTags as $tag) {
                        // Handle JSON format
                        $subQuery->orWhere(function($jsonQuery) use ($tag) {
                            $jsonQuery->whereRaw('JSON_CONTAINS(tags, ?)', ['"' . $tag . '"'])
                                     ->orWhere('tags', 'like', '%"' . $tag . '"%');
                        });
                        
                        // Handle comma-separated format
                        $subQuery->orWhere(function($csvQuery) use ($tag) {
                            $csvQuery->orWhere('tags', '=', $tag)
                                   ->orWhere('tags', 'like', $tag . ',%')
                                   ->orWhere('tags', 'like', '%,' . $tag . ',%')
                                   ->orWhere('tags', 'like', '%,' . $tag);
                        });
                    }
                });
            });
        }
        
        // Debug SQL query if debug is enabled
        if ($this->debug) {
            $sql = $query->toSql();
            $bindings = $query->getBindings();
            
            // Replace ? with actual values
            foreach ($bindings as $binding) {
                $value = is_numeric($binding) ? $binding : "'" . $binding . "'";
                $sql = preg_replace('/\?/', $value, $sql, 1);
            }
        
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
                ->toArray(),
            'availableTags' => $this->availableTags,
            'sql' => $this->debug ? 'log' : [] // Add SQL to view for debugging
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

    public function selectAllProviderTypes()
    {
        $this->provider_type = ['stripe', 'paypal'];
        $this->resetPage();
    }

    public function deselectAllProviderTypes()
    {
        $this->provider_type = [];
        $this->resetPage();
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

    // Add a method to toggle debug mode
    public function toggleDebug()
    {
        $this->debug = !$this->debug;
        if ($this->debug) {
            'log';
        } else {
            'log';
        }
    }
}
