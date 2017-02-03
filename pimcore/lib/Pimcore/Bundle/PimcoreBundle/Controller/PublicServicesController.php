<?php

namespace Pimcore\Bundle\PimcoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\Asset;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Logger;

class PublicServicesController {

    public function thumbnailAction(Request $request) {


        $assetId = $request->get("assetId");
        $thumbnailName = $request->get("thumbnailName");
        $filename = $request->get("filename");

        if ($asset = Asset::getById($assetId)) {
            try {
                $page = 1; // default
                $thumbnailFile = null;
                $thumbnailConfig = null;

                //get thumbnail for e.g. pdf page thumb__document_pdfPage-5
                if (preg_match("|document_(.*)\-(\d+)$|", $thumbnailName, $matchesThumbs)) {
                    $thumbnailName = $matchesThumbs[1];
                    $page = (int)$matchesThumbs[2];
                }

                // just check if the thumbnail exists -> throws exception otherwise
                $thumbnailConfig = Asset\Image\Thumbnail\Config::getByName($thumbnailName);

                if (!$thumbnailConfig) {
                    // check if there's an item in the TmpStore
                    $deferredConfigId = "thumb_" . $assetId . "__" . md5($request->getPathInfo());
                    if ($thumbnailConfigItem = TmpStore::get($deferredConfigId)) {
                        $thumbnailConfig = $thumbnailConfigItem->getData();
                        TmpStore::delete($deferredConfigId);

                        if (!$thumbnailConfig instanceof Asset\Image\Thumbnail\Config) {
                            throw new \Exception("Deferred thumbnail config file doesn't contain a valid \\Asset\\Image\\Thumbnail\\Config object");
                        }
                    }
                }

                if (!$thumbnailConfig) {
                    throw new \Exception("Thumbnail '" . $thumbnailName . "' file doesn't exists");
                }

                if ($asset instanceof Asset\Document) {
                    $thumbnailConfig->setName(preg_replace("/\-[\d]+/", "", $thumbnailConfig->getName()));
                    $thumbnailConfig->setName(str_replace("document_", "", $thumbnailConfig->getName()));

                    $thumbnailFile = $asset->getImageThumbnail($thumbnailConfig, $page)->getFileSystemPath();
                } elseif ($asset instanceof Asset\Image) {
                    //check if high res image is called

                    preg_match("@([^\@]+)(\@[0-9.]+x)?\.([a-zA-Z]{2,5})@", $filename, $matches);

                    if (array_key_exists(2, $matches)) {
                        $highResFactor = (float) str_replace(["@", "x"], "", $matches[2]);
                        $thumbnailConfig->setHighResolution($highResFactor);
                    }

                    // check if a media query thumbnail was requested
                    if (preg_match("#~\-~([\d]+w)#", $matches[1], $mediaQueryResult)) {
                        $thumbnailConfig->selectMedia($mediaQueryResult[1]);
                    }

                    $thumbnailFile = $asset->getThumbnail($thumbnailConfig)->getFileSystemPath();
                }

                if ($thumbnailFile && file_exists($thumbnailFile)) {

                    // set appropriate caching headers
                    // see also: https://github.com/pimcore/pimcore/blob/1931860f0aea27de57e79313b2eb212dcf69ef13/.htaccess#L86-L86
                    $lifetime = 86400 * 7; // 1 week lifetime, same as direct delivery in .htaccess
                    header("Cache-Control: public, max-age=" . $lifetime);
                    header("Expires: ". date("D, d M Y H:i:s T", time()+$lifetime));

                    $fileExtension = \Pimcore\File::getFileExtension($thumbnailFile);
                    if (in_array($fileExtension, ["gif", "jpeg", "jpeg", "png", "pjpeg"])) {
                        header("Content-Type: image/".$fileExtension, true);
                    } else {
                        header("Content-Type: " . $asset->getMimetype(), true);
                    }

                    header("Content-Length: " . filesize($thumbnailFile), true); while (@ob_end_flush()) ;
                    flush();

                    readfile($thumbnailFile);
                    exit;
                }
            } catch (\Exception $e) {
                // nothing to do
                Logger::error("Thumbnail with name '" . $thumbnailName . "' doesn't exist");
            }
        }
    }
}