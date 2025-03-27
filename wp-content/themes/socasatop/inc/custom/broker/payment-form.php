<?php
/**
 * Template para o formulário de pagamento do MercadoPago
 * Incluído pelo arquivo highlight-payment.php
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Variáveis disponíveis:
// $immobile_id - ID do imóvel
// $immobile - Objeto do post do imóvel
// $image_url - URL da imagem do imóvel
// $formatted_price - Preço formatado (R$ 99,90)
// $price - Preço numérico (99.90)
// $public_key - Chave pública do MercadoPago

// Verificar se o imóvel já está em destaque
$is_highlighted = get_post_meta($immobile_id, '_is_highlighted', true);
$highlight_expiry = get_post_meta($immobile_id, '_highlight_expiry', true);
$current_time = current_time('timestamp');
$is_active = $is_highlighted && $highlight_expiry && $highlight_expiry > $current_time;
?>

<div class="highlight-payment-container">
    <div class="highlight-payment-header">
        <h2>Destaque seu Imóvel</h2>
        <p>Aumente a visibilidade do seu imóvel e obtenha mais visualizações!</p>
    </div>
    
    <div class="highlight-payment-property">
        <div class="property-image">
            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($immobile->post_title); ?>">
        </div>
        <div class="property-info">
            <h3><?php echo esc_html($immobile->post_title); ?></h3>
            <div class="property-price"><?php echo esc_html($formatted_price); ?></div>
            <?php if ($is_active): ?>
            <div class="highlight-active">
                <span class="highlight-badge">Em destaque</span>
                <span class="highlight-expiry">Válido até: <?php echo date_i18n('d/m/Y', $highlight_expiry); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="highlight-payment-form">
        <h3>Informações de Pagamento</h3>
        
        <form id="payment-form">
            <div class="form-row">
                <label for="cardholderName">Nome no cartão</label>
                <input type="text" id="cardholderName" name="cardholderName" autocomplete="off" placeholder="Nome como está no cartão">
            </div>
            
            <div class="form-row">
                <label for="cardNumber">Número do cartão</label>
                <input type="text" id="cardNumber" name="cardNumber" autocomplete="off" placeholder="Número do cartão">
            </div>
            
            <div class="form-row form-row-inline">
                <div class="form-column">
                    <label for="cardExpirationMonth">Mês</label>
                    <select id="cardExpirationMonth" name="cardExpirationMonth">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-column">
                    <label for="cardExpirationYear">Ano</label>
                    <select id="cardExpirationYear" name="cardExpirationYear">
                        <?php 
                        $current_year = intval(date('Y'));
                        for ($i = $current_year; $i <= $current_year + 10; $i++): 
                        ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <label for="securityCode">Código de segurança</label>
                <input type="text" id="securityCode" name="securityCode" maxlength="4" autocomplete="off" placeholder="CVV">
            </div>
            
            <div class="form-row">
                <label for="identificationNumber">CPF do titular</label>
                <input type="text" id="identificationNumber" name="identificationNumber" autocomplete="off" placeholder="CPF do titular do cartão">
            </div>
            
            <div class="form-row">
                <label for="installments">Parcelas</label>
                <select id="installments" name="installments">
                    <option value="1">1x de <?php echo $formatted_price; ?></option>
                </select>
            </div>
            
            <div class="form-row checkbox-row">
                <input type="checkbox" id="save_card" name="save_card">
                <label for="save_card">Salvar cartão para futuras compras</label>
            </div>
            
            <div class="form-row checkbox-row">
                <input type="checkbox" id="termsAccepted" name="termsAccepted" required>
                <label for="termsAccepted">Aceito os <a href="#" class="terms-link">termos e condições</a></label>
            </div>
            
            <div class="form-row button-row">
                <button type="button" class="highlight-button" id="highlight-button" data-action="highlight-property">
                    <?php echo $is_active ? 'Renovar Destaque' : 'Destacar Imóvel'; ?>
                </button>
            </div>
        </form>
    </div>
    
    <div id="payment-result" style="display: none;">
        <div class="success-message" style="display: none;">
            <p>Pagamento realizado com sucesso! Seu imóvel agora está em destaque.</p>
        </div>
        <div class="error-message" style="display: none;"></div>
    </div>
    
    <div class="loading-overlay" style="display: none;">
        <div class="loader"></div>
        <p>Processando pagamento...</p>
    </div>
</div>

<?php if ($is_active): ?>
<div class="highlight-info-box">
    <h3>Imóvel já está em destaque</h3>
    <p>Este imóvel já está em destaque até <?php echo date_i18n('d/m/Y', $highlight_expiry); ?>.</p>
    <p>Você pode estender este período fazendo um novo pagamento.</p>
</div>
<?php endif; ?> 