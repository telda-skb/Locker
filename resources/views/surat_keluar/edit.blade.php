@extends('layouts.app')
@section('title', 'Surat Keluar')
@section('content')
<div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Edit Surat Keluar</h1>
            <p class="mt-1 text-sm text-slate-500">Perbarui data surat keluar yang dipilih.</p>
        </div>
        <a href="{{ route('surat-keluar.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali</a>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="mb-2 font-semibold">Periksa kembali data berikut:</p>
            <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('surat-keluar.update', $suratKeluar) }}" method="POST" enctype="multipart/form-data" class="space-y-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="nomor_surat" class="mb-1.5 block text-sm font-semibold text-slate-700">Nomor Surat</label>
                <input id="nomor_surat" name="nomor_surat" value="{{ old('nomor_surat', $suratKeluar->nomor_surat) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" required>
                @error('nomor_surat')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="tanggal_surat" class="mb-1.5 block text-sm font-semibold text-slate-700">Tanggal Surat</label>
                <input type="date" id="tanggal_surat" name="tanggal_surat" value="{{ old('tanggal_surat', optional($suratKeluar->tanggal_surat)->format('Y-m-d') ?? $suratKeluar->tanggal_surat) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                @error('tanggal_surat')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="pengirim" class="mb-1.5 block text-sm font-semibold text-slate-700">Tujuan Surat</label>
                <input id="pengirim" name="pengirim" value="{{ old('pengirim', $suratKeluar->pengirim) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                @error('pengirim')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="kategori_surat_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Kategori Surat</label>
                <select id="kategori_surat_id" name="kategori_surat_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">Pilih kategori</option>
                    @foreach ($kategoris as $kategori)
                        <option value="{{ $kategori->id }}" @selected((string) old('kategori_surat_id', $suratKeluar->kategori_surat_id) === (string) $kategori->id)>{{ $kategori->nama_kategori }}</option>
                    @endforeach
                </select>
                @error('kategori_surat_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label for="perihal" class="mb-1.5 block text-sm font-semibold text-slate-700">Perihal</label>
                <input id="perihal" name="perihal" value="{{ old('perihal', $suratKeluar->perihal) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" required>
                @error('perihal')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label for="ringkasan" class="mb-1.5 block text-sm font-semibold text-slate-700">Ringkasan</label>
                <textarea id="ringkasan" name="ringkasan" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('ringkasan', $suratKeluar->ringkasan) }}</textarea>
                @error('ringkasan')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="penandatangan_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Penandatangan</label>
                <select id="penandatangan_id" name="penandatangan_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">Pilih penandatangan</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('penandatangan_id', $suratKeluar->penandatangan_id) === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('penandatangan_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Status</label>
                @php($currentStatus = old('status', $suratKeluar->status ?? 'draft'))
                <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" required>
                    @foreach (['draft' => 'Draft', 'diproses' => 'Diproses', 'disetujui' => 'Disetujui', 'dikirim' => 'Dikirim', 'diarsipkan' => 'Diarsipkan'] as $value => $label)
                        <option value="{{ $value }}" @selected($currentStatus === $value || ($currentStatus === 'draf' && $value === 'draft'))>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <p class="mb-2 text-sm font-semibold text-slate-700">Lampiran</p>
                @if (!empty($suratKeluar->lampiran_file))
                    <p class="mb-3 text-sm text-slate-600">Lampiran saat ini tersedia. <a href="{{ route('surat-keluar.preview-lampiran', $suratKeluar) }}" target="_blank" rel="noopener" class="font-semibold text-blue-600 hover:underline">Lihat lampiran</a></p>
                @else
                    <p class="mb-3 text-sm text-slate-500">Belum ada lampiran.</p>
                @endif
                <input type="file" id="lampiran_file" name="lampiran_file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                <p class="mt-1 text-xs text-slate-500">Kosongkan jika tidak ingin mengganti lampiran.</p>
                @error('lampiran_file')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('surat-keluar.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
            <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
