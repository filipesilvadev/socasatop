<?php

function child_enqueue__parent_scripts()
{
    wp_enqueue_style('parent', get_template_directory_uri() . '/style.css');
}
add_action('wp_enqueue_scripts', 'child_enqueue__parent_scripts');

function scripts_so_casa_top()
{
    wp_register_style('so-casa-top', get_stylesheet_directory_uri() . '/assets/socasatop.css', array(), '1.0.0', 'all');
    wp_enqueue_style('so-casa-top');

    wp_register_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '2.4.1', 'all');
    wp_enqueue_style('select2');

    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '2.4.1', true);
    wp_enqueue_script('so-casa-top', get_stylesheet_directory_uri() . '/assets/socasatop.js', array('jquery'), '1.0.0', true);

    $options = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_nonce')
    ];

    wp_localize_script('so-casa-top', 'site', $options);
}
add_action('wp_enqueue_scripts', 'scripts_so_casa_top', 5);

function enqueue_sweetalert2_script()
{
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_sweetalert2_script');

function media_load_scripts()
{
    wp_enqueue_media();
}
add_action('wp_enqueue_scripts', 'media_load_scripts');

include_once "inc/custom/immobile/post.php";
include_once "inc/custom/lead/post.php";
include_once "inc/custom/broker/post.php";
include_once "inc/custom/location/taxonomy.php";
include_once "inc/custom/view-immobile/post.php";
include_once "inc/custom/search-ai/post.php";
include_once "inc/custom/broker/register.php";
include_once "inc/custom/immobile/admin-approval.php";
include_once "inc/custom/broker/shortcodes/broker-immobiles.php";
include_once "inc/custom/immobile/elementor-widgets.php";

// Verificar se os arquivos existem antes de incluir
if (file_exists(get_stylesheet_directory() . '/inc/custom/avisos/post.php')) {
    include_once "inc/custom/avisos/post.php";
    // Registrar evento de diagnóstico
    error_log('Arquivo de avisos/post.php incluído com sucesso');
} else {
    error_log('ERRO: Arquivo avisos/post.php não encontrado em: ' . get_stylesheet_directory() . '/inc/custom/avisos/post.php');
}

// Incluir arquivo de diagnóstico
if (file_exists(get_stylesheet_directory() . '/inc/custom/avisos/diagnostico.php')) {
    include_once "inc/custom/avisos/diagnostico.php";
} else {
    error_log('ERRO: Arquivo diagnostico.php não encontrado');
}

include_once "inc/filter/settings.php";
include_once "inc/ajax.php";

function display_amount()
{
    $amount = floatval(get_post_meta(get_the_ID(), 'amount', true));
    return "R$" . number_format($amount, 0, ',', '.');
}
add_shortcode('field_amount', 'display_amount');

function display_page_link($atts)
{
    $atts = shortcode_atts(array(
        'page' => '',
    ), $atts);
    extract($atts);
    $post_ID = get_the_ID();

    switch ($page) {
        case 'lead':
            $link = home_url("/editar-lead/?post=$post_ID");
            break;
        default:
            $link = "";
            break;
    }

    return $link;
}
add_shortcode('page_link', 'display_page_link');

function display_post_title()
{
    $name = '';
    if (isset($_GET['post'])) {
        $name = get_the_title($_GET['post']);
    }
    return $name;
}
add_shortcode('field_title', 'display_post_title');

function send_message_broker()
{
?>
    <script>
        jQuery(document).ready(function($) {
            let post_id = <?php echo get_the_ID(); ?>;
            $.ajax({
                url: site.ajax_url,
                method: 'POST',
                data: {
                    action: 'send_message_broker',
                    post_id: post_id,
                },
                success: function(response) {
                    if (response.data.json !== undefined) {
                        contacts = response.data.json;
                        $.ajax({
                            url: 'https://zion.digitalestudio.com.br/webhook/8d3a837a-dadc-40e0-aa96-a95a039fdc66',
                            method: 'POST',
                            data: {
                                contacts: response.data.json
                            }
                        });
                    }
                },
            });
        });
    </script>
<?php
    return "";
}
add_shortcode('send_message_broker', 'send_message_broker');

function display_must_show_meta($atts)
{
    $roles_user = wp_get_current_user()->roles;
    $attributes = shortcode_atts(array(
        'key' => ''
    ), $atts);

    if (!in_array('administrator', $roles_user)) {
        $meta = "";
    } else {
        $meta = get_post_meta(get_the_ID(), $attributes['key'], true);
    }

    return $meta;
}
add_shortcode('must_show_meta', 'display_must_show_meta');

function display_hidden_user_not_admin()
{
    $roles_user = wp_get_current_user()->roles;

    if (!in_array('administrator', $roles_user)) {
        $class = "d-none";
    }
    return $class ?? "";
}
add_shortcode('hidden_user_not_admin', 'display_hidden_user_not_admin');

function redirect_if_not_logged_in() {
  $loginID = get_page_by_path('login')->ID;
  $registerID = get_page_by_path('cadastro-corretor')->ID;
  $ia_page_ID = get_page_by_path('so-casa-top-ia')->ID;
  $current_url = $_SERVER['REQUEST_URI'];

  if (is_page($ia_page_ID) || 
      is_page($registerID) ||
      is_singular('immobile') || 
      strpos($current_url, '/listaimoveis/') === 0) {
      return;
  }

  if (!is_user_logged_in() && !is_page($loginID)) {
      wp_redirect(get_permalink($ia_page_ID));
      exit;
  }
}
add_action('template_redirect', 'redirect_if_not_logged_in');

function enqueue_smart_search_assets() {
  wp_enqueue_script('react', 'https://unpkg.com/react@17.0.2/umd/react.production.min.js', array(), '17.0.2', true);
  wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@17.0.2/umd/react-dom.production.min.js', array('react'), '17.0.2', true);
  
  wp_enqueue_script(
      'smart-search',
      get_stylesheet_directory_uri() . '/inc/custom/search-ai/assets/js/smart-search.js',
      array('react', 'react-dom'),
      '1.0.0',
      true
  );

  wp_localize_script('smart-search', 'smartSearchData', array(
      'restUrl' => rest_url('smart-search/v1/'),
      'nonce' => wp_create_nonce('wp_rest')
  ));
}
add_action('wp_enqueue_scripts', 'enqueue_smart_search_assets', 100);

wp_enqueue_script(
  'smart-search',
  get_stylesheet_directory_uri() . '/inc/custom/search-ai/assets/js/smart-search.js',
  array('react', 'react-dom'),
  '1.0.0',
  true
);

// Adicionar função de debug
function add_smart_search_debug() {
  ?>
  <script>
      console.log('Smart Search Assets Debug:', {
          react: typeof React,
          reactDOM: typeof ReactDOM,
          smartSearchData: typeof smartSearchData,
          container: document.getElementById('smart-search-root')
      });
  </script>
  <?php
}
add_action('wp_footer', 'add_smart_search_debug', 999);

add_action('init', function() {
  error_log('URL da API: ' . rest_url());
  error_log('Namespace API: ' . rest_url('smart-search/v1/'));
});

add_action('wp_footer', function() {
  if (is_page()) {  // ou ajuste conforme a página onde está usando o shortcode
      ?>
      <script>
      console.log('SmartSearch Debug:');
      console.log('Container exists:', !!document.getElementById('smart-search-root'));
      console.log('wp.element exists:', !!window.wp?.element);
      </script>
      <?php
  }
});

include_once "inc/custom/broker/dashboard.php";
include_once "inc/custom/broker/metrics.php";
include_once "inc/custom/broker/shortcodes/broker-immobiles.php";
include_once "inc/custom/immobile/contact-form.php";
include_once "inc/custom/immobile/metrics.php";
include_once "inc/custom/search-ai/views.php";
include_once "inc/custom/search-ai/checkout.php";

function enqueue_mp_scripts() {
  if (is_page('checkout')) {
      wp_enqueue_script('mercadopago', 'https://sdk.mercadopago.com/js/v2', [], null, true);
  }
}
add_action('wp_enqueue_scripts', 'enqueue_mp_scripts');

function rest_api_init_callback() {
  
  register_rest_route('smart-search/v1', '/process-payment', array(
      'methods' => 'POST',
      'callback' => function($request) {
          require_once get_stylesheet_directory() . '/inc/custom/search-ai/api.php';
          $api = new Smart_Search_API();
          return $api->handle_payment($request);
      },
      'permission_callback' => '__return_true'
  ));
}
add_action('rest_api_init', 'rest_api_init_callback');

function render_pagamento_confirmado() {
  if (!isset($_SERVER['HTTP_REFERER']) || !strpos($_SERVER['HTTP_REFERER'], 'checkout')) {
      return '<div class="payment-error">
          <p>Página acessada incorretamente.</p>
          <a href="/meus-imoveis" class="button">Voltar para Meus Imóveis</a>
      </div>';
  }

  $referer_url = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
  parse_str($referer_url, $query_params);
  $property_ids = isset($query_params['properties']) ? explode(',', $query_params['properties']) : [];

  ob_start();
  ?>
  <div class="payment-success">
      <div class="success-icon">✓</div>
      <h2>Pagamento Confirmado!</h2>
      
      <div class="payment-details">
          <p>Os seguintes imóveis serão exibidos de maneira prioritária nos próximos 30 dias:</p>
          <ul>
              <?php foreach($property_ids as $id): ?>
                  <li><?php echo get_the_title($id); ?></li>
              <?php endforeach; ?>
          </ul>
          
          <p class="dates">Período de patrocínio: <?php 
              echo date('d/m/Y') . ' até ' . date('d/m/Y', strtotime('+30 days')); 
          ?></p>
      </div>

      <a href="/meus-imoveis" class="return-button">Voltar para Meus Imóveis</a>
  </div>

  <style>
      .payment-success {
          max-width: 600px;
          margin: 40px auto;
          padding: 30px;
          background: white;
          border-radius: 10px;
          box-shadow: 0 2px 10px rgba(0,0,0,0.1);
          text-align: center;
      }

      .success-icon {
          font-size: 48px;
          color: #4CAF50;
          margin-bottom: 20px;
      }

      .payment-details {
          margin: 30px 0;
          text-align: left;
      }

      .payment-details ul {
          margin: 15px 0;
          padding-left: 20px;
      }

      .payment-details li {
          margin-bottom: 10px;
      }

      .dates {
          margin-top: 20px;
          color: #666;
          font-size: 0.9em;
      }

      .return-button {
          display: inline-block;
          padding: 12px 24px;
          background-color: #4CAF50;
          color: white;
          text-decoration: none;
          border-radius: 5px;
          transition: background-color 0.3s;
      }

      .return-button:hover {
          background-color: #45a049;
          color: white;
      }

      .payment-error {
          max-width: 600px;
          margin: 40px auto;
          padding: 30px;
          background: #fff3f3;
          border-radius: 10px;
          text-align: center;
      }
  </style>
  <?php
  return ob_get_clean();
}
add_shortcode('pagamento_confirmado', 'render_pagamento_confirmado');

function register_elementor_rest_routes() {
  register_rest_route('smart-search/v1', '/search', array(
      'methods' => 'GET',
      'callback' => function($request) {
          require_once get_stylesheet_directory() . '/inc/custom/search-ai/api.php';
          $api = new Smart_Search_API();
          return $api->handle_search($request);
      },
      'permission_callback' => '__return_true'
  ));
}
add_action('rest_api_init', 'register_elementor_rest_routes');

include_once "inc/custom/search-ai/sponsored.php";
include_once "inc/custom/immobile/create-immobile-flow.php";
include_once "inc/custom/immobile/ajax-payment.php";

function enqueue_jquery_mask() {
    wp_enqueue_script('jquery-mask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js', array('jquery'), '1.14.16', true);
}
add_action('wp_enqueue_scripts', 'enqueue_jquery_mask');

require_once 'inc/custom/admin-dashboard/dashboard.php';
require_once get_stylesheet_directory() . '/inc/custom/search-ai/register.php';

// Adicione este código no arquivo functions.php do tema ou em um arquivo de inicialização

function create_broker_immobile_tables() {
  global $wpdb;
  $charset_collate = $wpdb->get_charset_collate();

  $broker_immobile_table = $wpdb->prefix . 'broker_immobile';
  
  $sql = "CREATE TABLE IF NOT EXISTS $broker_immobile_table (
      id bigint(20) NOT NULL AUTO_INCREMENT,
      immobile_id bigint(20) NOT NULL,
      broker_id bigint(20) NOT NULL,
      is_sponsor tinyint(1) DEFAULT 0,
      created_at datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY  (id),
      KEY broker_id (broker_id),
      KEY immobile_id (immobile_id)
  ) $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}

