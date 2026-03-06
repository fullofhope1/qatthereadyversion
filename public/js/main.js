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
});
