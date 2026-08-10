@extends('group.layout')
@section('judul', 'Detail Siswa')
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
        <h1 class="mt-4 text-center">Detail Siswa</h1>
        <div class="d-flex justify-content-center">
            @include('layout.partials.entity-avatar', [
                'directory' => 'siswa',
                'filename' => $siswa->foto_siswa,
                'alt' => 'Foto ' . $siswa->nama_siswa,
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
                            {{ $siswa->nis }}
                        </div>
                        <div class="col-sm">
                            {{ $siswa->nama_siswa }}
                        </div>
                        <div class="col-sm">
                            {{ $siswa->nomer_hp }}
                        </div>
                        <div class="col-sm">
                            {{ $siswa->jenis_kelamin }}
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-sm">
                            Tingkat
                        </div>
                        <div class="col-sm">
                            Kelas
                        </div>
                        <div class="col-sm">
                            Jurusan
                        </div>
                        <div class="col-sm-3">
                            Status Siswa
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm">
                            {{ $siswa->tingkatan }}
                        </div>
                        <div class="col-sm">
                            {{ $siswa->nama_kelas }}
                        </div>
                        <div class="col-sm">
                            {{ $siswa->nama_jurusan }}
                        </div>
                        <div class="col-sm-3">
                            {{ $siswa->status_siswa }}
                        </div>
                    </div>

                    <br>

                    <div class="row d-flex justify-content-center w-full text-center">
                        <div class="col-sm-6">
                            Status Jabatan
                        </div>
                    </div>
                    <div class="row d-flex justify-content-center w-full text-center">
                        <div class="col-sm-6">
                            @if ($pengurus)
                                {{ $pengurus->jabatan }} - {{ $siswa->status_jabatan }}
                            @else
                                {{ $siswa->status_jabatan }}
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="mt-3 mb-5">
            <button type="button" id="kembali" class="btn text-decoration-underline text-light fw-bold rounded-3"
                style="background-color: #14C345; width: 150px;">Kembali</button>
                <a href="{{ url('tata-usaha/edit-siswa/'.$siswa->id_siswa) }}" class="btn text-decoration-underline text-light fw-bold rounded-3"
                style="background-color: #F9812A; width: 150px;">Edit siswa</a>
                <button type="button" class="btn btn-danger text-decoration-underline text-light fw-bold rounded-3"
                    style="width: 150px;" data-delete-url="{{ route('tata-usaha.siswa.destroy') }}"
                    data-delete-field="id_siswa" data-delete-id="{{ $siswa->id_siswa }}"
                    data-delete-title="Hapus siswa ini?" data-delete-message="Data siswa dan akun terkait akan dihapus.">Hapus siswa</button>
        </div>
    </div>
@endsection
