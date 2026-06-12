@extends('layouts.app')
@section('title', 'Kunci Jawaban')
@section('page-title', 'Kunci Jawaban')
@section('content')
<div class="page-header"><h1>Kunci Jawaban</h1><p>{{ $ujian->nama_ujian }}</p></div>

{{-- Import dari file --}}
<div class="card mb-3"><div class="card-header">Import Kunci Jawaban</div><div class="card-body">
    <form action="{{ route('kunci-jawaban.import', $ujian) }}" method="POST" enctype="multipart/form-data" class="d-flex gap-1 flex-wrap align-center">
        @csrf
        <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls" style="max-width:300px" required>
        <button type="submit" class="btn btn-outline"><i class="bi bi-upload"></i> Import</button>
        <a href="{{ route('kunci-jawaban.template', ['ujian' => $ujian, 'filename' => 'template_kunci_jawaban_' . str_replace(' ', '_', strtolower($ujian->nama_ujian)) . '.xlsx']) }}" class="btn btn-outline"><i class="bi bi-download"></i> Download Template</a>
    </form>
</div></div>

{{-- Form Kunci Manual --}}
<div class="card"><div class="card-header">Input Manual</div><div class="card-body">
    <form action="{{ route('kunci-jawaban.store', $ujian) }}" method="POST">
        @csrf
        <div class="table-responsive"><table class="table">
            <thead><tr><th style="width:70px">No</th><th>Kunci Jawaban</th><th style="width:100px">Bobot</th></tr></thead>
            <tbody>
                @foreach($ujian->soal as $soal)
                <tr>
                    <td>{{ $soal->nomor_soal }}</td>
                    <td>
                        <div style="display:flex;gap:8px">
                            @foreach(['A','B','C','D','E'] as $opt)
                            <label style="display:flex;align-items:center;gap:3px;cursor:pointer">
                                <input type="radio" name="kunci[{{ $soal->id }}]" value="{{ $opt }}" {{ $soal->kunci_jawaban === $opt ? 'checked' : '' }}>
                                {{ $opt }}
                            </label>
                            @endforeach
                            <label style="display:flex;align-items:center;gap:3px;cursor:pointer;color:var(--text-muted)">
                                <input type="radio" name="kunci[{{ $soal->id }}]" value="" {{ !$soal->kunci_jawaban ? 'checked' : '' }}>
                                Kosong
                            </label>
                        </div>
                    </td>
                    <td><input type="number" name="bobot[{{ $soal->id }}]" class="form-control" value="{{ $soal->bobot ?? 1 }}" min="0" step="0.01" style="width:80px"></td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
        <div class="btn-group mt-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Kunci Jawaban</button>
            <a href="{{ route('ujian.show', $ujian) }}" class="btn btn-outline">Kembali</a>
        </div>
    </form>
</div></div>
@endsection
