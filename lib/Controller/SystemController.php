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
use OC\Updater\VersionCheck;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\ServerVersion;

class SystemController extends Controller {
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
            protected VersionCheck $versionCheck,
            protected ServerVersion $serverVersion,
            private IAppConfig $appConfig
        ) {
        parent::__construct($appName, $request);
        $this->myService = $myService;
        $this->logger = $logger;
        $this->config = $config;
        $this->appManager = $appManager;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->l = $l;
    }

    
    
    public function storage(): DataResponse {
        try {
            $folder = $this->config->getSystemValue('datadirectory');
            $folder1 = $this->myService->getFolderSize($folder);
            $folder11 = $this->myService->formatBytes($folder1);
            $folder2 = $this->myService->storagefree($folder);
            $folder22 = $this->myService->formatBytes($folder2);
            $folder3 = $this->myService->storageall($folder);
            $folder33 = $this->myService->formatBytes($folder3);
            $folder4 = $folder3 - $folder2;
            $folder44 = $this->myService->formatBytes($folder4);

            return new DataResponse([
                'folder' => $folder,
                'folder1' => $folder1,
                'folder11' => $folder11,
                'folder2' => $folder2,
                'folder22' => $folder22,
                'folder3' => $folder3,
                'folder33' => $folder33,
                'folder4' => $folder4,
                'folder44' => $folder44,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error(
                'AdminCockpit: FATAL ERROR or EXCEPTION in DataController->storage: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                ['app' => 'admincockpit']
            );
            return new DataResponse([
                'folder' => -1,
                'folder1' => -1,
                'folder11' => -1,
                'folder2' => -1,
                'folder22' => -1,
                'folder3' => -1,
                'folder33' => -1,
                'folder4' => -1,
                'folder44' => -1,
            ], 500);
        }
    } 
    
    public function sqlinfo(): DataResponse {
        try {
             $thisdb = $this->config->getSystemValue('dbtype');
                $thisdbb = $this->myService->getDBSystemInfo();

            return new DataResponse([
                'dbtyp' => $thisdbb['type'],
                'dbversion' => $thisdbb['version'],
                'dbsize' => $thisdbb['size'],                                   
            ]);

        } catch (\Throwable $e) {
            $this->logger->error(
                'AdminCockpit: FATAL ERROR or EXCEPTION in DataController->sqlinfo: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                ['app' => 'admincockpit']
            );
            return new DataResponse([
                'db' => -1,
            ], 500);
        }
    }    
    
public function detectEnvironment() {
    if ($this->isRunningInDocker()) {
        return $this->l->t('docker container');
    } elseif ($this->isRunningInSnap()) {
        return $this->l->t('snap package');
    } elseif ($this->isRunningInLXC()) {
        return $this->l->t('LXC container');
    } elseif ($this->isRunningInVM()) {
        return $this->l->t('virtual machine');
    } else {
        return $this->l->t('local installation');
    }
}

public function isRunningInDocker() {
    return file_exists('/.dockerenv') || $this->isDockerCgroup();
}

public function isDockerCgroup() {
    $cgroupPath = file_get_contents('/proc/self/cgroup');
    return strpos($cgroupPath, 'docker') !== false || strpos($cgroupPath, 'kubepods') !== false;
}

public function isRunningInSnap() {
    if ($this->isSnapDaemonRunning()) {
        $output = shell_exec('snap list');
        return strpos($output, 'nextcloud') !== false;
    }
    return false;
}

public function isSnapDaemonRunning() {
    $output = shell_exec('systemctl is-active snapd');
    return trim($output) === 'active';
}

public function isRunningInLXC() {
    $cgroupPath = file_get_contents('/proc/self/cgroup');
    return strpos($cgroupPath, 'lxc') !== false || file_exists('/var/lib/lxc');
}

public function isRunningInVM() {
    $virtDetect = shell_exec('systemd-detect-virt');
    return strpos($virtDetect, 'none') === false && !empty($virtDetect);
}

public function isBehindProxy() {
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) || 
        isset($_SERVER['HTTP_CLIENT_IP']) || 
        isset($_SERVER['HTTP_X_REAL_IP'])) {
        return true;
    }
    return false;
}

