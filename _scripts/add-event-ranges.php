<?php
require __DIR__ . '/../_php/autoload.php';

use Symfony\Component\Yaml\Yaml;

$oPDO = new PDO('mysql:dbname=blog;host=localhost;port=3311', 'root', 'root');

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


    if (!isset($aYaml['id'])) {
        continue;
    }
    $iId = $aYaml['id'];

    $sQuery = sprintf("SELECT start, end FROM wp_ai1ec_events WHERE post_id = %d", $iId);
    if ($oStatement = $oPDO->query($sQuery)) {
        foreach ($oStatement as $aRow) {
            $iStart = $aRow[0];
            $iEnd = $aRow[1];
            break;
        }
        $oStart = new DateTime();
        $oStart->setTimeStamp($iStart);
        $oEnd = new DateTime();
        $oEnd->setTimeStamp($iEnd);
        if ($oStart->diff($oEnd)->format('%a') > 1) {
            $sStart = $oStart->format('Y-m-d');
            $sEnd = $oEnd->format('Y-m-d');
            printf("    from %s to %s\n", $sStart, $sEnd);
            $aYaml['end_date'] = $sEnd;
            $sNewFile = sprintf('%s/%s', $sNewDir, $sBasename);
            $sNewContents = '---' . "\n" . yamlDump($aYaml) . '---' . "\n" . trim($sContents);
            file_put_contents($sNewFile, $sNewContents);
            echo "    new file saved!\n";
        }
    } else {
        echo "Not found!\n";
    }


}

function yamlDump($aData) {
    return str_replace("'", null, Yaml::dump($aData, 4, 2));
}