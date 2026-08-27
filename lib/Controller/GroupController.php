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

    
    
    public function addgroup($who): DataResponse {
        try {
            if ($this->groupManager->groupExists($who)) { return new DataResponse('false'); }
            else { 
                $this->groupManager->createGroup($who);
                return new DataResponse('true');
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'AdminCockpit: FATAL ERROR or EXCEPTION in DataController->addgroup: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                ['app' => 'admincockpit']
            );
            return new DataResponse('false');
        }
    }
    
    public function deletegroup($who): DataResponse {
        try {
            if ($this->groupManager->groupExists($who)) { 
                $this->myService->deletegroup($who);
                return new DataResponse('true');
            }
            else { 
                return new DataResponse('false');
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'AdminCockpit: FATAL ERROR or EXCEPTION in DataController->deletegroup: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                ['app' => 'admincockpit']
            );
            return new DataResponse('false');
        }
    }

    #[NoCSRFRequired]
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

        $group = $this->groupManager->get($Groupid);
        if ($group->setDisplayName($newGroupName)) return new JSONResponse(['success' => true, 'message' => $this->l->t('Group renamed successfully')]);
        else return new JSONResponse(['success' => false, 'message' => $this->l->t('Group rename failed')]);
    }
  
  
}
