// Dashboard page JavaScript
import { authService } from '../js/services/auth.service.js';
import apiService from '../js/services/apiService.js';

document.addEventListener('DOMContentLoaded', function() {
    // Check authentication and role
    if (!authService.isAuthenticated()) {
        window.location.href = 'login.html';
        return;
    }

    const user = authService.getCurrentUser();
    if (user.role !== 'Admin' && user.role !== 'Manager') {
        window.location.href = 'index.html';
        return;
    }

    // Update UI for logged-in admin/manager
    updateAuthUI(user);

    // Load dashboard data
    loadDashboardStats();
    loadRecentBookings();

    // Setup logout
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        authService.logout();
    });
});

function updateAuthUI(user) {
    document.getElementById('userName').textContent = user.first_name || 'Admin';
    document.getElementById('userRole').textContent = user.role;

    // Show manager actions for managers
    if (user.role === 'Manager') {
        document.getElementById('managerActions').style.display = 'block';
    }
}

async function loadDashboardStats() {
    try {
        // Load various statistics
        const [bookingsResponse, hotelsResponse] = await Promise.all([
            apiService.getBookings().catch(() => ({ data: [] })),
            apiService.getHotels().catch(() => ({ data: [] }))
        ]);

        const bookings = bookingsResponse.data || [];
        const hotels = hotelsResponse.data || [];

        // Update stats
        document.getElementById('totalBookings').textContent = bookings.length;
        document.getElementById('totalRooms').textContent = hotels.length * 10; // Estimate rooms per hotel
        document.getElementById('totalUsers').textContent = '1'; // Would need user API
        document.getElementById('totalRevenue').textContent = '$' + (bookings.length * 150); // Estimate

    } catch (error) {
        console.error('Error loading dashboard stats:', error);
        showAlert('Failed to load dashboard statistics.', 'danger');
    }
}

async function loadRecentBookings() {
    const container = document.getElementById('recentBookings');

    try {
        const response = await apiService.getBookings();
        const bookings = response.data || [];

        // Show last 5 bookings
        const recentBookings = bookings.slice(0, 5);

        if (recentBookings.length === 0) {
            container.innerHTML = '<p class="text-muted mb-0">No recent bookings</p>';
            return;
        }

        container.innerHTML = recentBookings.map(booking => `
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                <div>
                    <small class="text-muted">Booking #${booking.id}</small>
                    <p class="mb-0 small">${booking.hotel_name || 'Hotel'} - ${formatDate(booking.check_in)}</p>
                </div>
                <span class="badge ${getStatusClass(booking.status)}">${booking.status}</span>
            </div>
        `).join('');

    } catch (error) {
        console.error('Error loading recent bookings:', error);
        container.innerHTML = '<p class="text-danger small mb-0">Failed to load recent bookings</p>';
    }
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
        month: 'short',
        day: 'numeric'
    });
}

function showAlert(message, type = 'danger') {
    const alert = document.getElementById('dashboardAlert');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.display = 'block';

    // Auto-hide after 5 seconds
    setTimeout(() => {
        alert.style.display = 'none';
    }, 5000);
}