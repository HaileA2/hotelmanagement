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
    $format = isset($_GET['format']) ? strtolower($_GET['format']) : 'json';
    
    // If manager, they can only see their hotel's data
    if ($payload['role'] === 'Manager') {
        $hotel_id = getManagerHotelId($db, $payload['user_id']);
    }
    
    // Initialize report generator
    $report = new ReportGenerator($db, $start_date, $end_date, $hotel_id);

    // Get occupancy data
    $occupancyData = getOccupancyData($db, $report);
    
    // Format response based on requested format
    switch ($format) {
        case 'csv':
            $csv = $report->toCSV($occupancyData, 'occupancy_report_' . date('Y-m-d') . '.csv');
            $report->sendDownloadHeaders($csv, 'occupancy_report_' . date('Y-m-d') . '.csv');
            break;
            
        case 'pdf':
            $title = 'Occupancy Report' . ($hotel_id ? ' - Hotel ID: ' . $hotel_id : '');
            $pdf = $report->toPDF($occupancyData, $title, 'occupancy_report_' . date('Y-m-d') . '.pdf');
            $report->sendDownloadHeaders($pdf, 'occupancy_report_' . date('Y-m-d') . '.pdf', 'application/pdf');
            break;
            
        case 'json':
        default:
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $occupancyData,
                'meta' => [
                    'start_date' => $report->getStartDate(),
                    'end_date' => $report->getEndDate(),
                    'hotel_id' => $hotel_id,
                    'generated_at' => date('Y-m-d H:i:s')
                ]
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error generating occupancy report: ' . $e->getMessage()
    ]);
}

/**
 * Get occupancy data from the database
 */
function getOccupancyData($db, $report) {
    $where = $report->getWhereClause();
    
    // Query to get room nights by date and hotel
    $query = "
        SELECT 
            DATE(b.check_in) AS date,
            h.name AS hotel_name,
            COUNT(DISTINCT b.id) AS bookings_count,
            COUNT(DISTINCT r.id) AS rooms_occupied,
            (SELECT COUNT(DISTINCT id) FROM rooms WHERE status = 'available' " .
            ($report->getHotelId() ? " AND hotel_id = :hotel_id" : "") .
            ") AS total_rooms,
            ROUND((COUNT(DISTINCT r.id) /
                (SELECT COUNT(DISTINCT id) FROM rooms WHERE status = 'available' " .
                ($report->getHotelId() ? " AND hotel_id = :hotel_id2" : "") .
                ") * 100, 2) AS occupancy_rate
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN hotels h ON r.hotel_id = h.id
        WHERE 
            b.status IN ('confirmed', 'checked_in')
            AND b.check_in <= :end_date
            AND b.check_out >= :start_date
            " . $where['where'] . "
        GROUP BY DATE(b.check_in), h.id, h.name
        ORDER BY date ASC, h.name
    ";
    
    $params = $where['params'];
    if ($report->getHotelId()) {
        $params[':hotel_id2'] = $report->getHotelId();
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[] = [
            'Date' => $row['date'],
            'Hotel' => $row['hotel_name'],
            'Bookings' => (int)$row['bookings_count'],
            'Rooms Occupied' => (int)$row['rooms_occupied'],
            'Total Rooms' => (int)$row['total_rooms'],
            'Occupancy Rate %' => (float)$row['occupancy_rate']
        ];
    }
    
    return $result;
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
