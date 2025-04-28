// File: script.js

document.addEventListener('DOMContentLoaded', function() {
    // Registration form validation
    if (document.getElementById('registrationForm')) {
        const form = document.getElementById('registrationForm');
        form.addEventListener('submit', function(event) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                event.preventDefault();
                alert('Passwords do not match!');
            }
            
            if (password.length < 6) {
                event.preventDefault();
                alert('Password must be at least 6 characters long!');
            }
        });
    }
});