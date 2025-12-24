/** assets/js/bookings.js */
import { authService } from './services/auth.service.js'; // Fixed path
import apiService from './services/apiService.js';      // Fixed path

document.addEventListener('DOMContentLoaded', function() {
    if (!authService.isAuthenticated()) {
        window.location.href = 'login.html';
        return;
    }

    updateAuthUI();
    loadBookings();

    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            authService.logout();
        });
    }
});

function updateAuthUI() {
    const user = authService.getCurrentUser();
    const userMenu = document.getElementById('userMenu');
    const userName = document.getElementById('userName');
    if (user && userMenu && userName) {
        document.getElementById('loginBtn').style.display = 'none';
        document.getElementById('registerBtn').style.display = 'none';
        userMenu.style.display = 'block';
        userName.textContent = user.first_name || 'User';
    }
}

async function loadBookings() {
    const container = document.getElementById('bookingsContainer');
    const emptyState = document.getElementById('emptyState');

    try {
        const response = await apiService.getBookings();
        const bookings = response.data || [];

        if (bookings.length === 0) {
            container.style.display = 'none';
            emptyState.style.display = 'block';
            return;
        }

        emptyState.style.display = 'none';
        container.style.display = 'block';
        container.innerHTML = bookings.map(booking => createBookingCard(booking)).join('');

        // Event Delegation for View and Cancel
        container.addEventListener('click', (e) => {
            const viewBtn = e.target.closest('.view-booking');
            const cancelBtn = e.target.closest('.cancel-booking');
            
            if (viewBtn) viewBookingDetails(viewBtn.dataset.bookingId);
            if (cancelBtn) cancelBooking(cancelBtn.dataset.bookingId);
        });

    } catch (error) {
        console.error('Error loading bookings:', error);
        showAlert('Failed to load bookings. Please try again.', 'danger');
    }
}

