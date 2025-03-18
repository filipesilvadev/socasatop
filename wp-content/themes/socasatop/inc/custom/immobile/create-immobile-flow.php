<?php
function create_immobile_flow() {
    if (!is_user_logged_in()) {
        return '<p>Você precisa estar logado para acessar esta página.</p>';
    }
    
    // Verificar permissões do usuário
    $user = wp_get_current_user();
    $allowed_roles = array('administrator', 'author', 'broker', 'contributor');
    $has_permission = false;
    
    foreach ($allowed_roles as $role) {
        if (in_array($role, (array) $user->roles)) {
            $has_permission = true;
            break;
        }
    }
    
    if (!$has_permission) {
        return '<p>Você não tem permissão para adicionar imóveis.</p>';
    }

    $locations = get_terms(array(
        'taxonomy' => 'locations',
        'hide_empty' => false,
    ));

    $options = ['Sim', 'Não'];
    $property_types = ['Casa', 'Apartamento', 'Terreno'];
    $offer_types = ['Comprar', 'Alugar'];

    $marketing_products = [
      'patrocinado' => [
          'name' => 'Destaque',
          'price' => 99,
          'description' => 'Tenha seu imóvel destacado no topo das buscas.'
      ],
      'assessoria' => [
          'name' => 'Assessoria',
          'price' => 20,
          'description' => 'Conte com um assessor especialista'
      ],
      'colab' => [
          'name' => 'Colab',
          'price' => 200,
          'description' => 'Contrate nosso Colab'
      ],
      'captacao_colab' => [
          'name' => 'Captação + Colab',
          'price' => 650,
          'description' => 'Promocional para expandir suas vendas.'
      ]
    ];

    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('mercadopago-js', 'https://sdk.mercadopago.com/js/v2', [], null, true);
    
    // Passar dados para o JavaScript
    wp_localize_script('jquery', 'site', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('immobile_nonce'),
        'site_url' => site_url()
    ));

    ob_start();
    ?>
    <div class="immobile-create-flow-container">
        <div class="flow-navigation">
            <div class="nav-step active" data-step="1">
                <span class="step-number">1</span>
                Seus Imóveis
            </div>
            <div class="nav-step" data-step="2">
                <span class="step-number">2</span>
                Impulsionando Vendas
            </div>
            <div class="nav-step" data-step="3">
                <span class="step-number">3</span>
                Publicar
            </div>
        </div>

        <div class="flow-actions top-actions">
            <button id="prev-step-top" style="display:none;" class="nav-button">Voltar</button>
            <button id="next-step-top" class="nav-button">Próximo</button>
        </div>

        <div class="flow-content">
            <div class="flow-step" id="step-1">
                <div class="step-container">
                    <div class="immobile-list-sidebar">
                        <h3>Imóveis Criados</h3>
                        <div id="immobile-list"></div>
                        <!-- <button id="add-immobile-btn" class="add-immobile-button">Adicionar Novo Imóvel</button> -->
                    </div>
                    
                    <div class="immobile-form-container">
                        <form id="immobile-creation-form">
                            <input type="hidden" name="broker" value="<?php echo get_current_user_id(); ?>">
                            
                            <div class="form-group">
                                <label for="immobile_name">Nome do Imóvel</label>
                                <input type="text" name="immobile_name" id="immobile_name" required>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="location">Localidade</label>
                                    <select name="location" id="location" required>
                                        <?php foreach ($locations as $location) : ?>
                                            <option value="<?php echo $location->name ?>"><?php echo $location->name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="property_type">Tipo</label>
                                    <select name="property_type" id="property_type" required>
                                        <?php foreach ($property_types as $property_type) : ?>
                                            <option value="<?php echo $property_type ?>"><?php echo $property_type; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                  <label for="offer_type">Tipo de Oferta</label>
                                  <select name="offer_type" id="offer_type" required>
                                      <?php foreach ($offer_types as $offer_type) : ?>
                                          <option value="<?php echo $offer_type ?>"><?php echo $offer_type; ?></option>
                                      <?php endforeach; ?>
                                  </select>
                                </div>

                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="condominium">É em Condomínio?</label>
                                    <select name="condominium" id="condominium">
                                        <?php foreach ($options as $option) : ?>
                                            <option value="<?php echo $option ?>"><?php echo $option; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="financing">Aceita financiamento?</label>
                                    <select name="financing" id="financing">
                                        <?php foreach ($options as $option) : ?>
                                            <option value="<?php echo $option ?>"><?php echo $option; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="bedrooms">Quantidade de quartos</label>
                                    <input type="number" name="bedrooms" id="bedrooms">
                                </div>

                                <div class="form-group">
                                    <label for="size">Metragem</label>
                                    <input type="number" name="size" id="size">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="amount">Preço</label>
                                <input type="text" name="amount" id="amount" class="money-input">
                            </div>

                            <div class="form-group">
                                <label for="details">Apresentação do Imóvel</label>
                                <textarea name="details" id="details" rows="4" placeholder="Escreva uma apresentação atraente do imóvel com todos os detalhes que deseja destacar!"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="facade">Tipo de Fachada</label>
                                <input type="text" name="facade" id="facade">
                            </div>

                            <div class="form-group social-media-authorization-box">
                                <div class="social-media-content">
                                    <div class="social-media-icon">
                                        <i class="fas fa-share-alt"></i>
                                    </div>
                                    <div class="social-media-text">
                                        <label for="not_social_media" class="checkbox-label">
                                            <input type="checkbox" name="not_social_media" id="not_social_media" value="1">
                                            <span>Não autorizo a publicação deste imóvel no Instagram e outras redes sociais <span class="not-recommended">(Não recomendado)</span></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="immobile_gallery">Galeria de Imagens</label>
                                <input type="hidden" id="immobile_gallery" name="immobile_gallery" />
                                <button type="button" id="upload_gallery_button" class="upload-button">Adicionar Imagens</button>
                                <div id="gallery_preview" class="gallery-preview"></div>
                                <p class="description">A primeira imagem será utilizada como capa. Arraste as imagens para reordenar. Ou passe o mouse sobre a imagem para defini-la como capa.</p>
                            </div>

                            <div class="form-group">
                                <label for="immobile_videos">Vídeos do Imóvel</label>
                                <input type="hidden" id="immobile_videos" name="immobile_videos" />
                                <button type="button" id="upload_videos_button" class="upload-button">Adicionar Vídeos</button>
                                <div id="videos_preview" class="videos-preview"></div>
                                <p class="description">Arraste os vídeos para reordenar. Formatos aceitos: MP4, WebM (máx. 128MB)</p>
                            </div>

                            <button type="submit" class="save-button">Salvar</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="flow-step" id="step-2" style="display:none;">
                <h2>Impulsione suas Vendas</h2>
                <div class="marketing-info-box">
                    <p><strong>Informação importante:</strong> Cada imóvel tem um custo base de <span class="highlight-price">R$15,00/mês</span> para publicação. Você pode adicionar serviços extras abaixo para aumentar a visibilidade e as chances de venda. Todos os valores são cobrados mensalmente como assinatura recorrente no seu cartão de crédito.</p>
                </div>
                <div id="marketing-section" class="marketing-section"></div>
            </div>

            <div class="flow-step" id="step-3" style="display:none;">
                <div class="summary-container">
                    <h2>Resumo e Publicação</h2>
                    <div id="summary-list"></div>
                    <div id="total-summary"></div>
                    <div id="cardPaymentBrick_container" class="payment-container"></div>
                </div>
            </div>
        </div>

        <div class="flow-actions bottom-actions">
            <button id="prev-step" style="display:none;" class="nav-button">Voltar</button>
            <button id="next-step" class="nav-button">Próximo</button>
        </div>
    </div>

    <style>
    .immobile-create-flow-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .flow-navigation {
        display: flex;
        justify-content: space-between;
        margin-bottom: 40px;
        position: relative;
    }

    .flow-navigation:after {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 2px;
        background: #e0e0e0;
        z-index: 1;
    }

    .nav-step {
        background: #fff;
        padding: 10px 20px;
        border-radius: 30px;
        display: flex;
        align-items: center;
        position: relative;
        z-index: 2;
    }

    .step-number {
        width: 30px;
        height: 30px;
        background: #e0e0e0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
    }

    .nav-step.active {
        background: #0056b3;
        color: white;
    }

    .nav-step.active .step-number {
        background: white;
        color: #0056b3;
    }

    .step-container {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 30px;
    }

    .immobile-list-sidebar {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        height: fit-content;
    }

    .immobile-item {
        background: white;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 6px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .immobile-form-container {
        background: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .gallery-preview {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .gallery-image {
        position: relative;
        display: inline-block;
        margin: 5px;
        border: 1px solid #ddd;
        padding: 5px;
        transition: all 0.3s ease;
    }

    .gallery-image img {
        max-width: 150px;
        max-height: 150px;
        object-fit: cover;
    }

    .gallery-image .image-actions {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 5px;
        opacity: 0;
        transition: opacity 0.3s ease;
        text-align: center;
    }

    .gallery-image:hover .image-actions {
        opacity: 1;
    }

    .make-featured {
        cursor: pointer;
        display: block;
        font-size: 12px;
        white-space: nowrap;
    }

    .remove-image {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(255,0,0,0.7);
        color: white;
        width: 20px;
        height: 20px;
        text-align: center;
        line-height: 20px;
        border-radius: 50%;
        cursor: pointer;
        z-index: 10;
    }

    .save-button {
        background: #0056b3;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }

    .add-immobile-button {
        width: 100%;
        background: #28a745;
        color: white;
        padding: 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-top: 20px;
    }

    .marketing-products {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 15px;
    }

    .marketing-product {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.08);
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
        overflow: hidden;
        border: 1px solid #eaeaea;
    }

    .marketing-product:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .product-header {
        display: flex;
        margin-bottom: 15px;
    }

    .checkbox-wrapper {
        position: relative;
        min-width: 24px;
        height: 24px;
        margin-right: 12px;
    }

    .checkbox-wrapper input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }

    .checkmark {
        position: absolute;
        top: 0;
        left: 0;
        height: 24px;
        width: 24px;
        background-color: #f0f0f0;
        border-radius: 4px;
        transition: all 0.2s;
        border: 1px solid #ddd;
    }

    .checkbox-wrapper:hover input ~ .checkmark {
        background-color: #e0e0e0;
    }

    .checkbox-wrapper input:checked ~ .checkmark {
        background-color: #0056b3;
        border-color: #0056b3;
    }

    .checkmark:after {
        content: "";
        position: absolute;
        display: none;
    }

    .checkbox-wrapper input:checked ~ .checkmark:after {
        display: block;
    }

    .checkbox-wrapper .checkmark:after {
        left: 9px;
        top: 5px;
        width: 5px;
        height: 10px;
        border: solid white;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
    }

    .product-label {
        display: flex;
        flex-direction: column;
        flex: 1;
        cursor: pointer;
    }

    .product-name {
        font-weight: bold;
        font-size: 1.1em;
        color: #333;
        margin-bottom: 5px;
    }

    .product-price {
        color: #0056b3;
        font-weight: 500;
    }

    .product-description {
        color: #666;
        font-size: 0.9em;
        line-height: 1.5;
        margin-left: 36px;
    }

    .marketing-immobile {
        background: #f9f9f9;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 25px;
        border: 1px solid #eee;
    }

    .payment-container {
        margin-top: 30px;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 8px;
    }

    .nav-button {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        margin: 0 10px;
    }

    #next-step {
        background: #0056b3;
        color: white;
    }

    #prev-step {
        background: #6c757d;
        color: white;
    }

    .summary-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .marketing-section {
    display: flex;
    flex-direction: column;
    gap: 30px;
    padding: 20px;
}

