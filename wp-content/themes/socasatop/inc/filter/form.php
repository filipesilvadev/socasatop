<?php
$args = [
    'role'    => 'author',
    'orderby' => 'display_name',
    'order'   => 'ASC'
];
$brokers_query =  new WP_User_Query($args);
$brokers = $brokers_query->get_results();

$locations = get_terms([
    'taxonomy' => 'locations',
    'hide_empty' => false,
]);
$options = ['Sim', 'Não', 'Indiferente'];
$property_types = ['Sobrado', 'Térreo'];
?>

<form id="add-filter" method="get" class="form-filter">
    <div class="form-wrapper">
        <label for="title">Nome:</label>
        <input type="text" name="title" id="title" value="<?php echo isset($_GET['title']) ? $_GET['title'] : ""; ?>">
    </div>
    <div class="form-wrapper">
        <label for="amount_min">Investimento Mínimo:</label>
        <input type="text" name="amount_min" id="amount_min" value="<?php echo isset($_GET['amount_min']) ? $_GET['amount_min'] : ""; ?>">
    </div>
    <div class="form-wrapper">
        <label for="amount_max">Investimento Máximo:</label>
        <input type="text" name="amount_max" id="amount_max" value="<?php echo isset($_GET['amount_max']) ? $_GET['amount_max'] : ""; ?>">
    </div>
    <div class="form-wrapper">
        <label for="bedrooms">Quantos Quartos:</label>
        <input type="number" name="bedrooms" id="bedrooms" value="<?php echo isset($_GET['bedrooms']) ? $_GET['bedrooms'] : ""; ?>">
    </div>
    <div class="form-wrapper">
        <label for="facade">Tipo de Fachada:</label>
        <input type="text" name="facade" id="facade" value="<?php echo isset($_GET['facade']) ? $_GET['facade'] : ""; ?>">
    </div>
    <div class="form-wrapper">
        <label for="financing">Deseja Financiar:</label>
        <select id="financing" name="financing" class="select2">
            <?php foreach ($options as $option) : ?>
                <?php if (isset($_GET['financing'])) : ?>
                    <option <?php echo ($_GET['financing'] == $option) ? 'selected' : '' ?> value="<?php echo $option ?>"><?php echo $option; ?></option>
                <?php else : ?>
                    <option value="<?php echo $option ?>"><?php echo $option; ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-wrapper">
        <label for="condominium">Em Condomínio:</label>
        <select id="condominium" name="condominium" class="select2">
            <?php foreach ($options as $option) : ?>
                <?php if (isset($_GET['condominium'])) : ?>
                    <option <?php echo ($_GET['condominium'] == $option) ? 'selected' : '' ?> value="<?php echo $option ?>"><?php echo $option; ?></option>
                <?php else : ?>
                    <option value="<?php echo $option ?>"><?php echo $option; ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-wrapper">
        <label for="location">Localidade Desejada:</label>
        <select id="location" name="location[]" multiple="multiple" class="select2 multiple">
            <?php foreach ($locations as $location) : ?>
                <?php if (isset($_GET['location'])) : ?>
                    <option <?php echo ($_GET['location'] == $location->name) ? 'selected' : '' ?> value="<?php echo $location->name ?>"><?php echo $location->name; ?></option>
                <?php else : ?>
                    <option value="<?php echo $location->name ?>"><?php echo $location->name; ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-wrapper">
        <label for="broker">Corretor:</label>
        <select id="broker" name="broker" class="select2">
            <option value="">Todos</option>
            <?php foreach ($brokers as $broker) : ?>
                <option value="<?php echo $broker->ID ?>"><?php echo $broker->display_name; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-wrapper">
        <label for="min_size">Mínimo Metros²:</label>
        <input type="number" name="min_size" id="min_size">
    </div> 
    <div class="form-wrapper">
        <label for="max_size">Máximo Metros²:</label>
        <input type="number" name="max_size" id="max_size">
    </div>
    <div class="form-wrapper">
        <button type="submit" class="btn btn-info">
            Procurar Imóveis
        </button>
        <button type="reset" class="btn btn-danger">
            Limpar
        </button>
    </div>
</form>
<script>
    jQuery(document).ready(function($) {
        $('[type="reset"]').on('click', function() {
            $("#location").val(null).trigger('change');
        });
    });
</script>