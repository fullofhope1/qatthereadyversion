-- Database for Qat ERP System

CREATE DATABASE IF NOT EXISTS qat_erp;
USE qat_erp;

-- 1. Qat Types (Types of Jamam, Sudur, Qatal)
CREATE TABLE IF NOT EXISTS qat_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL, -- e.g., 'Jamam Naqwah', 'Sudur'
    description TEXT
);

-- 2. Customers (Debt and Info)
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    total_debt DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Staff (Employees)
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(50), -- e.g., 'Seller', 'Helper'
    base_salary DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Purchases (Ra'wi Logic and Momsi)
CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_date DATE NOT NULL,
    qat_type_id INT, -- If specific type purchased, or NULL for general bundle
    vendor_name VARCHAR(100), -- Ra'wi name
    agreed_price DECIMAL(10, 2) NOT NULL, -- The initial price
    discount DECIMAL(10, 2) DEFAULT 0.00, -- 'Muraja'ah' or discount
    net_cost DECIMAL(10, 2) GENERATED ALWAYS AS (agreed_price - discount) STORED,
    quantity_kg DECIMAL(10, 2), -- Estimated weight bought
    status ENUM('Fresh', 'Momsi') DEFAULT 'Fresh', -- 'Fresh' is new Ra'wi, 'Momsi' is rolled over
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (qat_type_id) REFERENCES qat_types(id)
);

-- 5. Sales (Daily Sales)
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_date DATE DEFAULT (CURRENT_DATE),
    customer_id INT, -- NULL if walk-in customer
    qat_type_id INT,
    qat_status ENUM('Tari', 'Momsi') DEFAULT 'Tari', -- NEW: User requested to specify freshness
    weight_grams DECIMAL(10, 2) NOT NULL,
    weight_kg DECIMAL(10, 3) GENERATED ALWAYS AS (weight_grams / 1000) STORED,
    price DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('Cash', 'Debt', 'Internal Transfer', 'Kuraimi Deposit', 'Jayb Deposit') NOT NULL,
    is_paid BOOLEAN DEFAULT TRUE, -- False if Debt
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (qat_type_id) REFERENCES qat_types(id)
);

-- SEED DATA (Specific Types Requested)
INSERT INTO qat_types (name) VALUES 
('Jamam Naqwah'), 
('Jamam Kalif'), 
('Jamam Samin'), 
('Jamam Qasar'), 
('Sudur Naqwah'), 
('Sudur Adi'), 
('Qatal');

-- 6. Payments (Debt Repayment)
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_date DATE DEFAULT (CURRENT_DATE),
    customer_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- 7. Expenses (Shop & Staff Withdrawals)
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_date DATE DEFAULT (CURRENT_DATE),
    description VARCHAR(255) NOT NULL, -- e.g., 'Bags', 'Petrol', 'Dinner'
    amount DECIMAL(10, 2) NOT NULL,
    category ENUM('Shop', 'Staff', 'Other') NOT NULL,
    staff_id INT, -- If category is Staff
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for frequent searches
CREATE INDEX idx_sales_date ON sales(sale_date);
CREATE INDEX idx_purchases_date ON purchases(purchase_date);
CREATE INDEX idx_customer_name ON customers(name);
