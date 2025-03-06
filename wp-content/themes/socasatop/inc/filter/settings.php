<?php

function display_form_filter()
{
    ob_start();
    require_once(__DIR__ . "/form.php");
    $content = ob_get_clean();
    return $content;
}
add_shortcode('form_filter', 'display_form_filter');


function display_filter_immobile()
{
    ob_start();
    require_once(__DIR__ . "/filter-immobile.php");
    $content = ob_get_clean();
    return $content;
}
add_shortcode('filter_immobile', 'display_filter_immobile');

function display_list_immobile()
{
    ob_start();
    require_once(__DIR__ . "/list-immobile.php");
    $content = ob_get_clean();
    return $content;
}
add_shortcode('list_immobile', 'display_list_immobile');

function display_image_immobile()
{
    $gallery = get_post_meta(get_the_ID(), 'immobile_gallery', true);

    if (!empty($gallery)) {
        $gallery = explode(',', $gallery)[0];
        $type = get_post_mime_type($gallery);

        if ($type == "application/pdf") {
            $content = '<img src="' . get_stylesheet_directory_uri() . '/wp-content/uploads/2025/02/no-image.png" alt="No Image">';
        } else if (in_array($type, ['video/mp4', 'video/mpeg'])) {
            $video_url = wp_get_attachment_url($gallery);
            $content = '<video controls><source src="' . esc_url($video_url) . '" type="' . esc_attr($type) . '"></video>';
        } else {
            $content = wp_get_attachment_image($gallery, 'full');
        }
    } else {
        $content = '<img src="' . get_stylesheet_directory_uri() . '/wp-content/uploads/2025/02/no-image.png" alt="No Image">';
    }

    return $content;
}
add_shortcode('image_immobile', 'display_image_immobile');

function display_link_immobile()
{
    if (is_user_logged_in()) {
        $link = get_the_permalink();
    } else {
        $link = home_url('/imovel/?id=' . get_the_ID());
    }
    return $link;
}
add_shortcode('link_immobile', 'display_link_immobile');


function display_show_meta($atts)
{
    $attributes = shortcode_atts(array(
        'key' => ''
    ), $atts);
    $id = isset($_GET['id']) ? $_GET['id'] : get_the_ID();

    return get_post_meta($id, $attributes['key'], true);
}
add_shortcode('show_meta', 'display_show_meta');

function display_show_title_immobile()
{
    $id = isset($_GET['id']) ? $_GET['id'] : get_the_ID();
    return get_the_title($id);
}
add_shortcode('show_title_immobile', 'display_show_title_immobile');

