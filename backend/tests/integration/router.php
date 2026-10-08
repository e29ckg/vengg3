<?php
$root=dirname(__DIR__,3);
if (parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)==='/install.php') { require $root.'/install.php'; return; }
chdir($root.'/backend/public');
require $root.'/backend/public/index.php';
