<?php

namespace App\Http\Controllers;

use App\Services\HelpCenterService;
use Illuminate\Http\Request;

class HelpCenterController extends Controller
{
    public function __construct(
        protected HelpCenterService $helpCenter
    ) {}

    public function index(Request $request)
    {
        $query = $request->get('q', '');
        $categories = $this->helpCenter->categories();
        $results = $query ? $this->helpCenter->search($query) : [];

        return view('help.index', compact('categories', 'query', 'results'));
    }

    public function show(string $slug)
    {
        $article = $this->helpCenter->renderArticle($slug);

        if (!$article) {
            abort(404);
        }

        $categories = $this->helpCenter->categories();

        return view('help.show', compact('article', 'categories', 'slug'));
    }
}
