// DOM Elements
const dateRangeInput = document.getElementById('dateRange');
const adultsSelect = document.getElementById('adults');
const childrenSelect = document.getElementById('children');
const promoCodeInput = document.getElementById('promoCode');
const checkAvailabilityForm = document.getElementById('checkAvailabilityForm');
const reviewForm = document.getElementById('reviewForm');
const ratingInputs = document.querySelectorAll('.rating-input input[type="radio"]');
const reviewPhotosInput = document.getElementById('reviewPhotos');
const reviewPhotosContainer = document.querySelector('.review-photos');
const roomCards = document.querySelectorAll('.room-card');
const viewAllRoomsBtn = document.querySelector('.view-all-rooms');

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
  // Initialize date range picker
  if (dateRangeInput) {
    $(dateRangeInput).daterangepicker({
      opens: 'left',
      minDate: new Date(),
      startDate: moment().add(1, 'days'),
      endDate: moment().add(3, 'days'),
      locale: {
        format: 'MMM D, YYYY'
      }
    });
  }

  // Initialize tooltips
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(tooltipTriggerEl => {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Event listeners
  if (checkAvailabilityForm) {
    checkAvailabilityForm.addEventListener('submit', handleCheckAvailability);
  }

  if (reviewForm) {
    reviewForm.addEventListener('submit', handleReviewSubmit);
  }

  if (reviewPhotosInput) {
    reviewPhotosInput.addEventListener('change', handlePhotoUpload);
  }

  // Rating stars interaction
  ratingInputs.forEach(input => {
    input.addEventListener('change', function() {
      const value = this.value;
      updateRatingStars(value);
    });
  });

  // Room card hover effect
  roomCards.forEach(card => {
    card.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-5px)';
      this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
    });
    
    card.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0)';
      this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.05)';
    });
  });

  // View all rooms button
  if (viewAllRoomsBtn) {
    viewAllRoomsBtn.addEventListener('click', function(e) {
      e.preventDefault();
      // In a real app, this would load more rooms via AJAX
      this.textContent = 'All rooms loaded';
      this.disabled = true;
    });
  }

  // Initialize image gallery
  initImageGallery();
});

// Handle check availability form submission
function handleCheckAvailability(e) {
  e.preventDefault();
  
  const formData = {
    checkIn: $('#dateRange').data('daterangepicker').startDate.format('YYYY-MM-DD'),
    checkOut: $('#dateRange').data('daterangepicker').endDate.format('YYYY-MM-DD'),
    adults: adultsSelect ? adultsSelect.value : 2,
    children: childrenSelect ? childrenSelect.value : 0,
    promoCode: promoCodeInput ? promoCodeInput.value : ''
  };
  
  console.log('Checking availability:', formData);
  
  // In a real app, this would be an API call to check room availability
  // For now, just show a success message
  showAlert('success', 'Rooms are available for your selected dates!');
  
  // Scroll to rooms section
  const roomsSection = document.querySelector('.rooms-section');
  if (roomsSection) {
    roomsSection.scrollIntoView({ behavior: 'smooth' });
  }
}

// Handle review form submission
function handleReviewSubmit(e) {
  e.preventDefault();
  
  const formData = {
    rating: document.querySelector('input[name="rating"]:checked')?.value,
    title: document.getElementById('reviewTitle')?.value,
    comment: document.getElementById('reviewText')?.value,
    isAnonymous: document.getElementById('anonymousReview')?.checked
  };
  
  if (!formData.rating) {
    showAlert('error', 'Please select a rating');
    return;
  }
  
  if (!formData.title || !formData.comment) {
    showAlert('error', 'Please fill in all required fields');
    return;
  }
  
  console.log('Submitting review:', formData);
  
  // In a real app, this would be an API call to submit the review
  // For now, just show a success message and reset the form
  showAlert('success', 'Thank you for your review! It will be published after moderation.');
  reviewForm.reset();
  
  // Close the modal if it's open
  const reviewModal = bootstrap.Modal.getInstance(document.getElementById('writeReviewModal'));
  if (reviewModal) {
    reviewModal.hide();
  }
}

