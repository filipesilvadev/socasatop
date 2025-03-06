<?php
$locations = get_terms([
    'taxonomy' => 'locations',
    'hide_empty' => false,
]);
$options_status = [
    'Aguardando Contato',
    'Em Contato',
];
$options = ['Sim', 'Não', "Indiferente"];
$property_types = ['Sobrado', 'Térreo'];
?>
<form id="edit-lead" method="post" class="form">
    <?php if (isset($_GET['post'])) :
        $id = $_GET['post'];
        $gallery = get_post_meta($id, 'immobile_gallery', true);
    ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="form-wrapper">
            <label for="title">Nome:</label>
            <input type="text" name="title" id="title" required value="<?php echo get_the_title($id); ?>">
        </div>
        <div class="form-wrapper">
            <label for="phone">Telefone:</label>
            <input type="tel" name="phone" id="phone" required value="<?php echo get_post_meta($id, 'phone', true) ?>">
        </div>
        <div class="form-wrapper">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo get_post_meta($id, 'email', true) ?>">
        </div>
        <div class="form-wrapper">
            <label for="status">Status:</label>
            <select name="status" id="status" class="select2">
                <?php foreach ($options_status as $status) : ?>
                    <?php if (get_post_meta($post->ID, 'status', true) == $status) : ?>
                        <option selected value="<?php echo $status; ?>"><?php echo $status; ?></option>
                    <?php else : ?>
                        <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="group-inputs">
            <div class="form-wrapper w-1/2">
                <label for="amount">Investimento Disponível:</label>
                <input type="text" name="amount" id="amount" required value="<?php echo get_post_meta($id, 'amount', true) ?>">
            </div>
            <div class="form-wrapper w-1/2">
                <label for="financing">Deseja Financiar:</label>
                <select id="financing" name="financing" class="select2">
                    <?php foreach ($options as $option) : ?>
                        <option value="<?php echo $option ?>"><?php echo $option; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="group-inputs">
            <div class="form-wrapper w-1/2">
                <label for="location">Localidade Desejada:</label>
                <select id="location" name="location[]" class="select2" multiple="multiple">
                    <?php foreach ($locations as $location) : ?>
                        <option value="<?php echo $location->name ?>"><?php echo $location->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-wrapper w-1/2">
                <label for="condominium">Em Condomínio:</label>
                <select id="condominium" name="condominium" class="select2">
                    <?php foreach ($options as $option) : ?>
                        <option value="<?php echo $option ?>"><?php echo $option; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="group-inputs">
            <div class="form-wrapper w-1/2">
                <label for="bedrooms">Quantos Quartos:</label>
                <input type="number" name="bedrooms" id="bedrooms" required value="<?php echo get_post_meta($id, 'bedrooms', true) ?>">
            </div>
            <div class="form-wrapper w-1/2">
                <label for="facade">Tipo de Fachada:</label>
                <input type="text" name="facade" id="facade" required value="<?php echo get_post_meta($id, 'facade', true) ?>">
            </div>
        </div>
        <div class="form-wrapper">
            <label for="details">Detalhes:</label>
            <textarea name="details" id="details" class="form-input"><?php echo get_post_meta($id, 'details', true) ?></textarea>
        </div>
        <button type="submit" class="btn btn-info">
            Editar Lead
        </button>
    <?php else : ?>
        <p>Lead não encontrado.</p>
    <?php endif; ?>
</form>