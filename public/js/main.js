// public/js/main.js

function initPasswordToggle() {
    document.querySelectorAll('input[type="password"]').forEach(function(input) {
        // Only wrap if it's not already wrapped to avoid double-wrapping
        if (input.parentElement.classList.contains('password-wrapper')) return;

        // Create wrapper
        const wrapper = document.createElement('div');
        wrapper.className = 'password-wrapper';
        wrapper.style.position = 'relative';
        wrapper.style.display = 'flex';
        wrapper.style.alignItems = 'center';
        wrapper.style.width = '100%';
        
        // Insert wrapper before input
        input.parentNode.insertBefore(wrapper, input);
        
        // Move input inside wrapper
        wrapper.appendChild(input);
        
        // Create toggle icon
        const icon = document.createElement('i');
        icon.className = 'fas fa-eye text-muted';
        icon.style.position = 'absolute';
        icon.style.left = '15px'; // Left side since app is RTL
        icon.style.cursor = 'pointer';
        icon.style.zIndex = '10';
        icon.style.top = '50%';
        icon.style.transform = 'translateY(-50%)'; // Vertical centering
        
        // Pad input left side
        input.style.paddingLeft = '40px'; 

        wrapper.appendChild(icon);
        
        icon.addEventListener('click', function(e) {
            e.preventDefault();
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                icon.classList.add('text-primary');
                icon.classList.remove('text-muted');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                icon.classList.remove('text-primary');
                icon.classList.add('text-muted');
            }
        });
    });
}

function initKgConversion() {
    const gramInput = document.getElementById('weight_grams');
    const kgInput = document.getElementById('weight_kg');

    if (gramInput && kgInput && !gramInput.dataset.kgbound) {
        gramInput.dataset.kgbound = "true";
        gramInput.addEventListener('input', function () {
            const grams = parseFloat(this.value);
            if (!isNaN(grams)) {
                const kg = grams / 1000;
                kgInput.value = kg.toFixed(3); // Display 3 decimal places
            } else {
                kgInput.value = '';
            }
        });
    }
}

// Run immediately (since script is at bottom of body)
initPasswordToggle();
initKgConversion();

// Backup for dynamic loads
document.addEventListener('DOMContentLoaded', function () {
    initPasswordToggle();
    initKgConversion();
});
