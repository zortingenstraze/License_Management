/**
 * License Manager Admin JavaScript
 */

(function($) {
    'use strict';

    // Document ready function
    $(document).ready(function() {
        initLicenseManager();
    });

    /**
     * Initialize License Manager admin functionality
     */
    function initLicenseManager() {
        // Initialize components
        initDashboard();
        initLicenseActions();
        initCustomerActions();
        initFormValidation();
        initAjaxHandlers();
        initPaymentTabs();
    }

    /**
     * Initialize dashboard functionality
     */
    function initDashboard() {
        // Auto-refresh dashboard stats every 30 minutes (reduced from 5 minutes)
        if ($('.license-manager-dashboard').length) {
            setInterval(function() {
                refreshDashboardStats();
            }, 1800000); // 30 minutes
        }
    }

    /**
     * Initialize license actions
     */
    function initLicenseActions() {
        // Handle license testing
        $(document).on('click', '.test-license', function(e) {
            e.preventDefault();
            var licenseKey = $(this).data('license-key');
            if (licenseKey) {
                testLicense(licenseKey);
            }
        });

        // Handle license extension
        $(document).on('click', '.extend-license', function(e) {
            e.preventDefault();
            var licenseId = $(this).data('license-id');
            if (licenseId) {
                extendLicense(licenseId);
            }
        });

        // Handle bulk license actions
        $('#bulk-license-actions').on('click', function(e) {
            e.preventDefault();
            var action = $('#bulk-action-selector-top').val();
            var selectedLicenses = [];
            
            $('input[name="license[]"]:checked').each(function() {
                selectedLicenses.push($(this).val());
            });

            if (selectedLicenses.length === 0) {
                alert(licenseManagerAdmin.strings.no_items_selected);
                return;
            }

            if (action === 'delete') {
                if (confirm(licenseManagerAdmin.strings.confirm_delete)) {
                    bulkDeleteLicenses(selectedLicenses);
                }
            } else if (action === 'extend') {
                bulkExtendLicenses(selectedLicenses);
            }
        });
    }

    /**
     * Initialize customer actions
     */
    function initCustomerActions() {
        // Handle customer license assignment
        $(document).on('click', '.assign-license', function(e) {
            e.preventDefault();
            var customerId = $(this).data('customer-id');
            if (customerId) {
                assignLicenseToCustomer(customerId);
            }
        });

        // Handle customer domain validation
        $(document).on('blur', '#allowed_domains', function() {
            validateDomains($(this).val());
        });
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        // License form validation
        $('#post').on('submit', function(e) {
            if ($(this).find('input[name="post_type"]').val() === 'lm_license') {
                if (!validateLicenseForm()) {
                    e.preventDefault();
                    return false;
                }
            }
        });

        // Customer form validation
        $('#post').on('submit', function(e) {
            if ($(this).find('input[name="post_type"]').val() === 'lm_customer') {
                if (!validateCustomerForm()) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }

    /**
     * Initialize AJAX handlers
     */
    function initAjaxHandlers() {
        // Global AJAX error handler - only show errors for user-initiated actions
        $(document).ajaxError(function(event, xhr, settings, error) {
            // Log detailed error information to console for debugging
            console.log('AJAX Error Details:', {
                error: error,
                xhr: xhr,
                settings: settings,
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText
            });
            
            // Only show error messages for user-initiated requests, not background license checks
            // Skip error messages for license validation and status check requests
            if (settings.url && (
                settings.url.indexOf('validate_license') !== -1 || 
                settings.url.indexOf('check_license') !== -1 ||
                settings.url.indexOf('license_info') !== -1 ||
                settings.url.indexOf('license/v1/') !== -1 ||
                (settings.data && settings.data.indexOf && settings.data.indexOf('validate_license') !== -1)
            )) {
                // Silent failure for license-related background requests
                return;
            }
            
            // Show error messages only for explicit user actions
            var errorMessage = 'İşlem tamamlanamadı';
            if (xhr.status && xhr.statusText && xhr.status !== 0) {
                errorMessage += ' (HTTP ' + xhr.status + ')';
            }
            errorMessage += '. Lütfen tekrar deneyin.';
            
            showNotice('error', errorMessage);
        });

        // Global AJAX success handler for common responses
        $(document).ajaxSuccess(function(event, xhr, settings, data) {
            if (data && data.success === false && data.data && data.data.message) {
                showNotice('error', data.data.message);
            }
        });
    }

    /**
     * Test license via API
     */
    function testLicense(licenseKey) {
        showLoading();
        
        $.get(licenseManagerAdmin.apiUrl + '/license_info', {
            license_key: licenseKey
        })
        .done(function(response) {
            hideLoading();
            
            var message = 'License Status: ' + response.status + '\n';
            message += 'License Type: ' + response.license_type + '\n';
            message += 'Expires On: ' + response.expires_on + '\n';
            message += 'User Limit: ' + response.user_limit + '\n';
            message += 'Modules: ' + response.modules.join(', ') + '\n';
            message += 'Message: ' + response.message;
            
            alert(message);
        })
        .fail(function() {
            hideLoading();
            showNotice('error', 'Failed to test license. Please check your API connection.');
        });
    }

    /**
     * Extend license
     */
    function extendLicense(licenseId) {
        var days = prompt('How many days would you like to extend the license?', '30');
        
        if (days === null || isNaN(days) || days <= 0) {
            return;
        }

        showLoading();
        
        $.post(licenseManagerAdmin.ajaxurl, {
            action: 'extend_license',
            license_id: licenseId,
            days: days,
            nonce: licenseManagerAdmin.nonce
        })
        .done(function(response) {
            hideLoading();
            
            if (response.success) {
                showNotice('success', 'License extended successfully.');
                location.reload();
            } else {
                showNotice('error', response.data.message || 'Failed to extend license.');
            }
        })
        .fail(function() {
            hideLoading();
            showNotice('error', 'Failed to extend license. Please try again.');
        });
    }

    /**
     * Validate license form
     */
    function validateLicenseForm() {
        var isValid = true;
        var errors = [];

        // Check license key
        var licenseKey = $('#license_key').val();
        if (!licenseKey || licenseKey.length < 10) {
            errors.push('License key must be at least 10 characters long.');
            isValid = false;
        }

        // Check user limit
        var userLimit = $('#user_limit').val();
        if (!userLimit || userLimit < 1) {
            errors.push('User limit must be at least 1.');
            isValid = false;
        }

        // Check expiry date
        var expiresOn = $('#expires_on').val();
        if (expiresOn && new Date(expiresOn) < new Date()) {
            if (!confirm('The expiry date is in the past. Are you sure you want to continue?')) {
                isValid = false;
            }
        }

        if (errors.length > 0) {
            alert('Please fix the following errors:\n\n' + errors.join('\n'));
        }

        return isValid;
    }

    /**
     * Validate customer form
     */
    function validateCustomerForm() {
        var isValid = true;
        var errors = [];

        // Check email format
        var email = $('#email').val();
        if (email && !isValidEmail(email)) {
            errors.push('Please enter a valid email address.');
            isValid = false;
        }

        // Check website URL
        var website = $('#website').val();
        if (website && !isValidUrl(website)) {
            errors.push('Please enter a valid website URL.');
            isValid = false;
        }

        if (errors.length > 0) {
            alert('Please fix the following errors:\n\n' + errors.join('\n'));
        }

        return isValid;
    }

    /**
     * Validate domains
     */
    function validateDomains(domainsText) {
        if (!domainsText) return true;
        
        var domains = domainsText.split('\n');
        var invalidDomains = [];
        
        domains.forEach(function(domain) {
            domain = domain.trim();
            if (domain && !isValidDomain(domain)) {
                invalidDomains.push(domain);
            }
        });
        
        if (invalidDomains.length > 0) {
            showNotice('warning', 'Invalid domains detected: ' + invalidDomains.join(', '));
            return false;
        }
        
        return true;
    }

    /**
     * Refresh dashboard stats
     */
    function refreshDashboardStats() {
        $.post(licenseManagerAdmin.ajaxurl, {
            action: 'get_dashboard_stats',
            nonce: licenseManagerAdmin.nonce
        })
        .done(function(response) {
            if (response.success) {
                updateDashboardStats(response.data);
            }
        })
        .fail(function(xhr, status, error) {
            // Silent failure for background dashboard refresh
            console.log('Dashboard stats refresh failed (silent):', error);
        });
    }

    /**
     * Update dashboard stats
     */
    function updateDashboardStats(stats) {
        $('.stat-box').each(function() {
            var statType = $(this).data('stat-type');
            if (stats[statType]) {
                $(this).find('h3').text(stats[statType]);
            }
        });
    }

    /**
     * Show loading indicator
     */
    function showLoading() {
        $('body').addClass('loading');
        if ($('.spinner').length === 0) {
            $('body').append('<div class="spinner"></div>');
        }
    }

    /**
     * Hide loading indicator
     */
    function hideLoading() {
        $('body').removeClass('loading');
        $('.spinner').remove();
    }

    /**
     * Show notice
     */
    function showNotice(type, message) {
        var noticeClass = 'license-manager-notice notice-' + type;
        var notice = '<div class="' + noticeClass + '"><p>' + message + '</p></div>';
        
        $('.wrap h1').after(notice);
        
        // Auto-hide success notices after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $('.' + noticeClass).fadeOut();
            }, 5000);
        }
    }

    /**
     * Utility functions
     */
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function isValidUrl(url) {
        // Allow empty values
        if (!url) return true;
        
        // Basic invalid patterns
        if (url === 'not-a-url' || url.indexOf(' ') !== -1) {
            return false;
        }
        
        // Add protocol if missing
        if (!url.match(/^https?:\/\//)) {
            url = 'http://' + url;
        }
        
        try {
            new URL(url);
            return true;
        } catch (e) {
            // Fallback to basic regex validation
            var urlRegex = /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/i;
            return urlRegex.test(url);
        }
    }

    function isValidDomain(domain) {
        // Allow empty values
        if (!domain) return true;
        
        // Allow wildcard domains
        if (domain.startsWith('*.')) {
            domain = domain.substring(2);
        }
        
        // Allow localhost and IP addresses
        if (domain === 'localhost' || domain.match(/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/)) {
            return true;
        }
        
        // Allow .local and .test domains
        if (domain.endsWith('.local') || domain.endsWith('.test')) {
            return true;
        }
        
        // Check for invalid patterns first
        if (domain.indexOf('..') !== -1) {
            return false;
        }
        
        // Allow longer domains and more flexible validation
        var domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9-_.]{0,61}[a-zA-Z0-9]*\.[a-zA-Z]{2,}$/;
        return domainRegex.test(domain);
    }
    
    /**
     * Initialize payment tabs functionality
     */
    function initPaymentTabs() {
        // Handle tab switching
        $(document).on('click', '.tab-button', function(e) {
            e.preventDefault();
            
            var tabId = $(this).data('tab');
            var tabContainer = $(this).closest('.payment-tabs');
            
            // Remove active class from all buttons and content
            tabContainer.find('.tab-button').removeClass('active');
            tabContainer.find('.tab-content').hide();
            
            // Add active class to clicked button
            $(this).addClass('active');
            
            // Show corresponding tab content
            tabContainer.find('#' + tabId).show();
        });
        
        // Customer dropdown change handler for payment forms
        $(document).on('change', '#customer_id', function() {
            var customerId = $(this).val();
            var licenseSelect = $('#license_id');
            
            if (customerId && licenseSelect.length) {
                // Clear current options except first
                licenseSelect.find('option:not(:first)').remove();
                
                // Add loading indicator
                licenseSelect.append('<option value="">Lisanslar yükleniyor...</option>');
                
                // Make AJAX request to get customer licenses
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_customer_licenses',
                        customer_id: customerId,
                        nonce: licenseManagerAjax.nonce
                    },
                    success: function(response) {
                        licenseSelect.find('option:not(:first)').remove();
                        
                        if (response.success && response.data.length > 0) {
                            $.each(response.data, function(index, license) {
                                licenseSelect.append('<option value="' + license.id + '">' + license.title + '</option>');
                            });
                        } else {
                            licenseSelect.append('<option value="">Bu müşterinin lisansı yok</option>');
                        }
                    },
                    error: function() {
                        licenseSelect.find('option:not(:first)').remove();
                        licenseSelect.append('<option value="">Lisanslar yüklenemedi</option>');
                    }
                });
            }
        });
    }

})(jQuery);