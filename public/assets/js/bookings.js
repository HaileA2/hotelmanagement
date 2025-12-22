// Bookings page JavaScript
import { authService } from '../js/services/auth.service.js';
import apiService from '../js/services/apiService.js';

document.addEventListener('DOMContentLoaded', function() {
    // Check authentication
    if (!authService.isAuthenticated()) {
        window.location.href = 'login.html';
        return;
    }

    // Update UI for logged-in user
    updateAuthUI();

    // Load bookings
    loadBookings();

    // Setup logout
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        authService.logout();
    });
});

function updateAuthUI() {
    const user = authService.getCurrentUser();
    if (user) {
        document.getElementById('loginBtn').style.display = 'none';
        document.getElementById('registerBtn').style.display = 'none';
        document.getElementById('userMenu').style.display = 'block';
        document.getElementById('userName').textContent = user.first_name || 'User';
    }
}

async function loadBookings() {
    const container = document.getElementById('bookingsContainer');
    const emptyState = document.getElementById('emptyState');

    try {
        // Get user bookings
        const response = await apiService.getBookings();
        const bookings = response.data || [];

        if (bookings.length === 0) {
            container.style.display = 'none';
            emptyState.style.display = 'block';
            return;
        }

        // Render bookings
        container.innerHTML = bookings.map(booking => createBookingCard(booking)).join('');

        // Add event listeners for booking actions
        document.querySelectorAll('.view-booking').forEach(btn => {
            btn.addEventListener('click', function() {
                const bookingId = this.dataset.bookingId;
                viewBookingDetails(bookingId);
            });
        });

        document.querySelectorAll('.cancel-booking').forEach(btn => {
            btn.addEventListener('click', function() {
                const bookingId = this.dataset.bookingId;
                cancelBooking(bookingId);
            });
        });

    } catch (error) {
        console.error('Error loading bookings:', error);
        showAlert('Failed to load bookings. Please try again.', 'danger');
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h4>Unable to load bookings</h4>
                <p class="text-muted">Please check your connection and try again.</p>
                <button class="btn btn-primary mt-3" onclick="loadBookings()">Retry</button>
            </div>
        `;
    }
}

function createBookingCard(booking) {
    const statusClass = getStatusClass(booking.status);
    const statusText = booking.status.charAt(0).toUpperCase() + booking.status.slice(1);

    return `
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="card-title mb-2">${booking.hotel_name || 'Hotel Name'}</h5>
                        <p class="card-text text-muted mb-2">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            ${booking.hotel_location || 'Location'}
                        </p>
                        <div class="row">
                            <div class="col-sm-6">
                                <small class="text-muted">Check-in</small>
                                <p class="mb-1"><i class="fas fa-calendar-check me-1"></i>${formatDate(booking.check_in)}</p>
                            </div>
                            <div class="col-sm-6">
                                <small class="text-muted">Check-out</small>
                                <p class="mb-1"><i class="fas fa-calendar-times me-1"></i>${formatDate(booking.check_out)}</p>
                            </div>
                        </div>
                        <p class="mb-2">
                            <i class="fas fa-bed me-1"></i>Room: ${booking.room_type || 'Standard Room'} |
                            <i class="fas fa-users me-1"></i>Guests: ${booking.guest_count || 1}
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge ${statusClass} mb-2">${statusText}</span>
                        <p class="h5 mb-3">$${booking.total_price || '0.00'}</p>
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-primary btn-sm view-booking"
                                    data-booking-id="${booking.id}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#bookingModal">
                                <i class="fas fa-eye me-1"></i>View
                            </button>
                            ${booking.status === 'confirmed' || booking.status === 'pending' ?
                                `<button class="btn btn-outline-danger btn-sm cancel-booking"
                                        data-booking-id="${booking.id}">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>` : ''
                            }
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function getStatusClass(status) {
    switch (status) {
        case 'confirmed': return 'bg-success';
        case 'pending': return 'bg-warning text-dark';
        case 'cancelled': return 'bg-danger';
        case 'completed': return 'bg-info';
        default: return 'bg-secondary';
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

async function viewBookingDetails(bookingId) {
    const modalBody = document.getElementById('bookingModalBody');

    modalBody.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading booking details...</p>
        </div>
    `;

    try {
        // In a real implementation, you'd have a specific API endpoint for booking details
        // For now, we'll simulate with the booking data we already have
        const response = await apiService.getBookings();
        const booking = response.data.find(b => b.id == bookingId);

        if (booking) {
            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Hotel Information</h6>
                        <p><strong>${booking.hotel_name || 'Hotel Name'}</strong></p>
                        <p><i class="fas fa-map-marker-alt me-1"></i>${booking.hotel_location || 'Location'}</p>
                        <p><i class="fas fa-phone me-1"></i>+1 (555) 123-4567</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Booking Details</h6>
                        <p><strong>Booking ID:</strong> #${booking.id}</p>
                        <p><strong>Status:</strong> <span class="badge ${getStatusClass(booking.status)}">${booking.status}</span></p>
                        <p><strong>Total Price:</strong> $${booking.total_price || '0.00'}</p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h6>Stay Information</h6>
                        <p><strong>Check-in:</strong> ${formatDate(booking.check_in)}</p>
                        <p><strong>Check-out:</strong> ${formatDate(booking.check_out)}</p>
                        <p><strong>Room Type:</strong> ${booking.room_type || 'Standard Room'}</p>
                        <p><strong>Guests:</strong> ${booking.guest_count || 1}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Special Requests</h6>
                        <p>${booking.special_requests || 'No special requests'}</p>
                    </div>
                </div>
            `;
        } else {
            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5>Booking not found</h5>
                    <p class="text-muted">Unable to load booking details.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading booking details:', error);
        modalBody.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h5>Error loading details</h5>
                <p class="text-muted">Please try again later.</p>
            </div>
        `;
    }
}

async function cancelBooking(bookingId) {
    if (!confirm('Are you sure you want to cancel this booking?')) {
        return;
    }

    try {
        await apiService.cancelBooking({ booking_id: bookingId });
        showAlert('Booking cancelled successfully.', 'success');
        loadBookings(); // Reload bookings
    } catch (error) {
        console.error('Error cancelling booking:', error);
        showAlert('Failed to cancel booking. Please try again.', 'danger');
    }
}

function showAlert(message, type = 'danger') {
    const alert = document.getElementById('bookingAlert');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.display = 'block';

    // Auto-hide after 5 seconds
    setTimeout(() => {
        alert.style.display = 'none';
    }, 5000);
}