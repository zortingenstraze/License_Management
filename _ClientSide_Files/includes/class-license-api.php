<?php
/**
 * License API Communication Class
 * 
 * Handles communication with the central license server
 * 
 * @package Insurance_CRM
 * @author  Anadolu Birlik
 * @since   1.1.3
 */

if (!defined('ABSPATH')) {
    exit;
}

class Insurance_CRM_License_API {
    
    /**
     * Default license server URL
     */
    private $license_server_url;
    
    /**
     * API timeout in seconds
     */
    private $timeout;
    
    /**
     * Debug mode flag
     */
    private $debug_mode;

    /**
     * Constructor
     */
    public function __construct() {
        $this->license_server_url = get_option('insurance_crm_license_server_url', 'https://balkay.net/crm');
        $this->timeout = 60; // Increased timeout for better reliability
        $this->debug_mode = get_option('insurance_crm_license_debug_mode', false);
    }

    /**
     * Validate license key with central server
     * 
     * @param string $license_key The license key to validate
     * @return array Validation response
     */
    public function validate_license($license_key) {
        if (empty($license_key)) {
            return array(
                'status' => 'invalid',
                'message' => 'Lisans anahtarı boş olamaz'
            );
        }

        // Reload server URL in case it was updated
        $this->license_server_url = get_option('insurance_crm_license_server_url', 'https://balkay.net/crm');
        
        $domain = parse_url(home_url(), PHP_URL_HOST);
        
        $request_data = array(
            'license_key' => sanitize_text_field($license_key),
            'domain' => sanitize_text_field($domain)
        );

        // Try only the primary working endpoint first
        $primary_endpoint = '/api/validate_license';
        
        if ($this->debug_mode) {
            error_log('[LISANS DEBUG] Birincil endpoint deneniyor: ' . $primary_endpoint);
        }
        
        $response = $this->make_request($primary_endpoint, $request_data, 'GET');
        
        if (!is_wp_error($response)) {
            if ($this->debug_mode) {
                error_log('[LISANS DEBUG] Birincil endpoint başarılı: ' . $primary_endpoint);
            }
            return $response;
        }
        
        // Log error but don't show to user
        if ($this->debug_mode) {
            error_log('[LISANS DEBUG] Birincil endpoint başarısız: ' . $response->get_error_message());
        }

        // Try only one reliable fallback endpoint
        $fallback_endpoint = '/?action=validate_license';
        
        if ($this->debug_mode) {
            error_log('[LISANS DEBUG] Yedek endpoint deneniyor: ' . $fallback_endpoint);
        }
        
        $response = $this->make_request($fallback_endpoint, $request_data, 'GET');
        
        if (!is_wp_error($response)) {
            if ($this->debug_mode) {
                error_log('[LISANS DEBUG] Yedek endpoint başarılı: ' . $fallback_endpoint);
            }
            return $response;
        } else {
            if ($this->debug_mode) {
                error_log('[LISANS DEBUG] Yedek endpoint başarısız: ' . $fallback_endpoint . ' - ' . $response->get_error_message());
            }
        }
        
        // Return error silently without exposing details to UI
        return array(
            'status' => 'error',
            'message' => 'Sunucu ile iletişim kurulamadı'
        );
    }

    /**
     * Get license information from server
     * 
     * @param string $license_key The license key
     * @return array License information
     */
    public function get_license_info($license_key) {
        if (empty($license_key)) {
            return array(
                'status' => 'invalid',
                'message' => 'Lisans anahtarı boş olamaz'
            );
        }

        $request_data = array(
            'license_key' => sanitize_text_field($license_key),
            'action' => 'get_info'
        );

        // Try different API endpoint patterns
        $endpoints_to_try = array(
            '/wp-json/balkay-license/v1/info',
            '/api/license_info',
            '/wp-admin/admin-ajax.php',
            '/?action=license_info',
        );

        foreach ($endpoints_to_try as $endpoint) {
            if ($endpoint === '/wp-admin/admin-ajax.php') {
                $ajax_data = array_merge($request_data, array('action' => 'license_info'));
                $response = $this->make_request($endpoint, $ajax_data, 'POST');
            } elseif ($endpoint === '/?action=license_info') {
                $response = $this->make_request($endpoint, $request_data, 'GET');
            } else {
                $response = $this->make_request($endpoint, $request_data, 'GET');
            }
            
            if (!is_wp_error($response)) {
                return $response;
            }
        }
        
        return array(
            'status' => 'error',
            'message' => 'Sunucu ile iletişim kurulamadı'
        );
    }

