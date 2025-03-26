<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Opportunity;

class OpportunityController extends Controller
{
    public function get()
    {
        $opportunity = new Opportunity();
        return $opportunity->Opportunities();
    }
}