<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Tests\Support\CanonicalDatabase;
use Tests\TestCase;

class UpgradeParityTest extends TestCase
{
    use CanonicalDatabase;

    public function test_login_and_role_redirect_use_english_route_contracts(): void
    {
        $this->seedCanonicalDatabase();
        $this->get(route('login'))->assertOk()->assertViewIs('auth.login');
        $this->post(route('login'), ['username' => 'administrator.demo', 'password' => 'password123'])->assertRedirect(route('administration.dashboard'));
        $this->assertAuthenticatedAs($this->account('administrator.demo'));
    }

    public function test_old_indonesian_routes_are_not_registered(): void
    {
        $this->seedCanonicalDatabase();
        $this->get('/tata-usaha/dashboard')->assertNotFound();
        $this->get('/siswa/presensi')->assertNotFound();
        $this->get('/pengurus-kelas/presensi')->assertNotFound();
        $this->get('/guru-piket/presensi')->assertNotFound();
    }

    public function test_static_canonical_routes_are_not_captured_by_identifier_routes(): void
    {
        $this->seedCanonicalDatabase();

        $this->actingAs($this->account('administrator.demo'));
        $this->get(route('administration.classrooms.create'))->assertOk();
        $this->get(route('administration.teachers.create'))->assertOk();
        $this->get(route('administration.class-officers.create'))->assertOk();
        $this->get(route('administration.students.create'))->assertOk();

        $this->actingAs($this->account('counseling.demo'))
            ->get(route('counseling.attendance.pdf'))
            ->assertOk();

        $this->actingAs($this->account('duty.demo'))
            ->get(route('duty-teacher.attendance.pdf'))
            ->assertOk();
    }

    public function test_demo_accounts_are_canonical_and_passwords_are_hashed(): void
    {
        $this->seedCanonicalDatabase();
        $account = $this->account('administrator.demo');
        $this->assertTrue(Hash::check('password123', $account->password));
        $this->assertSame('administrator', $account->role?->code);
    }
}
