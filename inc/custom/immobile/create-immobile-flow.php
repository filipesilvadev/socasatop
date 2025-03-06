<?php
function create_immobile_flow() {
    if (!is_user_logged_in()) {
        return '<p>Você precisa estar logado para acessar esta página.</p>';
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
          'name' => 'Patrocinado',
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

    wp_enqueue_script('mercadopago-js', 'https://sdk.mercadopago.com/js/v2', [], null, true);

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


                            <div class="form-group">
                                <label for="immobile_gallery">Galeria de Imagens</label>
                                <input type="hidden" id="immobile_gallery" name="immobile_gallery" />
                                <button type="button" id="upload_gallery_button" class="upload-button">Adicionar Imagens</button>
                                <div id="gallery_preview" class="gallery-preview"></div>
                            </div>

                            <div class="form-group">
                                <label for="immobile_videos">Vídeos do Imóvel</label>
                                <input type="hidden" id="immobile_videos" name="immobile_videos" />
                                <button type="button" id="upload_videos_button" class="upload-button">Adicionar Vídeos</button>
                                <div id="videos_preview" class="videos-preview"></div>
                                <small class="form-text text-muted">Formatos aceitos: MP4, WebM (máx. 128MB)</small>
                            </div>

                            <button type="submit" class="save-button">Salvar</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="flow-step" id="step-2" style="display:none;">
                <h2>Impulsione suas Vendas</h2>
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

        <div class="flow-actions">
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
        padding: 20px;
    }

    .marketing-product {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
    }

    .video-preview video {
        width: 100%;
        border-radius: 4px;
    }

    .remove-video {
        position: absolute;
        top: -8px;
        right: -8px;
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
    </style>

<script>
    jQuery(document).ready(function($) {
        if (typeof MercadoPago === 'undefined') {
            console.error('MercadoPago não foi carregado');
            return;
        }

        let immobileList = [];
        const marketingProducts = <?php echo json_encode($marketing_products); ?>;
        const mp = new MercadoPago('TEST-70b46d06-add9-499a-942e-0f5c01b8769a');

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
                const productsHtml = Object.entries(marketingProducts).map(([key, product]) => `
                    <div class="marketing-product">
                        <div class="product-header">
                            <input type="checkbox" 
                                   id="product-${key}-${immobileIndex}" 
                                   name="marketing_products[${immobileIndex}][]" 
                                   value="${key}">
                            <label for="product-${key}-${immobileIndex}">
                                ${product.name} - R$${product.price.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </label>
                        </div>
                        <p class="product-description">${product.description}</p>
                    </div>
                `).join('');

                $marketingSection.append(`
                    <div class="marketing-immobile">
                        <h3>${immobile.immobile_name}</h3>
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
                
                attachments.forEach(attachment => {
                    $('#gallery_preview').append(`
                        <div class="gallery-image">
                            <img src="${attachment.url}" alt="" style="max-width: 100px;"/>
                        </div>
                    `);
                });
            });

            mediaUploader.open();
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

        function updateVideoIds() {
            var ids = [];
            $('.video-preview').each(function() {
                ids.push($(this).data('id'));
            });
            $('#immobile_videos').val(ids.join(','));
        }

        function setupPaymentBrick(totalValue) {
    const brickSettings = {
        initialization: {
            amount: totalValue
        },
        customization: {
            paymentMethods: {
                creditCard: 'all',
                debitCard: 'hidden',
                ticket: 'hidden'
            },
            visual: {
                hideFormLine: true,
                style: {
                    theme: 'default'
                }
            }
        },
        callbacks: {
            onReady: () => {},
            onSubmit: async (cardData) => {
                return new Promise((resolve, reject) => {
                    const paymentData = {
                        token: cardData.token,
                        installments: 1,
                        payment_method_id: cardData.payment_method_id,
                        transaction_amount: totalValue,
                        payer: {
                            email: cardData.payer.email,
                            identification: {
                                type: cardData.payer.identification.type,
                                number: cardData.payer.identification.number
                            }
                        }
                    };

                    $.ajax({
                        url: site.ajax_url,
                        method: 'POST',
                        data: {
                            action: 'process_immobile_creation_payment',
                            payment_data: paymentData,
                            immobile_list: immobileList,
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
                                resolve();
                            } else {
                                Swal.fire({
                                    title: 'Erro!',
                                    text: response.data.message || 'Erro ao processar pagamento',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                                reject();
                            }
                        },
                        error: function(error) {
                            Swal.fire({
                                title: 'Erro!',
                                text: 'Erro ao processar pagamento',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                            reject();
                        }
                    });
                });
            },
            onError: (error) => {
                console.error('Erro brick:', error);
                Swal.fire({
                    title: 'Erro!',
                    text: 'Erro ao processar pagamento',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        }
    };

    return mp.bricks().create('cardPayment', 'cardPaymentBrick_container', brickSettings);
}

        function updateSummary() {
    const $summaryList = $('#summary-list');
    const $totalSummary = $('#total-summary');
    let totalValue = 0;

    $summaryList.empty();
    
    immobileList.forEach((immobile, index) => {
        let immobileTotal = 25; // Valor base do imóvel
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
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('criar_imoveis', 'create_immobile_flow');
?>