function display_attachments_immobile()
{
    $id = isset($_GET['id']) ? $_GET['id'] : get_the_ID();
    $gallery = get_post_meta($id, 'immobile_gallery', true);
?>
    <script>
        //const swiperSelector = "#image-immobile .elementor-main-swiper";
        const swiperSelector = "#image-immobile .swiper";
        jQuery(`${swiperSelector} .swiper-slide`).remove();

        function addImageToCarousel(url, carouselSelector) {
            const carousel = jQuery(carouselSelector);

            if (!carousel) {
                return;
            }

            const newSlide = jQuery('<div>').addClass('swiper-slide');

            const isVideo = url.match(/\.(mp4|webm|ogg)$/i);
            if (isVideo) {
                const anchor = jQuery('<a>', {
                    href: url,
                    'data-elementor-open-lightbox': "yes",
                    'data-elementor-lightbox-slideshow': "bba9b31",
                });
                const video = jQuery('<video>', {
                    src: url,
                    controls: true,
                    alt: 'Video',
                    css: {
                        height: '100%',
                        'object-fit': 'cover',
                        width: '100%'
                    }
                });
                anchor.append(video);
                newSlide.append(anchor);
            } else {
                const img = jQuery('<img>', {
                    src: url,
                    alt: 'Image',
                    css: {
                        height: '100%',
                        'object-fit': 'cover',
                        width: '100%'
                    }
                });
                const anchor = jQuery('<a>', {
                    href: url,
                    'data-elementor-open-lightbox': "yes",
                    'data-elementor-lightbox-slideshow': "bba9b31",
                });
                const figure = jQuery('<figure>', {
                    class: 'swiper-slide-inner'
                });

                figure.append(img);
                anchor.append(figure);

                newSlide.append(anchor);
            }

            carousel.find('.swiper-wrapper').append(newSlide);

            if (carousel[0].swiper) {
                carousel[0].swiper.update();
            }
        }
        <?php

        if (!empty($gallery)) {
            $gallery = explode(',', $gallery);

            foreach ($gallery as $image) {
                $type = get_post_mime_type($image);
                $url = wp_get_attachment_url($image);
                if ($type == "application/pdf") { ?>
                    jQuery("#image-immobile").parent().append(`
                    <a href="<?php echo $url; ?>" class="btn-primary pdf">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="26" height="26" viewBox="0,0,256,256"><g fill="#FFFFFF" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal"><g transform="scale(5.12,5.12)"><path d="M7,2v46h36v-32.56641l-13.83203,-13.43359zM29,4l12,12h-12zM23.76953,19.94141c0.60547,0 1.13672,0.25391 1.49609,0.71875c0.76172,0.99219 0.71094,3.01563 -0.15625,6.01953c0.94531,1.60547 2.13672,3.0625 3.35547,4.09766c1.32813,-0.27344 2.46875,-0.41016 3.39063,-0.41016c2.78906,0 3.09375,1.39453 3.09375,1.99609c-0.00391,1.92969 -2.06641,1.92969 -2.84375,1.92969c-1.33203,0 -2.71875,-0.51172 -4.12109,-1.52734c-2.04297,0.48438 -4.32422,1.22656 -6.35937,2.07422c-2.58984,4.44141 -4.16797,4.44141 -4.69141,4.44141c-0.32031,0 -0.62891,-0.07422 -0.91406,-0.22266c-0.90625,-0.47266 -1.01953,-1.16406 -1.00391,-1.52734c0.01953,-0.46875 0.07422,-1.87109 5.26172,-4.125c1.08594,-1.96875 2.11328,-4.35156 2.79688,-6.51562c-0.78906,-1.48047 -2.08203,-4.38672 -1.08984,-6.01172c0.37109,-0.60156 1.00391,-0.9375 1.78516,-0.9375zM23.74219,21.75391c-0.0625,0.02734 -0.10547,0.04297 -0.11719,0.04688c-0.14844,0.13281 -0.19141,0.85547 0.1875,2.05859c0.20703,-1.37891 0.04688,-1.98437 -0.07031,-2.10547zM24.40234,29.03516l-0.04297,0.15234l-0.05078,-0.08203c-0.39453,1.05469 -0.84766,2.125 -1.32812,3.14844l0.15625,-0.06641l-0.07031,0.12109c1.02344,-0.36328 2.06641,-0.69141 3.07813,-0.96875l-0.05078,-0.04297l0.16016,-0.03516c-0.64844,-0.66406 -1.27734,-1.41797 -1.85156,-2.22656zM31.85547,32.20313c-0.27734,0 -0.58203,0.01953 -0.91797,0.04688c0.40625,0.13281 0.79688,0.20313 1.16797,0.20313c0.49609,0 0.75781,-0.03906 0.89453,-0.07422c-0.11719,-0.07031 -0.44141,-0.17578 -1.14453,-0.17578zM18.06641,36.625c-0.51562,0.33203 -0.82812,0.60156 -1,0.78516c0.19922,-0.07031 0.53906,-0.29297 1,-0.78516z"></path></g></g></svg>
                        Ver PDF
                    </a>`);
                    addImageToCarousel("<?php echo get_stylesheet_directory_uri(); ?>/wp-content/uploads/2025/02/no-image.png", swiperSelector);
                <?php
                } else { ?>
                    addImageToCarousel("<?php echo $url; ?>", swiperSelector);
            <?php
                }
            }
        } else { ?>
            addImageToCarousel("<?php echo get_stylesheet_directory_uri(); ?>/wp-content/uploads/2025/02/no-image.png", swiperSelector);
        <?php
        }
        ?>
    </script>
<?php
    return "";
}
add_shortcode('attachments_immobile', 'display_attachments_immobile');
