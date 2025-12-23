// public/assets/js/hotels.js
const API_LIST_HOTELS = '/hotelmanagement/api/list_hotels.php'; // adjust if needed
const PAGE_SIZE = 8;

let hotels = []; // full dataset
let filtered = [];
let currentPage = 1;
let priceRange = [0, 1000];

document.addEventListener('DOMContentLoaded', () => {
  initPriceSlider();
  bindUI();
  loadHotels();
});

function bindUI() {
  document.getElementById('applyFilters').addEventListener('click', applyFilters);
  document.getElementById('resetFilters').addEventListener('click', resetFilters);
  document.getElementById('sortBy').addEventListener('change', () => { applyFilters(); });
  document.getElementById('searchInput').addEventListener('input', debounce(() => applyFilters(), 300));
  document.getElementById('listViewBtn').addEventListener('click', () => toggleMap(false));
  document.getElementById('mapViewBtn').addEventListener('click', () => toggleMap(true));
}

async function loadHotels() {
  const container = document.getElementById('hotelsList');
  container.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
  try {
    const res = await fetch(API_LIST_HOTELS);
    const body = await res.json();
    if (!body.success) throw new Error(body.message || 'Failed to fetch hotels');
    hotels = body.data || [];
    // normalize amenities if stored as string
    hotels = hotels.map(h => {
      if (typeof h.amenities === 'string') {
        try { h.amenities = JSON.parse(h.amenities); } catch(e) { h.amenities = []; }
      }
      return h;
    });
    // set initial priceRange based on data
    const prices = hotels.map(h => parseFloat(h.price) || 0).filter(v => v>0);
    const min = Math.min(...(prices.length?prices:[0]));
    const max = Math.max(...(prices.length?prices:[1000]));
    setPriceSliderRange(min, max);
    applyFilters();
  } catch (err) {
    console.error(err);
    container.innerHTML = `<p class="text-muted">Failed to load hotels.</p>`;
  }
}

function renderList(hotelsToRender) {
  const container = document.getElementById('hotelsList');
  container.innerHTML = '';
  if (hotelsToRender.length === 0) {
    container.innerHTML = '<p class="text-muted">No hotels match your filters.</p>';
    document.getElementById('resultsCount').textContent = '0 hotels found';
    renderPagination(0);
    return;
  }

  document.getElementById('resultsCount').textContent = `${hotelsToRender.length} hotels found`;

  // paginate
  const start = (currentPage - 1) * PAGE_SIZE;
  const pageItems = hotelsToRender.slice(start, start + PAGE_SIZE);

  const row = document.createElement('div');
  row.className = 'row g-3';
  pageItems.forEach(h => row.appendChild(createHotelCard(h)));
  container.appendChild(row);

  renderPagination(hotelsToRender.length);
}

function createHotelCard(h) {
  const col = document.createElement('div');
  col.className = 'col-md-6';

  const card = document.createElement('div');
  card.className = 'card mb-3 shadow-sm';

  const body = document.createElement('div');
  body.className = 'card-body';

  const title = document.createElement('h5');
  title.className = 'card-title';
  title.textContent = h.name;

  const loc = document.createElement('p');
  loc.className = 'text-muted mb-1';
  loc.textContent = h.location || '';

  const desc = document.createElement('p');
  desc.className = 'mb-2 text-truncate';
  desc.textContent = h.description || '';

  const amenities = document.createElement('p');
  amenities.className = 'small text-muted mb-2';
  amenities.textContent = (h.amenities && h.amenities.length) ? h.amenities.slice(0,3).join(', ') : '';

  const footer = document.createElement('div');
  footer.className = 'd-flex justify-content-between align-items-center';

  const left = document.createElement('div');
  left.innerHTML = `<strong>$${h.price || '—'}</strong><div class="small text-muted">per night</div>`;

  const right = document.createElement('div');
  right.innerHTML = `
    <a class="btn btn-sm btn-outline-primary me-2" href="hotel-details.html?hotel_id=${encodeURIComponent(h.id)}">View Details</a>
    <a class="btn btn-sm btn-primary" href="hotel-details.html?hotel_id=${encodeURIComponent(h.id)}&open_booking=true">Book</a>
  `;

  body.appendChild(title);
  body.appendChild(loc);
  body.appendChild(desc);
  body.appendChild(amenities);
  footer.appendChild(left);
  footer.appendChild(right);
  body.appendChild(footer);
  card.appendChild(body);
  col.appendChild(card);
  return col;
}

