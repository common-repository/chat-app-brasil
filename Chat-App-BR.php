<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Plugin Name: Chat App Brasil
 * Plugin URI: https://lupee.com.br
 * Description: Plugin adicionar botão do WhatsApp em diferentes páginas.
 * Version: 1.2
 * Author: Luiz Mariano
 * Text Domain: Chat-App-BR
 * Domain Path: /languages
 */

// Verifica se as tabelas existem e cria se necessário
register_activation_hook( __FILE__, 'whats_lp_create_tables' );
function whats_lp_create_tables() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'whats_lp_click_tracking';

    // Verifica se a tabela existe
    if ( $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) != $table_name ) {

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT(11) NOT NULL AUTO_INCREMENT,
            button_id INT(11) NOT NULL,
            ip_address VARCHAR(100) NOT NULL,
            user_agent VARCHAR(255) NOT NULL,
            click_date DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}

// Adiciona a página de configurações do plugin no menu de administração
add_action('admin_menu', 'whats_lp_add_admin_menu');
function whats_lp_add_admin_menu() {
    add_menu_page(
        'WhatsLp Plugin',
        'WhatsLp',
        'manage_options',
        'whats-lp',
        'whats_lp_admin_page_callback',
        'dashicons-whatsapp',
        100
    );
}

// Cria o conteúdo da página de configurações do plugin
function whats_lp_admin_page_callback() {
    ?>
    <div class="wrap">
        <h1>Configurações do WhatsLp Plugin</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('whats_lp_settings');
            do_settings_sections('whats_lp_settings');
            submit_button();
            ?>
        </form>
	    <footer style="margin-top: 20px;">
            <p>By <a href="https://lupee.com.br" target="_blank">Luiz Mariano</a></p>
        </footer>
    </div>
    <?php
}

// Registra as configurações do plugin
add_action('admin_init', 'whats_lp_settings_init');
function whats_lp_settings_init() {
    register_setting(
        'whats_lp_settings',
        'whats_lp_pages'
    );
    register_setting(
        'whats_lp_settings',
        'whats_lp_button_size'
    );
    register_setting(
        'whats_lp_settings',
        'whats_lp_phone_number'
    );
    register_setting(
        'whats_lp_settings',
        'whats_lp_button_position'
    );
    register_setting(
        'whats_lp_settings',
        'whats_lp_button_z_index',
        array(
            'type' => 'integer',
            'default' => 9999,
            'sanitize_callback' => 'absint',
        )
    );

    add_settings_section(
        'whats_lp_settings_section',
        'Configurações do Botão WhatsApp',
        'whats_lp_settings_section_callback',
        'whats_lp_settings'
    );

    add_settings_field(
        'whats_lp_pages',
        'Páginas para ocultar o botão WhatsApp',
        'whats_lp_pages_field_callback',
        'whats_lp_settings',
        'whats_lp_settings_section'
    );

    add_settings_field(
        'whats_lp_button_size',
        'Tamanho do botão WhatsApp',
        'whats_lp_button_size_field_callback',
        'whats_lp_settings',
        'whats_lp_settings_section'
    );

    add_settings_field(
        'whats_lp_phone_number',
        'Número de telefone do WhatsApp',
        'whats_lp_phone_number_field_callback',
        'whats_lp_settings',
        'whats_lp_settings_section'
    );

    add_settings_field(
        'whats_lp_button_position',
        'Posição do botão WhatsApp',
        'whats_lp_button_position_field_callback',
        'whats_lp_settings',
        'whats_lp_settings_section'
    );

    add_settings_field(
        'whats_lp_button_z_index',
        'Valor de z-index do botão WhatsApp',
        'whats_lp_button_z_index_field_callback',
        'whats_lp_settings',
        'whats_lp_settings_section'
    );
}

// Adicione a função de rastrear cliques no botão.
add_action('wp_ajax_whats_lp_track_click', 'whats_lp_track_click');

function whats_lp_track_click() {
    $sanitized_nonce = sanitize_text_field($_POST['whats_lp_nonce']);
    
    if (!isset($sanitized_nonce) || 
        !wp_verify_nonce(sanitize_key($sanitized_nonce), basename(__FILE__)) || 
        !wp_verify_nonce($sanitized_nonce, basename(__FILE__))) {
        wp_send_json_error();
    }

    $button_id = sanitize_text_field($_POST['button_id']);

    // Adicione uma nova entrada na tabela de rastreamento de cliques
    global $wpdb;
    $query = $wpdb->insert(
        $wpdb->prefix . 'whats_lp_click_tracking',
        array(
            'button_id' => $button_id,
            'ip_address' => sanitize_text_field($_SERVER['REMOTE_ADDR']),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT']),
            'click_date' => current_time('mysql')
        )
    );

    if (!$query) {
        wp_send_json_error();
    }

    wp_send_json_success();
}																			
// Função de callback para exibir a descrição da seção de configurações
function whats_lp_settings_section_callback() {
    echo esc_html('Personalize as configurações do botão WhatsApp:');
}

