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
        {val: 'kinder', label: 'Kindergarten'},
        {val: 'grade_1', label: 'Grade 1'},
        {val: 'grade_2', label: 'Grade 2'},
        {val: 'grade_3', label: 'Grade 3'},
        {val: 'grade_4', label: 'Grade 4'},
        {val: 'grade_5', label: 'Grade 5'},
        {val: 'grade_6', label: 'Grade 6'}
    ],
    'Secondary': [
        {val: 'grade_7', label: 'Grade 7'},
        {val: 'grade_8', label: 'Grade 8'},
        {val: 'grade_9', label: 'Grade 9'},
        {val: 'grade_10', label: 'Grade 10'},
        {val: 'grade_11', label: 'Grade 11'},
        {val: 'grade_12', label: 'Grade 12'}
    ]
};

window.currentCampus = ''; // Global State

document.addEventListener('DOMContentLoaded', function() {
    initCampusSelection();
    initStickyHeader();
    initPageTransitions();
    initMobileProgress();
    initAgeCalculator();
    initConditionalFields();
    initDynamicFormatting();
    initFormValidation();
    initAutoSave();
    initAddressLookups();
    initAnimations();
    initSubmitLock();
    initLrnValidation();
});

// --- CORE LOGIC EXPOSED GLOBALLY FOR INLINE HTML ATTRIBUTES ---

window.selectCampus = function(campus) {
    window.currentCampus = campus;
    
    // Visual toggle
    document.querySelectorAll('.campus-option').forEach(opt => opt.classList.remove('active'));
    const activeInput = document.querySelector(`.campus-option input[value="${campus}"]`);
    if(activeInput) {
        const activeOpt = activeInput.parentElement;
        activeOpt.classList.add('active');
        activeInput.checked = true;
    }

    // --- NEW: Filter Documents by Campus ---
    const docItems = document.querySelectorAll('.document-item');
    let visibleCount = 0;
    
    // MAP THE HTML VALUE TO MATCH THE DATABASE CONSTANTS ('Alabang' or 'Diliman')
    let mappedCampusName = (campus === 'feu_alabang') ? 'FALAB' : 'FDIL';
    
    docItems.forEach(item => {
        const docCampus = item.getAttribute('data-campus');
        const input = item.querySelector('.document-input');
        
        // Show if it applies to BOTH (empty), matches the raw HTML, OR matches the DB constant!
        if (!docCampus || docCampus === campus || docCampus === mappedCampusName) {
            item.style.display = 'block';
            // Safely restore the required status if it was strictly required
            if (input && input.getAttribute('data-is-required') === 'true') {
                input.required = true;
            }
            visibleCount++;
        } else {
            // Hide docs for the OTHER campus
            item.style.display = 'none';
            // Strip required status so the hidden input doesn't block the submit button!
            if (input) input.required = false; 
        }
    });
    
    const noDocsMsg = document.getElementById('no-docs-message');
    if (noDocsMsg) {
        noDocsMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
    }

    // Handle FEU Alabang Restrictions
    const eduSelect = document.getElementById('educationType');
    if(!eduSelect) return;
    
    const primaryOption = eduSelect.querySelector('option[value="Primary"]');
    eduSelect.value = ""; // Reset selection
    
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
            // Filter out Junior High (7-10) if Alabang is selected
            if (window.currentCampus === 'feu_alabang' && type === 'Secondary') {
                if (['grade_7', 'grade_8', 'grade_9', 'grade_10'].includes(grade.val)) return;
            }
            const opt = document.createElement('option');
            opt.value = grade.val;
            opt.textContent = grade.label;
            select.appendChild(opt);
        });
    }
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
        } else {
            i.setAttribute('required', 'required');
        }
    });
    
    const form = document.getElementById('enrollmentForm');
    if(form) form.dispatchEvent(new Event('change'));
};

window.toggleParentStatus = function(currentId, otherId) {
    const current = document.getElementById(currentId);
    const other = document.getElementById(otherId);
    if(!current || !other) return;

    if (current.checked) {
        other.disabled = true;
        other.checked = false;
    } else {
        other.disabled = false;
    }
};

