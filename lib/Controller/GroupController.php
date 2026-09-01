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

    public function groupdata(): DataResponse {
        try {
            $allusers = $this->userManager->search('');
            /*
            $userList = [];
            $usrlist = [];
            $usrdisplayandid = [];
            $userOptions = [];
           // $preloa = [];
            foreach ($users as $user) {
                if($user->getLastLogin()) $status = false;
                else $status = true;
                $mids = $user->getManagerUids();
                if (!$mids) $mids []= null;
                $usrlist[] = $user->getUID();
                $usrdisplayandid[] = [
                    'id'            => $user->getUID(),
                    'usrdisplayname'   => $user->getDisplayName(),
                ];
                $userList[] = [
                    'uid' => $user->getUID(),
                    'displayname' => $user->getDisplayName(),
                    'lastlogin' => $user->getLastLogin(),
                    'firstlogin' => $user->getFirstLogin(),
                    'email' => $user->getEMailAddress(),
                    'cloudid' => $user->getCloudId(),
                    'quota' => $user->getQuota(),
                    'managerids' => $mids,
                    'last' => $this->l->l('datetime', $user->getLastLogin()),
                    'first' => $this->l->l('datetime', $user->getFirstLogin()),
                    'used' => $this->myService->folderSize($user->getHome()),
                    'isadmin' => $this->groupManager->isAdmin($user->getUID()),
                    'status' => $status,
                ];
                foreach ($this->myService->queryStatusForUsers([$user->getUID()]) as $key => $value) {
                    $userstatus = $value->getStatus();
                }

                    $userOptions[] = [
                        'id' => $user->getUID(),
                        'displayName' => $user->getDisplayName(),
                        'isNoUser' => false,
                        'user' => $user->getUID(),
                        'subname' => $user->getEMailAddress(),
                        'preloadedUserStatus' => [ 'status' => $userstatus, 'message' => $userstatus],
                    ];
                }*/
            $groups = $this->groupManager->search('');
            $groupList = [];
            $grlist = [];
            $grdisplaynamelist = [];
            $grpdisplayandid = [];
            foreach ($groups as $group) {
                $gusers = $group->getUsers();
               // $guserList = [];
                //$grlist[] = $group->getGID();
                //$grdisplaynamelist[] = $group->getDisplayName();

                //$grpdisplayandid[] = [
                //    'id'            => $group->getGID(),
                //    'displayname'   => $group->getDisplayName(),
               // ];



/*
            foreach ($gusers as $guser) {
                if($guser->getLastLogin()) $status = false;
                else $status = true;
                $guserList[] = [
                    'uid' => $guser->getUID(),
                    'displayname' => $guser->getDisplayName(),
                    'lastlogin' => $guser->getLastLogin(),
                    'firstlogin' => $guser->getFirstLogin(),
                    'email' => $guser->getEMailAddress(),
                    'cloudid' => $guser->getCloudId(),
                    'quota' => $guser->getQuota(),
                    'managerids' => $guser->getManagerUids(),
                    'last' => $this->l->l('datetime', $guser->getLastLogin()),
                    'first' => $this->l->l('datetime', $guser->getFirstLogin()),
                    'used' => $this->myService->folderSize($guser->getHome()),
                    'isadmin' => $this->groupManager->isAdmin($guser->getUID()),
                    'status' => $status,
                ];
            }*/
$groupid = $group->getGID();
$groupdisplayname = $group->getDisplayName();
                $groupList[] = [
                    'gid' => $groupid,
                    'id' => $groupid,
                    'gdisplayname' => $groupdisplayname,
                    'displayname' => $groupdisplayname,
                    //'gusers' => $gusers,
                    'guserscount' => count($gusers),
                    //'guser' => $guserList,
                ];
            }
            //$adminGroup = $this->groupManager->displayNamesInGroup('admin');

            return new DataResponse([
                //'userOptions' => $userOptions,
                //'userCount' => count($userList),
                'groupCount' => count($groupList),
                //'users' => $userList,
                'groups' => $groupList,
                'alluserscount' => count($allusers),
                //'adminCount' => count($adminGroup),
                //'admins' => $adminGroup,
                //'allusers' => $users,
                //'grlist' => $grlist,
                //'grdisplaynamelist' => $grdisplaynamelist,
                //'usrdisplayandid' => $usrdisplayandid,
                //'displayandid' => $grpdisplayandid,
                //'usrlist' => $usrlist,

            ]);

        } catch (\Throwable $e) {
            $this->logger->error(
                'AdminCockpit: FATAL ERROR or EXCEPTION in GroupController->groupdata: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                ['app' => 'admincockpit']
            );
            return new DataResponse([
                'userCount' => -1,
                'groupCount' => -1,
            ], 500);
        }
    }
  
  
}
