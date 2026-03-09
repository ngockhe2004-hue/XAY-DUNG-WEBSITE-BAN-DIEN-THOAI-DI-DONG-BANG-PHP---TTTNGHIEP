/**
 * Address Selector Utility for PhoneStore
 * Handles fetching provinces, districts, and wards from provinces.open-api.vn
 */

class AddressSelector {
    constructor(config) {
        this.provinceEl = document.querySelector(config.provinceSelector);
        this.districtEl = document.querySelector(config.districtSelector);
        this.wardEl = document.querySelector(config.wardSelector);

        if (!this.provinceEl || !this.districtEl || !this.wardEl) {
            console.error('AddressSelector: One or more elements not found');
            return;
        }

        this.init();
    }

    async init() {
        // Clear current values but keep the "Choose" option if exists
        this.setupListeners();
        await this.loadProvinces();
    }

    setupListeners() {
        this.provinceEl.addEventListener('change', async (e) => {
            const provinceCode = e.target.selectedOptions[0]?.dataset.code;
            if (provinceCode) {
                await this.loadDistricts(provinceCode);
            } else {
                this.resetDropdown(this.districtEl, 'Quận/Huyện');
                this.resetDropdown(this.wardEl, 'Phường/Xã');
            }
        });

        this.districtEl.addEventListener('change', async (e) => {
            const districtCode = e.target.selectedOptions[0]?.dataset.code;
            if (districtCode) {
                await this.loadWards(districtCode);
            } else {
                this.resetDropdown(this.wardEl, 'Phường/Xã');
            }
        });
    }

    async loadProvinces() {
        try {
            const response = await fetch('https://provinces.open-api.vn/api/p/');
            const provinces = await response.json();

            this.populateDropdown(this.provinceEl, provinces, 'Tỉnh/Thành');
        } catch (error) {
            console.error('Error loading provinces:', error);
        }
    }

    async loadDistricts(provinceCode) {
        try {
            const response = await fetch(`https://provinces.open-api.vn/api/p/${provinceCode}?depth=2`);
            const data = await response.json();

            this.populateDropdown(this.districtEl, data.districts || [], 'Quận/Huyện');
            this.resetDropdown(this.wardEl, 'Phường/Xã');
        } catch (error) {
            console.error('Error loading districts:', error);
        }
    }

    async loadWards(districtCode) {
        try {
            const response = await fetch(`https://provinces.open-api.vn/api/d/${districtCode}?depth=2`);
            const data = await response.json();

            this.populateDropdown(this.wardEl, data.wards || [], 'Phường/Xã');
        } catch (error) {
            console.error('Error loading wards:', error);
        }
    }

    populateDropdown(el, items, label) {
        const currentValue = el.dataset.pendingValue || el.value;
        el.innerHTML = `<option value="">--Chọn ${label}--</option>`;

        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.name;
            option.textContent = item.name;
            option.dataset.code = item.code;

            if (currentValue && (item.name === currentValue)) {
                option.selected = true;
            }

            el.appendChild(option);
        });

        // Trigger change if a value was pre-selected
        if (el.value && el.value !== "") {
            el.dispatchEvent(new Event('change'));
        }

        // Clear pending value
        delete el.dataset.pendingValue;
    }

    resetDropdown(el, label) {
        el.innerHTML = `<option value="">--Chọn ${label}--</option>`;
    }

    /**
     * Pre-fills the selector with existing values
     */
    async setValues(province, district, ward) {
        this.provinceEl.dataset.pendingValue = province;
        this.districtEl.dataset.pendingValue = district;
        this.wardEl.dataset.pendingValue = ward;

        // Re-load provinces to start the chain
        await this.loadProvinces();
    }
}

// Global initialization helper
window.initAddressSelector = function (config) {
    return new AddressSelector(config);
};
