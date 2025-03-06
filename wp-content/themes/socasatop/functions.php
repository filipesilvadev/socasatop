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

wp_enqueue_script('jquery-mask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js', array('jquery'), '1.14.16', true);

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
