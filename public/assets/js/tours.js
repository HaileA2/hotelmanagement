/** public/assets/js/tours.js **/
import apiService from './services/apiService.js';

// Configuration
const PAGE_SIZE = 8;

// State
let tours = [];
let filtered = [];
let currentPage = 1;

document.addEventListener('DOMContentLoaded', () => {
    bindUI();
    loadTours();
});

function bindUI() {
    document.getElementById('searchInput').addEventListener('input', debounce(() => applyFilters(), 300));
}

async function loadTours() {
    const container = document.getElementById('toursList');
    // Show a clean loading state
    container.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Finding amazing tours for you...</p>
        </div>`;

    try {
        const body = await apiService.getTours();

        // Backend returns { success: true, data: [...] }
        if (!body.success) {
            throw new Error(body.message || 'Failed to fetch tours');
        }

        // Additional safety check: ensure body.data is actually an array
        let toursData = body.data;
        if (!Array.isArray(toursData)) {
            console.warn('Frontend Warning: Expected array but received:', typeof toursData, toursData);
            
            // Try to handle different response structures as fallback
            if (toursData && typeof toursData === 'object') {
                if (Array.isArray(toursData.data)) toursData = toursData.data;
                else if (Array.isArray(toursData.tours)) toursData = toursData.tours;
                else if (Array.isArray(toursData.results)) toursData = toursData.results;
                else toursData = []; // Default to empty array
            } else {
                toursData = []; // Default to empty array if not an object
            }
        }

        // Normalize and Clean Data
        tours = toursData.map(t => ({
            ...t,
            price: parseFloat(t.price) || 0
        }));

        // Initial Render
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

function createTourCard(t) {
    const col = document.createElement('div');
    col.className = 'col-md-6 mb-4';

    const price = t.price || '—';

    col.innerHTML = `
        <div class="card h-100 shadow-sm border-0 tour-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="card-title mb-0 text-truncate">${t.title}</h5>
                </div>
                <p class="text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i>${t.location}</p>
                <p class="card-text text-muted small mb-3">Schedule: ${t.schedule_date || 'TBD'}</p>
                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                    <div>
                        <span class="h5 mb-0 text-primary">$${price}</span>
                    </div>
                    <button class="btn btn-sm btn-success px-3" onclick="bookTour(${t.id}, '${t.title}')">Book Now</button>
                </div>
            </div>
        </div>
    `;
    return col;
}

function applyFilters() {
    currentPage = 1;
    const q = document.getElementById('searchInput').value.trim().toLowerCase();

    filtered = tours.filter(t => {
        // Search filter
        if (q) {
            const haystack = `${t.title} ${t.location}`.toLowerCase();
            if (!haystack.includes(q)) return false;
        }
        return true;
    });

    renderList(filtered);
}

function renderList(toursToRender) {
    const container = document.getElementById('toursList');
    const countDisplay = document.getElementById('resultsCount');
    container.innerHTML = '';

    countDisplay.textContent = `${toursToRender.length} tours found`;

    if (toursToRender.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No tours match your search.</div>';
        renderPagination(0);
        return;
    }

    const start = (currentPage - 1) * PAGE_SIZE;
    const pageItems = toursToRender.slice(start, start + PAGE_SIZE);

    const row = document.createElement('div');
    row.className = 'row';
    pageItems.forEach(t => row.appendChild(createTourCard(t)));
    container.appendChild(row);

    renderPagination(toursToRender.length);
}

// UI Helpers (Pagination, Debounce)
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

function debounce(fn, delay) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), delay); };
}

// Global function for booking
window.bookTour = async function(tourId, title) {
    if (!confirm(`Book "${title}"?`)) return;

    try {
        const result = await apiService.bookTour({
            tour_id: tourId,
            customer_name: 'Test User', // In real app, get from user profile
            customer_email: 'test@example.com' // In real app, get from user profile
        });

        if (result.success) {
            alert('Tour booked successfully!');
        } else {
            alert('Booking failed: ' + (result.message || 'Unknown error'));
        }
    } catch (err) {
        alert('Booking failed: ' + err.message);
    }
};