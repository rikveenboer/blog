<?php
require __DIR__ . '/utilities.php';

use Symfony\Component\Yaml\Yaml;

foreach (glob('_data/gallery/*.yml') as $sFile) {
    yamlParse($sFile, $aYaml, $sContents);
    preg_match_all('~\s{2}(\w{7}):\n~', $sContents, $aMatches);
    $aPhotos = $aMatches[1];
    unset($aYaml['map']['zoom']);
    $i = 0;
    foreach ($aYaml['photos'] as $sPhoto => $aPhoto) {
        $aPhoto['date'] = date('Y-m-d H:i:s', $aPhoto['date']);
        unset($aPhoto['sizes']);
        if (isset($aPhoto['location'])) {
            $aPhoto = array_merge($aPhoto, $aPhoto['location']);
            unset($aPhoto['location']);
        }
        unset($aYaml['photos'][$sPhoto]);
        $aYaml['photos'][$aPhotos[$i]] = $aPhoto;
        ++$i;
    }
    $aYaml['date'] = date('Y-m-d', $aYaml['date']);
    if (isset($aYaml['end_date'])) {
        $aYaml['end_date'] = date('Y-m-d', $aYaml['end_date']);
    }
    file_put_contents($sFile, trim(yamlDump($aYaml)));
}