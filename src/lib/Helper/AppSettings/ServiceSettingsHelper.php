<?php
/*
 * @copyright 2021 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Helper\AppSettings;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Favicon\BestIconHelper;
use OCA\Passwords\Helper\Image\ImagickHelper;
use OCA\Passwords\Helper\Preview\BrowshotPreviewHelper;
use OCA\Passwords\Helper\Preview\ScreeenlyHelper;
use OCA\Passwords\Helper\Preview\ScreenShotLayerHelper;
use OCA\Passwords\Helper\Preview\ScreenShotMachineHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\HelperService;
use OCP\IL10N;

/**
 * Class ServiceSettingsHelper
 *
 * @package OCA\Passwords\Helper\AppSettings
 */
class ServiceSettingsHelper extends AbstractSettingsHelper {

    /**
     * @var IL10N
     */
    protected IL10N $localisation;

    /**
     * @var HelperService
     */
    protected HelperService $helperService;

    /**
     * @var string
     */
    protected string $scope = 'service';

    /**
     * @var array
     */
    protected array $keys
        = [
            'security' => 'service/security',
            'words'    => 'service/words',
            'images'   => 'service/images',
            'preview'  => 'service/preview',
            'favicon'  => 'service/favicon'
        ];

    /**
     * @var array
     */
    protected array $defaults
        = [
            'security'    => HelperService::SECURITY_HIBP,
            'preview'     => HelperService::PREVIEW_DEFAULT,
            'favicon'     => HelperService::FAVICON_DEFAULT,
            'preview.api' => '',
            'favicon.api' => ''
        ];

    /**
     * ServiceSettingsHelper constructor.
     *
     * @param ConfigurationService $config
     * @param HelperService        $helperService
     * @param IL10N                $localisation
     */
    public function __construct(ConfigurationService $config, HelperService $helperService, IL10N $localisation) {
        parent::__construct($config);
        $this->localisation  = $localisation;
        $this->helperService = $helperService;
    }

    /**
     * @return array
     */
    public function list(): array {
        try {
            return [
                $this->get('words'),
                $this->get('images'),
                $this->get('favicon'),
                $this->get('preview'),
                $this->get('security'),
                $this->get('favicon.api'),
                $this->get('preview.api')
            ];
        } catch(ApiException $e) {
            return [];
        }
    }

    /**
     * @param $key
     *
     * @return array
     * @throws ApiException
     */
    public function get(string $key): array {
        switch($key) {
            case 'words':
                return $this->getGenericSetting('words');
            case 'images':
                return $this->getGenericSetting('images');
            case 'favicon':
                return $this->getGenericSetting('favicon');
            case 'preview':
                return $this->getGenericSetting('preview');
            case 'security':
                return $this->getGenericSetting('security');
            case 'favicon.api':
                return $this->getFaviconApiSetting();
            case 'preview.api':
                return $this->getPreviewApiSetting();
        }

        throw new ApiException('Unknown setting identifier', 400);
    }

    /**
     * @param string $setting
     *
     * @return string
     * @throws ApiException
     */
    protected function getSettingKey(string $setting): string {
        switch($setting) {
            case 'preview.api':
                return $this->getPreviewApiSettingKey();
            case 'favicon.api':
                return $this->getFaviconApiSettingKey();
        }

        return parent::getSettingKey($setting);
    }

    /**
     * @param string $setting
     *
     * @return string
     * @throws ApiException
     */
    protected function getSettingDefault(string $setting) {
        switch($setting) {
            case 'words':
                return $this->helperService->getDefaultWordsHelperName();
            case 'images':
                return HelperService::getImageHelperName();
        }

        return parent::getSettingDefault($setting);
    }

    /**
     * @return array
     */
    protected function getFaviconApiSetting(): array {
        $configKey = $this->getFaviconApiSettingKey();
        $default   = $this->getFaviconApiSettingDefault();
        $value     = $this->config->getAppValue($configKey, $default);
        $isDefault = !$this->config->hasAppValue($configKey);

        return $this->generateSettingArray(
            'favicon.api',
            $value,
            [],
            $default,
            $isDefault,
            'string',
            [
                'service.favicon' => [HelperService::FAVICON_BESTICON]
            ]
        );
    }

    /**
     * @return string
     */
    protected function getFaviconApiSettingKey(): string {
        $service = $this->config->getAppValue('service/favicon', HelperService::FAVICON_DEFAULT);

        if($service === HelperService::FAVICON_BESTICON) {
            return BestIconHelper::BESTICON_CONFIG_KEY;
        }

        return 'service/favicon/api';
    }

    /**
     * @return string
     */
    protected function getFaviconApiSettingDefault(): string {
        return '';
    }

    /**
     * @return array
     */
    protected function getPreviewApiSetting(): array {
        $configKey = $this->getPreviewApiSettingKey();
        $value     = $this->config->getAppValue($configKey, '');
        $isDefault = !$this->config->hasAppValue($configKey);

        return $this->generateSettingArray(
            'preview.api',
            $value,
            [],
            '',
            $isDefault,
            'string',
            [
                'service.preview' => [
                    HelperService::PREVIEW_SCREEENLY,
                    HelperService::PREVIEW_BROW_SHOT,
                    HelperService::PREVIEW_SCREEN_SHOT_LAYER,
                    HelperService::PREVIEW_SCREEN_SHOT_MACHINE
                ]
            ]
        );
    }

