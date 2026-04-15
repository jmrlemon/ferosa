<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PasswordResetOtpController;
use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.submit');

    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

    Route::post('/forgot-password/send-otp', [PasswordResetOtpController::class, 'sendOtp'])->middleware('throttle:10,1')->name('forgot.send-otp');
    Route::post('/forgot-password/verify-otp', [PasswordResetOtpController::class, 'verifyOtp'])->middleware('throttle:10,1')->name('forgot.verify-otp');
    Route::post('/forgot-password/reset', [PasswordResetOtpController::class, 'resetPassword'])->middleware('throttle:10,1')->name('forgot.reset');

    // Legacy URLs (keep your original exact UI links working)
    Route::get('/ferosa-auth.html', [AuthController::class, 'showLogin'])->name('legacy.login');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/home', [PageController::class, 'home'])->name('home');
    Route::get('/shop', [PageController::class, 'shop'])->name('shop');
    Route::get('/checkout', [PageController::class, 'checkout'])->name('checkout');
    Route::post('/checkout', [PageController::class, 'storeCheckout'])->name('checkout.store');
    Route::get('/orders/confirmation/{order}', [PageController::class, 'orderConfirmation'])->name('orders.confirmation');
    Route::get('/orders/{order}/receipt', [PageController::class, 'orderReceipt'])->name('orders.receipt');
    Route::get('/orders', [PageController::class, 'orders'])->name('orders');
    Route::post('/orders/track', [PageController::class, 'trackOrder'])->name('orders.track');
    Route::get('/schedule', [PageController::class, 'schedule'])->name('schedule');
    Route::post('/schedule', [PageController::class, 'storeSchedule'])->name('schedule.store');
    Route::get('/schedule/availability', [PageController::class, 'scheduleAvailability'])->name('schedule.availability');
    Route::get('/estimator', [PageController::class, 'estimator'])->name('estimator');
    Route::get('/account', [PageController::class, 'account'])->name('account');
    Route::put('/account', [PageController::class, 'updateAccount'])->name('account.update');
    Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

    // Staff/Admin: dashboard + products, services, appointments, archive
    Route::middleware('staff')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/reports/orders-export', [AdminController::class, 'exportOrdersCsv'])->name('reports.orders-csv');

        Route::post('/products', [AdminController::class, 'storeProduct'])->name('products.store');
        Route::put('/products/{product}', [AdminController::class, 'updateProduct'])->name('products.update');
        Route::delete('/products/{product}', [AdminController::class, 'deleteProduct'])->name('products.delete');
        Route::put('/products/{product}/restore', [AdminController::class, 'restoreProduct'])->name('products.restore');

        Route::post('/services', [AdminController::class, 'storeService'])->name('services.store');
        Route::put('/services/{serviceType}', [AdminController::class, 'updateService'])->name('services.update');
        Route::delete('/services/{serviceType}', [AdminController::class, 'deleteService'])->name('services.delete');
        Route::put('/services/{serviceType}/restore', [AdminController::class, 'restoreService'])->name('services.restore');

        Route::put('/appointments/{appointment}/status', [AdminController::class, 'updateAppointmentStatus'])->name('appointments.status');
        Route::put('/appointments/{appointment}/archive', [AdminController::class, 'archiveAppointment'])->name('appointments.archive');
        Route::put('/appointments/{appointment}/restore', [AdminController::class, 'restoreAppointment'])->name('appointments.restore');
    });

    // Admin-only: orders and user role management
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::put('/orders/{order}/status', [AdminController::class, 'updateOrderStatus'])->name('orders.status');
        Route::post('/orders/bulk-status', [AdminController::class, 'bulkOrderStatus'])->name('orders.bulk-status');
        Route::put('/orders/{order}/archive', [AdminController::class, 'archiveOrder'])->name('orders.archive');
        Route::put('/orders/{order}/restore', [AdminController::class, 'restoreOrder'])->name('orders.restore');

        Route::put('/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('users.role');
    });

    // Legacy URLs (keep your original exact UI links working)
    Route::get('/ferosa-home.html', [PageController::class, 'home'])->name('legacy.home');
    Route::get('/ferosa-shop.html', [PageController::class, 'shop'])->name('legacy.shop');
    Route::get('/ferosa-orders.html', [PageController::class, 'orders'])->name('legacy.orders');
    Route::get('/ferosa-schedule.html', [PageController::class, 'schedule'])->name('legacy.schedule');
    Route::get('/ferosa-estimator.html', [PageController::class, 'estimator'])->name('legacy.estimator');
});
