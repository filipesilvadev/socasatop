<?php
function add_broker_metabox() {
    add_meta_box(
        'immobile_brokers',
        'Corretores Associados',
        'render_broker_metabox',
        'immobile',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'add_broker_metabox');

function render_broker_metabox($post) {
    global $wpdb;
    
    // Buscar todos os corretores (usuários com papel de autor)
    $brokers = get_users(['role' => 'author']);
    
    // Buscar corretores já associados a este imóvel
    $broker_immobile_table = $wpdb->prefix . 'broker_immobile';
    $associated_brokers = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT broker_id, is_sponsor FROM $broker_immobile_table WHERE immobile_id = %d",
            $post->ID
        )
    );
    
    $associated_broker_ids = wp_list_pluck($associated_brokers, 'broker_id');
    $sponsored_broker_ids = array_filter($associated_brokers, function($broker) {
        return $broker->is_sponsor == 1;
    });
    $sponsored_broker_ids = wp_list_pluck($sponsored_broker_ids, 'broker_id');
    
    ?>
    <div class="broker-selection">
        <?php foreach ($brokers as $broker): ?>
            <div>
                <label>
                    <input type="checkbox" 
                           name="immobile_brokers[]" 
                           value="<?php echo $broker->ID; ?>"
                           <?php checked(in_array($broker->ID, $associated_broker_ids)); ?>>
                    <?php echo $broker->display_name; ?>
                </label>
                <label>
                    <input type="checkbox" 
                           name="immobile_sponsor_brokers[]" 
                           value="<?php echo $broker->ID; ?>"
                           <?php checked(in_array($broker->ID, $sponsored_broker_ids)); ?>>
                    Destaque
                </label>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function save_broker_metabox($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    global $wpdb;
    $broker_immobile_table = $wpdb->prefix . 'broker_immobile';
    
    // Remover associações existentes
    $wpdb->delete($broker_immobile_table, ['immobile_id' => $post_id]);
    
    // Salvar novos corretores associados
    $brokers = isset($_POST['immobile_brokers']) ? $_POST['immobile_brokers'] : [];
    $sponsor_brokers = isset($_POST['immobile_sponsor_brokers']) ? $_POST['immobile_sponsor_brokers'] : [];
    
    foreach ($brokers as $broker_id) {
        $is_sponsor = in_array($broker_id, $sponsor_brokers) ? 1 : 0;
        
        $wpdb->insert(
            $broker_immobile_table,
            [
                'immobile_id' => $post_id,
                'broker_id' => $broker_id,
                'is_sponsor' => $is_sponsor
            ]
        );
    }
}
add_action('save_post_immobile', 'save_broker_metabox');