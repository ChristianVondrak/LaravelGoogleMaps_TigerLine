@extends('layouts.main')

@section('title', 'Buscador de Zonas')

@section('styles')
  <link rel="stylesheet" href="{{ asset('css/search.css') }}">
@endsection

@section('content')
<div id="map-container">
  <header class="search-container" role="search">
    <form id="search-form" class="search-bar" novalidate>
      <label for="search-input" class="visually-hidden">Buscar ZIP o lugar</label>
      <input
        id="search-input"
        name="q"
        type="search"
        placeholder="ZIP o nombre…"
        autocomplete="off"
        aria-autocomplete="list"
        aria-controls="autocomplete-list"
      />
      <button id="search-btn" type="submit" aria-label="Buscar" disabled>
        <span class="material-icons">search</span>
      </button>
      <div id="loader" class="loader" aria-hidden="true"></div>
    </form>
    <div id="search-error" class="error" role="alert" aria-live="assertive"></div>
    <ul id="autocomplete-list" class="autocomplete-list" role="listbox"></ul>
  </header>

  <div id="map"></div>
</div>
@endsection

@section('scripts')
  <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}" defer></script>
  <script src="{{ asset('js/search.js') }}" defer></script>
@endsection
