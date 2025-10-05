<?php
/**
 * Plugin Name: Instagram Feed Sync
 * Description: InstagramグラフAPIと基本表示APIの両方に対応（シンプル版）。設定画面から複数アカウント登録可能。ショートコードで簡単にInstagramフィードを表示。アクセストークンの自動更新機能付き。
 * Version: 2.1.1
 * Author: m yamada with AI
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) exit;

define('INSTAGRAM_FEED_SYNC_VERSION', '2.1.1');
define('INSTAGRAM_FEED_SYNC_PATH', plugin_dir_path(__FILE__));
define('INSTAGRAM_FEED_SYNC_URL', plugin_dir_url(__FILE__));

class Instagram_Encryption {
    private static $cipher = 'AES-256-CBC';
    
    public static function encrypt(string $data): string {
        $key = hash('sha256', wp_salt('auth'), true);
        $iv = substr(hash('sha256', wp_salt('secure_auth'), true), 0, 16);
        $encrypted = openssl_encrypt($data, self::$cipher, $key, 0, $iv);
        return base64_encode($encrypted);
    }
    
    public static function decrypt(string $data): string {
        if (empty($data)) return '';
        $key = hash('sha256', wp_salt('auth'), true);
        $iv = substr(hash('sha256', wp_salt('secure_auth'), true), 0, 16);
        $encrypted = base64_decode($data);
        $decrypted = openssl_decrypt($encrypted, self::$cipher, $key, 0, $iv);
        return $decrypted !== false ? $decrypted : '';
    }
}



class Instagram_API_Handler {
    private $api_type;
    private $access_token;
    
    public function __construct(string $api_type, string $access_token) {
        $this->api_type = $api_type;
        $this->access_token = Instagram_Encryption::decrypt($access_token);
    }
    
    public function get_profile(): array {
        $cache_key = 'instagram_profile_' . md5($this->access_token);
        $cached = get_transient($cache_key);
        if (false !== $cached) return $cached;
        
        $url = sprintf(
            'https://graph.instagram.com/me?fields=id,username,name,biography&access_token=%s',
            $this->access_token
        );
        
        $response = wp_remote_get($url, ['timeout' => 10, 'sslverify' => true]);
        if (is_wp_error($response)) return [];
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['error'])) return [];
        
        $profile = [
            'id' => $body['id'] ?? '',
            'username' => $body['username'] ?? '',
            'name' => $body['name'] ?? '',
            'biography' => $body['biography'] ?? ''
        ];
        
        set_transient($cache_key, $profile, DAY_IN_SECONDS);
        return $profile;
    }
    
    public function get_media(int $limit = 12): array {
        $cache_key = 'instagram_media_' . md5($this->access_token) . '_' . $limit;
        $cached = get_transient($cache_key);
        if (false !== $cached) return $cached;
        
        $url = sprintf(
            'https://graph.instagram.com/me/media?fields=id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,children{media_url,media_type}&limit=%d&access_token=%s',
            $limit,
            $this->access_token
        );
        
        $response = wp_remote_get($url, ['timeout' => 15, 'sslverify' => true]);
        if (is_wp_error($response)) return new WP_Error('api_error', $response->get_error_message());
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['error'])) return new WP_Error('api_error', $body['error']['message']);
        
        $media = array_map(function($item) {
            $is_carousel = ($item['media_type'] === 'CAROUSEL_ALBUM') || 
                          (isset($item['children']) && !empty($item['children']['data']));
            
            return [
                'id' => $item['id'] ?? '',
                'caption' => $item['caption'] ?? '',
                'media_type' => $item['media_type'] ?? 'IMAGE',
                'media_url' => $item['media_url'] ?? '',
                'thumbnail_url' => $item['thumbnail_url'] ?? ($item['media_url'] ?? ''),
                'permalink' => $item['permalink'] ?? '',
                'timestamp' => $item['timestamp'] ?? '',
                'is_carousel' => $is_carousel
            ];
        }, $body['data'] ?? []);
        
        $cache_duration = (int) get_option('instagram_feed_sync_cache_duration', 3600);
        set_transient($cache_key, $media, $cache_duration);
        return $media;
    }
    
    public function validate_token(): bool {
        $url = 'https://graph.instagram.com/me?fields=id&access_token=' . $this->access_token;
        $response = wp_remote_get($url, ['timeout' => 10]);
        if (is_wp_error($response)) return false;
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return !isset($body['error']);
    }
}

class Instagram_Token_Manager {
    public static function refresh_all_tokens(): void {
        $accounts = get_option('instagram_feed_sync_accounts', []);
        foreach ($accounts as $key => $account) {
            $encrypted_token = $account['access_token'] ?? '';
            if (empty($encrypted_token)) continue;
            
            $current_token = Instagram_Encryption::decrypt($encrypted_token);
            $url = 'https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=' . $current_token;
            $response = wp_remote_get($url, ['timeout' => 15]);
            
            if (!is_wp_error($response)) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($body['access_token'])) {
                    $accounts[$key]['access_token'] = Instagram_Encryption::encrypt($body['access_token']);
                    $accounts[$key]['last_refreshed'] = current_time('mysql');
                }
            }
        }
        update_option('instagram_feed_sync_accounts', $accounts);
    }
}

class Instagram_Settings {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_menu(): void {
        add_menu_page(
            'Instagram Feed Sync',
            'Instagram Feed',
            'manage_options',
            'instagram-feed-sync',
            [$this, 'render_page'],
            'dashicons-instagram',
            16
        );
    }

    public function enqueue_assets($hook): void {
        if ('toplevel_page_instagram-feed-sync' !== $hook) return;
        wp_enqueue_style('instagram-admin', INSTAGRAM_FEED_SYNC_URL . 'admin/css/admin.css', [], INSTAGRAM_FEED_SYNC_VERSION);
    }

    public function render_page(): void {
        if (!current_user_can('manage_options')) wp_die('権限がありません');

        $this->handle_form_actions();

        $action = $_GET['action'] ?? 'list';
        $account_key = isset($_GET['account_key']) ? sanitize_key($_GET['account_key']) : null;
        $accounts = get_option('instagram_feed_sync_accounts', []);

        echo '<div class="wrap">';
        echo '<h1>Instagram Feed Sync 設定</h1>';
        
        settings_errors('instagram_feed_sync_notices');

        if ($action === 'edit' && $account_key && isset($accounts[$account_key])) {
            $this->render_edit_form($account_key, $accounts[$account_key]);
        } else {
            $this->render_add_form();
            $this->render_accounts_list($accounts);
        }
        
        ?>
        <div class="card" style="max-width:800px;margin-top:20px;">
            <h3>ショートコード使用方法</h3>
            <p><code>[instagram_feed limit="12" columns="3"]</code></p>
            <p><code>[instagram_feed show_profile="true" limit="9"]</code></p>
            <h4>パラメータ</h4>
            <ul>
                <li><strong>limit</strong>: 表示件数（デフォルト: 12）</li>
                <li><strong>columns</strong>: カラム数 1-6（デフォルト: 3）</li>
                <li><strong>show_profile</strong>: プロフィール表示（true/false）</li>
                <li><strong>username</strong>: 表示アカウント指定</li>
            </ul>
            <small>不明な点はREADME.mdを参照してください。 リンク: <a href="https://github.com/Yamada-Megumi/instagram-feed-sync/blob/main/README.md" target="_blank">README.md</a></small>
        </div>
        <?php
        echo '</div>';
    }

    private function handle_form_actions(): void {
        if (isset($_POST['add_account'])) {
            check_admin_referer('instagram_add_account');
            $this->add_account();
        } elseif (isset($_POST['update_account'])) {
            check_admin_referer('instagram_update_account');
            $this->update_account();
        } elseif (isset($_GET['action'], $_GET['account_key'], $_GET['_wpnonce']) && $_GET['action'] === 'delete') {
            if (wp_verify_nonce($_GET['_wpnonce'], 'instagram_delete_account_' . $_GET['account_key'])) {
                $this->delete_account(sanitize_key($_GET['account_key']));
            }
        }
    }

    private function render_add_form(): void {
        ?>
        <h2>新規アカウント追加</h2>
        <form method="post" action="<?php echo admin_url('admin.php?page=instagram-feed-sync'); ?>">
            <?php wp_nonce_field('instagram_add_account'); ?>
            <table class="form-table">
                 <tr>
                    <th>ユーザー名</th>
                    <td><input type="text" name="username" class="regular-text" required>
                        <p class="description">アカウントの識別子として使われます（例: my_business_account）。</p>
                    </td>
                </tr>
                <tr>
                    <th>API種別</th>
                    <td><select name="api_type" required>
                        <option value="graph">InstagramグラフAPI（推奨）</option>
                        <option value="basic">Instagram基本表示API</option>
                    </select></td>
                </tr>
                <tr>
                    <th>アクセストークン</th>
                    <td><textarea name="access_token" rows="3" class="large-text" required></textarea></td>
                </tr>
                <tr>
                    <th>Instagram URL</th>
                    <td>
                        <input type="url" name="instagram_url" class="regular-text" placeholder="https://instagram.com/username">
                        <p class="description">プロフィールページのURL（オプション）</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('アカウント追加', 'primary', 'add_account'); ?>
            <p><small>不明な点はREADME.mdを参照してください。 リンク: <a href="https://github.com/Yamada-Megumi/instagram-feed-sync/blob/main/README.md" target="_blank">README.md</a></small></p>
        </form>
        <?php
    }

    private function render_edit_form(string $key, array $account): void {
        $decrypted_token = Instagram_Encryption::decrypt($account['access_token']);
        ?>
        <h2>アカウント編集: <?php echo esc_html($account['username']); ?></h2>
        <form method="post" action="<?php echo admin_url('admin.php?page=instagram-feed-sync'); ?>">
            <?php wp_nonce_field('instagram_update_account'); ?>
            <input type="hidden" name="account_key" value="<?php echo esc_attr($key); ?>">
            <table class="form-table">
                <tr>
                    <th>ユーザー名</th>
                    <td><input type="text" name="username" value="<?php echo esc_attr($account['username']); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th>API種別</th>
                    <td><select name="api_type" required>
                        <option value="graph" <?php selected($account['api_type'], 'graph'); ?>>InstagramグラフAPI（推奨）</option>
                        <option value="basic" <?php selected($account['api_type'], 'basic'); ?>>Instagram基本表示API</option>
                    </select></td>
                </tr>
                <tr>
                    <th>現在のアクセストークン</th>
                    <td>
                        <input type="password" id="existing_token" class="regular-text" value="<?php echo esc_attr($decrypted_token); ?>" readonly style="background:#eee;">
                        <button type="button" class="button" id="toggle_token_visibility">表示</button>
                    </td>
                </tr>
                <tr>
                    <th>新しいアクセストークン</th>
                    <td><textarea name="access_token" rows="3" class="large-text"></textarea>
                    <p class="description">トークンを更新する場合のみ入力してください。空のままなら既存のトークンが維持されます。</p>
                    </td>
                </tr>
                <tr>
                    <th>Instagram URL</th>
                    <td>
                        <input type="url" name="instagram_url" value="<?php echo esc_attr($account['instagram_url']); ?>" class="regular-text" placeholder="https://instagram.com/username">
                    </td>
                </tr>
            </table>
            <?php submit_button('更新', 'primary', 'update_account'); ?>
            <a href="?page=instagram-feed-sync" class="button">キャンセル</a>
            <p><small>不明な点はREADME.mdを参照してください。 リンク: <a href="https://github.com/Yamada-Megumi/instagram-feed-sync/blob/main/README.md" target="_blank">README.md</a></small></p>
        </form>
        <script>
            document.getElementById('toggle_token_visibility').addEventListener('click', function() {
                var tokenInput = document.getElementById('existing_token');
                if (tokenInput.type === 'password') {
                    tokenInput.type = 'text';
                    this.textContent = '隠す';
                } else {
                    tokenInput.type = 'password';
                    this.textContent = '表示';
                }
            });
        </script>
        <?php
    }

    private function render_accounts_list(array $accounts): void {
        if (empty($accounts)) return;
        ?>
        <h2>登録済みアカウント</h2>
        <table class="widefat striped">
            <thead>
                <tr><th>ユーザー名</th><th>API</th><th>URL</th><th>ショートコード</th><th>操作</th></tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $key => $account): 
                    $delete_url = wp_nonce_url(admin_url('admin.php?page=instagram-feed-sync&action=delete&account_key=' . $key), 'instagram_delete_account_' . $key);
                ?>
                <tr>
                    <td><strong><?php echo esc_html($account['username']); ?></strong></td>
                    <td><?php echo esc_html(strtoupper($account['api_type'])); ?></td>
                    <td><?php echo !empty($account['instagram_url']) ? '<a href="' . esc_url($account['instagram_url']) . '" target="_blank">表示</a>' : '-'; ?></td>
                    <td><code>[instagram_feed username="<?php echo esc_attr($account['username']); ?>"]</code></td>
                    <td>
                        <a href="?page=instagram-feed-sync&action=edit&account_key=<?php echo esc_attr($key); ?>" class="button button-small">編集</a>
                        <a href="<?php echo esc_url($delete_url); ?>" class="button button-small" style="color:#b32d2e;" onclick="return confirm('本当に削除しますか？');">削除</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private function add_account(): void {
        $username = sanitize_text_field($_POST['username']);
        $account_key = sanitize_key($username);
        $accounts = get_option('instagram_feed_sync_accounts', []);

        if (empty($username) || empty($_POST['access_token'])) {
            add_settings_error('instagram_feed_sync_notices', 'missing_fields', 'ユーザー名とアクセストークンは必須です。', 'error');
            return;
        }

        if (isset($accounts[$account_key])) {
            add_settings_error('instagram_feed_sync_notices', 'duplicate_user', 'このユーザー名は既に使用されています。', 'error');
            return;
        }

        $accounts[$account_key] = [
            'username'      => $username,
            'api_type'      => sanitize_text_field($_POST['api_type']),
            'access_token'  => Instagram_Encryption::encrypt(sanitize_textarea_field($_POST['access_token'])),
            'instagram_url' => esc_url_raw($_POST['instagram_url'] ?? ''),
            'added_at'      => current_time('mysql')
        ];
        
        update_option('instagram_feed_sync_accounts', $accounts);
        add_settings_error('instagram_feed_sync_notices', 'account_added', 'アカウントを追加しました。', 'success');
        wp_redirect(admin_url('admin.php?page=instagram-feed-sync'));
        exit;
    }

    private function update_account(): void {
        $account_key = sanitize_key($_POST['account_key']);
        $username = sanitize_text_field($_POST['username']);
        $new_key = sanitize_key($username);
        $accounts = get_option('instagram_feed_sync_accounts', []);

        if (!isset($accounts[$account_key])) {
            add_settings_error('instagram_feed_sync_notices', 'account_not_found', '更新対象のアカウントが見つかりません。', 'error');
            return;
        }

        if ($new_key !== $account_key && isset($accounts[$new_key])) {
            add_settings_error('instagram_feed_sync_notices', 'duplicate_user_update', 'そのユーザー名は既に存在します。別の名前を選択してください。', 'error');
            wp_redirect(admin_url('admin.php?page=instagram-feed-sync&action=edit&account_key=' . $account_key));
            exit;
        }

        $account_data = [
            'username'      => $username,
            'api_type'      => sanitize_text_field($_POST['api_type']),
            'instagram_url' => esc_url_raw($_POST['instagram_url'] ?? ''),
        ];

        if (!empty($_POST['access_token'])) {
            $account_data['access_token'] = Instagram_Encryption::encrypt(sanitize_textarea_field($_POST['access_token']));
        } else {
            $account_data['access_token'] = $accounts[$account_key]['access_token']; // Keep the old token
        }

        unset($accounts[$account_key]);
        $accounts[$new_key] = array_merge($accounts[$new_key] ?? [], $account_data);

        update_option('instagram_feed_sync_accounts', $accounts);
        add_settings_error('instagram_feed_sync_notices', 'account_updated', 'アカウント情報を更新しました。', 'success');
        wp_redirect(admin_url('admin.php?page=instagram-feed-sync'));
        exit;
    }
    
    private function delete_account(string $key): void {
        $accounts = get_option('instagram_feed_sync_accounts', []);
        
        if (isset($accounts[$key])) {
            $decrypted_token = Instagram_Encryption::decrypt($accounts[$key]['access_token']);
            unset($accounts[$key]);
            update_option('instagram_feed_sync_accounts', $accounts);
            
            delete_transient('instagram_profile_' . md5($decrypted_token));

            add_settings_error('instagram_feed_sync_notices', 'account_deleted', 'アカウントを削除しました。', 'success');
        } else {
            add_settings_error('instagram_feed_sync_notices', 'delete_failed', 'アカウントが見つかりませんでした。', 'error');
        }
        wp_redirect(admin_url('admin.php?page=instagram-feed-sync'));
        exit;
    }
}

class Instagram_Shortcode {
    public function __construct() {
        add_shortcode('instagram_feed', [$this, 'render']);
    }
    
    public function render($atts): string {
        $atts = shortcode_atts([
            'limit' => 12,
            'columns' => 3,
            'username' => '',
            'show_profile' => 'false'
        ], $atts);
        
        $accounts = get_option('instagram_feed_sync_accounts', []);
        if (empty($accounts)) return '<p>アカウント未登録</p>';
        
        $account = null;
        if (!empty($atts['username'])) {
            $username_key = str_replace('.', '_', sanitize_key($atts['username']));
            $account = $accounts[$username_key] ?? null;
        } else {
            $account = reset($accounts);
        }
        
        if (!$account) return '<p>アカウントが見つかりません</p>';
        
        $handler = new Instagram_API_Handler($account['api_type'], $account['access_token']);
        $media = $handler->get_media(absint($atts['limit']));
        if (is_wp_error($media) || empty($media)) return '<p>投稿を取得できません</p>';
        
        $profile = [];
        $show_profile = filter_var($atts['show_profile'], FILTER_VALIDATE_BOOLEAN);
        if ($show_profile) {
            $profile = $handler->get_profile();
        }
        
        ob_start();
        ?>
        <div class="instagram-feed-wrapper">
            <?php if ($show_profile && !empty($profile)): ?>
            <div>
                <h3><?php echo esc_html($profile['username']); ?></h3>
                <?php if (!empty($profile['biography'])): ?>
                    <p><?php echo wp_kses_post(nl2br($profile['biography'])); ?></p>
                <?php endif; ?>
                <?php if (!empty($account['instagram_url'])): ?>
                    <p><a href="<?php echo esc_url($account['instagram_url']); ?>" target="_blank" rel="noopener">Instagram</a></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="instagram-feed-container" data-columns="<?php echo esc_attr($atts['columns']); ?>">
                <div class="instagram-feed-grid">
                    <?php foreach ($media as $item): ?>
                    <div class="instagram-feed-item">
                        <a href="<?php echo esc_url($item['permalink']); ?>" target="_blank" rel="noopener">
                            <?php if ($item['media_type'] === 'VIDEO'): ?>
                                <div class="instagram-icon-overlay">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8 5v14l11-7z" fill="white" stroke="white" stroke-width="1"/>
                                    </svg>
                                </div>
                            <?php elseif (!empty($item['is_carousel'])): ?>
                                <div class="instagram-icon-overlay instagram-carousel-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="9" y="6" width="10" height="12" rx="1" fill="none" stroke="white" stroke-width="1.5"/>
                                        <rect x="5" y="6" width="10" height="12" rx="1" fill="none" stroke="white" stroke-width="1.5"/>
                                    </svg>
                                </div>
                            <?php else: ?>
                                <div class="instagram-icon-overlay">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="2" y="2" width="20" height="20" rx="5" stroke="white" stroke-width="1.5" fill="none"/>
                                        <circle cx="12" cy="12" r="4.5" stroke="white" stroke-width="1.5" fill="none"/>
                                        <circle cx="17.5" cy="6.5" r="1" fill="white"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <img src="<?php echo esc_url($item['thumbnail_url']); ?>" 
                                 alt="<?php echo esc_attr(wp_trim_words($item['caption'], 10)); ?>" 
                                 loading="lazy">
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

class Instagram_Feed_Sync {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        add_action('instagram_feed_sync_cron', [Instagram_Token_Manager::class, 'refresh_all_tokens']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    public function activate() {
        if (!wp_next_scheduled('instagram_feed_sync_cron')) {
            wp_schedule_event(time(), 'daily', 'instagram_feed_sync_cron');
        }
        add_option('instagram_feed_sync_cache_duration', 3600);
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('instagram_feed_sync_cron');
    }
    
    public function enqueue_assets() {
        wp_enqueue_style('instagram-feed', INSTAGRAM_FEED_SYNC_URL . 'public/css/style.css', [], INSTAGRAM_FEED_SYNC_VERSION);
        wp_enqueue_script('instagram-feed', INSTAGRAM_FEED_SYNC_URL . 'public/js/script.js', ['jquery'], INSTAGRAM_FEED_SYNC_VERSION, true);
    }
}

add_action('plugins_loaded', function() {
    Instagram_Feed_Sync::get_instance();
    if (is_admin()) new Instagram_Settings();
    new Instagram_Shortcode();
});