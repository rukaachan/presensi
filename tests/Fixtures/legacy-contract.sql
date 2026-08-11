INSERT INTO role_akun (id_role, nama_role) VALUES
 (1, 'Siswa'), (2, 'Wali Kelas'), (3, 'Pengurus Kelas'),
 (4, 'Guru Piket'), (5, 'Guru BK'), (6, 'Tata Usaha');
INSERT INTO akun (id_akun, id_role, username, password) VALUES
 (1, 6, 'administrator.legacy', 'hashed-admin'),
 (2, 1, 'student.legacy', 'hashed-student'),
 (3, 3, 'officer.legacy', 'hashed-officer'),
 (4, 2, 'homeroom.legacy', 'hashed-homeroom'),
 (5, 4, 'duty.legacy', 'hashed-duty'),
 (6, 5, 'counseling.legacy', 'hashed-counseling');
INSERT INTO tata_usaha (id_tata_usaha, id_akun) VALUES (1, 1);
INSERT INTO guru (id_guru, id_akun, nama_guru, foto_guru, pembuat) VALUES
 (1, 4, 'Wali Legacy', 'guru.jpg', 'fixture'),
 (2, 5, 'Piket Legacy', 'guru.jpg', 'fixture'),
 (3, 6, 'BK Legacy', 'guru.jpg', 'fixture');
INSERT INTO guru_piket (id_piket, id_guru) VALUES (1, 2);
INSERT INTO guru_bk (id_bk, id_guru) VALUES (1, 3);
INSERT INTO jurusan (id_jurusan, nama_jurusan, pembuat) VALUES (1, 'IPA', 'fixture');
INSERT INTO kelas (id_kelas, id_wali_kelas, id_jurusan, nama_kelas, tingkatan, status_kelas, pembuat)
 VALUES (1, 1, 1, 'X IPA 1', 'X', 'aktif', 'fixture');
INSERT INTO siswa (id_siswa, id_akun, id_kelas, nis, nama_siswa, nomer_hp, jenis_kelamin, status_siswa, status_jabatan, angkatan, foto_siswa, pembuat)
 VALUES (1, 2, 1, 1001, 'Siswa Legacy', '081234567890', 'laki-laki', 'aktif', 'siswa', 2025, 'siswa.jpg', 'fixture');
INSERT INTO pengurus_kelas (id_pengurus, id_siswa, jabatan, pembuat)
 VALUES (1, 1, 'Pengurus Kelas', 'fixture');
INSERT INTO attendance_sessions (id, code, label, kind, legacy_code, required, active, window_start, window_end, sort_order, settings, created_at, updated_at)
 VALUES
 (1, 'daily_check_in', 'Check-in harian', 'check_in', NULL, 1, 1, '05:00:00', '10:00:00', 10, '{}', '2026-02-01 00:00:00', '2026-02-01 00:00:00'),
 (2, 'break_1', 'Istirahat pertama', 'break', 'istirahat_pertama', 0, 1, NULL, NULL, 20, '{}', '2026-02-01 00:00:00', '2026-02-01 00:00:00'),
 (3, 'break_2', 'Istirahat kedua', 'break', 'istirahat_kedua', 0, 1, NULL, NULL, 30, '{}', '2026-02-01 00:00:00', '2026-02-01 00:00:00'),
 (4, 'break_3', 'Istirahat ketiga', 'break', 'istirahat_ketiga', 0, 1, NULL, NULL, 40, '{}', '2026-02-01 00:00:00', '2026-02-01 00:00:00');
INSERT INTO attendance_records
 (id, student_id, attendance_session_id, attendance_date, state, late, captured_at, evidence_disk, evidence_path, evidence_hash, evidence_mime, evidence_bytes, notes, source, created_by, updated_by, idempotency_key, legacy_presensi_id, created_at, updated_at)
 VALUES
 (1, 1, 1, '2026-02-20', 'submitted', 0, '2026-02-20 07:00:00', NULL, NULL, NULL, NULL, NULL, 'old target row', 'legacy', NULL, NULL, 'legacy-presensi:10', 10, '2026-02-20 07:00:00', '2026-02-20 07:00:00');
INSERT INTO attendance_events
 (id, student_id, attendance_session_id, event_date, state, proposed_status, observed_at, notes, source, observed_by, reviewed_by, reviewed_at, idempotency_key, legacy_validasi_id, legacy_presensi_id, created_at, updated_at)
 VALUES
 (1, 1, 2, '2026-02-20', 'submitted', NULL, NULL, 'old target event', 'legacy', NULL, NULL, NULL, 'legacy-validasi:20', 20, 10, '2026-02-20 07:00:00', '2026-02-20 07:00:00');
INSERT INTO audit_events
 (id, actor_id, actor_type, legacy_actor, action, subject_type, subject_id, `before`, `after`, metadata, occurred_at, legacy_log_id, created_at, updated_at)
 VALUES
 (1, NULL, 'legacy', 'fixture', 'create', 'siswa', NULL, NULL, NULL, NULL, '2026-02-20 08:00:00', 30, '2026-02-20 08:00:00', '2026-02-20 08:00:00');
INSERT INTO presensi_siswa (id_presensi, id_siswa, foto_bukti, jam_masuk, tanggal, status_kehadiran, keterangan, created_at, updated_at, pembuat)
 VALUES
 (10, 1, 'migration.png', '07:00:00', '2026-02-20', 'hadir', 'legacy hadir', '2026-02-20 07:00:00', '2026-02-20 07:00:00', 'fixture'),
 (11, 1, 'bukti.png', '07:10:00', '2026-02-21', 'izin', 'legacy izin', '2026-02-21 07:10:00', '2026-02-21 07:10:00', 'fixture'),
 (12, 1, 'bukti.png', '07:20:00', '2026-02-22', 'alpha', 'legacy alpha', '2026-02-22 07:20:00', '2026-02-22 07:20:00', 'fixture');
INSERT INTO validasi (id_validasi, id_pengurus, id_presensi, status_validasi, waktu_validasi)
 VALUES
 (20, 1, 10, 'hadir', 'istirahat_pertama'),
 (21, 1, 10, 'tidak_ada', 'istirahat_kedua'),
 (22, 1, 10, 'pulang', 'istirahat_ketiga');
INSERT INTO surat_keterangan (id_presensi, surat_keterangan) VALUES (11, NULL);
INSERT INTO logs (id_log, tabel, aktor, tanggal, jam, aksi, record, status) VALUES
 (30, 'siswa', 'fixture', '2026-02-20', '08:00:00', 'tambah', '1', 'aktif'),
 (31, 'presensi_siswa', 'fixture', '2026-02-21', '08:00:00', 'hapus', '10', 'tidak_aktif');
