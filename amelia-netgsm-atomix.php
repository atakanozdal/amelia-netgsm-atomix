<?php
/**
 * Plugin Name: Amelia → Netgsm SMS by AtomixBilişim
 * Description: Amelia randevu olaylarını dinleyip Netgsm REST API ile otomatik SMS gönderimi yapar. Kurulum için Amelia Webhook ayarları gereklidir.
 * Version: 1.0.0
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
 * Token Oluşturma
 */
register_activation_hook(__FILE__, function() {
    $s = amelia_netgsm_get();
    if (empty($s['token'])) {
        $s['token'] = wp_generate_password(24, false, false);
        update_option(AMELIA_NETGSM_OPT, $s);
    }
});

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

    $code = wp_remote_retrieve_response_code($r);
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
            if ($req->get_param('token') !== $s['token']) {
                return new WP_REST_Response(['ok'=>false,'error'=>'forbidden'],403);
            }

            $body = json_decode($req->get_body(), true);
            if (!is_array($body)) return new WP_REST_Response(['ok'=>false],400);

            $phone = $body['customer']['phone'] ?? '';
            $phone = amelia_netgsm_phone($phone);
            if (!$phone) return new WP_REST_Response(['ok'=>false,'error'=>'no-phone'],400);

            $name = trim(($body['customer']['firstName'] ?? '') . ' ' . ($body['customer']['lastName'] ?? ''));
            $service = $body['appointment']['service']['name'] ?? '';
            $date = $body['appointment']['date'] ?? '';
            $time = $body['appointment']['time'] ?? '';

            $msg = "Sayın $name, $service randevunuz $date $time tarihinde onaylanmıştır.";

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

    $s = amelia_netgsm_get();
    $updated = false;

    if (isset($_POST['save']) && check_admin_referer('amelia_netgsm_save')) {
        $s = [
            'usercode'  => sanitize_text_field($_POST['usercode']),
            'password'  => sanitize_text_field($_POST['password']),
            'msgheader' => sanitize_text_field($_POST['msgheader']),
            'token'     => $s['token']
        ];
        amelia_netgsm_update($s);
        $updated = true;
    }

    $url = site_url('/wp-json/amelia-netgsm/v1/hook?token='.$s['token']);

    ?>
    <div class="wrap">
        <h1>Amelia → Netgsm SMS</h1>
        <?php if ($updated): ?><div class="updated"><p>Ayarlar Kaydedildi ✅</p></div><?php endif; ?>

        <h3>Webhook URL</h3>
        <code><?php echo esc_html($url); ?></code>

        <h3>Netgsm Ayarları</h3>
        <form method="post">
            <?php wp_nonce_field('amelia_netgsm_save'); ?>
            <table class="form-table">
                <tr><th>Usercode</th><td><input type="text" name="usercode" value="<?php echo esc_attr($s['usercode']); ?>"></td></tr>
                <tr><th>Password</th><td><input type="text" name="password" value="<?php echo esc_attr($s['password']); ?>"></td></tr>
                <tr><th>Msgheader</th><td><input type="text" name="msgheader" value="<?php echo esc_attr($s['msgheader']); ?>"></td></tr>
            </table>
            <button class="button button-primary" name="save">Kaydet</button>
        </form>
    </div>
    <?php
}
