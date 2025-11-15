#!/usr/bin/env bash
# Render sets $PORT for you
php -S 0.0.0.0:${PORT:-10000} index.php