    /**
     * Check license status (for periodic validation)
     * 
     * @param string $license_key The license key
     * @return array Status response
     */
    public function check_license_status($license_key) {
        if (empty($license_key)) {
            return array(
                'status' => 'invalid',
                'message' => 'Lisans anahtarı boş olamaz'
            );
        }

        $domain = parse_url(home_url(), PHP_URL_HOST);
        
        $request_data = array(
            'license_key' => sanitize_text_field($license_key),
            'domain' => sanitize_text_field($domain)
        );

        // Try primary endpoint first
        $primary_endpoint = '/api/validate_license';
        $response = $this->make_request($primary_endpoint, $request_data, 'GET');
        
        if (!is_wp_error($response)) {
            return $response;
        }

        // Try one fallback endpoint
        $fallback_endpoint = '/?action=check_status';
        $response = $this->make_request($fallback_endpoint, $request_data, 'GET');
        
        if (!is_wp_error($response)) {
            return $response;
        }
        
        // In case of communication error, return last known status without error details
        $last_status = get_option('insurance_crm_license_status', 'inactive');
        return array(
            'status' => $last_status,
            'message' => 'Sunucu ile iletişim kurulamadı, son bilinen durum kullanılıyor',
            'offline' => true
        );
    }

    /**
     * Make HTTP request to license server
     * 
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param string $method HTTP method (GET, POST)
     * @return array|WP_Error Response data or error
     */
    private function make_request($endpoint, $data = array(), $method = 'GET') {
        $url = rtrim($this->license_server_url, '/') . $endpoint;
        
        $args = array(
            'method' => $method,
            'timeout' => $this->timeout,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'Insurance-CRM/' . INSURANCE_CRM_VERSION . '; ' . home_url(),
                'Accept' => 'application/json'
            ),
            'sslverify' => false, // SSL doğrulamayı devre dışı bırak (test için)
            'redirection' => 5
        );

        if ($method === 'POST') {
            if (strpos($endpoint, 'admin-ajax.php') !== false) {
                // WordPress AJAX expects form data, not JSON
                $args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
                $args['body'] = http_build_query($data);
            } else {
                $args['body'] = json_encode($data);
            }
        } else {
            if (!empty($data)) {
                $url = add_query_arg($data, $url);
            }
        }

        // Log debug information only in debug mode
        if ($this->debug_mode) {
            error_log('[LISANS DEBUG] İstek URL: ' . $url);
            error_log('[LISANS DEBUG] İstek Metodu: ' . $method);
            error_log('[LISANS DEBUG] İstek Verisi: ' . json_encode($data));
            error_log('[LISANS DEBUG] İstek Başlıkları: ' . json_encode($args['headers']));
        }

        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            
            // Only log in debug mode
            if ($this->debug_mode) {
                error_log('[LISANS HATA] API İsteği Başarısız: ' . $error_message);
            }
            
