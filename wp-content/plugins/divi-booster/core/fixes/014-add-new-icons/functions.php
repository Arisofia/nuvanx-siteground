<?php 
if (!defined('ABSPATH')) { exit(); } // No direct access

add_action('init', 'db014_register_icons');
do_action('dbdb_014-add-new-icons_after');
add_action('wp_head', 'db014_shared_css');
add_action('db_head_js', 'db014_sharedUserJs');
add_action('db_vb_jquery_ready', 'db014_getMutationObserverJs');


// Upgrade old (pre-Divi 4.13) icons

add_filter('the_content', 'db014_migrate_icons_in_content');
add_filter('content_edit_pre', 'db014_migrate_icons_in_content');
add_filter('et_fb_load_raw_post_content', 'db014_migrate_icons_in_content');
add_filter('db_filter_et_pb_layout', 'db014_migrate_icons_in_content');

function db014_migrate_icons_in_content($content) {
    if (function_exists('et_pb_get_all_font_icon_option_names_string')) { 
        $regex = '/(' . et_pb_get_all_font_icon_option_names_string() . ')\=\"%%([^"]*)%%\"/mi';
        $content = preg_replace_callback($regex, 'db014_migrate_icons_callback', $content);
    }
    return $content;
}

function db014_migrate_icons_callback($matches) {
    if (isset($matches[2]) && intval($matches[2]) >= 380) {
        return $matches[1].'="&#x'.esc_attr(intval($matches[2])-380+800).';||divi||400"';
    }
    return $matches[0];
}

// End: Upgrade old icons

function db014_register_icons() {
	if (!class_exists('DBDBExtendedIcon')) { return; }
	foreach(db014_get_icon_urls() as $id=>$url) {
		if (!empty($url)) {
            $icon = (new DBDBExtendedIcon($id, $url));
            $icon->init();
		}
	}
}

function db014_get_icon_urls() {
	$urls = array();
	$urlmax = dbdb_option('014-add-new-icons', 'urlmax', 0);
	for($i=0; $i<=$urlmax; $i++) {
		$urls[$i] = dbdb_option('014-add-new-icons', "url$i", '');
	}
	return $urls;
}

function db014_shared_css() { ?>
<style>	
/* Custom icons */		
.et-pb-icon.db-custom-icon {
    line-height: unset;
}
.db-custom-icon img { 
    height: 1em;
}
.et_pb_blurb_position_left .db-custom-icon, 
.et_pb_blurb_position_right .db-custom-icon {
    width: 1em;
    display: block;
}
.et_pb_blurb_position_left .dbdb-custom-icon-img,
.et_pb_blurb_position_right .dbdb-custom-icon-img {
    height: auto;    
    vertical-align: top;
}
/* Custom button icons */
.et_pb_custom_button_icon[data-icon^="wtfdivi014-url"]:before, 
.et_pb_custom_button_icon[data-icon^="wtfdivi014-url"]:after,
.db-custom-extended-icon:before,
.db-custom-extended-icon:after {
    background-size: auto 1em;
    background-repeat: no-repeat;
    min-width: 20em;
    height: 100%;
    content: "" !important;
    background-position: left center;
    position: absolute;
    top: 0;
}
.et_pb_custom_button_icon[data-icon^="wtfdivi014-url"],
.db-custom-extended-icon { 
    overflow: hidden;
}
/* Inline icons */
.et_pb_posts .et_pb_inline_icon[data-icon^="wtfdivi014-url"]:before,
.et_pb_portfolio_item .et_pb_inline_icon[data-icon^="wtfdivi014-url"]:before {
    content: '' !important;
    -webkit-transition: all 0.4s;
    -moz-transition: all 0.4s;
    transition: all 0.4s;
}
.et_pb_posts .entry-featured-image-url:hover .et_pb_inline_icon[data-icon^="wtfdivi014-url"] img,
.et_pb_portfolio_item .et_portfolio_image:hover .et_pb_inline_icon[data-icon^="wtfdivi014-url"] img { 
    margin-top:0px; transition: all 0.4s;
}
.et_pb_posts .entry-featured-image-url .et_pb_inline_icon[data-icon^="wtfdivi014-url"] img, 
.et_pb_portfolio_item .et_portfolio_image .et_pb_inline_icon[data-icon^="wtfdivi014-url"] img { 
    margin-top: 14px; 
}
/* Custom hover icons */
.db014_custom_hover_icon { 
    width:auto !important; 
    max-width:32px !important; 
    min-width:0 !important;
    height:auto !important; 
    max-height:32px !important; 
    min-height:0 !important;
    position:absolute;
    top:50%;
    left:50%;
    -webkit-transform: translate(-50%,-50%); 
    -moz-transform: translate(-50%,-50%); 
    -ms-transform: translate(-50%,-50%); 
    transform: translate(-50%,-50%); 
}
.et_pb_dmb_breadcrumbs a:first-child .db014_custom_hover_icon,
.et_pb_dmb_breadcrumbs li .db014_custom_hover_icon {
    position: relative !important;
    left: 0%; 
    transform: none;
    vertical-align: middle;
    margin-right: 8px;
}
.et_pb_dmb_breadcrumbs li .db014_custom_hover_icon {
    margin-left: 4px;
}
.et_pb_fullwidth_portfolio .et_overlay .db014_custom_hover_icon {
    top: 45%;
    -webkit-transition: all .3s;
    transition: all .3s;
}
.et_pb_fullwidth_portfolio .et_pb_portfolio_image:hover .et_overlay .db014_custom_hover_icon {
    top:33%;
}
/* Hide extra icons */
.et_pb_gallery .et_pb_gallery_image .et_pb_inline_icon[data-icon^="wtfdivi014-url"]:before,
.et_pb_blog_grid .et_pb_inline_icon[data-icon^="wtfdivi014-url"]:before,
.et_pb_image .et_pb_image_wrap .et_pb_inline_icon[data-icon^="wtfdivi014-url"]:before,
.et_pb_dmb_breadcrumbs ol > li > a:first-child[data-icon^="wtfdivi014-url"]:before,
.et_pb_dmb_breadcrumbs ol > li[data-icon^="wtfdivi014-url"]:before,
.et_pb_module.et_pb_dmb_breadcrumbs li.db014_breadcrumb_with_custom_icon:before
{ 
    display:none !important; 
}
span.db-custom-icon {
    color: rgba(0,0,0,0) !important;
}
</style>
<?php
}


