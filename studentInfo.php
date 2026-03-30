<?php
include '../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

// gibbon.php provides $gibbon; in some contexts $session may not be set.
$session = $session ?? ($gibbon->session ?? null);
$moduleFolder = $session ? $session->get('module') : '';
$currentUrl = '/modules/' . $moduleFolder . '/studentInfo.php';

header('Content-Type: application/json; charset=utf-8');

if (!isActionAccessible($guid, $connection2, $currentUrl)) {
    http_response_code(403);
    echo json_encode(['error' => 'access_denied'], JSON_UNESCAPED_UNICODE);
    exit;
}

$personID = trim((string)($_GET['personID'] ?? ''));
$yearGroupID = trim((string)($_GET['yearGroupID'] ?? ''));

if ($personID === '') {
    echo json_encode(['student' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $student = cr_getStudentInfo($connection2, $personID, $yearGroupID !== '' ? $yearGroupID : null);
    echo json_encode(['student' => $student], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'sql_error',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

