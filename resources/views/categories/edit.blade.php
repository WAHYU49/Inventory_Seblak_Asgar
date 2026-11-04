
@extends('layouts.app')

@section('title', 'Edit Kategori - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Edit Kategori Menu</h1>
    <p>Perbarui informasi kategori menu.</p>
</div>

<p>
    <a href="{{ route('categories.index') }}">← Kembali ke Kategori</a>
</p>

@if ($errors->any())
    <div style="color: red;">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form action="{{ route('categories.update', $category->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div style="margin-bottom: 15px;">
        <label>Nama Kategori</label><br>
        <input type="text"
               name="name"
               value="{{ old('name', $category->name) }}"
               required>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Deskripsi</label><br>
        <textarea name="description"
                  rows="4">{{ old('description', $category->description) }}</textarea>
    </div>

    <button type="submit">Simpan Perubahan</button>
</form>

@endsection
