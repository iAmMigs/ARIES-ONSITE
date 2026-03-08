/**
 * ARIES Enrollment Form Module
 * Handles form interactions, validation, address lookups, and dynamic UI elements.
 */

// --- Global Data Dictionaries ---
const strandData = {
    'feu_alabang': ['STEM', 'ABM', 'HUMSS', 'GAS', 'ICT'],
    'feu_diliman': ['STEM', 'ABM', 'HUMSS', 'GAS', 'Sports Track']
};

const educationGrades = {
    'Primary': [
        {val: 'kinder', label: 'Kindergarten'}, {val: 'grade_1', label: 'Grade 1'}, {val: 'grade_2', label: 'Grade 2'},
        {val: 'grade_3', label: 'Grade 3'}, {val: 'grade_4', label: 'Grade 4'}, {val: 'grade_5', label: 'Grade 5'}, {val: 'grade_6', label: 'Grade 6'}
    ],
    'Secondary': [
        {val: 'grade_7', label: 'Grade 7'}, {val: 'grade_8', label: 'Grade 8'}, {val: 'grade_9', label: 'Grade 9'},
        {val: 'grade_10', label: 'Grade 10'}, {val: 'grade_11', label: 'Grade 11'}, {val: 'grade_12', label: 'Grade 12'}
    ]
};

window.currentCampus = ''; // Global State
window.isFormDirty = false; // Tracks if user modified anything

document.addEventListener('DOMContentLoaded', function() {
    initCampusSelection();
    initStickyHeader();
    initPageTransitions();
    initMobileProgress();
    initAgeCalculator();
    initConditionalFields();
    initDynamicFormatting();
    initFormValidation();
    initAddressLookups();
    initAnimations();
    initSubmitLock();
    initLrnValidation();
    initUnsavedChangesWarning();

    // Fix for Browser Cache (When user clicks Back button after submit)
    window.addEventListener('pageshow', function(event) {
        if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
            const form = document.getElementById('enrollmentForm');
            if (form) {
                form.reset();
                window.isFormDirty = false;
                
                // Clear all validation UI classes dynamically
                form.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                    el.classList.remove('is-valid', 'is-invalid');
                });
                form.querySelectorAll('.error-message.show, .group-error-message.show').forEach(el => {
                    el.style.display = 'none';
                    el.classList.remove('show');
                });
                
                // Re-trigger initial select setups without triggering validation UI
                if (window.currentCampus) {
                    window.selectCampus(window.currentCampus);
                }
            }
        }
    });
});

// --- CORE LOGIC EXPOSED GLOBALLY ---
window.selectCampus = function(campus) {
    window.currentCampus = campus;
    
    document.querySelectorAll('.campus-option').forEach(opt => opt.classList.remove('active'));
    const activeInput = document.querySelector(`.campus-option input[value="${campus}"]`);
    if(activeInput) {
        const activeOpt = activeInput.parentElement;
        activeOpt.classList.add('active');
        activeInput.checked = true;
    }

    const docItems = document.querySelectorAll('.document-item');
    let visibleCount = 0;
    let mappedCampusName = (campus === 'feu_alabang') ? 'FALAB' : 'FDIL';
    
    docItems.forEach(item => {
        const docCampus = item.getAttribute('data-campus');
        const input = item.querySelector('.document-input');
        
        if (!docCampus || docCampus === campus || docCampus === mappedCampusName) {
            item.style.display = 'block';
            if (input && input.getAttribute('data-is-required') === 'true') {
                input.required = true;
            }
            visibleCount++;
        } else {
            item.style.display = 'none';
            if (input) input.required = false; 
        }
    });
    
    const noDocsMsg = document.getElementById('no-docs-message');
    if (noDocsMsg) {
        noDocsMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
    }

    const eduSelect = document.getElementById('educationType');
    if(!eduSelect) return;
    
    const primaryOption = eduSelect.querySelector('option[value="Primary"]');
    eduSelect.value = ""; 
    
    if (campus === 'feu_alabang') {
        if(primaryOption) {
            primaryOption.disabled = true;
            primaryOption.style.display = 'none'; 
            primaryOption.hidden = true;
        }
    } else {
        if(primaryOption) {
            primaryOption.disabled = false;
            primaryOption.style.display = 'block';
            primaryOption.hidden = false;
        }
    }
    
    if (window.ARIESValidation) window.ARIESValidation.resetField(eduSelect, window.ARIESValidation.getErrorElement(eduSelect));
    
    window.updateGradeLevels();
};

