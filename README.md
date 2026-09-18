# Upmonia

Upmonia, sitelerin ve servislerin kullanılabilirliğini izleyen bir uptime monitoring uygulamasıdır. HTTP, TCP ve diğer kontrol türleriyle periyodik sağlık kontrolleri yapar, kesintileri (incident) otomatik tespit eder ve tanımlı kanallar üzerinden uyarı gönderir.

## Özellikler

- **Monitörler** — HTTP/TCP vb. hedeflerin periyodik olarak kontrol edilmesi
- **Incident takibi** — kesinti başlangıç/bitişlerinin otomatik kaydı ve zaman çizelgesi
- **Alert kanalları** — e-posta (Resend) gibi kanallar üzerinden bildirim
- **Status page** — seçilen monitörlerin herkese açık durum sayfası
- **Bakım pencereleri** — planlı bakımlarda uyarıları susturma
- **Çoklu organizasyon** — organizasyon/üyelik tabanlı erişim
- **Rollup'lar** — ham kontrol sonuçlarının 1 dakikalık/1 saatlik özetlere indirgenmesi ve saklama süresi sınırlı ham veri

## Teknoloji

- Laravel + Livewire
- Tailwind CSS (Vite)
- MySQL

## Kurulum

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Yerel veritabanı için Docker kullanılıyor:

```bash
docker compose up -d
php artisan migrate
```

Geliştirme sunucusunu başlat:

```bash
composer run dev
```

Bu komut `php artisan serve`, kuyruk dinleyicisi ve Vite dev sunucusunu birlikte ayağa kaldırır.

## Ortam Değişkenleri

Önemli `.env` ayarları için `config/upmonia.php` dosyasına bakın:

- `RESEND_API_KEY`, `RESEND_FROM` — giden e-posta (Resend)
- `DEFAULT_MONITOR_REGION` — bu deployment'ın izleme bölgesi etiketi
- `CHECK_RETENTION_HOURS` — ham kontrol sonuçlarının saklanma süresi
- `UPMONIA_ALLOW_PRIVATE_TARGETS` — geliştirmede özel/iç ağ hedeflerine izin verir (production'da her zaman kapalı)
- `DEPLOY_TOKEN` — SSH erişimi olmayan hosting ortamlarında `/deploy` endpoint'i üzerinden migration/optimize tetiklemek için

## Lisans

Bu proje [Laravel](https://laravel.com) framework'ü üzerine inşa edilmiştir.
