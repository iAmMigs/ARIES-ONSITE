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

window.currentCampus = ''; 
window.isFormDirty = false; 

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

    window.addEventListener('pageshow', function(event) {
        if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
            const form = document.getElementById('enrollmentForm');
            if (form) {
                form.reset();
                window.isFormDirty = false;
                
                form.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                    el.classList.remove('is-valid', 'is-invalid');
                });
                form.querySelectorAll('.error-message.show, .group-error-message.show').forEach(el => {
                    el.style.display = 'none';
                    el.classList.remove('show');
                });
                
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
    const admType = document.getElementById('admissionType')?.value;
    if(!eduSelect) return;
    
    // Restrict Education Type Offerings based on Campus Selection
    const kinderOpt = eduSelect.querySelector('option[value="Kinder"]');
    const gsOpt = eduSelect.querySelector('option[value="Grade School"]');
    const jhsOpt = eduSelect.querySelector('option[value="Junior High School"]');
    
    eduSelect.value = ""; 
    
    if (campus === 'feu_alabang') {
        if(kinderOpt) { kinderOpt.disabled = true; kinderOpt.hidden = true; }
        if(gsOpt) { gsOpt.disabled = true; gsOpt.hidden = true; }
        if(jhsOpt) { jhsOpt.disabled = true; jhsOpt.hidden = true; }
    } else {
        if(kinderOpt) { 
            // Hide Kinder if user is a Transferee in Diliman
            if (admType === 'Transferee') {
                kinderOpt.disabled = true; kinderOpt.hidden = true;
            } else {
                kinderOpt.disabled = false; kinderOpt.hidden = false;
            }
        }
        if(gsOpt) { gsOpt.disabled = false; gsOpt.hidden = false; }
        if(jhsOpt) { jhsOpt.disabled = false; jhsOpt.hidden = false; }
    }
    
    if (window.ARIESValidation) window.ARIESValidation.resetField(eduSelect, window.ARIESValidation.getErrorElement(eduSelect));
    
    window.updateGradeLevels();
};

window.updateGradeLevels = function() {
    const admType = document.getElementById('admissionType')?.value;
    const eduType = document.getElementById('educationType')?.value;
    const eduSelect = document.getElementById('educationType');
    const gradeSelect = document.getElementById('gradeLevel');
    const lrnInput = document.getElementById('lrnInput');
    const lrnRequiredSpan = document.getElementById('lrnRequiredSpan');

    if (!gradeSelect) return;

    // Handle dynamic Kinder option restriction on Admission Type change
    if (eduSelect && admType) {
        const kinderOpt = eduSelect.querySelector('option[value="Kinder"]');
        if (admType === 'Transferee') {
            if (kinderOpt) { kinderOpt.disabled = true; kinderOpt.hidden = true; }
            
            // Clear selection if switched to Transferee while Kinder was selected
            if (eduType === 'Kinder') {
                eduSelect.value = '';
                if (window.ARIESValidation) window.ARIESValidation.resetField(eduSelect, window.ARIESValidation.getErrorElement(eduSelect));
                gradeSelect.innerHTML = '<option value="">Select Level</option>';
                window.updateStrands();
                return; 
            }
        } else {
            if (window.currentCampus !== 'feu_alabang') {
                if (kinderOpt) { kinderOpt.disabled = false; kinderOpt.hidden = false; }
            }
        }
    }

    gradeSelect.innerHTML = '<option value="">Select Level</option>';
    let options = [];
    let isLocked = false;

    // Map dropdown options and determine locking state based on application type
    if (admType && eduType) {
        if (admType === 'New Student') {
            isLocked = true; 
            if (eduType === 'Kinder') {
                options = [{val: 'Kinder', label: 'Kinder'}];
            } else if (eduType === 'Grade School') {
                options = [{val: 'Grade 1', label: 'Grade 1'}];
            } else if (eduType === 'Junior High School') {
                options = [{val: 'Grade 7', label: 'Grade 7'}];
            } else if (eduType === 'Senior High School') {
                options = [{val: 'Grade 11', label: 'Grade 11'}];
            }
        } else if (admType === 'Transferee') {
            isLocked = false;
            if (eduType === 'Grade School') {
                options = [
                    {val: 'Grade 2', label: 'Grade 2'}, {val: 'Grade 3', label: 'Grade 3'},
                    {val: 'Grade 4', label: 'Grade 4'}, {val: 'Grade 5', label: 'Grade 5'}, {val: 'Grade 6', label: 'Grade 6'}
                ];
            } else if (eduType === 'Junior High School') {
                options = [
                    {val: 'Grade 8', label: 'Grade 8'}, {val: 'Grade 9', label: 'Grade 9'}, {val: 'Grade 10', label: 'Grade 10'}
                ];
            } else if (eduType === 'Senior High School') {
                options = [{val: 'Grade 12', label: 'Grade 12'}];
            }
        }
    }

    options.forEach(opt => {
        const el = document.createElement('option');
        el.value = opt.val;
        el.textContent = opt.label;
        gradeSelect.appendChild(el);
    });

    // Apply visual and functional lock to single-option selections
    if (isLocked && options.length === 1) {
        gradeSelect.value = options[0].val;
        gradeSelect.style.pointerEvents = 'none'; 
        gradeSelect.style.backgroundColor = '#e9ecef'; 
        gradeSelect.setAttribute('tabindex', '-1'); 
    } else {
        gradeSelect.style.pointerEvents = 'auto'; 
        gradeSelect.style.backgroundColor = '';
        gradeSelect.removeAttribute('tabindex');
    }

    // Toggle LRN and Grade Level validation requirements for Kinder
    if (eduType === 'Kinder') {
        gradeSelect.removeAttribute('required');
        if (lrnInput) lrnInput.removeAttribute('required');
        if (lrnRequiredSpan) lrnRequiredSpan.style.display = 'none';
    } else {
        gradeSelect.setAttribute('required', 'required');
        if (lrnInput) lrnInput.setAttribute('required', 'required');
        if (lrnRequiredSpan) lrnRequiredSpan.style.display = 'inline';
    }

    if (window.ARIESValidation) window.ARIESValidation.resetField(gradeSelect, window.ARIESValidation.getErrorElement(gradeSelect));
    window.updateStrands();

    if (typeof window.updateEducationHistory === 'function') {
        window.updateEducationHistory();
    }
};

