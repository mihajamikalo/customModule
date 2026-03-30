<?php
include '../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

$moduleFolder = $gibbon->session->get('module');
$currentUrl = '/modules/' . $moduleFolder . '/bulletin_generateProcess.php';

if (!isActionAccessible($guid, $connection2, $currentUrl)) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$post = $_POST;

$archiveFolder = cr_safeFolderName(isset($post['archiveFolder']) ? (string)$post['archiveFolder'] : null);
if ($archiveFolder === null) {
    echo '<div style="color:#b00020; font-weight:bold;">Archive folder invalide.</div>';
    exit;
}

$baseDir = cr_getArchiveBaseDir();
if (!is_dir($baseDir)) {
    echo '<div style="color:#b00020; font-weight:bold;">Dossier de stockage introuvable.</div>';
    exit;
}

$targetDir = $baseDir . DIRECTORY_SEPARATOR . $archiveFolder;
if (!is_dir($targetDir)) {
    echo '<div style="color:#b00020; font-weight:bold;">Le dossier d\\\'archive n\\\'existe pas sur le serveur.</div>';
    exit;
}

// Student info
$studentId = isset($post['studentId']) ? (string)$post['studentId'] : '';
$yearGroupId = isset($post['yearGroupId']) ? (string)$post['yearGroupId'] : '';

$matricule = (string)($post['matricule'] ?? '');
$nom_prenoms = (string)($post['nom_prenoms'] ?? '');
$parcours = (string)($post['parcours'] ?? '');
$mail = (string)($post['mail'] ?? '');
$lieu_de_stage = (string)($post['lieu_de_stage'] ?? '');

$semestre = (string)($post['semestre'] ?? '1');
$annee = (string)($post['annee'] ?? '');

// UE definition mirrored from bulletin_preview.html
$ues = [
    'mcc' => [
        'id' => 'mcc',
        'name' => 'MARKETING COMMERCE COMMUNICATION',
        'courses' => [
            ['id' => 'mcc_1', 'name' => 'Elaborer un diagnostic marketing', 'max_credits' => 2],
            ['id' => 'mcc_2', 'name' => 'Posture et prise de parole', 'max_credits' => 1],
            ['id' => 'mcc_3', 'name' => 'Gestion de la relation client', 'max_credits' => 2],
            ['id' => 'mcc_4', 'name' => 'Outils de la performance commerciale', 'max_credits' => 2],
        ],
    ],
    'fin' => [
        'id' => 'fin',
        'name' => 'FINANCE CDG TQG',
        'courses' => [
            ['id' => 'fin_1', 'name' => 'Lecture et analyse de documents comptables', 'max_credits' => 2],
        ],
    ],
    'acp' => [
        'id' => 'acp',
        'name' => "ANALYSE ET CONCEPTION D'UN PLAN DE COMMUNICATION",
        'courses' => [
            ['id' => 'acp_1', 'name' => 'Théories de la com', 'max_credits' => 2],
            ['id' => 'acp_2', 'name' => 'Fondamentaux de la com', 'max_credits' => 2],
            ['id' => 'acp_3', 'name' => 'Identité visuelle', 'max_credits' => 2],
        ],
    ],
    'mho' => [
        'id' => 'mho',
        'name' => 'MANAGEMENT DES HOMMES DES ORGANISATIONS ET DES PROCESS',
        'courses' => [
            ['id' => 'mho_1', 'name' => 'Management de projet initial (méthodologie et outils)', 'max_credits' => 2],
            ['id' => 'mho_2', 'name' => 'Maitrise des outils informatiques', 'max_credits' => 1],
            ['id' => 'mho_3', 'name' => 'Communication écrite "Voltaire"', 'max_credits' => 1],
            ['id' => 'mho_4', 'name' => 'Méthodologie de travail', 'max_credits' => 1],
        ],
    ],
    'sed' => [
        'id' => 'sed',
        'name' => 'STRATÉGIE ECONOMIE DROIT',
        'courses' => [
            ['id' => 'sed_1', 'name' => 'Economie, simple et basique', 'max_credits' => 2],
        ],
    ],
    'lcc' => [
        'id' => 'lcc',
        'name' => 'LANGUES CULTURES CIVILISATIONS',
        'courses' => [
            ['id' => 'lcc_1', 'name' => 'Business English', 'max_credits' => 2],
            ['id' => 'lcc_2', 'name' => 'Communication skills', 'max_credits' => 2],
        ],
    ],
    'dtp' => [
        'id' => 'dtp',
        'name' => 'DOSSIERS ET TRAVAUX PROFESSIONNELS',
        'courses' => [
            ['id' => 'dtp_1', 'name' => 'Dossier Marketing', 'max_credits' => 4],
        ],
    ],
];

