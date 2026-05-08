/**
 * ARIES Enrollment Form Module
 * Handles form interactions, validation, address lookups, and dynamic UI elements.
 */

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
    
    // Initial fetch of documents if fields are pre-filled
    if (typeof window.fetchRequiredDocuments === 'function') {
        window.fetchRequiredDocuments();
    }
    initAddressLookups();
    initAnimations();
    initSubmitLock();
    initLrnValidation();
    initUnsavedChangesWarning();
    initSessionTimeout();
    initDPAModal();
    initGuardianSync();
    initGuardianAddressSync();

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

/** Globally exposed function to handle campus selection logic and filter document requirements. */
window.selectCampus = function(campus) {
    window.currentCampus = campus;
    
    document.querySelectorAll('.campus-option').forEach(opt => opt.classList.remove('active'));
    const activeInput = document.querySelector(`.campus-option input[value="${campus}"]`);
    if(activeInput) {
        const activeOpt = activeInput.parentElement;
        activeOpt.classList.add('active');
        activeInput.checked = true;
    }

    fetchRequiredDocuments();

    const eduSelect = document.getElementById('educationType');
    const admType = document.getElementById('admissionType')?.value;
    if(!eduSelect) return;
    
    const kinderOpt = eduSelect.querySelector('option[value="Kinder"]');
    const gsOpt = eduSelect.querySelector('option[value="Grade School"]');
    const jhsOpt = eduSelect.querySelector('option[value="Junior High School"]');
    
    if (campus === 'feu_alabang') {
        if(kinderOpt) { kinderOpt.disabled = true; kinderOpt.hidden = true; }
        if(gsOpt) { gsOpt.disabled = true; gsOpt.hidden = true; }
        if(jhsOpt) { jhsOpt.disabled = true; jhsOpt.hidden = true; }
        eduSelect.value = "Senior High School";
    } else {
        eduSelect.value = ""; 
        if(kinderOpt) { 
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

    if (eduSelect && admType) {
        const kinderOpt = eduSelect.querySelector('option[value="Kinder"]');
        if (admType === 'Transferee') {
            if (kinderOpt) { kinderOpt.disabled = true; kinderOpt.hidden = true; }
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
                options = [{val: 'Grade 11', label: 'Grade 11'}, {val: 'Grade 12', label: 'Grade 12'}];
            }
        }
    }

    options.forEach(opt => {
        const el = document.createElement('option');
        el.value = opt.val;
        el.textContent = opt.label;
        gradeSelect.appendChild(el);
    });

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

    fetchRequiredDocuments();
};

window.fetchRequiredDocuments = function() {
    const container = document.getElementById('dynamic-documents-container');
    const noDocsMsg = document.getElementById('no-docs-message');
    if (!container) return;

    const campus = window.currentCampus;
    const admType = document.getElementById('admissionType')?.value;
    const gradeLevel = document.getElementById('gradeLevel')?.value;

    if (!campus || !admType || !gradeLevel) {
        container.innerHTML = '<div class="text-center p-5 text-gray-500"><i class="ki-filled ki-information-5 text-3xl mb-2 text-gray-400"></i><p>Please complete Admission Information (Step 1) to view required documents.</p></div>';
        if (noDocsMsg) noDocsMsg.style.display = 'none';
        return;
    }

    const nationality = document.getElementById('citizenshipSelect')?.value || 'FILIPINO';

    container.innerHTML = '<div class="text-center p-5 text-gray-500"><i class="ki-filled ki-loading animate-spin text-3xl mb-2 text-feu-green-600"></i><p>Loading required documents...</p></div>';

    fetch(`/api/documents/required?campus=${encodeURIComponent(campus)}&admissionType=${encodeURIComponent(admType)}&gradeLevel=${encodeURIComponent(gradeLevel)}&nationality=${encodeURIComponent(nationality)}`)
        .then(response => response.json())
        .then(data => {
            container.innerHTML = '';
            
            if (data.length === 0) {
                if (noDocsMsg) noDocsMsg.style.display = 'block';
                return;
            }

            if (noDocsMsg) noDocsMsg.style.display = 'none';

            data.forEach(doc => {
                let acceptAttr = '';
                if (doc.allowedFileTypes) {
                    const exts = doc.allowedFileTypes.split(',');
                    acceptAttr = 'accept="' + exts.map(e => '.' + e.trim()).join(', ') + '"';
                }

                let extDisplay = doc.allowedFileTypes ? `<span class="uppercase font-bold text-gray-600">${doc.allowedFileTypes}</span>` : 'All Formats';

                const html = `
                    <div class="file-upload-group mb-4 document-item" data-campus="${doc.campus}">
                        <label class="form-label d-block">
                            ${doc.documentName} 
                        </label>
                        <input type="file" name="${doc.slug}" class="form-control document-input" data-is-required="true" data-doc-name="${doc.documentName}" ${acceptAttr}>
                        <p class="text-xs text-gray-500 mt-2">
                            Max size: 10MB. Accepted formats: ${extDisplay}
                        </p>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', html);
            });
        })
        .catch(error => {
            console.error('Error fetching documents:', error);
            container.innerHTML = '<div class="text-center p-5 text-red-500"><i class="ki-filled ki-cross-circle text-3xl mb-2"></i><p>Error loading documents. Please try refreshing.</p></div>';
        });
};

window.updateStrands = function() {
    const gradeSelect = document.getElementById('gradeLevel');
    const strandGroup = document.getElementById('strandGroup');
    const select = document.getElementById('strand');
    if(!gradeSelect || !strandGroup || !select) return;

    const grade = gradeSelect.value;
    select.innerHTML = '<option value="">Select Option</option>';
    
    const lowerGrade = grade.toLowerCase();
    const isDilimanGrade11 = (window.currentCampus === 'feu_diliman' && lowerGrade.includes('11'));
    const isShs = lowerGrade.includes('11') || lowerGrade.includes('12') || lowerGrade.includes('shs');

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

    if (typeof window.updateEducationHistory === 'function') {
        window.updateEducationHistory();
    }
    
    fetchRequiredDocuments();
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

window.toggleGuardianAddress = function() {
    const isChecked = document.getElementById('guardian_same_as_applicant').checked;
    const block = document.getElementById('guardian_address_block');
    const permCheckbox = document.getElementById('guardian_perm_same');
    
    if (block) {
        block.style.display = isChecked ? 'none' : 'block';
    }
    
    if (isChecked && permCheckbox) {
        permCheckbox.checked = true;
        window.toggleParentPermAddress('guardian');
    }
};

window.toggleParentPermAddress = function(prefix) {
    const isChecked = document.getElementById(`${prefix}_perm_same`).checked;
    const block = document.getElementById(`${prefix}_perm_block`);
    if(!block) return;

    block.style.display = isChecked ? 'none' : 'block';
};

window.toggleParentStatus = function(currentId, otherId, parentPrefix) {
    const current = document.getElementById(currentId);
    const other = document.getElementById(otherId);
    if(!current || !other) return;

    const isDeceased = currentId.includes('deceased') ? current.checked : other.checked;
    const isOfw = currentId.includes('ofw') ? current.checked : other.checked;

    if (current.checked) {
        other.disabled = true;
        other.checked = false;
        if (window.ARIESValidation) window.ARIESValidation.resetField(other, window.ARIESValidation.getErrorElement(other));
    } else {
        other.disabled = false;
    }

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
            
            if (typeof jQuery !== 'undefined' && $(countryInput).hasClass('select2-hidden-accessible')) {
                $(countryInput).trigger('change');
            }

            if (window.ARIESValidation) window.ARIESValidation.resetField(countryInput, window.ARIESValidation.getErrorElement(countryInput));
        }
    }
};

window.setGuardian = function(type) {
    const fCheckbox = document.getElementById('f_is_guardian');
    const mCheckbox = document.getElementById('m_is_guardian');
    
    let isChecked = false;
    
    if (type === 'father') {
        isChecked = fCheckbox.checked;
        if (mCheckbox && isChecked) mCheckbox.checked = false;
    } else if (type === 'mother') {
        isChecked = mCheckbox.checked;
        if (fCheckbox && isChecked) fCheckbox.checked = false;
    }

    const gName = document.getElementById('guardian_name');
    const gRelation = document.getElementById('guardian_relation');
    const gContact = document.getElementById('guardian_contact');
    
    if (!gName || !gRelation || !gContact) return;

    if (isChecked) {
        const pLast = document.getElementById(`${type}_lastname`)?.value || '';
        const pFirst = document.getElementById(`${type}_firstname`)?.value || '';
        const pMiddle = document.getElementById(`${type}_middlename`)?.value || '';
        const pContact = document.getElementById(`${type}_contact`)?.value || '';
        
        let fullName = '';
        if (pLast && pFirst) {
            fullName = `${pLast}, ${pFirst} ${pMiddle}`.trim();
        } else if (pLast) {
            fullName = pLast;
        } else if (pFirst) {
            fullName = pFirst;
        }

        gName.value = fullName;
        gRelation.value = type.charAt(0).toUpperCase() + type.slice(1);
        gContact.value = pContact;

        // Lock fields
        gName.readOnly = true;
        gRelation.readOnly = true;
        gContact.readOnly = true;
        gName.classList.add('bg-gray-50', 'cursor-not-allowed');
        gRelation.classList.add('bg-gray-50', 'cursor-not-allowed');
        gContact.classList.add('bg-gray-50', 'cursor-not-allowed');
    } else {
        gName.value = '';
        gRelation.value = '';
        gContact.value = '';

        // Unlock fields
        gName.readOnly = false;
        gRelation.readOnly = false;
        gContact.readOnly = false;
        gName.classList.remove('bg-gray-50', 'cursor-not-allowed');
        gRelation.classList.remove('bg-gray-50', 'cursor-not-allowed');
        gContact.classList.remove('bg-gray-50', 'cursor-not-allowed');
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

/** Handles displaying the submission confirmation modal. */
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
    
    const promissoryDate = document.getElementById('promissoryDate')?.value;
    const promissoryHtml = promissoryDate ? `
        <div class="summary-item mt-3 bg-amber-50 p-2 rounded border border-amber-100 flex-column align-items-start">
            <span class="summary-label text-amber-800">Promissory Date:</span> 
            <span class="summary-val text-amber-900 font-black">${new Date(promissoryDate).toLocaleDateString('en-US', {month: 'long', day: 'numeric', year: 'numeric'})}</span>
            <p class="text-[10px] text-amber-700 mt-1 italic">You agreed to submit missing documents by this date.</p>
        </div>
    ` : '';

    summaryContainer.innerHTML = `
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Campus:</span> <span class="summary-val">${campus}</span></div>
            <div class="summary-item"><span class="summary-label">Admission Type:</span> <span class="summary-val">${admissionType}</span></div>
            <div class="summary-item"><span class="summary-label">Level:</span> <span class="summary-val">${eduType} - ${gradeLevel}</span></div>
            <div class="summary-item mt-2"><span class="summary-label">Applicant Name:</span> <span class="summary-val text-feu-green-700">${fullName}</span></div>
            <div class="summary-item"><span class="summary-label">Email:</span> <span class="summary-val">${email}</span></div>
            <div class="summary-item"><span class="summary-label">Mobile:</span> <span class="summary-val">${mobile}</span></div>
            ${promissoryHtml}
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

/** Handles closing the submission confirmation modal. */
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

/** Processes the final confirmation event and subms the form. */
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

window.currentMissingDocs = [];

/** Logic for Missing Documents Warning Modal */
window.showDocWarningModal = function(missingDocs) {
    window.currentMissingDocs = missingDocs;
    const modal = document.getElementById('docWarningModal');
    const dialog = document.getElementById('docWarningDialog');
    const list = document.getElementById('missingDocsList');
    
    if (!modal || !list) return;

    list.innerHTML = '';
    missingDocs.forEach(name => {
        list.innerHTML += `
            <li class="flex items-center gap-2 text-sm text-amber-900 font-semibold">
                <i class="ki-filled ki-cross text-amber-400"></i>
                ${name}
            </li>`;
    });

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        if (dialog) dialog.classList.remove('scale-95');
    }, 10);
    document.body.style.overflow = 'hidden';
};

window.closeDocWarningModal = function() {
    const modal = document.getElementById('docWarningModal');
    const dialog = document.getElementById('docWarningDialog');
    if (modal) {
        modal.classList.add('opacity-0');
        if (dialog) dialog.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }
    document.body.style.overflow = '';
};

window.proceedToFinalConfirmation = function() {
    window.closeDocWarningModal();
    setTimeout(() => {
        window.showPromissoryModal();
    }, 400);
};

window.showPromissoryModal = function() {
    const modal = document.getElementById('promissoryModal');
    const dialog = document.getElementById('promissoryDialog');
    if (!modal) return;

    // Populate Applicant Name for Alabang
    const nameEl = document.getElementById('promissoryApplicantName');
    if (nameEl) {
        const firstName = document.querySelector('[name="first_name"]')?.value || '';
        const lastName = document.querySelector('[name="last_name"]')?.value || '';
        nameEl.textContent = `${firstName} ${lastName}`.trim().toUpperCase();
    }

    // Populate Missing Docs List
    const listEl = document.getElementById('promissoryMissingDocsList');
    if (listEl) {
        listEl.innerHTML = '';
        const docs = window.currentMissingDocs || [];
        docs.forEach(doc => {
            listEl.innerHTML += `
                <li class="flex items-center gap-2 text-sm text-gray-700 font-medium">
                    <i class="ki-filled ki-cross-circle text-red-500"></i>
                    ${doc}
                </li>`;
        });
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        if (dialog) dialog.classList.remove('scale-95');
    }, 10);
    document.body.style.overflow = 'hidden';
};

window.closePromissoryModal = function() {
    const modal = document.getElementById('promissoryModal');
    const dialog = document.getElementById('promissoryDialog');
    if (modal) {
        modal.classList.add('opacity-0');
        if (dialog) dialog.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }
    document.body.style.overflow = '';
};

window.validatePromissoryAndContinue = function() {
    const dateInput = document.getElementById('promissoryDate');
    const agreementInput = document.getElementById('promissoryAgreement');
    const errorEl = document.getElementById('promissoryError');
    
    // For Alabang, we removed the date input. For Diliman, it might still be there.
    if (dateInput) {
        if (!dateInput.value) {
            if (errorEl) errorEl.classList.remove('hidden');
            dateInput.classList.add('border-red-500');
            return;
        }
        if (errorEl) errorEl.classList.add('hidden');
        dateInput.classList.remove('border-red-500');
    }

    // Set the agreement flag if the hidden input exists
    if (agreementInput) {
        agreementInput.value = '1';
    }

    window.closePromissoryModal();
    setTimeout(() => {
        window.showConfirmationModal();
    }, 400);
};

function initUnsavedChangesWarning() {
    const form = document.getElementById('enrollmentForm');
    if (!form) return;

    form.addEventListener('input', () => { window.isFormDirty = true; });
    form.addEventListener('change', () => { window.isFormDirty = true; });

    window.addEventListener('beforeunload', function (e) {
        if (window._bypassBeforeUnload) return;
        if (window.isFormDirty) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
}

/**
 * Session Timeout Handler
 * After 5 minutes of inactivity, shows a floating warning card.
 * The card has a 10-minute countdown. If the user does not interact,
 * the page redirects to the campus landing page.
 */
function initSessionTimeout() {
    const form = document.getElementById('enrollmentForm');
    if (!form) return;

    const IDLE_LIMIT    = 5 * 60 * 1000;   // 5 minutes before warning
    const COUNTDOWN_SEC = 10 * 60;          // 10-minute countdown

    const campus = form.getAttribute('data-selected-campus') || '';
    const landingUrl = campus === 'feu_alabang' ? '/alabang' : '/diliman';

    let idleTimer       = null;
    let countdownTimer  = null;
    let overlay         = null;
    let remainingSec    = COUNTDOWN_SEC;

    // --- Build the overlay + card once ---
    function buildOverlay() {
        overlay = document.createElement('div');
        overlay.id = 'sessionTimeoutOverlay';
        overlay.style.cssText = `
            position:fixed;inset:0;z-index:99999;
            background:rgba(0,0,0,.45);backdrop-filter:blur(4px);
            display:none;align-items:center;justify-content:center;
            opacity:0;transition:opacity .3s ease;
        `;

        const card = document.createElement('div');
        card.style.cssText = `
            background:#fff;border-radius:16px;padding:36px 32px 28px;
            max-width:400px;width:90%;text-align:center;
            box-shadow:0 20px 60px rgba(0,0,0,.25);
            transform:scale(.92);transition:transform .3s ease;
        `;
        card.innerHTML = `
            <div style="margin-bottom:18px">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                     stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <h3 style="margin:0 0 6px;font-size:1.25rem;font-weight:700;color:#1f2937">Are you still there?</h3>
            <p style="margin:0 0 20px;font-size:.9rem;color:#6b7280">
                This session will be lost in
                <span id="sessionCountdown" style="font-weight:700;color:#ef4444;font-size:1rem"></span>
            </p>
            <button id="sessionContinueBtn" type="button" style="
                background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;
                border:none;border-radius:10px;padding:12px 36px;font-size:.95rem;
                font-weight:600;cursor:pointer;transition:transform .15s,box-shadow .15s;
                box-shadow:0 4px 14px rgba(22,163,74,.35);
            ">Continue</button>
        `;

        overlay.appendChild(card);
        document.body.appendChild(overlay);

        // Continue button
        document.getElementById('sessionContinueBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            dismissWarning();
        });

        // Click outside card
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) dismissWarning();
        });

        // Hover effect on button
        const btn = document.getElementById('sessionContinueBtn');
        btn.addEventListener('mouseenter', () => { btn.style.transform = 'scale(1.04)'; });
        btn.addEventListener('mouseleave', () => { btn.style.transform = 'scale(1)'; });
    }

    // --- Format mm:ss ---
    function formatTime(sec) {
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    }

    // --- Show the warning overlay ---
    function showWarning() {
        if (!overlay) buildOverlay();
        remainingSec = COUNTDOWN_SEC;
        document.getElementById('sessionCountdown').textContent = formatTime(remainingSec);

        overlay.style.display = 'flex';
        requestAnimationFrame(() => {
            overlay.style.opacity = '1';
            overlay.querySelector('div').style.transform = 'scale(1)';
        });

        countdownTimer = setInterval(() => {
            remainingSec--;
            document.getElementById('sessionCountdown').textContent = formatTime(remainingSec);
            if (remainingSec <= 0) {
                clearInterval(countdownTimer);
                redirectToLanding();
            }
        }, 1000);
    }

    // --- Dismiss warning and reset idle ---
    function dismissWarning() {
        if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
        if (overlay) {
            overlay.style.opacity = '0';
            overlay.querySelector('div').style.transform = 'scale(.92)';
            setTimeout(() => { overlay.style.display = 'none'; }, 300);
        }
        resetIdleTimer();
    }

    // --- Redirect, bypassing beforeunload ---
    function redirectToLanding() {
        if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
        window._bypassBeforeUnload = true;
        window.isFormDirty = false;
        window.location.href = landingUrl;
    }

    // --- Reset the 5-minute idle timer ---
    function resetIdleTimer() {
        if (idleTimer) clearTimeout(idleTimer);
        idleTimer = setTimeout(showWarning, IDLE_LIMIT);
    }

    // --- Listen for any user activity ---
    const activityEvents = ['mousemove','mousedown','keydown','touchstart','scroll','input','change'];
    activityEvents.forEach(evt => {
        document.addEventListener(evt, () => {
            // Only reset idle if the warning is NOT currently visible
            if (!overlay || overlay.style.display === 'none') {
                resetIdleTimer();
            }
        }, { passive: true });
    });

    // Start the idle timer
    resetIdleTimer();
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
    const nationalitySelect = document.getElementById('nationalitySelect');
    const citizenshipInput = document.getElementById('citizenshipInput');
    const foreignFieldsContainer = document.getElementById('foreign_fields_container');
    const indigenousGroupContainer = document.getElementById('indigenous_group_container');
    
    const passportInput = document.querySelector('[name="passport_number"]');
    const visaTypeInput = document.querySelector('[name="visa_type"]');
    const visaStatusInput = document.querySelector('[name="visa_status"]');
    const indigenousInput = document.querySelector('[name="indigenous_group"]');

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

    function updateCitizenship() {
        if (!nationalitySelect || !citizenshipInput) return;
        const nat = nationalitySelect.value.trim().toUpperCase();
        if (nat === 'FILIPINO' || nat === '') {
            citizenshipInput.value = 'LOCAL';
        } else {
            citizenshipInput.value = 'INTERNATIONAL';
        }
        if (citizenshipInput) {
            citizenshipInput.dispatchEvent(new Event('change'));
        }
    }

    // Since nationalitySelect is using select2, bind to change
    if (typeof jQuery !== 'undefined') {
        $(nationalitySelect).on('change', updateCitizenship);
    } else if (nationalitySelect) {
        nationalitySelect.addEventListener('change', updateCitizenship);
    }

    if (citizenshipInput) {
        citizenshipInput.addEventListener('change', function() {
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
            
            if (val === 'INTERNATIONAL') { 
                if (foreignFieldsContainer) foreignFieldsContainer.style.display = 'block';
                if (passportInput) passportInput.required = true;
                if (visaTypeInput) visaTypeInput.required = true;
                if (visaStatusInput) visaStatusInput.required = true;
                if (indigenousInput) indigenousInput.value = '';
                
            } else if (val === 'LOCAL') {
                if (indigenousGroupContainer) indigenousGroupContainer.style.display = 'block';
                if (passportInput) passportInput.value = '';
                if (visaTypeInput) visaTypeInput.value = '';
                if (visaStatusInput) visaStatusInput.value = '';
            }
            
            fetchRequiredDocuments();
        });
        citizenshipInput.dispatchEvent(new Event('change'));
    }
}

function initGuardianSync() {
    const parentFields = [
        'father_lastname', 'father_firstname', 'father_middlename', 'father_contact',
        'mother_lastname', 'mother_firstname', 'mother_middlename', 'mother_contact'
    ];

    parentFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                const type = fieldId.startsWith('father') ? 'father' : 'mother';
                const checkboxId = type === 'father' ? 'f_is_guardian' : 'm_is_guardian';
                const checkbox = document.getElementById(checkboxId);
                
                if (checkbox && checkbox.checked) {
                    window.setGuardian(type);
                }
            });
        }
    });
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
    let allProvinces = [];

    // Fetch all provinces once for autocomplete lookup
    fetch('/api/address/provinces-all')
        .then(r => r.json())
        .then(data => {
            allProvinces = data;
        })
        .catch(err => console.error('Error fetching provinces:', err));

    window.setupAddressLookup = function(prefix) {
        const regions = document.getElementById(prefix + '_region');
        const provinceSearch = document.getElementById(prefix + '_province_search');
        const provinceHidden = document.getElementById(prefix + '_province');
        const suggestionsContainer = document.getElementById(prefix + '_province_suggestions');
        const cities = document.getElementById(prefix + '_city');
        const barangays = document.getElementById(prefix + '_barangay');
        
        if(!regions || !provinceSearch) return;

        // Populate regions initially
        fetch('/api/address/regions').then(r => r.json()).then(data => {
            const currentVal = regions.value;
            regions.innerHTML = '<option value="">Select Region</option>';
            
            // Add "I am unsure" option first
            const unsureOpt = document.createElement('option');
            unsureOpt.value = 'unsure';
            unsureOpt.textContent = 'I am unsure';
            regions.appendChild(unsureOpt);

            data.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.code;
                opt.textContent = r.name;
                regions.appendChild(opt);
            });
            
            if (currentVal) regions.value = currentVal;
            
            // Ensure hidden field is synced initially
            const hidden = document.getElementById(prefix + '_region_hidden');
            if (hidden && regions.value) hidden.value = regions.value;
        });

        // Sync hidden field on change
        regions.addEventListener('change', function() {
            const hidden = document.getElementById(prefix + '_region_hidden');
            if (hidden) {
                hidden.value = this.value;
            }
            window.isFormDirty = true;
        });

        // Enable regions dropdown
        regions.disabled = false;
        regions.removeAttribute('disabled');

        const showSuggestions = (val = '') => {
            suggestionsContainer.innerHTML = '';
            const searchVal = val.toLowerCase().trim();
            const selectedRegion = regions.value;

            let filtered = allProvinces;
            
            // Filter by search text if any
            if (searchVal.length > 0) {
                filtered = filtered.filter(p => 
                    p.provinceName.toLowerCase().includes(searchVal)
                );
            }

            // Filter by region if selected
            if (selectedRegion && selectedRegion !== 'unsure' && selectedRegion !== '') {
                filtered = filtered.filter(p => p.regionCode == selectedRegion);
            }

            if (filtered.length > 0) {
                filtered.forEach(p => {
                    const div = document.createElement('div');
                    div.className = 'autocomplete-suggestion';
                    
                    let displayName = p.provinceName;
                    if (searchVal.length > 0) {
                        const regex = new RegExp(`(${searchVal})`, 'gi');
                        displayName = p.provinceName.replace(regex, '<b>$1</b>');
                    }
                    
                    div.innerHTML = `${displayName} <span class="text-xs text-gray-400 font-bold ml-1">[${p.regionName}]</span>`;
                    
                    div.addEventListener('click', function() {
                        provinceSearch.value = p.provinceName;
                        provinceHidden.value = p.provinceCode;
                        
                        // Autofill Region
                        regions.value = p.regionCode;
                        const regionHidden = document.getElementById(prefix + '_region_hidden');
                        if (regionHidden) regionHidden.value = p.regionCode;

                        if (typeof jQuery !== 'undefined' && $(regions).hasClass('select2-hidden-accessible')) {
                            $(regions).trigger('change.select2');
                            $(regions).trigger('change'); // Trigger normal change too
                        }

                        suggestionsContainer.classList.add('hidden');
                        
                        // Fetch Cities for the selected province
                        cities.innerHTML = '<option value="">Select City</option>';
                        cities.disabled = true;
                        if (barangays) {
                            barangays.innerHTML = '<option value="">Select Barangay</option>';
                            barangays.disabled = true;
                        }
                        
                        fetch(`/api/address/cities/${p.provinceCode}`).then(r => r.json()).then(data => {
                            data.forEach(c => {
                                const opt = document.createElement('option');
                                opt.value = c.code;
                                opt.textContent = c.name;
                                cities.appendChild(opt);
                            });
                            cities.disabled = false;
                        });

                        if (window.ARIESValidation) {
                            window.ARIESValidation.setValid(provinceSearch, window.ARIESValidation.getErrorElement(provinceSearch));
                            // Also clear any region error
                            window.ARIESValidation.setValid(regions, window.ARIESValidation.getErrorElement(regions));
                        }
                        
                        window.isFormDirty = true;
                    });
                    suggestionsContainer.appendChild(div);
                });
                suggestionsContainer.classList.remove('hidden');
            } else {
                suggestionsContainer.classList.add('hidden');
            }
        };
        
        provinceSearch.addEventListener('input', function() { showSuggestions(this.value); });
        provinceSearch.addEventListener('focus', function() { showSuggestions(this.value); });
        provinceSearch.addEventListener('click', function() { showSuggestions(this.value); });

        // Close suggestions on outside click
        document.addEventListener('click', function(e) {
            if (!provinceSearch.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                suggestionsContainer.classList.add('hidden');
            }
        });

        regions.addEventListener('change', function() {
            const regionHidden = document.getElementById(prefix + '_region_hidden');
            if (regionHidden) regionHidden.value = this.value;
            
            // If they change region to a specific one, clear province to maintain consistency
            if (this.value && this.value !== 'unsure') {
                provinceSearch.value = '';
                provinceHidden.value = '';
                cities.innerHTML = '<option value="">Select City</option>';
                cities.disabled = true;
                if (barangays) {
                    barangays.innerHTML = '<option value="">Select Barangay</option>';
                    barangays.disabled = true;
                }
            }
            window.isFormDirty = true;
        });

        cities.addEventListener('change', function() {
            if (barangays) {
                barangays.innerHTML = '<option value="">Select Barangay</option>'; 
                barangays.disabled = true;
            }
            if(this.value) {
                fetch(`/api/address/barangays/${this.value}`).then(r => r.json()).then(data => {
                    if (barangays) {
                        data.forEach(b => {
                            const opt = document.createElement('option');
                            opt.value = b.name;
                            opt.textContent = b.name;
                            opt.dataset.zip = b.zip;
                            barangays.appendChild(opt);
                        });
                        barangays.disabled = false;
                    }
                });
            }
            window.isFormDirty = true;
        });

        if (barangays) {
            barangays.addEventListener('change', function() {
                // Zip code autofill disabled as per user request
                window.isFormDirty = true;
            });
        }
    }

    window.setupAddressLookup('addr'); 
    window.setupAddressLookup('perm'); 
    window.setupAddressLookup('educ_kinder_0');
    window.setupAddressLookup('guardian_addr');
    window.setupAddressLookup('guardian_perm');
}

