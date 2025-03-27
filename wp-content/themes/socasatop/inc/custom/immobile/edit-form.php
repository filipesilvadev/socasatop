<?php
require_once(__DIR__ . '/config.php');
$immobile_options = get_immobile_options();

$args = [
    'role'    => 'author',
    'orderby' => 'display_name',
    'order'   => 'ASC'
];
$brokers_query = new WP_User_Query($args);
$brokers = $brokers_query->get_results();

$locations = get_terms([
    'taxonomy' => 'locations',
    'hide_empty' => false,
]);

// Usar as opções padronizadas do config.php
$options = $immobile_options['yes_no_options'];
$property_types = $immobile_options['property_types'];
$offer_types = $immobile_options['offer_types'];

// Verificar se o parâmetro post está definido
$id = isset($_GET['post']) ? intval($_GET['post']) : 0;
if ($id <= 0) {
    echo '<div class="error-message">Imóvel não encontrado. ID inválido.</div>';
    return;
}

// Verificar se o post existe e é do tipo 'immobile'
$post = get_post($id);
if (!$post || $post->post_type !== 'immobile') {
    echo '<div class="error-message">Imóvel não encontrado. O imóvel solicitado não existe ou foi removido.</div>';
    return;
}

$gallery = get_post_meta($id, 'immobile_gallery', true);
$videos = get_post_meta($id, 'immobile_videos', true);

wp_enqueue_script('jquery-ui-sortable');
?>

