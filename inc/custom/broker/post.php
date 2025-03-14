<?php
function add_phone_field($user)
{
    if (in_array('author', (array) $user->roles)) {
?>
        <h3>Informações Adicionais</h3>
        <table class="form-table">
            <tr>
                <th><label for="phone">Telefone:</label></th>
                <td>
                    <input type="text" name="phone" id="phone" value="<?php echo esc_attr(get_the_author_meta('phone', $user->ID)); ?>" class="regular-text" /><br />
                </td>
            </tr>
            <tr>
                <th><label for="whatsapp">WhatsApp:</label></th>
                <td>
                    <input type="text" name="whatsapp" id="whatsapp" value="<?php echo esc_attr(get_the_author_meta('whatsapp', $user->ID)); ?>" class="regular-text" /><br />
                </td>
            </tr>
            <tr>
                <th><label for="company_name">Nome da Empresa:</label></th>
                <td>
                    <input type="text" name="company_name" id="company_name" value="<?php echo esc_attr(get_the_author_meta('company_name', $user->ID)); ?>" class="regular-text" /><br />
                </td>
            </tr>
            <tr>
                <th><label for="instagram">Instagram:</label></th>
                <td>
                    <input type="text" name="instagram" id="instagram" value="<?php echo esc_attr(get_the_author_meta('instagram', $user->ID)); ?>" class="regular-text" /><br />
                    <span class="description">Digite seu nome de usuário do Instagram (sem @)</span>
                </td>
            </tr>
            <tr>
                <th><label for="profile_picture">Foto de Perfil:</label></th>
                <td>
                    <?php 
                    $profile_picture = get_the_author_meta('profile_picture', $user->ID);
                    if ($profile_picture) {
                        echo '<img src="' . esc_url($profile_picture) . '" style="max-width:150px;"><br><br>';
                    }
                    ?>
                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*" /><br />
                    <span class="description">Tamanho recomendado: 300x300 pixels</span>
                </td>
            </tr>
        </table>
<?php
    }
}
add_action('show_user_profile', 'add_phone_field');
add_action('edit_user_profile', 'add_phone_field');

function save_phone_field($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (isset($_POST['phone'])) {
        update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
    }
    if (isset($_POST['whatsapp'])) {
        update_user_meta($user_id, 'whatsapp', sanitize_text_field($_POST['whatsapp']));
    }
    if (isset($_POST['company_name'])) {
        update_user_meta($user_id, 'company_name', sanitize_text_field($_POST['company_name']));
    }
    if (isset($_POST['instagram'])) {
        update_user_meta($user_id, 'instagram', sanitize_text_field($_POST['instagram']));
    }
    
    if (!empty($_FILES['profile_picture']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('profile_picture', 0);
        
        if (!is_wp_error($attachment_id)) {
            $image_url = wp_get_attachment_url($attachment_id);
            update_user_meta($user_id, 'profile_picture', $image_url);
        }
    }
}

add_action('personal_options_update', 'save_phone_field');
add_action('edit_user_profile_update', 'save_phone_field');

function display_form_broker()
{
    ob_start();
    require_once(__DIR__ . "/form.php");
    $content = ob_get_clean();
    return $content;
}
add_shortcode('form_broker', 'display_form_broker');

function display_edit_form_broker()
{
    ob_start();
    require_once(__DIR__ . "/edit-form.php");
    $content = ob_get_clean();
    return $content;
}
add_shortcode('edit_form_broker', 'display_edit_form_broker');

function display_list_broker()
{
    ob_start();
    require_once(__DIR__ . "/list.php");
    $content = ob_get_clean();
    return $content;
}
add_shortcode('list_brokers', 'display_list_broker');

function display_broker_name()
{
    $user_id = get_post_meta(get_the_ID(), 'broker', true);
    $user_name = get_user_by('ID', $user_id)->display_name;
    return $user_name;
}
add_shortcode('broker_name', 'display_broker_name');