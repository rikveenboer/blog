<?php
require __DIR__ . '/../_php/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

$oConsole = new Application();
$oConsole
    ->register('run')
    ->setDefinition([        
        new InputOption('export', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Target image export sizes'),
        new InputOption('layout', null, InputOption::VALUE_REQUIRED, 'Rendering layout for individual images', 'gallery-photo'),
        new InputArgument('name', InputArgument::REQUIRED, 'Gallery name'),
        new InputArgument('dir', InputArgument::REQUIRED, 'Directory to scan for images'),
        new InputArgument('assetdir', InputArgument::OPTIONAL, 'Asset directory for exported images', 'asset/gallery'),
        new InputArgument('mdowndir', InputArgument::OPTIONAL, 'Markdown directory for dumping individual photo details', 'gallery'),
    ])
    ->setDescription('Parse a YAML-like gallery configuration and export it.')
    ->setHelp('
    The export option will accept values like:
    200x110 - photo will outset the boundary with the dimensions being 200x110
    1280 - photo will inset with the largest dimension being 1280
    ')
    ->setCode(
        function (InputInterface $oInput, OutputInterface $oOutput) {
            // Get input arguments and options
            $sGallery = $oInput->getArgument('name');
            $sDir = realpath(rtrim($oInput->getArgument('dir'), '/\\'));
            $sAssetPath = $oInput->getArgument('assetdir') . '/' . $sGallery;
            $sRenderPath = $oInput->getArgument('mdowndir') . '/' . $sGallery;    
            $sLayout = $oInput->getOption('layout');
            $sExports = $oInput->getOption('export');

            $oImagine = new Imagine\Gd\Imagine();

            // Initialize directories
            if (!is_dir($sAssetPath)) {
                mkdir($sAssetPath, 0700, true);
            }
            if (!is_dir($sRenderPath)) {
                mkdir($sRenderPath, 0700, true);
            }
            
            // Check directory and presence of YAML file
            if (!is_dir($sDir)) {                
                $oOutput->writeln('<error>Import directory does not exist</error>');
                exit;
            }
            $sYamlFile = sprintf('%s/meta.yaml', $sDir);
            if (!file_exists($sYamlFile)) {                
                $oOutput->writeln('<error>No meta.yaml in directory</error>');
                exit;
            }

            // Parse YAML file
            $aYaml = Yaml::parse(file_get_contents($sYamlFile));
            if (!isset($aYaml['gallery']) || !isset($aYaml['files'])) {
                $oOutput->writeln('<error>Invalid YAML file</error>');
                exit;
            }
            $aGallery = $aYaml['gallery'];
            $aMeta = $aYaml['files'];
            $aFiles = array_keys($aYaml['files']);

            // Loop over files
            $sHighlight = null;
            $aPhotos = [];
            foreach ($aFiles as $i => $sFile) {
                // Build photo information
                $aPhoto = [
                    'path' => $sDir . '/' . $sFile,
                    'ordering' => $i,
                    'name' => isset($aMeta[$sFile]['name']) ? $aMeta[$sFile]['name'] : null,
                    'comment' => isset($aMeta[$sFile]['comment']) ? $aMeta[$sFile]['comment'] : null
                ];

                // Generate id from file contents
                $aPhoto['id'] = substr(sha1_file($aPhoto['path']), 0, 7);
                if (isset($aPhoto['title'])) {
                    $aPhoto['id'] .= '-' . preg_replace('/(-| )+/', '-', preg_replace('/[^a-z0-9 ]/i', '-', preg_replace('/\'/', '', strtolower(preg_replace('/\p{Mn}/u', '', Normalizer::normalize($aPhoto['title'], Normalizer::FORM_KD))))));
                }

                // Check if photo is highlighted
                if (empty($sHighlight) && isset($aMeta[$sFile]['highlight'])) {
                    $sHighlight = $aPhoto['id'];
                }

                // Parse selected EXIF data
                $aPhoto['exif'] = exif_read_data($aPhoto['path']);
                if (isset($aPhoto['exif']['GPSLongitude'])) {
                    $aPhoto = array_merge($aPhoto, [
                        'longitude' => coordinateToDegrees($aPhoto['exif']['GPSLongitude'], $aPhoto['exif']['GPSLongitudeRef']),
                        'latitude' => coordinateToDegrees($aPhoto['exif']['GPSLatitude'], $aPhoto['exif']['GPSLatitudeRef']),
                        'altitude' => fractionToFloat($aPhoto['exif']['GPSAltitude']),
                        'direction' => fractionToFloat($aPhoto['exif']['GPSImgDirection'])]);
                    $aLongitude[] = $aPhoto['longitude'];
                    $aLatitude[] = $aPhoto['latitude'];
                }
                $aPhoto['date'] = new DateTime($aPhoto['exif']['DateTimeOriginal']);
                $aPhotos[] = $aPhoto;
            }

            // Manipulate
            foreach ($aPhotos as $i => $aPhoto) {
                $oOutput->write('<info>' . $aPhoto['id'] . '</info>');
                $aPhoto['sizes'] = [];

                // Image exports
                if (false || 0 < count($sExports)) {
                    $oSourceJpg = $oImagine->open($aPhoto['path']);
                    if (isset($aPhoto['exif']['Orientation'])) {
                        switch ($aPhoto['exif']['Orientation']) {
                          case 2:
                            $oSourceJpg->mirror();                            
                            break;
                          case 3:
                            $oSourceJpg->rotate(180);                            
                            break;
                          case 4:
                            $oSourceJpg->rotate(180)->mirror();                            
                            break;
                          case 5:
                            $oSourceJpg->rotate(90)->mirror();                            
                            break;
                          case 6:
                            $oSourceJpg->rotate(90);                            
                            break;
                          case 7:
                            $oSourceJpg->rotate(-90)->mirror();                            
                            break;
                          case 8:
                            $oSourceJpg->rotate(-90);                            
                            break;
                        }
                    }

                    $oSourceSize = $oSourceJpg->getSize();
                    $oOutput->writeln(' [' . $oSourceSize->getWidth() . 'x' . $oSourceSize->getHeight() . ']...');

                    foreach ($sExports as $sExport) {
                        $oOutput->write('    <comment>' . $sExport . '</comment>');

                        if (false !== strpos($sExport, 'x')) {
                            list($iW, $iH) = explode('x', $sExport);
                            $sExportImage = $oSourceJpg->thumbnail(
                                new \Imagine\Image\Box($iW, $iH),
                                \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND
                            );
                        } else {
                            if ('w' == substr($sExport, -1)) {
                                $iX = (int) $sExport;
                                $iY = ($iX * $oSourceSize->getHeight()) / $oSourceSize->getWidth();
                            } elseif ('h' == substr($sExport, -1)) {
                                $iY = (int) $sExport;
                                $iX = ($iY * $oSourceSize->getWidth()) / $oSourceSize->getHeight();
                            } elseif ($oSourceSize->getWidth() == max($oSourceSize->getWidth(), $oSourceSize->getHeight())) {
                                $iX = (int) $sExport;
                                $iY = ($iX * $oSourceSize->getHeight()) / $oSourceSize->getWidth();
                            } elseif ($oSourceSize->getHeight() == max($oSourceSize->getWidth(), $oSourceSize->getHeight())) {
                                $iY = (int) $sExport;
                                $iX = ($iY * $oSourceSize->getWidth()) / $oSourceSize->getHeight();
                            }                            
                            $sExportImage = $oSourceJpg->thumbnail(
                                new \Imagine\Image\Box(ceil($iX), ceil($iY)),
                                \Imagine\Image\ImageInterface::THUMBNAIL_INSET
                            );
                        }

                        $sExportsize = $sExportImage->getSize();
                        $aPhoto['sizes'][$sExport] = [
                            'width' => $sExportsize->getWidth(),
                            'height' => $sExportsize->getHeight(),
                        ];

                        $oOutput->writeln(' [' . $sExportsize->getWidth() . 'x' . $sExportsize->getHeight() . ']');
                        $sExportPath = $sAssetPath . '/' . $aPhoto['id'] . '~' . $sExport . '.jpg';

                        // Write converted image
                        file_put_contents(
                            $sExportPath,
                            $sExportImage->get('jpeg', ['quality' => 90])
                        );

                        touch($sExportPath, $aPhoto['date']->getTimestamp());
                        $sExportImage = null;
                    }
                    $oSourceJpg = null;
                }

                $oOutput->write('    <comment>markdown</comment>');
                $aMatter = [
                    'layout' => $sLayout,
                    'gallery' => $sGallery,
                    'file' => basename($aPhoto['path'], '.jpg'),
                    'title' => isset($aPhoto['title']) ? $aPhoto['title'] : '',
                    'date' => $aPhoto['date']->format('Y-m-d H:i:s'),
                    'ordering' => $aPhoto['ordering']
                ];
                
                // Keep track of album dates
                $oDate = $i > 0 ? min($oDate, $aPhoto['date']) : $aPhoto['date'];
                $oEndDate = $i > 0 ? max($oEndDate, $aPhoto['date']) : $aPhoto['date'];

                if (isset($aPhoto['exif']['Make'])) {
                    $aMatter['exif'] = [
                        'make' => $aPhoto['exif']['Make'],
                        'model' => $aPhoto['exif']['Model'],
                        'aperture' => $aPhoto['exif']['COMPUTED']['ApertureFNumber'],
                        'exposure' => $aPhoto['exif']['ExposureTime'],
                    ];
                }

                if (isset($aPhotos[$i - 1])) {
                    $aMatter['previous'] = $aPhotos[$i - 1]['id'];
                }

                if (isset($aPhotos[$i + 1])) {
                    $aMatter['next'] = $aPhotos[$i + 1]['id'];
                }

                if (isset($aPhoto['latitude'])) {
                    $aMatter['location'] = [
                        'latitude' => $aPhoto['latitude'],
                        'longitude' => $aPhoto['longitude'],
                    ];
                }

                if ($aPhoto['sizes']) {
                    $aMatter['sizes'] = $aPhoto['sizes'];
                }

                ksort_recursive($aMatter);
                uasort(
                    $aMatter['sizes'],
                    function ($aA, $aB) {
                        $iSurfaceA = $aA['width'] * $aA['height'];
                        $iSurfaceB = $aB['width'] * $aB['height'];
                        return $iSurfaceA == $iSurfaceB
                            ? 0
                            : (($iSurfaceA > $iSurfaceB) ? -1 : 1);
                    }
                );

            // Write photo Markdown file
            file_put_contents(
                $sRenderPath . '/' . $aPhoto['id'] . '.md',
                '---' . "\n" . yamlDump($aMatter) . '---' . "\n" . (empty($aPhoto['comment']) ? '' : $aPhoto['comment'] . "\n")
            );
            $oOutput->writeln(' done');
        }
        
        // Write slideshow
        $bSlideshow = isset($aGallery['slideshow']) ? $aGallery['slideshow'] : true;
        if ($bSlideshow) {
            $oOutput->write('<comment>slideshow</comment>');
            $aMatter = [
                'layout' => 'gallery-slideshow',
                'title' => empty($aGallery['title']) ? '' : $aGallery['title']
            ];
            file_put_contents(
                $sRenderPath . '/slideshow.html',
                '---' . "\n" . yamlDump($aMatter) . '---' . "\n"
            );
            $oOutput->writeln(' done');
        }

        // Write map
        $bMap = count($aLongitude) > 0 && (isset($aGallery['map']) ? $aGallery['map'] : true);
        if ($bMap) {
            $oOutput->write('<comment>map</comment>');
            $aMatter = [
                'layout' => 'gallery-map',
                'title' => empty($aGallery['title']) ? '' : $aGallery['title'],
                'gallery_map' => [
                    'latitude' => array_sum($aLatitude) / count($aLatitude),
                    'longitude' => array_sum($aLongitude) / count($aLongitude),
                    'zoom' => 8
                ]
            ];
            file_put_contents(
                $sRenderPath . '/map.html',
                '---' . "\n" . yamlDump($aMatter) . '---' . "\n"
            );
            $oOutput->writeln(' done');
        }
        
        // Write gallery index
        $oOutput->write('<comment>index</comment>');
        $aLinks = [];
        if ($bSlideshow) {
            $aLinks[] = '<a href="slideshow.html">[ slideshow ]</a>';
        }
        if ($bMap) {
            $aLinks[] = '<a href="map.html">[ map ]</a>';
        }
        $aMatter = [
            'layout' => 'gallery-list',
            'title' => empty($aGallery['title']) ? '' : $aGallery['title'],
            'highlight_photo' => empty($sHighlight) ? $aPhotos[0]['id'] : $sHighlight,
            'date' => $oDate->format('Y-m-d')
        ];
        if ($oDate->diff($oEndDate)->d > 0) {
            $aMatter['end_date'] = $oEndDate->format('Y-m-d');
        }

        $sContents = sprintf(
<<<EOF
<div itemprop="description">
    <p>%s</p>
    %s
</div>
{%% include gallery_list.html gallery='%s' %%}
EOF
        , (empty($aGallery['description']) ? '' : $aGallery['description']), implode(' &middot; ', $aLinks), $sGallery);
        file_put_contents(
            $sRenderPath . '/index.html',
            '---' . "\n" . yamlDump($aMatter) . '---' . "\n" . $sContents
        );
        $oOutput->writeln(' done');
        
    }
);

$oConsole->run(new ArgvInput(array_merge([$_SERVER['argv'][0], 'run' ], array_slice($_SERVER['argv'], 1))));

function ksort_recursive(&$aArray, $mSortFlags = SORT_REGULAR) {
    if (!is_array($aArray)) {
        return false;
    }
    foreach ($aArray as &$aSubarray) {
        ksort_recursive($aSubarray, $mSortFlags);
    }
    ksort($aArray, $mSortFlags);
    return true;
}

function coordinateToDegrees($aCoordinate, $sHemisphere) {
    $aCoordinate = array_map('fractionToFloat', $aCoordinate);
    $aDegrees = array_map(function ($a, $b) {
            return $a / $b;
        }, $aCoordinate, array(1, 60, 3600));
    $iFlip = ($sHemisphere == 'W' or $sHemisphere == 'S') ? -1 : 1;
    return $iFlip * array_sum($aDegrees);
}

function fractionToFloat($sFraction) {
    $aParts = explode('/', $sFraction);
    $iParts = count($aParts);
    return $iParts
        ? ($iParts > 1
            ? floatval($aParts[0]) / floatval($aParts[1])
            : $aParts[0])
        : 0;
}

function yamlDump($aData) {
    return str_replace("'", null, Yaml::dump($aData, 4, 2));
}