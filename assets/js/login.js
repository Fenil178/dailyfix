document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    if (togglePassword && password) {
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // AJAX form submission
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);
            const alertPlaceholder = document.getElementById('login-alert-placeholder');
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;

            submitButton.disabled = true;
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Signing In...`;

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                let alertClass = data.status === 'success' ? 'alert-success' : 'alert-danger';
                let iconClass = data.status === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
                alertPlaceholder.innerHTML = `<div class="alert ${alertClass}">
                                                <i class="${iconClass}"></i>
                                                <span>${data.message}</span>
                                                <button type="button" class="btn-close" onclick="this.parentElement.remove()">×</button>
                                              </div>`;

                if (data.status === 'success' && data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alertPlaceholder.innerHTML = `<div class="alert alert-danger">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <span>An unexpected error occurred. Please try again.</span>
                                                <button type="button" class="btn-close" onclick="this.parentElement.remove()">×</button>
                                              </div>`;
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            });
        });
    }
});