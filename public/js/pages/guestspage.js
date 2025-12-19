// public/js/pages/GuestsPage.js
import apiService from '../services/apiService.js';

class GuestsPage {
    constructor() {
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.guests = [];
        this.filteredGuests = [];
        this.countries = []; // Will be populated from API or a static list
        
        this.initElements();
        this.setupEventListeners();
        this.loadGuests();
        this.loadCountries();
    }

    initElements() {
        this.elements = {
            searchInput: document.getElementById('searchInput'),
            countryFilter: document.getElementById('countryFilter'),
            guestsTableBody: document.getElementById('guestsTableBody'),
            addGuestBtn: document.getElementById('addGuestBtn'),
            exportBtn: document.getElementById('exportBtn'),
            guestModal: document.getElementById('guestModal'),
            closeModal: document.getElementById('closeModal'),
            modalTitle: document.getElementById('modalTitle'),
            modalContent: document.getElementById('modalContent'),
            prevPage: document.getElementById('prevPage'),
            nextPage: document.getElementById('nextPage'),
            paginationNumbers: document.getElementById('paginationNumbers'),
            startItem: document.getElementById('startItem'),
            endItem: document.getElementById('endItem'),
            totalItems: document.getElementById('totalItems')
        };
    }

    async loadGuests() {
        try {
            const response = await apiService.getGuests();
            this.guests = response.data || [];
            this.filteredGuests = [...this.guests];
            this.renderGuests();
            this.setupPagination();
        } catch (error) {
            console.error('Error loading guests:', error);
            this.showError('Failed to load guests. Please try again.');
        }
    }

    loadCountries() {
        // This could be replaced with an API call to get countries
        this.countries = ['United States', 'Canada', 'United Kingdom', 'Australia', 'Germany', 'France', 'Japan'];
        const countryFilter = this.elements.countryFilter;
        
        this.countries.forEach(country => {
            const option = document.createElement('option');
            option.value = country;
            option.textContent = country;
            countryFilter.appendChild(option);
        });
    }

    renderGuests() {
        const start = (this.currentPage - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        const paginatedGuests = this.filteredGuests.slice(start, end);

        this.elements.guestsTableBody.innerHTML = paginatedGuests.length > 0
            ? paginatedGuests.map(guest => this.createGuestRow(guest)).join('')
            : '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No guests found</td></tr>';

        this.updatePaginationInfo();
    }

    createGuestRow(guest) {
        return `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-indigo-600 font-medium">${guest.firstName.charAt(0)}${guest.lastName.charAt(0)}</span>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">${guest.firstName} ${guest.lastName}</div>
                            <div class="text-sm text-gray-500">${guest.email}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${guest.phone || 'N/A'}</div>
                    <div class="text-sm text-gray-500">${guest.email}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${guest.country || 'N/A'}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${guest.lastStay ? new Date(guest.lastStay).toLocaleDateString() : 'N/A'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${guest.totalStays || 0}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button data-id="${guest.id}" class="text-indigo-600 hover:text-indigo-900 mr-3 view-guest">View</button>
                    <button data-id="${guest.id}" class="text-green-600 hover:text-green-900 edit-guest">Edit</button>
                </td>
            </tr>
        `;
    }

    // ... (rest of the GuestsPage implementation)
}

// Initialize the page
document.addEventListener('DOMContentLoaded', () => {
    new GuestsPage();
});