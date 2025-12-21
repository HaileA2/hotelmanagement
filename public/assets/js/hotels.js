// Import services
import { apiService } from '../js/services/api.service.js';
import { authService } from '../js/services/auth.service.js';

// Sample hotel data (fallback when API is not available)
const sampleHotels = [
  {
    id: 1,
    name: 'Luxury Grand Hotel',
    location: 'New York, NY',
    description: 'Experience unparalleled luxury at the Luxury Grand Hotel, located in the heart of New York City.',
    amenities: ['wifi', 'pool', 'restaurant', 'gym', 'parking'],
    created_at: '2023-12-01 10:00:00'
  },
  {
    id: 2,
    name: 'Sunset Resort & Spa',
    location: 'Miami, FL',
    description: 'A tropical paradise with world-class amenities and stunning ocean views.',
    amenities: ['wifi', 'pool', 'spa', 'restaurant', 'beach'],
    created_at: '2023-12-02 10:00:00'
  },
  {
    id: 3,
    name: 'Mountain View Lodge',
    location: 'Aspen, CO',
    description: 'Cozy mountain retreat with breathtaking views and outdoor activities.',
    amenities: ['wifi', 'restaurant', 'gym', 'parking', 'ski'],
    created_at: '2023-12-03 10:00:00'
  }
];

// DOM Elements
const hotelsList = document.getElementById('hotelsList');
const priceRange = document.getElementById('priceRange');
const minPrice = document.getElementById('minPrice');
const maxPrice = document.getElementById('maxPrice');
const sortBy = document.getElementById('sortBy');
const applyFiltersBtn = document.getElementById('applyFilters');
const resetFiltersBtn = document.getElementById('resetFilters');
const listViewBtn = document.getElementById('listViewBtn');
const mapViewBtn = document.getElementById('mapViewBtn');
const mapView = document.getElementById('mapView');
const resultsCount = document.getElementById('resultsCount');

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
  // Initialize price range slider
  if (priceRange) {
    noUiSlider.create(priceRange, {
      start: [50, 500],
      connect: true,
      range: {
        'min': 50,
        'max': 500
      },
      step: 10
    });

    priceRange.noUiSlider.on('update', function(values, handle) {
      const value = parseInt(values[handle]);
      if (handle) {
        maxPrice.textContent = value === 500 ? '$500+' : `$${value}`;
      } else {
        minPrice.textContent = `$${value}`;
      }
    });
  }

  // Load hotels
  loadHotels();

  // Event listeners
  if (applyFiltersBtn) {
    applyFiltersBtn.addEventListener('click', applyFilters);
  }

  if (resetFiltersBtn) {
    resetFiltersBtn.addEventListener('click', resetFilters);
  }

  if (sortBy) {
    sortBy.addEventListener('change', sortHotels);
  }

  if (listViewBtn && mapViewBtn) {
    listViewBtn.addEventListener('click', () => switchView('list'));
    mapViewBtn.addEventListener('click', () => switchView('map'));
  }
});

// Load hotels from API
async function loadHotels(filters = {}) {
  // Show loading state
  if (hotelsList) {
    showLoading(hotelsList);
  }

  try {
    // Import API service
    const { apiService } = await import('../js/services/api.service.js');

    // Call API
    let hotels = [];
    try {
        const response = await apiService.getHotels();
        hotels = response.data || [];
    } catch (apiError) {
        console.warn('API not available, using sample data:', apiError);
        // Fall back to sample data if API fails
        hotels = sampleHotels;
    }

    // Apply client-side filters (in production, this should be server-side)
    if (filters.minPrice) {
      hotels = hotels.filter(hotel => hotel.price >= filters.minPrice);
    }
    if (filters.maxPrice) {
      hotels = hotels.filter(hotel => hotel.price <= filters.maxPrice);
    }
    if (filters.amenities && filters.amenities.length > 0) {
      hotels = hotels.filter(hotel =>
        filters.amenities.every(amenity => hotel.amenities && hotel.amenities.includes(amenity))
      );
    }
    if (filters.rating) {
      hotels = hotels.filter(hotel => hotel.rating >= filters.rating);
    }

    // Update results count
    if (resultsCount) {
      resultsCount.textContent = `${hotels.length} hotels found`;
    }

    // Render hotels
    renderHotels(hotels);
  } catch (error) {
    console.error('Error loading hotels:', error);
    if (hotelsList) {
      hotelsList.innerHTML = `
        <div class="col-12 text-center py-5">
          <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
          <h4>Error loading hotels</h4>
          <p class="text-muted">${error.message}</p>
        </div>
      `;
    }
  }
}

