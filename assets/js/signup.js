document.addEventListener('DOMContentLoaded', function() {

    // --- DOM ELEMENT REFERENCES ---
    const step1 = document.getElementById('step-1');
    const step2 = document.getElementById('step-2');
    const roleSelect = document.getElementById('roleSelect');
    const nextButton = document.getElementById('nextButton');
    const signupForm = document.getElementById('signupForm');
    const alertPlaceholder = document.getElementById('signup-alert-placeholder');
    const password = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    
    const profile_imageInput = document.getElementById('profile_image');
    const dropArea = document.querySelector('.file-drop-area');
    const fileMsg = document.querySelector('.file-msg');
    const previewContainer = document.getElementById('filePreviewContainer');

    // --- IMAGE PREVIEW LOGIC ---
    function showPreview(file) {
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContainer.innerHTML = '';
                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('file-preview');
                previewContainer.appendChild(img);
                previewContainer.style.display = 'block';
                if (fileMsg) {
                    fileMsg.style.display = 'none';
                }
            };
            reader.readAsDataURL(file);
        }
    }

    if (profile_imageInput) {
        profile_imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                showPreview(this.files[0]);
            }
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
                profile_imageInput.files = files;
                showPreview(files[0]);
            }
        }, false);
    }
    // --- END: IMAGE PREVIEW LOGIC ---

    // --- PASSWORD VISIBILITY TOGGLE ---
    if (togglePassword) {
        togglePassword.addEventListener('click', function(e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    }

    // ... (rest of the signup.js code)

    // --- MULTI-STEP FORM LOGIC ---
    function updateButtonText() {
        const role = roleSelect.value;
        if (role === 'worker') {
            nextButton.textContent = 'Next';
        } else {
            nextButton.textContent = 'Create Account';
        }
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', updateButtonText);
        updateButtonText(); // Set initial state on page load
    }

    if (nextButton) {
        nextButton.addEventListener('click', function() {
            const fullName = signupForm.querySelector('[name="full_name"]').value;
            const email = signupForm.querySelector('[name="email"]').value;
            const passwordValue = signupForm.querySelector('[name="password"]').value;
            const role = roleSelect.value;
    
            // Basic client-side validation for Step 1
            if (!fullName || !email || !passwordValue || !role) {
                alertPlaceholder.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                Please fill all required fields.
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                              </div>`;
                return;
            }
            if (!/^\S+@\S+\.\S+$/.test(email)) {
                 alertPlaceholder.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                Invalid email format.
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                              </div>`;
                return;
            }
    
            // If role is 'customer', trigger the final form submission event
            if (role === 'customer') {
                signupForm.dispatchEvent(new Event('submit'));
            } else {
                // For 'worker' role, proceed to Step 2
                step1.style.display = 'none';
                step2.style.display = 'block';
            }
        });
    }
    
    // --- FINAL FORM SUBMISSION (Handles both roles) ---
    if (signupForm) {
        signupForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default submission initially
    
            const form = event.target;
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            
            submitButton.disabled = true;
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating Account...`;
    
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if the response is valid JSON before parsing
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else {
                    // Handle non-JSON responses (e.g., direct redirects)
                    throw new Error("Received non-JSON response from server.");
                }
            })
            .then(data => {
                let alertClass = data.status === 'success' ? 'alert-success' : 'alert-danger';
                alertPlaceholder.innerHTML = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                                                ${data.message}
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                              </div>`;
    
                if (data.status === 'success' && data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alertPlaceholder.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                An unexpected error occurred. Please try again.
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                              </div>`;
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            });
        });
    }
});