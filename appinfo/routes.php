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

return [
  'routes' => [
    ['name' => 'Page#index', 'url' => '/', 'verb' => 'GET'],
    ['name' => 'Page#apps', 'url' => '/apps', 'verb' => 'GET'],
    ['name' => 'Page#system', 'url' => '/system', 'verb' => 'GET'],
    ['name' => 'Page#user', 'url' => '/user', 'verb' => 'GET'],
    ['name' => 'Page#userlist', 'url' => '/userlist', 'verb' => 'POST'],
    ['name' => 'Page#userlistget', 'url' => '/userlist', 'verb' => 'GET'],
    ['name' => 'Page#allusers', 'url' => '/allusers', 'verb' => 'POST'],
    ['name' => 'Page#listuser', 'url' => '/listuser', 'verb' => 'GET'],
    ['name' => 'Apps#listCategories', 'url' => '/appsasc', 'verb' => 'GET'],
    ['name' => 'Apps#appsinfo', 'url' => '/appsinfo', 'verb' => 'GET'],
    ['name' => 'Apps#isnoti', 'url' => '/isnoti', 'verb' => 'GET'],
    ['name' => 'Apps#islogcleaner', 'url' => '/islogcleaner', 'verb' => 'GET'],
    ['name' => 'Apps#enableapp', 'url' => '/enableapp/{who}', 'verb' => 'GET'],
    ['name' => 'Apps#disableapp', 'url' => '/disableapp/{who}', 'verb' => 'GET'],
    ['name' => 'Apps#getAppsWithUpdates', 'url' => '/appupdates', 'verb' => 'GET'],
    ['name' => 'Apps#updateapp', 'url' => '/updateapp/{who}', 'verb' => 'GET'],
    ['name' => 'Group#addgroup', 'url' => '/addgroup/{who}', 'verb' => 'GET'],
    ['name' => 'Group#renamegroup', 'url' => '/renamegroup', 'verb' => 'POST'],
    ['name' => 'Group#deletegroup', 'url' => '/deletegroup/{who}', 'verb' => 'GET'],
    ['name' => 'Group#groupdata', 'url' => '/groupdata', 'verb' => 'GET'],
    ['name' => 'Settings#getparams', 'url' => '/getparams', 'verb' => 'GET'],
    ['name' => 'System#storage', 'url' => '/storage', 'verb' => 'GET'],
    ['name' => 'Settings#setparam', 'url' => '/setparam/{who}/{what}', 'verb' => 'GET'],
    ['name' => 'System#storage', 'url' => '/storage', 'verb' => 'GET'],
    ['name' => 'System#sqlinfo', 'url' => '/sqlinfo', 'verb' => 'GET'],
    ['name' => 'System#systeminfo', 'url' => '/systeminfo', 'verb' => 'GET'],
    ['name' => 'System#widgetinfo', 'url' => '/widgetinfo', 'verb' => 'GET'],
    ['name' => 'User#deleteuser', 'url' => '/deleteuser/{who}', 'verb' => 'GET'],
    ['name' => 'User#edituser', 'url' => '/edituser/{who}', 'verb' => 'GET'],
    ['name' => 'User#newuser', 'url' => '/newuser', 'verb' => 'POST'],
    ['name' => 'User#saveuser', 'url' => '/saveuser', 'verb' => 'POST'],
    ['name' => 'User#userexists', 'url' => '/userexists/{who}', 'verb' => 'GET'],
    ['name' => 'User#notifyuser', 'url' => '/notifyuser', 'verb' => 'POST'],
    ['name' => 'User#notifygroup', 'url' => '/notifygroup', 'verb' => 'POST'],
    ['name' => 'User#usercount', 'url' => '/usercount', 'verb' => 'GET'],
    ['name' => 'UserList#getUsers', 'url' => '/api/v1/groups/{groupId}/users', 'verb' => 'GET'],
    ['name' => 'UserList#navnu', 'url' => '/api/v1/nav/nu', 'verb' => 'GET'],
    ['name' => 'UserList#updateUser', 'url' => '/api/v1/users/{userId}', 'verb' => 'PUT'],
    ['name' => 'User#setEnabled', 'url' => '/api/v1/users/{id}/status', 'verb' => 'PUT'],
  ]
];
