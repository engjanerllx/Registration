document.addEventListener('DOMContentLoaded', function() {
    // Registration form validation
    if (document.getElementById('registrationForm')) {
        const form = document.getElementById('registrationForm');
        form.addEventListener('submit', function(event) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const mobile = document.getElementById('mobile').value;
            
            const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[\W_]).{8,}$/;
            const mobilePattern = /^0[0-9]{10}$/;

            if (password !== confirmPassword) {
                event.preventDefault();
                alert('Passwords do not match!');
            }
            
            if (!passwordPattern.test(password)) {
                event.preventDefault();
                alert('Password must be at least 8 characters, with at least one uppercase letter, one lowercase letter, one number, and one special character!');
            }

            if (!mobilePattern.test(mobile)) {
                event.preventDefault();
                alert('Mobile number must start with "0" and be 11 digits long!');
            }
        });
    }
});
