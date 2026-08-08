<?php

namespace App\Providers;

use App\Models\AppSetting;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // A worker keeps one PHP process alive across many jobs, so settings
        // memoised while handling one job would otherwise leak into the next.
        Queue::before(fn () => AppSetting::flushMemo());

        // The admin header cluster renders on pages served by several different
        // controllers, so its unread counts come from here rather than from
        // whichever controller happens to own the page.
        View::composer('admin.partials.workspace-header-actions', function ($view) {
            $user = auth()->user();

            if (! $user?->isStaffOrAdmin()) {
                $view->with(['totalUnreadMessages' => 0, 'adminUnreadNotifications' => 0]);

                return;
            }

            if (! app()->has('_adminHeaderCounts')) {
                app()->instance('_adminHeaderCounts', [
                    // Mirrors AdminController::dashboard() so the badge does not
                    // change value as you move between admin screens.
                    'totalUnreadMessages' => Message::query()
                        ->whereNull('read_at')
                        ->where('sender_id', '!=', $user->id)
                        ->whereHas('conversation.customer', fn ($q) => $q->where('role', 'user'))
                        ->count(),
                    'adminUnreadNotifications' => $user->unreadNotifications()->count(),
                ]);
            }

            $view->with(app('_adminHeaderCounts'));
        });

        $layouts = ['layouts.customer', 'partials.mobile-bottom-customer'];

        // Unread message count
        View::composer($layouts, function ($view) {
            if (!app()->has('_customerUnreadMessages')) {
                $count = 0;
                if ($user = auth()->user()) {
                    $convo = Conversation::where('customer_id', $user->id)->first();
                    if ($convo) {
                        $count = $convo->messages()
                            ->where('sender_id', '!=', $user->id)
                            ->whereNull('read_at')
                            ->count();
                    }
                }
                app()->instance('_customerUnreadMessages', $count);
            }
            $view->with('customerUnreadMessages', app('_customerUnreadMessages'));
        });

        // Unread notification count
        View::composer($layouts, function ($view) {
            if (!app()->has('_customerUnreadNotifications')) {
                $count = 0;
                if ($user = auth()->user()) {
                    $count = $user->unreadNotifications()->count();
                }
                app()->instance('_customerUnreadNotifications', $count);
            }
            $view->with('customerUnreadNotifications', app('_customerUnreadNotifications'));
        });

        // Orders that need customer confirmation after staff uploaded delivery proof.
        View::composer($layouts, function ($view) {
            if (!app()->has('_customerPendingConfirmations')) {
                $count = 0;
                if ($user = auth()->user()) {
                    $count = Order::query()
                        ->where('user_id', $user->id)
                        ->whereNull('archived_at')
                        ->where('status', 'delivered')
                        ->whereNotNull('delivery_proof_url')
                        ->whereNull('customer_confirmed_at')
                        ->count();
                }
                app()->instance('_customerPendingConfirmations', $count);
            }
            $view->with('customerPendingConfirmations', app('_customerPendingConfirmations'));
        });
    }
}
