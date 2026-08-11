<?php

namespace App\Services;

use App\Models\AttendanceSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AttendanceSessionCatalog
{
    private ?Collection $activeSessions = null;

    /**
     * Return active sessions in their operational order.
     *
     * @return Collection<int, AttendanceSession>
     */
    public function active(): Collection
    {
        if ($this->activeSessions !== null) {
            return $this->activeSessions;
        }

        if (! Schema::hasTable('attendance_sessions')) {
            return $this->activeSessions = collect();
        }

        return $this->activeSessions = AttendanceSession::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function required(): ?AttendanceSession
    {
        $requiredCode = (string) config('attendance.required_session_code', 'daily_check_in');

        return $this->active()->firstWhere('code', $requiredCode)
            ?? $this->active()->firstWhere('required', true);
    }

    /**
     * Sessions that can be used by the existing validation route.
     *
     * Legacy validation rows use `legacy_code`; new session kinds without a
     * legacy code remain available to the future event workflow.
     *
     * @return Collection<int, AttendanceSession>
     */
    public function validationSessions(): Collection
    {
        return $this->active()
            ->filter(static fn (AttendanceSession $session): bool => $session->legacy_code !== null
                && in_array($session->kind, ['break', 'check_out', 'special'], true))
            ->values();
    }

    /**
     * @return list<string>
     */
    public function validationCodes(): array
    {
        return $this->validationSessions()
            ->pluck('legacy_code')
            ->filter(static fn (mixed $code): bool => is_string($code) && $code !== '')
            ->values()
            ->all();
    }
}
