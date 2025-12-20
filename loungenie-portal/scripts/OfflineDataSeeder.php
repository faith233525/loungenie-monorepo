<?php
/* phpcs:ignoreFile */
/**
 * Offline Data Seeder: Creates realistic mock data
 */

class OfflineDataSeeder {
    public static function run($options = []) {
        OfflineOutput::header("LounGenie Portal: Offline Data Seeder");

        global $wpdb;
        $wpdb->clearData();

        try {
            OfflineOutput::section("1. Seeding Users");
            self::seedUsers();

            OfflineOutput::section("2. Seeding Companies");
            self::seedCompanies();

            OfflineOutput::section("3. Seeding Units");
            self::seedUnits();

            OfflineOutput::section("4. Seeding Gateways");
            self::seedGateways();

            OfflineOutput::section("5. Seeding Tickets");
            self::seedTickets();

            OfflineOutput::section("6. Seeding Attachments");
            self::seedAttachments();

            OfflineOutput::section("7. Seeding Training Videos");
            self::seedTrainingVideos();

            OfflineOutput::section("8. Seeding Geocoding Data");
            self::seedGeodata();

            OfflineOutput::section("9. Seeding Audit Logs");
            self::seedAuditLogs();

            // Save to file
            self::saveToFile();

            OfflineOutput::success("All mock data seeded successfully!");
            self::showSummary();

        } catch (Exception $e) {
            OfflineOutput::error("Seeding failed: " . $e->getMessage());
            throw $e;
        }
    }

    private static function seedUsers() {
        global $wpdb;

        $users = [
            [
                'user_login' => 'support_admin',
                'user_email' => 'admin@poolsafe.test',
                'user_nicename' => 'Support Admin',
                'display_name' => 'John Smith (Support)',
                'user_role' => 'support',
                'company_id' => 0,
            ],
            [
                'user_login' => 'partner_acme',
                'user_email' => 'contact@acme.test',
                'user_nicename' => 'ACME Corp',
                'display_name' => 'Sarah Johnson (ACME)',
                'user_role' => 'partner',
                'company_id' => 1,
            ],
            [
                'user_login' => 'partner_techsolutions',
                'user_email' => 'admin@techsolutions.test',
                'user_nicename' => 'Tech Solutions',
                'display_name' => 'Mike Davis (Tech Solutions)',
                'user_role' => 'partner',
                'company_id' => 2,
            ],
        ];

        foreach ($users as $user) {
            $user['user_registered'] = current_time('mysql');
            $id = $wpdb->insert('users', $user);
            OfflineOutput::success("Created user: {$user['display_name']} (ID: $id)");
        }
    }

    private static function seedCompanies() {
        global $wpdb;

        $companies = [
            [
                'management_company_id' => 1,
                'name' => 'ACME Lounges',
                'contact_name' => 'Sarah Johnson',
                'email' => 'sarah@acme.test',
                'phone' => '555-0101',
                'address1' => '123 Business Park Dr',
                'address2' => 'Suite 200',
                'city' => 'San Francisco',
                'state' => 'CA',
                'zip' => '94105',
                'country' => 'USA',
                'contract_type' => 'revenue_share',
                'contract_start_date' => '2024-01-15',
                'contract_end_date' => '2026-01-15',
                'secondary_contact_name' => 'John Chen',
                'secondary_contact_email' => 'john@acme.test',
                'secondary_contact_phone' => '555-0102',
            ],
            [
                'management_company_id' => 1,
                'name' => 'Tech Solutions Inc',
                'contact_name' => 'Mike Davis',
                'email' => 'mike@techsolutions.test',
                'phone' => '555-0201',
                'address1' => '456 Innovation Ave',
                'city' => 'Austin',
                'state' => 'TX',
                'zip' => '78701',
                'country' => 'USA',
                'contract_type' => 'direct_purchase',
                'contract_start_date' => '2024-06-01',
                'contract_end_date' => '2025-06-01',
                'secondary_contact_name' => 'Lisa Wong',
                'secondary_contact_email' => 'lisa@techsolutions.test',
                'secondary_contact_phone' => '555-0202',
            ],
            [
                'management_company_id' => 2,
                'name' => 'Premium Hotels Co',
                'contact_name' => 'Robert Martinez',
                'email' => 'robert@premiumhotels.test',
                'phone' => '555-0301',
                'address1' => '789 Luxury Blvd',
                'city' => 'Miami',
                'state' => 'FL',
                'zip' => '33101',
                'country' => 'USA',
                'contract_type' => 'revenue_share',
                'contract_start_date' => '2023-09-01',
                'contract_end_date' => '2025-09-01',
                'secondary_contact_name' => 'Patricia Lee',
                'secondary_contact_email' => 'patricia@premiumhotels.test',
                'secondary_contact_phone' => '555-0302',
            ],
        ];

        foreach ($companies as $company) {
            $company['created_at'] = current_time('mysql');
            $company['updated_at'] = current_time('mysql');
            $id = $wpdb->insert('companies', $company);
            OfflineOutput::success("Created company: {$company['name']} (ID: $id)");
        }
    }

