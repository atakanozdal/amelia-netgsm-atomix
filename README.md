# Amelia → Netgsm SMS (Hook Version)
**Developer:** Atakan Özdal  
**License:** MIT  
**Requires:** WordPress 5.4+, PHP 7.2+  

Amelia randevu eklentisinde gerçekleşen randevu işlemlerini (oluşturma, iptal, yeniden planlama) otomatik algılar ve Netgsm REST API üzerinden SMS gönderir.

---

## 🇹🇷 Özellikler
✅ Amelia Webhook gerekmez  
✅ Hook tabanlı direkt tetikleme  
✅ Netgsm REST v2 desteği  
✅ Hızlı test aracı  
✅ UTF-8 / TR mesaj desteği  
✅ Debug log kayıtları

---

## 🔧 Kurulum (TR)

1. ZIP dosyasını yükleyin  
   *Eklentiler → Yeni Ekle → Eklenti Yükle → Etkinleştir*
2. Yönetim panelinde açın:

Ayarlar → Amelia → Netgsm SMS

3. Netgsm bilgilerinizi girin
4. “Hızlı Test” ile doğrulayın ✅

> ⚠️ Mesaj başlığı (Msgheader) Netgsm panelinde  
**“Web Servis’te kullanım: AÇIK”** olmalı.

---

## 🧩 Nasıl Çalışır?
- Amelia event → Hook tetikler  
- Telefon no alınır → Sms hazırlanır → Netgsm’e gönderilir  
- Başarısız gönderimler `debug.log` içine yazılır

Log yolu:

/wp-content/debug.log


---

## 🧱 Cache / Güvenlik Ayarı
Cache eklentilerinde aşağıyı hariç tut:


/wp-admin/admin-ajax.php


Firewall’a izin:

api.netgsm.com.tr : 443


---

## 🇬🇧 English Quick Guide

### Install
1. Upload ZIP → Activate
2. Panel:
Settings → Amelia → Netgsm SMS

3. Enter Netgsm credentials
4. “Quick Test” → SMS must arrive ✅

---

## 📌 Changelog
| Version | Notes |
|--------|------|
| 1.0.0 | First release: Hook-based SMS |
| 1.1.0 | Debug improvements |
| 1.2.0 | XML fallback + error handling |

---

## 📄 License
MIT — © 2025 Atakan Özdal

