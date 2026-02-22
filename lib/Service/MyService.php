<?php

namespace OCA\AdminCockpit\Service;

use OCP\IDBConnection;
use OCA\AdminCockpit\Db\MyRepository;
use OCP\IUserManager;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class MyService {
    private $repository;
    private $db;
    private $userManager;
    private $logger;

    public function __construct(MyRepository $repository, IDBConnection $db, IUserManager $userManager, IConfig $config, LoggerInterface $logger) {
        $this->repository = $repository;
        $this->db = $db;
        $this->userManager = $userManager;
        $this->config = $config;
        $this->logger = $logger;
    }    
    
    public function getCountFromDb(string $who=''): int {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->func()->count('*'))
           ->from($who);
        $sql = '';
        try {
            $sql = $qb->getSQL();
        } catch (\Throwable $e) {
            $this->logger->warning('AdminCockpit: Failed to get SQL string from QueryBuilder: ' . $e->getMessage(), ['app' => 'admincockpit']);
            return -99; }
        try {
            $result = $qb->execute();
            if ($result === false) {
                $this->logger->warning('AdminCockpit: Query execution failed (returned boolean false).', ['app' => 'admincockpit', 'sql' => $sql]);
                return -1;
            }
            if ($result === null) {
                $this->logger->warning('AdminCockpit: Query execution returned null.', ['app' => 'admincockpit', 'sql' => $sql]);
                return -2;
            }
            if (!is_object($result) || !method_exists($result, 'fetchColumn')) {
                $this->logger->warning('AdminCockpit: Query execution returned an unexpected type/object without fetchColumn method.', ['app' => 'admincockpit', 'result_type' => gettype($result), 'sql' => $sql]);
                return -3;
            }
            $count = (int)$result->fetchColumn();
            $result->closeCursor();
            return $count;
        } catch (\Throwable $e) {
            $this->logger->error(
                'AdminCockpit: FATAL ERROR or EXCEPTION during user count DB query: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                ['app' => 'admincockpit', 'sql' => $sql]
            );
            return -4;
        }
    }
    
function getFolderSize($dir) {
    $dir = escapeshellarg($dir);
    $output = shell_exec("du -sb {$dir}");
    if ($output) {
        $parts = explode("\t", $output);
        return (int) $parts[0];
    }
    return false;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

private function humanReadableSize(int $kb): string {
    $units = ['KB', 'MB', 'GB', 'TB'];
    $i = 0;
    $size = $kb;

    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }

    return round($size, 2) . ' ' . $units[$i];
}

function formatramBytes($bytes, $precision = 2) {
    $bytes = $bytes * 1000;
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1000));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1000, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function storagefree($folderPath) {
$freeSpace = disk_free_space($folderPath);
if ($freeSpace !== false) {
    return $freeSpace;
} else {
    return -5;
}
}

function storageall($folderPath) {
$totalSpace = disk_total_space($folderPath);
if ($totalSpace !== false) {
    return $totalSpace;
} else {
    return -6;
}   
} 

function folderSize($dir) {
    $size = 0;
    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : $this->ffolderSize($each);
    }
    return $this->formatBytes($size);
}

function ffolderSize($dir) {
    $size = 0;
    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : $this->ffolderSize($each);
    }
    return $size;
}

    public function deletegroup(string $id): int {
        
        $qb = $this->db->getQueryBuilder();
        $qb->delete('groups')
            ->where($qb->expr()->eq('gid', $qb->expr()->literal($id)))
            ->execute();
        return 1;
    }
    
    public function deleteadmingroup(string $uid, string $x): int {        
        $qb = $this->db->getQueryBuilder();
        $qb->delete('group_admin')
            ->where($qb->expr()->eq('uid', $qb->expr()->literal($uid)))
            ->andWhere($qb->expr()->eq('gid', $qb->expr()->literal($x)))            
            ->execute();
        return 1;
    }
    
    public function addadmingroup(string $uid, string $x): int {        
        $qb = $this->db->getQueryBuilder();
        $qb->insert('group_admin')
           ->values([
               'gid' => $qb->expr()->literal($x),
               'uid' => $qb->expr()->literal($uid),
           ])
           ->execute();
        return 1;
    }

function admingroup(string $uid): array {
    $gids = [];

        try {
            $queryBuilder = $this->db->getQueryBuilder();
            $queryBuilder
                ->select('gid') 
                ->from('group_admin')
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid)));
            $result = $queryBuilder->execute();
            while ($row = $result->fetch()) {
                $gids[] = $row['gid'];
            }
            $result->closeCursor();
        } catch (\OCP\DB\Exception $e) {
            error_log("Nextcloud Query Builder Error: " . $e->getMessage());
        } catch (\Exception $e) {
            error_log("Nextcloud Unexpected Error in Query Builder: " . $e->getMessage());
        }

        return $gids;
}

