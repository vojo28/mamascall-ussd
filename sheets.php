<?php
require 'vendor/autoload.php';

/**
 * Build Sheets API client using environment variables
 */
function getSheetService() {

    $clientEmail  = getenv('GOOGLE_CLIENT_EMAIL');
    $privateKey   = getenv('GOOGLE_PRIVATE_KEY');
    $projectId    = getenv('GOOGLE_PROJECT_ID');

    if (!$clientEmail || !$privateKey || !$projectId) {
        throw new Exception("Missing Google environment variables.");
    }

    // Fix escaped newlines
    $privateKey = str_replace("\\n", "\n", $privateKey);

    // Build credentials JSON
    $credentials = [
        "type"                        => "service_account",
        "project_id"                  => $projectId,
        "private_key_id"              => "unused",
        "private_key"                 => $privateKey,
        "client_email"                => $clientEmail,
        "client_id"                   => "unused",
        "auth_uri"                    => "https://accounts.google.com/o/oauth2/auth",
        "token_uri"                   => "https://oauth2.googleapis.com/token",
        "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
        "client_x509_cert_url"        => ""
    ];

    // Save into temporary file (works on Render)
    $tempFile = sys_get_temp_dir() . '/google_creds.json';
    file_put_contents($tempFile, json_encode($credentials));

    $client = new \Google_Client();
    $client->setApplicationName('Mama Call USSD');
    $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
    $client->setAuthConfig($tempFile);

    return new \Google_Service_Sheets($client);
}

/**
 * Spreadsheet ID
 */
function getSheetId() {
    return '1XtSfnZTe6fecsFci1feq3M-0keSHUA9AThrCCHAvWII';
}

/**
 * Append a row and auto-insert header if sheet is empty
 */
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

/**
 * Update or append a row based on session_id
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

    $headers = [
        0 => 'session_id',
        1 => 'msisdn',
        2 => 'full_name',
        3 => 'status',
        4 => 'months_pregnant',
        5 => 'baby_age',
        6 => 'state',
        7 => 'consent',
        8 => 'user_input'
    ];

    if (!isset($headers[$column])) {
        throw new Exception("Invalid column number: $column");
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
?>