register_activation_hook(__FILE__, 'create_broker_immobile_tables');

// Função para migrar dados existentes
function migrate_existing_broker_data() {
  global $wpdb;
  
  $posts = get_posts(array(
      'post_type' => 'immobile',
      'numberposts' => -1
  ));

  foreach ($posts as $post) {
      $broker_id = get_post_meta($post->ID, 'broker', true);
      if ($broker_id) {
          $wpdb->insert(
              $wpdb->prefix . 'broker_immobile',
              array(
                  'immobile_id' => $post->ID,
                  'broker_id' => $broker_id,
                  'is_sponsor' => 0
              )
          );
      }
  }
}

add_action('admin_init', 'migrate_existing_broker_data');

function custom_media_gallery_metabox() {
  add_meta_box(
      'immobile_media_gallery',
      'Galeria de Mídias',
      'render_media_gallery_metabox',
      'immobile',
      'normal',
      'high'
  );
}
add_action('add_meta_boxes', 'custom_media_gallery_metabox');

function render_media_gallery_metabox($post) {
  wp_enqueue_media();
  $gallery_images = get_post_meta($post->ID, 'immobile_gallery', true);
  $gallery_videos = get_post_meta($post->ID, 'immobile_videos', true);
  ?>
  <div>
      <label>Imagens da Galeria:</label>
      <div id="gallery-container">
          <input type="hidden" id="immobile_gallery" name="immobile_gallery" value="<?php echo esc_attr($gallery_images); ?>">
          <button type="button" id="upload-gallery-button" class="button">Selecionar Imagens</button>
          <div id="gallery-preview" class="gallery-preview">
              <?php
              if ($gallery_images) {
                  $image_ids = explode(',', $gallery_images);
                  foreach ($image_ids as $image_id) {
                      $image = wp_get_attachment_image_src($image_id, 'thumbnail');
                      if ($image) {
                          echo '<div class="gallery-item" data-id="' . $image_id . '">';
                          echo '<img src="' . $image[0] . '">';
                          echo '<span class="remove-image">×</span>';
                          echo '</div>';
                      }
                  }
              }
              ?>
          </div>
      </div>
  </div>

  <div>
      <label>URLs de Vídeos (um por linha):</label>
      <textarea name="immobile_videos" rows="4" style="width:100%;"><?php echo esc_textarea($gallery_videos); ?></textarea>
  </div>

  <script>
  jQuery(document).ready(function($) {
      $('#upload-gallery-button').on('click', function() {
          var mediaUploader = wp.media({
              title: 'Selecionar Imagens',
              button: { text: 'Selecionar' },
              multiple: true
          });

          mediaUploader.on('select', function() {
              var attachments = mediaUploader.state().get('selection').map(
                  function(attachment) { return attachment.toJSON(); }
              );

              var currentIds = $('#immobile_gallery').val() ? $('#immobile_gallery').val().split(',') : [];

              attachments.forEach(function(attachment) {
                  if (currentIds.indexOf(attachment.id.toString()) === -1) {
                      currentIds.push(attachment.id);
                      $('#gallery-preview').append(
                          '<div class="gallery-item" data-id="' + attachment.id + '">' +
                          '<img src="' + attachment.sizes.thumbnail.url + '">' +
                          '<span class="remove-image">×</span>' +
                          '</div>'
                      );
                  }
              });

              $('#immobile_gallery').val(currentIds.join(','));
          });

          mediaUploader.open();
      });

      $(document).on('click', '.remove-image', function() {
          var $item = $(this).parent();
          var imageId = $item.data('id');
          var currentIds = $('#immobile_gallery').val().split(',');
          var newIds = currentIds.filter(function(id) { return id != imageId; });
          
          $('#immobile_gallery').val(newIds.join(','));
          $item.remove();
      });
  });
  </script>

  <style>
  .gallery-preview {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
  }
  .gallery-item {
      position: relative;
      width: 100px;
      height: 100px;
  }
  .gallery-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
  }
  .remove-image {
      position: absolute;
      top: -5px;
      right: -5px;
      background: red;
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
  }
  </style>
  <?php
}

