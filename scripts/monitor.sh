#!/bin/bash

# Health monitoring script for Listerino
# Can be run by cron every 5 minutes

# Configuration
SITE_URL="https://listerino.com"
ADMIN_EMAIL="eric@layercakemarketing.com"
LOG_FILE="/var/log/listerino-monitor.log"

# Function to send alert
send_alert() {
    local subject="$1"
    local message="$2"
    echo "$(date): ALERT - $subject" >> $LOG_FILE
    echo "$message" >> $LOG_FILE
    # Uncomment to enable email alerts
    # echo "$message" | mail -s "[Listerino Alert] $subject" $ADMIN_EMAIL
}

# Check if site is responding
echo "$(date): Checking site health..." >> $LOG_FILE

HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" $SITE_URL)
if [ "$HTTP_STATUS" != "200" ]; then
    send_alert "Site Down" "HTTP Status: $HTTP_STATUS"
    exit 1
fi

# Check Docker containers
CONTAINERS_DOWN=$(docker ps -a --filter "name=listerino" --filter "status=exited" --format "{{.Names}}")
if [ ! -z "$CONTAINERS_DOWN" ]; then
    send_alert "Containers Down" "The following containers are not running: $CONTAINERS_DOWN"
fi

# Check disk space
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 80 ]; then
    send_alert "High Disk Usage" "Disk usage is at ${DISK_USAGE}%"
fi

# Check database connectivity
docker exec listerino_app bash -c "cd /app && php artisan tinker --execute='DB::select(\"SELECT 1\")'" > /dev/null 2>&1
if [ $? -ne 0 ]; then
    send_alert "Database Connection Failed" "Unable to connect to database"
fi

echo "$(date): Health check completed successfully" >> $LOG_FILE