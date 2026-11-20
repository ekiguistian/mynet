<?php

use App\Http\Controllers\AttendancePhotoController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/attendance-files/{attendance}/{direction}', [AttendancePhotoController::class, 'show'])
    ->middleware('auth')
    ->name('admin.attendance-photo');

Route::redirect('/', '/admin');
