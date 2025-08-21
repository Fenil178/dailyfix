document.addEventListener('DOMContentLoaded', function() {
    // --- DOM ELEMENT REFERENCES ---
    const steps = document.querySelectorAll('.step');
    const roleCards = document.querySelectorAll('.role-card');
    const backBtns = document.querySelectorAll('.back-btn');
    const nextBtn = document.querySelector('.next-btn');
    
    const roleHiddenInput = document.getElementById('role-hidden-input');
    const workerKeyHiddenInput = document.getElementById('worker-key-hidden-input');
    const workerFields = document.getElementById('worker-fields');
    const roleTitle = document.getElementById('role-title');
    const registerBackBtn = document.getElementById('register-back-btn');

    const keyForm = document.getElementById('keyForm');
    const signupForm = document.getElementById('signupForm');
    const alertPlaceholder = document.getElementById('signup-alert-placeholder');
    
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const profileImageInput = document.getElementById('profile_image');
    const dropArea = document.querySelector('.file-drop-area');
    const fileMsg = document.querySelector('.file-msg');
    const previewContainer = document.getElementById('filePreviewContainer');
    
    const part1Form = document.getElementById('part1-form');
    const nextBtnElement = document.querySelector('.next-btn');

    // --- HELPER FUNCTIONS ---
    function showStep(stepId) {
        steps.forEach(step => {
            step.classList.remove('active');
        });
        const activeStep = document.getElementById(stepId);
        if (activeStep) {
            activeStep.classList.add('active');
        }
    }

    function showAlert(message, type = 'danger') {
        alertPlaceholder.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                                        ${message}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                      </div>`;
    }

    // --- STEP 1: ROLE SELECTION ---
    roleCards.forEach(card => {
        card.addEventListener('click', () => {
            const role = card.dataset.role;
            roleHiddenInput.value = role;
            if (roleTitle) {
                roleTitle.textContent = role.charAt(0).toUpperCase() + role.slice(1);
            }

            if (role === 'customer') {
                if (nextBtnElement) {
                    nextBtnElement.textContent = "Create Account";
                }
                showStep('step-register-part1');
            } else { // worker
                if (nextBtnElement) {
                    nextBtnElement.textContent = "Next";
                }
                showStep('step-key');
            }
        });
    });

    // --- BACK BUTTONS ---
    backBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetStep = btn.dataset.target;
            showStep(targetStep);
        });
    });

    // --- NEXT BUTTON (PART 1 VALIDATION & TRANSITION) ---
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            // Validate Part 1 fields
            const fullName = document.getElementById('full_name');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const phone = document.getElementById('phone');

            if (!fullName.checkValidity() || !email.checkValidity() || !password.checkValidity() || !phone.checkValidity()) {
                showAlert('Please fill in all required fields.');
                return;
            }

            // If customer, submit the form directly
            if (roleHiddenInput.value === 'customer') {
                submitForm();
            } else {
                // If worker, proceed to part 2
                showStep('step-register-part2');
            }
        });
    }

    // --- STEP 2: KEY VERIFICATION ---
    if (keyForm) {
        keyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const keyInput = document.getElementById('worker_key_input');
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verifying...`;

            fetch('/dailyfix/api/verify_worker_key.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `worker_key=${encodeURIComponent(keyInput.value)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    workerKeyHiddenInput.value = keyInput.value;
                    showStep('step-register-part1');
                } else {
                    showAlert(data.message);
                }
            })
            .catch(err => showAlert('An error occurred. Please try again.'))
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }

    // --- FINAL REGISTRATION SUBMISSION ---
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm();
        });
    }

    // Consolidated submission function
    function submitForm() {
        const formData = new FormData();
        
        // Append data from the first part of the form
        formData.append('full_name', document.getElementById('full_name').value);
        formData.append('email', document.getElementById('email').value);
        formData.append('password', document.getElementById('password').value);
        formData.append('phone', document.getElementById('phone').value);
        if (profileImageInput.files.length > 0) {
            formData.append('profile_image', profileImageInput.files[0]);
        }
        
        // Append hidden role and key
        formData.append('role', roleHiddenInput.value);
        formData.append('worker_key', workerKeyHiddenInput.value);
        
        // Append data from the second part of the form (worker only)
        if (roleHiddenInput.value === 'worker') {
            formData.append('bio', document.getElementById('bio').value);
            formData.append('experience_years', document.getElementById('experience_years').value);
            formData.append('hourly_rate', document.getElementById('hourly_rate').value);
            document.querySelectorAll('input[name="services[]"]:checked').forEach(checkbox => {
                formData.append('services[]', checkbox.value);
            });
        }
        
        const submitBtn = signupForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating Account...`;
        
        fetch('/dailyfix/signup.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.redirect) {
                showAlert(data.message, 'success');
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            } else {
                showAlert(data.message || 'An unknown error occurred.');
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
    }

    // --- UTILITIES: IMAGE PREVIEW & PASSWORD TOGGLE ---
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    }

    function showPreview(file) {
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (previewContainer) {
                    previewContainer.innerHTML = `<img src="${e.target.result}" class="file-preview" alt="Image Preview">`;
                    previewContainer.style.display = 'block';
                }
                if (fileMsg) fileMsg.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    }

    if (profileImageInput) {
        profileImageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) showPreview(this.files[0]);
        });
    }

    if (dropArea) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, () => dropArea.classList.add('is-active'), false);
        });
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, () => dropArea.classList.remove('is-active'), false);
        });
        dropArea.addEventListener('drop', e => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files[0]) {
                profileImageInput.files = files;
                showPreview(files[0]);
            }
        }, false);
    }
});