<?php
/**
 * License Management Class
 * Handles license custom post type and related functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class License_Manager_License {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_license_meta'));
        add_filter('manage_lm_license_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_lm_license_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_action('admin_init', array($this, 'handle_license_actions'));
    }
    
    /**
     * Add meta boxes for license edit screen
     */
    public function add_meta_boxes() {
        add_meta_box(
            'license_details',
            __('Lisans Detayları', 'license-manager'),
            array($this, 'license_details_meta_box'),
            'lm_license',
            'normal',
            'high'
        );
        
        add_meta_box(
            'license_customer',
            __('Müşteri Ataması', 'license-manager'),
            array($this, 'license_customer_meta_box'),
            'lm_license',
            'side',
            'default'
        );
        
        add_meta_box(
            'license_actions',
            __('Lisans İşlemleri', 'license-manager'),
            array($this, 'license_actions_meta_box'),
            'lm_license',
            'side',
            'default'
        );
    }
    
    /**
     * License details meta box
     */
    public function license_details_meta_box($post) {
        wp_nonce_field('save_license_meta', 'license_meta_nonce');
        
        $license_key = get_post_meta($post->ID, '_license_key', true);
        $expires_on = get_post_meta($post->ID, '_expires_on', true);
        $user_limit = get_post_meta($post->ID, '_user_limit', true);
        $allowed_domains = get_post_meta($post->ID, '_allowed_domains', true);
        $created_on = get_post_meta($post->ID, '_created_on', true);
        $last_check = get_post_meta($post->ID, '_last_check', true);
        $notes = get_post_meta($post->ID, '_notes', true);
        
        // Generate license key if empty
        if (empty($license_key)) {
            $license_key = $this->generate_license_key();
        }
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="license_key"><?php _e('Lisans Anahtarı', 'license-manager'); ?></label></th>
                <td>
                    <input type="text" id="license_key" name="license_key" value="<?php echo esc_attr($license_key); ?>" class="regular-text" readonly />
                    <button type="button" id="regenerate_license_key" class="button"><?php _e('Regenerate', 'license-manager'); ?></button>
                    <p class="description"><?php _e('Unique license key for this license.', 'license-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="expires_on"><?php _e('Bitiş Tarihi', 'license-manager'); ?></label></th>
                <td>
                    <input type="date" id="expires_on" name="expires_on" value="<?php echo esc_attr($expires_on); ?>" />
                    <p class="description"><?php _e('Leave empty for lifetime license.', 'license-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="user_limit"><?php _e('Kullanıcı Limiti', 'license-manager'); ?></label></th>
                <td>
                    <input type="number" id="user_limit" name="user_limit" value="<?php echo esc_attr($user_limit); ?>" min="1" />
                    <p class="description"><?php _e('Maximum number of users allowed.', 'license-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="allowed_domains"><?php _e('Allowed Domains', 'license-manager'); ?></label></th>
                <td>
                    <textarea id="allowed_domains" name="allowed_domains" rows="3" class="large-text" placeholder="example.com&#10;subdomain.example.com"><?php echo esc_textarea($allowed_domains); ?></textarea>
                    <p class="description"><?php _e('Enter one domain per line. Leave empty to allow any domain.', 'license-manager'); ?></p>
                </td>
            </tr>
            <?php if ($created_on): ?>
            <tr>
                <th><?php _e('Created On', 'license-manager'); ?></th>
                <td><?php echo date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($created_on)); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($last_check): ?>
            <tr>
                <th><?php _e('Last Check', 'license-manager'); ?></th>
                <td><?php echo date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_check)); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th><label for="notes"><?php _e('Notes', 'license-manager'); ?></label></th>
                <td><textarea id="notes" name="notes" rows="4" class="large-text"><?php echo esc_textarea($notes); ?></textarea></td>
            </tr>
        </table>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#regenerate_license_key').click(function() {
                if (confirm('<?php _e("Are you sure you want to regenerate the license key? This will invalidate the current key.", "license-manager"); ?>')) {
                    $.post(ajaxurl, {
                        action: 'regenerate_license_key',
                        post_id: <?php echo $post->ID; ?>,
                        nonce: '<?php echo wp_create_nonce('regenerate_license_key'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $('#license_key').val(response.data.license_key);
                        } else {
                            alert('<?php _e("Error regenerating license key.", "license-manager"); ?>');
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * License customer meta box
     */
    public function license_customer_meta_box($post) {
        $customer_id = get_post_meta($post->ID, '_customer_id', true);
        $customers = License_Manager_Customer::get_customers_dropdown();
        
        // Check if customer_id is passed via URL (for new licenses)
        if (empty($customer_id) && isset($_GET['customer_id'])) {
            $customer_id = intval($_GET['customer_id']);
        }
        
        ?>
        <p>
            <label for="customer_id"><?php _e('Assign to Customer:', 'license-manager'); ?></label><br>
            <select id="customer_id" name="customer_id" style="width: 100%;">
                <option value=""><?php _e('Select Customer', 'license-manager'); ?></option>
                <?php foreach ($customers as $id => $name): ?>
                <option value="<?php echo esc_attr($id); ?>" <?php selected($customer_id, $id); ?>>
                    <?php echo esc_html($name); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <?php if ($customer_id): ?>
        <p><a href="<?php echo get_edit_post_link($customer_id); ?>"><?php _e('Edit Customer', 'license-manager'); ?></a></p>
        <?php endif; ?>
        
        <p><a href="<?php echo admin_url('post-new.php?post_type=lm_customer'); ?>"><?php _e('Add New Customer', 'license-manager'); ?></a></p>
        <?php
    }
    
    /**
     * License actions meta box
     */
    public function license_actions_meta_box($post) {
        $license_key = get_post_meta($post->ID, '_license_key', true);
        
        ?>
        <div class="license-actions">
            <?php if (!empty($license_key)): ?>
            <p><a href="#" id="test_license" class="button"><?php _e('Test License', 'license-manager'); ?></a></p>
            <p><a href="#" id="extend_license" class="button"><?php _e('Extend License', 'license-manager'); ?></a></p>
            <?php endif; ?>
            <p><a href="<?php echo admin_url('admin.php?page=license-manager-licenses'); ?>" class="button"><?php _e('View All Licenses', 'license-manager'); ?></a></p>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#test_license').click(function(e) {
                e.preventDefault();
                // Test license via API
                var license_key = $('#license_key').val();
                if (license_key) {
                    window.open('<?php echo rest_url('balkay-license/v1/license_info'); ?>?license_key=' + license_key, '_blank');
                }
            });
            
            $('#extend_license').click(function(e) {
                e.preventDefault();
                var days = prompt('<?php _e("How many days to extend the license?", "license-manager"); ?>', '30');
                if (days && !isNaN(days)) {
                    $.post(ajaxurl, {
                        action: 'extend_license',
                        post_id: <?php echo $post->ID; ?>,
                        days: days,
                        nonce: '<?php echo wp_create_nonce('extend_license'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $('#expires_on').val(response.data.new_expiry);
                            alert('<?php _e("License extended successfully.", "license-manager"); ?>');
                        } else {
                            alert('<?php _e("Error extending license.", "license-manager"); ?>');
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save license meta data
     */
    public function save_license_meta($post_id) {
        // Check if nonce is valid
        if (!isset($_POST['license_meta_nonce']) || !wp_verify_nonce($_POST['license_meta_nonce'], 'save_license_meta')) {
            return;
        }
        
        // Check if user has permission to edit post
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check post type
        if (get_post_type($post_id) !== 'lm_license') {
            return;
        }
        
        // Save meta fields
        $meta_fields = array(
            '_license_key' => 'sanitize_text_field',
            '_expires_on' => 'sanitize_text_field',
            '_user_limit' => 'intval',
            '_allowed_domains' => 'sanitize_textarea_field',
            '_customer_id' => 'intval',
            '_notes' => 'sanitize_textarea_field'
        );
        
        foreach ($meta_fields as $field => $sanitize_callback) {
            $key = str_replace('_', '', $field);
            if (isset($_POST[$key])) {
                $value = call_user_func($sanitize_callback, $_POST[$key]);
                update_post_meta($post_id, $field, $value);
            }
        }
        
        // Set created_on if it's a new post
        if (!get_post_meta($post_id, '_created_on', true)) {
            update_post_meta($post_id, '_created_on', current_time('mysql'));
        }
        
        // Update last_check
        update_post_meta($post_id, '_last_check', current_time('mysql'));
    }
    
    /**
     * Set custom columns for license list
     */
    public function set_custom_columns($columns) {
        unset($columns['date']);
        
        $columns['license_key'] = __('Lisans Anahtarı', 'license-manager');
        $columns['customer'] = __('Müşteri', 'license-manager');
        $columns['license_type'] = __('Tür', 'license-manager');
        $columns['status'] = __('Durum', 'license-manager');
        $columns['expires_on'] = __('Bitiş Tarihi', 'license-manager');
        $columns['user_limit'] = __('Kullanıcı Limiti', 'license-manager');
        $columns['date'] = __('Date', 'license-manager');
        
        return $columns;
    }
    
    /**
     * Custom column content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'license_key':
                $license_key = get_post_meta($post_id, '_license_key', true);
                if ($license_key) {
                    echo '<code>' . esc_html(substr($license_key, 0, 20) . '...') . '</code>';
                }
                break;
                
            case 'customer':
                $customer_id = get_post_meta($post_id, '_customer_id', true);
                if ($customer_id) {
                    $customer = get_post($customer_id);
                    if ($customer) {
                        $company_name = get_post_meta($customer_id, '_company_name', true);
                        $display_name = $company_name ? $company_name : $customer->post_title;
                        echo '<a href="' . get_edit_post_link($customer_id) . '">' . esc_html($display_name) . '</a>';
                    }
                } else {
                    echo '<span class="text-muted">' . __('Müşteri atanmamış', 'license-manager') . '</span>';
                }
                break;
                
            case 'license_type':
                $terms = wp_get_post_terms($post_id, 'lm_license_type');
                if (!is_wp_error($terms) && !empty($terms)) {
                    echo esc_html($terms[0]->name);
                }
                break;
                
            case 'status':
                $terms = wp_get_post_terms($post_id, 'lm_license_status');
                if (!is_wp_error($terms) && !empty($terms)) {
                    $status = $terms[0]->slug;
                    $status_class = 'license-status-' . $status;
                    echo '<span class="' . esc_attr($status_class) . '">' . esc_html($terms[0]->name) . '</span>';
                } else {
                    // Auto-detect status based on expiry
                    $expires_on = get_post_meta($post_id, '_expires_on', true);
                    if ($expires_on && strtotime($expires_on) < current_time('timestamp')) {
                        echo '<span class="license-status-expired">' . __('Süresi Dolmuş', 'license-manager') . '</span>';
                    } else {
                        echo '<span class="license-status-active">' . __('Aktif', 'license-manager') . '</span>';
                    }
                }
                break;
                
            case 'expires_on':
                $expires_on = get_post_meta($post_id, '_expires_on', true);
                if ($expires_on) {
                    $expiry_date = strtotime($expires_on);
                    $current_time = current_time('timestamp');
                    $days_until_expiry = ($expiry_date - $current_time) / DAY_IN_SECONDS;
                    
                    echo date(get_option('date_format'), $expiry_date);
                    
                    if ($days_until_expiry < 0) {
                        echo '<br><small class="expired">' . __('Süresi Dolmuş', 'license-manager') . '</small>';
                    } elseif ($days_until_expiry < 30) {
                        echo '<br><small class="expiring-soon">' . sprintf(__('%d gün içinde süresi doluyor', 'license-manager'), ceil($days_until_expiry)) . '</small>';
                    }
                } else {
                    echo '<span class="lifetime">' . __('Yaşam Boyu', 'license-manager') . '</span>';
                }
                break;
                
            case 'user_limit':
                $user_limit = get_post_meta($post_id, '_user_limit', true);
                echo esc_html($user_limit ?: get_option('license_manager_default_user_limit', 5));
                break;
        }
    }
    
    /**
     * Handle license actions via AJAX
     */
    public function handle_license_actions() {
        add_action('wp_ajax_regenerate_license_key', array($this, 'ajax_regenerate_license_key'));
        add_action('wp_ajax_extend_license', array($this, 'ajax_extend_license'));
    }
    
    /**
     * AJAX handler for regenerating license key
     */
    public function ajax_regenerate_license_key() {
        check_ajax_referer('regenerate_license_key', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_die('Unauthorized');
        }
        
        $new_license_key = $this->generate_license_key();
        update_post_meta($post_id, '_license_key', $new_license_key);
        
        wp_send_json_success(array('license_key' => $new_license_key));
    }
    
    /**
     * AJAX handler for extending license
     */
    public function ajax_extend_license() {
        check_ajax_referer('extend_license', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $days = intval($_POST['days']);
        
        if (!current_user_can('edit_post', $post_id) || $days <= 0) {
            wp_die('Unauthorized or invalid data');
        }
        
        $current_expiry = get_post_meta($post_id, '_expires_on', true);
        
        if ($current_expiry) {
            $new_expiry = date('Y-m-d', strtotime($current_expiry . ' +' . $days . ' days'));
        } else {
            $new_expiry = date('Y-m-d', strtotime('+' . $days . ' days'));
        }
        
        update_post_meta($post_id, '_expires_on', $new_expiry);
        
        wp_send_json_success(array('new_expiry' => $new_expiry));
    }
    
    /**
     * Generate a unique license key
     */
    private function generate_license_key() {
        do {
            $license_key = 'LIC-' . strtoupper(wp_generate_password(8, false)) . '-' . strtoupper(wp_generate_password(8, false)) . '-' . strtoupper(wp_generate_password(8, false));
            
            // Check if key already exists
            $existing = get_posts(array(
                'post_type' => 'lm_license',
                'meta_query' => array(
                    array(
                        'key' => '_license_key',
                        'value' => $license_key,
                        'compare' => '='
                    )
                ),
                'posts_per_page' => 1,
                'fields' => 'ids'
            ));
        } while (!empty($existing));
        
        return $license_key;
    }
    
    /**
     * Get license data by ID
     */
    public static function get_license_data($license_id) {
        $license = get_post($license_id);
        
        if (!$license || $license->post_type !== 'lm_license') {
            return false;
        }
        
        $license_types = wp_get_post_terms($license->ID, 'lm_license_type');
        $license_statuses = wp_get_post_terms($license->ID, 'lm_license_status');
        $modules = wp_get_post_terms($license->ID, 'lm_modules');
        
        $module_slugs = array();
        if (!is_wp_error($modules) && !empty($modules)) {
            foreach ($modules as $module) {
                $module_slugs[] = $module->slug;
            }
        }
        
        return array(
            'id' => $license->ID,
            'license_key' => get_post_meta($license->ID, '_license_key', true),
            'expires_on' => get_post_meta($license->ID, '_expires_on', true),
            'user_limit' => get_post_meta($license->ID, '_user_limit', true),
            'allowed_domains' => get_post_meta($license->ID, '_allowed_domains', true),
            'customer_id' => get_post_meta($license->ID, '_customer_id', true),
            'license_type' => !empty($license_types) ? $license_types[0]->slug : 'monthly',
            'status' => !empty($license_statuses) ? $license_statuses[0]->slug : 'active',
            'modules' => $module_slugs,
            'notes' => get_post_meta($license->ID, '_notes', true),
            'created_on' => get_post_meta($license->ID, '_created_on', true),
            'last_check' => get_post_meta($license->ID, '_last_check', true),
        );
    }
}