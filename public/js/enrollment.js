/**
 * ARIES Enrollment Form Module
 * Handles form interactions, validation, and auto-save functionality.
 */

document.addEventListener('DOMContentLoaded', function() {
    initPageTransitions();
    initMobileProgress();
    initAgeCalculator();
    initConditionalFields();
    initFormValidation();
    initAutoSave();
});

/**
 * Initialize smooth page transitions.
 */
function initPageTransitions() {
    const formWrapper = document.querySelector('.form-wrapper');
    const forms = document.querySelectorAll('form');
    const navigationLinks = document.querySelectorAll('.btn-secondary, .btn-ghost, a[href]');
    
    // Ensure form wrapper is visible after load animation
    if (formWrapper) {
        formWrapper.style.opacity = '1';
    }
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                }
            });
            
            if (isValid && formWrapper) {
                formWrapper.classList.add('transitioning-out');
            }
        });
    });
    
    navigationLinks.forEach(link => {
        if (link.tagName === 'A' && link.href && !link.href.includes('#') && !link.href.includes('mailto:')) {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('http://') || href.startsWith('https://')) {
                    return;
                }
                
                e.preventDefault();
                
                if (formWrapper) {
                    formWrapper.classList.add('transitioning-out');
                    
                    setTimeout(() => {
                        window.location.href = href;
                    }, 300);
                } else {
                    window.location.href = href;
                }
            });
        }
    });
}

/**
 * Toggle mobile progress drawer visibility.
 */
function toggleMobileProgress() {
    const drawer = document.getElementById('mobileProgressDrawer');
    const overlay = document.getElementById('mobileOverlay');
    
    if (drawer && overlay) {
        drawer.classList.toggle('show');
        overlay.classList.toggle('show');
        document.body.style.overflow = drawer.classList.contains('show') ? 'hidden' : '';
    }
}

function initMobileProgress() {
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const drawer = document.getElementById('mobileProgressDrawer');
            if (drawer && drawer.classList.contains('show')) {
                toggleMobileProgress();
            }
        }
    });
}

/**
 * Calculate age from birth date.
 */
function initAgeCalculator() {
    const birthDateField = document.getElementById('birthDate');
    const ageField = document.getElementById('ageField');
    
    if (birthDateField && ageField) {
        birthDateField.addEventListener('change', function() {
            const birthDate = new Date(this.value);
            const today = new Date();
            
            if (birthDate && !isNaN(birthDate)) {
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                
                ageField.value = age > 0 ? age : '';
            }
        });
    }
}

/**
 * Initialize conditional field display logic.
 */
function initConditionalFields() {
    const gradeLevelField = document.getElementById('gradeLevel');
    const trackField = document.getElementById('trackField');
    
    if (gradeLevelField && trackField) {
        gradeLevelField.addEventListener('change', function() {
            const isSHS = this.value === 'grade11' || this.value === 'grade12';
            trackField.style.display = isSHS ? 'block' : 'none';
            
            const trackSelect = trackField.querySelector('select');
            if (trackSelect) {
                trackSelect.required = isSHS;
                if (!isSHS) trackSelect.value = '';
            }
        });
    }
    
    const citizenshipField = document.getElementById('citizenship');
    const visaSection = document.getElementById('visaSection');
    
    if (citizenshipField && visaSection) {
        citizenshipField.addEventListener('change', function() {
            const showVisa = this.value === 'foreign' || this.value === 'dual_citizen';
            visaSection.style.display = showVisa ? 'block' : 'none';
        });
    }
    
    const specialNeedsField = document.getElementById('specialNeeds');
    const specialNeedsDetail = document.getElementById('specialNeedsDetail');
    
    if (specialNeedsField && specialNeedsDetail) {
        specialNeedsField.addEventListener('change', function() {
            const showDetail = this.value && this.value !== '';
            specialNeedsDetail.style.display = showDetail ? 'block' : 'none';
        });
    }
}

