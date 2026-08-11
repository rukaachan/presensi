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
     * Optional sessions that support event observations.
     *
     * @return Collection<int, AttendanceSession>
     */
    public function validationSessions(): Collection
    {
        return $this->active()
            ->filter(static fn (AttendanceSession $session): bool => ! $session->required
                && in_array($session->kind, ['break', 'check_out', 'special'], true))
            ->values();
    }

    /** @return list<string> */
    public function validationCodes(): array
    {
        return $this->validationSessions()->pluck('code')->values()->all();
    }
}
