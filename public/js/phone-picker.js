/**
 * Standalone Reusable Country Phone Picker
 * Provides interactive searchable country dial code dropdown for .phone-input-group
 */
(function() {
    function initCountryPickers() {
        const groups = document.querySelectorAll('.phone-input-group');
        groups.forEach(group => {
            if (group.dataset.pickerInitialized === 'true') return;
            group.dataset.pickerInitialized = 'true';

            const pickerBtn = group.querySelector('.country-picker-btn');
            const dropdownMenu = group.querySelector('.country-dropdown-menu');
            const searchInput = group.querySelector('.country-search-input');
            const countryList = group.querySelector('.country-list');
            const selectedFlag = group.querySelector('.country-flag-icon');
            const selectedDial = group.querySelector('.country-dial-text');
            const hiddenInput = group.querySelector('input[type="hidden"]');
            const contactInput = group.querySelector('input[type="tel"]');

            if (!pickerBtn || !dropdownMenu || !countryList) return;

            function updatePhoneRestrictions() {
                if (!contactInput) return;
                const dialCode = hiddenInput ? hiddenInput.value : '+63';
                if (dialCode === '+63') {
                    contactInput.setAttribute('maxlength', '11');
                    contactInput.setAttribute('placeholder', '9xxxxxxxxx');
                } else {
                    contactInput.setAttribute('maxlength', '15');
                    contactInput.setAttribute('placeholder', 'Local phone number');
                }
            }

            function openDropdown() {
                if (pickerBtn.disabled) return;
                document.querySelectorAll('.country-dropdown-menu').forEach(m => {
                    if (m !== dropdownMenu) m.style.display = 'none';
                });
                document.querySelectorAll('.country-picker-btn').forEach(b => {
                    if (b !== pickerBtn) b.setAttribute('aria-expanded', 'false');
                });

                dropdownMenu.style.display = 'block';
                pickerBtn.setAttribute('aria-expanded', 'true');
                if (searchInput) {
                    searchInput.value = '';
                    filterCountries('');
                    setTimeout(() => searchInput.focus(), 50);
                }
            }

            function closeDropdown() {
                dropdownMenu.style.display = 'none';
                pickerBtn.setAttribute('aria-expanded', 'false');
            }

            function filterCountries(query) {
                const q = query.toLowerCase().trim();
                const items = countryList.querySelectorAll('.country-item');
                let visibleCount = 0;

                items.forEach(item => {
                    const name = (item.getAttribute('data-name') || '').toLowerCase();
                    const code = (item.getAttribute('data-code') || '').toLowerCase();
                    const iso = (item.getAttribute('data-iso') || '').toLowerCase();

                    if (!q || name.includes(q) || code.includes(q) || iso.includes(q)) {
                        item.style.display = 'flex';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });

                let noResults = countryList.querySelector('.country-no-results');
                if (visibleCount === 0) {
                    if (!noResults) {
                        noResults = document.createElement('li');
                        noResults.className = 'country-no-results';
                        noResults.textContent = 'No matching countries';
                        countryList.appendChild(noResults);
                    }
                    noResults.style.display = 'block';
                } else if (noResults) {
                    noResults.style.display = 'none';
                }
            }

            pickerBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (pickerBtn.disabled) return;
                if (dropdownMenu.style.display === 'none' || !dropdownMenu.style.display) {
                    openDropdown();
                } else {
                    closeDropdown();
                }
            });

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    filterCountries(this.value);
                });
                searchInput.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeDropdown();
                        pickerBtn.focus();
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        const firstVisible = countryList.querySelector('.country-item:not([style*="display: none"])');
                        if (firstVisible) {
                            firstVisible.click();
                        }
                    }
                });
            }

            countryList.addEventListener('click', function(e) {
                const item = e.target.closest('.country-item');
                if (!item) return;

                const code = item.getAttribute('data-code');
                const flag = item.getAttribute('data-flag');

                if (hiddenInput) {
                    hiddenInput.value = code;
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                if (selectedFlag) selectedFlag.textContent = flag;
                if (selectedDial) selectedDial.textContent = code;

                countryList.querySelectorAll('.country-item').forEach(el => el.classList.remove('active'));
                item.classList.add('active');

                updatePhoneRestrictions();
                closeDropdown();
                if (contactInput) contactInput.focus();
            });

            if (hiddenInput) {
                hiddenInput.addEventListener('change', updatePhoneRestrictions);
            }
            updatePhoneRestrictions();
        });
    }

    document.addEventListener('click', function(e) {
        document.querySelectorAll('.phone-input-group').forEach(group => {
            const pickerBtn = group.querySelector('.country-picker-btn');
            const dropdownMenu = group.querySelector('.country-dropdown-menu');
            if (pickerBtn && dropdownMenu && !pickerBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.style.display = 'none';
                pickerBtn.setAttribute('aria-expanded', 'false');
            }
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.country-dropdown-menu').forEach(menu => {
                menu.style.display = 'none';
            });
            document.querySelectorAll('.country-picker-btn').forEach(btn => {
                btn.setAttribute('aria-expanded', 'false');
            });
        }
    });

    document.addEventListener('input', function(e) {
        if (e.target && e.target.classList.contains('phone-input-field')) {
            e.target.value = e.target.value.replace(/[^\d]/g, '');
        }
    });

    window.initCountryPickers = initCountryPickers;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCountryPickers);
    } else {
        initCountryPickers();
    }
})();
