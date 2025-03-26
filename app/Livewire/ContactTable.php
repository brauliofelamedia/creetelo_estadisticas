<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Contact;
use Nnjeim\World\Models\Country;
use Carbon\Carbon;

class ContactTable extends Component
{
    use WithPagination;

    public $country = '*';
    public $date_filter = '*';
    public $search = '';
    public $perPage = 10;
    public $page = 1;
    public $totalPages;
    public $noResultsMessage = '';

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

    protected function getFilteredContacts()
    {
        $contacts = collect(Config('app.contacts.data'));

        if ($this->country !== '*') {
            $contacts = $contacts->filter(function ($contact) {
                return $contact->countryName === $this->country;
            });
        }

        if ($this->date_filter !== '*') {
            $contacts = $contacts->filter(function ($contact) {
            $contactDate = Carbon::parse($contact->dateAdded)->format('Y-m-d');
            return $contactDate >= $this->date_filter;
            });
        }

        if ($this->search !== '') {
            $contacts = $contacts->filter(function ($contact) {
                return str_contains(strtolower($contact->firstNameLowerCase), strtolower($this->search)) ||
                       str_contains(strtolower($contact->lastNameLowerCase), strtolower($this->search));
            });
        }

        return $contacts;
    }

    protected function getPaginatedContacts()
    {
        return $this->getFilteredContacts()
            ->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage);
    }

    public function calculateTotalPages()
    {
        $filteredContacts = $this->getFilteredContacts();
        $this->totalPages = ceil($filteredContacts->count() / $this->perPage);
    }

    public function render()
    {
        $this->calculateTotalPages();
        $contacts = $this->getPaginatedContacts();
        
        $this->noResultsMessage = $contacts->isEmpty() 
            ? 'No se encontraron registros con los filtros aplicados.' 
            : '';

        return view('livewire.contact-table', [
            'contacts' => $contacts,
            'countries' => collect(collect(Config('app.contacts.data'))->pluck('countryName')->unique()->values())
                ->sort(function ($a, $b) {
                    if ($a === 'United States') return -1;
                    if ($b === 'United States') return 1;
                    return $a <=> $b;
                })->values()->toArray(),
            'noResultsMessage' => $this->noResultsMessage
        ]);
    }

    public function updating($name)
    {
        $this->resetPage();
    }
}
