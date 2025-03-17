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
    // Objeto global para o sistema de pagamento
    window.SocasaPayment = {
        mp: null,
        cardForm: null,
        config: {},
        
        /**
         * Inicializa o sistema de pagamento
         * @param {Object} config - Configurações do sistema de pagamento
         */
        init: function(config) {
            this.config = Object.assign({
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
                entityIds: []          // Array de IDs de entidades para multi-produto
            }, config);
            
            // Inicializar o SDK do Mercado Pago
            if (typeof MercadoPago !== 'undefined') {
                this.mp = new MercadoPago(this.config.publicKey);
                this.setupCardForm();
                this.setupEventHandlers();
                
                console.log('SocasaPayment inicializado com sucesso');
            } else {
                console.error('SDK do Mercado Pago não encontrado. Verifique se o script foi carregado corretamente.');
                this.showErrorMessage('Erro ao carregar o sistema de pagamento. Por favor, recarregue a página.');
            }
            
            return this;
        },
        
        /**
         * Configura o formulário de cartão
         */
        setupCardForm: function() {
            const cardFormSettings = {
                amount: this.config.amount,
                autoMount: true,
                form: {
                    id: "card-form",
                    cardholderName: {
                        id: "cardholderName",
                        placeholder: "Nome como está no cartão",
                    },
                    cardNumber: {
                        id: "cardNumberContainer",
                        placeholder: "Número do cartão",
                    },
                    expirationDate: {
                        id: "expirationDateContainer",
                        placeholder: "MM/YY",
                    },
                    securityCode: {
                        id: "securityCodeContainer",
                        placeholder: "CVV",
                    }
                },
                callbacks: {
                    onFormMounted: error => {
                        if (error) {
                            console.error("Erro ao montar formulário:", error);
                            this.showErrorMessage("Erro ao carregar formulário. Por favor, tente novamente mais tarde.");
                        } else {
                            console.log("Formulário montado com sucesso");
                        }
                    },
                    onSubmit: event => {
                        event.preventDefault();
                        
                        // Obter dados do formulário
                        const cardData = this.cardForm.getCardFormData();
                        
                        // Processar pagamento
                        this.processNewCardPayment(cardData);
                    },
                    onFetching: (resource) => {
                        console.log("Buscando recurso:", resource);
                        
                        // Mostra indicação de carregamento
                        $(this.config.buttonSelector).prop("disabled", true);
                        $(this.config.buttonSelector).text("Processando...");
                        
                        return () => {
                            $(this.config.buttonSelector).prop("disabled", false);
                            $(this.config.buttonSelector).text("Pagar");
                        };
                    }
                }
            };
            
            try {
                this.cardForm = this.mp.cardForm(cardFormSettings);
                console.log("Formulário de cartão inicializado");
            } catch (error) {
                console.error("Erro ao inicializar formulário de cartão:", error);
            }
        },
        
        /**
         * Configura os manipuladores de eventos
         */
        setupEventHandlers: function() {
            const self = this;
            
            // Evento de processamento de pagamento
            $(this.config.formSelector).on('submit', function(e) {
                e.preventDefault();
                
                if (self.config.termsSelector && !$(self.config.termsSelector).is(":checked")) {
                    self.showErrorMessage("Você precisa aceitar os termos e condições para continuar.");
                    return;
                }
                
                // Verificar se está usando cartão salvo ou novo cartão
                const selectedCard = $('input[name="payment_method"]:checked').val();
                
                if (selectedCard && selectedCard !== 'new') {
                    // Processar com cartão salvo
                    self.processSavedCardPayment(selectedCard);
                } else {
                    // O processamento de novo cartão é tratado pelo onSubmit do cardForm
                }
            });
            
            // Alternar entre cartão novo e cartões salvos
            $('input[name="payment_method"]').on('change', function() {
                const selectedValue = $(this).val();
                
                if (selectedValue === 'new') {
                    $('.new-card-form').show();
                } else {
                    $('.new-card-form').hide();
                }
            });
        },
        
        /**
         * Processa o pagamento com um novo cartão
         */
        processNewCardPayment: function(cardData) {
            const self = this;
            
            if (!cardData || !cardData.token) {
                self.showErrorMessage('Por favor, preencha os dados do cartão corretamente.');
                return;
            }
            
            // Preparar dados para o processamento
            const paymentData = {
                payment_method: 'card',
                payment_data: {
                    token: cardData.token,
                    issuer_id: cardData.issuerId,
                    payment_method_id: cardData.paymentMethodId,
                    transaction_amount: parseFloat(self.config.amount),
                    installments: parseInt(cardData.installments || 1),
                    description: 'Pagamento de Serviço',
                    payer: {
                        email: cardData.cardholderEmail || '',
                        identification: {
                            type: cardData.identificationType || 'CPF',
                            number: cardData.identificationNumber || ''
                        }
                    }
                }
            };
            
            // Processar o pagamento
            if (self.config.multiProduct) {
                self.processMultiProductPayment(paymentData);
            } else {
                self.processSingleProductPayment(paymentData);
            }
        },
        
        /**
         * Processa pagamento com cartão salvo
         */
        processSavedCardPayment: function(cardId) {
            const self = this;
            
            if (!cardId) {
                self.showErrorMessage('Selecione um cartão para continuar.');
                return;
            }
            
            // Mostrar indicador de carregamento
            self.showLoading();
            
            // Preparar dados para o processamento
            const paymentData = {
                payment_method: 'saved_card',
                payment_data: {
                    saved_card_id: cardId,
                    amount: parseFloat(self.config.amount),
                    description: 'Pagamento de Serviço'
                }
            };
            
            // Processar o pagamento
            if (self.config.multiProduct) {
                self.processMultiProductPayment(paymentData);
            } else {
                self.processSingleProductPayment(paymentData);
            }
        },
        
        /**
         * Processa um pagamento para um único produto
         */
        processSingleProductPayment: function(paymentData) {
            const self = this;
            
            // Mostrar indicador de carregamento
            self.showLoading();
            
            // Enviar dados para o servidor
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'handle_unified_payment',
                    nonce: self.config.nonce,
                    payment_data: JSON.stringify(paymentData),
                    product_id: self.config.productId,
                    entity_id: self.config.entityId
                },
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success) {
                        self.showSuccessMessage(response.data.message, response.data);
                        
                        // Redirecionar após alguns segundos, se configurado
                        if (self.config.successRedirect) {
                            setTimeout(function() {
                                window.location.href = self.config.successRedirect;
                            }, 3000);
                        }
                    } else {
                        self.showErrorMessage(response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    self.hideLoading();
                    self.showErrorMessage('Erro ao processar pagamento: ' + error);
                }
            });
        },
        
        /**
         * Processa um pagamento para múltiplos produtos
         */
        processMultiProductPayment: function(paymentData) {
            const self = this;
            
            // Mostrar indicador de carregamento
            self.showLoading();
            
            // Enviar dados para o servidor
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'handle_multi_product_payment',
                    nonce: self.config.nonce,
                    payment_data: JSON.stringify(paymentData),
                    product_ids: JSON.stringify(self.config.productIds),
                    entity_ids: JSON.stringify(self.config.entityIds)
                },
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success) {
                        self.showSuccessMessage(response.data.message, response.data);
                        
                        // Redirecionar após alguns segundos, se configurado
                        if (self.config.successRedirect) {
                            setTimeout(function() {
                                window.location.href = self.config.successRedirect;
                            }, 3000);
                        }
                    } else {
                        self.showErrorMessage(response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    self.hideLoading();
                    self.showErrorMessage('Erro ao processar pagamento: ' + error);
                }
            });
        },
        
        /**
         * Exibe mensagem de sucesso
         */
        showSuccessMessage: function(message, data = {}) {
            const self = this;
            const formContainer = $(self.config.formSelector);
            const statusContainer = $('#payment-status');
            const successMessage = statusContainer.find('.success-message');
            const paymentDetails = successMessage.find('.payment-details');
            
            // Adicionar detalhes do pagamento
            let detailsHtml = '';
            if (data.payment_id) {
                detailsHtml += '<p><strong>ID da Transação:</strong> ' + data.payment_id + '</p>';
            }
            if (data.status) {
                const statusText = {
                    'approved': 'Aprovado',
                    'pending': 'Pendente',
                    'in_process': 'Em Processamento',
                    'rejected': 'Rejeitado',
                    'refunded': 'Reembolsado',
                    'cancelled': 'Cancelado',
                    'in_mediation': 'Em Mediação'
                };
                detailsHtml += '<p><strong>Status:</strong> ' + (statusText[data.status] || data.status) + '</p>';
            }
            
            if (paymentDetails.length) {
                paymentDetails.html(detailsHtml);
            } else {
                successMessage.find('.status-message').after('<div class="payment-details">' + detailsHtml + '</div>');
            }
            
            // Configurar botão de continuar
            const continueButton = successMessage.find('.continue-button');
            if (continueButton.length && self.config.successRedirect) {
                continueButton.attr('href', self.config.successRedirect);
            } else if (continueButton.length) {
                continueButton.hide();
            }
            
            // Mostrar mensagem de sucesso
            formContainer.hide();
            statusContainer.show();
            successMessage.show();
        },
        
        /**
         * Exibe mensagem de erro
         */
        showErrorMessage: function(message) {
            const self = this;
            const formContainer = $(self.config.formSelector);
            const statusContainer = $('#payment-status');
            const errorMessage = statusContainer.find('.error-message');
            
            // Adicionar detalhes do erro
            const errorDetails = errorMessage.find('.error-details');
            if (errorDetails.length) {
                errorDetails.text(message);
            } else {
                errorMessage.find('.status-message').text(message);
            }
            
            // Configurar botão de tentar novamente
            const retryButton = errorMessage.find('.retry-button');
            if (retryButton.length) {
                retryButton.on('click', function() {
                    statusContainer.hide();
                    errorMessage.hide();
                    formContainer.show();
                });
            }
            
            // Mostrar mensagem de erro
            formContainer.hide();
            statusContainer.show();
            errorMessage.show();
        },
        
        /**
         * Mostra o indicador de carregamento
         */
        showLoading: function() {
            if ($('.payment-loading-overlay').length === 0) {
                $('body').append('<div class="payment-loading-overlay"><div class="payment-loading-spinner"></div></div>');
                
                // Adicionar estilos se ainda não existirem
                if ($('#payment-loading-styles').length === 0) {
                    $('head').append(`
                        <style id="payment-loading-styles">
                            .payment-loading-overlay {
                                position: fixed;
                                top: 0;
                                left: 0;
                                width: 100%;
                                height: 100%;
                                background-color: rgba(0, 0, 0, 0.5);
                                display: flex;
                                justify-content: center;
                                align-items: center;
                                z-index: 9999;
                            }
                            .payment-loading-spinner {
                                width: 50px;
                                height: 50px;
                                border: 5px solid #f3f3f3;
                                border-top: 5px solid #3498db;
                                border-radius: 50%;
                                animation: spin 2s linear infinite;
                            }
                            @keyframes spin {
                                0% { transform: rotate(0deg); }
                                100% { transform: rotate(360deg); }
                            }
                        </style>
                    `);
                }
            }
        },
        
        /**
         * Esconde o indicador de carregamento
         */
        hideLoading: function() {
            $('.payment-loading-overlay').remove();
        }
    };
})(jQuery); 