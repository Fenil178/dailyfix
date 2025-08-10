document.addEventListener('DOMContentLoaded', function() {

    // --- START: IMAGE PREVIEW LOGIC ---

    const profile_imageInput = document.getElementById('profile_image');
    const dropArea = document.querySelector('.file-drop-area');
    const fileMsg = document.querySelector('.file-msg');
    const previewContainer = document.getElementById('filePreviewContainer');

    // This function handles reading the file and creating the preview
    function showPreview(file) {
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();

            reader.onload = function(e) {
                previewContainer.innerHTML = ''; // Clear any previous preview

                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('file-preview'); // Assumes this class exists in your CSS

                previewContainer.appendChild(img);
                previewContainer.style.display = 'block'; // Show the preview container
                if (fileMsg) {
                    fileMsg.style.display = 'none'; // Hide the "Drag & drop" message
                }
            };

            reader.readAsDataURL(file);
        }
    }

    // Event listener for file selection via the "click" dialog
    if (profile_imageInput) {
        profile_imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                showPreview(this.files[0]);
            }
        });
    }

    // Event listeners for drag and drop functionality
    if (dropArea) {
        // Prevent default browser behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });

        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, () => dropArea.classList.add('is-active'), false);
        });

        // Un-highlight drop area when item leaves
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, () => dropArea.classList.remove('is-active'), false);
        });

        // Handle the dropped file
        dropArea.addEventListener('drop', e => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files[0]) {
                profile_imageInput.files = files; // Important: assign files to the input
                showPreview(files[0]);
            }
        }, false);
    }

    // --- END: IMAGE PREVIEW LOGIC ---


    // Password visibility toggle (Your original code)
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    if (togglePassword) {
        togglePassword.addEventListener('click', function(e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    }

    // AJAX form submission (Your original code)
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission

            const form = event.target;
            const formData = new FormData(form);
            const alertPlaceholder = document.getElementById('signup-alert-placeholder');
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;

            // Disable button and show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Signing Up...`;

            fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    let alertClass = data.status === 'success' ? 'alert-success' : 'alert-danger';
                    alertPlaceholder.innerHTML = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                                                ${data.message}
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                              </div>`;

                    if (data.status === 'success' && data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 2000); // Wait 2 seconds before redirecting
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
                    // Re-enable button and restore original text
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                });
        });
    }
});