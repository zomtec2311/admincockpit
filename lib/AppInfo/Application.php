<?php

declare(strict_types=1);

namespace OCA\AdminCockpit\AppInfo;

use OCP\AppFramework\App;
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
use OCP\IURLGenerator;

class Application extends App implements IBootstrap {
    public const APP_ID = 'admincockpit';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
    }

    public function boot(IBootContext $context): void {
		$server = $context->getServerContainer();
		try {
			$context->injectFn($this->registerAppsManagementNavigation(...));
		} catch (NotFoundExceptionInterface|ContainerExceptionInterface|Throwable) {
		}
	}
    
    private function registerAppsManagementNavigation(IConfig $config, IAppManager $appManager): void {
		$container = $this->getContainer();
        $this->config = $config;
		$appManager->enableAppForGroups(self::APP_ID, array('admin'), false);
		$wtpara_menue = 2;
		if ($wtpara_menue == 1) {
			$container->get(INavigationManager::class)->add(function () use ($container) {
				$urlGenerator = $container->get(IURLGenerator::class);
				return [
					'id' => self::APP_ID,
					'order' => 2,
					'href' => $urlGenerator->linkToRoute(self::APP_ID.'.page.index'),
					'icon' => $urlGenerator->imagePath(self::APP_ID, 'app-dark.svg'),
					'name' => 'Admin Cockpit',
					'type' => 'settings'
				];
			});
		}
		else {
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
}
