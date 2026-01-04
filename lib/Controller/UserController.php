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

class UserController extends Controller {
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

    /**
     * @PublicPage
     * @NoAdminRequired
     * @NoCSRFRequired
     * @Route("/usercount",
     */
    public function usercount(): DataResponse {
        try {
            $users = $this->userManager->search('');
            $userList = [];
            $usrlist = [];
            foreach ($users as $user) {
                if($user->getLastLogin()) $status = false;
                else $status = true;
                $mids = $user->getManagerUids();
                if (!$mids) $mids []= null;
                $usrlist[] = $user->getUID();
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
            }
            
            $groups = $this->groupManager->search('');
            $groupList = [];
            $grlist = []; 
            foreach ($groups as $group) {
                $gusers = $group->getUsers();
                $guserList = [];                               
                $grlist[] = $group->getGID();
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
            }
                $groupList[] = [
                    'gid' => $group->getGID(),
                    'gusers' => $gusers,
                    'guserscount' => count($gusers),
                    'guser' => $guserList,
                ];
            }
            $adminGroup = $this->groupManager->displayNamesInGroup('admin');
            return new DataResponse([
                'userCount' => count($userList),
                'groupCount' => count($groupList),
                'users' => $userList,
                'groups' => $groupList,
                'adminCount' => count($adminGroup),
                'admins' => $adminGroup,
                'allusers' => $users,
                'grlist' => $grlist,
                'usrlist' => $usrlist,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error(
                'AdminCockpit: FATAL ERROR or EXCEPTION in DataController->usercount: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                ['app' => 'admincockpit']
            );
            return new DataResponse([
                'userCount' => -1,
                'groupCount' => -1,
            ], 500);
        }
    }   
    
    public function deleteuser($who) {
        try {
            if ($this->userManager->userExists($who)) { 
                 $user = $this->userManager->get($who);
                 if ($user->delete()) { return 'true'; }
                 else { return 'false'; }               
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
    
    public function edituser($who): DataResponse {
        try {
            $user =$this->userManager->get($who);
            $mids = $user->getManagerUids();
            if($mids) $mids = $mids[0];
            else $mids = "";
            $userList = [];
                $userList[] = [
                    'uid' => $who,
                    'displayname' => $user->getDisplayName(),
                    'email' => $user->getEMailAddress(),
                    'quota' => $user->getQuota(),
                    'managerids' => $mids,
                    'isadmin' => $this->groupManager->isAdmin($user->getUID()),
                    'groups' => $this->groupManager->getUserGroupIds($user),
                    'admingroups' => $this->myService->admingroup($who),
                    'lastlogin' => $user->getLastLogin(),
                    'firstlogin' => $user->getFirstLogin(),
                    'used' => $this->myService->folderSize($user->getHome()),
                    'status' => true,
                ];
            
            return new DataResponse([
                'user' => $userList,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error(
                'AdminCockpit: FATAL ERROR or EXCEPTION in DataController->edituser: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                ['app' => 'admincockpit']
            );
            return new DataResponse([
                'user' => -1,
            ], 500);
        }
    }
    
    public function saveuser($uid, $displayname, $password, $email, $groups, $admingroups, $quota, $managerids): JSONResponse {
        if($quota === $this->l->t('default quota')) $uquota = $this->appConfig->getValueString('files', 'default_quota', '1 GB', false);
        elseif($quota === $this->l->t('unlimited')) $uquota = "none";
        else $uquota = $quota;
        $user =$this->userManager->get($uid);
        $oldgroups = $this->groupManager->getUserGroupIds($user);
        $oldadmingroups = $this->myService->admingroup($uid);
        
        if ($user->getDisplayName() <> $displayname) $user->setDisplayName($displayname);
        if ($password) {
            if($user->setPassword($password, null)) $this->logger->error('AdminCockpit: Success in DataController->setPassword: ');
            else $this->logger->error('AdminCockpit: Fail in DataController->setPassword: ');
        }
        if ($user->getEMailAddress() <> $email) $user->setEMailAddress($email);
        if ($oldgroups <> $groups) {
                $missingElements = array_diff($oldgroups, $groups);
                $newElements = array_diff($groups, $oldgroups);
                foreach ($newElements as $x) {
                        $this->groupManager->get($x)->addUser($user);
                }
                foreach ($missingElements as $x) {
                        $this->groupManager->get($x)->removeUser($user);
                }            
        }
        if ($oldadmingroups <> $admingroups) {
                $missingElements = array_diff($oldadmingroups, $admingroups);
                $newElements = array_diff($admingroups, $oldadmingroups);
                foreach ($newElements as $x) {
                        $this->myService->addadmingroup($uid, $x);
                }
                foreach ($missingElements as $x) {
                        $this->myService->deleteadmingroup($uid, $x);
                }                
        }
        if ($user->getQuota() <> $quota) {
            $user->setQuota($uquota);
        }
        if ($user->getManagerUids() <> $managerids) {
            $usrmid = [];
            $usrmid[] = $managerids;
            $user->setManagerUids($usrmid);
        }
        return new JSONResponse([
         'uid' => $uid,
         'displayname' => $displayname,
         'password' => $password,
        'email' => $email,
        'groups' => $groups,
        'admingroups' => $admingroups,
        'quota' => $quota,
        'managerids' => $managerids,
        'status' => true,
		   ]);
        
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
    
    public function userexists($who) {
            if($this->userManager->get($who)) return true;
            else return false;
    }
    
    public function newuser($uid, $displayname, $password, $email, $groups, $admingroups, $quota, $managerids): DataResponse {
        try {
            $this->userManager->createUser($uid, $password);
            $this->saveuser($uid, $displayname, $password, $email, $groups, $admingroups, $quota, $managerids);
            $userList = [];
                $userList[] = [
                    'uid' => $uid,
                    'displayname' => '',
                    'email' => '',
                    'quota' => '',
                    'managerids' => '',
                    'isadmin' => '',
                ];
            
            return new DataResponse([
                'user' => $userList,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error(
                'AdminCockpit: FATAL ERROR or EXCEPTION in DataController->newuser: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                ['app' => 'admincockpit']
            );
            return new DataResponse([
                'user' => -1,
            ], 500);
        }
    }
    
    public function setuser($who) {
        return;
    }
  
}
