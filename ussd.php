<?php
// USSD endpoint for Mama's Call

$data = array_merge($_GET, $_POST);

// Log raw request immediately
file_put_contents("ussd_debug.log", print_r($data, true), FILE_APPEND);

$msisdn = $data['session_msisdn'] ?? '';
$session_id = $data['session_id'] ?? '';
$user_input = trim($data['session_msg'] ?? '');
$session_operation = $data['session_operation'] ?? '';
$step = explode('*', $user_input);

// Include Google Sheets functions
require 'sheets.php';

// --- Immediate response function ---
function respond($message) {
    echo $message;
    flush();
}

// --- Start of USSD Flow ---
$response = 'END Something went wrong. Please try again later.';

switch(count($step)) {
    case 0:
        $response = "CON Welcome to Mama’s Call ❤️\n1. Register\n2. Learn More";
        break;

    case 1:
        if ($step[0] == '1') {
            $response = "CON Please enter your full name:";
        } elseif ($step[0] == '2') {
            $response = "END Mama’s Call is Nigeria’s first 24/7 maternal care hotline. We've got you, mama.\nVisit https://mamascall.org";
        } else {
            $response = "END Invalid option. Try again.";
        }
        break;

    case 2:
        $response = "CON Select your status:\n1. Pregnant\n2. Nursing mother\n3. Father / Partner";
        break;

    case 3:
        $status = $step[2];
        if ($status == '1') $response = "CON How many months pregnant are you?\n1. 1–3 months\n2. 4–6 months\n3. 7–9 months\n4. Not sure";
        elseif ($status == '2') $response = "CON How old is your baby?\n1. 0–6 months\n2. 7–12 months\n3. 1–3 years\n4. Not sure";
        else $response = "CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others";
        break;

    case 4:
        $response = "CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others";
        break;

    case 5:
        $response = "CON Do you agree to receive helpful health messages from Mama’s Call?\n1. Yes\n2. No";
        break;

    case 6:
        $response = "END Thank you ❤️\nYou’ve successfully joined Mama’s Call. We’ll reach out soon with helpful updates.";
        break;

    default:
        $response = "END Something went wrong. Please try again later.";
}

respond($response);

// --- After response: save to Sheets asynchronously ---
register_shutdown_function(function() use ($msisdn, $session_id, $user_input) {
    try {
        $input_history = explode('*', $user_input);

        // Fetch existing session
        $service = getSheetService();
        $sheetId = getSheetId();
        $sheetData = $service->spreadsheets_values->get($sheetId, 'Sessions!A2:K');
        $rows = $sheetData->getValues() ?? [];

        $sessionIndex = null;
        $history = '';
        foreach ($rows as $i => $r) {
            if (($r[1] ?? '') === $session_id) {
                $sessionIndex = $i + 2;
                $history = $r[10] ?? '';
                break;
            }
        }

        $full_history = $sessionIndex ? explode('*', $history) : [];
        $full_history = array_merge($full_history, $input_history);

        if ($sessionIndex) {
            updateRow($session_id, 10, implode('*', $full_history));
            updateRow($session_id, 3, count($full_history));
        } else {
            $timestamp = date('Y-m-d H
