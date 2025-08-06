<?php
/**
 * License Restriction Page Template
 * 
 * Modern and user-friendly restriction page with enhanced messaging
 * 
 * @package Insurance_CRM
 * @author  Anadolu Birlik
 * @since   1.1.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get restriction details from URL parameters or transient
$restriction_type = isset($_GET['restriction']) ? sanitize_text_field($_GET['restriction']) : 'data';
$module = isset($_GET['module']) ? sanitize_text_field($_GET['module']) : '';

// Try to get detailed restriction info from transient
$restriction_details = null;
if (!empty($module)) {
    $restriction_details = get_transient('insurance_crm_restriction_details_' . get_current_user_id());
    delete_transient('insurance_crm_restriction_details_' . get_current_user_id());
}

// Get license info
global $insurance_crm_license_manager;
$license_info = $insurance_crm_license_manager ? $insurance_crm_license_manager->get_license_info() : array();

// Determine restriction content based on type and available information
$page_title = 'Erişim Kısıtlaması';
$main_message = '';
$sub_message = '';
$icon_class = 'fas fa-lock';
$container_class = 'license-restriction-general';

if ($restriction_type === 'user_limit') {
    // User limit exceeded restriction
    $current_users = isset($_GET['current_users']) ? intval($_GET['current_users']) : ($restriction_details['current_users'] ?? 0);
    $max_users = isset($_GET['max_users']) ? intval($_GET['max_users']) : ($restriction_details['max_users'] ?? 5);
    
    $page_title = 'Kullanıcı Limiti Aşıldı';
    $main_message = 'Kullanıcı Sayısını Aştınız!';
    $sub_message = sprintf(
        'Mevcut kullanıcı sayısı: <strong>%d</strong><br>Lisansınızın izin verdiği maksimum kullanıcı sayısı: <strong>%d</strong><br><br>Lütfen kullanıcı sayısını lisansınızın izin verdiği kadar kullanın veya yeni lisans satın alın.',
        $current_users,
        $max_users
    );
    $icon_class = 'fas fa-users';
    $container_class = 'license-restriction-user-limit';
} elseif ($restriction_type === 'module' && $restriction_details) {
    // Enhanced module restriction
    $page_title = 'Modül Erişimi Kısıtlı';
    $main_message = 'Bu Modüle Erişim İzniniz Yok';
    $sub_message = isset($restriction_details['message']) ? $restriction_details['message'] : 'Bu özellik lisansınıza dahil değil.';
    $icon_class = 'fas fa-ban';
    $container_class = 'license-restriction-module';
} elseif ($restriction_type === 'data') {
    // Data/general restriction
    $page_title = 'Veri Erişimi Kısıtlı';
    $main_message = 'Bu Veriye Erişim İzniniz Yok';
    $sub_message = 'Bu veri türüne erişim lisansınıza dahil değil veya yetkileriniz yeterli değil.';
    $icon_class = 'fas fa-database';
    $container_class = 'license-restriction-data';
} else {
    // Default restriction
    $main_message = 'Erişim Kısıtlı';
    $sub_message = 'Bu sayfaya veya özelliğe erişim izniniz bulunmamaktadır.';
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($page_title); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .restriction-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
            padding: 3rem 2rem;
            text-align: center;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .restriction-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.8;
        }
        
        .license-restriction-user-limit .restriction-icon {
            color: #e74c3c;
        }
        
        .license-restriction-module .restriction-icon {
            color: #f39c12;
        }
        
        .license-restriction-data .restriction-icon {
            color: #9b59b6;
        }
        
        .license-restriction-general .restriction-icon {
            color: #34495e;
        }
        
        h1 {
            color: #2c3e50;
            margin: 0 0 1rem 0;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .sub-message {
            color: #7f8c8d;
            line-height: 1.6;
            font-size: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }
        
        .license-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin: 1.5rem 0;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .license-info strong {
            color: #495057;
        }
        
        @media (max-width: 600px) {
            .restriction-container {
                padding: 2rem 1.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="restriction-container <?php echo esc_attr($container_class); ?>">
        <div class="restriction-icon">
            <i class="<?php echo esc_attr($icon_class); ?>"></i>
        </div>
        
        <h1><?php echo esc_html($main_message); ?></h1>
        
        <div class="sub-message">
            <?php echo wp_kses_post($sub_message); ?>
        </div>
        
        <?php if (!empty($license_info)): ?>
        <div class="license-info">
            <strong>Lisans Bilgileri:</strong><br>
            <?php if (!empty($license_info['package'])): ?>
            Paket: <?php echo esc_html($license_info['package']); ?><br>
            <?php endif; ?>
            <?php if (!empty($license_info['type_description'])): ?>
            Tür: <?php echo esc_html($license_info['type_description']); ?><br>
            <?php endif; ?>
            <?php if (!empty($license_info['expiry']) && $license_info['expiry'] !== '0000-00-00'): ?>
            Son Geçerlilik: <?php echo esc_html(date('d.m.Y', strtotime($license_info['expiry']))); ?><br>
            <?php endif; ?>
            <?php if (isset($license_info['user_limit'])): ?>
            Kullanıcı Limiti: <?php echo esc_html($license_info['user_limit']); ?><br>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <?php if ($restriction_type === 'user_limit'): ?>
                <a href="<?php echo esc_url(add_query_arg('view', 'all_personnel', home_url())); ?>" class="btn btn-primary">
                    <i class="fas fa-users"></i>
                    Kullanıcı Yönetimi
                </a>
                <a href="<?php echo esc_url(add_query_arg('view', 'license-management', home_url())); ?>" class="btn btn-secondary">
                    <i class="fas fa-key"></i>
                    Lisans Yönetimi
                </a>
            <?php else: ?>
                <a href="<?php echo esc_url(add_query_arg('view', 'license-management', home_url())); ?>" class="btn btn-primary">
                    <i class="fas fa-key"></i>
                    Lisans Yönetimi
                </a>
                <a href="<?php echo esc_url(home_url()); ?>" class="btn btn-secondary">
                    <i class="fas fa-home"></i>
                    Ana Sayfaya Dön
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>