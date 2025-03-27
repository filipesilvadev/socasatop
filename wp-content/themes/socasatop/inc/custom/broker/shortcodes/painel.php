<?php
function display_broker_first_name() {
    $user = wp_get_current_user();
    return $user->first_name ?: $user->display_name;
}
add_shortcode('user_first_name', 'display_broker_first_name');

function display_total_imoveis() {
    global $wpdb;
    $user_id = get_current_user_id();
    
    $total = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT i.ID) 
        FROM {$wpdb->posts} i
        JOIN {$wpdb->postmeta} pm ON i.ID = pm.post_id
        WHERE i.post_type = 'immobile'
        AND i.post_status = 'publish'
        AND pm.meta_key = 'broker'
        AND pm.meta_value = %d
    ", $user_id));
    
    return $total ?: '0';
}
add_shortcode('total_imoveis', 'display_total_imoveis');

function display_imoveis_destaque() {
    global $wpdb;
    $user_id = get_current_user_id();
    
    $total = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT i.ID)
        FROM {$wpdb->posts} i
        JOIN {$wpdb->postmeta} pm ON i.ID = pm.post_id
        JOIN {$wpdb->postmeta} pm2 ON i.ID = pm2.post_id
        WHERE i.post_type = 'immobile'
        AND i.post_status = 'publish'
        AND pm.meta_key = 'broker'
        AND pm.meta_value = %d
        AND pm2.meta_key = 'is_sponsored'
        AND pm2.meta_value = 'yes'
    ", $user_id));
    
    return $total ?: '0';
}
add_shortcode('imoveis_destaque', 'display_imoveis_destaque');

