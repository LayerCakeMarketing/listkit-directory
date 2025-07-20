#!/bin/bash

# Test place update endpoint
echo "Testing place update endpoint..."

# Set your auth token here (get it from browser dev tools)
AUTH_TOKEN="YOUR_AUTH_TOKEN_HERE"

# Place ID to test
PLACE_ID=1

# Test data
curl -X PUT http://localhost:8000/api/places/$PLACE_ID \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -d '{
    "title": "Test Place Updated",
    "description": "Updated description",
    "type": "business_b2c",
    "category_id": 1,
    "email": "test@example.com",
    "phone": "123-456-7890",
    "website_url": "https://example.com",
    "facebook_url": "https://facebook.com/testplace",
    "instagram_handle": "@testplace",
    "twitter_handle": "@testplace",
    "youtube_channel": "https://youtube.com/c/testplace",
    "location": {
      "address_line1": "123 Main St",
      "city": "New York",
      "state": "NY",
      "zip_code": "10001",
      "latitude": 40.7128,
      "longitude": -74.0060
    }
  }' | jq .

echo -e "\n\nTo use this script:"
echo "1. Get your auth token from browser dev tools (Network tab > Request Headers > Authorization)"
echo "2. Replace YOUR_AUTH_TOKEN_HERE with your actual token"
echo "3. Replace PLACE_ID with the ID of the place you want to update"
echo "4. Run: chmod +x test-place-update.sh && ./test-place-update.sh"