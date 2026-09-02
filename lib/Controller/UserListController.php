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

use OCA\AdminCockpit\Service\MyService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IL10N;
use OCP\IAppConfig;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use OCP\IDBConnection;

class UserListController extends Controller {

    private $groupManager;
    private $myService;
    private $logger;
    private IAppConfig $appConfig;
    private $userManager;
    private $l;
    private $userSession;
    private IDBConnection $dbConnection;

    public function __construct(
            string $appName,
            IRequest $request,
            MyService $myService,
            LoggerInterface $logger,
            IUserManager $userManager,
            IGroupManager $groupManager,
            IUserSession $userSession,
            IL10N $l,
            IDBConnection $dbConnection,
            IAppConfig $appConfig,
            private ContainerInterface $container,
    ) {
        parent::__construct($appName, $request);
        $this->groupManager = $groupManager;
        $this->myService = $myService;
        $this->logger = $logger;
        $this->appConfig = $appConfig;
        $this->userManager = $userManager;
        $this->userSession = $userSession;
        $this->l = $l;
        $this->dbConnection = $dbConnection;
    }

    public function navnu() {
        return $this->getUsers();
    }

    public function getUsers(string $groupId = ''): DataResponse {
        $search = $this->request->getParam('search', '');
        $statusParam = $this->request->getParam('status', 'all');

        $limit = $this->appConfig->getValueInt('admincockpit', 'admincockpit_user_per_page',12);
        $page = (int)$this->request->getParam('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        $offset = ($page - 1) * $limit;

        $allusers = $this->userManager->search($search);

        $usersall = [];
        $userOptions = [];

        foreach ($allusers as $user) {
            $uid = $user->getUID();
            $email = $user->getEMailAddress();
            $displayname = $user->getDisplayName();
            $ll = $user->getLastLogin();
            $fl = $user->getFirstLogin();
            $isEnabled = $user->isEnabled();
            $userstatus = 'offline';
            $isnouser = (!$ll || $ll === 0);

            if (!$isnouser) {
                foreach ($this->myService->queryStatusForUsers([$uid]) as $key => $value) {
                    $userstatus = $value->getStatus();
                }
            }

            $userItemOption = [
                'id' => $uid,
                'displayName' => $displayname,
                'isNoUser' => $isnouser,
                'user' => $uid,
                'subname' => $email,
                'preloadedUserStatus' => ['status' => $userstatus, 'message' => $userstatus],
            ];

            $userOptions[] = $userItemOption;

            $usersall[] = [
                'id' => $uid,
                'displayName' => $displayname,
                'email' => $email,
                'enabled' => $isEnabled,
                'lastlogin' => $ll,
                'firstlogin' => $fl,
                'cloudid' => $user->getCloudId(),
                'quota' => $user->getQuota(),
                'managerids' => $user->getManagerUids() ?: [null],
                'lastloginl10n' => $this->l->l('datetime', $ll),
                'firstloginl10n' => $this->l->l('datetime', $fl),
                'used' => $this->myService->folderSize($user->getHome()),
                'isadmin' => $this->groupManager->isAdmin($uid),
                'userOptions' => [$userItemOption],
            ];
        }

        $alluseroptions = $userOptions;

        $targetUsers = [];
        $translatedAllUsers = $this->l->t('all users');
        $isAllUsersGroup = ($groupId === '' || $groupId === $translatedAllUsers || $groupId === 'all users');

        if ($isAllUsersGroup) {
            $targetUsers = $usersall;
            $groupId = $translatedAllUsers;
            $groupDisplayName = $translatedAllUsers;
        } else {
            $group = $this->groupManager->get($groupId);
            if (!$group) {
                return new DataResponse(['error' => 'Group not found'], 404);
            }
            $groupDisplayName = $group->getDisplayName();

            $groupUserUids = [];
            foreach ($group->getUsers() as $gUser) {
                $groupUserUids[$gUser->getUID()] = true;
            }

            foreach ($usersall as $u) {
                if (isset($groupUserUids[$u['id']])) {
                    $targetUsers[] = $u;
                }
            }
        }

        if ($statusParam === 'active') {
            $targetUsers = array_values(array_filter($targetUsers, function ($u) {
                return $u['enabled'] === true;
            }));
        } elseif ($statusParam === 'inactive') {
            $targetUsers = array_values(array_filter($targetUsers, function ($u) {
                return $u['enabled'] === false;
            }));
        }

        $filteredTotal = count($targetUsers);

        if ($limit > 0) {
            $pagedUsers = array_slice($targetUsers, $offset, $limit);
        } else {
            $pagedUsers = $targetUsers;
        }

        return new DataResponse([
            'groupId' => $groupId,
            'groupDisplayName' => $groupDisplayName,
            'users' => $pagedUsers,
            'total' => $filteredTotal,
            'allusers' => $pagedUsers,
            'alluserstotal' => count($usersall),
            'alluseroptions' => $alluseroptions,
        ]);
    }

    private function transformUserData($user, array $userStatuses): array {
        $uid = $user->getUID();
        $email = $user->getEMailAddress();
        $displayname = $user->getDisplayName();
        $ll = $user->getLastLogin();
        $fl = $user->getFirstLogin();

        $isnouser = (!$ll || $ll === 0);
        $userstatus = $isnouser ? 'offline' : ($userStatuses[$uid] ?? 'offline');

        $userOption = [
            'id' => $uid,
            'displayName' => $displayname,
            'isNoUser' => $isnouser,
            'user' => $uid,
            'subname' => $email,
            'preloadedUserStatus' => ['status' => $userstatus, 'message' => $userstatus],
        ];

        $userData = [
            'id' => $uid,
            'displayName' => $displayname,
            'email' => $email,
            'enabled' => $user->isEnabled(),
            'lastlogin' => $ll,
            'firstlogin' => $fl,
            'cloudid' => $user->getCloudId(),
            'quota' => $user->getQuota(),
            'managerids' => $user->getManagerUids() ?: [null],
            'lastloginl10n' => $this->l->l('datetime', $ll),
            'firstloginl10n' => $this->l->l('datetime', $fl),
            'used' => $this->myService->folderSize($user->getHome()),
            'isadmin' => $this->groupManager->isAdmin($uid),
            'userOptions' => [$userOption],
        ];

        return [
            'user' => $userData,
            'userOption' => $userOption,
        ];
    }

    public function getFilteredUserIds(string $groupId, string $search, string $statusFilter, int $limit, int $offset): array {
        $decodedGroupId = urldecode(trim($groupId));
        $normalizedGroup = mb_strtolower($decodedGroupId);
        $translatedAll = mb_strtolower($this->l->t('all users'));

        $isAllUsersGroup = (
            $normalizedGroup === '' ||
            $normalizedGroup === 'all' ||
            $normalizedGroup === 'all users' ||
            $normalizedGroup === $translatedAll
        );

        $normalizedStatus = mb_strtolower(trim($statusFilter));
        $cleanSearch = trim($search);

        $sql = "SELECT DISTINCT u.uid FROM *PREFIX*users u";
        $params = [];

        if (!$isAllUsersGroup) {
            $sql .= " INNER JOIN *PREFIX*group_user gu ON u.uid = gu.uid AND gu.gid = ?";
            $params[] = $decodedGroupId;
        }

        if ($cleanSearch !== '') {
            $sql .= " LEFT JOIN *PREFIX*accounts_data ad ON u.uid = ad.uid AND ad.name = 'displayname'";
        }

        if ($normalizedStatus === 'active' || $normalizedStatus === 'inactive') {
            $sql .= " LEFT JOIN *PREFIX*preferences p ON u.uid = p.userid AND p.appid = 'core' AND p.configkey = 'enabled'";
        }

        $whereConditions = [];

        if ($cleanSearch !== '') {
            $whereConditions[] = "(u.uid LIKE ? OR ad.value LIKE ?)";
            $params[] = '%' . $cleanSearch . '%';
            $params[] = '%' . $cleanSearch . '%';
        }

        if ($normalizedStatus === 'inactive') {
            $whereConditions[] = "p.configvalue = 'false'";
        } elseif ($normalizedStatus === 'active') {
            $whereConditions[] = "(p.configvalue IS NULL OR p.configvalue != 'false')";
        }

        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }

        $sql .= " ORDER BY u.uid ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->dbConnection->prepare($sql);
        $result = $stmt->execute($params);

        $uids = [];
        while ($row = $result->fetch()) {
            $uids[] = $row['uid'];
        }
        $stmt->closeCursor();

        return $uids;
    }

