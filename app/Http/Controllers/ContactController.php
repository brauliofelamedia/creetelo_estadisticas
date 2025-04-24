<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Nnjeim\World\Models\Country;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::query();
        $perPage = $request->get('perPage', 15);

        // Apply country filter
        if ($request->has('country') && $request->country !== '*') {
            $query->where('country', $request->country);
        }

        // Apply date filter
        if ($request->has('date_filter') && $request->date_filter !== '*') {
            switch($request->date_filter) {
                case 'week':
                    $query->whereBetween('date_added', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereBetween('date_added', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
                    break;
                case 'year':
                    $query->whereBetween('date_added', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()]);
                    break;
                default:
                    // For specific dates
                    $date = Carbon::parse($request->date_filter);
                    $query->where('date_added', '>=', $date->format('Y-m-d'));
                    break;
            }
        }

        // Apply tag filter
        if ($request->has('tag') && !in_array('*', (array)$request->tag)) {
            $tagArray = (array)$request->tag;
            if (!empty($tagArray)) {
                $query->where(function($query) use ($tagArray) {
                    foreach ($tagArray as $tag) {
                        $query->orWhereJsonContains('tags', $tag);
                    }
                });
            }
        }

        // Apply search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'like', '%' . strtolower($request->search) . '%')
                  ->orWhere('last_name', 'like', '%' . strtolower($request->search) . '%')
                  ->orWhere('email', 'like', '%' . strtolower($request->search) . '%');
            });
        }

        // Load transaction amounts for each contact with status succeeded
        $query->with(['transactions' => function($query) {
            $query->where('status', 'succeeded');
        }]);

        // Get countries for filter
        $countriesUser = Contact::select('country')
            ->distinct()
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->pluck('country')
            ->unique()
            ->sort(function ($a, $b) {
                if ($a === 'United States') return -1;
                if ($b === 'United States') return 1;
                return $a <=> $b;
            })->values()->toArray();

        $countriesWithNames = Country::whereIn('iso2', $countriesUser)
            ->pluck('name', 'iso2')
            ->toArray();

        // Define special tags that need to be highlighted
        $specialTags = [
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

        // Get all unique tags for filter
        $allTags = Contact::select('tags')
            ->whereNotNull('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();
            
        // Separate special tags from other tags
        $otherTags = array_diff($allTags, $specialTags);
        
        // Sort both arrays
        sort($specialTags);
        sort($otherTags);
        
        // Execute the query to get all matching contacts (without pagination)
        $allFilteredContacts = $query->clone()->get();
        
        // Calculate total amount by summing only succeeded transactions
        $totalAmount = $allFilteredContacts->sum(function($contact) {
            return $contact->transactions->where('status', 'succeeded')->sum('amount');
        });

        // Get paginated results
        $contacts = $query->paginate($perPage);
        
        $noResultsMessage = $contacts->isEmpty() 
            ? 'No se encontraron registros con los filtros aplicados.' 
            : '';
        
        return view('admin.contact.index', [
            'contacts' => $contacts,
            'countries' => $countriesWithNames,
            'noResultsMessage' => $noResultsMessage,
            'perPage' => $perPage,
            'specialTags' => $specialTags,
            'otherTags' => $otherTags,
            'totalAmount' => $totalAmount,
        ]);
    }
}
