<?php
/**
 * Admin Interface Class
 * Handles WordPress admin interface for license management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class License_Manager_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_admin'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_post_license_manager_add_customer', array($this, 'handle_add_customer'));
        add_action('admin_post_license_manager_add_license', array($this, 'handle_add_license'));
        add_action('admin_post_license_manager_add_package', array($this, 'handle_add_package'));
        add_action('admin_post_license_manager_add_payment', array($this, 'handle_add_payment'));
        add_action('admin_post_license_manager_edit_customer', array($this, 'handle_edit_customer'));
        add_action('admin_post_license_manager_edit_license', array($this, 'handle_edit_license'));
        add_action('admin_post_license_manager_edit_package', array($this, 'handle_edit_package'));
        add_action('admin_post_license_manager_edit_payment', array($this, 'handle_edit_payment'));
        add_action('admin_post_license_manager_send_reminder', array($this, 'handle_send_reminder'));
        add_action('admin_post_license_manager_delete_customer', array($this, 'handle_delete_customer'));
        add_action('admin_post_license_manager_delete_license', array($this, 'handle_delete_license'));
        add_action('admin_post_license_manager_delete_package', array($this, 'handle_delete_package'));
        add_action('admin_post_license_manager_delete_payment', array($this, 'handle_delete_payment'));
    }
    
    /**
     * Initialize admin functionality
     */
    public function init_admin() {
        // Add custom capability to administrators
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_license_manager');
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Lisans Yöneticisi', 'license-manager'),
            __('Lisans Yöneticisi', 'license-manager'),
            'manage_license_manager',
            'license-manager',
            array($this, 'display_dashboard'),
            'dashicons-admin-network',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'license-manager',
            __('Kontrol Paneli', 'license-manager'),
            __('Kontrol Paneli', 'license-manager'),
            'manage_license_manager',
            'license-manager',
            array($this, 'display_dashboard')
        );
        
        // Customers submenu
        add_submenu_page(
            'license-manager',
            __('Müşteriler', 'license-manager'),
            __('Müşteriler', 'license-manager'),
            'manage_license_manager',
            'license-manager-customers',
            array($this, 'display_customers')
        );
        
        // Licenses submenu
        add_submenu_page(
            'license-manager',
            __('Lisanslar', 'license-manager'),
            __('Lisanslar', 'license-manager'),
            'manage_license_manager',
            'license-manager-licenses',
            array($this, 'display_licenses')
        );
        
        // License Packages submenu
        add_submenu_page(
            'license-manager',
            __('Lisans Paketleri', 'license-manager'),
            __('Lisans Paketleri', 'license-manager'),
            'manage_license_manager',
            'license-manager-packages',
            array($this, 'display_packages')
        );
        
        // Payments submenu
        add_submenu_page(
            'license-manager',
            __('Ödeme Yönetimi', 'license-manager'),
            __('Ödemeler', 'license-manager'),
            'manage_license_manager',
            'license-manager-payments',
            array($this, 'display_payments')
        );
        
        // Modules submenu
        add_submenu_page(
            'license-manager',
            __('Modül Yönetimi', 'license-manager'),
            __('Modüller', 'license-manager'),
            'manage_license_manager',
            'license-manager-modules',
            array($this, 'display_modules')
        );
        
        // Add New Customer submenu (hidden)
        add_submenu_page(
            null, // Hidden from menu
            __('Yeni Müşteri Ekle', 'license-manager'),
            __('Yeni Müşteri Ekle', 'license-manager'),
            'manage_license_manager',
            'license-manager-add-customer',
            array($this, 'display_add_customer')
        );
        
        // Add New License submenu (hidden)
        add_submenu_page(
            null, // Hidden from menu
            __('Yeni Lisans Ekle', 'license-manager'),
            __('Yeni Lisans Ekle', 'license-manager'),
            'manage_license_manager',
            'license-manager-add-license',
            array($this, 'display_add_license')
        );
        
        // Add New Package submenu (hidden)
        add_submenu_page(
            null, // Hidden from menu
            __('Yeni Paket Ekle', 'license-manager'),
            __('Yeni Paket Ekle', 'license-manager'),
            'manage_license_manager',
            'license-manager-add-package',
            array($this, 'display_add_package')
        );
        
        // Edit Customer submenu (hidden)
        add_submenu_page(
            null, // Hidden from menu
            __('Müşteri Düzenle', 'license-manager'),
            __('Müşteri Düzenle', 'license-manager'),
            'manage_license_manager',
            'license-manager-edit-customer',
            array($this, 'display_edit_customer')
        );
        
        // Edit License submenu (hidden)
        add_submenu_page(
            null, // Hidden from menu
            __('Lisans Düzenle', 'license-manager'),
            __('Lisans Düzenle', 'license-manager'),
            'manage_license_manager',
            'license-manager-edit-license',
            array($this, 'display_edit_license')
        );
        
        // Edit Package submenu (hidden)
        add_submenu_page(
            null, // Hidden from menu
            __('Paket Düzenle', 'license-manager'),
            __('Paket Düzenle', 'license-manager'),
            'manage_license_manager',
            'license-manager-edit-package',
            array($this, 'display_edit_package')
        );
        
        // Add Payment submenu (hidden)
        add_submenu_page(
            null, // Hidden from menu
            __('Yeni Ödeme Ekle', 'license-manager'),
            __('Yeni Ödeme Ekle', 'license-manager'),
            'manage_license_manager',
            'license-manager-add-payment',
            array($this, 'display_add_payment')
        );
        
        // Edit Payment submenu (hidden)
        add_submenu_page(
            null, // Hidden from menu
            __('Ödeme Düzenle', 'license-manager'),
            __('Ödeme Düzenle', 'license-manager'),
            'manage_license_manager',
            'license-manager-edit-payment',
            array($this, 'display_edit_payment')
        );
        
        // Add Module submenu (hidden)
        add_submenu_page(
            null, // Hidden from menu
            __('Yeni Modül Ekle', 'license-manager'),
            __('Yeni Modül Ekle', 'license-manager'),
            'manage_license_manager',
            'license-manager-add-module',
            array($this, 'display_add_module')
        );
        
        // Edit Module submenu (hidden)
        add_submenu_page(
            null, // Hidden from menu
            __('Modül Düzenle', 'license-manager'),
            __('Modül Düzenle', 'license-manager'),
            'manage_license_manager',
            'license-manager-edit-module',
            array($this, 'display_edit_module')
        );
        
        // Settings submenu
        add_submenu_page(
            'license-manager',
            __('Ayarlar', 'license-manager'),
            __('Ayarlar', 'license-manager'),
            'manage_license_manager',
            'license-manager-settings',
            array($this, 'display_settings')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'license-manager') === false) {
            return;
        }
        
        wp_enqueue_style(
            'license-manager-admin',
            LICENSE_MANAGER_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            LICENSE_MANAGER_VERSION
        );
        
        wp_enqueue_script(
            'license-manager-admin',
            LICENSE_MANAGER_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            LICENSE_MANAGER_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('license-manager-admin', 'licenseManagerAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('license_manager_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Bu öğeyi silmek istediğinizden emin misiniz?', 'license-manager'),
                'loading' => __('Yükleniyor...', 'license-manager'),
            )
        ));
    }
    
    /**
     * Display dashboard page
     */
    public function display_dashboard() {
        $stats = $this->get_dashboard_stats();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Lisans Yöneticisi Kontrol Paneli', 'license-manager'); ?></h1>
            
            <div class="license-manager-dashboard">
                <!-- Main Statistics Row -->
                <div class="dashboard-stats">
                    <div class="stat-box primary">
                        <h3><?php echo esc_html($stats['total_licenses']); ?></h3>
                        <p><?php _e('Toplam Lisans', 'license-manager'); ?></p>
                    </div>
                    <div class="stat-box success">
                        <h3><?php echo esc_html($stats['active_licenses']); ?></h3>
                        <p><?php _e('Aktif Lisans', 'license-manager'); ?></p>
                    </div>
                    <div class="stat-box warning">
                        <h3><?php echo esc_html($stats['expiring_soon']); ?></h3>
                        <p><?php _e('Yakında Dolacak', 'license-manager'); ?></p>
                    </div>
                    <div class="stat-box danger">
                        <h3><?php echo esc_html($stats['expired_licenses']); ?></h3>
                        <p><?php _e('Süresi Dolmuş', 'license-manager'); ?></p>
                    </div>
                </div>
                
                <!-- Customer Statistics Row -->
                <div class="dashboard-stats">
                    <div class="stat-box info">
                        <h3><?php echo esc_html($stats['total_customers']); ?></h3>
                        <p><?php _e('Toplam Müşteri', 'license-manager'); ?></p>
                    </div>
                    <div class="stat-box success">
                        <h3><?php echo esc_html($stats['licensed_customers']); ?></h3>
                        <p><?php _e('Lisanslı Müşteri', 'license-manager'); ?></p>
                    </div>
                    <div class="stat-box secondary">
                        <h3><?php echo esc_html($stats['unlicensed_customers']); ?></h3>
                        <p><?php _e('Lisansız Müşteri', 'license-manager'); ?></p>
                    </div>
                    <div class="stat-box premium">
                        <h3><?php echo esc_html($stats['lifetime_licenses']); ?></h3>
                        <p><?php _e('Yaşam Boyu Lisans', 'license-manager'); ?></p>
                    </div>
                </div>
                
                <!-- Financial Statistics Row -->
                <div class="dashboard-stats financial">
                    <div class="stat-box revenue">
                        <h3><?php echo esc_html(number_format($stats['total_revenue'], 2)); ?> ₺</h3>
                        <p><?php _e('Toplam Gelir', 'license-manager'); ?></p>
                    </div>
                    <div class="stat-box monthly-revenue">
                        <h3><?php echo esc_html(number_format($stats['monthly_revenue'], 2)); ?> ₺</h3>
                        <p><?php _e('Bu Ay Gelir', 'license-manager'); ?></p>
                    </div>
                    <div class="stat-box average">
                        <h3><?php echo esc_html(number_format($stats['avg_revenue_per_customer'], 2)); ?> ₺</h3>
                        <p><?php _e('Müşteri Başına Ortalama', 'license-manager'); ?></p>
                    </div>
                    <div class="stat-box">
                        <h3><a href="<?php echo admin_url('admin.php?page=license-manager-payments'); ?>" class="dashboard-link"><?php _e('Ödemeler', 'license-manager'); ?></a></h3>
                        <p><?php _e('Ödeme Yönetimi', 'license-manager'); ?></p>
                    </div>
                </div>
                
                <div class="dashboard-content">
                    <div class="dashboard-recent">
                        <div class="recent-licenses">
                            <h3><?php _e('Son Lisanslar', 'license-manager'); ?></h3>
                            <?php $this->display_recent_licenses(); ?>
                        </div>
                        
                        <div class="expiring-licenses">
                            <h3><?php _e('Yakında Süresi Dolacaklar', 'license-manager'); ?></h3>
                            <?php $this->display_expiring_licenses(); ?>
                        </div>
                    </div>
                    
                    <div class="dashboard-payments">
                        <div class="overdue-payments">
                            <h3><?php _e('Gecikmiş Ödemeler', 'license-manager'); ?></h3>
                            <?php $this->display_overdue_payments(); ?>
                        </div>
                        
                        <div class="upcoming-payments">
                            <h3><?php _e('Yaklaşan Ödemeler', 'license-manager'); ?></h3>
                            <?php $this->display_upcoming_payments(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display customers page
     */
    public function display_customers() {
        ?>
        <div class="wrap">
            <h1><?php _e('Müşteriler', 'license-manager'); ?> 
                <a href="<?php echo admin_url('admin.php?page=license-manager-add-customer'); ?>" class="page-title-action">
                    <?php _e('Yeni Müşteri Ekle', 'license-manager'); ?>
                </a>
            </h1>
            
            <?php
            // Show success messages
            if (isset($_GET['added']) && $_GET['added'] == '1') {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Müşteri başarıyla eklendi.', 'license-manager') . '</p></div>';
            }
            if (isset($_GET['updated']) && $_GET['updated'] == '1') {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Müşteri başarıyla güncellendi.', 'license-manager') . '</p></div>';
            }
            if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Müşteri başarıyla silindi.', 'license-manager') . '</p></div>';
            }
            
            // Display customers list
            $customers = get_posts(array(
                'post_type' => 'lm_customer',
                'post_status' => 'publish',
                'numberposts' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ));
            
            if (!empty($customers)) {
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Müşteri Adı', 'license-manager'); ?></th>
                            <th><?php _e('Şirket', 'license-manager'); ?></th>
                            <th><?php _e('E-posta', 'license-manager'); ?></th>
                            <th><?php _e('Telefon', 'license-manager'); ?></th>
                            <th><?php _e('Lisans Sayısı', 'license-manager'); ?></th>
                            <th><?php _e('İşlemler', 'license-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer) : 
                            $contact_person = get_post_meta($customer->ID, '_contact_person', true);
                            $company = get_post_meta($customer->ID, '_company', true);
                            $email = get_post_meta($customer->ID, '_email', true);
                            $phone = get_post_meta($customer->ID, '_phone', true);
                            
                            // Count licenses for this customer
                            $license_count = get_posts(array(
                                'post_type' => 'lm_license',
                                'meta_query' => array(
                                    array(
                                        'key' => '_customer_id',
                                        'value' => $customer->ID,
                                        'compare' => '='
                                    )
                                ),
                                'numberposts' => -1
                            ));
                            $license_count = count($license_count);
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($contact_person ?: $customer->post_title); ?></strong></td>
                            <td><?php echo esc_html($company); ?></td>
                            <td><?php echo esc_html($email); ?></td>
                            <td><?php echo esc_html($phone); ?></td>
                            <td><?php echo esc_html($license_count); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=license-manager-edit-customer&customer_id=' . $customer->ID); ?>" class="button button-small">
                                    <?php _e('Düzenle', 'license-manager'); ?>
                                </a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=license_manager_delete_customer&id=' . $customer->ID), 'delete_customer_' . $customer->ID); ?>" class="button button-small" 
                                   onclick="return confirm('<?php _e('Bu müşteriyi silmek istediğinizden emin misiniz?', 'license-manager'); ?>')">
                                    <?php _e('Sil', 'license-manager'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
            } else {
                ?>
                <p><?php _e('Henüz müşteri bulunmamaktadır.', 'license-manager'); ?></p>
                <?php
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Display licenses page
     */
    public function display_licenses() {
        ?>
        <div class="wrap">
            <h1><?php _e('Lisanslar', 'license-manager'); ?> 
                <a href="<?php echo admin_url('admin.php?page=license-manager-add-license'); ?>" class="page-title-action">
                    <?php _e('Yeni Lisans Ekle', 'license-manager'); ?>
                </a>
            </h1>
            
            <?php
            // Display licenses list
            $licenses = get_posts(array(
                'post_type' => 'lm_license',
                'post_status' => 'publish',
                'numberposts' => -1,
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            if (!empty($licenses)) {
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Lisans Anahtarı', 'license-manager'); ?></th>
                            <th><?php _e('Müşteri', 'license-manager'); ?></th>
                            <th><?php _e('Durum', 'license-manager'); ?></th>
                            <th><?php _e('Geçerlilik Tarihi', 'license-manager'); ?></th>
                            <th><?php _e('Kullanıcı Limiti', 'license-manager'); ?></th>
                            <th><?php _e('İzinli Web Siteleri', 'license-manager'); ?></th>
                            <th><?php _e('İşlemler', 'license-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($licenses as $license) : 
                            $license_key = get_post_meta($license->ID, '_license_key', true);
                            $customer_id = get_post_meta($license->ID, '_customer_id', true);
                            $expires_on = get_post_meta($license->ID, '_expires_on', true);
                            $user_limit = get_post_meta($license->ID, '_user_limit', true);
                            $status = get_post_meta($license->ID, '_status', true);
                            $allowed_domains = get_post_meta($license->ID, '_allowed_domains', true);
                            
                            // Get customer name
                            $customer_name = '';
                            if ($customer_id) {
                                $customer = get_post($customer_id);
                                if ($customer) {
                                    $customer_name = $customer->post_title;
                                }
                            }
                            
                            // Format allowed domains for display
                            $domains_display = '';
                            if (!empty($allowed_domains)) {
                                $domains_array = array_map('trim', explode("\n", $allowed_domains));
                                $domains_array = array_filter($domains_array); // Remove empty lines
                                if (!empty($domains_array)) {
                                    if (count($domains_array) > 3) {
                                        // Show first 3 domains and count of remaining
                                        $domains_display = implode('<br>', array_slice($domains_array, 0, 3));
                                        $remaining = count($domains_array) - 3;
                                        $domains_display .= '<br><small>+' . $remaining . ' ' . __('daha', 'license-manager') . '</small>';
                                    } else {
                                        $domains_display = implode('<br>', $domains_array);
                                    }
                                } else {
                                    $domains_display = '<em>' . __('Belirsiz', 'license-manager') . '</em>';
                                }
                            } else {
                                $domains_display = '<em>' . __('Belirsiz', 'license-manager') . '</em>';
                            }
                            
                            // Format expiry date
                            $expiry_text = '';
                            if ($expires_on) {
                                if ($expires_on === '0000-00-00' || $expires_on === 'lifetime') {
                                    $expiry_text = __('Yaşam Boyu', 'license-manager');
                                } else {
                                    $expiry_text = date_i18n(get_option('date_format'), strtotime($expires_on));
                                }
                            }
                            
                            // Format status with automatic expiry check
                            if (empty($status)) {
                                $status = 'active'; // Default status
                            }
                            
                            // Auto-update expired licenses
                            if ($status === 'active' && !empty($expires_on) && $expires_on !== 'lifetime' && $expires_on !== '0000-00-00') {
                                $expiry_date = strtotime($expires_on);
                                $current_date = current_time('timestamp');
                                
                                if ($current_date > $expiry_date) {
                                    $status = 'expired';
                                    update_post_meta($license->ID, '_status', 'expired');
                                }
                            }
                            
                            $status_text = '';
                            switch ($status) {
                                case 'active':
                                    $status_text = '<span style="color: green;">' . __('Aktif', 'license-manager') . '</span>';
                                    break;
                                case 'expired':
                                    $status_text = '<span style="color: red;">' . __('Süresi Dolmuş', 'license-manager') . '</span>';
                                    break;
                                case 'suspended':
                                    $status_text = '<span style="color: orange;">' . __('Askıya Alınmış', 'license-manager') . '</span>';
                                    break;
                                case 'invalid':
                                    $status_text = '<span style="color: red;">' . __('Geçersiz', 'license-manager') . '</span>';
                                    break;
                                default:
                                    $status_text = __('Belirsiz', 'license-manager');
                            }
                        ?>
                        <tr>
                            <td><code><?php echo esc_html($license_key); ?></code></td>
                            <td><?php echo esc_html($customer_name ?: __('Belirsiz', 'license-manager')); ?></td>
                            <td><?php echo $status_text; ?></td>
                            <td><?php echo esc_html($expiry_text); ?></td>
                            <td><?php echo esc_html($user_limit ?: __('Sınırsız', 'license-manager')); ?></td>
                            <td><?php echo $domains_display; ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=license-manager-edit-license&license_id=' . $license->ID); ?>" class="button button-small">
                                    <?php _e('Düzenle', 'license-manager'); ?>
                                </a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=license_manager_delete_license&id=' . $license->ID), 'delete_license_' . $license->ID); ?>" class="button button-small" 
                                   onclick="return confirm('<?php _e('Bu lisansı silmek istediğinizden emin misiniz?', 'license-manager'); ?>')">
                                    <?php _e('Sil', 'license-manager'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
            } else {
                ?>
                <p><?php _e('Henüz lisans bulunmamaktadır.', 'license-manager'); ?></p>
                <?php
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Display packages page
     */
    public function display_packages() {
        ?>
        <div class="wrap">
            <h1><?php _e('Lisans Paketleri', 'license-manager'); ?> 
                <a href="<?php echo admin_url('admin.php?page=license-manager-add-package'); ?>" class="page-title-action">
                    <?php _e('Yeni Paket Ekle', 'license-manager'); ?>
                </a>
            </h1>
            
            <?php
            // Display packages list
            $packages = get_posts(array(
                'post_type' => 'lm_license_package',
                'post_status' => 'publish',
                'numberposts' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ));
            
            if (!empty($packages)) {
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Paket Adı', 'license-manager'); ?></th>
                            <th><?php _e('Süre', 'license-manager'); ?></th>
                            <th><?php _e('Kullanıcı Limiti', 'license-manager'); ?></th>
                            <th><?php _e('Modüller', 'license-manager'); ?></th>
                            <th><?php _e('Fiyat', 'license-manager'); ?></th>
                            <th><?php _e('İşlemler', 'license-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packages as $package) : 
                            $duration = get_post_meta($package->ID, '_duration', true);
                            $user_limit = get_post_meta($package->ID, '_user_limit', true);
                            $price = get_post_meta($package->ID, '_price', true);
                            
                            // Get modules from taxonomy
                            $modules = wp_get_post_terms($package->ID, 'lm_modules');
                            $module_names = array();
                            if (!is_wp_error($modules) && !empty($modules)) {
                                foreach ($modules as $module) {
                                    $module_names[] = $module->name;
                                }
                            }
                            
                            // Format duration
                            $duration_text = '';
                            if ($duration == 'lifetime') {
                                $duration_text = __('Yaşam Boyu', 'license-manager');
                            } elseif ($duration == 'monthly') {
                                $duration_text = __('Aylık', 'license-manager');
                            } elseif ($duration == 'yearly') {
                                $duration_text = __('Yıllık', 'license-manager');
                            } else {
                                $duration_text = esc_html($duration);
                            }
                            
                            // Format modules
                            $modules_text = '';
                            if (!empty($module_names)) {
                                $modules_text = implode(', ', $module_names);
                            } else {
                                $modules_text = __('Modül yok', 'license-manager');
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($package->post_title); ?></strong></td>
                            <td><?php echo esc_html($duration_text); ?></td>
                            <td><?php echo esc_html($user_limit ?: __('Sınırsız', 'license-manager')); ?></td>
                            <td><?php echo esc_html($modules_text); ?></td>
                            <td><?php echo esc_html($price ? $price . ' TL' : __('Belirsiz', 'license-manager')); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=license-manager-edit-package&package_id=' . $package->ID); ?>" class="button button-small">
                                    <?php _e('Düzenle', 'license-manager'); ?>
                                </a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=license_manager_delete_package&id=' . $package->ID), 'delete_package_' . $package->ID); ?>" class="button button-small" 
                                   onclick="return confirm('<?php _e('Bu paketi silmek istediğinizden emin misiniz?', 'license-manager'); ?>')">
                                    <?php _e('Sil', 'license-manager'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
            } else {
                ?>
                <p><?php _e('Henüz lisans paketi bulunmamaktadır.', 'license-manager'); ?></p>
                <?php
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Display payments page
     */
    public function display_payments() {
        ?>
        <div class="wrap">
            <h1><?php _e('Ödeme Yönetimi', 'license-manager'); ?>
            <a href="<?php echo admin_url('admin.php?page=license-manager-add-payment'); ?>" class="page-title-action"><?php _e('Yeni Ödeme Ekle', 'license-manager'); ?></a>
            </h1>
            
            <?php
            // Display success and error messages
            if (isset($_GET['message'])) {
                $message_type = sanitize_text_field($_GET['message']);
                switch ($message_type) {
                    case 'reminder_sent':
                        echo '<div class="notice notice-success is-dismissible"><p>' . __('Hatırlatma e-postası başarıyla gönderildi.', 'license-manager') . '</p></div>';
                        break;
                    case 'reminder_error':
                        echo '<div class="notice notice-error is-dismissible"><p>' . __('Hatırlatma e-postası gönderilemedi. Lütfen müşteri e-posta adresini kontrol edin.', 'license-manager') . '</p></div>';
                        break;
                    case 'payment_added':
                        echo '<div class="notice notice-success is-dismissible"><p>' . __('Ödeme başarıyla eklendi.', 'license-manager') . '</p></div>';
                        break;
                    case 'payment_updated':
                        echo '<div class="notice notice-success is-dismissible"><p>' . __('Ödeme başarıyla güncellendi.', 'license-manager') . '</p></div>';
                        break;
                    case 'payment_deleted':
                        echo '<div class="notice notice-success is-dismissible"><p>' . __('Ödeme başarıyla silindi.', 'license-manager') . '</p></div>';
                        break;
                }
            }
            ?>
            
            <div class="payment-dashboard">
                <div class="payment-stats">
                    <?php
                    $stats = $this->get_dashboard_stats();
                    ?>
                    <div class="stat-box">
                        <h3><?php echo esc_html(number_format($stats['total_revenue'], 2)); ?> ₺</h3>
                        <p><?php _e('Toplam Gelir', 'license-manager'); ?></p>
                    </div>
                    <div class="stat-box">
                        <h3><?php echo esc_html(number_format($stats['monthly_revenue'], 2)); ?> ₺</h3>
                        <p><?php _e('Bu Ay Gelir', 'license-manager'); ?></p>
                    </div>
                    <div class="stat-box">
                        <h3><?php echo esc_html($stats['expired_licenses']); ?></h3>
                        <p><?php _e('Gecikmiş Ödemeler', 'license-manager'); ?></p>
                    </div>
                    <div class="stat-box">
                        <h3><?php echo esc_html($stats['expiring_soon']); ?></h3>
                        <p><?php _e('Yaklaşan Ödemeler', 'license-manager'); ?></p>
                    </div>
                </div>
                
                <div class="payment-tabs">
                    <div class="tab-buttons">
                        <button class="tab-button active" data-tab="payments"><?php _e('Ödeme Kayıtları', 'license-manager'); ?></button>
                        <button class="tab-button" data-tab="overdue"><?php _e('Gecikmiş Ödemeler', 'license-manager'); ?></button>
                        <button class="tab-button" data-tab="upcoming"><?php _e('Yaklaşan Ödemeler', 'license-manager'); ?></button>
                        <button class="tab-button" data-tab="history"><?php _e('Ödeme Geçmişi', 'license-manager'); ?></button>
                        <button class="tab-button" data-tab="customers"><?php _e('Müşteri CRM', 'license-manager'); ?></button>
                    </div>
                    
                    <div class="tab-content" id="payments" style="display: block;">
                        <h3><?php _e('Ödeme Kayıtları', 'license-manager'); ?></h3>
                        <?php $this->display_payment_records(); ?>
                    </div>
                    
                    <div class="tab-content" id="overdue" style="display: none;">
                        <h3><?php _e('Gecikmiş Ödemeler', 'license-manager'); ?></h3>
                        <?php $this->display_detailed_overdue_payments(); ?>
                    </div>
                    
                    <div class="tab-content" id="upcoming" style="display: none;">
                        <h3><?php _e('Yaklaşan Ödemeler (30 Gün)', 'license-manager'); ?></h3>
                        <?php $this->display_detailed_upcoming_payments(); ?>
                    </div>
                    
                    <div class="tab-content" id="history" style="display: none;">
                        <h3><?php _e('Ödeme Geçmişi', 'license-manager'); ?></h3>
                        <?php $this->display_payment_history(); ?>
                    </div>
                    
                    <div class="tab-content" id="customers" style="display: none;">
                        <h3><?php _e('Müşteri CRM Sistemi', 'license-manager'); ?></h3>
                        <?php $this->display_customer_crm(); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.tab-button').click(function() {
                var tabId = $(this).data('tab');
                
                $('.tab-button').removeClass('active');
                $(this).addClass('active');
                
                $('.tab-content').hide();
                $('#' + tabId).show();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Display modules management page
     */
    public function display_modules() {
        $modules_manager = new License_Manager_Modules();
        
        // Handle actions
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        
        if ($action === 'add') {
            $this->display_add_module();
            return;
        } elseif ($action === 'edit' && isset($_GET['id'])) {
            $this->display_edit_module();
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Modül Yönetimi', 'license-manager'); ?>
            <a href="<?php echo admin_url('admin.php?page=license-manager-modules&action=add'); ?>" class="page-title-action"><?php _e('Yeni Modül Ekle', 'license-manager'); ?></a>
            </h1>
            
            <?php
            // Display messages
            if (isset($_GET['message'])) {
                $message_type = sanitize_text_field($_GET['message']);
                switch ($message_type) {
                    case 'module_added':
                        echo '<div class="notice notice-success is-dismissible"><p>' . __('Modül başarıyla eklendi.', 'license-manager') . '</p></div>';
                        break;
                    case 'module_updated':
                        echo '<div class="notice notice-success is-dismissible"><p>' . __('Modül başarıyla güncellendi.', 'license-manager') . '</p></div>';
                        break;
                    case 'module_deleted':
                        echo '<div class="notice notice-success is-dismissible"><p>' . __('Modül başarıyla silindi.', 'license-manager') . '</p></div>';
                        break;
                }
            }
            
            if (isset($_GET['error'])) {
                $error_type = sanitize_text_field($_GET['error']);
                switch ($error_type) {
                    case 'missing_fields':
                        echo '<div class="notice notice-error is-dismissible"><p>' . __('Lütfen gerekli alanları doldurun.', 'license-manager') . '</p></div>';
                        break;
                    case 'invalid_view_parameter':
                        echo '<div class="notice notice-error is-dismissible"><p>' . __('View parametresi geçersiz. Sadece harfler, rakamlar ve tire kullanın.', 'license-manager') . '</p></div>';
                        break;
                    case 'module_exists':
                        echo '<div class="notice notice-error is-dismissible"><p>' . __('Bu modül zaten mevcut.', 'license-manager') . '</p></div>';
                        break;
                    case 'module_not_found':
                        echo '<div class="notice notice-error is-dismissible"><p>' . __('Modül bulunamadı.', 'license-manager') . '</p></div>';
                        break;
                }
            }
            ?>
            
            <div class="modules-list">
                <?php $this->display_modules_table($modules_manager); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display modules table
     */
    private function display_modules_table($modules_manager) {
        $modules = $modules_manager->get_modules();
        $categories = $modules_manager->get_module_categories();
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php _e('Modül Adı', 'license-manager'); ?></th>
                    <th scope="col"><?php _e('Slug', 'license-manager'); ?></th>
                    <th scope="col"><?php _e('View Parametresi', 'license-manager'); ?></th>
                    <th scope="col"><?php _e('Kategori', 'license-manager'); ?></th>
                    <th scope="col"><?php _e('Açıklama', 'license-manager'); ?></th>
                    <th scope="col"><?php _e('İşlemler', 'license-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($modules)) : ?>
                    <?php foreach ($modules as $module) : ?>
                        <tr>
                            <td data-label="<?php _e('Modül Adı', 'license-manager'); ?>"><strong><?php echo esc_html($module->name); ?></strong></td>
                            <td data-label="<?php _e('Slug', 'license-manager'); ?>"><code><?php echo esc_html($module->slug); ?></code></td>
                            <td data-label="<?php _e('View Parametresi', 'license-manager'); ?>">
                                <?php if (!empty($module->view_parameter)) : ?>
                                    <code>?view=<?php echo esc_html($module->view_parameter); ?></code>
                                <?php else : ?>
                                    <span class="description"><?php _e('Yok', 'license-manager'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?php _e('Kategori', 'license-manager'); ?>">
                                <?php 
                                $category_name = isset($categories[$module->category]) ? $categories[$module->category] : $module->category;
                                $category_class = 'module-category-' . esc_attr($module->category);
                                ?>
                                <span class="<?php echo $category_class; ?>"><?php echo esc_html($category_name); ?></span>
                            </td>
                            <td data-label="<?php _e('Açıklama', 'license-manager'); ?>"><?php echo esc_html($module->description); ?></td>
                            <td data-label="<?php _e('İşlemler', 'license-manager'); ?>">
                                <a href="<?php echo admin_url('admin.php?page=license-manager-modules&action=edit&id=' . $module->term_id); ?>" class="button button-small"><?php _e('Düzenle', 'license-manager'); ?></a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=license_manager_delete_module&id=' . $module->term_id), 'license_manager_delete_module_' . $module->term_id); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php _e('Bu modülü silmek istediğinizden emin misiniz?', 'license-manager'); ?>')"><?php _e('Sil', 'license-manager'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6"><?php _e('Henüz modül bulunmamaktadır.', 'license-manager'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Display add module page
     */
    public function display_add_module() {
        $modules_manager = new License_Manager_Modules();
        $categories = $modules_manager->get_module_categories();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Yeni Modül Ekle', 'license-manager'); ?>
            <a href="<?php echo admin_url('admin.php?page=license-manager-modules'); ?>" class="page-title-action"><?php _e('Modüllere Dön', 'license-manager'); ?></a>
            </h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="license_manager_add_module" />
                <?php wp_nonce_field('license_manager_add_module'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="name"><?php _e('Modül Adı', 'license-manager'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="name" name="name" class="regular-text" required />
                            <p class="description"><?php _e('Modülün görüntülenecek adı', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="slug"><?php _e('Slug', 'license-manager'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="slug" name="slug" class="regular-text" required />
                            <p class="description"><?php _e('Modülün benzersiz tanımlayıcısı (örn: sales-opportunities)', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="view_parameter"><?php _e('View Parametresi', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="view_parameter" name="view_parameter" class="regular-text" />
                            <p class="description"><?php _e('URL\'de kullanılacak view parametresi (örn: sales-opportunities → ?view=sales-opportunities)', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="category"><?php _e('Kategori', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <select id="category" name="category">
                                <option value=""><?php _e('Kategori Seçin', 'license-manager'); ?></option>
                                <?php foreach ($categories as $slug => $name) : ?>
                                    <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="description"><?php _e('Açıklama', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <textarea id="description" name="description" rows="3" class="large-text"></textarea>
                            <p class="description"><?php _e('Modülün açıklaması', 'license-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Modül Ekle', 'license-manager')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Display edit module page
     */
    public function display_edit_module() {
        $modules_manager = new License_Manager_Modules();
        $term_id = intval($_GET['id']);
        $module = $modules_manager->get_module($term_id);
        
        if (!$module) {
            wp_die(__('Modül bulunamadı.', 'license-manager'));
        }
        
        $categories = $modules_manager->get_module_categories();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Modül Düzenle', 'license-manager'); ?>
            <a href="<?php echo admin_url('admin.php?page=license-manager-modules'); ?>" class="page-title-action"><?php _e('Modüllere Dön', 'license-manager'); ?></a>
            </h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="license_manager_edit_module" />
                <input type="hidden" name="term_id" value="<?php echo esc_attr($term_id); ?>" />
                <?php wp_nonce_field('license_manager_edit_module'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="name"><?php _e('Modül Adı', 'license-manager'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="name" name="name" class="regular-text" value="<?php echo esc_attr($module->name); ?>" required />
                            <p class="description"><?php _e('Modülün görüntülenecek adı', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="slug"><?php _e('Slug', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="slug" name="slug" class="regular-text" value="<?php echo esc_attr($module->slug); ?>" readonly />
                            <p class="description"><?php _e('Slug değiştirilemez.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="view_parameter"><?php _e('View Parametresi', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="view_parameter" name="view_parameter" class="regular-text" value="<?php echo esc_attr($module->view_parameter); ?>" />
                            <p class="description"><?php _e('URL\'de kullanılacak view parametresi (örn: sales-opportunities → ?view=sales-opportunities)', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="category"><?php _e('Kategori', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <select id="category" name="category">
                                <option value=""><?php _e('Kategori Seçin', 'license-manager'); ?></option>
                                <?php foreach ($categories as $slug => $name) : ?>
                                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($module->category, $slug); ?>><?php echo esc_html($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="description"><?php _e('Açıklama', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <textarea id="description" name="description" rows="3" class="large-text"><?php echo esc_textarea($module->description); ?></textarea>
                            <p class="description"><?php _e('Modülün açıklaması', 'license-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Modül Güncelle', 'license-manager')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Display settings page
     */
    public function display_settings() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'license_manager_settings')) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>' . __('Ayarlar kaydedildi.', 'license-manager') . '</p></div>';
        }
        
        $debug_mode = get_option('license_manager_debug_mode', false);
        $default_duration = get_option('license_manager_default_license_duration', 30);
        $grace_period = get_option('license_manager_grace_period', 7);
        $default_user_limit = get_option('license_manager_default_user_limit', 5);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Lisans Yöneticisi Ayarları', 'license-manager'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('license_manager_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Hata Ayıklama Modu', 'license-manager'); ?></th>
                        <td>
                            <input type="checkbox" name="debug_mode" value="1" <?php checked($debug_mode); ?> />
                            <p class="description"><?php _e('Detaylı günlük kaydı için hata ayıklama modunu etkinleştir.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Varsayılan Lisans Süresi (gün)', 'license-manager'); ?></th>
                        <td>
                            <input type="number" name="default_duration" value="<?php echo esc_attr($default_duration); ?>" min="1" />
                            <p class="description"><?php _e('Yeni lisanslar için varsayılan süre (gün cinsinden).', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Ek Süre (gün)', 'license-manager'); ?></th>
                        <td>
                            <input type="number" name="grace_period" value="<?php echo esc_attr($grace_period); ?>" min="0" />
                            <p class="description"><?php _e('Lisans süresi dolduktan sonra ek kullanım süresi.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Varsayılan Kullanıcı Limiti', 'license-manager'); ?></th>
                        <td>
                            <input type="number" name="default_user_limit" value="<?php echo esc_attr($default_user_limit); ?>" min="1" />
                            <p class="description"><?php _e('Yeni lisanslar için varsayılan kullanıcı sayısı limiti.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Değişiklikleri Kaydet', 'license-manager')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        update_option('license_manager_debug_mode', isset($_POST['debug_mode']));
        update_option('license_manager_default_license_duration', intval($_POST['default_duration']));
        update_option('license_manager_grace_period', intval($_POST['grace_period']));
        update_option('license_manager_default_user_limit', intval($_POST['default_user_limit']));
    }
    
    /**
     * Add dashboard widgets
     */
    public function add_dashboard_widgets() {
        wp_add_dashboard_widget(
            'license_manager_overview',
            __('Lisans Yöneticisi Özeti', 'license-manager'),
            array($this, 'display_dashboard_widget')
        );
    }
    
    /**
     * Display dashboard widget
     */
    public function display_dashboard_widget() {
        $stats = $this->get_dashboard_stats();
        
        ?>
        <div class="license-manager-widget">
            <p><strong><?php _e('Toplam Lisans:', 'license-manager'); ?></strong> <?php echo esc_html($stats['total_licenses']); ?></p>
            <p><strong><?php _e('Aktif Lisans:', 'license-manager'); ?></strong> <?php echo esc_html($stats['active_licenses']); ?></p>
            <p><strong><?php _e('Süresi Dolmuş:', 'license-manager'); ?></strong> <?php echo esc_html($stats['expired_licenses']); ?></p>
            <p><strong><?php _e('Yakında Dolacak:', 'license-manager'); ?></strong> <?php echo esc_html($stats['expiring_soon']); ?></p>
            <p><strong><?php _e('Toplam Müşteri:', 'license-manager'); ?></strong> <?php echo esc_html($stats['total_customers']); ?></p>
            <p><strong><?php _e('Bu Ay Gelir:', 'license-manager'); ?></strong> <?php echo esc_html(number_format($stats['monthly_revenue'], 2)); ?> ₺</p>
            <p><a href="<?php echo admin_url('admin.php?page=license-manager'); ?>" class="button"><?php _e('Kontrol Paneli', 'license-manager'); ?></a></p>
            <p><a href="<?php echo admin_url('admin.php?page=license-manager-payments'); ?>" class="button"><?php _e('Ödeme Yönetimi', 'license-manager'); ?></a></p>
        </div>
        <?php
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        $current_date = current_time('Y-m-d');
        
        // Get total licenses (published posts only)
        $total_licenses = wp_count_posts('lm_license');
        $total_licenses = isset($total_licenses->publish) ? $total_licenses->publish : 0;
        
        // Get all published licenses for detailed analysis
        $all_licenses = get_posts(array(
            'post_type' => 'lm_license',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        $active_licenses_count = 0;
        $expired_licenses_count = 0;
        $expiring_soon_count = 0;
        $lifetime_licenses_count = 0;
        $customers_with_licenses = array();
        
        $next_week = date('Y-m-d', strtotime('+7 days'));
        
        foreach ($all_licenses as $license_id) {
            $status = get_post_meta($license_id, '_status', true);
            $expires_on = get_post_meta($license_id, '_expires_on', true);
            $customer_id = get_post_meta($license_id, '_customer_id', true);
            
            // Track customers with licenses
            if ($customer_id) {
                $customers_with_licenses[] = $customer_id;
            }
            
            // Check license status
            if ($status === 'active' || empty($status)) {
                if (!empty($expires_on) && $expires_on !== 'lifetime' && $expires_on !== '0000-00-00') {
                    if ($expires_on < $current_date) {
                        $expired_licenses_count++;
                    } else if ($expires_on <= $next_week) {
                        $expiring_soon_count++;
                        $active_licenses_count++;
                    } else {
                        $active_licenses_count++;
                    }
                } else {
                    // Lifetime license
                    $lifetime_licenses_count++;
                    $active_licenses_count++;
                }
            } else if ($status === 'expired') {
                $expired_licenses_count++;
            }
        }
        
        // Get real payment data
        $all_payments = get_posts(array(
            'post_type' => 'lm_payment',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        $total_revenue = 0;
        $monthly_revenue = 0;
        $this_month = date('Y-m');
        
        foreach ($all_payments as $payment_id) {
            $amount = get_post_meta($payment_id, '_amount', true);
            $payment_date = get_post_meta($payment_id, '_payment_date', true);
            
            // Check if payment is completed
            $status_terms = wp_get_post_terms($payment_id, 'lm_payment_status');
            $is_completed = false;
            if (!empty($status_terms) && !is_wp_error($status_terms)) {
                $is_completed = ($status_terms[0]->slug === 'completed');
            }
            
            if ($amount && is_numeric($amount) && $is_completed) {
                $total_revenue += floatval($amount);
                
                // Check if payment was this month
                if ($payment_date) {
                    $payment_month = date('Y-m', strtotime($payment_date));
                    if ($payment_month === $this_month) {
                        $monthly_revenue += floatval($amount);
                    }
                } else {
                    // Fall back to post date if payment date not set
                    $post_date = get_the_date('Y-m', $payment_id);
                    if ($post_date === $this_month) {
                        $monthly_revenue += floatval($amount);
                    }
                }
            }
        }
        
        // Get total customers (fix the count issue)
        $customers_query = get_posts(array(
            'post_type' => 'lm_customer',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        $total_customers = count($customers_query);
        
        // Count customers with active licenses
        $licensed_customers_count = count(array_unique($customers_with_licenses));
        
        // Calculate average revenue per customer
        $avg_revenue_per_customer = $licensed_customers_count > 0 ? $total_revenue / $licensed_customers_count : 0;
        
        return array(
            'total_licenses' => $total_licenses,
            'active_licenses' => $active_licenses_count,
            'expired_licenses' => $expired_licenses_count,
            'expiring_soon' => $expiring_soon_count,
            'lifetime_licenses' => $lifetime_licenses_count,
            'total_customers' => $total_customers,
            'licensed_customers' => $licensed_customers_count,
            'unlicensed_customers' => $total_customers - $licensed_customers_count,
            'total_revenue' => $total_revenue,
            'monthly_revenue' => $monthly_revenue,
            'avg_revenue_per_customer' => $avg_revenue_per_customer,
            'total_payments' => count($all_payments),
        );
    }
    
    /**
     * Display recent licenses
     */
    private function display_recent_licenses() {
        $recent_licenses = get_posts(array(
            'post_type' => 'lm_license',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($recent_licenses)) {
            echo '<p>' . __('Lisans bulunamadı.', 'license-manager') . '</p>';
            return;
        }
        
        echo '<ul>';
        foreach ($recent_licenses as $license) {
            $license_key = get_post_meta($license->ID, '_license_key', true);
            $customer_id = get_post_meta($license->ID, '_customer_id', true);
            $customer_name = $customer_id ? get_the_title($customer_id) : __('Bilinmeyen Müşteri', 'license-manager');
            
            echo '<li>';
            echo '<strong>' . esc_html(substr($license_key, 0, 10) . '...') . '</strong> - ';
            echo esc_html($customer_name);
            echo ' <small>(' . date(get_option('date_format'), strtotime($license->post_date)) . ')</small>';
            echo '</li>';
        }
        echo '</ul>';
    }
    
    /**
     * Display expiring licenses
     */
    private function display_expiring_licenses() {
        // Get licenses expiring in the next 30 days
        $expiring_licenses = get_posts(array(
            'post_type' => 'lm_license',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_expires_on',
                    'value' => array(
                        date('Y-m-d'),
                        date('Y-m-d', strtotime('+30 days'))
                    ),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                )
            ),
            'posts_per_page' => 5,
            'orderby' => 'meta_value',
            'meta_key' => '_expires_on',
            'order' => 'ASC'
        ));
        
        if (empty($expiring_licenses)) {
            echo '<p>' . __('Yakında süresi dolacak lisans bulunamadı.', 'license-manager') . '</p>';
            return;
        }
        
        echo '<ul>';
        foreach ($expiring_licenses as $license) {
            $license_key = get_post_meta($license->ID, '_license_key', true);
            $expires_on = get_post_meta($license->ID, '_expires_on', true);
            $customer_id = get_post_meta($license->ID, '_customer_id', true);
            $customer_name = $customer_id ? get_the_title($customer_id) : __('Bilinmeyen Müşteri', 'license-manager');
            
            echo '<li>';
            echo '<strong>' . esc_html(substr($license_key, 0, 10) . '...') . '</strong> - ';
            echo esc_html($customer_name);
            echo ' <small>(' . __('süresi doluyor', 'license-manager') . ' ' . date(get_option('date_format'), strtotime($expires_on)) . ')</small>';
        }
        echo '</ul>';
    }
    
    /**
     * Display add customer form
     */
    public function display_add_customer() {
        ?>
        <div class="wrap">
            <h1><?php _e('Yeni Müşteri Ekle', 'license-manager'); ?></h1>
            
            <?php
            // Display success message if customer was added
            if (isset($_GET['added']) && $_GET['added'] == '1') {
                echo '<div class="notice notice-success"><p>' . __('Müşteri başarıyla eklendi.', 'license-manager') . '</p></div>';
            }
            ?>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="license_manager_add_customer" />
                <?php wp_nonce_field('license_manager_add_customer', 'license_manager_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="customer_name"><?php _e('Müşteri Adı', 'license-manager'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="customer_name" name="customer_name" class="regular-text" required />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="company"><?php _e('Şirket', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="company" name="company" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="contact_person"><?php _e('İletişim Kişisi', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="contact_person" name="contact_person" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="email"><?php _e('E-posta', 'license-manager'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="email" id="email" name="email" class="regular-text" required />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="phone"><?php _e('Telefon', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="phone" name="phone" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="website"><?php _e('Web Sitesi', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="website" name="website" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="allowed_domains"><?php _e('İzin Verilen Domain\'ler', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <textarea id="allowed_domains" name="allowed_domains" class="large-text" rows="3" placeholder="<?php _e('Her satıra bir domain yazın (örn: example.com)', 'license-manager'); ?>"></textarea>
                            <p class="description"><?php _e('Bu müşterinin lisanslarını kullanabileceği domain\'leri belirtin.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="notes"><?php _e('Notlar', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <textarea id="notes" name="notes" class="large-text" rows="4"></textarea>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Müşteri Ekle', 'license-manager')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Display add license form
     */
    public function display_add_license() {
        // Get customers for dropdown
        $customers = get_posts(array(
            'post_type' => 'lm_customer',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        // Get packages for dropdown
        $packages = get_posts(array(
            'post_type' => 'lm_license_package',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        // Get modules - ensure they exist with better error handling
        $database = new License_Manager_Database();
        $modules = $database->get_available_modules();
        
        // If no modules found, force create them and try again
        if (empty($modules)) {
            $database->force_create_default_modules();
            $modules = $database->get_available_modules();
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Yeni Lisans Ekle', 'license-manager'); ?></h1>
            
            <?php
            // Display success message if license was added
            if (isset($_GET['added']) && $_GET['added'] == '1') {
                echo '<div class="notice notice-success"><p>' . __('Lisans başarıyla eklendi.', 'license-manager') . '</p></div>';
            }
            ?>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="license_manager_add_license" />
                <?php wp_nonce_field('license_manager_add_license', 'license_manager_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="customer_id"><?php _e('Müşteri', 'license-manager'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <select id="customer_id" name="customer_id" required>
                                <option value=""><?php _e('Müşteri Seçin', 'license-manager'); ?></option>
                                <?php foreach ($customers as $customer) : ?>
                                    <option value="<?php echo esc_attr($customer->ID); ?>">
                                        <?php echo esc_html($customer->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="package_id"><?php _e('Lisans Paketi', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <select id="package_id" name="package_id">
                                <option value=""><?php _e('Paket Seçin (İsteğe Bağlı)', 'license-manager'); ?></option>
                                <?php foreach ($packages as $package) : ?>
                                    <option value="<?php echo esc_attr($package->ID); ?>">
                                        <?php echo esc_html($package->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Paket seçerseniz, paket ayarları otomatik olarak uygulanır.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="license_type"><?php _e('Lisans Türü', 'license-manager'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <select id="license_type" name="license_type" required>
                                <option value="monthly"><?php _e('Aylık', 'license-manager'); ?></option>
                                <option value="yearly"><?php _e('Yıllık', 'license-manager'); ?></option>
                                <option value="lifetime"><?php _e('Yaşam Boyu', 'license-manager'); ?></option>
                                <option value="trial"><?php _e('Deneme', 'license-manager'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="expires_on"><?php _e('Geçerlilik Tarihi', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="date" id="expires_on" name="expires_on" />
                            <p class="description"><?php _e('Boş bırakırsanız lisans türüne göre otomatik hesaplanır.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="user_limit"><?php _e('Kullanıcı Limiti', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="user_limit" name="user_limit" min="1" placeholder="<?php echo esc_attr(get_option('license_manager_default_user_limit', 5)); ?>" />
                            <p class="description"><?php _e('Boş bırakırsanız varsayılan limit kullanılır.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="allowed_domains"><?php _e('İzin Verilen Domain\'ler', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <textarea id="allowed_domains" name="allowed_domains" class="large-text" rows="3" placeholder="<?php _e('Her satıra bir domain yazın (örn: example.com)', 'license-manager'); ?>"></textarea>
                            <p class="description"><?php _e('Bu lisansın kullanılabileceği domain\'leri belirtin.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <?php if (!empty($modules)) : ?>
                    <tr>
                        <th scope="row">
                            <label><?php _e('Modüller', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <?php foreach ($modules as $module) : ?>
                                <label>
                                    <input type="checkbox" name="modules[]" value="<?php echo esc_attr($module->slug); ?>" />
                                    <?php echo esc_html($module->name); ?>
                                </label><br />
                            <?php endforeach; ?>
                            <p class="description"><?php _e('Bu lisansla erişilebilecek modülleri seçin.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <?php submit_button(__('Lisans Ekle', 'license-manager')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Display add package form
     */
    public function display_add_package() {
        // Get modules - ensure they exist with better error handling
        $database = new License_Manager_Database();
        $modules = $database->get_available_modules();
        
        // If no modules found, force create them and try again
        if (empty($modules)) {
            $database->force_create_default_modules();
            $modules = $database->get_available_modules();
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Yeni Lisans Paketi Ekle', 'license-manager'); ?></h1>
            
            <?php
            // Display success message if package was added
            if (isset($_GET['added']) && $_GET['added'] == '1') {
                echo '<div class="notice notice-success"><p>' . __('Lisans paketi başarıyla eklendi.', 'license-manager') . '</p></div>';
            }
            ?>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="license_manager_add_package" />
                <?php wp_nonce_field('license_manager_add_package', 'license_manager_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="package_name"><?php _e('Paket Adı', 'license-manager'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="package_name" name="package_name" class="regular-text" required />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="description"><?php _e('Açıklama', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <textarea id="description" name="description" class="large-text" rows="4"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="duration"><?php _e('Süre', 'license-manager'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <select id="duration" name="duration" required>
                                <option value="monthly"><?php _e('Aylık', 'license-manager'); ?></option>
                                <option value="yearly"><?php _e('Yıllık', 'license-manager'); ?></option>
                                <option value="lifetime"><?php _e('Yaşam Boyu', 'license-manager'); ?></option>
                                <option value="trial"><?php _e('Deneme', 'license-manager'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="user_limit"><?php _e('Kullanıcı Limiti', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="user_limit" name="user_limit" min="1" placeholder="<?php echo esc_attr(get_option('license_manager_default_user_limit', 5)); ?>" />
                            <p class="description"><?php _e('Boş bırakırsanız varsayılan limit kullanılır.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="price"><?php _e('Fiyat (TL)', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="price" name="price" min="0" step="0.01" />
                        </td>
                    </tr>
                    <?php if (!empty($modules)) : ?>
                    <tr>
                        <th scope="row">
                            <label><?php _e('İçerilen Modüller', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <?php foreach ($modules as $module) : ?>
                                <label>
                                    <input type="checkbox" name="modules[]" value="<?php echo esc_attr($module->slug); ?>" />
                                    <?php echo esc_html($module->name); ?>
                                </label><br />
                            <?php endforeach; ?>
                            <p class="description"><?php _e('Bu pakette yer alan modülleri seçin.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <?php submit_button(__('Paket Ekle', 'license-manager')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Handle add customer form submission
     */
    public function handle_add_customer() {
        // Check nonce and capabilities
        if (!wp_verify_nonce($_POST['license_manager_nonce'], 'license_manager_add_customer') ||
            !current_user_can('manage_license_manager')) {
            wp_die(__('Yetkisiz erişim.', 'license-manager'));
        }
        
        // Sanitize input data
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $company = sanitize_text_field($_POST['company']);
        $contact_person = sanitize_text_field($_POST['contact_person']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $website = $this->sanitize_website_url($_POST['website']);
        $allowed_domains = sanitize_textarea_field($_POST['allowed_domains']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        // Validate required fields
        if (empty($customer_name)) {
            wp_die(__('Müşteri adı gereklidir.', 'license-manager'));
        }
        
        if (empty($email) || !is_email($email)) {
            wp_die(__('Geçerli bir e-posta adresi gereklidir.', 'license-manager'));
        }
        
        // Create customer post
        $customer_id = wp_insert_post(array(
            'post_title' => $customer_name,
            'post_content' => $notes,
            'post_type' => 'lm_customer',
            'post_status' => 'publish',
            'meta_input' => array(
                '_company' => $company,
                '_contact_person' => $contact_person,
                '_email' => $email,
                '_phone' => $phone,
                '_website' => $website,
                '_allowed_domains' => $allowed_domains,
            )
        ));
        
        if (is_wp_error($customer_id)) {
            wp_die(__('Müşteri eklenirken hata oluştu.', 'license-manager'));
        }
        
        // Redirect with success message
        wp_redirect(admin_url('admin.php?page=license-manager-customers&added=1'));
        exit;
    }
    
    /**
     * Handle add license form submission
     */
    public function handle_add_license() {
        // Check nonce and capabilities
        if (!wp_verify_nonce($_POST['license_manager_nonce'], 'license_manager_add_license') ||
            !current_user_can('manage_license_manager')) {
            wp_die(__('Yetkisiz erişim.', 'license-manager'));
        }
        
        // Sanitize input data
        $customer_id = intval($_POST['customer_id']);
        $package_id = !empty($_POST['package_id']) ? intval($_POST['package_id']) : 0;
        $license_type = sanitize_text_field($_POST['license_type']);
        $expires_on = sanitize_text_field($_POST['expires_on']);
        $user_limit = !empty($_POST['user_limit']) ? intval($_POST['user_limit']) : get_option('license_manager_default_user_limit', 5);
        $allowed_domains = sanitize_textarea_field($_POST['allowed_domains']);
        $modules = isset($_POST['modules']) ? array_map('sanitize_text_field', $_POST['modules']) : array();
        
        // Validate required fields
        if (empty($customer_id)) {
            wp_die(__('Müşteri seçimi gereklidir.', 'license-manager'));
        }
        
        // Generate license key
        $license_key = $this->generate_license_key();
        
        // Calculate expiry date if not provided
        if (empty($expires_on)) {
            $expires_on = $this->calculate_expiry_date($license_type);
        }
        
        // Create license post
        $license_id = wp_insert_post(array(
            'post_title' => 'Lisans: ' . $license_key,
            'post_type' => 'lm_license',
            'post_status' => 'publish',
            'meta_input' => array(
                '_license_key' => $license_key,
                '_customer_id' => $customer_id,
                '_license_type' => $license_type,
                '_expires_on' => $expires_on,
                '_user_limit' => $user_limit,
                '_allowed_domains' => $allowed_domains,
                '_modules' => $modules,
                '_status' => 'active',
                '_package_id' => $package_id,
            )
        ));
        
        if (is_wp_error($license_id)) {
            wp_die(__('Lisans eklenirken hata oluştu.', 'license-manager'));
        }
        
        // Set license status taxonomy
        wp_set_object_terms($license_id, 'active', 'lm_license_status');
        wp_set_object_terms($license_id, $license_type, 'lm_license_type');
        
        // Set modules if provided - store in both meta and taxonomy for consistency
        update_post_meta($license_id, '_modules', $modules);
        if (!empty($modules)) {
            wp_set_object_terms($license_id, $modules, 'lm_modules');
        }
        
        // Redirect with success message
        wp_redirect(admin_url('admin.php?page=license-manager-licenses&added=1'));
        exit;
    }
    
    /**
     * Handle add package form submission
     */
    public function handle_add_package() {
        // Check nonce and capabilities
        if (!wp_verify_nonce($_POST['license_manager_nonce'], 'license_manager_add_package') ||
            !current_user_can('manage_license_manager')) {
            wp_die(__('Yetkisiz erişim.', 'license-manager'));
        }
        
        // Sanitize input data
        $package_name = sanitize_text_field($_POST['package_name']);
        $description = sanitize_textarea_field($_POST['description']);
        $duration = sanitize_text_field($_POST['duration']);
        $user_limit = !empty($_POST['user_limit']) ? intval($_POST['user_limit']) : get_option('license_manager_default_user_limit', 5);
        $price = !empty($_POST['price']) ? floatval($_POST['price']) : 0;
        $modules = isset($_POST['modules']) ? array_map('sanitize_text_field', $_POST['modules']) : array();
        
        // Validate required fields
        if (empty($package_name)) {
            wp_die(__('Paket adı gereklidir.', 'license-manager'));
        }
        
        // Create package post
        $package_id = wp_insert_post(array(
            'post_title' => $package_name,
            'post_content' => $description,
            'post_type' => 'lm_license_package',
            'post_status' => 'publish',
            'meta_input' => array(
                '_duration' => $duration,
                '_user_limit' => $user_limit,
                '_price' => $price,
                '_modules' => $modules,
            )
        ));
        
        if (is_wp_error($package_id)) {
            wp_die(__('Lisans paketi eklenirken hata oluştu.', 'license-manager'));
        }
        
        // Set modules if provided - already stored in meta above, now store in taxonomy too
        if (!empty($modules)) {
            wp_set_object_terms($package_id, $modules, 'lm_modules');
        }
        
        // Redirect with success message
        wp_redirect(admin_url('admin.php?page=license-manager-packages&added=1'));
        exit;
    }
    
    /**
     * Generate unique license key
     */
    private function generate_license_key() {
        $prefix = 'LM';
        $key = $prefix . '-' . strtoupper(wp_generate_password(8, false)) . '-' . strtoupper(wp_generate_password(8, false));
        
        // Check if key already exists
        $existing = get_posts(array(
            'post_type' => 'lm_license',
            'meta_query' => array(
                array(
                    'key' => '_license_key',
                    'value' => $key,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
            'fields' => 'ids'
        ));
        
        // If key exists, generate a new one
        if (!empty($existing)) {
            return $this->generate_license_key();
        }
        
        return $key;
    }
    
    /**
     * Calculate expiration date based on license type
     */
    private function calculate_expiration_date($license_type) {
        $now = current_time('Y-m-d');
        
        switch ($license_type) {
            case 'monthly':
                return date('Y-m-d', strtotime($now . ' +1 month'));
            case 'yearly':
                return date('Y-m-d', strtotime($now . ' +1 year'));
            case 'trial':
                return date('Y-m-d', strtotime($now . ' +30 days'));
            case 'lifetime':
            default:
                return ''; // No expiration for lifetime
        }
    }
    
    /**
     * Sanitize website URL with flexible validation
     */
    private function sanitize_website_url($url) {
        if (empty($url)) {
            return '';
        }
        
        // Remove extra whitespace
        $url = trim($url);
        
        // Add protocol if missing
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'http://' . $url;
        }
        
        // Validate URL
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        
        // If validation fails, return sanitized text instead of empty
        return sanitize_text_field($url);
    }
    
    /**
     * Calculate expiry date based on license type
     */
    private function calculate_expiry_date($license_type) {
        switch ($license_type) {
            case 'monthly':
                return date('Y-m-d', strtotime('+1 month'));
            case 'yearly':
                return date('Y-m-d', strtotime('+1 year'));
            case 'trial':
                return date('Y-m-d', strtotime('+' . get_option('license_manager_default_license_duration', 30) . ' days'));
            case 'lifetime':
            default:
                return 'lifetime';
        }
    }
    
    /**
     * Display edit customer page
     */
    public function display_edit_customer() {
        $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
        
        if (!$customer_id) {
            wp_die(__('Geçersiz müşteri ID.', 'license-manager'));
        }
        
        $customer = get_post($customer_id);
        if (!$customer || $customer->post_type !== 'lm_customer') {
            wp_die(__('Müşteri bulunamadı.', 'license-manager'));
        }
        
        // Get customer metadata
        $company = get_post_meta($customer_id, '_company', true);
        $contact_person = get_post_meta($customer_id, '_contact_person', true);
        $email = get_post_meta($customer_id, '_email', true);
        $phone = get_post_meta($customer_id, '_phone', true);
        $website = get_post_meta($customer_id, '_website', true);
        $allowed_domains = get_post_meta($customer_id, '_allowed_domains', true);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Müşteri Düzenle', 'license-manager'); ?></h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="license-manager-form">
                <input type="hidden" name="action" value="license_manager_edit_customer">
                <input type="hidden" name="customer_id" value="<?php echo esc_attr($customer_id); ?>">
                <?php wp_nonce_field('license_manager_edit_customer', 'license_manager_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="customer_name"><?php _e('Müşteri Adı', 'license-manager'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="customer_name" name="customer_name" value="<?php echo esc_attr($customer->post_title); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="company"><?php _e('Şirket', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="company" name="company" value="<?php echo esc_attr($company); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="contact_person"><?php _e('İletişim Kişisi', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="contact_person" name="contact_person" value="<?php echo esc_attr($contact_person); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="email"><?php _e('E-posta', 'license-manager'); ?> *</label>
                        </th>
                        <td>
                            <input type="email" id="email" name="email" value="<?php echo esc_attr($email); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="phone"><?php _e('Telefon', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="phone" name="phone" value="<?php echo esc_attr($phone); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="website"><?php _e('Web Sitesi', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="website" name="website" value="<?php echo esc_attr($website); ?>" class="regular-text" placeholder="https://example.com">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="allowed_domains"><?php _e('İzinli Alan Adları', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <textarea id="allowed_domains" name="allowed_domains" rows="3" class="large-text"><?php echo esc_textarea($allowed_domains); ?></textarea>
                            <p class="description"><?php _e('Her satıra bir alan adı yazın. Örn: example.com', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="notes"><?php _e('Notlar', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <textarea id="notes" name="notes" rows="5" class="large-text"><?php echo esc_textarea($customer->post_content); ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Müşteriyi Güncelle', 'license-manager')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Handle edit customer form submission
     */
    public function handle_edit_customer() {
        // Check nonce and capabilities
        if (!wp_verify_nonce($_POST['license_manager_nonce'], 'license_manager_edit_customer') ||
            !current_user_can('manage_license_manager')) {
            wp_die(__('Yetkisiz erişim.', 'license-manager'));
        }
        
        $customer_id = intval($_POST['customer_id']);
        if (!$customer_id) {
            wp_die(__('Geçersiz müşteri ID.', 'license-manager'));
        }
        
        // Sanitize input data
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $company = sanitize_text_field($_POST['company']);
        $contact_person = sanitize_text_field($_POST['contact_person']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $website = $this->sanitize_website_url($_POST['website']);
        $allowed_domains = sanitize_textarea_field($_POST['allowed_domains']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        // Validate required fields
        if (empty($customer_name)) {
            wp_die(__('Müşteri adı gereklidir.', 'license-manager'));
        }
        
        if (empty($email) || !is_email($email)) {
            wp_die(__('Geçerli bir e-posta adresi gereklidir.', 'license-manager'));
        }
        
        // Update customer post
        $updated = wp_update_post(array(
            'ID' => $customer_id,
            'post_title' => $customer_name,
            'post_content' => $notes,
        ));
        
        if (is_wp_error($updated)) {
            wp_die(__('Müşteri güncellenirken hata oluştu.', 'license-manager'));
        }
        
        // Update customer metadata
        update_post_meta($customer_id, '_company', $company);
        update_post_meta($customer_id, '_contact_person', $contact_person);
        update_post_meta($customer_id, '_email', $email);
        update_post_meta($customer_id, '_phone', $phone);
        update_post_meta($customer_id, '_website', $website);
        update_post_meta($customer_id, '_allowed_domains', $allowed_domains);
        
        // Redirect with success message
        wp_redirect(admin_url('admin.php?page=license-manager-customers&updated=1'));
        exit;
    }
    
    /**
     * Handle delete customer
     */
    public function handle_delete_customer() {
        $customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (!$customer_id) {
            wp_die(__('Geçersiz müşteri ID.', 'license-manager'));
        }
        
        // Check nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_customer_' . $customer_id) ||
            !current_user_can('manage_license_manager')) {
            wp_die(__('Yetkisiz erişim.', 'license-manager'));
        }
        
        // Delete customer
        $deleted = wp_delete_post($customer_id, true);
        
        if (!$deleted) {
            wp_die(__('Müşteri silinirken hata oluştu.', 'license-manager'));
        }
        
        // Redirect with success message
        wp_redirect(admin_url('admin.php?page=license-manager-customers&deleted=1'));
        exit;
    }
    
    /**
     * Handle delete license
     */
    public function handle_delete_license() {
        $license_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (!$license_id) {
            wp_die(__('Geçersiz lisans ID.', 'license-manager'));
        }
        
        // Check nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_license_' . $license_id) ||
            !current_user_can('manage_license_manager')) {
            wp_die(__('Yetkisiz erişim.', 'license-manager'));
        }
        
        // Delete license
        $deleted = wp_delete_post($license_id, true);
        
        if (!$deleted) {
            wp_die(__('Lisans silinirken hata oluştu.', 'license-manager'));
        }
        
        // Redirect with success message
        wp_redirect(admin_url('admin.php?page=license-manager-licenses&deleted=1'));
        exit;
    }
    
    /**
     * Handle delete package
     */
    public function handle_delete_package() {
        $package_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (!$package_id) {
            wp_die(__('Geçersiz paket ID.', 'license-manager'));
        }
        
        // Check nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_package_' . $package_id) ||
            !current_user_can('manage_license_manager')) {
            wp_die(__('Yetkisiz erişim.', 'license-manager'));
        }
        
        // Delete package
        $deleted = wp_delete_post($package_id, true);
        
        if (!$deleted) {
            wp_die(__('Paket silinirken hata oluştu.', 'license-manager'));
        }
        
        // Redirect with success message
        wp_redirect(admin_url('admin.php?page=license-manager-packages&deleted=1'));
        exit;
    }
    
    /**
     * Display edit license page (placeholder)
     */
    /**
     * Display edit license page
     */
    public function display_edit_license() {
        if (!isset($_GET['license_id'])) {
            wp_die(__('Lisans ID belirtilmedi.', 'license-manager'));
        }
        
        $license_id = intval($_GET['license_id']);
        $license = get_post($license_id);
        
        if (!$license || $license->post_type !== 'lm_license') {
            wp_die(__('Geçersiz lisans ID.', 'license-manager'));
        }
        
        // Get customers for dropdown
        $customers = get_posts(array(
            'post_type' => 'lm_customer',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        // Get packages for dropdown
        $packages = get_posts(array(
            'post_type' => 'lm_license_package',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        // Get modules - ensure they exist with better error handling
        $database = new License_Manager_Database();
        $modules = $database->get_available_modules();
        
        // If no modules found, force create them and try again
        if (empty($modules)) {
            $database->force_create_default_modules();
            $modules = $database->get_available_modules();
        }
        
        // Get current license data
        $license_key = get_post_meta($license_id, '_license_key', true);
        $customer_id = get_post_meta($license_id, '_customer_id', true);
        $package_id = get_post_meta($license_id, '_package_id', true);
        $license_type = get_post_meta($license_id, '_license_type', true);
        $expires_on = get_post_meta($license_id, '_expires_on', true);
        $user_limit = get_post_meta($license_id, '_user_limit', true);
        $allowed_domains = get_post_meta($license_id, '_allowed_domains', true);
        $status = get_post_meta($license_id, '_status', true);
        
        // Get current modules - check both taxonomy and meta for consistency
        $current_modules = wp_get_post_terms($license_id, 'lm_modules');
        $current_module_slugs = array();
        if (!is_wp_error($current_modules) && !empty($current_modules)) {
            foreach ($current_modules as $module) {
                $current_module_slugs[] = $module->slug;
            }
        }
        
        // If no modules from taxonomy, try meta fallback
        if (empty($current_module_slugs)) {
            $modules_meta = get_post_meta($license_id, '_modules', true);
            if (is_array($modules_meta)) {
                $current_module_slugs = $modules_meta;
            }
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Lisans Düzenle', 'license-manager'); ?></h1>
            
            <?php
            // Display success message if license was updated
            if (isset($_GET['updated']) && $_GET['updated'] == '1') {
                echo '<div class="notice notice-success"><p>' . __('Lisans başarıyla güncellendi.', 'license-manager') . '</p></div>';
            }
            ?>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="license_manager_edit_license" />
                <input type="hidden" name="license_id" value="<?php echo esc_attr($license_id); ?>" />
                <?php wp_nonce_field('license_manager_edit_license', 'license_manager_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="license_key"><?php _e('Lisans Anahtarı', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="license_key" name="license_key" class="regular-text" value="<?php echo esc_attr($license_key); ?>" readonly />
                            <p class="description"><?php _e('Lisans anahtarı değiştirilemez.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="customer_id"><?php _e('Müşteri', 'license-manager'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <select id="customer_id" name="customer_id" required>
                                <option value=""><?php _e('Müşteri Seçin', 'license-manager'); ?></option>
                                <?php foreach ($customers as $customer) : ?>
                                    <option value="<?php echo esc_attr($customer->ID); ?>" <?php selected($customer_id, $customer->ID); ?>>
                                        <?php echo esc_html($customer->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="package_id"><?php _e('Lisans Paketi', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <select id="package_id" name="package_id">
                                <option value=""><?php _e('Paket Seçin (İsteğe Bağlı)', 'license-manager'); ?></option>
                                <?php foreach ($packages as $package) : ?>
                                    <option value="<?php echo esc_attr($package->ID); ?>" <?php selected($package_id, $package->ID); ?>>
                                        <?php echo esc_html($package->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Paket seçerseniz, paket ayarları otomatik olarak uygulanır.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="license_type"><?php _e('Lisans Türü', 'license-manager'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <select id="license_type" name="license_type" required>
                                <option value="monthly" <?php selected($license_type, 'monthly'); ?>><?php _e('Aylık', 'license-manager'); ?></option>
                                <option value="yearly" <?php selected($license_type, 'yearly'); ?>><?php _e('Yıllık', 'license-manager'); ?></option>
                                <option value="lifetime" <?php selected($license_type, 'lifetime'); ?>><?php _e('Yaşam Boyu', 'license-manager'); ?></option>
                                <option value="trial" <?php selected($license_type, 'trial'); ?>><?php _e('Deneme', 'license-manager'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="expires_on"><?php _e('Geçerlilik Tarihi', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="date" id="expires_on" name="expires_on" value="<?php echo esc_attr($expires_on); ?>" />
                            <p class="description"><?php _e('Boş bırakırsanız lisans türüne göre otomatik hesaplanır.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="user_limit"><?php _e('Kullanıcı Limiti', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="user_limit" name="user_limit" min="1" value="<?php echo esc_attr($user_limit); ?>" />
                            <p class="description"><?php _e('Boş bırakırsanız varsayılan limit kullanılır.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="allowed_domains"><?php _e('İzin Verilen Domain\'ler', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <textarea id="allowed_domains" name="allowed_domains" class="large-text" rows="3" placeholder="<?php _e('Her satıra bir domain yazın (örn: example.com)', 'license-manager'); ?>"><?php echo esc_textarea($allowed_domains); ?></textarea>
                            <p class="description"><?php _e('Bu lisansın kullanılabileceği domain\'leri belirtin.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="status"><?php _e('Durum', 'license-manager'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <select id="status" name="status" required>
                                <option value="active" <?php selected($status, 'active'); ?>><?php _e('Aktif', 'license-manager'); ?></option>
                                <option value="expired" <?php selected($status, 'expired'); ?>><?php _e('Süresi Dolmuş', 'license-manager'); ?></option>
                                <option value="invalid" <?php selected($status, 'invalid'); ?>><?php _e('Geçersiz', 'license-manager'); ?></option>
                                <option value="suspended" <?php selected($status, 'suspended'); ?>><?php _e('Askıya Alınmış', 'license-manager'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <?php if (!empty($modules)) : ?>
                    <tr>
                        <th scope="row">
                            <label><?php _e('Modüller', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <?php foreach ($modules as $module) : ?>
                                <label>
                                    <input type="checkbox" name="modules[]" value="<?php echo esc_attr($module->slug); ?>" <?php checked(in_array($module->slug, $current_module_slugs)); ?> />
                                    <?php echo esc_html($module->name); ?>
                                </label><br />
                            <?php endforeach; ?>
                            <p class="description"><?php _e('Bu lisansla erişilebilecek modülleri seçin.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <?php submit_button(__('Lisansı Güncelle', 'license-manager')); ?>
                
                <p>
                    <a href="<?php echo admin_url('admin.php?page=license-manager-licenses'); ?>" class="button"><?php _e('Geri Dön', 'license-manager'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Display edit package page
     */
    public function display_edit_package() {
        if (!isset($_GET['package_id'])) {
            wp_die(__('Paket ID belirtilmedi.', 'license-manager'));
        }
        
        $package_id = intval($_GET['package_id']);
        $package = get_post($package_id);
        
        if (!$package || $package->post_type !== 'lm_license_package') {
            wp_die(__('Geçersiz paket ID.', 'license-manager'));
        }
        
        // Get modules - ensure they exist with better error handling
        $database = new License_Manager_Database();
        $modules = $database->get_available_modules();
        
        // If no modules found, force create them and try again
        if (empty($modules)) {
            $database->force_create_default_modules();
            $modules = $database->get_available_modules();
        }
        
        // Get current package data
        $duration = get_post_meta($package_id, '_duration', true);
        $user_limit = get_post_meta($package_id, '_user_limit', true);
        $price = get_post_meta($package_id, '_price', true);
        
        // Get current modules - check both taxonomy and meta for consistency  
        $current_modules = wp_get_post_terms($package_id, 'lm_modules');
        $current_module_slugs = array();
        if (!is_wp_error($current_modules) && !empty($current_modules)) {
            foreach ($current_modules as $module) {
                $current_module_slugs[] = $module->slug;
            }
        }
        
        // If no modules from taxonomy, try meta fallback
        if (empty($current_module_slugs)) {
            $modules_meta = get_post_meta($package_id, '_modules', true);
            if (is_array($modules_meta)) {
                $current_module_slugs = $modules_meta;
            }
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Paket Düzenle', 'license-manager'); ?></h1>
            
            <?php
            // Display success message if package was updated
            if (isset($_GET['updated']) && $_GET['updated'] == '1') {
                echo '<div class="notice notice-success"><p>' . __('Paket başarıyla güncellendi.', 'license-manager') . '</p></div>';
            }
            ?>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="license_manager_edit_package" />
                <input type="hidden" name="package_id" value="<?php echo esc_attr($package_id); ?>" />
                <?php wp_nonce_field('license_manager_edit_package', 'license_manager_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="package_name"><?php _e('Paket Adı', 'license-manager'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="package_name" name="package_name" class="regular-text" value="<?php echo esc_attr($package->post_title); ?>" required />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="description"><?php _e('Açıklama', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <textarea id="description" name="description" class="large-text" rows="4"><?php echo esc_textarea($package->post_content); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="duration"><?php _e('Süre', 'license-manager'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <select id="duration" name="duration" required>
                                <option value="monthly" <?php selected($duration, 'monthly'); ?>><?php _e('Aylık', 'license-manager'); ?></option>
                                <option value="yearly" <?php selected($duration, 'yearly'); ?>><?php _e('Yıllık', 'license-manager'); ?></option>
                                <option value="lifetime" <?php selected($duration, 'lifetime'); ?>><?php _e('Yaşam Boyu', 'license-manager'); ?></option>
                                <option value="trial" <?php selected($duration, 'trial'); ?>><?php _e('Deneme', 'license-manager'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="user_limit"><?php _e('Kullanıcı Limiti', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="user_limit" name="user_limit" min="1" value="<?php echo esc_attr($user_limit); ?>" />
                            <p class="description"><?php _e('Boş bırakırsanız varsayılan limit kullanılır.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="price"><?php _e('Fiyat (TL)', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="price" name="price" min="0" step="0.01" value="<?php echo esc_attr($price); ?>" />
                        </td>
                    </tr>
                    <?php if (!empty($modules)) : ?>
                    <tr>
                        <th scope="row">
                            <label><?php _e('İçerilen Modüller', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <?php foreach ($modules as $module) : ?>
                                <label>
                                    <input type="checkbox" name="modules[]" value="<?php echo esc_attr($module->slug); ?>" <?php checked(in_array($module->slug, $current_module_slugs)); ?> />
                                    <?php echo esc_html($module->name); ?>
                                </label><br />
                            <?php endforeach; ?>
                            <p class="description"><?php _e('Bu pakette yer alan modülleri seçin.', 'license-manager'); ?></p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <?php submit_button(__('Paketi Güncelle', 'license-manager')); ?>
                
                <p>
                    <a href="<?php echo admin_url('admin.php?page=license-manager-packages'); ?>" class="button"><?php _e('Geri Dön', 'license-manager'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Handle edit license form submission
     */
    public function handle_edit_license() {
        // Check nonce and capabilities
        if (!wp_verify_nonce($_POST['license_manager_nonce'], 'license_manager_edit_license') ||
            !current_user_can('manage_license_manager')) {
            wp_die(__('Yetkisiz erişim.', 'license-manager'));
        }
        
        $license_id = intval($_POST['license_id']);
        $license = get_post($license_id);
        
        if (!$license || $license->post_type !== 'lm_license') {
            wp_die(__('Geçersiz lisans ID.', 'license-manager'));
        }
        
        // Sanitize input data
        $customer_id = intval($_POST['customer_id']);
        $package_id = intval($_POST['package_id']);
        $license_type = sanitize_text_field($_POST['license_type']);
        $expires_on = sanitize_text_field($_POST['expires_on']);
        $user_limit = intval($_POST['user_limit']);
        $allowed_domains = sanitize_textarea_field($_POST['allowed_domains']);
        $status = sanitize_text_field($_POST['status']);
        $modules = isset($_POST['modules']) ? array_map('sanitize_text_field', $_POST['modules']) : array();
        
        // Validate required fields
        if (empty($customer_id)) {
            wp_die(__('Müşteri seçimi gereklidir.', 'license-manager'));
        }
        
        if (empty($license_type)) {
            wp_die(__('Lisans türü gereklidir.', 'license-manager'));
        }
        
        if (empty($status)) {
            wp_die(__('Durum gereklidir.', 'license-manager'));
        }
        
        // Calculate expiration date if not provided
        if (empty($expires_on) && $license_type !== 'lifetime') {
            $expires_on = $this->calculate_expiration_date($license_type);
        }
        
        // Use default user limit if not provided
        if (empty($user_limit)) {
            $user_limit = get_option('license_manager_default_user_limit', 5);
        }
        
        // Update license metadata
        update_post_meta($license_id, '_customer_id', $customer_id);
        update_post_meta($license_id, '_package_id', $package_id);
        update_post_meta($license_id, '_license_type', $license_type);
        update_post_meta($license_id, '_expires_on', $expires_on);
        update_post_meta($license_id, '_user_limit', $user_limit);
        update_post_meta($license_id, '_allowed_domains', $allowed_domains);
        update_post_meta($license_id, '_status', $status);
        
        // Update modules - store in both meta and taxonomy for consistency
        update_post_meta($license_id, '_modules', $modules);
        wp_set_object_terms($license_id, $modules, 'lm_modules');
        
        // Update license type taxonomy
        wp_set_object_terms($license_id, $license_type, 'lm_license_type');
        
        // Update license status taxonomy
        wp_set_object_terms($license_id, $status, 'lm_license_status');
        
        // Redirect with success message
        wp_redirect(admin_url('admin.php?page=license-manager-edit-license&license_id=' . $license_id . '&updated=1'));
        exit;
    }
    
    /**
     * Handle edit package form submission
     */
    public function handle_edit_package() {
        // Check nonce and capabilities
        if (!wp_verify_nonce($_POST['license_manager_nonce'], 'license_manager_edit_package') ||
            !current_user_can('manage_license_manager')) {
            wp_die(__('Yetkisiz erişim.', 'license-manager'));
        }
        
        $package_id = intval($_POST['package_id']);
        $package = get_post($package_id);
        
        if (!$package || $package->post_type !== 'lm_license_package') {
            wp_die(__('Geçersiz paket ID.', 'license-manager'));
        }
        
        // Sanitize input data
        $package_name = sanitize_text_field($_POST['package_name']);
        $description = sanitize_textarea_field($_POST['description']);
        $duration = sanitize_text_field($_POST['duration']);
        $user_limit = intval($_POST['user_limit']);
        $price = floatval($_POST['price']);
        $modules = isset($_POST['modules']) ? array_map('sanitize_text_field', $_POST['modules']) : array();
        
        // Validate required fields
        if (empty($package_name)) {
            wp_die(__('Paket adı gereklidir.', 'license-manager'));
        }
        
        if (empty($duration)) {
            wp_die(__('Süre gereklidir.', 'license-manager'));
        }
        
        // Use default user limit if not provided
        if (empty($user_limit)) {
            $user_limit = get_option('license_manager_default_user_limit', 5);
        }
        
        // Update package post
        wp_update_post(array(
            'ID' => $package_id,
            'post_title' => $package_name,
            'post_content' => $description,
        ));
        
        // Update package metadata
        update_post_meta($package_id, '_duration', $duration);
        update_post_meta($package_id, '_user_limit', $user_limit);
        update_post_meta($package_id, '_price', $price);
        
        // Update modules - store in both meta and taxonomy for consistency
        update_post_meta($package_id, '_modules', $modules);
        wp_set_object_terms($package_id, $modules, 'lm_modules');
        
        // Redirect with success message
        wp_redirect(admin_url('admin.php?page=license-manager-edit-package&package_id=' . $package_id . '&updated=1'));
        exit;
    }
    
    /**
     * Display overdue payments
     */
    private function display_overdue_payments() {
        $current_date = current_time('Y-m-d');
        
        $overdue_licenses = get_posts(array(
            'post_type' => 'lm_license',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_expires_on',
                    'value' => $current_date,
                    'compare' => '<',
                    'type' => 'DATE'
                ),
                array(
                    'key' => '_status',
                    'value' => 'active',
                    'compare' => '='
                )
            ),
            'numberposts' => 5,
            'orderby' => 'meta_value',
            'order' => 'ASC'
        ));
        
        if (!empty($overdue_licenses)) {
            echo '<table class="widefat">';
            echo '<thead><tr><th>' . __('Müşteri', 'license-manager') . '</th><th>' . __('Lisans', 'license-manager') . '</th><th>' . __('Süresi Doldu', 'license-manager') . '</th><th>' . __('İşlem', 'license-manager') . '</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($overdue_licenses as $license) {
                $customer_id = get_post_meta($license->ID, '_customer_id', true);
                $customer = get_post($customer_id);
                $expires_on = get_post_meta($license->ID, '_expires_on', true);
                
                echo '<tr>';
                echo '<td>' . ($customer ? esc_html($customer->post_title) : __('Bilinmeyen', 'license-manager')) . '</td>';
                echo '<td>' . esc_html($license->post_title) . '</td>';
                echo '<td>' . esc_html($expires_on) . '</td>';
                echo '<td><a href="' . admin_url('admin.php?page=license-manager-edit-license&license_id=' . $license->ID) . '">' . __('Düzenle', 'license-manager') . '</a></td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('Gecikmiş ödeme yok.', 'license-manager') . '</p>';
        }
    }
    
    /**
     * Display upcoming payments
     */
    private function display_upcoming_payments() {
        $current_date = current_time('Y-m-d');
        $next_month = date('Y-m-d', strtotime('+30 days'));
        
        $upcoming_licenses = get_posts(array(
            'post_type' => 'lm_license',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_expires_on',
                    'value' => array($current_date, $next_month),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ),
                array(
                    'key' => '_status',
                    'value' => array('active', ''),
                    'compare' => 'IN'
                )
            ),
            'numberposts' => 5,
            'orderby' => 'meta_value',
            'order' => 'ASC'
        ));
        
        if (!empty($upcoming_licenses)) {
            echo '<table class="widefat">';
            echo '<thead><tr><th>' . __('Müşteri', 'license-manager') . '</th><th>' . __('Lisans', 'license-manager') . '</th><th>' . __('Süresi Dolacak', 'license-manager') . '</th><th>' . __('İşlem', 'license-manager') . '</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($upcoming_licenses as $license) {
                $customer_id = get_post_meta($license->ID, '_customer_id', true);
                $customer = get_post($customer_id);
                $expires_on = get_post_meta($license->ID, '_expires_on', true);
                
                echo '<tr>';
                echo '<td>' . ($customer ? esc_html($customer->post_title) : __('Bilinmeyen', 'license-manager')) . '</td>';
                echo '<td>' . esc_html($license->post_title) . '</td>';
                echo '<td>' . esc_html($expires_on) . '</td>';
                echo '<td><a href="' . admin_url('admin.php?page=license-manager-edit-license&license_id=' . $license->ID) . '">' . __('Düzenle', 'license-manager') . '</a></td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('Yaklaşan ödeme yok.', 'license-manager') . '</p>';
        }
    }
    
    /**
     * Display detailed overdue payments
     */
    private function display_detailed_overdue_payments() {
        $current_date = current_time('Y-m-d');
        
        $overdue_licenses = get_posts(array(
            'post_type' => 'lm_license',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_expires_on',
                    'value' => $current_date,
                    'compare' => '<',
                    'type' => 'DATE'
                ),
                array(
                    'key' => '_status',
                    'value' => 'active',
                    'compare' => '='
                )
            ),
            'numberposts' => -1,
            'orderby' => 'meta_value',
            'order' => 'ASC'
        ));
        
        if (!empty($overdue_licenses)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Müşteri', 'license-manager') . '</th>';
            echo '<th>' . __('E-posta', 'license-manager') . '</th>';
            echo '<th>' . __('Lisans', 'license-manager') . '</th>';
            echo '<th>' . __('Fiyat', 'license-manager') . '</th>';
            echo '<th>' . __('Süresi Doldu', 'license-manager') . '</th>';
            echo '<th>' . __('Gün Geçti', 'license-manager') . '</th>';
            echo '<th>' . __('İşlemler', 'license-manager') . '</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($overdue_licenses as $license) {
                $customer_id = get_post_meta($license->ID, '_customer_id', true);
                $customer = get_post($customer_id);
                $email = get_post_meta($customer_id, '_email', true);
                $expires_on = get_post_meta($license->ID, '_expires_on', true);
                $price = get_post_meta($license->ID, '_price', true);
                
                $days_overdue = '';
                if ($expires_on) {
                    $expire_date = new DateTime($expires_on);
                    $current = new DateTime($current_date);
                    $diff = $current->diff($expire_date);
                    $days_overdue = $diff->days . ' gün';
                }
                
                echo '<tr>';
                echo '<td>' . ($customer ? esc_html($customer->post_title) : __('Bilinmeyen', 'license-manager')) . '</td>';
                echo '<td>' . esc_html($email) . '</td>';
                echo '<td>' . esc_html($license->post_title) . '</td>';
                echo '<td>' . ($price ? esc_html(number_format($price, 2)) . ' ₺' : '-') . '</td>';
                echo '<td>' . esc_html($expires_on) . '</td>';
                echo '<td class="overdue-days">' . $days_overdue . '</td>';
                echo '<td>';
                echo '<a href="' . admin_url('admin.php?page=license-manager-edit-license&license_id=' . $license->ID) . '" class="button button-small">' . __('Düzenle', 'license-manager') . '</a> ';
                echo '<form method="post" action="' . admin_url('admin-post.php') . '" style="display: inline-block;">';
                echo '<input type="hidden" name="action" value="license_manager_send_reminder">';
                echo '<input type="hidden" name="license_id" value="' . $license->ID . '">';
                echo '<input type="hidden" name="reminder_type" value="overdue">';
                echo wp_nonce_field('send_reminder_' . $license->ID, 'send_reminder_nonce', true, false);
                echo '<button type="submit" class="button button-small" style="background: #dc3545; color: white;">' . __('Hatırlatma Gönder', 'license-manager') . '</button>';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<div class="notice notice-success"><p>' . __('Hiç gecikmiş ödeme yok!', 'license-manager') . '</p></div>';
        }
    }
    
    /**
     * Display detailed upcoming payments
     */
    private function display_detailed_upcoming_payments() {
        $current_date = current_time('Y-m-d');
        $next_month = date('Y-m-d', strtotime('+30 days'));
        
        $upcoming_licenses = get_posts(array(
            'post_type' => 'lm_license',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_expires_on',
                    'value' => array($current_date, $next_month),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ),
                array(
                    'key' => '_status',
                    'value' => array('active', ''),
                    'compare' => 'IN'
                )
            ),
            'numberposts' => -1,
            'orderby' => 'meta_value',
            'order' => 'ASC'
        ));
        
        if (!empty($upcoming_licenses)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Müşteri', 'license-manager') . '</th>';
            echo '<th>' . __('E-posta', 'license-manager') . '</th>';
            echo '<th>' . __('Lisans', 'license-manager') . '</th>';
            echo '<th>' . __('Fiyat', 'license-manager') . '</th>';
            echo '<th>' . __('Süresi Dolacak', 'license-manager') . '</th>';
            echo '<th>' . __('Kalan Gün', 'license-manager') . '</th>';
            echo '<th>' . __('İşlemler', 'license-manager') . '</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($upcoming_licenses as $license) {
                $customer_id = get_post_meta($license->ID, '_customer_id', true);
                $customer = get_post($customer_id);
                $email = get_post_meta($customer_id, '_email', true);
                $expires_on = get_post_meta($license->ID, '_expires_on', true);
                $price = get_post_meta($license->ID, '_price', true);
                
                $days_remaining = '';
                if ($expires_on) {
                    $expire_date = new DateTime($expires_on);
                    $current = new DateTime($current_date);
                    $diff = $expire_date->diff($current);
                    $days_remaining = $diff->days . ' gün';
                }
                
                echo '<tr>';
                echo '<td>' . ($customer ? esc_html($customer->post_title) : __('Bilinmeyen', 'license-manager')) . '</td>';
                echo '<td>' . esc_html($email) . '</td>';
                echo '<td>' . esc_html($license->post_title) . '</td>';
                echo '<td>' . ($price ? esc_html(number_format($price, 2)) . ' ₺' : '-') . '</td>';
                echo '<td>' . esc_html($expires_on) . '</td>';
                echo '<td class="remaining-days">' . $days_remaining . '</td>';
                echo '<td>';
                echo '<a href="' . admin_url('admin.php?page=license-manager-edit-license&license_id=' . $license->ID) . '" class="button button-small">' . __('Düzenle', 'license-manager') . '</a> ';
                echo '<form method="post" action="' . admin_url('admin-post.php') . '" style="display: inline-block;">';
                echo '<input type="hidden" name="action" value="license_manager_send_reminder">';
                echo '<input type="hidden" name="license_id" value="' . $license->ID . '">';
                echo '<input type="hidden" name="reminder_type" value="upcoming">';
                echo wp_nonce_field('send_reminder_' . $license->ID, 'send_reminder_nonce', true, false);
                echo '<button type="submit" class="button button-small" style="background: #007cba; color: white;">' . __('Hatırlatma Gönder', 'license-manager') . '</button>';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<div class="notice notice-info"><p>' . __('Önümüzdeki 30 gün içinde süresi dolacak lisans yok.', 'license-manager') . '</p></div>';
        }
    }
    
    /**
     * Display payment history
     */
    private function display_payment_history() {
        $all_licenses = get_posts(array(
            'post_type' => 'lm_license',
            'post_status' => 'publish',
            'numberposts' => 50,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (!empty($all_licenses)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Tarih', 'license-manager') . '</th>';
            echo '<th>' . __('Müşteri', 'license-manager') . '</th>';
            echo '<th>' . __('Lisans', 'license-manager') . '</th>';
            echo '<th>' . __('Tutar', 'license-manager') . '</th>';
            echo '<th>' . __('Tür', 'license-manager') . '</th>';
            echo '<th>' . __('Durum', 'license-manager') . '</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($all_licenses as $license) {
                $customer_id = get_post_meta($license->ID, '_customer_id', true);
                $customer = get_post($customer_id);
                $price = get_post_meta($license->ID, '_price', true);
                $license_type = get_post_meta($license->ID, '_license_type', true);
                $status = get_post_meta($license->ID, '_status', true);
                
                echo '<tr>';
                echo '<td>' . esc_html(get_the_date('d/m/Y', $license->ID)) . '</td>';
                echo '<td>' . ($customer ? esc_html($customer->post_title) : __('Bilinmeyen', 'license-manager')) . '</td>';
                echo '<td>' . esc_html($license->post_title) . '</td>';
                echo '<td>' . ($price ? esc_html(number_format($price, 2)) . ' ₺' : '-') . '</td>';
                echo '<td>' . esc_html(ucfirst($license_type)) . '</td>';
                echo '<td><span class="status-' . esc_attr($status) . '">' . esc_html(ucfirst($status ?: 'active')) . '</span></td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('Henüz ödeme geçmişi yok.', 'license-manager') . '</p>';
        }
    }
    
    /**
     * Display customer CRM system
     */
    private function display_customer_crm() {
        $customers = get_posts(array(
            'post_type' => 'lm_customer',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        if (!empty($customers)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Müşteri', 'license-manager') . '</th>';
            echo '<th>' . __('İletişim', 'license-manager') . '</th>';
            echo '<th>' . __('Lisans Sayısı', 'license-manager') . '</th>';
            echo '<th>' . __('Aktif Lisans', 'license-manager') . '</th>';
            echo '<th>' . __('Toplam Ödeme', 'license-manager') . '</th>';
            echo '<th>' . __('Son Ödeme', 'license-manager') . '</th>';
            echo '<th>' . __('İşlemler', 'license-manager') . '</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($customers as $customer) {
                $email = get_post_meta($customer->ID, '_email', true);
                $phone = get_post_meta($customer->ID, '_phone', true);
                
                // Get customer licenses
                $customer_licenses = get_posts(array(
                    'post_type' => 'lm_license',
                    'post_status' => 'publish',
                    'meta_query' => array(
                        array(
                            'key' => '_customer_id',
                            'value' => $customer->ID,
                            'compare' => '='
                        )
                    ),
                    'numberposts' => -1
                ));
                
                $total_licenses = count($customer_licenses);
                $active_licenses = 0;
                $total_paid = 0;
                $last_payment_date = '';
                
                foreach ($customer_licenses as $license) {
                    $status = get_post_meta($license->ID, '_status', true);
                    if ($status === 'active' || empty($status)) {
                        $active_licenses++;
                    }
                    
                    $price = get_post_meta($license->ID, '_price', true);
                    if ($price) {
                        $total_paid += floatval($price);
                    }
                    
                    if (empty($last_payment_date) || get_the_date('Y-m-d', $license->ID) > $last_payment_date) {
                        $last_payment_date = get_the_date('d/m/Y', $license->ID);
                    }
                }
                
                echo '<tr>';
                echo '<td><strong>' . esc_html($customer->post_title) . '</strong></td>';
                echo '<td>' . esc_html($email) . '<br><small>' . esc_html($phone) . '</small></td>';
                echo '<td>' . $total_licenses . '</td>';
                echo '<td>' . $active_licenses . '</td>';
                echo '<td>' . number_format($total_paid, 2) . ' ₺</td>';
                echo '<td>' . esc_html($last_payment_date) . '</td>';
                echo '<td>';
                echo '<a href="' . admin_url('admin.php?page=license-manager-edit-customer&customer_id=' . $customer->ID) . '" class="button button-small">' . __('Düzenle', 'license-manager') . '</a> ';
                echo '<a href="' . admin_url('admin.php?page=license-manager-add-payment&customer_id=' . $customer->ID) . '" class="button button-small" style="background: #28a745; color: white;">' . __('Ödeme Ekle', 'license-manager') . '</a> ';
                echo '<a href="mailto:' . esc_attr($email) . '" class="button button-small">' . __('E-posta', 'license-manager') . '</a>';
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('Henüz müşteri kaydı yok.', 'license-manager') . '</p>';
        }
    }
    
    /**
     * Display payment records table
     */
    private function display_payment_records() {
        $payments = get_posts(array(
            'post_type' => 'lm_payment',
            'post_status' => 'publish',
            'numberposts' => 50,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (!empty($payments)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Tarih', 'license-manager') . '</th>';
            echo '<th>' . __('Müşteri', 'license-manager') . '</th>';
            echo '<th>' . __('Lisans', 'license-manager') . '</th>';
            echo '<th>' . __('Tutar', 'license-manager') . '</th>';
            echo '<th>' . __('Durum', 'license-manager') . '</th>';
            echo '<th>' . __('Dosya', 'license-manager') . '</th>';
            echo '<th>' . __('İşlemler', 'license-manager') . '</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($payments as $payment) {
                $customer_id = get_post_meta($payment->ID, '_customer_id', true);
                $license_id = get_post_meta($payment->ID, '_license_id', true);
                $amount = get_post_meta($payment->ID, '_amount', true);
                $payment_method = get_post_meta($payment->ID, '_payment_method', true);
                $payment_file = get_post_meta($payment->ID, '_payment_file', true);
                
                $customer = $customer_id ? get_post($customer_id) : null;
                $license = $license_id ? get_post($license_id) : null;
                
                $status_terms = wp_get_post_terms($payment->ID, 'lm_payment_status');
                $status = !empty($status_terms) && !is_wp_error($status_terms) ? $status_terms[0]->name : __('Beklemede', 'license-manager');
                
                echo '<tr>';
                echo '<td>' . esc_html(get_the_date('d/m/Y H:i', $payment->ID)) . '</td>';
                echo '<td>' . ($customer ? esc_html($customer->post_title) : __('Bilinmeyen', 'license-manager')) . '</td>';
                echo '<td>' . ($license ? esc_html($license->post_title) : __('Bilinmeyen', 'license-manager')) . '</td>';
                echo '<td>' . ($amount ? esc_html(number_format($amount, 2)) . ' ₺' : '-') . '</td>';
                echo '<td><span class="status-' . esc_attr(strtolower($status)) . '">' . esc_html($status) . '</span></td>';
                echo '<td>';
                if ($payment_file) {
                    echo '<a href="' . esc_url($payment_file) . '" target="_blank">' . __('Dosyayı Görüntüle', 'license-manager') . '</a>';
                } else {
                    echo '-';
                }
                echo '</td>';
                echo '<td>';
                echo '<a href="' . admin_url('admin.php?page=license-manager-edit-payment&payment_id=' . $payment->ID) . '" class="button button-small">' . __('Düzenle', 'license-manager') . '</a> ';
                echo '<a href="' . wp_nonce_url(admin_url('admin-post.php?action=license_manager_delete_payment&payment_id=' . $payment->ID), 'delete_payment_' . $payment->ID) . '" class="button button-small" onclick="return confirm(\'' . __('Bu ödeme kaydını silmek istediğinizden emin misiniz?', 'license-manager') . '\')">' . __('Sil', 'license-manager') . '</a>';
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('Henüz ödeme kaydı yok.', 'license-manager') . '</p>';
        }
    }
    
    /**
     * Display add payment form
     */
    public function display_add_payment() {
        // Get customer_id from URL if coming from CRM
        $selected_customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
        $selected_customer = null;
        
        if ($selected_customer_id) {
            $selected_customer = get_post($selected_customer_id);
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Yeni Ödeme Ekle', 'license-manager'); ?></h1>
            
            <?php if ($selected_customer): ?>
                <div class="notice notice-info">
                    <p><?php printf(__('Müşteri için ödeme ekleniyor: <strong>%s</strong>', 'license-manager'), esc_html($selected_customer->post_title)); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('add_payment', 'add_payment_nonce'); ?>
                <input type="hidden" name="action" value="license_manager_add_payment">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="customer_id"><?php _e('Müşteri', 'license-manager'); ?> *</label>
                        </th>
                        <td>
                            <select name="customer_id" id="customer_id" required class="regular-text">
                                <option value=""><?php _e('Müşteri Seçin', 'license-manager'); ?></option>
                                <?php
                                $customers = get_posts(array(
                                    'post_type' => 'lm_customer',
                                    'posts_per_page' => -1,
                                    'orderby' => 'title',
                                    'order' => 'ASC'
                                ));
                                foreach ($customers as $customer) {
                                    $selected = ($customer->ID == $selected_customer_id) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($customer->ID) . '" ' . $selected . '>' . esc_html($customer->post_title) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="license_id"><?php _e('Lisans', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <select name="license_id" id="license_id" class="regular-text">
                                <option value=""><?php _e('Lisans Seçin (Opsiyonel)', 'license-manager'); ?></option>
                                <?php
                                $licenses = get_posts(array(
                                    'post_type' => 'lm_license',
                                    'posts_per_page' => -1,
                                    'orderby' => 'title',
                                    'order' => 'ASC'
                                ));
                                foreach ($licenses as $license) {
                                    // If customer is selected, only show their licenses
                                    if ($selected_customer_id) {
                                        $license_customer_id = get_post_meta($license->ID, '_customer_id', true);
                                        if ($license_customer_id != $selected_customer_id) {
                                            continue;
                                        }
                                    }
                                    echo '<option value="' . esc_attr($license->ID) . '">' . esc_html($license->post_title) . '</option>';
                                }
                                ?>
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="amount"><?php _e('Tutar', 'license-manager'); ?> *</label>
                        </th>
                        <td>
                            <input type="number" name="amount" id="amount" step="0.01" min="0" required class="regular-text" />
                            <span class="description"><?php _e('Ödenen tutar (₺)', 'license-manager'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="payment_method"><?php _e('Ödeme Yöntemi', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <select name="payment_method" id="payment_method" class="regular-text">
                                <option value="bank_transfer"><?php _e('Banka Havalesi', 'license-manager'); ?></option>
                                <option value="credit_card"><?php _e('Kredi Kartı', 'license-manager'); ?></option>
                                <option value="cash"><?php _e('Nakit', 'license-manager'); ?></option>
                                <option value="check"><?php _e('Çek', 'license-manager'); ?></option>
                                <option value="other"><?php _e('Diğer', 'license-manager'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="payment_status"><?php _e('Durum', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <select name="payment_status" id="payment_status" class="regular-text">
                                <?php
                                $statuses = get_terms(array(
                                    'taxonomy' => 'lm_payment_status',
                                    'hide_empty' => false,
                                ));
                                
                                // If no terms exist or error, create default ones
                                if (is_wp_error($statuses) || empty($statuses)) {
                                    $this->ensure_payment_status_terms();
                                    $statuses = get_terms(array(
                                        'taxonomy' => 'lm_payment_status',
                                        'hide_empty' => false,
                                    ));
                                }
                                
                                // If still no terms, create fallback options
                                if (is_wp_error($statuses) || empty($statuses)) {
                                    $default_statuses = array(
                                        'pending' => __('Beklemede', 'license-manager'),
                                        'completed' => __('Tamamlandı', 'license-manager'),
                                        'failed' => __('Başarısız', 'license-manager'),
                                        'cancelled' => __('İptal Edildi', 'license-manager'),
                                    );
                                    foreach ($default_statuses as $slug => $name) {
                                        $selected = ($slug === 'completed') ? 'selected' : '';
                                        echo '<option value="' . esc_attr($slug) . '" ' . $selected . '>' . esc_html($name) . '</option>';
                                    }
                                } else {
                                    foreach ($statuses as $status) {
                                        $selected = ($status->slug === 'completed') ? 'selected' : '';
                                        echo '<option value="' . esc_attr($status->slug) . '" ' . $selected . '>' . esc_html($status->name) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <span class="description"><?php _e('Ödeme durumunu seçin', 'license-manager'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="payment_date"><?php _e('Ödeme Tarihi', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="date" name="payment_date" id="payment_date" value="<?php echo date('Y-m-d'); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="payment_file"><?php _e('Evrak/Belge', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="file" name="payment_file" id="payment_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" />
                            <span class="description"><?php _e('Ödeme belgesini yükleyin (PDF, JPG, PNG, DOC dosyaları)', 'license-manager'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="notes"><?php _e('Notlar', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <textarea name="notes" id="notes" rows="4" class="large-text"></textarea>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="<?php _e('Ödeme Ekle', 'license-manager'); ?>" />
                    <a href="<?php echo admin_url('admin.php?page=license-manager-payments'); ?>" class="button"><?php _e('İptal', 'license-manager'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Display edit payment form
     */
    public function display_edit_payment() {
        $payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;
        
        if (!$payment_id || get_post_type($payment_id) !== 'lm_payment') {
            wp_die(__('Geçersiz ödeme ID\'si.', 'license-manager'));
        }
        
        $payment = get_post($payment_id);
        $customer_id = get_post_meta($payment_id, '_customer_id', true);
        $license_id = get_post_meta($payment_id, '_license_id', true);
        $amount = get_post_meta($payment_id, '_amount', true);
        $payment_method = get_post_meta($payment_id, '_payment_method', true);
        $payment_date = get_post_meta($payment_id, '_payment_date', true);
        $payment_file = get_post_meta($payment_id, '_payment_file', true);
        $notes = get_post_meta($payment_id, '_notes', true);
        
        $status_terms = wp_get_post_terms($payment_id, 'lm_payment_status');
        $current_status = !empty($status_terms) && !is_wp_error($status_terms) ? $status_terms[0]->slug : 'pending';
        
        ?>
        <div class="wrap">
            <h1><?php _e('Ödeme Düzenle', 'license-manager'); ?></h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('edit_payment_' . $payment_id, 'edit_payment_nonce'); ?>
                <input type="hidden" name="action" value="license_manager_edit_payment">
                <input type="hidden" name="payment_id" value="<?php echo esc_attr($payment_id); ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="customer_id"><?php _e('Müşteri', 'license-manager'); ?> *</label>
                        </th>
                        <td>
                            <select name="customer_id" id="customer_id" required class="regular-text">
                                <option value=""><?php _e('Müşteri Seçin', 'license-manager'); ?></option>
                                <?php
                                $customers = get_posts(array(
                                    'post_type' => 'lm_customer',
                                    'posts_per_page' => -1,
                                    'orderby' => 'title',
                                    'order' => 'ASC'
                                ));
                                foreach ($customers as $customer) {
                                    $selected = ($customer->ID == $customer_id) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($customer->ID) . '" ' . $selected . '>' . esc_html($customer->post_title) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="license_id"><?php _e('Lisans', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <select name="license_id" id="license_id" class="regular-text">
                                <option value=""><?php _e('Lisans Seçin (Opsiyonel)', 'license-manager'); ?></option>
                                <?php
                                $licenses = get_posts(array(
                                    'post_type' => 'lm_license',
                                    'posts_per_page' => -1,
                                    'orderby' => 'title',
                                    'order' => 'ASC'
                                ));
                                foreach ($licenses as $license) {
                                    $selected = ($license->ID == $license_id) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($license->ID) . '" ' . $selected . '>' . esc_html($license->post_title) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="amount"><?php _e('Tutar', 'license-manager'); ?> *</label>
                        </th>
                        <td>
                            <input type="number" name="amount" id="amount" step="0.01" min="0" required class="regular-text" value="<?php echo esc_attr($amount); ?>" />
                            <span class="description"><?php _e('Ödenen tutar (₺)', 'license-manager'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="payment_method"><?php _e('Ödeme Yöntemi', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <select name="payment_method" id="payment_method" class="regular-text">
                                <option value="bank_transfer" <?php selected($payment_method, 'bank_transfer'); ?>><?php _e('Banka Havalesi', 'license-manager'); ?></option>
                                <option value="credit_card" <?php selected($payment_method, 'credit_card'); ?>><?php _e('Kredi Kartı', 'license-manager'); ?></option>
                                <option value="cash" <?php selected($payment_method, 'cash'); ?>><?php _e('Nakit', 'license-manager'); ?></option>
                                <option value="check" <?php selected($payment_method, 'check'); ?>><?php _e('Çek', 'license-manager'); ?></option>
                                <option value="other" <?php selected($payment_method, 'other'); ?>><?php _e('Diğer', 'license-manager'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="payment_status"><?php _e('Durum', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <select name="payment_status" id="payment_status" class="regular-text">
                                <?php
                                $statuses = get_terms(array(
                                    'taxonomy' => 'lm_payment_status',
                                    'hide_empty' => false,
                                ));
                                
                                // If no terms exist or error, create default ones
                                if (is_wp_error($statuses) || empty($statuses)) {
                                    $this->ensure_payment_status_terms();
                                    $statuses = get_terms(array(
                                        'taxonomy' => 'lm_payment_status',
                                        'hide_empty' => false,
                                    ));
                                }
                                
                                // If still no terms, create fallback options
                                if (is_wp_error($statuses) || empty($statuses)) {
                                    $default_statuses = array(
                                        'pending' => __('Beklemede', 'license-manager'),
                                        'completed' => __('Tamamlandı', 'license-manager'),
                                        'failed' => __('Başarısız', 'license-manager'),
                                        'cancelled' => __('İptal Edildi', 'license-manager'),
                                    );
                                    foreach ($default_statuses as $slug => $name) {
                                        $selected = ($slug === $current_status) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($slug) . '" ' . $selected . '>' . esc_html($name) . '</option>';
                                    }
                                } else {
                                    foreach ($statuses as $status) {
                                        $selected = ($status->slug === $current_status) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($status->slug) . '" ' . $selected . '>' . esc_html($status->name) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <span class="description"><?php _e('Ödeme durumunu seçin', 'license-manager'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="payment_date"><?php _e('Ödeme Tarihi', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <input type="date" name="payment_date" id="payment_date" value="<?php echo esc_attr($payment_date ?: date('Y-m-d')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="payment_file"><?php _e('Evrak/Belge', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <?php if ($payment_file): ?>
                                <p><strong><?php _e('Mevcut Dosya:', 'license-manager'); ?></strong> 
                                <a href="<?php echo esc_url($payment_file); ?>" target="_blank"><?php _e('Dosyayı Görüntüle', 'license-manager'); ?></a></p>
                            <?php endif; ?>
                            <input type="file" name="payment_file" id="payment_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" />
                            <span class="description"><?php _e('Yeni dosya yüklerseniz eskisi değiştirilir', 'license-manager'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="notes"><?php _e('Notlar', 'license-manager'); ?></label>
                        </th>
                        <td>
                            <textarea name="notes" id="notes" rows="4" class="large-text"><?php echo esc_textarea($notes); ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="<?php _e('Ödeme Güncelle', 'license-manager'); ?>" />
                    <a href="<?php echo admin_url('admin.php?page=license-manager-payments'); ?>" class="button"><?php _e('İptal', 'license-manager'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Handle add payment form submission
     */
    public function handle_add_payment() {
        if (!current_user_can('manage_license_manager')) {
            wp_die(__('Bu işlemi yapmaya yetkiniz yok.', 'license-manager'));
        }
        
        if (!wp_verify_nonce($_POST['add_payment_nonce'], 'add_payment')) {
            wp_die(__('Güvenlik doğrulaması başarısız.', 'license-manager'));
        }
        
        $customer_id = intval($_POST['customer_id']);
        $license_id = intval($_POST['license_id']) ?: null;
        $amount = floatval($_POST['amount']);
        $payment_method = sanitize_text_field($_POST['payment_method']);
        $payment_status = sanitize_text_field($_POST['payment_status']);
        $payment_date = sanitize_text_field($_POST['payment_date']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        // Create payment post
        $customer = get_post($customer_id);
        $title = sprintf(__('Ödeme - %s - %s', 'license-manager'), 
                        $customer ? $customer->post_title : 'Bilinmeyen',
                        date('d/m/Y'));
        
        $payment_id = wp_insert_post(array(
            'post_type' => 'lm_payment',
            'post_title' => $title,
            'post_content' => $notes,
            'post_status' => 'publish',
            'post_date' => $payment_date ? $payment_date . ' ' . current_time('H:i:s') : current_time('mysql')
        ));
        
        if ($payment_id) {
            // Save payment metadata
            update_post_meta($payment_id, '_customer_id', $customer_id);
            if ($license_id) {
                update_post_meta($payment_id, '_license_id', $license_id);
            }
            update_post_meta($payment_id, '_amount', $amount);
            update_post_meta($payment_id, '_payment_method', $payment_method);
            update_post_meta($payment_id, '_payment_date', $payment_date);
            update_post_meta($payment_id, '_notes', $notes);
            
            // Set payment status
            wp_set_object_terms($payment_id, $payment_status, 'lm_payment_status');
            
            // Handle file upload
            if (!empty($_FILES['payment_file']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                
                $attachment_id = media_handle_upload('payment_file', $payment_id);
                if (!is_wp_error($attachment_id)) {
                    $file_url = wp_get_attachment_url($attachment_id);
                    update_post_meta($payment_id, '_payment_file', $file_url);
                    update_post_meta($payment_id, '_payment_file_id', $attachment_id);
                }
            }
            
            wp_redirect(admin_url('admin.php?page=license-manager-payments&message=payment_added'));
        } else {
            wp_redirect(admin_url('admin.php?page=license-manager-payments&message=error'));
        }
        exit;
    }
    
    /**
     * Handle edit payment form submission
     */
    public function handle_edit_payment() {
        if (!current_user_can('manage_license_manager')) {
            wp_die(__('Bu işlemi yapmaya yetkiniz yok.', 'license-manager'));
        }
        
        $payment_id = intval($_POST['payment_id']);
        
        if (!wp_verify_nonce($_POST['edit_payment_nonce'], 'edit_payment_' . $payment_id)) {
            wp_die(__('Güvenlik doğrulaması başarısız.', 'license-manager'));
        }
        
        if (get_post_type($payment_id) !== 'lm_payment') {
            wp_die(__('Geçersiz ödeme ID\'si.', 'license-manager'));
        }
        
        $customer_id = intval($_POST['customer_id']);
        $license_id = intval($_POST['license_id']) ?: null;
        $amount = floatval($_POST['amount']);
        $payment_method = sanitize_text_field($_POST['payment_method']);
        $payment_status = sanitize_text_field($_POST['payment_status']);
        $payment_date = sanitize_text_field($_POST['payment_date']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        // Update payment post
        $customer = get_post($customer_id);
        $title = sprintf(__('Ödeme - %s - %s', 'license-manager'), 
                        $customer ? $customer->post_title : 'Bilinmeyen',
                        date('d/m/Y', strtotime($payment_date)));
        
        wp_update_post(array(
            'ID' => $payment_id,
            'post_title' => $title,
            'post_content' => $notes,
            'post_date' => $payment_date ? $payment_date . ' ' . current_time('H:i:s') : current_time('mysql')
        ));
        
        // Update payment metadata
        update_post_meta($payment_id, '_customer_id', $customer_id);
        if ($license_id) {
            update_post_meta($payment_id, '_license_id', $license_id);
        } else {
            delete_post_meta($payment_id, '_license_id');
        }
        update_post_meta($payment_id, '_amount', $amount);
        update_post_meta($payment_id, '_payment_method', $payment_method);
        update_post_meta($payment_id, '_payment_date', $payment_date);
        update_post_meta($payment_id, '_notes', $notes);
        
        // Update payment status
        wp_set_object_terms($payment_id, $payment_status, 'lm_payment_status');
        
        // Handle file upload
        if (!empty($_FILES['payment_file']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            // Delete old file if exists
            $old_file_id = get_post_meta($payment_id, '_payment_file_id', true);
            if ($old_file_id) {
                wp_delete_attachment($old_file_id, true);
            }
            
            $attachment_id = media_handle_upload('payment_file', $payment_id);
            if (!is_wp_error($attachment_id)) {
                $file_url = wp_get_attachment_url($attachment_id);
                update_post_meta($payment_id, '_payment_file', $file_url);
                update_post_meta($payment_id, '_payment_file_id', $attachment_id);
            }
        }
        
        wp_redirect(admin_url('admin.php?page=license-manager-payments&message=payment_updated'));
        exit;
    }
    
    /**
     * Handle delete payment
     */
    public function handle_delete_payment() {
        if (!current_user_can('manage_license_manager')) {
            wp_die(__('Bu işlemi yapmaya yetkiniz yok.', 'license-manager'));
        }
        
        $payment_id = intval($_GET['payment_id']);
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_payment_' . $payment_id)) {
            wp_die(__('Güvenlik doğrulaması başarısız.', 'license-manager'));
        }
        
        if (get_post_type($payment_id) !== 'lm_payment') {
            wp_die(__('Geçersiz ödeme ID\'si.', 'license-manager'));
        }
        
        // Delete associated file
        $file_id = get_post_meta($payment_id, '_payment_file_id', true);
        if ($file_id) {
            wp_delete_attachment($file_id, true);
        }
        
        // Delete payment
        wp_delete_post($payment_id, true);
        
        wp_redirect(admin_url('admin.php?page=license-manager-payments&message=payment_deleted'));
        exit;
    }
    
    /**
     * Ensure payment status terms exist
     */
    private function ensure_payment_status_terms() {
        $statuses = array(
            'pending' => __('Beklemede', 'license-manager'),
            'completed' => __('Tamamlandı', 'license-manager'),
            'failed' => __('Başarısız', 'license-manager'),
            'cancelled' => __('İptal Edildi', 'license-manager'),
            'refunded' => __('İade Edildi', 'license-manager'),
        );
        
        foreach ($statuses as $slug => $name) {
            if (!term_exists($slug, 'lm_payment_status')) {
                $result = wp_insert_term($name, 'lm_payment_status', array('slug' => $slug));
                if (is_wp_error($result)) {
                    error_log('License Manager: Failed to create payment status ' . $slug . ': ' . $result->get_error_message());
                }
            }
        }
    }
    
    /**
     * Handle send reminder email
     */
    public function handle_send_reminder() {
        if (!current_user_can('manage_license_manager')) {
            wp_die(__('Bu işlemi yapmaya yetkiniz yok.', 'license-manager'));
        }
        
        $license_id = intval($_POST['license_id']);
        $reminder_type = sanitize_text_field($_POST['reminder_type']);
        
        if (!wp_verify_nonce($_POST['send_reminder_nonce'], 'send_reminder_' . $license_id)) {
            wp_die(__('Güvenlik doğrulaması başarısız.', 'license-manager'));
        }
        
        $license = get_post($license_id);
        if (!$license || $license->post_type !== 'lm_license') {
            wp_die(__('Geçersiz lisans ID.', 'license-manager'));
        }
        
        // Get license and customer information
        $customer_id = get_post_meta($license_id, '_customer_id', true);
        $customer = get_post($customer_id);
        $email = get_post_meta($customer_id, '_email', true);
        $expires_on = get_post_meta($license_id, '_expires_on', true);
        $license_key = get_post_meta($license_id, '_license_key', true);
        $price = get_post_meta($license_id, '_price', true);
        
        if (!$email || !$customer) {
            wp_redirect(admin_url('admin.php?page=license-manager-payments&message=reminder_error'));
            exit;
        }
        
        // Prepare email content based on reminder type
        if ($reminder_type === 'overdue') {
            $subject = __('Lisans Ödeme Hatırlatması - Süre Dolmuş', 'license-manager');
            $message = sprintf(
                __('Sayın %s,\n\nLisansınızın süresi dolmuştur. Lütfen ödemeinizi yaparak lisansınızı yenileyin.\n\nLisans Bilgileri:\n- Lisans Anahtarı: %s\n- Bitiş Tarihi: %s\n- Ödeme Tutarı: %s ₺\n\nÖdeme yapmak için bizimle iletişime geçin.\n\nTeşekkürler.', 'license-manager'),
                $customer->post_title,
                $license_key,
                date('d/m/Y', strtotime($expires_on)),
                $price ? number_format($price, 2) : '-'
            );
        } else {
            $subject = __('Lisans Yenileme Hatırlatması', 'license-manager');
            $days_remaining = '';
            if ($expires_on) {
                $expire_date = new DateTime($expires_on);
                $current = new DateTime();
                $diff = $expire_date->diff($current);
                $days_remaining = $diff->days;
            }
            
            $message = sprintf(
                '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lisans Yenileme Hatırlatması</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            background-color: #007bff;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
            line-height: 1.6;
        }
        .content p {
            margin: 0 0 15px;
        }
        .license-details {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .license-details h3 {
            margin-top: 0;
            color: #007bff;
        }
        .license-details ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .license-details ul li {
            padding: 5px 0;
        }
        .license-details ul li strong {
            display: inline-block;
            width: 150px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #777;
        }
        .button {
            display: inline-block;
            background-color: #28a745;
            color: #ffffff;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Lisans Yenileme Hatırlatması</h1>
        </div>
        <div class="content">
            <p>Sayın %s,</p>
            <p>Lisansınızın süresinin dolmasına <strong>%s gün kaldı</strong>. Hizmetlerinizde herhangi bir kesinti yaşamamanız için lisansınızı yenileme zamanı geldi.</p>
            <div class="license-details">
                <h3>Lisans Bilgileriniz</h3>
                <ul>
                    <li><strong>Lisans Anahtarı:</strong> %s</li>
                    <li><strong>Bitiş Tarihi:</strong> %s</li>
                    <li><strong>Ödeme Tutarı:</strong> %s ₺</li>
                </ul>
            </div>
            <p>Ödeme ve yenileme işlemleri için lütfen bizimle iletişime geçin.</p>
        </div>
        <div class="footer">
            <p>Teşekkür eder, iyi çalışmalar dileriz.</p>
        </div>
    </div>
</body>
</html>',
                $customer->post_title,
                $days_remaining,
                $license_key,
                date('d/m/Y', strtotime($expires_on)),
                $price ? number_format($price, 2) : '-'
            );
        }
        
        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($email, $subject, $message, $headers);
        
        // Redirect with success or error message
        if ($sent) {
            wp_redirect(admin_url('admin.php?page=license-manager-payments&message=reminder_sent'));
        } else {
            wp_redirect(admin_url('admin.php?page=license-manager-payments&message=reminder_error'));
        }
        exit;
    }
}