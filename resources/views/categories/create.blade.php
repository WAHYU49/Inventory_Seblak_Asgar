<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Kategori</title>
</head>
<body>

    @include('layouts.navbar')

    <div class="main-content">

    <h1>Tambah Kategori</h1>

    @if($errors->any())
        <ul style="color:red">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('categories.store') }}" method="POST">
        @csrf

        <label>Nama Kategori</label><br>
        <input type="text" name="name" value="{{ old('name') }}" required>

        <br><br>

        <label>Deskripsi</label><br>
        <textarea name="description">{{ old('description') }}</textarea>

        <br><br>

        <button type="submit">Simpan</button>
        <a href="{{ route('categories.index') }}">Kembali</a>
    </form>

</body>
</html>