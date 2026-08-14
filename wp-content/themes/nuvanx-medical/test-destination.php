<?php
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');
$dest = nvx_seo_route_alias_destination('/medicina-estetica-goya/');
echo "DEST: ";
var_dump($dest);
