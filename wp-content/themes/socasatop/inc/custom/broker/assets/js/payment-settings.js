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

    // Verificação adicional da chave pública
    if (!payment_settings.public_key || payment_settings.public_key.trim() === '') {
        console.error('Chave pública do Mercado Pago não configurada corretamente');
        $('#result-message').html('<div class="error-message">Erro de configuração: Chave pública do Mercado Pago inválida. Entre em contato com o suporte.</div>');
    }

    let cardForm = null;

    // Mostrar formulário de novo cartão
    $('#add-new-card').on('click', function() {
        // Limpar e recriar o contêiner do formulário
        $('#card-form-container').html(`
            <div id="result-message"></div>
            <form id="card-form" class="mp-form">
                <div class="form-group mp-form-row">
                    <div class="mp-col-12">
                        <label for="cardholderName">Nome no cartão</label>
                        <input type="text" id="cardholderName" name="cardholderName" class="form-control" data-checkout="cardholderName" placeholder="Nome como está no cartão" />
                    </div>
                </div>
                
                <div class="form-group mp-form-row">
                    <div class="mp-col-12">
                        <label for="cardNumber">Número do cartão</label>
                        <div id="cardNumber" class="mp-input-container"></div>
                    </div>
                </div>
                
                <div class="form-group mp-form-row">
                    <div class="mp-col-6">
                        <label for="expirationDate">Data de validade</label>
                        <div id="expirationDate" class="mp-input-container"></div>
                    </div>
                    <div class="mp-col-6">
                        <label for="securityCode">Código de segurança</label>
                        <div id="securityCode" class="mp-input-container"></div>
                    </div>
                </div>
                
                <div class="form-group mp-form-row">
                    <div class="mp-col-12">
                        <label for="identificationNumber">CPF</label>
                        <input type="text" id="identificationNumber" name="identificationNumber" class="form-control" data-checkout="identificationNumber" placeholder="Digite seu CPF" />
                        <select id="identificationType" name="identificationType" data-checkout="identificationType" style="display: none;">
                            <option value="CPF" selected>CPF</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group mp-form-row">
                    <div class="mp-col-12">
                        <label for="cardholderEmail">Email</label>
                        <input type="email" id="cardholderEmail" name="cardholderEmail" class="form-control" data-checkout="cardholderEmail" placeholder="Digite seu email" value="${payment_settings.user_email || ''}" />
                    </div>
                </div>
                
                <div class="form-group mp-form-row">
                    <div class="mp-col-12">
                        <label for="issuer">Bandeira</label>
                        <select id="issuer" name="issuer" class="form-control" data-checkout="issuer"></select>
                        <select id="installments" name="installments" class="form-control" data-checkout="installments" style="display: none;">
                            <option value="1" selected>1 parcela</option>
                        </select>
                    </div>
                </div>

                <div class="mp-form-actions">
                    <button type="button" id="cancel-card-form" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" id="save-card" class="btn btn-primary">Salvar cartão</button>
                </div>
            </form>
        `);
        
        // Mostrar o formulário
        $('#card-form-container').show();
        $(this).hide();
        
        // Adicionar máscaras e validações
        $('#identificationNumber').on('input', function() {
            // Remover caracteres não numéricos
            let value = $(this).val().replace(/\D/g, '');
            
            // Limitar a 11 dígitos (CPF)
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            
            // Aplicar máscara de CPF (000.000.000-00)
            if (value.length > 9) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
            } else if (value.length > 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            } else if (value.length > 3) {
                value = value.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            }
            
            $(this).val(value);
        });
        
        // Adicionar evento de atualização automática de bandeira (issuer)
        $('#cardNumber').on('change', function() {
            // Permitir um pequeno atraso para o SDK processar o número
            setTimeout(function() {
                // Tentar identificar a bandeira automaticamente
                let cardNumber = $('#cardNumber').val();
                if (cardNumber && cardNumber.length >= 6) {
                    if (!$('#issuer').prop('disabled')) {
                        console.log('Atualizando bandeira com base no número do cartão...');
                    }
                }
            }, 500);
        });
        
        // Adicionar novamente o evento de cancelar
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
        
        // Dar tempo para o DOM renderizar o formulário
        setTimeout(function() {
            initializeCardForm();
        }, 500);
    });

    function initializeCardForm() {
        try {
            // Verificar se o formulário já está inicializado
            if (cardForm) {
                console.log('Formulário já inicializado');
                return;
            }

            console.log('Buscando elemento do formulário...');
            // Verificar se o elemento do formulário existe
            const formElement = document.getElementById('card-form');
            if (!formElement) {
                console.error('Elemento #card-form não encontrado. Elementos disponíveis:', 
                    $('#card-form-container').html());
                $('#result-message').html('<div class="error-message">Erro ao inicializar o formulário. Elemento #card-form não encontrado.</div>');
                return;
            }
            
            console.log('Elemento #card-form encontrado:', formElement);
            
            // Verificar se todos os elementos do formulário estão presentes
            const requiredElements = [
                'cardholderName',
                'cardNumber',
                'expirationDate',
                'securityCode',
                'identificationType',
                'identificationNumber',
                'installments',
                'issuer',
                'cardholderEmail'
            ];
            
            let missingElements = [];
            requiredElements.forEach(elementId => {
                if (!document.getElementById(elementId)) {
                    missingElements.push(elementId);
                } else {
                    console.log(`Elemento #${elementId} encontrado:`, document.getElementById(elementId));
                }
            });
            
            if (missingElements.length > 0) {
                console.error('Elementos obrigatórios não encontrados:', missingElements);
                $('#result-message').html('<div class="error-message">Erro ao inicializar o formulário. Elementos não encontrados: ' + missingElements.join(', ') + '</div>');
                return;
            }
            
            // Garantir que todos os selects e inputs tenham valores iniciais
            if ($('#identificationType').length) {
                $('#identificationType').val('CPF');
            }
            
            if ($('#installments').length) {
                $('#installments').val('1');
            }
            
            console.log('Inicializando cardForm com o SDK do Mercado Pago...');
            
            // Configurar o formulário
            cardForm = mp.cardForm({
                amount: "49.90",
                autoMount: true,
                iframe: true,
                focus: false,
                locale: 'pt-BR',
                processingMode: 'aggregator',
                fieldBehavior: {
                    cardNumber: {
                        placeholder: true,
                        fixed: false,
                        integrator: true,
                        autofill: true
                    },
                    securityCode: {
                        placeholder: true,
                        fixed: false,
                        integrator: true,
                        autofill: true
                    },
                    expirationDate: {
                        placeholder: true,
                        fixed: false,
                        integrator: true,
                        autofill: true
                    }
                },
                form: {
                    id: "card-form",
                    cardholderName: {
                        id: "cardholderName",
                        placeholder: "Nome como está no cartão"
                    },
                    cardNumber: {
                        id: "cardNumber",
                        placeholder: "Número do cartão"
                    },
                    expirationDate: {
                        id: "expirationDate",
                        placeholder: "MM/YY"
                    },
                    securityCode: {
                        id: "securityCode", 
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
                        placeholder: "Bandeira"
                    },
                    cardholderEmail: {
                        id: "cardholderEmail",
                        placeholder: "Digite seu email"
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
                            
                            // Tratamento melhorado para erro
                            let errorMessage = 'Erro ao processar o cartão';
                            
                            try {
                                if (error) {
                                    if (error.message) {
                                        errorMessage += ': ' + error.message;
                                    } else if (typeof error === 'string') {
                                        errorMessage += ': ' + error;
                                    } else if (error.cause && Array.isArray(error.cause) && error.cause.length > 0) {
                                        errorMessage += ': ' + error.cause[0].description;
                                    } else if (error.error) {
                                        errorMessage += ': ' + error.error;
                                    } else {
                                        errorMessage += ': Verifique os dados do cartão';
                                    }
                                } else {
                                    // Error é undefined
                                    errorMessage += ': Erro desconhecido. Verifique os dados do cartão.';
                                }
                            } catch (e) {
                                console.error('Erro ao processar mensagem de erro:', e);
                                errorMessage = 'Erro ao processar o cartão. Por favor, verifique todos os dados e tente novamente.';
                            }
                            
                            $('#result-message').html('<div class="error-message">' + errorMessage + '</div>');
                            $('#save-card').prop('disabled', false).text('Salvar cartão');
                            return false; // Garantir que o processamento pare aqui
                        } else if (token) {
                            console.log('Token gerado com sucesso via callback:', token);
                            // saveCard(token); // Não chamamos aqui para evitar duplicidade
                            return true;
                        } else {
                            console.error('Token vazio ou indefinido recebido no callback');
                            $('#result-message').html('<div class="error-message">Erro ao gerar token do cartão: Resposta vazia</div>');
                            $('#save-card').prop('disabled', false).text('Salvar cartão');
                            return false;
                        }
                    },
                    onSubmit: function(event) {
                        event.preventDefault();
                        
                        const $form = $(event.target);
                        const $submitButton = $form.find('button[type="submit"]');
                        
                        // Desabilitar botão durante o processamento
                        $submitButton.prop('disabled', true).text('Processando...');
                        
                        console.log('Gerando token do cartão...');
                        
                        // Verificar se temos os campos básicos preenchidos primeiro
                        const cardholderName = $('#cardholderName').val();
                        const identificationNumber = $('#identificationNumber').val();
                        const cardholderEmail = $('#cardholderEmail').val();
                        
                        // Validação manual básica
                        let missingFields = [];
                        
                        if (!cardholderName || cardholderName.trim() === '') {
                            missingFields.push('Nome no cartão');
                        }
                        
                        if (!identificationNumber || identificationNumber.trim() === '') {
                            missingFields.push('CPF');
                        }
                        
                        if (!cardholderEmail || cardholderEmail.trim() === '') {
                            missingFields.push('Email');
                        }
                        
                        // Se temos campos faltando na validação básica, mostramos o erro
                        if (missingFields.length > 0) {
                            let errorMessage = 'Por favor, preencha todos os campos corretamente.';
                            errorMessage += '<br>Campos com problema: ' + missingFields.join(', ');
                            
                            $('#result-message').html('<div class="error-message">' + errorMessage + '</div>');
                            $submitButton.prop('disabled', false).text('Salvar cartão');
                            return;
                        }
                        
                        // Exibir indicador de processamento
                        $('#result-message').html('<div class="processing-message">Processando dados do cartão...</div>');
                        
                        // Forçar geração de token independente da validação interna
                        try {
                            // Verificar se o formulário tem todos os campos necessários para o Mercado Pago
                            const mpFields = ['cardNumber', 'securityCode', 'expirationDate'];
                            let missingMPFields = [];
                            
                            mpFields.forEach(field => {
                                const elem = document.getElementById(field);
                                if (!elem || elem.getAttribute('data-checkout-error')) {
                                    missingMPFields.push(field);
                                }
                            });
                            
                            if (missingMPFields.length > 0) {
                                console.error('Campos do Mercado Pago com erro:', missingMPFields);
                                const fieldNames = {
                                    'cardNumber': 'Número do cartão',
                                    'securityCode': 'Código de segurança', 
                                    'expirationDate': 'Data de validade'
                                };
                                
                                let errorMessage = 'Por favor, verifique os seguintes campos:<br><ul>';
                                missingMPFields.forEach(field => {
                                    errorMessage += `<li>${fieldNames[field] || field}</li>`;
                                });
                                errorMessage += '</ul>';
                                
                                $('#result-message').html('<div class="error-message">' + errorMessage + '</div>');
                                $submitButton.prop('disabled', false).text('Salvar cartão');
                                return;
                            }
                            
                            // Mapeamento de campos não-SDK para dados do formulário
                            const identificationNumber = $('#identificationNumber').val();
                            const identificationType = $('#identificationType').val() || 'CPF';
                            const cardholderName = $('#cardholderName').val();
                            const cardholderEmail = $('#cardholderEmail').val();
                            
                            // Verificar CPF
                            if (!identificationNumber || identificationNumber.trim() === '') {
                                $('#result-message').html('<div class="error-message">Por favor, informe seu CPF.</div>');
                                $submitButton.prop('disabled', false).text('Salvar cartão');
                                return;
                            }
                            
                            // Preparar o CPF (remover pontuação)
                            const cleanIdentificationNumber = identificationNumber.replace(/\D/g, '');
                            if (cleanIdentificationNumber.length !== 11) {
                                $('#result-message').html('<div class="error-message">CPF inválido. O CPF deve ter 11 dígitos.</div>');
                                $submitButton.prop('disabled', false).text('Salvar cartão');
                                return;
                            }
                            
                            // Definir o CPF sem formatação no campo oculto
                            $('#identificationNumber').data('clean-value', cleanIdentificationNumber);
                            
                            cardForm.createCardToken({
                                cardNumber: document.getElementById('cardNumber'),
                                expirationDate: document.getElementById('expirationDate'),
                                securityCode: document.getElementById('securityCode'),
                                identificationType: identificationType,
                                identificationNumber: cleanIdentificationNumber,  // Usar o CPF sem formatação
                                cardholderName: cardholderName
                            })
                            .then(function(result) {
                                console.log('Resultado da criação do token:', result);
                                
                                if (!result) {
                                    console.error('Resultado da criação do token é nulo ou indefinido');
                                    $('#result-message').html('<div class="error-message">Erro ao processar o cartão: Resposta vazia do servidor</div>');
                                    $submitButton.prop('disabled', false).text('Salvar cartão');
                                    
                                    // Fallback para processar usando getCardToken caso o createCardToken falhe
                                    tryProcessCardWithFallback();
                                    return;
                                }
                                
                                if (result && (result.error || (typeof result === 'object' && result.cause && result.cause.length > 0))) {
                                    // Tratar erros de validação específicos
                                    let errorMessages = [];
                                    
                                    if (result.cause && Array.isArray(result.cause)) {
                                        result.cause.forEach(function(causeItem) {
                                            if (causeItem.code && causeItem.description) {
                                                console.error(`Erro ${causeItem.code}: ${causeItem.description}`);
                                                errorMessages.push(causeItem.description);
                                            }
                                        });
                                    } else if (result.error) {
                                        errorMessages.push(result.error);
                                    }
                                    
                                    // Exibir mensagens de erro formatadas
                                    if (errorMessages.length > 0) {
                                        let errorHtml = '<div class="error-message">Por favor, verifique os dados do cartão:<br>';
                                        errorHtml += '<ul>';
                                        errorMessages.forEach(function(msg) {
                                            errorHtml += '<li>' + msg + '</li>';
                                        });
                                        errorHtml += '</ul></div>';
                                        
                                        $('#result-message').html(errorHtml);
                                    } else {
                                        $('#result-message').html('<div class="error-message">Erro ao processar o cartão. Por favor, tente novamente.</div>');
                                    }
                                    
                                    $submitButton.prop('disabled', false).text('Salvar cartão');
                                    return;
                                }
                                
                                // Extrair o token - a API pode retornar diferentes formatos
                                let tokenId = null;
                                
                                if (typeof result === 'string') {
                                    // Às vezes retorna diretamente a string do token
                                    tokenId = result;
                                } else if (result && result.id) {
                                    // Formato mais comum, objeto com id
                                    tokenId = result.id;
                                } else if (result && result.token) {
                                    // Outro formato possível
                                    tokenId = result.token;
                                } else if (result && typeof result.card_token === 'string') {
                                    // Verificação adicional para card_token
                                    tokenId = result.card_token;
                                }
                                
                                if (!tokenId) {
                                    console.error('Não foi possível obter o token do cartão', result);
                                    $('#result-message').html('<div class="error-message">Não foi possível processar o cartão. Verifique os dados inseridos.</div>');
                                    $submitButton.prop('disabled', false).text('Salvar cartão');
                                    return;
                                }
                                
                                // Token gerado com sucesso
                                console.log('Token gerado com sucesso:', tokenId);
                                return saveCard(tokenId);
                            })
                            .catch(function(error) {
                                console.error('Erro ao processar cartão:', error);
                                
                                // Tratamento melhorado para erro
                                let errorMessage = 'Erro ao processar cartão';
                                
                                if (error) {
                                    if (error.message) {
                                        errorMessage += ': ' + error.message;
                                    } else if (typeof error === 'string') {
                                        errorMessage += ': ' + error;
                                    } else if (error.cause && Array.isArray(error.cause) && error.cause.length > 0) {
                                        errorMessage += ': ' + error.cause[0].description;
                                    } else if (error.statusText) {
                                        errorMessage += ': ' + error.statusText;
                                    } else {
                                        errorMessage += ': Verifique os dados do cartão';
                                    }
                                } else {
                                    // Error é undefined
                                    errorMessage += ': Erro desconhecido. Verifique os dados do cartão.';
                                }
                                
                                $('#result-message').html('<div class="error-message">' + errorMessage + '</div>');
                                $submitButton.prop('disabled', false).text('Salvar cartão');
                                
                                // Tentar processar com método alternativo
                                tryProcessCardWithFallback();
                            });
                        } catch (error) {
                            console.error('Erro ao processar cartão:', error);
                            $('#result-message').html('<div class="error-message">Erro ao processar o cartão: ' + error.message + '</div>');
                            $submitButton.prop('disabled', false).text('Salvar cartão');
                            
                            // Tentar processar com método alternativo
                            tryProcessCardWithFallback();
                        }
                        
                        // Função de fallback para processar cartão usando método alternativo
                        function tryProcessCardWithFallback() {
                            try {
                                console.log('Tentando processar cartão com método alternativo...');
                                $('#result-message').html('<div class="processing-message">Tentando método alternativo...</div>');
                                
                                // Verificar se todos os campos estão preenchidos
                                const cardNumber = document.querySelector('#cardNumber iframe') ? 
                                    true : (document.querySelector('#cardNumber') ? 
                                        document.querySelector('#cardNumber').value : null);
                                        
                                const securityCode = document.querySelector('#securityCode iframe') ? 
                                    true : (document.querySelector('#securityCode') ? 
                                        document.querySelector('#securityCode').value : null);
                                
                                const expirationMonth = document.querySelector('#expirationDate iframe') ? 
                                    true : (document.querySelector('#expirationMonth') ? 
                                        document.querySelector('#expirationMonth').value : null);
                                        
                                const expirationYear = document.querySelector('#expirationDate iframe') ? 
                                    true : (document.querySelector('#expirationYear') ? 
                                        document.querySelector('#expirationYear').value : null);
                                
                                if (!cardNumber || !securityCode || (!expirationMonth && !expirationYear && !document.querySelector('#expirationDate iframe'))) {
                                    console.error('Campos obrigatórios não preenchidos para método alternativo');
                                    $('#result-message').html('<div class="error-message">Por favor, preencha todos os campos do cartão corretamente.</div>');
                                    $submitButton.prop('disabled', false).text('Salvar cartão');
                                    return;
                                }
                                
                                // Tentar renderizar o formulário e gerar o token novamente
                                setTimeout(function() {
                                    try {
                                        // Reiniciar o formulário do Mercado Pago
                                        if (cardForm) {
                                            try {
                                                cardForm.unmount();
                                            } catch (e) {
                                                console.log('Erro ao desmontar formulário:', e);
                                            }
                                        }
                                        
                                        // Recriar o formulário
                                        initializeCardForm();
                                        
                                        // Aguardar a inicialização
                                        setTimeout(function() {
                                            if (cardForm) {
                                                $('#result-message').html('<div class="processing-message">Formulário reiniciado. Por favor, tente novamente.</div>');
                                                $submitButton.prop('disabled', false).text('Salvar cartão');
                                            } else {
                                                $('#result-message').html('<div class="error-message">Não foi possível reiniciar o formulário. Atualize a página e tente novamente.</div>');
                                                $submitButton.prop('disabled', false).text('Salvar cartão');
                                            }
                                        }, 1500);
                                    } catch (e) {
                                        console.error('Erro ao tentar método alternativo:', e);
                                        $('#result-message').html('<div class="error-message">Erro ao processar o cartão. Por favor, atualize a página e tente novamente.</div>');
                                        $submitButton.prop('disabled', false).text('Salvar cartão');
                                    }
                                }, 1000);
                            } catch (e) {
                                console.error('Erro ao processar método alternativo:', e);
                                $('#result-message').html('<div class="error-message">Não foi possível processar o cartão. Por favor, verifique os dados inseridos.</div>');
                                $submitButton.prop('disabled', false).text('Salvar cartão');
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Erro ao inicializar formulário:', error);
            $('#result-message').html('<div class="error-message">Erro ao inicializar o formulário: ' + error.message + '</div>');
        }
    }

    // Função para salvar o cartão
    function saveCard(token) {
        if (!token) {
            console.error('Token não fornecido para função saveCard');
            $('#result-message').html('<div class="error-message">Token do cartão não fornecido</div>');
            $('#save-card').prop('disabled', false).text('Salvar cartão');
            return Promise.reject(new Error('Token do cartão não fornecido'));
        }
        
        // Verificar formato do token (deve ser uma string com formato específico do Mercado Pago)
        if (typeof token !== 'string' || !token.match(/^[a-zA-Z0-9_-]+$/)) {
            console.error('Token do cartão em formato inválido:', token);
            $('#result-message').html('<div class="error-message">Formato de token inválido. Por favor, tente novamente.</div>');
            $('#save-card').prop('disabled', false).text('Salvar cartão');
            return Promise.reject(new Error('Formato de token inválido'));
        }
        
        console.log('Salvando cartão com token:', token);
        $('#result-message').html('<div class="processing-message">Enviando dados do cartão para processamento...</div>');
        
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: payment_settings.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_card',
                    nonce: payment_settings.nonce,
                    token: token
                },
                timeout: 60000, // Aumentar timeout para 60 segundos
                success: function(response) {
                    console.log('Resposta do servidor ao salvar cartão:', response);
                    
                    if (!response) {
                        const errorMessage = 'Resposta vazia do servidor';
                        $('#result-message').html('<div class="error-message">' + errorMessage + '</div>');
                        $('#save-card').prop('disabled', false).text('Salvar cartão');
                        reject(new Error(errorMessage));
                        return;
                    }
                    
                    if (response.success) {
                        $('#result-message').html('<div class="success-message">Cartão salvo com sucesso!</div>');
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                        resolve(response);
                    } else {
                        const errorMessage = response.data && response.data.message ? response.data.message : 'Erro ao salvar cartão';
                        $('#result-message').html('<div class="error-message">' + errorMessage + '</div>');
                        $('#save-card').prop('disabled', false).text('Salvar cartão');
                        reject(new Error(errorMessage));
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'Erro ao comunicar com o servidor';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Tempo limite excedido. Verifique sua conexão e tente novamente.';
                    } else if (error) {
                        errorMessage += ': ' + error;
                    }
                    
                    console.error('Erro na requisição AJAX:', xhr, status, error);
                    $('#result-message').html('<div class="error-message">' + errorMessage + '</div>');
                    $('#save-card').prop('disabled', false).text('Salvar cartão');
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