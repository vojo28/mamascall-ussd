#!/bin/bash

# Fix permissions so PHP/Apache can read the Google credentials
if [ -f /etc/secrets/credentials.json ]; then
    chmod 644 /etc/secrets/credentials.json
    chown www-data:www-data /etc/secrets/credentials.json
fi

# Start Apache
apache2-foreground
