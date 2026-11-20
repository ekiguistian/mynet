<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AttendancePhotoController extends Controller
{
    public function show(Request $request, Attendance $attendance, string $direction): Response
    {
        $user = $request->user();

        abort_unless($user && $user->canAccessFeature('attendance'), 403);
        abort_if($user->mustCompleteAccountSetup(), 403);
        abort_unless(in_array($direction, ['check_in', 'check_out'], true), 404);
        abort_if($user->isBranchScoped() && (int) $attendance->branch_id !== (int) $user->branch_id, 404);
        abort_if($user->hasRole('technician', 'helper') && (int) $attendance->user_id !== (int) $user->getKey(), 404);

        $path = $attendance->{$direction.'_photo_path'};
        abort_unless(filled($path) && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, no-store']);
    }
}
