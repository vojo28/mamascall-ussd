#!/bin/bash

# Fix permission for Render secret file
chmod 644 /etc/secrets/credentials.json 2>/dev/null || true

# Start Apache
apache2-foreground
