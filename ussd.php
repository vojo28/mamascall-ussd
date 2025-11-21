<?php
require 'sheets.php';

// Get POST values safely
$msisdn = $_POST['session_msisdn'] ?? '';
$session_id = $_POST['session_id'] ?? '';
$user_input = trim($_POST['session_msg'] ?? '');

// Fetch existing session (check session_id in sheet)
$service = getSheetService();
$sheetId = getSheetId();
$sheetData = $service->spreadsheets_values->get($sheetId, 'Sessions!A2:J'); // A:J includes timestamp column
$rows = $sheetData->getValues() ?? [];

// Try to find existing session row
$sessionIndex = null;
$history = '';
foreach ($rows as $i => $r) {
    if (($r[1] ?? '') === $session_id) { // session_id is in column B (index 1)
        $sessionIndex = $i + 2; // Google Sheets row number
        $history = $r[9] ?? ''; // user_input column (J)
        break;
    }
}

// Track input history
$input_history = $sessionIndex ? explode('*', $history) : [];
if ($user_input !== '') $input_history[] = $user_input;

// Update or append session
if ($sessionIndex) {
    updateRow($session_id, 9, implode('*', $input_history)); // user_input column
    updateRow($session_id, 3, count($input_history));        // step column
} else {
    $timestamp = date('Y-m-d H:i:s');
    appendRow([$timestamp, $session_id, $msisdn, '', '', '', '', '', '', implode('*', $input_history)]);
}

// Process USSD Flow
switch (count($input_history)) {
    case 1:
        if ($input_history[0] == '1') {
            echo "CON Please enter your full name:";
        } elseif ($input_history[0] == '2') {
            echo "END Mama’s Call is Nigeria’s first 24/7 maternal care hotline. We've got you, mama.\nVisit https://mamascall.org";
        } else {
            echo "END Invalid option. Try again.";
        }
        break;

    case 2:
        $name = htmlspecialchars($input_history[1]);
        updateRow($session_id, 2, $name); // full_name column
        echo "CON Select your status:\n1. Pregnant\n2. Nursing mother\n3. Father / Partner";
        break;

    case 3:
        $status = $input_history[2];
        updateRow($session_id, 4, $status); // status column
        if ($status == '1') echo "CON How many months pregnant are you?\n1. 1–3 months\n2. 4–6 months\n3. 7–9 months\n4. Not sure";
        elseif ($status == '2') echo "CON How old is your baby?\n1. 0–6 months\n2. 7–12 months\n3. 1–3 years\n4. Not sure";
        else echo "CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others";
        break;

    case 4:
        $status = $input_history[2];
        $answer = $input_history[3];
        if ($status == '1') updateRow($session_id, 5, $answer); // months_pregnant
        elseif ($status == '2') updateRow($session_id, 6, $answer); // baby_age
        else updateRow($session_id, 7, $answer); // state
        echo "CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others";
        break;

    case 5:
        $state = $input_history[4];
        updateRow($session_id, 7, $state); // state
        echo "CON Do you agree to receive helpful health messages from Mama’s Call?\n1. Yes\n2. No";
        break;

    case 6:
        $consent = $input_history[5];
        updateRow($session_id, 8, $consent); // consent
        updateRow($session_id, 3, count($input_history)); // step
        echo "END Thank you ❤️\nYou’ve successfully joined Mama’s Call. We’ll reach out soon with helpful updates.";
        break;

    default:
        echo "END Something went wrong. Please try again later.";
        logError($session_id, $msisdn, 'USSD Flow', 'Invalid step count: '.count($input_history));
}
?>
