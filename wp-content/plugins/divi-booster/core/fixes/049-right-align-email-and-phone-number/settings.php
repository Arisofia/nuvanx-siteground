<?php 
if (!defined('ABSPATH')) { exit(); } // No direct access

function db049_add_setting($plugin) {  
	$plugin->setting_start(); 
	$plugin->techlink('https://divibooster.com/right-align-the-divi-top-header-icons/'); 
	$plugin->checkbox(__FILE__); ?> Move all header elements (phone, email, etc.) to right<?php
	$plugin->setting_end(); 
} 
$wtfdivi->add_setting('header-top', 'db049_add_setting');