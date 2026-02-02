<?php
define('APP_BASE_URL', rtrim(getenv('APP_BASE_URL') ?: 'http://localhost/lost_and_found-main', '/'));
define('AI_SERVICE_URL', getenv('AI_SERVICE_URL') ?: 'http://127.0.0.1:5001/match');

// AI matching: threshold 0–1 (e.g. 0.35 = 35% similarity). Used by PHP when calling Flask; Flask also reads env.
define('AI_MATCH_THRESHOLD', (float) (getenv('AI_MATCH_THRESHOLD') ?: '0.35'));
define('AI_MATCH_TOP_N', (int) (getenv('AI_MATCH_TOP_N') ?: '5'));
define('AI_NOTIFY_THRESHOLD', (float) (getenv('AI_NOTIFY_THRESHOLD') ?: '0.6'));

// Notifications: push email/SMS (set NOTIFY_PUSH_EMAIL=1 to actually send)
define('NOTIFY_PUSH_EMAIL', filter_var(getenv('NOTIFY_PUSH_EMAIL') ?: '0', FILTER_VALIDATE_BOOLEAN));
define('NOTIFY_PUSH_SMS', filter_var(getenv('NOTIFY_PUSH_SMS') ?: '0', FILTER_VALIDATE_BOOLEAN));
define('NOTIFY_EMAIL_FROM', getenv('NOTIFY_EMAIL_FROM') ?: 'noreply@localhost');
define('NOTIFY_SMS_GATEWAY_URL', getenv('NOTIFY_SMS_GATEWAY_URL') ?: '');

// Image upload: max size in bytes (default 5MB), allowed extensions and MIME types
define('UPLOAD_MAX_SIZE', (int) (getenv('UPLOAD_MAX_SIZE') ?: (5 * 1024 * 1024)));
define('UPLOAD_ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_ALLOWED_MIMES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
