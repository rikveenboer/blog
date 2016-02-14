<?php
require __DIR__ . '/utilities.php';

$sGallery = '2016-conic-hill';
$sSourceDir = 'D:\Rik\Photographs\Schotland\Activiteiten\Hike\Conic Hill';
    $sFile = sprintf('%s/../_data/gallery/%s.yml', __DIR__, $sGallery);
    yamlParse($sFile, $aYaml, $sContents);
$sTargetDir = 'C:\Users\Rik\Downloads\tmp';

if (!is_dir($sTargetDir)) {
    mkdir($sTargetDir, 0777, true);
}

$aPhotos = array_map(function($aPhoto) {
    return $aPhoto['file'];
}, $aYaml['photos']);

foreach ($aPhotos as $sFile) {
    $sPath = sprintf('%s/%s.jpg', $sSourceDir, $sFile);
    if (!file_exists($sPath)) {
        printf("File not found: %s\n", $sFile);
    }
    $sTarget = sprintf('%s/%s.jpg', $sTargetDir, $sFile);
    copy($sPath, $sTarget);
}