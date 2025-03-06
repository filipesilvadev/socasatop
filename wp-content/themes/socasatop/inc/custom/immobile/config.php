<?php
/**
 * Configurações compartilhadas para o CPT Immobile
 */

function get_immobile_options() {
    return array(
        'offer_types' => ['Comprar', 'Alugar'],
        'yes_no_options' => ['Sim', 'Não'],
        'property_types' => ['Casa', 'Apartamento', 'Terreno'],
    );
}