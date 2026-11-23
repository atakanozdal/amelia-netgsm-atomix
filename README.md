# Amelia → Netgsm SMS by AtomixBilişim

[English](#english) · [Türkçe](#türkçe)

## English

A WordPress plugin that adds a token protected REST endpoint. Point an Amelia webhook at it and it sends the customer an SMS through the Netgsm REST API when the webhook fires. Amelia has no Netgsm integration of its own, and most Turkish salons and clinics already have a Netgsm account, so this fills that gap with one small file.

A newer variant with per-event messages and more settings lives in [amelia-netgsm-atomix-webhook](https://github.com/atakanozdal/amelia-netgsm-atomix-webhook).

### Requirements

- WordPress 4.7 or later (uses the REST API, `register_rest_route`, `wp_remote_post`)
- PHP 7.0 or later (the code uses the `??` operator)
- Amelia with webhook support
- A Netgsm account with API access and an approved SMS header (msgheader), enabled for web service use in the Netgsm panel
- Pretty permalinks. The webhook URL shown in the admin is built as `/wp-json/...`, which does not work with plain permalinks.

### Installation

1. Download this repository as a ZIP, or copy `amelia-netgsm-atomix.php` into `wp-content/plugins/amelia-netgsm-atomix/`.
2. Activate "Amelia → Netgsm SMS by AtomixBilişim" in Plugins. A 24 character token is created on activation.
3. Open Settings → Amelia → Netgsm SMS, enter your Netgsm details and save.
4. Copy the Webhook URL shown at the top of the page.
5. In Amelia, add a webhook with that URL (method POST, JSON body).

### Configuration

Settings are stored in the `amelia_netgsm_atomix_settings` option.

| Field | Option key | Notes |
|---|---|---|
| Usercode | `usercode` | Netgsm user code |
| Password | `password` | Netgsm API password |
| Msgheader | `msgheader` | Approved sender name |
| (none) | `token` | Generated on activation, shown inside the Webhook URL. There is no button to change it. |

All three Netgsm fields are required. Encoding is fixed to `TR`.

### How it works

The plugin registers `POST /wp-json/amelia-netgsm/v1/hook`. On each request it:

1. Compares the `token` parameter with the stored token and returns 403 if they differ.
2. Reads `customer.phone`, `customer.firstName`, `customer.lastName`, `appointment.service.name`, `appointment.date` and `appointment.time` from the JSON body.
3. Normalizes the phone to `905XXXXXXXXX`. Returns 400 if there is no phone.
4. Sends this text to Netgsm: `Sayın {name}, {service} randevunuz {date} {time} tarihinde onaylanmıştır.`
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

- The message is always the "approved" text. A canceled or rescheduled booking gets the same SMS.
- The fields are read from fixed paths (`customer.*`, `appointment.service.name`, `appointment.date`, `appointment.time`). If your Amelia version sends a different structure, for example the customer inside `appointment.bookings[]` or the start time as `bookingStart`, the phone is not found or the date stays empty. Check what your Amelia webhook actually sends before relying on it.
- No logging. A failed send only shows up as a 500 response to Amelia.
- The token cannot be regenerated from the admin page.
- The password field is a plain text input and the password is stored unencrypted in the options table.

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
- Kalıcı bağlantı ayarının "Düz" dışında bir seçenekte olması. Yönetim sayfasında gösterilen adres `/wp-json/...` biçiminde üretiliyor, düz bağlantılarda çalışmaz.

### Kurulum

1. Depoyu ZIP olarak indirin ya da `amelia-netgsm-atomix.php` dosyasını `wp-content/plugins/amelia-netgsm-atomix/` klasörüne kopyalayın.
2. Eklentiler sayfasından "Amelia → Netgsm SMS by AtomixBilişim" eklentisini etkinleştirin. Etkinleştirme sırasında 24 karakterlik bir token oluşturulur.
3. Ayarlar → Amelia → Netgsm SMS sayfasında Netgsm bilgilerinizi girip kaydedin.
4. Sayfanın üstündeki Webhook URL'yi kopyalayın.
5. Amelia'da bu adresle bir webhook ekleyin (yöntem POST, gövde JSON).

### Ayarlar

Ayarlar `amelia_netgsm_atomix_settings` seçeneğinde saklanır.

| Alan | Anahtar | Açıklama |
|---|---|---|
| Usercode | `usercode` | Netgsm kullanıcı kodu |
| Password | `password` | Netgsm API şifresi |
| Msgheader | `msgheader` | Onaylı gönderici adı |
| (yok) | `token` | Etkinleştirmede üretilir, Webhook URL'nin içinde görünür. Değiştirmek için bir düğme yok. |

Üç Netgsm alanı da zorunlu. Karakter kodlaması `TR` olarak sabit.

### Nasıl çalışır

Eklenti `POST /wp-json/amelia-netgsm/v1/hook` uç noktasını kaydeder. Her istekte:

1. `token` parametresini kayıtlı token ile karşılaştırır, tutmazsa 403 döner.
2. JSON gövdeden `customer.phone`, `customer.firstName`, `customer.lastName`, `appointment.service.name`, `appointment.date` ve `appointment.time` alanlarını okur.
3. Telefonu `905XXXXXXXXX` biçimine çevirir. Telefon yoksa 400 döner.
4. Netgsm'e şu metni gönderir: `Sayın {ad}, {hizmet} randevunuz {tarih} {saat} tarihinde onaylanmıştır.`
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

- Mesaj her zaman "onaylanmıştır" metni. İptal edilen ya da ertelenen randevuda da aynı SMS gider.
- Alanlar sabit yollardan okunur (`customer.*`, `appointment.service.name`, `appointment.date`, `appointment.time`). Kullandığınız Amelia sürümü farklı bir yapı gönderiyorsa, örneğin müşteriyi `appointment.bookings[]` içinde ya da başlangıç zamanını `bookingStart` olarak, telefon bulunamaz ya da tarih boş kalır. Kullanmadan önce Amelia webhook'unun gerçekte ne gönderdiğine bakın.
- Günlük kaydı yok. Başarısız gönderim yalnızca Amelia'ya dönen 500 yanıtında görünür.
- Token yönetim sayfasından yenilenemiyor.
- Şifre alanı düz metin kutusu, şifre seçenekler tablosunda şifrelenmeden duruyor.

### Lisans

MIT. Ayrıntılar için [LICENSE](LICENSE).

Atakan Özdal · atomiXbilişim · https://atakanozdal.com.tr
