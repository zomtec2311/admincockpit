<?php
/**
 *
 * AdminCockpit APP (Nextcloud)
 *
 * @author Wolfgang Tödt <wtoedt@gmail.com>
 *
 * @copyright Copyright (c) 2025 Wolfgang Tödt
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace OCA\AdminCockpit\AppInfo;

use OCP\AppFramework\App;
use OCP\Server;
use OCP\App\IAppManager;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\Util;
use Psr\Log\LoggerInterface;
use OCA\AdminCockpit\Controller\DataController;
use OCP\INavigationManager;
use OCP\IServerContainer;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\IUser;
use OCP\IURLGenerator;
use OCA\AdminCockpit\Dashboard\AdminCockpitWidget;


class Application extends App implements IBootstrap {
    public const APP_ID = 'admincockpit';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
		$context->registerNotifierService(\OCA\AdminCockpit\Notification\Notifier::class);
		$context->registerDashboardWidget(\OCA\AdminCockpit\Dashboard\AdminCockpitWidget::class);
	}

	public function boot(IBootContext $context): void {
		$igroupManager = $context->getServerContainer()->get(IGroupManager::class);
		$iuserSession = $context->getServerContainer()->get(IUserSession::class);

		$navigationManager = $context->getServerContainer()->get(INavigationManager::class);
        $urlGenerator = $context->getServerContainer()->get(IURLGenerator::class);
		$appManager = $context->getServerContainer()->get(IAppManager::class);

		$appManager->enableAppForGroups(self::APP_ID, array('admin'), false);

		$myuid = $iuserSession->getUser();

		if ($myuid === null) {
			return;
		}

		if (!in_array("admin", $igroupManager->getUserGroupIds($myuid))) {
			return;
		}

		try {
			$navigationManager->add(function () use ($urlGenerator) {

				$myapptop = [
					'id' => self::APP_ID,
					'order' => 1000,
					'href' => $urlGenerator->linkToRoute(self::APP_ID.'.page.index'),
					'icon' => $urlGenerator->imagePath(self::APP_ID, 'app.svg'),
					'name' => 'Admin Cockpit',
					'type' => 'link',
					//'classes' => 'highlighted-nav-item js-admin-tab',
					'app' => self::APP_ID
				];

				return $myapptop;
			});
		} catch (NotFoundExceptionInterface|ContainerExceptionInterface|Throwable) {
		}
	}
}