window.updateGradeLevels = function() {
    const eduSelect = document.getElementById('educationType');
    const select = document.getElementById('gradeLevel');
    if(!eduSelect || !select) return;

    const type = eduSelect.value;
    select.innerHTML = '<option value="">Select Level</option>';
    
    if (type && educationGrades[type]) {
        educationGrades[type].forEach(grade => {
            if (window.currentCampus === 'feu_alabang' && type === 'Secondary' && ['grade_7', 'grade_8', 'grade_9', 'grade_10'].includes(grade.val)) return;
            const opt = document.createElement('option');
            opt.value = grade.val;
            opt.textContent = grade.label;
            select.appendChild(opt);
        });
    }
    
    if (window.ARIESValidation) window.ARIESValidation.resetField(select, window.ARIESValidation.getErrorElement(select));
    
    window.updateStrands();
};

window.updateStrands = function() {
    const gradeSelect = document.getElementById('gradeLevel');
    const strandGroup = document.getElementById('strandGroup');
    const select = document.getElementById('strand');
    if(!gradeSelect || !strandGroup || !select) return;

    const grade = gradeSelect.value;
    select.innerHTML = '<option value="">Select Option</option>';
    
    if(grade === 'grade_11' || grade === 'grade_12') {
        strandGroup.style.display = 'block';
        select.setAttribute('required', 'required');
        
        const strands = strandData[window.currentCampus] || [];
        strands.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s;
            opt.textContent = s;
            select.appendChild(opt);
        });
    } else {
        strandGroup.style.display = 'none';
        select.removeAttribute('required');
    }
    
    if (window.ARIESValidation) window.ARIESValidation.resetField(select, window.ARIESValidation.getErrorElement(select));
};

window.togglePermanentAddress = function() {
    const isChecked = document.getElementById('sameAsCurrent').checked;
    const block = document.getElementById('permanentAddressBlock');
    if(!block) return;

    block.style.display = isChecked ? 'none' : 'block';
    
    const inputs = block.querySelectorAll('select, input');
    inputs.forEach(i => {
        if (isChecked) {
            i.removeAttribute('required');
            i.value = ""; 
            if (window.ARIESValidation) window.ARIESValidation.resetField(i, window.ARIESValidation.getErrorElement(i));
        } else {
            i.setAttribute('required', 'required');
        }
    });
};

window.toggleParentStatus = function(currentId, otherId) {
    const current = document.getElementById(currentId);
    const other = document.getElementById(otherId);
    if(!current || !other) return;

    if (current.checked) {
        other.disabled = true;
        other.checked = false;
        if (window.ARIESValidation) window.ARIESValidation.resetField(other, window.ARIESValidation.getErrorElement(other));
    } else {
        other.disabled = false;
    }
};

