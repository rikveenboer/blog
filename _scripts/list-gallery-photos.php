<?php
require __DIR__ . '/../_php/autoload.php';

use Symfony\Component\Yaml\Yaml;

foreach (glob('gallery/*') as $sAlbumPath) {
    if (!is_dir($sAlbumPath)) continue;
    $sAlbum = basename($sAlbumPath);
    foreach (glob($sAlbumPath . '/*.md') as $sFile) {
        $sPhoto = basename($sFile);
        $sContents = file_get_contents($sFile);
        $aContents = explode("\n", $sContents);
        $aParts = explode('--', implode("\n", array_slice($aContents, 1)));
        $aYaml = Yaml::parse(trim(str_replace("  - \n", null, current($aParts))));
        if (isset($aYaml['file'])) {
            $sOriginal = basename($aYaml['file'], '.JPG');
            printf("[%s] %s = %s\n", $sAlbum, $sPhoto, $sOriginal);
        }
    }
}