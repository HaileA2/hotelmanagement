// public/js/pages/RoomsPage.js
import apiService from '../services/apiService.js';

class RoomsPage {
    constructor() {
        this.rooms = [];
        this.filteredRooms = [];
        
        this.initElements();
        this.setupEventListeners();
        this.loadRooms();
    }

    initElements() {
        this.elements = {
            searchInput: document.getElementById('searchInput'),
            roomTypeFilter: document.getElementById('roomTypeFilter'),
            statusFilter: document.getElementById('statusFilter'),
            roomsGrid: document.getElementById('roomsGrid'),
            addRoomBtn: document.getElementById('addRoomBtn'),
            roomModal: document.getElementById('roomModal'),
            closeModal: document.getElementById('closeModal'),
            modalTitle: document.getElementById('modalTitle'),
            modalContent: document.getElementById('modalContent')
        };
    }

    async loadRooms() {
        try {
            const response = await apiService.getRooms();
            this.rooms = response.data || [];
            this.filteredRooms = [...this.rooms];
            this.renderRooms();
        } catch (error) {
            console.error('Error loading rooms:', error);
            this.showError('Failed to load rooms. Please try again.');
        }
    }

    renderRooms() {
        if (this.filteredRooms.length === 0) {
            this.elements.roomsGrid.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-hotel text-4xl text-gray-300 mb-2"></i>
                    <p class="text-gray-500">No rooms found</p>
                </div>
            `;
            return;
        }

        this.elements.roomsGrid.innerHTML = this.filteredRooms
            .map(room => this.createRoomCard(room))
            .join('');
    }

    createRoomCard(room) {
        return `
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="relative pb-2/3">
                    <img src="${room.imageUrl || 'https://via.placeholder.com/300x200'}" 
                         alt="${room.type} Room" 
                         class="h-48 w-full object-cover">
                    <div class="absolute top-2 right-2">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${this.getStatusBadgeClass(room.status)}">
                            ${room.status}
                        </span>
                    </div>
                </div>
                <div class="p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">${room.type} Room</h3>
                            <p class="text-sm text-gray-500">Room ${room.roomNumber}</p>
                        </div>
                        <span class="text-lg font-bold text-indigo-600">$${room.pricePerNight}/night</span>
                    </div>
                    <div class="mt-4 flex items-center text-sm text-gray-500">
                        <i class="fas fa-user-friends mr-2"></i>
                        <span>${room.capacity} Guests</span>
                        <i class="fas fa-ruler-combined ml-4 mr-2"></i>
                        <span>${room.size} sq.ft</span>
                    </div>
                    <div class="mt-4 flex justify-between items-center">
                        <button class="text-indigo-600 hover:text-indigo-900 text-sm font-medium view-room" 
                                data-id="${room.id}">
                            View Details
                        </button>
                        <button class="px-3 py-1 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 book-now" 
                                data-id="${room.id}">
                            Book Now
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // ... (rest of the RoomsPage implementation)
}

// Initialize the page
document.addEventListener('DOMContentLoaded', () => {
    new RoomsPage();
});