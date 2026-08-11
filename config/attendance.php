<?php

return [
    'timezone' => env('ATTENDANCE_TIMEZONE', env('APP_TIMEZONE', 'Asia/Jakarta')),

    'required_session_code' => 'daily_check_in',

    'evidence_disk' => env('ATTENDANCE_EVIDENCE_DISK', 'local'),
    'max_evidence_bytes' => (int) env('ATTENDANCE_MAX_EVIDENCE_BYTES', 2097152),

    'retention' => [
        'attendance_days' => (int) env('ATTENDANCE_RETENTION_DAYS', 1825),
        'evidence_days' => (int) env('ATTENDANCE_EVIDENCE_RETENTION_DAYS', 90),
        'leave_attachment_days' => (int) env('ATTENDANCE_LEAVE_RETENTION_DAYS', 365),
        'audit_days' => (int) env('ATTENDANCE_AUDIT_RETENTION_DAYS', 730),
        'notification_days' => (int) env('ATTENDANCE_NOTIFICATION_RETENTION_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default attendance session catalog
    |--------------------------------------------------------------------------
    |
    | The daily check-in is always required. Other session events are optional
    | and can be activated or scheduled without changing the attendance route
    | contract.
    |
    */
    'sessions' => [
        [
            'code' => 'daily_check_in',
            'label' => 'attendance.sessions.daily_check_in',
            'kind' => 'check_in',
            'required' => true,
            'active' => true,
            'window_start' => '05:00:00',
            'window_end' => '10:00:00',
            'sort_order' => 10,
            'settings' => ['evidence' => 'photo'],
        ],
        [
            'code' => 'break_1',
            'label' => 'attendance.sessions.break_1',
            'kind' => 'break',
            'required' => false,
            'active' => true,
            'window_start' => null,
            'window_end' => null,
            'sort_order' => 20,
            'settings' => ['evidence' => 'optional'],
        ],
        [
            'code' => 'break_2',
            'label' => 'attendance.sessions.break_2',
            'kind' => 'break',
            'required' => false,
            'active' => true,
            'window_start' => null,
            'window_end' => null,
            'sort_order' => 30,
            'settings' => ['evidence' => 'optional'],
        ],
        [
            'code' => 'break_3',
            'label' => 'attendance.sessions.break_3',
            'kind' => 'break',
            'required' => false,
            'active' => true,
            'window_start' => null,
            'window_end' => null,
            'sort_order' => 40,
            'settings' => ['evidence' => 'optional'],
        ],
        [
            'code' => 'check_out',
            'label' => 'attendance.sessions.check_out',
            'kind' => 'check_out',
            'required' => false,
            'active' => false,
            'window_start' => '14:00:00',
            'window_end' => '18:00:00',
            'sort_order' => 50,
            'settings' => ['evidence' => 'optional'],
        ],
        [
            'code' => 'special',
            'label' => 'attendance.sessions.special',
            'kind' => 'special',
            'required' => false,
            'active' => false,
            'window_start' => null,
            'window_end' => null,
            'sort_order' => 60,
            'settings' => ['evidence' => 'optional'],
        ],
    ],
];