window.addSibling = function() {
    const container = document.getElementById('siblings_container');
    if(!container) return;

    const row = `
        <div class="sibling-row row g-3 mb-3 align-items-center">
            <button type="button" class="btn-remove-sibling" onclick="this.closest('.sibling-row').remove(); window.isFormDirty = true;" title="Remove Sibling">
                <i class="ki-filled ki-trash"></i>
            </button>
            <div class="col-md-5">
                <label class="form-label text-xs">Name</label>
                <input type="text" name="sibling_name[]" class="form-control form-control-sm" placeholder="Full Name" oninput="window.isFormDirty = true;">
            </div>
            <div class="col-md-4">
                <label class="form-label text-xs">School</label>
                <input type="text" name="sibling_school[]" class="form-control form-control-sm" placeholder="School" oninput="window.isFormDirty = true;">
            </div>
             <div class="col-md-3">
                <label class="form-label text-xs">Student No.</label>
                <input type="text" name="sibling_student_no[]" class="form-control form-control-sm" placeholder="20xxxxxx" oninput="window.isFormDirty = true;">
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', row);
    window.isFormDirty = true;
};

window.checkFormValidity = function() {
    const form = document.getElementById('enrollmentForm');
    const warning = document.getElementById('submitWarning');
    if(!form) return;

    const hasCustomErrors = form.querySelectorAll('.is-invalid').length > 0;
    if (form.checkValidity() && !hasCustomErrors) {
        if(warning) warning.style.display = 'none';
    }
};

// --- SUBMIT MODAL FUNCTIONS ---
window.showConfirmationModal = function() {
    const modal = document.getElementById('submitConfirmModal');
    const dialog = document.getElementById('modalDialog');
    const summaryContainer = document.getElementById('summaryContent');
    
    if (!modal || !summaryContainer) {
        document.getElementById('enrollmentForm').submit();
        return;
    }

    const campusInput = document.querySelector('input[name="campus_selected"]:checked');
    const campus = campusInput ? (campusInput.value === 'feu_alabang' ? 'FEU Alabang' : 'FEU Diliman') : 'Not Selected';
    const admissionType = document.querySelector('[name="admission_type"]')?.value || 'N/A';
    const eduType = document.querySelector('[name="education_type"]')?.value || 'N/A';
    
    const gradeLevelEl = document.querySelector('[name="grade_level"]');
    let gradeLevel = 'N/A';
    if (gradeLevelEl && gradeLevelEl.selectedIndex >= 0) {
        gradeLevel = gradeLevelEl.options[gradeLevelEl.selectedIndex].text;
    }
    
    const lastName = document.querySelector('[name="last_name"]')?.value || '';
    const firstName = document.querySelector('[name="first_name"]')?.value || '';
    const fullName = `${lastName}, ${firstName}`.trim();
    const email = document.querySelector('[name="email"]')?.value || 'N/A';
    const mobile = document.querySelector('[name="contact_number"]')?.value || 'N/A';

    summaryContainer.innerHTML = `
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Campus:</span> <span class="summary-val">${campus}</span></div>
            <div class="summary-item"><span class="summary-label">Admission Type:</span> <span class="summary-val">${admissionType}</span></div>
            <div class="summary-item"><span class="summary-label">Level:</span> <span class="summary-val">${eduType} - ${gradeLevel}</span></div>
            <div class="summary-item mt-2"><span class="summary-label">Applicant Name:</span> <span class="summary-val text-feu-green-700">${fullName}</span></div>
            <div class="summary-item"><span class="summary-label">Email:</span> <span class="summary-val">${email}</span></div>
            <div class="summary-item"><span class="summary-label">Mobile:</span> <span class="summary-val">${mobile}</span></div>
        </div>
    `;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        if (dialog) dialog.classList.remove('scale-95');
    }, 10);
    
    document.body.style.overflow = 'hidden';
};

window.closeConfirmationModal = function() {
    const modal = document.getElementById('submitConfirmModal');
    const dialog = document.getElementById('modalDialog');
    
    if(modal) {
        modal.classList.add('opacity-0');
        if (dialog) dialog.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }
    document.body.style.overflow = '';
};

window.confirmSubmission = function() {
    const form = document.getElementById('enrollmentForm');
    const btn = document.getElementById('btnConfirmSubmit');
    if (btn) {
        btn.innerHTML = '<i class="ki-filled ki-loading animate-spin text-xl"></i> Submitting...';
        btn.disabled = true;
    }
    
    window.isFormDirty = false;
    HTMLFormElement.prototype.submit.call(form); 
};


// --- INITIALIZATION FUNCTIONS ---

function initUnsavedChangesWarning() {
    const form = document.getElementById('enrollmentForm');
    if (!form) return;

    form.addEventListener('input', () => { window.isFormDirty = true; });
    form.addEventListener('change', () => { window.isFormDirty = true; });

    window.addEventListener('beforeunload', function (e) {
        if (window.isFormDirty) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
}

function initCampusSelection() {
    const form = document.getElementById('enrollmentForm');
    if(form) {
        window.currentCampus = form.getAttribute('data-selected-campus') || '';
        if(window.currentCampus) {
            window.selectCampus(window.currentCampus);
        } else {
            const docItems = document.querySelectorAll('.document-item');
            docItems.forEach(item => {
                item.style.display = 'none';
                const input = item.querySelector('.document-input');
                if (input) input.required = false;
            });
            window.updateGradeLevels();
        }
    }
}

function initStickyHeader() {
    window.addEventListener('scroll', function() {
        const mainHeader = document.getElementById('stickyHeader');
        if (mainHeader) {
            if (window.scrollY > 100) mainHeader.classList.add('scrolled');
            else mainHeader.classList.remove('scrolled');
        }

        const mobileHeader = document.querySelector('.mobile-header');
        if (mobileHeader) {
            if (window.scrollY > 10) mobileHeader.classList.add('scrolled');
            else mobileHeader.classList.remove('scrolled');
        }
    });
}

function initConditionalFields() {
    const citizenshipSelect = document.querySelector('[name="citizenship"]');
    const foreignFieldsContainer = document.getElementById('foreign_fields_container');
    const indigenousGroupContainer = document.getElementById('indigenous_group_container');
    
    const passportInput = document.querySelector('[name="passport_number"]');
    const visaTypeInput = document.querySelector('[name="visa_type"]');
    const visaStatusInput = document.querySelector('[name="visa_status"]');
    const indigenousInput = document.querySelector('[name="indigenous_group"]');

    if (citizenshipSelect) {
        citizenshipSelect.addEventListener('change', function() {
            const val = this.value.toLowerCase();
            
            if (foreignFieldsContainer) foreignFieldsContainer.style.display = 'none';
            if (indigenousGroupContainer) indigenousGroupContainer.style.display = 'none';
            
            if (passportInput) {
                passportInput.required = false;
                if (window.ARIESValidation) window.ARIESValidation.resetField(passportInput, window.ARIESValidation.getErrorElement(passportInput));
            }
            if (visaTypeInput) {
                visaTypeInput.required = false;
                if (window.ARIESValidation) window.ARIESValidation.resetField(visaTypeInput, window.ARIESValidation.getErrorElement(visaTypeInput));
            }
            if (visaStatusInput) {
                visaStatusInput.required = false;
                if (window.ARIESValidation) window.ARIESValidation.resetField(visaStatusInput, window.ARIESValidation.getErrorElement(visaStatusInput));
            }
            
            if (val === 'foreign' || val === 'dual' || val === 'dual citizenship') {
                if (foreignFieldsContainer) foreignFieldsContainer.style.display = 'block';
                if (passportInput) passportInput.required = true;
                if (visaTypeInput) visaTypeInput.required = true;
                if (visaStatusInput) visaStatusInput.required = true;
                if (indigenousInput) indigenousInput.value = '';
                
            } else if (val === 'filipino') {
                if (indigenousGroupContainer) indigenousGroupContainer.style.display = 'block';
                if (passportInput) passportInput.value = '';
                if (visaTypeInput) visaTypeInput.value = '';
                if (visaStatusInput) visaStatusInput.value = '';
            }
        });
        citizenshipSelect.dispatchEvent(new Event('change'));
    }
}

function initDynamicFormatting() {
    const numericFields = ['lrn', 'contact_number', 'father_contact', 'mother_contact', 'guardian_contact'];
    numericFields.forEach(fieldName => {
        const input = document.querySelector(`[name="${fieldName}"]`);
        if (input) {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');
                window.checkFormValidity();
            });
        }
    });

    if (window.ARIESValidation) {
        const nameFields = ['last_name', 'first_name', 'middle_name', 'father_firstname', 'father_lastname', 'mother_firstname', 'mother_lastname', 'guardian_name'];
        nameFields.forEach(name => {
            const el = document.querySelector(`[name="${name}"]`);
            if (el) window.ARIESValidation.setupFormatting(el, 'name');
        });

        const textFields = ['birth_place', 'religion', 'citizenship', 'indigenous_group', 'father_occupation', 'mother_occupation', 'prev_school_name', 'address', 'perm_address'];
        textFields.forEach(name => {
            const el = document.querySelector(`[name="${name}"]`);
            if (el) window.ARIESValidation.setupFormatting(el, 'text');
        });
    }
}

function initAddressLookups() {
    function setupAddressLookup(prefix) {
        const regions = document.getElementById(prefix + '_region');
        const provinces = document.getElementById(prefix + '_province');
        const cities = document.getElementById(prefix + '_city');
        const barangays = document.getElementById(prefix + '_barangay');
        
        if(!regions) return;

        fetch('/api/address/regions').then(r => r.json()).then(data => {
            data.forEach(r => regions.innerHTML += `<option value="${r.code}">${r.name}</option>`);
        });

        regions.addEventListener('change', function() {
            provinces.innerHTML = '<option value="">Select Province</option>'; provinces.disabled = true;
            cities.innerHTML = '<option value="">Select City</option>'; cities.disabled = true;
            barangays.innerHTML = '<option value="">Select Barangay</option>'; barangays.disabled = true;
            if(this.value) {
                fetch(`/api/address/provinces/${this.value}`).then(r => r.json()).then(data => {
                    data.forEach(p => provinces.innerHTML += `<option value="${p.code}">${p.name}</option>`);
                    provinces.disabled = false;
                });
            }
        });

        provinces.addEventListener('change', function() {
            cities.innerHTML = '<option value="">Select City</option>'; cities.disabled = true;
            if(this.value) {
                fetch(`/api/address/cities/${this.value}`).then(r => r.json()).then(data => {
                    data.forEach(c => cities.innerHTML += `<option value="${c.code}">${c.name}</option>`);
                    cities.disabled = false;
                });
            }
        });

        cities.addEventListener('change', function() {
            barangays.innerHTML = '<option value="">Select Barangay</option>'; barangays.disabled = true;
            if(this.value) {
                fetch(`/api/address/barangays/${this.value}`).then(r => r.json()).then(data => {
                    data.forEach(b => barangays.innerHTML += `<option value="${b.name}" data-zip="${b.zip}">${b.name}</option>`);
                    barangays.disabled = false;
                });
            }
        });
    }

    setupAddressLookup('addr'); // Current
    setupAddressLookup('perm'); // Permanent
}

function initAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.animate-section').forEach(section => observer.observe(section));
}

function initSubmitLock() {}

function initLrnValidation() {
    const lrnInput = document.querySelector('[name="lrn"]');
    if (!lrnInput) return;

    lrnInput.addEventListener('blur', function() {
        const lrnValue = this.value.trim();
        const errorEl = window.ARIESValidation.getErrorElement(lrnInput);
        
        if (lrnValue.length > 0) {
            if (window.ARIESValidation.patterns.lrn.test(lrnValue)) {
                fetch(`/enrollment/api/check-lrn?lrn=${encodeURIComponent(lrnValue)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            window.ARIESValidation.setInvalid(lrnInput, errorEl, 'LRN already exists, please check your credentials.');
                            window.checkFormValidity();
                        } else {
                            window.ARIESValidation.setValid(lrnInput, errorEl);
                            window.checkFormValidity();
                        }
                    });
            }
        } else {
            window.ARIESValidation.resetField(lrnInput, errorEl);
        }
    });
}

