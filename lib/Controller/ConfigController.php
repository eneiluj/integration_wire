<?php
/**
 * Nextcloud - Wire
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2022
 */

namespace OCA\Wire\Controller;

use DateTime;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\IL10N;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

use OCA\Wire\Service\WireAPIService;
use OCA\Wire\AppInfo\Application;
use OCP\PreConditionNotMetException;

class ConfigController extends Controller {

	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var IURLGenerator
	 */
	private $urlGenerator;
	/**
	 * @var IL10N
	 */
	private $l;
	/**
	 * @var WireAPIService
	 */
	private $wireAPIService;
	/**
	 * @var string|null
	 */
	private $userId;
	/**
	 * @var IInitialState
	 */
	private $initialStateService;

	public function __construct(string $appName,
								IRequest $request,
								IConfig $config,
								IURLGenerator $urlGenerator,
								IL10N $l,
								IInitialState $initialStateService,
								WireAPIService $wireAPIService,
								?string $userId) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->l = $l;
		$this->wireAPIService = $wireAPIService;
		$this->userId = $userId;
		$this->initialStateService = $initialStateService;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function isUserConnected(): DataResponse {
		$adminUrl = $this->config->getAppValue(Application::APP_ID, 'url');
		$userUrl = $this->config->getUserValue($this->userId, Application::APP_ID, 'url', $adminUrl) ?: $adminUrl;
		$token = $this->config->getUserValue($this->userId, Application::APP_ID, 'token');
		$cookie = $this->config->getUserValue($this->userId, Application::APP_ID, 'cookie');

		return new DataResponse([
			'connected' => $userUrl && $token && $cookie,
			'url' => $userUrl,
		]);
	}

	/**
	 * set config values
	 * @NoAdminRequired
	 *
	 * @param array $values
	 * @return DataResponse
	 * @throws PreConditionNotMetException
	 */
	public function setConfig(array $values): DataResponse {
		if (isset($values['url'], $values['login'], $values['password'])) {
			return $this->loginWithCredentials($values['url'], $values['login'], $values['password']);
		}

		foreach ($values as $key => $value) {
			$this->config->setUserValue($this->userId, Application::APP_ID, $key, $value);
		}
		$result = [];

		if (isset($values['token'])) {
			if ($values['token'] && $values['token'] === '') {
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'user_id');
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'user_name');
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'user_displayname');
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'token');
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'token_expires_at');
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'cookie');
				$result['user_id'] = '';
				$result['user_name'] = '';
				$result['user_displayname'] = '';
			}
		}
		return new DataResponse($result);
	}

	/**
	 * @param string $url
	 * @param string $login
	 * @param string $password
	 * @return DataResponse
	 * @throws \OCP\PreConditionNotMetException
	 */
	private function loginWithCredentials(string $url, string $login, string $password): DataResponse {
		// cleanup token, cookie and expiration date on classic login
		$this->config->deleteUserValue($this->userId, Application::APP_ID, 'cookie');
		$this->config->deleteUserValue($this->userId, Application::APP_ID, 'token');
		$this->config->deleteUserValue($this->userId, Application::APP_ID, 'token_expires_at');

		$result = $this->wireAPIService->login($url, $login, $password);
		if (isset($result['access_token'], $result['expires_in'], $result['user'])) {
			$this->config->setUserValue($this->userId, Application::APP_ID, 'url', $url);
			$this->config->setUserValue($this->userId, Application::APP_ID, 'token', $result['access_token']);
			$nowTs = (new Datetime())->getTimestamp();
			$expiresAt = $nowTs + (int) $result['expires_in'];
			$this->config->setUserValue($this->userId, Application::APP_ID, 'token_expires_at', $expiresAt);
			$this->config->setUserValue($this->userId, Application::APP_ID, 'cookie', $result['cookie']);

			$this->config->setUserValue($this->userId, Application::APP_ID, 'user_id', $result['user'] ?? '');
			$userInfo = $this->storeUserInfo();
			return new DataResponse([
				'user_id' => $result['user'] ?? '',
				'user_name' => $userInfo['user_name'] ?? '',
				'user_displayname' => $userInfo['name'] ?? '',
			]);
		}
		return new DataResponse([
			'user_id' => '',
			'user_name' => '',
			'user_displayname' => '',
		]);
	}

	/**
	 * set admin config values
	 *
	 * @param array $values
	 * @return DataResponse
	 */
	public function setAdminConfig(array $values): DataResponse {
		foreach ($values as $key => $value) {
			$this->config->setAppValue(Application::APP_ID, $key, $value);
		}
		return new DataResponse(1);
	}

	/**
	 * @return array|string[]
	 * @throws PreConditionNotMetException
	 */
	private function storeUserInfo(): array {
		$info = $this->wireAPIService->request($this->userId, 'self');
		if (isset($info['name'], $info['id'], $info['handle'])) {
			$this->config->setUserValue($this->userId, Application::APP_ID, 'user_id', $info['id'] ?? '');
			$this->config->setUserValue($this->userId, Application::APP_ID, 'user_name', $info['handle'] ?? '');
			$this->config->setUserValue($this->userId, Application::APP_ID, 'user_displayname', $info['name'] ?? '');

			return [
				'user_id' => $info['id'] ?? '',
				'user_name' => $info['handle'] ?? '',
				'user_displayname' => $info['name'] ?? '',
			];
		} else {
			$this->config->setUserValue($this->userId, Application::APP_ID, 'user_id', '');
			$this->config->setUserValue($this->userId, Application::APP_ID, 'user_name', '');
			return [
				'user_id' => '',
				'user_name' => '',
				'user_displayname' => '',
			];
		}
	}
}