window.toggleSchoolType = function(select, prefix) {
    const row = select.closest('.educ-section') || select.closest('.school-row');
    if (!row) return;

    const isInternational = select.value === 'International';
    const localGroup = document.getElementById(`${prefix}_local`);
    const intlGroup = document.getElementById(`${prefix}_intl_group`);
    const hiddenInput = row.querySelector('.intl-hidden-input');

    if (hiddenInput) hiddenInput.value = isInternational ? "1" : "0";
    
    if (isInternational) {
        if (localGroup) localGroup.style.display = 'none';
        if (intlGroup) intlGroup.style.display = 'flex';
    } else {
        if (localGroup) localGroup.style.display = 'flex';
        if (intlGroup) intlGroup.style.display = 'none';
    }
    
    window.isFormDirty = true;
};

/** Initializes UI animations and updates the floating step navigation state based on scroll coordinates. */
function initAnimations() {
    const fadeObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                fadeObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    const sections = document.querySelectorAll('.card.animate-section');
    sections.forEach(section => fadeObserver.observe(section));

    window.addEventListener('scroll', () => {
        let currentStep = 1;
        const scrollPosition = window.scrollY + (window.innerHeight / 3); 

        for(let i=1; i<=6; i++) {
            const card = document.getElementById('step-card-' + i);
            if(card && card.offsetTop <= scrollPosition) {
                currentStep = i;
            }
        }

        document.querySelectorAll('.step-nav-item').forEach(item => {
            item.classList.toggle('active', item.dataset.step == currentStep);
        });
    });
    
    window.dispatchEvent(new Event('scroll'));
}

