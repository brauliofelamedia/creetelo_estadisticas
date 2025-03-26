<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Config;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use App\Services\Contacts;
use Carbon\Carbon;

class ContactController extends Controller
{
    public function index()
    {
        $allContacts = collect(config('app.contacts.data'));
        $filterContacts = collect(config('app.contacts.data'));

        if (request()->has('country')) {
            $filterContacts = $filterContacts->filter(function ($contact) {
                return request('country') === '*' || $contact->countryName === request('country');
            });
        }

        if (request()->has('date_filter')) {
            $filterContacts = $filterContacts->filter(function ($contact) {
                if (request('date_filter') === '*') {
                    return true;
                }
            
                if(request('date_filter') === 'week'){
                    $contactDate = Carbon::parse($contact->dateAdded);
                    $weekStart = Carbon::now()->startOfWeek();
                    $weekEnd = Carbon::now()->endOfWeek();
                    return $contactDate->between($weekStart, $weekEnd);
                } else if(request('date_filter') === 'month'){
                    $contactDate = Carbon::parse($contact->dateAdded);
                    $monthStart = Carbon::now()->startOfMonth();
                    $monthEnd = Carbon::now()->endOfMonth();
                    return $contactDate->between($monthStart, $monthEnd);
                } else if(request('date_filter') === 'year'){
                    $contactDate = Carbon::parse($contact->dateAdded);
                    $yearStart = Carbon::now()->startOfYear();
                    $yearEnd = Carbon::now()->endOfYear();
                    return $contactDate->between($yearStart, $yearEnd);
                }
                return true;
            });
        }

        //Countries
        $countries = $allContacts->pluck('countryName')->unique()->values()->toArray();
        if (($key = array_search('United States', $countries)) !== false) {
            unset($countries[$key]);
            array_unshift($countries, 'United States');
        }
        return view('admin.contact.index', compact('filterContacts','countries'));
    }
    public function insert()
    {
        $contacts = new Contacts();
        $response = $contacts->contacts();
        
        //Get total table
        $totalTable = Contact::count();
        
        //Get total contacts
        $total = $response['total'];
        $number = ceil($total / 100);

        for ($i = 1; $i <= $number; $i++) {
            $contacts = new Contacts();
            $response = $contacts->contacts($i);

            $leads = response()->json([
                'data' => $response['contacts'],
            ]);

            $leadsData = json_decode(json_encode($leads->getData()), true);

            foreach($leadsData['data'] as $lead) {

                $leadExist = Contact::where('lead_id', $lead['id'])->first();

                if(!$leadExist) {
                    $contact = new Contact();
                    $contact->lead_id = $lead['id'];
                    $contact->phoneLabel = isset($lead['phoneLabel']) ? $lead['phoneLabel'] : '';
                    $contact->country = isset($lead['country']) ? $lead['country'] : '';
                    $contact->address = isset($lead['address']) ? $lead['address'] : '';
                    $contact->source = isset($lead['source']) ? $lead['source'] : '';
                    $contact->type = isset($lead['type']) ? $lead['type'] : '';
                    $contact->locationId = isset($lead['locationId']) ? $lead['locationId'] : '';
                    $contact->website = isset($lead['website']) ? $lead['website'] : '';
                    $contact->dnd = isset($lead['dnd']) ? $lead['dnd'] : false;
                    $contact->state = isset($lead['state']) ? $lead['state'] : '';
                    $contact->businessName = isset($lead['businessName']) ? $lead['businessName'] : '';
                    $contact->customFields = isset($lead['customFields']) ? $lead['customFields'] : null;
                    $contact->tags = isset($lead['tags']) ? $lead['tags'] : null;
                    $contact->dateAdded = isset($lead['dateAdded']) ? Carbon::parse($lead['dateAdded']) : null;
                    $contact->additionalEmails = isset($lead['additionalEmails']) ? $lead['additionalEmails'] : null;
                    $contact->phone = isset($lead['phone']) ? $lead['phone'] : '';
                    $contact->companyName = isset($lead['companyName']) ? $lead['companyName'] : '';
                    $contact->additionalPhones = isset($lead['additionalPhones']) ? $lead['additionalPhones'] : null;
                    $contact->dateUpdated = isset($lead['dateUpdated']) ? Carbon::parse($lead['dateUpdated']) : null;
                    $contact->city = isset($lead['city']) ? $lead['city'] : '';
                    $contact->dateOfBirth = isset($lead['dateOfBirth']) ? Carbon::createFromTimestamp($lead['dateOfBirth'] / 1000)->toDateTimeString(): null;
                    $contact->firstNameLowerCase = isset($lead['firstNameLowerCase']) ? $lead['firstNameLowerCase'] : '';
                    $contact->lastNameLowerCase = isset($lead['lastNameLowerCase']) ? $lead['lastNameLowerCase'] : '';
                    $contact->email = isset($lead['email']) ? $lead['email'] : '';
                    $contact->assignedTo = isset($lead['assignedTo']) ? $lead['assignedTo'] : '';
                    $contact->followers = isset($lead['followers']) ? $lead['followers'] : null;
                    $contact->validEmail = isset($lead['validEmail']) ? $lead['validEmail'] : false;
                    $contact->postalCode = isset($lead['postalCode']) ? $lead['postalCode'] : '';
                    $contact->businessId = isset($lead['businessId']) ? $lead['businessId'] : '';
                    $contact->save();
                } else {
                    $this->update($lead);
                }
                
            }
        }

        return response()->json(['message' => 'Sincronización exitosa']);
    }

