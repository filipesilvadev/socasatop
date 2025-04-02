<?php
$params = $_GET;

$args = [
    'post_type' => 'immobile',
    'posts_per_page' => -1,
];
$args['meta_query'] = ['relation' => 'AND',];

foreach ($params as $param => $value) {
    if (!empty($value)) {
        switch ($param) {
            case 'title':
                $args['s'] = $value;
                break;

            case 'amount_min':
                $value = str_replace('.', '', $value);
                $args['meta_query'][] = [
                    'key' => 'amount',
                    'value' => intval($value),
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                ];
                break;

            case 'amount_max':
                $value = str_replace('.', '', $value);
                $args['meta_query'][] = [
                    'key' => 'amount',
                    'value' => intval($value),
                    'compare' => '<=',
                    'type' => 'NUMERIC'
                ];
                break;

            case 'location':
                $index = count($args['meta_query']) - 1;
                $args['meta_query'][$index] = [
                    'relation' => 'OR',
                ];
                if(is_array($value)){
                    foreach ($value as $location) {
                        $args['meta_query'][$index][] = [
                            'key' => $param,
                            'value' => $location,
                            'compare' => '='
                        ];
                    }
                }else{
                    $args['meta_query'][$index][] = [
                        'key' => $param,
                        'value' => $value,
                        'compare' => '='
                    ];
                }
                break;
            
            case 'min_size':
                $args['meta_query'][] = [
                    'key' => 'size',
                    'value' => $value,
                    'compare' => '>='
                ];
                break;
            case 'max_size':
                $args['meta_query'][] = [
                    'key' => 'size',
                    'value' => $value,
                    'compare' => '<='
                ];
                break;

            default:
                if ($value != "Indiferente") {
                    $args['meta_query'][] = [
                        'key' => $param,
                        'value' => $value,
                        'compare' => '='
                    ];
                }
                break;
        }
    }
}
$immobile = new WP_Query($args);
?>
<?php if ($immobile->have_posts()) : ?>
    <div class="grid-immobile">
        <?php
        while ($immobile->have_posts()) {
            $immobile->the_post();
            
            // Verificar se existe um template específico no Elementor pelo ID
            $template_id = 1435; // ID do template do Elementor
            
            if (function_exists('elementor_theme_do_location') && has_action('elementor/theme/archive')) {
                do_action('elementor/theme/archive');
            } else {
                echo do_shortcode('[elementor-template id="' . $template_id . '"]');
            }
        }
        ?>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $(".total-immobile span").text("<?php echo $immobile->found_posts; ?>");
        });
    </script>
<?php endif; ?>