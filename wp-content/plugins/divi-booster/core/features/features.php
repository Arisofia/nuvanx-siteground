<?php

include_once(dirname(__FILE__).'/dbdb-posttitle-tags/dbdb-posttitle-tags.php');
include_once(dirname(__FILE__).'/socialmediafollownetworks/socialmediafollownetworks.php');
include_once(dirname(__FILE__).'/contactFormEmailBlacklist/dbdb-contactform-emailblacklist.php');

if (version_compare(phpversion(), '5.3', '>=')) {
    include_once(dirname(__FILE__).'/dbdb-blogmodule-tags/dbdb-blogmodule-tags.php');
    include_once(dirname(__FILE__).'/gallery-order/gallery-order.php');
}