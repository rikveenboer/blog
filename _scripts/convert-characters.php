<?php
require __DIR__ . '/../_php/autoload.php';

use Symfony\Component\Yaml\Yaml;

$sNewDir = 'blog/_posts.new';
if (!is_dir($sNewDir)) {
   mkdir($sNewDir); 
}

$sConvert = <<<EOL
8220="
8221="
8217='
8230=!
8216='
8211=-
8243="
EOL;
$aConversions = [];
foreach (explode("\n", $sConvert) as $sConversion) {
    list($iCode, $sConversion) = explode('=', $sConversion);
    $aConversions[$iCode] = $sConversion;
}

$aCharacters = [];
foreach (glob('blog/_posts/*.md') as $sFile) {
    $sBasename = basename($sFile);
    
    printf("%s...\n", $sBasename);
    
    $sContents = file_get_contents($sFile);
    $aContents = explode("\n", $sContents);
    $aParts = explode('--', implode("\n", array_slice($aContents, 1)));
    $aYaml = Yaml::parse(trim(str_replace("  - \n", null, current($aParts))));
    
    $sContents = end($aParts);
    if (!preg_match_all('/&#([\d]+);/', $sContents, $aMatches)) {
        echo "    no special character\n";
        continue;
    }
    $aCurrentCharacters = array_unique($aMatches[1]);
    $aCharacters = array_merge($aCharacters, $aCurrentCharacters);
    $aCurrentCharacters = array_flip($aCurrentCharacters);
    foreach ($aConversions as $iCode => $sConversion) {
        unset($aCurrentCharacters[$iCode]);
        $sContents = str_replace(sprintf('&#%d;', $iCode), $sConversion, $sContents);
    }
    if (count($aCurrentCharacters) > 0) {
        printf("    unknown characters: %s", implode(', ', array_keys($aCurrentCharacters)));
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