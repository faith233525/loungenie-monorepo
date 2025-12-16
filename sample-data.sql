-- ============================================================================
-- PoolSafe Portal - Sample Data SQL
-- ============================================================================
-- 
-- This file contains sample data for testing the PoolSafe Portal plugin.
-- It includes sample companies, tickets, services, and contacts.
--
-- USAGE:
-- 1. Ensure the PoolSafe Portal plugin is activated (this creates the tables)
-- 2. Import this file via phpMyAdmin or MySQL command line:
--    mysql -u username -p database_name < sample-data.sql
-- 3. Or use WordPress database import tools
--
-- NOTE: Replace 'wp_' with your actual WordPress table prefix if different
-- ============================================================================

-- ============================================================================
-- COMPANIES
-- ============================================================================

INSERT INTO `wp_psp_companies` (`id`, `username`, `password_hash`, `company_name`, `address`, `city`, `state`, `zip_code`, `phone`, `email`, `status`, `created_at`, `updated_at`) VALUES
(1, 'acmepools', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Acme Pool Services', '123 Main Street', 'Los Angeles', 'CA', '90001', '(555) 123-4567', 'contact@acmepools.com', 'active', NOW(), NOW()),
(2, 'clearwater', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Clearwater Management', '456 Oak Avenue', 'San Diego', 'CA', '92101', '(555) 234-5678', 'info@clearwater.com', 'active', NOW(), NOW()),
(3, 'poolpros', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pool Pros Inc', '789 Beach Blvd', 'Miami', 'FL', '33101', '(555) 345-6789', 'service@poolpros.com', 'active', NOW(), NOW()),
(4, 'bluewavepool', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Blue Wave Pool Service', '321 Sunset Drive', 'Phoenix', 'AZ', '85001', '(555) 456-7890', 'contact@bluewave.com', 'active', NOW(), NOW()),
(5, 'crystalclear', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Crystal Clear Pools', '654 Valley Road', 'Las Vegas', 'NV', '89101', '(555) 567-8901', 'info@crystalclear.com', 'active', NOW(), NOW());

-- Password for all sample companies: "password123" (for testing only)

-- ============================================================================
-- COMPANY CONTACTS
-- ============================================================================

INSERT INTO `wp_psp_company_contacts` (`id`, `company_id`, `contact_type`, `first_name`, `last_name`, `email`, `phone`, `position`, `is_primary`, `created_at`) VALUES
-- Acme Pool Services
(1, 1, 'primary', 'John', 'Smith', 'john.smith@acmepools.com', '(555) 123-4567', 'Owner', 1, NOW()),
(2, 1, 'secondary', 'Jane', 'Doe', 'jane.doe@acmepools.com', '(555) 123-4568', 'Operations Manager', 0, NOW()),

-- Clearwater Management
(3, 2, 'primary', 'Robert', 'Johnson', 'robert.j@clearwater.com', '(555) 234-5678', 'Director', 1, NOW()),
(4, 2, 'secondary', 'Emily', 'Davis', 'emily.d@clearwater.com', '(555) 234-5679', 'Service Coordinator', 0, NOW()),

-- Pool Pros Inc
(5, 3, 'primary', 'Michael', 'Wilson', 'michael.w@poolpros.com', '(555) 345-6789', 'CEO', 1, NOW()),
(6, 3, 'secondary', 'Sarah', 'Brown', 'sarah.b@poolpros.com', '(555) 345-6790', 'Service Manager', 0, NOW()),
(7, 3, 'additional', 'David', 'Martinez', 'david.m@poolpros.com', '(555) 345-6791', 'Technician Lead', 0, NOW()),

-- Blue Wave Pool Service
(8, 4, 'primary', 'Jennifer', 'Taylor', 'jennifer.t@bluewave.com', '(555) 456-7890', 'Owner', 1, NOW()),

-- Crystal Clear Pools
(9, 5, 'primary', 'William', 'Anderson', 'william.a@crystalclear.com', '(555) 567-8901', 'President', 1, NOW()),
(10, 5, 'secondary', 'Lisa', 'Thomas', 'lisa.t@crystalclear.com', '(555) 567-8902', 'Customer Service Manager', 0, NOW());

-- ============================================================================
-- TICKETS
-- ============================================================================

INSERT INTO `wp_psp_tickets` (`id`, `company_id`, `title`, `description`, `status`, `priority`, `category`, `assigned_to`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Pool heater not working', 'The pool heater at unit #5 is not turning on. Customer reports it stopped working yesterday.', 'open', 'high', 'maintenance', 'support', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 1, 'Chemical levels incorrect', 'Weekly test shows pH is too high. Need to schedule chemical balance service.', 'in_progress', 'medium', 'service', 'support', 1, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 2, 'New installation request', 'Customer wants to install a new pool automation system for their luxury resort property.', 'open', 'medium', 'install', NULL, 2, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 2, 'Filter replacement needed', 'Annual filter inspection completed. Recommend replacement of sand filter.', 'in_progress', 'low', 'maintenance', 'support', 2, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 3, 'Pump making noise', 'Main circulation pump is making unusual grinding noise. Possible bearing failure.', 'open', 'high', 'repair', 'support', 3, DATE_SUB(NOW(), INTERVAL 1 HOUR), DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(6, 3, 'Routine maintenance', 'Quarterly maintenance check scheduled for next week. Need to confirm date.', 'pending', 'low', 'service', NULL, 3, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(7, 4, 'Water level sensor fault', 'Automated water level sensor showing error code. May need replacement.', 'open', 'medium', 'repair', 'support', 4, DATE_SUB(NOW(), INTERVAL 12 HOUR), DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(8, 5, 'Annual service contract renewal', 'Annual service contract expiring next month. Need to discuss renewal terms.', 'pending', 'low', 'service', NULL, 5, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY)),
(9, 1, 'Chlorinator malfunction', 'Salt chlorinator not producing chlorine. Display shows error.', 'resolved', 'high', 'repair', 'support', 1, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 18 DAY)),
(10, 3, 'Equipment upgrade inquiry', 'Customer interested in upgrading to variable speed pump for energy savings.', 'open', 'low', 'install', NULL, 3, DATE_SUB(NOW(), INTERVAL 4 HOUR), DATE_SUB(NOW(), INTERVAL 4 HOUR));

-- ============================================================================
-- TICKET REPLIES (Comments/Thread History)
-- ============================================================================

INSERT INTO `wp_psp_ticket_replies` (`id`, `ticket_id`, `user_type`, `user_id`, `message`, `created_at`) VALUES
-- Ticket #1 replies
(1, 1, 'company', 1, 'This is urgent as the customer needs the heater working for an event this weekend.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 1, 'support', 0, 'Understood. I''ve scheduled a technician for tomorrow morning at 9 AM. Will bring replacement parts just in case.', DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Ticket #2 replies
(3, 2, 'support', 0, 'Chemical adjustment completed yesterday. Will retest tomorrow to confirm levels are stable.', DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Ticket #5 replies
(4, 5, 'company', 3, 'The noise started this morning during the pump''s normal cycle. It''s quite loud.', DATE_SUB(NOW(), INTERVAL 1 HOUR)),

-- Ticket #9 replies (resolved)
(5, 9, 'company', 1, 'Chlorinator replaced and system is working perfectly now. Thanks for the quick response!', DATE_SUB(NOW(), INTERVAL 18 DAY)),
(6, 9, 'support', 0, 'Great! Closing this ticket. Feel free to open a new one if you have any other issues.', DATE_SUB(NOW(), INTERVAL 18 DAY));

-- ============================================================================
-- SERVICE RECORDS
-- ============================================================================

INSERT INTO `wp_psp_service_records` (`id`, `company_id`, `service_type`, `service_date`, `technician`, `description`, `status`, `duration_minutes`, `cost`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'maintenance', DATE_SUB(NOW(), INTERVAL 30 DAY), 'Tech #1', 'Monthly pool maintenance - chemical balance, filter cleaning, equipment inspection', 'completed', 90, 150.00, 'All equipment functioning normally. Replaced filter cartridge.', DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY)),
(2, 1, 'repair', DATE_SUB(NOW(), INTERVAL 25 DAY), 'Tech #2', 'Replaced faulty pressure gauge on filter system', 'completed', 45, 95.00, 'Gauge was reading incorrectly causing customer concern.', DATE_SUB(NOW(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY)),
(3, 2, 'maintenance', DATE_SUB(NOW(), INTERVAL 15 DAY), 'Tech #1', 'Quarterly resort pool maintenance - 5 pools', 'completed', 240, 850.00, 'All pools serviced. Recommended filter replacement on pool #3.', DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY)),
(4, 2, 'install', DATE_SUB(NOW(), INTERVAL 45 DAY), 'Tech #3', 'Installed new pool automation system', 'completed', 360, 2500.00, 'System includes WiFi control, automated chemical dosing, and temperature management.', DATE_SUB(NOW(), INTERVAL 45 DAY), DATE_SUB(NOW(), INTERVAL 45 DAY)),
(5, 3, 'maintenance', DATE_SUB(NOW(), INTERVAL 20 DAY), 'Tech #2', 'Bi-weekly service - chemical adjustment and cleaning', 'completed', 60, 120.00, 'Water chemistry perfect. Minor algae growth addressed.', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY)),
(6, 3, 'maintenance', DATE_SUB(NOW(), INTERVAL 6 DAY), 'Tech #2', 'Bi-weekly service - chemical adjustment and cleaning', 'completed', 60, 120.00, 'All systems operational.', DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY)),
(7, 4, 'repair', DATE_SUB(NOW(), INTERVAL 10 DAY), 'Tech #1', 'Replaced defective pool light transformer', 'completed', 75, 185.00, 'Transformer had water damage. Sealed properly to prevent recurrence.', DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY)),
(8, 4, 'maintenance', NOW(), 'Tech #3', 'Monthly maintenance and inspection', 'scheduled', NULL, 150.00, 'Scheduled for today at 2 PM', NOW(), NOW()),
(9, 5, 'maintenance', DATE_ADD(NOW(), INTERVAL 3 DAY), 'Tech #1', 'Weekly maintenance service', 'scheduled', NULL, 100.00, 'Regular weekly service', NOW(), NOW()),
(10, 5, 'inspection', DATE_SUB(NOW(), INTERVAL 60 DAY), 'Tech #2', 'Annual safety inspection and equipment audit', 'completed', 120, 200.00, 'All equipment meets safety standards. Pool is compliant.', DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 60 DAY));

-- ============================================================================
-- SESSIONS (for testing session management)
-- ============================================================================
-- Note: Sessions are typically created dynamically during login
-- These are example records showing the table structure

-- INSERT INTO `wp_psp_sessions` (`id`, `company_id`, `token_hash`, `ip_address`, `user_agent`, `created_at`, `expires_at`, `last_activity`) VALUES
-- (1, 1, SHA2(CONCAT('sample_token_', RAND()), 256), '192.168.1.100', 'Mozilla/5.0', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), NOW()),
-- (2, 2, SHA2(CONCAT('sample_token_', RAND()), 256), '192.168.1.101', 'Mozilla/5.0', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), NOW());

