<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class AttendanceEvidenceStorage
{
    /**
     * Store a validated data URI on the configured private disk.
     *
     * @return array{disk: string, path: string, hash: string, mime: string, bytes: int}
     */
    public function storeDataUri(string $dataUri, string $directory = 'attendance/evidence'): array
    {
        if (! preg_match('/^data:(image\/(?:png|jpeg|jpg));base64,(.+)$/s', $dataUri, $matches)) {
            throw new InvalidArgumentException(__('attendance.errors.evidence_data_uri_invalid'));
        }

        $bytes = base64_decode($matches[2], true);
        if ($bytes === false) {
            throw new InvalidArgumentException(__('attendance.errors.evidence_base64_invalid'));
        }

        return $this->storeBytes($bytes, $matches[1], $directory);
    }

    /**
     * Store an uploaded image without trusting its original filename.
     *
     * @return array{disk: string, path: string, hash: string, mime: string, bytes: int}
     */
    public function storeUploadedFile(UploadedFile $file, string $directory = 'attendance/evidence'): array
    {
        if (! $file->isValid()) {
            throw new InvalidArgumentException(__('attendance.errors.evidence_upload_invalid'));
        }

        $realPath = $file->getRealPath();
        if ($realPath === false) {
            throw new RuntimeException(__('attendance.errors.evidence_upload_missing'));
        }

        $bytes = file_get_contents($realPath);
        if ($bytes === false) {
            throw new RuntimeException(__('attendance.errors.evidence_upload_unreadable'));
        }

        $mime = (string) $file->getMimeType();

        return $this->storeBytes($bytes, $mime, $directory);
    }

    public function delete(?string $path, ?string $disk = null): bool
    {
        if ($path === null || $path === '') {
            return true;
        }

        return Storage::disk($disk ?: $this->disk())->delete($path);
    }

    /**
     * @return array{disk: string, path: string, hash: string, mime: string, bytes: int}
     */
    private function storeBytes(string $bytes, string $mime, string $directory): array
    {
        $maxBytes = max(1, (int) config('attendance.max_evidence_bytes', 2097152));
        $size = strlen($bytes);
        if ($size === 0 || $size > $maxBytes) {
            throw new InvalidArgumentException(__('attendance.errors.evidence_size_limit'));
        }

        $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $extension = match ($detectedMime) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            default => throw new InvalidArgumentException(__('attendance.errors.evidence_image_invalid')),
        };

        $disk = $this->disk();
        $path = trim($directory, '/').'/'.Str::uuid().'.'.$extension;
        if (! Storage::disk($disk)->put($path, $bytes)) {
            throw new RuntimeException(__('attendance.errors.evidence_store_failed'));
        }

        return [
            'disk' => $disk,
            'path' => $path,
            'hash' => hash('sha256', $bytes),
            'mime' => $detectedMime,
            'bytes' => $size,
        ];
    }

    private function disk(): string
    {
        return (string) config('attendance.evidence_disk', 'local');
    }
}
