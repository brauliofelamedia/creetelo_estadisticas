<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use App\Models\User;

class UsersTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $page = 1;
    public $totalPages;
    public $noResultsMessage = '';
    public $status = '';
    public $role = '';

    protected $queryString = [
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

    protected function getFilteredUsers()
    {
        $users = User::all();

        if ($this->search !== '') {
            $users = $users->filter(function ($user) {
                return str_contains(strtolower($user->name), strtolower($this->search)) ||
                       str_contains(strtolower($user->email), strtolower($this->search));
            });
        }

        if ($this->status !== '') {
            $users = $users->filter(function ($user) {
                return $user->status === $this->status;
            });
        }

        if ($this->role !== '') {
            $users = $users->filter(function ($user) {
                return $user->role === $this->role;
            });
        }

        return $users;
    }

    protected function getPaginatedUsers()
    {
        return $this->getFilteredUsers()
            ->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage);
    }

    public function calculateTotalPages()
    {
        $filteredUsers = $this->getFilteredUsers();
        $this->totalPages = ceil($filteredUsers->count() / $this->perPage);
    }

    public function render()
    {
        $this->calculateTotalPages();
        $users = $this->getPaginatedUsers();
        
        $this->noResultsMessage = $users->isEmpty() 
            ? 'No se encontraron usuarios con los filtros aplicados.' 
            : '';

        return view('livewire.users-table', [
            'users' => $users,
            'noResultsMessage' => $this->noResultsMessage,
            'roles' => collect(collect(Config('app.users.data'))->pluck('role')->unique()->values())->sort()->values()->toArray()
        ]);
    }

    public function updating($name)
    {
        $this->resetPage();
    }
}
