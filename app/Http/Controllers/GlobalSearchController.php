<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GlobalSearchController extends BaseController
{
    public function __construct(private GlobalSearchService $searchService)
    {
        $this->middleware('auth');
    }

    public function suggest(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:100',
        ]);

        $results = $this->searchService->search(
            Auth::user(),
            $validated['q'] ?? '',
            $this->getInstitutionId()
        );

        return response()->json(['results' => $results]);
    }
}
