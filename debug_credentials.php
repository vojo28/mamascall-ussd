<?php
// debug_credentials.php

// Get the credentials JSON from the environment variable
$googleJson = getenv('GOOGLE_APPLICATION_CREDENTIALS_JSON');

if (!$googleJson) {
    echo "❌ No credentials found in environment variable.";
    exit;
}

// Try to decode the JSON
$data = json_decode($googleJson, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ Invalid JSON in environment variable: " . json_last_error_msg();
    exit;
}

// Check required keys in the service account
$requiredKeys = ['type','project_id','private_key','client_email'];
$missingKeys = [];

foreach ($requiredKeys as $key) {
    if (!isset($data[$key]) || empty($data[$key])) {
        $missingKeys[] = $key;
    }
}

if (!empty($missingKeys)) {
    echo "❌ Missing keys in credentials: " . implode(', ', $missingKeys);
} else {
    echo "✅ Credentials are valid and accessible!";
}

// Optional: show project ID
echo "\nProject ID: " . $data['project_id'];
?>
