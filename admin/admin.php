<?php
// Compatibility route. The public control panel is outside this historically
// HTTP-Basic-protected directory so it can use the site's account login page.
header('Location: /control-panel.php', true, 302);
exit;