function cr_post_num(array $post, string $key, float $default = 0.0): float {
    if (!array_key_exists($key, $post)) {
        return $default;
    }
    $v = $post[$key];
    if ($v === '' || $v === null) {
        return $default;
    }
    return (float)$v;
}

function cr_post_text(array $post, string $key, string $default = ''): string {
    if (!array_key_exists($key, $post)) {
        return $default;
    }
    $v = $post[$key];
    if ($v === null) {
        return $default;
    }
    return trim((string)$v);
}

$ueTablesHtml = '';

$totalEarned = 0.0;
$wTotal = 0.0;
$cTotal = 0.0;
$ueAvgSum = 0.0;
$ueN = 0;

foreach ($ues as $ue) {
    $ueId = $ue['id'];
    $ueName = $ue['name'];
    $earned = 0.0;
    $maxC = 0.0;
    $wSum = 0.0;
    $cSum = 0.0;

    $courseRowsHtml = '';
    foreach ($ue['courses'] as $course) {
        $courseId = $course['id'];
        $courseName = $course['name'];
        $maxCredits = (float)$course['max_credits'];

        $creditsKey = 'credits_' . $courseId;
        $noteKey = 'note_' . $courseId;
        $commentKey = 'comment_' . $courseId;
        $absKey = 'abs_' . $courseId;
        $retardKey = 'retard_' . $courseId;

        $credits = cr_post_num($post, $creditsKey, 0.0);
        $note = cr_post_num($post, $noteKey, 0.0);
        $comment = cr_post_text($post, $commentKey, '');
        $abs = cr_post_text($post, $absKey, '-');
        $retard = cr_post_text($post, $retardKey, '0:00');

        $earned += $credits;
        $maxC += $maxCredits;
        $wSum += $credits * $note;
        $cSum += $credits;

        $courseRowsHtml .= '
        <tr>
            <td style="width:42%;">' . htmlspecialchars($courseName, ENT_QUOTES, 'UTF-8') . '</td>

            <td class="tc" style="width:8%;">
                <input type="number"
                       class="inp-credits"
                       name="' . htmlspecialchars($creditsKey, ENT_QUOTES, 'UTF-8') . '"
                       id="' . htmlspecialchars($creditsKey, ENT_QUOTES, 'UTF-8') . '"
                       min="0" max="' . (int)$maxCredits . '" step="1"
                       value="' . htmlspecialchars((string)$credits, ENT_QUOTES, 'UTF-8') . '"
                       title="Crédits (0 à ' . (int)$maxCredits . ')">
            </td>

            <td class="tc" style="width:8%;">
                <input type="number"
                       class="inp-note"
                       name="' . htmlspecialchars($noteKey, ENT_QUOTES, 'UTF-8') . '"
                       id="' . htmlspecialchars($noteKey, ENT_QUOTES, 'UTF-8') . '"
                       min="0" max="20" step="0.01"
                       value="' . htmlspecialchars((string)$note, ENT_QUOTES, 'UTF-8') . '"
                       title="Note sur 20">
            </td>

            <td style="width:27%;">
                <input type="text" class="inp-comment"
                       name="' . htmlspecialchars($commentKey, ENT_QUOTES, 'UTF-8') . '"
                       value="' . htmlspecialchars($comment, ENT_QUOTES, 'UTF-8') . '">
            </td>

            <td class="tc" style="width:7%;">
                <input type="text" class="inp-sm"
                       name="' . htmlspecialchars($absKey, ENT_QUOTES, 'UTF-8') . '"
                       value="' . htmlspecialchars($abs, ENT_QUOTES, 'UTF-8') . '">
            </td>

            <td class="tc" style="width:8%;">
                <input type="text" class="inp-sm"
                       name="' . htmlspecialchars($retardKey, ENT_QUOTES, 'UTF-8') . '"
                       value="' . htmlspecialchars($retard, ENT_QUOTES, 'UTF-8') . '"
                       style="width:48px;">
            </td>
        </tr>';
    }

    $avg = ($cSum > 0) ? ($wSum / $cSum) : null;
    $avgTxt = ($avg !== null) ? number_format((float)$avg, 2, '.', '') : '–';

    $valid = ($cSum > 0) && ($avg !== null) && ((float)$avg >= 10) && ($earned >= $maxC);

    $badgeText = $valid ? 'Validé' : 'Non Validé';
    $badgeColor = $valid ? '#000' : '#c0392b';

    $totalCreditsTxt = ($cSum > 0 || $maxC > 0) ? (string)($earned) . '/' . (string)($maxC) : '–/–';
    $totalCreditsTxt = rtrim(rtrim($totalCreditsTxt, '0'), '.') . '/' . (int)$maxC;

    $courseRowsHtml .= '
        <tr class="bul-summary-row">
            <td class="bul-summary-label">Nombre de crédits obtenus / note moyenne</td>
            <td class="tc">
                <span id="total-credits-' . htmlspecialchars($ueId, ENT_QUOTES, 'UTF-8') . '" class="bul-total-val">
                    ' . htmlspecialchars((string)$totalCreditsTxt, ENT_QUOTES, 'UTF-8') . '
                </span>
            </td>
            <td class="tc">
                <span id="total-note-' . htmlspecialchars($ueId, ENT_QUOTES, 'UTF-8') . '" class="bul-total-val">
                    ' . htmlspecialchars((string)$avgTxt, ENT_QUOTES, 'UTF-8') . '
                </span>
            </td>
            <td colspan="3" class="bul-validation">
                <span id="validation-' . htmlspecialchars($ueId, ENT_QUOTES, 'UTF-8') . '" style="color:' . $badgeColor . '; font-weight:normal;">'
                    . $badgeText .
                '</span>
            </td>
        </tr>';

    if ($cSum > 0) {
        $ueAvgSum += ($wSum / $cSum);
        $ueN++;
    }

    $totalEarned += $earned;
    $wTotal += $wSum;
    $cTotal += $cSum;

    $ueTablesHtml .= '
    <table class="bul-ue-table" id="ue-table-' . htmlspecialchars($ueId, ENT_QUOTES, 'UTF-8') . '">
        <thead>
            <tr>
                <th>' . htmlspecialchars($ueName, ENT_QUOTES, 'UTF-8') . '</th>
                <th class="th-center">Crédits</th>
                <th class="th-center">Notes</th>
                <th class="th-comment th-center">Commentaire</th>
                <th class="th-center">Abs</th>
                <th class="th-center">Retard</th>
            </tr>
        </thead>
        <tbody>
            ' . $courseRowsHtml . '
        </tbody>
    </table>';
}

