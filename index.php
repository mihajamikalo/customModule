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

$loadingStudentsText = json_encode(__('Loading students...'), JSON_UNESCAPED_UNICODE);
$loadingStudentText  = json_encode(__('Loading student...'), JSON_UNESCAPED_UNICODE);
$ajaxBaseJs   = json_encode($ajaxBase, JSON_UNESCAPED_UNICODE);
$previewBaseJs = json_encode($previewBase, JSON_UNESCAPED_UNICODE);
$defaultYearGroupJs = ($defaultYearGroupID !== '' ? json_encode($defaultYearGroupID, JSON_UNESCAPED_UNICODE) : '""');

$js = <<<JS
(function () {
  var ajaxBase = {$ajaxBaseJs};
  var previewBase = {$previewBaseJs};
  var yearGroupSelect = document.getElementById("yearGroupSelect");
  var studentSelect = document.getElementById("studentSelect");
  var archiveSelect = document.getElementById("archiveSelect");
  var status = document.getElementById("cr-status");
  var iframe = document.getElementById("bulletinIframe");
  var currentStudent = null;

  function setStatus(msg, isErr) {
    status.textContent = msg || "";
    status.style.color = isErr ? "#b00020" : "#666";
  }

  function buildPreviewUrl(data) {
    var archiveFolder = archiveSelect.value || "";
    var yearGroupId = yearGroupSelect.value || "";
    var params = new URLSearchParams();
    params.set("matricule", data.matricule || "");
    params.set("nom_prenoms", data.nom_prenoms || "");
    params.set("parcours", data.parcours || "");
    params.set("mail", data.mail || "");
    params.set("lieu_de_stage", data.lieu_de_stage || "");
    params.set("archiveFolder", archiveFolder);
    params.set("studentId", data.studentId || "");
    params.set("yearGroupId", yearGroupId);
    return previewBase + "?" + params.toString();
  }

  function loadStudents() {
    var yearGroupId = yearGroupSelect.value;
    if (!yearGroupId) {
      studentSelect.innerHTML = '<option value="">Select...</option>';
      currentStudent = null;
      iframe.src = previewBase;
      return;
    }

    setStatus({$loadingStudentsText});
    studentSelect.innerHTML = '<option value="">Loading...</option>';

    fetch(ajaxBase + "studentsByYearGroup.php&yearGroupID=" + encodeURIComponent(yearGroupId))
      .then(function (res) {
        if (!res.ok) {
          return res.text().then(function (t) {
            throw new Error("HTTP " + res.status + (t ? ": " + t : ""));
          });
        }
        return res.json();
      })
      .then(function (json) {
        var students = (json && Array.isArray(json.students)) ? json.students : [];
        studentSelect.innerHTML = '<option value="">Select...</option>';
        for (var i = 0; i < students.length; i++) {
          var stu = students[i];
          var opt = document.createElement("option");
          opt.value = stu.id;
          opt.textContent = stu.name || stu.id;
          studentSelect.appendChild(opt);
        }
        currentStudent = null;
        iframe.src = previewBase;
        setStatus("");
      })
      .catch(function (e) {
        setStatus(String(e && e.message ? e.message : e) || "Error", true);
        studentSelect.innerHTML = '<option value="">Error</option>';
      });
  }

  function loadStudentInfo() {
    var personID = studentSelect.value;
    if (!personID) {
      currentStudent = null;
      iframe.src = previewBase;
      return;
    }

    var yearGroupId = yearGroupSelect.value || "";
    setStatus({$loadingStudentText});

    fetch(ajaxBase + "studentInfo.php&personID=" + encodeURIComponent(personID) + "&yearGroupID=" + encodeURIComponent(yearGroupId))
      .then(function (res) {
        if (!res.ok) {
          return res.text().then(function (t) {
            throw new Error("HTTP " + res.status + (t ? ": " + t : ""));
          });
        }
        return res.json();
      })
      .then(function (json) {
        currentStudent = json ? json.student : null;
        if (currentStudent) {
          iframe.src = buildPreviewUrl(currentStudent);
          setStatus("");
        }
      })
      .catch(function (e) {
        setStatus(String(e && e.message ? e.message : e) || "Error", true);
      });
  }

  yearGroupSelect.addEventListener("change", loadStudents);
  studentSelect.addEventListener("change", loadStudentInfo);
  archiveSelect.addEventListener("change", function () {
    if (currentStudent) {
      iframe.src = buildPreviewUrl(currentStudent);
    } else {
      var yearGroupId = yearGroupSelect.value || "";
      var archiveFolder = archiveSelect.value || "";
      var params = new URLSearchParams();
      params.set("archiveFolder", archiveFolder);
      params.set("yearGroupId", yearGroupId);
      iframe.src = previewBase + "?" + params.toString();
    }
  });

  if ({$defaultYearGroupJs}) {
    loadStudents();
  }
})();
JS;

echo "<script>\\n{$js}\\n</script>";
