<?php

use App\Core\Router;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\Admin as Admin;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| Routes are loaded by the App and assigned to the Controller actions.
|
*/

// Public Routes
Router::get('/', [HomeController::class, 'index'])->name('home');
Router::get('/home', [HomeController::class, 'index'])->name('home.alias');
Router::get('/about', [HomeController::class, 'about'])->name('about');
Router::get('/contact', [HomeController::class, 'contact'])->name('contact');
Router::get('/destinations', [HomeController::class, 'destinations'])->name('destinations');
Router::get('/financing', [HomeController::class, 'financing'])->name('financing');
Router::get('/bus', [HomeController::class, 'bus'])->name('bus');
Router::get('/bus-tickets', fn() => redirect('/bus'))->name('bus-tickets');
Router::get('/terms', [HomeController::class, 'terms'])->name('terms');
Router::get('/privacy', [HomeController::class, 'privacy'])->name('privacy');
Router::get('/flights', [FlightController::class, 'index'])->name('flights');
Router::get('/flights/{id}', [FlightController::class, 'show'])->name('flights.show');
Router::get('/hotels', [HotelController::class, 'index'])->name('hotels');
Router::get('/hotels/{id}', [HotelController::class, 'show'])->name('hotels.show');

// Public account sign-in is not used. Booking requests go to the contact page.
Router::get('/login', function (\App\Core\Request $request) {
    return redirect('/contact');
})->name('login');
Router::post('/login', function (\App\Core\Request $request) {
    return redirect('/contact');
});
Router::get('/register', function (\App\Core\Request $request) {
    return redirect('/contact');
})->name('register');
Router::post('/register', function (\App\Core\Request $request) {
    return redirect('/contact');
});
Router::any('/logout', function (\App\Core\Request $request) {
    return redirect('/contact');
})->name('logout');

// Admin Authentication Routes (Guest Only)
Router::get('/admin/login', [Admin\AuthController::class, 'showLoginForm'])->name('admin.login')->middleware('admin.guest');
Router::post('/admin/login', [Admin\AuthController::class, 'login'])->middleware(['csrf', 'admin.guest']);
Router::get('/admin/forgot-password', [Admin\AuthController::class, 'showForgotPasswordForm'])->name('admin.forgot-password')->middleware('admin.guest');
Router::post('/admin/forgot-password', [Admin\AuthController::class, 'sendResetLink'])->middleware(['csrf', 'admin.guest']);
Router::get('/admin/reset-password', [Admin\AuthController::class, 'showResetPasswordForm'])->name('admin.reset-password')->middleware('admin.guest');
Router::post('/admin/reset-password', [Admin\AuthController::class, 'resetPassword'])->middleware(['csrf', 'admin.guest']);

// Admin Logout
Router::post('/admin/logout', [Admin\AuthController::class, 'logout'])->name('admin.logout')->middleware(['csrf', 'admin.auth']);
Router::get('/admin/logout', [Admin\AuthController::class, 'logout'])->middleware('admin.auth');

// Protected Admin Routes
Router::get('/admin', function () {
    return redirect('/admin/dashboard');
})->middleware('admin.auth');

Router::get('/admin/dashboard', [Admin\DashboardController::class, 'index'])->name('admin.dashboard')->middleware('admin.auth');
Router::get('/admin/api/dashboard-stats', [Admin\DashboardController::class, 'statsApi'])->name('admin.api.dashboard-stats')->middleware('admin.auth');
Router::get('/admin/customers', [Admin\CustomerController::class, 'index'])->name('admin.customers')->middleware('admin.auth');
Router::get('/admin/flights', [Admin\FlightController::class, 'index'])->name('admin.flights')->middleware('admin.auth');
Router::get('/admin/hotels', [Admin\HotelController::class, 'index'])->name('admin.hotels')->middleware('admin.auth');
Router::get('/admin/loans', [Admin\LoanController::class, 'index'])->name('admin.loans')->middleware('admin.auth');
Router::get('/admin/destinations', [Admin\DestinationController::class, 'index'])->name('admin.destinations')->middleware('admin.auth');
Router::get('/admin/transactions', [Admin\TransactionController::class, 'index'])->name('admin.transactions')->middleware('admin.auth');
Router::get('/admin/api', [Admin\ApiController::class, 'index'])->name('admin.api')->middleware('admin.auth');
Router::get('/admin/api/live-data', [Admin\ApiController::class, 'liveData'])->name('admin.api.live-data')->middleware('admin.auth');
Router::post('/admin/api/records/update', [Admin\ApiController::class, 'updateRecord'])->name('admin.api.records.update')->middleware(['csrf', 'admin.auth']);
Router::post('/admin/api/records/delete', [Admin\ApiController::class, 'deleteRecord'])->name('admin.api.records.delete')->middleware(['csrf', 'admin.auth']);
Router::post('/admin/api/customers/create', [Admin\ApiController::class, 'createCustomer'])->name('admin.api.customers.create')->middleware(['csrf', 'admin.auth']);
Router::post('/admin/api/flights/create', [Admin\ApiController::class, 'createFlight'])->name('admin.api.flights.create')->middleware(['csrf', 'admin.auth']);