    /**
     * @return string
     */
    protected function getPreviewApiSettingKey(): string {
        $service = $this->config->getAppValue('service/preview', HelperService::PREVIEW_DEFAULT);

        if($service === HelperService::PREVIEW_SCREEN_SHOT_LAYER) {
            return ScreenShotLayerHelper::SSL_API_CONFIG_KEY;
        }

        if($service === HelperService::PREVIEW_SCREEN_SHOT_MACHINE) {
            return ScreenShotMachineHelper::SSM_API_CONFIG_KEY;
        }

        if($service === HelperService::PREVIEW_BROW_SHOT) {
            return BrowshotPreviewHelper::BWS_API_CONFIG_KEY;
        }

        if($service === HelperService::PREVIEW_SCREEENLY) {
            return ScreeenlyHelper::SCREEENLY_API_CONFIG_KEY;
        }

        return 'service/preview/api';
    }

    /**
     * @return array
     */
    protected function getSecurityOptions(): array {
        return [
            $this->generateOptionArray(
                HelperService::SECURITY_HIBP,
                $this->localisation->t('Have I been pwned? (recommended)'),
                $this->helperService->getSecurityHelper(HelperService::SECURITY_HIBP)->isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::SECURITY_BIG_LOCAL,
                $this->localisation->t('Big local database (25M passwords)'),
                $this->helperService->getSecurityHelper(HelperService::SECURITY_BIG_LOCAL)->isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::SECURITY_SMALL_LOCAL,
                $this->localisation->t('Small local database (5M passwords)'),
                $this->helperService->getSecurityHelper(HelperService::SECURITY_SMALL_LOCAL)->isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::SECURITY_BIGDB_HIBP,
                $this->localisation->t('Big local database & Hibp?'),
                $this->helperService->getSecurityHelper(HelperService::SECURITY_BIGDB_HIBP)->isAvailable()
            )
        ];
    }

    /**
     * @return array
     */
    protected function getWordsOptions(): array {
        return [
            $this->generateOptionArray(
                HelperService::WORDS_LOCAL,
                $this->localisation->t('Local dictionary'),
                $this->helperService->getWordsHelper(HelperService::WORDS_LOCAL)->isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::WORDS_LEIPZIG,
                $this->localisation->t('Leipzig Corpora Collection (recommended)'),
                $this->helperService->getWordsHelper(HelperService::WORDS_LEIPZIG)->isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::WORDS_SNAKES,
                $this->localisation->t('watchout4snakes.com'),
                $this->helperService->getWordsHelper(HelperService::WORDS_SNAKES)->isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::WORDS_RANDOM,
                $this->localisation->t('Random Characters'),
                $this->helperService->getWordsHelper(HelperService::WORDS_RANDOM)->isAvailable()
            )
        ];
    }

    /**
     * @return array
     */
    protected function getFaviconOptions(): array {
        return [
            $this->generateOptionArray(
                HelperService::FAVICON_LOCAL,
                $this->localisation->t('Local analyzer')
            ),
            $this->generateOptionArray(
                HelperService::FAVICON_BESTICON,
                $this->localisation->t('Besticon (recommended)')
            ),
            $this->generateOptionArray(
                HelperService::FAVICON_FAVICON_GRABBER,
                $this->localisation->t('favicongrabber.com')
            ),
            $this->generateOptionArray(
                HelperService::FAVICON_DUCK_DUCK_GO,
                $this->localisation->t('DuckDuckGo')
            ),
            $this->generateOptionArray(
                HelperService::FAVICON_GOOGLE,
                $this->localisation->t('Google')
            ),
            $this->generateOptionArray(
                HelperService::FAVICON_DEFAULT,
                $this->localisation->t('None')
            )
        ];
    }

    /**
     * @return array
     */
    protected function getPreviewOptions(): array {
        return [
            $this->generateOptionArray(
                HelperService::PREVIEW_PAGERES,
                $this->localisation->t('Pageres CLI (Local)')
            ),
            $this->generateOptionArray(
                HelperService::PREVIEW_BROW_SHOT,
                $this->localisation->t('Browshot')
            ),
            $this->generateOptionArray(
                HelperService::PREVIEW_SCREEENLY,
                $this->localisation->t('screeenly')
            ),
            $this->generateOptionArray(
                HelperService::PREVIEW_SCREEN_SHOT_LAYER,
                $this->localisation->t('screenshotlayer')
            ),
            $this->generateOptionArray(
                HelperService::PREVIEW_SCREEN_SHOT_MACHINE,
                $this->localisation->t('screenshotmachine.com')
            ),
            $this->generateOptionArray(
                HelperService::PREVIEW_DEFAULT,
                $this->localisation->t('None')
            )
        ];
    }

    /**
     * @return array
     */
    protected function getImagesOptions(): array {
        return [
            $this->generateOptionArray(
                HelperService::IMAGES_IMAGICK,
                $this->localisation->t('Imagick/GMagick (recommended)'),
                ImagickHelper::isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::IMAGES_GDLIB,
                $this->localisation->t('PHP GDLib')
            )
        ];
    }
}