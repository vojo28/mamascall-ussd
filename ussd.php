<?php
// Log all incoming requests with timestamp
file_put_contents("ussd_debug.log", "[".date('Y-m-d H:i:s')."] ".print_r($_POST,true)."\n", FILE_APPEND);

// Fetch POST parameters safely
$msisdn = $_POST['session_msisdn'] ?? '';
$session_id = $_POST['session_id'] ?? '';
$session_msg = trim($_POST['session_msg'] ?? '');
$session_operation = $_POST['session_operation'] ?? '';
$session_from = $_POST['session_from'] ?? '';
$session_type = $_POST['session_type'] ?? '';
$session_mno = $_POST['session_mno'] ?? '';

// Handle empty or malformed requests
if (empty($msisdn) || empty($session_id)) {
    echo "END Something went wrong. Please try again later.";
    exit;
}

// Split user input into steps
$step = explode('*', $session_msg);

// Step 0: No input yet, show main menu
if (count($step) == 0 || $session_msg == '') {
    echo "CON Welcome to Mama’s Call\n1. Register\n2. Learn More";
    exit;
}

// Step 1: Main menu selection
if (count($step) == 1) {
    if ($step[0] == '1') {
        echo "CON Please enter your full name:";
    } elseif ($step[0] == '2') {
        echo "END Mama’s Call provides helpful health messages for pregnant and nursing mothers. Stay tuned!";
    } else {
        echo "END Invalid option. Try again.";
    }
    exit;
}

// Step 2: Full name entered
if (count($step) == 2) {
    $name = htmlspecialchars($step[1]);
    echo "CON Select your status:\n1. Pregnant\n2. Nursing mother\n3. Father / Partner";
    exit;
}

// Step 3: Status selection
if (count($step) == 3) {
    $status = $step[2];

    if ($status == '1') {
        echo "CON How many months pregnant are you?\n1. 1–3 months\n2. 4–6 months\n3. 7–9 months\n4. Not sure";
    } elseif ($status == '2') {
        echo "CON How old is your baby?\n1. 0–6 months\n2. 7–12 months\n3. 1–3 years\n4. Not sure";
    } elseif ($status == '3') {
        echo "CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others";
    } else {
        echo "END Invalid option. Try again.";
    }
    exit;
}

// Step 4: Pregnancy / Nursing / State flow
if (count($step) == 4) {
    $status = $step[2];

    if ($status == '1' || $status == '2') {
        echo "CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others";
    } elseif ($status == '3') {
        echo "CON Do you agree to receive helpful health messages from Mama’s Call?\n1. Yes\n2. No";
    } else {
        echo "END Invalid option. Try again.";
    }
    exit;
}

// Step 5: Final confirmation / SMS consent
if (count($step) >= 5) {
    $name = htmlspecialchars($step[1]);
    echo "END Thank you, $name.\nYou’ve successfully joined Mama’s Call. We’ll reach out soon with helpful updates.";
    exit;
}

// Fallback for any unexpected request
echo "END Something went wrong. Please try again later.";
exit;
?>
