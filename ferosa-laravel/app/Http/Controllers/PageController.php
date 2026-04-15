<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScheduleRequest;
use App\Mail\AppointmentBooked;
use App\Mail\OrderPlaced;
use App\Models\Appointment;
use App\Models\Order;
use App\Models\Product;
use App\Models\ServiceType;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('home');
    }

    public function shop(Request $request): View
    {
        $q        = trim((string) $request->get('q', ''));
        $category = (string) $request->get('category', 'all');
        $sort     = (string) $request->get('sort', 'name_asc');
        $maxPrice = $request->filled('max_price') ? (float) $request->get('max_price') : null;

        $query = Product::query()
            ->where('is_active', true)
            ->whereNull('archived_at');

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', '%'.$q.'%')
                   ->orWhere('description', 'like', '%'.$q.'%')
                   ->orWhere('category', 'like', '%'.$q.'%');
            });
        }

        if ($category !== 'all' && $category !== '') {
            $query->where('category', $category);
        }

        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        match ($sort) {
            'price_asc'  => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'newest'     => $query->latest(),
            default      => $query->orderBy('name'),
        };

        $products   = $query->get();
        $categories = Product::query()
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('shop', compact('products', 'categories', 'q', 'category', 'sort', 'maxPrice'));
    }

    public function checkout(): View
    {
        return view('checkout');
    }

    public function storeCheckout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cart_data'          => ['required', 'string'],
            'delivery_method'    => ['required', 'string', 'in:delivery,pickup'],
            'delivery_name'      => ['required_if:delivery_method,delivery', 'nullable', 'string', 'max:255'],
            'delivery_phone'     => ['required_if:delivery_method,delivery', 'nullable', 'string', 'max:50'],
            'delivery_address'   => ['required_if:delivery_method,delivery', 'nullable', 'string', 'max:500'],
            'delivery_city'      => ['required_if:delivery_method,delivery', 'nullable', 'string', 'max:255'],
            'delivery_notes'     => ['nullable', 'string', 'max:1000'],
            'payment_method'     => ['required', 'string', 'in:cod,gcash'],
            'payment_reference'  => ['required_if:payment_method,gcash', 'nullable', 'string', 'max:100'],
        ]);

        $cartData = json_decode($data['cart_data'], true);
        if (! $cartData || ! is_array($cartData) || count($cartData) === 0) {
            return back()->withErrors(['Your cart is empty.']);
        }

        // Extract product IDs and look up real prices from the database
        $productIds = collect($cartData)->pluck('id')->filter()->unique()->all();
        $products = Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->get()
            ->keyBy('id');

        $lineItems = [];
        $totalPrice = 0;

        foreach ($cartData as $item) {
            $productId = $item['id'] ?? null;
            $product = $products->get($productId);

            if (! $product) {
                return back()->withErrors(["Product \"{$item['name']}\" is no longer available."]);
            }

            $qty = max(1, (int) ($item['qty'] ?? 1));

            if ($product->stock_qty < $qty) {
                $available = $product->stock_qty;
                return back()->withErrors([
                    $available === 0
                        ? "\"{$product->name}\" is out of stock."
                        : "\"{$product->name}\" only has {$available} unit(s) left.",
                ]);
            }

            $price = (float) $product->price;

            $lineItems[] = [
                'product_id' => $product->id,
                'name'       => $product->name,
                'price'      => $price,
                'qty'        => $qty,
            ];
            $totalPrice += $price * $qty;
        }

        $orderNumber = 'FRS-'.strtoupper(substr(uniqid(), -5)).rand(10, 99);

        $order = DB::transaction(function () use ($lineItems, $totalPrice, $orderNumber, $data) {
            // Decrement stock inside the transaction
            foreach ($lineItems as $item) {
                Product::where('id', $item['product_id'])
                    ->where('stock_qty', '>=', $item['qty'])
                    ->decrement('stock_qty', $item['qty']);
            }
            Cache::forget('shop_products_active');

            $order = Order::create([
                'user_id'            => auth()->id(),
                'order_number'       => $orderNumber,
                'status'             => 'pending',
                'total_amount'       => $totalPrice,
                'items'              => $lineItems,
                'delivery_method'    => $data['delivery_method'],
                'delivery_name'      => $data['delivery_name'] ?? null,
                'delivery_phone'     => $data['delivery_phone'] ?? null,
                'delivery_address'   => $data['delivery_address'] ?? null,
                'delivery_city'      => $data['delivery_city'] ?? null,
                'delivery_notes'     => $data['delivery_notes'] ?? null,
                'payment_method'     => $data['payment_method'],
                'payment_reference'  => $data['payment_reference'] ?? null,
            ]);

            foreach ($lineItems as $item) {
                $order->orderItems()->create([
                    'product_id' => $item['product_id'],
                    'name'       => $item['name'],
                    'price'      => $item['price'],
                    'qty'        => $item['qty'],
                ]);
            }

            return $order;
        });

        $order->load('user');

        try {
            Mail::to($order->user->email)->send(new OrderPlaced($order));
        } catch (\Throwable $e) {
            report($e);
        }

        // After transaction: check for low-stock and alert admins
        $lowStock = Product::whereIn('id', collect($lineItems)->pluck('product_id'))
            ->where('stock_qty', '<=', 5)
            ->get();
        if ($lowStock->isNotEmpty()) {
            $adminEmails = \App\Models\User::where('role', 'admin')->pluck('email');
            foreach ($adminEmails as $email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\LowStockAlert($lowStock));
                } catch (\Throwable $e) { report($e); }
            }
        }

        return redirect()->route('orders.confirmation', $order)
            ->with('clear_cart', true);
    }

    public function orderConfirmation(Order $order): View
    {
        abort_unless((int) $order->user_id === (int) auth()->id(), 403);
        $order->load(['user', 'orderItems']);

        return view('order-confirmation', [
            'order' => $order,
        ]);
    }

    public function orders(): View
    {
        $data = request()->validate([
            'status' => ['nullable', 'string', 'in:pending,confirmed,out_for_delivery,delivered,cancelled'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $q = Order::query()
            ->where('user_id', auth()->id())
            ->whereNull('archived_at')
            ->latest();

        if (! empty($data['status'])) {
            $q->where('status', $data['status']);
        }

        if (! empty($data['from'])) {
            $q->whereDate('created_at', '>=', $data['from']);
        }

        if (! empty($data['to'])) {
            $q->whereDate('created_at', '<=', $data['to']);
        }

        return view('orders', [
            'orders' => $q->paginate(8)->withQueryString(),
        ]);
    }

    public function trackOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:50'],
        ]);

        $order = Order::query()
            ->where('user_id', auth()->id())
            ->whereNull('archived_at')
            ->where('order_number', $data['order_number'])
            ->first();

        if (! $order) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'created_at' => optional($order->created_at)->format('M d, Y \u00b7 h:i A'),
        ]);
    }

    public function schedule(): View
    {
        return view('schedule', [
            'serviceTypes' => ServiceType::query()
                ->where('is_active', true)
                ->whereNull('archived_at')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function scheduleAvailability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_type_id' => ['required', 'integer', 'exists:service_types,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $dayStart = Carbon::createFromFormat('Y-m-d', $data['date'])->startOfDay();
        $dayEnd = (clone $dayStart)->endOfDay();

        $booked = Appointment::query()
            ->where('service_type_id', $data['service_type_id'])
            ->whereBetween('appointment_at', [$dayStart, $dayEnd])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->pluck('appointment_at')
            ->map(fn ($dt) => Carbon::parse($dt)->format('H:i'))
            ->values()
            ->all();

        return response()->json([
            'date' => $data['date'],
            'service_type_id' => (int) $data['service_type_id'],
            'booked_times' => $booked,
        ]);
    }

    public function storeSchedule(StoreScheduleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $serviceTypeId = $data['service_type_id'] ?? null;
        if (! $serviceTypeId) {
            $serviceTypeId = ServiceType::query()
                ->where('name', $data['service_name'] ?? '')
                ->value('id');
        }

        abort_unless($serviceTypeId, 422, 'Invalid service type.');

        // Prevent double booking (server-side)
        $appointmentAt = Carbon::parse($data['appointment_at'])->seconds(0);
        $alreadyBooked = Appointment::query()
            ->where('service_type_id', $serviceTypeId)
            ->where('appointment_at', $appointmentAt)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->exists();

        if ($alreadyBooked) {
            return back()->withErrors([
                'appointment_at' => 'That time slot is already booked. Please choose another time.',
            ]);
        }

        $appointment = Appointment::create([
            'user_id' => auth()->id(),
            'service_type_id' => $serviceTypeId,
            'appointment_at' => $appointmentAt,
            'status' => 'scheduled',
            'notes' => $data['notes'] ?? null,
        ]);

        $appointment->load(['user', 'serviceType']);

        try {
            Mail::to($appointment->user->email)->send(new AppointmentBooked($appointment));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('status', 'Booking confirmed successfully.');
    }

    public function orderReceipt(Order $order): View
    {
        abort_unless((int) $order->user_id === (int) auth()->id(), 403);
        $order->load(['user', 'orderItems']);

        return view('order-receipt', compact('order'));
    }

    public function estimator(): View
    {
        return view('estimator');
    }

    public function account(): View
    {
        $user = auth()->user();

        $appointments = Appointment::query()
            ->with('serviceType')
            ->where('user_id', $user->id)
            ->whereNull('archived_at')
            ->orderByDesc('appointment_at')
            ->limit(10)
            ->get();

        return view('account', compact('user', 'appointments'));
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return back()->with('status', 'Profile updated successfully.');
    }
}
