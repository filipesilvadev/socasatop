(function($) {
    $(document).ready(function() {
        console.log('Highlight Payment JS loaded');
        
        // Verificar se temos os dados necessários
        if (typeof highlight_payment === 'undefined') {
            console.error('Highlight payment data not found');
            return;
        }
        
        // Inicializar o SDK do Mercado Pago
        if (typeof MercadoPago !== 'undefined') {
            try {
                const mp = new MercadoPago(highlight_payment.public_key);
                console.log('MercadoPago SDK inicializado com chave:', highlight_payment.public_key);
                let cardForm;
                
                // Inicializar o formulário de cartão se o elemento existir
                if (document.getElementById('cardNumberContainer')) {
                    const cardFormSettings = {
                        amount: highlight_payment.price,
                        autoMount: true,
                        form: {
                            id: "new-card-form",
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
                                    console.error("Form Mount error:", error);
                                    showErrorMessage("Erro ao carregar formulário. Por favor, tente novamente mais tarde.");
                                } else {
                                    console.log("Formulário do cartão montado com sucesso");
                                }
                            },
                            onSubmit: event => {
                                event.preventDefault();
                                console.log("Submit interceptado");
                            },
                            onFetching: (resource) => {
                                console.log("Fetching resource:", resource);
                                
                                // Aqui você pode mostrar um loader, desabilitar o botão, etc.
                                $(".highlight-button").prop("disabled", true);
                                $(".highlight-button").text("Processando...");
                            },
                            onCardTokenReceived: (error, token) => {
                                if (error) {
                                    console.error("Token error:", error);
                                    showErrorMessage("Erro ao processar cartão: " + error);
                                    return;
                                }
                                console.log("Card token recebido:", token);
                            }
                        }
                    };
                    
                    // Montar o formulário do cartão
                    try {
                        cardForm = mp.cardForm(cardFormSettings);
                        console.log('Card form initialized');
                    } catch(e) {
                        console.error('Error initializing card form:', e);
                        showErrorMessage("Erro ao inicializar formulário de cartão: " + e.message);
                    }
                } else {
                    console.warn('Elemento cardNumberContainer não encontrado');
                }
            } catch(e) {
                console.error('Erro ao inicializar MercadoPago SDK:', e);
                showErrorMessage("Erro ao carregar o gateway de pagamento. Por favor, atualize a página e tente novamente.");
            }
        } else {
            console.warn('MercadoPago SDK not available');
            showErrorMessage("SDK do MercadoPago não está disponível. Verifique sua conexão com a internet e tente novamente.");
        }
        
        // Alternar entre cartão salvo e novo cartão
        $('input[name="payment_method"]').on('change', function() {
            const value = $(this).val();
            
            if (value === 'new') {
                $('#new-card-form').show();
                $('#saved-card-selection').hide();
            } else {
                $('#new-card-form').hide();
                $('#saved-card-selection').show();
            }
        });
        
        // Botão para destacar imóvel
        $('.highlight-button[data-action="highlight-property"]').on('click', function(e) {
            e.preventDefault();
            console.log('Botão de destacar clicado');
            
            // Verificar se os termos foram aceitos
            if ($('#accept-terms').length && !$('#accept-terms').is(':checked')) {
                showErrorMessage('Você precisa aceitar os termos de uso para continuar.');
                return;
            }
            
            // Mostrar confirmação
            if (confirm('Tem certeza que deseja destacar este imóvel?')) {
                // Verificar se está usando cartão salvo ou novo cartão
                const paymentMethod = $('input[name="payment_method"]:checked').val();
                
                if (paymentMethod === 'saved') {
                    // Usar cartão salvo
                    const cardId = $('input[name="card_id"]:checked').val();
                    
                    if (!cardId) {
                        showErrorMessage('Selecione um cartão para continuar.');
                        return;
                    }
                    
                    highlightProperty({
                        payment_method: 'saved',
                        card_id: cardId
                    });
                } else if (paymentMethod === 'new' && typeof cardForm !== 'undefined') {
                    // Usar novo cartão
                    processNewCardPayment();
                } else {
                    // Modo direto (sem cartão)
                    highlightProperty({
                        payment_method: 'direct'
                    });
                }
            }
        });
        
        // Função para processar novo cartão
        function processNewCardPayment() {
            console.log('Processando pagamento com novo cartão');
            if (typeof cardForm === 'undefined') {
                showErrorMessage('Erro ao processar o cartão. Formulário não inicializado. Tente novamente mais tarde.');
                return;
            }
            
            const identificationNumber = $('#identificationNumber').val().trim();
            
            if (!identificationNumber) {
                showErrorMessage('Por favor, preencha o número do CPF.');
                return;
            }
            
            // Mostrar loader
            $('.highlight-button[data-action="highlight-property"]')
                .text('Processando...')
                .attr('disabled', true)
                .css('opacity', '0.7');
            
            console.log('Criando token do cartão...');
            
            // Obter token do cartão
            cardForm.createCardToken()
                .then(result => {
                    console.log('Token criado:', result);
                    if (result.error) {
                        showErrorMessage(result.error);
                        // Restaurar botão
                        $('.highlight-button[data-action="highlight-property"]')
                            .text('Destacar Imóvel Agora')
                            .attr('disabled', false)
                            .css('opacity', '1');
                        return;
                    }
                    
                    // Enviar token para o servidor
                    highlightProperty({
                        payment_method: 'new',
                        token: result.token,
                        payment_method_id: result.paymentMethodId,
                        issuer_id: result.issuerId,
                        identification_number: identificationNumber
                    });
                })
                .catch(error => {
                    console.error('Error creating card token:', error);
                    showErrorMessage('Erro ao processar o cartão: ' + (error.message || 'Verifique os dados e tente novamente.'));
                    
                    // Restaurar botão
                    $('.highlight-button[data-action="highlight-property"]')
                        .text('Destacar Imóvel Agora')
                        .attr('disabled', false)
                        .css('opacity', '1');
                });
        }
        
        // Função para destacar imóvel
        function highlightProperty(params = {}) {
            console.log('Enviando requisição de destaque com parâmetros:', params);
            
            // Mostrar loader
            $('.highlight-button[data-action="highlight-property"]')
                .text('Processando...')
                .attr('disabled', true)
                .css('opacity', '0.7');
            
            // Parâmetros padrão
            const data = {
                action: 'highlight_payment_process',
                nonce: highlight_payment.nonce,
                immobile_id: highlight_payment.immobile_id,
                payment_method: 'direct' // Forma de pagamento padrão
            };
            
            // Mesclar com parâmetros adicionais
            Object.assign(data, params);
            
            // Enviar requisição AJAX
            $.ajax({
                url: highlight_payment.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    console.log('Highlight response:', response);
                    
                    if (response.success) {
                        // Mostrar mensagem de sucesso
                        showSuccessMessage(response.data.message || 'Imóvel destacado com sucesso!');
                        
                        // Redirecionar para a URL de retorno após 2 segundos
                        setTimeout(function() {
                            if (response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            } else {
                                window.location.reload();
                            }
                        }, 2000);
                    } else {
                        // Mostrar mensagem de erro
                        showErrorMessage(response.data.message || 'Erro ao destacar imóvel. Tente novamente.');
                        
                        // Restaurar botão
                        $('.highlight-button[data-action="highlight-property"]')
                            .text('Destacar Imóvel Agora')
                            .attr('disabled', false)
                            .css('opacity', '1');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.error('Response:', xhr.responseText);
                    showErrorMessage('Erro ao comunicar com o servidor: ' + error);
                    
                    // Restaurar botão
                    $('.highlight-button[data-action="highlight-property"]')
                        .text('Destacar Imóvel Agora')
                        .attr('disabled', false)
                        .css('opacity', '1');
                }
            });
        }
        
        // Função para mostrar mensagem de sucesso
        function showSuccessMessage(message) {
            $('#payment-result').show();
            $('.success-message').show().find('p').text(message);
            $('.error-message').hide();
            
            // Esconder o formulário e o botão
            $('.payment-options').hide();
            $('.highlight-action').hide();
        }
        
        // Função para mostrar mensagem de erro
        function showErrorMessage(message) {
            $('#payment-result').show();
            $('.error-message').show().text(message);
            $('.success-message').hide();
        }
    });
})(jQuery); 