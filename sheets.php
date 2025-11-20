<?php
require 'vendor/autoload.php';

function getSheetService() {

    // Read the entire JSON key from one environment variable
    $json = getenv("GOOGLE_APPLICATION_CREDENTIALS_JSON");

    if (!$json) {
        throw new Exception("GOOGLE_APPLICATION_CREDENTIALS_JSON not found.");
    }

    // Decode JSON
    $credentials = json_decode($json, true);

    if (!$credentials) {
        throw new Exception("Invalid JSON in GOOGLE_APPLICATION_CREDENTIALS_JSON.");
    }

    // Create Google Client
    $client = new \Google_Client();
    $client->setApplicationName('Mama Call USSD');
    $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
    $client->setAuthConfig($credentials);

    return new \Google_Service_Sheets($client);
}

function getSheetId() {
    return '1XtSfnZTe6fecsFci1feq3M-0keSHUA9AThrCCHAvWII';
}

function appendRow($row) {
    $service = getSheetService();
    $spreadsheetId = getSheetId();

    $sheetData = $service->spreadsheets_values->get($spreadsheetId, 'Sessions!A1:I');
    $rows = $sheetData->getValues() ?? [];

    if (empty($rows)) {
        $headers = [
            'session_id','msisdn','full_name','status',
            'months_pregnant','baby_age','state','consent','user_input'
        ];
        $body = new \Google_Service_Sheets_ValueRange(['values' => [$headers]]);
        $service->spreadsheets_values->append(
            $spreadsheetId,
            'Sessions!A1:I1',
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    $body = new \Google_Service_Sheets_ValueRange(['values' => [$row]]);
    $service->spreadsheets_values->append(
        $spreadsheetId,
        'Sessions!A2:I',
        $body,
        ['valueInputOption' => 'USER_ENTERED']
    );
}

function updateRow($session_id, $column, $value) {
    $service = getSheetService();
    $spreadsheetId = getSheetId();

    $sheetData = $service->spreadsheets_values->get($spreadsheetId, 'Sessions!A2:I');
    $rows = $sheetData->getValues() ?? [];

    // Find existing row
    $rowIndex = null;
    foreach ($rows as $i => $r) {
        if (($r[0] ?? '') === $session_id) {
            $rowIndex = $i + 2;
            break;
        }
    }

    $colIndexMap = [
        'session_id','msisdn','full_name','status',
        'months_pregnant','baby_age','state','consent','user_input'
    ];

    if (!isset($colIndexMap[$column])) {
        throw new Exception("Invalid column index: $column");
    }

    if ($rowIndex !== null) {
        $range = "Sessions!" . chr(65 + $column) . $rowIndex;
        $body = new \Google_Service_Sheets_ValueRange(['values' => [[$value]]]);
        $service->spreadsheets_values->update(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
    } else {
        $newRow = array_fill(0, 9, '');
        $newRow[$column] = $value;
        appendRow($newRow);
    }
}

/**
 * Log errors to the Errors sheet
 */
function logError($session_id, $msisdn, $step, $error_message) {
    try {
        $service = getSheetService();
        $spreadsheetId = getSheetId();
        
        $timestamp = date('Y-m-d H:i:s');
        $row = [$timestamp, $session_id, $msisdn, $step, $error_message];
        
        $body = new \Google_Service_Sheets_ValueRange(['values' => [$row]]);
        $service->spreadsheets_values->append(
            $spreadsheetId,
            'Errors!A:E', // Tab name + range
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
        
        // Optional: send email alert
        sendErrorEmail($row);
        
    } catch (\Exception $e) {
        error_log("Failed to log error to sheet: " . $e->getMessage());
    }
}

/**
 * Send email alert when an error occurs
 */
function sendErrorEmail($row) {
    $to = getenv('ADMIN_EMAIL'); // Set your admin email in environment variables
    $subject = "USSD Service Error Alert";
    $message = "An error occurred in USSD session:\n\n";
    $message .= "Timestamp: " . $row[0] . "\n";
    $message .= "Session ID: " . $row[1] . "\n";
    $message .= "MSISDN: " . $row[2] . "\n";
    $message .= "Step: " . $row[3] . "\n";
    $message .= "Error: " . $row[4] . "\n";

    @mail($to, $subject, $message);
}

?>
