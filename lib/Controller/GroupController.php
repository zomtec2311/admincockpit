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

use OCP\IUserManager;
use OCP\IGroupManager;

class GroupController extends Controller {
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

    
    
    public function addgroup($who) {
        try {
            if ($this->groupManager->groupExists($who)) { return 'false'; }
            else { 
                $this->groupManager->createGroup($who);
                return 'true';
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'AdminCockpit: FATAL ERROR or EXCEPTION in DataController->addgroup: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                ['app' => 'admincockpit']
            );
            return 'false';
        }
    }
    
    public function deletegroup($who) {
        try {
            if ($this->groupManager->groupExists($who)) { 
                $this->myService->deletegroup($who);
                return 'true';                
            }
            else { 
                return 'false';
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'AdminCockpit: FATAL ERROR or EXCEPTION in DataController->deletegroup: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                ['app' => 'admincockpit']
            );
            return 'false';
        }
    }

    #[NoCSRFRequired]
    public function rrrrenamegroup() {
        $rawData = file_get_contents('php://input');

        $data = json_decode($rawData, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $message = $data['what'] ?? '';
            $who = $data['who'] ?? '';
                $para = [
                    'message' => $message,
                ];
                $group = $this->groupManager->get($who);
                if ($group->setDisplayName($message)) return 'true';
                else return false;
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
        }

    }

    public function renameGroup(): JSONResponse
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $oldGroupName = trim($data['oldGroupName'] ?? '');
        $newGroupName = trim($data['newGroupName'] ?? '');
        $Groupid = trim($data['Groupid'] ?? '');

        if ($oldGroupName === '' || $newGroupName === '') {
            return new JSONResponse([
                'success' => false,
                'message' => $this->l->t('Missing group name'),
            ], 400);
        }

        if (mb_strlen($newGroupName) > 100) {
            return new JSONResponse([
                'success' => false,
                'message' => $this->l->t('The group name is too long'),
            ], 400);
        }

        // Hier deine eigentliche Umbenennung durchführen
        // Beispiel:
        // $this->groupManager->get($oldGroupName)->rename($newGroupName);
        $group = $this->groupManager->get($Groupid);
                if ($group->setDisplayName($newGroupName)) return new JSONResponse(['success' => true, 'message' => $this->l->t('Group renamed successfully')]);
                else return new JSONResponse(['success' => false, 'message' => $this->l->t('Group rename failed')]);;





        //return new JSONResponse(['success' => true, 'message' => $this->l->t('Group renamed successfully')]);
    }
  
  
}
