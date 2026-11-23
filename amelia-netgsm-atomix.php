<?php
/**
 * Plugin Name: Amelia → Netgsm SMS by AtomixBilişim
 * Description: Amelia randevu olaylarını dinleyip Netgsm REST API ile otomatik SMS gönderimi yapar. Kurulum için Amelia Webhook ayarları gereklidir.
 * Version: 1.0.1
 * Author: Atakan Özdal (AtomixBilişim)
 * Text Domain: amelia-netgsm-atomix
 */

if (!defined('ABSPATH')) exit;

// SETTINGS KEY
const AMELIA_NETGSM_OPT = 'amelia_netgsm_atomix_settings';

// DEFAULTS
function amelia_netgsm_defaults() {
    return [
        'token'     => '',
        'usercode'  => '',
        'password'  => '',
        'msgheader' => '',
    ];
}

function amelia_netgsm_get() {
    $o = get_option(AMELIA_NETGSM_OPT, []);
    return array_merge(amelia_netgsm_defaults(), is_array($o) ? $o : []);
}

function amelia_netgsm_update($arr) {
    update_option(AMELIA_NETGSM_OPT, array_merge(amelia_netgsm_get(), $arr));
}

/**
 * Token yoksa üretir ve kaydeder. Güncel token'ı döner.
 */
function amelia_netgsm_ensure_token() {
    $s = amelia_netgsm_get();
    if (empty($s['token'])) {
        $s['token'] = wp_generate_password(24, false, false);
        update_option(AMELIA_NETGSM_OPT, $s);
    }
    return (string)$s['token'];
}

/**
 * Token Oluşturma
 */
register_activation_hook(__FILE__, 'amelia_netgsm_ensure_token');

/**
 * Token kontrolü: kayıtlı token boşsa her istek reddedilir.
 */
function amelia_netgsm_token_ok($stored, $given) {
    if (!is_string($stored) || $stored === '') return false;
    if (!is_string($given) || $given === '') return false;
    return hash_equals($stored, $given);
}

/**
 * Telefon normalize
 */
function amelia_netgsm_phone($raw) {
    $d = preg_replace('/\D+/', '', (string)$raw);
    if (strlen($d) === 11 && $d[0] === '0') $d = '90'.substr($d,1);
    if (strlen($d) === 10) $d = '90'.$d;
    return $d;
}

/**
 * Dizide "a.b.0.c" biçimindeki yollardan ilk dolu metni döner.
 */
function amelia_netgsm_pick($arr, $paths) {
    foreach ($paths as $path) {
        $v = $arr;
        foreach (explode('.', $path) as $k) {
            if (is_array($v) && array_key_exists($k, $v)) { $v = $v[$k]; } else { $v = null; break; }
        }
        if (is_scalar($v) && trim((string)$v) !== '') return trim((string)$v);
    }
    return '';
}

/**
 * Olay adını sadeleştirir: bookingCanceled, booking_canceled, canceled → canceled
 */
function amelia_netgsm_event($evt) {
    $e = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', trim((string)$evt)));
    $e = preg_replace('/^(booking|appointment)_/', '', $e);
    $map = [
        'added' => 'added', 'created' => 'added',
        'canceled' => 'canceled', 'cancelled' => 'canceled',
        'rescheduled' => 'rescheduled', 'time_updated' => 'rescheduled',
        'status_updated' => 'status', 'status' => 'status',
    ];
    return isset($map[$e]) ? $map[$e] : '';
}

/**
 * Olaya ve duruma göre SMS metni
 */
function amelia_netgsm_message($evt, $name, $service, $date, $time, $status) {
    $name = $name !== '' ? $name : 'Müşterimiz';
    $when = trim($date . ' ' . $time);
    $what = trim(($when !== '' ? $when . ' tarihli ' : '') . $service . ' randevunuz');
    $status = strtolower($status);
    $evt = amelia_netgsm_event($evt);

    if ($evt === 'added') {
        $msg = "Sayın $name, $what oluşturulmuştur.";
    } elseif ($evt === 'canceled') {
        $msg = "Sayın $name, $what iptal edilmiştir.";
    } elseif ($evt === 'rescheduled') {
        $svc = trim($service . ' randevunuz');
        $msg = $when !== '' ? "Sayın $name, $svc $when olarak güncellenmiştir." : "Sayın $name, $svc güncellenmiştir.";
    } elseif ($status === 'approved') {
        $msg = "Sayın $name, $what onaylanmıştır.";
    } elseif ($status === 'pending') {
        $msg = "Sayın $name, $what onay beklemektedir.";
    } elseif ($status === 'canceled' || $status === 'cancelled') {
        $msg = "Sayın $name, $what iptal edilmiştir.";
    } elseif ($status === 'rejected') {
        $msg = "Sayın $name, $what reddedilmiştir.";
    } elseif ($evt === 'status') {
        $msg = "Sayın $name, $what için durum güncellenmiştir.";
    } else {
        $msg = "Sayın $name, $what hakkında bilgilendirmedir.";
    }
    return trim(preg_replace('/\s+/', ' ', $msg));
}

/**
 * Netgsm REST API
 */
