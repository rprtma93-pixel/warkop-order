@extends('admin.layout')
@section('title', 'Login Admin')

@section('content')
<div class="box" style="max-width:340px;margin:60px auto">
  <h2 style="margin-top:0">Login Admin</h2>
  <form method="POST" action="/login">
    @csrf
    <input type="password" name="password" placeholder="Password admin" style="width:100%" autofocus required>
    <button style="width:100%;margin-top:10px">Masuk</button>
  </form>
</div>
@endsection
