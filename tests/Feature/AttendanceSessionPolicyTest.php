<?php

namespace Tests\Feature;

use App\Domain\Attendance\AttendanceState;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Services\AttendanceSessionCatalog;
use Illuminate\Support\Facades\DB;
use Tests\Support\CanonicalDatabase;
use Tests\TestCase;

class AttendanceSessionPolicyTest extends TestCase
{
    use CanonicalDatabase;

    public function test_hybrid_catalog_has_one_required_check_in_and_optional_sessions(): void
    {
        $this->seedCanonicalDatabase();
        $catalog = app(AttendanceSessionCatalog::class);

        $this->assertSame('daily_check_in', $catalog->required()?->code);
        $this->assertSame(['break_1', 'break_2', 'break_3'], $catalog->validationCodes());
        $this->assertSame(1, $catalog->active()->where('required', true)->count());
        $this->assertSame(3, $catalog->active()->where('required', false)->count());
        $this->assertSame(6, DB::table('attendance_sessions')->count());
    }

    public function test_seeded_optional_events_use_canonical_machine_values(): void
    {
        $this->seedCanonicalDatabase();

        $this->assertSame(0, DB::table('attendance_events')->whereIn('proposed_status', ['hadir', 'izin', 'alpha', 'pulang'])->count());
        $this->assertSame(0, DB::table('attendance_records')->whereIn('state', ['hadir', 'izin', 'alpha'])->count());
        $this->assertGreaterThan(0, AttendanceEvent::query()->where('state', AttendanceState::NEEDS_REVIEW)->count());
        $this->assertGreaterThan(0, AttendanceRecord::query()->where('state', AttendanceState::CONFIRMED)->count());
    }
}