$footerCredits = ($cTotal > 0) ? (string)$totalEarned : '–';
$footerMoyGen = ($cTotal > 0) ? number_format((float)($wTotal / $cTotal), 2, '.', '') : '–';
$footerMoyUE = ($ueN > 0) ? number_format((float)($ueAvgSum / $ueN), 2, '.', '') : '–';

$templatePath = __DIR__ . '/bulletin_preview.html';
$templateHtml = @file_get_contents($templatePath);
if ($templateHtml === false) {
    echo '<div style="color:#b00020; font-weight:bold;">Impossible de lire le template bulletin_preview.html.</div>';
    exit;
}

// 1) Insert UE tables (static output, no JS buildTables() call)
$templateHtml = str_replace('<div id="ue-container"></div>', $ueTablesHtml, $templateHtml);

// 2) Set footer totals
$templateHtml = str_replace(
    '<span class="bul-display-val" id="footer-credits">–</span>',
    '<span class="bul-display-val" id="footer-credits">' . htmlspecialchars($footerCredits, ENT_QUOTES, 'UTF-8') . '</span>',
    $templateHtml
);
$templateHtml = str_replace('<span id="footer-moy-gen">–</span>', '<span id="footer-moy-gen">' . htmlspecialchars($footerMoyGen, ENT_QUOTES, 'UTF-8') . '</span>', $templateHtml);
$templateHtml = str_replace('<span id="footer-moy-ue">–</span>', '<span id="footer-moy-ue">' . htmlspecialchars($footerMoyUE, ENT_QUOTES, 'UTF-8') . '</span>', $templateHtml);

