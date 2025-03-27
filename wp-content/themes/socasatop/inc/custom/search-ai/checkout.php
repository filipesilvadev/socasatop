<?php
function mp_get_config() {
    return [
        'sandbox' => get_option('mercadopago_sandbox', 'yes') === 'yes',
        'public_key' => get_option('mercadopago_public_key', 'TEST-70b46d06-add9-499a-942e-0f5c01b8769a'),
        'access_token' => get_option('mercadopago_access_token', 'TEST-1105123470040162-010319-784660b8cba90a127251b50a9e066db6-242756635')
    ];
}

function render_checkout_patrociados() {
    if (!isset($_GET['properties'])) {
        return 'Nenhum imóvel selecionado para patrocínio.';
    }

    // Verificar se o usuário está logado
    if (!is_user_logged_in()) {
        return '<div class="checkout-error">Você precisa estar <a href="/login">logado</a> para realizar o patrocínio de imóveis.</div>';
    }

    // Obter os IDs dos imóveis selecionados
    $property_ids = explode(',', $_GET['properties']);
    
    // Verificar se há imóveis válidos
    if (empty($property_ids)) {
        return '<div class="checkout-error">Nenhum imóvel válido selecionado para patrocínio.</div>';
    }
    
    // Criando lista de produtos para o checkout unificado
    $products = [];
    foreach ($property_ids as $id) {
        $title = get_the_title($id);
        if (!empty($title)) {
            $products[] = [
                'id' => 'sponsored_property',
                'entity_id' => $id,
                'name' => 'Patrocínio: ' . $title,
                'price' => 25.00,
                'description' => 'Patrocínio do imóvel por 30 dias'
            ];
        }
    }
    
    // Verificar se há produtos válidos
    if (empty($products)) {
        return '<div class="checkout-error">Não foi possível carregar informações dos imóveis selecionados.</div>';
    }
    
    // Salvar produtos na sessão (será usado pelo manipulador AJAX)
    if (!session_id()) {
        session_start();
    }
    $_SESSION['checkout_products'] = $products;
    
    // Verificar se a função de checkout unificado existe
    if (function_exists('render_multi_product_checkout')) {
        // Usar o checkout unificado
        return render_multi_product_checkout($products, [
            'success_url' => home_url('/patrocinio-confirmado/'),
            'cancel_url' => home_url('/patrocinio-cancelado/')
        ]);
    } else {
        // Fallback para o checkout antigo (não deveria acontecer se a implementação estiver correta)
        error_log('Função render_multi_product_checkout não encontrada. Usando checkout antigo.');
        return render_legacy_checkout($property_ids);
    }
}

/**
 * Função de fallback para o checkout antigo
 * Esta função só será usada se o checkout unificado não estiver disponível
 */
function render_legacy_checkout($property_ids) {
    $total_amount = count($property_ids) * 25;
    $config = mp_get_config();

    ob_start();
    ?>
    <div class="checkout-container legacy-checkout">
        <h2>Checkout - Patrocínio de Imóveis</h2>
        <div class="checkout-notice">
            <p>Atenção: Usando sistema de pagamento legado. Entre em contato com o administrador.</p>
        </div>
        <div class="selected-properties">
            <h3>Imóveis Selecionados:</h3>
            <ul>
                <?php foreach ($property_ids as $id): ?>
                    <li><?php echo get_the_title($id); ?> - R$ 25,00</li>
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
        .checkout-notice {
            background-color: #fffacd;
            border-left: 4px solid #ffa500;
            padding: 10px 15px;
            margin-bottom: 20px;
        }
        .checkout-error {
            background-color: #ffecec;
            border-left: 4px solid #f44336;
            padding: 10px 15px;
            margin-bottom: 20px;
        }
        .legacy-checkout {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 20px;
            background-color: #f9f9f9;
        }
    </style>
    <?php
    return ob_get_clean();
}

add_shortcode('checkout_patrociados', 'render_checkout_patrociados');