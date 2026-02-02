<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/validate_image.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid method'], 405);
}

$itemType = isset($_POST['item_type']) ? sanitize_input($_POST['item_type']) : '';
$allowedTypes = ['lost', 'found'];

if (!in_array($itemType, $allowedTypes, true)) {
    json_response(['success' => false, 'message' => 'Invalid item type'], 422);
}

$fields = [
    'item_name' => sanitize_input($_POST['item_name'] ?? ''),
    'description' => sanitize_input($_POST['description'] ?? ''),
    'location' => sanitize_input($_POST['location'] ?? ''),
    'event_date' => sanitize_input($_POST[$itemType === 'lost' ? 'date_lost' : 'date_found'] ?? ''),
    'contact_name' => sanitize_input($_POST[$itemType === 'lost' ? 'owner_name' : 'finder_name'] ?? ''),
    'contact_email' => sanitize_input($_POST[$itemType === 'lost' ? 'owner_email' : 'finder_email'] ?? ''),
    'contact_phone' => sanitize_input($_POST[$itemType === 'lost' ? 'owner_phone' : 'finder_phone'] ?? ''),
];

foreach (['item_name', 'description', 'location', 'event_date', 'contact_name', 'contact_email'] as $required) {
    if (empty($fields[$required])) {
        json_response(['success' => false, 'message' => 'Missing required fields'], 422);
    }
}
if (!filter_var($fields['contact_email'], FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email address'], 422);
}
if (!empty($fields['contact_phone']) && !preg_match('/^[\d\s\-+()]{7,20}$/', $fields['contact_phone'])) {
    json_response(['success' => false, 'message' => 'Invalid phone number'], 422);
}

$imagePath = null;
if (!empty($_FILES['item_image']['name'])) {
    list($valid, $imgError) = validate_uploaded_image($_FILES['item_image']);
    if (!$valid) {
        json_response(['success' => false, 'message' => $imgError], 422);
    }
    $uploadsDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadsDir)) {
        if (!@mkdir($uploadsDir, 0755, true)) {
            json_response(['success' => false, 'message' => 'Upload directory unavailable'], 500);
        }
    }
    $extension = strtolower(pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, UPLOAD_ALLOWED_EXTENSIONS, true)) {
        json_response(['success' => false, 'message' => 'Invalid image type'], 422);
    }
    $filename = 'item_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $targetPath = $uploadsDir . $filename;
    if (!move_uploaded_file($_FILES['item_image']['tmp_name'], $targetPath)) {
        json_response(['success' => false, 'message' => 'Failed to save image'], 500);
    }
    $imagePath = '/uploads/' . $filename;
}

$stmt = $mysqli->prepare("INSERT INTO items (item_type, item_name, description, location, event_date, contact_name, contact_email, contact_phone, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
$stmt->bind_param(
    'sssssssss',
    $itemType,
    $fields['item_name'],
    $fields['description'],
    $fields['location'],
    $fields['event_date'],
    $fields['contact_name'],
    $fields['contact_email'],
    $fields['contact_phone'],
    $imagePath
);

if ($stmt->execute()) {
    json_response(['success' => true, 'message' => 'Submission received. Pending admin approval.']);
}

json_response(['success' => false, 'message' => 'Database error: ' . $mysqli->error], 500);

