<?php

function child_enqueue__parent_scripts()
{
    wp_enqueue_style('parent', get_template_directory_uri() . '/style.css');
}
add_action('wp_enqueue_scripts', 'child_enqueue__parent_scripts');

function scripts_so_casa_top()
{
    wp_register_style('so-casa-top', get_stylesheet_directory_uri() . '/assets/socasatop.css', array(), '1.0.1', 'all');
    wp_enqueue_style('so-casa-top');

    wp_register_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '2.4.1', 'all');
    wp_enqueue_style('select2');

    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '2.4.1', true);
    wp_enqueue_script('so-casa-top', get_stylesheet_directory_uri() . '/assets/socasatop.js', array('jquery'), '1.0.1', true);
    wp_enqueue_script('js-forms', get_stylesheet_directory_uri() . '/assets/js-forms.js', array('jquery', 'so-casa-top'), '1.0.1', true);

    $options = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_nonce')
    ];

    // Adicionar dados do usuário atual se estiver logado
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $options['user_id'] = $current_user->ID;
        $options['user_display_name'] = $current_user->display_name;
        $options['user_firstname'] = $current_user->user_firstname;
        $options['user_firstname2'] = $current_user->first_name;
        $options['user_login'] = $current_user->user_login;
        $options['user_email'] = $current_user->user_email;
        $options['user_phone'] = get_user_meta($current_user->ID, 'phone', true);
        $options['user_whatsapp'] = get_user_meta($current_user->ID, 'whatsapp', true);
        $options['user_telefone'] = get_user_meta($current_user->ID, 'telefone', true);
        $options['is_logged_in'] = true;
    } else {
        $options['is_logged_in'] = false;
    }

    // Disponibilizar os dados para os scripts
    wp_localize_script('so-casa-top', 'site', $options);
    wp_localize_script('js-forms', 'site', $options);  // Garantir que o js-forms tenha acesso direto
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

function enqueue_font_awesome()
{
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');
}
add_action('wp_enqueue_scripts', 'enqueue_font_awesome');

/**
 * Função para carregar script que suprime erros de webhook do Elementor
 */
function enqueue_elementor_form_fix() {
    wp_enqueue_script('elementor-form-error-fix', get_stylesheet_directory_uri() . '/assets/elementor-form-fix.js', array('jquery'), '1.0.1', true);
    wp_enqueue_style('elementor-form-fix-style', get_stylesheet_directory_uri() . '/assets/elementor-form-fix.css', array(), '1.0.1', 'all');
}
add_action('wp_enqueue_scripts', 'enqueue_elementor_form_fix');

/**
 * Filtro para modificar a resposta das ações de formulário do Elementor
 * Isso garante que mesmo que haja um erro no webhook, a resposta para o usuário final seja de sucesso
 */
function filter_elementor_form_responses($response, $form_id, $settings) {
    // Se houver um erro mas o webhook foi chamado corretamente, converte para sucesso
    if (isset($response['success']) && !$response['success'] && !empty($response['error'])) {
        // Verificar se o erro está relacionado a webhook ou integração de API
        if (strpos(strtolower($response['error']), 'webhook') !== false || 
            strpos(strtolower($response['error']), 'api') !== false ||
            strpos(strtolower($response['error']), 'mercado pago') !== false ||
            strpos(strtolower($response['error']), 'server') !== false ||
            strpos(strtolower($response['error']), 'parse') !== false) {
            
            // Identifica se o formulário pertence ao popup de assessoria
            $is_assessoria_form = false;
            if (isset($settings['id']) && $settings['id'] == '14752') {
                $is_assessoria_form = true;
            }
            
            // Substitui a resposta com sucesso
            return [
                'success' => true,
                'message' => $is_assessoria_form ? 'Entramos em contato através do seu WhatsApp' : 'Formulário enviado com sucesso!',
                'data' => $response['data'] ?? []
            ];
        }
    }
    
    return $response;
}
add_filter('elementor_pro/forms/actions/webhook/response', 'filter_elementor_form_responses', 10, 3);
add_filter('elementor_pro/forms/actions/remote_request/response', 'filter_elementor_form_responses', 10, 3);

