<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../helpers/jwt_helper.php';
include_once '../../classes/ReportGenerator.php';

$database = new Database();
$db = $database->getConnection();
$jwt = getBearerToken();

if (!$jwt) {
    http_response_code(401);
    echo json_encode(["message" => "Access denied."]);
    exit();
}

try {
    $jwtHandler = new JwtHandler($db);
    $payload = $jwtHandler->getTokenPayload($jwt);
    
    // Only admin and managers can access reports
    if (!in_array($payload['role'], ['Admin', 'Manager'])) {
        http_response_code(403);
        echo json_encode(["message" => "Insufficient permissions."]);
        exit();
    }
    
    // Get query parameters
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    $hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : null;
    $group_by = isset($_GET['group_by']) ? strtolower($_GET['group_by']) : 'day';
    $format = isset($_GET['format']) ? strtolower($_GET['format']) : 'json';
    
    // Validate group_by parameter
    $valid_group_by = ['day', 'week', 'month', 'hotel', 'room_type'];
    if (!in_array($group_by, $valid_group_by)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid group_by parameter. Must be one of: " . implode(', ', $valid_group_by)]);
        exit();
    }
    
    // If manager, they can only see their hotel's data
    if ($payload['role'] === 'Manager') {
        $hotel_id = getManagerHotelId($db, $payload['user_id']);
        if (!$hotel_id) {
            http_response_code(403);
            echo json_encode(["message" => "No hotel assigned to this manager."]);
            exit();
        }
    }
    
    // Initialize report generator
    $report = new ReportGenerator($db, $start_date, $end_date, $hotel_id);
    
    // Get revenue data
    $revenueData = getRevenueData($db, $report, $group_by);
    
    // Calculate totals
    $totalRevenue = array_sum(array_column($revenueData, 'Revenue'));
    $totalBookings = array_sum(array_column($revenueData, 'Bookings'));
    $totalNights = array_sum(array_column($revenueData, 'Room Nights'));
    
    // Format response based on requested format
    switch ($format) {
        case 'csv':
            $csv = $report->toCSV($revenueData, 'revenue_report_' . date('Y-m-d') . '.csv');
            $report->sendDownloadHeaders($csv, 'revenue_report_' . date('Y-m-d') . '.csv');
            break;
            
        case 'pdf':
            $title = 'Revenue Report' . ($hotel_id ? ' - Hotel ID: ' . $hotel_id : '');
            
            // Add summary to the data for PDF
            $pdfData = $revenueData;
            $pdfData[] = [
                'Period' => 'TOTAL',
                'Revenue' => number_format($totalRevenue, 2),
                'Bookings' => $totalBookings,
                'Room Nights' => $totalNights,
                'Average Daily Rate' => $totalNights > 0 ? number_format($totalRevenue / $totalNights, 2) : '0.00',
                'Revenue per Available Room' => 'N/A'
            ];
            
            $pdf = $report->toPDF($pdfData, $title, 'revenue_report_' . date('Y-m-d') . '.pdf');
            $report->sendDownloadHeaders($pdf, 'revenue_report_' . date('Y-m-d') . '.pdf', 'application/pdf');
            break;
            
        case 'json':
        default:
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $revenueData,
                'summary' => [
                    'total_revenue' => $totalRevenue,
                    'total_bookings' => $totalBookings,
                    'total_room_nights' => $totalNights,
                    'average_daily_rate' => $totalNights > 0 ? $totalRevenue / $totalNights : 0,
                    'revenue_per_available_room' => calculateRevPAR($db, $report, $totalRevenue)
                ],
                'meta' => [
                    'start_date' => $report->getStartDate(),
                    'end_date' => $report->getEndDate(),
                    'hotel_id' => $report->getHotelId(),
                    'group_by' => $group_by,
                    'generated_at' => date('Y-m-d H:i:s')
                ]
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error generating revenue report: ' . $e->getMessage()
    ]);
}

/**
 * Get revenue data from the database
 */