// Função de callback para exibir o campo de páginas
function whats_lp_pages_field_callback() {
    $whats_lp_pages = get_option('whats_lp_pages', '');
    ?>
    <textarea name="whats_lp_pages" rows="5" cols="50"><?php echo esc_html(esc_textarea(implode("\n", explode(", ", $whats_lp_pages)))); ?></textarea>
    <p class="description">Insira a URL completa das páginas em que você deseja ocultar o botão WhatsApp, uma por linha. Exemplo: <code>https://site.com.br/pagina-teste</code> não esqueça da / no fim da URL.</p>
    <?php
}

// Função de callback para exibir o campo de tamanho do botão
function whats_lp_button_size_field_callback() {
    $whats_lp_button_size = get_option('whats_lp_button_size', '80px');
    ?>
    <input type="text" name="whats_lp_button_size" value="<?php echo esc_html(esc_attr($whats_lp_button_size)); ?>" />
    <p class="description">Defina o tamanho do botão WhatsApp. Use um valor em pixels (Exemplo, <code>80px</code>).</p>
    <?php
}

// Função de callback para exibir o campo de número de telefone
function whats_lp_phone_number_field_callback() {
    $whats_lp_phone_number = get_option('whats_lp_phone_number', '');
    ?>
    <input type="text" name="whats_lp_phone_number" value="<?php echo esc_html(esc_attr($whats_lp_phone_number)); ?>" />
    <p class="description">Insira o número de WhatsApp.(Exemplo, <code>5511999999999</code>).</p>
    <?php
}

// Função de callback para exibir o campo de posição do botão
function whats_lp_button_position_field_callback() {
    $whats_lp_button_position = get_option('whats_lp_button_position', 'bottom-right');
    ?>
    <select name="whats_lp_button_position">
        <option value="top-left" <?php selected($whats_lp_button_position, 'top-left'); ?>>Topo Esquerda</option>
        <option value="top-right" <?php selected($whats_lp_button_position, 'top-right'); ?>>Topo Direita</option>
        <option value="bottom-left" <?php selected($whats_lp_button_position, 'bottom-left'); ?>>Inferior Esquerda</option>
        <option value="bottom-right" <?php selected($whats_lp_button_position, 'bottom-right'); ?>>Inferior Direita</option>
    </select>
    <?php
}

// Função de callback para exibir o campo de valor de z-index do botão
function whats_lp_button_z_index_field_callback() {
    $whats_lp_button_z_index = get_option('whats_lp_button_z_index', 99999);
    ?>
    <input type="number" name="whats_lp_button_z_index" value="<?php echo esc_html(esc_attr($whats_lp_button_z_index)); ?>" min="1" />
    <p class="description">Defina o valor de z-index para o botão WhatsApp.</p>
    <?php
}

// Adiciona o botão WhatsApp nas páginas selecionadas
add_action('wp_footer', 'whats_lp_add_whatsapp_button');
function whats_lp_add_whatsapp_button() {
    $whats_lp_pages = get_option('whats_lp_pages', '');
    $current_page_url = trailingslashit(get_permalink());

    // Verifica se o botão deve ser ocultado nesta página
    $hide_button = false;
    if ($whats_lp_pages) {
        $hide_pages = explode("\n", $whats_lp_pages);
        foreach ($hide_pages as $hide_page) {
            $hide_page = trim($hide_page);
            if ($hide_page === $current_page_url || $hide_page === $current_page_url . '/') {
                $hide_button = true;
                break;
            }
        }
    }

    if ($hide_button) {
        return;
    }

    // Restante do código para adicionar o botão WhatsApp
    $whats_lp_button_size = get_option('whats_lp_button_size', '300px');
    $whats_lp_phone_number = get_option('whats_lp_phone_number', '');
    $whats_lp_button_position = get_option('whats_lp_button_position', 'bottom-right');
    $whats_lp_button_z_index = get_option('whats_lp_button_z_index', 9999);

// Exibe o código do botão WhatsApp com a posição selecionada
echo '<div style="position: fixed;';
if ($whats_lp_button_position === 'top-left') {
    echo 'top: 20px; left: 20px;';
} elseif ($whats_lp_button_position === 'top-right') {
    echo 'top: 20px; right: 20px;';
} elseif ($whats_lp_button_position === 'bottom-left') {
    echo 'bottom: 20px; left: 20px;';
} else {
    echo 'bottom: 20px; right: 20px;';
}
echo ' z-index: ' . esc_attr($whats_lp_button_z_index) . ';">'; // Adiciona o valor de z-index ao estilo do botão
echo '<a href="https://wa.me/' . esc_attr($whats_lp_phone_number) . '" target="_blank" rel="nofollow">';
echo '<lottie-player src="' . plugins_url('assets/whats-lp.json', __FILE__) . '" background="transparent" speed="1" style="width: ' . esc_attr($whats_lp_button_size) . '; height: ' . esc_attr($whats_lp_button_size) . ';" loop autoplay></lottie-player>';
echo '</a>';
echo '</div>';
}

