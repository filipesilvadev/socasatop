<?php
/**
 * Formulário para adicionar/editar propriedades
 */

// Impedir acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza o formulário de propriedade
 */
function render_property_form($atts = []) {
    // Verificar se o usuário está logado
    if (!is_user_logged_in()) {
        return '<div class="alert alert-warning">Você precisa estar logado para adicionar imóveis.</div>';
    }
    
    // Iniciar o buffer de saída
    ob_start();
    
    // Valores padrão
    $property_data = [
        'title' => '',
        'description' => '',
        'price' => '',
        'address' => '',
        'city' => '',
        'state' => '',
        'bedrooms' => '',
        'bathrooms' => '',
        'area' => '',
        'property_type' => '',
        'features' => []
    ];
    
    // Verificar se está editando uma propriedade existente
    $property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($property_id > 0) {
        // Carregar dados da propriedade
        $property = get_post($property_id);
        if ($property && $property->post_type == 'property') {
            $property_data['title'] = $property->post_title;
            $property_data['description'] = $property->post_content;
            // Carregar meta dados
            $property_data['price'] = get_post_meta($property_id, 'property_price', true);
            $property_data['address'] = get_post_meta($property_id, 'property_address', true);
            $property_data['city'] = get_post_meta($property_id, 'property_city', true);
            $property_data['state'] = get_post_meta($property_id, 'property_state', true);
            $property_data['bedrooms'] = get_post_meta($property_id, 'property_bedrooms', true);
            $property_data['bathrooms'] = get_post_meta($property_id, 'property_bathrooms', true);
            $property_data['area'] = get_post_meta($property_id, 'property_area', true);
            $property_data['property_type'] = get_post_meta($property_id, 'property_type', true);
            // Características
            $features = get_post_meta($property_id, 'property_features', true);
            if ($features) {
                $property_data['features'] = is_array($features) ? $features : explode(',', $features);
            }
        }
    }
    
    // Formulário de adição/edição de propriedade
    ?>
    <form id="property-form" class="property-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('property_form_nonce', 'property_nonce'); ?>
        <input type="hidden" name="action" value="save_property">
        <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
        
        <div class="form-section">
            <h3>Informações Básicas</h3>
            
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="property_title">Título do Imóvel*</label>
                    <input type="text" class="form-control" id="property_title" name="property_title" value="<?php echo esc_attr($property_data['title']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="property_description">Descrição*</label>
                    <textarea class="form-control" id="property_description" name="property_description" rows="5" required><?php echo esc_textarea($property_data['description']); ?></textarea>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="property_price">Preço (R$)*</label>
                    <input type="text" class="form-control" id="property_price" name="property_price" value="<?php echo esc_attr($property_data['price']); ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="property_type">Tipo de Imóvel*</label>
                    <select class="form-control" id="property_type" name="property_type" required>
                        <option value="">Selecione...</option>
                        <option value="apartment" <?php selected($property_data['property_type'], 'apartment'); ?>>Apartamento</option>
                        <option value="house" <?php selected($property_data['property_type'], 'house'); ?>>Casa</option>
                        <option value="commercial" <?php selected($property_data['property_type'], 'commercial'); ?>>Comercial</option>
                        <option value="land" <?php selected($property_data['property_type'], 'land'); ?>>Terreno</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3>Localização</h3>
            
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="property_address">Endereço*</label>
                    <input type="text" class="form-control" id="property_address" name="property_address" value="<?php echo esc_attr($property_data['address']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="property_city">Cidade*</label>
                    <input type="text" class="form-control" id="property_city" name="property_city" value="<?php echo esc_attr($property_data['city']); ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="property_state">Estado*</label>
                    <input type="text" class="form-control" id="property_state" name="property_state" value="<?php echo esc_attr($property_data['state']); ?>" required>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3>Detalhes</h3>
            
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="property_bedrooms">Quartos</label>
                    <input type="number" class="form-control" id="property_bedrooms" name="property_bedrooms" value="<?php echo esc_attr($property_data['bedrooms']); ?>" min="0">
                </div>
                <div class="form-group col-md-4">
                    <label for="property_bathrooms">Banheiros</label>
                    <input type="number" class="form-control" id="property_bathrooms" name="property_bathrooms" value="<?php echo esc_attr($property_data['bathrooms']); ?>" min="0">
                </div>
                <div class="form-group col-md-4">
                    <label for="property_area">Área (m²)</label>
                    <input type="text" class="form-control" id="property_area" name="property_area" value="<?php echo esc_attr($property_data['area']); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label>Características</label>
                    <div class="checkbox-group">
                        <?php
                        $features = [
                            'garage' => 'Garagem',
                            'pool' => 'Piscina',
                            'garden' => 'Jardim',
                            'security' => 'Segurança',
                            'gym' => 'Academia',
                            'elevator' => 'Elevador',
                            'bbq' => 'Churrasqueira',
                            'furnished' => 'Mobiliado'
                        ];
                        
                        foreach ($features as $key => $label) {
                            $checked = in_array($key, $property_data['features']) ? 'checked' : '';
                            echo '<div class="form-check">';
                            echo '<input class="form-check-input" type="checkbox" name="property_features[]" value="' . $key . '" id="feature_' . $key . '" ' . $checked . '>';
                            echo '<label class="form-check-label" for="feature_' . $key . '">' . $label . '</label>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3>Imagens</h3>
            
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="property_images">Imagens do Imóvel</label>
                    <input type="file" class="form-control-file" id="property_images" name="property_images[]" multiple accept="image/*">
                    <small class="form-text text-muted">Você pode selecionar várias imagens ao mesmo tempo.</small>
                </div>
            </div>
            
            <?php if ($property_id > 0) : ?>
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Imagens Atuais</label>
                        <div class="property-images-gallery">
                            <?php
                            // Exibir imagens existentes
                            $attachments = get_posts([
                                'post_type' => 'attachment',
                                'posts_per_page' => -1,
                                'post_parent' => $property_id,
                                'post_status' => 'any',
                                'order' => 'ASC',
                                'orderby' => 'menu_order'
                            ]);
                            
                            if ($attachments) {
                                foreach ($attachments as $attachment) {
                                    $thumb = wp_get_attachment_image_src($attachment->ID, 'thumbnail');
                                    echo '<div class="property-image-item">';
                                    echo '<img src="' . $thumb[0] . '" alt="Imagem da Propriedade">';
                                    echo '<div class="image-actions">';
                                    echo '<button type="button" class="btn btn-sm btn-danger remove-image" data-id="' . $attachment->ID . '">Remover</button>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p>Nenhuma imagem adicionada.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">
                <?php echo $property_id > 0 ? 'Atualizar Imóvel' : 'Publicar Imóvel'; ?>
            </button>
        </div>
        
        <div id="upload-messages" class="mt-3"></div>
    </form>
    
    <script>
    jQuery(document).ready(function($) {
        // Máscara para campos de preço
        if ($.fn.inputmask) {
            $('#property_price').inputmask('currency', {
                prefix: 'R$ ',
                groupSeparator: '.',
                radixPoint: ',',
                autoGroup: true,
                digits: 2,
                digitsOptional: false,
                rightAlign: false,
                allowMinus: false
            });
            
            $('#property_area').inputmask('decimal', {
                groupSeparator: '.',
                radixPoint: ',',
                autoGroup: true,
                digits: 2,
                digitsOptional: true,
                rightAlign: false,
                allowMinus: false
            });
        }
        
        // Submissão do formulário via AJAX
        $('#property-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            
            // Exibir mensagem de carregamento
            $('#upload-messages').html('<div class="alert alert-info">Enviando dados, aguarde...</div>');
            
            $.ajax({
                url: site.ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.success) {
                        $('#upload-messages').html('<div class="alert alert-success">' + response.data.message + '</div>');
                        
                        // Redirecionar ou atualizar a interface conforme necessário
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1500);
                        }
                    } else {
                        $('#upload-messages').html('<div class="alert alert-danger">' + response.data.message + '</div>');
                    }
                },
                error: function() {
                    $('#upload-messages').html('<div class="alert alert-danger">Erro ao enviar os dados. Tente novamente.</div>');
                }
            });
        });
        
        // Remover imagem
        $('.remove-image').on('click', function() {
            var imageId = $(this).data('id');
            var imageItem = $(this).closest('.property-image-item');
            
            if (confirm('Tem certeza que deseja remover esta imagem?')) {
                $.ajax({
                    url: site.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'remove_property_image',
                        image_id: imageId,
                        nonce: site.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            imageItem.fadeOut(300, function() {
                                $(this).remove();
                                
                                if ($('.property-image-item').length === 0) {
                                    $('.property-images-gallery').html('<p>Nenhuma imagem adicionada.</p>');
                                }
                            });
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert('Erro ao remover imagem. Tente novamente.');
                    }
                });
            }
        });
    });
    </script>
    <?php
    
    // Retornar o conteúdo do buffer
    return ob_get_clean();
}
add_shortcode('property_form', 'render_property_form');

