/** public/assets/js/hotels.js **/
/** public/assets/js/hotels.js **/
import apiService from './services/apiService.js';
import { authService } from './services/auth.service.js';

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
    renderAdminBarIfAllowed();
});

function bindUI() {
    document.getElementById('applyFilters').addEventListener('click', applyFilters);
    document.getElementById('resetFilters').addEventListener('click', resetFilters);
    document.getElementById('sortBy').addEventListener('change', applyFilters);
    document.getElementById('searchInput').addEventListener('input', debounce(() => applyFilters(), 300));
}

hotelSaveBtn
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
                    <div class="d-flex gap-2 align-items-center">
                        <a href="hotel-details.html?hotel_id=${h.id}" class="btn btn-sm btn-primary px-3">View Details</a>
                        ${renderAdminButtons(h.id)}
                    </div>
                </div>
            </div>
        </div>
    `;
    return col;
}

function renderAdminButtons(hotelId) {
    try {
        const user = authService.getCurrentUser();
        if (!user) return '';
        const role = (user.role || '').toLowerCase();
        if (!['admin', 'manager'].includes(role)) return '';

        return `
            <div class="btn-group" role="group">
                <button class="btn btn-sm btn-outline-secondary btn-edit-hotel" data-id="${hotelId}">Edit</button>
                <button class="btn btn-sm btn-outline-danger btn-delete-hotel" data-id="${hotelId}">Delete</button>
            </div>
        `;
    } catch (e) {
        return '';
    }
}

// Inject admin bar (Add Hotel) and modal markup when current user is admin/manager
function renderAdminBarIfAllowed() {
    const user = authService.getCurrentUser();
    if (!user) return;
    const role = (user.role || '').toLowerCase();
    if (!['admin', 'manager'].includes(role)) return;

    // Add Add Hotel button near the top heading
    const heading = document.querySelector('.d-flex.justify-content-between.align-items-center.mb-3');
    if (heading) {
        const existingBtn = document.getElementById('addHotelBtn');
        if (existingBtn) {
            // Reveal the static button and bind handler if not already bound
            existingBtn.classList.remove('d-none');
            if (!existingBtn.dataset.bound) {
                existingBtn.addEventListener('click', () => showHotelModal());
                existingBtn.dataset.bound = 'true';
            }
        } else {
            const wrap = document.createElement('div');
            wrap.className = 'ms-3';
            wrap.innerHTML = `<button id="addHotelBtn" class="btn btn-outline-success">+ Add Hotel</button>`;
            heading.appendChild(wrap);
            document.getElementById('addHotelBtn').addEventListener('click', () => showHotelModal());
        }
    }

    injectHotelModal();
}

function injectHotelModal() {
    if (document.getElementById('hotelModal')) return;
    const modal = document.createElement('div');
    modal.innerHTML = `
    <div class="modal fade" id="hotelModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="hotelModalTitle">Add Hotel</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="hotelModalAlert" style="display:none"></div>
            <form id="hotelForm">
              <input type="hidden" id="hotelId" />
              <div class="mb-3"><label class="form-label">Name</label><input id="hotelName" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Location</label><input id="hotelLocation" class="form-control"></div>
                            <div class="mb-3"><label class="form-label">Address</label><input id="hotelAddress" class="form-control"></div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">City</label><input id="hotelCity" class="form-control"></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Country</label><input id="hotelCountry" class="form-control"></div>
                            </div>
                            <div class="mb-3"><label class="form-label">Price per night</label><input id="hotelPrice" type="number" class="form-control"></div>
                            <div class="mb-3"><label class="form-label">Rating</label><input id="hotelRating" type="number" min="0" max="5" step="0.1" class="form-control"></div>
                            <div class="mb-3"><label class="form-label">Amenities (comma separated)</label><input id="hotelAmenities" class="form-control"></div>
                            <div class="mb-3"><label class="form-label">Description</label><textarea id="hotelDescription" class="form-control"></textarea></div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" id="hotelSaveBtn" class="btn btn-primary">Save</button>
          </div>
        </div>
      </div>
    </div>`;
    document.body.appendChild(modal);

    // Bind save handler
    document.getElementById('hotelSaveBtn').addEventListener('click', async () => {
        // Check authentication and role before attempting save
        const token = authService.getToken();
        const user = authService.getCurrentUser();
        const role = (user && user.role) ? String(user.role).toLowerCase() : null;
        if (!token || !user || !['admin', 'manager'].includes(role)) {
            showModalAlert('You must be logged in as Admin or Manager to perform this action.', 'danger');
            return;
        }
        const id = document.getElementById('hotelId').value;

        // Common fields
        const name = document.getElementById('hotelName').value.trim();
        const location = document.getElementById('hotelLocation').value.trim();
        const address = document.getElementById('hotelAddress').value.trim();
        const city = document.getElementById('hotelCity').value.trim();
        const country = document.getElementById('hotelCountry').value.trim();
        const price_per_night = parseFloat(document.getElementById('hotelPrice').value) || 0;
        const rating = parseFloat(document.getElementById('hotelRating').value) || 0;
        const amenities = (document.getElementById('hotelAmenities').value || '').split(',').map(s => s.trim()).filter(Boolean);
        const description = document.getElementById('hotelDescription').value.trim();

        try {
            if (id) {
                // Update: map to server-expected fields for update_hotel.php
                const payload = {
                    id: parseInt(id),
                    name,
                    location,
                    address,
                    city,
                    country,
                    description,
                    price_per_night,
                    rating,
                    amenities
                };
                await apiService.updateHotel(payload);
                showModalAlert('Hotel updated successfully.', 'success');
            } else {
                // Create: map to create_hotel.php expected fields
                const payload = {
                    name,
                    location,
                    address,
                    city,
                    country,
                    description,
                    price_per_night,
                    rating,
                    amenities
                };
                await apiService.createHotel(payload);
                showModalAlert('Hotel created successfully.', 'success');
            }

            // Close and refresh after a short delay
            setTimeout(() => {
                const modalEl = document.getElementById('hotelModal');
                const bsModal = bootstrap.Modal.getInstance(modalEl);
                bsModal.hide();
                loadHotels();
            }, 700);
        } catch (err) {
            showModalAlert(err.message || 'Unable to save hotel.', 'danger');
        }
    });

    // Delegate edit/delete button clicks
    document.body.addEventListener('click', async (e) => {
        if (e.target.matches('.btn-edit-hotel')) {
            const token = authService.getToken();
            const user = authService.getCurrentUser();
            const role = (user && user.role) ? String(user.role).toLowerCase() : null;
            if (!token || !user || !['admin', 'manager'].includes(role)) {
                alert('You must be logged in as Admin or Manager to edit hotels.');
                return;
            }
            const id = e.target.getAttribute('data-id');
            await openEditHotel(id);
        } else if (e.target.matches('.btn-delete-hotel')) {
            const token = authService.getToken();
            const user = authService.getCurrentUser();
            const role = (user && user.role) ? String(user.role).toLowerCase() : null;
            if (!token || !user || !['admin', 'manager'].includes(role)) {
                alert('You must be logged in as Admin or Manager to delete hotels.');
                return;
            }
            const id = e.target.getAttribute('data-id');
            await confirmAndDeleteHotel(id);
        }
    });
}

function showModalAlert(msg, type = 'info') {
    const el = document.getElementById('hotelModalAlert');
    if (!el) return;
    el.style.display = 'block';
    el.className = `alert alert-${type}`;
    el.textContent = msg;
}

async function openEditHotel(id) {
    try {
        const res = await apiService.getHotelDetails(id);
        if (!res.success) throw new Error(res.message || 'Failed to fetch hotel');
    const hotel = res.data.hotel || res.data;
    document.getElementById('hotelId').value = hotel.id || '';
    document.getElementById('hotelName').value = hotel.name || '';
    // Fill location, address, city, country
    document.getElementById('hotelLocation').value = hotel.location || '';
    document.getElementById('hotelAddress').value = hotel.address || '';
    document.getElementById('hotelCity').value = hotel.city || '';
    document.getElementById('hotelCountry').value = hotel.country || '';
    document.getElementById('hotelPrice').value = hotel.price_per_night || '';
    document.getElementById('hotelRating').value = hotel.rating || '';
    document.getElementById('hotelAmenities').value = Array.isArray(hotel.amenities) ? hotel.amenities.join(', ') : (typeof hotel.amenities === 'string' ? hotel.amenities : '');
    document.getElementById('hotelDescription').value = hotel.description || '';
        document.getElementById('hotelModalTitle').textContent = 'Edit Hotel';
        document.getElementById('hotelModalAlert').style.display = 'none';
        const modalEl = document.getElementById('hotelModal');
        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
    } catch (err) {
        alert(err.message || 'Unable to load hotel for editing');
    }
}

async function confirmAndDeleteHotel(id) {
    if (!confirm('Are you sure you want to delete this hotel? This action cannot be undone.')) return;
    try {
        await apiService.deleteHotel(id);
        loadHotels();
    } catch (err) {
        alert(err.message || 'Unable to delete hotel');
    }
}

function showHotelModal() {
    document.getElementById('hotelId').value = '';
    document.getElementById('hotelName').value = '';
    document.getElementById('hotelLocation').value = '';
    document.getElementById('hotelPrice').value = '';
    document.getElementById('hotelRating').value = '';
    document.getElementById('hotelAmenities').value = '';
    document.getElementById('hotelDescription').value = '';
    document.getElementById('hotelModalTitle').textContent = 'Add Hotel';
    document.getElementById('hotelModalAlert').style.display = 'none';
    const modalEl = document.getElementById('hotelModal');
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
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