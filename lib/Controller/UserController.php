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
use OCP\IUserSession;
use OCP\IUserManager;
use OCP\IGroupManager;
use Psr\Container\ContainerInterface;


class UserController extends Controller {
    private $myService;
    private $logger;
    private $config;
    private $userManager;
    private $groupManager;
    private $l;
    private IAppManager $appManager;
    private IUserSession $userSession;

    public function __construct(
            string $appName,
            IRequest $request,
            MyService $myService,
            LoggerInterface $logger,
            IConfig $config,
            IAppManager $appManager,
            IUserManager $userManager,
            IGroupManager $groupManager,
            IUserSession $userSession,
            IL10N $l,
            private IAppConfig $appConfig,
            private ContainerInterface $container,
        ) {
        parent::__construct($appName, $request);
        $this->myService = $myService;
        $this->logger = $logger;
        $this->config = $config;
        $this->appManager = $appManager;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->userSession = $userSession;
        $this->l = $l;
    }

    public function usercount(): DataResponse {
        try {
            $users = $this->userManager->search('');
            $userList = [];
            $usrlist = [];
            $usrdisplayandid = [];
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
            }

            $groups = $this->groupManager->search('');
            $groupList = [];
            $grlist = [];
            $grdisplaynamelist = [];
            $grpdisplayandid = [];
            foreach ($groups as $group) {
                $gusers = $group->getUsers();
                $guserList = [];
                $grlist[] = $group->getGID();
                $grdisplaynamelist[] = $group->getDisplayName();

                $grpdisplayandid[] = [
                    'id'            => $group->getGID(),
                    'displayname'   => $group->getDisplayName(),
                ];




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
                    'gdisplayname' => $group->getDisplayName(),
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
                'grdisplaynamelist' => $grdisplaynamelist,
                'usrdisplayandid' => $usrdisplayandid,
                'displayandid' => $grpdisplayandid,
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

    public function deleteuser($who): DataResponse {
        try {
            if ($this->userManager->userExists($who)) {
                 $user = $this->userManager->get($who);
                 if ($user->delete()) {
                     $this->logger->info("AdminCockpit: User $who successful deleted");
                     return new DataResponse('true');
                }
                 else { return new DataResponse('false'); }
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

    public function edituser($who): DataResponse {
        try {
            $user =$this->userManager->get($who);
            $mids = $user->getManagerUids();
            $usergrps = $this->groupManager->getUserGroups($user);
            $admingrps = $this->myService->admingroup($who);
            $groupdisplaynames = [];
            foreach ($usergrps as $usergrp) {
                $groupdisplaynames[] = [
                    'uid' => $usergrp->getGID(),
                    'displayname' => $usergrp->getDisplayName(),
                    ];
            }

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
                    'groupdisplaynamesandids' => $groupdisplaynames,
                    'admingroups' => $admingrps,
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
        elseif($quota === '') $uquota = $this->appConfig->getValueString('files', 'default_quota', '1 GB', false);
        else $uquota = $quota;
        $user =$this->userManager->get($uid);
        $oldgroups = $this->groupManager->getUserGroupIds($user);
        $oldadmingroups = $this->myService->admingroup($uid);

        if ($user->getDisplayName() <> $displayname) $user->setDisplayName($displayname);
        if ($password) {
            if($user->setPassword($password, null)) $this->logger->info('AdminCockpit: Success in DataController->setPassword: ');
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

    public function userexists($who): DataResponse {
            if($this->userManager->get($who)) return new DataResponse(true);
            else return new DataResponse(false);
    }

    public function newuser($uid, $displayname, $password, $email, $groups, $admingroups, $quota, $managerids): DataResponse {
        $ncinfo = $this->myService->getNCInfo();
        $parts = explode(".", $ncinfo['nc_version']);
        $version = (int)$parts[0];
        if($version < 32) {
            $enabledapps = $this->appManager->getEnabledAppsForUser($this->userSession->getUser());
        }
        else {
            $enabledapps = $this->appManager->getEnabledApps();
        }
            if (in_array('password_policy', $enabledapps)) {
                $class = 'OCA\\Password_Policy\\Controller\\APIController';
                if (class_exists($class)) {
                    $apiController = $this->container->query($class);
                    $response = $apiController->validate($password, 'account');
                    $data = $response->getData();
                    $passed = $data['passed'];
                    $reason = $data['reason'] ?? null;
                    if (!$passed) {
                        return new DataResponse([
                            'user' => -1,
                            'success' => $passed,
                            'msg' => $reason,
                        ]);
                    }
                }
            }

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

                $this->logger->info("AdminCockpit: User $uid successful created");

            return new DataResponse([
                'user' => $userList,
                'success' => true,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('AdminCockpit: ' . $e->getMessage());
            return new DataResponse([
                'user' => -1,
                'success' => false,
                'msg' => $e->getMessage(),
            ]);
        }
    }

    public function setuser($who) {
        return;
    }

   #[NoCSRFRequired]
    public function notifyuser(): DataResponse {
$rawData = file_get_contents('php://input');

$data = json_decode($rawData, true);
if (json_last_error() === JSON_ERROR_NONE) {
    $message = $data['what'] ?? '';
    $who = $data['who'] ?? '';
        $para = [
            'message' => $message,
            'von' => $this->userSession->getUser()->getUID(),
        ];
        $nmanager = \OCP\Server::get(\OCP\Notification\IManager::class);
        $notification = $nmanager->createNotification();

        $notification->setApp('admincockpit')
            ->setUser($who)
            ->setDateTime(new \DateTime())
            ->setObject('remote', '2311')
            ->setSubject('abc', $para)
        ;
        $nmanager->notify($notification);
        return new DataResponse('true');
        } else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
}

    }

    #[NoCSRFRequired]
    public function notifygroup(): DataResponse {
$rawData = file_get_contents('php://input');

$data = json_decode($rawData, true);
if (json_last_error() === JSON_ERROR_NONE) {
    $message = $data['what'] ?? '';
    $who = $data['who'] ?? '';
        $para = [
            'message' => $message,
            'von' => $this->userSession->getUser()->getUID(),
        ];
        $group = $this->groupManager->get($who);
        $groupusers = $group->getUsers();
        $nmanager = \OCP\Server::get(\OCP\Notification\IManager::class);
        foreach ($groupusers as $groupuser) {
            $notification = $nmanager->createNotification();
            $notification->setApp('admincockpit')
            ->setUser($groupuser->getUID())
            ->setDateTime(new \DateTime())
            ->setObject('remote', '2311')
            ->setSubject('abc', $para)
        ;
        $nmanager->notify($notification);
        $notification = $nmanager->createNotification();
        }
        return new DataResponse('true');
        } else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
}

    }

}
