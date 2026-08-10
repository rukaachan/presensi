@extends('group.layout')
@section('judul', 'Detail Guru')
<style>
    .block {
        padding: 100px;
        text-align: center;
        border-radius: 20px
    }

    .color-text {
        color: #F9812A;
    }
</style>
@section('isi')
    <img class="" src="{{ asset('img/group_guru.png') }}" width="100%" height="250px" alt="" style="">
    <div class="container">
        <h1 class="mt-4 text-center">Detail Guru</h1>
        <div class="d-flex justify-content-center">
            @include('layout.partials.entity-avatar', [
                'directory' => 'guru',
                'filename' => $guru->foto_guru,
                'alt' => 'Foto ' . $guru->nama_guru,
                'variant' => 'profile',
            ])
        </div>
        <div class="card mt-3  bg-white">
            <div class="card-body">
                <div class="container">

                    <div class="row">
                        <div class="col-sm">
                            Nama Guru
                        </div>
                        <div class="col-sm">
                            Username
                        </div>
                        <div class="col-sm">
                            Status Guru
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm">
                            {{ $guru->nama_guru }}
                        </div>
                        <div class="col-sm">
                            {{ $guru->username }}
                        </div>
                        <div class="col-sm">
                            @if(isset($guruBk))
                                Guru BK
                                @endif
                            @if (isset($guruPiket))
                                Guru Piket
                            @endif
                            @if (isset($kelas))
                                Wali Kelas                                                                
                            @endif
                        </div>
                    </div>

                    @if (isset($kelas[0]))
                        <br>
                        <div class="" style="width:400 !important;">
                            <div class="row d-flex">
                                <div class="col-sm">
                                    Membina Kelas
                                </div>
                            </div>
                            <div class="row d-flex flex-column">
                                @foreach ($kelas as $k)                                
                                <div class="col-sm">
                                    {{ $k->tingkatan }}
                                    {{ $k->nama_jurusan }}
                                    {{ $k->nama_kelas }}
                                    (
                                        {{ $k->status_kelas }}
                                    )
                                </div>
                                <br>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>

            </div>
        </div>
        <div class="mt-3 mb-5">
            <button type="button" id="kembali" class="btn text-decoration-underline text-light fw-bold rounded-3"
                style="background-color: #14C345; width: 150px;">Kembali</button>
            <a href="{{ url('tata-usaha/edit-guru/'.$guru->id_guru) }}" class="btn text-decoration-underline text-light fw-bold rounded-3"
                style="background-color: #F9812A; width: 150px;">Edit guru</a>
            <button type="button" class="btn btn-danger text-decoration-underline text-light fw-bold rounded-3"
                style="width: 150px;" data-delete-url="{{ route('tata-usaha.guru.destroy') }}"
                data-delete-field="id_guru" data-delete-id="{{ $guru->id_guru }}"
                data-delete-title="Hapus guru ini?" data-delete-message="Relasi kelas dan akun guru akan ikut diperbarui.">Hapus guru</button>
        </div>
    </div>
@endsection