    public function update($lead)
    {

        // Update contact
        $contact = Contact::where('lead_id', $lead['id'])->first();
        $contact->lead_id = $lead['id'];
        $contact->phoneLabel = isset($lead['phoneLabel']) ? $lead['phoneLabel'] : '';
        $contact->country = isset($lead['country']) ? $lead['country'] : '';
        $contact->address = isset($lead['address']) ? $lead['address'] : '';
        $contact->source = isset($lead['source']) ? $lead['source'] : '';
        $contact->type = isset($lead['type']) ? $lead['type'] : '';
        $contact->locationId = isset($lead['locationId']) ? $lead['locationId'] : '';
        $contact->website = isset($lead['website']) ? $lead['website'] : '';
        $contact->dnd = isset($lead['dnd']) ? $lead['dnd'] : false;
        $contact->state = isset($lead['state']) ? $lead['state'] : '';
        $contact->businessName = isset($lead['businessName']) ? $lead['businessName'] : '';
        $contact->customFields = isset($lead['customFields']) ? $lead['customFields'] : null;
        $contact->tags = isset($lead['tags']) ? $lead['tags'] : null;
        $contact->dateAdded = isset($lead['dateAdded']) ? Carbon::parse($lead['dateAdded']) : null;
        $contact->additionalEmails = isset($lead['additionalEmails']) ? $lead['additionalEmails'] : null;
        $contact->phone = isset($lead['phone']) ? $lead['phone'] : '';
        $contact->companyName = isset($lead['companyName']) ? $lead['companyName'] : '';
        $contact->additionalPhones = isset($lead['additionalPhones']) ? $lead['additionalPhones'] : null;
        $contact->dateUpdated = isset($lead['dateUpdated']) ? Carbon::parse($lead['dateUpdated']) : null;
        $contact->city = isset($lead['city']) ? $lead['city'] : '';
        $contact->dateOfBirth = isset($lead['dateOfBirth']) ?  Carbon::createFromTimestamp($lead['dateOfBirth'] / 1000)->toDateString() : null;
        $contact->firstNameLowerCase = isset($lead['firstNameLowerCase']) ? $lead['firstNameLowerCase'] : '';
        $contact->lastNameLowerCase = isset($lead['lastNameLowerCase']) ? $lead['lastNameLowerCase'] : '';
        $contact->email = isset($lead['email']) ? $lead['email'] : '';
        $contact->assignedTo = isset($lead['assignedTo']) ? $lead['assignedTo'] : '';
        $contact->followers = isset($lead['followers']) ? $lead['followers'] : null;
        $contact->validEmail = isset($lead['validEmail']) ? $lead['validEmail'] : false;
        $contact->postalCode = isset($lead['postalCode']) ? $lead['postalCode'] : '';
        $contact->businessId = isset($lead['businessId']) ? $lead['businessId'] : '';
        $contact->save();

        return response()->json(['message' => 'Se ha actualizado correctamente']);
    }
}
