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
    
    // Adicionar metabox para destacar imóvel
    add_meta_box(
        'immobile_sponsored',
        'Imóvel Destacado',
        'render_sponsored_metabox',
        'immobile',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'add_immobile_metabox');

function render_sponsored_metabox($post) {
    wp_nonce_field('immobile_sponsored_nonce', 'immobile_sponsored_nonce');
    
    $is_sponsored = get_post_meta($post->ID, 'is_sponsored', true) === 'yes';
    ?>
    <div style="padding: 10px 0;">
        <label>
            <input type="checkbox" name="is_sponsored" value="yes" <?php checked($is_sponsored); ?> />
            Marcar como Imóvel Destacado
        </label>
        <p class="description">
            Imóveis destacados aparecem no carrossel de destaques e são priorizados nas buscas.
        </p>
    </div>
    <?php
}

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
    
    // Salvar campo de imóvel destacado
    if (isset($_POST['immobile_sponsored_nonce']) && 
        wp_verify_nonce($_POST['immobile_sponsored_nonce'], 'immobile_sponsored_nonce')) {
        
        $is_sponsored = isset($_POST['is_sponsored']) ? 'yes' : 'no';
        update_post_meta($post_id, 'is_sponsored', $is_sponsored);
    }
}
add_action('save_post_immobile', 'save_immobile_metabox');