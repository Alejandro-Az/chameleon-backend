<?php

use App\Http\Controllers\Api\V1\AdminAppointmentAvailabilityController;
use App\Http\Controllers\Api\V1\AdminAppointmentController;
use App\Http\Controllers\Api\V1\AdminAppointmentServiceController;
use App\Http\Controllers\Api\V1\AdminAppointmentStaffController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->middleware(['auth:api', 'throttle:admin', 'user.active', 'user.verified', 'jwt.not_revoked'])
    ->group(function () {
        Route::apiResource('appointment-services', AdminAppointmentServiceController::class)->middleware('perm:appointments.services.manage');

        Route::get('appointment-staff', [AdminAppointmentStaffController::class, 'index'])->middleware('perm:appointments.staff.manage');
        Route::post('appointment-staff', [AdminAppointmentStaffController::class, 'store'])->middleware('perm:appointments.staff.manage');
        Route::get('appointment-staff/{appointmentStaffProfile}', [AdminAppointmentStaffController::class, 'show'])->middleware('perm:appointments.staff.manage');
        Route::patch('appointment-staff/{appointmentStaffProfile}', [AdminAppointmentStaffController::class, 'update'])->middleware('perm:appointments.staff.manage');
        Route::delete('appointment-staff/{appointmentStaffProfile}', [AdminAppointmentStaffController::class, 'destroy'])->middleware('perm:appointments.staff.manage');

        Route::get('appointment-availability', [AdminAppointmentAvailabilityController::class, 'show'])->middleware('perm:appointments.availability.manage');
        Route::put('appointment-availability', [AdminAppointmentAvailabilityController::class, 'update'])->middleware('perm:appointments.availability.manage');
        Route::get('appointment-availability/exceptions', [AdminAppointmentAvailabilityController::class, 'exceptions'])->middleware('perm:appointments.availability.manage');
        Route::post('appointment-availability/exceptions', [AdminAppointmentAvailabilityController::class, 'storeException'])->middleware('perm:appointments.availability.manage');
        Route::patch('appointment-availability/exceptions/{appointmentException}', [AdminAppointmentAvailabilityController::class, 'updateException'])->middleware('perm:appointments.availability.manage');
        Route::delete('appointment-availability/exceptions/{appointmentException}', [AdminAppointmentAvailabilityController::class, 'destroyException'])->middleware('perm:appointments.availability.manage');
        Route::get('appointment-availability/staff/{appointmentStaffProfile}', [AdminAppointmentAvailabilityController::class, 'showStaff'])->middleware('perm:appointments.availability.manage');
        Route::put('appointment-availability/staff/{appointmentStaffProfile}', [AdminAppointmentAvailabilityController::class, 'updateStaff'])->middleware('perm:appointments.availability.manage');
        Route::post('appointment-availability/staff/{appointmentStaffProfile}/exceptions', [AdminAppointmentAvailabilityController::class, 'storeStaffException'])->middleware('perm:appointments.availability.manage');
        Route::patch('appointment-availability/staff/{appointmentStaffProfile}/exceptions/{appointmentStaffException}', [AdminAppointmentAvailabilityController::class, 'updateStaffException'])->middleware('perm:appointments.availability.manage');
        Route::delete('appointment-availability/staff/{appointmentStaffProfile}/exceptions/{appointmentStaffException}', [AdminAppointmentAvailabilityController::class, 'destroyStaffException'])->middleware('perm:appointments.availability.manage');
        Route::post('appointment-availability/reset-defaults', [AdminAppointmentAvailabilityController::class, 'resetDefaults'])->middleware('perm:appointments.availability.manage');

        Route::get('appointments', [AdminAppointmentController::class, 'index'])->middleware('perm:appointments.bookings.view_all');
        Route::post('appointments', [AdminAppointmentController::class, 'store'])->middleware('perm:appointments.bookings.create_internal');
        Route::get('appointments/slots', [AdminAppointmentController::class, 'slots'])->middleware('perm:appointments.bookings.create_internal');
        Route::get('appointments/{appointment}', [AdminAppointmentController::class, 'show']);
        Route::patch('appointments/{appointment}', [AdminAppointmentController::class, 'update'])->middleware('perm:appointments.bookings.manage');
        Route::post('appointments/{appointment}/assign', [AdminAppointmentController::class, 'assign'])->middleware('perm:appointments.bookings.assign');
        Route::post('appointments/{appointment}/status', [AdminAppointmentController::class, 'updateStatus'])->middleware('perm:appointments.bookings.status.manage');
        Route::post('appointments/{appointment}/cancel', [AdminAppointmentController::class, 'cancel'])->middleware('perm:appointments.bookings.manage');
        Route::post('appointments/{appointment}/reschedule', [AdminAppointmentController::class, 'reschedule'])->middleware('perm:appointments.bookings.manage');
    });
