-- Expanded Ferosa Garden & Landscaping Database Schema
CREATE DATABASE IF NOT EXISTS ferosa_laravel;
USE ferosa_laravel;

-- 1. USERS MODULE
CREATE TABLE IF NOT EXISTS users (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    account_type VARCHAR(50) DEFAULT 'personal',
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    avatar_url VARCHAR(2048),
    remember_token VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS feedbacks (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    product_id BIGINT(20) UNSIGNED NULL,
    service_id BIGINT(20) UNSIGNED NULL,
    rating TINYINT(3) UNSIGNED NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS conversations (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT(20) UNSIGNED NOT NULL,
    subject VARCHAR(255),
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS messages (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT(20) UNSIGNED NOT NULL,
    sender_id BIGINT(20) UNSIGNED NOT NULL,
    body TEXT,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. SCHEDULING & SERVICES MODULE
CREATE TABLE IF NOT EXISTS service_types (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    default_duration_minutes INT(10) UNSIGNED NOT NULL,
    default_fee DECIMAL(10, 2) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS schedule_templates (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    day_of_week TINYINT(3) UNSIGNED NOT NULL COMMENT '0=Sunday, 6=Saturday',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_duration_minutes INT(10) UNSIGNED NOT NULL,
    max_capacity INT(10) UNSIGNED DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS time_slots (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_template_id BIGINT(20) UNSIGNED NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_capacity INT(10) UNSIGNED DEFAULT 1,
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_template_id) REFERENCES schedule_templates(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS appointments (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(20) UNSIGNED NULL,
    walk_in_name VARCHAR(255),
    walk_in_phone VARCHAR(20),
    time_slot_id BIGINT(20) UNSIGNED NOT NULL,
    service_type_id BIGINT(20) UNSIGNED NOT NULL,
    fee DECIMAL(10, 2) NOT NULL,
    status ENUM('scheduled', 'confirmed', 'completed', 'cancelled') DEFAULT 'scheduled',
    notes TEXT,
    staff_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (service_type_id) REFERENCES service_types(id) ON DELETE CASCADE
);

-- 3. PRODUCTS & INVENTORY MODULE
CREATE TABLE IF NOT EXISTS product_categories (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT(20) UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    sku VARCHAR(100) UNIQUE,
    brand VARCHAR(255),
    material_type VARCHAR(255) COMMENT 'For landscaping materials/tools',
    size_dimensions VARCHAR(255) COMMENT 'For plants, materials',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS product_images (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT(20) UNSIGNED NOT NULL,
    image_url VARCHAR(2048) NOT NULL,
    sort_order INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS inventory (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT(20) UNSIGNED NOT NULL,
    quantity INT(11) NOT NULL DEFAULT 0,
    reorder_level INT(11) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 4. SALES & ORDERS MODULE
CREATE TABLE IF NOT EXISTS orders (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(20) UNSIGNED NULL,
    walk_in_name VARCHAR(255),
    walk_in_phone VARCHAR(20),
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('pending', 'confirmed', 'ready', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(12, 2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT(20) UNSIGNED NOT NULL,
    product_id BIGINT(20) UNSIGNED NOT NULL,
    quantity INT(10) UNSIGNED NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS bills (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT(20) UNSIGNED NULL,
    appointment_id BIGINT(20) UNSIGNED NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    payment_status ENUM('unpaid', 'paid', 'refunded', 'voided') DEFAULT 'unpaid',
    payment_method VARCHAR(100),
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

-- Add foreign keys for feedbacks after products table exists
ALTER TABLE feedbacks ADD CONSTRAINT fk_feedback_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;
ALTER TABLE feedbacks ADD CONSTRAINT fk_feedback_service FOREIGN KEY (service_id) REFERENCES service_types(id) ON DELETE CASCADE;

-- Insert initial categories
INSERT INTO product_categories (name, slug, description) VALUES
('Plants', 'plants', 'Indoor and outdoor landscaping plants.'),
('Tools', 'tools', 'Gardening and landscaping tools.'),
('Materials', 'materials', 'Soil, mulch, stones, and lighting.');

-- Insert initial service types
INSERT INTO service_types (name, description, default_duration_minutes, default_fee) VALUES
('Garden Design Consultation', 'Initial consultation for garden layout and design.', 60, 1500.00),
('Routine Maintenance', 'Regular pruning, weeding, and fertilizing.', 120, 2500.00),
('Irrigation System Check', 'Inspection and repair of sprinklers and drip systems.', 60, 1200.00),
('Hardscaping Quote', 'Measurement and estimate for pathways and retaining walls.', 90, 0.00);

-- Insert Dummy Products
INSERT INTO products (category_id, name, description, price, sku) VALUES
(1, 'Monstera Deliciosa', 'Lush indoor foliage plant in ceramic pot.', 1200.00, 'PLN-MONS-01'),
(1, 'Boxwood Hedge (Set of 3)', 'Perfect for defining garden borders.', 3200.00, 'PLN-BOX-03'),
(2, 'Premium Garden Trowel', 'Stainless steel with ergonomic handle.', 450.00, 'TOL-TRW-01'),
(3, 'Irrigation Drip Kit', 'Complete drip system for 25 m2 garden.', 2100.00, 'MAT-IRR-01'),
(3, 'Landscape Lighting Set', 'Warm white LED path lights (8 pcs).', 3800.00, 'MAT-LGT-08'),
(3, 'Soil & Mulch Combo', 'Organic soil mix and bark mulch bundle.', 950.00, 'MAT-SMC-01');

-- Insert Inventory
INSERT INTO inventory (product_id, quantity, reorder_level) VALUES
(1, 20, 5), (2, 15, 3), (3, 50, 10), (4, 10, 2), (5, 25, 5), (6, 40, 10);