Router::get('/api/flights', function (\App\Core\Request $request) {
    return [
        'success' => true,
        'data' => \App\Services\RealtimeDataService::getPublicFlights(),
    ];
})->name('api.flights');

Router::post('/api/flights/book', function (\App\Core\Request $request) {
    $booked = \App\Services\RealtimeDataService::bookFlightFromSearch($request->all());
    $status = !empty($booked['success']) ? 200 : 422;
    return \App\Core\Response::json($booked, $status);
})->name('api.flights.book');
Router::post('/admin/api/hotels/create', [Admin\ApiController::class, 'createHotel'])->name('admin.api.hotels.create')->middleware(['csrf', 'admin.auth']);
Router::get('/api/hotels', function (\App\Core\Request $request) {
    return [
        'success' => true,
        'data' => \App\Services\RealtimeDataService::getPublicHotels(),
    ];
})->name('api.hotels');
Router::post('/api/hotels/book', function (\App\Core\Request $request) {
    $booked = \App\Services\RealtimeDataService::bookHotelFromSearch($request->all());
    $status = !empty($booked['success']) ? 200 : 422;
    return \App\Core\Response::json($booked, $status);
})->name('api.hotels.book');
Router::post('/admin/api/loans/create', [Admin\ApiController::class, 'createLoan'])->name('admin.api.loans.create')->middleware(['csrf', 'admin.auth']);
Router::post('/admin/api/transactions/create', [Admin\ApiController::class, 'createTransaction'])->name('admin.api.transactions.create')->middleware(['csrf', 'admin.auth']);
Router::post('/api/loans/apply', function (\App\Core\Request $request) {
    $applied = \App\Services\RealtimeDataService::applyLoanFromFinancing($request->all());
    $status = !empty($applied['success']) ? 200 : 422;
    return \App\Core\Response::json($applied, $status);
})->name('api.loans.apply');
Router::post('/api/contact', function (\App\Core\Request $request) {
    $saved = \App\Services\RealtimeDataService::createContactMessage($request->all());
    $status = !empty($saved['success']) ? 200 : 422;
    return \App\Core\Response::json($saved, $status);
})->name('api.contact');
Router::post('/api/promo/register', function (\App\Core\Request $request) {
    $registered = \App\Services\RealtimeDataService::registerPromo($request->all());
    $status = !empty($registered['success']) ? 200 : 422;
    return \App\Core\Response::json($registered, $status);
})->name('api.promo.register');
Router::get('/api/promo/active', function (\App\Core\Request $request) {
    return [
        'success' => true,
        'data' => \App\Services\RealtimeDataService::getActivePromoCampaign(),
    ];
})->name('api.promo.active');

Router::post('/api/bus/enquire', function (\App\Core\Request $request) {
    $limit = \App\Services\BusBookingRateLimiter::attempt($request->ip());
    if (($limit['outcome'] ?? '') === 'storage') {
        return \App\Core\Response::json([
            'success' => false,
            'error' => 'Bus enquiries are temporarily unavailable. Please try again in a moment.',
        ], 503);
    }
    if (!$limit['allowed']) {
        return \App\Core\Response::json([
            'success' => false,
            'error' => 'Too many booking attempts from this network. Please wait a few minutes and try again.',
        ], 429);
    }

    $created = \App\Services\RealtimeDataService::createBusBooking($request->all());
    $status = !empty($created['success']) ? 200 : 422;
    return \App\Core\Response::json($created, $status);
})->name('api.bus.enquire')->middleware('csrf');

Router::get('/api/bus/routes', function (\App\Core\Request $request) {
    return [
        'success' => true,
        'data' => \App\Services\RealtimeDataService::getPopularBusRoutes(),
    ];
})->name('api.bus.routes');