// Render hotels list
function renderHotels(hotels) {
  if (!hotelsList) return;

  if (hotels.length === 0) {
    hotelsList.innerHTML = `
      <div class="col-12 text-center py-5">
        <i class="fas fa-hotel fa-3x text-muted mb-3"></i>
        <h4>No hotels found</h4>
        <p class="text-muted">Try adjusting your search or filters</p>
        <button class="btn btn-outline-primary mt-2" onclick="resetFilters()">Reset Filters</button>
      </div>
    `;
    return;
  }

  hotelsList.innerHTML = hotels.map(hotel => {
    const amenities = Array.isArray(hotel.amenities) ? hotel.amenities : (hotel.amenities ? JSON.parse(hotel.amenities) : []);
    const rating = hotel.rating || 4.0;
    const price = hotel.price || 150; // Default price since API doesn't provide it
    const image = hotel.image || 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?ixlib=rb-1.2.1&auto=format&fit=crop&w=634&q=80';

    return `
      <div class="col-12 mb-4">
        <div class="card hotel-card">
          <div class="row g-0">
            <div class="col-md-4 position-relative">
              <img src="${image}" class="img-fluid rounded-start h-100" alt="${hotel.name}" style="object-fit: cover; min-height: 200px;">
              <button class="btn btn-sm btn-light position-absolute top-0 end-0 m-2 rounded-circle"
                      onclick="toggleFavorite(${hotel.id}, this)">
                <i class="far fa-heart text-danger"></i>
              </button>
            </div>
            <div class="col-md-5">
              <div class="card-body h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div>
                    <h5 class="card-title mb-1">${hotel.name}</h5>
                    <p class="text-muted mb-2">
                      <i class="fas fa-map-marker-alt text-primary"></i> ${hotel.location}
                    </p>
                  </div>
                  <div class="text-end">
                    <div class="rating mb-1">
                      ${renderRatingStars(rating)}
                    </div>
                    <small class="text-muted">Rating: ${rating}/5</small>
                  </div>
                </div>

                <div class="amenities mb-3">
                  ${renderAmenities(amenities)}
                </div>

                <div class="mt-auto">
                  <a href="hotel-details.html?id=${hotel.id}" class="btn btn-link p-0">View details <i class="fas fa-chevron-right small"></i></a>
                </div>
              </div>
            </div>
            <div class="col-md-3 bg-light p-4 d-flex flex-column">
              <div class="text-end mb-3">
                <p class="h4 mb-0">$${price}</p>
                <small class="text-muted">per night</small>
                <p class="text-success small mb-0">Free cancellation</p>
              </div>
              <button onclick="bookHotel(${hotel.id}, '${hotel.name}')" class="btn btn-primary w-100 mt-auto">Book Now</button>
            </div>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// Render rating stars
function renderRatingStars(rating) {
  const fullStars = Math.floor(rating);
  const hasHalfStar = rating % 1 >= 0.5;
  let stars = '';
  
  for (let i = 1; i <= 5; i++) {
    if (i <= fullStars) {
      stars += '<i class="fas fa-star text-warning"></i>';
    } else if (i === fullStars + 1 && hasHalfStar) {
      stars += '<i class="fas fa-star-half-alt text-warning"></i>';
    } else {
      stars += '<i class="far fa-star text-warning"></i>';
    }
  }
  
  return stars;
}

// Render amenities
function renderAmenities(amenities) {
  const amenityIcons = {
    wifi: '<i class="fas fa-wifi" data-bs-toggle="tooltip" title="Free WiFi"></i>',
    pool: '<i class="fas fa-swimming-pool" data-bs-toggle="tooltip" title="Swimming Pool"></i>',
    restaurant: '<i class="fas fa-utensils" data-bs-toggle="tooltip" title="Restaurant"></i>',
    gym: '<i class="fas fa-dumbbell" data-bs-toggle="tooltip" title="Fitness Center"></i>',
    parking: '<i class="fas fa-parking" data-bs-toggle="tooltip" title="Parking"></i>',
    spa: '<i class="fas fa-spa" data-bs-toggle="tooltip" title="Spa"></i>',
    beach: '<i class="fas fa-umbrella-beach" data-bs-toggle="tooltip" title="Beach Access"></i>'
  };
  
  return amenities.map(amenity => 
    `<span class="badge bg-light text-dark me-2 mb-2">${amenityIcons[amenity] || ''}</span>`
  ).join('');
}

// Apply filters
function applyFilters() {
  const filters = {};
  
  // Get price range
  if (priceRange && priceRange.noUiSlider) {
    const [min, max] = priceRange.noUiSlider.get();
    filters.minPrice = parseInt(min);
    filters.maxPrice = parseInt(max);
  }
  
  // Get selected amenities
  const selectedAmenities = [];
  document.querySelectorAll('.amenities input[type="checkbox"]:checked').forEach(checkbox => {
    selectedAmenities.push(checkbox.value);
  });
  
  if (selectedAmenities.length > 0) {
    filters.amenities = selectedAmenities;
  }
  
  // Get selected rating
  const ratingCheckbox = document.querySelector('input[name="rating"]:checked');
  if (ratingCheckbox) {
    filters.rating = parseInt(ratingCheckbox.value);
  }
  
  // Load hotels with filters
  loadHotels(filters);
}

// Reset all filters
function resetFilters() {
  // Reset price range
  if (priceRange && priceRange.noUiSlider) {
    priceRange.noUiSlider.set([50, 500]);
  }
  
  // Uncheck all checkboxes
  document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
    checkbox.checked = false;
  });
  
  // Reset sort
  if (sortBy) {
    sortBy.value = 'recommended';
  }
  
  // Reload hotels without filters
  loadHotels();
}

// Sort hotels
function sortHotels() {
  if (!sortBy) return;
  
  const sortValue = sortBy.value;
  // In a real app, this would be handled by the API
  console.log(`Sorting by: ${sortValue}`);
  // For demo, just reload the hotels
  loadHotels();
}

// Toggle favorite
function toggleFavorite(hotelId, button) {
   const icon = button.querySelector('i');
   const isFavorite = icon.classList.contains('fas');

   if (isFavorite) {
       icon.classList.remove('fas');
       icon.classList.add('far');
   } else {
       icon.classList.remove('far');
       icon.classList.add('fas');
       // Add animation
       button.classList.add('animate__animated', 'animate__heartBeat');
       setTimeout(() => {
           button.classList.remove('animate__animated', 'animate__heartBeat');
       }, 1000);
   }

   // In a real app, update the favorite status via API
   console.log(`Hotel ${hotelId} ${isFavorite ? 'removed from' : 'added to'} favorites`);
}

// Handle hotel booking
window.bookHotel = async function(hotelId, hotelName) {
   if (!authService.isAuthenticated()) {
       alert('Please login to book a hotel');
       window.location.href = 'login.html';
       return;
   }

   // Show booking modal
   const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
   document.getElementById('bookingModalLabel').textContent = `Book ${hotelName}`;
   bookingModal.show();

   // Set minimum dates
   const today = new Date().toISOString().split('T')[0];
   document.getElementById('checkIn').min = today;
   document.getElementById('checkOut').min = today;

   // Handle booking confirmation
   document.getElementById('confirmBooking').onclick = async function() {
       const checkIn = document.getElementById('checkIn').value;
       const checkOut = document.getElementById('checkOut').value;
       const guests = document.getElementById('guests').value;
       const specialRequests = document.getElementById('specialRequests').value;

       if (!checkIn || !checkOut) {
           alert('Please select check-in and check-out dates');
           return;
       }

       try {
           const result = await apiService.createBooking({
               hotel_id: hotelId,
               check_in: checkIn,
               check_out: checkOut,
               guest_count: guests,
               special_requests: specialRequests
           });

           alert('Booking created successfully!');
           bookingModal.hide();
           window.location.href = 'bookings.html';
       } catch (error) {
           alert('Booking failed: ' + (error.message || 'Unknown error'));
       }
   };
};

// Switch between list and map view
function switchView(view) {
  if (view === 'map') {
    listViewBtn.classList.remove('active');
    mapViewBtn.classList.add('active');
    if (mapView) mapView.style.display = 'block';
    if (hotelsList) hotelsList.style.display = 'none';
  } else {
    listViewBtn.classList.add('active');
    mapViewBtn.classList.remove('active');
    if (mapView) mapView.style.display = 'none';
    if (hotelsList) hotelsList.style.display = 'block';
  }
}
