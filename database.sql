-- Create database
CREATE DATABASE IF NOT EXISTS hotel_management;
USE hotel_management;

-- Token Blacklist table
CREATE TABLE IF NOT EXISTS token_blacklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token VARCHAR(512) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    role ENUM('admin','manager','customer') NOT NULL,
    professional_details TEXT,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255),
    verification_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hotels table
CREATE TABLE IF NOT EXISTS hotels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    address VARCHAR(255),
    city VARCHAR(255),
    country VARCHAR(255),
    description TEXT,
    price_per_night DECIMAL(10,2) DEFAULT 0,
    amenities TEXT,
    rating DECIMAL(2,1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rooms table
CREATE TABLE IF NOT EXISTS rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    availability ENUM('Available','Booked','Maintenance') DEFAULT 'Available',
    max_occupancy INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    status ENUM('Pending','Confirmed','Cancelled','Completed') DEFAULT 'Pending',
    total_price DECIMAL(10,2) NOT NULL,
    guest_count INT,
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add missing columns to hotels table if they don't exist
ALTER TABLE hotels ADD COLUMN IF NOT EXISTS address VARCHAR(255);
ALTER TABLE hotels ADD COLUMN IF NOT EXISTS city VARCHAR(255);
ALTER TABLE hotels ADD COLUMN IF NOT EXISTS country VARCHAR(255);
ALTER TABLE hotels ADD COLUMN IF NOT EXISTS price_per_night DECIMAL(10,2) DEFAULT 0;
ALTER TABLE hotels ADD COLUMN IF NOT EXISTS rating DECIMAL(2,1) DEFAULT 0;

-- Create an admin user (password: Admin@123)
INSERT INTO users (email, password, first_name, last_name, role, professional_details, email_verified)
VALUES ('admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin', 'System Administrator', 1);

-- Create a system customer user for API bookings
INSERT INTO users (email, password, first_name, last_name, role, email_verified, status)
VALUES ('api-customer@hotelhub.com', '', 'API', 'Customer', 'customer', 1, 'active');

-- Sample hotel data
INSERT INTO hotels (name, location, address, city, country, description, price_per_night, amenities, rating)
VALUES
('Grand Hotel Addis', 'Addis Ababa, Ethiopia', '123 Bole Road', 'Addis Ababa', 'Ethiopia',
 'Luxury hotel in the heart of Addis Ababa with world-class amenities.',
 150.00, '["Free WiFi", "Swimming Pool", "Restaurant", "Parking"]', 4.5);

-- Sample room data
INSERT INTO rooms (hotel_id, type, price_per_night, availability, max_occupancy)
VALUES
(1, 'Standard Single', 100.00, 'Available', 1),
(1, 'Standard Double', 150.00, 'Available', 2),
(1, 'Deluxe Suite', 250.00, 'Available', 4);
