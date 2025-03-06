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
    <div class="immobile-header">
        <div class="location-title">
            <?php echo get_post_meta($current_post_id, 'location', true); ?>
        </div>
        <div class="price-value">
            R$ <?php echo number_format(floatval(get_post_meta($current_post_id, 'amount', true)), 2, ',', '.'); ?>
        </div>
    </div>

    <div class="immobile-container">
        <div class="main-image">
            <?php 
            if (!empty($gallery_ids)) {
                $image_url = wp_get_attachment_image_url($gallery_ids[0], 'full');
                if ($image_url) {
                    echo '<img src="' . esc_url($image_url) . '" alt="Imagem principal do imóvel">';
                }
            }
            ?>
        </div>

        <div class="content-tabs">
            <div class="tab-buttons">
                <button class="tab-button active" data-tab="description">DESCRIÇÃO</button>
                <button class="tab-button" data-tab="specs">ESPECIFICAÇÕES</button>
                <button class="tab-button" data-tab="brokers">LISTA DE CORRETORES!!!</button>
            </div>

            <div class="tab-content">
                <div class="tab-panel active" id="description">
                    <?php echo wpautop(get_post_meta($current_post_id, 'details', true)); ?>
                </div>

                <div class="tab-panel" id="specs">
                    <ul class="specs-list">
                        <li><strong>Tipo:</strong> <?php echo get_post_meta($current_post_id, 'property_type', true); ?></li>
                        <li><strong>Quartos:</strong> <?php echo get_post_meta($current_post_id, 'bedrooms', true); ?></li>
                        <li><strong>Metragem:</strong> <?php echo get_post_meta($current_post_id, 'size', true); ?>m²</li>
                        <li><strong>Fachada:</strong> <?php echo get_post_meta($current_post_id, 'facade', true); ?></li>
                        <li><strong>Condomínio:</strong> <?php echo get_post_meta($current_post_id, 'condominium', true); ?></li>
                        <li><strong>Financiamento:</strong> <?php echo get_post_meta($current_post_id, 'financing', true); ?></li>
                    </ul>
                </div>

                <div class="tab-panel" id="brokers">
                    <div class="brokers-grid">
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
                                        <span class="sponsor-badge">Patrocinador</span>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .immobile-header {
            background: linear-gradient(to right, #000066, #6666cc, #ffffff);
            padding: 20px;
            color: white;
            margin-bottom: 20px;
        }

        .location-title {
            font-size: 24px;
            font-weight: bold;
            display: inline-block;
        }

        .price-value {
            font-size: 24px;
            font-weight: bold;
            float: right;
        }

        .immobile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .main-image {
            margin-bottom: 30px;
        }

        .main-image img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .content-tabs {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .tab-buttons {
            display: flex;
            border-bottom: 1px solid #e1e1e1;
        }

        .tab-button {
            padding: 15px 30px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #666;
        }

        .tab-button.active {
            color: #000066;
            border-bottom: 2px solid #000066;
        }

        .tab-panel {
            display: none;
            padding: 20px;
        }

        .tab-panel.active {
            display: block;
        }

        .specs-list {
            list-style: none;
            padding: 0;
        }

        .specs-list li {
            padding: 10px 0;
            border-bottom: 1px solid #e1e1e1;
        }

        .brokers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .broker-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .broker-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
        }

        .broker-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .broker-info h3 {
            margin: 0;
            font-size: 16px;
            color: #333;
        }

        .sponsor-badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-top: 5px;
        }

        .contact-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background-color: #000066;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .contact-btn:hover {
            background-color: #000099;
        }

        @media (max-width: 768px) {
            .immobile-header {
                text-align: center;
            }

            .location-title, .price-value {
                display: block;
                float: none;
                margin-bottom: 10px;
            }

            .tab-buttons {
                flex-wrap: wrap;
            }

            .tab-button {
                flex: 1 1 auto;
                padding: 10px;
            }
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        $('.tab-button').click(function() {
            const tabId = $(this).data('tab');
            
            $('.tab-button').removeClass('active');
            $(this).addClass('active');
            
            $('.tab-panel').removeClass('active');
            $('#' + tabId).addClass('active');
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('immobile_profile', 'display_immobile_template');
