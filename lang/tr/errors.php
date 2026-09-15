<?php

// Human-readable labels for CheckOutcome::$errorClass, shown on the dashboard and in
// incident alert emails — e.g. "TLS sertifikası süresi dolmuş" instead of "site down".
return [
    'dns_nxdomain' => 'Alan adı çözümlenemedi',
    'ssrf_blocked' => 'Hedef adres güvenlik nedeniyle engellendi',
    'tcp_refused' => 'Bağlantı reddedildi',
    'tcp_timeout' => 'Bağlantı zaman aşımına uğradı',
    'timeout' => 'İstek zaman aşımına uğradı',
    'connection_reset' => 'Bağlantı sıfırlandı',
    'tls_expired' => 'TLS sertifikasının süresi dolmuş',
    'tls_self_signed' => 'TLS sertifikası kendinden imzalı',
    'tls_expiring' => 'TLS sertifikasının süresi yakında doluyor',
    'tls_error' => 'TLS bağlantı hatası',
    'http_5xx' => 'Sunucu hatası (5xx)',
    'http_unexpected_status' => 'Beklenmeyen HTTP durum kodu',
    'keyword_missing' => 'Beklenen anahtar kelime bulunamadı',
    'keyword_present' => 'İstenmeyen anahtar kelime bulundu',
    'redirect_loop' => 'Yönlendirme döngüsü veya çok fazla yönlendirme',
    'heartbeat_missed' => 'Heartbeat sinyali beklenen sürede alınamadı',
    'unknown_error' => 'Bilinmeyen hata',
];
