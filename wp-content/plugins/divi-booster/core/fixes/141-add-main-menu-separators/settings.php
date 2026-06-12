<?php 
if (!defined('ABSPATH')) { exit(); } // No direct access

function db141_add_setting($plugin) { 
	$plugin->setting_start();  
	$plugin->techlink('https://divibooster.com/add-a-separator-between-the-divi-primary-menu-items/'); 
	$plugin->checkbox(__FILE__); ?> Add vertical separator bars between menu items<?php
	$plugin->setting_end(); 
} 
$wtfdivi->add_setting('header-main', 'db141_add_setting');	