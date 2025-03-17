(function($) {
    $(document).ready(function() {
        // Modo de desenvolvimento para testar sem dependência do MercadoPago
        const DEV_MODE = true; // Sempre em modo de desenvolvimento para ambiente simulado
        
        console.log('Inicializando configurações de pagamento...');
        
        // Verificar se a API do Mercado Pago está disponível
        if (!DEV_MODE && typeof MercadoPago === 'undefined') {
            console.error("SDK do Mercado Pago não carregado. Verifique a conexão com a internet.");
            $('#add-new-card').prop('disabled', true).text('Indisponível no momento');
            return;
        }

        // Inicializar SDK do Mercado Pago com verificação de chave pública
        if (!DEV_MODE && !payment_settings.public_key) {
            console.error("Chave pública do Mercado Pago não configurada.");
            $('#add-new-card').prop('disabled', true).text('Configuração incompleta');
            return;
        }
        
        let mp, cardForm;
        
        // Inicializar o SDK somente se não estiver em modo de desenvolvimento
        if (!DEV_MODE) {
            mp = new MercadoPago(payment_settings.public_key, {
                locale: 'pt-BR'
            });
        }
        
        // Mostrar formulário de novo cartão
        $('#add-new-card').on('click', function(e) {
            e.preventDefault();
            console.log('Botão Adicionar novo cartão clicado');
            
            // Limpar mensagens anteriores
            $('#result-message').html('');
            
            if (DEV_MODE) {
                // Mostrar formulário simulado para desenvolvimento
                showSimulatedCardForm();
            } else {
                // Mostrar formulário do MercadoPago
                $('#card-form-container').show();
                $(this).hide();
                
                try {
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
                                } else {
                                    console.log("Formulário montado com sucesso");
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
                                } else if (identificationTypes && identificationTypes.length > 0) {
                                    console.log("Tipos de documento recebidos:", identificationTypes.length);
                                }
                            },
                            onPaymentMethodsReceived: (error, paymentMethods) => {
                                if (error) {
                                    console.log("paymentMethods error: ", error);
                                } else if (paymentMethods) {
                                    console.log("Métodos de pagamento recebidos");
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
                                } else {
                                    console.log("Token gerado com sucesso:", token);
                                }
                            },
                            onSubmit: event => {
                                event.preventDefault();
                            },
                            onFetching: (resource) => {
                                console.log("Fetching resource: ", resource);
                                $('#save-card').prop('disabled', true).text('Processando...');
                            },
                            onValidityChange: (error, field) => {
                                // Mudança no estado de validação de um campo
                                console.log("Validity change for field", field);
                            },
                            onError: (error) => {
                                console.log("Form error: ", error);
                                showError("Erro no formulário: " + error);
                                $('#save-card').prop('disabled', false).text('Salvar cartão');
                            }
                        }
                    });
                } catch (error) {
                    console.error("Erro ao inicializar o formulário:", error);
                    showError("Não foi possível inicializar o formulário de cartão. Por favor, tente novamente mais tarde.");
                    $('#card-form-container').hide();
                    $('#add-new-card').show();
                }
            }
        });
        
        // Função para mostrar o formulário simulado
        function showSimulatedCardForm() {
            console.log('Exibindo formulário simulado');
            
            // Verificar se o formulário simulado já existe
            if ($('#simulated-card-form-container').length) {
                $('#simulated-card-form-container').show();
                $('#add-new-card').hide();
                return;
            }
            
            // HTML do formulário simulado
            const simulatedFormHtml = `
                <div id="simulated-card-form-container">
                    <h4>Novo Cartão (Ambiente Simulado)</h4>
                    <form id="simulated-card-form" class="mp-form">
                        <div class="mp-form-row">
                            <div class="mp-col-12">
                                <label for="simulated-card-name">Nome no cartão</label>
                                <input type="text" id="simulated-card-name" placeholder="Nome impresso no cartão" required>
                            </div>
                        </div>
                        <div class="mp-form-row">
                            <div class="mp-col-12">
                                <label for="simulated-card-number">Número do cartão</label>
                                <input type="text" id="simulated-card-number" placeholder="0000 0000 0000 0000" maxlength="19" required>
                            </div>
                        </div>
                        <div class="mp-form-row">
                            <div class="mp-col-6">
                                <label for="simulated-expiry-month">Mês de validade</label>
                                <select id="simulated-expiry-month" required>
                                    <option value="">Mês</option>
                                    ${Array.from({length: 12}, (_, i) => `<option value="${String(i+1).padStart(2, '0')}">${String(i+1).padStart(2, '0')}</option>`).join('')}
                                </select>
                            </div>
                            <div class="mp-col-6">
                                <label for="simulated-expiry-year">Ano de validade</label>
                                <select id="simulated-expiry-year" required>
                                    <option value="">Ano</option>
                                    ${(() => {
                                        const years = [];
                                        const currentYear = new Date().getFullYear();
                                        for (let i = 0; i < 10; i++) {
                                            years.push(`<option value="${currentYear + i}">${currentYear + i}</option>`);
                                        }
                                        return years.join('');
                                    })()}
                                </select>
                            </div>
                        </div>
                        <div class="mp-form-row">
                            <div class="mp-col-6">
                                <label for="simulated-security-code">Código de segurança</label>
                                <input type="text" id="simulated-security-code" placeholder="CVV" maxlength="4" required>
                            </div>
                        </div>
                        <div id="simulated-result-message"></div>
                        <div class="mp-form-actions">
                            <button type="button" id="simulated-save-card" class="button button-primary">Salvar cartão</button>
                            <button type="button" id="simulated-cancel-card-form" class="button">Cancelar</button>
                        </div>
                    </form>
                </div>
            `;
            
            // Adicionar o formulário ao DOM
            $('.add-card-section').append(simulatedFormHtml);
            $('#add-new-card').hide();
            
            console.log('Formulário simulado adicionado ao DOM');
            
            // Configurar campos do formulário
            setupSimulatedFormFields();
        }
        
        // Configurar campos do formulário simulado
        function setupSimulatedFormFields() {
            // Formatar o número do cartão com espaços
            $('#simulated-card-number').on('input', function() {
                $(this).val($(this).val().replace(/[^0-9]/g, '').replace(/(.{4})/g, '$1 ').trim());
            });
            
            // Formatar o código de segurança para aceitar apenas números
            $('#simulated-security-code').on('input', function() {
                $(this).val($(this).val().replace(/[^0-9]/g, ''));
            });
            
            // Botão cancelar
            $('#simulated-cancel-card-form').on('click', function(e) {
                e.preventDefault();
                $('#simulated-card-form-container').hide();
                $('#add-new-card').show();
                $('#simulated-result-message').html('');
            });
            
            // Salvar cartão simulado
            $('#simulated-save-card').on('click', function(e) {
                e.preventDefault();
                console.log('Botão Salvar cartão clicado');
                
                const cardName = $('#simulated-card-name').val().trim();
                const cardNumber = $('#simulated-card-number').val().replace(/\s/g, '');
                const expiryMonth = $('#simulated-expiry-month').val();
                const expiryYear = $('#simulated-expiry-year').val();
                const securityCode = $('#simulated-security-code').val().trim();
                
                console.log('Dados do cartão:', {
                    cardName,
                    cardNumber: cardNumber.substring(0, 4) + '********' + cardNumber.substring(cardNumber.length - 4),
                    expiryMonth,
                    expiryYear
                });
                
                // Validar campos
                if (!cardName || !cardNumber || !expiryMonth || !expiryYear || !securityCode) {
                    $('#simulated-result-message').html('<div class="error-message">Por favor, preencha todos os campos.</div>');
                    return;
                }
                
                // Validar número do cartão (verificação simples)
                if (cardNumber.length < 13 || cardNumber.length > 19) {
                    $('#simulated-result-message').html('<div class="error-message">Número de cartão inválido.</div>');
                    return;
                }
                
                $(this).prop('disabled', true).text('Processando...');
                $('#simulated-result-message').html('<div class="info-message">Processando seu cartão...</div>');
                
                const ajaxData = {
                    action: 'add_simulated_card',
                    nonce: payment_settings.nonce,
                    card_name: cardName,
                    card_number: cardNumber,
                    expiry_month: expiryMonth,
                    expiry_year: expiryYear
                };
                
                console.log('Enviando requisição AJAX:', {
                    url: payment_settings.ajax_url,
                    action: ajaxData.action,
                    nonce: ajaxData.nonce ? 'presente' : 'ausente'
                });
                
                // Enviar dados para o servidor
                $.ajax({
                    url: payment_settings.ajax_url,
                    type: 'POST',
                    data: ajaxData,
                    success: function(response) {
                        console.log('Resposta AJAX:', response);
                        if (response.success) {
                            $('#simulated-result-message').html('<div class="success-message">Cartão salvo com sucesso!</div>');
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        } else {
                            $('#simulated-result-message').html('<div class="error-message">' + (response.data || 'Erro ao salvar o cartão.') + '</div>');
                            $('#simulated-save-card').prop('disabled', false).text('Salvar cartão');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Erro AJAX detalhado:', {
                            status: status,
                            error: error,
                            responseText: xhr.responseText
                        });
                        $('#simulated-result-message').html('<div class="error-message">Erro ao processar a solicitação. Tente novamente.</div>');
                        $('#simulated-save-card').prop('disabled', false).text('Salvar cartão');
                    }
                });
            });
        }
        
        // Definir cartão como padrão
        $('.set-default-card').on('click', function() {
            const cardId = $(this).data('card-id');
            const $button = $(this);
            
            // Desativar o botão para evitar cliques duplos
            $button.prop('disabled', true).text('Processando...');
            
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
                        alert(response.data || 'Erro ao definir o cartão como padrão.');
                        $button.prop('disabled', false).text('Definir como padrão');
                    }
                },
                error: function() {
                    alert('Erro ao processar a solicitação. Tente novamente.');
                    $button.prop('disabled', false).text('Definir como padrão');
                }
            });
        });
        
        // Remover cartão
        $('.delete-card').on('click', function() {
            const cardId = $(this).data('card-id');
            
            if (confirm('Tem certeza que deseja remover este cartão?')) {
                const $button = $(this);
                $button.prop('disabled', true).text('Removendo...');
                
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
                            alert(response.data || 'Erro ao remover o cartão.');
                            $button.prop('disabled', false).text('Remover');
                        }
                    },
                    error: function() {
                        alert('Erro ao processar a solicitação. Tente novamente.');
                        $button.prop('disabled', false).text('Remover');
                    }
                });
            }
        });
        
        // Mostrar mensagem de erro
        function showError(message) {
            $('#result-message').html('<div class="error-message">' + message + '</div>');
        }
        
        // Mostrar mensagem de sucesso
        function showSuccess(message) {
            $('#result-message').html('<div class="success-message">' + message + '</div>');
        }
    });
})(jQuery); 