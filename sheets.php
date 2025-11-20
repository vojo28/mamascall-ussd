<?php
require 'vendor/autoload.php';

/**
 * Initialize Google Sheets client
 */
function getSheetService() {
    $client = new \Google_Client();
    $client->setApplicationName('Mama Call USSD');
    $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
    $client->setAuthConfig('/etc/secrets/credentials.json'); // Ensure your secret is correctly mounted
    $service = new \Google_Service_Sheets($client);
    return $service;
}

/**
 * Return the spreadsheet ID
 */
function getSheetId() {
    return '1XtSfnZTe6fecsFci1feq3M-0keSHUA9AThrCCHAvWII';
}

/**
 * Append a row. Adds header row if sheet is empty.
 */
function appendRow($row) {
    $service = getSheetService();
    $spreadsheetId = getSheetId();

    // Check if the sheet has any data
    $sheetData = $service->spreadsheets_values->get($spreadsheetId, 'Sessions!A1:I');
    $rows = $sheetData->getValues() ?? [];

    // Add headers if sheet is empty
    if (empty($rows)) {
        $headers = ['session_id','msisdn','full_name','status','months_pregnant','baby_age','state','consent','user_input'];
        $body = new \Google_Service_Sheets_ValueRange(['values' => [$headers]]);
        $service->spreadsheets_values->append($spreadsheetId, 'Sessions!A1:I1', $body, ['valueInputOption' => 'USER_ENTERED']);
    }

    // Append the row
    $body = new \Google_Service_Sheets_ValueRange(['values' => [$row]]);
    $service->spreadsheets_values->append($spreadsheetId, 'Sessions!A2:I', $body, ['valueInputOption' => 'USER_ENTERED']);
}

/**
 * Update a specific column for a session_id. If session doesn't exist, append a new row
 */
function updateRow($session_id, $column, $value) {
    $service = getSheetService();
    $spreadsheetId = getSheetId();

    // Fetch sheet rows
    $sheetData = $service->spreadsheets_values->get($spreadsheetId, 'Sessions!A2:I');
    $rows = $sheetData->getValues() ?? [];

    $rowIndex = null;
    foreach ($rows as $i => $r) {
        if (($r[0] ?? '') === $session_id) {
            $rowIndex = $i + 2; // A2 = row 2
            break;
        }
    }

    $colIndexMap = ['session_id','msisdn','full_name','status','months_pregnant','baby_age','state','consent','user_input'];
    if (!isset($colIndexMap[$column])) {
        throw new Exception("Invalid column index: $column");
    }

    if ($rowIndex !== null) {
        // Update existing row
        $range = "Sessions!" . chr(65 + $column) . $rowIndex;
        $body = new \Google_Service_Sheets_ValueRange(['values' => [[$value]]]);
        $service->spreadsheets_values->update($spreadsheetId, $range, $body, ['valueInputOption' => 'USER_ENTERED']);
    } else {
        // Append new row
        $newRow = array_fill(0, 9, '');
        $newRow[$column] = $value;
        appendRow($newRow);
    }
}
?>