/**
 * Registra logs de erros dos formulários Elementor para debugging
 * Isso ajuda a diagnosticar problemas sem mostrar erros para os usuários
 */
function log_elementor_form_errors($ajax_handler) {
    // Esta função é muito menos intrusiva - apenas registra os erros sem modificar o comportamento
    $form_name = $ajax_handler->get_form_settings('form_name');
    $form_id = $ajax_handler->get_form_settings('id');
    $errors = $ajax_handler->get_errors();
    
    if (!empty($errors)) {
        // Registra os erros em um arquivo de log
        error_log(sprintf(
            '[Elementor Form Error] Form Name: %s, Form ID: %s, Errors: %s',
            $form_name,
            $form_id,
            json_encode($errors)
        ));
    }
}
add_action('elementor_pro/forms/validation', 'log_elementor_form_errors', 999);

include_once "inc/custom/immobile/post.php";
include_once "inc/custom/lead/post.php";
include_once "inc/custom/broker/post.php";
include_once "inc/custom/location/taxonomy.php";
include_once "inc/custom/view-immobile/post.php";
include_once "inc/custom/search-ai/post.php";

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
include_once "inc/custom/broker/shortcodes/painel.php";
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

// Função para carregar o script jquery-mask no hook correto
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
    // Garantir que a biblioteca de mídia esteja disponível
    wp_enqueue_media();
    
    // Adicionar jQuery UI Sortable se ainda não estiver carregado
    wp_enqueue_script('jquery-ui-sortable');
    
    // Get existing gallery images
    $gallery_images = get_post_meta($post->ID, 'immobile_gallery', true);
    $gallery_videos = get_post_meta($post->ID, 'immobile_videos', true);
    
    // Adicionar nonce para segurança
    wp_nonce_field('immobile_metabox_nonce', 'immobile_metabox_nonce');
    
    // Display image gallery field
    ?>
    <div style="margin-bottom: 20px;">
        <h4>Galeria de Imagens</h4>
        <input type="hidden" id="immobile_gallery" name="immobile_gallery" value="<?php echo esc_attr($gallery_images); ?>">
        <div id="gallery-container" class="gallery-container" style="display: flex; flex-wrap: wrap; margin-top: 10px;">
            <?php
            if (!empty($gallery_images)) {
                $image_ids = explode(',', $gallery_images);
                foreach ($image_ids as $image_id) {
                    if (!empty($image_id)) {
                        $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                        echo '<div class="gallery-item" data-id="' . esc_attr($image_id) . '" style="position: relative; margin: 5px; cursor: move;">';
                        echo '<img src="' . esc_url($image_url) . '" width="100" height="100" style="object-fit: cover;">';
                        echo '<span class="remove-image" style="position: absolute; top: 0; right: 0; background: red; color: white; width: 20px; height: 20px; text-align: center; line-height: 20px; cursor: pointer;">×</span>';
                        echo '<label class="make-featured" style="position: absolute; bottom: 0; left: 0; background: rgba(0,0,0,0.5); color: white; padding: 2px 5px; font-size: 10px; cursor: pointer; opacity: 0; transition: opacity 0.3s ease;">';
                        echo '<input type="radio" name="featured_image" value="' . esc_attr($image_id) . '" ' . checked(strpos($gallery_images, $image_id.',') === 0, true, false) . '> Capa';
                        echo '</label>';
                        echo '</div>';
                    }
                }
            }
            ?>
        </div>
        <button type="button" id="immobile-upload-images" class="button" style="margin-top: 10px;">Adicionar Imagens</button>
        <p class="description">Arraste as imagens para reordenar. Selecione uma imagem como "Capa" para defini-la como a imagem principal do imóvel.</p>
    </div>

    <div>
        <h4>Vídeos do Imóvel</h4>
        <input type="hidden" id="immobile_videos" name="immobile_videos" value="<?php echo esc_attr($gallery_videos); ?>">
        <div id="videos-container" class="videos-container" style="display: flex; flex-wrap: wrap; margin-top: 10px;">
            <?php
            if (!empty($gallery_videos)) {
                $video_ids = explode(',', $gallery_videos);
                foreach ($video_ids as $video_id) {
                    if (!empty($video_id)) {
                        $video_url = wp_get_attachment_url($video_id);
                        echo '<div class="video-item" data-id="' . esc_attr($video_id) . '" style="position: relative; margin: 5px; cursor: move;">';
                        echo '<video width="150" height="100" controls style="object-fit: cover;">';
                        echo '<source src="' . esc_url($video_url) . '" type="video/mp4">';
                        echo 'Seu navegador não suporta o elemento de vídeo.';
                        echo '</video>';
                        echo '<span class="remove-video" style="position: absolute; top: 0; right: 0; background: red; color: white; width: 20px; height: 20px; text-align: center; line-height: 20px; cursor: pointer;">×</span>';
                        echo '</div>';
                    }
                }
            }
            ?>
        </div>
        <button type="button" id="immobile-upload-videos" class="button" style="margin-top: 10px;">Adicionar Vídeos</button>
        <p class="description">Faça upload de vídeos nos formatos MP4, WebM ou OGG.</p>
    </div>

    <script type="text/javascript">
    jQuery(function($) {
        // Hover effect for "Capa" label
        $(document).on('mouseenter', '.gallery-item', function() {
            $(this).find('.make-featured').css('opacity', '1');
        }).on('mouseleave', '.gallery-item', function() {
            $(this).find('.make-featured').css('opacity', '0');
        });
        
        // Sortable for image gallery
        if ($.fn.sortable) {
            $('#gallery-container').sortable({
                update: function(event, ui) {
                    updateGalleryOrder();
                }
            });
            
            $('#videos-container').sortable({
                update: function(event, ui) {
                    updateVideoOrder();
                }
            });
        } else {
            console.error('jQuery UI Sortable não está disponível');
        }
        
        // Function to update gallery order in hidden field
        function updateGalleryOrder() {
            var currentIds = [];
            $('#gallery-container .gallery-item').each(function() {
                currentIds.push($(this).data('id'));
            });
            $('#immobile_gallery').val(currentIds.join(','));
        }
        
        // Function to update video order in hidden field
        function updateVideoOrder() {
            var currentIds = [];
            $('#videos-container .video-item').each(function() {
                currentIds.push($(this).data('id'));
            });
            $('#immobile_videos').val(currentIds.join(','));
        }
        
        // Image uploader
        $('#immobile-upload-images').on('click', function(e) {
            e.preventDefault();
            console.log('Botão de imagens clicado'); // Debug
            
            if (typeof wp !== 'undefined' && wp.media && wp.media.editor) {
                var frame = wp.media({
                    title: 'Selecionar Imagens',
                    button: { text: 'Usar essas imagens' },
                    multiple: true,
                    library: { type: 'image' }
                });
                
                frame.on('select', function() {
                    var selection = frame.state().get('selection');
                    var currentIds = $('#immobile_gallery').val() ? $('#immobile_gallery').val().split(',') : [];
                    
                    selection.map(function(attachment) {
                        attachment = attachment.toJSON();
                        console.log('Imagem selecionada:', attachment); // Debug
                        
                        if ($.inArray(attachment.id.toString(), currentIds) === -1) {
                            currentIds.push(attachment.id);
                            
                            $('#gallery-container').append(
                                '<div class="gallery-item" data-id="' + attachment.id + '" style="position: relative; margin: 5px; cursor: move;">' +
                                '<img src="' + (attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url) + '" width="100" height="100" style="object-fit: cover;">' +
                                '<span class="remove-image" style="position: absolute; top: 0; right: 0; background: red; color: white; width: 20px; height: 20px; text-align: center; line-height: 20px; cursor: pointer;">×</span>' +
                                '<label class="make-featured" style="position: absolute; bottom: 0; left: 0; background: rgba(0,0,0,0.5); color: white; padding: 2px 5px; font-size: 10px; cursor: pointer; opacity: 0; transition: opacity 0.3s ease;">' +
                                '<input type="radio" name="featured_image" value="' + attachment.id + '"> Capa' +
                                '</label>' +
                                '</div>'
                            );
                        }
                    });
                    
                    $('#immobile_gallery').val(currentIds.join(','));
                });
                
                frame.open();
            } else {
                console.error('Media library not available');
            }
        });
        
        // Video uploader
        $('#immobile-upload-videos').on('click', function(e) {
            e.preventDefault();
            console.log('Botão de vídeos clicado'); // Debug
            
            if (typeof wp !== 'undefined' && wp.media && wp.media.editor) {
                var frame = wp.media({
                    title: 'Selecionar Vídeos',
                    button: { text: 'Usar esses vídeos' },
                    multiple: true,
                    library: { type: 'video' }
                });
                
                frame.on('select', function() {
                    var selection = frame.state().get('selection');
                    var currentIds = $('#immobile_videos').val() ? $('#immobile_videos').val().split(',') : [];
                    
                    selection.map(function(attachment) {
                        attachment = attachment.toJSON();
                        console.log('Vídeo selecionado:', attachment); // Debug
                        
                        if ($.inArray(attachment.id.toString(), currentIds) === -1) {
                            currentIds.push(attachment.id);
                            
                            $('#videos-container').append(
                                '<div class="video-item" data-id="' + attachment.id + '" style="position: relative; margin: 5px; cursor: move;">' +
                                '<video width="150" height="100" controls style="object-fit: cover;">' +
                                '<source src="' + attachment.url + '" type="video/mp4">' +
                                'Seu navegador não suporta o elemento de vídeo.' +
                                '</video>' +
                                '<span class="remove-video" style="position: absolute; top: 0; right: 0; background: red; color: white; width: 20px; height: 20px; text-align: center; line-height: 20px; cursor: pointer;">×</span>' +
                                '</div>'
                            );
                        }
                    });
                    
                    $('#immobile_videos').val(currentIds.join(','));
                });
                
                frame.open();
            } else {
                console.error('Media library not available');
            }
        });
        
        // Remove image
        $(document).on('click', '.remove-image', function() {
            var item = $(this).parent();
            var id = item.data('id');
            var currentIds = $('#immobile_gallery').val().split(',');
            var newIds = currentIds.filter(function(value) { return value != id; });
            
            $('#immobile_gallery').val(newIds.join(','));
            item.remove();
        });
        
        // Remove video
        $(document).on('click', '.remove-video', function() {
            var item = $(this).parent();
            var id = item.data('id');
            var currentIds = $('#immobile_videos').val().split(',');
            var newIds = currentIds.filter(function(value) { return value != id; });
            
            $('#immobile_videos').val(newIds.join(','));
            item.remove();
        });
        
        // Handle featured image selection
        $(document).on('change', 'input[name="featured_image"]', function() {
            var featuredId = $(this).val();
            var currentIds = $('#immobile_gallery').val().split(',');
            
            // Remove the featured ID from the array
            currentIds = currentIds.filter(function(value) { return value != featuredId; });
            
            // Add the featured ID to the beginning
            currentIds.unshift(featuredId);
            
            // Update the hidden field
            $('#immobile_gallery').val(currentIds.join(','));
        });
    });
    </script>
    <?php
}

