(function($) {
    $(document).ready(function() {
        console.log('Highlight Payment JS carregado');
        
        // Função para adicionar logs
        function logDebug(message, data) {
            console.log('[MP DEBUG]', message, data);
            if (typeof window.mpDebug === 'function') {
                window.mpDebug(message, data);
            }
        }
        
        // Funções para mostrar/esconder mensagens
        function showErrorMessage(message) {
            console.error('Erro:', message);
            $('#payment-result').show();
            $('.error-message').show().text(message);
            $('.success-message').hide();
        }
        
        function showSuccessMessage(message) {
            console.log('Sucesso:', message);
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
        } else {
            logDebug('Highlight payment data', highlight_payment);
        }
        
        // Função para criar um token de cartão
        function createCardToken(cardData) {
            return new Promise((resolve, reject) => {
                if (typeof MercadoPago === 'undefined') {
                    reject(new Error('SDK MercadoPago não está disponível'));
                    return;
                }
                
                try {
                    const mp = new MercadoPago(highlight_payment.public_key);
                    
                    mp.createCardToken(cardData)
                        .then(function(result) {
                            if (result.error) {
                                reject(new Error(result.error));
                            } else {
                                resolve(result);
                            }
                        })
                        .catch(function(error) {
                            reject(error);
                        });
                } catch (error) {
                    reject(error);
                }
            });
        }
        
        // Processar pagamento com o token do cartão
        function processPayment(tokenData) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: highlight_payment.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'highlight_payment_process',
                        nonce: highlight_payment.nonce,
                        immobile_id: highlight_payment.immobile_id,
                        token: tokenData.id,
                        payment_method_id: tokenData.payment_method_id,
                        issuer_id: tokenData.issuer.id,
                        identification_number: $('#identificationNumber').val().replace(/\D/g, ''),
                        save_card: $('#save_card').is(':checked')
                    },
                    success: function(response) {
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(new Error(response.data && response.data.message ? response.data.message : 'Erro ao processar pagamento.'));
                        }
                    },
                    error: function(xhr, status, error) {
                        reject(new Error('Erro na comunicação com o servidor: ' + error));
                    }
                });
            });
        }
        
        // Lidar com evento de clique no botão
        $('#highlight-button').on('click', function(event) {
            event.preventDefault();
            
            // Validar formulário
            const cardholderName = $('#cardholderName').val().trim();
            const cardNumber = $('#cardNumber').val().replace(/\D/g, '');
            const expirationMonth = $('#cardExpirationMonth').val();
            const expirationYear = $('#cardExpirationYear').val();
            const securityCode = $('#securityCode').val().trim();
            const identificationNumber = $('#identificationNumber').val().replace(/\D/g, '');
            
            // Verificar se todos os campos estão preenchidos
            if (!cardholderName) {
                showErrorMessage('Por favor, preencha o nome que está no cartão.');
                return;
            }
            
            if (!cardNumber || cardNumber.length < 14) {
                showErrorMessage('Por favor, informe um número de cartão válido.');
                return;
            }
            
            if (!securityCode) {
                showErrorMessage('Por favor, informe o código de segurança do cartão.');
                return;
            }
            
            if (!identificationNumber || identificationNumber.length < 11) {
                showErrorMessage('Por favor, informe um CPF válido.');
                return;
            }
            
            // Verificar se os termos foram aceitos
            if ($('#termsAccepted').length > 0 && !$('#termsAccepted').is(':checked')) {
                showErrorMessage('Você precisa aceitar os termos e condições para continuar.');
                return;
            }
            
            // Mostrar loading
            showLoading(true);
            
            // Construir dados do cartão
            const cardData = {
                cardholderName: cardholderName,
                cardNumber: cardNumber,
                cardExpirationMonth: expirationMonth,
                cardExpirationYear: expirationYear,
                securityCode: securityCode,
                identificationType: 'CPF',
                identificationNumber: identificationNumber
            };
            
            logDebug('Criando token de cartão com os dados', cardData);
            
            // Criar token e processar pagamento
            createCardToken(cardData)
                .then(tokenData => {
                    logDebug('Token criado com sucesso', tokenData);
                    return processPayment(tokenData);
                })
                .then(response => {
                    showLoading(false);
                    showSuccessMessage(response.data.message || 'Pagamento processado com sucesso!');
                    
                    // Esconder formulário
                    $('.highlight-payment-form').slideUp();
                    
                    // Redirecionar se necessário
                    if (response.data && response.data.redirect_url) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 2000);
                    }
                })
                .catch(error => {
                    showLoading(false);
                    showErrorMessage(error.message || 'Erro ao processar pagamento. Tente novamente.');
                    console.error('Erro durante o processamento do pagamento:', error);
                });
        });
        
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
        
        // Máscara para número do cartão
        $('#cardNumber').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            
            if (value.length > 16) {
                value = value.substring(0, 16);
            }
            
            // Formatar número do cartão em grupos de 4
            value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            
            $(this).val(value);
        });
        
        // Máscara para CVV
        $('#securityCode').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            
            if (value.length > 4) {
                value = value.substring(0, 4);
            }
            
            $(this).val(value);
        });
        
        logDebug('Script de pagamento inicializado');
    });
})(jQuery); 