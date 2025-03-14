<?php
require_once(__DIR__ . '/config.php');
$immobile_options = get_immobile_options();

global $wpdb;

// Buscar TODOS os usuários do tipo 'author'
$brokers_args = array(
    'role' => 'author',
    'orderby' => 'display_name',
    'order' => 'ASC',
    'number' => -1
);
$brokers = get_users($brokers_args);

// Buscar corretores associados
$broker_immobile_table = $wpdb->prefix . 'broker_immobile';
$associated_brokers = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT broker_id, is_sponsor FROM $broker_immobile_table WHERE immobile_id = %d",
        $post->ID
    )
);

$broker_associations = array();
foreach ($associated_brokers as $assoc) {
    $broker_associations[$assoc->broker_id] = $assoc->is_sponsor;
}

$locations = get_terms(array(
    'taxonomy' => 'locations',
    'hide_empty' => false,
));

$gallery = get_post_meta($post->ID, 'immobile_gallery', true);

// Usar as opções padronizadas do config.php
$options = $immobile_options['yes_no_options'];
$property_types = $immobile_options['property_types'];
$offer_types = $immobile_options['offer_types'];
?>

<div class="form-group">
    <label for="offer_type">Tipo de Oferta:</label><br>
    <select name="offer_type" id="offer_type">
        <?php foreach ($offer_types as $offer_type) : ?>
            <?php if (get_post_meta($post->ID, 'offer_type', true) == $offer_type) : ?>
                <option selected value="<?php echo $offer_type ?>"><?php echo $offer_type; ?></option>
            <?php else : ?>
                <option value="<?php echo $offer_type ?>"><?php echo $offer_type; ?></option>
            <?php endif; ?>
        <?php endforeach; ?>
    </select>
</div>

<div class="wrap">
    <div class="broker-section">
        <label>Corretores Associados:</label><br>
        <div class="broker-list">
            <?php foreach ($brokers as $broker) : ?>
                <div class="broker-item">
                    <div class="broker-checkboxes">
                        <input type="checkbox" 
                               name="brokers[]" 
                               value="<?php echo $broker->ID; ?>"
                               <?php checked(isset($broker_associations[$broker->ID])); ?>>
                        
                        <input type="checkbox" 
                               name="sponsor_brokers[]" 
                               value="<?php echo $broker->ID; ?>"
                               <?php checked(!empty($broker_associations[$broker->ID]) && $broker_associations[$broker->ID] == 1); ?>
                               class="sponsor-checkbox">
                    </div>
                    <label><?php echo $broker->display_name; ?></label>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="description">Primeiro checkbox: associar corretor | Segundo checkbox: patrocínio</p>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="location">Localidade:</label><br>
            <select name="location" id="location">
                <?php foreach ($locations as $location) : ?>
                    <?php if (get_post_meta($post->ID, 'location', true) == $location->name) : ?>
                        <option selected value="<?php echo $location->name ?>"><?php echo $location->name; ?></option>
                    <?php else : ?>
                        <option value="<?php echo $location->name ?>"><?php echo $location->name; ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="property_type">Tipo de Imóvel:</label><br>
            <select name="property_type" id="property_type">
                <?php foreach ($property_types as $property_type) : ?>
                    <?php if (get_post_meta($post->ID, 'property_type', true) == $property_type) : ?>
                        <option selected value="<?php echo $property_type ?>"><?php echo $property_type; ?></option>
                    <?php else : ?>
                        <option value="<?php echo $property_type ?>"><?php echo $property_type; ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="condominium">Condomínio:</label><br>
            <select name="condominium" id="condominium">
                <?php foreach ($options as $option) : ?>
                    <?php if (get_post_meta($post->ID, 'condominium', true) == $option) : ?>
                        <option selected value="<?php echo $option ?>"><?php echo $option; ?></option>
                    <?php else : ?>
                        <option value="<?php echo $option ?>"><?php echo $option; ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="financing">Aceita Financiamento:</label><br>
            <select name="financing" id="financing">
                <?php foreach ($options as $option) : ?>
                    <?php if (get_post_meta($post->ID, 'financing', true) == $option) : ?>
                        <option selected value="<?php echo $option ?>"><?php echo $option; ?></option>
                    <?php else : ?>
                        <option value="<?php echo $option ?>"><?php echo $option; ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="bedrooms">Quartos:</label><br>
            <input type="number" name="bedrooms" id="bedrooms" value="<?php echo get_post_meta($post->ID, 'bedrooms', true); ?>">
        </div>

        <div class="form-group">
            <label for="size">Metragem:</label><br>
            <input type="number" name="size" id="size" value="<?php echo get_post_meta($post->ID, 'size', true); ?>">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="committee">Comissão:</label><br>
            <input type="number" name="committee" id="committee" value="<?php echo get_post_meta($post->ID, 'committee', true); ?>">
        </div>

        <div class="form-group">
            <label for="committee_socasatop">Comissão Só Casa Top:</label><br>
            <input type="number" name="committee_socasatop" id="committee_socasatop" value="<?php echo get_post_meta($post->ID, 'committee_socasatop', true); ?>">
        </div>
    </div>

    <div class="form-group">
        <label for="amount">Valor:</label><br>
        <input type="number" name="amount" id="amount" value="<?php echo get_post_meta($post->ID, 'amount', true); ?>">
    </div>

    <div class="form-group">
        <label for="details">Detalhes:</label><br>
        <textarea name="details" id="details" rows="4"><?php echo get_post_meta($post->ID, 'details', true); ?></textarea>
    </div>

    <div class="form-group">
        <label for="link">Link Externo:</label><br>
        <input type="url" name="link" id="link" value="<?php echo get_post_meta($post->ID, 'link', true); ?>">
    </div>

    <div class="form-group">
        <label for="facade">Tipo de Fachada:</label><br>
        <input type="text" name="facade" id="facade" value="<?php echo get_post_meta($post->ID, 'facade', true); ?>">
    </div>

    <div class="media-section">
        <div class="form-group">
            <label for="immobile_gallery">Galeria de Imagens</label><br>
            <input type="hidden" id="immobile_gallery" name="immobile_gallery" value="<?php echo esc_attr($gallery); ?>" />
            <button type="button" class="button" id="upload_gallery_button">Adicionar Imagens</button>
            <div id="gallery_preview" class="gallery-preview">
                <?php
                if ($gallery) {
                    $gallery_ids = explode(',', $gallery);
                    foreach ($gallery_ids as $id) {
                        $image = wp_get_attachment_image_src($id, 'thumbnail');
                        if ($image) {
                            echo '<div class="gallery-item">';
                            echo '<img src="' . esc_url($image[0]) . '" />';
                            echo '<button type="button" class="remove-image" data-id="' . $id . '">×</button>';
                            echo '</div>';
                        }
                    }
                }
                ?>
            </div>
        </div>

        <div class="form-group">
            <label for="immobile_videos">Vídeos do Imóvel</label><br>
            <textarea name="immobile_videos" id="immobile_videos" rows="4" class="large-text" placeholder="Cole os URLs dos vídeos do YouTube (um por linha)"><?php echo get_post_meta($post->ID, 'immobile_videos', true); ?></textarea>
            <p class="description">Insira as URLs do YouTube, uma por linha</p>
        </div>
    </div>