.marketing-immobile {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.marketing-products {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.product-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.product-description {
    font-size: 14px;
    color: #666;
    margin-top: 8px;
}

.videos-preview {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }

    .video-preview {
        position: relative;
        background: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        cursor: move;
    }

    .video-preview video {
        width: 100%;
        border-radius: 4px;
    }

    .remove-video {
        position: absolute;
        top: 5px;
        right: 5px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #dc3545;
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        z-index: 5;
    }

    .summary-item {
    background: #f8f9fa;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #dee2e6;
}

.summary-price {
    font-size: 1.2em;
    font-weight: bold;
    color: #0056b3;
}

.summary-details {
    color: #666;
}

.services-list {
    list-style: none;
    padding-left: 20px;
    margin-top: 10px;
}

.services-list li {
    padding: 5px 0;
    color: #0056b3;
}

.total-summary-content {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.total-price {
    text-align: right;
}

.total-price h4 {
    color: #666;
    margin-bottom: 5px;
}

.total-price p {
    font-size: 1.5em;
    font-weight: bold;
    color: #0056b3;
    margin: 0;
}

@media (max-width: 768px) {
    .step-container {
        grid-template-columns: 1fr;
    }

    .immobile-list-sidebar {
        margin-bottom: 20px;
    }

    .immobile-form-container {
        padding: 15px;
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .nav-step {
        padding: 4px;
        font-size: 12px;
    }

    .step-number {
        width: 24px;
        height: 24px;
        font-size: 10px;
        margin-right:4px;
    }

    .gallery-preview {
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    }

    .videos-preview {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
    .immobile-create-flow-container{
      padding:0;
    }
}

.flow-actions {
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
}

.top-actions {
    margin-bottom: 20px;
}

.bottom-actions {
    margin-top: 20px;
}

.flow-actions button:last-child {
    margin-left: auto;
}

/* Estilo para o checkbox de desautorização */
.checkbox-group {
    margin: 15px 0;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 10px;
    margin-top: 3px;
}

.not-recommended {
    color: #dc3545;
    font-weight: bold;
    margin-left: 5px;
}

.base-price {
    font-size: 0.7em;
    font-weight: normal;
    color: #0056b3;
    margin-left: 8px;
}

.marketing-info-box {
    background-color: #f8f9fa;
    border-left: 4px solid #0056b3;
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.highlight-price {
    color: #0056b3;
    font-weight: bold;
}

.immobile-title {
    font-size: 1.1em;
    margin-bottom: 10px;
}

/* Estilo para o checkbox de desautorização */
.checkbox-group {
    margin: 15px 0;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 10px;
    margin-top: 3px;
}

.not-recommended {
    color: #dc3545;
    font-weight: bold;
    margin-left: 5px;
}

/* Novo estilo para o box de autorização de redes sociais */
.social-media-authorization-box {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.social-media-content {
    display: flex;
    align-items: center;
}

.social-media-icon {
    background-color: #e9ecef;
    color: #0056b3;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    flex-shrink: 0;
}

.social-media-icon i {
    font-size: 18px;
}

.social-media-text {
    flex-grow: 1;
}

/* Estilos para o novo formulário de cartões */
.payment-methods {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 20px;
}

.payment-method-options {
    margin-top: 20px;
}

.payment-option {
    margin-bottom: 15px;
    padding: 15px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.saved-cards-list {
    margin-top: 15px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.saved-card-item {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 12px;
    transition: all 0.2s;
    background: #f9f9f9;
}

.saved-card-item:hover {
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
    border-color: #0056b3;
}

.default-card {
    border-color: #0056b3;
    background-color: #f0f7ff;
}

.card-select {
    display: flex;
    cursor: pointer;
}

.card-select input[type="radio"] {
    margin-right: 10px;
    margin-top: 4px;
}

.card-details {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.card-brand {
    display: flex;
    align-items: center;
    margin-bottom: 5px;
}

.card-brand img {
    height: 24px;
    max-width: 40px;
    object-fit: contain;
}

.card-info {
    display: flex;
    flex-direction: column;
}

.card-number {
    font-weight: bold;
    margin-bottom: 5px;
}

.card-expiry {
    font-size: 0.9em;
    color: #666;
}

.default-badge {
    background: #0056b3;
    color: white;
    padding: 3px 8px;
    font-size: 0.8em;
    border-radius: 12px;
    display: inline-block;
    margin-top: 8px;
}

.payment-actions {
    margin-top: 20px;
    text-align: center;
}

#process-payment-button {
    background: #0056b3;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
}

#process-payment-button:hover {
    background: #004494;
}

#process-payment-button:disabled {
    background: #cccccc;
    cursor: not-allowed;
}

#new-card-form-container {
    margin-top: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

/* Estilos para o formulário do MercadoPago */
.mp-form {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mp-form-row {
    margin-bottom: 20px;
}

.mp-col-12 {
    width: 100%;
}

.mp-col-6 {
    width: 48%;
    display: inline-block;
    margin-right: 2%;
}

.mp-col-6:last-child {
    margin-right: 0;
}

.mp-input-container {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 8px;
    background: #fff;
    min-height: 40px;
}

.mp-form label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
}

.mp-form input[type="text"],
.mp-form select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.mp-form select {
    height: 40px;
    background: #fff;
}

.payment-submit-button {
    width: 100%;
    padding: 12px 24px;
    background: #0056b3;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.payment-submit-button:hover {
    background: #004494;
}

.payment-submit-button:disabled {
    background: #cccccc;
    cursor: not-allowed;
}

.error-message {
    color: #dc3545;
    padding: 10px;
    margin-top: 10px;
    border: 1px solid #dc3545;
    border-radius: 4px;
    background: #fff;
}

/* Estilos para o iframe do MercadoPago */
.mp-iframe-container {
    width: 100%;
    min-height: 400px;
}

@media (max-width: 768px) {
    .mp-col-6 {
        width: 100%;
        margin-right: 0;
        margin-bottom: 15px;
    }
}
    </style>

<script>
    jQuery(document).ready(function($) {
        // Inicializar MercadoPago com a chave pública
        const mp = new MercadoPago('TEST-70b46d06-add9-499a-942e-0f5c01b8769a', {
            locale: 'pt-BR'
        });

        let immobileList = [];
        const marketingProducts = <?php echo json_encode($marketing_products); ?>;

        // Verificar se o MercadoPago foi carregado corretamente
        if (typeof mp === 'undefined' || !mp) {
            console.error('MercadoPago não foi carregado corretamente');
            $('#cardPaymentBrick_container').html(`
                <div class="payment-error-message">
                    <p>Ocorreu um erro ao carregar a integração de pagamento. Por favor, tente novamente mais tarde.</p>
                </div>
            `);
            return;
        }

        $('#amount').mask('000.000.000.000.000,00', {reverse: true});

        $('#immobile-creation-form').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serializeArray();
            const immobile = {};
            
            formData.forEach(item => {
                immobile[item.name] = item.value;
            });

            immobile.gallery_preview = $('#gallery_preview').html();
            immobile.videos = $('#immobile_videos').val();
            
            immobileList.push(immobile);
            updateImmobileList();
            this.reset();
            $('#gallery_preview').empty();
            $('#videos_preview').empty();
            
            // Disparar evento de imóvel adicionado
            $(document).trigger('immobile_added', [immobile]);
        });

        function updateImmobileList() {
            const $list = $('#immobile-list');
            $list.empty();
            
            immobileList.forEach((immobile, index) => {
                const previewImage = $(immobile.gallery_preview).first().prop('outerHTML') || '';
                $list.append(`
                    <div class="immobile-item">
                        ${previewImage}
                        <h4>${immobile.immobile_name}</h4>
                        <p>${immobile.location} - ${immobile.property_type}</p>
                        <button class="remove-immobile" data-index="${index}">Remover</button>
                    </div>
                `);
            });
        }

        $(document).on('click', '.remove-immobile', function() {
            const index = $(this).data('index');
            immobileList.splice(index, 1);
            updateImmobileList();
        });

        function updateMarketingSection() {
            const $marketingSection = $('#marketing-section');
            $marketingSection.empty();
            
            immobileList.forEach((immobile, immobileIndex) => {
                let immobileTotal = 15; // Valor base do imóvel
                const selectedProducts = [];

                $(`input[name="marketing_products[${immobileIndex}][]"]:checked`).each(function() {
                    const productKey = $(this).val();
                    const productPrice = marketingProducts[productKey].price;
                    immobileTotal += productPrice;
                    selectedProducts.push(marketingProducts[productKey].name);
                });

                const productsHtml = Object.entries(marketingProducts).map(([key, product]) => `
                    <div class="marketing-product">
                        <div class="product-header">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" 
                                       id="product-${key}-${immobileIndex}" 
                                       name="marketing_products[${immobileIndex}][]" 
                                       value="${key}">
                                <span class="checkmark"></span>
                            </div>
                            <label for="product-${key}-${immobileIndex}" class="product-label">
                                <span class="product-name">${product.name}</span>
                                <span class="product-price">R$${product.price.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                            </label>
                        </div>
                        <p class="product-description">${product.description}</p>
                    </div>
                `).join('');

                $marketingSection.append(`
                    <div class="marketing-immobile">
                        <h3 class="immobile-title">${immobile.immobile_name} <span class="base-price">R$15,00/mês</span></h3>
                        <div class="marketing-products">
                            ${productsHtml}
                        </div>
                    </div>
                `);
            });
        }

        $('#upload_gallery_button').on('click', function() {
            var mediaUploader = wp.media({
                title: 'Selecionar Imagens',
                button: {
                    text: 'Usar estas imagens'
                },
                multiple: true,
                library: {
                    type: 'image'
                }
            });

            mediaUploader.on('select', function() {
                var attachments = mediaUploader.state().get('selection').map(
                    attachment => {
                        return attachment.toJSON();
                    }
                );
                
                let imageIds = [];
                // Obter IDs existentes
                $('.gallery-image').each(function() {
                    imageIds.push($(this).data('id'));
                });
                
                attachments.forEach(attachment => {
                    // Adicionar novo ID à lista
                    imageIds.push(attachment.id);
                    
                    $('#gallery_preview').append(`
                        <div class="gallery-image" data-id="${attachment.id}">
                            <img src="${attachment.url}" alt="" />
                            <span class="remove-image">×</span>
                            <div class="image-actions">
                                <label class="make-featured">
                                    <input type="radio" name="featured_image" value="${attachment.id}"> Definir como Capa
                                </label>
                            </div>
                        </div>
                    `);
                });
                
                // Atualizar o campo oculto com os IDs
                $('#immobile_gallery').val(imageIds.join(','));
            });

            mediaUploader.open();
        });
        
        // Remover imagem
        $(document).on('click', '.remove-image', function() {
            var $item = $(this).closest('.gallery-image');
            var imageId = $item.data('id');
            var currentIds = $('#immobile_gallery').val() ? $('#immobile_gallery').val().split(',') : [];
            var newIds = currentIds.filter(id => id != imageId);
            
            $('#immobile_gallery').val(newIds.join(','));
            $item.remove();
        });
        
        // Definir imagem como capa
        $(document).on('change', 'input[name="featured_image"]', function() {
            var featuredId = $(this).val();
            var currentIds = $('#immobile_gallery').val() ? $('#immobile_gallery').val().split(',') : [];
            
            // Remover o ID da imagem de capa da lista
            currentIds = currentIds.filter(id => id != featuredId);
            
            // Adicionar o ID da imagem de capa no início
            currentIds.unshift(featuredId);
            
            // Atualizar o campo oculto
            $('#immobile_gallery').val(currentIds.join(','));
        });
        
        // Tornar galeria de imagens ordenável
        $('#gallery_preview').sortable({
            update: function(event, ui) {
                var imageIds = [];
                $('.gallery-image').each(function() {
                    imageIds.push($(this).data('id'));
                });
                $('#immobile_gallery').val(imageIds.join(','));
            }
        });

        $('#upload_videos_button').on('click', function() {
            var videoUploader = wp.media({
                title: 'Selecionar Vídeos',
                button: {
                    text: 'Usar estes vídeos'
                },
                multiple: true,
                library: {
                    type: 'video'
                }
            });

            videoUploader.on('select', function() {
                var attachments = videoUploader.state().get('selection').map(
                    attachment => {
                        return attachment.toJSON();
                    }
                );
                
                attachments.forEach(attachment => {
                    $('#videos_preview').append(`
                        <div class="video-preview" data-id="${attachment.id}">
                            <video width="200" controls>
                                <source src="${attachment.url}" type="${attachment.mime}">
                            </video>
                            <button type="button" class="remove-video">×</button>
                        </div>
                    `);
                });
                
                updateVideoIds();
            });

            videoUploader.open();
        });

        $(document).on('click', '.remove-video', function() {
            $(this).closest('.video-preview').remove();
            updateVideoIds();
        });
        
        // Tornar lista de vídeos ordenável
        $('#videos_preview').sortable({
            update: function(event, ui) {
                updateVideoIds();
            }
        });

        function updateVideoIds() {
            var ids = [];
            $('.video-preview').each(function() {
                ids.push($(this).data('id'));
            });
            $('#immobile_videos').val(ids.join(','));
        }

        function setupPaymentBrick(totalValue) {
            if (typeof MercadoPago === 'undefined') {
                console.error('MercadoPago não foi carregado corretamente');
                $('#cardPaymentBrick_container').html(`
                    <div class="payment-error-message">
                        <p>Ocorreu um erro ao carregar a integração de pagamento. Por favor, tente novamente mais tarde.</p>
                    </div>
                `);
                return;
            }
            
            // Verificar se o usuário tem cartões salvos
            $.ajax({
                url: site.ajax_url,
                method: 'POST',
                data: {
                    action: 'get_user_saved_cards',
                    nonce: site.nonce
                },
                success: function(response) {
                    if (response.success && response.data.cards && response.data.cards.length > 0) {
                        // Mostrar cartões salvos e opção de novo cartão
                        renderSavedCardsAndPaymentOptions(response.data.cards, response.data.default_card_id, totalValue);
                    } else {
                        // Se não tiver cartões salvos, mostrar apenas o formulário de novo cartão
                        renderNewCardPaymentBrick(totalValue);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Erro ao obter cartões salvos:", error);
                    // Em caso de erro, mostrar apenas o formulário de novo cartão
                    renderNewCardPaymentBrick(totalValue);
                }
            });
        }

        function renderSavedCardsAndPaymentOptions(cards, defaultCardId, totalValue) {
            // Limpar o container
            const $container = $('#cardPaymentBrick_container');
            $container.empty();
            
            // Adicionar HTML para seleção de forma de pagamento
            $container.html(`
                <div class="payment-methods">
                    <h4>Escolha como pagar</h4>
                    
                    <div class="payment-method-options">
                        <div class="payment-option">
                            <label>
                                <input type="radio" name="payment_method" value="saved_card" checked>
                                <span>Cartão salvo</span>
                            </label>
                            
                            <div class="saved-cards-container">
                                <div class="saved-cards-list"></div>
                            </div>
                        </div>
                        
                        <div class="payment-option">
                            <label>
                                <input type="radio" name="payment_method" value="new_card">
                                <span>Novo cartão</span>
                            </label>
                            
                            <div id="new-card-form-container" style="display: none;"></div>
                        </div>
                    </div>
                    
                    <div class="payment-actions">
                        <button id="process-payment-button" class="button button-primary">Finalizar Pagamento</button>
                    </div>
                </div>
            `);
            
            // Renderizar os cartões salvos
            const $savedCardsList = $('.saved-cards-list');
            let html = '';
            
            cards.forEach(function(card) {
                const isDefault = card.id === defaultCardId;
                const cardBrand = card.brand || 'unknown';
                const cardNumber = card.last_four || '****';
                const expMonth = card.expiry_month || card.expiration_month || '**';
                const expYear = card.expiry_year || card.expiration_year || '****';
                
                html += `<div class="saved-card-item ${isDefault ? 'default-card' : ''}">
                    <label class="card-select">
                        <input type="radio" name="payment_card" value="${card.id}" ${isDefault ? 'checked' : ''}>
                        <div class="card-details">
                            <div class="card-brand">
                                <img src="${getCardBrandLogo(cardBrand)}" alt="${cardBrand}" 
                                    onerror="this.src='${getCardBrandLogo('generic-card')}';">
                            </div>
                            <div class="card-info">
                                <span class="card-number">•••• •••• •••• ${cardNumber}</span>
                                <span class="card-expiry">Válido até: ${expMonth}/${expYear}</span>
                            </div>
                            ${isDefault ? '<span class="default-badge">Padrão</span>' : ''}
                        </div>
                    </label>
                </div>`;
            });
            
            $savedCardsList.html(html);
            
            // Alternar entre cartão salvo e novo cartão
            $('input[name="payment_method"]').on('change', function() {
                if ($(this).val() === 'new_card') {
                    $('.saved-cards-container').hide();
                    $('#new-card-form-container').show();
                    // Inicializar o brick de novo cartão se ainda não foi inicializado
                    if ($('#new-card-form-container').is(':empty')) {
                        renderNewCardPaymentBrick(totalValue, 'new-card-form-container');
                    }
                } else {
                    $('.saved-cards-container').show();
                    $('#new-card-form-container').hide();
                }
            });
            
            // Processar pagamento ao clicar no botão
            $('#process-payment-button').on('click', function() {
                const paymentMethod = $('input[name="payment_method"]:checked').val();
                
                if (paymentMethod === 'saved_card') {
                    const cardId = $('input[name="payment_card"]:checked').val();
                    processSavedCardPayment(cardId, totalValue);
                } else {
                    // O formulário de novo cartão será processado pelo próprio brick
                }
            });
        }

        function getCardBrandLogo(brand) {
            return `${site.site_url}/wp-content/themes/socasatop/inc/custom/broker/assets/images/card-brands/${brand}.png`;
        }

        function processSavedCardPayment(cardId, totalValue) {
            $('#process-payment-button').prop('disabled', true).text('Processando...');
            
            $.ajax({
                url: site.ajax_url,
                method: 'POST',
                data: {
                    action: 'process_immobile_creation_payment',
                    payment_method: 'saved_card',
                    saved_card_id: cardId,
                    immobile_list: immobileList,
                    amount: totalValue,
                    nonce: site.nonce
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Sucesso!',
                            text: 'Cartão validado com sucesso! Você terá 30 dias grátis.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = '/meus-imoveis';
                        });
                    } else {
                        Swal.fire({
                            title: 'Erro!',
                            text: response.data.message || 'Erro ao processar pagamento',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        $('#process-payment-button').prop('disabled', false).text('Finalizar Pagamento');
                    }
                },
                error: function(error) {
                    Swal.fire({
                        title: 'Erro!',
                        text: 'Erro ao processar pagamento',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    $('#process-payment-button').prop('disabled', false).text('Finalizar Pagamento');
                }
            });
        }

        function renderNewCardPaymentBrick(totalValue, containerId = 'cardPaymentBrick_container') {
            const $container = $('#' + containerId);
            if (!$container.length) {
                console.error("Container " + containerId + " não encontrado");
                return null;
            }
            
            $container.empty().html(`
                <div class="card-form-wrapper">
                    <form id="card-form-new" class="mp-form">
                        <div id="form-checkout">
                            <div class="mp-form-row">
                                <div class="mp-col-12">
                                    <label for="form-checkout__cardholderName">Nome no cartão</label>
                                    <input type="text" id="form-checkout__cardholderName" />
                                </div>
                            </div>
                            
                            <div class="mp-form-row">
                                <div class="mp-col-12">
                                    <label>Número do cartão</label>
                                    <div id="form-checkout__cardNumber" class="mp-input-container"></div>
                                </div>
                            </div>
                            
                            <div class="mp-form-row">
                                <div class="mp-col-6">
                                    <label>Data de validade</label>
                                    <div id="form-checkout__expirationDate" class="mp-input-container"></div>
                                </div>
                                <div class="mp-col-6">
                                    <label>Código de segurança</label>
                                    <div id="form-checkout__securityCode" class="mp-input-container"></div>
                                </div>
                            </div>
                            
                            <div class="mp-form-row">
                                <div class="mp-col-6">
                                    <label for="form-checkout__identificationType">Tipo de documento</label>
                                    <select id="form-checkout__identificationType"></select>
                                </div>
                                <div class="mp-col-6">
                                    <label for="form-checkout__identificationNumber">Número do documento</label>
                                    <input type="text" id="form-checkout__identificationNumber"/>
                                </div>
                            </div>

                            <div class="mp-form-row">
                                <div class="mp-col-12">
                                    <label for="form-checkout__issuer">Banco emissor</label>
                                    <select id="form-checkout__issuer"></select>
                                </div>
                            </div>

                            <div class="mp-form-row">
                                <div class="mp-col-12">
                                    <label for="form-checkout__installments">Parcelas</label>
                                    <select id="form-checkout__installments"></select>
                                </div>
                            </div>

                            <div class="mp-form-row">
                                <div class="mp-col-12">
                                    <input type="hidden" id="amount" value="${totalValue}" />
                                    <input type="hidden" name="paymentMethodId" id="paymentMethodId" />
                                    <input type="hidden" name="token" id="token" />
                                </div>
                            </div>

                            <div class="mp-form-row">
                                <div class="mp-col-12">
                                    <button type="submit" id="form-checkout__submit" class="payment-submit-button">Salvar cartão e finalizar</button>
                                </div>
                            </div>
                        </div>
                        <div id="result-message"></div>
                    </form>
                </div>
            `);

            try {
                const cardForm = mp.cardForm({
                    amount: totalValue.toString(),
                    iframe: true,
                    form: {
                        id: "card-form-new",
                        cardholderName: {
                            id: "form-checkout__cardholderName",
                            placeholder: "Titular do cartão"
                        },
                        cardNumber: {
                            id: "form-checkout__cardNumber",
                            placeholder: "Número do cartão"
                        },
                        expirationDate: {
                            id: "form-checkout__expirationDate",
                            placeholder: "MM/YY"
                        },
                        securityCode: {
                            id: "form-checkout__securityCode",
                            placeholder: "CVV"
                        },
                        identificationType: {
                            id: "form-checkout__identificationType"
                        },
                        identificationNumber: {
                            id: "form-checkout__identificationNumber",
                            placeholder: "CPF"
                        },
                        issuer: {
                            id: "form-checkout__issuer",
                            placeholder: "Banco emissor"
                        },
                        installments: {
                            id: "form-checkout__installments",
                            placeholder: "Parcelas"
                        },
                        submit: {
                            id: "form-checkout__submit"
                        }
                    },
                    callbacks: {
                        onFormMounted: function(error) {
                            if (error) {
                                console.error("Erro ao montar formulário:", error);
                                $('#result-message').html('<div class="error-message">Erro ao carregar formulário de cartão. Tente novamente mais tarde.</div>');
                            } else {
                                console.log("Formulário montado com sucesso");
                            }
                        },
                        onIdentificationTypesReceived: function(error, identificationTypes) {
                            if (error) {
                                console.error("Erro ao obter tipos de documento:", error);
                            }
                        },
                        onPaymentMethodsReceived: function(error, paymentMethods) {
                            if (error) {
                                console.error("Erro ao obter métodos de pagamento:", error);
                            }
                        },
                        onIssuersReceived: function(error, issuers) {
                            if (error) {
                                console.error("Erro ao obter bancos:", error);
                            }
                        },
                        onInstallmentsReceived: function(error, installments) {
                            if (error) {
                                console.error("Erro ao obter parcelas:", error);
                            }
                        },
                        onCardTokenReceived: function(error, token) {
                            if (error) {
                                console.error("Erro ao gerar token do cartão:", error);
                                $('#result-message').html('<div class="error-message">Erro ao processar o cartão. Verifique os dados e tente novamente.</div>');
                                $('#process-payment-button').prop('disabled', false).text('Finalizar Pagamento');
                            } else {
                                console.log("Token gerado com sucesso:", token);
                                processNewCardPayment(token, totalValue);
                            }
                        }
                    }
                });

                return cardForm;
            } catch (error) {
                console.error("Erro ao inicializar o formulário de cartão:", error);
                $('#result-message').html('<div class="error-message">Erro ao inicializar o formulário: ' + error.message + '</div>');
                return null;
            }
        }

        function processNewCardPayment(cardToken, totalValue) {
            if (!cardToken) {
                $('#result-message').html('<div class="error-message">Token do cartão inválido.</div>');
                $('#process-payment-button').prop('disabled', false).text('Finalizar Pagamento');
                return;
            }

            const formData = {
                action: 'process_immobile_creation_payment',
                payment_method: 'new_card',
                payment_data: {
                    token: cardToken.id,
                    payment_method_id: $('#paymentMethodId').val(),
                    issuer_id: $('#form-checkout__issuer').val(),
                    installments: $('#form-checkout__installments').val() || 1,
                    transaction_amount: totalValue,
                    payer: {
                        email: '<?php echo wp_get_current_user()->user_email; ?>',
                        identification: {
                            type: $('#form-checkout__identificationType').val(),
                            number: $('#form-checkout__identificationNumber').val()
                        }
                    }
                },
                immobile_list: immobileList,
                nonce: site.nonce
            };

            $.ajax({
                url: site.ajax_url,
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Sucesso!',
                            text: 'Cartão validado com sucesso! Você terá 30 dias grátis.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = '/meus-imoveis';
                        });
                    } else {
                        const errorMessage = response.data && response.data.message 
                            ? response.data.message 
                            : 'Erro ao processar pagamento. Por favor, tente novamente.';
                        
                        Swal.fire({
                            title: 'Erro!',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        $('#process-payment-button').prop('disabled', false).text('Finalizar Pagamento');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisição:', error);
                    Swal.fire({
                        title: 'Erro!',
                        text: 'Erro ao processar pagamento. Por favor, tente novamente mais tarde.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    $('#process-payment-button').prop('disabled', false).text('Finalizar Pagamento');
                }
            });
        }

        function updateSummary() {
            const $summaryList = $('#summary-list');
            const $totalSummary = $('#total-summary');
            let totalValue = 0;

            $summaryList.empty();
            
            immobileList.forEach((immobile, index) => {
                let immobileTotal = 15; // Valor base do imóvel
                const selectedProducts = [];

                $(`input[name="marketing_products[${index}][]"]:checked`).each(function() {
                    const productKey = $(this).val();
                    const productPrice = marketingProducts[productKey].price;
                    immobileTotal += productPrice;
                    selectedProducts.push(marketingProducts[productKey].name);
                });

                totalValue += immobileTotal;

                $summaryList.append(`
                    <div class="summary-item">
                        <div class="summary-header">
                            <h3>${immobile.immobile_name}</h3>
                            <span class="summary-price">R$ ${immobileTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                        </div>
                        <div class="summary-details">
                            <p><strong>Localização:</strong> ${immobile.location}</p>
                            <p><strong>Tipo:</strong> ${immobile.property_type}</p>
                            ${selectedProducts.length > 0 ? 
                                `<p><strong>Serviços Contratados:</strong></p>
                                <ul class="services-list">
                                    ${selectedProducts.map(product => `<li>${product}</li>`).join('')}
                                </ul>` : 
                                '<p>Nenhum serviço adicional selecionado</p>'
                            }
                        </div>
                    </div>
                `);
            });

            $totalSummary.html(`
                <div class="total-summary-content">
                    <div class="summary-info">
                        <h3>Resumo do Pedido</h3>
                        <p>Total de Imóveis: ${immobileList.length}</p>
                    </div>
                    <div class="total-price">
                        <h4>Valor Total</h4>
                        <p>R$ ${totalValue.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                    </div>
                </div>
            `);

            setupPaymentBrick(totalValue);
        }

        $('#next-step').on('click', function() {
            const currentStep = $('.nav-step.active').data('step');
            
            if (currentStep === 1 && immobileList.length === 0) {
                alert('Adicione pelo menos um imóvel');
                return;
            }
            
            if (currentStep < 3) {
                $(`.flow-step#step-${currentStep}`).hide();
                $(`.flow-step#step-${currentStep + 1}`).show();
                
                $('.nav-step').removeClass('active');
                $(`.nav-step[data-step="${currentStep + 1}"]`).addClass('active');
                
                if (currentStep + 1 === 3) {
                    updateSummary();
                }

                if (currentStep + 1 === 2) {
                    updateMarketingSection();
                }
                
                $('#prev-step').show();
                
                if (currentStep + 1 === 3) {
                    $('#next-step').hide();
                }
                
                // Disparar evento de mudança de etapa
                $(document).trigger('step_change', [currentStep + 1]);
            }
        });

        $('#prev-step').on('click', function() {
            const currentStep = $('.nav-step.active').data('step');
            
            if (currentStep > 1) {
                $(`.flow-step#step-${currentStep}`).hide();
                $(`.flow-step#step-${currentStep - 1}`).show();
                
                $('.nav-step').removeClass('active');
                $(`.nav-step[data-step="${currentStep - 1}"]`).addClass('active');
                
                $('#next-step').show();
                
                if (currentStep - 1 === 1) {
                    $('#prev-step').hide();
                }
                
                // Disparar evento de mudança de etapa
                $(document).trigger('step_change', [currentStep - 1]);
            }
        });

        // Sincronizar os botões de navegação superior e inferior
        $('#next-step-top').on('click', function() {
            $('#next-step').click();
        });
        
        $('#prev-step-top').on('click', function() {
            $('#prev-step').click();
        });
        
        // Função para rolar para o topo quando um novo imóvel é adicionado
        function scrollToTop() {
            $('html, body').animate({
                scrollTop: $('.immobile-create-flow-container').offset().top - 50
            }, 500);
        }
        
        // Quando um novo imóvel é adicionado com sucesso
        $(document).on('immobile_added', function(e, response) {
            // Rolar para o topo após adicionar um novo imóvel
            scrollToTop();
            
            // Mostrar mensagem de sucesso
            Swal.fire({
                title: 'Sucesso!',
                text: 'Imóvel adicionado com sucesso!',
                icon: 'success',
                confirmButtonText: 'OK'
            });
        });
        
        // Atualizar visibilidade dos botões no topo
        $(document).on('step_change', function(e, currentStep) {
            if (currentStep > 1) {
                $('#prev-step-top').show();
            } else {
                $('#prev-step-top').hide();
            }
            
            if (currentStep === 3) {
                $('#next-step-top').hide();
            } else {
                $('#next-step-top').show();
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('criar_imoveis', 'create_immobile_flow');
?>