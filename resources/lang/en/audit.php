<?php

return [
    'append_only' => 'Audit events are append-only and cannot be deleted.',
    'actions' => [
        'seeded' => 'Fixture data created',
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'legacy_event' => 'Legacy event',
        'attendance' => [
            'submitted' => 'Attendance submitted',
            'event_submitted' => 'Attendance event submitted',
            'event_suggested' => 'Attendance event suggested',
            'state_changed' => 'Attendance state changed',
            'event_state_changed' => 'Attendance event state changed',
            'corrected' => 'Attendance corrected',
            'excused' => 'Attendance excused',
        ],
        'leave' => [
            'submitted' => 'Leave request submitted',
            'decided' => 'Leave request decided',
        ],
    ],
    'actors' => [
        'account' => 'Account',
        'system' => 'System',
        'migration' => 'Migration',
    ],
    'subjects' => [
        'account' => 'Account',
        'student' => 'Student',
        'teacher' => 'Teacher',
        'classroom' => 'Classroom',
        'department' => 'Department',
        'class_officer' => 'Class officer',
        'attendance_record' => 'Attendance record',
        'attendance_event' => 'Attendance event',
        'leave_request' => 'Leave request',
        'audit_event' => 'Audit event',
        'fixture' => 'Fixture data',
        'system_event' => 'System event',
    ],
];
