# Lisans Doğrulama Sistemi Kullanım Kılavuzu

## API Endpoint'leri

License Manager eklentisi aşağıdaki REST API endpoint'lerini sağlar:

### 1. Lisans Doğrulama
```
POST /wp-json/balkay-license/v1/validate_license
```

**Alternatif Endpoint:**
```
POST /wp-json/balkay-license/v1/validate
```

**Ek API Endpoint:**
```
POST /api/validate_license
```

**Parameters:**
- `license_key` (zorunlu): Lisans anahtarı
- `domain` (zorunlu): Doğrulanacak alan adı
- `action` (opsiyonel): Varsayılan "validate"

**Örnek Kullanım:**
```bash
curl -X POST "https://www.balkay.net/crm/wp-json/balkay-license/v1/validate_license" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "LM-XXXXXXXX-XXXXXXXX",
    "domain": "example.com"
  }'
```

**Alternatif kullanım (/validate endpoint):**
```bash
curl -X POST "https://www.balkay.net/crm/wp-json/balkay-license/v1/validate" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "LM-XXXXXXXX-XXXXXXXX",
    "domain": "example.com"
  }'
```

**API endpoint kullanımı:**
```bash
curl -X POST "https://www.balkay.net/crm/api/validate_license" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "LM-XXXXXXXX-XXXXXXXX",
    "domain": "example.com"
  }'
```

### 2. Lisans Bilgileri
```
GET /wp-json/balkay-license/v1/license_info?license_key=KEY
```

**Örnek Kullanım:**
```bash
curl "https://www.balkay.net/crm/wp-json/balkay-license/v1/license_info?license_key=LM-XXXXXXXX-XXXXXXXX"
```

### 3. Durum Kontrolü
```
POST /wp-json/balkay-license/v1/check_status
```

**Parameters:** validate_license ile aynı

## Yanıt Formatı

Tüm endpoint'ler aşağıdaki JSON formatında yanıt döner:

```json
{
    "status": "active|expired|invalid|suspended",
    "license_type": "monthly|yearly|lifetime",
    "expires_on": "YYYY-MM-DD",
    "user_limit": 10,
    "modules": ["customers", "policies", "tasks", "reports"],
    "message": "Durum mesajı"
}
```

## CRM Sisteminde Kullanım

Insurance CRM sistemi lisans doğrulaması için aşağıdaki adımları takip eder:

1. **Lisans Anahtarı Girişi**: Kullanıcı CRM sistemine lisans anahtarını girer
2. **Domain Tespiti**: Sistem otomatik olarak mevcut domain'i tespit eder
3. **API Çağrısı**: License Manager API'sine doğrulama isteği gönderilir
4. **Yanıt İşleme**: API'den gelen yanıta göre lisans durumu belirlenir

### CRM'de Örnek PHP Kodu:

```php
function validate_license($license_key, $domain) {
    $api_url = 'https://www.balkay.net/crm/wp-json/balkay-license/v1/validate_license';
    
    $data = array(
        'license_key' => $license_key,
        'domain' => $domain
    );
    
    $response = wp_remote_post($api_url, array(
        'body' => json_encode($data),
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    return $result;
}

// Kullanım
$license_result = validate_license('LM-XXXXXXXX-XXXXXXXX', $_SERVER['HTTP_HOST']);

if ($license_result && $license_result['status'] === 'active') {
    // Lisans geçerli, CRM kullanımına izin ver
    define('LICENSE_VALID', true);
} else {
    // Lisans geçersiz, CRM kullanımını engelle
    define('LICENSE_VALID', false);
}
```

## Hata Ayıklama

### 1. WordPress Hata Loglarını Kontrol Edin
```bash
tail -f /path/to/wordpress/wp-content/debug.log
```

### 2. API Endpoint'lerini Test Edin
WordPress admin panelinde: **Araçlar > Site Health > Info > Server**

### 3. Lisans Detaylarını Kontrol Edin
- Lisans anahtarının doğru olduğundan emin olun
- Domain'in izinli domainler listesinde olduğunu kontrol edin
- Lisans durumunun "aktif" olduğunu doğrulayın
- Lisans süresinin dolmadığından emin olun

### 4. Domain Eşleştirme Sorunları
Sistem aşağıdaki domain formatlarını kabul eder:
- `example.com`
- `www.example.com`
- `https://example.com`
- `https://www.example.com`

## Yaygın Hatalar ve Çözümleri

### "Geçersiz lisans anahtarı"
- Lisans anahtarının doğru yazıldığından emin olun
- Lisansın License Manager'da mevcut olduğunu kontrol edin

### "Bu lisans için alan adı yetkilendirilmemiş"
- Müşteri kaydında "İzinli Alan Adları" alanını kontrol edin
- Domain'i doğru formatda girdiğinizden emin olun

### "Lisans süresi dolmuş"
- Lisans geçerlilik tarihini kontrol edin
- Gerekirse lisansı yenileyin veya uzatın

## İletişim

Teknik destek için lütfen repository'deki issue'ları kullanın veya sistem yöneticisiyle iletişime geçin.

## Gelişmiş Hata Ayıklama

### Tanı Aracı

Endpoint'lerde sorun yaşıyorsanız, tanı aracını kullanın:

1. `diagnostic.php` dosyasını WordPress kök dizinine yükleyin
2. `https://yourdomain.com/diagnostic.php` adresine erişin
3. Sonuçları gözden geçirin ve sorunları tespit edin

### Yaygın HTTP 404 Hataları

**Endpoint'lerde 404 Hatası:**
- Plugin'in aktif olduğundan emin olun
- Ayarlar → Kalıcı Bağlantılar → Değişiklikleri Kaydet ile rewrite rules'ları temizleyin
- REST API'nin etkin ve engellenmeyen olduğunu kontrol edin
- Sunucunun `/api/` yollarını engellemediğini doğrulayın

**Rewrite Rules Çalışmıyor:**
- Plugin'i deaktif edip tekrar aktif edin
- Tüm önbellekleri temizleyin (sunucu, plugin, CDN)
- `.htaccess` dosyasında çakışma olup olmadığını kontrol edin

**REST API Sorunları:**
- WordPress REST API'nin devre dışı bırakılmadığını kontrol edin
- Plugin çakışmalarını kontrol edin
- Sunucu yapılandırmasının REST API isteklerine izin verdiğini doğrulayın

### Manuel Endpoint Testi

Endpoint'leri manuel olarak test edin:

```bash
# Ana endpoint'leri test et
curl -X GET "https://yourdomain.com/wp-json/balkay-license/v1/test"
curl -X POST "https://yourdomain.com/wp-json/balkay-license/v1/validate" \
  -H "Content-Type: application/json" \
  -d '{"license_key": "TEST-KEY", "domain": "test.com"}'
```