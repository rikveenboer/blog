<?php
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$oPDO = new PDO('mysql:dbname=blog;host=localhost;port=3311', 'root', 'root');

$sNewDir = 'blog/_posts.new';
if (!is_dir($sNewDir)) {
   mkdir($sNewDir); 
}

$i = 0;
foreach (glob('blog/_posts/*.md') as $sFile) {
    $sBasename = basename($sFile);
    
    printf("%s...\n", $sBasename);
    
    $sContents = file_get_contents($sFile);
    $aContents = explode("\n", $sContents);
    $aParts = explode('--', implode("\n", array_slice($aContents, 1)));
    $aYaml = Yaml::parse(trim(str_replace("  - \n", null, current($aParts))));
    
    if (strpos(end($aParts), '<') === false) {
        echo "    no html\n";
        continue;
    }

    if (!isset($aYaml['id'])) {
        echo "    no id\n";
        continue;
    }
    $iId = $aYaml['id'];  
    $sQuery = sprintf("SELECT post_content FROM wp_posts WHERE post_parent = %d OR guid LIKE '%%p=%d%%' ORDER BY post_date DESC LIMIT 1", $iId, $iId);
    if ($oStatement = $oPDO->query($sQuery)) {
        echo "    found match in database\n";
        foreach ($oStatement as $aRow) {
            $sContents = $aRow[0];
            break;
        }        
        $sNewFile = sprintf('%s/%s', $sNewDir, $sBasename);
        $sNewContents = '---' . "\n" . yamlDump($aYaml) . '---' . "\n" . trim($sContents);
        file_put_contents($sNewFile, $sNewContents);
        echo "    new file saved!\n";
    } else {
        echo "    no match in database\n";
    }
}

function yamlDump($aData) {
    return str_replace("'", null, Yaml::dump($aData, 4, 2));
}