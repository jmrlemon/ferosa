<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Order;
use App\Models\Product;
use App\Models\ServiceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $myFeedbacks = Feedback::query()
            ->with(['product', 'serviceType', 'order'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        // Delivered orders that don't have feedback yet
        $deliveredOrders = Order::query()
            ->where('user_id', $user->id)
            ->where('status', 'delivered')
            ->whereDoesntHave('feedback')
            ->orderByDesc('created_at')
            ->get();

        $products = Product::query()
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $services = ServiceType::query()
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('feedback', compact('myFeedbacks', 'products', 'services', 'deliveredOrders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rating'          => ['required', 'integer', 'min:1', 'max:5'],
            'comment'         => ['nullable', 'string', 'max:1000'],
            'product_id'      => ['nullable', 'exists:products,id'],
            'service_type_id' => ['nullable', 'exists:service_types,id'],
            'order_id'        => ['nullable', 'exists:orders,id'],
        ]);

        // If tied to an order, verify it belongs to the user and is delivered
        if (!empty($data['order_id'])) {
            $order = Order::where('id', $data['order_id'])
                ->where('user_id', auth()->id())
                ->where('status', 'delivered')
                ->firstOrFail();

            // Prevent duplicate feedback for same order
            if ($order->feedback()->exists()) {
                return redirect()->route('feedback')
                    ->with('status', 'You have already submitted feedback for this order.');
            }
        }

        Feedback::create([
            'user_id'         => auth()->id(),
            'order_id'        => $data['order_id'] ?? null,
            'rating'          => $data['rating'],
            'comment'         => $data['comment'] ?? null,
            'product_id'      => $data['product_id'] ?? null,
            'service_type_id' => $data['service_type_id'] ?? null,
        ]);

        return redirect()->route('feedback')->with('status', 'Thank you for your feedback!');
    }
}