</div>

<style>
.wrap {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.broker-list {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #ddd;
    padding: 15px;
    margin: 10px 0;
    background: #f9f9f9;
    border-radius: 4px;
}

.broker-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    padding: 5px;
}

.broker-checkboxes {
    display: flex;
    gap: 10px;
    margin-right: 15px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.gallery-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.gallery-item {
    position: relative;
}

.gallery-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 4px;
}

.remove-image {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    line-height: 24px;
    text-align: center;
    cursor: pointer;
    padding: 0;
}

.media-section {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.broker-item input[name="brokers[]"]').on('change', function() {
        var sponsorCheckbox = $(this).closest('.broker-item').find('input[name="sponsor_brokers[]"]');
        if (!this.checked) {
            sponsorCheckbox.prop('checked', false);
        }
    });

    $('.broker-item input[name="sponsor_brokers[]"]').on('change', function() {
        var brokerCheckbox = $(this).closest('.broker-item').find('input[name="brokers[]"]');
        if (this.checked) {
            brokerCheckbox.prop('checked', true);
        }
    });

    var mediaUploader;
    $('#upload_gallery_button').on('click', function(e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Selecionar Imagens',
            button: {
                text: 'Usar estas imagens'
            },
            multiple: true
        });

        mediaUploader.on('select', function() {
            var attachments = mediaUploader.state().get('selection').map(
                attachment => {
                    return attachment.toJSON();
                }
            );

            var currentGallery = $('#immobile_gallery').val();
            var ids = currentGallery ? currentGallery.split(',') : [];

            attachments.forEach(attachment => {
                if (!ids.includes(attachment.id.toString())) {
                    ids.push(attachment.id);
                    $('#gallery_preview').append(`
                        <div class="gallery-item">
                            <img src="${attachment.sizes.thumbnail.url}" />
                            <button type="button" class="remove-image" data-id="${attachment.id}">×</button>
                        </div>
                    `);
                }
            });

            $('#immobile_gallery').val(ids.join(','));
        });

        mediaUploader.open();
    });

    $(document).on('click', '.remove-image', function() {
        var imageId = $(this).data('id');
        var currentIds = $('#immobile_gallery').val().split(',');
        var newIds = currentIds.filter(id => id != imageId);
        
        $('#immobile_gallery').val(newIds.join(','));
        $(this).closest('.gallery-item').remove();
    });
});