function save_media_gallery_metabox($post_id) {
    // Check if our nonce is set.
    if (!isset($_POST['immobile_metabox_nonce'])) {
        return;
    }

    // Verify that the nonce is valid.
    if (!wp_verify_nonce($_POST['immobile_metabox_nonce'], 'immobile_metabox_nonce')) {
        return;
    }

    // If this is an autosave, we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Save the gallery images
    if (isset($_POST['immobile_gallery'])) {
        update_post_meta($post_id, 'immobile_gallery', sanitize_text_field($_POST['immobile_gallery']));
    }

    // Save the videos
    if (isset($_POST['immobile_videos'])) {
        update_post_meta($post_id, 'immobile_videos', sanitize_text_field($_POST['immobile_videos']));
    }
}
add_action('save_post_immobile', 'save_media_gallery_metabox');

include_once "inc/custom/immobile/broker-select-metabox.php";
include_once "inc/custom/immobile/metabox.php";
include_once "inc/custom/search-ai/sponsored-carousel.php";
include_once "inc/custom/user-permissions/author-restrictions.php";
include_once "inc/custom/immobile/admin-approval.php";

// Shortcode para atualizar a localidade de ARNIQUEIRAS para ARNIQUEIRA
function update_arniqueira_location_shortcode() {
    if (!current_user_can('manage_options')) {
        return '<p>Acesso negado. Você precisa ser administrador para executar esta ação.</p>';
    }
    
    ob_start();
    
    // Procurar o termo ARNIQUEIRAS
    $term = get_term_by('name', 'ARNIQUEIRAS', 'locations');
    if ($term) {
        // Atualizar o nome para ARNIQUEIRA
        $result = wp_update_term($term->term_id, 'locations', array(
            'name' => 'ARNIQUEIRA'
        ));
        
        if (!is_wp_error($result)) {
            echo "<p>Localidade atualizada com sucesso de 'ARNIQUEIRAS' para 'ARNIQUEIRA'.</p>";
        } else {
            echo "<p>Erro ao atualizar a localidade: " . $result->get_error_message() . "</p>";
        }
    } else {
        // Procurar com variações de capitalização
        $term = get_term_by('name', 'Arniqueiras', 'locations');
        if ($term) {
            $result = wp_update_term($term->term_id, 'locations', array(
                'name' => 'ARNIQUEIRA'
            ));
            
            if (!is_wp_error($result)) {
                echo "<p>Localidade atualizada com sucesso de 'Arniqueiras' para 'ARNIQUEIRA'.</p>";
            } else {
                echo "<p>Erro ao atualizar a localidade: " . $result->get_error_message() . "</p>";
            }
        } else {
            echo "<p>Termo 'ARNIQUEIRAS' não encontrado na taxonomia 'locations'.</p>";
        }
    }
    
    return ob_get_clean();
}
add_shortcode('update_arniqueira_location', 'update_arniqueira_location_shortcode');

