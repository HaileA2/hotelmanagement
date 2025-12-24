/** public/assets/js/hotels.js **/
/** public/assets/js/hotels.js **/
import apiService from './services/apiService.js';

// Configuration
const PAGE_SIZE = 8;

// State
let hotels = []; 
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
    document.getElementById('sortBy').addEventListener('change', applyFilters);
    document.getElementById('searchInput').addEventListener('input', debounce(() => applyFilters(), 300));
}

async function loadHotels() {
    const container = document.getElementById('hotelsList');
    // Show a clean loading state
    container.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Finding the best hotels for you...</p>
        </div>`;
    
    try {
        const body = await apiService.getHotels(); 
        
        if (!body.success) throw new Error(body.message || 'Failed to fetch hotels');
        
        // 1. Normalize and Clean Data
        hotels = (body.data || []).map(h => {
            // Convert amenities string to Array if it's still JSON
            if (typeof h.amenities === 'string') {
                try { 
                    h.amenities = JSON.parse(h.amenities); 
                } catch(e) { 
                    h.amenities = []; 
                }
            }
            
            // Ensure numeric types for logic operations
            return {
                ...h,
                rating: parseFloat(h.rating) || 0,
                price_per_night: parseFloat(h.price_per_night) || 0,
                amenities: Array.isArray(h.amenities) ? h.amenities : []
            };
        });

        // 2. Dynamically adjust the Price Slider based on actual data
        const prices = hotels.map(h => h.price_per_night).filter(v => v > 0);
        if (prices.length > 0) {
            const minPrice = Math.floor(Math.min(...prices));
            const maxPrice = Math.ceil(Math.max(...prices));
            
            // Update the slider UI only if the element exists
            const slider = document.getElementById('priceRange');
            if (slider && slider.noUiSlider) {
                setPriceSliderRange(minPrice, maxPrice);
                // Also update the state variable used for filtering
                priceRange = [minPrice, maxPrice];
            }
        }

        // 3. Initial Render
        applyFilters();

    } catch (err) {
        console.error('Frontend Error:', err);
        container.innerHTML = `
            <div class="alert alert-warning m-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Oops!</strong> ${err.message}
            </div>`;
    }
}


function createHotelCard(h) {
    const col = document.createElement('div');
    col.className = 'col-md-6 mb-4';
    
    const price = h.price_per_night || '—';
    const amenitiesHtml = (h.amenities || []).slice(0, 3).map(a => 
        `<span class="badge bg-light text-dark me-1 border small fw-normal">${a}</span>`
    ).join('');

    // Generate Stars HTML
    let starsHtml = '';
    const fullStars = Math.floor(h.rating);
    for (let i = 0; i < 5; i++) {
        starsHtml += `<i class="${i < fullStars ? 'fas' : 'far'} fa-star text-warning small"></i>`;
    }

    col.innerHTML = `
        <div class="card h-100 shadow-sm border-0 hotel-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="card-title mb-0 text-truncate">${h.name}</h5>
                    <div class="text-nowrap">${starsHtml}</div>
                </div>
                <p class="text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i>${h.location}</p>
                <p class="card-text text-muted small mb-3" style="height: 40px; overflow: hidden;">${h.description || ''}</p>
                <div class="mb-3" style="height: 25px;">${amenitiesHtml}</div>
                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                    <div>
                        <span class="h5 mb-0 text-primary">$${price}</span>
                        <span class="text-muted extra-small">/night</span>
                    </div>
                    <a href="hotel-details.html?hotel_id=${h.id}" class="btn btn-sm btn-primary px-3">View Details</a>
                </div>
            </div>
        </div>
    `;
    return col;
}



function applyFilters() {
    currentPage = 1;
    const q = document.getElementById('searchInput').value.trim().toLowerCase();
    
    // Get the minimum rating selected (e.g., if 4 is checked, we show 4 and 5)
    const selectedRatings = Array.from(document.querySelectorAll('.rating-filter:checked'))
                                 .map(i => parseInt(i.value));
    const minRequiredRating = selectedRatings.length > 0 ? Math.min(...selectedRatings) : 0;

    const selectedAmenities = Array.from(document.querySelectorAll('.amenity-filter:checked'))
                                   .map(i => i.value);
    const sortBy = document.getElementById('sortBy').value;

    const [minPrice, maxPriceVal] = priceRange;

    filtered = hotels.filter(h => {
        // 1. Search filter
        if (q) {
            const haystack = `${h.name} ${h.location} ${h.description}`.toLowerCase();
            if (!haystack.includes(q)) return false;
        }

        // 2. Price filter
        const price = parseFloat(h.price_per_night) || 0;
        if (price < minPrice || price > maxPriceVal) return false;

        // 3. Rating filter (NEW)
        // Checks if hotel rating is at least the minimum star level selected
        const hotelRating = parseFloat(h.rating) || 0;
        if (minRequiredRating > 0 && hotelRating < minRequiredRating) return false;

        // 4. Amenities filter
        if (selectedAmenities.length) {
            const ams = h.amenities || [];
            if (!selectedAmenities.every(a => ams.includes(a))) return false;
        }

        return true;
    });

    // --- Sort Logic ---
    if (sortBy === 'price_low') {
        filtered.sort((a, b) => (parseFloat(a.price_per_night) || 0) - (parseFloat(b.price_per_night) || 0));
    } else if (sortBy === 'price_high') {
        filtered.sort((a, b) => (parseFloat(b.price_per_night) || 0) - (parseFloat(a.price_per_night) || 0));
    } else if (sortBy === 'rating') {
        // Sort by guest rating (Highest first)
        filtered.sort((a, b) => (parseFloat(b.rating) || 0) - (parseFloat(a.rating) || 0));
    }

    renderList(filtered);
}


function renderList(hotelsToRender) {
    const container = document.getElementById('hotelsList');
    const countDisplay = document.getElementById('resultsCount');
    container.innerHTML = '';
    
    countDisplay.textContent = `${hotelsToRender.length} hotels found`;

    if (hotelsToRender.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No hotels match your filters.</div>';
        renderPagination(0);
        return;
    }

    const start = (currentPage - 1) * PAGE_SIZE;
    const pageItems = hotelsToRender.slice(start, start + PAGE_SIZE);

    const row = document.createElement('div');
    row.className = 'row';
    pageItems.forEach(h => row.appendChild(createHotelCard(h)));
    container.appendChild(row);

    renderPagination(hotelsToRender.length);
}

// UI Helpers (Pagination, Slider, Debounce)
function renderPagination(totalItems) {
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';
    const totalPages = Math.ceil(totalItems / PAGE_SIZE);
    if (totalPages <= 1) return;

    for (let p = 1; p <= totalPages; p++) {
        const li = document.createElement('li');
        li.className = `page-item ${p === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#">${p}</a>`;
        li.onclick = (e) => { e.preventDefault(); currentPage = p; renderList(filtered); };
        pagination.appendChild(li);
    }
}

function initPriceSlider() {
    const slider = document.getElementById('priceRange');
    if (!slider) return;
    noUiSlider.create(slider, {
        start: [0, 1000],
        connect: true,
        range: { min: 0, max: 1000 },
        format: { to: v => Math.round(v), from: v => Number(v) }
    });
    slider.noUiSlider.on('update', (values) => {
        priceRange = values.map(v => parseInt(v));
        document.getElementById('minPrice').textContent = `$${priceRange[0]}`;
        document.getElementById('maxPrice').textContent = `$${priceRange[1]}`;
    });
}

function setPriceSliderRange(min, max) {
    const slider = document.getElementById('priceRange');
    if (slider && slider.noUiSlider) {
        slider.noUiSlider.updateOptions({ range: { min, max: Math.max(max, min + 1) } });
    }
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.querySelectorAll('.rating-filter, .amenity-filter').forEach(c => c.checked = false);
    priceRange = [0, 1000];
    const slider = document.getElementById('priceRange');
    if (slider) slider.noUiSlider.set([0, 1000]);
    applyFilters();
}

function debounce(fn, delay) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), delay); };
}