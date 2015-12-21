<?php
require __DIR__ . '/../_php/autoload.php';

use Symfony\Component\Yaml\Yaml;

$sNewDir = 'blog/_posts.new';
if (!is_dir($sNewDir)) {
   mkdir($sNewDir); 
}

$aPosts = [];
foreach (glob('blog/_posts/*.md') as $sFile) {
    $sBasename = basename($sFile, '.md');
    $aPosts[implode('-', array_slice(explode('-', $sBasename), 3))] = $sBasename;
}

foreach (glob('blog/_posts/*.md') as $sFile) {
    $sBasename = basename($sFile);

    printf("%s...\n", $sBasename);

    $sContents = file_get_contents($sFile);
    $aContents = explode("\n", $sContents);
    $aParts = explode('--', implode("\n", array_slice($aContents, 1)));
    $aYaml = Yaml::parse(trim(str_replace("  - \n", null, current($aParts))));
    
    $sContents = end($aParts);
    if (!preg_match_all('~/\?ai1ec_event=([\w\d-]+)(?:[^ ")]*)~', $sContents, $aMatches, PREG_SET_ORDER)) {
        echo "    no local urls found\n";
        continue;
    }

    foreach ($aMatches as $aMatch) {
        $sName = $aMatch[1];
        if (isset($aPosts[$sName])) {
            $sPost = $aPosts[$sName];
            printf("    substituting name (%s) with: %s\n", $sName, $sPost);
            $sContents = str_replace($aMatch[0], sprintf('{%% post_link %s %%}', $sPost), $sContents);
        } else {
            printf("    unknown name: %s\n", $sName);
        }
    }

    $sNewFile = sprintf('%s/%s', $sNewDir, $sBasename);
    $sNewContents = '---' . "\n" . yamlDump($aYaml) . '---' . "\n" . trim($sContents);
    file_put_contents($sNewFile, $sNewContents);
    echo "    new file saved!\n";
}

function yamlDump($aData) {
    return str_replace("'", null, Yaml::dump($aData, 4, 2));
}