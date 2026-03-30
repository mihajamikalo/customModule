<?php
/*
 * Custom rapport - Generate bulletin (Action 0)
 */

require_once __DIR__ . '/moduleFunctions.php';

$moduleFolder = $session->get('module');
$currentUrl = '/modules/' . $moduleFolder . '/index.php';

if (!isActionAccessible($guid, $connection2, $currentUrl)) {
    $page->addError(__('You do not have access to this action.'));
    exit;
}

$page->breadcrumbs->add(__('Generate bulletin'));

$yearGroups = cr_getYearGroups($connection2);
$archives = cr_listArchiveFolders();

$absoluteURL = rtrim($session->get('absoluteURL'), '/');
$ajaxBase = $absoluteURL . '/index.php?q=/modules/' . $moduleFolder . '/';
$previewBase = $absoluteURL . '/modules/' . $moduleFolder . '/bulletin_preview.html';

$defaultYearGroupID = $yearGroups[0]['id'] ?? '';
$defaultArchive = $archives[0]['name'] ?? '';

echo '<div style="max-width:1100px;">';
echo '  <h2>' . __('Generate bulletin') . '</h2>';
echo '  <div style="display:grid; grid-template-columns: 320px 1fr; gap: 16px;">';

echo '    <div style="border:1px solid #ddd; padding:12px; border-radius:6px; background:#fafafa;">';
echo '      <div style="margin-bottom:10px;">';
echo '        <label for="yearGroupSelect" style="font-weight:bold;">' . __('Year group') . '</label><br>';
echo '        <select id="yearGroupSelect" style="width:100%;">';
echo '          <option value="">' . __('Select...') . '</option>';
foreach ($yearGroups as $yg) {
    $id = htmlspecialchars((string)$yg['id'], ENT_QUOTES, 'UTF-8');
    $name = htmlspecialchars((string)$yg['name'], ENT_QUOTES, 'UTF-8');
    $selected = ($defaultYearGroupID !== '' && (string)$yg['id'] === (string)$defaultYearGroupID) ? ' selected' : '';
    echo "          <option value=\"{$id}\"{$selected}>{$name}</option>";
}
echo '        </select>';
echo '      </div>';

echo '      <div style="margin-bottom:10px;">';
echo '        <label for="archiveSelect" style="font-weight:bold;">' . __('Archive folder') . '</label><br>';
echo '        <select id="archiveSelect" style="width:100%;">';
echo '          <option value="">' . __('Select...') . '</option>';
foreach ($archives as $arch) {
    $name = (string)$arch['name'];
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $selected = ($defaultArchive !== '' && $name === $defaultArchive) ? ' selected' : '';
    echo "          <option value=\"{$safeName}\"{$selected}>{$safeName}</option>";
}
echo '        </select>';
echo '        <div style="margin-top:8px; font-size:12px;">';
echo '          <a href="' . $absoluteURL . '/index.php?q=/modules/' . $moduleFolder . '/create_archive.php">' . __('Create a new archive folder') . '</a>';
echo '        </div>';
echo '      </div>';

echo '      <div style="margin-bottom:10px;">';
echo '        <label for="studentSelect" style="font-weight:bold;">' . __('Student') . '</label><br>';
echo '        <select id="studentSelect" style="width:100%;">';
echo '          <option value="">' . __('Select year group first...') . '</option>';
echo '        </select>';
echo '      </div>';

echo '      <div id="cr-status" style="font-size:12px; color:#666; margin-top:10px;"></div>';
echo '    </div>';

echo '    <div style="border:1px solid #ddd; padding:12px; border-radius:6px;">';
echo '      <div style="font-weight:bold; margin-bottom:8px;">' . __('Bulletin preview') . '</div>';
echo '      <iframe id="bulletinIframe" style="width:100%; height:720px; border:0; background:#fff;" src="' . htmlspecialchars($previewBase, ENT_QUOTES, 'UTF-8') . '"></iframe>';
echo '    </div>';

echo '  </div>';
echo '</div>';

echo '<script>';
echo '  (function(){';
echo '    const ajaxBase = ' . json_encode($ajaxBase, JSON_UNESCAPED_UNICODE) . ';';
echo '    const previewBase = ' . json_encode($previewBase, JSON_UNESCAPED_UNICODE) . ';';
echo '    const yearGroupSelect = document.getElementById("yearGroupSelect");';
echo '    const studentSelect = document.getElementById("studentSelect");';
echo '    const archiveSelect = document.getElementById("archiveSelect");';
echo '    const status = document.getElementById("cr-status");';
echo '    const iframe = document.getElementById("bulletinIframe");';
echo '    let currentStudent = null;';

echo '    function setStatus(msg, isErr){';
echo '      status.textContent = msg || "";';
echo '      status.style.color = isErr ? "#b00020" : "#666";';
echo '    }';