function save_media_gallery_metabox($post_id) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

  if (isset($_POST['immobile_gallery'])) {
      update_post_meta($post_id, 'immobile_gallery', sanitize_text_field($_POST['immobile_gallery']));
  }

  if (isset($_POST['immobile_videos'])) {
      update_post_meta($post_id, 'immobile_videos', sanitize_text_field($_POST['immobile_videos']));
  }
}
add_action('save_post_immobile', 'save_media_gallery_metabox');

include_once "inc/custom/immobile/broker-select-metabox.php";
include_once "inc/custom/immobile/metabox.php";
include_once "inc/custom/search-ai/sponsored-carousel.php";

/**
 * Remove a barra de administração preta para usuários com papel de Autor
 */
function remove_admin_bar_for_authors() {
    // Verifica se o usuário está logado e tem o papel de Autor
    if (is_user_logged_in() && current_user_can('author') && !current_user_can('administrator')) {
        // Remove a barra de administração
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'remove_admin_bar_for_authors');

/**
 * Bloqueia o acesso ao painel de administração para usuários com papel de Autor
 */
function restrict_admin_access_for_authors() {
    // Verifica se o usuário está na área administrativa, está logado e tem o papel de Autor
    if (is_admin() && is_user_logged_in() && current_user_can('author') && !current_user_can('administrator')) {
        // Exceções: permitir AJAX, uploads e acesso a API REST
        global $pagenow;
        if ($pagenow === 'admin-ajax.php' || $pagenow === 'async-upload.php' || $pagenow === 'rest-api') {
            return;
        }
        
        // Redireciona para a página inicial do site
        wp_redirect(home_url());
        exit;
    }
}
add_action('init', 'restrict_admin_access_for_authors');

function enqueue_approval_assets() {
    // Registrar apenas na página de aprovação de imóveis ou quando o shortcode está presente
    if (is_page('aprovacao-imoveis') || 
        is_admin() && isset($_GET['page']) && $_GET['page'] == 'immobile-approval') {
        
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        wp_enqueue_style('approval-styles', get_stylesheet_directory_uri() . '/inc/custom/immobile/assets/approval.css', array(), '1.0.0');
        
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('approval-scripts', get_stylesheet_directory_uri() . '/inc/custom/immobile/assets/approval.js', array('jquery', 'jquery-ui-datepicker'), '1.0.0', true);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_approval_assets');
add_action('admin_enqueue_scripts', 'enqueue_approval_assets');

// Garantir que o Elementor processe corretamente o shortcode de aprovação
function elementor_custom_shortcodes() {
    // Registrar shortcode para o Elementor
    if (class_exists('\\Elementor\\Plugin')) {
        add_shortcode('immobile_approval', 'display_immobile_approval_page');
    }
}
add_action('init', 'elementor_custom_shortcodes', 20);

// Forçar o processamento de shortcodes no Elementor
function render_shortcode_in_elementor($content) {
    if (has_shortcode($content, 'immobile_approval')) {
        $content = do_shortcode($content);
    }
    return $content;
}
add_filter('elementor/frontend/the_content', 'render_shortcode_in_elementor');
add_filter('elementor/widget/render_content', 'render_shortcode_in_elementor');

// Redefinindo o shortcode imóveis diretamente no arquivo functions.php
function immobile_approval_shortcode() {
    if (function_exists('display_immobile_approval_page')) {
        return display_immobile_approval_page();
    } else {
        return '<p>Sistema de aprovação não disponível.</p>';
    }
}
// Registrar o shortcode diretamente (independentemente do Elementor)
add_shortcode('immobile_approval', 'immobile_approval_shortcode');

// Script de depuração para shortcode immobile_approval
function debug_immobile_approval() {
    global $post;
    if (is_page() && has_shortcode($post->post_content, 'immobile_approval')) {
        ?>
        <script>
            console.log('Página contém shortcode immobile_approval');
            console.log('Função display_immobile_approval_page existe: <?php echo function_exists('display_immobile_approval_page') ? 'Sim' : 'Não'; ?>');
        </script>
        <div style="display:none" id="debug-shortcode">
            <?php 
            if (function_exists('display_immobile_approval_page')) {
                echo "Função de aprovação existe e será chamada.";
                // Teste direto da função
                $content = display_immobile_approval_page();
                echo "Comprimento do conteúdo gerado: " . strlen($content);
            } else {
                echo "ERRO: Função de aprovação não encontrada!";
            }
            ?>
        </div>
        <?php
    }
}
add_action('wp_footer', 'debug_immobile_approval', 100);

// Inclusão do arquivo de post type de leads
require_once get_stylesheet_directory() . '/inc/custom/lead/post-type.php';

/**
 * INÍCIO DA INTEGRAÇÃO EVOLUTION API PARA WHATSAPP
 * Adiciona funcionalidade para enviar avisos via WhatsApp quando posts são publicados
 */

// Registrar o CPT Avisos diretamente no functions.php
function register_avisos_post_type_direct() {
    $labels = array(
        'name'                  => 'Avisos',
        'singular_name'         => 'Aviso',
        'menu_name'             => 'Avisos',
        'name_admin_bar'        => 'Aviso',
        'archives'              => 'Arquivo de Avisos',
        'all_items'             => 'Todos os Avisos',
        'add_new_item'          => 'Adicionar Novo Aviso',
        'add_new'               => 'Adicionar Novo',
        'new_item'              => 'Novo Aviso',
        'edit_item'             => 'Editar Aviso',
        'update_item'           => 'Atualizar Aviso',
        'view_item'             => 'Ver Aviso',
        'search_items'          => 'Buscar Aviso',
    );
    
    $args = array(
        'label'                 => 'Aviso',
        'description'           => 'Avisos para envio via WhatsApp',
        'labels'                => $labels,
        'supports'              => array('title', 'editor', 'thumbnail'),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-megaphone',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
    );
    
    /* Desativado temporariamente register_post_type('avisos */', $args);
}
add_action('init', 'register_avisos_post_type_direct');

// Hook para quando um novo aviso for publicado
function send_whatsapp_on_avisos_publish_direct($new_status, $old_status, $post) {
    // Verificar se é o tipo de post "avisos" e se o status mudou para "publish"
    if ($post->post_type === 'avisos' && $new_status === 'publish' && $old_status !== 'publish') {
        // Executar o envio diretamente para não depender de eventos agendados
        send_whatsapp_notification_direct($post->ID);
    }
}
add_action('transition_post_status', 'send_whatsapp_on_avisos_publish_direct', 10, 3);

// Função para enviar notificações WhatsApp
function send_whatsapp_notification_direct($post_id) {
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'avisos') {
        return;
    }
    
    $titulo = $post->post_title;
    $conteudo = wp_strip_all_tags(get_the_content(null, false, $post));
    $permalink = get_permalink($post_id);
    
    // Obter todos os usuários corretores (autores)
    $args = array(
        'role' => 'author',
        'fields' => array('ID', 'display_name', 'user_email'),
    );
    
    $corretores = get_users($args);
    
    // Se não houver corretores, encerrar a função
    if (empty($corretores)) {
        error_log('Nenhum corretor encontrado para enviar notificação.');
        return;
    }
    
    foreach ($corretores as $corretor) {
        // Obter o número do WhatsApp do corretor (meta personalizado)
        $whatsapp = get_user_meta($corretor->ID, 'whatsapp', true);
        
        if (empty($whatsapp)) {
            error_log('Corretor ID ' . $corretor->ID . ' não possui WhatsApp cadastrado.');
            continue;
        }
        
        // Formatar o WhatsApp (remover caracteres não numéricos)
        $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);
        
        // Verificar se o WhatsApp está no formato correto
        if (strlen($whatsapp) < 10) {
            error_log('Número de WhatsApp inválido para o corretor ID ' . $corretor->ID);
            continue;
        }
        
        // Enviar a mensagem via Evolution API
        send_whatsapp_via_evolution_api_direct($whatsapp, $titulo, $conteudo, $permalink, $corretor->display_name);
    }
}

// Integração com a Evolution API
function send_whatsapp_via_evolution_api_direct($whatsapp, $titulo, $conteudo, $permalink, $nome_corretor) {
    // URL da Evolution API (substitua pelo seu endpoint)
    $api_url = get_option('evolution_api_url', '');
    $instance_name = get_option('evolution_api_instance', '');
    $api_key = get_option('evolution_api_key', '');
    
    if (empty($api_url) || empty($instance_name) || empty($api_key)) {
        error_log('Configuração da Evolution API não está completa.');
        return false;
    }
    
    // Formatar a mensagem
    $mensagem = "Olá, $nome_corretor!\n\n";
    $mensagem .= "*NOVO AVISO IMPORTANTE*\n\n";
    $mensagem .= "*$titulo*\n\n";
    $mensagem .= "$conteudo\n\n";
    $mensagem .= "Saiba mais: $permalink";
    
    // Preparar a requisição
    $endpoint = "$api_url/message/sendText/$instance_name";
    
    $body = array(
        'number' => $whatsapp,
        'options' => array(
            'delay' => 1200,
            'presence' => 'composing',
        ),
        'textMessage' => array(
            'text' => $mensagem
        )
    );
    
    $args = array(
        'method' => 'POST',
        'timeout' => 30,
        'headers' => array(
            'Content-Type' => 'application/json',
            'apikey' => $api_key
        ),
        'body' => json_encode($body)
    );
    
    // Enviar a requisição
    $response = wp_remote_post($endpoint, $args);
    
    // Verificar a resposta
    if (is_wp_error($response)) {
        error_log('Erro ao enviar mensagem via Evolution API: ' . $response->get_error_message());
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code !== 200 && $response_code !== 201) {
        error_log("Erro na Evolution API: $response_code - $response_body");
        return false;
    }
    
    error_log("Mensagem enviada com sucesso para $whatsapp");
    return true;
}

// Adicionar metabox para o CPT Avisos
function register_avisos_metabox_direct() {
    add_meta_box(
        'avisos_options',
        'Opções de Envio',
        'render_avisos_metabox_direct',
        'avisos',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'register_avisos_metabox_direct');

// Renderizar o metabox de opções
function render_avisos_metabox_direct($post) {
    // Adicionar nonce para verificação
    wp_nonce_field('avisos_metabox_nonce_direct', 'avisos_metabox_nonce_direct');
    
    // Recuperar valores existentes
    $grupos_corretores = get_post_meta($post->ID, '_grupos_corretores', true);
    
    ?>
    <div class="avisos-metabox">
        <p>
            <label for="grupos_corretores">Enviar para:</label><br />
            <select id="grupos_corretores" name="grupos_corretores" class="widefat">
                <option value="todos" <?php selected($grupos_corretores, 'todos'); ?>>Todos os corretores</option>
                <option value="ativos" <?php selected($grupos_corretores, 'ativos'); ?>>Apenas corretores ativos</option>
                <option value="destacados" <?php selected($grupos_corretores, 'destacados'); ?>>Apenas corretores destacados</option>
            </select>
        </p>
        
        <p class="description">
            Nota: Os avisos só serão enviados para corretores com número de WhatsApp cadastrado no perfil.
        </p>
    </div>
    <?php
}

// Salvar dados do metabox
function save_avisos_metabox_direct($post_id) {
    // Verificar se é um autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Verificar nonce
    if (!isset($_POST['avisos_metabox_nonce_direct']) || !wp_verify_nonce($_POST['avisos_metabox_nonce_direct'], 'avisos_metabox_nonce_direct')) {
        return;
    }
    
    // Verificar permissões
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Salvar grupo de corretores
    if (isset($_POST['grupos_corretores'])) {
        update_post_meta($post_id, '_grupos_corretores', sanitize_text_field($_POST['grupos_corretores']));
    }
}
add_action('save_post_avisos', 'save_avisos_metabox_direct');

// Adicionar campo de WhatsApp ao perfil do usuário
function add_whatsapp_field_direct($user) {
    ?>
    <h3>Informações de Contato para Avisos</h3>
    <table class="form-table">
        <tr>
            <th><label for="whatsapp">WhatsApp</label></th>
            <td>
                <input type="text" name="whatsapp" id="whatsapp" value="<?php echo esc_attr(get_user_meta($user->ID, 'whatsapp', true)); ?>" class="regular-text" />
                <p class="description">Informe o número com DDD, ex: (11) 98765-4321</p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'add_whatsapp_field_direct');
add_action('edit_user_profile', 'add_whatsapp_field_direct');

// Salvar o campo de WhatsApp
function save_whatsapp_field_direct($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    
    if (isset($_POST['whatsapp'])) {
        update_user_meta($user_id, 'whatsapp', sanitize_text_field($_POST['whatsapp']));
    }
}
add_action('personal_options_update', 'save_whatsapp_field_direct');
add_action('edit_user_profile_update', 'save_whatsapp_field_direct');

// Adicionar página de configurações para Evolution API
function add_evolution_api_settings_menu() {
    add_options_page(
        'Configurações da Evolution API',
        'Evolution API',
        'manage_options',
        'evolution-api-settings',
        'evolution_api_settings_page'
    );
}
add_action('admin_menu', 'add_evolution_api_settings_menu');

// Página de configurações da Evolution API
function evolution_api_settings_page() {
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Salvar configurações
    if (isset($_POST['evolution_api_settings_nonce']) && wp_verify_nonce($_POST['evolution_api_settings_nonce'], 'evolution_api_settings')) {
        update_option('evolution_api_url', sanitize_text_field($_POST['evolution_api_url']));
        update_option('evolution_api_instance', sanitize_text_field($_POST['evolution_api_instance']));
        update_option('evolution_api_key', sanitize_text_field($_POST['evolution_api_key']));
        echo '<div class="notice notice-success is-dismissible"><p>Configurações salvas com sucesso!</p></div>';
    }
    
    // Obter valores atuais
    $api_url = get_option('evolution_api_url', '');
    $instance_name = get_option('evolution_api_instance', '');
    $api_key = get_option('evolution_api_key', '');
    
    ?>
    <div class="wrap">
        <h1>Configurações da Evolution API</h1>
        <form method="post">
            <?php wp_nonce_field('evolution_api_settings', 'evolution_api_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">URL da Evolution API</th>
                    <td>
                        <input type="text" name="evolution_api_url" value="<?php echo esc_attr($api_url); ?>" class="regular-text" placeholder="https://sua-api-evolution.com.br" />
                        <p class="description">Ex: https://sua-api-evolution.com.br</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Nome da Instância</th>
                    <td>
                        <input type="text" name="evolution_api_instance" value="<?php echo esc_attr($instance_name); ?>" class="regular-text" placeholder="minha-instancia" />
                        <p class="description">Nome da instância configurada na Evolution API</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">API Key</th>
                    <td>
                        <input type="password" name="evolution_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                        <p class="description">Chave de API fornecida pela Evolution API</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Salvar Configurações" />
            </p>
        </form>
    </div>
    <?php
}

// Registrar configurações
function register_evolution_api_settings() {
    register_setting('evolution_api_options', 'evolution_api_url');
    register_setting('evolution_api_options', 'evolution_api_instance');
    register_setting('evolution_api_options', 'evolution_api_key');
}
add_action('admin_init', 'register_evolution_api_settings');

/**
 * FIM DA INTEGRAÇÃO EVOLUTION API PARA WHATSAPP
 */

/**
 * WEBHOOK PARA EVOLUTION API
 * Permite receber atualizações sobre o status de entrega das mensagens
 */

// Registrar a rota da API REST para o Webhook
function register_evolution_api_webhook_route_direct() {
    register_rest_route('evolution-api/v1', '/webhook', array(
        'methods'  => 'POST',
        'callback' => 'handle_evolution_api_webhook_direct',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'register_evolution_api_webhook_route_direct');

// Função que manipula as requisições do webhook
function handle_evolution_api_webhook_direct($request) {
    // Obter o corpo da requisição
    $body = $request->get_body();
    $data = json_decode($body, true);
    
    // Verificar se há dados
    if (empty($data)) {
        return new WP_REST_Response(array(
            'status' => 'error',
            'message' => 'Dados inválidos'
        ), 400);
    }
    
    // Registrar o evento no log
    error_log('Webhook da Evolution API recebido: ' . print_r($data, true));
    
    // Identificar o tipo de evento
    $event_type = isset($data['event']) ? $data['event'] : '';
    $message_id = isset($data['id']) ? $data['id'] : '';
    $status = isset($data['status']) ? $data['status'] : '';
    $phone = isset($data['phone']) ? $data['phone'] : '';
    
    // Gravar o status da mensagem no banco de dados
    if (!empty($message_id) && !empty($phone)) {
        // Aqui você pode implementar o registro do status, por exemplo, em uma tabela personalizada
        // ou em post meta, etc.
        
        // Exemplo de como poderia ser o registro em uma opção:
        $messages_log = get_option('evolution_api_messages_log', array());
        $messages_log[$message_id] = array(
            'phone' => $phone,
            'status' => $status,
            'event_type' => $event_type,
            'time' => current_time('mysql'),
            'data' => $data
        );
        update_option('evolution_api_messages_log', $messages_log);
    }
    
    // Retornar resposta de sucesso
    return new WP_REST_Response(array(
        'status' => 'success',
        'message' => 'Webhook processado com sucesso'
    ), 200);
}

/**
 * FIM DO WEBHOOK PARA EVOLUTION API
 */

/**
 * RELATÓRIO DE MENSAGENS EVOLUTION API
 * Adiciona página para visualizar status de entrega das mensagens
 */

// Adicionar menu para visualizar relatórios de mensagens
function evolution_api_messages_report_menu_direct() {
    add_submenu_page(
        'edit.php?post_type=avisos',
        'Relatório de Mensagens',
        'Relatório de Mensagens',
        'manage_options',
        'evolution-api-messages-report',
        'evolution_api_messages_report_page_direct'
    );
}
add_action('admin_menu', 'evolution_api_messages_report_menu_direct');

// Página de relatório de mensagens
function evolution_api_messages_report_page_direct() {
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Obter as mensagens
    $messages_log = get_option('evolution_api_messages_log', array());
    
    ?>
    <div class="wrap">
        <h1>Relatório de Mensagens WhatsApp</h1>
        
        <?php if (empty($messages_log)): ?>
            <div class="notice notice-info">
                <p>Nenhuma mensagem registrada ainda.</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID da Mensagem</th>
                        <th>Telefone</th>
                        <th>Status</th>
                        <th>Evento</th>
                        <th>Data/Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages_log as $message_id => $message): ?>
                        <tr>
                            <td><?php echo esc_html($message_id); ?></td>
                            <td><?php echo esc_html($message['phone']); ?></td>
                            <td>
                                <?php 
                                $status_colors = array(
                                    'sent' => '#0073aa',
                                    'delivered' => '#46b450',
                                    'read' => '#00a32a',
                                    'failed' => '#dc3232'
                                );
                                $color = isset($status_colors[$message['status']]) ? $status_colors[$message['status']] : '';
                                ?>
                                <span style="color: <?php echo esc_attr($color); ?>; font-weight: <?php echo ($message['status'] === 'read') ? 'bold' : 'normal'; ?>">
                                    <?php echo esc_html($message['status']); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($message['event_type']); ?></td>
                            <td><?php echo esc_html($message['time']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p>
                <form method="post" action="">
                    <?php wp_nonce_field('clear_messages_log', 'clear_messages_log_nonce'); ?>
                    <input type="submit" name="clear_messages_log" class="button button-secondary" value="Limpar Registro de Mensagens">
                </form>
            </p>
        <?php endif; ?>
    </div>
    <?php
    
    // Processar limpeza de registros
    if (isset($_POST['clear_messages_log']) && isset($_POST['clear_messages_log_nonce']) && wp_verify_nonce($_POST['clear_messages_log_nonce'], 'clear_messages_log')) {
        delete_option('evolution_api_messages_log');
        ?>
        <script>
            window.location.reload();
        </script>
        <?php
    }
}

/**
 * FIM DO RELATÓRIO DE MENSAGENS EVOLUTION API
 */

/**
 * NOTIFICAÇÕES E SUPORTE PARA EVOLUTION API
 */

// Adicionar notificação sobre o CPT Avisos
function evolution_api_admin_notice() {
    // Verificar se é um administrador
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Verificar se o CPT Avisos está registrado
    $post_types = get_post_types(array(), 'names');
    if (in_array('avisos', $post_types)) {
        // Verificar se as configurações da API estão preenchidas
        $api_url = get_option('evolution_api_url', '');
        $instance_name = get_option('evolution_api_instance', '');
        $api_key = get_option('evolution_api_key', '');
        
        if (empty($api_url) || empty($instance_name) || empty($api_key)) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>Evolution API para WhatsApp:</strong> O tipo de post "Avisos" está ativo, mas você ainda não configurou a API. 
                    <a href="<?php echo admin_url('options-general.php?page=evolution-api-settings'); ?>">Configurar agora</a>
                </p>
            </div>
            <?php
        }
    }
}
add_action('admin_notices', 'evolution_api_admin_notice');

// Adicionar coluna de status na listagem de Avisos
function add_status_column_to_avisos($columns) {
    $columns['status_envio'] = 'Status de Envio';
    return $columns;
}
add_filter('manage_avisos_posts_columns', 'add_status_column_to_avisos');

// Mostrar dados na coluna de status
function show_status_column_content($column, $post_id) {
    if ($column === 'status_envio') {
        $enviado = get_post_meta($post_id, '_enviado_whatsapp', true);
        
        if ($enviado) {
            echo '<span style="color: #46b450;">✓ Enviado</span>';
        } else {
            if (get_post_status($post_id) === 'publish') {
                echo '<span style="color: #dc3232;">✗ Não enviado</span> <a href="#" class="reenviar-aviso" data-post-id="' . esc_attr($post_id) . '">Reenviar</a>';
                
                // Adicionar script para reenvio
                ?>
                <script>
                jQuery(document).ready(function($) {
                    $('.reenviar-aviso').on('click', function(e) {
                        e.preventDefault();
                        var postId = $(this).data('post-id');
                        var button = $(this);
                        
                        button.text('Enviando...').attr('disabled', true);
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'reenviar_aviso_whatsapp',
                                post_id: postId,
                                nonce: '<?php echo wp_create_nonce('reenviar_aviso_whatsapp'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    button.parent().html('<span style="color: #46b450;">✓ Enviado</span>');
                                } else {
                                    button.text('Reenviar').removeAttr('disabled');
                                    alert('Erro ao reenviar: ' + response.data.message);
                                }
                            },
                            error: function() {
                                button.text('Reenviar').removeAttr('disabled');
                                alert('Erro na solicitação');
                            }
                        });
                    });
                });
                </script>
                <?php
            } else {
                echo '<span style="color: #999;">Pendente</span>';
            }
        }
    }
}
add_action('manage_avisos_posts_custom_column', 'show_status_column_content', 10, 2);

