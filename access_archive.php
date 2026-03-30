<?php
require_once __DIR__ . '/moduleFunctions.php';

$moduleFolder = $session->get('module');
$currentUrl = '/modules/' . $moduleFolder . '/access_archive.php';

if (!isActionAccessible($guid, $connection2, $currentUrl)) {
    $page->addError(__('You do not have access to this action.'));
    exit;
}

$page->breadcrumbs->add(__('Bulletin archive'));

$absoluteURL = rtrim($session->get('absoluteURL'), '/');
$archives = cr_listArchiveFolders();

$selected = cr_safeFolderName(is_string($_GET['archiveFolder'] ?? '') ? $_GET['archiveFolder'] : null);

$files = [];
$selectedDir = '';
if ($selected !== null && $selected !== '' && in_array($selected, array_map(fn($a) => $a['name'], $archives), true)) {
    $selectedDir = cr_getArchiveBaseDir() . DIRECTORY_SEPARATOR . $selected;
    if (is_dir($selectedDir)) {
        $items = @scandir($selectedDir);
        if (is_array($items)) {
            foreach ($items as $it) {
                if ($it === '.' || $it === '..') continue;
                $full = $selectedDir . DIRECTORY_SEPARATOR . $it;
                if (!is_file($full)) continue;
                $ext = strtolower(pathinfo($it, PATHINFO_EXTENSION));
                if (in_array($ext, ['html', 'pdf'], true)) {
                    $files[] = $it;
                }
            }
        }
    }
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);
}

$moduleStaticBase = $absoluteURL . '/modules/' . $moduleFolder . '/bulletin_archives/';

echo '<div style="max-width:900px;">';
echo '  <h2>' . __('Access generated bulletins') . '</h2>';

echo '  <div style="margin-bottom:14px; padding:12px; border:1px solid #ddd; border-radius:6px; background:#fafafa;">';
echo '    <label for="archiveFolderSelect" style="font-weight:bold;">' . __('Archive folder') . '</label><br>';
echo '    <select id="archiveFolderSelect" style="width:100%; max-width:520px;">';
echo '      <option value="">' . __('Select...') . '</option>';
foreach ($archives as $a) {
    $name = (string)$a['name'];
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $sel = ($selected !== null && $name === $selected) ? ' selected' : '';
    echo "      <option value=\"{$safeName}\"{$sel}>{$safeName}</option>";
}
echo '    </select>';
echo '  </div>';

echo '  <div id="archiveFiles">';
if ($selected === null || $selected === '') {
    echo '<div>' . __('Choose an archive folder to view bulletins.') . '</div>';
} else {
    echo '  <h3>' . __('Folder') . ': ' . htmlspecialchars((string)$selected, ENT_QUOTES, 'UTF-8') . '</h3>';
    if (!$files) {
        echo '<div>' . __('No bulletins found in this folder yet.') . '</div>';
    } else {
        echo '  <table style="width:100%; border-collapse:collapse;">';
        echo '    <thead>';
        echo '      <tr>';
        echo '        <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">' . __('File') . '</th>';
        echo '        <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">' . __('Actions') . '</th>';
        echo '      </tr>';
        echo '    </thead>';
        echo '    <tbody>';
        foreach ($files as $f) {
            $safeFile = htmlspecialchars($f, ENT_QUOTES, 'UTF-8');
            $hrefBase = $moduleStaticBase . rawurlencode((string)$selected) . '/';
            $href = $hrefBase . rawurlencode($f);
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            echo '      <tr>';
            echo '        <td style="border-bottom:1px solid #f0f0f0; padding:8px;">' . $safeFile . '</td>';
            echo '        <td style="border-bottom:1px solid #f0f0f0; padding:8px;">';
            echo '          <a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" target="_blank">' . __('Open') . '</a>';
            echo '        </td>';
            echo '      </tr>';
        }
        echo '    </tbody>';
        echo '  </table>';
    }
}
echo '  </div>';

echo '  <div style="margin-top:16px;">';
echo '    <a href="' . $absoluteURL . '/index.php?q=/modules/' . $moduleFolder . '/index.php">' . __('Back to generation') . '</a>';
echo '  </div>';

echo '</div>';

echo '<script>';
echo '  (function(){';
echo '    const select = document.getElementById("archiveFolderSelect");';
echo '    select.addEventListener("change", function(){';
echo '      const v = select.value || "";';
echo '      const url = ' . json_encode($absoluteURL . '/index.php?q=/modules/' . $moduleFolder . '/access_archive.php', JSON_UNESCAPED_UNICODE) . ' + (v ? ("&archiveFolder=" + encodeURIComponent(v)) : "");';
echo '      window.location.href = url;';
echo '    });';
echo '  })();';
echo '</script>';

