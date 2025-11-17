#!/bin/bash
# Fix file permissions so PHP can read the secret
if [ -f /etc/secrets/credentials.json ]; then
    chmod 644 /etc/secrets/credentials.json
fi

# Start Apache
apache2-foreground
