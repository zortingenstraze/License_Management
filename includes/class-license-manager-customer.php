<?php
/**
 * Customer Management Class
 * Handles customer custom post type and related functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class License_Manager_Customer {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_customer_meta'));
        add_filter('manage_lm_customer_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_lm_customer_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
    }
    
    /**
     * Add meta boxes for customer edit screen
     */
    public function add_meta_boxes() {
        add_meta_box(
            'customer_details',
            __('Müşteri Detayları', 'license-manager'),
            array($this, 'customer_details_meta_box'),
            'lm_customer',
            'normal',
            'high'
        );
        
        add_meta_box(
            'customer_licenses',
            __('Müşteri Lisansları', 'license-manager'),
            array($this, 'customer_licenses_meta_box'),
            'lm_customer',
            'side',
            'default'
        );
    }
    
    /**
     * Customer details meta box
     */
    public function customer_details_meta_box($post) {
        wp_nonce_field('save_customer_meta', 'customer_meta_nonce');
        
        $company_name = get_post_meta($post->ID, '_company_name', true);
        $contact_person = get_post_meta($post->ID, '_contact_person', true);
        $email = get_post_meta($post->ID, '_email', true);
        $phone = get_post_meta($post->ID, '_phone', true);
        $website = get_post_meta($post->ID, '_website', true);
        $address = get_post_meta($post->ID, '_address', true);
        $allowed_domains = get_post_meta($post->ID, '_allowed_domains', true);
        $notes = get_post_meta($post->ID, '_notes', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="company_name"><?php _e('Şirket Adı', 'license-manager'); ?></label></th>
                <td><input type="text" id="company_name" name="company_name" value="<?php echo esc_attr($company_name); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="contact_person"><?php _e('İletişim Kişisi', 'license-manager'); ?></label></th>
                <td><input type="text" id="contact_person" name="contact_person" value="<?php echo esc_attr($contact_person); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="email"><?php _e('E-posta', 'license-manager'); ?></label></th>
                <td><input type="email" id="email" name="email" value="<?php echo esc_attr($email); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="phone"><?php _e('Phone', 'license-manager'); ?></label></th>
                <td><input type="text" id="phone" name="phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="website"><?php _e('Website', 'license-manager'); ?></label></th>
                <td><input type="url" id="website" name="website" value="<?php echo esc_attr($website); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="address"><?php _e('Address', 'license-manager'); ?></label></th>
                <td><textarea id="address" name="address" rows="3" class="large-text"><?php echo esc_textarea($address); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="allowed_domains"><?php _e('Allowed Domains', 'license-manager'); ?></label></th>
                <td>
                    <textarea id="allowed_domains" name="allowed_domains" rows="3" class="large-text" placeholder="example.com&#10;subdomain.example.com"><?php echo esc_textarea($allowed_domains); ?></textarea>
                    <p class="description"><?php _e('Enter one domain per line. These domains will be authorized for licenses assigned to this customer.', 'license-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="notes"><?php _e('Notes', 'license-manager'); ?></label></th>
                <td><textarea id="notes" name="notes" rows="4" class="large-text"><?php echo esc_textarea($notes); ?></textarea></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Customer licenses meta box
     */
    public function customer_licenses_meta_box($post) {
        $licenses = get_posts(array(
            'post_type' => 'lm_license',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_customer_id',
                    'value' => $post->ID,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1
        ));
        
        if (empty($licenses)) {
            echo '<p>' . __('Bu müşteriye henüz lisans atanmamış.', 'license-manager') . '</p>';
        } else {
            echo '<ul>';
            foreach ($licenses as $license) {
                $license_key = get_post_meta($license->ID, '_license_key', true);
                $status_terms = wp_get_post_terms($license->ID, 'lm_license_status');
                $status = !empty($status_terms) ? $status_terms[0]->name : __('Bilinmeyen', 'license-manager');
                $expires_on = get_post_meta($license->ID, '_expires_on', true);
                
                echo '<li>';
                echo '<strong>' . esc_html(substr($license_key, 0, 15) . '...') . '</strong><br>';
                echo '<small>' . __('Durum:', 'license-manager') . ' ' . esc_html($status) . '</small><br>';
                if ($expires_on) {
                    echo '<small>' . __('Süresi Doluyor:', 'license-manager') . ' ' . date(get_option('date_format'), strtotime($expires_on)) . '</small><br>';
                }
                echo '<a href="' . get_edit_post_link($license->ID) . '">' . __('Lisans Düzenle', 'license-manager') . '</a>';
                echo '</li><br>';
            }
            echo '</ul>';
        }
        
        echo '<p><a href="' . admin_url('post-new.php?post_type=lm_license&customer_id=' . $post->ID) . '" class="button">' . __('Yeni Lisans Ekle', 'license-manager') . '</a></p>';
    }
    
    /**
     * Save customer meta data
     */
    public function save_customer_meta($post_id) {
        // Check if nonce is valid
        if (!isset($_POST['customer_meta_nonce']) || !wp_verify_nonce($_POST['customer_meta_nonce'], 'save_customer_meta')) {
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
        if (get_post_type($post_id) !== 'lm_customer') {
            return;
        }
        
        // Save meta fields
        $meta_fields = array(
            '_company_name',
            '_contact_person',
            '_email',
            '_phone',
            '_website',
            '_address',
            '_allowed_domains',
            '_notes'
        );
        
        foreach ($meta_fields as $field) {
            $key = str_replace('_', '', $field);
            if (isset($_POST[$key])) {
                $value = $_POST[$key];
                
                // Sanitize based on field type
                switch ($field) {
                    case '_email':
                        $value = sanitize_email($value);
                        break;
                    case '_website':
                        $value = esc_url_raw($value);
                        break;
                    case '_allowed_domains':
                    case '_address':
                    case '_notes':
                        $value = sanitize_textarea_field($value);
                        break;
                    default:
                        $value = sanitize_text_field($value);
                        break;
                }
                
                update_post_meta($post_id, $field, $value);
            }
        }
    }
    
    /**
     * Set custom columns for customer list
     */
    public function set_custom_columns($columns) {
        unset($columns['date']);
        
        $columns['company_name'] = __('Şirket Adı', 'license-manager');
        $columns['contact_person'] = __('İletişim Kişisi', 'license-manager');
        $columns['email'] = __('E-posta', 'license-manager');
        $columns['licenses_count'] = __('Lisanslar', 'license-manager');
        $columns['date'] = __('Tarih', 'license-manager');
        
        return $columns;
    }
    
    /**
     * Custom column content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'company_name':
                echo esc_html(get_post_meta($post_id, '_company_name', true));
                break;
                
            case 'contact_person':
                echo esc_html(get_post_meta($post_id, '_contact_person', true));
                break;
                
            case 'email':
                $email = get_post_meta($post_id, '_email', true);
                if ($email) {
                    echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
                }
                break;
                
            case 'licenses_count':
                $licenses = get_posts(array(
                    'post_type' => 'lm_license',
                    'post_status' => 'publish',
                    'meta_query' => array(
                        array(
                            'key' => '_customer_id',
                            'value' => $post_id,
                            'compare' => '='
                        )
                    ),
                    'posts_per_page' => -1,
                    'fields' => 'ids'
                ));
                
                $count = count($licenses);
                echo '<span class="license-count">' . $count . '</span>';
                
                if ($count > 0) {
                    echo ' <a href="' . admin_url('edit.php?post_type=lm_license&customer_id=' . $post_id) . '">' . __('Görüntüle', 'license-manager') . '</a>';
                }
                break;
        }
    }
    
    /**
     * Get customer data by ID
     */
    public static function get_customer_data($customer_id) {
        $customer = get_post($customer_id);
        
        if (!$customer || $customer->post_type !== 'lm_customer') {
            return false;
        }
        
        return array(
            'id' => $customer->ID,
            'name' => $customer->post_title,
            'company_name' => get_post_meta($customer->ID, '_company_name', true),
            'contact_person' => get_post_meta($customer->ID, '_contact_person', true),
            'email' => get_post_meta($customer->ID, '_email', true),
            'phone' => get_post_meta($customer->ID, '_phone', true),
            'website' => get_post_meta($customer->ID, '_website', true),
            'address' => get_post_meta($customer->ID, '_address', true),
            'allowed_domains' => get_post_meta($customer->ID, '_allowed_domains', true),
            'notes' => get_post_meta($customer->ID, '_notes', true),
        );
    }
    
    /**
     * Get customers list for dropdown
     */
    public static function get_customers_dropdown() {
        $customers = get_posts(array(
            'post_type' => 'lm_customer',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $options = array();
        foreach ($customers as $customer) {
            $company_name = get_post_meta($customer->ID, '_company_name', true);
            $display_name = $company_name ? $company_name : $customer->post_title;
            $options[$customer->ID] = $display_name;
        }
        
        return $options;
    }
}