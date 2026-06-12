<?php 
if (!defined('ABSPATH')) { exit(); } // No direct access

function db148_add_setting($plugin) { 
	$plugin->setting_start(); 
	$plugin->checkbox(__FILE__); ?> Set footer menu alignment
	<?php
	$options = array(
		'left' => 'Left',
		'center' => 'Center',
		'right' => 'Right'
	);
	$selected = dbdb_option('148-footer-menu-alignment', 'align', 'left');
	$plugin->selectpicker(__FILE__, 'align', $options, $selected);
	$plugin->setting_end(); 
} 
$wtfdivi->add_setting('footer-menu', 'db148_add_setting');	