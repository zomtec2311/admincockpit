<?php

declare(strict_types=1);

namespace OCA\AdminCockpit\Controller;

use OCA\AdminCockpit\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IRequest;
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
	
	public function __construct(string $appName, IRequest $request, IUserManager $userManager, MyService $myService, UserController $userController, IL10N $l,) {
        parent::__construct($appName, $request);
        $this->userManager = $userManager;
        $this->myService = $myService;
		$this->userController = $userController;
		$this->l = $l;
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
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/')]
	public function apps(): TemplateResponse {
		return new TemplateResponse(
			Application::APP_ID,
			'apps',
		);
	}
	
	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/')]
	public function system(): TemplateResponse {
		return new TemplateResponse(
			Application::APP_ID,
			'system',
		);
	}
	
	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/')]
	public function user(): TemplateResponse {
		return new TemplateResponse(
			Application::APP_ID,
			'user',
		);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/')]
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
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'POST', url: '/')]
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
}
