<?php
require 'vendor/autoload.php';

function getSheetService() {
    $client = new \Google_Client();
    $client->setApplicationName('Mama Call USSD');
    $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
   $client->setAuthConfig(__DIR__ . '/credentials.json');
    $service = new \Google_Service_Sheets($client);
    return $service;
}

function getSheetId() {
    // Replace with your Google Sheet ID (from URL)
    return '1XtSfnZTe6fecsFci1feq3M-0keSHUA9AThrCCHAvWII';
}

function appendRow($row) {
    $service = getSheetService();
    $spreadsheetId = getSheetId();
    $range = 'Sheet1!A1'; // Adjust if sheet name is different
    $body = new \Google_Service_Sheets_ValueRange([
        'values' => [$row]
    ]);
    $params = ['valueInputOption' => 'USER_ENTERED'];
    $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
}

function updateRow($session_id, $column, $value) {
    $service = getSheetService();
    $spreadsheetId = getSheetId();
    $sheetData = $service->spreadsheets_values->get($spreadsheetId, 'Sheet1!A2:J'); // skip header
    $rows = $sheetData->getValues();
    $rowIndex = null;

    foreach ($rows as $i => $r) {
        if ($r[0] === $session_id) {
            $rowIndex = $i + 2; // +2 because A2 = row 2
            break;
        }
    }

    if ($rowIndex !== null) {
        $colIndex = ['session_id','msisdn','user_input','step','full_name','status','months_pregnant','baby_age','state','consent'][$column];
        $range = "Sheet1!" . chr(65 + $column) . $rowIndex;
        $body = new \Google_Service_Sheets_ValueRange(['values' => [[$value]]]);
        $service->spreadsheets_values->update($spreadsheetId, $range, $body, ['valueInputOption' => 'USER_ENTERED']);
    }
}
?>
