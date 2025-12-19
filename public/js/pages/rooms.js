import apiService from '../services/apiService.js';

export function initRoomsPage() {
    loadRooms();
    setupEventListeners();
}

async function loadRooms() {
    const container = document.getElementById('roomsContainer');
    UI.showLoading(container);

    try {
        const result = await apiService.getRooms();
        const rooms = result.data || [];

        if (rooms.length === 0) {
            container.innerHTML = '<div class="p-6 text-center text-gray-500">No rooms found.</div>';
            return;
        }

        renderRoomsTable(rooms, container);
    } catch (error) {
        console.error('Error loading rooms:', error);
        UI.showError('Failed to load rooms.', container);
    }
}

function renderRoomsTable(rooms, container) {
    container.innerHTML = `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    ${rooms.map(room => `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${room.room_number || room.number || 'N/A'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${room.type || room.room_type_name || 'Standard'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${UI.formatCurrency(room.price)}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    ${room.status === 'Available' ? 'bg-green-100 text-green-800' :
            room.status === 'Occupied' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'}">
                                    ${room.status}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="window.editRoom('${room.id}')" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                <button onclick="window.deleteRoom('${room.id}')" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;

    // Make helpers global for inline onclicks (simple approach) or add listeners properly
    window.editRoom = (id) => openModal(rooms.find(r => r.id == id));
    window.deleteRoom = handleDeleteRoom;
}

function setupEventListeners() {
    const modal = document.getElementById('roomModal');
    const addBtn = document.getElementById('addRoomBtn');
    const closeBtn = document.getElementById('closeModalBtn');
    const form = document.getElementById('roomForm');

    addBtn.addEventListener('click', () => openModal());
    closeBtn.addEventListener('click', () => modal.classList.add('hidden'));

    // Close on simple outside click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.add('hidden');
    });

    form.addEventListener('submit', handleFormSubmit);
}

function openModal(room = null) {
    const modal = document.getElementById('roomModal');
    const title = document.getElementById('modalTitle');
    const form = document.getElementById('roomForm');

    modal.classList.remove('hidden');

    if (room) {
        title.textContent = 'Edit Room';
        form.id.value = room.id;
        form.number.value = room.room_number || room.number;
        form.type.value = room.type; // Note: Select value must match options
        form.price.value = room.price;
        form.status.value = room.status;
    } else {
        title.textContent = 'Add New Room';
        form.reset();
        form.id.value = '';
    }
}

async function handleFormSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    const errorContainer = document.getElementById('errorContainer');

    // Simple validation
    if (!data.number || !data.price) {
        UI.showError('Please fill all required fields', errorContainer);
        return;
    }

    try {
        let result;
        if (data.id) {
            result = await apiService.updateRoom(data);
        } else {
            result = await apiService.createRoom(data);
        }

        if (result.status === 'success' || result.message?.includes('success')) {
            document.getElementById('roomModal').classList.add('hidden');
            loadRooms(); // Refresh list
        } else {
            throw new Error(result.message || 'Operation failed');
        }
    } catch (error) {
        console.error('Save room error:', error);
        UI.showError(error.message, errorContainer);
    }
}

async function handleDeleteRoom(id) {
    if (!confirm('Are you sure you want to delete this room?')) return;

    try {
        const result = await apiService.deleteRoom(id);
        if (result.status === 'success' || result.message?.includes('success')) {
            loadRooms();
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Delete error:', error);
        alert('Failed to delete room: ' + error.message);
    }
}
