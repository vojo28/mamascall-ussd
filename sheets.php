<?php
require 'sheets.php';

// Support both GET and POST
$data = array_merge($_GET, $_POST);

// Log raw request
file_put_contents("ussd_debug.log", print_r($data, true), FILE_APPEND);

// Extract variables
$msisdn = $data['session_msisdn'] ?? '';
$session_id = $data['session_id'] ?? '';
$user_input = trim($data['session_msg'] ?? '');
$session_operation = $data['session_operation'] ?? '';

// Split input history
$input_history = $user_input === '' ? [] : explode('*', $user_input);

// Fetch existing session step from sheet if exists
$step = count($input_history);

// Append/update session history in Sheets
if ($session_operation === 'begin' && $step === 0) {
    // New session
    $timestamp = date('Y-m-d H:i:s');
    appendRow([
        $timestamp,
        $session_id,
        $msisdn,
        0,   // step
        '',  // full_name
        '',  // status
        '',  // months_pregnant
        '',  // baby_age
        '',  // state
        '',  // consent
        '',  // input history
    ]);
} else {
    updateRow($session_id, 10, $user_input); // column K = input history
    updateRow($session_id, 3, $step);        // column D = step
}

// -------------------- USSD FLOW -------------------- //
switch ($step) {
    case 0:
        echo "CON Welcome to Mama’s Call ❤️\n1. Register\n2. Learn More";
        break;

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
        updateRow($session_id, 4, $name); // column E = full_name
        echo "CON Select your status:\n1. Pregnant\n2. Nursing mother\n3. Father / Partner";
        break;

    case 3:
        $status = $input_history[2];
        updateRow($session_id, 5, $status); // column F = status
        if ($status == '1') {
            echo "CON How many months pregnant are you?\n1. 1–3 months\n2. 4–6 months\n3. 7–9 months\n4. Not sure";
        } elseif ($status == '2') {
            echo "CON How old is your baby?\n1. 0–6 months\n2. 7–12 months\n3. 1–3 years\n4. Not sure";
        } else {
            echo "CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others";
        }
        break;

    case 4:
        $status = $input_history[2];
        $answer = $input_history[3];
        if ($status == '1') {
            updateRow($session_id, 6, $answer); // months_pregnant
        } elseif ($status == '2') {
            updateRow($session_id, 7, $answer); // baby_age
        } else {
            updateRow($session_id, 8, $answer); // state
        }
        echo "CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others";
        break;

    case 5:
        $state = $input_history[4];
        updateRow($session_id, 8, $state); // column I = state
        echo "CON Do you agree to receive helpful health messages from Mama’s Call?\n1. Yes\n2. No";
        break;

    case 6:
        $consent = $input_history[5];
        updateRow($session_id, 9, $consent); // column J = consent
        echo "END Thank you ❤️\nYou’ve successfully joined Mama’s Call. We’ll reach out soon with helpful updates.";
        break;

    default:
        echo "END Something went wrong. Please try again later.";
        logError($session_id, $msisdn, 'USSD Flow', 'Invalid step count: ' . $step);
        break;
}
?>
