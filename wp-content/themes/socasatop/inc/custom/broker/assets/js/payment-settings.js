(function($) {
    $(document).ready(function() {
        // Inicializar SDK do Mercado Pago
        const mp = new MercadoPago(payment_settings.public_key, {
            locale: 'pt-BR'
        });
        
        let cardForm;
        
        // Mostrar formulário de novo cartão
        $('#add-new-card').on('click', function() {
            $('#card-form-container').show();
            $(this).hide();
            
            // Inicializar formulário de cartão
            cardForm = mp.cardForm({
                amount: "49.90",
                autoMount: true,
                form: {
                    id: "card-form",
                    cardholderName: {
                        id: "cardholderName",
                        placeholder: "Titular do cartão"
                    },
                    cardNumber: {
                        id: "cardNumberContainer",
                        placeholder: "Número do cartão"
                    },
                    expirationDate: {
                        id: "expirationDateContainer",
                        placeholder: "MM/YY"
                    },
                    securityCode: {
                        id: "securityCodeContainer",
                        placeholder: "CVV"
                    },
                    installments: {
                        id: "installments",
                        placeholder: "Parcelas"
                    },
                    identificationType: {
                        id: "identificationType"
                    },
                    identificationNumber: {
                        id: "identificationNumber",
                        placeholder: "Número do documento"
                    }
                },
                callbacks: {
                    onFormMounted: error => {
                        if (error) {
                            console.log("Form Mounted error: ", error);
                            showError("Erro ao montar o formulário: " + error);
                        }
                    },
                    onFormUnmounted: error => {
                        if (error) {
                            console.log("Form Unmounted error: ", error);
                        }
                    },
                    onIdentificationTypesReceived: (error, identificationTypes) => {
                        if (error) {
                            console.log("identificationTypes error: ", error);
                        }
                    },
                    onPaymentMethodsReceived: (error, paymentMethods) => {
                        if (error) {
                            console.log("paymentMethods error: ", error);
                        }
                    },
                    onIssuersReceived: (error, issuers) => {
                        if (error) {
                            console.log("issuers error: ", error);
                        }
                    },
                    onInstallmentsReceived: (error, installments) => {
                        if (error) {
                            console.log("installments error: ", error);
                        }
                    },
                    onCardTokenReceived: (error, token) => {
                        if (error) {
                            console.log("Token error: ", error);
                            showError("Erro ao processar o cartão: " + error);
                        }
                    },
                    onSubmit: event => {
                        event.preventDefault();
                    },
                    onFetching: (resource) => {
                        console.log("Fetching resource: ", resource);
                    },
                    onValidityChange: (error, field) => {
                        // Mudança no estado de validação de um campo
                    },
                    onError: (error) => {
                        console.log("Form error: ", error);
                        showError("Erro no formulário: " + error);
                    }
                }
            });
        });
        
        // Cancelar adição de cartão
        $('#cancel-card-form').on('click', function() {
            $('#card-form-container').hide();
            $('#add-new-card').show();
            $('#result-message').html('');
        });
        
        // Salvar novo cartão
        $('#save-card').on('click', function() {
            const formData = cardForm.getCardFormData();
            
            if (formData.validate) {
                // Obter token do cartão
                cardForm.createCardToken().then(function(token) {
                    saveCardToMercadoPago(token);
                }).catch(function(error) {
                    console.error("Error creating token:", error);
                    showError("Erro ao processar o cartão. Verifique os dados e tente novamente.");
                });
            } else {
                showError("Por favor, preencha corretamente todos os campos.");
            }
        });
        
        // Definir cartão como padrão
        $('.set-default-card').on('click', function() {
            const cardId = $(this).data('card-id');
            
            $.ajax({
                url: payment_settings.ajax_url,
                type: 'POST',
                data: {
                    action: 'set_default_card',
                    nonce: payment_settings.nonce,
                    card_id: cardId
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        showError(response.data || "Erro ao definir cartão como padrão.");
                    }
                },
                error: function() {
                    showError("Erro de comunicação ao definir cartão como padrão.");
                }
            });
        });
        
        // Remover cartão
        $('.delete-card').on('click', function() {
            const cardId = $(this).data('card-id');
            
            if (confirm("Tem certeza que deseja remover este cartão?")) {
                $.ajax({
                    url: payment_settings.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'delete_card',
                        nonce: payment_settings.nonce,
                        card_id: cardId
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            showError(response.data || "Erro ao remover cartão.");
                        }
                    },
                    error: function() {
                        showError("Erro de comunicação ao remover cartão.");
                    }
                });
            }
        });
        
        // Função para salvar o cartão no Mercado Pago
        function saveCardToMercadoPago(token) {
            $.ajax({
                url: payment_settings.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_card',
                    nonce: payment_settings.nonce,
                    token: token,
                    user_id: payment_settings.user_id
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess("Cartão salvo com sucesso!");
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showError(response.data || "Erro ao salvar cartão.");
                    }
                },
                error: function() {
                    showError("Erro de comunicação ao salvar cartão.");
                }
            });
        }
        
        // Mostrar mensagem de erro
        function showError(message) {
            $('#result-message').html('<div class="error-message">' + message + '</div>');
        }
        
        // Mostrar mensagem de sucesso
        function showSuccess(message) {
            $('#result-message').html('<div class="success-message">' + message + '</div>');
        }
        
        // Pausar assinatura
        $('.pause-subscription').on('click', function() {
            const subscriptionId = $(this).data('subscription-id');
            const immobileId = $(this).data('immobile-id');
            
            if (confirm("Tem certeza que deseja pausar esta assinatura? Seu imóvel deixará de ser destacado.")) {
                $.ajax({
                    url: payment_settings.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pause_subscription',
                        nonce: payment_settings.nonce,
                        subscription_id: subscriptionId,
                        immobile_id: immobileId
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            showError(response.data || "Erro ao pausar assinatura.");
                        }
                    },
                    error: function() {
                        showError("Erro de comunicação ao pausar assinatura.");
                    }
                });
            }
        });
    });
})(jQuery); 