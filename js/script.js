// University Companion WebApp - Main JavaScript File

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

// Form validation
function validateForm(formId) {
    var form = document.getElementById(formId);
    if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    form.classList.add('was-validated');
    return form.checkValidity();
}

// File upload validation
function validateFileUpload(input, allowedTypes, maxSize) {
    var file = input.files[0];
    var errorDiv = input.parentNode.querySelector('.file-error');
    
    if (errorDiv) {
        errorDiv.remove();
    }
    
    if (file) {
        // Check file type
        var fileType = file.type;
        if (allowedTypes && !allowedTypes.includes(fileType)) {
            showFileError(input, 'Invalid file type. Allowed types: ' + allowedTypes.join(', '));
            return false;
        }
        
        // Check file size (maxSize in MB)
        if (maxSize && file.size > maxSize * 1024 * 1024) {
            showFileError(input, 'File size too large. Maximum size: ' + maxSize + 'MB');
            return false;
        }
        
        return true;
    }
    return false;
}

function showFileError(input, message) {
    var errorDiv = document.createElement('div');
    errorDiv.className = 'file-error text-danger mt-2';
    errorDiv.innerHTML = '<small>' + message + '</small>';
    input.parentNode.appendChild(errorDiv);
}

// Confirm delete actions
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Show loading spinner
function showLoading(button) {
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Loading...';
    button.disabled = true;
}

// Hide loading spinner
function hideLoading(button, originalText) {
    button.innerHTML = originalText;
    button.disabled = false;
}

// Format date for display
function formatDate(dateString) {
    var date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Search functionality
function searchTable(inputId, tableId) {
    var input = document.getElementById(inputId);
    var table = document.getElementById(tableId);
    var rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    input.addEventListener('keyup', function() {
        var filter = input.value.toLowerCase();
        
        for (var i = 0; i < rows.length; i++) {
            var cells = rows[i].getElementsByTagName('td');
            var found = false;
            
            for (var j = 0; j < cells.length; j++) {
                if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
            
            rows[i].style.display = found ? '' : 'none';
        }
    });
}

// Sidebar navigation active state
function setActiveNavItem(currentPage) {
    var navLinks = document.querySelectorAll('.sidebar .nav-link');
    navLinks.forEach(function(link) {
        link.classList.remove('active');
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
}