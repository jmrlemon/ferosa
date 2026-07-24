<?php

namespace App\Http\Controllers;

use App\Jobs\SendSmsJob;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isStaffOrAdmin()) {
            return redirect()->route('admin.dashboard', ['tab' => 'messages']);
        }

        $conversation = Conversation::with(['messages' => fn ($q) => $q->with('sender:id,name,role')->oldest()])
            ->firstOrCreate(
                ['customer_id' => $user->id],
                ['last_message_at' => now()]
            );

        // Mark all admin messages as read
        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('messages', compact('conversation'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $user = auth()->user();
        abort_if($user->isStaffOrAdmin(), 403, 'Staff accounts must reply from the admin inbox.');

        $conversation = Conversation::firstOrCreate(
            ['customer_id' => $user->id],
            ['last_message_at' => now()]
        );

        $conversation->messages()->create([
            'sender_id' => $user->id,
            'body'      => trim($data['body']),
        ]);

        $conversation->update(['last_message_at' => now()]);

        // SMS admin if this is their first unread message in this conversation
        $unreadCount = $conversation->messages()
            ->where('sender_id', $user->id)
            ->whereNull('read_at')
            ->count();

        if ($unreadCount === 1) {
            $admin = User::where('role', 'admin')->whereNotNull('phone_number')->first();
            if ($admin) {
                SendSmsJob::dispatch(
                    $admin->phone_number,
                    "Ferosa: {$user->name} sent you a new message. Reply on the admin dashboard."
                );
            }
        }

        return back();
    }

    // JSON polling endpoint — returns messages created after a given timestamp
    public function poll(Request $request): JsonResponse
    {
        $user = auth()->user();
        abort_if($user->isStaffOrAdmin(), 403, 'Staff accounts must use the admin inbox.');

        $after = $request->query('after'); // ISO timestamp

        $conversation = Conversation::where('customer_id', $user->id)->first();
        if (! $conversation) {
            return response()->json(['messages' => []]);
        }

        $messages = $conversation->messages()
            ->with('sender:id,name,role')
            ->when($after, fn ($q) => $q->where('created_at', '>', $after))
            ->oldest()
            ->get()
            ->map(fn ($m) => [
                'id'         => $m->id,
                'body'       => $m->body,
                'created_at' => $m->created_at->toISOString(),
                'is_mine'    => $m->sender_id === $user->id,
                'sender'     => $m->sender->name,
            ]);

        // Mark newly-fetched admin messages as read
        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['messages' => $messages]);
    }
}
