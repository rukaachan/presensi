@extends('group.layout')
@section('judul', 'Detail Presensi')
<style>
    .block {
        padding: 100px;
        text-align: center;
        border-radius: 20px
    }

    .color-text {
        color: #F9812A;
    }
    /* .gambar{
        height: auto;
        width: auto;        
    } */
</style>
@section('isi')
    <img src="{{ asset('img/group_siswa.png') }}" width="100%" height="200px" alt="" style="object-fit: fill;">
    <div class="container">
        <div class="d-flex justify-content-center">
            @include('layout.partials.entity-avatar', [
                'directory' => 'siswa',
                'filename' => $presensi->foto_siswa,
                'alt' => 'Foto ' . $presensi->nama_siswa,
                'variant' => 'profile',
            ])
        </div>
        <div class="card mt-3  bg-white">
            <div class="card-body">
                <div class="container">

                    <div class="row">
                        <div class="col-sm">
                            NIS
                        </div>
                        <div class="col-sm">
                            Nama Siswa
                        </div>
                        <div class="col-sm">
                            Nomor HP
                        </div>
                        <div class="col-sm">
                            Jenis Kelamin
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm">
                            {{ $presensi->nis }}
                        </div>
                        <div class="col-sm">
                            {{ $presensi->nama_siswa }}
                        </div>
                        <div class="col-sm">
                            {{ $presensi->nomer_hp }}
                        </div>
                        <div class="col-sm">
                            {{ $presensi->jenis_kelamin }}
                        </div>
                    </div>
                    
                    <br>

                    <div class="row">
                        <div class="col-sm">
                            Tingkat
                        </div>
                        <div class="col-sm">
                            Kelas
                        </div>
                        <div class="col-sm">
                            Jurusan
                        </div>
                        <div class="col-sm">
                            Status Pengurus
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm">
                            {{ $presensi->tingkatan }}
                        </div>
                        <div class="col-sm">
                            {{ $presensi->nama_kelas }}
                        </div>
                        <div class="col-sm">
                            {{ $presensi->nama_jurusan }}
                        </div>
                        <div class="col-sm">
                            {{ $presensi->status_siswa }}
                        </div>
                    </div>

                    <br>
                    <div class="row d-flex justify-content-center w-full text-center">
                        <div class="col-sm">
                            Status Jabatan
                        </div>
                    </div>

                    <div class="row d-flex justify-content-center w-full text-center">
                        <div class="col-sm">
                            {{ $presensi->jabatan }} - {{ $presensi->status_jabatan }}
                        </div>
                    </div>

                    <br>
                    <div class="row">
                        <div class="col-sm">
                            Tanggal
                        </div>
                        <div class="col-sm">
                            Jam Masuk
                        </div>
                        <div class="col-sm">
                            Status kehadiran
                        </div>
                        <div class="col-sm">
                            Keterangan
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm">
                            {{ $presensi->tanggal }}
                        </div>
                        <div class="col-sm">
                            {{ $presensi->jam_masuk }}
                        </div>
                        <div class="col-sm">
                            {{ $presensi->status_kehadiran }}
                        </div>
                        <div class="col-sm">
                            {{ $presensi->keterangan }}
                        </div>
                    </div>

                    <br>
                    <div class="row">
                        <div class="col-sm">
                            Bukti Kehadiran
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm">
                            @if ($presensi->foto_bukti && file_exists(public_path('presensi_bukti/' . $presensi->foto_bukti)))
                                <img src="{{ asset('presensi_bukti/' . $presensi->foto_bukti) }}" class="evidence-preview" alt="Bukti presensi {{ $presensi->nama_siswa }}">
                            @else
                                <span class="evidence-placeholder"><i class="ph-bold ph-image-square" aria-hidden="true"></i> Bukti tidak tersedia</span>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="mt-3 mb-5">
            <button type="button" id="kembali" class="btn text-decoration-underline text-light fw-bold rounded-3"
                style="background-color: #14C345; width: 150px;">Kembali</button>
            <a href="{{ url('guru-piket/edit-presensi/'.$presensi->id_presensi) }}" class="btn text-decoration-underline text-light fw-bold rounded-3"
                style="background-color: #F9812A; width: 200px;">EDIT PRESENSI</a>
        </div>
    </div>
@endsection