/** Scrolls the viewport smoothly to the selected step card using its explicit ID. */
window.scrollToStep = function(stepNumber) {
    const target = document.getElementById('step-card-' + stepNumber);
    if (target) {
        window.scrollTo({
            top: target.offsetTop - 90, 
            behavior: 'smooth'
        });
    }
};

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
            // Check for missing documents
            const docInputs = form.querySelectorAll('.document-input');
            const missingDocs = [];
            docInputs.forEach(input => {
                if (!input.files || input.files.length === 0) {
                    missingDocs.push(input.getAttribute('data-doc-name') || 'Required Document');
                }
            });

            if (missingDocs.length > 0) {
                window.showDocWarningModal(missingDocs);
            } else {
                window.showConfirmationModal();
            }
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

/** Updates the display state and field requirements of the education history section based on the selected applicant grade. */
window.updateEducationHistory = function() {
    const gradeSelect = document.getElementById('gradeLevel');
    if (!gradeSelect) return;
    const grade = gradeSelect.value;

    const placeholder = document.getElementById('educ_history_placeholder');
    const fieldsContainer = document.getElementById('educ_history_fields');
    const sections = ['kinder', 'elem', 'jhs', 'shs'];
    
    /** Hides all education sections, disables inputs to prevent submission, and resets validation states. */
    sections.forEach(lvl => {
        const sec = document.getElementById(`section_educ_${lvl}`);
        if(sec) {
            sec.style.display = 'none';
            sec.querySelectorAll('.educ-input').forEach(inp => {
                inp.removeAttribute('required');
                inp.disabled = true;
                if (window.ARIESValidation && typeof window.ARIESValidation.getErrorElement === 'function') {
                    try {
                        const err = window.ARIESValidation.getErrorElement(inp);
                        if (err) window.ARIESValidation.resetField(inp, err);
                    } catch(e) {}
                }
            });
        }
    });

    /** Determines whether to display the placeholder message or the education history fields container based on the selected grade. */
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

    /** Utility function to display a specific education section, ensure its initial row exists, and enable its inputs. */
    const requireSection = (lvl) => {
        const sec = document.getElementById(`section_educ_${lvl}`);
        if (sec) {
            sec.style.display = 'block';
            if (lvl !== 'kinder') {
                window.ensureInitialRow(lvl);
            }
            sec.querySelectorAll('.educ-input').forEach(inp => {
                inp.disabled = false;
                inp.setAttribute('required', 'required');
            });
        }
    };

    /** Logic Matrix mapping selected grades to their required previous education history levels. */
    if (grade === 'Grade 1') {
        requireSection('kinder');
    } else if (['Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7'].includes(grade)) {
        // Grades 2-6 need previous elementary/kinder history
        // Grade 7 needs Elementary Graduation history
        requireSection('kinder');
        requireSection('elem');
    } else if (['Grade 8', 'Grade 9', 'Grade 10'].includes(grade)) {
        // JHS students need at least Elementary and previous JHS history
        requireSection('elem');
        requireSection('jhs');
    } else if (grade === 'Grade 11') {
        // SHS Grade 11 only needs JHS history
        requireSection('jhs');
    } else if (grade === 'Grade 12') {
        // SHS Grade 12 needs JHS and Grade 11 history
        requireSection('jhs');
        requireSection('shs');
    }
};