// Traduzir o texto "Lost your password?" para "Esqueci minha senha?"
function translate_lost_password_text($translated_text, $text, $domain) {
    if ($text === 'Lost your password?') {
        return 'Esqueci minha senha?';
    }
    return $translated_text;
}
add_filter('gettext', 'translate_lost_password_text', 20, 3);

// Carregar os shortcodes personalizados
function load_custom_shortcodes() {
    // Verificar se os diretórios necessários existem
    $dirs = array(
        get_stylesheet_directory() . '/inc/custom/broker/assets',
        get_stylesheet_directory() . '/inc/custom/broker/assets/js',
    );
    
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }
    
    // Carregar o sistema de produtos primeiro
    include_once(get_stylesheet_directory() . '/inc/custom/broker/payment-product.php');
    
    // Carregar o sistema de pagamento unificado
    include_once(get_stylesheet_directory() . '/inc/custom/broker/payment-unified.php');
    
    // Carregar o arquivo de configurações de pagamento
    include_once(get_stylesheet_directory() . '/inc/custom/broker/payment-settings.php');
    
    // Carregar o arquivo de shortcodes
    include_once(get_stylesheet_directory() . '/inc/custom/broker/shortcodes.php');
    
    // Carregar o arquivo de integração com o Mercado Pago
    include_once(get_stylesheet_directory() . '/inc/custom/immobile/mercadopago.php');
}
add_action('init', 'load_custom_shortcodes', 5);

