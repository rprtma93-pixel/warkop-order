@extends('admin.layout')
@section('title', 'Pesanan - Admin Warkop')
@section('head')
<meta http-equiv="refresh" content="20">
@endsection

@section('content')
<div class="stats">
  <div class="stat"><small>Pesanan hari ini</small><b>{{ $today->n }}</b></div>
  <div class="stat"><small>Omzet hari ini</small><b>Rp {{ number_format($today->revenue, 0, ',', '.') }}</b></div>
  <div class="stat"><small>Perlu diproses</small><b>{{ $active }}</b></div>
</div>

<div class="box">
  <table>
    <thead><tr><th>#</th><th>Waktu</th><th>Pemesan</th><th>Item</th><th>Total</th><th>Status</th><th>Ubah</th></tr></thead>
    <tbody>
    @forelse($orders as $o)
      <tr>
        <td>#{{ $o->id }}</td>
        <td>{{ \Illuminate\Support\Carbon::parse($o->created_at)->setTimezone('Asia/Jakarta')->format('d/m H:i') }}</td>
        <td>
          <b>{{ $o->customer_name }}</b><br>
          <small>{{ $o->table_no ? 'Meja '.$o->table_no : '' }}{{ $o->notes ? ' · '.$o->notes : '' }}</small>
        </td>
        <td>
          @foreach($items->get($o->id, collect()) as $i)
            {{ $i->qty }}× {{ $i->name }}@if(! $loop->last), @endif
          @endforeach
        </td>
        <td>Rp {{ number_format($o->total, 0, ',', '.') }}</td>
        <td><span class="badge {{ $o->status }}">{{ $o->status }}</span></td>
        <td>
          <form method="POST" action="/admin/orders/{{ $o->id }}/status">
            @csrf
            <select name="status" onchange="this.form.submit()">
              @foreach($statuses as $s)
                <option value="{{ $s }}" @selected($s === $o->status)>{{ $s }}</option>
              @endforeach
            </select>
          </form>
        </td>
      </tr>
    @empty
      <tr><td colspan="7">Belum ada pesanan.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
<small>Halaman dimuat ulang otomatis tiap 20 detik.</small>
@endsection
