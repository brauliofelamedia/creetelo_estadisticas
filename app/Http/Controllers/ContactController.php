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

        // Apply country filter
        if ($request->has('country') && $request->country !== '*') {
            $query->where('country', $request->country);
        }

        // Apply date filter
        if ($request->has('date_filter') && $request->date_filter !== '*') {
            $date = Carbon::parse($request->date_filter);
            
            switch($request->date_filter) {
                case 'week':
                    $query->whereBetween('dateAdded', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereBetween('dateAdded', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
                    break;
                case 'year':
                    $query->whereBetween('dateAdded', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()]);
                    break;
            }
        }

        // Apply search
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('firstNameLowerCase', 'like', '%' . strtolower($request->search) . '%')
                  ->orWhere('lastNameLowerCase', 'like', '%' . strtolower($request->search) . '%')
                  ->orWhere('email', 'like', '%' . strtolower($request->search) . '%');
            });
        }

        // Get countries for filter
        $countries = Contact::select('country')
            ->distinct()
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->pluck('country');

        // Get paginated results
        $contacts = $query->paginate(30);
        
        return view('admin.contact.index');
    }
}
