<?php
// USSD endpoint for Mama's Call
// Production-safe version

$data = array_merge($_GET, $_POST);

// Immediate logging
file_put_contents("ussd_debug.log", date('Y-m-d H:i:s').' - '.json_encode($data).PHP_EOL, FILE_APPEND);

$msisdn        = $data['session_msisdn'] ?? '';
$session_id    = $data['session_id'] ?? '';
$user_input    = trim($data['session_msg'] ?? '');
$step          = explode('*', $user_input);

// Google Sheets functions
require 'sheets.php';

// --- Immediate response function ---
function respond($message) {
    header('Content-Type: text/plain');
    echo $message;
    flush();
    exit; // ensures no further processing blocks memory
}

// --- USSD Flow ---
$step_count = count($step);
$response = "END Something went wrong. Please try again later.";

// Step 0: Welcome
if ($step_count === 0 || $user_input === '') {
    respond("CON Welcome to Mama’s Call ❤️\n1. Register\n2. Learn More");
}

// Step 1: Main Menu
if ($step_count === 1) {
    if ($step[0] === '1') {
        respond("CON Please enter your full name:");
    } elseif ($step[0] === '2') {
        respond("END Mama’s Call is Nigeria’s first 24/7 maternal care hotline. We've got you, mama.\nVisit https://mamascall.org");
    } else {
        respond("END Invalid option. Try again.");
    }
}

// Step 2: Full name
if ($step_count === 2) {
    respond("CON Select your status:\n1. Pregnant\n2. Nursing mother\n3. Father / Partner");
}

// Step 3: Status
if ($step_count === 3) {
    $status = $step[2] ?? '';
    if ($status === '1') {
        respond("CON How many months pregnant are you?\n1. 1–3 months\n2. 4–6 months\n3. 7–9 months\n4. Not sure");
    } elseif ($status === '2') {
        respond("CON How old is your baby?\n1. 0–6 months\n2. 7–12 months\n3. 1–3 years\n4. Not sure");
    } else {
        respond("CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others");
    }
}

// Step 4: Pregnancy/Nursing → State
if ($step_count === 4) {
    respond("CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others");
}

// Step 5: SMS Consent
if ($step_count === 5) {
    respond("CON Do you agree to receive helpful health messages from Mama’s Call?\n1. Yes\n2. No");
}

// Step 6: Success
if ($step_count >= 6) {
    respond("END Thank you ❤️\nYou’ve successfully joined Mama’s Call. We’ll reach out soon with helpful updates.");
}

// --- AFTER RESPONSE: store to Google Sheets asynchronously ---
register_shutdown_function(function() use ($msisdn, $session_id, $step) {
    try {
        $full_history = implode('*', $step);
        $timestamp = date('Y-m-d H:i:s');

        // Append row safely
        appendRow([
            $timestamp,
            $session_id,
            $msisdn,
            $step_count,
            $step[1] ?? '', // full name
            $step[2] ?? '', // status
            $step[3] ?? '', // months pregnant
            $step[3] ?? '', // baby age (reuse step if needed)
            $step[4] ?? '', // state
            $step[5] ?? '', // consent
            $full_history
        ]);
    } catch (Exception $e) {
        logError($session_id, $msisdn, 'USSD Flow', $e->getMessage());
    }
});
