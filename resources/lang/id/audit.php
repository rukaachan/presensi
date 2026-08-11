<?php

return [
    'append_only' => 'Peristiwa audit hanya dapat ditambahkan dan tidak dapat dihapus.',
    'actions' => [
        'seeded' => 'Data contoh dibuat',
        'created' => 'Dibuat',
        'updated' => 'Diperbarui',
        'deleted' => 'Dihapus',
        'legacy_event' => 'Peristiwa lama',
        'attendance' => [
            'submitted' => 'Presensi dikirim',
            'event_submitted' => 'Peristiwa presensi dikirim',
            'event_suggested' => 'Usulan peristiwa presensi dikirim',
            'state_changed' => 'Status presensi diubah',
            'event_state_changed' => 'Status peristiwa presensi diubah',
            'corrected' => 'Presensi dikoreksi',
            'excused' => 'Presensi diberi pengecualian',
        ],
        'leave' => [
            'submitted' => 'Permohonan izin dikirim',
            'decided' => 'Permohonan izin diputuskan',
        ],
    ],
    'actors' => [
        'account' => 'Akun',
        'system' => 'Sistem',
        'migration' => 'Migrasi',
    ],
    'subjects' => [
        'account' => 'Akun',
        'student' => 'Siswa',
        'teacher' => 'Guru',
        'classroom' => 'Kelas',
        'department' => 'Jurusan',
        'class_officer' => 'Pengurus kelas',
        'attendance_record' => 'Catatan presensi',
        'attendance_event' => 'Peristiwa presensi',
        'leave_request' => 'Permohonan izin',
        'audit_event' => 'Peristiwa audit',
        'fixture' => 'Data contoh',
        'system_event' => 'Peristiwa sistem',
    ],
];
