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

namespace OCA\AdminCockpit\Controller;

use OC\App\AppManager;
use OC\App\AppStore\Bundles\BundleFetcher;
use OC\App\AppStore\Fetcher\AppDiscoverFetcher;
use OC\App\AppStore\Fetcher\AppFetcher;
use OC\App\AppStore\Fetcher\CategoryFetcher;
use OC\App\AppStore\Version\VersionParser;
use OC\App\DependencyAnalyzer;
use OCP\L10N\IFactory;

use OC\Installer;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\IL10N;
use OCP\IConfig;
use OCP\AppFramework\Db\TTransactional;
use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCA\AdminCockpit\Service\MyService;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use OCP\IAppConfig;
use OCP\App\IAppManager;
use OCP\Settings\IManager;
use OCP\IUserManager;
use OCP\IGroupManager;

class AppsController extends Controller {
    private $myService;
    private $logger;
    private $config;
    private $userManager;
    private $groupManager;
    private $l;
    private IAppManager $appManager;

    public function __construct(
            string $appName, 
            IRequest $request, 
            MyService $myService, 
            LoggerInterface $logger, 
            IConfig $config,
            IAppManager $appManager,
            IUserManager $userManager, 
            IGroupManager $groupManager, 
            IL10N $l, 
            private Installer $installer,
            private IAppConfig $appConfig,
            private IManager $settingManager,
            private IFactory $l10nFactory, //NEU
            private CategoryFetcher $categoryFetcher, //NEU
        ) {
        parent::__construct($appName, $request);
        $this->myService = $myService;
        $this->logger = $logger;
        $this->config = $config;
        $this->appManager = $appManager;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->settingManager = $settingManager;
        $this->l = $l;
    }

    
    
    public function appsinfo(): DataResponse {
        try {
            $thisapps = $this->appManager->getAllAppsInAppsFolders();
            sort($thisapps);
            $thisappsenabled = $this->appManager->getInstalledApps();
            $thisappsdisabled = array_diff($thisapps, $thisappsenabled);
            $thisappsdisabledfull = $this->appsfull($thisappsdisabled);
            $thisappsenabledfull = $this->appsfull($thisappsenabled);
            $getadminsections = $this->settingManager->getAdminSections();
            $getpersonalsections = $this->settingManager->getPersonalSections();
            $dummy = [];
            $i = 0;
            foreach($getadminsections as $key => $value){
                $dummy[$i] = $key;
                $i++;
            }
            $adminsections = [];
            $adminsectionsappname = [];
            $i = 0;
            foreach($dummy as $key => $value){
                    $adminsections[$i] = $getadminsections[$value][0]->getID();
                    $adminsectionsappname[$i] = $getadminsections[$value][0]->getName();
                    $adminsectionsappicon[$i] = $getadminsections[$value][0]->getIcon();
                    if ( $getadminsections[$value][1] != '' ) {
                        $adminsections[$i + 1] = $getadminsections[$value][1]->getID();
                        $adminsectionsappname[$i +1 ] = $getadminsections[$value][1]->getName();
                        $adminsectionsappicon[$i + 1] = $getadminsections[$value][1]->getIcon();
                        $i = $i+1;
                    }
                    if ( $getadminsections[$value][2] != '' ) {
                        $adminsections[$i + 1] = $getadminsections[$value][2]->getID();
                        $adminsectionsappname[$i +1 ] = $getadminsections[$value][2]->getName();
                        $adminsectionsappicon[$i + 1] = $getadminsections[$value][2]->getIcon();
                        $i = $i+1;
                    }
                    $i++;
            }
            $dummy = [];
            $i = 0;
            foreach($getpersonalsections as $key => $value){
                $dummy[$i] = $key;
                $i++;
            }
            $personalsections = [];
            $personalsectionsappname = [];
            $i = 0;
            foreach($dummy as $key => $value){
                    $personalsections[$i] = $getpersonalsections[$value][0]->getID();
                    $personalsectionsappname[$i] = $getpersonalsections[$value][0]->getName();
                    $personalsectionsappicon[$i] = $getpersonalsections[$value][0]->getIcon();
                    
                if ( $getpersonalsections[$value][1] != '' ) {
                    if ($getpersonalsections[$value][1]->getID() != 'calendar') {
                        $personalsections[$i + 1] = $getpersonalsections[$value][1]->getID();
                        $personalsectionsappname[$i +1 ] = $getpersonalsections[$value][1]->getName();
                        $personalsectionsappicon[$i + 1] = $getpersonalsections[$value][1]->getIcon();
                        $i = $i+1;
                    }
                }
                if ( $getpersonalsections[$value][2] != '' ) {
                        $personalsections[$i + 1] = $getpersonalsections[$value][2]->getID();
                        $personalsectionsappname[$i +1 ] = $getpersonalsections[$value][2]->getName();
                        $personalsectionsappicon[$i + 1] = $getpersonalsections[$value][2]->getIcon();
                        $i = $i+1;
                }
                $i++;
            }

            return new DataResponse([
                'adminsections' => $adminsections,
                'adminsectionsappname' => $adminsectionsappname,
                'adminsectionsappicon' => $adminsectionsappicon,
                'personalsections' => $personalsections,
                'personalsectionsappname' => $personalsectionsappname,
                'personalsectionsappicon' => $personalsectionsappicon,                
                'allapps' => count($thisapps),
                'appsenabled' => count($thisappsenabled),
                'thisapps' => $thisapps,
                'thisappsenabled' => $thisappsenabled,
                'thisappsdisabled' => $thisappsdisabled,
                'thisappsdisabledfull' => $thisappsdisabledfull,
                'thisappsenabledfull' => $thisappsenabledfull,               
            ]);

        } catch (\Throwable $e) {
            $this->logger->error(
                'AdminCockpit: FATAL ERROR or EXCEPTION in DataController->appsinfo: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                ['app' => 'admincockpit']
            );
            return new DataResponse([
                'db' => -1,
            ], 500);
        }
    }
    
