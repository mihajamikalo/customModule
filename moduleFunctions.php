<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/



/**
 * Custom rapport (Gibbon custom module)
 * Helpers used by module pages.
 */

function cr_getArchiveBaseDir(): string {
    return __DIR__ . DIRECTORY_SEPARATOR . 'bulletin_archives';
}

function cr_safeFolderName(?string $name): ?string {
    if ($name === null) {
        return null;
    }
    $name = trim($name);
    if ($name === '') {
        return null;
    }
    // Allow common characters for folder names.
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_\\- ]{0,80}$/u', $name)) {
        return null;
    }
    // Collapse spaces to underscores for filesystem friendliness.
    $name = preg_replace('/\\s+/', '_', $name);
    return $name;
}

function cr_ensureDir(string $path): bool {
    if (is_dir($path)) {
        return true;
    }
    return @mkdir($path, 0755, true);
}

function cr_listArchiveFolders(): array {
    $baseDir = cr_getArchiveBaseDir();
    if (!is_dir($baseDir)) {
        return [];
    }

    $folders = [];
    $items = @scandir($baseDir);
    if (!is_array($items)) {
        return [];
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $full = $baseDir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full)) {
            $folders[] = $item;
        }
    }

    sort($folders, SORT_NATURAL | SORT_FLAG_CASE);
    return array_map(fn($n) => ['name' => $n], $folders);
}

function cr_getQueryAll($connection2, string $sql, array $params = []): array {
    if (!method_exists($connection2, 'prepare')) {
        throw new Exception('Gibbon connection does not support prepare().');
    }
    $stmt = $connection2->prepare($sql);
    foreach ($params as $key => $value) {
        if (is_int($key)) {
            $stmt->bindValue($key + 1, $value);
        } else {
            $stmt->bindValue(':' . ltrim((string)$key, ':'), $value);
        }
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}

function cr_getQueryOne($connection2, string $sql, array $params = []): ?array {
    $rows = cr_getQueryAll($connection2, $sql, $params);
    if (!$rows) {
        return null;
    }
    return $rows[0];
}

function cr_buildPersonDisplayName(array $personRow): string {
    $preferredName = $personRow['preferredName'] ?? null;
    $familyName    = $personRow['familyName'] ?? ($personRow['surname'] ?? null);
    $otherNames    = $personRow['otherNames'] ?? null;
    $givenName     = $personRow['givenName'] ?? null;

    $parts = [];
    if (!empty($preferredName)) {
        $parts[] = $preferredName;
    } elseif (!empty($givenName)) {
        $parts[] = $givenName;
    }

    if (!empty($familyName)) {
        $parts[] = $familyName;
    }

    if (!empty($otherNames)) {
        $parts[] = $otherNames;
    }

    $name = trim(implode(' ', array_filter($parts, fn($p) => $p !== null && $p !== '')));
    if ($name !== '') {
        return $name;
    }

    if (!empty($personRow['gibbonPersonID'])) {
        return (string)$personRow['gibbonPersonID'];
    }

    return '';
}

function cr_getYearGroups($connection2): array {
    $rows = cr_getQueryAll($connection2, 'SELECT * FROM gibbonYearGroup');
    $out = [];
    foreach ($rows as $row) {
        $id = $row['gibbonYearGroupID'] ?? ($row['yearGroupID'] ?? null);
        if ($id === null) {
            continue;
        }
        $name = $row['name'] ?? ($row['yearGroupName'] ?? ($row['shortName'] ?? ''));
        if ($name === '') {
            foreach ($row as $k => $v) {
                if (is_string($v) && $v !== '' && !in_array($k, ['gibbonYearGroupID', 'yearGroupID'], true)) {
                    $name = $v;
                    break;
                }
            }
        }
        $out[] = ['id' => (string)$id, 'name' => (string)$name];
    }

    usort($out, fn($a, $b) => strcasecmp($a['name'], $b['name']));
    return $out;
}

function cr_getYearGroupName($connection2, string $yearGroupID): string {
    $row = cr_getQueryOne(
        $connection2,
        'SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID = :id',
        ['id' => $yearGroupID]
    );
    if (!$row) {
        return '';
    }
    return (string)($row['name'] ?? ($row['yearGroupName'] ?? ''));
}

function cr_getStudentsByYearGroup($connection2, string $yearGroupID): array {
    $sql = '
        SELECT ge.gibbonPersonID AS personID, p.*
        FROM gibbonStudentEnrolment ge
        JOIN gibbonPerson p ON p.gibbonPersonID = ge.gibbonPersonID
        WHERE ge.gibbonYearGroupID = :yearGroupID
    ';

    $rows = cr_getQueryAll($connection2, $sql, ['yearGroupID' => $yearGroupID]);
    $students = [];
    foreach ($rows as $row) {
        $personID = $row['personID'] ?? ($row['gibbonPersonID'] ?? null);
        if ($personID === null) {
            continue;
        }

        $students[] = [
            'id' => (string)$personID,
            'name' => cr_buildPersonDisplayName($row),
        ];
    }

    usort($students, fn($a, $b) => strcasecmp($a['name'], $b['name']));
    return $students;
}

function cr_getStudentInfo($connection2, string $personID, ?string $yearGroupID = null): array {
    $personRow = cr_getQueryOne(
        $connection2,
        'SELECT * FROM gibbonPerson WHERE gibbonPersonID = :id',
        ['id' => $personID]
    );

    if (!$personRow) {
        return [
            'studentId' => $personID,
            'matricule' => '',
            'nom_prenoms' => '',
            'parcours' => $yearGroupID ? cr_getYearGroupName($connection2, $yearGroupID) : '',
            'mail' => '',
            'lieu_de_stage' => '',
        ];
    }

    $matricule = $personRow['registrationNumber']
        ?? ($personRow['matriculationNumber'] ?? ($personRow['studentNumber'] ?? ($personRow['externalIdentifier'] ?? '')));
    $mail = $personRow['emailAddress']
        ?? ($personRow['email'] ?? ($personRow['emailHome'] ?? ($personRow['preferredEmailAddress'] ?? '')));

    $nom = cr_buildPersonDisplayName($personRow);
    $parcours = $yearGroupID ? cr_getYearGroupName($connection2, $yearGroupID) : '';

    return [
        'studentId' => (string)$personID,
        'matricule' => (string)$matricule,
        'nom_prenoms' => (string)$nom,
        'parcours' => (string)$parcours,
        'mail' => (string)$mail,
        'lieu_de_stage' => '',
    ];
}