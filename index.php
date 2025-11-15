<?php

date_default_timezone_set("Africa/Lagos");

// Debug log
file_put_contents("ussd_debug.log", "[".date('Y-m-d H:i:s')."] ".print_r($_POST, true)."\n", FILE_APPEND);

// Ensure request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "END Invalid Request";
    exit;
}

// Read HollaTags params safely
$msisdn   = $_POST['session_msisdn'] ?? '';
$op       = $_POST['session_operation'] ?? '';
$msg      = $_POST['session_msg'] ?? '';
$session  = $_POST['session_id'] ?? '';

// Start the session
if ($op === "start") {
    echo "CON Welcome to Mama’s Call\n1. Register\n2. Learn More";
    exit;
}
