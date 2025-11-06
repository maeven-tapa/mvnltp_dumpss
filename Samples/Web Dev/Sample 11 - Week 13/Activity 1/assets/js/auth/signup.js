const togglePassword = document.getElementById('togglePassword');
const toggleConfirm = document.getElementById('toggleConfirm');
const pwd = document.getElementById('password');
const cpwd = document.getElementById('confirmPassword');

togglePassword.addEventListener('click', () => {
  const type = pwd.type === 'password' ? 'text' : 'password';
  pwd.type = type;
  togglePassword.src = type === 'password'
    ? '../assets/images/eye-open.png'
    : '../assets/images/eye-close.png';
});

toggleConfirm.addEventListener('click', () => {
  const type = cpwd.type === 'password' ? 'text' : 'password';
  cpwd.type = type;
  toggleConfirm.src = type === 'password'
    ? '../assets/images/eye-open.png'
    : '../assets/images/eye-close.png';
});

// Basic client-side validation for contact number and password confirmation
const signupForm = document.getElementById('signupForm');
const contactInput = document.getElementById('contact');

if (signupForm) {
  signupForm.addEventListener('submit', (e) => {
    // Confirm password match
    if (pwd.value !== cpwd.value) {
      e.preventDefault();
      alert('Passwords do not match.');
      return false;
    }

    // Validate contact number (digits only, 7-15 characters)
    if (contactInput) {
      const contact = contactInput.value.trim();
      const digitsOnly = /^\d{7,15}$/.test(contact);
      if (!digitsOnly) {
        e.preventDefault();
        alert('Please enter a valid contact number (7-15 digits).');
        return false;
      }
    }

    return true;
  });
}
