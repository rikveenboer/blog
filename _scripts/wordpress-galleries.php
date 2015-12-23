<?php
if (isset($argv[1])) {
    $sSourceDir =  realpath(rtrim($argv[1]));
    unset($argv[1]);
    if (!is_dir($sSourceDir)) {
        die('Directory does not exist');
    }
}

require __DIR__ . '/generate-gallery-yaml.php';
require __DIR__ . '/../_php/autoload.php';

use Symfony\Component\Yaml\Yaml;

$oPDO = new PDO('mysql:dbname=blog;host=localhost;port=3311', 'root', 'root');

$sQuery = 'SELECT gid, path, title, galdesc FROM wp_ngg_gallery';
if ($oStatement = $oPDO->query($sQuery)) {
    $aCommands = [];
    foreach ($oStatement as $aRow) {
        $iId = $aRow['gid'];
        $sTitle = $aRow['title'];
        $sPath = $aRow['path'];
        $sDescription = $aRow['galdesc'];
        $sQuery = sprintf('SELECT pid, filename, description, imagedate FROM wp_ngg_pictures WHERE galleryid = %d', $iId);
        $aFiles = [];
        if ($oStatement = $oPDO->query($sQuery)) {
            foreach ($oStatement as $aRow) {
                 $iYear = substr($aRow['imagedate'], 0, 4);
                 $aFiles[basename($aRow['filename'])] = [
                    'id' => $aRow['pid'],
                    'title' => '',
                    'comment' => utf8_encode($aRow['description'])
                ];
            }
            $sDir = str_replace('wp-content/gallery/', null, $sPath);
            if (preg_match('/-[\d]{4}/', $sDir, $aMatch)) {
                $sCleanDir = str_replace($aMatch[0], null, $sDir);
            } else {
                $sCleanDir = $sDir;
            }
            $sName = sprintf('%d-%s', $iYear, strtolower(str_replace(' ', '-', $sCleanDir)));
            $aYaml = getMetaYaml($sDir, false, $sName, $sTitle, utf8_encode($sDescription));
            $aYaml['id'] = $iId;
            $aYaml['files'] = $aFiles;
            $sGalleryDir = sprintf('%s/%s', $sSourceDir, $sDir);
            if (!is_dir($sGalleryDir)) {
                printf("Path does not exist: %s\n", $sGalleryDir);
                continue;
            }
            writeMetaYaml($sGalleryDir, $aYaml);
            $aCommands[] = sprintf('call _scripts/generate-gallery %s "%s"', $sName, $sGalleryDir);
        } else {
            die('Failed to get pictures data');
        }
    }
    $aCommands[] = 'yekyll build';
    file_put_contents(sprintf('%s/commands.bat', $sSourceDir), implode("\n", $aCommands));
} else {
    die('Failed to get gallery data');
}