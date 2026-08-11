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
            throw new InvalidArgumentException('Evidence must be a base64 PNG or JPEG data URI.');
        }

        $bytes = base64_decode($matches[2], true);
        if ($bytes === false) {
            throw new InvalidArgumentException('Evidence contains invalid base64 data.');
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
            throw new InvalidArgumentException('Evidence upload is invalid.');
        }

        $realPath = $file->getRealPath();
        if ($realPath === false) {
            throw new RuntimeException('Evidence upload could not be located.');
        }

        $bytes = file_get_contents($realPath);
        if ($bytes === false) {
            throw new RuntimeException('Evidence upload could not be read.');
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
            throw new InvalidArgumentException('Evidence exceeds the configured size limit.');
        }

        $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $extension = match ($detectedMime) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            default => throw new InvalidArgumentException('Evidence must be a valid PNG or JPEG image.'),
        };

        $disk = $this->disk();
        $path = trim($directory, '/').'/'.Str::uuid().'.'.$extension;
        if (! Storage::disk($disk)->put($path, $bytes)) {
            throw new RuntimeException('Evidence could not be stored.');
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
