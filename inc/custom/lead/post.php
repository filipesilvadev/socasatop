<?php

function lead_post()
{
    register_post_type(
        'lead',
        array(
            'labels' => array(
                'name' => "Leads",
                'singular_name' => "Lead"
            ),
            'public' => true,
            'supports' => array('title'),
            'menu_icon' => 'dashicons-groups',
        )
    );
}
add_action('init', 'lead_post');

function lead_fields_meta_box()
{
    add_meta_box(
        'lead_fields_meta_box',
        'Dados Lead',
        'lead_fields_meta_box_callback',
        'lead',
        'normal',
        'high'
    );
}

function lead_fields_meta_box_callback($post)
{
    include_once __DIR__ . '/fields.php';
}
add_action('add_meta_boxes', 'lead_fields_meta_box');

function save_lead_field_meta($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields_to_save = array(
        'phone',
        'email',
        'condominium',
        'financing',
        'bedrooms',
        'location',
        'property_type',
        'amount',
        'status',
        'facade',
        'details'
    );

    foreach ($fields_to_save as $field_name) {
        if (isset($_POST[$field_name])) {
            $value =  $_POST[$field_name];

            if ($field_name == "amount") {
                $value = str_replace('.', '', $value);
            }

            if ($field_name == "facade") {
                $value = strtolower($value);
                $value = ucwords($value);
            }

            if ($field_name == "location") {
                $locations = [];
                foreach ($_POST['location'] as $location) {
                    array_push($locations, $location);
                }
                update_post_meta(
                    $post_id,
                    "$field_name",
                    implode(',', $locations)
                );
                continue;
            }

            update_post_meta(
                $post_id,
                "$field_name",
                $value
            );
        }
    }
}
add_action('save_post_lead', 'save_lead_field_meta');

function display_leads()
{
    $post_id = get_the_ID();
    $amount = intval(get_post_meta($post_id, 'amount', true));
    $amount_percent = $amount * 0.2;
    $args = [
        'post_type' => 'lead',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'amount',
                'value' => $amount + $amount_percent,
                'compare' => '<=',
                'type' => 'NUMERIC'
            ],
            [
                'key' => 'amount',
                'value' => $amount - $amount_percent,
                'compare' => '>=',
                'type' => 'NUMERIC'
            ],
            [
                'relation' => 'OR',
                [
                    'key' => 'location',
                    'value' => get_post_meta($post_id, 'location', true),
                    'compare' => 'LIKE'
                ],
                [
                    'key' => 'location',
                    'value' => '',
                    'compare' => '='
                ],
            ],
            [
                'key' => 'bedrooms',
                'value' => get_post_meta($post_id, 'bedrooms', true),
                'compare' => '='
            ],
            [
                'key' => 'facade',
                'value' => get_post_meta($post_id, 'facade', true),
                'compare' => '='
            ],
            [
                'relation' => 'OR',
                [
                    'key' => 'condominium',
                    'value' => get_post_meta($post_id, 'condominium', true),
                    'compare' => '='
                ],
                [
                    'key' => 'condominium',
                    'value' => "Indiferente",
                    'compare' => '='
                ],
            ],
            [
                'relation' => 'OR',
                [
                    'key' => 'financing',
                    'value' => get_post_meta($post_id, 'financing', true),
                    'compare' => '='
                ],
                [
                    'key' => 'financing',
                    'value' => "Indiferente",
                    'compare' => '='
                ],
            ]
        ]
    ];

    $leads = new WP_Query($args);
?>
    <ul class="leads">
        <?php
        while ($leads->have_posts()) {
            $leads->the_post();
        ?>
            <li>
                <a href="<?php the_permalink(); ?>">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="25" height="25" viewBox="0,0,256,256">
                        <g fill="#3756e4" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal">
                            <g transform="scale(5.12,5.12)">
                                <path d="M47,11h-21v-1c0,-1.65234 -1.34766,-3 -3,-3h-20c-1.65234,0 -3,1.34766 -3,3v32c0,1.65234 1.34766,3 3,3h6v-3h4v3h24v-3h4v3h6c1.65234,0 3,-1.34766 3,-3v-28c0,-1.65234 -1.34766,-3 -3,-3zM6,34c0.20703,-3.57812 4.98438,-3.05859 5.64844,-4.91406c0.05469,-0.63672 0.03516,-1.07812 0.03516,-1.66016c-0.27734,-0.14844 -0.79297,-1.10937 -0.875,-1.92578c-0.21875,-0.01562 -0.55859,-0.23828 -0.66016,-1.10937c-0.05469,-0.46875 0.16016,-0.73047 0.28906,-0.8125c-0.73437,-2.94531 -0.33203,-5.51562 3.02734,-5.57812c0.83984,0 1.48438,0.23438 1.73438,0.69531c2.45313,0.35156 1.71484,3.77734 1.36328,4.88281c0.12891,0.08203 0.34375,0.34766 0.28906,0.8125c-0.09766,0.875 -0.44141,1.09375 -0.66016,1.11328c-0.08594,0.8125 -0.57812,1.77344 -0.85547,1.92578c0,0.57813 -0.01953,1.01953 0.03516,1.65625c0.66406,1.85547 5.42188,1.33594 5.62891,4.91406zM44,34h-18v-2h18zM44,29h-18v-2h18zM44,24h-18v-2h18z"></path>
                            </g>
                        </g>
                    </svg>
                    <?php the_title(); ?>
                </a>
            </li>
        <?php
        }
        ?>
    </ul>
<?php
    wp_reset_postdata();
}
add_shortcode('leads', 'display_leads');


function display_form_lead()
{
    ob_start();
    require_once(__DIR__ . "/form.php");
    $content = ob_get_clean();
    return $content;
}
add_shortcode('form_lead', 'display_form_lead');

function display_edit_form_lead()
{
    ob_start();
    require_once(__DIR__ . "/edit-form.php");
    $content = ob_get_clean();
    return $content;
}
add_shortcode('edit_form_lead', 'display_edit_form_lead');

function display_link_edit_lead()
{
    return home_url('/editar-lead/') . "?post=" . get_the_ID();
}
add_shortcode('link_edit_lead', 'display_link_edit_lead');

function display_link_lead_immobile()
{
    $amount = get_post_meta(get_the_ID(), 'amount', true) ?: 0;
    $amount = str_replace('.', '', $amount);
    $amount = intval($amount);


    $query_params = http_build_query(array(
        'amount_min' => $amount - ($amount * 0.2),
        'amount_max' => $amount + ($amount * 0.2),
        'financing' => get_post_meta(get_the_ID(), 'financing', true) ?: '',
        'condominium' => get_post_meta(get_the_ID(), 'condominium', true) ?: '',
        'location' => get_post_meta(get_the_ID(), 'location', true) ?: '',
        'bedrooms' => get_post_meta(get_the_ID(), 'bedrooms', true) ?: '',
        'facade' => get_post_meta(get_the_ID(), 'facade', true) ?: '',
    ));

    return home_url("/?$query_params");
}
add_shortcode('link_lead_immobile', 'display_link_lead_immobile');
