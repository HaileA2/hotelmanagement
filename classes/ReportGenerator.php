<?php
class ReportGenerator {
    protected $db;
    protected $start_date;
    protected $end_date;
    protected $hotel_id;
    
    public function __construct($db, $start_date = null, $end_date = null, $hotel_id = null) {
        $this->db = $db;
        $this->start_date = $start_date ?: date('Y-m-01'); // Default to start of current month
        $this->end_date = $end_date ?: date('Y-m-t'); // Default to end of current month
        $this->hotel_id = $hotel_id;
    }
    
    protected function validateDates() {
        $start = new DateTime($this->start_date);
        $end = new DateTime($this->end_date);
        $interval = $start->diff($end);
        
        // Limit date range to 1 year
        if ($interval->y >= 1 && $interval->m > 0) {
            throw new Exception("Date range cannot exceed 1 year.");
        }
        
        if ($start > $end) {
            throw new Exception("Start date cannot be after end date.");
        }
    }
    
    protected function getWhereClause() {
        $where = [];
        $params = [
            ':start_date' => $this->start_date,
            ':end_date' => $this->end_date
        ];
        
        if ($this->hotel_id) {
            $where[] = "r.hotel_id = :hotel_id";
            $params[':hotel_id'] = $this->hotel_id;
        }
        
        return [
            'where' => !empty($where) ? 'AND ' . implode(' AND ', $where) : '',
            'params' => $params
        ];
    }
    
    public function toCSV($data, $filename = 'report.csv') {
        if (empty($data)) {
            return '';
        }
        
        // Open output stream
        $output = fopen('php://temp', 'w');
        
        // Add BOM for Excel compatibility
        fputs($output, "\xEF\xBB\xBF");
        
        // Add headers
        fputcsv($output, array_keys($data[0]));
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        // Return the CSV data
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    public function toPDF($data, $title, $filename = 'report.pdf') {
        // This is a simplified version. In a real application, you would use a library like TCPDF or mPDF
        // For now, we'll just return a simple HTML representation
        
        $html = "<html><head><title>$title</title>";
        $html .= "<style>body{font-family:Arial,sans-serif;} table{width:100%;border-collapse:collapse;} th,td{padding:8px;text-align:left;border-bottom:1px solid #ddd;} th{background-color:#f2f2f2;}</style>";
        $html .= "</head><body>";
        $html .= "<h2>$title</h2>";
        $html .= "<p>Report Period: {$this->start_date} to {$this->end_date}";
        $html .= $this->hotel_id ? " | Hotel ID: {$this->hotel_id}</p>" : "</p>";
        
        if (empty($data)) {
            $html .= "<p>No data available for the selected period.</p>";
        } else {
            $html .= "<table>";
            
            // Table header
            $html .= "<tr>";
            foreach (array_keys($data[0]) as $header) {
                $html .= "<th>" . htmlspecialchars($header) . "</th>";
            }
            $html .= "</tr>";
            
            // Table rows
            foreach ($data as $row) {
                $html .= "<tr>";
                foreach ($row as $cell) {
                    $html .= "<td>" . htmlspecialchars($cell) . "</td>";
                }
                $html .= "</tr>";
            }
            
            $html .= "</table>";
        }
        
        $html .= "<p style='margin-top:20px;font-size:0.8em;'>Generated on: " . date('Y-m-d H:i:s') . "</p>";
        $html .= "</body></html>";
        
        return $html;
    }
    
    public function sendDownloadHeaders($content, $filename, $contentType = 'text/csv') {
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        echo $content;
        exit;
    }
}
