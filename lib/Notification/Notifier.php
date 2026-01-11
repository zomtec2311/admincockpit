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

namespace OCA\AdminCockpit\Notification;

use OCA\AdminCockpit\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

class Notifier implements INotifier {
	private IFactory $factory;
	private IURLGenerator $url;

	public function __construct(\OCP\L10N\IFactory $factory,
								\OCP\IURLGenerator $urlGenerator) {
		$this->factory = $factory;
		$this->url = $urlGenerator;
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 * @return string
	 */
	public function getID(): string {
		return 'admincockpit';
	}

	/**
	 * Human-readable name describing the notifier
	 * @return string
	 */
	public function getName(): string {
		return $this->factory->get('admincockpit')->t('admincockpit');
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== 'admincockpit') {
			// Not my app => throw
			throw new \OCP\Notification\UnknownNotificationException();
		}

		// Read the language from the notification
		$l = $this->factory->get('admincockpit', $languageCode);
        
        switch ($notification->getSubject()) {
			case 'abc':
				$parameters = $notification->getSubjectParameters();
				$message = $parameters['message'];
                $von = $parameters['von'];
                $notification->setParsedSubject('Nachricht von ' . $von)
					->setIcon($this->url->getAbsoluteURL($this->url->imagePath('admincockpit', 'app-dark.svg')))
                    ->setParsedMessage($message);

				$action = $notification->createAction();
				$action->setParsedLabel($l->t('Read more'))
					->setPrimary(true);
				$notification->addParsedAction($action);

				return $notification;

			default:
				throw new UnknownNotificationException();
		}
	}

	/**
	 * This is a little helper function which automatically sets the simple parsed subject
	 * based on the rich subject you set. This is also the default behaviour of the API
	 * since Nextcloud 26, but in case you would like to return simpler or other strings,
	 * this function allows you to take over.
	 *
	 * @param INotification $notification
	 */
	protected function setParsedSubjectFromRichSubject(INotification $notification): void {
		$placeholders = $replacements = [];
		foreach ($notification->getRichSubjectParameters() as $placeholder => $parameter) {
			$placeholders[] = '{' . $placeholder . '}';
			if ($parameter['type'] === 'file') {
				$replacements[] = $parameter['path'];
			} else {
				$replacements[] = $parameter['name'];
			}
		}

		$notification->setParsedSubject(str_replace($placeholders, $replacements, $notification->getRichSubject()));
	}
}
