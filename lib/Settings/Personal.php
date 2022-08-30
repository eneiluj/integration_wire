<?php
namespace OCA\Wire\Settings;

use OCA\Wire\Service\WireAPIService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;

use OCA\Wire\AppInfo\Application;

class Personal implements ISettings {

	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var IInitialState
	 */
	private $initialStateService;
	/**
	 * @var string|null
	 */
	private $userId;
	/**
	 * @var WireAPIService
	 */
	private $WireAPIService;

	public function __construct(IConfig $config,
								IInitialState $initialStateService,
								WireAPIService $mattermostAPIService,
								?string $userId) {
		$this->config = $config;
		$this->initialStateService = $initialStateService;
		$this->userId = $userId;
		$this->mattermostAPIService = $mattermostAPIService;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$token = $this->config->getUserValue($this->userId, Application::APP_ID, 'token');
		$navigationEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'navigation_enabled', '0');
		$wireUserId = $this->config->getUserValue($this->userId, Application::APP_ID, 'user_id');
		$wireUserName = $this->config->getUserValue($this->userId, Application::APP_ID, 'user_name');
		$wireUserDisplayName = $this->config->getUserValue($this->userId, Application::APP_ID, 'user_displayname');
		$adminUrl = $this->config->getAppValue(Application::APP_ID, 'url');
		$userUrl = $this->config->getUserValue($this->userId, Application::APP_ID, 'url', $adminUrl) ?: $adminUrl;

		$userConfig = [
			'token' => $token ? 'dummyTokenContent' : '',
			'url' => $userUrl,
			'user_id' => $wireUserId,
			'user_name' => $wireUserName,
			'user_displayname' => $wireUserDisplayName,
			'navigation_enabled' => ($navigationEnabled === '1'),
		];
		$this->initialStateService->provideInitialState('user-config', $userConfig);
		return new TemplateResponse(Application::APP_ID, 'personalSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 10;
	}
}
