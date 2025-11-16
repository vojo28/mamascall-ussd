<?php
include 'db.php';

// Get input
$msisdn = $_POST['session_msisdn'] ?? '';
$session_id = $_POST['session_id'] ?? '';
$user_input = trim($_POST['session_msg'] ?? '');

// Fetch existing session
$stmt = $conn->prepare("SELECT * FROM ussd_sessions WHERE session_id = :session_id");
$stmt->execute(['session_id' => $session_id]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

// Determine current step
if ($session) {
    $step_count = $session['step'];
    $input_history = $session['user_input'] ? explode('*', $session['user_input']) : [];
} else {
    $step_count = 0;
    $input_history = [];
}

// Append latest input
if ($user_input !== '') {
    $input_history[] = $user_input;
}

// Update or create session
if ($session) {
    $stmt = $conn->prepare("UPDATE ussd_sessions SET user_input = :user_input, step = :step WHERE session_id = :session_id");
    $stmt->execute([
        'user_input' => implode('*', $input_history),
        'step' => count($input_history),
        'session_id' => $session_id
    ]);
} else {
    $stmt = $conn->prepare("INSERT INTO ussd_sessions (session_id, msisdn, user_input, step) VALUES (:session_id, :msisdn, :user_input, :step)");
    $stmt->execute([
        'session_id' => $session_id,
        'msisdn' => $msisdn,
        'user_input' => implode('*', $input_history),
        'step' => 1
    ]);
}

// Process flow based on steps
switch (count($input_history)) {
    case 1:
        // Main menu
        if ($input_history[0] == '1') {
            echo "CON Please enter your full name:";
        } elseif ($input_history[0] == '2') {
            echo "END Mama’s Call is Nigeria’s first 24/7 maternal care hotline. We've got you, mama. Visit https://mamascall.org for more info.";
        } else {
            echo "END Invalid option. Try again.";
        }
        break;

    case 2:
        // Save full name
        $name = htmlspecialchars($input_history[1]);
        $stmt = $conn->prepare("UPDATE ussd_sessions SET full_name = :name WHERE session_id = :session_id");
        $stmt->execute(['name' => $name, 'session_id' => $session_id]);
        echo "CON Select your status:\n1. Pregnant\n2. Nursing mother\n3. Father / Partner";
        break;

    case 3:
        // Status selection
        $status = $input_history[2];
        $stmt = $conn->prepare("UPDATE ussd_sessions SET status = :status WHERE session_id = :session_id");
        $stmt->execute(['status' => $status, 'session_id' => $session_id]);

        if ($status == '1') {
            echo "CON How many months pregnant are you?\n1. 1–3 months\n2. 4–6 months\n3. 7–9 months\n4. Not sure";
        } elseif ($status == '2') {
            echo "CON How old is your baby?\n1. 0–6 months\n2. 7–12 months\n3. 1–3 years\n4. Not sure";
        } elseif ($status == '3') {
            echo "CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others";
        } else {
            echo "END Invalid option. Try again.";
        }
        break;

    case 4:
        // Pregnancy/Nursing/State flow
        $status = $input_history[2];
        $answer = $input_history[3];

        if ($status == '1') {
            $stmt = $conn->prepare("UPDATE ussd_sessions SET months_pregnant = :value WHERE session_id = :session_id");
            $stmt->execute(['value' => $answer, 'session_id' => $session_id]);
        } elseif ($status == '2') {
            $stmt = $conn->prepare("UPDATE ussd_sessions SET baby_age = :value WHERE session_id = :session_id");
            $stmt->execute(['value' => $answer, 'session_id' => $session_id]);
        } else {
            // Father/Partner, answer is state
            $stmt = $conn->prepare("UPDATE ussd_sessions SET state = :value WHERE session_id = :session_id");
            $stmt->execute(['value' => $answer, 'session_id' => $session_id]);
            echo "CON Do you agree to receive helpful health messages from Mama’s Call?\n1. Yes\n2. No";
            break;
        }

        // Next: state selection
        echo "CON Select your state of residence:\n1. Lagos\n2. Abuja\n3. Oyo\n4. Others";
        break;

    case 5:
        // Save state
        $state = $input_history[4];
        $stmt = $conn->prepare("UPDATE ussd_sessions SET state = :state WHERE session_id = :session_id");
        $stmt->execute(['state' => $state, 'session_id' => $session_id]);
        echo "CON Do you agree to receive helpful health messages from Mama’s Call?\n1. Yes\n2. No";
        break;

    case 6:
        // Save consent and finish
        $consent = $input_history[5];
        $stmt = $conn->prepare("UPDATE ussd_sessions SET consent = :consent WHERE session_id = :session_id");
        $stmt->execute(['consent' => $consent, 'session_id' => $session_id]);

        $stmt = $conn->prepare("SELECT full_name FROM ussd_sessions WHERE session_id = :session_id");
        $stmt->execute(['session_id' => $session_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "END Thank you, {$user['full_name']} ❤️\nYou’ve successfully joined Mama’s Call. We’ll reach out soon with helpful updates.";
        break;

    default:
        echo "END Something went wrong. Please try again later.";
}
?>