window.addSchoolRow = function(level) {
    const container = document.getElementById(`container_educ_${level}`);
    if (!container) return;

    if (level === 'shs' && container.children.length >= 1) return;

    const index = Date.now() + Math.floor(Math.random() * 100);
    const levelLabel = level === 'kinder' ? 'Kindergarten' : (level === 'elem' ? 'Elementary' : (level === 'jhs' ? 'Junior High School' : 'Senior High School'));
    const isFirst = container.children.length === 0;

    const deleteBtnHtml = !isFirst ? 
        `<button type="button" class="btn-remove-sibling position-absolute shadow-sm" style="top: -5px; left: -5px; width: 22px; height: 22px; font-size: 10px; z-index: 10;" title="Remove School" onclick="this.closest('.school-row').remove(); window.isFormDirty = true;">
            <i class="ki-filled ki-trash"></i>
        </button>` : '';

    const prefix = `educ_${level}_${index}`;
    const countryOptions = document.getElementById('country_options_template')?.innerHTML || '';

    const row = document.createElement('div');
    row.className = 'sibling-row p-3 bg-light rounded border border-gray-200 mb-3 school-row position-relative';
    row.id = `row_${index}`;

    row.innerHTML = deleteBtnHtml + `
        <div class="row g-3 align-items-center mb-3">
            <div class="col-md-5">
                <label class="form-label text-xs">School Name <span class="required">*</span></label>
                <input type="text" name="educ_${level}_school[]" class="form-control form-control-sm educ-input" placeholder="School Name" required oninput="window.isFormDirty = true;">
                <input type="hidden" name="educ_${level}_level[]" value="${levelLabel}">
            </div>
            <div class="col-md-3">
                <label class="form-label text-xs">School Type <span class="required">*</span></label>
                <select name="educ_${level}_type[]" class="form-select form-control-sm educ-input" required onchange="window.isFormDirty = true;">
                    <option value="Private">Private</option>
                    <option value="Public">Public</option>
                    <option value="International">International</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label text-xs">School Year <span class="required">*</span></label>
                <input type="text" name="educ_${level}_year[]" class="form-control form-control-sm educ-input" placeholder="YYYY-YYYY" pattern="\\d{4}-\\d{4}" maxlength="9" required oninput="window.isFormDirty = true;">
            </div>
        </div>

        <div class="row g-2 mt-2 location-local-group" id="${prefix}_local">
            <div class="col-md-4">
                <label class="form-label text-[10px] font-bold text-gray-500 uppercase mb-1">Region</label>
                <select id="${prefix}_region" class="form-select form-select-sm" data-placeholder="Region">
                    <option value=""></option>
                </select>
                <input type="hidden" name="educ_${level}_region[]" id="${prefix}_region_hidden">
            </div>
            <div class="col-md-4 position-relative">
                <label class="form-label text-[10px] font-bold text-gray-500 uppercase mb-1">Province</label>
                <input type="text" id="${prefix}_province_search" class="form-control form-control-sm province-autocomplete" placeholder="Province" autocomplete="off">
                <input type="hidden" id="${prefix}_province" name="educ_${level}_province[]">
                <div id="${prefix}_province_suggestions" class="autocomplete-suggestions hidden"></div>
            </div>
            <div class="col-md-4">
                <label class="form-label text-[10px] font-bold text-gray-500 uppercase mb-1">City/Municipality</label>
                <select id="${prefix}_city" name="educ_${level}_city[]" class="form-select form-select-sm" disabled data-placeholder="City/Municipality">
                    <option value=""></option>
                </select>
            </div>
        </div>
        
        <div class="row g-2 mt-2 location-intl-group" id="${prefix}_intl_group" style="display:none;">
            <div class="col-md-6">
                <label class="form-label text-[10px] font-bold text-gray-500 uppercase mb-1">Country</label>
                <select name="educ_${level}_country[]" class="form-select form-select-sm country-select" data-placeholder="Country">
                    ${countryOptions}
                </select>
            </div>
        </div>
        <input type="hidden" name="educ_${level}_is_international[]" class="intl-hidden-input" value="0">
    `;
    container.appendChild(row);

    const select = row.querySelector(`select[name="educ_${level}_type[]"]`);
    if (select) {
        select.addEventListener('change', function() {
            window.toggleSchoolType(this, prefix);
        });
    }

    if (window.setupAddressLookup) {
        window.setupAddressLookup(prefix);
    }
};