<form id="edit-immobile" method="post" class="form">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    
    <div class="form-wrapper">
        <label for="title">Nome:</label>
        <input type="text" name="title" id="title" required value="<?php echo get_the_title($id); ?>">
    </div>

    <div class="form-wrapper">
        <label for="facade">Tipo de Fachada:</label>
        <input type="text" name="facade" id="facade" required value="<?php echo get_post_meta($id, 'facade', true) ?>">
    </div>

    <div class="group-inputs">
        <div class="form-wrapper w-1/2">
            <label for="offer_type">Tipo de Oferta:</label>
            <select id="offer_type" name="offer_type" class="select2">
                <?php foreach ($offer_types as $offer_type) : ?>
                    <?php if (get_post_meta($id, 'offer_type', true) == $offer_type) : ?>
                        <option selected value="<?php echo $offer_type ?>"><?php echo $offer_type; ?></option>
                    <?php else : ?>
                        <option value="<?php echo $offer_type ?>"><?php echo $offer_type; ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-wrapper w-1/2">
            <label for="amount">Valor:</label>
            <input type="text" name="amount" id="amount" required value="<?php echo get_post_meta($id, 'amount', true) ?>">
        </div>
    </div>

    <div class="group-inputs">
        <div class="form-wrapper w-1/2">
            <label for="bedrooms">Quantos Quartos:</label>
            <input type="number" name="bedrooms" id="bedrooms" required value="<?php echo get_post_meta($id, 'bedrooms', true) ?>">
        </div>
        <div class="form-wrapper w-1/2">
            <label for="size">Metragem:</label>
            <input type="number" name="size" id="size" required value="<?php echo get_post_meta($id, 'size', true) ?>">
        </div>
    </div>

    <div class="group-inputs">
        <div class="form-wrapper w-1/2">
            <label for="committee">Comissão:</label>
            <input type="number" name="committee" id="committee" required value="<?php echo get_post_meta($id, 'committee', true) ?>">
        </div>
        <div class="form-wrapper w-1/2">
            <label for="committee_socasatop">Comissão So Casa Top:</label>
            <input type="number" name="committee_socasatop" id="committee_socasatop" required value="<?php echo get_post_meta($id, 'committee_socasatop', true) ?>">
        </div>
    </div>

    <div class="group-inputs">
        <div class="form-wrapper w-1/2">
            <label for="location">Localidade:</label>
            <select id="location" name="location" class="select2">
                <?php foreach ($locations as $location) : ?>
                    <?php if (get_post_meta($id, 'location', true) == $location->name) : ?>
                        <option selected value="<?php echo $location->name ?>"><?php echo $location->name; ?></option>
                    <?php else : ?>
                        <option value="<?php echo $location->name ?>"><?php echo $location->name; ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-wrapper w-1/2">
            <label for="property_type">Tipo de Imóvel:</label>
            <select id="property_type" name="property_type" class="select2">
                <?php foreach ($property_types as $property_type) : ?>
                    <?php if (get_post_meta($id, 'property_type', true) == $property_type) : ?>
                        <option selected value="<?php echo $property_type ?>"><?php echo $property_type; ?></option>
                    <?php else : ?>
                        <option value="<?php echo $property_type ?>"><?php echo $property_type; ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="group-inputs">
        <div class="form-wrapper w-1/2">
            <label for="condominium">Condomínio:</label>
            <select id="condominium" name="condominium" class="select2">
                <?php foreach ($options as $option) : ?>
                    <?php if (get_post_meta($id, 'condominium', true) == $option) : ?>
                        <option selected value="<?php echo $option ?>"><?php echo $option; ?></option>
                    <?php else : ?>
                        <option value="<?php echo $option ?>"><?php echo $option; ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-wrapper w-1/2">
            <label for="financing">Aceita Financiamento:</label>
            <select id="financing" name="financing" class="select2">
                <?php foreach ($options as $option) : ?>
                    <?php if (get_post_meta($id, 'financing', true) == $option) : ?>
                        <option selected value="<?php echo $option ?>"><?php echo $option; ?></option>
                    <?php else : ?>
                        <option value="<?php echo $option ?>"><?php echo $option; ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="group-inputs">
        <div class="form-wrapper w-1/2">
            <label for="broker">Corretor Responsável:</label>
            <select name="broker" id="broker" class="select2">
                <?php foreach ($brokers as $broker) : ?>
                    <?php if (get_post_meta($id, 'broker', true) == $broker->ID) : ?>
                        <option value="<?php echo $broker->ID ?>" selected><?php echo $broker->display_name; ?></option>
                    <?php else : ?>
                        <option value="<?php echo $broker->ID ?>"><?php echo $broker->display_name; ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-wrapper">
        <label for="details">Detalhes:</label>
        <textarea name="details" id="details"><?php echo get_post_meta($id, 'details', true) ?></textarea>
    </div>

    <div class="form-wrapper">
        <label for="link">Link:</label>
        <input type="url" name="link" id="link" value="<?php echo get_post_meta($id, 'link', true) ?>">
    </div>

    <div class="form-wrapper">
        <div class="form-wrapper">
            <label for="immobile_gallery" class="pb-2">Galeria de Imagens</label><br>
            <input type="hidden" id="immobile_gallery" name="immobile_gallery" value="<?php echo $gallery; ?>" />
            <button type="button" id="upload_gallery_button" class="btn btn-info">Adicionar Imagens</button>
        </div>
        <div id="gallery_preview" class="rounded-md border-2 border-dotted border-[#3858e9] p-2">
            <?php
            if ($gallery) {
                $gallery_ids = explode(',', $gallery);
                foreach ($gallery_ids as $image_id) {
                    if (!empty($image_id)) {
                        $image = wp_get_attachment_image_src($image_id, 'thumbnail');
                        if ($image) {
                            ?>
                            <div class="gallery-image" data-id="<?php echo $image_id; ?>">
                                <img src="<?php echo $image[0]; ?>" alt="">
                                <span class="remove-image">×</span>
                                <label class="make-featured">
                                    <input type="radio" name="featured_image" value="<?php echo $image_id; ?>" <?php checked(strpos($gallery, $image_id.',') === 0 || $gallery === $image_id, true); ?>> Capa
                                </label>
                            </div>
                            <?php
                        }
                    }
                }
            }
            ?>
        </div>
        <p class="description mt-2">Arraste as imagens para reordenar. Passe o mouse sobre uma imagem para definir como capa.</p>
    </div>

    <div class="form-wrapper mt-4">
        <div class="form-wrapper">
            <label for="immobile_videos" class="pb-2">Vídeos do Imóvel</label><br>
            <input type="hidden" id="immobile_videos" name="immobile_videos" value="<?php echo isset($videos) ? $videos : ''; ?>" />
            <button type="button" id="upload_videos_button" class="btn btn-info">Adicionar Vídeos</button>
        </div>
        <div id="videos_preview" class="rounded-md border-2 border-dotted border-[#3858e9] p-2">
            <?php
            if (isset($videos) && !empty($videos)) {
                $video_ids = explode(',', $videos);
                foreach ($video_ids as $video_id) {
                    if (!empty($video_id)) {
                        $video_url = wp_get_attachment_url($video_id);
                        if ($video_url) {
                            ?>
                            <div class="video-preview" data-id="<?php echo $video_id; ?>">
                                <video width="200" controls>
                                    <source src="<?php echo $video_url; ?>" type="video/mp4">
                                    Seu navegador não suporta o elemento de vídeo.
                                </video>
                                <button type="button" class="remove-video">×</button>
                            </div>
                            <?php
                        }
                    }
                }
            }
            ?>
        </div>
        <p class="description mt-2">Arraste os vídeos para reordenar. Formatos aceitos: MP4, WebM.</p>
    </div>

    <button type="submit" class="btn btn-info">
        Editar Imóvel
    </button>
</form>

