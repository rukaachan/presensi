<?php

namespace Database\Seeders;

use App\Models\ClassOfficer;
use App\Models\Classroom;
use App\Models\Student;
use Carbon\Carbon;
use Database\Seeders\Support\DemoSeeder;

class StudentSeeder extends DemoSeeder
{
    public function run(): void
    {
        $names = [
            'Aditya Nugraha', 'Alya Maharani', 'Bagas Ramadhan',
            'Bima Prakoso', 'Citra Lestari', 'Daffa Maulana',
            'Dinda Aulia', 'Eka Saputra', 'Fauzan Hakim',
            'Gita Permata', 'Hana Kurnia', 'Ilham Fadillah',
            'Jihan Safitri', 'Kamal Hidayat', 'Laras Wulandari',
            'Miko Firmansyah', 'Nabila Putri', 'Oki Setiawan',
            'Putri Ananda', 'Rafi Akbar', 'Salsa Amelia',
            'Tegar Pratama', 'Ulya Rahma', 'Vino Kurniawan',
        ];

        $academicYear = Carbon::now('Asia/Jakarta')->year;
        $sequence = 0;

        foreach (Classroom::query()->orderBy('id')->get() as $classroom) {
            for ($slot = 1; $slot <= 3; $slot++) {
                $sequence++;
                $isOfficer = $slot === 1;
                $username = match ($sequence) {
                    1 => 'officer.demo',
                    2 => 'student.demo',
                    default => $isOfficer
                        ? sprintf('officer.%03d', $sequence)
                        : sprintf('student.%03d', $sequence),
                };
                $roleCode = $isOfficer ? 'class_officer' : 'student';
                $account = $this->account($username, $roleCode);
                $student = Student::query()->updateOrCreate(
                    ['account_id' => $account->getKey()],
                    [
                        'classroom_id' => $classroom->getKey(),
                        'student_number' => 260000 + $sequence,
                        'name' => $names[$sequence - 1],
                        'phone' => sprintf('08120000%04d', $sequence),
                        'gender' => $sequence % 2 === 0 ? 'female' : 'male',
                        'admission_year' => $academicYear,
                        'status' => 'active',
                        'position' => $isOfficer ? 'class_president' : 'student',
                        'photo_path' => 'student.jpg',
                        'created_by_label' => $this->creator(),
                    ],
                );

                if ($isOfficer) {
                    ClassOfficer::query()->updateOrCreate(
                        ['student_id' => $student->getKey()],
                        [
                            'position' => 'class_officer',
                            'created_by_label' => $this->creator(),
                        ],
                    );
                }
            }
        }
    }
}
