@extends('layouts.impression')

@section('titre', 'Procès-verbal')
@section('titre-pv', 'Procès-verbal')

@section('introduction')
    @yield('introduction-pv')
@endsection

@section('contenu')
    @yield('corps-pv')
    @yield('signatures-pv')
@endsection
