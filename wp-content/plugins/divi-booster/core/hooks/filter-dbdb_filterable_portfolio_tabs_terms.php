<?php 

$filter = (new DBDBFilterablePortfolioTabsTermsFilter());
$filter->init();

class DBDBFilterablePortfolioTabsTermsFilter {

    private $props;
    private $atts;

    public function init() {
        add_filter('dbdb_et_pb_module_shortcode_attributes', array($this, 'add_terms_filter'), 10, 3);
        add_filter('et_module_shortcode_output', array($this, 'remove_terms_filter'));
    }
    
    public function add_terms_filter($props, $atts, $slug) {
        if ($slug !== 'et_pb_filterable_portfolio') return $props; 
        $this->props = $props;
        $this->atts = $atts;
        add_filter('get_terms', array($this, 'portfolio_tabs_filter'));
        return $props;
    }

    public function portfolio_tabs_filter($terms) {
        return apply_filters('dbdb_filterable_portfolio_tabs_terms', $terms, $this->props, $this->atts);
    }

    function remove_terms_filter($content) {
        remove_filter('get_terms', array($this, 'portfolio_tabs_filter'));
        return $content;
    }
}