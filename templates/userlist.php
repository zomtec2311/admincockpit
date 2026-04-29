<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\AdminCockpit\AppInfo\Application::APP_ID, 'admincockpit-user');
Util::addStyle(OCA\AdminCockpit\AppInfo\Application::APP_ID, 'admincockpit-main');
?>

<div id="admin-cockpit-setup"
     data-who="<?php p($_['who']); ?>"
     data-gid="<?php p($_['gid']); ?>"
     data-guser="<?php p($_['guser']); ?>">
</div>

<div id="admincockpit"></div>
