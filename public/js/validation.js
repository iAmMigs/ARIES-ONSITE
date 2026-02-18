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
        name: {
            required: 'Name is required',
            invalid: 'Please enter a valid name (2-50 characters, letters only)'
        },
        email: {
            required: 'Email address is required',
            invalid: 'Please enter a valid email address'
        },
        phone: {
            required: 'Contact number is required',
            invalid: 'Please enter a valid Philippine mobile number (09XX XXX XXXX)'
        },
        landline: {
            invalid: 'Please enter a valid landline number'
        },
        postalCode: {
            invalid: 'Please enter a valid 4-digit postal code'
        },
        date: {
            required: 'Date is required',
            invalid: 'Please enter a valid date',
            future: 'Date cannot be in the future',
            past: 'Date cannot be in the past',
            tooOld: 'Please enter a valid birth year',
            tooYoung: 'You must be at least 15 years old'
        },
        select: {
            required: 'Please select an option'
        },
        radio: {
            required: 'Please select one option'
        },
        checkbox: {
            required: 'This checkbox must be checked'
        },
        file: {
            required: 'Please upload a file',
            type: 'Invalid file type',
            size: 'File size exceeds the limit'
        },
        password: {
            required: 'Password is required',
            invalid: 'Password must be at least 8 characters with uppercase, lowercase, and number'
        },
        lrn: {
            invalid: 'LRN must be exactly 12 digits'
        },
        gpa: {
            invalid: 'Please enter a valid GPA (1.00 - 5.00)'
        },
        address: {
            required: 'Address is required',
            invalid: 'Please enter a valid address'
        },
        minLength: (min) => `Must be at least ${min} characters`,
        maxLength: (max) => `Must not exceed ${max} characters`,
        min: (min) => `Value must be at least ${min}`,
        max: (max) => `Value must not exceed ${max}`
    };

    // ===== Formatting Helpers =====
    function toTitleCase(str) {
        if (!str) return '';
        return str.toLowerCase().replace(/\b\w/g, s => s.toUpperCase());
    }

    function removeSpecialChars(value) {
        // Allows letters, numbers, and spaces. Removes everything else.
        return value.replace(/[^a-zA-Z0-9\s]/g, '');
    }

    function removeNonAlpha(value) {
        // Allows letters and spaces only (for Names)
        return value.replace(/[^a-zA-Z\s]/g, '');
    }

    // ===== Public Setup Function =====
    /**
     * Attaches formatting rules to an input.
     * @param {HTMLElement} input - The input element
     * @param {string} type - 'name' (Alpha only) or 'text' (AlphaNumeric)
     */
    function setupFormatting(input, type = 'text') {
        if (!input) return;

        // 1. Real-time: Remove special characters immediately
        input.addEventListener('input', function() {
            const cursorStart = this.selectionStart;
            const originalVal = this.value;
            
            let cleanVal;
            if (type === 'name') {
                cleanVal = removeNonAlpha(originalVal);
            } else {
                cleanVal = removeSpecialChars(originalVal);
            }

            if (originalVal !== cleanVal) {
                this.value = cleanVal;
                // Try to preserve cursor position
                this.setSelectionRange(cursorStart - 1, cursorStart - 1);
            }
        });

        // 2. On Blur: Format to Title Case (e.g. "miGuEl" -> "Miguel")
        input.addEventListener('blur', function() {
            this.value = toTitleCase(this.value.trim());
        });
    }
    
    // ===== Utility Functions =====
    function isEmpty(value) {
        return value === null || value === undefined || value.toString().trim() === '';
    }
    
    function sanitizeInput(value) {
        if (typeof value !== 'string') return value;
        // Remove potentially dangerous characters but keep valid ones
        return value
            .replace(/<[^>]*>/g, '') // Remove HTML tags
            .replace(/[<>]/g, '') // Remove angle brackets
            .trim();
    }
    
    function formatPhoneNumber(value) {
        // Remove all non-digits
        let digits = value.replace(/\D/g, '');
        
        // Limit to 11 digits for PH mobile
        if (digits.length > 11) {
            digits = digits.slice(0, 11);
        }
        
        return digits;
    }
    
    // ===== DOM Helpers =====
    function setValid(input, errorEl) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        if (errorEl) {
            errorEl.classList.remove('show');
            errorEl.setAttribute('aria-hidden', 'true');
        }
        input.setAttribute('aria-invalid', 'false');
    }
    
    function setInvalid(input, errorEl, message) {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        if (errorEl) {
            errorEl.classList.add('show');
            errorEl.setAttribute('aria-hidden', 'false');
            const msgSpan = errorEl.querySelector('span');
            if (msgSpan && message) {
                msgSpan.textContent = message;
            }
        }
        input.setAttribute('aria-invalid', 'true');
    }
    
    function resetField(input, errorEl) {
        input.classList.remove('is-valid', 'is-invalid');
        if (errorEl) {
            errorEl.classList.remove('show');
            errorEl.setAttribute('aria-hidden', 'true');
        }
        input.removeAttribute('aria-invalid');
    }
    
    function getErrorElement(input) {
        // Look for error element by various conventions
        const id = input.id;
        let errorEl = document.getElementById(id + 'Error');
        
        if (!errorEl) {
            errorEl = document.getElementById(id + '-error');
        }
        
        if (!errorEl) {
            // Look for sibling with error-message class
            errorEl = input.parentElement.querySelector('.error-message');
        }
        
        if (!errorEl) {
            // Look in parent's parent
            errorEl = input.parentElement.parentElement.querySelector('.error-message');
        }
        
        return errorEl;
    }
    
    // ===== Validation Functions =====
    function validateRequired(value) {
        return !isEmpty(value);
    }
    
    function validatePattern(value, pattern) {
        if (isEmpty(value)) return true; // Empty is handled by required
        return pattern.test(value);
    }
    
    function validateMinLength(value, minLength) {
        if (isEmpty(value)) return true;
        return value.length >= minLength;
    }
    
    function validateMaxLength(value, maxLength) {
        if (isEmpty(value)) return true;
        return value.length <= maxLength;
    }
    
    function validateMin(value, min) {
        if (isEmpty(value)) return true;
        return parseFloat(value) >= min;
    }
    
    function validateMax(value, max) {
        if (isEmpty(value)) return true;
        return parseFloat(value) <= max;
    }
    
    function validateAge(birthDate, minAge = 15, maxAge = 100) {
        if (isEmpty(birthDate)) return { valid: true };
        
        const today = new Date();
        const birth = new Date(birthDate);
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        
        if (age < minAge) {
            return { valid: false, message: messages.date.tooYoung };
        }
        
        if (age > maxAge) {
            return { valid: false, message: messages.date.tooOld };
        }
        
        if (birth > today) {
            return { valid: false, message: messages.date.future };
        }
        
        return { valid: true };
    }
    
    function validateFileSize(file, maxSizeMB = 5) {
        if (!file) return true;
        const maxBytes = maxSizeMB * 1024 * 1024;
        return file.size <= maxBytes;
    }
    
    function validateFileType(file, allowedTypes) {
        if (!file) return true;
        const extension = '.' + file.name.split('.').pop().toLowerCase();
        return allowedTypes.some(type => 
            type.toLowerCase() === extension || 
            file.type.includes(type.replace('.', ''))
        );
    }
    
    // ===== Field Validators =====
    function validateField(input, rules = {}) {
        const value = sanitizeInput(input.value);
        const errorEl = getErrorElement(input);
        let isValid = true;
        let errorMessage = '';
        
        // Required check
        if (rules.required && !validateRequired(value)) {
            isValid = false;
            errorMessage = rules.requiredMessage || messages.required;
        }
        
        // Pattern check
        if (isValid && rules.pattern && !validatePattern(value, rules.pattern)) {
            isValid = false;
            errorMessage = rules.patternMessage || 'Invalid format';
        }
        
        // Min length check
        if (isValid && rules.minLength && !validateMinLength(value, rules.minLength)) {
            isValid = false;
            errorMessage = messages.minLength(rules.minLength);
        }
        
        // Max length check
        if (isValid && rules.maxLength && !validateMaxLength(value, rules.maxLength)) {
            isValid = false;
            errorMessage = messages.maxLength(rules.maxLength);
        }
        
        // Min value check
        if (isValid && rules.min !== undefined && !validateMin(value, rules.min)) {
            isValid = false;
            errorMessage = messages.min(rules.min);
        }
        
        // Max value check
        if (isValid && rules.max !== undefined && !validateMax(value, rules.max)) {
            isValid = false;
            errorMessage = messages.max(rules.max);
        }
        
        // Custom validator
        if (isValid && rules.custom) {
            const customResult = rules.custom(value, input);
            if (customResult !== true) {
                isValid = false;
                errorMessage = typeof customResult === 'string' ? customResult : 'Invalid value';
            }
        }
        
        // Update UI
        if (isValid) {
            if (!isEmpty(value)) {
                setValid(input, errorEl);
            } else {
                resetField(input, errorEl);
            }
        } else {
            setInvalid(input, errorEl, errorMessage);
        }
        
        return isValid;
    }
    
    function validateName(input, isRequired = true) {
        return validateField(input, {
            required: isRequired,
            requiredMessage: messages.name.required,
            pattern: patterns.name,
            patternMessage: messages.name.invalid,
            minLength: 2,
            maxLength: 50
        });
    }
    
    function validateEmail(input, isRequired = true) {
        return validateField(input, {
            required: isRequired,
            requiredMessage: messages.email.required,
            pattern: patterns.email,
            patternMessage: messages.email.invalid
        });
    }
    
    function validatePhone(input, isRequired = true) {
        // Format first
        if (input.value) {
            input.value = formatPhoneNumber(input.value);
        }
        
        return validateField(input, {
            required: isRequired,
            requiredMessage: messages.phone.required,
            pattern: patterns.phone,
            patternMessage: messages.phone.invalid
        });
    }
    
    function validateSelect(input, isRequired = true) {
        const errorEl = getErrorElement(input);
        const isValid = !isRequired || (input.value && input.value !== '');
        
        if (isValid) {
            if (input.value) {
                setValid(input, errorEl);
            } else {
                resetField(input, errorEl);
            }
        } else {
            setInvalid(input, errorEl, messages.select.required);
        }
        
        return isValid;
    }
    
    function validateRadioGroup(name, isRequired = true) {
        const radios = document.querySelectorAll(`input[name="${name}"]`);
        const errorEl = document.getElementById(name.replace('_', '') + 'Error') ||
                       document.getElementById(name + 'Error') ||
                       document.getElementById(name + '-error');
        
        const isChecked = Array.from(radios).some(radio => radio.checked);
        const isValid = !isRequired || isChecked;
        
        if (errorEl) {
            if (isValid) {
                errorEl.classList.remove('show');
            } else {
                errorEl.classList.add('show');
                const msgSpan = errorEl.querySelector('span');
                if (msgSpan) {
                    msgSpan.textContent = messages.radio.required;
                }
            }
        }
        
        return isValid;
    }
    
    function validateBirthDate(input, isRequired = true, minAge = 15) {
        const errorEl = getErrorElement(input);
        let isValid = true;
        let errorMessage = '';
        
        if (isRequired && isEmpty(input.value)) {
            isValid = false;
            errorMessage = messages.date.required;
        } else if (!isEmpty(input.value)) {
            const ageResult = validateAge(input.value, minAge);
            if (!ageResult.valid) {
                isValid = false;
                errorMessage = ageResult.message;
            }
        }
        
        if (isValid) {
            if (input.value) {
                setValid(input, errorEl);
            } else {
                resetField(input, errorEl);
            }
        } else {
            setInvalid(input, errorEl, errorMessage);
        }
        
        return isValid;
    }
    
    function validateAddress(input, isRequired = true) {
        return validateField(input, {
            required: isRequired,
            requiredMessage: messages.address.required,
            pattern: patterns.address,
            patternMessage: messages.address.invalid,
            minLength: 5,
            maxLength: 500
        });
    }
    
    function validatePostalCode(input, isRequired = false) {
        return validateField(input, {
            required: isRequired,
            pattern: patterns.postalCode,
            patternMessage: messages.postalCode.invalid
        });
    }
    
    // ===== Form-Level Validation =====
    function validateForm(formEl, validationRules) {
        let isFormValid = true;
        const errors = [];
        
        Object.keys(validationRules).forEach(fieldId => {
            const input = formEl.querySelector(`#${fieldId}`) || 
                         formEl.querySelector(`[name="${fieldId}"]`);
            
            if (!input) return;
            
            const rules = validationRules[fieldId];
            let isFieldValid = true;
            
            // Handle different input types
            if (input.type === 'radio') {
                isFieldValid = validateRadioGroup(input.name, rules.required);
            } else if (rules.type === 'name') {
                isFieldValid = validateName(input, rules.required);
            } else if (rules.type === 'email') {
                isFieldValid = validateEmail(input, rules.required);
            } else if (rules.type === 'phone') {
                isFieldValid = validatePhone(input, rules.required);
            } else if (rules.type === 'select' || input.tagName === 'SELECT') {
                isFieldValid = validateSelect(input, rules.required);
            } else if (rules.type === 'birthdate') {
                isFieldValid = validateBirthDate(input, rules.required, rules.minAge);
            } else if (rules.type === 'address') {
                isFieldValid = validateAddress(input, rules.required);
            } else if (rules.type === 'postalCode') {
                isFieldValid = validatePostalCode(input, rules.required);
            } else {
                isFieldValid = validateField(input, rules);
            }
            
            if (!isFieldValid) {
                isFormValid = false;
                errors.push({ field: fieldId, input: input });
            }
        });
        
        return { valid: isFormValid, errors: errors };
    }
    
    // ===== Real-time Validation Setup =====
    function setupRealTimeValidation(input, validationType, options = {}) {
        const validateFn = () => {
            switch(validationType) {
                case 'name':
                    validateName(input, options.required);
                    break;
                case 'email':
                    validateEmail(input, options.required);
                    break;
                case 'phone':
                    validatePhone(input, options.required);
                    break;
                case 'select':
                    validateSelect(input, options.required);
                    break;
                case 'birthdate':
                    validateBirthDate(input, options.required, options.minAge);
                    break;
                case 'address':
                    validateAddress(input, options.required);
                    break;
                default:
                    validateField(input, options);
            }
        };
        
        // Validate on blur
        input.addEventListener('blur', validateFn);
        
        // Re-validate on input if already invalid
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateFn();
            }
            
            // Format phone numbers as user types
            if (validationType === 'phone') {
                this.value = formatPhoneNumber(this.value);
            }
        });
        
        // For select elements
        if (input.tagName === 'SELECT') {
            input.addEventListener('change', validateFn);
        }
    }
    
    // ===== Scroll to First Error =====
    function scrollToFirstError(formEl) {
        const firstError = formEl.querySelector('.is-invalid, .error-message.show');
        if (firstError) {
            firstError.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
            
            // Focus the input if possible
            const input = firstError.classList.contains('is-invalid') ? 
                         firstError : 
                         firstError.previousElementSibling;
            if (input && input.focus) {
                setTimeout(() => input.focus(), 500);
            }
        }
    }

    // ===== Utility Functions =====
    function isEmpty(value) {
        return value === null || value === undefined || value.toString().trim() === '';
    }

    function sanitizeInput(value) {
        if (typeof value !== 'string') return value;
        return value.replace(/<[^>]*>/g, '').replace(/[<>]/g, '').trim();
    }
    
    // ===== Public API =====
    return {
        patterns,
        messages,
        
        // Utilities
        isEmpty,
        sanitizeInput,
        formatPhoneNumber,

        // Formatting
        toTitleCase,
        setupFormatting,
        
        // UI helpers
        setValid,
        setInvalid,
        resetField,
        getErrorElement,
        
        // Individual validators
        validateField,
        validateName,
        validateEmail,
        validatePhone,
        validateSelect,
        validateRadioGroup,
        validateBirthDate,
        validateAddress,
        validatePostalCode,
        validateAge,
        validateFileSize,
        validateFileType,
        
        // Form validation
        validateForm,
        scrollToFirstError,
        
        // Setup
        setupRealTimeValidation
    };
})();

// Make available globally
window.ARIESValidation = ARIESValidation;
