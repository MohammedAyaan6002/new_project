<?php
/**
 * Strong image validation for uploads.
 * Requires config.php for UPLOAD_* constants.
 */

if (!function_exists('validate_uploaded_image')) {
    /**
     * Validate an uploaded file as a safe image. Returns [true, null] or [false, error_message].
     *
     * @param array $file $_FILES['item_image']
     * @return array{0: bool, 1: ?string}
     */
    function validate_uploaded_image(array $file): array
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return [false, 'No file uploaded or invalid upload.'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds server limit.',
                UPLOAD_ERR_FORM_SIZE => 'File too large.',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error.',
                UPLOAD_ERR_CANT_WRITE => 'Server cannot save file.',
                UPLOAD_ERR_EXTENSION => 'Upload blocked by server.',
            ];
            return [false, $messages[$file['error']] ?? 'Upload error.'];
        }
        $maxSize = defined('UPLOAD_MAX_SIZE') ? UPLOAD_MAX_SIZE : (5 * 1024 * 1024);
        if ($file['size'] > $maxSize) {
            return [false, 'Image must be under ' . round($maxSize / 1024 / 1024, 1) . ' MB.'];
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExt = defined('UPLOAD_ALLOWED_EXTENSIONS') ? UPLOAD_ALLOWED_EXTENSIONS : ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowedExt, true)) {
            return [false, 'Allowed formats: ' . implode(', ', $allowedExt) . '.'];
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowedMimes = defined('UPLOAD_ALLOWED_MIMES') ? UPLOAD_ALLOWED_MIMES : ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowedMimes, true)) {
            return [false, 'File must be a valid image (JPEG, PNG, GIF, or WebP).'];
        }
        $info = @getimagesize($file['tmp_name']);
        if ($info === false || empty($info[0]) || empty($info[1])) {
            return [false, 'File is not a valid image or is corrupted.'];
        }
        return [true, null];
    }
}
