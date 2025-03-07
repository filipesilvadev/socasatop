<?php

function register_broker_form() {
    if (is_user_logged_in()) {
        return '<p class="broker-message">Você já está logado no sistema.</p>';
    }

    ob_start();
    ?>
    <form id="broker-register-form" class="broker-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('broker_register_nonce', 'broker_register_nonce'); ?>
        
        <div class="form-group">
            <label for="broker_name">Nome Completo *</label>
            <input type="text" id="broker_name" name="broker_name" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="broker_email">E-mail *</label>
            <input type="email" id="broker_email" name="broker_email" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="broker_password">Senha *</label>
            <input type="password" id="broker_password" name="broker_password" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="broker_creci">CRECI *</label>
            <input type="text" id="broker_creci" name="broker_creci" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="broker_release">Biografia - Crie um breve resumo sobre seus diferenciais. * </label>
            <textarea id="broker_release" name="broker_release" class="form-control" rows="4"></textarea>
        </div>

        <div class="form-group">
            <label for="broker_whatsapp">WhatsApp</label>
            <input type="text" id="broker_whatsapp" name="broker_whatsapp" class="form-control">
        </div>

        <div class="form-group">
            <label for="broker_company">Nome da Empresa</label>
            <input type="text" id="broker_company" name="broker_company" class="form-control">
        </div>

        <div class="form-group">
            <label for="broker_instagram">Instagram (sem @)</label>
            <input type="text" id="broker_instagram" name="broker_instagram" class="form-control">
        </div>

        <div class="form-group">
            <label for="broker_profile_picture">Foto de Perfil</label>
            <input type="file" id="broker_profile_picture" name="broker_profile_picture" class="form-control" accept="image/*">
            <small class="form-text text-muted">Tamanho recomendado: 300x300 pixels</small>
        </div>

        <div class="form-group">
            <label class="checkbox-container">
                <input type="checkbox" id="broker_terms" name="broker_terms" required>
                <span class="checkbox-text">Ao continuar utilizando a plataforma Só Casa Top, declaro que li, compreendi e aceito integralmente os presentes Termos e Condições.*</span>
            </label>
        </div>

        <div class="form-submit">
            <button type="submit" class="submit-button">Cadastrar</button>
        </div>
    </form>

    <script>
    jQuery(document).ready(function($) {
        $('#broker-register-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData();
            formData.append('action', 'register_broker');
            formData.append('nonce', site.nonce);
            formData.append('name', $('#broker_name').val());
            formData.append('email', $('#broker_email').val());
            formData.append('password', $('#broker_password').val());
            formData.append('creci', $('#broker_creci').val());
            formData.append('release', $('#broker_release').val());
            formData.append('whatsapp', $('#broker_whatsapp').val());
            formData.append('company', $('#broker_company').val());
            formData.append('instagram', $('#broker_instagram').val());
            formData.append('terms', $('#broker_terms').is(':checked'));
            
            if ($('#broker_profile_picture')[0].files[0]) {
                formData.append('profile_picture', $('#broker_profile_picture')[0].files[0]);
            }
            
            $.ajax({
                url: site.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        window.location.href = '/ajuda';
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: response.data
                        });
                    }
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('register_broker_form', 'register_broker_form');


function register_broker_ajax() {
    check_ajax_referer('ajax_nonce', 'nonce');
    
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $creci = sanitize_text_field($_POST['creci']);
    $release = sanitize_textarea_field($_POST['release']);
    
    if (!is_email($email)) {
        wp_send_json_error('E-mail inválido');
    }
    
    if (email_exists($email)) {
        wp_send_json_error('Este e-mail já está cadastrado');
    }
    
    $user_id = wp_create_user($email, $password, $email);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error($user_id->get_error_message());
    }
    
    $user = new WP_User($user_id);
    $user->set_role('author');
    
    update_user_meta($user_id, 'first_name', $name);
    update_user_meta($user_id, 'creci', $creci);
    update_user_meta($user_id, 'release', $release);
    
    if (isset($_POST['whatsapp'])) {
        update_user_meta($user_id, 'whatsapp', sanitize_text_field($_POST['whatsapp']));
    }
    if (isset($_POST['company'])) {
        update_user_meta($user_id, 'company_name', sanitize_text_field($_POST['company']));
    }
    if (isset($_POST['instagram'])) {
        update_user_meta($user_id, 'instagram', sanitize_text_field($_POST['instagram']));
    }
    
    if (!empty($_FILES['profile_picture'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('profile_picture', 0);
        
        if (!is_wp_error($attachment_id)) {
            $image_url = wp_get_attachment_url($attachment_id);
            update_user_meta($user_id, 'profile_picture', $image_url);
        }
    }
    
    // Fazer login automático
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    // Enviar e-mail de boas-vindas
    $to = $email;
    $subject = 'Bem-vindo ao nosso portal';
    $message = sprintf(
        'Olá %s,<br><br>'.
        'Seu cadastro foi realizado com sucesso!<br>'.
        'Acesse sua conta com os seguintes dados:<br><br>'.
        'E-mail: %s<br>'.
        'Senha: %s<br><br>'.
        'Acesse sua conta em: <a href="%s">%s</a>',
        $name,
        $email,
        $password,
        home_url('/ajuda'),
        home_url('/ajuda')
    );
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($to, $subject, $message, $headers);
    
    wp_send_json_success('Cadastro realizado com sucesso!');
    update_user_meta($user_id, 'new_broker_registration', true);
}
add_action('wp_ajax_nopriv_register_broker', 'register_broker_ajax');


function new_broker_first_login_redirect($user_login, $user) {
    $is_new_broker = get_user_meta($user->ID, 'new_broker_registration', true);
    
    if ($is_new_broker && in_array('author', $user->roles)) {
        wp_redirect(get_permalink(get_page_by_path('ajuda')->ID));
        delete_user_meta($user->ID, 'new_broker_registration');
        exit;
    }
}
add_action('wp_login', 'new_broker_first_login_redirect', 11, 2);