window.updateStrands = function() {
    const gradeSelect = document.getElementById('gradeLevel');
    const strandGroup = document.getElementById('strandGroup');
    const select = document.getElementById('strand');
    if(!gradeSelect || !strandGroup || !select) return;

    const grade = gradeSelect.value;
    select.innerHTML = '<option value="">Select Option</option>';
    
    const isDilimanGrade11 = (window.currentCampus === 'feu_diliman' && grade === 'Grade 11');
    const isShs = (grade === 'Grade 11' || grade === 'Grade 12');

    if (isShs && !isDilimanGrade11) {
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

    // FIX: Trigger the Education History layout update whenever the Grade Level changes
    if (typeof window.updateEducationHistory === 'function') {
        window.updateEducationHistory();
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
            if (window.ARIESValidation) window.ARIESValidation.resetField(i, window.ARIESValidation.getErrorElement(i));
        } else {
            i.setAttribute('required', 'required');
        }
    });
};

window.toggleParentStatus = function(currentId, otherId, parentPrefix) {
    const current = document.getElementById(currentId);
    const other = document.getElementById(otherId);
    if(!current || !other) return;

    /*
     * Determine the active state based on which checkbox triggered the event
     */
    const isDeceased = currentId.includes('deceased') ? current.checked : other.checked;
    const isOfw = currentId.includes('ofw') ? current.checked : other.checked;

    /*
     * Mutually exclusive toggle for the checkboxes
     */
    if (current.checked) {
        other.disabled = true;
        other.checked = false;
        if (window.ARIESValidation) window.ARIESValidation.resetField(other, window.ARIESValidation.getErrorElement(other));
    } else {
        other.disabled = false;
    }

    /*
     * Handle Deceased logic: Hide groups, clear inputs, and remove required attributes
     */
    const occGroup = document.getElementById(`${parentPrefix}_occupation_group`);
    const contactGroup = document.getElementById(`${parentPrefix}_contact_group`);
    const occInput = document.getElementById(`${parentPrefix}_occupation`);
    const contactInput = document.getElementById(`${parentPrefix}_contact`);
    
    if (isDeceased) {
        if(occGroup) occGroup.style.display = 'none';
        if(contactGroup) contactGroup.style.display = 'none';
        
        if(occInput) { 
            occInput.disabled = true; 
            occInput.value = ''; 
        }
        if(contactInput) { 
            contactInput.disabled = true; 
            contactInput.value = ''; 
            contactInput.removeAttribute('required'); 
            if (window.ARIESValidation) window.ARIESValidation.resetField(contactInput, window.ARIESValidation.getErrorElement(contactInput));
        }
    } else {
        if(occGroup) occGroup.style.display = 'block';
        if(contactGroup) contactGroup.style.display = 'block';
        
        if(occInput) { occInput.disabled = false; }
        if(contactInput) { 
            contactInput.disabled = false; 
            contactInput.setAttribute('required', 'required'); 
        }
    }

    /*
     * Handle OFW logic: Show/Hide and require the country field
     */
    const countryGroup = document.getElementById(`${parentPrefix}_ofw_country_group`);
    const countryInput = document.getElementById(`${parentPrefix}_ofw_country`);
    
    if (isOfw) {
        if(countryGroup) countryGroup.style.display = 'block';
        if(countryInput) countryInput.setAttribute('required', 'required');
    } else {
        if(countryGroup) countryGroup.style.display = 'none';
        if(countryInput) {
            countryInput.removeAttribute('required');
            countryInput.value = '';
            if (window.ARIESValidation) window.ARIESValidation.resetField(countryInput, window.ARIESValidation.getErrorElement(countryInput));
        }
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

    // --- NEW RELIGION LOGIC ---
    const religionSelect = document.getElementById('religionSelect');
    const otherReligionContainer = document.getElementById('other_religion_container');
    const otherReligionInput = document.getElementById('otherReligionInput');

    if (religionSelect) {
        religionSelect.addEventListener('change', function() {
            const val = this.value.toUpperCase();
            
            if (val === 'OTHER') {
                if (otherReligionContainer) otherReligionContainer.style.display = 'block';
                if (otherReligionInput) otherReligionInput.required = true;
            } else {
                if (otherReligionContainer) otherReligionContainer.style.display = 'none';
                if (otherReligionInput) {
                    otherReligionInput.required = false;
                    otherReligionInput.value = '';
                    if (window.ARIESValidation) window.ARIESValidation.resetField(otherReligionInput, window.ARIESValidation.getErrorElement(otherReligionInput));
                }
            }
            window.checkFormValidity();
        });
        religionSelect.dispatchEvent(new Event('change'));
    }

    if (citizenshipSelect) {
        citizenshipSelect.addEventListener('change', function() {
            const val = this.value.toUpperCase();
            
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
            
            if (val && val !== 'FILIPINO') { 
                if (foreignFieldsContainer) foreignFieldsContainer.style.display = 'block';
                if (passportInput) passportInput.required = true;
                if (visaTypeInput) visaTypeInput.required = true;
                if (visaStatusInput) visaStatusInput.required = true;
                if (indigenousInput) indigenousInput.value = '';
                
            } else if (val === 'FILIPINO') {
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

    // Formatting
    document.addEventListener('input', function(e) {
        if (e.target && e.target.name && e.target.name.includes('_year')) {
            let val = e.target.value.replace(/\D/g, '');
            if (val.length > 4) {
                val = val.substring(0, 4) + '-' + val.substring(4, 8);
            }
            e.target.value = val;
        }
    });

    if (window.ARIESValidation) {
        const nameFields = ['last_name', 'first_name', 'middle_name', 'father_firstname', 'father_lastname', 'father_middlename', 'mother_firstname', 'mother_lastname', 'mother_middlename', 'guardian_name'];
        nameFields.forEach(name => {
            const el = document.querySelector(`[name="${name}"]`);
            if (el) window.ARIESValidation.setupFormatting(el, 'name');
        });

        const textFields = ['other_religion', 'birth_place', 'indigenous_group', 'father_occupation', 'mother_occupation', 'prev_school_name', 'address', 'perm_address'];
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
            if (input.name.includes('contact') || input.name.includes('phone')) {
                errorMessage = 'Please enter a valid phone number with 11 digits (e.g. 09123456789)';
            }
        } else if (input.value) {
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

// --- EDUCATION HISTORY LOGIC ---

window.updateEducationHistory = function() {
    const gradeSelect = document.getElementById('gradeLevel');
    if (!gradeSelect) return;
    const grade = gradeSelect.value;

    const placeholder = document.getElementById('educ_history_placeholder');
    const fieldsContainer = document.getElementById('educ_history_fields');
    const sections = ['kinder', 'elem', 'jhs', 'shs'];
    
    // 1. Hide all sections, disable inputs (prevents submission), and clear validation
    sections.forEach(lvl => {
        const sec = document.getElementById(`section_educ_${lvl}`);
        if(sec) {
            sec.style.display = 'none';
            sec.querySelectorAll('.educ-input').forEach(inp => {
                inp.removeAttribute('required');
                inp.disabled = true; // Disable so hidden fields are not submitted
                if (window.ARIESValidation && typeof window.ARIESValidation.getErrorElement === 'function') {
                    try {
                        const err = window.ARIESValidation.getErrorElement(inp);
                        if (err) window.ARIESValidation.resetField(inp, err);
                    } catch(e) {}
                }
            });
        }
    });

    // 2. Determine if we show the placeholder or the fields container
    if (!grade || grade === 'Kinder' || grade.trim() === '') {
        placeholder.style.display = 'block';
        fieldsContainer.style.display = 'none';
        if (grade === 'Kinder' || grade.trim() === '') {
            placeholder.textContent = grade === 'Kinder' ? 'No previous education history required for Kinder.' : 'Please select a Grade Level in Step 1 to view required education history.';
        }
        return;
    }

    placeholder.style.display = 'none';
    fieldsContainer.style.display = 'block';

    // 3. Helper to display a section, enable inputs, and GUARANTEE the initial row exists
    const requireSection = (lvl) => {
        const sec = document.getElementById(`section_educ_${lvl}`);
        if (sec) {
            sec.style.display = 'block';
            if (lvl !== 'kinder') {
                window.ensureInitialRow(lvl);
            }
            // Enable and require all inputs currently inside this section
            sec.querySelectorAll('.educ-input').forEach(inp => {
                inp.disabled = false;
                inp.setAttribute('required', 'required');
            });
        }
    };

    // 4. Logic Matrix mapping grades to their required previous history
    if (grade === 'Grade 1') {
        requireSection('kinder');
    } else if (['Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7'].includes(grade)) {
        requireSection('kinder');
        requireSection('elem');
    } else if (['Grade 8', 'Grade 9', 'Grade 10', 'Grade 11'].includes(grade)) {
        requireSection('kinder');
        requireSection('elem');
        requireSection('jhs');
    } else if (grade === 'Grade 12') {
        requireSection('kinder');
        requireSection('elem');
        requireSection('jhs');
        requireSection('shs');
    }
};

window.addSchoolRow = function(level) {
    const container = document.getElementById(`container_educ_${level}`);
    if (!container) return;

    // SHS is strictly capped at 1 row limit
    if (level === 'shs' && container.children.length >= 1) return;

    const index = Date.now() + Math.floor(Math.random() * 100);
    const levelLabel = level === 'elem' ? 'Elementary' : (level === 'jhs' ? 'Junior High School' : 'Senior High School');
    const isFirst = container.children.length === 0;

    // Determine the delete button HTML (First row cannot be deleted)
    const deleteBtnHtml = !isFirst ? 
        `<button type="button" class="btn-remove-sibling position-absolute shadow-sm" style="top: -5px; left: -5px; width: 22px; height: 22px; font-size: 10px; z-index: 10;" title="Remove School" onclick="this.closest('.school-row').remove(); window.isFormDirty = true;">
            <i class="ki-filled ki-trash"></i>
        </button>` : '';

    const row = document.createElement('div');
    row.className = 'sibling-row row g-3 align-items-center mb-3 school-row position-relative';
    row.id = `row_${index}`;

    row.innerHTML = deleteBtnHtml + `
        <div class="col-md-8">
            <label class="form-label text-xs">School Name <span class="required">*</span></label>
            <input type="text" name="educ_${level}_school[]" class="form-control form-control-sm educ-input" placeholder="School Name" required oninput="window.isFormDirty = true;">
            <input type="hidden" name="educ_${level}_level[]" value="${levelLabel}">
        </div>
        <div class="col-md-4">
            <label class="form-label text-xs">School Year <span class="required">*</span></label>
            <input type="text" name="educ_${level}_year[]" class="form-control form-control-sm educ-input" placeholder="YYYY-YYYY" pattern="\\d{4}-\\d{4}" maxlength="9" required oninput="window.isFormDirty = true;">
        </div>
    `;
    container.appendChild(row);
};

window.ensureInitialRow = function(level) {
    const container = document.getElementById(`container_educ_${level}`);
    if (container && container.children.length === 0) {
        window.addSchoolRow(level);
    }
};