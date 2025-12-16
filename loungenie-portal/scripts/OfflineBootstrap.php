<?php
/**
 * Offline Bootstrap: Mock WordPress environment without server
 */

// Mock WordPress global $wpdb
class WPDB_Mock {
    public $prefix = 'wp_lgp_';
    private $tables = [];
    private $data = [];

    public function __construct() {
        $this->initializeTables();
    }

    private function initializeTables() {
        $this->tables = [
            'companies' => ['id', 'management_company_id', 'name', 'contact_name', 'email', 'phone', 'address1', 'address2', 'city', 'state', 'zip', 'country', 'contract_type', 'contract_start_date', 'contract_end_date', 'secondary_contact_name', 'secondary_contact_email', 'secondary_contact_phone', 'created_at', 'updated_at'],
            'units' => ['id', 'company_id', 'unit_number', 'model', 'serial_number', 'color', 'lock_brand', 'seasonality', 'warranty_date', 'assigned_technician', 'service_history', 'created_at', 'updated_at'],
            'gateways' => ['id', 'company_id', 'channel_number', 'gateway_address', 'unit_capacity', 'call_button', 'included_equipment', 'admin_password', 'created_at', 'updated_at'],
            'tickets' => ['id', 'company_id', 'created_by', 'title', 'description', 'priority', 'status', 'created_at', 'updated_at'],
            'ticket_attachments' => ['id', 'ticket_id', 'file_name', 'file_type', 'file_size', 'file_path', 'uploaded_by', 'created_at'],
            'training_videos' => ['id', 'title', 'description', 'video_url', 'category', 'target_companies', 'duration', 'created_by', 'created_at', 'updated_at'],
            'audit_logs' => ['id', 'user_id', 'company_id', 'action', 'object_type', 'object_id', 'details', 'ip_address', 'created_at'],
            'notifications' => ['id', 'user_id', 'type', 'title', 'message', 'related_id', 'is_read', 'created_at'],
            'users' => ['ID', 'user_login', 'user_email', 'user_nicename', 'display_name', 'user_registered', 'user_role', 'company_id'],
            'options' => ['option_id', 'option_name', 'option_value'],
        ];

        // Initialize empty data storage
        foreach (array_keys($this->tables) as $table) {
            $this->data[$table] = [];
        }
    }

    public function insert($table, $data) {
        $table = str_replace($this->prefix, '', $table);
        if (!isset($this->data[$table])) {
            return false;
        }

        $data['id'] = (int)($this->getLastId($table) + 1);
        $data['created_at'] = $data['created_at'] ?? current_time('mysql');
        $this->data[$table][] = (object)$data;
        return $data['id'];
    }

    public function query($sql) {
        return true; // Simplified for offline mode
    }

    public function get_results($sql) {
        // Basic query support for SELECT statements
        return [];
    }

    public function prepare($sql, ...$args) {
        return vsprintf(str_replace('%s', "'%s'", str_replace('%d', '%d', $sql)), $args);
    }

    public function get_table_name($table) {
        return $this->prefix . $table;
    }

    private function getLastId($table) {
        if (empty($this->data[$table])) {
            return 0;
        }
        return max(array_map(function($item) { return $item->id ?? 0; }, $this->data[$table]));
    }

    public function getData($table) {
        return $this->data[str_replace($this->prefix, '', $table)] ?? [];
    }

    public function setData($table, $data) {
        $table = str_replace($this->prefix, '', $table);
        $this->data[$table] = $data;
    }

    public function clearData($table = null) {
        if ($table) {
            $table = str_replace($this->prefix, '', $table);
            $this->data[$table] = [];
        } else {
            foreach (array_keys($this->data) as $t) {
                $this->data[$t] = [];
            }
        }
    }
}

// Mock WordPress globals and functions
$GLOBALS['wpdb'] = new WPDB_Mock();
$GLOBALS['wp_locale'] = null;
$GLOBALS['current_user'] = null;

// Core WordPress mock functions
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        $options = $GLOBALS['wpdb']->getData('options');
        foreach ($options as $opt) {
            if ($opt->option_name === $option) {
                return maybe_unserialize($opt->option_value);
            }
        }
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        global $wpdb;
        $options = $wpdb->getData('options');
        foreach ($options as &$opt) {
            if ($opt->option_name === $option) {
                $opt->option_value = $value;
                return true;
            }
        }
        $wpdb->insert('options', ['option_name' => $option, 'option_value' => $value]);
        return true;
    }
}

if (!function_exists('current_time')) {
    function current_time($format = 'mysql', $gmt = false) {
        if ('mysql' === $format) {
            return gmdate('Y-m-d H:i:s', time());
        }
        return time();
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}

if (!function_exists('maybe_unserialize')) {
    function maybe_unserialize($data) {
        if (is_array($data) || is_object($data)) {
            return $data;
        }
        if (is_serialized($data)) {
            return unserialize($data);
        }
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            return $decoded !== null ? $decoded : $data;
        }
        return $data;
    }
}

if (!function_exists('is_serialized')) {
    function is_serialized($data) {
        if (!is_string($data)) {
            return false;
        }
        return @unserialize($data) !== false;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($text) {
        return strip_tags($text);
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() {
        return isset($GLOBALS['current_user']) && $GLOBALS['current_user'] !== null;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return is_user_logged_in() ? $GLOBALS['current_user']->ID : 0;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        $user = $GLOBALS['current_user'] ?? null;
        if (!$user) return false;
        if ($capability === 'manage_options') {
            return $user->user_role === 'administrator' || $user->user_role === 'support';
        }
        return true;
    }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir($time = null) {
        return [
            'path' => OFFLINE_DATAPATH . '/uploads',
            'url' => '/offline/uploads',
            'subdir' => '/uploads',
            'basedir' => OFFLINE_DATAPATH,
            'baseurl' => '/offline',
            'error' => false,
        ];
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($pathname, $mode = 0755) {
        return @mkdir($pathname, $mode, true);
    }
}

// Mock Logger class
class LGP_Logger_Mock {
    public static $logs = [];

    public static function log($action, $object_type = '', $object_id = 0, $details = []) {
        global $wpdb;
        $user_id = get_current_user_id();
        $company_id = isset($GLOBALS['current_user']->company_id) ? $GLOBALS['current_user']->company_id : 0;

        self::$logs[] = [
            'timestamp' => current_time('mysql'),
            'user_id' => $user_id,
            'company_id' => $company_id,
            'action' => $action,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'details' => $details,
            'ip_address' => '127.0.0.1',
        ];

        return $wpdb->insert('audit_logs', [
            'user_id' => $user_id,
            'company_id' => $company_id,
            'action' => $action,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'details' => wp_json_encode($details),
            'ip_address' => '127.0.0.1',
        ]);
    }

    public static function getLogs() {
        return self::$logs;
    }

    public static function clearLogs() {
        self::$logs = [];
    }
}

// Mock Notifications class
class LGP_Notifications_Mock {
    public static $notifications = [];

    public static function notify($user_id, $type, $title, $message, $related_id = 0) {
        global $wpdb;
        self::$notifications[] = [
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $related_id,
            'timestamp' => current_time('mysql'),
        ];
        return $wpdb->insert('notifications', [
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $related_id,
        ]);
    }

    public static function getNotifications() {
        return self::$notifications;
    }

    public static function clearNotifications() {
        self::$notifications = [];
    }
}

// Global aliases
class_alias('LGP_Logger_Mock', 'LGP_Logger');
class_alias('LGP_Notifications_Mock', 'LGP_Notifications');
