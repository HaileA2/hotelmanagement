// public/js/pages/BookingsPage.js
import apiService from '../services/apiService.js';
import flatpickr from 'https://cdn.jsdelivr.net/npm/flatpickr';

class BookingsPage {
    constructor() {
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.bookings = [];
        this.filteredBookings = [];
        
        this.initElements();
        this.setupEventListeners();
        this.loadBookings();
    }

    initElements() {
        this.elements = {
            searchInput: document.getElementById('searchInput'),
            statusFilter: document.getElementById('statusFilter'),
            dateRange: document.getElementById('dateRange'),
            bookingsTableBody: document.getElementById('bookingsTableBody'),
            newBookingBtn: document.getElementById('newBookingBtn'),
            bookingModal: document.getElementById('bookingModal'),
            closeModal: document.getElementById('closeModal'),
            modalTitle: document.getElementById('modalTitle'),
            modalContent: document.getElementById('modalContent'),
            prevPage: document.getElementById('prevPage'),
            nextPage: document.getElementById('nextPage'),
            paginationNumbers: document.getElementById('paginationNumbers'),
            startItem: document.getElementById('startItem'),
            endItem: document.getElementById('endItem'),
            totalItems: document.getElementById('totalItems')
    }

    setupEventListeners() {
        // Initialize date range picker
        if (window.flatpickr) {
            window.flatpickr(this.elements.dateRange, {
                mode: 'range',
                dateFormat: 'Y-m-d',
                onChange: () => this.filterBookings()
            });
        }

        // Sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
            });
        }

        // Search and filter events
        this.elements.searchInput.addEventListener('input', () => this.filterBookings());
        this.elements.statusFilter.addEventListener('change', () => this.filterBookings());

        // Modal events
        this.elements.newBookingBtn.addEventListener('click', () => this.openNewBookingModal());
        this.elements.closeModal.addEventListener('click', () => this.closeModal());

        // Pagination events
        this.elements.prevPage.addEventListener('click', () => this.changePage(this.currentPage - 1));
        this.elements.nextPage.addEventListener('click', () => this.changePage(this.currentPage + 1));

        // Logout functionality
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => {
                localStorage.removeItem('auth_token');
                window.location.href = 'login.html';
            });
        }

    filterBookings() {
        try {
            const response = await apiService.getBookings();
            this.bookings = response.data || [];
            this.filteredBookings = [...this.bookings];
            this.renderBookings();
            this.setupPagination();
        } catch (error) {
            console.error('Error loading bookings:', error);
            this.showError('Failed to load bookings. Please try again.');
        }
    }

    renderBookings() {
        const start = (this.currentPage - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        const paginatedBookings = this.filteredBookings.slice(start, end);

        this.elements.bookingsTableBody.innerHTML = paginatedBookings.length > 0
            ? paginatedBookings.map(booking => this.createBookingRow(booking)).join('')
            : '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No bookings found</td></tr>';

        this.updatePaginationInfo();
    }

    createBookingRow(booking) {
        return `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${booking.id}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${booking.guestName}</div>
                    <div class="text-sm text-gray-500">${booking.guestEmail}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${booking.roomType}</div>
                    <div class="text-sm text-gray-500">${booking.roomNumber}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${new Date(booking.checkInDate).toLocaleDateString()}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${new Date(booking.checkOutDate).toLocaleDateString()}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${this.getStatusBadgeClass(booking.status)}">
                        ${booking.status}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button data-id="${booking.id}" class="text-indigo-600 hover:text-indigo-900 mr-3 view-booking">View</button>
                    <button data-id="${booking.id}" class="text-green-600 hover:text-green-900 edit-booking">Edit</button>
                </td>
            </tr>
    }

    filterBookings() {
        const searchTerm = this.elements.searchInput.value.toLowerCase();
        const statusFilter = this.elements.statusFilter.value;
        const dateRange = this.elements.dateRange.value;

        this.filteredBookings = this.bookings.filter(booking => {
            const matchesSearch = !searchTerm ||
                booking.guestName.toLowerCase().includes(searchTerm) ||
                booking.guestEmail.toLowerCase().includes(searchTerm) ||
                booking.roomType.toLowerCase().includes(searchTerm);

            const matchesStatus = !statusFilter || booking.status === statusFilter;

            let matchesDate = true;
            if (dateRange) {
                const [startDate, endDate] = dateRange.split(' to ').map(d => new Date(d));
                const checkIn = new Date(booking.checkInDate);
                matchesDate = checkIn >= startDate && checkIn <= endDate;
            }

            return matchesSearch && matchesStatus && matchesDate;
        });

        this.currentPage = 1;
        this.renderBookings();
        this.setupPagination();
    }

    openNewBookingModal() {
        this.elements.modalTitle.textContent = 'New Booking';
        this.elements.modalContent.innerHTML = '<p>New booking form would go here.</p>';
        this.elements.bookingModal.classList.remove('hidden');
    }

    closeModal() {
        this.elements.bookingModal.classList.add('hidden');
    }

    changePage(page) {
        if (page < 1 || page > Math.ceil(this.filteredBookings.length / this.itemsPerPage)) return;
        this.currentPage = page;
        this.renderBookings();
        this.setupPagination();
    }

    viewBooking(id) {
        const booking = this.bookings.find(b => b.id == id);
        if (booking) {
            this.elements.modalTitle.textContent = 'View Booking';
            this.elements.modalContent.innerHTML = `
                <div class="space-y-4">
                    <p><strong>ID:</strong> ${booking.id}</p>
                    <p><strong>Guest:</strong> ${booking.guestName} (${booking.guestEmail})</p>
                    <p><strong>Room:</strong> ${booking.roomType} - ${booking.roomNumber}</p>
                    <p><strong>Check-in:</strong> ${new Date(booking.checkInDate).toLocaleDateString()}</p>
                    <p><strong>Check-out:</strong> ${new Date(booking.checkOutDate).toLocaleDateString()}</p>
                    <p><strong>Status:</strong> ${booking.status}</p>
                </div>
            `;
            this.elements.bookingModal.classList.remove('hidden');
        }
    }

    editBooking(id) {
        const booking = this.bookings.find(b => b.id == id);
        if (booking) {
            this.elements.modalTitle.textContent = 'Edit Booking';
            this.elements.modalContent.innerHTML = '<p>Edit booking form would go here.</p>';
            this.elements.bookingModal.classList.remove('hidden');
        }
    }

    getStatusBadgeClass(status) {
        switch (status.toLowerCase()) {
            case 'confirmed': return 'bg-green-100 text-green-800';
            case 'pending': return 'bg-yellow-100 text-yellow-800';
            case 'cancelled': return 'bg-red-100 text-red-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    }

    updatePaginationInfo() {
        const start = (this.currentPage - 1) * this.itemsPerPage + 1;
        const end = Math.min(start + this.itemsPerPage - 1, this.filteredBookings.length);
        this.elements.startItem.textContent = this.filteredBookings.length > 0 ? start : 0;
        this.elements.endItem.textContent = end;
        this.elements.totalItems.textContent = this.filteredBookings.length;
    }

    setupPagination() {
        const totalPages = Math.ceil(this.filteredBookings.length / this.itemsPerPage);
        this.elements.paginationNumbers.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
            const button = document.createElement('button');
            button.textContent = i;
            button.className = `px-3 py-1 mx-1 rounded ${i === this.currentPage ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700'}`;
            button.addEventListener('click', () => this.changePage(i));
            this.elements.paginationNumbers.appendChild(button);
        }

        this.elements.prevPage.disabled = this.currentPage === 1;
        this.elements.nextPage.disabled = this.currentPage === totalPages;
    }

    showError(message) {
        // Simple error display - could be enhanced
        alert(message);
    }

    // ... (rest of the BookingsPage implementation)
}

// Initialize the page
document.addEventListener('DOMContentLoaded', () => {
    new BookingsPage();
});