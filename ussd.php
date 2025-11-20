<?php
require 'sheets.php';

// Capture POST data
$msisdn = $_POST['session_msisdn'] ?? '';
$session_id = $_POST['session_id'] ?? '';
$user_input = trim($_POST['session_msg'] ?? '');
$input_history = explode('*', $user_input);

// Fetch existing session (check session_id in sheet)
$service = getSheetService();
$sheetId = getSheetId();
$sheetData = $service->spreadsheets_values->get($sheetId, 'Sessions!A2:I');
$rows = $sheetData->getValues() ?? [];

$sessionExists = false;
$history = '';
foreach ($rows as $r) {
    if (($r[0] ?? '') === $session_id) {
        $sessionExists = true;
        $history = $r[8] ?? ''; // user_input column
        break;
    }
}

$input_history = $sessionExists ? array_merge(explode('*', $history), [$user_input]) : [$user_input];

// Save or update session in sheet
if ($sessionExists) {
    updateRow($session_id, 8, implode('*', $input_history)); // user_input column
    updateRow($session_id, 3, count($input_history));        // step column
} else {
    appendRow([$session_id, $msisdn, '', '', '', '', '', '', implode('*', $input_history)]);
}

// Process USSD Flow
$stepCount = count($input_history);
$currentStep = $input_history[$stepCount - 1] ?? '';

switch ($stepCount) {
    case 1:
        if ($currentStep == '1') {
            echo "CON Please enter your full name:";
        } elseif ($currentStep == '2') {
            echo "END Mama’s Call is Nigeria’s first 24/7 maternal care hotline. Visit https://mamascall.org";
        } else {
            echo "END Invalid option. Try again.";
        }
        break;

    case 2:
        $name = htmlspecialchars($currentStep);
        updateRow($session_id, 2, $name); // full_name
        echo "CON Select your status:\n1. Pregnant\n2. Nursing mother\n3. Father / Partner";
        break;

    case 3:
        $status = $currentStep;
        updateRow($session_id, 3, $status); // status column
        if ($status == '1') {
            echo "CON How many months pregnant are you?\n1. 1–3\n2. 4–6\n3. 7–9\n4. Not sure";
        } elseif ($status == '2') {
            echo "CON How old is your baby?\n1. 0–6 months\n2. 7–12 months\n3. 1–3 years\n4. Not sure";
        } else {
            echo "CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others";
        }
        break;

    case 4:
        $status = $input_history[2];
        $answer = $input_history[3];
        if ($status == '1') updateRow($session_id, 4, $answer); // months_pregnant
        elseif ($status == '2') updateRow($session_id, 5, $answer); // baby_age
        else updateRow($session_id, 6, $answer); // state
        echo "CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others";
        break;

    case 5:
        $state = $input_history[4];
        updateRow($session_id, 6, $state); // state
        echo "CON Do you agree to receive helpful health messages from Mama’s Call?\n1. Yes\n2. No";
        break;

    case 6:
        $consent = $input_history[5];
        updateRow($session_id, 7, $consent); // consent
        updateRow($session_id, 3, count($input_history)); // final step
        echo "END Thank you ❤️\nYou’ve successfully joined Mama’s Call. We’ll reach out soon with helpful updates.";
        break;

    default:
        echo "END Something went wrong. Please try again later.";
        break;
}
?>