/**
 * Função para carregar os scripts e estilos dos formulários personalizados
 */
function enqueue_custom_forms_assets() {
    wp_enqueue_style('forms-style', get_stylesheet_directory_uri() . '/assets/forms-style.css', array(), '1.0.0', 'all');
    wp_enqueue_script('js-forms', get_stylesheet_directory_uri() . '/assets/js-forms.js', array('jquery'), '1.0.0', true);
    
    // Passar dados do usuário logado para o script
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $user_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ajax_nonce'),
            'user_display_name' => $current_user->display_name,
            'user_firstname' => $current_user->first_name,
            'user_email' => $current_user->user_email,
            'user_phone' => get_user_meta($current_user->ID, 'phone', true),
            'user_whatsapp' => get_user_meta($current_user->ID, 'whatsapp', true)
        );
        
        // Localizar diretamente o script js-forms
        wp_localize_script('js-forms', 'site', $user_data);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_custom_forms_assets', 20); // Prioridade mais alta para garantir que seja executado após scripts_so_casa_top 

/**
 * Função AJAX para obter dados do corretor
 */
function get_corretor_data() {
    check_ajax_referer('ajax_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não está logado');
        return;
    }
    
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    
    // Obter dados do telefone e whatsapp
    $phone = get_user_meta($user_id, 'phone', true);
    $whatsapp = get_user_meta($user_id, 'whatsapp', true);
    
    // Usar o primeiro número de telefone válido disponível
    $telefone = '';
    if (!empty($phone)) {
        $telefone = preg_replace('/[^0-9]/', '', $phone);
    } else if (!empty($whatsapp)) {
        $telefone = preg_replace('/[^0-9]/', '', $whatsapp);
    }
    
    // Dados para retornar
    $dados_corretor = array(
        'nome' => $user->display_name,
        'email' => $user->user_email,
        'telefone' => $telefone,
        'user_id' => $user_id,
        // Incluir todos os dados para debug
        'raw_phone' => $phone,
        'raw_whatsapp' => $whatsapp,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name
    );
    
    wp_send_json_success($dados_corretor);
}
add_action('wp_ajax_get_corretor_data', 'get_corretor_data');