/**
 * Manipula a submissão do formulário de propriedade
 */
function handle_property_form_submission() {
    // Verificar se é uma requisição para salvar propriedade
    if (!isset($_POST['action']) || $_POST['action'] !== 'save_property') {
        return;
    }
    
    // Verificar nonce
    if (!isset($_POST['property_nonce']) || !wp_verify_nonce($_POST['property_nonce'], 'property_form_nonce')) {
        wp_send_json_error(['message' => 'Erro de segurança. Atualize a página e tente novamente.']);
    }
    
    // Verificar se o usuário está logado
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Você precisa estar logado para adicionar imóveis.']);
    }
    
    // Validar campos obrigatórios
    $required_fields = ['property_title', 'property_description', 'property_price', 'property_type', 'property_address', 'property_city', 'property_state'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            wp_send_json_error(['message' => 'Preencha todos os campos obrigatórios.']);
        }
    }
    
    // Preparar dados para o post
    $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
    $is_update = $property_id > 0;
    
    $property_data = [
        'post_title' => sanitize_text_field($_POST['property_title']),
        'post_content' => wp_kses_post($_POST['property_description']),
        'post_type' => 'property',
        'post_status' => 'pending', // Enviar para revisão
        'post_author' => get_current_user_id()
    ];
    
    // Se for uma atualização, incluir o ID do post
    if ($is_update) {
        $property_data['ID'] = $property_id;
        
        // Verificar se o usuário atual é o autor da propriedade
        $property = get_post($property_id);
        if ($property->post_author !== get_current_user_id() && !current_user_can('edit_others_posts')) {
            wp_send_json_error(['message' => 'Você não tem permissão para editar esta propriedade.']);
        }
    }
    
    // Inserir ou atualizar o post
    $post_id = wp_insert_post($property_data);
    
    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => 'Erro ao salvar a propriedade: ' . $post_id->get_error_message()]);
    }
    
    // Salvar campos personalizados
    update_post_meta($post_id, 'property_price', sanitize_text_field($_POST['property_price']));
    update_post_meta($post_id, 'property_address', sanitize_text_field($_POST['property_address']));
    update_post_meta($post_id, 'property_city', sanitize_text_field($_POST['property_city']));
    update_post_meta($post_id, 'property_state', sanitize_text_field($_POST['property_state']));
    update_post_meta($post_id, 'property_type', sanitize_text_field($_POST['property_type']));
    
    // Campos opcionais
    if (isset($_POST['property_bedrooms'])) {
        update_post_meta($post_id, 'property_bedrooms', intval($_POST['property_bedrooms']));
    }
    
    if (isset($_POST['property_bathrooms'])) {
        update_post_meta($post_id, 'property_bathrooms', intval($_POST['property_bathrooms']));
    }
    
    if (isset($_POST['property_area'])) {
        update_post_meta($post_id, 'property_area', sanitize_text_field($_POST['property_area']));
    }
    
    // Características
    if (isset($_POST['property_features']) && is_array($_POST['property_features'])) {
        $features = array_map('sanitize_text_field', $_POST['property_features']);
        update_post_meta($post_id, 'property_features', $features);
    } else {
        update_post_meta($post_id, 'property_features', []);
    }
    
    // Processar imagens
    if (!empty($_FILES['property_images']['name'][0])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $files = $_FILES['property_images'];
        $uploaded_ids = [];
        
        foreach ($files['name'] as $key => $value) {
            if ($files['name'][$key]) {
                $file = [
                    'name' => $files['name'][$key],
                    'type' => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error' => $files['error'][$key],
                    'size' => $files['size'][$key]
                ];
                
                $_FILES = ['property_image' => $file];
                $attachment_id = media_handle_upload('property_image', $post_id);
                
                if (!is_wp_error($attachment_id)) {
                    $uploaded_ids[] = $attachment_id;
                }
            }
        }
        
        if (!empty($uploaded_ids)) {
            update_post_meta($post_id, '_thumbnail_id', $uploaded_ids[0]);
        }
    }
    
    // Preparar resposta
    $message = $is_update ? 'Imóvel atualizado com sucesso!' : 'Imóvel enviado para aprovação com sucesso!';
    $redirect = get_permalink($post_id);
    
    // Retornar resposta
    wp_send_json_success([
        'message' => $message,
        'property_id' => $post_id,
        'redirect' => $redirect
    ]);
}
add_action('wp_ajax_save_property', 'handle_property_form_submission');

