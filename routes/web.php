<?php

use App\Core\Router;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin as Admin;
use App\Http\Controllers\Customer as Customer;

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
Router::get('/about', [HomeController::class, 'about'])->name('about');
Router::get('/contact', [HomeController::class, 'contact'])->name('contact');
Router::get('/destinations', [HomeController::class, 'destinations'])->name('destinations');
Router::get('/financing', [HomeController::class, 'financing'])->name('financing');
Router::get('/flights', [FlightController::class, 'index'])->name('flights');
Router::get('/flights/{id}', [FlightController::class, 'show'])->name('flights.show');
Router::get('/hotels', [HotelController::class, 'index'])->name('hotels');
Router::get('/hotels/{id}', [HotelController::class, 'show'])->name('hotels.show');

// Authentication Routes
Router::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Router::post('/login', [LoginController::class, 'login']);
Router::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Router::post('/register', [RegisterController::class, 'register']);
Router::any('/logout', [LoginController::class, 'logout'])->name('logout');

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
Router::post('/admin/api/destinations/create', [Admin\ApiController::class, 'createDestination'])->name('admin.api.destinations.create')->middleware(['csrf', 'admin.auth']);
Router::get('/admin/reports', [Admin\ReportController::class, 'index'])->name('admin.reports')->middleware('admin.auth');
Router::get('/admin/settings', [Admin\SettingController::class, 'index'])->name('admin.settings')->middleware('admin.auth');
Router::post('/admin/settings/profile', [Admin\SettingController::class, 'updateProfile'])->name('admin.settings.profile')->middleware(['csrf', 'admin.auth']);
Router::post('/admin/settings/password', [Admin\SettingController::class, 'updatePassword'])->name('admin.settings.password')->middleware(['csrf', 'admin.auth']);

// Customer Routes
Router::get('/customer', function () {
    return redirect('/customer/dashboard');
});
Router::get('/customer/dashboard', [Customer\DashboardController::class, 'index'])->name('customer.dashboard');
Router::get('/customer/overview', [Customer\DashboardController::class, 'index'])->name('customer.overview');
Router::get('/customer/flights', [Customer\FlightController::class, 'index'])->name('customer.flights');
Router::get('/customer/hotels', [Customer\HotelController::class, 'index'])->name('customer.hotels');
Router::get('/customer/financing', [Customer\FinancingController::class, 'index'])->name('customer.financing');
Router::get('/customer/payments', [Customer\PaymentController::class, 'index'])->name('customer.payments');
Router::get('/customer/profile', [Customer\ProfileController::class, 'index'])->name('customer.profile');
Router::get('/customer/settings', [Customer\SettingController::class, 'index'])->name('customer.settings');

// Legacy .html and .php redirects for backward compatibility
Router::get('/index.html', fn() => redirect('/'));
Router::get('/about.html', fn() => redirect('/about'));
Router::get('/contact.html', fn() => redirect('/contact'));
Router::get('/destinations.html', fn() => redirect('/destinations'));
Router::get('/financing.html', fn() => redirect('/financing'));
Router::get('/flights.html', fn() => redirect('/flights'));
Router::get('/hotels.html', fn() => redirect('/hotels'));
Router::get('/login.html', fn() => redirect('/login'));
Router::get('/register.html', fn() => redirect('/register'));

Router::get('/index.php', fn() => redirect('/'));
Router::get('/about.php', fn() => redirect('/about'));
Router::get('/contact.php', fn() => redirect('/contact'));
Router::get('/destinations.php', fn() => redirect('/destinations'));
Router::get('/financing.php', fn() => redirect('/financing'));
Router::get('/flights.php', fn() => redirect('/flights'));
Router::get('/hotels.php', fn() => redirect('/hotels'));
Router::get('/login.php', fn() => redirect('/login'));
Router::get('/register.php', fn() => redirect('/register'));

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

Router::get('/customer/dashboard.php', fn() => redirect('/customer/dashboard'));
Router::get('/customer/overview.php', fn() => redirect('/customer/dashboard'));
Router::get('/customer/flights.php', fn() => redirect('/customer/flights'));
Router::get('/customer/hotels.php', fn() => redirect('/customer/hotels'));
Router::get('/customer/financing.php', fn() => redirect('/customer/financing'));
Router::get('/customer/payments.php', fn() => redirect('/customer/payments'));
Router::get('/customer/profile.php', fn() => redirect('/customer/profile'));
Router::get('/customer/settings.php', fn() => redirect('/customer/settings'));

