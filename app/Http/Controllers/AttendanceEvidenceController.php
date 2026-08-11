<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AttendanceEvidenceController extends Controller
{
    public function show(AttendanceRecord $attendanceRecord)
    {
        Gate::authorize('view', $attendanceRecord);

        $disk = (string) $attendanceRecord->evidence_disk;
        $path = (string) $attendanceRecord->evidence_path;
        abort_if($disk === '' || $path === '', 404);

        $storage = Storage::disk($disk);
        abort_unless($storage->exists($path), 404);

        $stream = $storage->readStream($path);
        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Cache-Control' => 'private, max-age=300',
            'Content-Type' => (string) ($attendanceRecord->evidence_mime ?: $storage->mimeType($path)),
            'Content-Disposition' => 'inline',
        ]);
    }
}