// Ajax para reenviar um aviso
function reenviar_aviso_whatsapp_callback() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'reenviar_aviso_whatsapp')) {
        wp_send_json_error(array('message' => 'Verificação de segurança falhou'));
    }
    
    // Verificar permissões
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Permissão negada'));
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(array('message' => 'ID de post inválido'));
    }
    
    // Reenviar a notificação
    $result = send_whatsapp_notification_direct($post_id);
    
    if ($result) {
        // Marcar como enviado
        update_post_meta($post_id, '_enviado_whatsapp', true);
        
        wp_send_json_success(array('message' => 'Aviso reenviado com sucesso'));
    } else {
        wp_send_json_error(array('message' => 'Falha ao reenviar o aviso'));
    }
}
add_action('wp_ajax_reenviar_aviso_whatsapp', 'reenviar_aviso_whatsapp_callback');

// Marcar post como enviado após o envio bem-sucedido
add_action('transition_post_status', function($new_status, $old_status, $post) {
    if ($post->post_type === 'avisos' && $new_status === 'publish' && $old_status !== 'publish') {
        // Marcar para verificar depois
        wp_schedule_single_event(time() + 30, 'verificar_envio_whatsapp', array($post->ID));
    }
}, 20, 3);

// Função para verificar se o envio foi concluído
function verificar_envio_whatsapp($post_id) {
    // Marcar como enviado
    update_post_meta($post_id, '_enviado_whatsapp', true);
}
add_action('verificar_envio_whatsapp', 'verificar_envio_whatsapp');

