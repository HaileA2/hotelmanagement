import { authService } from '../js/services/auth.service.js';

document.addEventListener('DOMContentLoaded', async () => {
    if (!authService.isAuthenticated()) {
        window.location.href = 'login.html';
        return;
    }

    const user = authService.getCurrentUser();
    if (!['admin', 'manager'].includes(user.role.toLowerCase())) {
        window.location.href = 'index.html';
        return;
    }

    updateAuthUI(user);
    await loadHotels();
    await loadRooms();

    document.getElementById('saveRoomBtn').addEventListener('click', saveRoom);
    document.getElementById('logoutBtn').addEventListener('click', e => {
        e.preventDefault();
        authService.logout();
    });
});

function updateAuthUI(user) {
    document.getElementById('userName').textContent = user.first_name || 'Manager';
}

let editingRoomId = null;

// Load Hotels dropdown
async function loadHotels() {
    try {
        const token = authService.getToken();
        const response = await fetch('/hotelmanagement/api/hotel/list_hotels.php', {
            headers: { Authorization: `Bearer ${token}` }
        });
        const data = await response.json();
        const hotels = data.data || [];
        const hotelSelect = document.getElementById('hotelSelect');
        hotelSelect.innerHTML = '<option value="">Select Hotel</option>';
        hotels.forEach(h => {
            const option = document.createElement('option');
            option.value = h.id;
            option.textContent = h.name;
            hotelSelect.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading hotels:', error);
    }
}

// Load rooms
async function loadRooms() {
    const container = document.getElementById('roomsContainer');
    container.innerHTML = '';

    try {
        const response = await fetch('/hotelmanagement/api/room/list_rooms.php', {
            headers: { Authorization: `Bearer ${authService.getToken()}` }
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to fetch rooms');

        const rooms = data.data || [];
        if (rooms.length === 0) {
            container.innerHTML = `<p class="text-muted">No rooms available.</p>`;
            return;
        }

        const table = document.createElement('table');
        table.className = 'table table-striped';
        table.innerHTML = `
            <thead>
                <tr>
                    <th>#</th>
                    <th>Hotel</th>
                    <th>Room Number</th>
                    <th>Type</th>
                    <th>Price/Night</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${rooms.map((room, index) => `
                    <tr data-id="${room.id}">
                        <td>${index + 1}</td>
                        <td>${room.hotel_id}</td>
                        <td>${room.room_number || ''}</td>
                        <td>${room.type}</td>
                        <td>${room.price_per_night}</td>
                        <td>${room.capacity}</td>
                        <td>${room.status}</td>
                        <td>
                            <button class="btn btn-sm btn-primary edit-room">Edit</button>
                            <button class="btn btn-sm btn-danger delete-room">Delete</button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        `;
        container.appendChild(table);

        // Edit/Delete buttons
        container.querySelectorAll('.edit-room').forEach(btn => {
            btn.addEventListener('click', e => {
                const tr = e.target.closest('tr');
                editingRoomId = tr.getAttribute('data-id');
                editRoom(editingRoomId);
            });
        });
        container.querySelectorAll('.delete-room').forEach(btn => {
            btn.addEventListener('click', e => {
                const tr = e.target.closest('tr');
                deleteRoom(tr.getAttribute('data-id'));
            });
        });
    } catch (error) {
        console.error(error);
        showAlert(error.message || 'Failed to load rooms', 'danger');
    }
}

// Open modal for editing
async function editRoom(roomId) {
    try {
        const response = await fetch(`/hotelmanagement/api/room/list_rooms.php?id=${roomId}`, {
            headers: { Authorization: `Bearer ${authService.getToken()}` }
        });
        const data = await response.json();
        if (!data.success || !data.data || !data.data[0]) {
            showAlert('Failed to load room data', 'danger');
            return;
        }
        const room = data.data[0];
        document.getElementById('hotelSelect').value = room.hotel_id;
        document.getElementById('roomNumber').value = room.room_number;
        document.getElementById('roomType').value = room.type;
        document.getElementById('roomPrice').value = room.price_per_night;
        document.getElementById('maxOccupancy').value = room.capacity;
        document.getElementById('roomStatus').value = room.status;

        const modal = new bootstrap.Modal(document.getElementById('roomModal'));
        modal.show();
    } catch (error) {
        console.error(error);
        showAlert(error.message || 'Failed to load room', 'danger');
    }
}

// Save room
async function saveRoom() {
    const hotelId = document.getElementById('hotelSelect').value;
    const roomNumber = document.getElementById('roomNumber').value;
    const type = document.getElementById('roomType').value;
    const price = document.getElementById('roomPrice').value;
    const maxOccupancy = document.getElementById('maxOccupancy').value;
    const status = document.getElementById('roomStatus').value;

    if (!hotelId || !type || !price || !maxOccupancy) {
        showAlert('Please fill all required fields.', 'danger');
        return;
    }

    const roomData = {
        hotel_id: hotelId,
        room_number: roomNumber,
        type,
        price_per_night: parseFloat(price),
        capacity: parseInt(maxOccupancy),
        status
    };

    const url = editingRoomId
        ? `/hotelmanagement/api/room/update_room.php?id=${editingRoomId}`
        : `/hotelmanagement/api/room/create_room.php`;
    const method = editingRoomId ? 'PUT' : 'POST';

    try {
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${authService.getToken()}`
            },
            body: JSON.stringify(roomData)
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to save room');

        showAlert(data.message, 'success');
        editingRoomId = null;
        document.getElementById('roomForm').reset();
        const modal = bootstrap.Modal.getInstance(document.getElementById('roomModal'));
        if (modal) modal.hide();
        await loadRooms();
    } catch (error) {
        console.error(error);
        showAlert(error.message || 'Failed to save room', 'danger');
    }
}

// Delete room
async function deleteRoom(roomId) {
    if (!confirm('Are you sure you want to delete this room?')) return;
    try {
        const response = await fetch(`/hotelmanagement/api/room/delete_room.php?id=${roomId}`, {
            method: 'DELETE',
            headers: { Authorization: `Bearer ${authService.getToken()}` }
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to delete room');

        showAlert(data.message, 'success');
        await loadRooms();
    } catch (error) {
        console.error(error);
        showAlert(error.message || 'Failed to delete room', 'danger');
    }
}

// Alert utility
function showAlert(message, type = 'danger') {
    const alert = document.getElementById('roomsAlert');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.display = 'block';
    setTimeout(() => { alert.style.display = 'none'; }, 5000);
}
