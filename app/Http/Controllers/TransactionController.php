<?php

namespace App\Http\Controllers;

use App\Exports\TransactionsExport;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Contact;
use Maatwebsite\Excel\Facades\Excel;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        // Get filter parameters from request
        $search = $request->input('search', '');
        $status = $request->input('status', ['succeeded', 'refunded', 'failed']);
        $startDate = $request->input('startDate', '2024-01-01');
        $endDate = $request->input('endDate', Carbon::now()->format('Y-m-d'));
        $provider_type = $request->input('provider_type', ['paypal','stripe']);
        $sourceType = $request->input('sourceType', ['membership', 'subscription', 'payment_link','invoice','manual','communities','funnel']);
        $selectedTags = $request->input('selectedTags', []);

        // Get unique source types for filtering - using entity_source_type instead of source_type
        $sourceTypeNames = Transaction::select('entity_source_type')
            ->distinct()
            ->whereNotNull('entity_source_type')
            ->orderBy('entity_source_type')
            ->pluck('entity_source_type')
            ->toArray();

        // Get all sources from Transaction model instead of Subscription
        $allSources = Transaction::select('entity_resource_name')
            ->whereNotNull('entity_resource_name')
            ->distinct()
            ->pluck('entity_resource_name')
            ->toArray();

        // Define priority sources - refined list
        $prioritySources = Transaction::select('entity_resource_name')
            ->where(function($query) {
                $query->where('entity_resource_name', 'like', 'M.%')
                      ->orWhere('entity_resource_name', 'like', 'Únete a Créetelo%');
            })
            ->distinct()
            ->pluck('entity_resource_name')
            ->toArray();

        // Get other sources by excluding priority sources
        $otherSources = array_diff($allSources, $prioritySources);
        
        // Use source from request, or default to prioritySources if not provided
        $source = $request->input('source', $prioritySources);

        // Flag to check if any filter has been applied
        $filtersApplied = $search !== '' || 
                        !empty($status) || 
                        !empty($source) || 
                        !empty($provider_type) || 
                        !empty($sourceType) ||
                        ($startDate && $endDate);
        
        // Flag to check if tags filter is specifically applied
        $tagsFilterApplied = $request->has('selectedTags');

        // Default provider_type values only when filters are applied
        if (empty($provider_type) && $filtersApplied) {
            $provider_type = ['stripe', 'paypal'];
        }

        // Default date range only when filters are applied
        if (empty($startDate) && empty($endDate) && $filtersApplied) {
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        }
        
        // Define available tags
        $availableTags = [
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
        
        // Get source names - this is redundant with allSources but kept for backward compatibility
        $sourceNames = $allSources;
            
        // Get filtered source names based on source type
        $filteredSourceNames = [];
        if (!empty($sourceType)) {
            $filteredSourceNames = Transaction::select('entity_resource_name')
                ->distinct()
                ->whereNotNull('entity_resource_name')
                ->whereIn('entity_source_type', $sourceType)
                ->pluck('entity_resource_name')
                ->toArray();
        } else {
            $filteredSourceNames = $sourceNames;
        }
        
        // Separate filtered source names into main and secondary sources
        $filteredMainSources = array_intersect($filteredSourceNames, $prioritySources);
        $filteredSecondarySources = array_intersect($filteredSourceNames, $otherSources);
        
        // Build the query
        $query = Transaction::query()->with('contact');
        
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
    
        if (!empty($provider_type)) {
            $query->whereIn('payment_provider', $provider_type);
        }
        
        if (!empty($status)) {
            $query->whereIn('status', $status);
        }
        
        if (!empty($source)) {
            $query->whereIn('entity_resource_name', $source);
        }
        
        if (!empty($sourceType)) {
            $query->whereIn('entity_source_type', $sourceType);
        }
        
        // FIX: Properly handle the tag filtering - fix for the empty results issue
        if (!empty($selectedTags)) {
            // First, get contacts that have any of the selected tags
            $contactsWithTags = Contact::where(function ($query) use ($selectedTags) {
                foreach ($selectedTags as $tag) {
                    $query->orWhereRaw("JSON_CONTAINS(tags, ?)", ['"' . $tag . '"']);
                }
            })->pluck('id');
            
            // Then filter transactions by those contacts
            if ($contactsWithTags->isNotEmpty()) {
                $query->whereIn('contactId', $contactsWithTags);
            } else {
                // If no contacts found with these tags, make sure no results are returned
                $query->whereRaw('1 = 0');
            }
        }
        
        if ($startDate && $endDate) {
            $query->whereBetween('create_time', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }
        
        // Calculate total amount for succeeded transactions
        $totalAmount = (clone $query)
            ->where('status', 'succeeded')
            ->sum('amount');
        
        // Get paginated results using Laravel's built-in pagination
        $transactions = $query->paginate(12);
        
        // Debug info about tag filtering
        $tagFilterInfo = '';
        if (!empty($selectedTags)) {
            $tagFilterInfo = 'Filtrado por etiquetas: ' . implode(', ', $selectedTags) . 
                           ' - Contactos encontrados: ' . (isset($contactsWithTags) ? $contactsWithTags->count() : 0);
        }
        
        // Set no results message
        $noResultsMessage = $transactions->isEmpty() 
            ? 'No se encontraron registros con los filtros aplicados.' . 
              (!empty($tagFilterInfo) ? ' ' . $tagFilterInfo : '')
            : '';

        return view('admin.transactions.index', [
            'transactions' => $transactions,
            'search' => $search,
            'status' => $status,
            'source' => $source,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'provider_type' => $provider_type,
            'sourceType' => $sourceType,
            'selectedTags' => $selectedTags,
            'sourceNames' => $sourceNames,
            'filteredSourceNames' => $filteredSourceNames,
            'filteredMainSources' => $filteredMainSources,
            'filteredSecondarySources' => $filteredSecondarySources,
            'sourceTypeNames' => $sourceTypeNames,
            'availableTags' => $availableTags,
            'totalAmount' => $totalAmount,
            'noResultsMessage' => $noResultsMessage,
            'filtersApplied' => $filtersApplied,
            'tagsFilterApplied' => $tagsFilterApplied,
            'allSources' => $allSources,
            'prioritySources' => $prioritySources,
            'otherSources' => $otherSources
        ]);
    }

    public function export(Request $request)
    {
        // Get filter parameters from request
        $search = $request->input('search', '');
        $status = $request->input('status', ['succeeded', 'refunded', 'failed']);
        $startDate = $request->input('startDate', '2024-01-01');
        $endDate = $request->input('endDate', Carbon::now()->format('Y-m-d'));
        $provider_type = $request->input('provider_type', ['paypal','stripe']);
        $sourceType = $request->input('sourceType', ['membership', 'subscription', 'payment_link','invoice','manual','communities','funnel']);
        $selectedTags = $request->input('selectedTags', []);
        $source = $request->input('source', []);

        // Build the query
        $query = Transaction::query()->with('contact');
        
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
    
        if (!empty($provider_type)) {
            $query->whereIn('payment_provider', $provider_type);
        }
        
        if (!empty($status)) {
            $query->whereIn('status', $status);
        }
        
        if (!empty($source)) {
            $query->whereIn('entity_resource_name', $source);
        }
        
        if (!empty($sourceType)) {
            $query->whereIn('entity_source_type', $sourceType);
        }
        
        // Handle tag filtering
        if (!empty($selectedTags)) {
            $contactsWithTags = Contact::where(function ($query) use ($selectedTags) {
                foreach ($selectedTags as $tag) {
                    $query->orWhereRaw("JSON_CONTAINS(tags, ?)", ['"' . $tag . '"']);
                }
            })->pluck('id');
            
            if ($contactsWithTags->isNotEmpty()) {
                $query->whereIn('contactId', $contactsWithTags);
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        
        if ($startDate && $endDate) {
            $query->whereBetween('create_time', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }

        // Generate a meaningful filename with the current date
        $filename = 'transactions_export_' . Carbon::now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(new TransactionsExport($query), $filename);
    }
}