/**
 * FIM DAS NOTIFICAÇÕES E SUPORTE
 */

/**
 * SISTEMA DE LOGS PARA EVOLUTION API
 */

// Função para registrar logs específicos da Evolution API
function evolution_api_log($message, $type = 'info') {
    // Verifica se a opção de debug está ativada
    $debug_enabled = get_option('evolution_api_debug_mode', false);
    
    if (!$debug_enabled && $type === 'debug') {
        return; // Não registrar logs de debug se o modo debug não estiver ativado
    }
    
    // Formatar a mensagem com data, hora e tipo
    $formatted_message = date('[Y-m-d H:i:s]') . " [$type] " . $message;
    
    // Registrar no log do WordPress
    error_log('Evolution API: ' . $formatted_message);
    
    // Salvar em nosso próprio log também
    $logs = get_option('evolution_api_logs', array());
    
    // Limitar a 100 entradas para não sobrecarregar a tabela de opções
    if (count($logs) >= 100) {
        array_shift($logs); // Remove o primeiro item (mais antigo)
    }
    
    // Adicionar nova entrada no final
    $logs[] = array(
        'timestamp' => time(),
        'type' => $type,
        'message' => $message
    );
    
    update_option('evolution_api_logs', $logs);
}

// Substituir a função error_log nas funções da Evolution API
function send_whatsapp_via_evolution_api_direct_with_logging($whatsapp, $titulo, $conteudo, $permalink, $nome_corretor) {
    // URL da Evolution API (substitua pelo seu endpoint)
    $api_url = get_option('evolution_api_url', '');
    $instance_name = get_option('evolution_api_instance', '');
    $api_key = get_option('evolution_api_key', '');
    
    if (empty($api_url) || empty($instance_name) || empty($api_key)) {
        evolution_api_log('Configuração da Evolution API não está completa.', 'error');
        return false;
    }
    
    evolution_api_log("Preparando envio para $nome_corretor ($whatsapp)", 'info');
    
    // Formatar a mensagem
    $mensagem = "Olá, $nome_corretor!\n\n";
    $mensagem .= "*NOVO AVISO IMPORTANTE*\n\n";
    $mensagem .= "*$titulo*\n\n";
    $mensagem .= "$conteudo\n\n";
    $mensagem .= "Saiba mais: $permalink";
    
    // Preparar a requisição
    $endpoint = "$api_url/message/sendText/$instance_name";
    
    $body = array(
        'number' => $whatsapp,
        'options' => array(
            'delay' => 1200,
            'presence' => 'composing',
        ),
        'textMessage' => array(
            'text' => $mensagem
        )
    );
    
    $args = array(
        'method' => 'POST',
        'timeout' => 30,
        'headers' => array(
            'Content-Type' => 'application/json',
            'apikey' => $api_key
        ),
        'body' => json_encode($body)
    );
    
    evolution_api_log("Enviando requisição para $endpoint", 'debug');
    
    // Enviar a requisição
    $response = wp_remote_post($endpoint, $args);
    
    // Verificar a resposta
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        evolution_api_log("Erro ao enviar mensagem via Evolution API: $error_message", 'error');
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    evolution_api_log("Resposta recebida: $response_code - $response_body", 'debug');
    
    if ($response_code !== 200 && $response_code !== 201) {
        evolution_api_log("Erro na Evolution API: $response_code - $response_body", 'error');
        return false;
    }
    
    evolution_api_log("Mensagem enviada com sucesso para $whatsapp", 'success');
    return true;
}

