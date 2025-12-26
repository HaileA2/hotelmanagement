// hotel.js - Hotel Listing and Filtering System

class HotelManager {
    constructor() {
        this.hotels = [];
        this.filteredHotels = [];
        this.currentPage = 1;
        this.itemsPerPage = 6;
        this.currentView = 'grid'; // 'grid' or 'list'
        this.filters = {
            searchQuery: '',
            minPrice: 0,
            maxPrice: 1000,
            ratings: [],
            amenities: [],
            sortBy: ''
        };
        
        this.initialize();
    }

    async initialize() {
        // Load hotels from API
        await this.loadHotels();
        
        // Initialize event listeners
        this.setupEventListeners();
        
        // Initialize price slider
        this.initPriceSlider();
        
        // Render initial hotels
        this.applyFilters();
    }

    async loadHotels() {
        try {
            const response = await fetch('https://hotel-management.lovestoblog.com/api/hotel/list_hotels.php');
            const data = await response.json();
            
            if (data.success && data.data) {
                // Clean up amenities data
                this.hotels = data.data.map(hotel => ({
                    ...hotel,
                    // Clean amenities array
                    amenities: this.cleanAmenities(hotel.amenities),
                    // Ensure price is a number
                    price_per_night: parseFloat(hotel.price_per_night) || 0,
                    // Ensure rating is a number
                    rating: parseFloat(hotel.rating) || 0
                }));
                
                // Find max price for slider
                const maxPrice = Math.max(...this.hotels.map(h => h.price_per_night));
                this.filters.maxPrice = Math.ceil(maxPrice / 100) * 100 || 1000;
                
                this.updatePriceSliderRange();
            }
        } catch (error) {
            console.error('Error loading hotels:', error);
            this.showError('Failed to load hotels. Please try again later.');
        }
    }

    cleanAmenities(amenities) {
        if (!amenities || !Array.isArray(amenities)) return [];
        
        return amenities.map(amenity => {
            // Remove quotes and brackets if present
            let cleanAmenity = amenity.toString()
                .replace(/[\[\]"]+/g, '')
                .replace(/&quot;/g, '"')
                .trim();
            
            // Common amenity mappings
            const amenityMap = {
                'WiFi': 'Free WiFi',
                'Pool': 'Swimming Pool',
                'Spa': 'Spa Services',
                'Gym': 'Fitness Center',
                'Breakfast Included': 'Free Breakfast'
            };
            
            return amenityMap[cleanAmenity] || cleanAmenity;
        }).filter(amenity => amenity);
    }

    initPriceSlider() {
        const slider = document.getElementById('priceSlider');
        if (!slider) return;
        
        noUiSlider.create(slider, {
            start: [0, this.filters.maxPrice],
            connect: true,
            range: {
                'min': 0,
                'max': this.filters.maxPrice
            },
            step: 10,
            format: {
                to: value => Math.round(value),
                from: value => parseFloat(value)
            }
        });

        slider.noUiSlider.on('update', (values) => {
            this.filters.minPrice = parseInt(values[0]);
            this.filters.maxPrice = parseInt(values[1]);
            document.getElementById('priceMin').textContent = `$${this.filters.minPrice}`;
            document.getElementById('priceMax').textContent = `$${this.filters.maxPrice}`;
        });
    }

    updatePriceSliderRange() {
        const slider = document.getElementById('priceSlider');
        if (slider && slider.noUiSlider) {
            slider.noUiSlider.updateOptions({
                range: {
                    'min': 0,
                    'max': this.filters.maxPrice
                }
            });
        }
    }

    setupEventListeners() {
        // Search input
        document.getElementById('searchInput').addEventListener('input', (e) => {
            this.filters.searchQuery = e.target.value.toLowerCase();
            this.debouncedApplyFilters();
        });

        // Search button
        document.getElementById('searchBtn').addEventListener('click', () => {
            this.applyFilters();
        });

        // Global search
        document.getElementById('globalSearch').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.filters.searchQuery = e.target.value.toLowerCase();
                this.applyFilters();
            }
        });

        document.getElementById('globalSearchBtn').addEventListener('click', () => {
            const searchInput = document.getElementById('globalSearch');
            this.filters.searchQuery = searchInput.value.toLowerCase();
            this.applyFilters();
        });

