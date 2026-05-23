<?php
// api/endpoints/prospect_send.php
// AJAX: recebe array de site_ids, dispara webhook e activa os drafts

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/prospecting.php';

header('Content-Type: application/json');

if (!is_logged_in() || get_logged_user()['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$siteIds  = array_map('intval', $input['site_ids'] ?? []);
$csrf     = $input['csrf_token'] ?? '';

if (!verify_csrf_token($csrf)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

if (empty($siteIds)) {
    echo json_encode(['error' => 'No sites selected']);
    exit;
}

$admin   = get_logged_user();
$sent    = 0;
$errors  = [];

foreach ($siteIds as $siteId) {
    $site = db_fetch_one("SELECT s.*, u.name, u.email, u.phone FROM sites s JOIN users u ON u.id = s.user_id WHERE s.id = :id AND s.status = 'draft'", [':id' => $siteId]);
    if (!$site) {
        $errors[] = "Site {$siteId} not found or not a draft";
        continue;
    }

    $password    = 'Welcome' . date('Y') . '!';
    $siteUrl     = 'https://superpage.co.uk/' . $site['slug'];

    $payload = [
        'event'         => 'prospect_site_created',
        'business_name' => $site['name'],
        'site_url'      => $siteUrl,
        'email'         => $site['email'] ?? '',
        'phone'         => $site['phone'] ?? '',
        'password'      => $password,
        'has_email'     => !empty($site['email']),
        'has_phone'     => !empty($site['phone']),
    ];

    fire_prospect_webhook($siteId, (int)$admin['id'], $payload);
    $sent++;
}

echo json_encode(['success' => true, 'sent' => $sent, 'errors' => $errors]);
