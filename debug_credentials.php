<?php

$path = "/etc/secrets/credentials.json";

echo "<pre>";

if (!file_exists($path)) {
    echo "❌ File does NOT exist at: $path\n";
    exit;
}

echo "✔ File exists\n";

// Check permissions
$perms = substr(sprintf('%o', fileperms($path)), -4);
echo "Permissions: $perms\n";

// Try reading file
$content = @file_get_contents($path);

if ($content === false) {
    echo "❌ Cannot read the file. Permission denied.\n";
} else {
    echo "✔ File can be read. First 200 chars:\n";
    echo substr($content, 0, 200);
}

echo "</pre>";
