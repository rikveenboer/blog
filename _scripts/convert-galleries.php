<?php
require __DIR__ . '/../_php/autoload.php';

use Symfony\Component\Yaml\Yaml;

$sCollectionDir = '_gallery';
$sGalleryDir = 'gallery';
$sDataDir = '_data/gallery';

if (!file_exists($sDataDir)) {
    mkdir($sDataDir);
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
    $aPhotos = [];
    foreach (glob(sprintf('%s/%s/*.md', $sGalleryDir, $sGallery)) as $sPhotoFile) {
        $sName = basename($sPhotoFile, '.md');
        $aYaml['photos'][] = $sName;
        parseFile($sPhotoFile, $aPhotoYaml);
        if (isset($aPhotoYaml['date'])) {
            $aPhotos[$aPhotoYaml['date']] = $sName;
        }
    }
    ksort($aPhotos);
    $sDataFile = sprintf('%s/%s.yml', $sDataDir, $sGallery);
    $aYaml['highlight'] = sprintf('%07s', current($aPhotos));
    file_put_contents($sDataFile, yamlDump(array_values($aPhotos)));
    unset($aYaml['photos']);
    writeFile($sFile, $aYaml, $sContents);
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