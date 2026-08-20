// ============================================
// PREVENT BLINKING - Disable auto-hide alerts
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // DO NOT auto-hide alerts - prevents blinking
    // Remove any setTimeout that hides alerts
    
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('show');
        });
    }
    
    // Form validation (keep as is)
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let valid = true;
            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    valid = false;
                    input.classList.add('error');
                } else {
                    input.classList.remove('error');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
});
/**
 * Machakos University Sports Management System
 * Main JavaScript File
 */

// ============================================
// DOM READY
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    
    // ----- Mobile Menu Toggle -----
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuToggle && sidebar) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-open');
            
            // Toggle icon
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            }
        });
    }
    
    // Close sidebar on outside click (mobile)
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (sidebar && !sidebar.contains(e.target) && !mobileMenuToggle?.contains(e.target)) {
                sidebar.classList.remove('sidebar-open');
                const icon = mobileMenuToggle?.querySelector('i');
                if (icon) {
                    icon.classList.add('fa-bars');
                    icon.classList.remove('fa-times');
                }
            }
        }
    });
    
    // ----- Form Validation -----
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        // Remove any existing validation listeners
        form.removeEventListener('submit', handleFormSubmit);
        form.addEventListener('submit', handleFormSubmit);
        
        // Real-time validation on blur
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateField(this);
                }
            });
        });
    });
    
    // ----- Date Picker Restrictions -----
    const dobInput = document.getElementById('date_of_birth');
    if (dobInput) {
        const today = new Date();
        const minDate = new Date(today.getFullYear() - 80, today.getMonth(), today.getDate());
        const maxDate = new Date(today.getFullYear() - 16, today.getMonth(), today.getDate());
        
        dobInput.max = maxDate.toISOString().split('T')[0];
        dobInput.min = minDate.toISOString().split('T')[0];
    }
    
    // ----- Table Row Click (Enhanced) -----
    const tableRows = document.querySelectorAll('table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('click', function(event) {
            // Ignore clicks on buttons, links, or inputs
            if (event.target.closest('button') || 
                event.target.closest('a') || 
                event.target.closest('input') ||
                event.target.closest('.btn')) {
                return;
            }
            
            // Find first non-button link in row
            const link = this.querySelector('a:not(.btn)');
            if (link && link.href) {
                window.location.href = link.href;
            }
        });
    });
    
    // ----- Delete Confirmation (Global) -----
    document.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.btn-danger, .delete-btn, [data-delete]');
        if (deleteBtn) {
            const form = deleteBtn.closest('form');
            const message = deleteBtn.dataset.confirm || 'Are you sure you want to delete this item?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            if (form) {
                const originalText = deleteBtn.innerHTML;
                deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                deleteBtn.disabled = true;
                
                // Form will submit normally
            }
        }
    });
    
    // ----- Print Button Enhancement -----
    const printBtn = document.querySelector('[onclick="window.print()"]');
    if (printBtn) {
        printBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.print();
        });
    }
    
    // ----- Auto-hide Alerts -----
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.display = 'none';
                }
            }, 500);
        }, 5000);
    });
    
    // ----- Number Input Restrictions -----
    const numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(input => {
        input.addEventListener('keydown', function(e) {
            // Allow only numbers, backspace, delete, tab, arrows
            const allowed = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];
            if (!allowed.includes(e.key) && !/^[0-9]$/.test(e.key)) {
                e.preventDefault();
            }
        });
    });
});

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Validate a single form field
 */
function validateField(input) {
    const value = input.value.trim();
    const type = input.type;
    const name = input.name || input.id || 'field';
    
    // Remove existing error
    input.classList.remove('error');
    const existingError = input.parentElement.querySelector('.error-message');
    if (existingError) existingError.remove();
    
    // Required check
    if (input.hasAttribute('required') && !value) {
        input.classList.add('error');
        showError(input, 'This field is required');
        return false;
    }
    
    // Email validation
    if (type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            input.classList.add('error');
            showError(input, 'Please enter a valid email address');
            return false;
        }
    }
    
    // Phone validation
    if ((name.toLowerCase().includes('phone') || type === 'tel') && value) {
        const cleaned = value.replace(/[\s\-\(\)]/g, '');
        if (!/^[0-9]{10,15}$/.test(cleaned)) {
            input.classList.add('error');
            showError(input, 'Enter 10-15 digits only');
            return false;
        }
    }
    
    // Student ID validation
    if (name === 'student_id' && value) {
        if (!/^[A-Za-z0-9-]+$/.test(value)) {
            input.classList.add('error');
            showError(input, 'Format: e.g., Vd19-5803-2023');
            return false;
        }
    }
    
    return true;
}

/**
 * Show inline error message
 */
function showError(input, message) {
    const parent = input.parentElement;
    const error = document.createElement('span');
    error.className = 'error-message';
    error.textContent = message;
    error.style.cssText = `
        color: #e74c3c;
        font-size: 12px;
        display: block;
        margin-top: 3px;
    `;
    parent.appendChild(error);
}

/**
 * Handle form submission
 */
function handleFormSubmit(e) {
    const form = this;
    let valid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            valid = false;
        }
    });
    
    if (!valid) {
        e.preventDefault();
        if (window.showToast) {
            showToast('Please fix the errors in the form.', 'error');
        }
        // Focus first error
        const firstError = form.querySelector('.error');
        if (firstError) {
            firstError.focus();
        }
        return false;
    }
    
    // Show loading state on submit button
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
        
        // Re-enable after 30 seconds (fallback)
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 30000);
    }
}

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // Add icon
    const iconMap = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    toast.innerHTML = `
        <i class="fas ${iconMap[type] || 'fa-info-circle'}"></i>
        <span>${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    document.body.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // Auto-hide after 5 seconds
    const timeout = setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 300);
    }, 5000);
    
    // Close on click
    toast.addEventListener('click', function(e) {
        if (e.target === this) {
            clearTimeout(timeout);
            this.classList.remove('show');
            setTimeout(() => {
                if (this.parentNode) {
                    this.remove();
                }
            }, 300);
        }
    });
}

/**
 * Format date for display
 */
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-KE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Export table to CSV
 */
function exportToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId) || document.querySelector('table');
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => {
            let text = col.innerText.trim();
            // Escape commas and quotes
            if (text.includes(',') || text.includes('"')) {
                text = `"${text.replace(/"/g, '""')}"`;
            }
            rowData.push(text);
        });
        csv.push(rowData.join(','));
    });
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

// Make functions globally available
window.showToast = showToast;
window.formatDate = formatDate;
window.exportToCSV = exportToCSV;
window.validateField = validateField;