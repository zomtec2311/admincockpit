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
namespace OCA\AdminCockpit\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IAppConfig;
use OCP\AppFramework\Http\DataResponse;
use Psr\Log\LoggerInterface;
use OCP\App\IAppManager;

class SettingsController extends Controller {
	private $config;
	private $l;
	public function __construct(
		IL10N $l,
		IConfig $config,
		IRequest $request,
		private Helper $helper,
		private readonly LoggerInterface $logger,
		private IAppManager $appManager,
		private IAppConfig $appConfig,
	) {
		parent::__construct('logcleaner', $request);
		$this->l = $l;
		$this->config = $config;
		$this->helper = $helper;
		$this->appManager = $appManager;
	}

	public function getparams(): DataResponse {

		return new DataResponse([
			'admincockpit_user_per_page' => $this->appConfig->getValueInt('admincockpit', 'admincockpit_user_per_page',12),
			'admincockpit_groups_at_start' => $this->appConfig->getValueInt('admincockpit', 'admincockpit_groups_at_start',1),
			'admincockpit_personal_settings_at_start' => $this->appConfig->getValueInt('admincockpit', 'admincockpit_personal_settings_at_start',1),
			'admincockpit_administration_settings_at_start' => $this->appConfig->getValueInt('admincockpit', 'admincockpit_administration_settings_at_start',1),
		]);
	}

	public function setparam($who, $what): DataResponse {
		$what = intval($what);
       if ((int)$this->appConfig->setValueInt('admincockpit', $who, $what));
			return new DataResponse([
            ]);
	}
}
