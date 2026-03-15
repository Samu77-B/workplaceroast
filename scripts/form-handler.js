// Contact Form Handler

document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    
    if (contactForm) {
        contactForm.addEventListener('submit', handleFormSubmit);
        
        // Real-time validation
        const inputs = contactForm.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearFieldError);
        });
    }
});

// Handle form submission
function handleFormSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const formMessage = document.getElementById('formMessage');
    
    // Validate all fields
    let isValid = true;
    const requiredFields = ['name', 'email', 'message'];
    
    requiredFields.forEach(fieldName => {
        if (!validateField({ target: form.querySelector(`[name="${fieldName}"]`) })) {
            isValid = false;
        }
    });
    
    // Validate email format
    const email = formData.get('email');
    if (email && !isValidEmail(email)) {
        showFieldError('email', 'Please enter a valid email address');
        isValid = false;
    }
    
    if (!isValid) {
        showFormMessage('Please fix the errors in the form', 'error');
        return;
    }
    
    // Show loading state
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'Sending...';
    
    // Prepare form data
    const formObject = {};
    formData.forEach((value, key) => {
        formObject[key] = value;
    });
    
    // Send email via your chosen method
    sendEmail(formObject)
        .then(() => {
            // Show success message
            showFormMessage('Thank you! Your message has been sent. We\'ll get back to you soon.', 'success');
            
            // Reset form
            form.reset();
            
            // Clear any field errors
            clearAllFieldErrors();
        })
        .catch((error) => {
            console.error('Error sending email:', error);
            showFormMessage('Sorry, there was an error sending your message. Please try again or contact us directly.', 'error');
        })
        .finally(() => {
            // Reset button
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
            
            // Scroll to message
            formMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
}

// Validate individual field
function validateField(e) {
    const field = e.target;
    const fieldName = field.name;
    const fieldValue = field.value.trim();
    
    // Clear previous error
    clearFieldError(e);
    
    // Check if required field is empty
    if (field.hasAttribute('required') && !fieldValue) {
        showFieldError(fieldName, 'This field is required');
        return false;
    }
    
    // Email validation
    if (fieldName === 'email' && fieldValue && !isValidEmail(fieldValue)) {
        showFieldError(fieldName, 'Please enter a valid email address');
        return false;
    }
    
    // Message length validation
    if (fieldName === 'message' && fieldValue.length < 10) {
        showFieldError(fieldName, 'Please enter at least 10 characters');
        return false;
    }
    
    return true;
}

// Show field error
function showFieldError(fieldName, message) {
    const field = document.querySelector(`[name="${fieldName}"]`);
    const errorElement = document.getElementById(`${fieldName}Error`);
    
    if (field) {
        field.style.borderColor = '#dc3545';
    }
    
    if (errorElement) {
        errorElement.textContent = message;
    }
}

// Clear field error
function clearFieldError(e) {
    const field = e.target;
    const fieldName = field.name;
    const errorElement = document.getElementById(`${fieldName}Error`);
    
    if (field) {
        field.style.borderColor = '';
    }
    
    if (errorElement) {
        errorElement.textContent = '';
    }
}

// Clear all field errors
function clearAllFieldErrors() {
    const errorElements = document.querySelectorAll('.form__error');
    errorElements.forEach(error => {
        error.textContent = '';
    });
    
    const fields = document.querySelectorAll('.form__input, .form__textarea, .form__select');
    fields.forEach(field => {
        field.style.borderColor = '';
    });
}

// Show form message
function showFormMessage(message, type) {
    const formMessage = document.getElementById('formMessage');
    
    if (formMessage) {
        formMessage.textContent = message;
        formMessage.className = `form__message ${type}`;
        formMessage.style.display = 'block';
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            formMessage.style.display = 'none';
        }, 5000);
    }
}

// Email validation helper
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Send email function - Using Web3Forms
async function sendEmail(formData) {
    // ============================================
    // Web3Forms Configuration
    // ============================================
    // 1. Get your access key from https://web3forms.com/
    // 2. Replace 'YOUR_ACCESS_KEY' below with your actual access key
    
    const response = await fetch('https://api.web3forms.com/submit', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            access_key: '2edfa525-3092-4690-875d-e41ed318314f', // Replace with your Web3Forms access key
            subject: `New Contact Form Submission from ${formData.name}`,
            name: formData.name,
            email: formData.email,
            business: formData.business || 'Not provided',
            plan: formData.plan || 'Not specified',
            message: formData.message,
            from_name: formData.name,
            from_email: formData.email
        })
    });
    
    const result = await response.json();
    
    if (!response.ok) {
        throw new Error(result.message || 'Failed to send email');
    }
    
    if (!result.success) {
        throw new Error(result.message || 'Failed to send email');
    }
    
    return result;
}

