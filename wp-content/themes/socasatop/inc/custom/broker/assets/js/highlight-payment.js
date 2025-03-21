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
        
        // Variáveis para armazenar referências
        let mpInstance = null;
        let cardForm = null;
        
        // Função para inicializar o MercadoPago
        function initializeMercadoPago() {
            // Verificar se a biblioteca MercadoPago está disponível
            if (typeof MercadoPago === 'undefined') {
                console.error('MercadoPago SDK não está disponível');
                
                // Tentar carregar o SDK novamente após um curto atraso
                setTimeout(function() {
                    if (typeof MercadoPago !== 'undefined') {
                        console.log('MercadoPago SDK carregado por script alternativo');
                        initializeMercadoPago();
                    } else {
                        showErrorMessage('Não foi possível carregar o SDK do MercadoPago. Recarregue a página ou tente novamente mais tarde.');
                    }
                }, 1500);
                
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
            } catch (e) {
                console.error('Erro ao inicializar MercadoPago:', e);
                showErrorMessage('Erro ao inicializar o gateway de pagamento: ' + e.message);
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
                // Configurações do formulário
                const cardFormSettings = {
                    amount: parseFloat(highlight_payment.price),
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
                        },
                        installments: {
                            id: "form-checkout__installments",
                            placeholder: "Parcelas"
                        }
                    },
                    callbacks: {
                        onFormMounted: function(error) {
                            if (error) {
                                console.error("Erro ao montar formulário:", error);
                                showErrorMessage("Erro ao carregar formulário: " + error);
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
                        onInstallmentsReceived: function(error, installments) {
                            if (error) {
                                console.error("Erro ao obter parcelas:", error);
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
                            event.preventDefault();
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
                cardForm = mpInstance.cardForm(cardFormSettings);
                console.log('Formulário de cartão inicializado com sucesso');
            } catch (e) {
                console.error('Erro ao inicializar formulário de cartão:', e);
                showErrorMessage('Erro ao configurar formulário de pagamento: ' + e.message);
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