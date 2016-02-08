<?php
if (!isset($argv[1])) {
    $sDir = __DIR__ . '/../../asset/gallery';
} else {
    $sDir = rtrim($argv[1], '"\'/\\');
}
if (!file_exists($sDir)) {
    die('Directory does not exist!');
}

// Scan for galleries
foreach (glob(sprintf('%s/*', $sDir, DIRECTORY_SEPARATOR)) as $sGalleryDir) {
   $sGallery = basename($sGalleryDir);

    // Map photos
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

    // Iterate over photos
    $bFirst = true;
    foreach ($aPhotos as $sPhoto => $aDimensions) {
        if (!in_array('640w', $aDimensions)) {
            if ($bFirst) {
                printf("%s\n", $sGallery);
                $bFirst = false;
            }
            printf("    %s\n", $sPhoto);
        }
    }
}