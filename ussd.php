<?php
require 'sheets.php';

try {
    // Get POST values safely
    $msisdn = $_POST['session_msisdn'] ?? '';
    $session_id = $_POST['session_id'] ?? '';
    $user_input = trim($_POST['session_msg'] ?? '');

    // Fetch existing session
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

    $input_history = $sessionExists ? explode('*', $history) : [];
    if ($user_input !== '') $input_history[] = $user_input;

    // Save or update session
    if ($sessionExists) {
        updateRow($session_id, 8, implode('*', $input_history)); // user_input column
    } else {
        appendRow([$session_id, $msisdn, '', '', '', '', '', '', implode('*', $input_history)]);
    }

    // Determine current screen
    $currentScreen = $sessionExists ? getCurrentScreen($input_history) : 'welcome';

    // USSD Flow
    switch ($currentScreen) {
        case 'welcome':
            $option = $input_history[0] ?? '';
            if ($option === '1') {
                echo "CON Please enter your full name:";
            } elseif ($option === '2') {
                echo "END Mama’s Call is Nigeria’s first 24/7 maternal care hotline. We've got you, mama.\nVisit https://mamascall.org";
            } else {
                echo "CON Welcome to Mama’s Call ❤️\n1. Register\n2. Learn More";
            }
            break;

        case 'full_name':
            $name = htmlspecialchars($input_history[1] ?? '');
            updateRow($session_id, 2, $name); // full_name
            echo "CON Select your status:\n1. Pregnant\n2. Nursing mother\n3. Father / Partner";
            break;

        case 'status':
            $status = $input_history[2] ?? '';
            updateRow($session_id, 3, $status); // status
            if ($status === '1') {
                echo "CON How many months pregnant are you?\n1. 1–3 months\n2. 4–6 months\n3. 7–9 months\n4. Not sure";
            } elseif ($status === '2') {
                echo "CON How old is your baby?\n1. 0–6 months\n2. 7–12 months\n3. 1–3 years\n4. Not sure";
            } else {
                echo "CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others";
            }
            break;

        case 'months_pregnant':
            $answer = $input_history[3] ?? '';
            updateRow($session_id, 4, $answer); // months_pregnant
            echo "CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others";
            break;

        case 'baby_age':
            $answer = $input_history[3] ?? '';
            updateRow($session_id, 5, $answer); // baby_age
            echo "CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others";
            break;

        case 'state':
            $state = $input_history[4] ?? '';
            updateRow($session_id, 6, $state); // state
            echo "CON Do you agree to receive helpful health messages from Mama’s Call?\n1. Yes\n2. No";
            break;

        case 'consent':
            $consent = $input_history[5] ?? '';
            updateRow($session_id, 7, $consent); // consent
            echo "END Thank you ❤️\nYou’ve successfully joined Mama’s Call. We’ll reach out soon with helpful updates.";
            break;

        default:
            throw new Exception("Unknown screen step encountered: " . count($input_history));
    }

} catch (Exception $e) {
    // Log the error to the Errors sheet and send email
    $error_message = $e->getMessage();
    logError($session_id ?? 'unknown', $msisdn ?? 'unknown', count($input_history) ?? 0, $error_message);
    echo "END An error occurred. We’ve logged the issue and will follow up.";
}

// Determine the current screen based on input history
function getCurrentScreen($input_history) {
    $stepCount = count($input_history);
    if ($stepCount === 0) return 'welcome';
    if ($stepCount === 1 && $input_history[0] === '1') return 'full_name';
    if ($stepCount === 2) return 'status';
    if ($stepCount === 3) {
        return ($input_history[2] === '1') ? 'months_pregnant' : (($input_history[2] === '2') ? 'baby_age' : 'state');
    }
    if ($stepCount === 4) return 'state';
    if ($stepCount === 5) return 'consent';
    return 'error';
}
?>
