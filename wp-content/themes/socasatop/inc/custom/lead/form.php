<?php
$locations = get_terms([
    'taxonomy' => 'locations',
    'hide_empty' => false,
]);
$options = ['Sim', 'Não', "Indiferente"];
?>
<form id="add-lead" method="post" class="form">
    <div class="form-wrapper">
        <label for="title">Nome:</label>
        <input type="text" name="title" id="title" required>
    </div>
    <div class="form-wrapper">
        <label for="phone">Telefone:</label>
        <input type="tel" name="phone" id="phone" required>
    </div>
    <div class="form-wrapper">
        <label for="email">Email:</label>
        <input type="email" name="email" id="email">
    </div>
    <div class="form-wrapper">
        <label for="status">Status:</label>
        <select name="status" id="status" class="select2">
            <option value="Em Contato">Em Contato</option>
            <option value="Aguardando Contato">Aguardando Contato</option>
        </select>
    </div>
    <div class="group-inputs">
        <div class="form-wrapper w-1/2">
            <label for="amount">Investimento Disponível:</label>
            <input type="text" name="amount" id="amount" required>
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
            <select id="location" name="location" class="select2" multiple="multiple">
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
        <div class="form-wrapper  w-1/2">
            <label for="bedrooms">Quantos Quartos:</label>
            <input type="number" name="bedrooms" id="bedrooms" required>
        </div>
        <div class="form-wrapper w-1/2">
            <label for="facade">Tipo de Fachada:</label>
            <input type="text" name="facade" id="facade" required>
        </div>
    </div>
    <div class="form-wrapper">
        <label for="details">Detalhes:</label>
        <textarea name="details" id="details"></textarea>
    </div>
    <button type="submit" class="btn btn-info">
        Cadastrar Lead
    </button>
</form>