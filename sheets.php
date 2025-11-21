<?php
// Support both GET and POST
$data = array_merge($_GET, $_POST);

// Log raw request
file_put_contents("ussd_debug.log", print_r($data, true), FILE_APPEND);

// Extract variables
$msisdn = $data['session_msisdn'] ?? '';
$session_id = $data['session_id'] ?? '';
$user_input = trim($data['session_msg'] ?? '');
$session_operation = $data['session_operation'] ?? '';
$step = explode('*', $user_input);

require 'vendor/autoload.php';
use Google\Client;
use Google\Service\Sheets;

function getSheetService() {
    static $service = null;

    if ($service === null) {
        $client = new Client();
        $client->setApplicationName("Mama's Call USSD");
        $client->setScopes([Sheets::SPREADSHEETS]);
        
        // Credentials pulled from environment
        $credentialsJson = getenv('GOOGLE_APPLICATION_CREDENTIALS_JSON');

        if (!$credentialsJson) {
            throw new Exception("Google credential JSON not found in environment variable.");
        }

        $client->setAuthConfig(json_decode($credentialsJson, true));
        $service = new Sheets($client);
    }

    return $service;
}

function getSheetId() {
    return getenv('GOOGLE_SHEET_ID'); // Ensure this is set in Render
}

/*  
=========================================================
  DATA STRUCTURE IN SHEET (Sessions Sheet)
=========================================================
A  Timestamp
B  Session ID
C  MSISDN
D  Step Number
E  Full Name
F  Status
G  Months Pregnant
H  Baby Age
I  State
J  Consent
K  Input History (1*2*3...)
=========================================================
*/

/**
 * Appends a new row to the Sessions sheet
 */
function appendRow($rowData) {
    $service = getSheetService();
    $sheetId = getSheetId();

    $range = 'Sessions!A:K';
    $body = new Sheets\ValueRange([
        'values' => [$rowData]
    ]);

    $params = ['valueInputOption' => 'RAW'];

    $service->spreadsheets_values->append(
        $sheetId,
        $range,
        $body,
        $params
    );
}

/**
 * Updates a specific column in the row matching session_id.
 * 
 * @param $sessionId  session_id to find in column B
 * @param $columnIndex the column number (0 = A, 1 = B...)
 * @param $newValue the value to write
 */
function updateRow($sessionId, $columnIndex, $newValue) {
    $service = getSheetService();
    $sheetId = getSheetId();

    // Read entire Sessions sheet
    $response = $service->spreadsheets_values->get($sheetId, 'Sessions!A2:K');
    $rows = $response->getValues() ?? [];

    foreach ($rows as $i => $row) {
        if (isset($row[1]) && $row[1] == $sessionId) {
            $sheetRowNumber = $i + 2;
            $columnLetter = chr(65 + $columnIndex); // 0=A, 1=B ...

            $range = "Sessions!{$columnLetter}{$sheetRowNumber}";
            $body = new Sheets\ValueRange(['values' => [[$newValue]]]);

            $service->spreadsheets_values->update(
                $sheetId,
                $range,
                $body,
                ['valueInputOption' => 'RAW']
            );

            return;
        }
    }

    // If no row found → log automatically
    logError($sessionId, '', 'Sheets', "Session not found while updating column $columnIndex");
}

/**
 * Log errors into the Errors sheet
 */
function logError($sessionId, $msisdn, $module, $message) {
    $service = getSheetService();
    $sheetId = getSheetId();

    $timestamp = date('Y-m-d H:i:s');

    $body = new Sheets\ValueRange([
        'values' => [[
            $timestamp,
            $sessionId,
            $msisdn,
            $module,
            $message
        ]]
    ]);

    $service->spreadsheets_values->append(
        $sheetId,
        'Errors!A:E',
        $body,
        ['valueInputOption' => 'RAW']
    );
}
?>