            // Return generic error without exposing details
            return new WP_Error(
                'api_connection_error',
                'Lisans sunucusuna bağlanılamadı'
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_headers = wp_remote_retrieve_headers($response);

        // Log response information only in debug mode
        if ($this->debug_mode) {
            error_log('[LISANS DEBUG] Yanıt Kodu: ' . $response_code);
            error_log('[LISANS DEBUG] Yanıt Boyutu: ' . strlen($response_body) . ' byte');
            error_log('[LISANS DEBUG] Yanıt İçeriği (ilk 500 karakter): ' . substr($response_body, 0, 500));
            error_log('[LISANS DEBUG] Yanıt Başlıkları: ' . json_encode($response_headers));
        }

        // Handle HTTP errors silently
        $successful_codes = array(200, 201, 202);
        if (!in_array($response_code, $successful_codes)) {
            // Only log in debug mode
            if ($this->debug_mode) {
                error_log('[LISANS HATA] HTTP Hatası: ' . $response_code);
            }
            
            // Return generic errors without exposing technical details
            if ($response_code == 404) {
                return new WP_Error(
                    'api_endpoint_not_found',
                    'API endpoint bulunamadı'
                );
            }
            
            return new WP_Error(
                'api_http_error',
                'Lisans sunucusundan HTTP hatası'
            );
        }

        // Handle empty responses silently
        if (empty($response_body)) {
            if ($this->debug_mode) {
                error_log('[LISANS HATA] Boş yanıt alındı');
            }
            return new WP_Error(
                'api_empty_response',
                'Lisans sunucusundan boş yanıt alındı'
            );
        }

        // Handle HTML responses silently (expected JSON)
        if (strpos($response_body, '<!DOCTYPE') === 0 || strpos($response_body, '<html') !== false) {
            if ($this->debug_mode) {
                error_log('[LISANS HATA] HTML yanıt alındı, JSON bekliyordu');
            }
            return new WP_Error(
                'api_html_response',
                'Sunucu HTML yanıt döndürdü, JSON bekliyordu'
            );
        }

        $decoded_response = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            if ($this->debug_mode) {
                error_log('[LISANS HATA] JSON Parse Hatası: ' . json_last_error_msg());
            }
            return new WP_Error(
                'json_parse_error',
                'Sunucu yanıtı çözümlenemedi'
            );
        }

