<?php
/**
 * Nextcloud - Wire
 *
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2022
 */

namespace OCA\Wire\AppInfo;

use Closure;
use OCA\DAV\Events\CalendarObjectCreatedEvent;
use OCA\DAV\Events\CalendarObjectUpdatedEvent;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;

use OCP\Util;

/**
 * Class Application
 *
 * @package OCA\Wire\AppInfo
 */
class Application extends App implements IBootstrap {
	public const APP_ID = 'integration_wire';
	public const DEFAULT_WIRE_API_URL = 'https://prod-nginz-https.wire.com';
	public const DEFAULT_WIRE_WEB_URL = 'https://app.wire.com';

	public const INTEGRATION_USER_AGENT = 'Nextcloud Wire integration';

	/**
	 * @var mixed
	 */
	private $config;

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();
		$this->config = $container->get(IConfig::class);

		$eventDispatcher = $container->get(IEventDispatcher::class);
		// load files plugin script
		$eventDispatcher->addListener(LoadAdditionalScriptsEvent::class, static function () {
			Util::addscript(self::APP_ID, self::APP_ID . '-filesplugin', 'files');
			Util::addStyle(self::APP_ID, self::APP_ID . '-files');
		});
	}

	public function register(IRegistrationContext $context): void {
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(Closure::fromCallable([$this, 'registerNavigation']));
	}

	public function registerNavigation(IUserSession $userSession): void {
		$user = $userSession->getUser();
		if ($user !== null) {
			$userId = $user->getUID();
			$container = $this->getContainer();

			if ($this->config->getUserValue($userId, self::APP_ID, 'navigation_enabled', '0') === '1') {
				$adminUrl = $this->config->getAppValue(Application::APP_ID, 'url', self::DEFAULT_WIRE_WEB_URL) ?: self::DEFAULT_WIRE_WEB_URL;
				$userUrl = $this->config->getUserValue($userId, self::APP_ID, 'url', $adminUrl) ?: $adminUrl;
				if ($userUrl === '') {
					return;
				}
				$container->get(INavigationManager::class)->add(function () use ($container, $userUrl) {
					$urlGenerator = $container->get(IURLGenerator::class);
					$l10n = $container->get(IL10N::class);
					return [
						'id' => self::APP_ID,
						'order' => 10,
						'href' => $userUrl,
						'icon' => $urlGenerator->imagePath(self::APP_ID, 'app.svg'),
						'name' => $l10n->t('Wire'),
					];
				});
			}
		}
	}
}