    private static function seedUnits() {
        global $wpdb;

        $units = [
            // ACME Lounges
            ['company_id' => 1, 'unit_number' => 'UNIT-001', 'model' => 'LG-Pro', 'serial_number' => 'SN-2024-001', 'color' => 'classic-blue', 'lock_brand' => 'MAKE', 'seasonality' => 'year-round', 'warranty_date' => '2025-12-31', 'assigned_technician' => 'Tech Team A'],
            ['company_id' => 1, 'unit_number' => 'UNIT-002', 'model' => 'LG-Pro', 'serial_number' => 'SN-2024-002', 'color' => 'ice-blue', 'lock_brand' => 'L&F', 'seasonality' => 'seasonal', 'warranty_date' => '2025-06-30', 'assigned_technician' => 'Tech Team B'],
            // Tech Solutions
            ['company_id' => 2, 'unit_number' => 'TS-UNIT-01', 'model' => 'LG-Standard', 'serial_number' => 'SN-2024-003', 'color' => 'ducati-red', 'lock_brand' => 'MAKE', 'seasonality' => 'year-round', 'warranty_date' => '2025-09-30', 'assigned_technician' => 'Tech Team A'],
            ['company_id' => 2, 'unit_number' => 'TS-UNIT-02', 'model' => 'LG-Pro', 'serial_number' => 'SN-2024-004', 'color' => 'yellow', 'lock_brand' => 'other', 'seasonality' => 'year-round', 'warranty_date' => '2026-03-31', 'assigned_technician' => 'Tech Team C'],
            // Premium Hotels
            ['company_id' => 3, 'unit_number' => 'PH-LOUNGE-A', 'model' => 'LG-Premium', 'serial_number' => 'SN-2024-005', 'color' => 'custom', 'lock_brand' => 'L&F', 'seasonality' => 'year-round', 'warranty_date' => '2024-12-31', 'assigned_technician' => 'Tech Team D'],
        ];

        foreach ($units as $unit) {
            $unit['service_history'] = wp_json_encode([['date' => '2024-11-01', 'action' => 'Installation', 'notes' => 'Unit installed and tested']]);
            $unit['created_at'] = current_time('mysql');
            $unit['updated_at'] = current_time('mysql');
            $id = $wpdb->insert('units', $unit);
            OfflineOutput::success("Created unit: {$unit['unit_number']} (ID: $id)");
        }
    }

