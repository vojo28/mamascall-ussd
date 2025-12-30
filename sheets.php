<?php
require 'vendor/autoload.php';

use Google\Client;
use Google\Service\Sheets;

/**
 * Initialize and return Google Sheets service
 */
function getSheetService() {
    static $service = null;

    if ($service === null) {
        $client = new Client();
        $client->setApplicationName("Mama's Call USSD");
        $client->setScopes([Sheets::SPREADSHEETS]);

        $credentialsJson = getenv('GOOGLE_APPLICATION_CREDENTIALS_JSON');
        if (!$credentialsJson) {
            throw new Exception("Google credentials not found in environment variable.");
        }

        $client->setAuthConfig(json_decode($credentialsJson, true));
        $service = new Sheets($client);
    }

    return $service;
}

/**
 * Get the Sheet ID from environment variable
 */
function getSheetId() {
    return getenv('GOOGLE_SHEET_ID');
}

/**
 * Append a new row to the Sessions sheet
 */
function appendRow(array $rowData) {
    $service = getSheetService();
    $sheetId = getSheetId();
    $range = 'Sessions!A:K';
    $body = new Sheets\ValueRange(['values' => [$rowData]]);
    $params = ['valueInputOption' => 'RAW'];

    $service->spreadsheets_values->append($sheetId, $range, $body, $params);
}

/**
 * Update a single column for a specific session_id
 * Only fetches rows until it finds the matching session_id
 */
function updateRow(string $sessionId, int $columnIndex, string $newValue) {
    $service = getSheetService();
    $sheetId = getSheetId();

    // Fetch only the session ID column and row numbers
    $range = 'Sessions!B2:B'; // column B = session_id
    $response = $service->spreadsheets_values->get($sheetId, $range);
    $rows = $response->getValues() ?? [];

    foreach ($rows as $i => $row) {
        if (($row[0] ?? '') === $sessionId) {
            $sheetRow = $i + 2; // adjust for header row
            $columnLetter = chr(65 + $columnIndex); // 0=A, 1=B, ...
            $updateRange = "Sessions!{$columnLetter}{$sheetRow}";
            $body = new Sheets\ValueRange(['values' => [[$newValue]]]);
            $service->spreadsheets_values->update($sheetId, $updateRange, $body, ['valueInputOption' => 'RAW']);
            return;
        }
    }

    // If session_id not found, log an error
    logError($sessionId, '', 'Sheets', "Session ID not found while updating column $columnIndex");
}

/**
 * Log errors into Errors sheet
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

    $service->spreadsheets_values->append($sheetId, 'Errors!A:E', $body, ['valueInputOption' => 'RAW']);
}
?>
