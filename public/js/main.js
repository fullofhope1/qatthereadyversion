// public/js/main.js

document.addEventListener('DOMContentLoaded', function () {
    // ----------------------------------------------------
    // Sales Interface: Grams to KG Conversion
    // ----------------------------------------------------
    const gramInput = document.getElementById('weight_grams');
    const kgInput = document.getElementById('weight_kg');

    if (gramInput && kgInput) {
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

    // ----------------------------------------------------
    // Auto-Select 'Debt' if Customer is selected? 
    // (Optional enhancement logic)
    // ----------------------------------------------------
    
    // ----------------------------------------------------
    // Password Toggle Visibility
    // ----------------------------------------------------
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
        
        // Pad input left side
        input.style.paddingLeft = '40px'; 

        wrapper.appendChild(icon);
        
        icon.addEventListener('click', function() {
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
});
