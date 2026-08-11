<?php

namespace App\Providers;

use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Policies\AttendanceEventPolicy;
use App\Policies\AttendanceRecordPolicy;
use App\Policies\LeaveRequestPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AttendanceRecord::class => AttendanceRecordPolicy::class,
        AttendanceEvent::class => AttendanceEventPolicy::class,
        LeaveRequest::class => LeaveRequestPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
