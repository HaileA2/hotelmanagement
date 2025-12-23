import apiService from './services/apiService.js';
import { authService } from './services/auth.service.js';

// DOM Elements
const dateRangeInput = document.getElementById('dateRange');
const adultsSelect = document.getElementById('adults');
const childrenSelect = document.getElementById('children');
const checkAvailabilityForm = document.getElementById('checkAvailabilityForm');
const roomsContainer = document.getElementById('roomsGrid'); // Ensure this ID exists in your HTML

// Initialize the page
document.addEventListener('DOMContentLoaded', async function() {
    const urlParams = new URLSearchParams(window.location.search);
    const hotelId = urlParams.get('hotel_id');

    if (!hotelId) {
        showAlert('danger', 'No hotel selected. Redirecting...');
        setTimeout(() => window.location.href = 'hotels.html', 2000);
        return;
    }

    // 1. Initialize Date Range Picker
    initDateRangePicker();

    // 2. Load Hotel and Room Data
    await loadHotelContent(hotelId);

    // 3. Event Listeners
    if (checkAvailabilityForm) {
        checkAvailabilityForm.addEventListener('submit', (e) => handleCheckAvailability(e, hotelId));
    }
});

function initDateRangePicker() {
    if (dateRangeInput) {
        $(dateRangeInput).daterangepicker({
            opens: 'left',
            minDate: new Date(),
            startDate: moment().add(1, 'days'),
            endDate: moment().add(3, 'days'),
            locale: { format: 'MMM D, YYYY' }
        });
    }
}

async function loadHotelContent(hotelId) {
    try {
        // Fetch data from api/hotel/hotel_details.php
        const response = await apiService.getHotelDetails(hotelId);
        if (response.success) {
            const { hotel, rooms } = response.data;
            
            // Update Hotel UI
            document.title = `${hotel.name} - HotelHub`;
            document.getElementById('hotelName').textContent = hotel.name;
            document.getElementById('hotelLocation').textContent = hotel.location;
            document.getElementById('hotelDescription').textContent = hotel.description;
            
            // Render Rooms
            renderRooms(rooms, hotelId);
        }
    } catch (error) {
        console.error('Error loading hotel:', error);
        showAlert('danger', 'Failed to load hotel details.');
    }
}

function renderRooms(rooms, hotelId) {
    if (!roomsContainer) return;
    
    if (rooms.length === 0) {
        roomsContainer.innerHTML = '<div class="col-12"><p class="text-center">No rooms available for this hotel.</p></div>';
        return;
    }

    roomsContainer.innerHTML = rooms.map(room => `
        <div class="col-md-6 mb-4">
            <div class="card room-card h-100">
                <div class="card-body">
                    <h5 class="card-title">${room.type}</h5>
                    <p class="card-text text-muted">${room.description || 'Comfortable room with modern amenities.'}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="h4 mb-0 text-primary">$${room.price_per_night}<small class="text-muted">/night</small></span>
                        <button class="btn btn-primary book-now-btn" 
                                data-room-id="${room.id}" 
                                data-price="${room.price_per_night}">
                            Book Now
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    // Add event listeners to newly created buttons
    document.querySelectorAll('.book-now-btn').forEach(btn => {
        btn.addEventListener('click', () => handleBooking(btn.dataset.roomId, btn.dataset.price, hotelId));
    });
}

async function handleCheckAvailability(e, hotelId) {
    e.preventDefault();
    const startDate = $('#dateRange').data('daterangepicker').startDate.format('YYYY-MM-DD');
    const endDate = $('#dateRange').data('daterangepicker').endDate.format('YYYY-MM-DD');

    showAlert('info', 'Updating room availability...');
    
    try {
        // We filter rooms based on date availability
        const response = await apiService.getRooms(hotelId); 
        // Note: You can further refine this by calling a specific check_availability endpoint
        renderRooms(response.data, hotelId);
        showAlert('success', 'Availability updated for selected dates.');
    } catch (error) {
        showAlert('danger', 'Error checking availability.');
    }
}

async function handleBooking(roomId, price, hotelId) {
    if (!authService.isAuthenticated()) {
        showAlert('warning', 'Please login to book a room.');
        setTimeout(() => window.location.href = 'login.html', 1500);
        return;
    }

    const startDate = $('#dateRange').data('daterangepicker').startDate;
    const endDate = $('#dateRange').data('daterangepicker').endDate;
    const nights = endDate.diff(startDate, 'days');

    const bookingData = {
        hotel_id: hotelId,
        room_id: roomId,
        check_in: startDate.format('YYYY-MM-DD'),
        check_out: endDate.format('YYYY-MM-DD'),
        guest_count: adultsSelect.value,
        total_price: price * nights
    };

    try {
        const response = await apiService.createBooking(bookingData);
        if (response.booking_id) {
            showAlert('success', `Booking successful! Reference ID: ${response.booking_id}`);
            setTimeout(() => window.location.href = 'bookings.html', 2000);
        }
    } catch (error) {
        showAlert('danger', error.message || 'Booking failed.');
    }
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 5000);
}