// Adiciona widget no painel do WordPress para exibir estatísticas de cliques
add_action( 'wp_dashboard_setup', 'whats_lp_add_dashboard_widget' );
function whats_lp_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'whats_lp_dashboard_widget',
        'Estatísticas do Botão do WhatsApp',
        'whats_lp_dashboard_widget_callback'
    );
}

function whats_lp_dashboard_widget_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'whats_lp_click_tracking';

    $total_clicks = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM %s", $table_name) );

    echo esc_html('<p>Total de cliques: ' . $total_clicks . '</p>');
}

// Adicione o rastreamento de cliques no botão WhatsApp
add_action('wp_footer', 'whats_lp_add_click_tracking');
function whats_lp_add_click_tracking() {
    $whats_lp_phone_number = get_option('whats_lp_phone_number', '');
    
    // Verifique se o número de telefone está definido
    if (empty($whats_lp_phone_number)) {
        return;
    }
    
    // Adicione um atributo data-button-id com um valor único para cada botão WhatsApp
    echo '<a href="https://wa.me/' . esc_attr($whats_lp_phone_number) . '" target="_blank" rel="nofollow" class="whats-lp-button" data-button-id="' . uniqid() . '">';

    // Adicione a função de rastreamento de cliques no botão WhatsApp
    ?>
      <script>
    jQuery(document).ready(function($) {
        $('body').on('click', '.whats-lp-button', function(e) {
            e.preventDefault();
            
            var buttonId = $(this).data('button-id');
            
            $.ajax({
                url: '<?php echo esc_html(admin_url('admin-ajax.php')); ?>',
                type: 'POST',
                data: {
                    action: 'whats_lp_track_click',
                    button_id: buttonId,
                    whats_lp_nonce: '<?php echo esc_html(wp_create_nonce(basename(__FILE__))); ?>'
                },
                success: function(response) {
                    console.log('Click tracked successfully');
                    // Redirecionar para o link do WhatsApp
                    window.location.href = $(e.currentTarget).attr('href');
                },
                error: function(xhr, status, error) {
                    console.log('Error tracking click');
                    // Redirecionar para o link do WhatsApp em caso de erro
                    window.location.href = $(e.currentTarget).attr('href');
                }
            });
        });
    });
}

    if (empty($whats_lp_phone_number)) {
        return;
    }

    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $("a[href='https://wa.me/<?php echo esc_js($whats_lp_phone_number); ?>']").on('click', function(e) {
                e.preventDefault();
                var buttonId = $(this).attr('data-button-id');
                var ajaxurl = '<?php echo esc_url(admin_url("admin-ajax.php")); ?>';
                var nonce = \'<?php echo esc_js(wp_create_nonce(basename(__FILE__))); ?>\';
                $.post(ajaxurl, {
                    action: "whats_lp_track_click",
                    button_id: buttonId,
                    whats_lp_nonce: nonce
                }, function(response) {
                    if(response.success) {
                        window.location.href = 'https://wa.me/<?php echo esc_js($whats_lp_phone_number); ?>';
                    } else {
                        alert('Ocorreu um erro ao rastrear o clique. Tente novamente.');
                    }
                });
            });
        });
    if (empty($whats_lp_phone_number)) {
        return; // Não faça nada se o número de telefone não estiver definido
    }

    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('a[href="https://wa.me/<?php echo esc_js($whats_lp_phone_number); ?>"]').on('click', function() {
                var data = {
                    action: 'whats_lp_track_click',
                    whats_lp_nonce: '<?php echo esc_js(wp_create_nonce(basename(__FILE__))); ?>',
                    button_id: 1
                };

                // Envie uma solicitação AJAX para rastrear o clique
				$.post('<?php echo esc_url_raw(admin_url('admin-ajax.php')); ?>', data, function(response) {
                });
            });
        });
    </script>
    <?php
}

// Carregar lottie-player.js
function chat_app_br_enqueue_lottie_player() {
    $script_path = plugin_dir_url( __FILE__ ) . 'assets/lottie-player.js';

    // Enfileira o script
    wp_enqueue_script('lottie-player', $script_path, array(), '1.0', true);
}

add_action('wp_enqueue_scripts', 'chat_app_br_enqueue_lottie_player');