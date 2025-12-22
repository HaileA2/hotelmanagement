// Rooms management page JavaScript
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

    // Load hotels and rooms
    loadHotels();
    loadRooms();

    // Setup event listeners
    document.getElementById('saveRoomBtn').addEventListener('click', saveRoom);

    // Setup logout
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        authService.logout();
    });
});

function updateAuthUI(user) {
    document.getElementById('userName').textContent = user.first_name || 'Manager';
}

async function loadHotels() {
    try {
        const response = await apiService.getHotels();
        const hotels = response.data || [];

        const hotelSelect = document.getElementById('hotelSelect');
        hotelSelect.innerHTML = '<option value="">Select Hotel</option>';

        hotels.forEach(hotel => {
            const option = document.createElement('option');
            option.value = hotel.id;
            option.textContent = hotel.name;
            hotelSelect.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading hotels:', error);
    }
}

async function loadRooms() {
    const container = document.getElementById('roomsContainer');

    try {
        // Note: The API doesn't have a getRooms endpoint yet, so we'll show a placeholder
        container.innerHTML = `
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-bed fa-3x text-muted mb-3"></i>
                    <h5>Room Management</h5>
                    <p class="text-muted">Room management functionality will be implemented here.</p>
                    <p class="text-muted small">This would allow managers to add, edit, and manage hotel rooms.</p>
                </div>
            </div>
        `;
    } catch (error) {
        console.error('Error loading rooms:', error);
        showAlert('Failed to load rooms. Please try again.', 'danger');
    }
}

function saveRoom() {
    // Get form data
    const hotelId = document.getElementById('hotelSelect').value;
    const roomType = document.getElementById('roomType').value;
    const price = document.getElementById('roomPrice').value;
    const maxOccupancy = document.getElementById('maxOccupancy').value;
    const status = document.getElementById('roomStatus').value;

    // Validation
    if (!hotelId || !roomType || !price || !maxOccupancy) {
        showAlert('Please fill in all required fields.', 'danger');
        return;
    }

    // Prepare room data
    const roomData = {
        hotel_id: hotelId,
        type: roomType,
        price_per_night: parseFloat(price),
        availability: status,
        max_occupancy: parseInt(maxOccupancy)
    };

    // In a real implementation, this would call the API
    console.log('Saving room:', roomData);
    showAlert('Room saved successfully! (This is a placeholder)', 'success');

    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('roomModal'));
    if (modal) {
        modal.hide();
    }

    // Reset form
    document.getElementById('roomForm').reset();
}

function showAlert(message, type = 'danger') {
    const alert = document.getElementById('roomsAlert');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.display = 'block';

    // Auto-hide after 5 seconds
    setTimeout(() => {
        alert.style.display = 'none';
    }, 5000);
}