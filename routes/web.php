<?php

use App\Http\Controllers\GuruBkController;
use App\Http\Controllers\GuruPiketController;
use App\Http\Controllers\OtentikasiController;
use App\Http\Controllers\PengurusKelasController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TataUsaha\GuruController as TataUsahaGuruController;
use App\Http\Controllers\TataUsaha\JurusanController as TataUsahaJurusanController;
use App\Http\Controllers\TataUsaha\KelasController as TataUsahaKelasController;
use App\Http\Controllers\TataUsaha\LogController as TataUsahaLogController;
use App\Http\Controllers\TataUsaha\PengurusController as TataUsahaPengurusController;
use App\Http\Controllers\TataUsaha\PresensiController as TataUsahaPresensiController;
use App\Http\Controllers\TataUsaha\SiswaController as TataUsahaSiswaController;
use App\Http\Controllers\TataUsahaController;
use App\Http\Controllers\WaliKelasController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [OtentikasiController::class, 'index'])->name('login');
Route::post('/', [OtentikasiController::class, 'authenticated']);
Route::post('/logout', [OtentikasiController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {

    Route::prefix('tata-usaha')->middleware('akses:6')->group(function () {
        // DASHBOARD
        Route::get('dashboard', [TataUsahaController::class, 'index'])->name('tata-usaha.dashboard');

        // Akun Guru
        Route::get('jurusan', [TataUsahaJurusanController::class, 'showJurusan'])->name('tata-usaha.jurusan.index');
        Route::get('tambah-jurusan', [TataUsahaJurusanController::class, 'createJurusan'])->name('tata-usaha.jurusan.create');
        Route::post('simpan-jurusan', [TataUsahaJurusanController::class, 'storeJurusan'])->name('tata-usaha.jurusan.store');
        Route::get('edit-jurusan/{id}', [TataUsahaJurusanController::class, 'editJurusan'])->name('tata-usaha.jurusan.edit');
        Route::post('edit-jurusan/update', [TataUsahaJurusanController::class, 'updateJurusan'])->name('tata-usaha.jurusan.update');
        Route::delete('hapus-jurusan', [TataUsahaJurusanController::class, 'destroyJurusan'])->name('tata-usaha.jurusan.destroy');

        Route::get('kelas', [TataUsahaKelasController::class, 'showKelas'])->name('tata-usaha.kelas.index');
        Route::get('detail-kelas/{id}', [TataUsahaKelasController::class, 'detailKelas'])->name('tata-usaha.kelas.detail');
        Route::get('tambah-kelas', [TataUsahaKelasController::class, 'createKelas'])->name('tata-usaha.kelas.create');
        Route::post('simpan-kelas', [TataUsahaKelasController::class, 'storeKelas'])->name('tata-usaha.kelas.store');
        Route::get('edit-kelas/{id}', [TataUsahaKelasController::class, 'editKelas'])->name('tata-usaha.kelas.edit');
        Route::post('edit-kelas/update', [TataUsahaKelasController::class, 'updateKelas'])->name('tata-usaha.kelas.update');
        Route::delete('hapus-kelas', [TataUsahaKelasController::class, 'destroyKelas'])->name('tata-usaha.kelas.destroy');

        // Akun Guru
        Route::get('akun-guru', [TataUsahaGuruController::class, 'showGuru'])->name('tata-usaha.guru.index');
        Route::get('detail-guru/{id}', [TataUsahaGuruController::class, 'detailGuru'])->name('tata-usaha.guru.detail');
        Route::get('tambah-guru', [TataUsahaGuruController::class, 'createGuru'])->name('tata-usaha.guru.create');
        Route::post('simpan-guru', [TataUsahaGuruController::class, 'storeGuru'])->name('tata-usaha.guru.store');
        Route::get('edit-guru/{id}', [TataUsahaGuruController::class, 'editGuru'])->name('tata-usaha.guru.edit');
        Route::post('edit-guru/update', [TataUsahaGuruController::class, 'updateGuru'])->name('tata-usaha.guru.update');
        Route::delete('hapus-guru', [TataUsahaGuruController::class, 'destroyGuru'])->name('tata-usaha.guru.destroy');

        // PENGURUS KELAS
        Route::get('akun-pengurus-kelas', [TataUsahaPengurusController::class, 'showPengurus'])->name('tata-usaha.pengurus-kelas.index');
        Route::get('detail-pengurus-kelas/{id}', [TataUsahaPengurusController::class, 'detailPengurus'])->name('tata-usaha.pengurus-kelas.detail');
        Route::get('tambah-pengurus-kelas', [TataUsahaPengurusController::class, 'createPengurus'])->name('tata-usaha.pengurus-kelas.create');
        Route::post('simpan-pengurus-kelas', [TataUsahaPengurusController::class, 'storePengurus'])->name('tata-usaha.pengurus-kelas.store');
        Route::get('edit-pengurus-kelas/{id}', [TataUsahaPengurusController::class, 'editPengurus'])->name('tata-usaha.pengurus-kelas.edit');
        Route::post('edit-pengurus-kelas/update', [TataUsahaPengurusController::class, 'updatePengurus'])->name('tata-usaha.pengurus-kelas.update');
        Route::delete('hapus-pengurus-kelas', [TataUsahaPengurusController::class, 'destroyPengurus'])->name('tata-usaha.pengurus-kelas.destroy');

        // AKUN SISWA
        Route::get('akun-siswa', [TataUsahaSiswaController::class, 'showSiswa'])->name('tata-usaha.siswa.index');
        Route::get('detail-siswa/{id}', [TataUsahaSiswaController::class, 'detailSiswa'])->name('tata-usaha.siswa.detail');
        Route::get('tambah-siswa', [TataUsahaSiswaController::class, 'createSiswa'])->name('tata-usaha.siswa.create');
        Route::post('simpan-siswa', [TataUsahaSiswaController::class, 'storeSiswa'])->name('tata-usaha.siswa.store');
        Route::get('edit-siswa/{id}', [TataUsahaSiswaController::class, 'editSiswa'])->name('tata-usaha.siswa.edit');
        Route::post('edit-siswa/update', [TataUsahaSiswaController::class, 'updateSiswa'])->name('tata-usaha.siswa.update');
        Route::delete('hapus-siswa', [TataUsahaSiswaController::class, 'destroySiswa'])->name('tata-usaha.siswa.destroy');

        // PRESENSI
        Route::get('presensi', [TataUsahaPresensiController::class, 'showPresensi'])->name('tata-usaha.presensi.index');
        Route::get('presensi-pdf', [TataUsahaPresensiController::class, 'exportPresensi'])->name('tata-usaha.presensi.pdf');
        // LOGS
        Route::get('logs', [TataUsahaLogController::class, 'logs'])->name('tata-usaha.logs.index');
        Route::post('hapus-logs', [TataUsahaLogController::class, 'deleteLogs'])->name('tata-usaha.logs.delete');
    });

    // GURU BK
    Route::prefix('guru-bk')->middleware('akses:5')->group(function () {
        Route::get('dashboard', [GuruBkController::class, 'index'])->name('guru-bk.dashboard');
        Route::get('detail-profil/{id}', [GuruBKController::class, 'detailProfil'])->name('guru-bk.profil.detail');
        Route::get('detail-presensi/{id}', [GuruBkController::class, 'detailPresensi'])->name('guru-bk.presensi.detail');
        Route::get('presensi', [GuruBkController::class, 'showPresensi'])->name('guru-bk.presensi.index');
        Route::get('presensi-pdf', [GuruBkController::class, 'exportPresensi'])->name('guru-bk.presensi.pdf');
    });

    // GURU PIKET
    Route::prefix('guru-piket')->middleware('akses:4')->group(function () {
        Route::get('dashboard', [GuruPiketController::class, 'index'])->name('guru-piket.dashboard');
        Route::get('detail-profil/{id}', [GuruPiketController::class, 'detailProfil'])->name('guru-piket.profil.detail');
        Route::get('akun-pengurus-kelas', [GuruPiketController::class, 'showPengurus'])->name('guru-piket.pengurus-kelas.index');
        Route::get('detail-pengurus-kelas/{id}', [GuruPiketController::class, 'detailPengurus'])->name('guru-piket.pengurus-kelas.detail');
        Route::get('presensi', [GuruPiketController::class, 'showPresensi'])->name('guru-piket.presensi.index');
        Route::get('detail-presensi/{id}', [GuruPiketController::class, 'detailPresensi'])->name('guru-piket.presensi.detail');
        Route::get('edit-presensi/{id}', [GuruPiketController::class, 'editPresensi'])->name('guru-piket.presensi.edit');
        Route::post('edit-presensi/update', [GuruPiketController::class, 'updatePresensi'])->name('guru-piket.presensi.update');
        Route::get('presensi-pdf', [GuruPiketController::class, 'exportPresensi'])->name('guru-piket.presensi.pdf');
    });

    // PENGURUS KELAS
    Route::prefix('pengurus-kelas')->middleware('akses:3')->group(function () {
        // DASHBOARD
        Route::get('dashboard', [PengurusKelasController::class, 'index'])->name('pengurus-kelas.dashboard');

        Route::get('detail-profil/{id}', [PengurusKelasController::class, 'detailProfil'])->name('pengurus-kelas.profil.detail');

        Route::get('histori', [PengurusKelasController::class, 'showHistori'])->name('pengurus-kelas.histori.index');

        // PRESENSI
        Route::get('/presensi', [PengurusKelasController::class, 'openCam'])->name('pengurus-kelas.presensi.index');
        Route::post('webcam', [PengurusKelasController::class, 'store'])->name('pengurus-kelas.webcam.capture');
        Route::post('/webcam/check_snapshot', [PengurusKelasController::class, 'checkSnapshot'])->name('pengurus-kelas.webcam.check_snapshot');

        // VALIDASI
        Route::get('kelas', [PengurusKelasController::class, 'showKelas'])->name('pengurus-kelas.kelas.index');
        Route::post('update-validasi', [PengurusKelasController::class, 'updateValidasi'])->name('pengurus-kelas.kelas.validasi.update');

        Route::get('presensi-pdf', [PengurusKelasController::class, 'exportPresensi'])->name('pengurus-kelas.presensi.pdf');
        Route::get('kelas-pdf', [PengurusKelasController::class, 'exportKelas'])->name('pengurus-kelas.kelas.pdf');
    });

    // WALI KELAS
    Route::prefix('wali-kelas')->middleware('akses:2')->group(function () {
        // DASHBOARD
        Route::get('dashboard', [WaliKelasController::class, 'index'])->name('wali-kelas.dashboard');
        Route::get('detail-profil/{id}', [WaliKelasController::class, 'detailProfil'])->name('wali-kelas.profil.detail');
        // AKUN SISWA
        Route::get('akun-siswa', [WaliKelasController::class, 'showSiswa'])->name('wali-kelas.siswa.index');
        Route::get('detail-siswa/{id}', [WaliKelasController::class, 'detailSiswa'])->name('wali-kelas.siswa.detail');
        Route::get('edit-siswa/{id}', [WaliKelasController::class, 'editSiswa'])->name('wali-kelas.siswa.edit');
        Route::post('edit-siswa/simpan', [WaliKelasController::class, 'updateSiswa'])->name('wali-kelas.siswa.update');

        // PENGURUS KELAS
        Route::get('akun-pengurus-kelas', [WaliKelasController::class, 'showPengurus'])->name('wali-kelas.pengurus-kelas.index');
        Route::get('tambah-pengurus-kelas', [WaliKelasController::class, 'createPengurus'])->name('wali-kelas.pengurus-kelas.create');
        Route::post('simpan-pengurus-kelas', [WaliKelasController::class, 'storePengurus'])->name('wali-kelas.pengurus-kelas.store');
        Route::get('detail-kelas/{id}', [WaliKelasController::class, 'detailKelasPengurus'])->name('wali-kelas.pengurus-kelas.detail-kelas');
        Route::get('detail-siswa-pengurus/{id}', [WaliKelasController::class, 'detailSiswa'])->name('wali-kelas.pengurus-kelas.detail-siswa');
        Route::get('edit-pengurus-kelas/{id}', [WaliKelasController::class, 'editPengurus'])->name('wali-kelas.pengurus-kelas.edit');
        Route::post('edit-pengurus-kelas/update', [WaliKelasController::class, 'updatePengurus'])->name('wali-kelas.pengurus-kelas.update');
        Route::delete('hapus-pengurus-kelas', [WaliKelasController::class, 'destroyPengurus'])->name('wali-kelas.pengurus-kelas.destroy');

        // PRESENSI SISWA
        Route::get('presensi-siswa', [WaliKelasController::class, 'showPresensi'])->name('wali-kelas.presensi-siswa.index');
        Route::get('edit-presensi-siswa/{id}', [WaliKelasController::class, 'editPresensi'])->name('wali-kelas.presensi-siswa.edit');
        Route::post('edit-presensi-siswa/update', [WaliKelasController::class, 'updatePresensi'])->name('wali-kelas.presensi-siswa.update');
        Route::get('presensi-pdf', [WaliKelasController::class, 'exportPresensi'])->name('wali-kelas.presensi-siswa.pdf');

        // LOGS
        Route::get('logs', [WaliKelasController::class, 'logs'])->name('wali-kelas.logs.index');
    });

    // SISWA
    Route::prefix('siswa')->middleware('akses:1')->group(function () {
        Route::get('dashboard', [SiswaController::class, 'index'])->name('siswa.dashboard');
        Route::get('detail-profil/{id}', [SiswaController::class, 'detailProfil'])->name('siswa.profil.detail');
        Route::get('histori', [SiswaController::class, 'showHistori'])->name('siswa.histori.index');

        // PRESENSI
        Route::get('/presensi', [SiswaController::class, 'openCam'])->name('siswa.presensi.index');
        Route::post('webcam', [SiswaController::class, 'store'])->name('siswa.webcam.capture');
        Route::post('/webcam/check_snapshot', [SiswaController::class, 'checkSnapshot'])->name('siswa.webcam.check_snapshot');
        Route::get('presensi-pdf', [SiswaController::class, 'exportPresensi'])->name('siswa.presensi.pdf');

    });
});
