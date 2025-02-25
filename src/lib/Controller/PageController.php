<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller;

use Exception;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Helper\Http\SetupReportHelper;
use OCA\Passwords\Helper\Token\ApiTokenHelper;
use OCA\Passwords\Helper\Upgrade\UpgradeCheckHelper;
use OCA\Passwords\Services\DeferredActivationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\NotificationService;
use OCA\Passwords\Services\UserChallengeService;
use OCA\Passwords\Services\UserSettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;
use OCP\Util;

/**
 * Class PageController
 *
 * @package OCA\Passwords\Controller
 */
class PageController extends Controller {

    /**
     * @var UserSettingsService
     */
    protected UserSettingsService $settings;

    /**
     * @var ApiTokenHelper
     */
    protected ApiTokenHelper $tokenHelper;

    /**
     * @var EnvironmentService
     */
    protected EnvironmentService $environment;

    /**
     * @var NotificationService
     */
    protected NotificationService $notifications;

    /**
     * @var UserChallengeService
     */
    protected UserChallengeService $challengeService;

    /**
     * @var SetupReportHelper
     */
    protected SetupReportHelper $setupReportHelper;

    /**
     * @var DeferredActivationService
     */
    protected DeferredActivationService $das;

    /**
     * @var IInitialState
     */
    protected IInitialState             $initialState;

    /**
     * @param IRequest                  $request
     * @param ApiTokenHelper            $tokenHelper
     * @param IInitialState             $initialState
     * @param UserSettingsService       $settings
     * @param EnvironmentService        $environment
     * @param NotificationService       $notifications
     * @param SetupReportHelper         $setupReportHelper
     * @param UserChallengeService      $challengeService
     * @param DeferredActivationService $das
     */
    public function __construct(
        IRequest                  $request,
        ApiTokenHelper            $tokenHelper,
        IInitialState             $initialState,
        UserSettingsService       $settings,
        EnvironmentService        $environment,
        NotificationService       $notifications,
        SetupReportHelper         $setupReportHelper,
        UserChallengeService      $challengeService,
        DeferredActivationService $das
    ) {
        parent::__construct(Application::APP_NAME, $request);
        $this->das               = $das;
        $this->settings          = $settings;
        $this->tokenHelper       = $tokenHelper;
        $this->environment       = $environment;
        $this->initialState      = $initialState;
        $this->notifications     = $notifications;
        $this->challengeService  = $challengeService;
        $this->setupReportHelper = $setupReportHelper;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @UseSession
     * @throws Exception
     */
    public function index(): TemplateResponse {
        $isSecure = $this->checkIfHttpsUsed();

        if($isSecure) {
            $this->addHeaders();
            $this->checkImpersonation();
        } else {
            $this->tokenHelper->destroyWebUiToken();
        }

        $response = new TemplateResponse(
            $this->appName,
            'index',
            $this->getTemplateVariables($isSecure)
        );

        $this->getContentSecurityPolicy($response);

        return $response;
    }

    /**
     * @return bool
     */
    protected function checkIfHttpsUsed(): bool {
        $httpsParam = $this->request->getParam('https', 'true') === 'true';

        return $this->request->getServerProtocol() === 'https' && $httpsParam;
    }

    /**
     *
     * @throws Exception
     */
    protected function addHeaders(): void {
        $this->initialState->provideInitialState('settings', $this->settings->list());

        [$token, $user] = $this->tokenHelper->getWebUiToken();
        $this->initialState->provideInitialState('api-user', $user);
        $this->initialState->provideInitialState('api-token', $token);

        $this->initialState->provideInitialState('authenticate', $this->challengeService->hasChallenge());
        $this->initialState->provideInitialState('impersonate', $this->environment->isImpersonating());
        $this->initialState->provideInitialState('features', $this->das->getClientFeatures());
    }

    /**
     * @param TemplateResponse $response
     *
     * @throws Exception
     */
    protected function getContentSecurityPolicy(TemplateResponse $response): void {
        $manualHost = parse_url($this->settings->get('server.handbook.url'), PHP_URL_HOST);

        $csp = $response->getContentSecurityPolicy();
        $csp->addAllowedScriptDomain($this->request->getServerHost());
        $csp->addAllowedConnectDomain($manualHost);
        $csp->addAllowedConnectDomain('data:');
        $csp->addAllowedImageDomain($manualHost);
        $csp->addAllowedMediaDomain($manualHost);
        $csp->addAllowedMediaDomain('blob:');
        $csp->allowInlineStyle();
        $csp->allowEvalScript();

        $response->setContentSecurityPolicy($csp);
    }

    /**
     * @throws Exception
     */
    protected function checkImpersonation(): void {
        if($this->environment->isImpersonating()) {
            $this->notifications->sendImpersonationNotification(
                $this->environment->getUserId(),
                $this->environment->getRealUser()->getUID()
            );
        }
    }

    /**
     * @param bool $isSecure
     *
     * @return array[]
     */
    protected function getTemplateVariables(bool $isSecure): array {
        $variables = [
            'https' => $isSecure
        ];

        if(!$isSecure) {
            $variables['report'] = $this->setupReportHelper->getHttpsSetupReport();
        }

        return $variables;
    }
}
