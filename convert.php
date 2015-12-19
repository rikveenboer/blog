<?php
require __DIR__ . '/vendor/autoload.php';

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
        new InputOption('layout', null, InputOption::VALUE_REQUIRED, 'Rendering layout for individual photos', 'gallery-photo'),
        new InputArgument('name', null, InputArgument::REQUIRED, 'Gallery name'),
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
            $sGallery = $oInput->getArgument('name');
            $sAssetPath = $oInput->getArgument('assetdir') . '/' . $sGallery;
            $sRenderPath = $oInput->getArgument('mdowndir') . '/' . $sGallery;
            $sLayout = $oInput->getOption('layout');
            $sExports = $oInput->getOption('export');

            $sStdin = stream_get_contents(STDIN);

            $oImagine = new Imagine\Gd\Imagine();

            if (!is_dir($sAssetPath)) {
                mkdir($sAssetPath, 0700, true);
            }

            if (!is_dir($sRenderPath)) {
                mkdir($sRenderPath, 0700, true);
            }

            $sStdinPhotos = explode('------------', trim($sStdin));
            $aPhotos = [];

            // load data
            foreach ($sStdinPhotos as $i => $aPhotoRaw) {
                $aPhotoSplit = explode('------', trim($aPhotoRaw), 2);

                if (empty($aPhotoSplit[0])) {
                    continue;
                }

                $aPhoto = array_merge([
                    'ordering' => $i,
                    'comment' => isset($aPhotoSplit[1]) ? $aPhotoSplit[1] : null,
                ], Yaml::parse($aPhotoSplit[0]));

                $aPhoto['id'] = substr(sha1_file($aPhoto['path']), 0, 7) . '-' . preg_replace('/(-| )+/', '-', preg_replace('/[^a-z0-9 ]/i', '-', preg_replace('/\'/', '', strtolower(preg_replace('/\p{Mn}/u', '', Normalizer::normalize($aPhoto['title'], Normalizer::FORM_KD))))));
                $aPhoto['date'] = \DateTime::createFromFormat(
                    'l, F j, Y \a\t g:i:s A',
                    $aPhoto['date']
                );

                $aPhoto['exif'] = exif_read_data($aPhoto['path']);
                $aPhotos[] = $aPhoto;
            }

            // manipulate
            foreach ($aPhotos as $i => $aPhoto) {
                $oOutput->write('<info>' . $aPhoto['id'] . '</info>');

                $aPhoto['sizes'] = [];

                // image exports
                if (0 < count($sExports)) {
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
                    $oOutput->write('[' . $oSourceSize->getWidth() . 'x' . $oSourceSize->getHeight() . ']...');

                    foreach ($sExports as $sExport) {
                        $oOutput->write('<comment>' . $sExport . '</comment>');

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

                        $oOutput->write('[' . $sExportsize->getWidth() . 'x' . $sExportsize->getHeight() . ']');

                        $sExportPath = $sAssetPath . '/' . $aPhoto['id'] . '~' . $sExport . '.jpg';

                        file_put_contents(
                            $sExportPath,
                            $sExportImage->get('jpeg', ['quality' => 90])
                        );

                        touch($sExportPath, $aPhoto['date']->getTimestamp());
                        $sExportImage = null;
                        $oOutput->write('...');
                    }
                    $oSourceJpg = null;
                }

                $oOutput->write('<comment>markdown</comment>...');

                $aMatter = [
                    'layout' => $sLayout,
                    'title' => $aPhoto['title'],
                    'date' => $aPhoto['date']->format('Y-m-d H:i:s'),
                    'ordering' => $aPhoto['ordering']
                ];

                if ($aPhoto['exif']) {
                    $aMatter['exif'] = [
                        'make' => $aPhoto['exif']['Make'],
                        'model' => $aPhoto['exif']['Model'],
                        'aperture' => $aPhoto['exif']['COMPUTED']['ApertureFNumber'],
                        'exposure' => $aPhoto['exif']['ExposureTime'],
                    ];
                }

                if (isset($aPhotos[$i - 1])) {
                    $aMatter['previous'] = '/gallery/' . $sGallery . '/' . $aPhotos[$i - 1]['id'];
                }

                if (isset($aPhotos[$i + 1])) {
                    $aMatter['next'] = '/gallery/' . $sGallery . '/' . $aPhotos[$i + 1]['id'];
                }

                if ($aPhoto['latitude']) {
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
                            : (($aa > $bb)? -1 : 1);
                    }
                );

                file_put_contents(
                    $sRenderPath . '/' . $aPhoto['id'] . '.md',
                    '---' . "\n" . Yaml::dump($aMatter, 4, 2) . '---' . "\n" . ((!empty($aPhoto['comment'])) ? ($aPhoto['comment'] . "\n") : '')
                );

                $oOutput->writeln('done');
            }
        });

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
