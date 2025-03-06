<?php
function add_broker_select_metabox() {
    add_meta_box(
        'immobile_brokers_select',
        'Corretores Associados',
        'render_broker_select_metabox',
        'immobile',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'add_broker_select_metabox');

function render_broker_select_metabox($post) {
    global $wpdb;
    
    wp_nonce_field('broker_select_metabox', 'broker_select_nonce');
    
    $broker_immobile_table = $wpdb->prefix . 'broker_immobile';
    $brokers = get_users(['role' => 'author']);
    
    $associated_brokers = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT broker_id 
        FROM {$broker_immobile_table} 
        WHERE immobile_id = %d
    ", $post->ID));

    $broker_data = [];
    foreach ($associated_brokers as $broker_id) {
        $user = get_user_by('id', $broker_id);
        if ($user) {
            $broker_data[] = [
                'id' => $user->ID,
                'name' => $user->display_name
            ];
        }
    }
    ?>
    <div class="broker-selection">
        <div class="broker-search">
            <input type="text" id="broker-search" placeholder="Digite o nome do corretor..." class="widefat">
            <div id="broker-results" class="broker-results"></div>
        </div>
        <div id="selected-brokers" class="selected-brokers">
            <?php foreach($broker_data as $broker): ?>
                <div class="selected-broker" data-id="<?php echo esc_attr($broker['id']); ?>">
                    <?php echo esc_html($broker['name']); ?>
                    <span class="remove-broker">×</span>
                    <input type="hidden" name="immobile_brokers[]" value="<?php echo esc_attr($broker['id']); ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <style>
    .broker-search {
        margin-bottom: 10px;
        position: relative;
    }
    .broker-results {
        display: none;
        position: absolute;
        background: white;
        border: 1px solid #ddd;
        max-height: 200px;
        overflow-y: auto;
        width: 100%;
        z-index: 1000;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .broker-result-item {
        padding: 8px 10px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }
    .broker-result-item:hover {
        background: #f0f0f0;
    }
    .selected-brokers {
        margin-top: 10px;
    }
    .selected-broker {
        background: #f0f0f0;
        padding: 8px 10px;
        margin-bottom: 5px;
        border-radius: 3px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .remove-broker {
        color: red;
        cursor: pointer;
        font-weight: bold;
        margin-left: 8px;
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        var brokers = <?php echo json_encode(array_map(function($broker) {
            return [
                'id' => $broker->ID,
                'name' => $broker->display_name
            ];
        }, $brokers)); ?>;

        function updateSelectedBrokers() {
            var selectedIds = [];
            $('.selected-broker').each(function() {
                selectedIds.push($(this).data('id'));
            });
            return selectedIds;
        }

        $('#broker-search').on('input', function() {
            var search = $(this).val().toLowerCase();
            var $results = $('#broker-results');
            var selectedIds = updateSelectedBrokers();
            
            if (search.length < 2) {
                $results.hide();
                return;
            }

            var filtered = brokers.filter(function(broker) {
                return broker.name.toLowerCase().includes(search) &&
                       !selectedIds.includes(broker.id);
            });

            if (filtered.length > 0) {
                $results.html('').show();
                filtered.forEach(function(broker) {
                    $results.append(
                        $('<div class="broker-result-item">')
                            .text(broker.name)
                            .data('broker', broker)
                    );
                });
            } else {
                $results.hide();
            }
        });

        $(document).on('click', '.broker-result-item', function() {
            var broker = $(this).data('broker');
            var selectedIds = updateSelectedBrokers();
            
            if (!selectedIds.includes(broker.id)) {
                $('#selected-brokers').append(
                    '<div class="selected-broker" data-id="' + broker.id + '">' +
                        broker.name +
                        '<span class="remove-broker">×</span>' +
                        '<input type="hidden" name="immobile_brokers[]" value="' + broker.id + '">' +
                    '</div>'
                );
            }
            $('#broker-search').val('');
            $('#broker-results').hide();
        });

        $(document).on('click', '.remove-broker', function() {
            $(this).closest('.selected-broker').remove();
        });

        $(document).click(function(e) {
            if (!$(e.target).closest('.broker-search').length) {
                $('#broker-results').hide();
            }
        });
    });
    </script>
    <?php
}

function save_broker_select_metabox($post_id) {
    if (!isset($_POST['broker_select_nonce']) || !wp_verify_nonce($_POST['broker_select_nonce'], 'broker_select_metabox')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    global $wpdb;
    $broker_immobile_table = $wpdb->prefix . 'broker_immobile';
    
    $wpdb->delete($broker_immobile_table, ['immobile_id' => $post_id]);
    
    if (isset($_POST['immobile_brokers']) && is_array($_POST['immobile_brokers'])) {
        $inserted_brokers = [];
        foreach ($_POST['immobile_brokers'] as $broker_id) {
            $broker_id = intval($broker_id);
            if (!in_array($broker_id, $inserted_brokers)) {
                $wpdb->insert(
                    $broker_immobile_table,
                    [
                        'immobile_id' => $post_id,
                        'broker_id' => $broker_id,
                        'is_sponsor' => 0
                    ],
                    ['%d', '%d', '%d']
                );
                $inserted_brokers[] = $broker_id;
            }
        }
    }
}
add_action('save_post_immobile', 'save_broker_select_metabox');