#!/bin/bash
# test_cors.sh — Test CORS headers and connectivity
# Usage: bash test_cors.sh

API_URL="http://localhost:8000/api/trips"
ORIGIN="http://localhost:5173"

echo "=========================================="
echo "CORS & Connectivity Test"
echo "=========================================="

# Test 1: OPTIONS Preflight
echo -e "\n[1] Testing OPTIONS preflight..."
OPTIONS_RESPONSE=$(curl -s -i -X OPTIONS "$API_URL" \
  -H "Origin: $ORIGIN" \
  -H "Access-Control-Request-Method: GET" \
  -H "Access-Control-Request-Headers: Content-Type")

echo "$OPTIONS_RESPONSE"

# Extract status code
OPTIONS_STATUS=$(echo "$OPTIONS_RESPONSE" | grep "HTTP" | awk '{print $2}')
echo "Status: $OPTIONS_STATUS"

# Check for required headers
if echo "$OPTIONS_RESPONSE" | grep -q "Access-Control-Allow-Origin"; then
  echo "✓ Access-Control-Allow-Origin: PASS"
else
  echo "✗ Access-Control-Allow-Origin: FAIL"
fi

if echo "$OPTIONS_RESPONSE" | grep -q "Access-Control-Allow-Methods"; then
  echo "✓ Access-Control-Allow-Methods: PASS"
else
  echo "✗ Access-Control-Allow-Methods: FAIL"
fi

# Test 2: GET Request with Origin
echo -e "\n[2] Testing GET request..."
GET_RESPONSE=$(curl -s -i -X GET "$API_URL" \
  -H "Origin: $ORIGIN" \
  -H "Content-Type: application/json")

echo "$GET_RESPONSE"

GET_STATUS=$(echo "$GET_RESPONSE" | grep "HTTP" | awk '{print $2}')
echo "Status: $GET_STATUS"

if echo "$GET_RESPONSE" | grep -q "Access-Control-Allow-Origin"; then
  echo "✓ GET CORS Header: PASS"
else
  echo "✗ GET CORS Header: FAIL"
fi

# Test 3: POST Request
echo -e "\n[3] Testing POST /api/route/calc..."
POST_RESPONSE=$(curl -s -i -X POST "http://localhost:8000/api/route/calc" \
  -H "Origin: $ORIGIN" \
  -H "Content-Type: application/json" \
  -d '{"from":"77.1025,28.7041","to":"72.8777,19.0760"}')

echo "$POST_RESPONSE"

POST_STATUS=$(echo "$POST_RESPONSE" | grep "HTTP" | awk '{print $2}')
echo "Status: $POST_STATUS"

if echo "$POST_RESPONSE" | grep -q '"ok":true'; then
  echo "✓ POST Response: PASS"
else
  echo "✗ POST Response: FAIL"
fi

echo -e "\n=========================================="
echo "Test Complete"
echo "=========================================="