public function getServerType() {
    if (isset($_SERVER['SERVER_SOFTWARE'])) {
        return $_SERVER['SERVER_SOFTWARE'];
    }
    return 'unknown';
}
 
    public function systeminfo(): DataResponse {
        try {
            $cpu = $this->myService->getCpuInfo();
            $phpinfo = $this->myService->getPhpEnvironmentInfo();
            $wttest = $this->myService->getDiskInfo();
            $raminfo = $this->myService->getRAMInfo();
            $ncinfo = $this->myService->getNCInfo();
            $ncupdate = $this->getSystemStatus();
            $logfile = $this->getlogfile();
            /*
             p($l->t('Select file from %1$slocal filesystem%2$s or %3$scloud%4$s', ['<a href="#" id="browselink">', '</a>', '<a href="#" id="cloudlink">', '</a>']));
             */
            
            return new DataResponse([
                'hostname' => gethostname(),
                'osname' => PHP_OS . ' ' . php_uname('r') . ' ' . php_uname('m'),
                'cpu' => ($cpu['model'] ?? 'unknown') . ' (' . ($cpu['cores'] ?? 'unknown') . ' ' .$this->l->t('Threads') . ')' ,
                'load1' => round(($cpu['load'][0] / $cpu['cores']) * 100, 2),
                'load5' => round(($cpu['load'][1] / $cpu['cores']) * 100, 2),
                'load15' => round(($cpu['load'][2] / $cpu['cores']) * 100, 2),
                'php_version' => $phpinfo['php_version'],
                'memory_limit' => $phpinfo['memory_limit'],
                'max_upload_size' => $phpinfo['max_upload_size'],
                'extensions' => $phpinfo['extensions'],
                'max_execution_time' => $phpinfo['max_execution_time'],
                'opcache_freq' => $phpinfo['opcache_freq'],
                'diskinfo' => $wttest,
                'ram_total' => $raminfo['ram_total'],
                'ram_used' => $raminfo['ram_used'],
                'ram_available' => $raminfo['ram_available'],
                'ram_percent' => $raminfo['ram_percent'],
                'webserver' => ($this->isBehindProxy()) ? $this->l->t('%1$s - behind reverse proxy', [$this->getServerType()]) : $this->l->t('%1$s - without reverse proxy', [$this->getServerType()]),
                'nc_version' => $ncinfo['nc_version'],
                'nc_installation_type' => $this->detectEnvironment(),
                'nc_datadirectory' => $ncinfo['datadirectory'],
                'nc_updateAvailable' => $ncupdate['updateAvailable'],
                'nc_currentVersion' => $ncupdate['currentVersion'],
                'nc_updateVersion' => $ncupdate['updateVersion'],
                'nc_currentVersionimplode' => $ncupdate['currentVersionimplode'],
                'nc_logfile' => $logfile['file'],
                'nc_logfile_size' => $logfile['filesize'],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error(
                'AdminCockpit: FATAL ERROR or EXCEPTION in DataController->systeminfo: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                ['app' => 'admincockpit']
            );
            return new DataResponse([
                'db' => -1,
            ], 500);
        }
    }
    
    public function getSystemStatus(): array {
        $currentVersion = \OCP\Util::getVersion();
		$currentVersionimplode = implode('.', $currentVersion);
        $check = $this->versionCheck->check();
        $update = true;
        if (!isset($check['version'])) {
            $update = false;
            $check['version'] = '';
        }
        if (!$this->config->getSystemValueBool('updatechecker', true)) {
			$update = false;
		}

		if (\in_array($this->serverVersion->getChannel(), ['daily', 'git'], true)) {
			$update = false;
		}

    $newVersion = '';
    if ($update) {
        $newVersion = $this->config->getAppValue('core', 'lastupdatedat', '');
    }
    $data = [
            'updateAvailable' => (bool)$update,
            'updateVersion' => $check['version'],
            'currentVersion' => $currentVersion,
            'currentVersionimplode' => $currentVersionimplode,
        ];
    return $data;
}

 public function getlogfile(): array {
     
        $wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
		if (file_exists($wtlogfile)) {
			$data = [
            'file' => $wtlogfile,
            'filesize' => $this->show_filesize($wtlogfile, 2),
            ];
            return $data;
            }
		else {
            $data = [
            'file' => $this->l->t('not available'),
            'filesize' => $this->l->t('not available'),
            ];
            return $data;
            }
			
		}
		
		public function show_filesize($filename, $decimalplaces = 0) {
            $size = filesize($filename);
            $sizes = array('B', 'kB', 'MB', 'GB', 'TB');
            for ($i=0; $size > 1024 && $i < count($sizes) - 1; $i++) {
                $size /= 1024;
            }
            return round($size, $decimalplaces).' '.$sizes[$i];
        }


protected function checkCoreUpdate(): void {
		if (!$this->config->getSystemValueBool('updatechecker', true)) {
			return;
		}

		if (\in_array($this->serverVersion->getChannel(), ['daily', 'git'], true)) {
			return;
		}

		$status = $this->versionCheck->check();
		if ($status === false) {
			$errors = 1 + $this->appConfig->getAppValueInt('update_check_errors', 0);
			$this->appConfig->setAppValueInt('update_check_errors', $errors);

			if (\in_array($errors, self::CONNECTION_NOTIFICATIONS, true)) {
				$this->sendErrorNotifications($errors);
			}
		} elseif (\is_array($status)) {
			$this->appConfig->setAppValueInt('update_check_errors', 0);
			$this->clearErrorNotifications();

			if (isset($status['version'])) {
				$this->createNotifications('core', $status['version'], $status['versionstring']);
			}
		}
	}
    
  
}
