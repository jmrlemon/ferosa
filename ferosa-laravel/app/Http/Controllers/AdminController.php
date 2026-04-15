<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentStatusUpdated;
use App\Mail\OrderStatusUpdated;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Feedback;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function archiveOrder(Request $request, Order $order): RedirectResponse
    {
        $before = Audit::snapshot($order, ['status', 'total_amount', 'archived_at']);
        $order->update(['archived_at' => now()]);

        $order->refresh();
        Audit::log($request, 'order.archive', $order, $before, Audit::snapshot($order, ['status', 'total_amount', 'archived_at']));

        return redirect()->route('admin.dashboard', ['tab' => 'orders'])
            ->with('status', "Order {$order->order_number} archived.");
    }

    public function restoreOrder(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->archived_at !== null, 404);

        $before = Audit::snapshot($order, ['status', 'total_amount', 'archived_at']);
        $order->update(['archived_at' => null]);

        $order->refresh();
        Audit::log($request, 'order.restore', $order, $before, Audit::snapshot($order, ['status', 'total_amount', 'archived_at']));

        return redirect()->route('admin.dashboard', ['tab' => 'archived'])
            ->with('status', "Order {$order->order_number} restored.");
    }

    public function archiveAppointment(Request $request, Appointment $appointment): RedirectResponse
    {
        $before = Audit::snapshot($appointment, ['status', 'appointment_at', 'archived_at']);
        $appointment->update(['archived_at' => now()]);

        $appointment->refresh();
        Audit::log($request, 'appointment.archive', $appointment, $before, Audit::snapshot($appointment, ['status', 'appointment_at', 'archived_at']));

        return redirect()->route('admin.dashboard', ['tab' => 'appointments'])
            ->with('status', 'Appointment archived.');
    }

    public function restoreAppointment(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->archived_at !== null, 404);

        $before = Audit::snapshot($appointment, ['status', 'appointment_at', 'archived_at']);
        $appointment->update(['archived_at' => null]);

        $appointment->refresh();
        Audit::log($request, 'appointment.restore', $appointment, $before, Audit::snapshot($appointment, ['status', 'appointment_at', 'archived_at']));

        return redirect()->route('admin.dashboard', ['tab' => 'archived'])
            ->with('status', 'Appointment restored.');
    }
    private const ORDER_STATUSES = ['pending', 'confirmed', 'out_for_delivery', 'delivered', 'cancelled'];

    public function dashboard(): View
    {
        $range = request()->validate([
            'sales_from' => ['nullable', 'date'],
            'sales_to' => ['nullable', 'date', 'after_or_equal:sales_from'],
        ]);

        $salesFrom = $range['sales_from'] ?? null;
        $salesTo = $range['sales_to'] ?? null;

        $ordersBase = Order::query()
            ->when($salesFrom, fn (Builder $q) => $q->whereDate('created_at', '>=', $salesFrom))
            ->when($salesTo, fn (Builder $q) => $q->whereDate('created_at', '<=', $salesTo));

        $totalSales = (float) (clone $ordersBase)->sum('total_amount');
        $totalOrders = (clone $ordersBase)->count();
        $deliveredOrders = (clone $ordersBase)->where('status', 'delivered')->count();
        $pendingOrders = (clone $ordersBase)->whereIn('status', ['pending', 'confirmed', 'out_for_delivery'])->count();

        $salesByStatus = (clone $ordersBase)
            ->selectRaw('status, COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as sales_total')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $adminOrders = $this->filteredOrdersQuery()->take(200)->get();

        $monthlySalesRows = Order::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COALESCE(SUM(total_amount), 0) as total")
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(5))
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $monthlySales = collect(range(5, 0, -1))
            ->push(0)
            ->map(function (int $monthsAgo) use ($monthlySalesRows) {
                $dt = now()->startOfMonth()->subMonths($monthsAgo);
                $ym = $dt->format('Y-m');

                return [
                    'label' => $dt->format('M Y'),
                    'total' => (float) ($monthlySalesRows[$ym]->total ?? 0),
                ];
            });

        $productQ = trim((string) request('product_q', ''));
        $products = Product::query()
            ->whereNull('archived_at')
            ->when($productQ !== '', function (Builder $q) use ($productQ) {
                $q->where(function (Builder $qq) use ($productQ) {
                    $qq->where('name', 'like', '%'.$productQ.'%')
                        ->orWhere('category', 'like', '%'.$productQ.'%');
                });
            })
            ->orderBy('name')
            ->paginate(10, ['*'], 'products_page')
            ->withQueryString();

        $archivedProducts = Product::query()
            ->whereNotNull('archived_at')
            ->orderByDesc('archived_at')
            ->take(500)
            ->get();

        $lowStockProducts = Product::query()
            ->whereNull('archived_at')
            ->where('stock_qty', '<=', 5)
            ->where('is_active', true)
            ->orderBy('stock_qty', 'asc')
            ->get();

        $pendingAppointments = Appointment::query()
            ->whereNull('archived_at')
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->count();
        $totalUsers = User::query()->count();

        $apptQ = trim((string) request('appt_q', ''));
        $appointments = Appointment::query()
            ->with(['user', 'serviceType'])
            ->whereNull('archived_at')
            ->when($apptQ !== '', function (Builder $q) use ($apptQ) {
                $q->where(function (Builder $qq) use ($apptQ) {
                    $qq->where('notes', 'like', '%'.$apptQ.'%')
                        ->orWhereHas('user', function (Builder $u) use ($apptQ) {
                            $u->where('email', 'like', '%'.$apptQ.'%')
                                ->orWhere('name', 'like', '%'.$apptQ.'%');
                        })
                        ->orWhereHas('serviceType', function (Builder $s) use ($apptQ) {
                            $s->where('name', 'like', '%'.$apptQ.'%');
                        });
                });
            })
            ->latest()
            ->paginate(10, ['*'], 'appts_page')
            ->withQueryString();

        $serviceQ = trim((string) request('service_q', ''));
        $services = ServiceType::query()
            ->whereNull('archived_at')
            ->when($serviceQ !== '', function (Builder $q) use ($serviceQ) {
                $q->where('name', 'like', '%'.$serviceQ.'%');
            })
            ->orderBy('name')
            ->paginate(10, ['*'], 'services_page')
            ->withQueryString();

        $archivedServices = ServiceType::query()
            ->whereNotNull('archived_at')
            ->orderByDesc('archived_at')
            ->take(500)
            ->get();
        $users = User::query()->orderBy('name')->get();

        $archivedOrders = Order::query()
            ->whereNotNull('archived_at')
            ->with('user:id,name,email')
            ->orderByDesc('archived_at')
            ->paginate(10, ['*'], 'arch_orders_page')
            ->withQueryString();

        $archivedAppointments = Appointment::query()
            ->whereNotNull('archived_at')
            ->with(['user:id,name,email', 'serviceType:id,name'])
            ->orderByDesc('archived_at')
            ->paginate(10, ['*'], 'arch_appts_page')
            ->withQueryString();

        $thisWeekStart = now()->startOfWeek();
        $thisWeekEnd = now();
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd = now()->subWeek()->endOfWeek();

        $thisWeekSales = (float) Order::query()->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])->sum('total_amount');
        $lastWeekSales = (float) Order::query()->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->sum('total_amount');
        $weekSalesDeltaPct = $lastWeekSales > 0
            ? round((($thisWeekSales - $lastWeekSales) / $lastWeekSales) * 100, 1)
            : null;

        $topProducts = $this->topSellingProducts();

        $auditQ = trim((string) request('audit_q', ''));
        $auditLogs = AuditLog::query()
            ->with('actor:id,name,email')
            ->when($auditQ !== '', function (Builder $q) use ($auditQ) {
                $q->where(function (Builder $qq) use ($auditQ) {
                    $qq->where('action', 'like', '%'.$auditQ.'%')
                        ->orWhere('auditable_type', 'like', '%'.$auditQ.'%')
                        ->orWhere('auditable_id', 'like', '%'.$auditQ.'%')
                        ->orWhereHas('actor', function (Builder $u) use ($auditQ) {
                            $u->where('email', 'like', '%'.$auditQ.'%')
                                ->orWhere('name', 'like', '%'.$auditQ.'%');
                        });
                });
            })
            ->latest('id')
            ->paginate(15, ['*'], 'audit_page')
            ->withQueryString();

        $feedbackQ = trim((string) request('feedback_q', ''));
        $feedbacks = Feedback::query()
            ->with(['user:id,name,email', 'product:id,name', 'serviceType:id,name'])
            ->when($feedbackQ !== '', function (Builder $q) use ($feedbackQ) {
                $q->where(function (Builder $qq) use ($feedbackQ) {
                    $qq->where('comment', 'like', '%'.$feedbackQ.'%')
                        ->orWhereHas('user', function (Builder $u) use ($feedbackQ) {
                            $u->where('name', 'like', '%'.$feedbackQ.'%')
                              ->orWhere('email', 'like', '%'.$feedbackQ.'%');
                        });
                });
            })
            ->latest()
            ->paginate(15, ['*'], 'fb_page')
            ->withQueryString();

        $avgRating = Feedback::query()->avg('rating');

        return view('admin.dashboard', compact(
            'totalSales',
            'totalOrders',
            'deliveredOrders',
            'pendingOrders',
            'salesByStatus',
            'adminOrders',
            'products',
            'archivedProducts',
            'monthlySales',
            'pendingAppointments',
            'totalUsers',
            'appointments',
            'services',
            'archivedServices',
            'users',
            'thisWeekSales',
            'lastWeekSales',
            'weekSalesDeltaPct',
            'topProducts',
            'productQ',
            'serviceQ',
            'apptQ',
            'auditLogs',
            'auditQ',
            'salesFrom',
            'salesTo',
            'archivedOrders',
            'archivedAppointments',
            'feedbacks',
            'feedbackQ',
            'avgRating',
            'lowStockProducts'
        ));
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'name')->whereNull('archived_at'),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'image_url'   => ['nullable', 'url', 'max:2048'],
            'price'       => ['required', 'numeric', 'min:0'],
            'stock_qty'   => ['nullable', 'integer', 'min:0'],
            'category'    => ['required', 'string', 'max:100'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $product = Product::query()->create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? '',
            'image_url'   => $data['image_url'] ?? null,
            'price'       => $data['price'],
            'stock_qty'   => (int) ($data['stock_qty'] ?? 0),
            'category'    => strtolower(trim($data['category'])),
            'is_active'   => (bool) ($data['is_active'] ?? false),
            'archived_at' => null,
        ]);

        Audit::log(
            $request,
            'product.create',
            $product,
            null,
            Audit::snapshot($product, ['name', 'description', 'image_url', 'price', 'stock_qty', 'category', 'is_active', 'archived_at'])
        );

        Cache::forget('shop_products_active');

        return redirect()->route('admin.dashboard', ['tab' => 'products'])
            ->with('status', 'Product added successfully.');
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->archived_at === null, 404);

        $before = Audit::snapshot($product, ['name', 'description', 'image_url', 'price', 'stock_qty', 'category', 'is_active', 'archived_at']);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'name')
                    ->ignore($product->id)
                    ->whereNull('archived_at'),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'image_url'   => ['nullable', 'url', 'max:2048'],
            'price'       => ['required', 'numeric', 'min:0'],
            'stock_qty'   => ['nullable', 'integer', 'min:0'],
            'category'    => ['required', 'string', 'max:100'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $product->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? '',
            'image_url'   => $data['image_url'] ?? null,
            'price'       => $data['price'],
            'stock_qty'   => (int) ($data['stock_qty'] ?? 0),
            'category'    => strtolower(trim($data['category'])),
            'is_active'   => (bool) ($data['is_active'] ?? false),
        ]);

        Cache::forget('shop_products_active');

        $product->refresh();
        Audit::log(
            $request,
            'product.update',
            $product,
            $before,
            Audit::snapshot($product, ['name', 'description', 'image_url', 'price', 'stock_qty', 'category', 'is_active', 'archived_at'])
        );

        return redirect()->route('admin.dashboard', ['tab' => 'products'])
            ->with('status', 'Product updated successfully.');
    }

    public function deleteProduct(Request $request, Product $product): RedirectResponse
    {
        $before = Audit::snapshot($product, ['name', 'description', 'image_url', 'price', 'stock_qty', 'category', 'is_active', 'archived_at']);
        $product->update(['archived_at' => now()]);

        Cache::forget('shop_products_active');

        $product->refresh();
        Audit::log(
            $request,
            'product.archive',
            $product,
            $before,
            Audit::snapshot($product, ['name', 'description', 'image_url', 'price', 'stock_qty', 'category', 'is_active', 'archived_at'])
        );

        return redirect()->route('admin.dashboard', ['tab' => 'products'])
            ->with('status', 'Product archived successfully.');
    }

    public function restoreProduct(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->archived_at !== null, 404);

        $before = Audit::snapshot($product, ['name', 'description', 'image_url', 'price', 'stock_qty', 'category', 'is_active', 'archived_at']);
        $product->update(['archived_at' => null]);

        Cache::forget('shop_products_active');

        $product->refresh();
        Audit::log(
            $request,
            'product.restore',
            $product,
            $before,
            Audit::snapshot($product, ['name', 'description', 'image_url', 'price', 'stock_qty', 'category', 'is_active', 'archived_at'])
        );

        return redirect()->route('admin.dashboard', ['tab' => 'archived'])
            ->with('status', 'Product restored successfully.');
    }

    public function updateOrderStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', self::ORDER_STATUSES)],
            'payment_status' => ['required', 'string', 'in:unpaid,paid'],
        ]);

        $order->update([
            'status' => $data['status'],
            'payment_status' => $data['payment_status'],
        ]);
        $order->load('user');

        try {
            Mail::to($order->user->email)->send(new OrderStatusUpdated($order));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('admin.dashboard', ['tab' => 'orders'])
            ->with('status', "Order {$order->order_number} updated to ".str_replace('_', ' ', $data['status']).'.');
    }

    public function bulkOrderStatus(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
            'status' => ['required', 'string', 'in:'.implode(',', self::ORDER_STATUSES)],
        ]);

        $count = Order::query()->whereIn('id', $data['order_ids'])->update([
            'status' => $data['status'],
        ]);

        return redirect()->route('admin.dashboard', ['tab' => 'orders'])
            ->with('status', "Updated {$count} order(s) to ".str_replace('_', ' ', $data['status']).'.');
    }

    public function exportOrdersCsv(): StreamedResponse
    {
        $filename = 'orders-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Order #', 'Customer', 'Email', 'Amount', 'Status', 'Created']);

            foreach ($this->filteredOrdersQuery()->cursor() as $order) {
                fputcsv($out, [
                    $order->order_number,
                    $order->user->name ?? '',
                    $order->user->email ?? '',
                    $order->total_amount,
                    $order->status,
                    $order->created_at?->toAtomString() ?? '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function storeService(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_types', 'name')->whereNull('archived_at'),
            ],
            'default_fee' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $serviceType = ServiceType::query()->create([
            'name' => $data['name'],
            'default_fee' => $data['default_fee'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'archived_at' => null,
        ]);

        Audit::log(
            $request,
            'service.create',
            $serviceType,
            null,
            Audit::snapshot($serviceType, ['name', 'default_fee', 'is_active', 'archived_at'])
        );

        return redirect()->route('admin.dashboard', ['tab' => 'services'])
            ->with('status', 'Service added successfully.');
    }

    public function updateService(Request $request, ServiceType $serviceType): RedirectResponse
    {
        abort_unless($serviceType->archived_at === null, 404);

        $before = Audit::snapshot($serviceType, ['name', 'default_fee', 'is_active', 'archived_at']);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_types', 'name')
                    ->ignore($serviceType->id)
                    ->whereNull('archived_at'),
            ],
            'default_fee' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $serviceType->update([
            'name' => $data['name'],
            'default_fee' => $data['default_fee'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        $serviceType->refresh();
        Audit::log(
            $request,
            'service.update',
            $serviceType,
            $before,
            Audit::snapshot($serviceType, ['name', 'default_fee', 'is_active', 'archived_at'])
        );

        return redirect()->route('admin.dashboard', ['tab' => 'services'])
            ->with('status', 'Service updated successfully.');
    }

    public function deleteService(Request $request, ServiceType $serviceType): RedirectResponse
    {
        $before = Audit::snapshot($serviceType, ['name', 'default_fee', 'is_active', 'archived_at']);
        $serviceType->update(['archived_at' => now()]);

        $serviceType->refresh();
        Audit::log(
            $request,
            'service.archive',
            $serviceType,
            $before,
            Audit::snapshot($serviceType, ['name', 'default_fee', 'is_active', 'archived_at'])
        );

        return redirect()->route('admin.dashboard', ['tab' => 'services'])
            ->with('status', 'Service archived successfully.');
    }

    public function restoreService(Request $request, ServiceType $serviceType): RedirectResponse
    {
        abort_unless($serviceType->archived_at !== null, 404);

        $before = Audit::snapshot($serviceType, ['name', 'default_fee', 'is_active', 'archived_at']);
        $serviceType->update(['archived_at' => null]);

        $serviceType->refresh();
        Audit::log(
            $request,
            'service.restore',
            $serviceType,
            $before,
            Audit::snapshot($serviceType, ['name', 'default_fee', 'is_active', 'archived_at'])
        );

        return redirect()->route('admin.dashboard', ['tab' => 'archived'])
            ->with('status', 'Service restored successfully.');
    }

    public function archived(): View
    {
        $archivedProducts = Product::query()
            ->whereNotNull('archived_at')
            ->orderByDesc('archived_at')
            ->take(500)
            ->get();

        $archivedServices = ServiceType::query()
            ->whereNotNull('archived_at')
            ->orderByDesc('archived_at')
            ->take(500)
            ->get();

        return view('admin.archived', [
            'archivedProducts' => $archivedProducts,
            'archivedServices' => $archivedServices,
        ]);
    }

    public function updateAppointmentStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:scheduled,confirmed,completed,cancelled'],
            'payment_status' => ['required', 'string', 'in:unpaid,paid'],
        ]);

        $appointment->update([
            'status' => $data['status'],
            'payment_status' => $data['payment_status'],
        ]);
        $appointment->load(['user', 'serviceType']);

        try {
            Mail::to($appointment->user->email)->send(new AppointmentStatusUpdated($appointment));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('admin.dashboard', ['tab' => 'appointments'])
            ->with('status', 'Appointment updated to '.ucfirst($data['status']).'.');
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'string', 'in:admin,staff,user'],
        ]);

        if ((int) $user->id === (int) $request->user()->id) {
            return redirect()->route('admin.dashboard', ['tab' => 'users'])
                ->with('status', 'You cannot change your own role.');
        }

        $user->update([
            'role' => $data['role'],
        ]);

        return redirect()->route('admin.dashboard', ['tab' => 'users'])
            ->with('status', "User {$user->name} role updated to ".ucfirst($data['role']).'.');
    }

    private function filteredOrdersQuery(): Builder
    {
        $ordersQuery = Order::query()
            ->with('user:id,name,email')
            ->whereNull('archived_at')
            ->latest();

        if ($status = request('order_status')) {
            $ordersQuery->where('status', $status);
        }

        if ($q = trim((string) request('order_q', ''))) {
            $ordersQuery->where(function ($qry) use ($q) {
                $qry->where('order_number', 'like', '%'.$q.'%')
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('email', 'like', '%'.$q.'%')
                            ->orWhere('name', 'like', '%'.$q.'%');
                    });
            });
        }

        return $ordersQuery;
    }

    private function topSellingProducts(): Collection
    {
        try {
            return OrderItem::query()
                ->selectRaw('name, SUM(qty) as total_qty')
                ->groupBy('name')
                ->orderByDesc('total_qty')
                ->limit(10)
                ->get()
                ->map(fn ($row) => ['name' => $row->name, 'qty' => (int) $row->total_qty])
                ->values();
        } catch (\Throwable) {
            return collect();
        }
    }
}
