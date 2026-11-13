
@extends('layouts.app')

@section('title', 'Edit Kategori - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Edit Kategori Menu</h1>
    <p>Perbarui informasi kategori menu.</p>
</div>
<p><a href="{{ route('categories.index') }}" class="btn btn-secondary">← Kembali ke Kategori</a></p>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Periksa kembali isian berikut:</strong>
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="content-card">
    <form action="{{ route('categories.update', $category->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="name">Nama Kategori</label>
            <input id="name" type="text" name="name"
                value="{{ old('name', $category->name) }}" required>
            @error('name') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="4">{{ old('description', $category->description) }}</textarea>
            @error('description') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="{{ route('categories.index') }}" class="btn btn-secondary">Batal</a>
    </form>
</div>

@endsection
