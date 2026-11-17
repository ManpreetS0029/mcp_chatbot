<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ScheduleController as AdminScheduleController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ChatbotController;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Route;

// ==========================================
// Customer Booking Routes (Public)
// ==========================================
Route::get('/', [BookingController::class, 'index'])->name('booking.index');
Route::get('/book', [BookingController::class, 'index']);
Route::get('/api/available-slots', [BookingController::class, 'getSlots'])->name('booking.slots');
Route::get('/api/month-availability', [BookingController::class, 'getMonthAvailability'])->name('booking.month-availability');
Route::post('/api/chatbot', [ChatbotController::class, 'chat'])->name('chatbot.chat');
Route::post('/book', [BookingController::class, 'store'])->name('booking.store');
Route::get('/booking/confirmation/{reference}', [BookingController::class, 'confirmation'])->name('booking.confirmation');
Route::get('/booking/lookup', [BookingController::class, 'lookup'])->name('booking.lookup');

// ==========================================
// Admin Authentication Routes
// ==========================================
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login'); // Fallback for auth middleware
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// ==========================================
// Protected Admin Panel Routes
// ==========================================
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    // Dashboard & Appointments management
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/calendar-appointments', [AdminDashboardController::class, 'getCalendarAppointments'])->name('appointments.calendar');
    Route::get('/appointments/{appointment}', [AdminDashboardController::class, 'show'])->name('appointments.show');
    Route::patch('/appointments/{appointment}/status', [AdminDashboardController::class, 'updateStatus'])->name('appointments.status');
    Route::delete('/appointments/{appointment}', [AdminDashboardController::class, 'destroy'])->name('appointments.destroy');

    // Days & Time Slots schedule management
    Route::get('/schedule', [AdminScheduleController::class, 'index'])->name('schedule.index');
    Route::patch('/schedule/days/{workingDay}/toggle', [AdminScheduleController::class, 'toggleDay'])->name('schedule.days.toggle');
    Route::post('/schedule/slots', [AdminScheduleController::class, 'storeSlot'])->name('schedule.slots.store');
    Route::post('/schedule/slots/bulk', [AdminScheduleController::class, 'bulkStoreSlots'])->name('schedule.slots.bulk');
    Route::patch('/schedule/slots/{timeSlot}/toggle', [AdminScheduleController::class, 'toggleSlot'])->name('schedule.slots.toggle');
    Route::delete('/schedule/slots/{timeSlot}', [AdminScheduleController::class, 'destroySlot'])->name('schedule.slots.destroy');
});

Route::get('/test', function () {
    $model = config('gemini.model', 'gemini-3.5-flash');

    $result = Gemini::generativeModel(
        model: $model
    )->generateContent(
        'Hello! You are an appointment booking assistant.'
    );

    return response()->json([
        'message' => $result->text(),
    ]);
});
