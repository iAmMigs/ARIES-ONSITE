/**
 * Philippine Address Lookup Data
 * Based on PSGC (Philippine Standard Geographic Code).
 */

const PhAddressLookup = {
    regions: [
        { code: '17', name: 'NATIONAL CAPITAL REGION (NCR)' },
        { code: '1', name: 'REGION I (ILOCOS REGION)' },
        { code: '2', name: 'REGION II (CAGAYAN VALLEY)' },
        { code: '3', name: 'REGION III (CENTRAL LUZON)' },
        { code: '4', name: 'REGION IV-A (CALABARZON)' },
        { code: '5', name: 'REGION IV-B (MIMAROPA)' },
        { code: '6', name: 'REGION V (BICOL REGION)' },
        { code: '7', name: 'REGION VI (WESTERN VISAYAS)' },
        { code: '8', name: 'REGION VII (CENTRAL VISAYAS)' },
        { code: '9', name: 'REGION VIII (EASTERN VISAYAS)' },
        { code: '10', name: 'REGION IX (ZAMBOANGA PENINSULA)' },
        { code: '11', name: 'REGION X (NORTHERN MINDANAO)' },
        { code: '12', name: 'REGION XI (DAVAO REGION)' },
        { code: '13', name: 'REGION XII (SOCCSKSARGEN)' },
        { code: '14', name: 'BARMM' },
        { code: '15', name: 'CAR (CORDILLERA ADMINISTRATIVE REGION)' },
        { code: '16', name: 'REGION XIII (CARAGA)' }
    ],

    provinces: {
        '17': [{ code: '81', name: 'METRO MANILA' }],
        '1': [
            { code: '1', name: 'ILOCOS NORTE' },
            { code: '2', name: 'ILOCOS SUR' },
            { code: '3', name: 'LA UNION' },
            { code: '4', name: 'PANGASINAN' }
        ],
        '2': [
            { code: '5', name: 'BATANES' },
            { code: '6', name: 'CAGAYAN' },
            { code: '7', name: 'ISABELA' },
            { code: '8', name: 'NUEVA VIZCAYA' },
            { code: '9', name: 'QUIRINO' }
        ],
        '3': [
            { code: '10', name: 'AURORA' },
            { code: '11', name: 'BATAAN' },
            { code: '12', name: 'BULACAN' },
            { code: '13', name: 'NUEVA ECIJA' },
            { code: '14', name: 'PAMPANGA' },
            { code: '15', name: 'TARLAC' },
            { code: '16', name: 'ZAMBALES' }
        ],
        '4': [
            { code: '17', name: 'BATANGAS' },
            { code: '18', name: 'CAVITE' },
            { code: '19', name: 'LAGUNA' },
            { code: '20', name: 'QUEZON' },
            { code: '21', name: 'RIZAL' }
        ],
        '5': [
            { code: '22', name: 'MARINDUQUE' },
            { code: '23', name: 'OCCIDENTAL MINDORO' },
            { code: '24', name: 'ORIENTAL MINDORO' },
            { code: '25', name: 'PALAWAN' },
            { code: '26', name: 'ROMBLON' }
        ],
        '6': [
            { code: '27', name: 'ALBAY' },
            { code: '28', name: 'CAMARINES NORTE' },
            { code: '29', name: 'CAMARINES SUR' },
            { code: '30', name: 'SORSOGON' },
            { code: '31', name: 'CATANDUANES' },
            { code: '32', name: 'MASBATE' }
        ],
        '7': [
            { code: '33', name: 'AKLAN' },
            { code: '34', name: 'ANTIQUE' },
            { code: '35', name: 'CAPIZ' },
            { code: '36', name: 'GUIMARAS' },
            { code: '37', name: 'ILOILO' }
        ],
        '8': [
            { code: '40', name: 'BOHOL' },
            { code: '41', name: 'CEBU' }
        ],
        '9': [
            { code: '43', name: 'BILIRAN' },
            { code: '44', name: 'EASTERN SAMAR' },
            { code: '45', name: 'LEYTE' },
            { code: '46', name: 'NORTHERN SAMAR' },
            { code: '47', name: 'SAMAR (WESTERN SAMAR)' },
            { code: '48', name: 'SOUTHERN LEYTE' }
        ],
        '10': [
            { code: '49', name: 'ZAMBOANGA DEL NORTE' },
            { code: '50', name: 'ZAMBOANGA DEL SUR' },
            { code: '51', name: 'ZAMBOANGA SIBUGAY' }
        ],
        '11': [
            { code: '52', name: 'BUKIDNON' },
            { code: '53', name: 'CAMIGUIN' },
            { code: '54', name: 'LANAO DEL NORTE' },
            { code: '55', name: 'MISAMIS OCCIDENTAL' },
            { code: '56', name: 'MISAMIS ORIENTAL' }
        ],
        '12': [
            { code: '57', name: 'DAVAO DE ORO' },
            { code: '58', name: 'DAVAO DEL NORTE' },
            { code: '59', name: 'DAVAO DEL SUR' },
            { code: '60', name: 'DAVAO ORIENTAL' },
            { code: '82', name: 'DAVAO OCCIDENTAL' }
        ],
        '13': [
            { code: '61', name: 'COTABATO (NORTH COTABATO)' },
            { code: '62', name: 'SARANGANI' },
            { code: '63', name: 'SOUTH COTABATO' },
            { code: '64', name: 'SULTAN KUDARAT' }
        ],
        '14': [
            { code: '65', name: 'BASILAN' },
            { code: '66', name: 'LANAO DEL SUR' },
            { code: '67', name: 'MAGUINDANAO DEL NORTE' },
            { code: '68', name: 'SULU' },
            { code: '69', name: 'TAWI-TAWI' }
        ],
        '15': [
            { code: '70', name: 'ABRA' },
            { code: '71', name: 'APAYAO' },
            { code: '72', name: 'BENGUET' },
            { code: '73', name: 'IFUGAO' },
            { code: '74', name: 'KALINGA' },
            { code: '75', name: 'MOUNTAIN PROVINCE' }
        ],
        '16': [
            { code: '76', name: 'AGUSAN DEL NORTE' },
            { code: '77', name: 'AGUSAN DEL SUR' },
            { code: '78', name: 'SURIGAO DEL SUR' },
            { code: '79', name: 'SURIGAO DEL NORTE' },
            { code: '80', name: 'DINAGAT ISLANDS' }
        ],
        '18': [
            { code: '38', name: 'NEGROS OCCIDENTAL' },
            { code: '39', name: 'NEGROS ORIENTAL' },
            { code: '42', name: 'SIQUIJOR' }
        ]
    },

    cities: {
        '81': [
            { code: '1617', name: 'CALOOCAN CITY' },
            { code: '1618', name: 'LAS PIÑAS CITY' },
            { code: '1619', name: 'MAKATI CITY' },
            { code: '1620', name: 'MALABON CITY' },
            { code: '1621', name: 'MANDALUYONG CITY' },
            { code: '1622', name: 'MANILA CITY' },
            { code: '1623', name: 'MARIKINA CITY' },
            { code: '1624', name: 'MUNTINLUPA CITY' },
            { code: '1625', name: 'NAVOTAS CITY' },
            { code: '1626', name: 'PARAÑAQUE CITY' },
            { code: '1627', name: 'PASAY CITY' },
            { code: '1628', name: 'PASIG CITY' },
            { code: '1629', name: 'QUEZON CITY' },
            { code: '1630', name: 'SAN JUAN CITY' },
            { code: '1631', name: 'TAGUIG CITY' },
            { code: '1632', name: 'VALENZUELA CITY' },
            { code: '1633', name: 'PATEROS' }
        ],
        '18': [ // Cavite
            { code: '1', name: 'ALFONSO' },
            { code: '2', name: 'AMADEO' },
            { code: '3', name: 'BACOOR CITY' },
            { code: '4', name: 'CARMONA' },
            { code: '5', name: 'CAVITE CITY' },
            { code: '6', name: 'DASMARIÑAS CITY' },
            { code: '7', name: 'GEN. MARIANO ALVAREZ' },
            { code: '8', name: 'GEN. EMILIO AGUINALDO' },
            { code: '9', name: 'GEN. TRIAS CITY' },
            { code: '10', name: 'IMUS CITY' },
            { code: '11', name: 'INDANG' },
            { code: '12', name: 'KAWIT' },
            { code: '13', name: 'MAGALLANES' },
            { code: '14', name: 'MARAGONDON' },
            { code: '15', name: 'MENDEZ' },
            { code: '16', name: 'NAIC' },
            { code: '17', name: 'NOVELETA' },
            { code: '18', name: 'ROSARIO' },
            { code: '19', name: 'SILANG' },
            { code: '20', name: 'TAGAYTAY CITY' },
            { code: '21', name: 'TANZA' },
            { code: '22', name: 'TERNATE' },
            { code: '23', name: 'TRECE MARTIRES CITY' }
        ],
        '19': [ // Laguna
            { code: '1', name: 'ALAMINOS' },
            { code: '2', name: 'BAY' },
            { code: '3', name: 'BIÑAN CITY' },
            { code: '4', name: 'CABUYAO CITY' },
            { code: '5', name: 'CALAMBA CITY' },
            { code: '6', name: 'CALAUAN' },
            { code: '7', name: 'CAVINTI' },
            { code: '8', name: 'FAMY' },
            { code: '9', name: 'KALAYAAN' },
            { code: '10', name: 'LILIW' },
            { code: '11', name: 'LOS BAÑOS' },
            { code: '12', name: 'LUISIANA' },
            { code: '13', name: 'LUMBAN' },
            { code: '14', name: 'MABITAC' },
            { code: '15', name: 'MAGDALENA' },
            { code: '16', name: 'MAJAYJAY' },
            { code: '17', name: 'NAGCARLAN' },
            { code: '18', name: 'PAETE' },
            { code: '19', name: 'PAGSANJAN' },
            { code: '20', name: 'PAKIL' },
            { code: '21', name: 'PANGIL' },
            { code: '22', name: 'PILA' },
            { code: '23', name: 'RIZAL' },
            { code: '24', name: 'SAN PABLO CITY' },
            { code: '25', name: 'SAN PEDRO CITY' },
            { code: '26', name: 'SANTA CRUZ' },
            { code: '27', name: 'SANTA MARIA' },
            { code: '28', name: 'SANTA ROSA CITY' },
            { code: '29', name: 'SINILOAN' },
            { code: '30', name: 'VICTORIA' }
        ],
        '21': [ // Rizal
            { code: '1', name: 'ANGONO' },
            { code: '2', name: 'ANTIPOLO CITY' },
            { code: '3', name: 'BARAS' },
            { code: '4', name: 'BINANGONAN' },
            { code: '5', name: 'CAINTA' },
            { code: '6', name: 'CARDONA' },
            { code: '7', name: 'JALAJALA' },
            { code: '8', name: 'MORONG' },
            { code: '9', name: 'PILILLA' },
            { code: '10', name: 'RODRIGUEZ' },
            { code: '11', name: 'SAN MATEO' },
            { code: '12', name: 'TANAY' },
            { code: '13', name: 'TAYTAY' },
            { code: '14', name: 'TERESA' }
        ],
        '12': [ // Bulacan
            { code: '1', name: 'ANGAT' },
            { code: '2', name: 'BALAGTAS' },
            { code: '3', name: 'BALIUAG' },
            { code: '4', name: 'BOCAUE' },
            { code: '5', name: 'BULACAN' },
            { code: '6', name: 'BUSTOS' },
            { code: '7', name: 'CALUMPIT' },
            { code: '8', name: 'DOÑA REMEDIOS TRINIDAD' },
            { code: '9', name: 'GUIGUINTO' },
            { code: '10', name: 'HAGONOY' },
            { code: '11', name: 'MALOLOS CITY' },
            { code: '12', name: 'MARILAO' },
            { code: '13', name: 'MEYCAUAYAN CITY' },
            { code: '14', name: 'NORZAGARAY' },
            { code: '15', name: 'OBANDO' },
            { code: '16', name: 'PANDI' },
            { code: '17', name: 'PAOMBONG' },
            { code: '18', name: 'PLARIDEL' },
            { code: '19', name: 'PULILAN' },
            { code: '20', name: 'SAN ILDEFONSO' },
            { code: '21', name: 'SAN JOSE DEL MONTE CITY' },
            { code: '22', name: 'SAN MIGUEL' },
            { code: '23', name: 'SAN RAFAEL' },
            { code: '24', name: 'SANTA MARIA' }
        ],
        '14': [ // Pampanga
            { code: '1', name: 'ANGELES CITY' },
            { code: '2', name: 'APALIT' },
            { code: '3', name: 'ARAYAT' },
            { code: '4', name: 'BACOLOR' },
            { code: '5', name: 'CANDABA' },
            { code: '6', name: 'FLORIDABLANCA' },
            { code: '7', name: 'GUAGUA' },
            { code: '8', name: 'LUBAO' },
            { code: '9', name: 'MABALACAT CITY' },
            { code: '10', name: 'MACABEBE' },
            { code: '11', name: 'MAGALANG' },
            { code: '12', name: 'MASANTOL' },
            { code: '13', name: 'MEXICO' },
            { code: '14', name: 'MINALIN' },
            { code: '15', name: 'PORAC' },
            { code: '16', name: 'SAN FERNANDO CITY' },
            { code: '17', name: 'SAN LUIS' },
            { code: '18', name: 'SAN SIMON' },
            { code: '19', name: 'SANTA ANA' },
            { code: '20', name: 'SANTA RITA' },
            { code: '21', name: 'SANTO TOMAS' },
            { code: '22', name: 'SASMUAN' }
        ]
    },

    // Sample barangays for popular cities
    barangays: {
        '1622': [ // Manila
            'Binondo', 'Ermita', 'Intramuros', 'Malate', 'Paco', 'Pandacan', 'Port Area',
            'Quiapo', 'Sampaloc', 'San Andres', 'San Miguel', 'San Nicolas', 'Santa Ana',
            'Santa Cruz', 'Santa Mesa', 'Tondo'
        ],
        '1629': [ // Quezon City
            'Bagong Pag-asa', 'Bahay Toro', 'Balingasa', 'Bungad', 'Commonwealth', 'Culiat',
            'Diliman', 'Fairview', 'Holy Spirit', 'Kamuning', 'Katipunan', 'Krus na Ligas',
            'Loyola Heights', 'New Manila', 'Novaliches', 'Project 2', 'Project 3', 'Project 4',
            'Project 6', 'Project 7', 'Project 8', 'Quezon City Proper', 'San Francisco del Monte',
            'Santa Mesa Heights', 'Sienna', 'South Triangle', 'Tandang Sora', 'Teachers Village',
            'UP Campus', 'UP Village', 'Vasra', 'Veterans Village', 'West Triangle'
        ],
        '1619': [ // Makati
            'Bangkal', 'Bel-Air', 'Carmona', 'Cembo', 'Comembo', 'Dasmariñas', 'East Rembo',
            'Forbes Park', 'Guadalupe Nuevo', 'Guadalupe Viejo', 'Kasilawan', 'La Paz',
            'Legaspi Village', 'Magallanes', 'Olympia', 'Palanan', 'Pembo', 'Pinagkaisahan',
            'Pio del Pilar', 'Pitogo', 'Poblacion', 'Post Proper Northside', 'Post Proper Southside',
            'Rizal', 'Salcedo Village', 'San Antonio', 'San Isidro', 'San Lorenzo', 'Santa Cruz',
            'Singkamas', 'South Cembo', 'Tejeros', 'Urdaneta', 'Valenzuela', 'West Rembo'
        ],
        '1628': [ // Pasig
            'Bagong Ilog', 'Bagong Katipunan', 'Bambang', 'Buting', 'Caniogan', 'Dela Paz',
            'Kalawaan', 'Kapasigan', 'Kapitolyo', 'Malinao', 'Manggahan', 'Maybunga',
            'Oranbo', 'Palatiw', 'Pinagbuhatan', 'Pineda', 'Rosario', 'Sagad', 'San Antonio',
            'San Joaquin', 'San Jose', 'San Miguel', 'San Nicolas', 'Santa Cruz', 'Santa Lucia',
            'Santa Rosa', 'Santo Tomas', 'Santolan', 'Sumilang', 'Ugong'
        ],
        '1631': [ // Taguig
            'Bagumbayan', 'Bambang', 'Calzada', 'Central Bicutan', 'Central Signal Village',
            'Fort Bonifacio', 'Hagonoy', 'Ibayo-Tipas', 'Katuparan', 'Ligid-Tipas', 'Lower Bicutan',
            'Maharlika Village', 'Napindan', 'New Lower Bicutan', 'North Daang Hari',
            'North Signal Village', 'Palingon', 'Pinagsama', 'San Miguel', 'Santa Ana',
            'South Daang Hari', 'South Signal Village', 'Tanyag', 'Tuktukan', 'Upper Bicutan',
            'Ususan', 'Wawa', 'Western Bicutan'
        ],
        '1621': [ // Mandaluyong
            'Addition Hills', 'Bagong Silang', 'Barangka Drive', 'Barangka Ibaba', 'Barangka Ilaya',
            'Barangka Itaas', 'Buayang Bato', 'Burol', 'Daang Bakal', 'Hagdang Bato Itaas',
            'Hagdang Bato Libis', 'Harapin Ang Bukas', 'Highway Hills', 'Hulo', 'Mabini-J. Rizal',
            'Malamig', 'Mauway', 'Namayan', 'New Zañiga', 'Old Zañiga', 'Pag-asa', 'Plainview',
            'Pleasant Hills', 'Poblacion', 'San Jose', 'Vergara', 'Wack-Wack Greenhills'
        ]
    },

    // Get provinces by region
    getProvinces(regionCode) {
        return this.provinces[regionCode] || [];
    },

    // Get cities by province
    getCities(provinceCode) {
        return this.cities[provinceCode] || [];
    },

    // Get barangays by city
    getBarangays(cityCode) {
        return this.barangays[cityCode] || [];
    },

    // Initialize address dropdowns
    initAddressDropdowns(config = {}) {
        const {
            regionSelect = 'address_region',
            provinceSelect = 'address_province',
            citySelect = 'address_city',
            barangaySelect = 'address_barangay'
        } = config;

        const regionEl = document.getElementById(regionSelect);
        const provinceEl = document.getElementById(provinceSelect);
        const cityEl = document.getElementById(citySelect);
        const barangayEl = document.getElementById(barangaySelect);

        if (!regionEl) return;

        // Populate regions
        this.regions.forEach(region => {
            const option = document.createElement('option');
            option.value = region.code;
            option.textContent = region.name;
            regionEl.appendChild(option);
        });

        // Region change handler
        regionEl.addEventListener('change', () => {
            const regionCode = regionEl.value;
            
            // Clear dependent dropdowns
            if (provinceEl) {
                provinceEl.innerHTML = '<option value="">Select Province</option>';
                const provinces = this.getProvinces(regionCode);
                provinces.forEach(province => {
                    const option = document.createElement('option');
                    option.value = province.code;
                    option.textContent = province.name;
                    provinceEl.appendChild(option);
                });
            }
            
            if (cityEl) cityEl.innerHTML = '<option value="">Select City/Municipality</option>';
            if (barangayEl) barangayEl.innerHTML = '<option value="">Select Barangay</option>';
        });

        // Province change handler
        if (provinceEl) {
            provinceEl.addEventListener('change', () => {
                const provinceCode = provinceEl.value;
                
                if (cityEl) {
                    cityEl.innerHTML = '<option value="">Select City/Municipality</option>';
                    const cities = this.getCities(provinceCode);
                    cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.code;
                        option.textContent = city.name;
                        cityEl.appendChild(option);
                    });
                }
                
                if (barangayEl) barangayEl.innerHTML = '<option value="">Select Barangay</option>';
            });
        }

        // City change handler
        if (cityEl) {
            cityEl.addEventListener('change', () => {
                const cityCode = cityEl.value;
                
                if (barangayEl) {
                    barangayEl.innerHTML = '<option value="">Select Barangay</option>';
                    const barangays = this.getBarangays(cityCode);
                    if (barangays.length > 0) {
                        barangays.forEach(barangay => {
                            const option = document.createElement('option');
                            option.value = barangay;
                            option.textContent = barangay;
                            barangayEl.appendChild(option);
                        });
                    }
                }
            });
        }
    }
};

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Initialize for current address
    PhAddressLookup.initAddressDropdowns({
        regionSelect: 'addressRegion',
        provinceSelect: 'addressProvince',
        citySelect: 'addressCity',
        barangaySelect: 'addressBarangay'
    });

    // Initialize for birth place if exists
    if (document.getElementById('birthRegion')) {
        PhAddressLookup.initAddressDropdowns({
            regionSelect: 'birthRegion',
            provinceSelect: 'birthProvince',
            citySelect: 'birthCity',
            barangaySelect: null
        });
    }
});

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PhAddressLookup;
}