function initFormValidation() {
    const form = document.getElementById('enrollmentForm');
    if (!form) return;
    
    let isInitialLoad = true;
    setTimeout(() => { isInitialLoad = false; }, 500);

    function validateSingleField(input) {
        if (input.disabled || input.type === 'hidden' || input.offsetParent === null) {
            window.ARIESValidation.resetField(input, window.ARIESValidation.getErrorElement(input));
            return true;
        }
        
        let fieldValid = true;
        let errorMessage = window.ARIESValidation.messages.required;

        if (input.type === 'radio') {
            if (input.required) {
                const group = document.querySelectorAll(`input[name="${input.name}"]`);
                fieldValid = Array.from(group).some(r => r.checked);
                errorMessage = 'Please select an option.';
            }
        } else if (input.type === 'checkbox') {
            if (input.required && !input.checked) {
                fieldValid = false;
                errorMessage = 'Please check this box.';
            }
        } else if (input.required && window.ARIESValidation.isEmpty(input.value)) {
            fieldValid = false;
            // Provide explicit message even when field is left empty
            if (input.name.includes('contact') || input.name.includes('phone')) {
                errorMessage = 'Please enter a valid phone number with 11 digits (e.g. 09123456789)';
            }
        } else if (input.value) {
            // Provide explicit message when pattern fails
            if (input.name.includes('contact') || input.name.includes('phone')) {
                if (!window.ARIESValidation.patterns.phone.test(input.value)) {
                    fieldValid = false;
                    errorMessage = 'Please enter a valid phone number with 11 digits (e.g. 09123456789)';
                }
            } else if (input.name === 'lrn') {
                if (!window.ARIESValidation.patterns.lrn.test(input.value)) {
                    fieldValid = false;
                    errorMessage = window.ARIESValidation.messages.lrn.invalid;
                }
            } else if (input.type === 'email') {
                if (!window.ARIESValidation.patterns.email.test(input.value)) {
                    fieldValid = false;
                    errorMessage = window.ARIESValidation.messages.email.invalid;
                }
            } else if (input.hasAttribute('pattern')) {
                const regex = new RegExp('^' + input.getAttribute('pattern') + '$');
                if (!regex.test(input.value)) {
                    fieldValid = false;
                    errorMessage = 'Invalid format.';
                    if (input.name.includes('contact') || input.name.includes('phone')) {
                        errorMessage = 'Please enter a valid phone number with 11 digits (e.g. 09123456789)';
                    }
                }
            }
        }

        const errorEl = window.ARIESValidation.getErrorElement(input);
        if (!fieldValid) {
            window.ARIESValidation.setInvalid(input, errorEl, errorMessage);
        } else {
            if (input.value && input.value.toString().trim() !== "") {
                window.ARIESValidation.setValid(input, errorEl);
            } else {
                window.ARIESValidation.resetField(input, errorEl);
            }
        }
        return fieldValid;
    }

    const inputsToValidate = form.querySelectorAll('input, select, textarea');
    inputsToValidate.forEach(input => {
        input.addEventListener('blur', function(e) { validateSingleField(this); });
        input.addEventListener('input', function(e) {
            if (this.classList.contains('is-invalid')) validateSingleField(this);
        });
        if (input.tagName === 'SELECT' || input.type === 'file' || input.type === 'radio' || input.type === 'checkbox') {
            input.addEventListener('change', function(e) { 
                if (isInitialLoad && !e.isTrusted) return; 
                validateSingleField(this); 
            });
        }
    });

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        event.stopPropagation();
        
        let isFormValid = true;
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            if (!validateSingleField(input)) isFormValid = false;
        });

        const lrnInput = document.querySelector('[name="lrn"]');
        if (lrnInput && lrnInput.classList.contains('is-invalid')) isFormValid = false;

        if (isFormValid) {
            window.showConfirmationModal();
        } else {
            const warning = document.getElementById('submitWarning');
            if (warning) {
                warning.innerHTML = '<i class="ki-filled ki-information-2 text-danger"></i> There are missing or incorrect fields. Please check the highlighted boxes.';
                warning.style.display = 'block';
            }
            window.ARIESValidation.scrollToFirstError(form);
        }
    }, false);
}

function initPageTransitions() {
    const formWrapper = document.querySelector('.form-wrapper');
    if (formWrapper) formWrapper.style.opacity = '1';
}

function toggleMobileProgress() {
    const drawer = document.getElementById('mobileProgressDrawer');
    const overlay = document.getElementById('mobileOverlay');
    if (drawer && overlay) {
        drawer.classList.toggle('show');
        overlay.classList.toggle('show');
        document.body.style.overflow = drawer.classList.contains('show') ? 'hidden' : '';
    }
}
window.toggleMobileProgress = toggleMobileProgress;

function initMobileProgress() {
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const drawer = document.getElementById('mobileProgressDrawer');
            if (drawer && drawer.classList.contains('show')) toggleMobileProgress();
        }
    });
}

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
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) age--;
                ageField.value = age > 0 ? age : '';
            }
        });
    }
}