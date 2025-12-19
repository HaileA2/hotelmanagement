<?php
class AuditLog {
    private $conn;
    private $table_name = "audit_logs";

    public $id;
    public $user_id;
    public $action;
    public $table_name_affected;
    public $record_id;
    public $old_values;
    public $new_values;
    public $ip_address;
    public $user_agent;
    public $created_at;

    // Action types
    const ACTION_CREATE = 'CREATE';
    const ACTION_UPDATE = 'UPDATE';
    const ACTION_DELETE = 'DELETE';
    const ACTION_LOGIN = 'LOGIN';
    const ACTION_LOGOUT = 'LOGOUT';
    const ACTION_PASSWORD_CHANGE = 'PASSWORD_CHANGE';
    const ACTION_PROFILE_UPDATE = 'PROFILE_UPDATE';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new audit log entry
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (user_id, action, table_name_affected, record_id, 
                  old_values, new_values, ip_address, user_agent)
                 VALUES 
                 (:user_id, :action, :table_name_affected, :record_id, 
                  :old_values, :new_values, :ip_address, :user_agent)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->user_id = $this->user_id ? htmlspecialchars(strip_tags($this->user_id)) : null;
        $this->action = htmlspecialchars(strip_tags($this->action));
        $this->table_name_affected = $this->table_name_affected ? htmlspecialchars(strip_tags($this->table_name_affected)) : null;
        $this->record_id = $this->record_id ? htmlspecialchars(strip_tags($this->record_id)) : null;
        $this->old_values = $this->old_values ? json_encode($this->old_values) : null;
        $this->new_values = $this->new_values ? json_encode($this->new_values) : null;
        $this->ip_address = $this->getClientIP();
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":action", $this->action);
        $stmt->bindParam(":table_name_affected", $this->table_name_affected);
        $stmt->bindParam(":record_id", $this->record_id);
        $stmt->bindParam(":old_values", $this->old_values);
        $stmt->bindParam(":new_values", $this->new_values);
        $stmt->bindParam(":ip_address", $this->ip_address);
        $stmt->bindParam(":user_agent", $this->user_agent);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Get client IP address
    private function getClientIP() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    // Read all audit logs with filters
    public function readAll($filters = []) {
        $query = "SELECT al.*, u.email as user_email, u.first_name, u.last_name 
                 FROM " . $this->table_name . " al
                 LEFT JOIN users u ON al.user_id = u.id
                 WHERE 1=1";
        
        $params = [];
        $types = '';
        
        // Apply filters
        if (!empty($filters['user_id'])) {
            $query .= " AND al.user_id = ?";
            $params[] = $filters['user_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['action'])) {
            $query .= " AND al.action = ?";
            $params[] = $filters['action'];
            $types .= 's';
        }
        
        if (!empty($filters['table_name'])) {
            $query .= " AND al.table_name_affected = ?";
            $params[] = $filters['table_name'];
            $types .= 's';
        }
        
        if (!empty($filters['record_id'])) {
            $query .= " AND al.record_id = ?";
            $params[] = $filters['record_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['start_date'])) {
            $query .= " AND DATE(al.created_at) >= ?";
            $params[] = $filters['start_date'];
            $types .= 's';
        }
        
        if (!empty($filters['end_date'])) {
            $query .= " AND DATE(al.created_at) <= ?";
            $params[] = $filters['end_date'];
            $types .= 's';
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (
                al.action LIKE ? OR 
                al.table_name_affected LIKE ? OR 
                al.old_values LIKE ? OR 
                al.new_values LIKE ? OR
                u.email LIKE ? OR
                CONCAT(u.first_name, ' ', u.last_name) LIKE ?
            )";
            $search_term = "%" . $filters['search'] . "%";
            $params = array_merge($params, array_fill(0, 6, $search_term));
            $types .= str_repeat('s', 6);
        }
        
        $query .= " ORDER BY al.created_at DESC";
        
        // Add pagination
        if (!empty($filters['limit'])) {
            $query .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
            $types .= 'i';
            
            if (!empty($filters['offset'])) {
                $query .= " OFFSET ?";
                $params[] = (int)$filters['offset'];
                $types .= 'i';
            }
        }
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters dynamically if any
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        return $stmt;
    }

    // Read single audit log entry
    public function readOne() {
        $query = "SELECT al.*, u.email as user_email, u.first_name, u.last_name 
                 FROM " . $this->table_name . " al
                 LEFT JOIN users u ON al.user_id = u.id
                 WHERE al.id = ?
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->user_id = $row['user_id'];
            $this->action = $row['action'];
            $this->table_name_affected = $row['table_name_affected'];
            $this->record_id = $row['record_id'];
            $this->old_values = $row['old_values'] ? json_decode($row['old_values'], true) : null;
            $this->new_values = $row['new_values'] ? json_decode($row['new_values'], true) : null;
            $this->ip_address = $row['ip_address'];
            $this->user_agent = $row['user_agent'];
            $this->created_at = $row['created_at'];
            
            // Additional joined fields
            $this->user_email = $row['user_email'];
            $this->user_name = $row['first_name'] . ' ' . $row['last_name'];
            
            return true;
        }
        return false;
    }

    // Get distinct actions for filter dropdowns
    public function getDistinctActions() {
        $query = "SELECT DISTINCT action FROM " . $this->table_name . " ORDER BY action ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Get distinct table names for filter dropdowns
    public function getDistinctTables() {
        $query = "SELECT DISTINCT table_name_affected FROM " . $this->table_name . " 
                 WHERE table_name_affected IS NOT NULL 
                 ORDER BY table_name_affected ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Clean up old logs (retention policy)
    public function cleanupOldLogs($days = 90) {
        $query = "DELETE FROM " . $this->table_name . " 
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $days, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Log a user action (helper method)
    public static function logAction($db, $user_id, $action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
        $log = new self($db);
        $log->user_id = $user_id;
        $log->action = $action;
        $log->table_name_affected = $table_name;
        $log->record_id = $record_id;
        $log->old_values = $old_values;
        $log->new_values = $new_values;
        
        return $log->create();
    }
}
?>