function display_total_views() {
    global $wpdb;
    $user_id = get_current_user_id();
    
    $total = $wpdb->get_var($wpdb->prepare("
        SELECT COALESCE(SUM(CAST(vm.meta_value AS UNSIGNED)), 0)
        FROM {$wpdb->posts} i
        JOIN {$wpdb->postmeta} pm ON i.ID = pm.post_id
        JOIN {$wpdb->postmeta} vm ON i.ID = vm.post_id
        WHERE i.post_type = 'immobile'
        AND i.post_status = 'publish'
        AND pm.meta_key = 'broker'
        AND pm.meta_value = %d
        AND vm.meta_key = 'total_views'
    ", $user_id));
    
    return $total;
}
add_shortcode('total_views', 'display_total_views');

function display_total_clicks() {
    global $wpdb;
    $user_id = get_current_user_id();
    
    $total = $wpdb->get_var($wpdb->prepare("
        SELECT COALESCE(SUM(CAST(cm.meta_value AS UNSIGNED)), 0)
        FROM {$wpdb->posts} i
        JOIN {$wpdb->postmeta} pm ON i.ID = pm.post_id
        JOIN {$wpdb->postmeta} cm ON i.ID = cm.post_id
        WHERE i.post_type = 'immobile'
        AND i.post_status = 'publish'
        AND pm.meta_key = 'broker'
        AND pm.meta_value = %d
        AND cm.meta_key = 'total_clicks'
    ", $user_id));
    
    return $total;
}
add_shortcode('total_clicks', 'display_total_clicks');

function display_total_conversions() {
    global $wpdb;
    $user_id = get_current_user_id();
    
    $total = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT l.ID)
        FROM {$wpdb->posts} l
        JOIN {$wpdb->postmeta} pm ON l.ID = pm.post_id
        WHERE l.post_type = 'lead'
        AND l.post_status = 'publish'
        AND pm.meta_key = 'broker_id'
        AND pm.meta_value = %d
    ", $user_id));
    
    return $total;
}
add_shortcode('total_conversions', 'display_total_conversions');

function display_broker_immobiles() {
    global $wpdb;
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        return '<p>Você precisa estar logado para ver seus imóveis.</p>';
    }
    
    // Buscar imóveis do corretor
    $args = array(
        'post_type' => 'immobile',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'broker',
                'value' => $user_id,
                'compare' => '='
            )
        )
    );
    
    $immobiles = new WP_Query($args);
    
    ob_start();
    ?>
    <div class="broker-immobiles">
        <h2>Meus Imóveis</h2>
        
        <?php if ($immobiles->have_posts()) : ?>
            <div class="immobiles-list">
                <?php while ($immobiles->have_posts()) : $immobiles->the_post(); 
                    $post_id = get_the_ID();
                    $status = get_post_status($post_id);
                    $is_paused = $status === 'draft';
                    $location = get_post_meta($post_id, 'location', true);
                    $amount = get_post_meta($post_id, 'amount', true);
                    $property_type = get_post_meta($post_id, 'property_type', true);
                    $gallery = get_post_meta($post_id, 'immobile_gallery', true);
                    $gallery_ids = $gallery ? explode(',', $gallery) : [];
                    $featured_image = !empty($gallery_ids) ? wp_get_attachment_image_url($gallery_ids[0], 'thumbnail') : '';
                ?>
                    <div class="immobile-item <?php echo $is_paused ? 'paused' : ''; ?>">
                        <div class="immobile-image">
                            <?php if ($featured_image) : ?>
                                <img src="<?php echo esc_url($featured_image); ?>" alt="<?php the_title(); ?>">
                            <?php else : ?>
                                <div class="no-image">Sem imagem</div>
                            <?php endif; ?>
                        </div>
                        <div class="immobile-details">
                            <h3><?php the_title(); ?></h3>
                            <p class="immobile-location"><?php echo esc_html($location); ?></p>
                            <p class="immobile-type"><?php echo esc_html($property_type); ?></p>
                            <p class="immobile-price">R$ <?php echo number_format(floatval($amount), 2, ',', '.'); ?></p>
                            <?php if ($is_paused) : ?>
                                <span class="status-badge paused">Pausado</span>
                            <?php else : ?>
                                <span class="status-badge active">Ativo</span>
                            <?php endif; ?>
                        </div>
                        <div class="immobile-actions">
                            <a href="<?php echo get_permalink($post_id); ?>" class="view-button">Ver</a>
                            <a href="/editar-imovel/?id=<?php echo $post_id; ?>" class="edit-button">Editar</a>
                            <?php if ($is_paused) : ?>
                                <button class="toggle-status-button activate" data-id="<?php echo $post_id; ?>" data-action="activate">Ativar</button>
                            <?php else : ?>
                                <button class="toggle-status-button pause" data-id="<?php echo $post_id; ?>" data-action="pause">Pausar</button>
                            <?php endif; ?>
                            <button class="delete-button" data-id="<?php echo $post_id; ?>">Excluir</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('.toggle-status-button').on('click', function() {
                    const button = $(this);
                    const immobileId = button.data('id');
                    const action = button.data('action');
                    
                    $.ajax({
                        url: site.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'toggle_immobile_status',
                            immobile_id: immobileId,
                            status_action: action,
                            nonce: site.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                // Atualizar a interface
                                const item = button.closest('.immobile-item');
                                
                                if (action === 'pause') {
                                    item.addClass('paused');
                                    button.text('Ativar').removeClass('pause').addClass('activate').data('action', 'activate');
                                    item.find('.status-badge').text('Pausado').removeClass('active').addClass('paused');
                                } else {
                                    item.removeClass('paused');
                                    button.text('Pausar').removeClass('activate').addClass('pause').data('action', 'pause');
                                    item.find('.status-badge').text('Ativo').removeClass('paused').addClass('active');
                                }
                                
                                // Mostrar mensagem de sucesso
                                alert(response.data.message);
                            } else {
                                alert('Erro ao alterar o status do imóvel.');
                            }
                        }
                    });
                });
                
                // Adiciona handler para o botão de excluir
                $('.delete-button').on('click', function() {
                    if (confirm('Tem certeza que deseja excluir este imóvel? Esta ação não pode ser desfeita.')) {
                        const button = $(this);
                        const immobileId = button.data('id');
                        
                        $.ajax({
                            url: site.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'delete_immobile',
                                immobile_id: immobileId,
                                nonce: site.nonce
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Remover o item da lista
                                    button.closest('.immobile-item').fadeOut(300, function() {
                                        $(this).remove();
                                    });
                                    
                                    // Mostrar mensagem de sucesso
                                    alert(response.data.message);
                                } else {
                                    alert('Erro ao excluir o imóvel.');
                                }
                            }
                        });
                    }
                });
            });
            </script>
            
            <style>
            .broker-immobiles {
                margin: 20px 0;
            }
            
            .immobiles-list {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            
            .immobile-item {
                border: 1px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
                transition: all 0.3s ease;
            }
            
            .immobile-item.paused {
                opacity: 0.7;
                background-color: #f8f8f8;
            }
            
            .immobile-image {
                height: 180px;
                overflow: hidden;
            }
            
            .immobile-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .no-image {
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: #f0f0f0;
                color: #666;
            }
            
            .immobile-details {
                padding: 15px;
            }
            
            .immobile-details h3 {
                margin: 0 0 10px;
                font-size: 18px;
            }
            
            .immobile-location, .immobile-type, .immobile-price {
                margin: 5px 0;
                font-size: 14px;
            }
            
            .immobile-price {
                font-weight: bold;
                color: #1E56B3;
            }
            
            .status-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 4px;
                font-size: 12px;
                margin-top: 10px;
            }
            
            .status-badge.active {
                background-color: #28a745;
                color: white;
            }
            
            .status-badge.paused {
                background-color: #dc3545;
                color: white;
            }
            
            .immobile-actions {
                display: flex;
                padding: 10px 15px;
                background-color: #f8f8f8;
                border-top: 1px solid #ddd;
            }
            
            .immobile-actions a, .immobile-actions button {
                flex: 1;
                padding: 8px 12px;
                text-align: center;
                border-radius: 4px;
                margin: 0 5px;
                cursor: pointer;
                font-size: 14px;
                text-decoration: none;
                border: none;
            }
            
            .view-button {
                background-color: #6c757d;
                color: white;
            }
            
            .edit-button {
                background-color: #1E56B3;
                color: white;
            }
            
            .toggle-status-button {
                font-weight: bold;
            }
            
            .toggle-status-button.pause {
                background-color: #dc3545;
                color: white;
            }
            
            .toggle-status-button.activate {
                background-color: #28a745;
                color: white;
            }
            
            .delete-button {
                background-color: #dc3545;
                color: white;
                font-weight: bold;
            }
            </style>
        <?php else : ?>
            <div class="no-immobiles">
                <p>Você ainda não tem imóveis cadastrados.</p>
                <a href="/corretores/novo-imovel/" class="add-immobile-button">Adicionar Imóvel</a>
            </div>
        <?php endif; ?>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('broker_immobiles', 'display_broker_immobiles');