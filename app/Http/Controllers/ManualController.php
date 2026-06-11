<?php

namespace App\Http\Controllers;

use App\Services\HelpCenterService;
use App\Services\UserManualService;
use Illuminate\Http\Request;

class ManualController extends Controller
{
    public function __construct(
        protected UserManualService $manual,
        protected HelpCenterService $helpCenter
    ) {}

    public function hub(Request $request)
    {
        $query = $request->get('q', '');
        $manualResults = $query ? $this->manual->search($query) : [];
        $helpResults = $query ? $this->helpCenter->search($query) : [];
        $web = $this->manual->parseWebManual();
        $mobile = $this->manual->parseMobileManual();

        return view('manual.hub', [
            'query' => $query,
            'manualResults' => $manualResults,
            'helpResults' => $helpResults,
            'webParts' => $web['parts'],
            'mobileParts' => $mobile['parts'],
            'webModuleCount' => $this->manual->webModuleCount(),
            'mobilePartCount' => $this->manual->mobilePartCount(),
            'categories' => $this->helpCenter->categories(),
            'webFallback' => $this->manual->isWebFallback(),
            'mobileFallback' => $this->manual->isMobileFallback(),
        ]);
    }

    public function webIndex()
    {
        $parsed = $this->manual->parseWebManual();

        return view('manual.web-index', [
            'introduction' => $parsed['introduction'],
            'parts' => $parsed['parts'],
            'moduleCount' => count($parsed['modules']),
            'contentFallback' => $this->manual->isWebFallback(),
        ]);
    }

    public function webShow(string $slug)
    {
        $page = $this->manual->renderWebModule($slug);
        if (!$page) {
            abort(404);
        }

        return view('manual.show', [
            'page' => $page,
            'manualType' => 'web',
            'toc' => $this->manual->parseWebManual(),
            'contentFallback' => $this->manual->isWebFallback(),
        ]);
    }

    public function mobileIndex()
    {
        $parsed = $this->manual->parseMobileManual();

        return view('manual.mobile-index', [
            'introduction' => $parsed['introduction'],
            'parts' => $parsed['parts'],
            'contentFallback' => $this->manual->isMobileFallback(),
        ]);
    }

    public function mobileShow(string $slug)
    {
        $page = $this->manual->renderMobileSection($slug);
        if (!$page) {
            abort(404);
        }

        return view('manual.show', [
            'page' => $page,
            'manualType' => 'mobile',
            'toc' => $this->manual->parseMobileManual(),
            'contentFallback' => $this->manual->isMobileFallback(),
        ]);
    }
}
