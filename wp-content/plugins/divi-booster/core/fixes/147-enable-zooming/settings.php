<?php 
if (!defined('ABSPATH')) { exit(); } // No direct access

function db147_add_setting($plugin) {  
	$plugin->setting_start(); 
	$plugin->techlink('https://divibooster.com/enable-mobile-pinch-zooming-in-divi/'); 
	$plugin->checkbox(__FILE__); ?> Enable zooming<?php
	$plugin->setting_end(); 
} 
$wtfdivi->add_setting('general-accessibility', 'db147_add_setting');

