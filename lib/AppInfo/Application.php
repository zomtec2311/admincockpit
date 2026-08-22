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
use OCP\App\IAppManager;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\INavigationManager;
use OCP\IGroupManager;
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
		$igroupmanager = $context->getServerContainer()->get(IGroupManager::class);
		if (!in_array("admin", $igroupmanager->getUserGroups())) return;
		try {
			$context->injectFn($this->registerAppsManagementNavigation(...));
		} catch (NotFoundExceptionInterface|ContainerExceptionInterface|Throwable) {
		}
	}
    
    private function registerAppsManagementNavigation(IAppManager $appManager): void {
		$container = $this->getContainer();
		$appManager->enableAppForGroups(self::APP_ID, array('admin'), false);
		$container->get(INavigationManager::class)->add(function () use ($container) {
			$urlGenerator = $container->get(IURLGenerator::class);
			return [
			'id' => self::APP_ID,
			'order' => 1000,
			'href' => $urlGenerator->linkToRoute(self::APP_ID.'.page.index'),
			'icon' => $urlGenerator->imagePath(self::APP_ID, 'app.svg'),
			'name' => 'Admin Cockpit',
			];
		});
	}
}