    public function getFilteredUserCount(string $groupId, string $search, string $statusFilter): int {
        $decodedGroupId = urldecode(trim($groupId));
        $normalizedGroup = mb_strtolower($decodedGroupId);
        $translatedAll = mb_strtolower($this->l->t('all users'));

        $isAllUsersGroup = (
            $normalizedGroup === '' ||
            $normalizedGroup === 'all' ||
            $normalizedGroup === 'all users' ||
            $normalizedGroup === $translatedAll
        );

        $normalizedStatus = mb_strtolower(trim($statusFilter));
        $cleanSearch = trim($search);

        $sql = "SELECT COUNT(DISTINCT u.uid) AS count_users FROM *PREFIX*users u";
        $params = [];

        if (!$isAllUsersGroup) {
            $sql .= " INNER JOIN *PREFIX*group_user gu ON u.uid = gu.uid AND gu.gid = ?";
            $params[] = $decodedGroupId;
        }

        if ($cleanSearch !== '') {
            $sql .= " LEFT JOIN *PREFIX*accounts_data ad ON u.uid = ad.uid AND ad.name = 'displayname'";
        }

        if ($normalizedStatus === 'active' || $normalizedStatus === 'inactive') {
            $sql .= " LEFT JOIN *PREFIX*preferences p ON u.uid = p.userid AND p.appid = 'core' AND p.configkey = 'enabled'";
        }

        $whereConditions = [];

        if ($cleanSearch !== '') {
            $whereConditions[] = "(u.uid LIKE ? OR ad.value LIKE ?)";
            $params[] = '%' . $cleanSearch . '%';
            $params[] = '%' . $cleanSearch . '%';
        }

        if ($normalizedStatus === 'inactive') {
            $whereConditions[] = "p.configvalue = 'false'";
        } elseif ($normalizedStatus === 'active') {
            $whereConditions[] = "(p.configvalue IS NULL OR p.configvalue != 'false')";
        }

        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }

        $stmt = $this->dbConnection->prepare($sql);
        $result = $stmt->execute($params);
        $row = $result->fetch();
        $stmt->closeCursor();

        return (int)($row['count_users'] ?? 0);
    }

    public function updateUser(string $userId, bool $enabled): DataResponse {

        return new DataResponse(['status' => 'success', 'userId' => $userId]);
    }
}
