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

use OCP\IRequest;
use OCP\IUserManager;
use OCA\AdminCockpit\Service\MyService;
use OCP\AppFramework\Http\DataResponse;
use Psr\Log\LoggerInterface;
/**
 * @psalm-suppress UnusedClass
 */
class PageController extends Controller {
	private $userManager;
    private $myService;
	
	public function __construct(string $appName, IRequest $request, IUserManager $userManager, MyService $myService) {
        parent::__construct($appName, $request);
        $this->userManager = $userManager;
        $this->myService = $myService;
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
}
