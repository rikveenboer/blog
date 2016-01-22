<?php
require __DIR__ . '/../_php/autoload.php';

use Symfony\Component\Yaml\Yaml;

$sOriginalDir = '_gallery.orig';
$sCollectionDir = '_gallery';
$sGalleryDir = 'gallery';

if (!is_dir($sCollectionDir)) {
   mkdir($sCollectionDir); 
}
if (!is_dir($sGalleryDir)) {
    mkdir($sGalleryDir);
}

foreach (glob($sOriginalDir . '/*/index.html') as $sFile) {
    $sBasename = basename($sFile, '.md');
    $sGallery = basename(dirname($sFile));
    printf("%s..\n", $sGallery);

    $sDir = sprintf('%s/%s', $sOriginalDir, $sGallery);
    $sMapFile = sprintf('%s/map.html', $sDir);

    parseFile($sFile, $aYaml, $sContents);
    if (strpos($sContents, 'gallery_list') !== false) {
        $sContents = null;
    }
    $aYaml['date'] = date('Y-m-d', $aYaml['date']);
    if (strpos($aYaml['title'], ' ') !== false) {
        $aYaml['title'] = sprintf('"%s"', $aYaml['title']);
    }
    if (isset($aYaml['end_date'])) {
        $aYaml['end_date'] = date('Y-m-d', $aYaml['end_date']);
    }
    unset($aYaml['highlight_photo']);
    unset($aYaml['layout']);
    unset($aYaml['map']);
    if (file_exists($sMapFile)) {
        parseFile($sMapFile, $aMapYaml, $sContents);
        if (isset($aMapYaml['gallery_map'])) {
            $aYaml['map'] = $aMapYaml['gallery_map'];
        }
    }

    $sCopyDir = sprintf('%s/%s', $sGalleryDir, $sGallery);
    if (!file_exists($sCopyDir)) {
        mkdir($sCopyDir);
    }

    $aYaml['photos'] = [];
    $aPhotos = [];
    foreach (glob(sprintf('%s/*.md', $sDir)) as $sFile) {
        $sName = basename($sFile, '.md');
        $aYaml['photos'][] = $sName;
        $sTargetFile = sprintf('%s/%s.md', $sCopyDir, $sName);
        parseFile($sFile, $aPhotoYaml, $sPhotoContents);
        // unset($aPhotoYaml['name']);
        if (isset($aPhotoYaml['exif'])) {
            $aPhotoYaml = array_merge($aPhotoYaml, $aPhotoYaml['exif']);
            unset($aPhotoYaml['exif']);
        }
        if (isset($aPhotoYaml['album'])) {
            $aPhotoYaml['gallery'] = $aPhotoYaml['album'];            
            unset($aPhotoYaml['album']);
        }
        if (isset($aPhotoYaml['date'])) {
            $aPhotoYaml['date'] = date('Y-m-d H:i:s', $aPhotoYaml['date']);
            $aPhotos[$aPhotoYaml['date']] = $sName;
        }
        writeFile($sTargetFile, $aPhotoYaml, $sPhotoContents);
    }
    ksort($aPhotos);
    // $aYaml['photos'] = array_values($aPhotos);
    unset($aYaml['photos']);
    $aYaml['highlight'] = current($aPhotos);

    $sIndexFile = sprintf('%s/%s.md', $sCollectionDir, $sGallery);
    // writeFile($sIndexFile, $aYaml, $sContents);
}

function parseFile($sFile, &$aYaml, &$sContents = null) {
    $sContents = file_get_contents($sFile);
    $aContents = explode("\n", $sContents);
    $aParts = explode('---', implode("\n", array_slice($aContents, 1)));  
    $aYaml = Yaml::parse(trim(str_replace("  - \n", null, current($aParts))));
    $sContents = trim(next($aParts));
}

function writeFile($sFile, $aYaml, $sContents) {
    file_put_contents($sFile, '---' . "\n" . yamlDump($aYaml) . '---' . "\n" . $sContents);
}

function yamlDump($aData) {
    return str_replace("'", null, Yaml::dump($aData, 4, 2));
}