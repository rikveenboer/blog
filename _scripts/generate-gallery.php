<?php
require __DIR__ . '/utilities.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

$oConsole = new Application();
$oConsole
    ->register('run')
    ->setDefinition([        
        new InputOption('export', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Target image export sizes'),
        new InputOption('skip-resize', null, InputOption::VALUE_NONE, 'Skip resizing'),
        new InputArgument('name', InputArgument::REQUIRED, 'Gallery name'),
        new InputArgument('dir', InputArgument::REQUIRED, 'Directory to scan for images'),
        new InputArgument('assetdir', InputArgument::OPTIONAL, 'Asset directory for exported images', 'asset/gallery'),
        new InputArgument('datadir', InputArgument::OPTIONAL, 'Data directory for gallery yaml file', __DIR__ . '/../_data/gallery'),
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
            $sDataPath = $oInput->getArgument('datadir');
            $sDataFile = sprintf('%s/%s.yml', $sDataPath, $sGallery);
            $sExports = $oInput->getOption('export');
            $bSkipResize = $oInput->getOption('skip-resize');

            $oImagine = new Imagine\Gd\Imagine();

            // Initialize directories
            if (!is_dir($sAssetPath)) {
                mkdir($sAssetPath, 0700, true);
            }
            if (!is_dir($sDataPath)) {
                mkdir($sDataPath, 0700, true);
            }

            // Check directory
            if (!is_dir($sDir)) {              
                $oOutput->writeln('<error>Import directory does not exist</error>');
                exit;
            }

            // Loop over files
            $aGallery['title'] = ucwords(str_replace('-', ' ', substr($sGallery, 5)));
            $aPhotos = $aLongitude = $aLatitude = [];
            foreach (glob($sDir . '/*.jpg') as $i => $sFile) {
                // Generate id from file contents
                $sId = substr(sha1_file($sFile), 0, 7);
                $oOutput->write('<info>' . $sId . '</info>');

                // Parse selected EXIF data
                $aExif = exif_read_data($sFile);
                if (isset($aExif['GPSLongitude'])) {
                    $aPhoto = [
                        'longitude' => coordinateToDegrees($aExif['GPSLongitude'], $aExif['GPSLongitudeRef']),
                        'latitude' => coordinateToDegrees($aExif['GPSLatitude'], $aExif['GPSLatitudeRef'])
                    ];
                    if (isset($aExif['GPSAltitude'])) {
                        $aPhoto['altitude'] = fractionToFloat($aExif['GPSAltitude']);
                    }
                    if (isset($aExif['GPSImgDirection'])) {
                        $aPhoto['direction'] = fractionToFloat($aExif['GPSImgDirection']);
                    }
                    $aLongitude[] = $aPhoto['longitude'];
                    $aLatitude[] = $aPhoto['latitude'];
                } else {
                    $aPhoto = [];
                }
                if (isset($aExif['DateTimeOriginal'])) {
                    $aPhoto['date'] = new DateTime($aExif['DateTimeOriginal']);
                } else {
                    $oDate = new DateTime();
                    $aPhoto['date'] = $oDate->setTimestamp($aExif['FileDateTime']);
                }

                // Image exports
                if (count($sExports) > 0) {
                    $oSourceJpg = $oImagine->open($sFile);
                    if (isset($aExif['Orientation'])) {
                        switch ($aExif['Orientation']) {
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

                    $iWidth = 0;
                    foreach ($sExports as $sExport) {
                        $oOutput->write('    <comment>' . $sExport . '</comment>');

                        if (strpos($sExport, 'x') !== false) {
                            list($iX, $iY) = explode('x', $sExport);
                            if ($iX > $oSourceSize->getWidth() || $iY > $oSourceSize->getHeight()) {
                                $oOutput->writeln('    <comment>[skipping]</comment>');
                                continue;
                            }
                            if (!$bSkipResize) {
                                $sExportImage = $oSourceJpg->thumbnail(
                                    new \Imagine\Image\Box($iX, $iY),
                                    \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND
                                );
                            }
                        } else {
                            if ($oSourceSize->getWidth() == max($oSourceSize->getWidth(), $oSourceSize->getHeight())) {
                                $iX = (int) $sExport;
                                $iY = ($iX * $oSourceSize->getHeight()) / $oSourceSize->getWidth();
                            } elseif ($oSourceSize->getHeight() == max($oSourceSize->getWidth(), $oSourceSize->getHeight())) {
                                $iY = (int) $sExport;
                                $iX = ($iY * $oSourceSize->getWidth()) / $oSourceSize->getHeight();
                            }

                            if ($iX > $oSourceSize->getWidth() || $iY > $oSourceSize->getHeight()) {
                                $oOutput->writeln('    <comment>[skipping]</comment>');
                                continue;
                            }
                         
                            $iX = ceil($iX);
                            $iY = ceil($iY);
                            if (!$bSkipResize) {
                                $sExportImage = $oSourceJpg->thumbnail(
                                    new \Imagine\Image\Box($iX, $iY),
                                    \Imagine\Image\ImageInterface::THUMBNAIL_INSET
                                );
                            }
                        }

                        if ($bSkipResize) {
                            $oOutput->writeln('');
                        } else {
                            $aExportsize = $sExportImage->getSize();
                            if ($iX != $aExportsize->getWidth() || $iY != $aExportsize->getHeight()) {
                                $oOutput->writeln(sprintf(' [%dx%d] vs [%dx%d]', $iX, $iY, $aExportsize->getWidth(), $aExportsize->getHeight()));
                            } else {
                                $oOutput->writeln(sprintf(' [%dx%d]', $iX, $iY));
                            }
                            $sExportPath = $sAssetPath . '/' . $sId . '~' . $sExport . '.jpg';

                            // Write converted image
                            file_put_contents(
                                $sExportPath,
                                $sExportImage->get('jpeg', ['quality' => 90])
                            );

                            touch($sExportPath, $aPhoto['date']->getTimestamp());
                            $sExportImage = null;

                            // Keep track of dimensions
                            $iX = $aExportsize->getWidth();
                            $iY = $aExportsize->getHeight();                            
                        }
                        $iWidth = max($iWidth, $iX);
                    }
                    $oSourceJpg = null;
                    if ($iWidth != 640) {
                        $aPhoto['width'] = $iWidth;
                    }
                }

                // Keep track of album dates
                $oDate = $i > 0 ? min($oDate, $aPhoto['date']) : $aPhoto['date'];
                $oEndDate = $i > 0 ? max($oEndDate, $aPhoto['date']) : $aPhoto['date'];

                $aPhoto = array_merge($aPhoto, [
                    'file' => str_ireplace('.jpg', null, basename($sFile)),
                    'date' => $aPhoto['date']->format('Y-m-d H:i:s'),
                ]);

                if (isset($aExif['Make'])) {
                    $aPhoto['make'] = $aExif['Make'];
                    if (isset($aExif['Model'])) {
                        $aPhoto['model'] = $aExif['Model'];
                    }
                    if (isset($aExif['COMPUTED']['ApertureFNumber'])) {
                        $aPhoto['aperture'] = $aExif['COMPUTED']['ApertureFNumber'];
                    }
                    if (isset($aExif['ExposureTime'])) {
                        $aPhoto['exposure'] = $aExif['ExposureTime'];
                    }
                }
                ksort($aPhoto);
                $aPhotos[$sId] = $aPhoto;
            }

            // Write datafile
            $aGallery['date'] = $oDate->format('Y-m-d');
            if ($oDate->diff($oEndDate)->d > 0) {
                $aGallery['end_date'] = $oEndDate->format('Y-m-d');
            }
            if (count($aLongitude) > 0) {
                $aGallery['map'] = [
                    'latitude' => array_sum($aLatitude) / count($aLatitude),
                    'longitude' => array_sum($aLongitude) / count($aLongitude)
                ];
            }
            $aGallery['photos'] = $aPhotos;
            file_put_contents($sDataFile, yamlDump($aGallery));
        }
);

$oConsole->run(new ArgvInput(array_merge([$_SERVER['argv'][0], 'run' ], array_slice($_SERVER['argv'], 1))));