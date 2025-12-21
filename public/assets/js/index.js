// Index page JavaScript
import { apiService } from '../js/services/api.service.js';
import { authService } from '../js/services/auth.service.js';

document.addEventListener('DOMContentLoaded', function() {
    // Update authentication UI
    updateAuthUI();

    // Load featured hotels
    loadFeaturedHotels();

    // Handle quick search form
    const quickSearchForm = document.getElementById('quickSearchForm');
    if (quickSearchForm) {
        quickSearchForm.addEventListener('submit', handleQuickSearch);
    }

    // Set minimum dates for date inputs
    const checkInInput = document.getElementById('checkIn');
    const checkOutInput = document.getElementById('checkOut');
    if (checkInInput && checkOutInput) {
        const today = new Date().toISOString().split('T')[0];
        checkInInput.min = today;
        checkOutInput.min = today;

        checkInInput.addEventListener('change', function() {
            checkOutInput.min = this.value;
        });
    }
});

// Update authentication UI
function updateAuthUI() {
    const user = authService.getCurrentUser();
    const loginBtn = document.getElementById('loginBtn');
    const registerBtn = document.getElementById('registerBtn');
    const userMenu = document.getElementById('userMenu');
    const userName = document.getElementById('userName');

    if (user) {
        if (loginBtn) loginBtn.style.display = 'none';
        if (registerBtn) registerBtn.style.display = 'none';
        if (userMenu) userMenu.style.display = 'block';
        if (userName) userName.textContent = user.first_name || 'User';
    } else {
        if (loginBtn) loginBtn.style.display = 'block';
        if (registerBtn) registerBtn.style.display = 'block';
        if (userMenu) userMenu.style.display = 'none';
    }
}

// Load featured hotels
async function loadFeaturedHotels() {
    const featuredHotelsContainer = document.getElementById('featuredHotels');

    try {
        const response = await apiService.getHotels();
        const hotels = response.data || [];

        // Take first 3 hotels as featured
        const featuredHotels = hotels.slice(0, 3);

        if (featuredHotels.length === 0) {
            // Show sample hotels if no real data
            showSampleFeaturedHotels();
            return;
        }

        renderFeaturedHotels(featuredHotels);
    } catch (error) {
        console.warn('API not available, showing sample hotels:', error);
        showSampleFeaturedHotels();
    }
}

// Show sample featured hotels
function showSampleFeaturedHotels() {
    const sampleHotels = [
        {
            id: 1,
            name: 'Luxury Grand Hotel',
            location: 'New York, NY',
            image: 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            price: 299
        },
        {
            id: 2,
            name: 'Sunset Resort & Spa',
            location: 'Miami, FL',
            image: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            price: 349
        },
        {
            id: 3,
            name: 'Mountain View Lodge',
            location: 'Aspen, CO',
            image: 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            price: 279
        }
    ];

    renderFeaturedHotels(sampleHotels);
}

// Render featured hotels
function renderFeaturedHotels(hotels) {
    const container = document.getElementById('featuredHotels');
    if (!container) return;

    container.innerHTML = hotels.map(hotel => `
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <img src="${hotel.image || 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80'}"
                     class="card-img-top" alt="${hotel.name}" style="height: 200px; object-fit: cover;">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${hotel.name}</h5>
                    <p class="card-text text-muted">
                        <i class="fas fa-map-marker-alt me-1"></i>${hotel.location}
                    </p>
                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="h5 text-primary mb-0">$${hotel.price || 150}</span>
                                <small class="text-muted">/night</small>
                            </div>
                            <a href="hotel-details.html?id=${hotel.id}" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Handle quick search
function handleQuickSearch(e) {
    e.preventDefault();

    const destination = document.getElementById('destination').value;
    const checkIn = document.getElementById('checkIn').value;
    const checkOut = document.getElementById('checkOut').value;
    const guests = document.getElementById('guests').value;

    // Build query string
    const params = new URLSearchParams();
    if (destination) params.append('destination', destination);
    if (checkIn) params.append('checkin', checkIn);
    if (checkOut) params.append('checkout', checkOut);
    if (guests) params.append('guests', guests);

    // Redirect to hotels page with search parameters
    window.location.href = `hotels.html?${params.toString()}`;
}

// Service functions - redirect to dedicated service pages
window.showTours = async function() {
    if (!authService.isAuthenticated()) {
        alert('Please login to access tour services');
        window.location.href = 'login.html';
        return;
    }
    // Redirect to tours page (would need to be created)
    window.location.href = 'tours.html';
};

window.showRestaurants = async function() {
    if (!authService.isAuthenticated()) {
        alert('Please login to access restaurant services');
        window.location.href = 'login.html';
        return;
    }
    // Redirect to restaurants page (would need to be created)
    window.location.href = 'restaurants.html';
};

window.showTaxis = async function() {
    if (!authService.isAuthenticated()) {
        alert('Please login to access transportation services');
        window.location.href = 'login.html';
        return;
    }
    // Redirect to taxis page (would need to be created)
    window.location.href = 'taxis.html';
};