        // Sort select
        document.getElementById('sortSelect').addEventListener('change', (e) => {
            this.filters.sortBy = e.target.value;
            this.applyFilters();
        });

        // Rating filters
        document.querySelectorAll('.form-check-input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updateFiltersFromUI();
                this.debouncedApplyFilters();
            });
        });

        // Apply filters button
        document.getElementById('applyFilters').addEventListener('click', () => {
            this.applyFilters();
        });

        // Reset filters buttons
        document.getElementById('resetFilters').addEventListener('click', () => this.resetFilters());
        document.getElementById('resetFilters2').addEventListener('click', () => this.resetFilters());

        // View toggle buttons
        document.getElementById('viewGrid').addEventListener('click', () => this.setView('grid'));
        document.getElementById('viewList').addEventListener('click', () => this.setView('list'));

        // Pagination (delegated)
        document.getElementById('pagination').addEventListener('click', (e) => {
            if (e.target.closest('.page-link')) {
                e.preventDefault();
                const page = parseInt(e.target.closest('.page-link').dataset.page);
                if (page) {
                    this.goToPage(page);
                }
            }
        });
    }

    updateFiltersFromUI() {
        // Get selected ratings
        this.filters.ratings = [];
        document.querySelectorAll('.form-check-input[type="checkbox"]:checked').forEach(checkbox => {
            if (checkbox.id.startsWith('rating')) {
                this.filters.ratings.push(parseInt(checkbox.value));
            }
        });

        // Get selected amenities
        this.filters.amenities = [];
        document.querySelectorAll('.form-check-input[type="checkbox"]:checked').forEach(checkbox => {
            if (checkbox.id.startsWith('amenity')) {
                this.filters.amenities.push(checkbox.value);
            }
        });
    }

    debouncedApplyFilters = this.debounce(() => this.applyFilters(), 300);

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    applyFilters() {
        this.updateFiltersFromUI();
        
        // Apply filters
        this.filteredHotels = this.hotels.filter(hotel => {
            // Search query filter
            if (this.filters.searchQuery) {
                const searchTerm = this.filters.searchQuery.toLowerCase();
                const nameMatch = hotel.name.toLowerCase().includes(searchTerm);
                const locationMatch = hotel.location?.toLowerCase().includes(searchTerm) || false;
                const amenitiesMatch = hotel.amenities.some(amenity => 
                    amenity.toLowerCase().includes(searchTerm)
                );
                
                if (!nameMatch && !locationMatch && !amenitiesMatch) {
                    return false;
                }
            }

            // Price filter
            if (hotel.price_per_night < this.filters.minPrice || 
                hotel.price_per_night > this.filters.maxPrice) {
                return false;
            }

            // Rating filter
            if (this.filters.ratings.length > 0) {
                const hotelRating = Math.floor(hotel.rating);
                if (!this.filters.ratings.some(r => hotelRating >= r)) {
                    return false;
                }
            }

            // Amenities filter
            if (this.filters.amenities.length > 0) {
                const hotelAmenities = hotel.amenities.map(a => a.toLowerCase());
                const requiredAmenities = this.filters.amenities.map(a => a.toLowerCase());
                
                if (!requiredAmenities.every(req => 
                    hotelAmenities.some(hotelAmenity => hotelAmenity.includes(req))
                )) {
                    return false;
                }
            }

            return true;
        });

        // Apply sorting
        this.sortHotels();

        // Update UI
        this.updateResultsInfo();
        this.renderHotels();
        this.renderPagination();
    }

    sortHotels() {
        switch (this.filters.sortBy) {
            case 'rating_desc':
                this.filteredHotels.sort((a, b) => b.rating - a.rating);
                break;
            case 'rating_asc':
                this.filteredHotels.sort((a, b) => a.rating - b.rating);
                break;
            case 'name_asc':
                this.filteredHotels.sort((a, b) => a.name.localeCompare(b.name));
                break;
            case 'name_desc':
                this.filteredHotels.sort((a, b) => b.name.localeCompare(a.name));
                break;
            default:
                // Default: sort by rating (descending)
                this.filteredHotels.sort((a, b) => b.rating - a.rating);
        }
    }

    updateResultsInfo() {
        const resultsCount = document.getElementById('resultsCount');
        const resultsTitle = document.getElementById('resultsTitle');
        
        if (this.filteredHotels.length === 0) {
            resultsCount.textContent = 'No hotels found';
            resultsTitle.textContent = 'No Results';
            document.getElementById('noResults').style.display = 'block';
            document.getElementById('paginationContainer').style.display = 'none';
        } else {
            resultsCount.textContent = `${this.filteredHotels.length} hotels found`;
            resultsTitle.textContent = 'Available Hotels';
            document.getElementById('noResults').style.display = 'none';
            document.getElementById('paginationContainer').style.display = 'block';
        }
    }

    renderHotels() {
        const container = document.getElementById('hotelsContainer');
        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const pageHotels = this.filteredHotels.slice(startIndex, endIndex);

        if (this.currentView === 'grid') {
            container.innerHTML = this.renderGridView(pageHotels);
        } else {
            container.innerHTML = this.renderListView(pageHotels);
        }

        // Add click listeners to view details buttons
        document.querySelectorAll('.view-details-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const hotelId = parseInt(e.target.closest('.hotel-card').dataset.hotelId);
                this.showHotelDetails(hotelId);
            });
        });
    }

    renderGridView(hotels) {
        if (hotels.length === 0) {
            return '<div class="col-12 text-center py-5"><p class="text-muted">No hotels match your criteria.</p></div>';
        }

        return `
            <div class="row g-4">
                ${hotels.map(hotel => `
                    <div class="col-md-6 col-lg-4">
                        <div class="hotel-card shadow-sm" data-hotel-id="${hotel.id}">
                            <div class="hotel-img-placeholder">
                                <i class="fas fa-hotel fa-3x"></i>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0">${this.escapeHtml(hotel.name)}</h5>
                                    <span class="rating-badge">
                                        <i class="fas fa-star"></i> ${hotel.rating.toFixed(1)}
                                    </span>
                                </div>
                                
                                ${hotel.location ? `
                                    <p class="card-text text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        ${this.escapeHtml(hotel.location)}
                                    </p>
                                ` : ''}
                                
                                ${hotel.description ? `
                                    <p class="card-text mb-3 small">${this.truncateText(this.escapeHtml(hotel.description), 100)}</p>
                                ` : ''}
                                
                                ${hotel.amenities.length > 0 ? `
                                    <div class="amenities-list">
                                        ${hotel.amenities.slice(0, 3).map(amenity => `
                                            <span class="amenity-badge">${this.escapeHtml(amenity)}</span>
                                        `).join('')}
                                        ${hotel.amenities.length > 3 ? `<span class="amenity-badge">+${hotel.amenities.length - 3}</span>` : ''}
                                    </div>
                                ` : ''}
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div>
                                        <div class="price-tag">$${hotel.price_per_night.toFixed(2)}</div>
                                        <small class="text-muted">per night</small>
                                    </div>
                                    <button class="btn btn-outline-primary view-details-btn">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    renderListView(hotels) {
        if (hotels.length === 0) {
            return '<div class="col-12 text-center py-5"><p class="text-muted">No hotels match your criteria.</p></div>';
        }

        return `
            <div class="list-group">
                ${hotels.map(hotel => `
                    <div class="list-group-item list-group-item-action p-4 hotel-card" data-hotel-id="${hotel.id}">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <div class="hotel-img-placeholder rounded" style="height: 120px;">
                                    <i class="fas fa-hotel fa-2x"></i>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="mb-0">${this.escapeHtml(hotel.name)}</h5>
                                    <span class="rating-badge">
                                        <i class="fas fa-star"></i> ${hotel.rating.toFixed(1)}
                                    </span>
                                </div>
                                
                                ${hotel.location ? `
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        ${this.escapeHtml(hotel.location)}
                                    </p>
                                ` : ''}
                                
                                ${hotel.description ? `
                                    <p class="mb-2">${this.truncateText(this.escapeHtml(hotel.description), 150)}</p>
                                ` : ''}
                                
                                ${hotel.amenities.length > 0 ? `
                                    <div class="amenities-list">
                                        ${hotel.amenities.slice(0, 4).map(amenity => `
                                            <span class="amenity-badge">${this.escapeHtml(amenity)}</span>
                                        `).join('')}
                                    </div>
                                ` : ''}
                            </div>
                            <div class="col-md-3 text-end">
                                <div class="mb-3">
                                    <div class="price-tag">$${hotel.price_per_night.toFixed(2)}</div>
                                    <small class="text-muted">per night</small>
                                </div>
                                <button class="btn btn-primary view-details-btn w-100">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    renderPagination() {
        const totalPages = Math.ceil(this.filteredHotels.length / this.itemsPerPage);
        const pagination = document.getElementById('pagination');
        
        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }

        let html = '';
        
        // Previous button
        html += `
            <li class="page-item ${this.currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${this.currentPage - 1}" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `;

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= this.currentPage - 1 && i <= this.currentPage + 1)) {
                html += `
                    <li class="page-item ${i === this.currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            } else if (i === this.currentPage - 2 || i === this.currentPage + 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        // Next button
        html += `
            <li class="page-item ${this.currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${this.currentPage + 1}" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `;

        pagination.innerHTML = html;
    }

    goToPage(page) {
        if (page < 1 || page > Math.ceil(this.filteredHotels.length / this.itemsPerPage)) {
            return;
        }
        
        this.currentPage = page;
        this.renderHotels();
        
        // Update active state in pagination
        document.querySelectorAll('.page-link').forEach(link => {
            link.closest('.page-item').classList.remove('active');
            if (parseInt(link.dataset.page) === page) {
                link.closest('.page-item').classList.add('active');
            }
        });
        
        // Scroll to top of hotels container
        document.getElementById('hotelsContainer').scrollIntoView({ behavior: 'smooth' });
    }

    setView(view) {
        this.currentView = view;
        this.currentPage = 1;
        
        // Update button states
        document.getElementById('viewGrid').classList.toggle('active', view === 'grid');
        document.getElementById('viewList').classList.toggle('active', view === 'list');
        
        this.renderHotels();
        this.renderPagination();
    }

    showHotelDetails(hotelId) {
        const hotel = this.hotels.find(h => h.id === hotelId);
        if (!hotel) return;

        // Update modal content
        document.getElementById('modalHotelName').textContent = hotel.name;
        document.getElementById('modalRating').innerHTML = `
            <i class="fas fa-star"></i> ${hotel.rating.toFixed(1)}
        `;
        document.getElementById('modalLocation').innerHTML = hotel.location ? 
            `<i class="fas fa-map-marker-alt"></i> ${this.escapeHtml(hotel.location)}` :
            '<i class="fas fa-map-marker-alt"></i> Location not specified';
        document.getElementById('modalDescription').textContent = hotel.description || 'No description available.';
        document.getElementById('modalPrice').textContent = `$${hotel.price_per_night.toFixed(2)}`;
        document.getElementById('modalCreatedAt').textContent = new Date(hotel.created_at).toLocaleDateString();

        // Update amenities
        const amenitiesContainer = document.getElementById('modalAmenities');
        if (hotel.amenities.length > 0) {
            amenitiesContainer.innerHTML = hotel.amenities.map(amenity => 
                `<span class="modal-amenity">${this.escapeHtml(amenity)}</span>`
            ).join(' ');
        } else {
            amenitiesContainer.innerHTML = '<span class="text-muted">No amenities listed</span>';
        }

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('hotelDetailsModal'));
        modal.show();
    }

    resetFilters() {
        // Reset filter values
        this.filters = {
            searchQuery: '',
            minPrice: 0,
            maxPrice: Math.max(...this.hotels.map(h => h.price_per_night)) || 1000,
            ratings: [],
            amenities: [],
            sortBy: ''
        };

        // Reset UI elements
        document.getElementById('searchInput').value = '';
        document.getElementById('globalSearch').value = '';
        document.getElementById('sortSelect').value = '';
        
        // Uncheck all checkboxes
        document.querySelectorAll('.form-check-input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = false;
        });

        // Reset price slider
        const slider = document.getElementById('priceSlider');
        if (slider && slider.noUiSlider) {
            slider.noUiSlider.set([0, this.filters.maxPrice]);
        }

        // Reset view to grid
        this.setView('grid');

        // Apply filters
        this.applyFilters();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }

    showError(message) {
        const container = document.getElementById('hotelsContainer');
        container.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
            </div>
        `;
    }
}

// Utility function to show login modal
function showLoginModal() {
    const modal = new bootstrap.Modal(document.getElementById('loginModal'));
    modal.show();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.hotelManager = new HotelManager();
});