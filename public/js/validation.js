/**
 * ARIES Form Validation Module
 * Provides comprehensive form validation with real-time feedback.
 */

'use strict';

const ARIESValidation = (function() {
    
    const patterns = {
        name: /^[a-zA-ZÀ-ÿñÑ\s\-']{2,50}$/,
        email: /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/,
        phone: /^(09|\+639)\d{9}$/,
        landline: /^(\(0\d{1,2}\)\s?)?\d{3,4}[-\s]?\d{4}$/,
        postalCode: /^\d{4}$/,
        date: /^\d{4}-\d{2}-\d{2}$/,
        year: /^(19|20)\d{2}$/,
        gpa: /^([1-4](\.\d{1,2})?|5(\.00?)?)$/,
        percentage: /^(100(\.0{1,2})?|[1-9]?\d(\.\d{1,2})?)$/,
        lrn: /^\d{12}$/,
        password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/,
        documentFile: /\.(pdf|doc|docx|jpg|jpeg|png)$/i,
        imageFile: /\.(jpg|jpeg|png|gif|webp)$/i,
        alphanumericSpaces: /^[a-zA-Z0-9\s]+$/,
        address: /^[a-zA-Z0-9À-ÿñÑ\s,.\-#'()\/]+$/
    };
    
    const messages = {
        required: 'This field is required',
        name: { required: 'Name is required', invalid: 'Please enter a valid name (2-50 characters, letters only)' },
        email: { required: 'Email address is required', invalid: 'Please enter a valid email address' },
        phone: { required: 'Contact number is required', invalid: 'Please enter a valid 11-digit mobile number (e.g. 09123456789)' },
        landline: { invalid: 'Please enter a valid landline number' },
        postalCode: { invalid: 'Please enter a valid 4-digit postal code' },
        date: { required: 'Date is required', invalid: 'Please enter a valid date', future: 'Date cannot be in the future', past: 'Date cannot be in the past', tooOld: 'Please enter a valid birth year', tooYoung: 'You must be at least 15 years old' },
        select: { required: 'Please select an option' },
        radio: { required: 'Please select one option' },
        checkbox: { required: 'This checkbox must be checked' },
        file: { required: 'Please upload a file', type: 'Invalid file type', size: 'File size exceeds the limit' },
        password: { required: 'Password is required', invalid: 'Password must be at least 8 characters with uppercase, lowercase, and number' },
        lrn: { invalid: 'LRN must be exactly 12 digits' },
        gpa: { invalid: 'Please enter a valid GPA (1.00 - 5.00)' },
        address: { required: 'Address is required', invalid: 'Please enter a valid address' },
        minLength: (min) => `Must be at least ${min} characters`,
        maxLength: (max) => `Must not exceed ${max} characters`,
        min: (min) => `Value must be at least ${min}`,
        max: (max) => `Value must not exceed ${max}`
    };

    function toUpperCaseString(str) {
        if (!str) return '';
        return str.toUpperCase();
    }

    function removeSpecialChars(value) { return value.replace(/[^a-zA-Z0-9\s]/g, ''); }
    function removeNonAlpha(value) { return value.replace(/[^a-zA-Z\s]/g, ''); }

    function setupFormatting(input, type = 'text') {
        if (!input) return;
        
        // INSTANT UPPERCASE VISUALLY
        input.style.textTransform = 'uppercase';
        
        input.addEventListener('input', function() {
            const cursorStart = this.selectionStart;
            const originalVal = this.value;
            let cleanVal = (type === 'name') ? removeNonAlpha(originalVal) : removeSpecialChars(originalVal);
            
            if (originalVal !== cleanVal) {
                this.value = cleanVal;
                // Safely adjust cursor if characters were stripped
                try { this.setSelectionRange(cursorStart - 1, cursorStart - 1); } catch(e) {}
            }
        });
        
        // COMMIT TRUE UPPERCASE TO DATA
        input.addEventListener('blur', function() { 
            this.value = this.value.toUpperCase().trim(); 
        });
    }
    
    function isEmpty(value) { return value === null || value === undefined || value.toString().trim() === ''; }
    function sanitizeInput(value) {
        if (typeof value !== 'string') return value;
        return value.replace(/<[^>]*>/g, '').replace(/[<>]/g, '').trim();
    }
    function formatPhoneNumber(value) { return value.replace(/\D/g, '').slice(0, 11); }
    
    // ===== DOM Helpers =====
    function getErrorElement(input) {
        if (input.type === 'radio') {
            const groupContainer = input.closest('.campus-selector-row') || input.closest('.form-group') || input.closest('.card-body') || input.parentElement;
            if (!groupContainer) return null;

            let err = groupContainer.querySelector('.group-error-message');
            if (!err) {
                err = document.createElement('div');
                err.className = 'group-error-message text-danger mt-2 text-xs fw-bold w-100 text-center';
                err.style.display = 'none';
                groupContainer.appendChild(err);
            }
            return err;
        }

        const id = input.id || input.name;
        let errorEl = id ? document.getElementById(id + '-error') : null;
        
        if (!errorEl && input.parentElement) {
            errorEl = input.parentElement.querySelector('.error-message');
        }
        
        if (!errorEl && input.parentElement) {
            errorEl = document.createElement('div');
            if (id) errorEl.id = id + '-error';
            errorEl.className = 'error-message text-danger mt-1 text-xs fw-bold';
            errorEl.style.fontSize = '0.85em';
            errorEl.style.display = 'none';
            
            const span = document.createElement('span');
            errorEl.appendChild(span);
            
            if (input.nextElementSibling && input.nextElementSibling.classList.contains('text-gray-500')) {
                input.parentElement.insertBefore(errorEl, input.nextElementSibling.nextSibling);
            } else {
                input.parentElement.appendChild(errorEl);
            }
        }
        
        return errorEl;
    }

    function setValid(input, errorEl) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        if (errorEl) {
            errorEl.classList.remove('show');
            errorEl.style.display = 'none';
            errorEl.setAttribute('aria-hidden', 'true');
        }
        input.setAttribute('aria-invalid', 'false');
    }
    
    function setInvalid(input, errorEl, message) {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        if (errorEl) {
            errorEl.classList.add('show');
            errorEl.style.display = 'block';
            errorEl.setAttribute('aria-hidden', 'false');
            const msgSpan = errorEl.querySelector('span') || errorEl;
            if (message) msgSpan.textContent = message;
        }
        input.setAttribute('aria-invalid', 'true');
    }
    
    function resetField(input, errorEl) {
        input.classList.remove('is-valid', 'is-invalid');
        if (errorEl) {
            errorEl.classList.remove('show');
            errorEl.style.display = 'none';
            errorEl.setAttribute('aria-hidden', 'true');
        }
        input.removeAttribute('aria-invalid');
    }

    function scrollToFirstError(formEl) {
        const firstError = formEl.querySelector('.is-invalid, .group-error-message.show');
        if (firstError) {
            const yOffset = -150; 
            const y = firstError.getBoundingClientRect().top + window.scrollY + yOffset;

            window.scrollTo({ top: y, behavior: 'smooth' });
            
            if (firstError.focus && !firstError.classList.contains('group-error-message')) {
                setTimeout(() => firstError.focus(), 500);
            }
        }
    }
    
    return {
        patterns,
        messages,
        isEmpty,
        sanitizeInput,
        formatPhoneNumber,
        toUpperCaseString,
        setupFormatting,
        setValid,
        setInvalid,
        resetField,
        getErrorElement,
        scrollToFirstError
    };
})();

window.ARIESValidation = ARIESValidation;