// 3) Set student and header inputs (value attributes)
// Semestre
$templateHtml = preg_replace('/(<input type="number"[^>]*name="semestre"[^>]*id="semestre"[^>]*value=")([^"]*)(")/u', '$1' . htmlspecialchars($semestre, ENT_QUOTES, 'UTF-8') . '$3', $templateHtml, 1);
// Année
$templateHtml = preg_replace('/(<input type="text"[^>]*name="annee"[^>]*id="annee"[^>]*value=")([^"]*)(")/u', '$1' . htmlspecialchars($annee, ENT_QUOTES, 'UTF-8') . '$3', $templateHtml, 1);

// Student inputs in the template initially have no value=""
$eMatricule = htmlspecialchars($matricule, ENT_QUOTES, 'UTF-8');
$eNom       = htmlspecialchars($nom_prenoms, ENT_QUOTES, 'UTF-8');
$eParcours  = htmlspecialchars($parcours, ENT_QUOTES, 'UTF-8');
$eMail      = htmlspecialchars($mail, ENT_QUOTES, 'UTF-8');
$eStage     = htmlspecialchars($lieu_de_stage, ENT_QUOTES, 'UTF-8');

// Add value="" just before closing ">" for inputs with placeholders.
$templateHtml = preg_replace(
    '/(<input[^>]*name="matricule"[^>]*id="matricule"[^>]*placeholder="[^"]*")\s*>/u',
    '$1 value="' . $eMatricule . '">',
    $templateHtml,
    1
);
$templateHtml = preg_replace(
    '/(<input[^>]*name="nom_prenoms"[^>]*id="nom_prenoms"[^>]*placeholder="[^"]*")\s*>/u',
    '$1 value="' . $eNom . '">',
    $templateHtml,
    1
);
$templateHtml = preg_replace(
    '/(<input[^>]*name="parcours"[^>]*id="parcours"[^>]*placeholder="[^"]*")\s*>/u',
    '$1 value="' . $eParcours . '">',
    $templateHtml,
    1
);
$templateHtml = preg_replace(
    '/(<input[^>]*name="mail"[^>]*id="mail"[^>]*placeholder="[^"]*")\s*>/u',
    '$1 value="' . $eMail . '">',
    $templateHtml,
    1
);
$templateHtml = preg_replace(
    '/(<input[^>]*name="lieu_de_stage"[^>]*id="lieu_de_stage"[^>]*placeholder="[^"]*")\s*>/u',
    '$1 value="' . $eStage . '">',
    $templateHtml,
    1
);