        if ($this->debug_mode) {
            error_log('[LISANS DEBUG] Başarılı yanıt alındı: ' . json_encode($decoded_response));
        }
        return $decoded_response;
    }

    /**
     * Get fallback/demo license data for testing
     * 
     * @return array Demo license data
     */
    public function get_demo_license_data() {
        return array(
            'status' => 'active',
            'user_limit' => 5,
            'expires_on' => date('Y-m-d', strtotime('+30 days')),
            'license_type' => 'monthly',
            'modules' => array('customers', 'policies', 'tasks', 'reports'),
            'message' => 'Demo lisans - test amaçlı'
        );
    }

    /**
     * Check if bypass license is enabled (for development)
     * 
     * @return bool True if bypass is enabled
     */
    public function is_license_bypassed() {
        return get_option('insurance_crm_bypass_license', false);
    }

    /**
     * Set license server URL
     * 
     * @param string $url License server URL
     */
    public function set_license_server_url($url) {
        $this->license_server_url = $url;
        update_option('insurance_crm_license_server_url', $url);
    }

    /**
     * Enable/disable debug mode
     * 
     * @param bool $enabled Debug mode status
     */
    public function set_debug_mode($enabled) {
        $this->debug_mode = $enabled;
        update_option('insurance_crm_license_debug_mode', $enabled);
    }

    /**
     * Test basic connectivity to license server
     * 
     * @return array Test results
     */
    public function test_server_connection() {
        $server_url = $this->license_server_url;
        $results = array();
        
        // Test 1: Basic HTTP connectivity
        error_log('[LISANS DEBUG] Temel bağlantı testi başlatılıyor: ' . $server_url);
        
        $response = wp_remote_get($server_url, array(
            'timeout' => 30, // Increased timeout for connection test
            'sslverify' => false,
            'redirection' => 5
        ));
        
        if (is_wp_error($response)) {
            $results['connectivity'] = array(
                'success' => false,
                'message' => 'Temel bağlantı başarısız: ' . $response->get_error_message()
            );
        } else {
            $code = wp_remote_retrieve_response_code($response);
            $results['connectivity'] = array(
                'success' => true,
                'message' => 'Temel bağlantı başarılı (HTTP ' . $code . ')',
                'code' => $code
            );
        }
        
        // Test 2: Check available endpoints
        $endpoints_to_test = array(
            '/wp-json/balkay-license/v1/validate',
            '/api/validate_license',
            '/wp-admin/admin-ajax.php',
            '/wp-json/wp/v2'  // WordPress REST API base
        );
        
        $results['endpoints'] = array();
        
        foreach ($endpoints_to_test as $endpoint) {
            $test_url = rtrim($server_url, '/') . $endpoint;
            $test_response = wp_remote_get($test_url, array(
                'timeout' => 20, // Increased timeout for endpoint tests
                'sslverify' => false
            ));
            
            if (!is_wp_error($test_response)) {
                $test_code = wp_remote_retrieve_response_code($test_response);
                $test_body = wp_remote_retrieve_body($test_response);
                
                $results['endpoints'][$endpoint] = array(
                    'code' => $test_code,
                    'accessible' => in_array($test_code, array(200, 201, 400, 405)), // 400/405 means endpoint exists but wrong method/params
                    'body_preview' => substr($test_body, 0, 100)
                );
            } else {
                $results['endpoints'][$endpoint] = array(
                    'code' => null,
                    'accessible' => false,
                    'error' => $test_response->get_error_message()
                );
            }
        }
        
        // Test 3: Check if it's a WordPress site
        $wp_json_url = rtrim($server_url, '/') . '/wp-json/wp/v2';
        $wp_response = wp_remote_get($wp_json_url, array('timeout' => 20, 'sslverify' => false));
        
        if (!is_wp_error($wp_response) && wp_remote_retrieve_response_code($wp_response) == 200) {
            $results['is_wordpress'] = true;
            $results['wordpress_api'] = 'Erişilebilir';
        } else {
            $results['is_wordpress'] = false;
            $results['wordpress_api'] = 'Erişilemez';
        }
        
        return $results;
    }
    
    /**
     * Get available modules from license server
     * 
     * @return array Modules data
     */
    public function get_modules() {
        $url = rtrim($this->license_server_url, '/') . '/wp-json/balkay-license/v1/modules';
        
        if ($this->debug_mode) {
            error_log('License API: Getting modules from: ' . $url);
        }
        
        $args = array(
            'method' => 'GET',
            'timeout' => $this->timeout,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'Insurance-CRM-License-Client/1.0'
            ),
            'sslverify' => false // For development/testing
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            if ($this->debug_mode) {
                error_log('License API: Modules request failed: ' . $response->get_error_message());
            }
            return array('modules' => array(), 'error' => $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            if ($this->debug_mode) {
                error_log('License API: Modules request returned code: ' . $response_code);
                error_log('License API: Response body: ' . $body);
            }
            return array('modules' => array(), 'error' => "HTTP $response_code");
        }
        
        $data = json_decode($body, true);
        
        if (!is_array($data)) {
            if ($this->debug_mode) {
                error_log('License API: Invalid modules response: ' . $body);
            }
            return array('modules' => array(), 'error' => 'Invalid JSON response');
        }
        
        // Handle both old and new response formats
        if (isset($data['success']) && $data['success'] && isset($data['modules'])) {
            // New format with success flag
            $modules_array = $data['modules'];
        } elseif (isset($data['modules'])) {
            // Legacy format
            $modules_array = $data['modules'];
        } else {
            // No modules key found
            if ($this->debug_mode) {
                error_log('License API: No modules key found in response');
            }
            return array('modules' => array(), 'error' => 'No modules data in response');
        }
        
        if ($this->debug_mode) {
            $module_count = count($modules_array);
            error_log('License API: Successfully retrieved ' . $module_count . ' modules');
            if (is_array($modules_array)) {
                foreach ($modules_array as $module) {
                    if (isset($module['name']) && isset($module['slug'])) {
                        error_log('License API: Module - ' . $module['name'] . ' (slug: ' . $module['slug'] . ')');
                    }
                }
            }
        }
        
        return array('modules' => $modules_array, 'success' => true);
    }
}