    private static function seedGateways() {
        global $wpdb;

        $gateways = [
            ['company_id' => 1, 'channel_number' => 1, 'gateway_address' => '192.168.1.100', 'unit_capacity' => 4, 'call_button' => 1, 'included_equipment' => 'Router, Power Supply'],
            ['company_id' => 1, 'channel_number' => 2, 'gateway_address' => '192.168.1.101', 'unit_capacity' => 2, 'call_button' => 0, 'included_equipment' => 'Router'],
            ['company_id' => 2, 'channel_number' => 1, 'gateway_address' => '10.0.0.50', 'unit_capacity' => 6, 'call_button' => 1, 'included_equipment' => 'Router, Power Supply, Backup Battery'],
            ['company_id' => 3, 'channel_number' => 1, 'gateway_address' => '172.16.0.20', 'unit_capacity' => 8, 'call_button' => 1, 'included_equipment' => 'Premium Router, UPS, Monitoring'],
        ];

        foreach ($gateways as $gw) {
            $gw['admin_password'] = password_hash('GatewayPass123', PASSWORD_BCRYPT);
            $gw['created_at'] = current_time('mysql');
            $gw['updated_at'] = current_time('mysql');
            $id = $wpdb->insert('gateways', $gw);
            $button = $gw['call_button'] ? 'YES' : 'NO';
            OfflineOutput::success("Created gateway: Channel {$gw['channel_number']} (Call Button: $button, ID: $id)");
        }
    }

    private static function seedTickets() {
        global $wpdb;

        $tickets = [
            ['company_id' => 1, 'created_by' => 2, 'title' => 'Unit not responding to calls', 'description' => 'UNIT-001 is not responding to incoming calls. LED is on but no audio.', 'priority' => 'high', 'status' => 'open'],
            ['company_id' => 1, 'created_by' => 2, 'title' => 'Warranty question', 'description' => 'When does UNIT-002 warranty expire?', 'priority' => 'low', 'status' => 'resolved'],
            ['company_id' => 2, 'created_by' => 3, 'title' => 'Gateway configuration needed', 'description' => 'Need help configuring new gateway for expansion.', 'priority' => 'medium', 'status' => 'open'],
            ['company_id' => 3, 'created_by' => 2, 'title' => 'Monthly maintenance report', 'description' => 'Please review lounge maintenance logs for October.', 'priority' => 'low', 'status' => 'open'],
        ];

        foreach ($tickets as $ticket) {
            $ticket['created_at'] = current_time('mysql');
            $ticket['updated_at'] = current_time('mysql');
            $id = $wpdb->insert('tickets', $ticket);
            OfflineOutput::success("Created ticket: {$ticket['title']} (ID: $id, Priority: {$ticket['priority']})");
        }
    }

