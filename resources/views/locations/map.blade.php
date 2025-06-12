<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="initial-scale=1.0">
  <title>Buscador de Zonas</title>
  <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500&display=swap" rel="stylesheet">
  <!-- Material Icons para los iconos -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <style>
    html, body { height:100%; margin:0; font-family:'Roboto',sans-serif; }
    #map { height:100%; width:100%; }

    .search-container {
      position: absolute; top:15px; left:50%;
      transform: translateX(-50%);
      width: 320px;
      display:flex; flex-direction:column;
      background: rgba(255,255,255,0.95);
      border-radius:4px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.3);
      z-index:5;
      transition: box-shadow .3s;
    }
    .search-container:focus-within {
      box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    }

    .search-bar {
      display:flex; align-items:center;
      padding:8px;
    }
    .search-bar input {
      flex:1; border:none; outline:none;
      font-size:16px; padding:6px 8px; color:#333;
      border-radius:2px;
    }
    .search-bar button {
      background: #4285F4; border:none; color:#fff;
      padding:8px 12px; margin-left:8px; border-radius:2px;
      font-size:14px; cursor:pointer; transition:background .2s;
    }
    .search-bar button:disabled {
      background:#a0c6ff; cursor:default;
    }
    .search-bar button:hover:not(:disabled) {
      background:#3367d6;
    }
    .loader {
      width:20px; height:20px; border:3px solid #f3f3f3;
      border-top:3px solid #4285F4; border-radius:50%;
      animation:spin 1s linear infinite;
      margin-left:10px; display:none;
    }
    @keyframes spin { from{transform:rotate(0);} to{transform:rotate(360deg);} }

    .error {
      color:#d93025; font-size:14px;
      padding:0 8px 6px;
    }

    /* Nuevo dropdown de sugerencias */
    .autocomplete-list {
      max-height:200px;
      overflow-y:auto;
      border-top:1px solid #eee;
    }
    .autocomplete-item {
      display:flex; align-items:center;
      padding:8px;
      cursor:pointer;
      transition:background .2s;
    }
    .autocomplete-item:hover {
      background:#f1f1f1;
    }
    .autocomplete-item .material-icons {
      font-size:20px;
      margin-right:8px;
      color:#4285F4;
    }
    .autocomplete-item span {
      font-size:14px;
      color:#333;
    }
  </style>

  <script
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}"
    defer
  ></script>
</head>
<body onload="initMap()">

  <div class="search-container">
    <div class="search-bar">
      <input
        id="search-input"
        type="text"
        placeholder="ZIP o nombre…"
        oninput="onInputChange()"
        onkeydown="if(event.key==='Enter') doSearch()"
        autocomplete="off"
      />
      <button id="search-btn" onclick="doSearch()">Buscar</button>
      <div class="loader" id="loader"></div>
    </div>
    <div class="error" id="search-error"></div>
    <div id="autocomplete-list" class="autocomplete-list"></div>
  </div>

  <div id="map"></div>

  <script>
    let map, highlightPolygon, debounceTimer;

    function initMap() {
      const mapDiv = document.getElementById('map');
      map = new google.maps.Map(mapDiv, {
        center: { lat: 28.2, lng: -81.7 },
        zoom: 9,
      });
    }

    function onInputChange() {
      clearTimeout(debounceTimer);
      const q = document.getElementById('search-input').value.trim();
      if (q.length < 2) {
        document.getElementById('autocomplete-list').innerHTML = '';
        return;
      }
      debounceTimer = setTimeout(fetchSuggestions, 300);
    }

    async function fetchSuggestions() {
      const q = encodeURIComponent(document.getElementById('search-input').value.trim());
      if (!q) return;

      try {
        const res  = await fetch(`/autocomplete?q=${q}`);
        const list = await res.json();
        const box  = document.getElementById('autocomplete-list');
        box.innerHTML = '';

        list.forEach(item => {
          const div = document.createElement('div');
          div.className = 'autocomplete-item';
          // Suponemos que si contiene dígitos es ZIP
          const isZip = /^\d+$/.test(item.value);
          div.innerHTML = `
            <span class="material-icons">
              ${isZip ? 'local_post_office' : 'place'}
            </span>
            <span>${item.value}</span>
          `;
          div.addEventListener('click', () => {
            document.getElementById('search-input').value = item.value;
            box.innerHTML = '';
            doSearch();
          });
          box.appendChild(div);
        });
      } catch (e) {
        console.error('Error sugerencias:', e);
      }
    }

    async function doSearch() {
      const input = document.getElementById('search-input');
      const q     = input.value.trim();
      const btn   = document.getElementById('search-btn');
      const loader= document.getElementById('loader');
      const errEl = document.getElementById('search-error');
      errEl.textContent = '';

      if (!q) {
        errEl.textContent = 'Introduce un término.';
        return;
      }

      btn.disabled = true;
      loader.style.display = 'block';

      try {
        const res  = await fetch(`/search?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || res.statusText);

        if (highlightPolygon) highlightPolygon.setMap(null);

        const paths = (data.boundary || []).map(poly =>
          poly[0].map(([lng,lat]) => ({ lat, lng }))
        );

        highlightPolygon = new google.maps.Polygon({
          paths,
          strokeColor: '#4285F4',
          strokeOpacity: 0.9,
          strokeWeight: 2,
          fillColor:   '#4285F4',
          fillOpacity: 0.1,
          clickable:   false,
          map
        });

        const bounds = new google.maps.LatLngBounds();
        paths.flat().forEach(pt => bounds.extend(pt));
        map.fitBounds(bounds);

      } catch (e) {
        errEl.textContent = e.message;
      } finally {
        btn.disabled = false;
        loader.style.display = 'none';
      }
    }
  </script>
</body>
</html>
