<?php

namespace App\Http\Controllers;

use App\Jobs\TestCronHeartbeatJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;

class QueueMonitorController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(RoleMiddleware::class . ':Super Admin');
        $this->setPageTitle(__('queue_monitor.page_title'));
    }

    public function index()
    {
        $failedJobs = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->orderByDesc('failed_at')->limit(50)->get()
            : collect();

        $pendingJobs = Schema::hasTable('jobs')
            ? DB::table('jobs')->count()
            : 0;

        $lastHeartbeat = Cache::get('queue.cron_test_heartbeat');

        return view('platform.queue_monitor', compact('failedJobs', 'pendingJobs', 'lastHeartbeat'));
    }

    public function retry(Request $request, string $id)
    {
        if (!Schema::hasTable('failed_jobs')) {
            return back()->with('error', __('queue_monitor.no_failed_table'));
        }

        Artisan::call('queue:retry', ['id' => $id]);

        return back()->with('success', __('queue_monitor.retry_dispatched'));
    }

    public function retryAll()
    {
        if (!Schema::hasTable('failed_jobs')) {
            return back()->with('error', __('queue_monitor.no_failed_table'));
        }

        Artisan::call('queue:retry', ['id' => 'all']);

        return back()->with('success', __('queue_monitor.retry_all_dispatched'));
    }

    public function dispatchTest()
    {
        TestCronHeartbeatJob::dispatch();

        return back()->with('success', __('queue_monitor.test_job_dispatched'));
    }
}