// Substituir a função original pela versão com logging
function enable_evolution_api_logging() {
    // Substituir apenas se a função original existir
    if (function_exists('send_whatsapp_via_evolution_api_direct')) {
        // Remover a função atual do hook de envio
        remove_action('transition_post_status', 'send_whatsapp_on_avisos_publish_direct', 10);
        
        // Adicionar a nova função com logging
        add_action('transition_post_status', 'send_whatsapp_on_avisos_publish_with_logging', 10, 3);
    }
}
add_action('init', 'enable_evolution_api_logging');

// Versão da função de hook com logging
function send_whatsapp_on_avisos_publish_with_logging($new_status, $old_status, $post) {
    // Verificar se é o tipo de post "avisos" e se o status mudou para "publish"
    if ($post->post_type === 'avisos' && $new_status === 'publish' && $old_status !== 'publish') {
        evolution_api_log("Post ID {$post->ID} publicado. Iniciando envio de notificações.", 'info');
        
        // Executar o envio diretamente para não depender de eventos agendados
        $post = get_post($post->ID);
        
        if (!$post || $post->post_type !== 'avisos') {
            evolution_api_log("Post ID {$post->ID} não encontrado ou não é do tipo 'avisos'.", 'error');
            return;
        }
        
        $titulo = $post->post_title;
        $conteudo = wp_strip_all_tags(get_the_content(null, false, $post));
        $permalink = get_permalink($post->ID);
        
        // Obter todos os usuários corretores (autores)
        $args = array(
            'role' => 'author',
            'fields' => array('ID', 'display_name', 'user_email'),
        );
        
        $corretores = get_users($args);
        
        evolution_api_log("Encontrados " . count($corretores) . " corretores para enviar notificações.", 'info');
        
        // Se não houver corretores, encerrar a função
        if (empty($corretores)) {
            evolution_api_log('Nenhum corretor encontrado para enviar notificação.', 'warning');
            return;
        }
        
        $envios_sucesso = 0;
        $envios_falha = 0;
        
        foreach ($corretores as $corretor) {
            // Obter o número do WhatsApp do corretor (meta personalizado)
            $whatsapp = get_user_meta($corretor->ID, 'whatsapp', true);
            
            if (empty($whatsapp)) {
                evolution_api_log('Corretor ID ' . $corretor->ID . ' não possui WhatsApp cadastrado.', 'warning');
                $envios_falha++;
                continue;
            }
            
            // Formatar o WhatsApp (remover caracteres não numéricos)
            $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);
            
            // Verificar se o WhatsApp está no formato correto
            if (strlen($whatsapp) < 10) {
                evolution_api_log('Número de WhatsApp inválido para o corretor ID ' . $corretor->ID, 'warning');
                $envios_falha++;
                continue;
            }
            
            // Enviar a mensagem via Evolution API com logging
            $resultado = send_whatsapp_via_evolution_api_direct_with_logging(
                $whatsapp, $titulo, $conteudo, $permalink, $corretor->display_name
            );
            
            if ($resultado) {
                $envios_sucesso++;
            } else {
                $envios_falha++;
            }
        }
        
        evolution_api_log("Envio concluído. Sucessos: $envios_sucesso, Falhas: $envios_falha", 'info');
        
        // Marcar o post como enviado
        if ($envios_sucesso > 0) {
            update_post_meta($post->ID, '_enviado_whatsapp', true);
            update_post_meta($post->ID, '_whatsapp_stats', array(
                'successos' => $envios_sucesso,
                'falhas' => $envios_falha,
                'data_envio' => current_time('mysql')
            ));
        }
    }
}