<style>
    .gallery-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
    .gallery-image {
        position: relative;
        margin: 10px;
        cursor: move;
    }
    .gallery-image img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 4px;
    }
    .remove-image {
        position: absolute;
        top: 5px;
        right: 5px;
        background: red;
        color: white;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 5;
    }
    .make-featured {
        position: absolute;
        bottom: 5px;
        left: 5px;
        background: rgba(0,0,0,0.6);
        color: white;
        padding: 3px 6px;
        border-radius: 3px;
        font-size: 10px;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .gallery-image:hover .make-featured {
        opacity: 1;
    }
    .video-preview {
        position: relative;
        margin: 10px;
        cursor: move;
    }
    .video-preview video {
        width: 200px;
        border-radius: 4px;
    }
    .remove-video {
        position: absolute;
        top: 5px;
        right: 5px;
        background: red;
        color: white;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 5;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // Tornar galeria de imagens ordenável
        $('#gallery_preview').sortable({
            update: function(event, ui) {
                updateGalleryOrder();
            }
        });

        // Tornar lista de vídeos ordenável
        $('#videos_preview').sortable({
            update: function(event, ui) {
                updateVideoIds();
            }
        });

        function updateGalleryOrder() {
            var imageIds = [];
            $('#gallery_preview .gallery-image').each(function() {
                imageIds.push($(this).data('id'));
            });
            $('#immobile_gallery').val(imageIds.join(','));
        }

        function updateVideoIds() {
            var videoIds = [];
            $('#videos_preview .video-preview').each(function() {
                videoIds.push($(this).data('id'));
            });
            $('#immobile_videos').val(videoIds.join(','));
        }

        $('#upload_gallery_button').on('click', function() {
            var mediaUploader = wp.media({
                title: 'Selecionar Imagens',
                button: { text: 'Usar estas imagens' },
                multiple: true,
                library: { type: 'image' }
            });

            mediaUploader.on('select', function() {
                var attachments = mediaUploader.state().get('selection').map(
                    function(attachment) { return attachment.toJSON(); }
                );
                var currentIds = $('#immobile_gallery').val() ? $('#immobile_gallery').val().split(',') : [];
                
                attachments.forEach(function(attachment) {
                    if (currentIds.indexOf(attachment.id.toString()) === -1) {
                        currentIds.push(attachment.id);
                        $('#gallery_preview').append(`
                            <div class="gallery-image" data-id="${attachment.id}">
                                <img src="${attachment.sizes.thumbnail.url}" alt="">
                                <span class="remove-image">×</span>
                                <label class="make-featured">
                                    <input type="radio" name="featured_image" value="${attachment.id}"> Capa
                                </label>
                            </div>
                        `);
                    }
                });
                
                $('#immobile_gallery').val(currentIds.join(','));
            });

            mediaUploader.open();
        });

        $('#upload_videos_button').on('click', function() {
            var videoUploader = wp.media({
                title: 'Selecionar Vídeos',
                button: { text: 'Usar estes vídeos' },
                multiple: true,
                library: { type: 'video' }
            });

            videoUploader.on('select', function() {
                var attachments = videoUploader.state().get('selection').map(
                    function(attachment) { return attachment.toJSON(); }
                );
                var currentIds = $('#immobile_videos').val() ? $('#immobile_videos').val().split(',') : [];
                
                attachments.forEach(function(attachment) {
                    if (currentIds.indexOf(attachment.id.toString()) === -1) {
                        currentIds.push(attachment.id);
                        $('#videos_preview').append(`
                            <div class="video-preview" data-id="${attachment.id}">
                                <video width="200" controls>
                                    <source src="${attachment.url}" type="video/mp4">
                                    Seu navegador não suporta o elemento de vídeo.
                                </video>
                                <button type="button" class="remove-video">×</button>
                            </div>
                        `);
                    }
                });
                
                $('#immobile_videos').val(currentIds.join(','));
            });

            videoUploader.open();
        });

        $(document).on('click', '.remove-image', function() {
            var $item = $(this).closest('.gallery-image');
            var imageId = $item.data('id');
            var currentIds = $('#immobile_gallery').val().split(',');
            var newIds = currentIds.filter(function(id) { return id != imageId; });
            
            $('#immobile_gallery').val(newIds.join(','));
            $item.remove();
        });

        $(document).on('click', '.remove-video', function() {
            var $item = $(this).closest('.video-preview');
            var videoId = $item.data('id');
            var currentIds = $('#immobile_videos').val().split(',');
            var newIds = currentIds.filter(function(id) { return id != videoId; });
            
            $('#immobile_videos').val(newIds.join(','));
            $item.remove();
        });

        $(document).on('change', 'input[name="featured_image"]', function() {
            var featuredId = $(this).val();
            var currentIds = $('#immobile_gallery').val().split(',');
            
            // Remover o ID da imagem de capa da lista
            currentIds = currentIds.filter(function(id) { return id != featuredId; });
            
            // Adicionar o ID da imagem de capa no início
            currentIds.unshift(featuredId);
            
            // Atualizar o campo oculto
            $('#immobile_gallery').val(currentIds.join(','));
        });
    });
</script>