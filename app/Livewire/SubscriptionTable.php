<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Subscription;

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
    public $status = '';
    public $source = '';

    protected $queryString = [
        'country' => ['except' => '*'],
        'date_filter' => ['except' => '*'],
        'search' => ['except' => '']
    ];

    public function mount()
    {
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

    protected function getFilteredSubscriptions()
    {
        $subscriptions = collect(Config('app.subscriptions.data'));

        if ($this->search !== '') {
            $subscriptions = $subscriptions->filter(function ($subscription) {
                return str_contains(strtolower($subscription->contactName), strtolower($this->search));
            });
        }

        if ($this->status !== '') {
            $subscriptions = $subscriptions->filter(function ($subscription) {
                return $subscription->status === $this->status;
            });
        }

        if ($this->source !== '') {
            $subscriptions = $subscriptions->filter(function ($subscription) {
                return $subscription->entitySourceName === $this->source;
            });
        }

        return $subscriptions;
    }

    protected function getPaginatedSubscriptions()
    {
        return $this->getFilteredSubscriptions()
            ->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage);
    }

    public function calculateTotalPages()
    {
        $filteredSubscriptions = $this->getFilteredSubscriptions();
        $this->totalPages = ceil($filteredSubscriptions->count() / $this->perPage);
    }

    public function render()
    {
        $this->calculateTotalPages();
        $subscriptions = $this->getPaginatedSubscriptions();
        
        $this->noResultsMessage = $subscriptions->isEmpty() 
            ? 'No se encontraron registros con los filtros aplicados.' 
            : '';

        return view('livewire.subscription-table', [
            'subscriptions' => $subscriptions,
            'noResultsMessage' => $this->noResultsMessage,
            'sourceNames' => collect(collect(Config('app.subscriptions.data'))->pluck('entitySourceName')->unique()->values())->sort()->values()->toArray()
        ]);
    }

    public function updating($name)
    {
        $this->resetPage();
    }
}