Router::post('/api/bus/estimate', function (\App\Core\Request $request) {
    $from = (string)$request->input('from', '');
    $to = (string)$request->input('to', '');
    $tripType = (string)$request->input('trip_type', 'one-way');
    $passengers = (int)$request->input('passengers', 1);
    $coachClass = (string)$request->input('coach_class', 'Executive Luxury AC Coach');

    $estimatedFare = \App\Services\RealtimeDataService::estimateBusFare($from, $to, $tripType, $passengers, $coachClass);
    return [
        'success' => true,
        'estimatedFare' => $estimatedFare,
        'formattedFare' => '₦' . number_format($estimatedFare),
    ];
})->name('api.bus.estimate');
Router::get('/index', fn() => redirect('/'));
Router::post('/admin/api/destinations/create', [Admin\ApiController::class, 'createDestination'])->name('admin.api.destinations.create')->middleware(['csrf', 'admin.auth']);
Router::post('/admin/api/providers/save', [Admin\ApiController::class, 'saveProvider'])->name('admin.api.providers.save')->middleware(['csrf', 'admin.auth']);
Router::post('/admin/api/providers/delete', [Admin\ApiController::class, 'deleteProvider'])->name('admin.api.providers.delete')->middleware(['csrf', 'admin.auth']);
Router::post('/admin/api/providers/test', [Admin\ApiController::class, 'testProvider'])->name('admin.api.providers.test')->middleware(['csrf', 'admin.auth']);
Router::get('/api/destinations', function (\App\Core\Request $request) {
    return [
        'success' => true,
        'data' => \App\Services\RealtimeDataService::getPublicDestinations(),
    ];
})->name('api.destinations');
Router::get('/admin/emails', [Admin\EmailController::class, 'index'])->name('admin.emails')->middleware('admin.auth');
Router::post('/admin/emails/send', [Admin\EmailController::class, 'send'])->name('admin.emails.send')->middleware(['csrf', 'admin.auth']);
Router::post('/admin/emails/settings', [Admin\EmailController::class, 'saveSettings'])->name('admin.emails.settings')->middleware(['csrf', 'admin.auth']);
Router::get('/admin/messages', [Admin\MessageController::class, 'index'])->name('admin.messages')->middleware('admin.auth');
Router::post('/admin/messages/delete', [Admin\MessageController::class, 'destroy'])->name('admin.messages.delete')->middleware(['csrf', 'admin.auth']);
Router::get('/admin/reports', [Admin\ReportController::class, 'index'])->name('admin.reports')->middleware('admin.auth');
Router::get('/admin/reports/export', [Admin\ReportController::class, 'export'])->name('admin.reports.export')->middleware('admin.auth');
Router::get('/admin/settings', [Admin\SettingController::class, 'index'])->name('admin.settings')->middleware('admin.auth');
Router::get('/admin/settings.php', fn() => redirect('/admin/settings'));
Router::post('/admin/settings/profile', [Admin\SettingController::class, 'updateProfile'])->name('admin.settings.profile')->middleware(['csrf', 'admin.auth']);
Router::post('/admin/settings/password', [Admin\SettingController::class, 'updatePassword'])->name('admin.settings.password')->middleware(['csrf', 'admin.auth']);
Router::post('/admin/settings/promo', [Admin\SettingController::class, 'updatePromoCampaign'])->name('admin.settings.promo')->middleware(['csrf', 'admin.auth']);
Router::post('/admin/settings/contact-phone', [Admin\SettingController::class, 'updateContactPhone'])->name('admin.settings.contact-phone')->middleware(['csrf', 'admin.auth']);
Router::get('/admin/bus', [Admin\BusController::class, 'index'])->name('admin.bus')->middleware('admin.auth');
Router::post('/admin/api/bus/status', [Admin\BusController::class, 'updateStatus'])->name('admin.api.bus.status')->middleware(['csrf', 'admin.auth']);
Router::post('/admin/api/bus/delete', [Admin\BusController::class, 'destroy'])->name('admin.api.bus.delete')->middleware(['csrf', 'admin.auth']);