echo '    function buildPreviewUrl(data){';
echo '      const archiveFolder = archiveSelect.value || "";';
echo '      const yearGroupId = yearGroupSelect.value || "";';
echo '      const params = new URLSearchParams();';
echo '      params.set("matricule", data.matricule || "");';
echo '      params.set("nom_prenoms", data.nom_prenoms || "");';
echo '      params.set("parcours", data.parcours || "");';
echo '      params.set("mail", data.mail || "");';
echo '      params.set("lieu_de_stage", data.lieu_de_stage || "");';
echo '      params.set("archiveFolder", archiveFolder);';
echo '      params.set("studentId", data.studentId || "");';
echo '      params.set("yearGroupId", yearGroupId);';
echo '      return previewBase + "?" + params.toString();';
echo '    }';

echo '    async function loadStudents(){';
echo '      const yearGroupId = yearGroupSelect.value;';
echo '      if (!yearGroupId){';
echo '        studentSelect.innerHTML = \'<option value="">Select...</option>\';';
echo '        currentStudent = null;';
echo '        iframe.src = previewBase;';
echo '        return;';
echo '      }';
echo '      setStatus(' . json_encode(__('Loading students...'), JSON_UNESCAPED_UNICODE) . ');';
echo '      studentSelect.innerHTML = \'<option value="">Loading...</option>\';';
echo '      try {';
echo '        const res = await fetch(ajaxBase + "studentsByYearGroup.php&yearGroupID=" + encodeURIComponent(yearGroupId));';
echo '        if (!res.ok) {';
echo '          const t = await res.text().catch(() => "");';
echo '          throw new Error("HTTP " + res.status + (t ? ": " + t : ""));';
echo '        }';
echo '        const json = await res.json().catch(() => ({}));';
echo '        const students = Array.isArray(json.students) ? json.students : [];';
echo '        studentSelect.innerHTML = \'<option value="">Select...</option>\';';
echo '        students.forEach(stu => {';
echo '          const opt = document.createElement("option");';
echo '          opt.value = stu.id;';
echo '          opt.textContent = stu.name || stu.id;';
echo '          studentSelect.appendChild(opt);';
echo '        });';
echo '        currentStudent = null;';
echo '        iframe.src = previewBase;';
echo '        if (students.length === 1){';
echo '          studentSelect.value = students[0].id;';
echo '          await loadStudentInfo();';
echo '        }';
echo '        setStatus("");';
echo '      } catch (e){';
echo '        setStatus(String(e.message || e) || "Error", true);';
echo '        studentSelect.innerHTML = \'<option value="">Error</option>\';';
echo '      }';
echo '    }';

echo '    async function loadStudentInfo(){';
echo '      const personID = studentSelect.value;';
echo '      if (!personID){';
echo '        currentStudent = null;';
echo '        iframe.src = previewBase;';
echo '        return;';
echo '      }';
echo '      const yearGroupId = yearGroupSelect.value || "";';
echo '      setStatus(' . json_encode(__('Loading student...'), JSON_UNESCAPED_UNICODE) . ');';
echo '      try {';
echo '        const url = ajaxBase + "studentInfo.php&personID=" + encodeURIComponent(personID) + "&yearGroupID=" + encodeURIComponent(yearGroupId);';
echo '        const res = await fetch(url);';
echo '        if (!res.ok) {';
echo '          const t = await res.text().catch(() => "");';
echo '          throw new Error("HTTP " + res.status + (t ? ": " + t : ""));';
echo '        }';
echo '        const json = await res.json().catch(() => ({}));';
echo '        currentStudent = json.student || null;';
echo '        if (currentStudent){';
echo '          iframe.src = buildPreviewUrl(currentStudent);';
echo '          setStatus("");';
echo '        }';
echo '      } catch (e){';
echo '        setStatus(String(e.message || e) || "Error", true);';
echo '      }';
echo '    }';

echo '    yearGroupSelect.addEventListener("change", loadStudents);';
echo '    studentSelect.addEventListener("change", loadStudentInfo);';
echo '    archiveSelect.addEventListener("change", function(){';
echo '      if (currentStudent) {';
echo '        iframe.src = buildPreviewUrl(currentStudent);';
echo '      } else {';
echo '        const yearGroupId = yearGroupSelect.value || "";';
echo '        const archiveFolder = archiveSelect.value || "";';
echo '        const params = new URLSearchParams();';
echo '        params.set("archiveFolder", archiveFolder);';
echo '        params.set("yearGroupId", yearGroupId);';
echo '        iframe.src = previewBase + "?" + params.toString();';
echo '      }';
echo '    });';

echo '    // Initial load';
echo '    if (' . ($defaultYearGroupID !== '' ? json_encode($defaultYearGroupID, JSON_UNESCAPED_UNICODE) : '""') . '){';
echo '      loadStudents();';
echo '    }';

echo '  })();';
echo '</script>';
