-- LounGenie Portal Sample Data
-- Import this file to populate the plugin with demo data for testing
-- Replace 'wp_' prefix with your actual WordPress table prefix if different

-- Sample Management Companies
INSERT INTO wp_lgp_management_companies (name, address, contact_name, contact_email, contact_phone) VALUES
('Premium Property Management', '456 Oak Avenue, Suite 200, Los Angeles, CA 90001', 'Jane Smith', 'jane@premiumpm.com', '(555) 111-2222'),
('Sunshine Properties', '789 Beach Blvd, Miami, FL 33101', 'Mike Johnson', 'mike@sunshineprops.com', '(555) 333-4444'),
('Mountain View Management', '321 Alpine Road, Denver, CO 80202', 'Sarah Williams', 'sarah@mountainview.com', '(555) 555-6666');

-- Sample Companies (with venue_type)
INSERT INTO wp_lgp_companies (name, address, state, venue_type, contact_name, contact_email, contact_phone, management_company_id) VALUES
('Acme Corporation', '123 Main Street, Suite 100, Los Angeles, CA 90001', 'CA', 'Hotel', 'John Doe', 'john@acme.com', '(555) 123-4567', 1),
('TechStart Inc', '456 Innovation Drive, San Francisco, CA 94102', 'CA', 'Resort', 'Alice Brown', 'alice@techstart.com', '(555) 234-5678', 1),
('Global Solutions LLC', '789 Business Park, Miami, FL 33101', 'FL', 'Waterpark', 'Bob Wilson', 'bob@globalsolutions.com', '(555) 345-6789', 2),
('Coastal Resorts', '321 Oceanfront Way, Miami, FL 33139', 'FL', 'Resort', 'Emma Davis', 'emma@coastalresorts.com', '(555) 456-7890', 2),
('Peak Enterprises', '654 Mountain Pass, Denver, CO 80202', 'CO', 'Surf Park', 'Chris Taylor', 'chris@peakenterprises.com', '(555) 567-8901', 3);

-- Sample LounGenie Units (with new fields: lock_brand, season, venue_type)
INSERT INTO wp_lgp_units (company_id, management_company_id, address, lock_type, lock_brand, color_tag, season, venue_type, status, install_date, service_history) VALUES
(1, 1, 'Pool Area A, 123 Main Street, Los Angeles, CA', 'Smart Lock Pro', 'MAKE', 'Yellow', 'year-round', 'Hotel', 'active', '2024-01-15', 'Initial installation completed'),
(1, 1, 'Pool Area B, 123 Main Street, Los Angeles, CA', 'Smart Lock Pro', 'MAKE', 'Red', 'seasonal', 'Hotel', 'active', '2024-01-15', 'Initial installation completed'),
(1, 1, 'Spa Area, 123 Main Street, Los Angeles, CA', 'Smart Lock Basic', 'L&F', 'Classic Blue', 'year-round', 'Hotel', 'active', '2024-02-01', 'Installed with standard package'),
(2, 1, 'Main Pool, 456 Innovation Drive, San Francisco, CA', 'Smart Lock Pro', 'MAKE', 'Ice Blue', 'year-round', 'Resort', 'active', '2024-01-20', 'Installation and testing complete'),
(2, 1, 'Rooftop Pool, 456 Innovation Drive, San Francisco, CA', 'Smart Lock Elite', 'MAKE', 'Yellow', 'seasonal', 'Resort', 'active', '2024-02-15', 'Elite unit with premium features'),
(3, 2, 'Pool Complex A, 789 Business Park, Miami, FL', 'Smart Lock Pro', 'L&F', 'Red', 'year-round', 'Waterpark', 'active', '2024-01-25', 'Installed successfully'),
(3, 2, 'Pool Complex B, 789 Business Park, Miami, FL', 'Smart Lock Pro', 'MAKE', 'Classic Blue', 'seasonal', 'Waterpark', 'install', '2024-03-01', 'Installation scheduled'),
(4, 2, 'Beach Pool 1, 321 Oceanfront Way, Miami, FL', 'Smart Lock Elite', 'MAKE', 'Ice Blue', 'seasonal', 'Resort', 'active', '2023-12-10', 'Premium installation with warranty'),
(4, 2, 'Beach Pool 2, 321 Oceanfront Way, Miami, FL', 'Smart Lock Elite', 'L&F', 'Yellow', 'seasonal', 'Resort', 'active', '2023-12-10', 'Premium installation with warranty'),
(4, 2, 'Spa Complex, 321 Oceanfront Way, Miami, FL', 'Smart Lock Pro', 'MAKE', 'Red', 'year-round', 'Resort', 'service', '2023-11-15', 'Scheduled for routine maintenance'),
(5, 3, 'Mountain Pool, 654 Mountain Pass, Denver, CO', 'Smart Lock Pro', 'L&F', 'Classic Blue', 'seasonal', 'Surf Park', 'active', '2024-02-20', 'Cold weather installation complete'),
(5, 3, 'Indoor Pool, 654 Mountain Pass, Denver, CO', 'Smart Lock Basic', 'MAKE', 'Ice Blue', 'year-round', 'Hotel', 'active', '2024-02-20', 'Standard installation');

