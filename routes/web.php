<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Customer;
use App\Http\Controllers\Public;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/
Route::get('/', Public\HomeController::class)->name('home');
Route::get('/about', [Public\PageController::class, 'about'])->name('about');
Route::get('/privacy', [Public\PageController::class, 'privacy'])->name('privacy');

Route::get('/services', [Public\ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{slug}', [Public\ServiceController::class, 'show'])->name('services.show');

Route::get('/products', [Public\ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [Public\ProductController::class, 'show'])->name('products.show');

Route::get('/branches', [Public\BranchController::class, 'index'])->name('branches.index');
Route::get('/branches/{slug}', [Public\BranchController::class, 'show'])->name('branches.show');

Route::get('/health-resources', [Public\ArticleController::class, 'index'])->name('articles.index');
Route::get('/health-resources/{slug}', [Public\ArticleController::class, 'show'])->name('articles.show');

Route::get('/contact', [Public\ContactController::class, 'create'])->name('contact');
Route::post('/contact', [Public\ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

Route::get('/appointments/book', [Public\AppointmentController::class, 'create'])->name('appointments.create');
Route::post('/appointments', [Public\AppointmentController::class, 'store'])->middleware('throttle:appointments')->name('appointments.store');
Route::get('/appointments/received', [Public\AppointmentController::class, 'received'])->name('appointments.received');
Route::get('/appointments/availability', [Public\AppointmentController::class, 'availability'])->middleware('throttle:60,1')->name('appointments.availability');

/*
| Shop and online payments (Paystack)
*/
Route::middleware('shop')->group(function () {
    Route::get('/cart', [Public\ShopController::class, 'cart'])->name('shop.cart');
    Route::post('/cart', [Public\ShopController::class, 'add'])->name('shop.cart.add');
    Route::patch('/cart', [Public\ShopController::class, 'update'])->name('shop.cart.update');
    Route::delete('/cart/{product}', [Public\ShopController::class, 'remove'])->whereNumber('product')->name('shop.cart.remove');
    Route::get('/checkout', [Public\ShopController::class, 'checkout'])->name('shop.checkout');
    Route::post('/checkout', [Public\ShopController::class, 'placeOrder'])->middleware('throttle:checkout')->name('shop.checkout.store');
});
// Signed links (sent by email / shown after checkout) – work without signing in, cannot be guessed.
Route::middleware('signed')->group(function () {
    Route::get('/orders/{order}', [Public\OrderController::class, 'show'])->name('shop.orders.show');
    Route::post('/orders/{order}/pay', [Public\OrderController::class, 'pay'])->middleware('throttle:checkout')->name('shop.orders.pay');
    Route::get('/appointments/{appointment}/pay', [Public\AppointmentPaymentController::class, 'show'])->name('appointments.pay');
    Route::post('/appointments/{appointment}/pay', [Public\AppointmentPaymentController::class, 'pay'])->middleware('throttle:checkout')->name('appointments.pay.start');
});
Route::get('/payments/callback', [Public\PaymentController::class, 'callback'])->middleware('throttle:30,1')->name('payments.callback');
Route::post('/paystack/webhook', [Public\PaymentController::class, 'webhook'])->middleware('throttle:120,1')->name('paystack.webhook');

// Media library images, stored in the database.
Route::get('/media/{path}', Public\MediaFileController::class)->where('path', '[a-z]+/[A-Za-z0-9]+\.[a-z0-9]+')->name('media.file');

Route::get('/sitemap.xml', Public\SitemapController::class)->name('sitemap');
Route::get('/robots.txt', [Public\SitemapController::class, 'robots'])->name('robots');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [Auth\LoginController::class, 'create'])->name('login');
    Route::post('/login', [Auth\LoginController::class, 'store'])->middleware('throttle:login');

    Route::middleware('customer.accounts')->group(function () {
        Route::get('/register', [Auth\RegisterController::class, 'create'])->name('register');
        Route::post('/register', [Auth\RegisterController::class, 'store'])->middleware('throttle:register');
    });

    Route::get('/forgot-password', [Auth\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [Auth\PasswordResetController::class, 'email'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [Auth\PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [Auth\PasswordResetController::class, 'update'])->middleware('throttle:password-reset')->name('password.update');
});

Route::post('/logout', [Auth\LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Customer account area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'customer.accounts', 'customer'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', [Customer\AccountController::class, 'index'])->name('index');
    Route::get('/profile', [Customer\AccountController::class, 'edit'])->name('profile');
    Route::patch('/profile', [Customer\AccountController::class, 'update'])->name('profile.update');
    Route::put('/password', [Customer\AccountController::class, 'password'])->name('password');

    Route::get('/appointments', [Customer\AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/{appointment}', [Customer\AppointmentController::class, 'show'])->name('appointments.show');
    Route::post('/appointments/{appointment}/cancel', [Customer\AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/appointments/{appointment}/request-reschedule', [Customer\AppointmentController::class, 'reschedule'])->name('appointments.reschedule');

    Route::get('/enquiries', [Customer\AccountController::class, 'enquiries'])->name('enquiries');
    Route::get('/orders', [Customer\AccountController::class, 'orders'])->name('orders');
});

/*
|--------------------------------------------------------------------------
| Admin dashboard (staff only; every action is permission-checked)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::middleware('can:appointments.view')->group(function () {
        Route::get('/appointments', [Admin\AppointmentController::class, 'index'])->name('appointments.index');
        Route::get('/appointments/{appointment}', [Admin\AppointmentController::class, 'show'])->name('appointments.show');
        Route::patch('/appointments/{appointment}', [Admin\AppointmentController::class, 'update'])->middleware('can:appointments.manage')->name('appointments.update');
        Route::patch('/appointments/{appointment}/note', [Admin\AppointmentController::class, 'note'])->middleware('can:appointments.manage')->name('appointments.note');
        Route::post('/appointments/{appointment}/deposit', [Admin\AppointmentController::class, 'recordDeposit'])->middleware('can:appointments.manage')->name('appointments.deposit');
        Route::post('/appointments/{appointment}/refund', [Admin\AppointmentController::class, 'refund'])->middleware('can:appointments.manage')->name('appointments.refund');
    });

    Route::middleware('can:orders.view')->group(function () {
        Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}', [Admin\OrderController::class, 'update'])->middleware('can:orders.manage')->name('orders.update');
        Route::patch('/orders/{order}/note', [Admin\OrderController::class, 'note'])->middleware('can:orders.manage')->name('orders.note');
        Route::post('/orders/{order}/refund', [Admin\OrderController::class, 'refund'])->middleware('can:orders.manage')->name('orders.refund');
    });

    Route::middleware('can:payments.view')->group(function () {
        Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/{payment}/recheck', [Admin\PaymentController::class, 'recheck'])->name('payments.recheck');
    });

    Route::middleware('can:customers.view')->group(function () {
        Route::get('/customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [Admin\CustomerController::class, 'show'])->name('customers.show');
        Route::get('/customers/{customer}/edit', [Admin\CustomerController::class, 'edit'])->middleware('can:customers.manage')->name('customers.edit');
        Route::patch('/customers/{customer}', [Admin\CustomerController::class, 'update'])->middleware('can:customers.manage')->name('customers.update');
        Route::patch('/customers/{customer}/status', [Admin\CustomerController::class, 'toggle'])->middleware('can:customers.manage')->name('customers.toggle');
    });

    Route::middleware('can:enquiries.view')->group(function () {
        Route::get('/enquiries', [Admin\EnquiryController::class, 'index'])->name('enquiries.index');
        Route::get('/enquiries/{enquiry}', [Admin\EnquiryController::class, 'show'])->name('enquiries.show');
        Route::patch('/enquiries/{enquiry}', [Admin\EnquiryController::class, 'update'])->middleware('can:enquiries.manage')->name('enquiries.update');
        Route::post('/enquiries/{enquiry}/notes', [Admin\EnquiryController::class, 'note'])->middleware('can:enquiries.manage')->name('enquiries.notes.store');
    });

    Route::middleware('can:services.manage')->group(function () {
        Route::resource('services', Admin\ServiceController::class)->except('show');
        Route::patch('/services/{service}/restore', [Admin\ServiceController::class, 'restore'])->withTrashed()->name('services.restore');
    });

    Route::middleware('can:products.manage')->group(function () {
        Route::resource('products', Admin\ProductController::class)->except('show');
        Route::patch('/products/{product}/restore', [Admin\ProductController::class, 'restore'])->withTrashed()->name('products.restore');
        Route::resource('categories', Admin\ProductCategoryController::class)->except('show')->parameters(['categories' => 'category']);
    });

    Route::middleware('can:branches.manage')->group(function () {
        Route::resource('branches', Admin\BranchController::class)->except('show');
        Route::patch('/branches/{branch}/restore', [Admin\BranchController::class, 'restore'])->withTrashed()->name('branches.restore');
    });

    Route::middleware('can:articles.manage')->group(function () {
        Route::resource('articles', Admin\ArticleController::class);
        Route::patch('/articles/{article}/restore', [Admin\ArticleController::class, 'restore'])->withTrashed()->name('articles.restore');
        Route::resource('article-categories', Admin\ArticleCategoryController::class)->except(['show', 'create', 'edit'])->parameters(['article-categories' => 'articleCategory']);
    });

    Route::resource('team', Admin\TeamMemberController::class)->except('show')->middleware('can:team.manage');

    Route::middleware('can:media.manage')->group(function () {
        Route::get('/media', [Admin\MediaController::class, 'index'])->name('media.index');
        Route::post('/media', [Admin\MediaController::class, 'store'])->name('media.store');
        Route::get('/media/{medium}/edit', [Admin\MediaController::class, 'edit'])->name('media.edit');
        Route::patch('/media/{medium}', [Admin\MediaController::class, 'update'])->name('media.update');
        Route::delete('/media/{medium}', [Admin\MediaController::class, 'destroy'])->name('media.destroy');
    });

    Route::middleware('can:content.transfer')->group(function () {
        Route::get('/content-transfer', [Admin\ContentTransferController::class, 'index'])->name('content-transfer.index');
        Route::post('/content-transfer/export', [Admin\ContentTransferController::class, 'export'])->name('content-transfer.export');
        Route::post('/content-transfer/import', [Admin\ContentTransferController::class, 'import'])->name('content-transfer.import');
    });

    Route::middleware('can:database.view')->group(function () {
        Route::get('/database', [Admin\DatabaseController::class, 'index'])->name('database.index');
        Route::get('/database/{table}', [Admin\DatabaseController::class, 'show'])->where('table', '[A-Za-z0-9_]+')->name('database.show');
    });

    Route::middleware('can:staff.manage')->group(function () {
        Route::resource('staff', Admin\StaffController::class)->except(['show', 'destroy'])->parameters(['staff' => 'staff']);
        Route::patch('/staff/{staff}/status', [Admin\StaffController::class, 'toggle'])->name('staff.toggle');
    });

    Route::get('/settings', [Admin\SettingsController::class, 'edit'])->middleware('can:settings.access')->name('settings.edit');
    Route::put('/settings', [Admin\SettingsController::class, 'update'])->middleware('can:settings.access')->name('settings.update');
    Route::post('/settings/test-notification', [Admin\SettingsController::class, 'test'])->middleware(['can:settings.system', 'throttle:10,1'])->name('settings.test');

    Route::middleware('can:reports.view')->group(function () {
        Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [Admin\ReportController::class, 'export'])->middleware('can:reports.export')->name('reports.export');
    });

    Route::get('/activity', Admin\ActivityController::class)->middleware('can:audit.view')->name('activity');

    Route::get('/password', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/password', [Admin\ProfileController::class, 'update'])->name('profile.update');
});
