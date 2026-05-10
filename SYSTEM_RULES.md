# QAT ERP SYSTEM RULES

This document serves as a persistent memory for the AI assistant to ensure core business logic and architectural patterns are preserved during updates.

## 1. Data Isolation
*   **Admins vs. Super Admins**: Expenses and Deposits are isolated by `created_by`. 
*   Admins must ONLY see their own records.
*   Super Admins must ONLY see their own records.
*   *Note*: Some dashboard metrics (Sales, Debts, Purchases) remain at the "Role Group" level (e.g., Admin sees all Admin-group data) to support team collaboration, unless specified otherwise.

## 2. Operational Day Boundary (The 6:20 AM Rule)
*   The business day does NOT end at midnight. It ends at **6:20 AM**.
*   Any records (Sales, Expenses, etc.) created between 12:00 AM and 05:59 AM belong to the **Previous Calendar Day**.
*   Use the `getOperationalDate()` helper function from `config/db.php` for all date-related defaults.
*   Auto-closing logic in `includes/auto_close.php` also respects this boundary.

## 3. Optional Fields
*   **Phone Numbers**: Phone numbers for Customers and Providers are **OPTIONAL**. 
*   Do NOT enforce `required` attributes or non-empty validation in backend services (`CustomerService`, `ProviderService`).
*   Validation for format (digits only) should only occur if the field is NOT empty.

## 4. Sales UI Logic
*   **Qat Types Visibility**: Qat types should only be visible in the Sales pages (`sales.php`, `sales_leftovers_1.php`, `sales_leftovers_2.php`) if there is active, non-zero stock available for that type on the current operational date.
*   If a type has no shipment or its stock is depleted, it should be hidden from the selection grid.

## 5. Contact Information
*   **Location**: إب - شارع الدائري سوق السلام (Ibb - Al-Dayri St - Al-Salam Market).
*   **WhatsApp**: +967 774456261
*   **Call**: +967 775065459
*   **Social Media**: Facebook and Instagram only. (Snapchat and YouTube removed).

## 6. Development Workflow
*   **NO GITHUB UPLOADS**: Do not upload code to GitHub without explicit user authorization.
*   **System Integrity**: Always check `SYSTEM_RULES.md` before making changes to ensure no regressions occur.

## 7. Roles & Permissions (Specific Restrictions)
*   **Receiving/Verifier Roles**: Users with these sub-roles (Super Admin group) are strictly for operational reception.
*   They are **BLOCKED** from seeing the dashboard.php (لوحة التحكم) to prevent unauthorized access to overall financial summaries.
*   Their home link is redirected to settings.php or index.php.
*   Ensure the no_dash array in includes/header.php includes these roles.
