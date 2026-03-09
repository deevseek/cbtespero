@extends('layouts.app')
@section('content')
<div class="bg-white rounded-xl p-6 shadow max-w-2xl">
    <h2 class="text-xl font-semibold mb-3">Konfigurasi</h2>
    <form method="post" action="{{ route('admin.config.save') }}" class="space-y-2">@csrf
        <input name="nama_aplikasi" value="{{ $setting->nama_aplikasi }}" class="w-full border rounded-xl p-2" placeholder="Nama aplikasi">
        <input name="nama_sekolah" value="{{ $setting->nama_sekolah }}" class="w-full border rounded-xl p-2" placeholder="Nama sekolah">
        <input name="alamat_sekolah" value="{{ $setting->alamat_sekolah }}" class="w-full border rounded-xl p-2" placeholder="Alamat sekolah">
        <input name="tahun_ajaran" value="{{ $setting->tahun_ajaran }}" class="w-full border rounded-xl p-2" placeholder="Tahun ajaran">
        <label><input type="checkbox" name="acak_soal" value="1" @checked($setting->acak_soal)> Acak soal</label><br>
        <label><input type="checkbox" name="acak_jawaban" value="1" @checked($setting->acak_jawaban)> Acak jawaban</label><br>
        <label><input type="checkbox" name="tampilkan_nilai_setelah_ujian" value="1" @checked($setting->tampilkan_nilai_setelah_ujian)> Tampilkan nilai setelah ujian</label><br>
        <button class="bg-blue-600 text-white rounded-xl px-4 py-2">Simpan</button>
    </form>
</div>
@endsection
