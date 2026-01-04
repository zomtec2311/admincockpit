<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\AdminCockpit\AppInfo\Application::APP_ID, 'admincockpit-home');
Util::addStyle(OCA\AdminCockpit\AppInfo\Application::APP_ID, 'admincockpit-main');
?>

<div id="admincockpit"></div>
