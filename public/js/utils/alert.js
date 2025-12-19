// Show alert message
export function showAlert(message, type = 'info') {
    // Remove any existing alerts
    const existingAlert = document.getElementById('global-alert');
    if (existingAlert) {
        existingAlert.remove();
    }

    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.id = 'global-alert';
    alertDiv.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 ${
        type === 'error' ? 'bg-red-100 border-l-4 border-red-500 text-red-700' :
        type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' :
        'bg-blue-100 border-l-4 border-blue-500 text-blue-700'
    }`;
    
    alertDiv.innerHTML = `
        <div class="flex">
            <div class="flex-shrink-0">
                ${type === 'error' ? '❌' : type === 'success' ? '✅' : 'ℹ️'}
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium">
                    ${message}
                </p>
            </div>
            <div class="ml-4">
                <button type="button" class="close-alert-button">
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;

    // Add to body
    document.body.appendChild(alertDiv);

    // Auto-remove after 5 seconds
    const timeout = setTimeout(() => {
        alertDiv.remove();
    }, 5000);

    // Close button handler
    const closeButton = alertDiv.querySelector('.close-alert-button');
    closeButton.addEventListener('click', () => {
        clearTimeout(timeout);
        alertDiv.remove();
    });
}
