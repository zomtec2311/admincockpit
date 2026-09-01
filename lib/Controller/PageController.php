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

use OCA\AdminCockpit\AppInfo\Application;
use OCP\IInitialStateService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Util;
use OCP\IUserManager;
use OCA\AdminCockpit\Service\MyService;
use OCA\AdminCockpit\Controller\UserController;
use OCP\AppFramework\Http\DataResponse;
use Psr\Log\LoggerInterface;
/**
 * @psalm-suppress UnusedClass
 */
class PageController extends Controller {
	private $userManager;
    private $myService;
	private $userController;
	private $l;
	private IInitialStateService $initialStateService;

	public function __construct(string $appName, IRequest $request, IUserManager $userManager, MyService $myService, UserController $userController, IL10N $l, IInitialStateService $initialStateService) {
        parent::__construct($appName, $request);
        $this->userManager = $userManager;
        $this->myService = $myService;
		$this->userController = $userController;
		$this->l = $l;
		$this->initialStateService = $initialStateService;
    }

	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/')]
	public function index(): TemplateResponse {
		return new TemplateResponse(
			Application::APP_ID,
			'index',
		);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function apps(): TemplateResponse {
		return new TemplateResponse(
			Application::APP_ID,
			'apps',
		);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function system(): TemplateResponse {
		return new TemplateResponse(
			Application::APP_ID,
			'system',
		);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function user(): TemplateResponse {
		return new TemplateResponse(
			Application::APP_ID,
			'user',
		);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function userlistget(string $who = '', string $guser = '', string $gid = ''): TemplateResponse {
		if (empty($guser)) {
        $response = $this->userController->usercount();
        $data = $response->getData();
        $guser = json_encode($data['users']);
    }
		return $this->userlist($this->l->t('all users'), $guser, $this->l->t('all users'));
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function userlist(string $who = '', string $guser = '', string $gid = ''): TemplateResponse {
		return new TemplateResponse(
			Application::APP_ID,
			'userlist',
			[
				'who'   => $who,
				'guser' => $guser,
				'gid'   => $gid,
			]
		);
	}

	#[NoCSRFRequired]
    #[NoAdminRequired]
    public function allusers(): TemplateResponse {
        Util::addScript(Application::APP_ID, 'admincockpit-allusers');
        Util::addStyle(Application::APP_ID, 'admincockpit-main');

        $who = $this->request->getParam('who', 'all users');
        $gid = $this->request->getParam('gid', 'all users');

        $this->initialStateService->provideInitialState(
            Application::APP_ID,
            'who',
            $who
        );
        $this->initialStateService->provideInitialState(
            Application::APP_ID,
            'gid',
            $gid
        );

        return new TemplateResponse(
            Application::APP_ID,
            'allusers',
            []
        );
    }


	#[NoCSRFRequired]
    #[NoAdminRequired]
    public function listuser(): TemplateResponse {
        Util::addScript(Application::APP_ID, 'admincockpit-allusers');
        Util::addStyle(Application::APP_ID, 'admincockpit-main');

        $who = $this->request->getParam('who', $this->l->t('all users'));
        $gid = $this->request->getParam('gid', 'all users');
        $this->initialStateService->provideInitialState(
            Application::APP_ID,
            'who',
            $who
        );
        $this->initialStateService->provideInitialState(
            Application::APP_ID,
            'gid',
            $gid
        );

        return new TemplateResponse(
            Application::APP_ID,
            'allusers',
            []
        );
    }
}

