<?php
require 'vendor/autoload.php';

/**
 * Get Google Sheets service using environment variables
 */
function getSheetService() {
    $client = new \Google_Client();
    $client->setApplicationName('Mama Call USSD');
    $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);

    // Construct credentials array from environment variables
    $credentials = [
        "type"                        => "service_account",
        "project_id"                  => getenv("GOOGLE_PROJECT_ID"),
        "private_key_id"              => getenv("GOOGLE_PRIVATE_KEY_ID"),
        "private_key"                 => str_replace("\\n", "\n", getenv("GOOGLE_PRIVATE_KEY")),
        "client_email"                => getenv("GOOGLE_CLIENT_EMAIL"),
        "client_id"                   => getenv("GOOGLE_CLIENT_ID"),
        "auth_uri"                    => "https://accounts.google.com/o/oauth2/auth",
        "token_uri"                   => "https://oauth2.googleapis.com/token",
        "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
        "client_x509_cert_url"        => getenv("GOOGLE_CLIENT_X509_CERT_URL")
    ];

    $client->setAuthConfig($credentials);

    return new \Google_Service_Sheets($client);
}

/**
 * Spreadsheet ID
 */
function getSheetId() {
    return '1XtSfnZTe6fecsFci1feq3M-0keSHUA9AThrCCHAvWII';
}

/**
 * Append a row. Adds header if empty.
 */
function appendRow($row) {
    $service = getSheetService();
    $spreadsheetId = getSheetId();

    $sheetData = $service->spreadsheets_values->get($spreadsheetId, 'Sessions!A1:I');
    $rows = $sheetData->getValues() ?? [];

    if (empty($rows)) {
        $headers = ['session_id','msisdn','full_name','status','months_pregnant','baby_age','state','consent','user_input'];
        $body = new \Google_Service_Sheets_ValueRange(['values' => [$headers]]);
        $service->spreadsheets_values->append($spreadsheetId, 'Sessions!A1:I1', $body, ['valueInputOption' => 'USER_ENTERED']);
    }

    $body = new \Google_Service_Sheets_ValueRange(['values' => [$row]]);
    $service->spreadsheets_values->append($spreadsheetId, 'Sessions!A2:I', $body, ['valueInputOption' => 'USER_ENTERED']);
}

/**
 * Update a column for a session_id. Append if missing.
 */
function updateRow($session_id, $column, $value) {
    $service = getSheetService();
    $spreadsheetId = getSheetId();

    $sheetData = $service->spreadsheets_values->get($spreadsheetId, 'Sessions!A2:I');
    $rows = $sheetData->getValues() ?? [];

    $rowIndex = null;
    foreach ($rows as $i => $r) {
        if (($r[0] ?? '') === $session_id) {
            $rowIndex = $i + 2;
            break;
        }
    }

    $colIndexMap = ['session_id','msisdn','full_name','status','months_pregnant','baby_age','state','consent','user_input'];
    if (!isset($colIndexMap[$column])) {
        throw new Exception("Invalid column index: $column");
    }

    if ($rowIndex !== null) {
        $range = "Sessions!" . chr(65 + $column) . $rowIndex;
        $body = new \Google_Service_Sheets_ValueRange(['values' => [[$value]]]);
        $service->spreadsheets_values->update($spreadsheetId, $range, $body, ['valueInputOption' => 'USER_ENTERED']);
    } else {
        $newRow = array_fill(0, 9, '');
        $newRow[$column] = $value;
        appendRow($newRow);
    }
}
?>
