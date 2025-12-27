<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    /**
     * Get all countries
     */
    public function countries()
    {
        // Assuming you have a 'countries' table
        $countries = DB::table('countries')->orderBy('name')->select('id', 'name')->get();
        return response()->json($countries);
    }

    /**
     * Get states by country_id
     */
    public function states(Request $request)
    {
        $countryId = $request->country_id;
        
        // If your table is named 'states' or 'provinces'
        $states = DB::table('states')
            ->where('country_id', $countryId)
            ->orderBy('name')
            ->select('id', 'name')
            ->get();
            
        return response()->json($states);
    }

    /**
     * Get cities by state_id
     */
    public function cities(Request $request)
    {
        $stateId = $request->state_id;
        
        $cities = DB::table('cities')
            ->where('state_id', $stateId)
            ->orderBy('name')
            ->select('id', 'name')
            ->get();
            
        return response()->json($cities);
    }
}