/**
 * Initialize form validation with visual feedback.
 */
function initFormValidation() {
    const form = document.querySelector('.enrollment-form');
    
    if (form) {
        const inputs = form.querySelectorAll('.form-input, .form-select');
        
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                const errorMsg = this.parentNode.querySelector('.error-message');
                if (errorMsg) errorMsg.remove();
            });
        });
        
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!validateField(field)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                const firstError = form.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });
    }
}

function validateField(field) {
    const value = field.value.trim();
    const isRequired = field.hasAttribute('required');
    const type = field.type || 'text';
    
    field.classList.remove('is-invalid', 'is-valid');
    const existingError = field.parentNode.querySelector('.error-message');
    if (existingError) existingError.remove();
    
    if (isRequired && !value) {
        showFieldError(field, 'This field is required');
        return false;
    }
    
    if (type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            showFieldError(field, 'Please enter a valid email address');
            return false;
        }
    }
    
    if (field.name && field.name.includes('phone') && value) {
        const phoneRegex = /^(09|\+639)\d{9}$/;
        if (!phoneRegex.test(value.replace(/[- ]/g, ''))) {
            showFieldError(field, 'Please enter a valid Philippine mobile number');
            return false;
        }
    }
    
    if (field.name === 'lrn' && value) {
        if (!/^\d{12}$/.test(value)) {
            showFieldError(field, 'LRN must be exactly 12 digits');
            return false;
        }
    }
    
    if (field.name && field.name.includes('zip') && value) {
        if (!/^\d{4}$/.test(value)) {
            showFieldError(field, 'Zip code must be 4 digits');
            return false;
        }
    }
    
    if (value) {
        field.classList.add('is-valid');
    }
    
    return true;
}

function showFieldError(field, message) {
    field.classList.add('is-invalid');
    
    const errorDiv = document.createElement('span');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * Initialize auto-save functionality using localStorage.
 */
function initAutoSave() {
    const form = document.querySelector('.enrollment-form');
    
    if (form) {
        const formId = form.id || 'enrollment-form';
        const storageKey = `aries_${formId}_draft`;
        
        loadFormData(form, storageKey);
        
        let saveTimeout;
        form.addEventListener('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                saveFormData(form, storageKey);
            }, 1000);
        });
        
        form.addEventListener('submit', function() {
            localStorage.removeItem(storageKey);
        });
    }
}

function saveFormData(form, key) {
    const formData = new FormData(form);
    const data = {};
    
    formData.forEach((value, name) => {
        if (!['password', 'token', 'csrf', 'adcon'].some(s => name.toLowerCase().includes(s))) {
            data[name] = value;
        }
    });
    
    try {
        localStorage.setItem(key, JSON.stringify(data));
    } catch (e) {
        console.warn('Could not save form draft:', e);
    }
}

function loadFormData(form, key) {
    try {
        const savedData = localStorage.getItem(key);
        
        if (savedData) {
            const data = JSON.parse(savedData);
            
            Object.keys(data).forEach(name => {
                const field = form.querySelector(`[name="${name}"]`);
                if (field && data[name]) {
                    field.value = data[name];
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
    } catch (e) {
        console.warn('Could not load form draft:', e);
    }
}

/**
 * Format phone number input.
 */
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.startsWith('63')) {
        value = '0' + value.substring(2);
    }
    
    if (value.length > 4) {
        value = value.substring(0, 4) + '-' + value.substring(4);
    }
    if (value.length > 8) {
        value = value.substring(0, 8) + '-' + value.substring(8, 12);
    }
    
    input.value = value;
}

function formatLRN(input) {
    input.value = input.value.replace(/\D/g, '').substring(0, 12);
}

/**
 * Handle scroll header effect.
 */
window.addEventListener('scroll', function() {
    const header = document.querySelector('.mobile-header');
    if (header) {
        if (window.scrollY > 10) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    }
});

window.toggleMobileProgress = toggleMobileProgress;
