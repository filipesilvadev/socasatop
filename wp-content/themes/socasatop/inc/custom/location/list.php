<?php
$items_per_page = 10;
$current_page =  (get_query_var('paged')) ? get_query_var('paged') : 1;
$offset = ($current_page - 1) * $items_per_page;
$total_locations = wp_count_terms('locations', ['hide_empty' => false]);

$locations = get_terms([
    'taxonomy' => 'locations',
    'hide_empty' => false,
    'number' => $items_per_page,
    'offset' => $offset,
]);

$total_pages = ceil($total_locations / $items_per_page);
?>
<?php foreach ($locations as $location) : ?>
    <div data-elementor-type="loop-item" data-elementor-id="1878" class="elementor elementor-1878 e-loop-item e-loop-item-1368 post-1368 page type-page status-publish hentry" data-elementor-post-type="elementor_library" data-custom-edit-handle="1">
        <div class="elementor-element elementor-element-378d2fdb e-con-full e-flex e-con e-parent" data-id="378d2fdb" data-element_type="container">
            <div class="elementor-element elementor-element-2a0424d elementor-widget elementor-widget-heading" data-id="2a0424d" data-element_type="widget" data-widget_type="heading.default">
                <div class="elementor-widget-container">
                    <p class="elementor-heading-title elementor-size-default">
                        <?php echo $location->name; ?>
                    </p>
                </div>
            </div>
            <div class="elementor-element elementor-element-43b64c7b e-con-full e-flex e-con e-child" data-id="43b64c7b" data-element_type="container">
                <div class="elementor-element elementor-element-6772427c elementor-button-align-stretch elementor-widget elementor-widget-form" data-id="6772427c" data-element_type="widget" data-settings="{&quot;step_next_label&quot;:&quot;Next&quot;,&quot;step_previous_label&quot;:&quot;Previous&quot;,&quot;button_width&quot;:&quot;100&quot;,&quot;step_type&quot;:&quot;number_text&quot;,&quot;step_icon_shape&quot;:&quot;circle&quot;}" data-widget_type="form.default">
                    <div class="elementor-widget-container">
                        <form method="post" id="delete_location" name="New Form">
                            <input type="hidden" name="queried_id" value="1368">
                            <div class="elementor-form-fields-wrapper elementor-labels-above">
                                <div class="elementor-field-group elementor-column elementor-field-type-submit elementor-col-100 e-form__buttons">
                                    <button type="submit" class="elementor-button elementor-size-sm">
                                        <span>
                                            <span class="elementor-align-icon-left elementor-button-icon">
                                                <svg aria-hidden="true" class="e-font-icon-svg e-fas-trash" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M432 32H312l-9.4-18.7A24 24 0 0 0 281.1 0H166.8a23.72 23.72 0 0 0-21.4 13.3L136 32H16A16 16 0 0 0 0 48v32a16 16 0 0 0 16 16h416a16 16 0 0 0 16-16V48a16 16 0 0 0-16-16zM53.2 467a48 48 0 0 0 47.9 45h245.8a48 48 0 0 0 47.9-45L416 128H32z"></path>
                                                </svg> </span>
                                            <span class="elementor-button-text">Excluir</span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php if ($total_pages > 1) : ?>
    <style>
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
        }

        .pagination .page-numbers {
            background: rgb(56, 88, 233);
            padding: 0.25rem 0.7rem;
            border-radius: 50px;
            color: #FFF;
        }

        .pagination .page-numbers:hover {
            background: rgb(29, 78, 216);
        }

        .pagination .current {
            background: #1e40af !important;
        }
    </style>
    <nav class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
            <a class="page-numbers <?= ($i === $current_page) ? "current" : "" ?>" href="?paged=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>