-- Sample Service Requests
INSERT INTO wp_lgp_service_requests (company_id, unit_id, request_type, priority, status, notes) VALUES
(1, 1, 'maintenance', 'normal', 'completed', 'Routine maintenance check completed successfully'),
(1, 2, 'repair', 'high', 'in_progress', 'Lock mechanism needs adjustment'),
(2, 4, 'maintenance', 'normal', 'pending', 'Quarterly maintenance due'),
(3, 7, 'install', 'high', 'pending', 'New unit installation request'),
(4, 10, 'maintenance', 'urgent', 'in_progress', 'Lock not responding, urgent attention needed'),
(5, 11, 'update', 'normal', 'pending', 'Firmware update requested');

-- Sample Tickets
INSERT INTO wp_lgp_tickets (service_request_id, status, thread_history, email_reference) VALUES
(1, 'closed', '{"messages":[{"timestamp":"2024-03-01 10:00:00","user":"John Doe","message":"Routine maintenance needed"},{"timestamp":"2024-03-01 14:30:00","user":"Support Team","message":"Maintenance completed, all systems operational"}]}', 'ticket-001@loungenie.com'),
(2, 'open', '{"messages":[{"timestamp":"2024-03-10 09:15:00","user":"John Doe","message":"Lock mechanism making unusual noise"},{"timestamp":"2024-03-10 10:00:00","user":"Support Team","message":"Technician assigned, will arrive tomorrow"}]}', 'ticket-002@loungenie.com'),
(3, 'open', '{"messages":[{"timestamp":"2024-03-15 11:00:00","user":"Alice Brown","message":"Requesting quarterly maintenance"}]}', 'ticket-003@loungenie.com'),
(4, 'open', '{"messages":[{"timestamp":"2024-03-12 08:30:00","user":"Bob Wilson","message":"Need installation for new pool unit"}]}', 'ticket-004@loungenie.com'),
(5, 'open', '{"messages":[{"timestamp":"2024-03-14 16:45:00","user":"Emma Davis","message":"Urgent: Lock not responding to commands"},{"timestamp":"2024-03-14 17:00:00","user":"Support Team","message":"Emergency technician dispatched"}]}', 'ticket-005@loungenie.com'),
(6, 'open', '{"messages":[{"timestamp":"2024-03-16 13:20:00","user":"Chris Taylor","message":"Please update firmware to latest version"}]}', 'ticket-006@loungenie.com');

-- Summary of sample data:
-- - 3 Management Companies
-- - 5 Companies (linked to management companies)
-- - 12 LounGenie Units (various statuses: active, install, service)
-- - 6 Service Requests (various types and priorities)
-- - 6 Tickets (open and closed)
--
-- After importing this data, you can:
-- 1. Create a support user and view all data
-- 2. Create partner users and link them to company IDs (1-5)
-- 3. Test the dashboard, map view, and service request functionality
