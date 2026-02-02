# AI Matching & Setup Guide

## Admin Login (required for dashboard)

1. **Create admin user** (one-time):
   - Run SQL: `sql/add_admin_user.sql` (default: admin@example.com / **password**), or
   - Run: `php admin/create_admin.php` (default: admin@example.com / **admin123**)
2. Open **Admin** in the nav → sign in at `/admin/login.php`.
3. Dashboard and approve/reject actions are protected; moderate-item API requires admin session + CSRF.

## Problem
AI matching may not work if the Flask AI service is not running.

## Solution

### Step 1: Start the Flask AI Service

**Option A: Use the Startup Script (Easiest)**
1. Double-click `START_FLASK_AI.bat` in the project root folder
2. Wait for it to install dependencies (first time only)
3. The service will start on port 5001
4. Keep the window open while using AI features

**Option B: Manual Start**
```bash
cd "C:\xampp\htdocs\New Folder\flask-service"
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
python -m spacy download en_core_web_sm
python app.py
```

### Step 2: Verify the Service is Running

**Option A: Test Script**
- Open: `http://localhost/New%20Folder/test_ai_service.php`
- It will show connection status and test results

**Option B: Manual Check**
- Open browser: `http://127.0.0.1:5001/health`
- Should show: `{"status": "ok"}`

### Step 3: Test AI Matching

1. Go to: `http://localhost/New%20Folder/pages/search.php`
2. Enter a search query (e.g., "backpack")
3. Click "AI Suggest" button
4. You should see AI-suggested matches with similarity scores

## What Was Fixed

✅ **Better Error Handling**
- Added detailed error messages when Flask service is not available
- Shows helpful instructions in the UI

✅ **Startup Script Created**
- `START_FLASK_AI.bat` - Automatically sets up and starts the service

✅ **Test Script Created**
- `test_ai_service.php` - Diagnoses connection issues

✅ **Improved Frontend Feedback**
- Clear error messages with instructions
- Better handling of failed requests

## Troubleshooting

### Error: "AI service unavailable"
- **Cause**: Flask service not running
- **Fix**: Run `START_FLASK_AI.bat`

### Error: "Connection refused"
- **Cause**: Port 5001 is blocked or service crashed
- **Fix**: 
  - Check if another process is using port 5001
  - Restart the Flask service
  - Check Windows Firewall settings

### Error: Python not found
- **Cause**: Python is not installed or not in PATH
- **Fix**: Install Python 3.10+ from https://www.python.org/
  - Make sure to check "Add Python to PATH" during installation

### Error: ModuleNotFoundError (spacy, flask, etc.)
- **Cause**: Dependencies not installed
- **Fix**: The startup script installs them automatically, or run:
  ```bash
  pip install -r requirements.txt
  ```

### No matches found
- **Cause**: Normal if no items match the query above the configured similarity threshold
- **Fix**: Try different search terms, add more items, or lower `AI_MATCH_THRESHOLD` (env or config)

## Configurable AI threshold

- **PHP**: `includes/config.php` and env vars `AI_MATCH_THRESHOLD` (0–1), `AI_MATCH_TOP_N`, `AI_NOTIFY_THRESHOLD`.
- **Flask**: Env vars `AI_MATCH_THRESHOLD`, `AI_MATCH_TOP_N`; request body can override with `min_score`, `top_n`.

## Notifications (email/SMS)

- Notifications are **logged** to the `notifications` table and optionally **pushed**.
- To enable email: set env `NOTIFY_PUSH_EMAIL=1` and optionally `NOTIFY_EMAIL_FROM`.
- To enable SMS: set `NOTIFY_PUSH_SMS=1` and `NOTIFY_SMS_GATEWAY_URL` (e.g. `https://gateway?phone={phone}&msg={message}`).

## Service Requirements

The Flask AI service needs:
- Python 3.10+
- Flask
- scikit-learn
- spacy
- numpy
- spaCy English model (en_core_web_sm)

All of these are automatically installed by `START_FLASK_AI.bat`.

## Keep Service Running

- The Flask service must be running whenever you want to use AI matching
- Leave the command window open
- The service runs on: `http://127.0.0.1:5001`
- Stop it by pressing `Ctrl+C` in the command window