function db014_sharedUserJs() {
    echo <<<'END'

    function db014_update_icon(icon_id, icon_url) {
        db014_update_icons(jQuery(document), icon_id, icon_url);
        var $app_frame = jQuery("#et-fb-app-frame");
        if ($app_frame) {
            db014_update_icons($app_frame.contents(), icon_id, icon_url);
        }
    }
    
    function db014_update_icons(doc, icon_id, icon_url) { 
        db014_update_custom_icons(doc, icon_id, icon_url);
        db014_update_custom_inline_icons(doc, icon_id, icon_url);
    }

    function db014_update_custom_icons(doc, icon_id, icon_url) {
        var $custom_icons = doc.find('.et-pb-icon:contains("'+icon_id+'")');	
        var icon_visible = (icon_url !== '');
        var $icons = $custom_icons.filter(function(){ return jQuery(this).text() == icon_id; }); 
        $icons.addClass('db-custom-icon');
        $icons.html('<img class="dbdb-custom-icon-img" src="'+icon_url+'"/>');
        $icons.toggle(icon_visible); 
    }
    
    function db014_update_custom_inline_icons(doc, icon_id, icon_url) {
        var $custom_inline_icons = doc.find('.et_pb_inline_icon[data-icon="'+icon_id+'"]');
        var icon_visible = (icon_url !== '');
        var $icons_inline = $custom_inline_icons.filter(function(){ return jQuery(this).attr('data-icon') == icon_id; });
        $icons_inline.addClass('db-custom-icon');
        $icons_inline.each(function(){
            if (jQuery(this).children('.db014_custom_hover_icon').length === 0) {
                if (jQuery(this).closest('.et_pb_dmb_breadcrumbs').length === 0) {
                    jQuery(this).html('<img class="db014_custom_hover_icon"/>');
                } else {
                    jQuery(this).prepend(jQuery('<img class="db014_custom_hover_icon"/>'));
                    jQuery(this).addClass('db014_breadcrumb_with_custom_icon');
                }
            }
            jQuery(this).children('.db014_custom_hover_icon').attr('src', icon_url);
        });
        $icons_inline.toggle(icon_visible);
    }
END;
}

function db014_getMutationObserverJs() { 
    echo <<<'END'
    db014_watch_for_changes_that_might_update_icons();
    
    function db014_watch_for_changes_that_might_update_icons() {
        var target = document.getElementById('et-fb-app'); 
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'characterData') {
                    $(document).trigger('db_vb_custom_icons_updated');
                } else if (mutation.type === 'childList') {
                    if (db014_may_contain_icons(mutation.target)) {
                        $(document).trigger('db_vb_custom_icons_updated');
                    }
                } else if (mutation.type === 'attributes') {
                    $(document).trigger('db_vb_custom_icons_updated');
                }

            });
        });
        observer.observe(
            document.getElementById('et-fb-app'), 
            { 
                attributes: true, 
                attributeFilter: ["class"],
                childList: true, 
                characterData: true,
                subtree: true
            }
        );
    }
    
    function db014_may_contain_icons(target) {
        if (target.className === undefined) { 
            return false; 
        }
        var classes = target.className;
        if (classes.search === undefined) { 
            return false; 
        }
        if (classes.search(/(et-pb-icon|et_pb_inline_icon|et-fb-root-ancestor|et_pb_root--vb|et-fb-post-content|et_pb_section|et_pb_row|et_pb_column)/i) !== -1) {
            return true;
        }
        return false;
    }
END;
}