public function getDBSystemInfo(): array {
        $type = $this->db->getDatabaseProvider();
        $version = $this->db->getServerVersion();
        $size = $this->calculateDBSize($type);

        return [
            'type'    => $type,
            'version' => $version,
            'size' => $size
        ];
    }
    
    private function calculateDBSize(string $type): float {
        try {
            if ($type === 'mysql') {
                $sql = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) 
                        FROM information_schema.tables 
                        WHERE table_schema = DATABASE()";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                return (float)$stmt->fetchColumn();
            } 
            
            if ($type === 'pgsql') {
                $sql = "SELECT ROUND(pg_database_size(current_database()) / 1024 / 1024, 2)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                return (float)$stmt->fetchColumn();
            }

            if ($type === 'sqlite') {
                $dbname = $this->config->getSystemValue('dbname', 'owncloud');
                $dbfile = $dbname . '.db';
                $dataPath = $this->config->getSystemValue('datadirectory');
                $fullPath = $dataPath . '/' . $dbfile;
                if (file_exists($fullPath) && is_readable($fullPath)) {
                    $sizeInBytes = filesize($fullPath);
                    return round($sizeInBytes / 1024 / 1024, 2);
                }

                return 0.0; 
            }
            
            
        } catch (\Exception $e) {
            return 0.0;
        }
        return 0.0;
    }
    
    public function getNCInfo(): array {
        $data = [
            'nc_version' => $this->config->getSystemValue('version'),
            'datadirectory' => $this->config->getSystemValue('datadirectory'),
        ];
        
        return $data;
    }
    
    public function getCpuInfo() {
        $load = sys_getloadavg();
        $cpuInfo = [];
        if (is_readable('/proc/cpuinfo')) {
            $data = file_get_contents('/proc/cpuinfo');
            $lines = explode("\n", $data);

            foreach ($lines as $line) {
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line);
                    $key = trim($key);
                    $value = trim($value);
                    if ($key === 'model name') $cpuInfo['model'] = $value;
                    if ($key === 'Model') $cpuInfo['model1'] = $value;
                }
            }
        }
        $cpuInfo['cores'] = $this->getCpuCoreCount();
        if (empty($cpuInfo['model'])) {
            $cpuInfo['model'] = $cpuInfo['model1'] ?: 'unknown';
        }
        
        $cpuInfo['load'] = $load;

        return $cpuInfo;
    }
    
    public function getCpuCoreCount() {
        if (PHP_OS_FAMILY == 'Windows') {
            $cores = shell_exec('echo %NUMBER_OF_PROCESSORS%');
        }
        else {
            $cores = shell_exec('nproc');
        }
        return (int)$cores;
    }
    
    public function getRamInfo(): array {
    $data = [
        'ram_total' => '0 GB',
        'ram_used' => '0 GB',
        'ram_free' => '0 GB',
        'ram_percent' => '0%'
    ];

    if (!is_readable('/proc/meminfo')) {
        return $data;
    }

    $meminfo = file_get_contents('/proc/meminfo');
    preg_match('/MemTotal:\s+(\d+)/', $meminfo, $totalMatches);
    preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $availableMatches);

    if (isset($totalMatches[1]) && isset($availableMatches[1])) {
        $totalKb = (int)$totalMatches[1];
        $availableKb = (int)$availableMatches[1];
        $usedKb = $totalKb - $availableKb;

        $percent = round(($usedKb / $totalKb) * 100, 2);

        $data['ram_total'] = $this->humanReadableSize($totalKb);
        $data['ram_used'] = $this->humanReadableSize($usedKb);
        $data['ram_available'] = $this->humanReadableSize($availableKb);
        $data['ram_percent'] = $percent . '%';
    }

    return $data;
}
    
    public function getPhpEnvironmentInfo() {
        $mlimit = ini_get('memory_limit');

        $int_var = preg_replace('/[^0-9]/', '', $mlimit); 
        if ($int_var > 1000) { $mlimit = $this->humanReadableSize($int_var / 1024); }
  
        return [
            'php_version' => PHP_VERSION,
            'memory_limit' => $mlimit,
            'max_execution_time' => ini_get('max_execution_time'),
            'max_upload_size' => ini_get('upload_max_filesize'),
            'opcache_freq' => ini_get('opcache.revalidate_freq') ?: 'Deaktiviert',
            'extensions' => (function() {
                $exts = get_loaded_extensions();
                natcasesort($exts);
                return implode(', ', $exts);
            })(),
        ];
    }
	
	public function getDiskInfo(): array {
    $data = [];

    try {
        $disks = $this->executeCommand('df -TPk');
    } catch (RuntimeException $e) {
        return $data;
    }

    $lines = explode("\n", trim($disks));

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || str_contains($line, 'Filesystem')) {
            continue;
        }

        $parts = preg_split('/\s+/', $line);
        if (count($parts) < 7) {
            continue;
        }

        $filesystem = $parts[0];
        $type       = $parts[1];
        $blocks     = (int)$parts[2];
        $used_blocks = (int)$parts[3];
        $available  = (int)$parts[4];
        $capacity   = $parts[5];
        $mounted    = $parts[6];
        $used_kb = $used_blocks;
        $available_kb = $available;
        if (in_array($type, ['tmpfs', 'devtmpfs', 'squashfs', 'shm'])) {
            continue;
        }
        if (in_array($mounted, ['/etc/hostname', '/etc/hosts', '/etc/resolv.conf'])) {
            continue;
        }

        $disk = new \stdClass();
        $disk->Device    = $filesystem;
        $disk->Fs        = $type;
        $disk->Used      = (int)ceil($used_blocks / 1024);
        $disk->Available = (int)floor($available / 1024);
        $disk->Percent   = $capacity;
        $disk->Mount     = $mounted;
        $disk->UsedFormatted      = $this->humanReadableSize($used_kb);
        $disk->AvailableFormatted = $this->humanReadableSize($available_kb);
        $disk->TotalFormatted     = $this->humanReadableSize($blocks);

        $data[] = $disk;
    }

    return $data;
}
	
	protected function executeCommand(string $command): string {
		if (function_exists('shell_exec') === false) {
			throw new RuntimeException('shell_exec unavailable');
		}

		$output = shell_exec(escapeshellcmd($command));
		if ($output === false || $output === null || $output === '') {
			throw new RuntimeException('No output for command: "' . $command . '"');
		}

		return $output;
	}
}
