<?php
function immobile_post()
{
    $labels = array(
        'name' => 'Imóveis',
        'singular_name' => 'Imóvel',
        'menu_name' => 'Imóveis',
        'add_new' => 'Adicionar Novo',
        'add_new_item' => 'Adicionar Novo Imóvel',
        'edit_item' => 'Editar Imóvel',
        'new_item' => 'Novo Imóvel',
        'view_item' => 'Ver Imóvel',
        'search_items' => 'Buscar Imóveis',
        'not_found' => 'Nenhum imóvel encontrado',
        'not_found_in_trash' => 'Nenhum imóvel encontrado na lixeira'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'imovel'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-admin-home',
        'supports' => array('title')
    );

    register_post_type('immobile', $args);
}
add_action('init', 'immobile_post');

function display_form_immobile()
{
    ob_start();
    require_once(__DIR__ . "/form.php");
    $content = ob_get_clean();
    return $content;
}
add_shortcode('form_immobile', 'display_form_immobile');

function display_edit_form_immobile()
{
    ob_start();
    require_once(__DIR__ . "/edit-form.php");
    $content = ob_get_clean();
    return $content;
}
add_shortcode('edit_form_immobile', 'display_edit_form_immobile');

function display_immobile_template() {
  global $wpdb;
  
  $current_post_id = get_the_ID();
  
  if (!$current_post_id || get_post_type($current_post_id) !== 'immobile') {
      return '';
  }
  
  $gallery = get_post_meta($current_post_id, 'immobile_gallery', true);
  $gallery_ids = $gallery ? explode(',', $gallery) : [];
  
  $videos = get_post_meta($current_post_id, 'immobile_videos', true);
  $video_urls = $videos ? explode("\n", $videos) : [];
  
  $broker_immobile_table = $wpdb->prefix . 'broker_immobile';
  $brokers = $wpdb->get_results($wpdb->prepare(
      "SELECT DISTINCT u.*, bi.is_sponsor 
       FROM {$wpdb->users} u 
       JOIN {$broker_immobile_table} bi ON u.ID = bi.broker_id 
       WHERE bi.immobile_id = %d 
       GROUP BY u.ID",
      $current_post_id
  ));

  ob_start();
  ?>
  <div class="immobile-content">
      <div class="content-main">
      <?php
$current_post_id = get_the_ID();
$location = get_post_meta($current_post_id, 'location', true);
$amount = get_post_meta($current_post_id, 'amount', true);
$disable_social_sharing = get_post_meta($current_post_id, 'disable_social_sharing', true);
?>

<?php if ($disable_social_sharing == '1' && current_user_can('administrator')): ?>
<div class="social-sharing-disabled-alert">
    <strong>ATENÇÃO:</strong> Este imóvel está desautorizado para publicação nas redes sociais.
</div>
<?php endif; ?>

<div class="immobile-header">
    <h1><?php echo esc_html($location); ?></h1>
    <p class="price">R$ <?php echo number_format($amount, 2, ',', '.'); ?></p>
</div>
          <div class="media-wrapper">
            <div class="media-tabs">
                <button class="media-tab active" data-tab="photos">FOTOS</button>
                <button class="media-tab" data-tab="videos">VÍDEOS</button>
            </div>
            <div class="media-content">
                <div class="media-pane active" id="photos">
                    <div class="swiper gallery-slider">
                        <div class="swiper-wrapper">
                            <?php foreach ($gallery_ids as $image_id): 
                                $image_url = wp_get_attachment_image_url($image_id, 'full');
                                if ($image_url):
                            ?>
                                <div class="swiper-slide">
                                    <img src="<?php echo esc_url($image_url); ?>" alt="Imagem do imóvel">
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                </div>

                <div class="media-pane" id="videos">
                    <div class="swiper video-slider">
                        <div class="swiper-wrapper">
                            <?php foreach ($video_urls as $video_url): 
                                $video_id = '';
                                if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video_url, $matches)) {
                                    $video_id = $matches[1];
                                }
                                if ($video_id):
                            ?>
                                <div class="swiper-slide">
                                    <div class="video-container">
                                        <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>" 
                                                frameborder="0" 
                                                allowfullscreen></iframe>
                                    </div>
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                </div>
            </div>
          </div>
          <div class="content-wrapper">
            <div class="content-tabs">
                <div class="tab-navigation">
                    <button class="content-tab active" data-content="description">DESCRIÇÃO</button>
                    <button class="content-tab" data-content="specs">ESPECÍFICAÇÕES</button>
                </div>

                <div class="tab-content">
                    <div class="content-panel active" id="description">
                        <?php echo wpautop(get_post_meta($current_post_id, 'details', true)); ?>
                    </div>
                    
                    <div class="content-panel" id="specs">
                        <ul class="specs-list">
                            <li><strong>Tipo:</strong> <?php echo get_post_meta($current_post_id, 'property_type', true); ?></li>
                            <li><strong>Quartos:</strong> <?php echo get_post_meta($current_post_id, 'bedrooms', true); ?></li>
                            <li><strong>Metragem:</strong> <?php echo get_post_meta($current_post_id, 'size', true); ?>m²</li>
                            <li><strong>Fachada:</strong> <?php echo get_post_meta($current_post_id, 'facade', true); ?></li>
                            <li><strong>Condomínio:</strong> <?php echo get_post_meta($current_post_id, 'condominium', true); ?></li>
                            <li><strong>Financiamento:</strong> <?php echo get_post_meta($current_post_id, 'financing', true); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="brokers-list">
              <h2>LISTA DE CORRETORES</h2>
              <?php if (!empty($brokers)): ?>
                  <?php foreach ($brokers as $broker): 
                      $profile_picture = get_user_meta($broker->ID, 'profile_picture', true);
                      $profile_picture = $profile_picture ?: '/wp-content/uploads/2025/02/Profile_avatar_placeholder_large.png';
                  ?>
                      <div class="broker-card">
                          <div class="broker-image">
                              <img src="<?php echo esc_url($profile_picture); ?>" alt="<?php echo esc_attr($broker->display_name); ?>">
                          </div>
                          <div class="broker-info">
                              <h3><?php echo $broker->display_name; ?></h3>
                              <?php if ($broker->is_sponsor): ?>
                                  <span class="sponsor-badge">Destaque</span>
                              <?php endif; ?>
                              <button 
                                  onclick="openContactForm(<?php echo esc_attr($broker->ID); ?>, <?php echo esc_attr($current_post_id); ?>)"
                                  class="contact-btn"
                              >
                                  Liberar Contato
                              </button>
                          </div>
                      </div>
                  <?php endforeach; ?>
              <?php else: ?>
                  <p>Nenhum corretor associado a este imóvel.</p>
              <?php endif; ?>
            </div>
          </div>
      </div>


  </div>

  <style>
      .immobile-content {
          max-width: 1200px;
          display:flex;
          flex-direction:column;
          margin: 0 auto;
      }

      .social-sharing-disabled-alert {
          padding: 15px;
          background-color: #ffebee;
          color: #d32f2f;
          border: 1px solid #d32f2f;
          border-radius: 4px;
          margin-bottom: 15px;
          font-weight: bold;
          text-align: center;
      }
      
      .content-main {
          max-width: 100%;
          overflow: hidden;
      }

      .media-wrapper {
          margin-bottom: 50px;
      }

      .media-tabs {
          display: flex;
      }

      .media-tab, .content-tab {
          padding: 10px 50px;
          background: #0056b3;
          cursor: pointer;
          transition: all 0.3s ease;
          color: #fff;
          border:0;
          border-radius:0;
      }

      .media-tab.active, .content-tab.active {
          color: #fff;
          font-weight: bold;
          background: #002b80;
      }

      .media-tab:hover, .content-tab:hover{
        background: #002b80;
      }

      .media-pane:not(.active), 
      .content-panel:not(.active) {
          display: none;
      }
      .immobile-header {
        display: flex;
        justify-content: space-between;
        align-items:center;
        color:#777;
        font-size:26px;
        font-weight:900!important;
        margin-top:90px;
      }

      .swiper {
          width: 100%;
          margin: 0 auto;
      }

      .swiper-slide {
          width: 100%;
          height: 100%;
          display: flex;
          justify-content: center;
          align-items: center;
      }

      .tab-navigation {
          border-bottom: 1px solid #1e56b3;
          margin-bottom: 25px;
      }

      .swiper-slide img {
          max-width: 100%;
          max-height: 100%;
          object-fit: contain;
      }

      .video-container {
          position: relative;
          width: 100%;
          padding-bottom: 56.25%;
          height: 0;
      }

      .video-container iframe {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
      }

      .media-content {
          min-height: 700px;
      }

      .content-wrapper{
        display:flex;
        justify-content:space-between;
      }

      .content-wrapper .content-tabs{
        width: 65%;
      }

      .content-wrapper .brokers-list {
          background-color: #f8f9fa;
          padding: 20px;
          border-radius: 8px;
          width: 30%;
      }

      .broker-card {
          display: flex;
          align-items: center;
          gap: 15px;
          padding: 15px;
          background-color: white;
          border-radius: 8px;
          margin-bottom: 15px;
          box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      }

      .broker-image {
          width: 80px;
          height: 80px;
      }

      .broker-image img {
          width: 100%;
          height: 100%;
          object-fit: cover;
          border-radius: 50%;
      }

      .broker-info {
          flex-grow: 1;
      }

      .broker-info h3 {
          margin: 0 0 10px 0;
          font-size: 16px;
      }

      .sponsor-badge {
          background-color: #28a745;
          color: white;
          padding: 2px 8px;
          border-radius: 4px;
          font-size: 0.8em;
          margin-left: 10px;
      }

      .contact-btn {
          display: inline-block;
          margin-top: 10px;
          padding: 8px 16px;
          background-color: #4CAF50;
          color: white;
          border: none;
          border-radius: 4px;
          cursor: pointer;
          transition: background-color 0.3s;
      }
      
      .contact-btn:hover {
          background-color: #45a049;
      }

      @media (max-width: 768px) {
          .immobile-content {
              grid-template-columns: 1fr;
          }

          .swiper {
              height: 300px;
          }
      }
  </style>

  <script>
  jQuery(document).ready(function($) {
      const gallerySlider = new Swiper('.gallery-slider', {
          slidesPerView: 1,
          navigation: {
              nextEl: '.swiper-button-next',
              prevEl: '.swiper-button-prev',
          }
      });

      const videoSlider = new Swiper('.video-slider', {
          slidesPerView: 1,
          navigation: {
              nextEl: '.swiper-button-next',
              prevEl: '.swiper-button-prev',
          }
      });

      $('.media-tab').click(function() {
          $('.media-tab').removeClass('active');
          $(this).addClass('active');
          
          const tabId = $(this).data('tab');
          $('.media-pane').removeClass('active');
          $(`#${tabId}`).addClass('active');
          
          if (tabId === 'photos') {
              gallerySlider.update();
          } else {
              videoSlider.update();
          }
      });

      $('.content-tab').click(function() {
          $('.content-tab').removeClass('active');
          $(this).addClass('active');
          
          const contentId = $(this).data('content');
          $('.content-panel').removeClass('active');
          $(`#${contentId}`).addClass('active');
      });
  });
  </script>
  <div class="destaque-interna">
    <div class="destaque-wrapper">
  <h2 class="destaque-title">Confira outros destaques</h2>
  <?php
  return ob_get_clean() . do_shortcode('[sponsored_carousel]');
  ?>
  </div>
  </div>
  <?php
}
add_shortcode('immobile_profile', 'display_immobile_template');

function immobile_template_include($template) {
    if (is_singular('immobile')) {
        $new_template = locate_template(array('single-immobile.php'));
        if ('' != $new_template) {
            return $new_template;
        }
        return plugin_dir_path(__FILE__) . 'templates/single-immobile.php';
    }
    return $template;
}
add_filter('template_include', 'immobile_template_include');