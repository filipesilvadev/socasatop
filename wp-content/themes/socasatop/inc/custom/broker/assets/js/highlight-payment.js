(function($) {
    $(document).ready(function() {
        console.log('Highlight Payment JS loaded');
        
        // Funções para mostrar/esconder mensagens
        function showErrorMessage(message) {
            $('#payment-result').show();
            $('.error-message').show().text(message);
            $('.success-message').hide();
        }
        
        function showSuccessMessage(message) {
            $('#payment-result').show();
            $('.success-message').show().find('p').text(message);
            $('.error-message').hide();
        }
        
        function showLoading(show = true) {
            if (show) {
                $('.loading-overlay').fadeIn(300);
                $('.highlight-button').prop('disabled', true);
            } else {
                $('.loading-overlay').fadeOut(300);
                $('.highlight-button').prop('disabled', false);
            }
        }
        
        // Verificar se temos os dados necessários
        if (typeof highlight_payment === 'undefined') {
            console.error('Highlight payment data not found');
            showErrorMessage('Erro ao carregar dados do pagamento. Recarregue a página e tente novamente.');
            return;
        }
        
        // Função para determinar o ícone correto da bandeira do cartão
        function getCardIcon(cardType) {
            if (!cardType) return 'fa-credit-card';
            
            const cardTypeLC = cardType.toLowerCase();
            
            if (cardTypeLC.includes('visa')) {
                return 'fa-cc-visa';
            } else if (cardTypeLC.includes('master')) {
                return 'fa-cc-mastercard';
            } else if (cardTypeLC.includes('amex') || cardTypeLC.includes('american')) {
                return 'fa-cc-amex';
            } else if (cardTypeLC.includes('diners')) {
                return 'fa-cc-diners-club';
            } else if (cardTypeLC.includes('discover')) {
                return 'fa-cc-discover';
            } else if (cardTypeLC.includes('jcb')) {
                return 'fa-cc-jcb';
            } else if (cardTypeLC.includes('elo')) {
                return 'fa-cc-elo';
            } else if (cardTypeLC.includes('hipercard')) {
                return 'fa-credit-card';
            } else {
                return 'fa-credit-card';
            }
        }
        
        // Atualizar os ícones de cartão para cartões salvos
        function updateSavedCardIcons() {
            $('.saved-card-option').each(function() {
                const cardLabel = $(this).find('.card-details').text();
                let cardType = '';
                
                // Tentar extrair o tipo de cartão do texto
                if (cardLabel.toLowerCase().includes('visa')) {
                    cardType = 'visa';
                } else if (cardLabel.toLowerCase().includes('master')) {
                    cardType = 'mastercard';
                } else if (cardLabel.toLowerCase().includes('amex')) {
                    cardType = 'amex';
                } else if (cardLabel.toLowerCase().includes('diners')) {
                    cardType = 'diners';
                } else if (cardLabel.toLowerCase().includes('elo')) {
                    cardType = 'elo';
                } else if (cardLabel.toLowerCase().includes('hipercard')) {
                    cardType = 'hipercard';
                }
                
                // Se encontrou um tipo, atualizar o ícone
                if (cardType) {
                    const icon = getCardIcon(cardType);
                    const iconElement = $(this).find('.card-info i');
                    
                    if (iconElement.length) {
                        iconElement.attr('class', 'fab ' + icon);
                    } else {
                        $(this).find('.card-info').prepend('<i class="fab ' + icon + '"></i>');
                    }
                }
            });
        }
        
        // Variáveis para armazenar referências
        let mpInstance = null;
        let cardForm = null;
        
        // Função para inicializar o MercadoPago
        function initializeMercadoPago() {
            // Verificar se a biblioteca MercadoPago está disponível
            if (typeof MercadoPago === 'undefined') {
                console.error('MercadoPago SDK não está disponível');
                
                // Carregar o SDK do MercadoPago manualmente
                const script = document.createElement('script');
                script.src = 'https://sdk.mercadopago.com/js/v2';
                script.onload = function() {
                    console.log('MercadoPago SDK carregado manualmente');
                    if (typeof MercadoPago !== 'undefined') {
                        initializeMercadoPago();
                    } else {
                        showErrorMessage('Não foi possível carregar o SDK do MercadoPago. Recarregue a página ou tente novamente mais tarde.');
                    }
                };
                script.onerror = function() {
                    console.error('Erro ao carregar o SDK do MercadoPago manualmente');
                    showErrorMessage('Não foi possível carregar o SDK do MercadoPago. Verifique sua conexão com a internet.');
                };
                document.head.appendChild(script);
                return;
            }
            
            try {
                // Verificar se a chave pública está presente
                if (!highlight_payment.public_key) {
                    console.error('MercadoPago public key is missing');
                    showErrorMessage('Erro de configuração: Chave pública do MercadoPago não encontrada.');
                    return;
                }
                
                console.log('Inicializando MercadoPago com chave:', highlight_payment.public_key);
                mpInstance = new MercadoPago(highlight_payment.public_key);
                console.log('MercadoPago SDK inicializado com sucesso');
                
                // Inicializar o formulário de cartão
                initializeCardForm();
                
                // Atualizar ícones dos cartões salvos
                updateSavedCardIcons();
            } catch (e) {
                console.error('Erro ao inicializar MercadoPago:', e);
                showErrorMessage('Erro ao inicializar o gateway de pagamento: ' + (e.message || 'Verifique as configurações do MercadoPago.'));
            }
        }
        
        // Função para inicializar o formulário de cartão
        function initializeCardForm() {
            // Verificar se os elementos necessários existem
            const cardNumberElement = document.getElementById('cardNumberContainer');
            const expirationDateElement = document.getElementById('expirationDateContainer');
            const securityCodeElement = document.getElementById('securityCodeContainer');
            
            if (!cardNumberElement || !expirationDateElement || !securityCodeElement) {
                console.warn('Elementos do formulário de cartão não encontrados');
                return;
            }
            
            console.log('Elementos do formulário de cartão encontrados, inicializando...');
            
            try {
                // Definir configurações padrão
                const defaultSettings = {
                    amount: parseFloat(highlight_payment.price || 99.90),
                    autoMount: true,
                    form: {
                        id: "new-card-panel",
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
                        onFormMounted: function(error) {
                            if (error) {
                                console.error("Erro ao montar formulário:", error);
                                showErrorMessage("Erro ao carregar formulário de pagamento: " + error);
                            } else {
                                console.log("Formulário de cartão montado com sucesso");
                            }
                        },
                        onFormUnmounted: function(error) {
                            if (error) {
                                console.error("Erro ao desmontar formulário:", error);
                            }
                        },
                        onIdentificationTypesReceived: function(error, identificationTypes) {
                            if (error) {
                                console.error("Erro ao obter tipos de identificação:", error);
                            }
                        },
                        onPaymentMethodsReceived: function(error, paymentMethods) {
                            if (error) {
                                console.error("Erro ao obter métodos de pagamento:", error);
                            }
                        },
                        onIssuersReceived: function(error, issuers) {
                            if (error) {
                                console.error("Erro ao obter emissores:", error);
                            }
                        },
                        onCardTokenReceived: function(error, token) {
                            if (error) {
                                console.error("Erro ao obter token do cartão:", error);
                            }
                        },
                        onFetching: function(resource) {
                            console.log("Buscando recurso:", resource);
                        },
                        onSubmit: function(event) {
                            if (event && event.preventDefault) {
                                event.preventDefault();
                            }
                            console.log("Submit do formulário interceptado");
                        },
                        onValidityChange: function(error, field) {
                            // Destacar visualmente campos com erro
                            if (error) {
                                console.log(`Campo ${field} inválido:`, error);
                                $(`#${field}`).addClass('mp-error');
                            } else {
                                $(`#${field}`).removeClass('mp-error');
                            }
                        }
                    }
                };
                
                // Criar o formulário
                if (!mpInstance) {
                    throw new Error('MercadoPago SDK não inicializado corretamente');
                }
                
                if (!mpInstance.cardForm) {
                    throw new Error('MercadoPago SDK não possui o método cardForm');
                }
                
                cardForm = mpInstance.cardForm(defaultSettings);
                console.log('Formulário de cartão inicializado com sucesso');
            } catch (e) {
                console.error('Erro ao inicializar formulário de cartão:', e);
                showErrorMessage('Erro ao configurar formulário de pagamento: ' + (e.message || 'Erro desconhecido'));
            }
        }
        
        // Inicializar MercadoPago
        initializeMercadoPago();
        
        // Controle de abas de pagamento
        $('.payment-tab').on('click', function() {
            const targetPanel = $(this).data('target');
            
            // Remover classe ativa de todas as abas
            $('.payment-tab').removeClass('active');
            $(this).addClass('active');
            
            // Esconder todos os painéis
            $('.payment-panel').removeClass('active');
            
            // Mostrar o painel selecionado
            $('#' + targetPanel).addClass('active');
        });
        
        // Anexar evento de clique no botão de destaque
        $('.highlight-button[data-action="highlight-property"]').on('click', function(e) {
            e.preventDefault();
            
            // Verificar se os termos foram aceitos
            if ($('#accept-terms').length && !$('#accept-terms').is(':checked')) {
                showErrorMessage('Você precisa aceitar os termos de uso para continuar.');
                return;
            }
            
            // Determinar qual método de pagamento está selecionado
            let paymentMethod = 'new';
            
            // Se temos abas de pagamento, verificar qual está ativa
            if ($('.payment-tab').length > 0) {
                paymentMethod = $('.payment-tab.active').hasClass('saved-cards-tab') ? 'saved' : 'new';
            }
            
            // Processar pagamento conforme o método selecionado
            if (paymentMethod === 'saved') {
                // Verificar se um cartão salvo está selecionado
                const selectedCardId = $('input[name="card_id"]:checked').val();
                
                if (!selectedCardId) {
                    showErrorMessage('Selecione um cartão para continuar.');
                    return;
                }
                
                // Confirmar antes de processar
                if (confirm('Confirmar pagamento com o cartão selecionado?')) {
                    processPaymentWithSavedCard(selectedCardId);
                }
            } else {
                // Novo cartão
                if (!cardForm) {
                    showErrorMessage('Erro ao processar pagamento. Formulário não inicializado.');
                    return;
                }
                
                // Verificar se o CPF foi preenchido
                const cpf = $('#identificationNumber').val().trim();
                if (!cpf) {
                    showErrorMessage('Por favor, informe o CPF do titular do cartão.');
                    return;
                }
                
                // Confirmar antes de processar
                if (confirm('Confirmar pagamento com novo cartão?')) {
                    processPaymentWithNewCard();
                }
            }
        });
        
        // Função para processar pagamento com cartão salvo
        function processPaymentWithSavedCard(cardId) {
            showLoading(true);
            
            // Obter outros dados do pagamento
            const data = {
                action: 'highlight_payment_process',
                nonce: highlight_payment.nonce,
                immobile_id: highlight_payment.immobile_id,
                payment_method: 'saved',
                card_id: cardId
            };
            
            // Enviar requisição para processar o pagamento
            $.ajax({
                url: highlight_payment.ajax_url,
                type: 'POST',
                data: data,
                success: handlePaymentResponse,
                error: handlePaymentError
            });
        }
        
        // Função para processar pagamento com novo cartão
        function processPaymentWithNewCard() {
            showLoading(true);
            
            // Verificar se cardForm existe e tem o método createCardToken
            if (!cardForm || typeof cardForm.createCardToken !== 'function') {
                showLoading(false);
                showErrorMessage('Erro ao processar pagamento: formulário não inicializado corretamente');
                console.error('cardForm não está inicializado corretamente', cardForm);
                return;
            }
            
            // Obter token do cartão via SDK
            cardForm.createCardToken()
                .then(function(response) {
                    if (response.error) {
                        showLoading(false);
                        showErrorMessage('Erro ao processar cartão: ' + response.error);
                        return;
                    }
                    
                    // Obter dados adicionais
                    const saveCard = $('#save_card').is(':checked');
                    const cpf = $('#identificationNumber').val().trim();
                    
                    // Preparar dados para a requisição
                    const data = {
                        action: 'highlight_payment_process',
                        nonce: highlight_payment.nonce,
                        immobile_id: highlight_payment.immobile_id,
                        payment_method: 'new',
                        token: response.token,
                        payment_method_id: response.payment_method_id,
                        issuer_id: response.issuer_id,
                        identification_number: cpf,
                        save_card: saveCard
                    };
                    
                    // Enviar requisição para processar o pagamento
                    $.ajax({
                        url: highlight_payment.ajax_url,
                        type: 'POST',
                        data: data,
                        success: handlePaymentResponse,
                        error: handlePaymentError
                    });
                })
                .catch(function(error) {
                    showLoading(false);
                    console.error('Erro ao criar token do cartão:', error);
                    showErrorMessage('Erro ao processar cartão: ' + (error.message || 'Verifique os dados e tente novamente.'));
                });
        }
        
        // Função para tratar resposta do pagamento
        function handlePaymentResponse(response) {
            showLoading(false);
            console.log('Resposta do pagamento:', response);
            
            if (response.success) {
                // Exibir mensagem de sucesso
                showSuccessMessage(response.data.message || 'Pagamento processado com sucesso!');
                
                // Esconder formulário de pagamento
                $('.payment-options-section').slideUp();
                
                // Redirecionar se houver URL
                if (response.data.redirect_url) {
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 2000);
                }
            } else {
                // Exibir mensagem de erro
                showErrorMessage(response.data.message || 'Erro ao processar pagamento. Tente novamente.');
            }
        }
        
        // Função para tratar erro na requisição
        function handlePaymentError(xhr, status, error) {
            showLoading(false);
            console.error('Erro na requisição AJAX:', status, error);
            showErrorMessage('Erro na comunicação com o servidor: ' + error);
        }
        
        // Máscara para CPF
        $('#identificationNumber').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            
            if (value.length > 9) {
                value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{1,2})$/, '$1.$2.$3-$4');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{3})(\d{3})(\d{1,3})$/, '$1.$2.$3');
            } else if (value.length > 3) {
                value = value.replace(/^(\d{3})(\d{1,3})$/, '$1.$2');
            }
            
            $(this).val(value);
        });
    });
})(jQuery); 