// Handle photo upload for reviews
function handlePhotoUpload(e) {
  const files = e.target.files;
  const maxFiles = 5;
  
  if (files.length > maxFiles) {
    showAlert('error', `You can upload a maximum of ${maxFiles} photos`);
    return;
  }
  
  // Clear previous previews
  if (reviewPhotosContainer) {
    reviewPhotosContainer.innerHTML = '';
  }
  
  // Show previews
  Array.from(files).forEach(file => {
    if (!file.type.match('image.*')) {
      showAlert('error', 'Only image files are allowed');
      return;
    }
    
    const reader = new FileReader();
    
    reader.onload = function(e) {
      const imgContainer = document.createElement('div');
      imgContainer.className = 'position-relative d-inline-block me-2 mb-2';
      imgContainer.innerHTML = `
        <img src="${e.target.result}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
        <button type="button" class="btn-close position-absolute top-0 end-0" aria-label="Remove"></button>
      `;
      
      // Add remove button functionality
      const removeBtn = imgContainer.querySelector('.btn-close');
      removeBtn.addEventListener('click', function() {
        imgContainer.remove();
      });
      
      if (reviewPhotosContainer) {
        reviewPhotosContainer.appendChild(imgContainer);
      }
    };
    
    reader.readAsDataURL(file);
  });
}

// Update rating stars UI
function updateRatingStars(value) {
  const stars = document.querySelectorAll('.rating-input .star i');
  
  stars.forEach((star, index) => {
    if (index < value) {
      star.className = 'fas fa-star';
    } else {
      star.className = 'far fa-star';
    }
  });
}

// Initialize image gallery
function initImageGallery() {
  // In a real app, this would load images from an API
  const galleryImages = [
    { src: 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?ixlib=rb-1.2.1&auto=format&fit=crop&w=634&q=80', title: 'Lobby' },
    { src: 'https://images.unsplash.com/photo-1590490360182-c33d57733427?ixlib=rb-1.2.1&auto=format&fit=crop&w=634&q=80', title: 'Deluxe Room' },
    { src: 'https://images.unsplash.com/photo-1566665797739-1674de7a421c?ixlib=rb-1.2.1&auto=format&fit=crop&w=634&q=80', title: 'Executive Suite' },
    { src: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-1.2.1&auto=format&fit=crop&w=634&q=80', title: 'Swimming Pool' },
    { src: 'https://images.unsplash.com/photo-1414235077428-338989f6d9c3?ixlib=rb-1.2.1&auto=format&fit=crop&w=634&q=80', title: 'Restaurant' },
    { src: 'https://images.unsplash.com/photo-1537047902294-62a40c20a6ae?ixlib=rb-1.2.1&auto=format&fit=crop&w=634&q=80', title: 'Spa' }
  ];
  
  const galleryGrid = document.getElementById('galleryGrid');
  
  if (galleryGrid) {
    // Clear existing content
    galleryGrid.innerHTML = '';
    
    // Add gallery items
    galleryImages.forEach((image, index) => {
      const col = document.createElement('div');
      col.className = 'col-6 col-md-4 col-lg-3';
      col.innerHTML = `
        <a href="${image.src}" data-lightbox="hotel-gallery" data-title="${image.title}" class="d-block">
          <img src="${image.src}" class="img-fluid rounded mb-3" alt="${image.title}" style="width: 100%; height: 150px; object-fit: cover;">
        </a>
      `;
      galleryGrid.appendChild(col);
    });
  }
}

// Show alert message
function showAlert(type, message) {
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
  alertDiv.role = 'alert';
  alertDiv.style.zIndex = '9999';
  alertDiv.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  `;
  
  document.body.appendChild(alertDiv);
  
  // Auto-dismiss after 5 seconds
  setTimeout(() => {
    const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
    if (alert) {
      alert.close();
    }
  }, 5000);
}

// Toggle room details
function toggleRoomDetails(button) {
  const card = button.closest('.room-card');
  const details = card.querySelector('.room-details');
  
  if (details) {
    details.classList.toggle('show');
    
    if (details.classList.contains('show')) {
      button.innerHTML = 'Hide Details <i class="fas fa-chevron-up ms-1"></i>';
    } else {
      button.innerHTML = 'View Details <i class="fas fa-chevron-down ms-1"></i>';
    }
  }
}

// Initialize map (placeholder for actual map implementation)
function initMap() {
  // In a real app, this would initialize a map using Google Maps, Mapbox, etc.
  console.log('Map would be initialized here');
}

// Call initMap when the page loads
window.onload = initMap;
