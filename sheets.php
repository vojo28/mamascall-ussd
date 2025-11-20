<?php
require 'vendor/autoload.php';

/**
 * Initialize Google Sheets client from environment variable JSON
 */
function getSheetService() {
    $json = getenv("GOOGLE_APPLICATION_CREDENTIALS_JSON");

    if (!$json) {
        throw new Exception("GOOGLE_APPLICATION_CREDENTIALS_JSON not found.");
    }

    $credentials = json_decode($json, true);

    if (!$credentials) {
        throw new Exception("Invalid JSON in GOOGLE_APPLICATION_CREDENTIALS_JSON.");
    }

    $client = new \Google_Client();
    $client->setApplicationName('Mama Call USSD');
    $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
    $client->setAuthConfig($credentials);

    return new \Google_Service_Sheets($client);
}

/**
 * Google Sheet ID
 */
function getSheetId() {
    return '1XtSfnZTe6fecsFci1feq3M-0keSHUA9AThrCCHAvWII';
}

/**
 * Append a row. Automatically adds headers if sheet is empty.
 */
function appendRow($row) {
    $service = getSheetService();
    $spreadsheetId = getSheetId();

    // Prepend timestamp
    $timestamp = date('Y-m-d H:i:s');
    array_unshift($row, $timestamp);

    // Check if sheet is empty
    $sheetData = $service->spreadsheets_values->get($spreadsheetId, 'Sessions!A1:J');
    $rows = $sheetData->getValues() ?? [];

    if (empty($rows)) {
        $headers = [
            'timestamp', 'session_id','msisdn','full_name','status',
            'months_pregnant','baby_age','state','consent','user_input'
        ];
        $body = new \Google_Service_Sheets_ValueRange(['values' => [$headers]]);
        $service->spreadsheets_values->append(
            $spreadsheetId,
            'Sessions!A1:J1',
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    // Append the row
    $body = new \Google_Service_Sheets_ValueRange(['values' => [$row]]);
    $service->spreadsheets_values->append(
        $spreadsheetId,
        'Sessions!A2:J',
        $body,
        ['valueInputOption' => 'USER_ENTERED']
    );
}

/**
 * Update a specific column for a session_id. If session doesn't exist, append a new row.
 * Column indices are zero-based, 0 = timestamp
 */
function updateRow($session_id, $column, $value) {
    $service = getSheetService();
    $spreadsheetId = getSheetId();

    $sheetData = $service->spreadsheets_values->get($spreadsheetId, 'Sessions!A2:J');
    $rows = $sheetData->getValues() ?? [];

    $rowIndex = null;
    foreach ($rows as $i => $r) {
        if (($r[1] ?? '') === $session_id) { // column 1 = session_id
            $rowIndex = $i + 2; // A2 = row 2
            break;
        }
    }

    if ($rowIndex !== null) {
        // Update the column
        $range = "Sessions!" . chr(65 + $column) . $rowIndex;
        $body = new \Google_Service_Sheets_ValueRange(['values' => [[$value]]]);
        $service->spreadsheets_values->update(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );

        // Update timestamp automatically
        if ($column != 0) { // skip updating timestamp if we just updated timestamp
            $timestamp = date('Y-m-d H:i:s');
            $service->spreadsheets_values->update(
                $spreadsheetId,
                "Sessions!A$rowIndex",
                new \Google_Service_Sheets_ValueRange(['values' => [[$timestamp]]]),
                ['valueInputOption' => 'USER_ENTERED']
            );
        }
    } else {
        // Row doesn't exist, create new row
        $newRow = array_fill(0, 10, ''); // 10 columns including timestamp
        $newRow[$column] = $value;
        $newRow[1] = $session_id; // session_id column
        appendRow($newRow);
    }
}

/**
 * Log errors to the Errors sheet and optionally send email alerts
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
            'Errors!A:E',
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );

        // Optional email alert
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
    if (!$to) return;

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
