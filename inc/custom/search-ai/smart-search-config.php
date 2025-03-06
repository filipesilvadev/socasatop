<?php
class SmartSearchConfig {
    public static $PROPERTY_TYPES = [
        'apartamento' => 'Apartamento',
        'casa' => 'Casa',
        'sobrado' => 'Sobrado',
        'cobertura' => 'Cobertura',
        'terreno' => 'Terreno'
    ];

    public static $BEDROOM_RANGES = [
        0 => 'Qualquer',
        1 => '1 quarto',
        2 => '2 quartos',
        3 => '3 quartos',
        4 => '4+ quartos'
    ];

    public static function get_property_types() {
        return self::$PROPERTY_TYPES;
    }

    public static function get_bedroom_ranges() {
        return self::$BEDROOM_RANGES;
    }
}