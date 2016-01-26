<?php
require __DIR__ . '/../_php/autoload.php';

use Symfony\Component\Yaml\Yaml;

$sCollectionDir = '_old__gallery';
$sGalleryDir = '_old_gallery';
$sDataDir = '_data/_gallery';
$sNewDataDir = '_data/gallery';

if (!file_exists($sNewDataDir)) {
    mkdir($sNewDataDir);
}

foreach (glob($sCollectionDir . '/*.md') as $sFile) {
    $sGallery = basename($sFile, '.md');
    printf("%s..\n", $sGallery);

    parseFile($sFile, $aYaml, $sContents);
    if (strpos($aYaml['title'], ' ') !== false) {
        $aYaml['title'] = sprintf('"%s"', $aYaml['title']);
    }
    $aYaml['date'] = date('Y-m-d', $aYaml['date']);
    if (isset($aYaml['end_date'])) {
        $aYaml['end_date'] = date('Y-m-d', $aYaml['end_date']);
    }
    unset($aYaml['highlight']);
    
    $aPhotos = explode("\n", trim(str_replace('- ', null, file_get_contents(sprintf('%s/%s.yml', $sDataDir, $sGallery)))));
    foreach ($aPhotos as $sName) {
        parseFile(sprintf('%s/%s/%s.md', $sGalleryDir, $sGallery, $sName), $aPhotoYaml, $sContents);
        if (isset($aPhotoYaml['date'])) {
            $aPhotoYaml['date'] = date('Y-m-d H:i:s', $aPhotoYaml['date']);
        }
        if (!empty($sContents)) {
            $aPhotoYaml['description'] = sprintf('"%s"', $sContents);
        }
        if (empty($aPhotoYaml['title']) == 'null') {
            unset($aPhotoYaml['title']);
        }
        unset($aPhotoYaml['gallery']);
        unset($aPhotoYaml['layout']);
        unset($aPhotoYaml['next']);
        unset($aPhotoYaml['previous']);
        unset($aPhotoYaml['ordering']);
        $aYaml['photos'][$sName] = $aPhotoYaml;
    }
    $sNewDataFile = sprintf('%s/%s.yml', $sNewDataDir, $sGallery);
    file_put_contents($sNewDataFile, trim(yamlDump($aYaml)));
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