/**
 * Remove uma imagem da propriedade
 */
function remove_property_image() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(['message' => 'Erro de segurança. Atualize a página e tente novamente.']);
    }
    
    // Verificar se o usuário está logado
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Você precisa estar logado para remover imagens.']);
    }
    
    // Verificar ID da imagem
    if (!isset($_POST['image_id']) || empty($_POST['image_id'])) {
        wp_send_json_error(['message' => 'ID da imagem não fornecido.']);
    }
    
    $image_id = intval($_POST['image_id']);
    $attachment = get_post($image_id);
    
    // Verificar se a imagem existe
    if (!$attachment || $attachment->post_type !== 'attachment') {
        wp_send_json_error(['message' => 'Imagem não encontrada.']);
    }
    
    // Verificar se o usuário atual é o autor da propriedade
    $property_id = $attachment->post_parent;
    $property = get_post($property_id);
    
    if ($property && $property->post_author !== get_current_user_id() && !current_user_can('delete_others_posts')) {
        wp_send_json_error(['message' => 'Você não tem permissão para remover esta imagem.']);
    }
    
    // Remover a imagem
    $result = wp_delete_attachment($image_id, true);
    
    if ($result) {
        wp_send_json_success(['message' => 'Imagem removida com sucesso.']);
    } else {
        wp_send_json_error(['message' => 'Erro ao remover a imagem. Tente novamente.']);
    }
}
add_action('wp_ajax_remove_property_image', 'remove_property_image'); 