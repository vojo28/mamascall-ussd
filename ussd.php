<?php

file_put_contents("ussd_debug.log", print_r($_POST, true), FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "END Invalid Request";
    exit;
}

$session_msg = $_POST['session_msg'] ?? '';
$session_operation = $_POST['session_operation'] ?? '';

if ($session_operation === 'start') {

    echo "CON Welcome to Mama's Call\n";
    echo "1. Register\n";
    echo "2. Learn More";

} else {
    echo "END Thank you";
}
