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

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

use OCA\Wire\Service\WireAPIService;
use OCA\Wire\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\PreConditionNotMetException;

class WireAPIController extends Controller {

	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var WireAPIService
	 */
	private $wireAPIService;
	/**
	 * @var string|null
	 */
	private $userId;
	/**
	 * @var IURLGenerator
	 */
	private $urlGenerator;

	public function __construct(string $appName,
								IRequest $request,
								IConfig $config,
								IURLGenerator $urlGenerator,
								WireAPIService $wireAPIService,
								?string $userId) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->wireAPIService = $wireAPIService;
		$this->userId = $userId;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function getWireUrl(): DataResponse {
		$adminUrl = $this->config->getAppValue(Application::APP_ID, 'url') ?: Application::DEFAULT_WIRE_API_URL;
		$userUrl = $this->config->getUserValue($this->userId, Application::APP_ID, 'url', $adminUrl) ?: $adminUrl;
		return new DataResponse($userUrl);
	}

	/**
	 * get wire user avatar
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $domain
	 * @param string $userId
	 * @param int $useFallback
	 * @return DataDisplayResponse|RedirectResponse
	 * @throws PreConditionNotMetException
	 */
	public function getUserAvatar(string $domain, string $userId, int $useFallback = 1) {
		$result = $this->wireAPIService->getUserAvatar($this->userId, $domain, $userId);
		if (isset($result['avatarContent'])) {
			$response = new DataDisplayResponse($result['avatarContent']);
//			$response->cacheFor(60 * 60 * 24);
			return $response;
		} elseif ($useFallback !== 0 && isset($result['userInfo'])) {
			$userName = $result['userInfo']['name'] ?? '??';
			$fallbackAvatarUrl = $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => $userName, 'size' => 44]);
			return new RedirectResponse($fallbackAvatarUrl);
		}
		return new DataDisplayResponse('', Http::STATUS_NOT_FOUND);
	}

	/**
	 * get wire team icon/avatar
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $domain
	 * @param string $teamId
	 * @param int $useFallback
	 * @return DataDisplayResponse|RedirectResponse
	 * @throws PreConditionNotMetException
	 */
	public function getTeamAvatar(string $domain, string $teamId, int $useFallback = 1)	{
		$result = $this->wireAPIService->getTeamAvatar($this->userId, $domain, $teamId);
		if (isset($result['avatarContent'])) {
			$response = new DataDisplayResponse($result['avatarContent']);
//			$response->cacheFor(60 * 60 * 24);
			return $response;
		} elseif ($useFallback !== 0 && isset($result['teamInfo'])) {
			$teamName = $result['teamInfo']['name'] ?? '??';
			$fallbackAvatarUrl = $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => $teamName, 'size' => 44]);
			return new RedirectResponse($fallbackAvatarUrl);
		}
		return new DataDisplayResponse('', Http::STATUS_NOT_FOUND);
	}

	/**
	 * @return DataResponse
	 * @throws \Exception
	 */
	public function getConversations() {
		$result = $this->wireAPIService->getMyConversations($this->userId);
		if (isset($result['error'])) {
			return new DataResponse($result['error'], Http::STATUS_BAD_REQUEST);
		} else {
			return new DataResponse($result);
		}
	}

	/**
	 * @param string $message
	 * @param string $conversationDomain
	 * @param string $conversationId
	 * @return DataResponse
	 * @throws \Exception
	 */
	public function sendMessage(string $message, string $conversationDomain, string $conversationId) {
		$result = $this->wireAPIService->sendMessage($this->userId, $message, $conversationDomain, $conversationId);
		if (isset($result['error'])) {
			return new DataResponse($result['error'], Http::STATUS_BAD_REQUEST);
		} else {
			return new DataResponse($result);
		}
	}

	/**
	 * @param int $fileId
	 * @param string $conversationId
	 * @return DataResponse
	 * @throws \Exception
	 */
	public function sendFile(int $fileId, string $conversationId) {
		$result = $this->wireAPIService->sendFile($this->userId, $fileId, $conversationId);
		if (isset($result['error'])) {
			return new DataResponse($result['error'], Http::STATUS_BAD_REQUEST);
		} else {
			return new DataResponse($result);
		}
	}

	/**
	 * @param array $fileIds
	 * @param string $conversationId
	 * @param string $conversationName
	 * @param string $comment
	 * @param string $permission
	 * @param string|null $expirationDate
	 * @param string|null $password
	 * @return DataResponse
	 * @throws \Exception
	 */
	public function sendLinks(array  $fileIds,
							  string $conversationId, string $conversationDomain, string $conversationName, array $conversationMembers,
							  string $comment, string $permission, ?string $expirationDate = null, ?string $password = null): DataResponse {
		$result = $this->wireAPIService->sendLinks(
			$this->userId, $fileIds, $conversationDomain, $conversationId, $conversationName, $conversationMembers,
			$comment, $permission, $expirationDate, $password
		);
		if (isset($result['error'])) {
			return new DataResponse($result['error'], Http::STATUS_BAD_REQUEST);
		} else {
			return new DataResponse($result);
		}
	}
}