function amelia_netgsm_send_sms($phone90, $msg) {
    $s = amelia_netgsm_get();
    if (!$s['usercode'] || !$s['password'] || !$s['msgheader']) return false;

    $payload = [
        'msgheader' => $s['msgheader'],
        'messages' => [
            [ 'msg' => $msg, 'no' => $phone90 ]
        ],
        'encoding' => 'TR'
    ];

    $r = wp_remote_post('https://api.netgsm.com.tr/sms/rest/v2/send', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($s['usercode'] . ':' . $s['password'])
        ],
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'timeout' => 20
    ]);

    if (is_wp_error($r)) return false;

    $code = (int)wp_remote_retrieve_response_code($r);
    $body = wp_remote_retrieve_body($r);
    $json = json_decode($body, true);

    return ($code === 200 && isset($json['code']) && $json['code'] === '00');
}

/**
 * REST Endpoint ⇒ Amelia Webhook çağıracak
 */
add_action('rest_api_init', function () {
    register_rest_route('amelia-netgsm/v1', '/hook', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => function(WP_REST_Request $req) {

            $s = amelia_netgsm_get();
            if (!amelia_netgsm_token_ok($s['token'], $req->get_param('token'))) {
                return new WP_REST_Response(['ok'=>false,'error'=>'forbidden'],403);
            }

            $body = json_decode($req->get_body(), true);
            if (!is_array($body)) return new WP_REST_Response(['ok'=>false],400);

            // Eski düz yollar önce, sonra Amelia'nın gerçek yapısı (appointment.bookings[0].customer)
            $phone = amelia_netgsm_pick($body, ['customer.phone', 'appointment.bookings.0.customer.phone', 'bookings.0.customer.phone']);
            $phone = amelia_netgsm_phone($phone);
            if (!$phone) return new WP_REST_Response(['ok'=>false,'error'=>'no-phone'],400);

            $first = amelia_netgsm_pick($body, ['customer.firstName', 'appointment.bookings.0.customer.firstName', 'bookings.0.customer.firstName']);
            $last  = amelia_netgsm_pick($body, ['customer.lastName', 'appointment.bookings.0.customer.lastName', 'bookings.0.customer.lastName']);
            $name = trim($first . ' ' . $last);
            $service = amelia_netgsm_pick($body, ['appointment.service.name', 'service.name']);
            $date = amelia_netgsm_pick($body, ['appointment.date']);
            $time = amelia_netgsm_pick($body, ['appointment.time']);
            $start = amelia_netgsm_pick($body, ['appointment.bookingStart', 'bookingStart']);
            if ($start !== '' && preg_match('/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/', $start, $m)) {
                if ($date === '') $date = $m[1];
                if ($time === '') $time = $m[2];
            }
            $status = amelia_netgsm_pick($body, ['appointment.status', 'appointment.bookings.0.status', 'status']);

            $evt = $req->get_param('evt');
            if (!is_string($evt) || $evt === '') $evt = isset($body['evt']) && is_string($body['evt']) ? $body['evt'] : '';
            $evt = sanitize_text_field($evt);

            $msg = amelia_netgsm_message($evt, $name, $service, $date, $time, $status);

            $ok = amelia_netgsm_send_sms($phone, $msg);

            return new WP_REST_Response(['ok'=>$ok], $ok ? 200 : 500);
        }
    ]);
});

/**
 * Admin Menü
 */
add_action('admin_menu', function () {
    add_options_page(
        'Amelia → Netgsm SMS',
        'Amelia → Netgsm SMS',
        'manage_options',
        'amelia-netgsm-atomix',
        'amelia_netgsm_admin'
    );
});

function amelia_netgsm_admin() {
    if (!current_user_can('manage_options')) return;

    amelia_netgsm_ensure_token();
    $s = amelia_netgsm_get();
    $updated = false;

    if (isset($_POST['save']) && check_admin_referer('amelia_netgsm_save')) {
        $new = [
            'usercode'  => isset($_POST['usercode']) ? sanitize_text_field(wp_unslash($_POST['usercode'])) : '',
            'msgheader' => isset($_POST['msgheader']) ? sanitize_text_field(wp_unslash($_POST['msgheader'])) : '',
        ];
        $password = isset($_POST['password']) ? trim((string)wp_unslash($_POST['password'])) : '';
        if ($password !== '') $new['password'] = $password; // boş gelirse kayıtlı şifre korunur
        amelia_netgsm_update($new);
        $s = amelia_netgsm_get();
        $updated = true;
    }

    $url = add_query_arg('token', rawurlencode($s['token']), rest_url('amelia-netgsm/v1/hook'));

    ?>
    <div class="wrap">
        <h1>Amelia → Netgsm SMS</h1>
        <?php if ($updated): ?><div class="updated"><p>Ayarlar Kaydedildi ✅</p></div><?php endif; ?>

        <h3>Webhook URL</h3>
        <code><?php echo esc_html($url); ?></code>
        <p class="description">Olaya göre mesaj için adrese <code>&amp;evt=booking_added</code>, <code>booking_canceled</code>, <code>booking_rescheduled</code> ya da <code>booking_status_updated</code> ekleyin.</p>

        <h3>Netgsm Ayarları</h3>
        <form method="post">
            <?php wp_nonce_field('amelia_netgsm_save'); ?>
            <table class="form-table">
                <tr><th>Usercode</th><td><input type="text" name="usercode" value="<?php echo esc_attr($s['usercode']); ?>"></td></tr>
                <tr><th>Password</th><td><input type="password" name="password" autocomplete="new-password" value="" placeholder="<?php echo $s['password'] !== '' ? esc_attr('(kayıtlı)') : ''; ?>"></td></tr>
                <tr><th>Msgheader</th><td><input type="text" name="msgheader" value="<?php echo esc_attr($s['msgheader']); ?>"></td></tr>
            </table>
            <button class="button button-primary" name="save">Kaydet</button>
        </form>
    </div>
    <?php
}
