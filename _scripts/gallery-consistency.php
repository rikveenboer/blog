<?php
require __DIR__ . '/utilities.php';

$sAssetDir = __DIR__ . '/../../asset/gallery';
$sDataDir = __DIR__ . '/../_data/gallery';
$sFixDir = __DIR__ . '/../../x/gallery';
$bFix = true;

// Scan for photo files
$aFiles = [];
foreach (glob(sprintf('%s%s*', $sAssetDir, DIRECTORY_SEPARATOR)) as $sGalleryDir) {
    $sGallery = basename($sGalleryDir);
    $aPhotos = [];
    foreach (glob(sprintf('%s%s*', $sGalleryDir, DIRECTORY_SEPARATOR)) as $sFile) {
        $sPhoto = basename($sFile, '.jpg');
        list($sName, $sDimension) = explode('~', $sPhoto);
        if (isset($aPhotos[$sName])) {
            $aPhotos[$sName][] = $sDimension;
        } else {
            $aPhotos[$sName] = [$sDimension];
        }
    }
    $aFiles[$sGallery] = array_keys($aPhotos);
}

// Scan for gallery photos
foreach (glob(sprintf('%s%s*.yml', $sDataDir, DIRECTORY_SEPARATOR)) as $sFile) {
    yamlParse($sFile, $aYaml, $sContents);
    preg_match_all('~\s{2}(\w{7}):\n~', $sContents, $aMatches);
    $aPhotos = $aMatches[1];
    $sGallery = basename($sFile, '.yml');

    // Check match
    if (!isset($aFiles[$sGallery])) {
        printf("[%s] no files!\n", $sGallery);
    }

    // Compare
    $aFileOnly = array_diff($aFiles[$sGallery], $aPhotos);
    $aReferenceOnly = array_diff($aPhotos, $aFiles[$sGallery]);
    $bFileOnly = count($aFileOnly) > 0;
    $bReferenceOnly = count($aReferenceOnly) > 0;

    // Display results
    if ($bFileOnly || $bReferenceOnly) {
        printf("[%s]\n", $sGallery);
    }
    if ($bFileOnly) {
        printf("   files:\n        - %s\n", implode("\n        - ", $aFileOnly));
        if ($bFix) {
            $aFix = [];
            foreach ($aFileOnly as $sPhoto) {
                $sPhotoFile = sprintf('%s%s%s%s%s.md', $sFixDir, DIRECTORY_SEPARATOR, $sGallery, DIRECTORY_SEPARATOR, $sPhoto);
                if (!file_exists($sPhotoFile)) {
                    printf("    <%s> non existent!\n", $sPhoto);
                    continue;
                }
                parseFile($sPhotoFile, $aPhotoYaml);
                unset($aPhotoYaml['gallery']);
                unset($aPhotoYaml['layout']);
                unset($aPhotoYaml['next']);
                unset($aPhotoYaml['ordering']);
                unset($aPhotoYaml['previous']);
                unset($aPhotoYaml['sizes']);
                unset($aPhotoYaml['title']);
                if (!isset($aPhotoYaml['date'])) {
                    printf("    <%s> no date!\n", $sPhoto);
                    continue;
                } else {
                    $sDate = @date('Y-m-d H:i:s', $aPhotoYaml['date']);
                    $aPhotoYaml['date'] = $sDate ? $sDate : '!';
                }
                if (isset($aPhotoYaml['location'])) {
                    $aPhotoYaml = array_merge($aPhotoYaml, $aPhotoYaml['location']);
                    unset($aPhotoYaml['location']);
                }
                $aFix[$sPhoto] = $aPhotoYaml;
            }
            if (count($aFix) > 0) {
                $sFix = substr(yamlDump(array('' => $aFix)), 1);
                file_put_contents($sFile, trim(sprintf("%s%s", file_get_contents($sFile), $sFix)));
            }
        }
    }
    if ($bReferenceOnly) {
        printf("   references:\n        - %s\n", implode("\n        - ", $aReferenceOnly));
    }    
}