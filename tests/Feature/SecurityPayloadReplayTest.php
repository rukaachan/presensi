<?php

namespace Tests\Feature;

use Tests\Support\CanonicalDatabase;
use Tests\TestCase;

class SecurityPayloadReplayTest extends TestCase
{
    use CanonicalDatabase;

    public function test_student_search_treats_injection_payload_as_text(): void
    {
        $this->seedCanonicalDatabase();
        $payload = "' OR 1=1 --";

        $this->actingAs($this->account('administrator.demo'))
            ->get(route('administration.students.index', ['keyword' => $payload]))
            ->assertOk()
            ->assertViewIs('administration.students')
            ->assertViewHas('students', static fn ($students): bool => $students->total() === 0)
            ->assertDontSee('SQLSTATE');
    }

    public function test_classroom_and_attendance_filters_do_not_accept_raw_sql(): void
    {
        $this->seedCanonicalDatabase();
        $payload = '1 OR 1=1';
        $this->actingAs($this->account('administrator.demo'))->get(route('administration.classrooms.index', ['keyword' => $payload]))->assertOk()->assertDontSee('SQLSTATE');
        $this->actingAs($this->account('administrator.demo'))->get(route('administration.attendance.index', ['keyword' => $payload]))->assertOk()->assertDontSee('SQLSTATE');
    }
}
