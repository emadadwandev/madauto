#!/bin/bash

echo "Starting Careem-Loyverse Queue Worker..."
echo ""
echo "Press Ctrl+C to stop the worker"
echo ""

php artisan queue:work database --sleep=3 --tries=3 --timeout=60 --verbose
