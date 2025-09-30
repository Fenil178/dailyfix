document.addEventListener('DOMContentLoaded', function() {
    // --- DOM ELEMENT REFERENCES ---
    const steps = document.querySelectorAll('.step');
    const roleCards = document.querySelectorAll('.role-card');
    const backBtns = document.querySelectorAll('.back-btn');
    const nextBtns = document.querySelectorAll('.next-btn');
    
    const roleHiddenInput = document.getElementById('role-hidden-input');
    const workerKeyHiddenInput = document.getElementById('worker-key-hidden-input');
    const roleTitle = document.getElementById('role-title');
    const registerBackBtn = document.getElementById('register-back-btn');
    const locationBackBtn = document.getElementById('location-back-btn');

    const verifyKeyBtn = document.getElementById('verifyKeyBtn');
    const signupForm = document.getElementById('signupForm');
    const alertPlaceholder = document.getElementById('signup-alert-placeholder');
    
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const profileImageInput = document.getElementById('profile_image');
    const dropArea = document.querySelector('.file-drop-area');
    const fileMsg = document.querySelector('.file-msg');
    const previewContainer = document.getElementById('filePreviewContainer');
    
    const subServicesContainer = document.getElementById('sub-services-container');
    const subServiceItemsContainer = document.getElementById('sub-service-items-container');
    const accountDetailsNextBtn = document.getElementById('account-details-next-btn');

    // --- Location-related elements ---
    const mapContainer = document.getElementById('map');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    let map = null;
    let marker = null;

    const stateDropdown = document.getElementById('state');
    const cityDropdown = document.getElementById('city');
    const pincodeInput = document.getElementById('pincode');
    const addressLine1Input = document.getElementById('address_line1');
    const addressLine2Input = document.getElementById('address_line2');
    const pincodeSpinner = document.getElementById('pincode-spinner');

    // Step Indicator References
    const workerIndicator = document.getElementById('worker-indicator');
    const customerIndicator = document.getElementById('customer-indicator');
    
    // --- HELPER FUNCTIONS ---
    function showStep(stepId) {
        steps.forEach(step => step.classList.remove('active'));
        const activeStep = document.getElementById(stepId);
        if (activeStep) activeStep.classList.add('active');
        
        if (stepId === 'step-role') {
            workerIndicator.style.display = 'none';
            customerIndicator.style.display = 'none';
        } else {
            const role = roleHiddenInput.value;
            workerIndicator.style.display = role === 'worker' ? 'flex' : 'none';
            customerIndicator.style.display = role === 'customer' ? 'flex' : 'none';
        }
        
        if (stepId === 'step-location' && !map) initializeMap();

        updateStepIndicator(stepId);
        window.scrollTo(0, 0); 
    }
    
    function validateStep(stepId) {
        const step = document.getElementById(stepId);
        if (!step) return false;

        const inputs = step.querySelectorAll('input[required], textarea[required], select[required]');
        let isValid = true;
        
        for (const input of inputs) {
            if (!input.value.trim()) {
                isValid = false;
                break;
            }
            if (input.type === 'email') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(input.value)) {
                    isValid = false;
                    break;
                }
            }
        }
        if (!isValid) {
            showAlert('Please fill out all required fields in this step before proceeding.');
        }
        return isValid;
    }

    // --- FIX APPLIED FOR CHECKMARK BUG ---
    function updateStepIndicator(currentStepId) {
        const role = roleHiddenInput.value;
        const indicatorWrapper = role === 'worker' ? workerIndicator : customerIndicator;
        if (!indicatorWrapper || indicatorWrapper.style.display === 'none') return;

        const indicatorItems = indicatorWrapper.querySelectorAll('.step-indicator-item');
        let currentStepIndex = -1;

        indicatorItems.forEach((item, index) => {
            if (item.getAttribute('data-step-target') === currentStepId) {
                currentStepIndex = index;
            }
        });

        if (currentStepIndex !== -1) {
            indicatorItems.forEach((item, index) => {
                const span = item.querySelector('span');
                if (!span) return;

                if (index < currentStepIndex) {
                    // Completed Step
                    item.classList.add('completed');
                    item.classList.remove('active');
                    span.innerHTML = '&#x2713;'; // Directly set checkmark HTML entity
                } else if (index === currentStepIndex) {
                    // Active Step
                    item.classList.add('active');
                    item.classList.remove('completed');
                    span.textContent = index + 1;
                } else {
                    // Future Step
                    item.classList.remove('active', 'completed');
                    span.textContent = index + 1;
                }
            });
        }
    }

    function showAlert(message, type = 'danger') {
        alertPlaceholder.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                                        ${message}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                      </div>`;
    }
    
    async function reverseGeocode(lat, lng) {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
            if (!response.ok) throw new Error('Failed to fetch address.');
            
            const data = await response.json();
            if (data && data.address) {
                const addr = data.address;
                addressLine1Input.value = addr.house_number || '';
                addressLine2Input.value = [addr.road, addr.neighbourhood, addr.suburb].filter(Boolean).join(', ');
                pincodeInput.value = addr.postcode || '';
                if (addr.state) {
                    stateDropdown.value = addr.state;
                    stateDropdown.dispatchEvent(new Event('change'));
                }
                setTimeout(() => {
                    const city = addr.city || addr.town || addr.village || addr.city_district;
                    if (city) cityDropdown.value = city;
                }, 100);
            }
        } catch (error) {
            console.error("Reverse geocoding error:", error);
        }
    }

    function initializeMap() {
        const initialCoords = [22.3039, 70.8022]; // Default to Gujarat
        map = L.map(mapContainer).setView(initialCoords, 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        marker = L.marker(initialCoords, { draggable: true }).addTo(map);
        latitudeInput.value = initialCoords[0];
        longitudeInput.value = initialCoords[1];
        const updateMarkerAndAddress = (latlng) => {
            latitudeInput.value = latlng.lat.toFixed(8);
            longitudeInput.value = latlng.lng.toFixed(8);
            reverseGeocode(latlng.lat, latlng.lng);
        };
        marker.on('dragend', (event) => updateMarkerAndAddress(marker.getLatLng()));
        map.on('click', (e) => {
            marker.setLatLng(e.latlng);
            updateMarkerAndAddress(e.latlng);
        });
        setTimeout(() => map.invalidateSize(), 200);
    }

    roleCards.forEach(card => {
        card.addEventListener('click', () => {
            const role = card.dataset.role;
            roleHiddenInput.value = role;
            roleTitle.textContent = role.charAt(0).toUpperCase() + role.slice(1);
            if (role === 'customer') {
                accountDetailsNextBtn.dataset.target = 'step-location';
                registerBackBtn.dataset.target = 'step-role';
                locationBackBtn.dataset.target = 'step-register-part1';
                showStep('step-register-part1');
            } else {
                accountDetailsNextBtn.dataset.target = 'step-register-part2';
                registerBackBtn.dataset.target = 'step-key';
                locationBackBtn.dataset.target = 'step-sub-service-items';
                showStep('step-key');
            }
        });
    });

    backBtns.forEach(btn => btn.addEventListener('click', () => showStep(btn.dataset.target)));
    
    nextBtns.forEach(btn => btn.addEventListener('click', () => {
        const currentStep = btn.closest('.step').id;
        if (!validateStep(currentStep)) return;
        const targetStep = btn.dataset.target;
        if (targetStep === 'step-sub-services') populateSubServices();
        if (targetStep === 'step-sub-service-items') populateSubServiceItems();
        showStep(targetStep);
    }));
    
     function populateSubServices() {
        subServicesContainer.innerHTML = '';
        const selectedMainServices = Array.from(document.querySelectorAll('input[name="main_services[]"]:checked')).map(cb => cb.value);
        let content = '';
        for (const serviceName in groupedSubServices) {
            const subServices = groupedSubServices[serviceName];
            const mainServiceId = subServices[0].main_service_id;
            if (selectedMainServices.includes(String(mainServiceId))) {
                let categoryHtml = `<div class="service-category-group"><h4><i class="fas fa-tools"></i> ${serviceName}</h4><div class="services-checkbox-grid">`;
                subServices.forEach(service => {
                    categoryHtml += `<div class="checkbox-item"><input type="checkbox" id="service-${service.id}" name="services[]" value="${service.id}"><label for="service-${service.id}">${service.name}</label></div>`;
                });
                categoryHtml += `</div></div>`;
                content += categoryHtml;
            }
        }
        subServicesContainer.innerHTML = content || '<p>Please go back and select a main service category first.</p>';
    }

    function populateSubServiceItems() {
        subServiceItemsContainer.innerHTML = '';
        const selectedSubServices = Array.from(document.querySelectorAll('#sub-services-container input[type="checkbox"]:checked')).map(cb => parseInt(cb.value));
        if (selectedSubServices.length === 0) {
            subServiceItemsContainer.innerHTML = '<p>Please go back and select at least one sub-service.</p>';
            return;
        }
        let content = '';
        selectedSubServices.forEach(subServiceId => {
            const items = subServiceItems[subServiceId];
            if (items && items.length > 0) {
                const subService = Object.values(groupedSubServices).flat().find(s => s.id === subServiceId);
                let categoryHtml = `<div class="service-category-group"><h4><i class="${subService.icon}"></i> ${subService.name}</h4><div class="services-checkbox-grid">`;
                items.forEach(item => {
                    categoryHtml += `<div class="checkbox-item"><input type="checkbox" id="item-${item.id}" name="sub_service_items[]" value="${item.id}"><label for="item-${item.id}">${item.name}</label></div>`;
                });
                categoryHtml += `</div></div>`;
                content += categoryHtml;
            }
        });
        subServiceItemsContainer.innerHTML = content || '<p>No service items found for the selected sub-services.</p>';
    }

    if (verifyKeyBtn) {
        verifyKeyBtn.addEventListener('click', function() {
            const keyInput = document.getElementById('worker_key_input');
            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Verifying...`;
            fetch('/dailyfix/api/verify_worker_key.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `worker_key=${encodeURIComponent(keyInput.value)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    workerKeyHiddenInput.value = keyInput.value.trim().toUpperCase().replace(/-/g, '');
                    showStep('step-register-part1');
                } else {
                    showAlert(data.message);
                }
            })
            .catch(() => showAlert('An error occurred. Please try again.'))
            .finally(() => {
                this.disabled = false;
                this.innerHTML = originalText;
            });
        });
    }

    if (stateDropdown && cityDropdown) {
        stateDropdown.addEventListener('change', function() {
            const selectedState = this.value;
            cityDropdown.innerHTML = '<option value="" disabled selected>Select City</option>';
            if (selectedState && citiesByState[selectedState]) {
                cityDropdown.disabled = false;
                citiesByState[selectedState].forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    cityDropdown.appendChild(option);
                });
            } else {
                cityDropdown.innerHTML = '<option value="" disabled selected>Select State First</option>';
                cityDropdown.disabled = true;
            }
        });
    }

    let pincodeTimeout;
    if (pincodeInput) {
        pincodeInput.addEventListener('keyup', function() {
            clearTimeout(pincodeTimeout);
            const pincode = this.value;
            if (pincode.length === 6) {
                pincodeSpinner.style.display = 'block';
                pincodeTimeout = setTimeout(() => {
                    fetch(`https://api.postalpincode.in/pincode/${pincode}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data[0].Status === 'Success') {
                                const postOffice = data[0].PostOffice[0];
                                const state = postOffice.State;
                                const city = postOffice.District;
                                stateDropdown.value = state;
                                stateDropdown.dispatchEvent(new Event('change'));
                                setTimeout(() => { cityDropdown.value = city; }, 100);
                            }
                        })
                        .catch(error => console.error('Pincode API error:', error))
                        .finally(() => pincodeSpinner.style.display = 'none');
                }, 500);
            }
        });
    }

    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!validateStep('step-location')) return;
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Creating Account...`;
            fetch('/dailyfix/signup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.redirect) {
                    showAlert(data.message, 'success');
                    setTimeout(() => { window.location.href = data.redirect; }, 1500);
                } else {
                    showAlert(data.message || 'An unknown error occurred.');
                    window.scrollTo(0, 0);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An unexpected network error occurred. Please try again.');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }

    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    }

    function showPreview(file) {
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => {
                previewContainer.innerHTML = `<img src="${e.target.result}" class="file-preview">`;
                previewContainer.style.display = 'block';
                fileMsg.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    }

    if (profileImageInput) {
        profileImageInput.addEventListener('change', e => showPreview(e.target.files[0]));
    }

    if (dropArea) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });
        ['dragenter', 'dragover'].forEach(eventName => dropArea.addEventListener(eventName, () => dropArea.classList.add('is-active')));
        ['dragleave', 'drop'].forEach(eventName => dropArea.addEventListener(eventName, () => dropArea.classList.remove('is-active')));
        dropArea.addEventListener('drop', e => {
            const files = e.dataTransfer.files;
            if (files.length) {
                profileImageInput.files = files;
                showPreview(files[0]);
            }
        });
    }
});