# Amelia → Netgsm SMS by AtomixBilişim

[![DOI](https://zenodo.org/badge/DOI/10.5281/zenodo.24462815.svg)](https://doi.org/10.5281/zenodo.24462815)

[English](#english) · [Türkçe](#türkçe)

## English

A WordPress plugin that adds a token protected REST endpoint. Point an Amelia webhook at it and it sends the customer an SMS through the Netgsm REST API when the webhook fires. Amelia has no Netgsm integration of its own, and most Turkish salons and clinics already have a Netgsm account, so this fills that gap with one small file.

A newer variant with per-event messages and more settings lives in [amelia-netgsm-atomix-webhook](https://github.com/atakanozdal/amelia-netgsm-atomix-webhook).

### Requirements

- WordPress 4.7 or later (uses the REST API, `register_rest_route`, `wp_remote_post`)
- PHP 7.0 or later (the code uses the `??` operator)
- Amelia with webhook support
- A Netgsm account with API access and an approved SMS header (msgheader), enabled for web service use in the Netgsm panel

### Installation

1. Download this repository as a ZIP, or copy `amelia-netgsm-atomix.php` into `wp-content/plugins/amelia-netgsm-atomix/`.
2. Activate "Amelia → Netgsm SMS by AtomixBilişim" in Plugins. A 24 character token is created on activation.
3. Open Settings → Amelia → Netgsm SMS, enter your Netgsm details and save.
4. Copy the Webhook URL shown at the top of the page.
5. In Amelia, add a webhook with that URL (method POST, JSON body). To get the right message per event, add one webhook per event and put `&evt=` at the end of the URL:

```
...&evt=booking_added
...&evt=booking_canceled
...&evt=booking_rescheduled
...&evt=booking_status_updated
```

### Configuration

Settings are stored in the `amelia_netgsm_atomix_settings` option.

| Field | Option key | Notes |
|---|---|---|
| Usercode | `usercode` | Netgsm user code |
| Password | `password` | Netgsm API password. The field stays empty after saving; leave it empty to keep the stored password |
| Msgheader | `msgheader` | Approved sender name |
| (none) | `token` | Generated on activation, or when the settings page is opened and no token exists. Shown inside the Webhook URL. There is no button to change it. |

All three Netgsm fields are required. Encoding is fixed to `TR`.

### How it works

The plugin registers `POST /wp-json/amelia-netgsm/v1/hook`. On each request it:

1. Compares the `token` parameter with the stored token and returns 403 if they differ. If no token is stored, every request gets 403.
2. Reads the customer from `customer.*`, or from `appointment.bookings[0].customer.*` (the shape Amelia actually sends). The service comes from `appointment.service.name` or `service.name`. Date and time come from `appointment.date` and `appointment.time`, or are taken from `appointment.bookingStart` (`YYYY-MM-DD HH:MM:SS`).
3. Normalizes the phone to `905XXXXXXXXX`. Returns 400 if there is no phone.
4. Picks the message by the `evt` parameter (URL or JSON body) and the appointment status:

   | `evt` | Message |
   |---|---|
   | `booking_added` | Sayın {name}, {date} {time} tarihli {service} randevunuz oluşturulmuştur. |
   | `booking_canceled` | Sayın {name}, {date} {time} tarihli {service} randevunuz iptal edilmiştir. |
   | `booking_rescheduled` | Sayın {name}, {service} randevunuz {date} {time} olarak güncellenmiştir. |
   | `booking_status_updated` or missing | Chosen by the appointment status: `approved` onaylanmıştır, `pending` onay beklemektedir, `canceled` iptal edilmiştir, `rejected` reddedilmiştir. Any other status with `booking_status_updated`: için durum güncellenmiştir. |
   | missing, and no known status | Sayın {name}, {date} {time} tarihli {service} randevunuz hakkında bilgilendirmedir. |

   The camelCase names (`bookingAdded`) and the short names used by the webhook plugin (`created`, `canceled`, `rescheduled`, `status`) work too. If there is no name, "Müşterimiz" is used.

5. Returns `{"ok": true}` with 200, or `{"ok": false}` with 500 if Netgsm did not answer with code `00`.

### Example request

```bash
curl -X POST "https://example.com/wp-json/amelia-netgsm/v1/hook?token=YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "customer": { "firstName": "Ayşe", "lastName": "Yılmaz", "phone": "05XXXXXXXXX" },
    "appointment": {
      "service": { "name": "Cilt Bakımı" },
      "date": "2025-10-30",
      "time": "14:00"
    }
  }'
```

### Known limitations

- Amelia does not say in the payload which event fired, so without `evt` the message only follows the appointment status.
- Only the first booking in `appointment.bookings[]` gets an SMS. Group appointments with several customers are not handled.
- The date is sent as Amelia gives it (`YYYY-MM-DD`).
- No logging. A failed send only shows up as a 500 response to Amelia.
- The token cannot be regenerated from the admin page.
- The password is stored unencrypted in the options table.

### License

MIT. See [LICENSE](LICENSE).

Atakan Özdal · atomiXbilişim · https://atakanozdal.com.tr

## Türkçe

Token korumalı bir REST uç noktası ekleyen WordPress eklentisi. Amelia'da bir webhook'u bu adrese yönlendirdiğinizde, webhook her tetiklendiğinde müşteriye Netgsm REST API üzerinden SMS gider. Amelia'nın kendi Netgsm entegrasyonu yok, Türkiye'deki salon ve kliniklerin çoğunda ise zaten Netgsm hesabı var. Bu eklenti o boşluğu tek bir dosyayla kapatıyor.

Olaya göre farklı mesaj gönderen ve daha fazla ayarı olan yeni sürüm [amelia-netgsm-atomix-webhook](https://github.com/atakanozdal/amelia-netgsm-atomix-webhook) deposunda.

### Gereksinimler

- WordPress 4.7 veya üstü (REST API, `register_rest_route`, `wp_remote_post` kullanılıyor)
- PHP 7.0 veya üstü (kodda `??` operatörü var)
- Webhook desteği olan Amelia
- API erişimi olan bir Netgsm hesabı ve Netgsm panelinde web servis kullanımına açılmış onaylı bir SMS başlığı (msgheader)

### Kurulum

1. Depoyu ZIP olarak indirin ya da `amelia-netgsm-atomix.php` dosyasını `wp-content/plugins/amelia-netgsm-atomix/` klasörüne kopyalayın.
2. Eklentiler sayfasından "Amelia → Netgsm SMS by AtomixBilişim" eklentisini etkinleştirin. Etkinleştirme sırasında 24 karakterlik bir token oluşturulur.
3. Ayarlar → Amelia → Netgsm SMS sayfasında Netgsm bilgilerinizi girip kaydedin.
4. Sayfanın üstündeki Webhook URL'yi kopyalayın.
5. Amelia'da bu adresle bir webhook ekleyin (yöntem POST, gövde JSON). Her olay için doğru mesajın gitmesi için olay başına ayrı bir webhook ekleyip adresin sonuna `&evt=` koyun:

```
...&evt=booking_added
...&evt=booking_canceled
...&evt=booking_rescheduled
...&evt=booking_status_updated
```

### Ayarlar

Ayarlar `amelia_netgsm_atomix_settings` seçeneğinde saklanır.

| Alan | Anahtar | Açıklama |
|---|---|---|
| Usercode | `usercode` | Netgsm kullanıcı kodu |
| Password | `password` | Netgsm API şifresi. Kaydettikten sonra alan boş görünür; boş bırakırsanız kayıtlı şifre korunur |
| Msgheader | `msgheader` | Onaylı gönderici adı |
| (yok) | `token` | Etkinleştirmede ya da token yokken ayar sayfası açıldığında üretilir, Webhook URL'nin içinde görünür. Değiştirmek için bir düğme yok. |

Üç Netgsm alanı da zorunlu. Karakter kodlaması `TR` olarak sabit.

### Nasıl çalışır

Eklenti `POST /wp-json/amelia-netgsm/v1/hook` uç noktasını kaydeder. Her istekte:

1. `token` parametresini kayıtlı token ile karşılaştırır, tutmazsa 403 döner. Kayıtlı token yoksa her istek 403 alır.
2. Müşteriyi `customer.*` alanlarından ya da `appointment.bookings[0].customer.*` içinden (Amelia'nın gerçekte gönderdiği yapı) okur. Hizmet `appointment.service.name` ya da `service.name` alanından gelir. Tarih ve saat `appointment.date` ve `appointment.time` alanlarından, yoksa `appointment.bookingStart` (`YYYY-MM-DD HH:MM:SS`) değerinden alınır.
3. Telefonu `905XXXXXXXXX` biçimine çevirir. Telefon yoksa 400 döner.
4. Mesajı `evt` parametresine (adreste ya da JSON gövdede) ve randevu durumuna göre seçer:

   | `evt` | Mesaj |
   |---|---|
   | `booking_added` | Sayın {ad}, {tarih} {saat} tarihli {hizmet} randevunuz oluşturulmuştur. |
   | `booking_canceled` | Sayın {ad}, {tarih} {saat} tarihli {hizmet} randevunuz iptal edilmiştir. |
   | `booking_rescheduled` | Sayın {ad}, {hizmet} randevunuz {tarih} {saat} olarak güncellenmiştir. |
   | `booking_status_updated` ya da boş | Randevu durumuna göre: `approved` onaylanmıştır, `pending` onay beklemektedir, `canceled` iptal edilmiştir, `rejected` reddedilmiştir. `booking_status_updated` ile başka bir durumda: için durum güncellenmiştir. |
   | boş ve durum da bilinmiyor | Sayın {ad}, {tarih} {saat} tarihli {hizmet} randevunuz hakkında bilgilendirmedir. |

   camelCase adlar (`bookingAdded`) ve webhook eklentisindeki kısa adlar (`created`, `canceled`, `rescheduled`, `status`) da çalışır. Ad yoksa "Müşterimiz" yazılır.

5. Başarılıysa 200 ile `{"ok": true}`, Netgsm `00` kodu dönmezse 500 ile `{"ok": false}` döner.

### Örnek istek

```bash
curl -X POST "https://example.com/wp-json/amelia-netgsm/v1/hook?token=YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "customer": { "firstName": "Ayşe", "lastName": "Yılmaz", "phone": "05XXXXXXXXX" },
    "appointment": {
      "service": { "name": "Cilt Bakımı" },
      "date": "2025-10-30",
      "time": "14:00"
    }
  }'
```

### Bilinen kısıtlar

- Amelia hangi olayın tetiklendiğini gövdede söylemiyor. `evt` yoksa mesaj yalnızca randevu durumuna göre seçilir.
- `appointment.bookings[]` içindeki yalnızca ilk kayda SMS gider. Birden çok müşterili grup randevuları ele alınmıyor.
- Tarih Amelia'nın verdiği gibi (`YYYY-MM-DD`) gönderilir.
- Günlük kaydı yok. Başarısız gönderim yalnızca Amelia'ya dönen 500 yanıtında görünür.
- Token yönetim sayfasından yenilenemiyor.
- Şifre seçenekler tablosunda şifrelenmeden duruyor.

### Lisans

MIT. Ayrıntılar için [LICENSE](LICENSE).

Atakan Özdal · atomiXbilişim · https://atakanozdal.com.tr