// Adicionar página para visualizar os logs
function evolution_api_logs_menu() {
    add_submenu_page(
        'edit.php?post_type=avisos',
        'Logs do WhatsApp',
        'Logs do WhatsApp',
        'manage_options',
        'evolution-api-logs',
        'evolution_api_logs_page'
    );
}
add_action('admin_menu', 'evolution_api_logs_menu');

// Página de visualização de logs
function evolution_api_logs_page() {
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Processar alterações de configuração
    if (isset($_POST['evolution_api_settings_logs_nonce']) && wp_verify_nonce($_POST['evolution_api_settings_logs_nonce'], 'evolution_api_settings_logs')) {
        $debug_mode = isset($_POST['evolution_api_debug_mode']) ? true : false;
        update_option('evolution_api_debug_mode', $debug_mode);
        
        if (isset($_POST['clear_logs'])) {
            update_option('evolution_api_logs', array());
            echo '<div class="notice notice-success is-dismissible"><p>Logs limpos com sucesso!</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>Configurações salvas com sucesso!</p></div>';
        }
    }
    
    // Obter configurações atuais
    $debug_mode = get_option('evolution_api_debug_mode', false);
    
    // Obter logs
    $logs = get_option('evolution_api_logs', array());
    $logs = array_reverse($logs); // Mostrar mais recentes primeiro
    
    ?>
    <div class="wrap">
        <h1>Logs da Evolution API</h1>
        
        <div class="card">
            <h2>Configurações de Log</h2>
            <form method="post" action="">
                <?php wp_nonce_field('evolution_api_settings_logs', 'evolution_api_settings_logs_nonce'); ?>
                <p>
                    <label>
                        <input type="checkbox" name="evolution_api_debug_mode" value="1" <?php checked($debug_mode, true); ?> />
                        Ativar modo de depuração (logs detalhados)
                    </label>
                </p>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Salvar Configurações">
                    <input type="submit" name="clear_logs" class="button button-secondary" value="Limpar Logs">
                </p>
            </form>
        </div>
        
        <h2>Registros de Log</h2>
        
        <?php if (empty($logs)): ?>
            <div class="notice notice-info">
                <p>Nenhum log registrado ainda.</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Tipo</th>
                        <th>Mensagem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html(date('Y-m-d H:i:s', $log['timestamp'])); ?></td>
                            <td>
                                <?php 
                                $type_colors = array(
                                    'info' => '#0073aa',
                                    'debug' => '#666',
                                    'warning' => '#ffba00',
                                    'error' => '#dc3232',
                                    'success' => '#46b450'
                                );
                                $color = isset($type_colors[$log['type']]) ? $type_colors[$log['type']] : '';
                                ?>
                                <span style="color: <?php echo esc_attr($color); ?>; font-weight: bold;">
                                    <?php echo esc_html(strtoupper($log['type'])); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($log['message']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * FIM DO SISTEMA DE LOGS PARA EVOLUTION API
 */

/**
 * PÁGINA DE AJUDA PARA EVOLUTION API
 */

// Adicionar página de ajuda para o CPT Avisos
function evolution_api_help_menu() {
    add_submenu_page(
        'edit.php?post_type=avisos',
        'Ajuda WhatsApp',
        'Ajuda WhatsApp',
        'edit_posts',
        'evolution-api-help',
        'evolution_api_help_page'
    );
}
add_action('admin_menu', 'evolution_api_help_menu');

// Página de ajuda
function evolution_api_help_page() {
    ?>
    <div class="wrap">
        <h1>Ajuda - Sistema de Avisos via WhatsApp</h1>
        
        <div class="card">
            <h2>Sobre o Sistema de Avisos</h2>
            <p>
                Este sistema permite enviar automaticamente mensagens de WhatsApp para todos os corretores (usuários com papel de autor) 
                quando um novo aviso é publicado. 
            </p>
        </div>
        
        <div class="card">
            <h2>Como usar</h2>
            <ol>
                <li>
                    <strong>Configuração Inicial</strong>
                    <ul>
                        <li>Acesse <a href="<?php echo admin_url('options-general.php?page=evolution-api-settings'); ?>">Configurações > Evolution API</a></li>
                        <li>Preencha os dados da API: URL, Nome da Instância e API Key</li>
                        <li>Certifique-se de que os corretores têm seus números de WhatsApp cadastrados no perfil</li>
                    </ul>
                </li>
                <li>
                    <strong>Criação de Avisos</strong>
                    <ul>
                        <li>Acesse <a href="<?php echo admin_url('post-new.php?post_type=avisos'); ?>">Avisos > Adicionar Novo</a></li>
                        <li>Digite um título informativo (será o título da mensagem)</li>
                        <li>Escreva o conteúdo do aviso</li>
                        <li>Selecione para quais corretores o aviso será enviado</li>
                        <li>Clique em "Publicar"</li>
                    </ul>
                </li>
                <li>
                    <strong>Monitoramento</strong>
                    <ul>
                        <li>Acompanhe o status de envio na coluna "Status de Envio" na <a href="<?php echo admin_url('edit.php?post_type=avisos'); ?>">listagem de avisos</a></li>
                        <li>Consulte os logs detalhados em <a href="<?php echo admin_url('edit.php?post_type=avisos&page=evolution-api-logs'); ?>">Avisos > Logs do WhatsApp</a></li>
                    </ul>
                </li>
            </ol>
        </div>
        
        <div class="card">
            <h2>Webhook (Status de entrega e leitura)</h2>
            <p>
                Para receber confirmações de entrega e leitura das mensagens, configure o seguinte webhook 
                no painel da Evolution API:
            </p>
            <div class="code-block" style="background: #f1f1f1; padding: 15px; border-radius: 4px;">
                <code><?php echo site_url('/wp-json/evolution-api/v1/webhook'); ?></code>
                <button class="button button-small copy-button" onclick="copyToClipboard('<?php echo esc_js(site_url('/wp-json/evolution-api/v1/webhook')); ?>')">Copiar</button>
            </div>
            <script>
                function copyToClipboard(text) {
                    var tempInput = document.createElement('input');
                    tempInput.value = text;
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempInput);
                    
                    alert('URL copiada para a área de transferência!');
                }
            </script>
        </div>
        
        <div class="card">
            <h2>Formato da Mensagem</h2>
            <p>Cada mensagem enviada terá o seguinte formato:</p>
            <pre style="background: #f1f1f1; padding: 15px; border-radius: 4px;">
Olá, [Nome do Corretor]!

*NOVO AVISO IMPORTANTE*

*[Título do Aviso]*

[Conteúdo do Aviso]

Saiba mais: [Link para o Aviso]
            </pre>
        </div>
        
        <div class="card">
            <h2>Solução de problemas</h2>
            <p>Se estiver enfrentando problemas com o sistema de avisos:</p>
            <ol>
                <li>Verifique se todas as configurações da API estão corretas</li>
                <li>Verifique se os corretores têm números de WhatsApp cadastrados em seus perfis</li>
                <li>Consulte os logs para identificar erros específicos</li>
                <li>Certifique-se de que o servidor da Evolution API está online e funcionando</li>
                <li>Verifique se a instância do WhatsApp está conectada (QR Code escaneado)</li>
                <li>Caso precise, use o botão "Reenviar" na listagem de avisos para tentar novamente</li>
            </ol>
        </div>
    </div>
    <style>
        .wrap .card {
            max-width: 100%;
            margin-top: 20px;
            margin-bottom: 20px;
            padding: 20px;
        }
        .code-block {
            position: relative;
        }
        .copy-button {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
    <?php
}

/**
 * FIM DA PÁGINA DE AJUDA PARA EVOLUTION API
 */

/**
 * CAMPOS DE PERFIL PARA WHATSAPP
 */

// Adicionar campo de WhatsApp no perfil do usuário
function add_whatsapp_profile_fields($user) {
    ?>
    <h3>Informações para Notificações via WhatsApp</h3>
    <table class="form-table">
        <tr>
            <th><label for="whatsapp_number">Número do WhatsApp</label></th>
            <td>
                <input type="text" name="whatsapp_number" id="whatsapp_number" 
                       value="<?php echo esc_attr(get_user_meta($user->ID, 'whatsapp_number', true)); ?>" 
                       class="regular-text" />
                <p class="description">Informe seu número com código do país e DDD, sem espaços ou caracteres especiais (ex: 5511987654321)</p>
            </td>
        </tr>
        <tr>
            <th><label for="receive_notifications">Notificações</label></th>
            <td>
                <label for="receive_notifications">
                    <input type="checkbox" name="receive_notifications" id="receive_notifications" 
                           value="1" <?php checked(get_user_meta($user->ID, 'receive_notifications', true), '1'); ?> />
                    Desejo receber notificações via WhatsApp
                </label>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'add_whatsapp_profile_fields');
add_action('edit_user_profile', 'add_whatsapp_profile_fields');

// Salvar campo de WhatsApp no perfil do usuário
function save_whatsapp_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (isset($_POST['whatsapp_number'])) {
        // Sanitiza e formata o número
        $whatsapp_number = preg_replace('/[^0-9]/', '', sanitize_text_field($_POST['whatsapp_number']));
        update_user_meta($user_id, 'whatsapp_number', $whatsapp_number);
    }

    if (isset($_POST['receive_notifications'])) {
        update_user_meta($user_id, 'receive_notifications', '1');
    } else {
        update_user_meta($user_id, 'receive_notifications', '0');
    }
}
add_action('personal_options_update', 'save_whatsapp_profile_fields');
add_action('edit_user_profile_update', 'save_whatsapp_profile_fields');

// Adicionar coluna de WhatsApp na lista de usuários (admin)
function add_whatsapp_column($columns) {
    $columns['whatsapp'] = 'WhatsApp';
    return $columns;
}
add_filter('manage_users_columns', 'add_whatsapp_column');

// Mostrar o número de WhatsApp na lista de usuários
function show_whatsapp_column_content($value, $column_name, $user_id) {
    if ('whatsapp' === $column_name) {
        $whatsapp = get_user_meta($user_id, 'whatsapp_number', true);
        $receive = get_user_meta($user_id, 'receive_notifications', true);
        
        if (!empty($whatsapp)) {
            $status = $receive == '1' ? '<span style="color:green;">✓</span>' : '<span style="color:red;">✗</span>';
            return esc_html($whatsapp) . ' ' . $status;
        } else {
            return '<span style="color:red;">Não cadastrado</span>';
        }
    }
    return $value;
}
add_action('manage_users_custom_column', 'show_whatsapp_column_content', 10, 3);

/**
 * FIM DOS CAMPOS DE PERFIL PARA WHATSAPP
 */

/**
 * CRIAÇÃO DE TABELAS DO SISTEMA WHATSAPP
 */

// Função para criar a tabela de mensagens do WhatsApp quando o tema for ativado
function create_whatsapp_messages_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . 'whatsapp_messages';

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20),
        whatsapp_number varchar(20) NOT NULL,
        message_id varchar(255),
        status varchar(50) DEFAULT 'pending',
        event_type varchar(50),
        user_id bigint(20),
        user_name varchar(100),
        timestamp datetime DEFAULT CURRENT_TIMESTAMP,
        additional_data text,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY whatsapp_number (whatsapp_number),
        KEY message_id (message_id),
        KEY status (status)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Chamada na ativação do tema
// Precisamos usar after_setup_theme porque o tema já está ativo
add_action('after_setup_theme', 'create_whatsapp_messages_table');

/**
 * FIM CRIAÇÃO DE TABELAS DO SISTEMA WHATSAPP
 */

/**
 * MENU DE TESTE DO WHATSAPP
 */

// Adicionar item de menu para a página de teste do WhatsApp
function add_test_whatsapp_menu() {
    add_submenu_page(
        'edit.php?post_type=avisos',  // Adicionar como submenu de Avisos
        'Teste de WhatsApp',          // Título da página
        'Teste de WhatsApp',          // Texto do menu
        'edit_posts',                 // Capacidade necessária
        'test-whatsapp',              // Slug do menu
        'display_test_whatsapp_page'  // Função de callback
    );
}
add_action('admin_menu', 'add_test_whatsapp_menu');

// Função de callback para exibir a página de teste
function display_test_whatsapp_page() {
    // Incluir o arquivo de teste
    include(get_template_directory() . '/test-whatsapp.php');
}

/**
 * FIM DO MENU DE TESTE DO WHATSAPP
 */
