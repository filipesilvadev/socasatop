/**
 * Sistema unificado de pagamento com Mercado Pago
 * Este arquivo centraliza a lógica de pagamento para ser utilizada em diferentes contextos:
 * - Destaque de imóveis
 * - Pagamento durante fluxo de publicação
 * - Outros serviços futuros
 * 
 * Versão modular atualizada - não gerada dinamicamente
 */

(function($) {
    'use strict';

    // Objeto global para o sistema de pagamento
    window.SocasaPayment = {
        mp: null,
        cardForm: null,
        config: {
            publicKey: '',
            amount: 0,
            ajaxUrl: '',
            nonce: '',
            formSelector: '#payment-form',
            buttonSelector: '#process-payment',
            termsSelector: '#accept-terms',
            resultSelector: '#payment-result',
            productId: '',         // ID do produto
            entityId: 0,           // ID do imóvel ou outra entidade
            successRedirect: '',
            cardListSelector: '.saved-cards-list',
            newCardSelector: '#new-card-option',
            multiProduct: false,   // Indica se é checkout multi-produto
            productIds: [],        // Array de IDs de produtos para multi-produto
            entityIds: [],          // Array de IDs de entidades para multi-produto
            testMode: true,
            locale: 'pt-BR',
            siteUrl: '',
            debug: false,
            autoInitCardForm: true // Indica se deve inicializar o formulário automaticamente
        },
        
        /**
         * Inicializa o sistema de pagamento
         * @param {Object} config - Configurações do sistema de pagamento
         */
        init: function(config) {
            // Verificar se o Mercado Pago SDK está disponível
            if (typeof window.MercadoPago === 'undefined') {
                console.error('MercadoPago SDK não está disponível. Verifique a conexão com a internet e tente novamente.');
                this.showCardFormError('Não foi possível carregar a biblioteca de pagamento. Verifique sua conexão com a internet e tente novamente.');
                return;
            }
            
            // Mesclar as configurações padrão com as configurações fornecidas
            this.config = Object.assign(this.config, config || {});
            
            // Inicializar objeto do Mercado Pago
            this.mp = new window.MercadoPago(this.config.publicKey, {
                locale: this.config.locale
            });
            
            // Se for multi-produto, configurar eventos específicos
            if (this.config.multiProduct) {
                this.setupMultiProductEvents();
            }
            
            // Caso autoInitCardForm esteja habilitado, inicializar o formulário de cartão
            if (this.config.autoInitCardForm) {
                this.initCardForm();
            }
            
            // Log de inicialização
            if (this.config.debug) {
                console.log('SocasaPayment inicializado com configurações:', this.config);
            }
        },
        
        // Inicializar formulários de pagamento
        initPaymentForms: function() {
            const self = this;

            // Procurar por formulários de pagamento na página
            $('.socasa-payment-form').each(function() {
                const $form = $(this);
                const formId = $form.attr('id');

                // Inicializar abas de métodos de pagamento
                self.initPaymentTabs($form);

                // Inicializar formulário de cartão
                self.initCardForm($form);

                // Inicializar seleção de cartões salvos
                self.initSavedCards($form);

                // Inicializar botão de pagamento
                self.initPaymentButton($form);
            });
        },
        
        // Inicializar abas de métodos de pagamento
        initPaymentTabs: function($form) {
            const $tabs = $form.find('.payment-method-tabs');
            const $tabContents = $form.find('.payment-method-content');

            $tabs.find('.payment-tab').on('click', function(e) {
                e.preventDefault();
                const target = $(this).data('target');

                // Ativar aba
                $tabs.find('.payment-tab').removeClass('active');
                $(this).addClass('active');

                // Mostrar conteúdo
                $tabContents.hide();
                $form.find('.' + target).show();

                // Atualizar método de pagamento selecionado
                $form.find('input[name="payment_method"]').val(target);
            });

            // Ativar primeira aba por padrão
            $tabs.find('.payment-tab:first').trigger('click');
        },
        
        // Inicializar formulário de cartão
        initCardForm: function($form) {
            try {
                // Verificar se o elemento de formulário existe na página
                if (!document.getElementById('cardNumberContainer')) {
                    if (this.config.debug) {
                        console.warn('Elemento #cardNumberContainer não encontrado na página. Ignorando inicialização do formulário de cartão.');
                    }
                    return;
                }
                
                // Configurar o formulário de cartão
                if (this.config.debug) {
                    console.log('Inicializando formulário de cartão...');
                }
                
                const cardForm = this.mp.cardForm({
                    amount: this.config.amount || '0.00',
                    autoMount: true,
                    processingMode: 'aggregator',
                    form: {
                        id: 'card-form',
                        cardNumber: {
                            id: 'cardNumberContainer',
                            placeholder: 'Número do cartão',
                        },
                        expirationDate: {
                            id: 'expirationDateContainer',
                            placeholder: 'MM/YY',
                        },
                        securityCode: {
                            id: 'securityCodeContainer',
                            placeholder: 'CVV',
                        },
                        cardholderName: {
                            id: 'cardholderName',
                            placeholder: 'Nome no cartão',
                        },
                        installments: {
                            id: 'installments',
                            placeholder: 'Parcelas',
                        }
                    },
                    callbacks: {
                        onFormMounted: error => {
                            if (error) {
                                if (this.config.debug) {
                                    console.error('Erro ao montar formulário de cartão:', error);
                                }
                                this.showCardFormError('Não foi possível inicializar o formulário de cartão. Por favor, tente novamente mais tarde.');
                                return;
                            }
                            
                            if (this.config.debug) {
                                console.log('Formulário de cartão montado com sucesso');
                            }
                        },
                        onCardTokenReceived: (error, token) => {
                            if (error) {
                                if (this.config.debug) {
                                    console.error('Erro ao gerar token do cartão:', error);
                                }
                                return;
                            }
                            
                            if (this.config.debug) {
                                console.log('Token de cartão gerado:', token);
                            }
                        }
                    }
                });
                
                // Armazenar o objeto do formulário de cartão
                this.cardForm = cardForm;
                
                if (this.config.debug) {
                    console.log('Formulário de cartão inicializado com sucesso');
                }
            } catch (error) {
                if (this.config.debug) {
                    console.error('Erro ao inicializar formulário de cartão:', error);
                }
                this.showCardFormError('Não foi possível inicializar o formulário de cartão. Por favor, tente novamente mais tarde.');
            }
        },
        
        // Exibe erro no formulário de cartão
        showCardFormError: function(message) {
            // Buscar containers de mensagem
            const containers = [
                document.querySelector('.card-form-error'),
                document.querySelector('.payment-messages'),
                document.querySelector('.mp-form')
            ];
            
            // Localizar o primeiro container disponível
            let container = null;
            for (let i = 0; i < containers.length; i++) {
                if (containers[i]) {
                    container = containers[i];
                    break;
                }
            }
            
            // Se nenhum container específico for encontrado, criar um
            if (!container) {
                const cardForm = document.getElementById('card-form');
                if (cardForm) {
                    container = document.createElement('div');
                    container.className = 'card-form-error';
                    cardForm.prepend(container);
                } else {
                    // Caso extremo: nenhum lugar para exibir a mensagem
                    if (this.config.debug) {
                        console.error('Não foi possível encontrar lugar para exibir mensagem de erro:', message);
                    }
                    alert(message);
                    return;
                }
            }
            
            // Exibir mensagem
            container.innerHTML = '<div class="payment-error">' + message + '</div>';
        },
        
        // Configura eventos para checkout multi-produto
        setupMultiProductEvents: function() {
            const self = this;
            jQuery(document).ready(function($) {
                const $checkoutForm = $('.multi-product-checkout');
                if (!$checkoutForm.length) {
                    return;
                }
                
                // Alternar entre cartão salvo e novo cartão
                $checkoutForm.on('change', 'input[name="payment_method"]', function() {
                    const value = $(this).val();
                    
                    if (value === 'new_card') {
                        $('.new-card-form').slideDown();
                        
                        // Inicializar ou reinicializar o formulário do cartão, se necessário
                        if (!self.cardForm) {
                            self.initCardForm();
                        }
                    } else {
                        $('.new-card-form').slideUp();
                    }
                });
                
                // Processar pagamento quando o formulário for enviado
                $checkoutForm.on('submit', '#payment-form', function(e) {
                    e.preventDefault();
                    self.processMultiProductPayment($(this));
                });
                
                // Iniciar com o método de pagamento selecionado
                const selectedMethod = $checkoutForm.find('input[name="payment_method"]:checked').val();
                if (selectedMethod === 'new_card') {
                    $('.new-card-form').show();
                } else {
                    $('.new-card-form').hide();
                }
                
                // Adicionar botão de pagamento se não existir
                if (!$checkoutForm.find('.payment-button').length) {
                    $checkoutForm.find('#payment-form').append(
                        '<div class="payment-messages"></div>' +
                        '<button type="submit" class="checkout-button payment-button">Finalizar Pagamento</button>'
                    );
                }
            });
        },
        
        // Processa pagamento para checkout multi-produto
        processMultiProductPayment: function($form) {
            const self = this;
            const $ = jQuery;
            
            // Obter botão de pagamento
            const $button = $form.find('.payment-button');
            
            // Desabilitar botão para evitar múltiplos envios
            $button.prop('disabled', true).text('Processando...');
            
            // Limpar mensagens anteriores
            $form.find('.payment-messages').empty();
            
            // Obter método de pagamento
            const paymentMethod = $form.find('input[name="payment_method"]:checked').val();
            
            // Preparar dados de pagamento
            const paymentData = {
                action: 'handle_multi_product_payment',
                nonce: this.config.nonce,
                payment_method: paymentMethod
            };
            
            if (paymentMethod === 'saved_card') {
                // Usar cartão salvo
                const selectedCard = $form.find('input[name="payment_method"]:checked');
                if (!selectedCard.length) {
                    this.handlePaymentError($form, 'Por favor, selecione um cartão para continuar.');
                    return;
                }
                
                // Obter ID do cartão selecionado
                const cardId = selectedCard.data('card-id');
                if (!cardId) {
                    this.handlePaymentError($form, 'Cartão inválido. Por favor, selecione outro cartão ou adicione um novo.');
                    return;
                }
                
                // Adicionar ID do cartão aos dados de pagamento
                paymentData.saved_card_id = cardId;
                
                // Enviar solicitação de pagamento
                this.sendPaymentRequest(paymentData, $form);
                
            } else if (paymentMethod === 'new_card') {
                // Usar novo cartão
                if (!this.cardForm) {
                    this.handlePaymentError($form, 'O formulário de cartão não foi inicializado corretamente. Por favor, recarregue a página e tente novamente.');
                    return;
                }
                
                // Obter token do cartão
                this.cardForm.createCardToken().then(response => {
                    if (response.error) {
                        this.handlePaymentError($form, 'Erro ao processar dados do cartão: ' + response.error);
                        return;
                    }
                    
                    // Adicionar token aos dados de pagamento
                    paymentData.card_token = response.token;
                    
                    // Verificar se deve salvar o cartão
                    const saveCard = $form.find('#save-card-checkbox').is(':checked');
                    if (saveCard) {
                        paymentData.save_card = true;
                    }
                    
                    // Enviar solicitação de pagamento
                    this.sendPaymentRequest(paymentData, $form);
                    
                }).catch(error => {
                    this.handlePaymentError($form, 'Erro ao processar cartão: ' + (error.message || 'Verifique os dados e tente novamente.'));
                });
            } else {
                this.handlePaymentError($form, 'Por favor, selecione um método de pagamento válido.');
            }
        },
        
        // Envia requisição de pagamento
        sendPaymentRequest: function(paymentData, $form) {
            const $ = jQuery;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: paymentData,
                dataType: 'json',
                success: response => {
                    if (response.success) {
                        this.handlePaymentSuccess($form, response);
                    } else {
                        const message = response.data && response.data.message ? response.data.message : 'Ocorreu um erro ao processar o pagamento. Por favor, tente novamente.';
                        this.handlePaymentError($form, message);
                    }
                },
                error: (xhr, status, error) => {
                    this.handlePaymentError($form, 'Erro na comunicação com o servidor: ' + (error || 'Verifique sua conexão e tente novamente.'));
                }
            });
        },
        
        // Inicializar seleção de cartões salvos
        initSavedCards: function($form) {
            const self = this;
            const $savedCardsSection = $form.find('.saved-cards-section');
            
            if ($savedCardsSection.length === 0) return;
            
            // Adicionar evento de seleção de cartão
            $savedCardsSection.find('.saved-card').on('click', function() {
                const $card = $(this);
                
                // Remover seleção anterior
                $savedCardsSection.find('.saved-card').removeClass('selected');
                
                // Selecionar cartão
                $card.addClass('selected');
                
                // Atualizar campo oculto com o ID do cartão
                $form.find('input[name="card_id"]').val($card.data('card-id'));
                
                // Habilitar botão de pagamento
                self.updatePaymentButtonState($form);
            });
        },
        
        // Inicializar botão de pagamento
        initPaymentButton: function($form) {
            const self = this;
            const $button = $form.find('.payment-button');
            
            $button.on('click', function(e) {
                e.preventDefault();
                
                // Verificar se o formulário é válido
                if (!self.validatePaymentForm($form)) {
                    return;
                }
                
                // Desabilitar botão durante o processamento
                $button.prop('disabled', true).text('Processando...');
                
                // Processar pagamento
                const paymentMethod = $form.find('input[name="payment_method"]').val();
                
                if (paymentMethod === 'saved-card') {
                    self.processSavedCardPayment($form);
                } else if (paymentMethod === 'new-card') {
                    self.processNewCardPayment($form);
                }
            });
            
            // Atualizar estado inicial do botão
            self.updatePaymentButtonState($form);
        },
        
        // Validar formulário de pagamento
        validatePaymentForm: function($form) {
            const paymentMethod = $form.find('input[name="payment_method"]').val();
            
            if (paymentMethod === 'saved-card') {
                // Verificar se um cartão foi selecionado
                const cardId = $form.find('input[name="card_id"]').val();
                
                if (!cardId) {
                    this.showError($form, 'Selecione um cartão para continuar.');
                    return false;
                }
            } else if (paymentMethod === 'new-card') {
                // Verificar se os campos do cartão são válidos
                const cardFields = $form.data('cardFields');
                
                if (!cardFields) {
                    this.showError($form, 'Erro ao inicializar o formulário de cartão.');
                    return false;
                }
                
                const fieldStates = {
                    cardNumber: cardFields.cardNumber.getState(),
                    expirationDate: cardFields.expirationDate.getState(),
                    securityCode: cardFields.securityCode.getState(),
                    cardholderName: cardFields.cardholderName.getState()
                };
                
                if (!fieldStates.cardNumber.valid) {
                    this.showError($form, 'Número do cartão inválido.');
                    return false;
                }
                
                if (!fieldStates.expirationDate.valid) {
                    this.showError($form, 'Data de validade inválida.');
                    return false;
                }
                
                if (!fieldStates.securityCode.valid) {
                    this.showError($form, 'Código de segurança inválido.');
                    return false;
                }
                
                if (!fieldStates.cardholderName.valid) {
                    this.showError($form, 'Nome do titular inválido.');
                    return false;
                }
                
                // Verificar se o CPF foi preenchido
                const cpf = $form.find('input[name="cpf"]').val();
                
                if (!cpf || !this.validateCPF(cpf)) {
                    this.showError($form, 'CPF inválido.');
                    return false;
                }
            }
            
            return true;
        },
        
        // Validar CPF
        validateCPF: function(cpf) {
            cpf = cpf.replace(/[^\d]+/g, '');
            
            if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
                return false;
            }
            
            let sum = 0;
            let remainder;
            
            for (let i = 1; i <= 9; i++) {
                sum = sum + parseInt(cpf.substring(i - 1, i)) * (11 - i);
            }
            
            remainder = (sum * 10) % 11;
            
            if ((remainder === 10) || (remainder === 11)) {
                remainder = 0;
            }
            
            if (remainder !== parseInt(cpf.substring(9, 10))) {
                return false;
            }
            
            sum = 0;
            
            for (let i = 1; i <= 10; i++) {
                sum = sum + parseInt(cpf.substring(i - 1, i)) * (12 - i);
            }
            
            remainder = (sum * 10) % 11;
            
            if ((remainder === 10) || (remainder === 11)) {
                remainder = 0;
            }
            
            if (remainder !== parseInt(cpf.substring(10, 11))) {
                return false;
            }
            
            return true;
        },
        
        // Processar pagamento com cartão salvo
        processSavedCardPayment: function($form) {
            const self = this;
            const formData = this.getFormData($form);
            
            // Adicionar método de pagamento
            formData.payment_method = 'saved-card';
            
            // Enviar requisição AJAX
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'handle_multi_product_payment',
                    payment_data: formData,
                    security: self.config.nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        self.handlePaymentSuccess($form, response);
                    } else {
                        self.handlePaymentError($form, response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    self.handlePaymentError($form, 'Erro ao processar o pagamento. Tente novamente.');
                }
            });
        },
        
        // Processar pagamento com novo cartão
        processNewCardPayment: function($form) {
            const self = this;
            const cardFields = $form.data('cardFields');
            
            // Criar token do cartão
            self.mp.fields.createCardToken({
                cardholderName: cardFields.cardholderName.getValue(),
                identificationType: 'CPF',
                identificationNumber: $form.find('input[name="cpf"]').val().replace(/[^\d]+/g, '')
            }).then(function(token) {
                // Obter dados do formulário
                const formData = self.getFormData($form);
                
                // Adicionar token e método de pagamento
                formData.token = token.id;
                formData.payment_method = 'new-card';
                formData.save_card = $form.find('input[name="save_card"]').is(':checked');
                
                // Enviar requisição AJAX
                $.ajax({
                    url: self.config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'handle_multi_product_payment',
                        payment_data: formData,
                        security: self.config.nonce
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            self.handlePaymentSuccess($form, response);
                        } else {
                            self.handlePaymentError($form, response.data.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        self.handlePaymentError($form, 'Erro ao processar o pagamento. Tente novamente.');
                    }
                });
            }).catch(function(error) {
                self.handlePaymentError($form, 'Erro ao gerar token do cartão: ' + error.message);
            });
        },
        
        // Obter dados do formulário
        getFormData: function($form) {
            const formData = {};
            
            // Obter produtos
            formData.products = [];
            $form.find('input[name="products[]"]').each(function() {
                formData.products.push($(this).val());
            });
            
            // Obter ID do cartão (se for cartão salvo)
            const cardId = $form.find('input[name="card_id"]').val();
            if (cardId) {
                formData.card_id = cardId;
            }
            
            // Obter dados adicionais
            formData.form_id = $form.attr('id');
            
            return formData;
        },
        
        // Tratar sucesso no pagamento
        handlePaymentSuccess: function($form, response) {
            const $button = $form.find('.payment-button');
            const $messageContainer = $form.find('.payment-messages');
            
            // Exibir mensagem de sucesso
            $messageContainer.html('<div class="payment-success">' + response.data.message + '</div>');
            
            // Resetar botão
            $button.prop('disabled', false).text('Pagar');
            
            // Redirecionar se necessário
            if (response.data.redirect_url) {
                setTimeout(function() {
                    window.location.href = response.data.redirect_url;
                }, 2000);
            }
        },
        
        // Tratar erro no pagamento
        handlePaymentError: function($form, message) {
            const $button = $form.find('.payment-button');
            
            // Exibir mensagem de erro
            this.showError($form, message);
            
            // Resetar botão
            $button.prop('disabled', false).text('Pagar');
        },
        
        // Exibir mensagem de erro
        showError: function($form, message) {
            const $messageContainer = $form.find('.payment-messages');
            
            $messageContainer.html('<div class="payment-error">' + message + '</div>');
            
            // Rolar para a mensagem
            $('html, body').animate({
                scrollTop: $messageContainer.offset().top - 100
            }, 500);
        },
        
        // Atualizar estado do botão de pagamento
        updatePaymentButtonState: function($form) {
            const paymentMethod = $form.find('input[name="payment_method"]').val();
            const $button = $form.find('.payment-button');
            
            if (paymentMethod === 'saved-card') {
                // Habilitar botão se um cartão foi selecionado
                const cardId = $form.find('input[name="card_id"]').val();
                $button.prop('disabled', !cardId);
            } else if (paymentMethod === 'new-card') {
                // O botão será habilitado quando o usuário preencher todos os campos
                $button.prop('disabled', false);
            }
        }
    };

    // Inicializar quando o documento estiver pronto
    $(document).ready(function() {
        // Verificar se há configurações de pagamento na página
        if (typeof window.socasaPaymentConfig !== 'undefined') {
            // Inicializar o sistema de pagamento
            window.SocasaPayment.init(window.socasaPaymentConfig);
        }
    });

    /**
     * Carrega os cartões salvos do usuário para a página de publicação
     */
    function loadSavedCardsForPublication() {
        // Verificar se estamos na página de publicação
        if (!document.querySelector('.property-publish-form') && !document.querySelector('.add-property-form')) {
            return;
        }
        
        // Verificar se o elemento container para cartões existe
        const savedCardsContainer = document.querySelector('#saved-payment-cards');
        if (!savedCardsContainer) {
            console.warn("Container para cartões salvos não encontrado");
            return;
        }
        
        // Mostrar indicador de carregamento
        savedCardsContainer.innerHTML = '<div class="loading-cards">Carregando cartões salvos...</div>';
        
        // Verificar se ajaxurl está definido
        if (typeof ajaxurl === 'undefined') {
            if (typeof payment_settings !== 'undefined' && payment_settings.ajax_url) {
                window.ajaxurl = payment_settings.ajax_url;
            } else if (typeof wp_ajax !== 'undefined' && wp_ajax.url) {
                window.ajaxurl = wp_ajax.url;
            } else {
                console.error("URL do Ajax não disponível");
                savedCardsContainer.innerHTML = '<div class="card-error">Não foi possível carregar seus cartões. Atualize a página e tente novamente.</div>';
                return;
            }
        }
        
        // Fazer requisição AJAX para obter os cartões
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_user_saved_cards',
                nonce: document.querySelector('input[name="payment_nonce"]') ? document.querySelector('input[name="payment_nonce"]').value : ''
            },
            success: function(response) {
                if (response.success && response.data.cards) {
                    renderSavedCards(response.data.cards, response.data.default_card, savedCardsContainer);
                } else {
                    savedCardsContainer.innerHTML = '<div class="no-cards">Você ainda não possui cartões salvos. <a href="/configuracoes-de-pagamento/">Adicionar cartão</a></div>';
                }
            },
            error: function() {
                savedCardsContainer.innerHTML = '<div class="card-error">Erro ao carregar cartões. Tente novamente mais tarde.</div>';
            }
        });
    }

    /**
     * Renderiza os cartões salvos no container
     */
    function renderSavedCards(cards, defaultCardId, container) {
        if (!cards || !cards.length) {
            container.innerHTML = '<div class="no-cards">Você ainda não possui cartões salvos. <a href="/configuracoes-de-pagamento/">Adicionar cartão</a></div>';
            return;
        }
        
        let html = '<div class="saved-cards-list">';
        
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
                            <img src="${getCardBrandLogoUrl(cardBrand)}" alt="${cardBrand}" 
                                onerror="this.src='${getCardBrandLogoUrl('generic-card')}';">
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
        
        html += '</div>';
        container.innerHTML = html;
    }

    /**
     * Obtém a URL do logo da bandeira do cartão
     */
    function getCardBrandLogoUrl(brand) {
        const themeUrl = window.location.origin + '/wp-content/themes/socasatop';
        const basePath = themeUrl + '/inc/custom/broker/assets/images/card-brands/';
        
        // Normalizar nome da bandeira
        brand = brand.toLowerCase().replace(/\s+/g, '');
        
        // Lista de bandeiras conhecidas
        const knownBrands = ['visa', 'mastercard', 'amex', 'discover', 'diners', 'elo', 'hipercard', 'jcb'];
        
        if (knownBrands.includes(brand)) {
            return basePath + brand + '.svg';
        }
        
        // Mapeamento de nomes alternativos para bandeiras conhecidas
        const brandMapping = {
            'visa': ['visa', 'visacredito', 'visadebito'],
            'mastercard': ['mastercard', 'master', 'mastercardcredito', 'mastercarddebito'],
            'amex': ['amex', 'americanexpress']
        };
        
        for (const [key, aliases] of Object.entries(brandMapping)) {
            if (aliases.includes(brand)) {
                return basePath + key + '.svg';
            }
        }
        
        // Retornar logo genérico se a bandeira não for reconhecida
        return basePath + 'generic-card.png';
    }

    // Inicializar o carregamento de cartões quando o DOM estiver pronto
    jQuery(document).ready(function($) {
        // Verificar se estamos na página de publicação
        if (document.querySelector('.property-publish-form') || document.querySelector('.add-property-form')) {
            loadSavedCardsForPublication();
        }
    });

})(jQuery); 