// Customer account pages are closed. Visitors book through the contact page.
Router::get('/customer', function (\App\Core\Request $request) {
    return redirect('/contact');
});
Router::get('/customer/dashboard', function (\App\Core\Request $request) {
    return redirect('/contact');
})->name('customer.dashboard');
Router::get('/customer/overview', function (\App\Core\Request $request) {
    return redirect('/contact');
})->name('customer.overview');
Router::get('/customer/flights', function (\App\Core\Request $request) {
    return redirect('/contact');
})->name('customer.flights');
Router::get('/customer/hotels', function (\App\Core\Request $request) {
    return redirect('/contact');
})->name('customer.hotels');
Router::get('/customer/financing', function (\App\Core\Request $request) {
    return redirect('/contact');
})->name('customer.financing');
Router::get('/customer/payments', function (\App\Core\Request $request) {
    return redirect('/contact');
})->name('customer.payments');
Router::get('/customer/profile', function (\App\Core\Request $request) {
    return redirect('/contact');
})->name('customer.profile');
Router::post('/customer/profile', function (\App\Core\Request $request) {
    return redirect('/contact');
})->name('customer.profile.update');
Router::get('/customer/settings', function (\App\Core\Request $request) {
    return redirect('/contact');
})->name('customer.settings');

// Legacy .html and .php redirects for backward compatibility
Router::get('/index.html', fn() => redirect('/'));
Router::get('/home.html', fn() => redirect('/'));
Router::get('/about.html', fn() => redirect('/about'));
Router::get('/contact.html', fn() => redirect('/contact'));
Router::get('/destinations.html', fn() => redirect('/destinations'));
Router::get('/financing.html', fn() => redirect('/financing'));
Router::get('/terms.html', fn() => redirect('/terms'));
Router::get('/privacy.html', fn() => redirect('/privacy'));
Router::get('/flights.html', fn() => redirect('/flights'));
Router::get('/hotels.html', fn() => redirect('/hotels'));
Router::get('/bus.html', fn() => redirect('/bus'));
Router::get('/bus-tickets.html', fn() => redirect('/bus'));
Router::get('/login.html', fn() => redirect('/login'));
Router::get('/register.html', fn() => redirect('/register'));

Router::get('/index.php', fn() => redirect('/'));
Router::get('/home.php', fn() => redirect('/'));
Router::get('/about.php', fn() => redirect('/about'));
Router::get('/contact.php', fn() => redirect('/contact'));
Router::get('/destinations.php', fn() => redirect('/destinations'));
Router::get('/financing.php', fn() => redirect('/financing'));
Router::get('/terms.php', fn() => redirect('/terms'));
Router::get('/privacy.php', fn() => redirect('/privacy'));
Router::get('/flights.php', fn() => redirect('/flights'));
Router::get('/hotels.php', fn() => redirect('/hotels'));
Router::get('/bus.php', fn() => redirect('/bus'));
Router::get('/bus-tickets.php', fn() => redirect('/bus'));
Router::get('/login.php', fn() => redirect('/login'));
Router::get('/register.php', fn() => redirect('/register'));

Router::get('/admin/login.php', fn() => redirect('/admin/login'));
Router::get('/admin/forgot-password.php', fn() => redirect('/admin/forgot-password'));
Router::get('/admin/reset-password.php', fn() => redirect('/admin/reset-password'));
Router::get('/admin/dashboard.php', fn() => redirect('/admin/dashboard'));
Router::get('/admin/customers.php', fn() => redirect('/admin/customers'));
Router::get('/admin/flights.php', fn() => redirect('/admin/flights'));
Router::get('/admin/hotels.php', fn() => redirect('/admin/hotels'));
Router::get('/admin/loans.php', fn() => redirect('/admin/loans'));
Router::get('/admin/destinations.php', fn() => redirect('/admin/destinations'));
Router::get('/admin/transactions.php', fn() => redirect('/admin/transactions'));
Router::get('/admin/api.php', fn() => redirect('/admin/api'));
Router::get('/admin/reports.php', fn() => redirect('/admin/reports'));
Router::get('/admin/settings.php', fn() => redirect('/admin/settings'));
Router::get('/admin/bus.php', fn() => redirect('/admin/bus'));

Router::get('/customer/dashboard.php', fn() => redirect('/customer/dashboard'));
Router::get('/customer/overview.php', fn() => redirect('/customer/dashboard'));
Router::get('/customer/flights.php', fn() => redirect('/customer/flights'));
Router::get('/customer/hotels.php', fn() => redirect('/customer/hotels'));
Router::get('/customer/financing.php', fn() => redirect('/customer/financing'));
Router::get('/customer/payments.php', fn() => redirect('/customer/payments'));
Router::get('/customer/profile.php', fn() => redirect('/customer/profile'));
Router::get('/customer/settings.php', fn() => redirect('/customer/settings'));

