<?php
function mp_get_config() {
    return [
        'sandbox' => true,
        'public_key' => 'TEST-70b46d06-add9-499a-942e-0f5c01b8769a',
        'access_token' => 'TEST-1105123470040162-010319-784660b8cba90a127251b50a9e066db6-242756635'
    ];
}

function render_checkout_patrociados() {
    if (!isset($_GET['properties'])) {
        return 'Nenhum imóvel selecionado para patrocínio.';
    }

    $property_ids = explode(',', $_GET['properties']);
    $total_amount = count($property_ids) * 25;
    $config = mp_get_config();

    ob_start();
    ?>
    <div class="checkout-container">
        <h2>Checkout - Patrocínio de Imóveis</h2>
        <div class="selected-properties">
            <h3>Imóveis Selecionados:</h3>
            <ul>
                <?php foreach ($property_ids as $id): ?>
                    <li><?php echo get_the_title($id); ?> - R$ 15,00</li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="checkout-summary">
            <p>Total: R$ <?php echo number_format($total_amount, 2, ',', '.'); ?></p>
        </div>
        <div id="cardPaymentBrick_container"></div>
    </div>

    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mp = new MercadoPago('<?php echo $config['public_key']; ?>', {
                locale: 'pt-BR'
            });

            const bricksBuilder = mp.bricks();

            const renderCardPaymentBrick = async (bricksBuilder) => {
                const settings = {
                    initialization: {
                        amount: <?php echo $total_amount; ?>
                    },
                    callbacks: {
                        onReady: () => {
                            console.log('Brick pronto');
                        },
                        onSubmit: async (formData) => {
                            console.log('FormData:', formData);
                            try {
                                const paymentData = {
                                    formData,
                                    properties: <?php echo json_encode($property_ids); ?>,
                                    amount: <?php echo $total_amount; ?>
                                };
                                console.log('Payment Data:', paymentData);
                                
                                const response = await fetch('/wp-json/smart-search/v1/process-payment', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify(paymentData)
                                });
                                const result = await response.json();
                                console.log('Response:', result);
                                
                                if (result.success) {
                                    window.location.href = '/pagamento-confirmado';
                                } else {
                                    alert('Erro no pagamento: ' + (result.error || 'Erro desconhecido'));
                                }
                            } catch (error) {
                                console.error('Erro:', error);
                                alert('Erro ao processar pagamento: ' + error.message);
                            }
                        },
                        onError: (error) => {
                            console.error('Erro:', error);
                            alert('Erro ao processar pagamento: ' + error.message);
                        }
                    }
                };

                await bricksBuilder.create('cardPayment', 'cardPaymentBrick_container', settings);
            };

            renderCardPaymentBrick(bricksBuilder);
        });
    </script>

    <style>
        .checkout-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('checkout_patrociados', 'render_checkout_patrociados');