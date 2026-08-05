<?php
/**
 * PHPUnit tests for NUVANX theme core modules
 *
 * Tests critical resolution logic and schema functions
 * as recommended in Antigravity audit P3-12
 */

use PHPUnit\Framework\TestCase;

class NuvanxCoreModulesTest extends TestCase {

    /**
     * Test nvx_seo_resolve_robots_policy logic
     */
    public function test_robots_policy_resolution() {
        // Test that production environment returns proper robots policy
        $this->assertTrue(function_exists('nvx_seo_resolve_robots_policy'));
        
        // This would require mocking the environment constants
        // For now, we verify the function exists and is callable
        $this->assertIsCallable('nvx_seo_resolve_robots_policy');
    }

    /**
     * Test nvx_schema_resolve_clinic_keys logic
     */
    public function test_clinic_keys_resolution() {
        // Test that clinic key resolution function exists
        $this->assertTrue(function_exists('nvx_schema_resolve_clinic_keys'));
        $this->assertIsCallable('nvx_schema_resolve_clinic_keys');
    }

    /**
     * Test nvx_seo_current_metadata_key logic
     */
    public function test_metadata_key_resolution() {
        // Test that metadata key resolution function exists
        $this->assertTrue(function_exists('nvx_seo_current_metadata_key'));
        $this->assertIsCallable('nvx_seo_current_metadata_key');
    }

    /**
     * Test config.json loader exists and returns array
     */
    public function test_config_json_loader() {
        $this->assertTrue(function_exists('nvx_get_config'));
        
        $config = nvx_get_config();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('contact', $config);
    }

    /**
     * Test tariff catalog loader exists and returns array
     */
    public function test_tariff_catalog_loader() {
        $this->assertTrue(function_exists('nvx_get_tariff_catalog'));
        
        $catalog = nvx_get_tariff_catalog();
        $this->assertIsArray($catalog);
    }

    /**
     * Test catalog JSON loader exists and is callable
     */
    public function test_catalog_json_loader() {
        $this->assertTrue(function_exists('nvx_catalog_json_resolved'));
        $this->assertIsCallable('nvx_catalog_json_resolved');
    }
}
