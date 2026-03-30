<?php
include '../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

$moduleFolder = $session->get('module');
$currentUrl = '/modules/' . $moduleFolder . '/studentsByYearGroup.php';

header('Content-Type: application/json; charset=utf-8');

if (!isActionAccessible($guid, $connection2, $currentUrl)) {
    http_response_code(403);
    echo json_encode(['error' => 'access_denied'], JSON_UNESCAPED_UNICODE);
    exit;
}

$yearGroupID = trim((string)($_GET['yearGroupID'] ?? ''));
if ($yearGroupID === '') {
    echo json_encode(['students' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $students = cr_getStudentsByYearGroup($connection2, $yearGroupID);
    echo json_encode(['students' => $students], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'sql_error',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

