<?php
$options_status = [
    'Aguardando Contato',
    'Em Contato',
];
$options = ['Sim', 'Não', "Indiferente"];
$locations = get_terms(array(
    'taxonomy' => 'locations',
    'hide_empty' => false,
));
$property_types = ['Sobrado', 'Térreo'];
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
        <?php echo get_post_meta($post->ID, 'location', true);?>
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
        <label for="amount">Oferta:</label><br>
        <input type="number" name="amount" id="amount" value="<?php echo get_post_meta($post->ID, 'amount', true); ?>">
    </div>
    <div>
        <label for="details">O que deseja?:</label><br>
        <textarea name="details" id="details"><?php echo get_post_meta($post->ID, 'details', true); ?></textarea>
    </div>
</div>