<?php 

if (!function_exists('et_pb_get_font_down_icon_symbols')) {
    // Override the built-in Divi function to support filtering
	function et_pb_get_font_down_icon_symbols() {
		$symbols = array( '&amp;#x22;', '&amp;#x33;', '&amp;#x37;', '&amp;#x3b;', '&amp;#x3f;', '&amp;#x43;', '&amp;#x47;', '&amp;#xe03a;', '&amp;#xe044;', '&amp;#xe048;', '&amp;#xe04c;' );
        $symbols = apply_filters('dbdb_et_pb_get_font_down_icon_symbols', $symbols);
		return $symbols;
	}
}