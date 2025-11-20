<?php
require 'sheets.php';

try {
    // Simulate an error
    throw new Exception("This is a test error for USSD logging and email alert!");
} catch (Exception $e) {
    // Log error to Google Sheets and send email
    $session_id = 'TEST123';
    $msisdn = '2348012345678';
    $step = 0;
    $error_message = $e->getMessage();

    logError($session_id, $msisdn, $step, $error_message);

    echo "✅ Test error logged and email sent (if ADMIN_EMAIL is set).";
}
?>