// Hidden fields already have value="", so we replace them normally.
$eArchiveFolder = htmlspecialchars($archiveFolder, ENT_QUOTES, 'UTF-8');
$eStudentId     = htmlspecialchars($studentId, ENT_QUOTES, 'UTF-8');
$eYearGroupId   = htmlspecialchars($yearGroupId, ENT_QUOTES, 'UTF-8');
$templateHtml = preg_replace(
    '/(<input[^>]*name="archiveFolder"[^>]*id="archiveFolder"[^>]*value=")[^"]*(")/u',
    '$1' . $eArchiveFolder . '$2',
    $templateHtml,
    1
);
$templateHtml = preg_replace(
    '/(<input[^>]*name="studentId"[^>]*id="studentId"[^>]*value=")[^"]*(")/u',
    '$1' . $eStudentId . '$2',
    $templateHtml,
    1
);
$templateHtml = preg_replace(
    '/(<input[^>]*name="yearGroupId"[^>]*id="yearGroupId"[^>]*value=")[^"]*(")/u',
    '$1' . $eYearGroupId . '$2',
    $templateHtml,
    1
);

// 4) Remove JS init calls so our static UE tables are not overwritten.
$templateHtml = preg_replace(
    '/\/\/ ── Initialise on load ──[\\t ]*\\r?\\n[\\t ]*buildTables\\(\\);[\\t ]*\\r?\\n[\\t ]*recalcFooter\\(\\);/u',
    '',
    $templateHtml,
    1
);

$fileBase = 'bulletin_' . ($studentId !== '' ? $studentId : 'student') . '_' . date('Ymd_His');
$htmlOutPath = $targetDir . DIRECTORY_SEPARATOR . $fileBase . '.html';
@file_put_contents($htmlOutPath, $templateHtml);

$pdfOutPath = $targetDir . DIRECTORY_SEPARATOR . $fileBase . '.pdf';
$pdfGenerated = false;

// Optional PDF generation using dompdf if installed.
try {
    $autoload = __DIR__ . '/../../vendor/autoload.php';
    if (!class_exists('Dompdf\\Dompdf') && file_exists($autoload)) {
        require_once $autoload;
    }
    if (class_exists('Dompdf\\Dompdf')) {
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml($templateHtml);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfBytes = $dompdf->output();
        @file_put_contents($pdfOutPath, $pdfBytes);
        $pdfGenerated = true;
    }
} catch (Throwable $e) {
    $pdfGenerated = false;
}

$absoluteURL = rtrim($gibbon->session->get('absoluteURL'), '/');
$staticBase = $absoluteURL . '/modules/' . $moduleFolder . '/bulletin_archives/' . rawurlencode($archiveFolder) . '/';

$htmlLink = $staticBase . rawurlencode($fileBase . '.html');
$pdfLink = $staticBase . rawurlencode($fileBase . '.pdf');

echo '<div style="font-family:Arial,sans-serif; font-size:13px;">';
echo '<div style="padding:10px; border:1px solid #0a0; background:#efe; color:#060; margin-bottom:10px;">';
echo htmlspecialchars(__('Bulletin généré.'), ENT_QUOTES, 'UTF-8');
echo '</div>';

echo '<div style="margin-bottom:6px;">' . __('Archive') . ': <b>' . htmlspecialchars($archiveFolder, ENT_QUOTES, 'UTF-8') . '</b></div>';
echo '<div style="margin-bottom:6px;">' . __('Student') . ': <b>' . htmlspecialchars($nom_prenoms, ENT_QUOTES, 'UTF-8') . '</b></div>';

echo '<div style="margin-top:10px;">';
echo '<a href="' . htmlspecialchars($htmlLink, ENT_QUOTES, 'UTF-8') . '" target="_blank">' . __('Open HTML') . '</a>';
if ($pdfGenerated) {
    echo ' | <a href="' . htmlspecialchars($pdfLink, ENT_QUOTES, 'UTF-8') . '" target="_blank">' . __('Open PDF') . '</a>';
} else {
    echo ' | <span style="color:#666;">' . __('PDF non généré (dompdf manquant).') . '</span>';
}
echo '</div>';

echo '</div>';

