<?php
/**
 *
 * LogCleaner APP (Nextcloud)
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

namespace OCA\AdminCockpit\Dashboard;

use OCA\AdminCockpit\AppInfo\Application;
use OCP\Dashboard\IWidget;
use OCP\Dashboard\IConditionalWidget;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Util;
use OCP\IUserSession;
use OCP\IGroupManager;

#[\AllowDynamicProperties]
class AdminCockpitWidget implements IWidget, IConditionalWidget
{
  public function __construct(private IL10N $l10n,
  private IURLGenerator $url,
  private IConfig $config,
  IUserSession $userSession,
  IGroupManager $groupManager,
) {
  $user = $userSession->getUser();
  $this->wtisadmin = $groupManager->isAdmin($user->getUID());
}

public function isEnabled(): bool {
  return $this->wtisadmin ? true : false;
}

/**
 * @inheritDoc
 */
public function getId(): string {
  return 'admincockpit-widget';
}

/**
 * @inheritDoc
 */
public function getTitle(): string {
  return $this->l10n->t('AdminCockpit');
}

/**
 * @inheritDoc
 */
public function getOrder(): int {
  return 10;
}

/**
 * @inheritDoc
 */
public function getIconClass(): string {
  return 'icon-admincockpit';
}

/**
 * @inheritDoc
 */
public function getUrl(): ?string {
  return null;
}

    public function load(): void
    {
        Util::addScript('admincockpit', 'admincockpit-widget');
        Util::addStyle('admincockpit', 'admincockpit-widget');
    }
}
