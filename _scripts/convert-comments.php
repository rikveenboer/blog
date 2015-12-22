<?php
require __DIR__ . '/../_php/autoload.php';

use Symfony\Component\Yaml\Yaml;

$sBase = 'http://localhost/jekyll/blog';

if (!isset($argv[1]) || !file_exists($argv[1])) {
    die('no comment file given!');
}
$aUrls = explode("\n", file_get_contents($argv[1]));

$aPosts = [];
foreach (glob('blog/_posts/*.md') as $sFile) {
    $sBasename = basename($sFile, '.md');

    $sContents = file_get_contents($sFile);
    $aContents = explode("\n", $sContents);
    $aParts = explode('--', implode("\n", array_slice($aContents, 1)));
    $aYaml = Yaml::parse(trim(str_replace("  - \n", null, current($aParts))));

    if (isset($aYaml['id'])) {        
        $aName = explode('-', $sBasename);        
        $aPosts[$aYaml['id']] = [$sBasename, implode('-', array_slice($aName, 3))];
    }
}

foreach ($aUrls as $sUrl) {
    $aUrl = explode('=', $sUrl);
    $sNewUrl = null;
    if (count($aUrl) > 1) {
        $sReference = trim(end($aUrl));
        if (is_numeric($sReference) && isset($aPosts[$sReference][0])) {
            $sName = $aPosts[$sReference][0];
            $aName = explode('-', $sName);
            $sNewUrl = sprintf('%s/%s/%s.html', $sBase, implode('/', array_slice($aName, 0, 3)), implode('-', array_slice($aName, 3)));
        } else {
            // printf("    %s\n", $sReference);
            $aOptions = [];
            foreach ($aPosts as $aPost) {
                if (strpos($aPost[1], $sReference) !== false) {
                    $aName = explode('-', $aPost[0]); 
                    $aOptions[] = sprintf('%s/%s/%s.html', $sBase, implode('/', array_slice($aName, 0, 3)), implode('-', array_slice($aName, 3)));
                }
            }
            $iCount = count($aOptions);
            if ($iCount > 0) {
                $sNewUrl = implode(';', $aOptions);
            }
        }
    }
    if ($sNewUrl) {
        printf("%s, %s\n", trim($sUrl), $sNewUrl);
    }
}