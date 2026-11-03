<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Kategori</title>
</head>
<body>

    @include('layouts.navbar')

    <div class="main-content">

    <h1>Edit Kategori</h1>

    @if($errors->any())
        <ul style="color:red">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('categories.update', $category) }}" method="POST">
        @csrf
        @method('PUT')

        <label>Nama Kategori</label><br>
        <input type="text" name="name"
               value="{{ old('name', $category->name) }}" required>

        <br><br>

        <label>Deskripsi</label><br>
        <textarea name="description">{{ old('description', $category->description) }}</textarea>

        <br><br>

        <button type="submit">Update</button>
        <a href="{{ route('categories.index') }}">Kembali</a>
    </form>
    </div>

</body>
</html>