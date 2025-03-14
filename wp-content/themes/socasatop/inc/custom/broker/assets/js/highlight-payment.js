jQuery(document).ready(function($) {
    // Inicializar o SDK do Mercado Pago
    const mp = new MercadoPago(highlight_payment.public_key);
    let cardForm;
    
    // Inicializar o formulário de cartão
    const cardFormSettings = {
        amount: highlight_payment.price,
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
                    console.error("Form Mount error:", error);
                    showErrorMessage("Erro ao carregar formulário. Por favor, tente novamente mais tarde.");
                }
            },
            onSubmit: event => {
                event.preventDefault();
            },
            onFetching: (resource) => {
                console.log("Fetching resource:", resource);
                
                // Aqui você pode mostrar um loader, desabilitar o botão, etc.
                $("#process-payment").prop("disabled", true);
                $("#process-payment").text("Processando...");
            }
        }
    };
    
    // Montar o formulário do cartão
    cardForm = mp.cardForm(cardFormSettings);
    
    // Processar pagamento
    $("#process-payment").click(function(e) {
        e.preventDefault();
        
        if (!$("#accept-terms").is(":checked")) {
            showErrorMessage("Você precisa aceitar os termos e condições para continuar.");
            return;
        }
        
        // Verificar se está usando cartão salvo ou novo cartão
        const selectedCard = $('input[name="payment_method"]:checked').val();
        
        if (selectedCard) {
            // Processar com cartão salvo
            processSavedCardPayment(selectedCard);
        } else {
            // Processar com novo cartão
            processNewCardPayment();
        }
    });
    
    function processNewCardPayment() {
        const identificationNumber = $("#identificationNumber").val().trim();
        
        if (!identificationNumber) {
            showErrorMessage("Por favor, preencha o número do CPF.");
            return;
        }
        
        // Obter token do cartão
        cardForm.createCardToken()
            .then(result => {
                if (result.error) {
                    showErrorMessage(result.error);
                    return;
                }
                
                // Enviar token para o servidor
                const paymentData = {
                    token: result.token,
                    transaction_amount: parseFloat(highlight_payment.price),
                    installments: 1,
                    payment_method_id: result.paymentMethodId,
                    issuer_id: result.issuerId,
                    payer: {
                        email: "",  // Será preenchido pelo servidor com o email do usuário logado
                        identification: {
                            type: "CPF",
                            number: identificationNumber
                        }
                    },
                    save_card: true
                };
                
                // Processar pagamento no servidor
                processPayment(paymentData);
            })
            .catch(error => {
                console.error("Error creating card token:", error);
                showErrorMessage("Erro ao processar o cartão. Verifique os dados e tente novamente.");
            });
    }
    
    function processSavedCardPayment(cardId) {
        // Enviar dados para o servidor
        const paymentData = {
            token: cardId,
            transaction_amount: parseFloat(highlight_payment.price),
            installments: 1,
            payer: {
                email: "",  // Será preenchido pelo servidor com o email do usuário logado
                identification: {
                    type: "CPF",
                    number: ""  // O servidor preencherá isso
                }
            }
        };
        
        // Processar pagamento no servidor
        processPayment(paymentData);
    }
    
    function processPayment(paymentData) {
        $.ajax({
            url: highlight_payment.ajax_url,
            type: 'POST',
            data: {
                action: 'process_highlight_payment',
                nonce: highlight_payment.nonce,
                payment_data: paymentData,
                immobile_id: highlight_payment.immobile_id
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage();
                    
                    // Redirecionar após 2 segundos
                    setTimeout(function() {
                        window.location.href = '/corretores/configuracoes-pagamento/';
                    }, 2000);
                } else {
                    showErrorMessage(response.data.message || "Erro ao processar pagamento.");
                }
            },
            error: function() {
                showErrorMessage("Erro de comunicação com o servidor. Por favor, tente novamente.");
            },
            complete: function() {
                $("#process-payment").prop("disabled", false);
                $("#process-payment").text("Destacar Imóvel");
            }
        });
    }
    
    function showSuccessMessage() {
        $("#payment-result").show();
        $("#payment-result .success-message").show();
        $("#payment-result .error-message").hide();
        
        // Esconder o formulário
        $(".payment-form form").hide();
        $(".payment-actions").hide();
    }
    
    function showErrorMessage(message) {
        $("#payment-result").show();
        $("#payment-result .success-message").hide();
        $("#payment-result .error-message").text(message).show();
        
        // Reativar botão
        $("#process-payment").prop("disabled", false);
        $("#process-payment").text("Destacar Imóvel");
    }
}); 