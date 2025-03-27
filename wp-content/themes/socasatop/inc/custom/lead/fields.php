<?php
$options_status = [
    'Aguardando Contato',
    'Em Contato',
];
$options = ['Sim', 'Não', "Indiferente"];

// Buscar localizações para o dropdown
$locations = get_terms(array(
    'taxonomy' => 'locations',
    'hide_empty' => false,
));

$property_types = ['Sobrado', 'Térreo'];

// Obter dados do imóvel associado, se existir
$immobile_id = get_post_meta($post->ID, 'immobile_id', true);
$immobile_location = '';
$immobile_price = '';

if ($immobile_id) {
    // Pegar localizações do imóvel
    $immobile_terms = wp_get_post_terms($immobile_id, 'locations');
    if (!empty($immobile_terms) && !is_wp_error($immobile_terms)) {
        $immobile_location = $immobile_terms[0]->name;
    }
    
    // Pegar preço do imóvel
    $immobile_price = get_post_meta($immobile_id, 'price', true);
}
?>
<div class="wrap">
    <div>
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
    <div>
        <label for="status">Status:</label><br>
        <select name="status" id="status">
            <?php foreach ($options_status as $status) : ?>
                <?php if (get_post_meta($post->ID, 'status', true) == $status) : ?>
                    <option selected value="<?php echo $status; ?>"><?php echo $status; ?></option>
                <?php else : ?>
                    <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="financing">Deseja Financiar:</label><br>
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
    <div>
        <label for="condominium">Em Condomínio:</label><br>
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
    <div>
        <label for="location">Localidade:</label><br>
        <select name="location" id="location">
            <option value="">Selecione uma localização</option>
            <?php foreach ($locations as $location) : ?>
                <?php 
                $saved_location = get_post_meta($post->ID, 'location', true);
                $selected = ($saved_location == $location->name || (!$saved_location && $immobile_location == $location->name)) ? 'selected' : '';
                ?>
                <option value="<?php echo esc_attr($location->name); ?>" <?php echo $selected; ?>>
                    <?php echo esc_html($location->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($immobile_location && !get_post_meta($post->ID, 'location', true)) : ?>
            <p class="description">Localização extraída do imóvel: <?php echo esc_html($immobile_location); ?></p>
        <?php endif; ?>
    </div>
    <div>
        <label for="bedrooms">Quartos:</label><br>
        <input type="number" name="bedrooms" id="bedrooms" value="<?php echo get_post_meta($post->ID, 'bedrooms', true); ?>">
    </div>
    <div>
        <label for="email">e-mail:</label><br>
        <input type="email" name="email" id="email" value="<?php echo get_post_meta($post->ID, 'email', true); ?>">
    </div>
    <div>
        <label for="phone">Telefone:</label><br>
        <input type="tel" name="phone" id="phone" value="<?php echo get_post_meta($post->ID, 'phone', true); ?>">
    </div>
    <div>
        <label for="facade">Tipo de Fachada:</label><br>
        <input type="text" name="facade" id="facade" value="<?php echo get_post_meta($post->ID, 'facade', true); ?>">
    </div>
    <div>
        <label for="immobile_id">Imóvel Relacionado:</label><br>
        <select name="immobile_id" id="immobile_id">
            <option value="">Selecione um imóvel</option>
            <?php 
            $immobiles = get_posts([
                'post_type' => 'immobile',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
            ]);
            
            foreach ($immobiles as $immobile) : 
                $selected = selected($immobile_id, $immobile->ID, false);
            ?>
                <option value="<?php echo $immobile->ID; ?>" <?php echo $selected; ?>>
                    <?php echo esc_html($immobile->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="amount">Oferta/Interesse de Preço:</label><br>
        <input type="number" name="amount" id="amount" value="<?php echo get_post_meta($post->ID, 'amount', true) ?: $immobile_price; ?>">
        <?php if ($immobile_price && !get_post_meta($post->ID, 'amount', true)) : ?>
            <p class="description">Preço extraído do imóvel: R$ <?php echo number_format($immobile_price, 2, ',', '.'); ?></p>
        <?php endif; ?>
    </div>
    <div>
        <label for="details">O que deseja?:</label><br>
        <textarea name="details" id="details" rows="5" cols="50"><?php echo get_post_meta($post->ID, 'details', true); ?></textarea>
    </div>
</div>