function createBookingCard(booking) {
    const statusClass = getStatusClass(booking.status);
    // Note: API returns 'checkInDate' and 'checkOutDate' based on your list_bookings.php item mapping
    return `
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="card-title mb-2 text-primary">${booking.hotel_name}</h5>
                        <p class="card-text text-muted mb-3">
                            <i class="fas fa-map-marker-alt me-1"></i>${booking.hotel_location || 'Location'}
                        </p>
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted d-block">Check-in</small>
                                    <strong><i class="fas fa-calendar-check me-2 text-success"></i>${formatDate(booking.checkInDate)}</strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted d-block">Check-out</small>
                                    <strong><i class="fas fa-calendar-times me-2 text-danger"></i>${formatDate(booking.checkOutDate)}</strong>
                                </div>
                            </div>
                        </div>
                        <p class="mb-0">
                            <span class="me-3"><i class="fas fa-bed me-1"></i> ${booking.room_type}</span>
                            <span><i class="fas fa-users me-1"></i> Guests: ${booking.guest_count}</span>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0 border-start-md">
                        <span class="badge ${statusClass} mb-3 p-2 px-3">${booking.status}</span>
                        <h4 class="mb-3">$${booking.room_price} <small class="text-muted" style="font-size: 0.6em;">/night</small></h4>
                        <div class="d-grid gap-2 d-md-block">
                            <button class="btn btn-outline-primary btn-sm view-booking" 
                                    data-booking-id="${booking.id}" 
                                    data-bs-toggle="modal" data-bs-target="#bookingModal">
                                <i class="fas fa-eye me-1"></i> Details
                            </button>
                            ${(booking.status.toLowerCase() === 'pending' || booking.status.toLowerCase() === 'confirmed') ? 
                                `<button class="btn btn-outline-danger btn-sm cancel-booking" data-booking-id="${booking.id}">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </button>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function getStatusClass(status) {
    const s = status.toLowerCase();
    if (s === 'confirmed' || s === 'completed') return 'bg-success';
    if (s === 'pending') return 'bg-warning text-dark';
    if (s === 'cancelled') return 'bg-danger';
    return 'bg-secondary';
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
}

async function viewBookingDetails(bookingId) {
    const modalBody = document.getElementById('bookingModalBody');
    
    // Show Loading Spinner
    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Retrieving receipt details...</p>
        </div>
    `;

    try {
        const response = await apiService.getBookings();
        const booking = response.data.find(b => b.id == bookingId);

        if (booking) {
            modalBody.innerHTML = `
                <div class="container-fluid">
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <h6 class="text-muted text-uppercase small">Hotel Information</h6>
                            <h4 class="text-primary mb-1">${booking.hotel_name}</h4>
                            <p class="text-muted mb-0"><i class="fas fa-map-marker-alt me-1"></i> ${booking.hotel_location || 'Address not available'}</p>
                        </div>
                        <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                            <h6 class="text-muted text-uppercase small">Booking ID</h6>
                            <h4 class="mb-1">#${booking.id}</h4>
                            <span class="badge ${getStatusClass(booking.status)}">${booking.status}</span>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-light p-3 rounded me-3">
                                    <i class="fas fa-calendar-alt fa-lg text-primary"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Check-in / Check-out</small>
                                    <strong>${formatDate(booking.checkInDate)}</strong> 
                                    <i class="fas fa-long-arrow-alt-right mx-2 text-muted"></i> 
                                    <strong>${formatDate(booking.checkOutDate)}</strong>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-light p-3 rounded me-3">
                                    <i class="fas fa-bed fa-lg text-primary"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Accomodation</small>
                                    <strong>${booking.room_type}</strong> (Room #${booking.roomNumber})
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-light p-3 rounded me-3">
                                    <i class="fas fa-user fa-lg text-primary"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Guest Contact</small>
                                    <strong>${booking.guestName || 'Main Guest'}</strong><br>
                                    <small class="text-muted">${booking.guestEmail}</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-light p-3 rounded me-3">
                                    <i class="fas fa-users fa-lg text-primary"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Party Size</small>
                                    <strong>${booking.guest_count} Guest(s)</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="small text-uppercase text-muted"><i class="fas fa-comment-alt me-2"></i>Special Requests</h6>
                        <p class="mb-0 italic">${booking.special_requests || 'No special requirements noted for this stay.'}</p>
                    </div>

                    <div class="mt-4 border-top pt-3 text-end">
                        <h6 class="text-muted mb-1">Total Price Paid</h6>
                        <h3 class="text-primary">$${booking.room_price}</h3>
                        <small class="text-muted">Booked on: ${new Date(booking.created_at).toLocaleString()}</small>
                    </div>
                    <div class="mt-4">
                        <h6 class="text-muted mb-1">Total Price Paid</h6>
                        <h3 class="text-primary">$${booking.total_price}</h3>
                    </div>
                </div>
            `;
        } else {
            throw new Error("Booking not found");
        }
    } catch (error) {
        modalBody.innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="fas fa-exclamation-circle me-2"></i> Error loading details: ${error.message}
            </div>
        `;
    }
}

async function cancelBooking(bookingId) {
    if (!confirm('Are you sure you want to cancel this booking?')) return;

    // Find the button to show loading state
    const cancelBtn = document.querySelector(`.cancel-booking[data-booking-id="${bookingId}"]`);
    const originalContent = cancelBtn.innerHTML;

    try {
        // Disable UI during request
        cancelBtn.disabled = true;
        cancelBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span>`;

        // The key must match your PHP $data->booking_id
        const response = await apiService.cancelBooking({ booking_id: bookingId });

        if (response.success) {
            showAlert('Booking cancelled successfully.', 'success');
            // Re-fetch the list to see the "Cancelled" badge
            await loadBookings();
        } else {
            throw new Error(response.message || 'Failed to cancel');
        }
    } catch (error) {
        console.error('Cancellation Error:', error);
        showAlert(error.message || 'Could not cancel booking. Please try again.', 'danger');
        
        // Reset button if failed
        cancelBtn.disabled = false;
        cancelBtn.innerHTML = originalContent;
    }
}

function showAlert(message, type = 'danger') {
    const alert = document.getElementById('bookingAlert');
    if (alert) {
        alert.className = `alert alert-${type} show`;
        alert.textContent = message;
        alert.style.display = 'block';
        setTimeout(() => alert.style.display = 'none', 5000);
    }
}