@extends('admin.layout')
@section('title', 'Menu - Admin Warkop')

@section('content')
<div class="box">
  <h3 style="margin-top:0">{{ $edit ? 'Edit Menu' : 'Tambah Menu' }}</h3>
  <form method="POST" action="{{ $edit ? '/admin/menu/'.$edit->id : '/admin/menu' }}">
    @csrf
    <div class="grid">
      <input name="name" placeholder="Nama" value="{{ old('name', $edit->name ?? '') }}" required>
      <input name="category" placeholder="Kategori (Kopi/Non-Kopi/Makanan)" value="{{ old('category', $edit->category ?? '') }}" required>
      <input name="price" type="number" min="0" placeholder="Harga" value="{{ old('price', $edit->price ?? '') }}" required>
      <input name="description" placeholder="Deskripsi" value="{{ old('description', $edit->description ?? '') }}">
      <label><input type="checkbox" name="available" value="1" @checked(old('available', $edit->available ?? true))> Tersedia</label>
    </div>
    <div style="margin-top:10px">
      <button>Simpan</button>
      @if($edit)<a class="btn ghost" href="/admin/menu">Batal edit</a>@endif
    </div>
  </form>
</div>

<div class="box">
  <table>
    <thead><tr><th>Nama</th><th>Kategori</th><th>Harga</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse($items as $m)
      <tr>
        <td>{{ $m->name }}<br><small>{{ $m->description }}</small></td>
        <td>{{ $m->category }}</td>
        <td>Rp {{ number_format($m->price, 0, ',', '.') }}</td>
        <td>{{ $m->available ? 'Tersedia' : 'Habis' }}</td>
        <td>
          <a class="btn sm" href="/admin/menu?edit={{ $m->id }}">Edit</a>
          <form class="inline" method="POST" action="/admin/menu/{{ $m->id }}/delete" onsubmit="return confirm('Hapus menu ini?')">
            @csrf
            <button class="sm danger">Hapus</button>
          </form>
        </td>
      </tr>
    @empty
      <tr><td colspan="5">Belum ada menu.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
