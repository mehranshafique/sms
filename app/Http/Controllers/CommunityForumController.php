<?php

namespace App\Http\Controllers;

use App\Models\CommunityReply;
use App\Models\CommunityThread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommunityForumController extends Controller
{
    public function categories(): array
    {
        return [
            'general' => __('community.cat_general'),
            'finance' => __('community.cat_finance'),
            'payments' => __('community.cat_payments'),
            'attendance' => __('community.cat_attendance'),
            'reports' => __('community.cat_reports'),
            'mobile' => __('community.cat_mobile'),
        ];
    }

    public function index(Request $request)
    {
        $categories = $this->categories();
        $category = $request->get('category');

        $threads = CommunityThread::with(['user', 'institution'])
            ->withCount('replies')
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('community.index', compact('threads', 'categories', 'category'));
    }

    public function show(CommunityThread $thread)
    {
        $thread->increment('views');
        $thread->load(['user', 'institution', 'replies.user']);
        $categories = $this->categories();

        return view('community.show', compact('thread', 'categories'));
    }

    public function create()
    {
        return view('community.create', ['categories' => $this->categories()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'body' => 'required|string|min:20|max:10000',
            'category' => 'required|string|in:' . implode(',', array_keys($this->categories())),
        ]);

        $user = Auth::user();
        $institutionId = session('active_institution_id') ?: $user->institute_id;
        if ($institutionId === 'global') {
            $institutionId = null;
        }

        $thread = CommunityThread::create([
            'user_id' => $user->id,
            'institution_id' => $institutionId,
            'category' => $request->category,
            'title' => $request->title,
            'body' => $request->body,
        ]);

        return redirect()->route('community.show', $thread)->with('success', __('community.thread_created'));
    }

    public function reply(Request $request, CommunityThread $thread)
    {
        if ($thread->is_locked) {
            return back()->with('error', __('community.thread_locked'));
        }

        $request->validate([
            'body' => 'required|string|min:5|max:5000',
        ]);

        CommunityReply::create([
            'community_thread_id' => $thread->id,
            'user_id' => Auth::id(),
            'body' => $request->body,
        ]);

        $thread->touch();

        return back()->with('success', __('community.reply_posted'));
    }
}
