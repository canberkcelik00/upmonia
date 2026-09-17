<?php

return [

    'welcome' => [
        'subject' => 'Upmonia\'ya hoş geldiniz',
        'greeting' => 'Merhaba :name,',
        'body' => 'Upmonia hesabınız oluşturuldu. Şimdi ilk monitörünüzü ekleyerek başlayabilirsiniz.',
        'cta' => 'Panele git',
    ],

    'verify_email' => [
        'subject' => 'E-posta adresinizi doğrulayın',
        'greeting' => 'Merhaba :name,',
        'body' => 'E-posta adresinizi doğrulamak için aşağıdaki bağlantıya tıklayın. Bağlantı 24 saat geçerlidir.',
        'cta' => 'E-postamı doğrula',
        'ignore' => 'Bu isteği siz yapmadıysanız bu e-postayı görmezden gelebilirsiniz.',
    ],

    'reset_password' => [
        'subject' => 'Parola sıfırlama isteği',
        'greeting' => 'Merhaba :name,',
        'body' => 'Hesabınız için bir parola sıfırlama isteği aldık. Yeni bir parola belirlemek için aşağıdaki bağlantıya tıklayın. Bağlantı 60 dakika geçerlidir.',
        'cta' => 'Parolamı sıfırla',
        'ignore' => 'Bu isteği siz yapmadıysanız bu e-postayı görmezden gelebilirsiniz, parolanız değişmeyecektir.',
    ],

    'channel_verify' => [
        'subject' => 'Bildirim kanalını doğrulayın',
        'body' => ':org sizi bir uyarı bildirim kanalı olarak eklemek istedi. Onaylamak için aşağıdaki bağlantıya tıklayın. Bağlantı 24 saat geçerlidir.',
        'cta' => 'Kanalı doğrula',
        'ignore' => 'Bu isteği siz başlatmadıysanız bu e-postayı görmezden gelebilirsiniz.',
    ],

    'client_label' => 'Müşteri',

    'incident_triggered' => [
        'subject' => 'Kesinti — :monitor',
        'body' => ':monitor için bir kesinti tespit edildi.',
        'cause' => 'Neden',
        'started_at' => 'Başlangıç',
        'flapping_warning' => 'Bu monitör son 10 dakikada birden fazla kez durum değiştirdi (flapping) — ağ kaynaklı geçici bir sorun olabilir.',
        'cta' => 'Panelde görüntüle',
    ],

    'test_email' => [
        'subject' => 'Upmonia test e-postası',
        'body' => 'Bu, ":channel" bildirim kanalı için gönderilen bir test e-postasıdır. Bunu görüyorsanız kanal doğru çalışıyor demektir.',
    ],

    'incident_resolved' => [
        'subject' => 'Düzeldi — :monitor',
        'body' => ':monitor için kesinti sona erdi.',
        'duration' => 'Süre',
        'cta' => 'Panelde görüntüle',
    ],

];
