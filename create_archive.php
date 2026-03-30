<?php
require_once __DIR__ . '/moduleFunctions.php';

$moduleFolder = $session->get('module');
$currentUrl = '/modules/' . $moduleFolder . '/create_archive.php';

if (!isActionAccessible($guid, $connection2, $currentUrl)) {
    $page->addError(__('You do not have access to this action.'));
    exit;
}

$page->breadcrumbs->add(__('Bulletin archive folders'));

$archives = cr_listArchiveFolders();
$error = trim((string)($_GET['error'] ?? ''));
$success = trim((string)($_GET['success'] ?? ''));

$absoluteURL = rtrim($session->get('absoluteURL'), '/');
$actionURL = $absoluteURL . '/index.php?q=/modules/' . $moduleFolder . '/create_archiveProcess.php';

echo '<div style="max-width:800px;">';
echo '  <h2>' . __('Create archive folder') . '</h2>';

if ($success === '1') {
    echo '<div class="success" style="padding:10px; border:1px solid #0a0; background:#efe;">' . __('Folder created successfully.') . '</div>';
} elseif ($error !== '') {
    echo '<div class="error" style="padding:10px; border:1px solid #b00020; background:#fee; color:#b00020;">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>';
}

echo '  <div style="margin:14px 0; padding:12px; border:1px solid #ddd; border-radius:6px;">';
echo '    <form method="post" action="' . htmlspecialchars($actionURL, ENT_QUOTES, 'UTF-8') . '">';
echo '      <label for="archiveFolder" style="font-weight:bold;">' . __('Folder name (trimester name)') . '</label><br>';
echo '      <input type="text" id="archiveFolder" name="archiveFolder" style="width:100%; max-width:420px;" placeholder="e.g. T1_2025-2026" required>';
echo '      <div style="margin-top:10px;">';
echo '        <button type="submit" class="gibbon-button" style="padding:8px 14px;">' . __('Create folder') . '</button>';
echo '      </div>';
echo '    </form>';
echo '  </div>';

echo '  <h3 style="margin-top:18px;">' . __('Existing folders') . '</h3>';
if (!$archives) {
    echo '<div>' . __('No archive folder found yet.') . '</div>';
} else {
    echo '<ul>';
    foreach ($archives as $a) {
        echo '<li>' . htmlspecialchars((string)$a['name'], ENT_QUOTES, 'UTF-8') . '</li>';
    }
    echo '</ul>';
}

echo '  <div style="margin-top:16px;">';
echo '    <a href="' . $absoluteURL . '/index.php?q=/modules/' . $moduleFolder . '/index.php">' . __('Back to bulletin generation') . '</a>';
echo '  </div>';

echo '</div>';