/**
 * Garantir que scripts e estilos de imóveis sejam carregados em todas as páginas relacionadas
 */
function load_immobile_assets() {
    // Verificar se estamos em qualquer página relacionada a imóveis
    if (is_singular('immobile') || is_post_type_archive('immobile') || 
        is_singular('listaimoveis') || is_tax('location') ||
        strpos($_SERVER['REQUEST_URI'], '/imovel/') !== false || 
        strpos($_SERVER['REQUEST_URI'], '/listaimoveis/') !== false) {
        
        // Bibliotecas essenciais
        wp_enqueue_style('swiper', 'https://unpkg.com/swiper/swiper-bundle.min.css');
        wp_enqueue_script('swiper', 'https://unpkg.com/swiper/swiper-bundle.min.js', array('jquery'), null, true);
        
        // Fontawesome para ícones
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
        
        // Nossos arquivos específicos
        wp_enqueue_style('immobile-styles', get_stylesheet_directory_uri() . '/assets/immobile.css', array(), '1.0.3');
        wp_enqueue_script('immobile-scripts', get_stylesheet_directory_uri() . '/assets/immobile.js', array('jquery', 'swiper'), '1.0.3', true);
    }
}
add_action('wp_enqueue_scripts', 'load_immobile_assets', 20);

/**
 * Função para depuração de templates usados
 */
function debug_template_used() {
    if (current_user_can('administrator') && isset($_GET['debug_template'])) {
        global $template;
        echo '<div style="position: fixed; bottom: 10px; right: 10px; z-index: 9999; background: rgba(0,0,0,0.8); color: #fff; padding: 10px; border-radius: 5px; font-family: monospace;">
            Template: ' . str_replace(ABSPATH, '', $template) . '
        </div>';
    }
}
add_action('wp_footer', 'debug_template_used');

/**
 * Registrar locais de template do Elementor Theme Builder
 */
function register_elementor_locations($elementor_theme_manager) {
    $elementor_theme_manager->register_all_core_location();
    $elementor_theme_manager->register_location('archive');
}
add_action('elementor/theme/register_locations', 'register_elementor_locations');

/**
 * Adicionar suporte ao Elementor para todos os tipos de post personalizados
 */
function add_elementor_support_for_all_cpts() {
    $post_types = ['immobile', 'imovel', 'listaimoveis', 'lead'];
    
    foreach ($post_types as $post_type) {
        if (post_type_exists($post_type)) {
            add_post_type_support($post_type, 'elementor');
        }
    }
}
add_action('init', 'add_elementor_support_for_all_cpts', 20);
