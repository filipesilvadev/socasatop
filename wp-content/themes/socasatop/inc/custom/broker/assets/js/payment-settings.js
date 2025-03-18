jQuery(document).ready(function($) {
    // Verificar se as configurações do payment_settings estão disponíveis
    if (typeof payment_settings === 'undefined') {
        console.error('Configurações de pagamento não encontradas');
        return;
    }

    // Verificar se o SDK do Mercado Pago está disponível
    if (typeof MercadoPago === 'undefined') {
        console.error('SDK do Mercado Pago não encontrado');
        $('#result-message').html('<div class="error-message">Erro ao carregar a biblioteca de pagamento. Por favor, recarregue a página e tente novamente.</div>');
        return;
    }

    // Inicializar o SDK do Mercado Pago
    const mp = new MercadoPago(payment_settings.public_key, {
        locale: 'pt-BR'
    });
    console.log('SDK do Mercado Pago inicializado com sucesso');

    let cardForm = null;

    // Mostrar formulário de novo cartão
    $('#add-new-card').on('click', function() {
        $('#card-form-container').show();
        $(this).hide();
        initializeCardForm();
    });

    // Cancelar adição de cartão
    $('#cancel-card-form').on('click', function() {
        $('#card-form-container').hide();
        $('#add-new-card').show();
        $('#result-message').html('');
        
        // Limpar formulário
        if (cardForm) {
            try {
                cardForm.unmount();
                cardForm = null;
            } catch (error) {
                console.error('Erro ao desmontar formulário:', error);
            }
        }
    });

    function initializeCardForm() {
        try {
            // Verificar se o formulário já está inicializado
            if (cardForm) {
                console.log('Formulário já inicializado');
                return;
            }

            // Verificar se o elemento do formulário existe
            const formElement = document.getElementById('card-form');
            if (!formElement) {
                console.error('Elemento #card-form não encontrado');
                $('#result-message').html('<div class="error-message">Erro ao inicializar o formulário. Elemento não encontrado.</div>');
                return;
            }

            // Garantir que o tipo de identificação esteja definido como CPF (Brasil)
            if ($('#identificationType').length) {
                $('#identificationType').val('CPF');
            }
            
            // Garantir que o valor das parcelas seja 1 (assinatura mensal)
            if ($('#installments').length) {
                $('#installments').val('1');
            }
            
            console.log('Iniciando formulário de cartão com elementos:', {
                cardholderName: $('#cardholderName').length ? 'encontrado' : 'não encontrado',
                cardNumberContainer: $('#cardNumberContainer').length ? 'encontrado' : 'não encontrado',
                expirationDateContainer: $('#expirationDateContainer').length ? 'encontrado' : 'não encontrado',
                securityCodeContainer: $('#securityCodeContainer').length ? 'encontrado' : 'não encontrado',
                identificationType: $('#identificationType').length ? 'encontrado' : 'não encontrado',
                identificationNumber: $('#identificationNumber').length ? 'encontrado' : 'não encontrado',
                installments: $('#installments').length ? 'encontrado' : 'não encontrado',
                issuer: $('#issuer').length ? 'encontrado' : 'não encontrado'
            });

            // Configurar o formulário
            cardForm = mp.cardForm({
                amount: "49.90",
                autoMount: false,
                form: {
                    id: "card-form",
                    cardholderName: {
                        id: "cardholderName",
                        placeholder: "Nome como está no cartão"
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
                    identificationType: {
                        id: "identificationType",
                        placeholder: "Tipo de documento"
                    },
                    identificationNumber: {
                        id: "identificationNumber",
                        placeholder: "CPF"
                    },
                    installments: {
                        id: "installments",
                        placeholder: "Parcelas"
                    },
                    issuer: {
                        id: "issuer",
                        placeholder: "Banco emissor"
                    }
                },
                callbacks: {
                    onFormMounted: function(error) {
                        if (error) {
                            console.error('Erro ao montar formulário:', error);
                            let errorMsg = 'Erro ao carregar formulário';
                            if (error.message) {
                                errorMsg += ': ' + error.message;
                            } else if (typeof error === 'string') {
                                errorMsg += ': ' + error;
                            }
                            $('#result-message').html('<div class="error-message">' + errorMsg + '</div>');
                        } else {
                            console.log('Formulário montado com sucesso');
                        }
                    },
                    onFormUnmounted: function(error) {
                        if (error) {
                            console.error('Erro ao desmontar formulário:', error);
                        }
                    },
                    onIdentificationTypesReceived: function(error, identificationTypes) {
                        if (error) {
                            console.error('Erro ao obter tipos de documento:', error);
                        }
                    },
                    onPaymentMethodsReceived: function(error, paymentMethods) {
                        if (error) {
                            console.error('Erro ao obter métodos de pagamento:', error);
                        }
                    },
                    onIssuersReceived: function(error, issuers) {
                        if (error) {
                            console.error('Erro ao obter emissores:', error);
                        }
                    },
                    onInstallmentsReceived: function(error, installments) {
                        if (error) {
                            console.error('Erro ao obter parcelas disponíveis:', error);
                        }
                    },
                    onCardTokenReceived: function(error, token) {
                        if (error) {
                            console.error('Erro ao gerar token do cartão:', error);
                            $('#result-message').html('<div class="error-message">Erro ao processar o cartão: ' + error.message + '</div>');
                        } else {
                            console.log('Token gerado com sucesso:', token);
                            saveCard(token);
                        }
                    },
                    onSubmit: function(event) {
                        event.preventDefault();
                        
                        const $form = $(event.target);
                        const $submitButton = $form.find('button[type="submit"]');
                        
                        // Desabilitar botão durante o processamento
                        $submitButton.prop('disabled', true).text('Processando...');
                        
                        // Validar formulário
                        const cardData = cardForm.getCardFormData();
                        if (!cardData.validate) {
                            $('#result-message').html('<div class="error-message">Por favor, preencha todos os campos corretamente.</div>');
                            $submitButton.prop('disabled', false).text('Salvar cartão');
                            return;
                        }
                        
                        // Gerar token do cartão
                        cardForm.createCardToken()
                            .then(function(token) {
                                if (token.error) {
                                    throw new Error(token.error);
                                }
                                return saveCard(token);
                            })
                            .catch(function(error) {
                                console.error('Erro ao processar cartão:', error);
                                $('#result-message').html('<div class="error-message">Erro ao processar cartão: ' + error.message + '</div>');
                                $submitButton.prop('disabled', false).text('Salvar cartão');
                            });
                    }
                }
            });
            
            // Montar manualmente o formulário após a inicialização
            setTimeout(function() {
                try {
                    cardForm.mount();
                    console.log("Formulário montado manualmente");
                } catch (error) {
                    console.error("Erro ao montar formulário manualmente:", error);
                    $('#result-message').html('<div class="error-message">Erro ao inicializar o formulário. Por favor, tente novamente.</div>');
                }
            }, 500);
        } catch (error) {
            console.error('Erro ao inicializar formulário:', error);
            $('#result-message').html('<div class="error-message">Erro ao inicializar o formulário: ' + error.message + '</div>');
        }
    }

    // Função para salvar o cartão
    function saveCard(token) {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: payment_settings.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_card',
                    nonce: payment_settings.nonce,
                    token: token
                },
                success: function(response) {
                    if (response.success) {
                        $('#result-message').html('<div class="success-message">Cartão salvo com sucesso!</div>');
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                        resolve(response);
                    } else {
                        const errorMessage = response.data ? response.data.message : 'Erro ao salvar cartão';
                        $('#result-message').html('<div class="error-message">' + errorMessage + '</div>');
                        reject(new Error(errorMessage));
                    }
                },
                error: function(xhr, status, error) {
                    const errorMessage = 'Erro ao comunicar com o servidor: ' + error;
                    $('#result-message').html('<div class="error-message">' + errorMessage + '</div>');
                    reject(new Error(errorMessage));
                }
            });
        });
    }

    // Definir cartão como padrão
    $('.set-default-card').on('click', function() {
        const cardId = $(this).data('card-id');
        const $button = $(this);
        
        $button.prop('disabled', true);
        
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
                    const errorMessage = response.data ? response.data.message : 'Erro ao definir cartão como padrão';
                    alert(errorMessage);
                    $button.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                alert('Erro ao comunicar com o servidor. Tente novamente.');
                $button.prop('disabled', false);
            }
        });
    });

    // Remover cartão
    $('.delete-card').on('click', function() {
        if (!confirm('Tem certeza que deseja remover este cartão?')) {
            return;
        }

        const cardId = $(this).data('card-id');
        const $button = $(this);
        
        $button.prop('disabled', true);
        
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
                    const errorMessage = response.data ? response.data.message : 'Erro ao remover cartão';
                    alert(errorMessage);
                    $button.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                alert('Erro ao comunicar com o servidor. Tente novamente.');
                $button.prop('disabled', false);
            }
        });
    });
}); 