    public function appsfull($apps) {
        $i = 0;
        $wtarr = [];
        $obja = new \stdClass();
        foreach($apps as $appid){
            $icon = $this->appManager->getAppIcon($appid, false);
            $obja->appid = $appid;
            $obja->id = $i;
            $wtarr[$i]["appid"] = $appid;
            $wtarr[$i]["name"] = $this->appManager->getAppInfo($appid, false, "en_GB");
            $wtarr[$i]["id"] = $i;
            $wtarr[$i]["icon"] = $icon ? $icon : $this->appManager->getAppWebPath('admincockpit') . "/img/dummy.svg";
            
            $wtarr[$i]["version"] = $this->appManager->getAppVersion($appid, true);
            $wtarr[$i]["shipped"] = $this->appManager->isShipped($appid);
            $i++;
            
}

		  return $wtarr;
    }
    
    public function disableapp($who) {
        try {
            if ($this->appManager->isInstalled($who)) { 
                $this->appManager->disableApp($who, false);
                return 'true';                
            }
            else { 
                return 'false';
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'AdminCockpit: FATAL ERROR or EXCEPTION in DataController->disableapp: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                ['app' => 'admincockpit']
            );
            return 'false';
        }
    }
    
    public function enableapp($who) {
        
            $this->appManager->enableApp($who, false);
                return 'true';                
            
    }
    
    public function uuupdateapp($who) {
        
        try {
            if ($this->appManager->isInstalled($who)) { 
                $this->installer->updateAppstoreApp($who, false);
                return 'true';                
            }
            else { 
                return 'false';
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'AdminCockpit: FATAL ERROR or EXCEPTION in DataController->disableapp: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                ['app' => 'admincockpit']
            );
            return 'false';
        }
    }
    
    public function updateapp(string $who): JSONResponse {
        set_time_limit(300);
		$appId = $this->appManager->cleanAppId($who);

		$this->config->setSystemValue('maintenance', true);
		try {
			$result = $this->installer->updateAppstoreApp($appId);
			$this->config->setSystemValue('maintenance', false);
		} catch (\Exception $ex) {
			$this->config->setSystemValue('maintenance', false);
			return new JSONResponse(['data' => ['message' => $ex->getMessage()]], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		if ($result !== false) {
			return new JSONResponse(['data' => ['appid' => $appId]]);
		}
		else {
            $this->logger->error('Could not update app. ');
		return new JSONResponse(['data' => ['message' => $this->l10n->t('Could not update app.')]], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
	}
    
    //#[NoAdminRequired]
    //#[NoCSRFRequired]
    public function getAppsWithUpdates(): DataResponse {
		$appClass = new \OC_App();
		$apps = $appClass->listAllApps();
		foreach ($apps as $key => $app) {
			$newVersion = $this->installer->isUpdateAvailable($app['id']);
			if ($newVersion === false) {
				unset($apps[$key]);
			}
		}
		return new DataResponse([
            'apps' => array_values($apps),
            'appscount' => count($apps),
        ]);
	}
	
	public function listCategories(): JSONResponse {
		return new JSONResponse($this->getAllCategories());
	}

	private function getAllCategories() {
		$currentLanguage = substr($this->l10nFactory->findLanguage(), 0, 2);

		$categories = $this->categoryFetcher->get();
		return array_map(fn ($category) => [
			'id' => $category['id'],
			'displayName' => $category['translations'][$currentLanguage]['name'] ?? $category['translations']['en']['name'],
		], $categories);
	}
  
}
