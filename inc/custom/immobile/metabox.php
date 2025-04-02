<?php
require_once(__DIR__ . '/config.php');

function add_immobile_metabox() {
    add_meta_box(
        'immobile_details',
        'Detalhes do Imóvel',
        'render_immobile_metabox',
        'immobile',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_immobile_metabox');

function render_immobile_metabox($post) {
    wp_nonce_field('immobile_metabox_nonce', 'immobile_metabox_nonce');
    
    // Buscar configurações padronizadas
    $immobile_options = get_immobile_options();
    
    // Buscar localidades
    $locations = get_terms([
        'taxonomy' => 'locations',
        'hide_empty' => false,
    ]);

    // Array com a configuração dos campos
    $fields = [
        // Campos Select
        'select_fields' => [
            'offer_type' => [
                'label' => 'Tipo de Oferta',
                'options' => $immobile_options['offer_types']
            ],
            'location' => [
                'label' => 'Localidade',
                'options' => array_map(function($term) { return $term->name; }, $locations)
            ],
            'property_type' => [
                'label' => 'Tipo de Imóvel',
                'options' => $immobile_options['property_types']
            ],
            'condominium' => [
                'label' => 'Condomínio',
                'options' => $immobile_options['yes_no_options']
            ],
            'financing' => [
                'label' => 'Aceita Financiamento',
                'options' => $immobile_options['yes_no_options']
            ]
        ],
        // Campos Número
        'number_fields' => [
            'bedrooms' => 'Quartos',
            'size' => 'Metragem',
            'committee' => 'Comissão',
            'committee_socasatop' => 'Comissão SoCasaTop',
            'amount' => 'Valor'
        ],
        // Campos Texto
        'text_fields' => [
            'facade' => 'Tipo de Fachada',
            'link' => 'Link Externo'
        ],
        // Campo Textarea
        'textarea_fields' => [
            'details' => 'Detalhes'
        ]
    ];
    
    echo '<div class="immobile-fields-container">';

    // Renderizar campos Select
    foreach ($fields['select_fields'] as $field_id => $field) {
        $current_value = get_post_meta($post->ID, $field_id, true);
        ?>
        <div class="immobile-field">
            <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($field['label']); ?>:</label>
            <select name="<?php echo esc_attr($field_id); ?>" id="<?php echo esc_attr($field_id); ?>" class="widefat">
                <?php foreach ($field['options'] as $option) : ?>
                    <option value="<?php echo esc_attr($option); ?>" <?php selected($current_value, $option); ?>>
                        <?php echo esc_html($option); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    // Renderizar campos Número
    foreach ($fields['number_fields'] as $field_id => $label) {
        $value = get_post_meta($post->ID, $field_id, true);
        ?>
        <div class="immobile-field">
            <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($label); ?>:</label>
            <input type="number" name="<?php echo esc_attr($field_id); ?>" id="<?php echo esc_attr($field_id); ?>" 
                   value="<?php echo esc_attr($value); ?>" class="widefat">
        </div>
        <?php
    }

    // Renderizar campos Texto
    foreach ($fields['text_fields'] as $field_id => $label) {
        $value = get_post_meta($post->ID, $field_id, true);
        ?>
        <div class="immobile-field">
            <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($label); ?>:</label>
            <input type="text" name="<?php echo esc_attr($field_id); ?>" id="<?php echo esc_attr($field_id); ?>" 
                   value="<?php echo esc_attr($value); ?>" class="widefat">
        </div>
        <?php
    }

    // Renderizar campos Textarea
    foreach ($fields['textarea_fields'] as $field_id => $label) {
        $value = get_post_meta($post->ID, $field_id, true);
        ?>
        <div class="immobile-field full-width">
            <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($label); ?>:</label>
            <textarea name="<?php echo esc_attr($field_id); ?>" id="<?php echo esc_attr($field_id); ?>" 
                      rows="4" class="widefat"><?php echo esc_textarea($value); ?></textarea>
        </div>
        <?php
    }
    
    // Campo para desautorizar publicação em redes sociais
    $disable_social_sharing = get_post_meta($post->ID, 'disable_social_sharing', true);
    ?>
    <div class="immobile-field full-width not-recommended-wrapper">
        <label class="not-recommended-label">
            <input type="checkbox" name="disable_social_sharing" id="disable_social_sharing" value="1" <?php checked($disable_social_sharing, '1'); ?>>
            Desautorizar publicação nas redes sociais <span class="not-recommended-tag">(Não recomendado)</span>
        </label>
    </div>
    <?php

    echo '</div>';
    
    // Estilos CSS
    ?>
    <style>
    .immobile-fields-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        padding: 15px;
    }

    .immobile-field {
        display: flex;
        flex-direction: column;
    }

    .immobile-field.full-width {
        grid-column: 1 / -1;
    }

    .immobile-field label {
        margin-bottom: 5px;
        font-weight: 600;
    }

    .immobile-field input,
    .immobile-field select,
    .immobile-field textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .not-recommended-wrapper {
        margin-top: 20px;
        padding: 10px;
        border: 1px solid #ffcccc;
        background-color: #fff5f5;
        border-radius: 5px;
    }
    
    .not-recommended-label {
        display: flex;
        align-items: center;
        color: #d32f2f;
        font-weight: bold;
    }
    
    .not-recommended-label input[type="checkbox"] {
        width: auto;
        margin-right: 10px;
    }
    
    .not-recommended-tag {
        margin-left: 5px;
        font-size: 0.8em;
        background-color: #d32f2f;
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
    }
    </style>
    <?php
}

function save_immobile_metabox($post_id) {
    if (!isset($_POST['immobile_metabox_nonce']) || 
        !wp_verify_nonce($_POST['immobile_metabox_nonce'], 'immobile_metabox_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $fields = array(
        'offer_type', 'location', 'property_type', 'condominium', 
        'financing', 'bedrooms', 'size', 'committee', 'committee_socasatop',
        'amount', 'details', 'link', 'facade'
    );

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
    
    // Salvar campo de desautorização de redes sociais
    update_post_meta($post_id, 'disable_social_sharing', isset($_POST['disable_social_sharing']) ? '1' : '0');
}
add_action('save_post_immobile', 'save_immobile_metabox');