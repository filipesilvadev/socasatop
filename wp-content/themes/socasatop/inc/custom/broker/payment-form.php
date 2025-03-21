<?php
/**
 * Template para o formulário de cartão do MercadoPago
 * 
 * Este arquivo é incluído no formulário de destaque para exibir os campos de cartão
 */

// Impedir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div class="mp-form">
    <div class="form-row">
        <label for="cardNumberContainer">Número do cartão</label>
        <div id="cardNumberContainer" class="mp-card-input"></div>
    </div>
    
    <div class="form-row card-details-row">
        <div class="card-exp">
            <label for="expirationDateContainer">Validade</label>
            <div id="expirationDateContainer" class="mp-card-input"></div>
        </div>
        
        <div class="card-cvc">
            <label for="securityCodeContainer">CVV</label>
            <div id="securityCodeContainer" class="mp-card-input"></div>
        </div>
    </div>
    
    <div class="form-row">
        <label for="cardholderName">Nome como está no cartão</label>
        <input type="text" id="cardholderName" name="cardholderName" placeholder="Nome como está no cartão">
    </div>
    
    <div class="form-row">
        <label for="identificationNumber">CPF do titular</label>
        <input type="text" id="identificationNumber" name="identificationNumber" placeholder="Apenas números">
    </div>
    
    <div class="form-row">
        <label class="checkbox-label">
            <input type="checkbox" id="save_card" name="save_card" checked>
            <span>Salvar este cartão para futuras transações</span>
        </label>
    </div>
</div> 