window.ensureInitialRow = function(level) {
    const container = document.getElementById(`container_educ_${level}`);
    if (container && container.children.length === 0) {
        window.addSchoolRow(level);
    }
};

function initDPAModal() {
    const overlay = document.getElementById('dpaModalOverlay');
    const scrollArea = document.getElementById('dpaScrollArea');
    const checkbox = document.getElementById('dpa_consent_check');
    const btnAgree = document.getElementById('btnDpaAgree');
    const hint = document.getElementById('dpaScrollHint');

    if (!overlay) return;

    if (sessionStorage.getItem('dpa_agreed')) {
        return; // Already agreed this session
    }

    // Delay slightly to ensure transition works
    setTimeout(() => {
        overlay.classList.add('visible');
        document.body.style.overflow = 'hidden';
    }, 100);

    // Initial check in case the content is small enough to not need scrolling
    setTimeout(() => {
        if (scrollArea.scrollHeight <= scrollArea.clientHeight + 20) {
            checkbox.disabled = false;
            hint.style.display = 'none';
        }
    }, 200);

    // Handle scroll to unlock
    scrollArea.addEventListener('scroll', function() {
        if (this.scrollTop + this.clientHeight >= this.scrollHeight - 20) {
            checkbox.disabled = false;
            if (hint) hint.style.display = 'none';
        }
    });

    // Handle checkbox to unlock button
    if (checkbox) {
        checkbox.addEventListener('change', function() {
            if (btnAgree) btnAgree.disabled = !this.checked;
        });
    }

    // Handle agree button click
    if (btnAgree) {
        btnAgree.addEventListener('click', function() {
            sessionStorage.setItem('dpa_agreed', '1');
            overlay.classList.remove('visible');
            document.body.style.overflow = '';
        });
    }
}

/**
 * Guardian Address Sync (Guardian-Only)
 * - Guardian "Same as Applicant" checkbox: hides/shows the guardian's own address block.
 * - Guardian also has a "Permanent same as Current" checkbox for their own addresses.
 * - Parents (Father/Mother) always have independent address blocks with their own perm toggles.
 */
function initGuardianAddressSync() {
    // --- Guardian "Same as Applicant" toggle ---
    const guardianSync = document.getElementById('guardian_same_as_applicant');
    const guardianBlock = document.getElementById('guardian_address_block');

    if (guardianSync && guardianBlock) {
        // Initial state
        guardianBlock.style.display = guardianSync.checked ? 'none' : '';

        guardianSync.addEventListener('change', function() {
            guardianBlock.style.display = this.checked ? 'none' : '';
        });
    }

    // --- Guardian permanent address toggle ---
    const guardianPermSame = document.getElementById('guardian_perm_same');
    const guardianPermBlock = document.getElementById('guardian_perm_block');
    if (guardianPermSame && guardianPermBlock) {
        guardianPermSame.addEventListener('change', function() {
            guardianPermBlock.style.display = this.checked ? 'none' : '';
        });
    }

}