-- ============================================================================
-- LOGIN LOG (for testing authentication tracking)
-- ============================================================================

INSERT INTO `wp_psp_login_log` (`id`, `company_id`, `username`, `login_type`, `status`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'acmepools', 'company', 'success', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(2, 2, 'clearwater', 'company', 'success', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(3, 3, 'poolpros', 'company', 'success', '192.168.1.102', 'Mozilla/5.0 (X11; Linux x86_64)', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(4, NULL, 'support@company.com', 'support_m365', 'success', '192.168.1.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(5, NULL, 'admin@company.com', 'support_m365', 'success', '192.168.1.201', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(6, 1, 'acmepools', 'company', 'failed', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(7, 4, 'bluewavepool', 'company', 'success', '192.168.1.103', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1)', DATE_SUB(NOW(), INTERVAL 8 HOUR)),
(8, 5, 'crystalclear', 'company', 'success', '192.168.1.104', 'Mozilla/5.0 (iPad; CPU OS 14_7_1)', DATE_SUB(NOW(), INTERVAL 10 HOUR));

-- ============================================================================
-- PARTNERS (Legacy table - may be used for location/mapping data)
-- ============================================================================

INSERT INTO `wp_psp_partners` (`id`, `company_id`, `partner_name`, `address`, `city`, `state`, `zip_code`, `latitude`, `longitude`, `phone`, `email`, `units_count`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Acme Pool Services', '123 Main Street', 'Los Angeles', 'CA', '90001', 34.0522, -118.2437, '(555) 123-4567', 'contact@acmepools.com', 15, 'active', NOW(), NOW()),
(2, 2, 'Clearwater Management', '456 Oak Avenue', 'San Diego', 'CA', '92101', 32.7157, -117.1611, '(555) 234-5678', 'info@clearwater.com', 42, 'active', NOW(), NOW()),
(3, 3, 'Pool Pros Inc', '789 Beach Blvd', 'Miami', 'FL', '33101', 25.7617, -80.1918, '(555) 345-6789', 'service@poolpros.com', 28, 'active', NOW(), NOW()),
(4, 4, 'Blue Wave Pool Service', '321 Sunset Drive', 'Phoenix', 'AZ', '85001', 33.4484, -112.0740, '(555) 456-7890', 'contact@bluewave.com', 19, 'active', NOW(), NOW()),
(5, 5, 'Crystal Clear Pools', '654 Valley Road', 'Las Vegas', 'NV', '89101', 36.1699, -115.1398, '(555) 567-8901', 'info@crystalclear.com', 33, 'active', NOW(), NOW());

-- ============================================================================
-- SAMPLE DATA SUMMARY
-- ============================================================================
--
-- This sample data includes:
-- - 5 Companies with login credentials (password: "password123")
-- - 10 Company contacts across all companies
-- - 10 Support tickets with various statuses and priorities
-- - 6 Ticket replies showing conversation threads
-- - 10 Service records including completed, current, and scheduled services
-- - 8 Login log entries showing authentication history
-- - 5 Partner location records for mapping features
--
-- TESTING SCENARIOS:
-- 
-- 1. Company Login:
--    - Username: acmepools (or any other company username)
--    - Password: password123
--
-- 2. View Tickets:
--    - Companies can see their own tickets
--    - Support users can see all tickets
--
-- 3. Filter Testing:
--    - Filter by status: open, in_progress, pending, resolved
--    - Filter by priority: high, medium, low
--    - Filter by category: maintenance, service, install, repair
--
-- 4. Service History:
--    - View completed services
--    - See scheduled upcoming services
--    - Track service costs and duration
--
-- 5. Analytics Testing:
--    - Dashboard should show ticket counts by status
--    - Service statistics by company
--    - Login activity tracking
--
-- ============================================================================
