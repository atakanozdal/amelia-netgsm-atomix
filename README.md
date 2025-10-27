Amelia randevu eklentisindeki randevu oluşturma / değiştirme / iptal işlemlerini WordPress Hook’larıyla algılayarak, Netgsm REST v2 API üzerinden otomatik SMS gönderir.
Ekstra servis gerekmez — tamamen WordPress içinde çalışır.

🇹🇷 Türkçe Kurulum & Kullanım
Gereksinimler

WordPress 5.4+

PHP 7.2+

Amelia (herhangi bir sürüm — webhook gerekmez)

Netgsm Web Servis API yetkisi

Kurulum Adımları

1️⃣ Eklentiyi ZIP formatında yükleyin:
🛠 amelia-netgsm-atomix.zip → Eklentiler → Yeni Ekle → Yükle → Etkinleştir

2️⃣ Yönetim panelinden gidin:
⚙️ Ayarlar → Amelia → Netgsm SMS

3️⃣ Netgsm bilgilerini girin

Usercode

Password (API şifresi)

Msgheader (Web Servis’te kullanım AÇIK olmalı)

Encoding seçin (TR önerilir)

4️⃣ ✅ "Hızlı Test" ile SMS göndererek doğrulayın

Nasıl Çalışır?

Amelia randevu olayları → Hook → Telefon no → Netgsm’e gönderim

Türkçe mesaj şablonları otomatik doldurulur

Başarısız olursa log’a yazılır

debug.log → /wp-content/debug.log

Cache / Firewall Ayarı

Cache varsa güvenlik için istisna ekle:

/wp-admin/admin-ajax.php


Firewall:
api.netgsm.com.tr adresine 443 çıkış izni olmalı

🇬🇧 English — Installation & Usage
Requirements

WordPress 5.4+

PHP 7.2+

Amelia installed

Netgsm REST credentials

Setup

Upload & activate plugin

Open: Settings → Amelia → Netgsm SMS

Fill Netgsm credentials

Use Quick Test to verify SMS sending

Changelog
Version	Notes
1.0.0	İlk sürüm — Hook tabanlı SMS, admin paneli, test aracı
1.1.0	Debug geliştirme
1.2.0	XML fallback, hata iyileştirme
License

MIT — © 2025 Atakan Özdal