function getRevenueData($db, $report, $group_by = 'day') {
    $where = $report->getWhereClause();
    
    // Determine the GROUP BY clause based on the group_by parameter
    $group_by_clause = '';
    $select_fields = '';
    
    switch ($group_by) {
        case 'week':
            $select_fields = "
                CONCAT('Week ', WEEK(b.check_in, 1), ' (', 
                       DATE_FORMAT(DATE_ADD(b.check_in, INTERVAL(-WEEKDAY(b.check_in)) DAY), '%Y-%m-%d'), ' to ', 
                       DATE_FORMAT(DATE_ADD(b.check_in, INTERVAL(6-WEEKDAY(b.check_in)) DAY), '%Y-%m-%d'),
                       ')') AS period,
                WEEK(b.check_in, 1) AS week_number,
                YEAR(b.check_in) AS year";
            $group_by_clause = "WEEK(b.check_in, 1), YEAR(b.check_in)";
            break;
            
        case 'month':
            $select_fields = "
                DATE_FORMAT(b.check_in, '%Y-%m') AS period,
                MONTHNAME(b.check_in) AS month_name,
                YEAR(b.check_in) AS year";
            $group_by_clause = "YEAR(b.check_in), MONTH(b.check_in)";
            break;
            
        case 'hotel':
            $select_fields = "
                h.name AS period,
                h.id AS hotel_id";
            $group_by_clause = "h.id, h.name";
            break;
            
        case 'room_type':
            $select_fields = "
                rt.name AS period,
                rt.id AS room_type_id";
            $group_by_clause = "rt.id, rt.name";
            break;
            
        case 'day':
        default:
            $select_fields = "
                DATE(b.check_in) AS period,
                DAYNAME(b.check_in) AS day_name";
            $group_by_clause = "DATE(b.check_in)";
            break;
    }
    
    // Main query to get revenue data
    $query = "
        SELECT 
            {$select_fields},
            COUNT(DISTINCT b.id) AS bookings_count,
            SUM(DATEDIFF(LEAST(b.check_out, :end_date), 
                        GREATEST(b.check_in, :start_date)) + 1) AS room_nights,
            SUM(b.total_amount) AS total_revenue,
            SUM(b.total_amount) / NULLIF(SUM(DATEDIFF(LEAST(b.check_out, :end_date), 
                                              GREATEST(b.check_in, :start_date)) + 1), 0) AS average_daily_rate
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id
        JOIN hotels h ON r.hotel_id = h.id
        WHERE 
            b.status IN ('confirmed', 'checked_in', 'completed')
            AND b.check_in <= :end_date
            AND b.check_out >= :start_date
            " . $where['where'] . "
        GROUP BY {$group_by_clause}
        ORDER BY period ASC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($where['params']);
    
    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[] = [
            'Period' => $row['period'],
            'Revenue' => (float)$row['total_revenue'],
            'Bookings' => (int)$row['bookings_count'],
            'Room Nights' => (int)$row['room_nights'],
            'Average Daily Rate' => (float)$row['average_daily_rate'],
            'Revenue per Available Room' => calculateRevPAR($db, $report, $row['total_revenue'], $row['period'], $group_by)
        ];
    }
    
    return $result;
}

/**
 * Calculate Revenue per Available Room (RevPAR)
 */
function calculateRevPAR($db, $report, $revenue, $period = null, $group_by = null) {
    // If we're grouping by hotel, we need to calculate available rooms per hotel
    if ($group_by === 'hotel' && $period) {
        $stmt = $db->prepare("SELECT COUNT(*) as room_count FROM rooms WHERE hotel_id = :hotel_id AND status = 'available'");
        $stmt->execute([':hotel_id' => $period]);
        $room_count = $stmt->fetch(PDO::FETCH_ASSOC)['room_count'];
    } else {
        // For other groupings, get total available rooms
        $query = "SELECT COUNT(*) as room_count FROM rooms WHERE status = 'available'";
        $params = [];
        
        if ($report->getHotelId()) {
            $query .= " AND hotel_id = :hotel_id";
            $params[':hotel_id'] = $report->getHotelId();
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $room_count = $stmt->fetch(PDO::FETCH_ASSOC)['room_count'];
    }
    
    if ($room_count == 0) return 0;
    
    // Calculate number of days in the period
    $days = 1;
    if ($group_by === 'week') {
        $days = 7;
    } elseif ($group_by === 'month') {
        $date = new DateTime($period . '-01');
        $days = $date->format('t');
    } elseif ($group_by === 'day') {
        $days = 1;
    } else {
        // For other groupings, use the entire date range
        $start = new DateTime($report->getStartDate());
        $end = new DateTime($report->getEndDate());
        $days = $start->diff($end)->days + 1;
    }
    
    $available_room_nights = $room_count * $days;
    
    return $available_room_nights > 0 ? $revenue / $available_room_nights : 0;
}

/**
 * Get the hotel ID for a manager
 */
function getManagerHotelId($db, $manager_id) {
    $stmt = $db->prepare("SELECT id FROM hotels WHERE manager_id = :manager_id LIMIT 1");
    $stmt->execute([':manager_id' => $manager_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['id'] : null;
}

function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}
?>
