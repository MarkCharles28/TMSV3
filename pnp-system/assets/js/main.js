/**
 * Main JavaScript file for the Municipal PNP System
 * Contains common functions used across the application
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize Bootstrap popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-hide flash messages after 5 seconds
    setTimeout(function() {
        const flashMessages = document.querySelectorAll('.alert-dismissible');
        flashMessages.forEach(function(message) {
            const bsAlert = new bootstrap.Alert(message);
            bsAlert.close();
        });
    }, 5000);
    
    // File input customization
    const fileInputs = document.querySelectorAll('.custom-file-input');
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'No file chosen';
            const nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Mark notifications as read
    const notificationLinks = document.querySelectorAll('.notification-item a');
    notificationLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            const notificationId = this.closest('.notification-item').dataset.id;
            if (notificationId) {
                markNotificationAsRead(notificationId);
            }
        });
    });
    
    // Add print functionality
    const printButtons = document.querySelectorAll('.btn-print');
    printButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            window.print();
        });
    });
    
    // Handle ticket status changes
    const statusSelect = document.getElementById('status-select');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
        });
    }
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Dynamic search functionality
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            const searchTerm = this.value.trim();
            if (searchTerm.length > 2) {
                performSearch(searchTerm);
            }
        }, 500));
    }
});

/**
 * Debounce function to limit how often a function can be called
 * @param {Function} func - The function to debounce
 * @param {number} delay - The delay in milliseconds
 * @returns {Function} - The debounced function
 */
function debounce(func, delay) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            func.apply(context, args);
        }, delay);
    };
}

/**
 * Mark notification as read via AJAX
 * @param {string} id - Notification ID
 */
function markNotificationAsRead(id) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'mark-notification-read.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            const notification = document.querySelector(`.notification-item[data-id="${id}"]`);
            if (notification) {
                notification.classList.remove('unread');
            }
            updateNotificationCount();
        }
    };
    xhr.send('notification_id=' + id);
}

/**
 * Update the notification badge count
 */
function updateNotificationCount() {
    const badge = document.querySelector('.badge');
    if (badge) {
        let count = parseInt(badge.textContent) - 1;
        if (count <= 0) {
            badge.style.display = 'none';
        } else {
            badge.textContent = count;
        }
    }
}

/**
 * Perform search via AJAX
 * @param {string} searchTerm - The search term
 */
function performSearch(searchTerm) {
    const xhr = new XMLHttpRequest();
    const resultsContainer = document.getElementById('search-results');
    
    xhr.open('GET', `search.php?q=${encodeURIComponent(searchTerm)}`, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            if (resultsContainer) {
                resultsContainer.innerHTML = xhr.responseText;
                resultsContainer.style.display = 'block';
            }
        }
    };
    xhr.send();
} 