<?php
require 'sheets.php';

try {
    $service = getSheetService();
    echo "✅ Credentials are valid and accessible!\n";
    echo "Spreadsheet ID: " . getSheetId() . "\n";
} catch (Exception $e) {
    echo "❌ Error accessing Google Sheets: " . $e->getMessage();
}
?>
