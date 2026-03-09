@echo off
REM test_cors.bat — Test CORS on Windows
REM Usage: test_cors.bat

setlocal enabledelayedexpansion
set API_URL=http://localhost:8000/api/trips
set ORIGIN=http://localhost:5173

echo.
echo ==========================================
echo CORS ^& Connectivity Test (Windows)
echo ==========================================

REM Test 1: OPTIONS Preflight
echo.
echo [1] Testing OPTIONS preflight...
echo.
curl -i -X OPTIONS "%API_URL%" ^
  -H "Origin: %ORIGIN%" ^
  -H "Access-Control-Request-Method: GET" ^
  -H "Access-Control-Request-Headers: Content-Type"

REM Test 2: GET Request
echo.
echo [2] Testing GET request...
echo.
curl -i -X GET "%API_URL%" ^
  -H "Origin: %ORIGIN%" ^
  -H "Content-Type: application/json"

REM Test 3: POST Request
echo.
echo [3] Testing POST /api/route/calc...
echo.
curl -i -X POST "http://localhost:8000/api/route/calc" ^
  -H "Origin: %ORIGIN%" ^
  -H "Content-Type: application/json" ^
  -d "{\"from\":\"77.1025,28.7041\",\"to\":\"72.8777,19.0760\"}"

echo.
echo ==========================================
echo Test Complete
echo ==========================================
pause
