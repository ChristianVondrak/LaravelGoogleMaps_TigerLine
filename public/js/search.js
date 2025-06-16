// search.js

document.addEventListener('DOMContentLoaded', () => {
  // --- Configuración inicial del mapa ---
  const map = new google.maps.Map(document.getElementById('map'), {
    center: { lat: 28.2, lng: -81.7 },
    zoom: 9,
  });

  let highlightPolygon    = null;    // Polígono destacado
  let suggestionController = null;   // AbortController

  const searchForm       = document.getElementById('search-form');
  const searchInput      = document.getElementById('search-input');
  const searchBtn        = document.getElementById('search-btn');
  const loader           = document.getElementById('loader');
  const errorEl          = document.getElementById('search-error');
  const autocompleteList = document.getElementById('autocomplete-list');

  function setLoading(isLoading) {
    searchBtn.disabled    = isLoading;
    loader.style.display  = isLoading ? 'block' : 'none';
  }

  // Ahora acepta opciones (p.ej. signal)
  async function apiGet(path, options = {}) {
    const res = await fetch(path, options);
    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error(err.error || res.statusText);
    }
    return res.json();
  }

  function debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn(...args), delay);
    };
  }

  function clearSuggestions() {
    autocompleteList.innerHTML = '';
  }

  // --- Autocomplete ---
  const fetchSuggestions = debounce(async () => {
    const term = searchInput.value.trim();
    if (term.length < 2) {
      clearSuggestions();
      return;
    }

    suggestionController?.abort();
    suggestionController = new AbortController();

    try {
      // Cambiado de ?q= a ?term=
      const suggestions = await apiGet(
        `/autocomplete?term=${encodeURIComponent(term)}`, 
        { signal: suggestionController.signal }
      );
      renderSuggestions(suggestions);
    } catch (err) {
      if (err.name !== 'AbortError') console.error('Fetch suggestions:', err);
    }
  }, 300);

  function renderSuggestions(list) {
    clearSuggestions();
    list.forEach(item => {
      const isZip = /^\d+$/.test(item.value);
      const li = document.createElement('li');
      li.className = 'autocomplete-item';
      li.setAttribute('role', 'option');
      li.innerHTML = `
        <span class="material-icons">${isZip ? 'local_post_office' : 'place'}</span>
        <span>${item.value}</span>
      `;
      li.addEventListener('click', () => {
        searchInput.value = item.value;
        clearSuggestions();
        performSearch();
      });
      autocompleteList.appendChild(li);
    });
  }

  // --- Búsqueda y pinta polígono ---
  async function performSearch() {
    const term = searchInput.value.trim();
    errorEl.textContent = '';

    if (!term) {
      errorEl.textContent = 'Introduce un término válido.';
      return;
    }

    setLoading(true);

    try {
      // Cambiado de ?q= a ?term=
      const data = await apiGet(`/search?term=${encodeURIComponent(term)}`);

      if (highlightPolygon) highlightPolygon.setMap(null);

      const paths = data.boundary.flatMap(polygon =>
        polygon.map(ring =>
          ring.map(([lng, lat]) => ({ lat, lng }))
        )
      );

      highlightPolygon = new google.maps.Polygon({
        paths,
        strokeColor:  '#4285F4',
        strokeOpacity: 0.9,
        strokeWeight:  2,
        fillColor:    '#4285F4',
        fillOpacity:   0.1,
        clickable:    false,
        map
      });

      const bounds = paths.reduce((b, ring) => {
        ring.forEach(pt => b.extend(pt));
        return b;
      }, new google.maps.LatLngBounds());
      map.fitBounds(bounds);

    } catch (err) {
      errorEl.textContent = err.message;
    } finally {
      setLoading(false);
    }
  }

  // --- Listeners ---
  searchInput.addEventListener('input', () => {
    const hasText = searchInput.value.trim().length > 0;
    searchBtn.disabled = !hasText;
    fetchSuggestions();
  });

  searchForm.addEventListener('submit', e => {
    e.preventDefault();
    clearSuggestions();
    performSearch();
  });
});
