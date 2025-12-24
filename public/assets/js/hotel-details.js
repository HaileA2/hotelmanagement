/** public/assets/js/hotel-details.js */
import apiService from './services/apiService.js';
import { authService } from './services/auth.service.js';

// DOM Elements
const dateRangeInput = document.getElementById('dateRange');
const adultsSelect = document.getElementById('adults');
const childrenSelect = document.getElementById('children');
const checkAvailabilityForm = document.getElementById('checkAvailabilityForm');
const roomsContainer = document.getElementById('roomsGrid');

document.addEventListener('DOMContentLoaded', async function() {
    if (!authService.isAuthenticated()) {
        window.location.href = 'login.html?redirect=' + encodeURIComponent(window.location.href);
        return;
    }

    const urlParams = new URLSearchParams(window.location.search);
    const hotelId = urlParams.get('hotel_id');
    // Check if the user came here specifically to book
    const autoBook = urlParams.get('book') === 'true' || urlParams.get('open_booking') === 'true';

    if (!hotelId) {
        showAlert('danger', 'No hotel selected.');
        setTimeout(() => window.location.href = 'hotels.html', 2000);
        return;
    }

    initDateRangePicker();
    
    // Pass the autoBook flag to the loader
    await loadHotelContent(hotelId, {}, autoBook);

    if (checkAvailabilityForm) {
        checkAvailabilityForm.addEventListener('submit', (e) => handleCheckAvailability(e, hotelId));
    }
});

function initDateRangePicker() {
    if (dateRangeInput) {
        $(dateRangeInput).daterangepicker({
            opens: 'left',
            minDate: moment(),
            startDate: moment().add(1, 'days'),
            endDate: moment().add(3, 'days'),
            locale: { format: 'MMM D, YYYY' }
        });
    }
}

async function loadHotelContent(hotelId, filterParams = {}, autoBook = false) {
    try {
        const requestParams = { hotel_id: hotelId, ...filterParams };
        const response = await apiService.getHotelDetails(hotelId, requestParams);
        
        if (response.success) {
            const { hotel, rooms } = response.data;
            
            // UI Updates
            document.getElementById('hotelName').textContent = hotel.name;
            document.getElementById('hotelLocation').textContent = hotel.location;
            document.getElementById('hotelDescription').textContent = hotel.description;
            
            renderRooms(rooms, hotelId);

            // If autoBook is true, trigger the first available room's booking
            if (autoBook && rooms.length > 0) {
                const firstRoom = rooms[0];
                handleBooking(firstRoom.id, firstRoom.price_per_night, hotelId);
            }
        }
    } catch (error) {
        showAlert('danger', 'Failed to load hotel details.');
    }
}

function renderRooms(rooms, hotelId) {
    if (!roomsContainer) return;
    
    if (!rooms || rooms.length === 0) {
        roomsContainer.innerHTML = `
            <div class="col-12 text-center py-5 shadow-sm bg-light rounded">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <p class="text-muted h5">No rooms available for the selected criteria.</p>
            </div>`;
        return;
    }

    roomsContainer.innerHTML = rooms.map(room => `
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0">${room.type}</h5>
                        <span class="badge bg-info text-white">Room ${room.room_number}</span>
                    </div>
                    <p class="card-text text-muted small">${room.description || 'Modern amenities included.'}</p>
                    <div class="mb-3 small">
                        <span class="me-3"><i class="fas fa-users me-1"></i> Capacity: ${room.capacity}</span>
                        <span class="text-success"><i class="fas fa-check-circle me-1"></i> Available</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-top pt-3">
                        <div>
                            <span class="h4 mb-0 text-primary">$${room.price_per_night}</span>
                            <small class="text-muted">/night</small>
                        </div>
                        <button class="btn btn-primary book-now-btn" 
                                data-room-id="${room.id}" 
                                data-price="${room.price_per_night}">
                            Book Room
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    document.querySelectorAll('.book-now-btn').forEach(btn => {
        btn.addEventListener('click', () => handleBooking(btn.dataset.roomId, btn.dataset.price, hotelId));
    });
}

async function handleCheckAvailability(e, hotelId) {
    e.preventDefault();
    const drp = $(dateRangeInput).data('daterangepicker');
    
    const filterParams = {
        check_in: drp.startDate.format('YYYY-MM-DD'),
        check_out: drp.endDate.format('YYYY-MM-DD'),
        guests: parseInt(adultsSelect.value) + (parseInt(childrenSelect.value) || 0)
    };

    showAlert('info', 'Searching for available rooms...');
    await loadHotelContent(hotelId, filterParams);
}

async function handleBooking(roomId, price, hotelId) {
    const drp = $(dateRangeInput).data('daterangepicker');
    const nights = drp.endDate.diff(drp.startDate, 'days');
    const totalPrice = parseFloat(price) * nights;

    if (!confirm(`Confirm booking for ${nights} nights?\nTotal Price: $${totalPrice.toFixed(2)}`)) return;

    const bookingData = {
        hotel_id: parseInt(hotelId),
        room_id: parseInt(roomId),
        check_in: drp.startDate.format('YYYY-MM-DD'),
        check_out: drp.endDate.format('YYYY-MM-DD'),
        guest_count: parseInt(adultsSelect.value) + (parseInt(childrenSelect.value) || 0),
        total_price: totalPrice
    };

    try {
        const response = await apiService.createBooking(bookingData);
        if (response.success || response.booking_id) {
            showAlert('success', 'Booking Confirmed! Redirecting...');
            setTimeout(() => window.location.href = 'bookings.html', 2000);
        }
    } catch (error) {
        showAlert('danger', error.message || 'Booking failed. The room may have been taken.');
    }
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.style.zIndex = '10000';
    alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 4000);
}