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
        const iconContainer = document.createElement('span');
        iconContainer.style.position = 'absolute';
        iconContainer.style.left = '15px'; // Left side since app is RTL
        iconContainer.style.cursor = 'pointer';
        iconContainer.style.zIndex = '10';
        iconContainer.style.top = '50%';
        iconContainer.style.transform = 'translateY(-50%)'; // Vertical centering
        iconContainer.style.color = '#6c757d'; // text-muted color
        
        const svgEye = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 576 512" fill="currentColor"><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 92.9-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.8-35.7-46.1-87.7-92.9-131.1C433.5 68.8 368.8 32 288 32zM128 256a160 160 0 1 1 320 0 160 160 0 1 1 -320 0zm160-80a80 80 0 1 0 0 160 80 80 0 1 0 0-160z"/></svg>`;
        const svgEyeSlash = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 640 512" fill="currentColor"><path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L500.4 361.3C544.1 319.4 577.6 268 594 245.5c3-4.1 3-9 0-13.1c-16.4-22.5-49.9-73.9-93.6-115.8C448.9 68.8 376.6 32 288 32c-47.5 0-90.8 13.6-128.9 36l-120.3-63zm46.5 125l24.4 19.1c34.5-23.7 75.3-37.2 118.3-37.2c65.2 0 125 24.5 168.4 62.4C440.4 213.9 470 255.8 483 283.6l44.3 34.7c-5.7 7.7-12.7 17-21.1 27.6L443 296.6c2.1-13.1 3-26.6 3-40.6c0-79.5-64.5-144-144-144c-25.9 0-50 6.9-71.1 18.7L181.8 116.5c15.6-10.7 32.5-19.8 50.4-27.1L85.3 130.1zM58.3 189L134 248.3C130.6 257.6 128 266.6 128 272c0 88.4 71.6 160 160 160c18.5 0 36.3-3.1 52.8-8.9l69.7 54.6C374 492.3 332.2 496 288 496c-88.6 0-160.9-36.8-212.4-80.6C28.6 368 2.3 303.4 1.5 292.5c-2.3-4.1-2.3-9 0-13.1C10.7 261.3 29.5 218 58.3 189zM304 384c-61.9 0-112-50.1-112-112c0-8 1-15.8 2.6-23.3l128 100.3c3.5 1.7 7.1 2.9 10.9 3.5c-9.1 19.9-29.5 31.5-29.5 31.5z"/></svg>`;

        iconContainer.innerHTML = svgEye;
        
        // Pad input left side
        input.style.paddingLeft = '40px'; 

        wrapper.appendChild(iconContainer);
        
        iconContainer.addEventListener('click', function(e) {
            e.preventDefault();
            if (input.type === 'password') {
                input.type = 'text';
                iconContainer.innerHTML = svgEyeSlash;
                iconContainer.style.color = '#0d6efd'; // text-primary
            } else {
                input.type = 'password';
                iconContainer.innerHTML = svgEye;
                iconContainer.style.color = '#6c757d'; // text-muted
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

// Create a MutationObserver to watch for any dynamically added or re-rendered password inputs (Ensures eye icon never disappears)
const observer = new MutationObserver(function(mutations) {
    let shouldInit = false;
    mutations.forEach(function(mutation) {
        if (mutation.addedNodes.length > 0) {
            shouldInit = true;
        }
    });
    if (shouldInit) {
        initPasswordToggle();
    }
});

// Start observing the body for changes in the DOM
if (document.body) {
    observer.observe(document.body, { childList: true, subtree: true });
}