window.addSibling = function() {
    const container = document.getElementById('siblings_container');
    if(!container) return;

    const row = `
        <div class="sibling-row row g-3 mb-3 align-items-center">
            <button type="button" class="btn-remove-sibling" onclick="this.closest('.sibling-row').remove()" title="Remove Sibling">
                <i class="ki-filled ki-trash"></i>
            </button>
            <div class="col-md-5">
                <label class="form-label text-xs">Name</label>
                <input type="text" name="sibling_name[]" class="form-control form-control-sm" placeholder="Full Name">
            </div>
            <div class="col-md-4">
                <label class="form-label text-xs">School</label>
                <input type="text" name="sibling_school[]" class="form-control form-control-sm" placeholder="School">
            </div>
             <div class="col-md-3">
                <label class="form-label text-xs">Student No.</label>
                <input type="text" name="sibling_student_no[]" class="form-control form-control-sm" placeholder="20xxxxxx">
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', row);
};

window.checkFormValidity = function() {
    const form = document.getElementById('enrollmentForm');
    const btn = document.getElementById('submitBtn');
    const warning = document.getElementById('submitWarning');
    if(!form || !btn) return;

    // Verify if any field is forcefully marked invalid (like via the LRN API)
    const hasCustomErrors = form.querySelectorAll('.is-invalid').length > 0;

    if (form.checkValidity() && !hasCustomErrors) {
        btn.disabled = false;
        if(warning) warning.style.display = 'none';
    } else {
        btn.disabled = true;
        if(warning) warning.style.display = 'block';
    }
};

// --- INITIALIZATION FUNCTIONS ---

function initCampusSelection() {
    const form = document.getElementById('enrollmentForm');
    if(form) {
        window.currentCampus = form.getAttribute('data-selected-campus') || '';
        if(window.currentCampus) {
            window.selectCampus(window.currentCampus);
        } else {
            // If no campus is selected yet, hide ALL documents initially
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
        // Handle desktop sticky header
        const mainHeader = document.getElementById('stickyHeader');
        if (mainHeader) {
            if (window.scrollY > 100) mainHeader.classList.add('scrolled');
            else mainHeader.classList.remove('scrolled');
        }

        // Handle mobile header if exists
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
            
            // Hide both containers by default
            if (foreignFieldsContainer) foreignFieldsContainer.style.display = 'none';
            if (indigenousGroupContainer) indigenousGroupContainer.style.display = 'none';
            
            // Remove 'required' from foreign fields
            if (passportInput) passportInput.required = false;
            if (visaTypeInput) visaTypeInput.required = false;
            if (visaStatusInput) visaStatusInput.required = false;
            
            // Handle specific selections
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
    // Restrict specific fields to numeric input only
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

    // Formatting for Names and Texts (Title Case)
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

function initSubmitLock() {
    const form = document.getElementById('enrollmentForm');
    if(!form) return;

    form.addEventListener('change', window.checkFormValidity);
    form.addEventListener('input', window.checkFormValidity);
}

function initLrnValidation() {
    const lrnInput = document.querySelector('[name="lrn"]');
    if (!lrnInput) return;

    const errorMsg = document.createElement('div');
    errorMsg.className = 'text-danger mt-1 fw-bold';
    errorMsg.style.display = 'none';
    errorMsg.style.fontSize = '0.85em';
    errorMsg.innerText = 'LRN already exists, please check your credentials.';
    lrnInput.parentNode.appendChild(errorMsg);

    lrnInput.addEventListener('blur', function() {
        const lrnValue = this.value.trim();
        
        if (lrnValue.length > 0) {
            fetch(`/enrollment/api/check-lrn?lrn=${encodeURIComponent(lrnValue)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        errorMsg.style.display = 'block';
                        lrnInput.classList.add('is-invalid');
                        lrnInput.style.borderColor = 'red';
                        window.checkFormValidity();
                    } else {
                        errorMsg.style.display = 'none';
                        lrnInput.classList.remove('is-invalid');
                        lrnInput.style.borderColor = '';
                        window.checkFormValidity();
                    }
                });
        } else {
            errorMsg.style.display = 'none';
            lrnInput.classList.remove('is-invalid');
            lrnInput.style.borderColor = '';
            window.checkFormValidity();
        }
    });
}

function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity() || form.querySelectorAll('.is-invalid').length > 0) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

function initPageTransitions() {
    const formWrapper = document.querySelector('.form-wrapper');
    const navigationLinks = document.querySelectorAll('.btn-secondary, .btn-ghost, a[href]');
    
    if (formWrapper) formWrapper.style.opacity = '1';
    
    navigationLinks.forEach(link => {
        if (link.tagName === 'A' && link.href && !link.href.includes('#') && !link.href.includes('mailto:')) {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('http://') || href.startsWith('https://')) return;
                
                e.preventDefault();
                if (formWrapper) {
                    formWrapper.classList.add('transitioning-out');
                    setTimeout(() => window.location.href = href, 300);
                } else {
                    window.location.href = href;
                }
            });
        }
    });
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

function initAutoSave() {
    const form = document.getElementById('enrollmentForm');
    if (form) {
        const formId = form.id;
        const storageKey = `aries_${formId}_draft`;
        
        loadFormData(form, storageKey);
        
        let saveTimeout;
        form.addEventListener('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => saveFormData(form, storageKey), 1000);
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
        if (!['password', 'token', 'csrf', 'adcon', 'req_id_picture'].some(s => name.toLowerCase().includes(s))) {
            data[name] = value;
        }
    });
    try { localStorage.setItem(key, JSON.stringify(data)); } catch (e) {}
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
    } catch (e) {}
}