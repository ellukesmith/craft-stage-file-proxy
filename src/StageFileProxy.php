<?php
/**
 * Stage File Proxy plugin for Craft CMS 3.x
 *
 * Stage File Proxy is a general solution for getting production files on a development server on demand.
 *
 * @link      liftov.be
 * @copyright Copyright (c) 2019 Wouter Van Scharen
 */

namespace liftov\stagefileproxy;

use Craft;
use craft\base\Plugin;
use craft\events\DefineAssetThumbUrlEvent;
use craft\events\DefineAssetUrlEvent;
use craft\services\Assets;
use craft\elements\Asset;

use yii\base\Event;

/**
 * Class StageFileProxy
 *
 * Modified to better handle multiple volumes and dynamic folder names
 * 
 * @author    Wouter Van Scharen
 * @package   StageFileProxy
 * @since     1.0.0
 *
 */
class StageFileProxy extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var StageFileProxy
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            Assets::class,
            Assets::EVENT_DEFINE_THUMB_URL,
            fn(DefineAssetThumbUrlEvent $event) => $this->processAsset($event),
            null,
            false
        );

        Event::on(
            Asset::class,
            Asset::EVENT_DEFINE_URL,
            fn(DefineAssetUrlEvent $event) => $this->processAsset($event),
            null,
            false
        );

        Craft::info(
            Craft::t(
                'stage-file-proxy',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    private function processAsset($event)
    {
        $remoteSource = getenv("STAGE_FILE_PROXY_REMOTE");
        $assetBaseFolder = getenv("STAGE_FILE_PROXY_BASE_FOLDER") ?: '';
        $path = str_replace('@webroot', '', $event->asset->getVolume()->getFs()->path);

        if ($remoteSource) {
            $filename = $event->asset->path;
            $localeFilePath = trim($assetBaseFolder . '/' . $path, '/') . '/' . $filename;       
            $fileDirectory = dirname($localeFilePath);                

            if (!file_exists($localeFilePath)) {
                if (!file_exists($fileDirectory)) {
                    mkdir($fileDirectory, 0775, true);
                }

                $remoteFilePath = $remoteSource . $localeFilePath;

                file_put_contents($localeFilePath, fopen($remoteFilePath, 'r'));
            }
        }
    }
}