function renderPagination(totalItems) {
  const pagination = document.getElementById('pagination');
  pagination.innerHTML = '';
  const totalPages = Math.max(1, Math.ceil(totalItems / PAGE_SIZE));
  // prev
  const prev = document.createElement('li');
  prev.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
  prev.innerHTML = `<a class="page-link" href="#">Previous</a>`;
  prev.addEventListener('click', (e) => { e.preventDefault(); if (currentPage>1) { currentPage--; applyFilters(); }});
  pagination.appendChild(prev);

  // pages (limit to 5)
  const startPage = Math.max(1, currentPage - 2);
  const endPage = Math.min(totalPages, startPage + 4);
  for (let p = startPage; p <= endPage; p++) {
    const li = document.createElement('li');
    li.className = `page-item ${p === currentPage ? 'active' : ''}`;
    li.innerHTML = `<a class="page-link" href="#">${p}</a>`;
    li.addEventListener('click', (e) => { e.preventDefault(); currentPage = p; applyFilters(); });
    pagination.appendChild(li);
  }

  // next
  const next = document.createElement('li');
  next.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
  next.innerHTML = `<a class="page-link" href="#">Next</a>`;
  next.addEventListener('click', (e) => { e.preventDefault(); if (currentPage<totalPages) { currentPage++; applyFilters(); }});
  pagination.appendChild(next);
}

function applyFilters() {
  currentPage = 1;
  const q = document.getElementById('searchInput').value.trim().toLowerCase();
  const selectedRatings = Array.from(document.querySelectorAll('.rating-filter:checked')).map(i => parseInt(i.value));
  const selectedAmenities = Array.from(document.querySelectorAll('.amenity-filter:checked')).map(i => i.value);
  const sortBy = document.getElementById('sortBy').value;

  const [minPrice, maxPriceVal] = priceRange;

  filtered = hotels.filter(h => {
    // search
    if (q) {
      const hay = ((h.name||'') + ' ' + (h.location||'') + ' ' + (h.description||'')).toLowerCase();
      if (!hay.includes(q)) return false;
    }

    // price (if hotel.price exists)
    const price = parseFloat(h.price) || 0;
    if (price && (price < minPrice || price > maxPriceVal)) return false;

    // rating
    if (selectedRatings.length) {
      const r = parseInt(h.rating) || 0;
      if (!selectedRatings.some(sr => r >= sr)) return false;
    }

    // amenities
    if (selectedAmenities.length) {
      const am = h.amenities || [];
      const hasAll = selectedAmenities.every(a => am.includes(a));
      if (!hasAll) return false;
    }

    return true;
  });

  // sorting
  if (sortBy === 'price_low') filtered.sort((a,b)=>(parseFloat(a.price||0)||0)-(parseFloat(b.price||0)||0));
  else if (sortBy === 'price_high') filtered.sort((a,b)=>(parseFloat(b.price||0)||0)-(parseFloat(a.price||0)||0));
  else if (sortBy === 'rating') filtered.sort((a,b)=>(parseFloat(b.rating||0)||0)-(parseFloat(a.rating||0)||0));

  renderList(filtered);
}

function resetFilters() {
  document.getElementById('searchInput').value = '';
  document.querySelectorAll('.rating-filter').forEach(ch => ch.checked = false);
  document.querySelectorAll('.amenity-filter').forEach(ch => ch.checked = false);
  document.getElementById('sortBy').value = 'recommended';
  setPriceSliderRange(0, 1000);
  applyFilters();
}

function initPriceSlider() {
  const slider = document.getElementById('priceRange');
  if (!slider) return;
  noUiSlider.create(slider, {
    start: [0, 1000],
    connect: true,
    range: { min: 0, max: 1000 },
    step: 1,
    tooltips: [true, true],
    format: {
      to: v => Math.round(v),
      from: v => Number(v)
    }
  });
  slider.noUiSlider.on('update', (values) => {
    priceRange = values.map(v => parseInt(v));
    document.getElementById('minPrice').textContent = `$${priceRange[0]}`;
    document.getElementById('maxPrice').textContent = priceRange[1] >= 1000 ? `$${priceRange[1]}+` : `$${priceRange[1]}`;
  });
  slider.noUiSlider.on('change', () => applyFilters());
}

function setPriceSliderRange(min, max) {
  const slider = document.getElementById('priceRange');
  if (!slider || !slider.noUiSlider) return;
  const high = Math.max(max, min + 50);
  slider.noUiSlider.updateOptions({ range: { min, max: high }});
  slider.noUiSlider.set([min, high]);
}

function toggleMap(show) {
  document.getElementById('mapView').style.display = show ? 'block' : 'none';
  document.getElementById('listViewBtn').classList.toggle('active', !show);
  document.getElementById('mapViewBtn').classList.toggle('active', show);
}

// small debounce
function debounce(fn, delay = 200) {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn.apply(this, args), delay);
  };
}
