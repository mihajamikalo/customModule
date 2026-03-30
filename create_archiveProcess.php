<?php
include '../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

$moduleFolder = $gibbon->session->get('module');
$currentUrl = '/modules/' . $moduleFolder . '/create_archiveProcess.php';

if (!isActionAccessible($guid, $connection2, $currentUrl)) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$folderInput = $_POST['archiveFolder'] ?? null;
$folder = cr_safeFolderName(is_string($folderInput) ? $folderInput : null);

$absoluteURL = rtrim($gibbon->session->get('absoluteURL'), '/');
$returnURL = $absoluteURL . '/index.php?q=/modules/' . $moduleFolder . '/create_archive.php';

if ($folder === null) {
    header('Location: ' . $returnURL . '&error=' . urlencode('Nom de dossier invalide.'));
    exit;
}

$baseDir = cr_getArchiveBaseDir();
if (!cr_ensureDir($baseDir)) {
    header('Location: ' . $returnURL . '&error=' . urlencode('Impossible de créer le dossier de stockage.'));
    exit;
}

$targetDir = $baseDir . DIRECTORY_SEPARATOR . $folder;
if (is_dir($targetDir)) {
    header('Location: ' . $returnURL . '&error=' . urlencode('Le dossier existe déjà.'));
    exit;
}

$ok = @mkdir($targetDir, 0755, true);
if (!$ok) {
    header('Location: ' . $returnURL . '&error=' . urlencode('Erreur lors de la création du dossier.'));
    exit;
}

header('Location: ' . $returnURL . '&success=1');
exit;