    private static function seedAttachments() {
        global $wpdb;

        // Create attachment directory
        @mkdir(OFFLINE_DATAPATH . '/attachments', 0755, true);

        $attachments = [
            ['ticket_id' => 1, 'file_name' => 'unit_diagnostic.pdf', 'file_type' => 'application/pdf', 'file_size' => 256000, 'uploaded_by' => 1],
            ['ticket_id' => 3, 'file_name' => 'gateway_config.txt', 'file_type' => 'text/plain', 'file_size' => 2048, 'uploaded_by' => 1],
            ['ticket_id' => 4, 'file_name' => 'october_report.docx', 'file_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'file_size' => 512000, 'uploaded_by' => 1],
        ];

        foreach ($attachments as $attach) {
            $attach['file_path'] = OFFLINE_DATAPATH . '/attachments/' . md5($attach['file_name'] . time()) . '.' . pathinfo($attach['file_name'], PATHINFO_EXTENSION);
            $attach['created_at'] = current_time('mysql');
            
            // Create mock file
            @file_put_contents($attach['file_path'], '[Mock file: ' . $attach['file_name'] . ']');
            
            $id = $wpdb->insert('ticket_attachments', $attach);
            OfflineOutput::success("Created attachment: {$attach['file_name']} (ID: $id, Size: " . self::formatBytes($attach['file_size']) . ")");
        }
    }

    private static function seedTrainingVideos() {
        global $wpdb;

        $videos = [
            ['title' => 'Getting Started with LounGenie', 'description' => 'Introduction to LounGenie unit setup and operation', 'video_url' => 'https://youtube.com/watch?v=stub1', 'category' => 'general', 'target_companies' => '[]', 'duration' => 480, 'created_by' => 1],
            ['title' => 'Installation Guide', 'description' => 'Step-by-step installation of LounGenie Pro units', 'video_url' => 'https://youtube.com/watch?v=stub2', 'category' => 'installation', 'target_companies' => '[1,2]', 'duration' => 720, 'created_by' => 1],
            ['title' => 'Troubleshooting Common Issues', 'description' => 'How to diagnose and fix common problems', 'video_url' => 'https://youtube.com/watch?v=stub3', 'category' => 'troubleshooting', 'target_companies' => '[]', 'duration' => 600, 'created_by' => 1],
            ['title' => 'Maintenance Best Practices', 'description' => 'Regular maintenance schedule and procedures', 'video_url' => 'https://youtube.com/watch?v=stub4', 'category' => 'maintenance', 'target_companies' => '[1]', 'duration' => 540, 'created_by' => 1],
        ];

        foreach ($videos as $video) {
            $video['created_at'] = current_time('mysql');
            $video['updated_at'] = current_time('mysql');
            $id = $wpdb->insert('training_videos', $video);
            $duration_min = intval($video['duration'] / 60);
            OfflineOutput::success("Created video: {$video['title']} (ID: $id, Duration: {$duration_min}m)");
        }
    }

    private static function seedGeodata() {
        $coordinates = [
            1 => ['lat' => 37.7749, 'lng' => -122.4194, 'name' => 'ACME Lounges - San Francisco'],
            2 => ['lat' => 30.2672, 'lng' => -97.7431, 'name' => 'Tech Solutions - Austin'],
            3 => ['lat' => 25.7617, 'lng' => -80.1918, 'name' => 'Premium Hotels - Miami'],
        ];

        foreach ($coordinates as $company_id => $coord) {
            update_option("lgp_geocode_$company_id", $coord);
            OfflineOutput::success("Cached geocode: {$coord['name']} ({$coord['lat']}, {$coord['lng']})");
        }
    }

    private static function seedAuditLogs() {
        // Simulate audit logs from previous actions
        $logs = [
            ['user_id' => 1, 'company_id' => 0, 'action' => 'user_login', 'object_type' => 'user', 'object_id' => 1, 'details' => wp_json_encode(['ip' => '127.0.0.1'])],
            ['user_id' => 2, 'company_id' => 1, 'action' => 'user_login', 'object_type' => 'user', 'object_id' => 2, 'details' => wp_json_encode(['ip' => '127.0.0.1'])],
            ['user_id' => 1, 'company_id' => 1, 'action' => 'ticket_create', 'object_type' => 'ticket', 'object_id' => 1, 'details' => wp_json_encode(['title' => 'Unit not responding'])],
            ['user_id' => 1, 'company_id' => 1, 'action' => 'attachment_upload', 'object_type' => 'attachment', 'object_id' => 1, 'details' => wp_json_encode(['file' => 'unit_diagnostic.pdf'])],
        ];

        global $wpdb;
        foreach ($logs as $log) {
            $log['ip_address'] = '127.0.0.1';
            $log['created_at'] = current_time('mysql');
            $wpdb->insert('audit_logs', $log);
            OfflineOutput::success("Logged: {$log['action']} by user {$log['user_id']}");
        }
    }

    private static function saveToFile() {
        global $wpdb;

        $data = [];
        foreach (['users', 'companies', 'units', 'gateways', 'tickets', 'ticket_attachments', 'training_videos'] as $table) {
            $data[$table] = $wpdb->getData($table);
        }

        $file = OFFLINE_DATAPATH . '/seeded_data.json';
        file_put_contents($file, wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        OfflineOutput::success("Data saved to: $file");
    }

    private static function showSummary() {
        global $wpdb;

        echo "\n" . str_repeat("=", 70) . "\n";
        echo "📊 Seeded Data Summary\n";
        echo str_repeat("=", 70) . "\n";

        $tables = ['users', 'companies', 'units', 'gateways', 'tickets', 'ticket_attachments', 'training_videos'];
        foreach ($tables as $table) {
            $count = count($wpdb->getData($table));
            echo sprintf("  %-20s: %3d records\n", ucfirst($table), $count);
        }

        echo "\n";
    }

    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
