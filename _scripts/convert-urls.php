<?php
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$sNewDir = 'blog/_posts.new';
if (!is_dir($sNewDir)) {
   mkdir($sNewDir); 
}


foreach (glob('blog/_posts/*.md') as $sFile) {
    $sBasename = basename($sFile);
    
    printf("%s...\n", $sBasename);
    
    $sContents = file_get_contents($sFile);
    $aContents = explode("\n", $sContents);
    $aParts = explode('--', implode("\n", array_slice($aContents, 1)));
    $aYaml = Yaml::parse(trim(str_replace("  - \n", null, current($aParts))));
    
    $sContents = end($aParts);
    if (!preg_match_all('~<a href="([^"]+)"(?: title="([^"]+)")?>([^>]+)</a>~', $sContents, $aMatches, PREG_SET_ORDER)) {
        echo "    no url found\n";
        continue;
    }
    foreach ($aMatches as $aMatch) {
        $sMarkdown = sprintf('[%s](%s%s)', $aMatch[3], $aMatch[1], empty($aMatch[2]) ? null : sprintf(' "%s"', $aMatch[2]));
        printf("    replacing: %s\n", $sMarkdown);
        $sContents = str_replace($aMatch[0], $sMarkdown, $sContents);
    }

    $sNewFile = sprintf('%s/%s', $sNewDir, $sBasename);
    $sNewContents = '---' . "\n" . yamlDump($aYaml) . '---' . "\n" . trim($sContents);
    file_put_contents($sNewFile, $sNewContents);
    echo "    new file saved!\n";
}
// echo implode("\n", array_unique($aCharacters));

function yamlDump($aData) {
    return str_replace("'", null, Yaml::dump($aData, 4, 2));
}