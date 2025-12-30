<?php
define('DISABLE_SHEETS', true);

$data = array_merge($_GET, $_POST);
file_put_contents("ussd_debug.log", print_r($data, true), FILE_APPEND);

$user_input = trim($data['session_msg'] ?? '');
$step = $user_input === '' ? [] : explode('*', $user_input);

$response = "END Error";

switch (count($step)) {
    case 0:
        $response = "CON Welcome to Mama’s Call ❤️\n1. Register\n2. Learn More";
        break;

    case 1:
        if ($step[0] === '1') {
            $response = "CON Please enter your full name:";
        } elseif ($step[0] === '2') {
            $response = "END Mama’s Call is Nigeria’s first 24/7 maternal care hotline.";
        } else {
            $response = "END Invalid option";
        }
        break;

    case 2:
        $response = "END Thank you ❤️";
        break;
}

echo $response;
flush();
exit;
