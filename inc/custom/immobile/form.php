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
?>

<form id="add-immobile" method="post" class="form">
    <div class="form-wrapper">
        <label for="title">Nome:</label>
        <input type="text" name="title" id="title" required>
    </div>

    <div class="form-wrapper">
        <label for="facade">Tipo de Fachada:</label>
        <input type="text" name="facade" id="facade" required>
    </div>

    <div class="group-inputs">
        <div class="form-wrapper w-1/2">
            <label for="offer_type">Tipo de Oferta:</label>
            <select id="offer_type" name="offer_type" class="select2">
                <?php foreach ($offer_types as $offer_type) : ?>
                    <option value="<?php echo $offer_type ?>"><?php echo $offer_type; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-wrapper w-1/2">
            <label for="amount">Valor:</label>
            <input type="text" name="amount" id="amount" required>
        </div>
    </div>

    <div class="group-inputs">
        <div class="form-wrapper w-1/2">
            <label for="bedrooms">Quantos Quartos:</label>
            <input type="number" name="bedrooms" id="bedrooms" required>
        </div>
        <div class="form-wrapper w-1/2">
            <label for="size">Metragem:</label>
            <input type="number" name="size" id="size" required>
        </div>
    </div>

    <div class="group-inputs">
        <div class="form-wrapper w-1/2">
            <label for="committee">Comissão:</label>
            <input type="number" name="committee" id="committee" required>
        </div>
        <div class="form-wrapper w-1/2">
            <label for="committee_socasatop">Comissão So Casa Top:</label>
            <input type="number" name="committee_socasatop" id="committee_socasatop" required>
        </div>
    </div>

    <div class="group-inputs">
        <div class="form-wrapper w-1/2">
            <label for="location">Localidade:</label>
            <select id="location" name="location" class="select2">
                <?php foreach ($locations as $location) : ?>
                    <option value="<?php echo $location->name ?>"><?php echo $location->name; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-wrapper w-1/2">
            <label for="property_type">Tipo de Imóvel:</label>
            <select id="property_type" name="property_type" class="select2">
                <?php foreach ($property_types as $property_type) : ?>
                    <option value="<?php echo $property_type ?>"><?php echo $property_type; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="group-inputs">
        <div class="form-wrapper w-1/2">
            <label for="condominium">Condomínio:</label>
            <select id="condominium" name="condominium" class="select2">
                <?php foreach ($options as $option) : ?>
                    <option value="<?php echo $option ?>"><?php echo $option; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-wrapper w-1/2">
            <label for="financing">Aceita Financiamento:</label>
            <select id="financing" name="financing" class="select2">
                <?php foreach ($options as $option) : ?>
                    <option value="<?php echo $option ?>"><?php echo $option; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="group-inputs">
        <?php
        $user = wp_get_current_user();
        $roles_user = $user->roles;
        ?>
        <div class="form-wrapper <?php echo (!in_array('administrator', $roles_user)) ? 'w-full' : 'w-1/2'; ?>">
            <label for="broker">Corretor Responsável:</label>
            <select id="broker" name="broker" class="select2">
                <?php foreach ($brokers as $broker) : ?>
                    <?php if (in_array('author', $roles_user) && $user->display_name == $broker->display_name) : ?>
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
        <textarea name="details" id="details"></textarea>
    </div>

    <div class="form-wrapper">
        <label for="link">Link:</label>
        <input type="url" name="link" id="link">
    </div>

    <div>
        <div class="form-wrapper">
            <label for="upload_gallery_button">Galeria de Imagens</label><br>
            <input type="hidden" id="immobile_gallery" name="immobile_gallery" value="" />
            <button type="button" id="upload_gallery_button" class="btn btn-info">Adicionar Imagens</button>
        </div>
        <div id="gallery_preview" class="gallery-preview">
        </div>
    </div>

    <div class="form-wrapper not-recommended-wrapper">
        <label for="disable_social_sharing" class="not-recommended-label">
            <input type="checkbox" name="disable_social_sharing" id="disable_social_sharing">
            Desautorizar publicação nas redes sociais <span class="not-recommended-tag">(Não recomendado)</span>
        </label>
    </div>

    <button type="submit" class="btn btn-info">
        Cadastrar Imóvel
    </button>
</form>

<style>
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
    
    .not-recommended-tag {
        margin-left: 5px;
        font-size: 0.8em;
        background-color: #d32f2f;
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
    }
</style>