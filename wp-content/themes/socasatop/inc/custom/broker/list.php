<?php
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$users_per_page = 30;
$args = [
    'role'    => 'author',
    'number'  => $users_per_page,
    'paged'   => $paged,
    'orderby' => 'display_name',
    'order'   => 'ASC'
];

$brokers_query =  new WP_User_Query($args);
$brokers = $brokers_query->get_results();
$total_users = $brokers_query->get_total();
$total_pages = ceil($total_users / $users_per_page);
?>
<ul style="padding: 0;">
    <?php
    if (!empty($brokers)) {
        foreach ($brokers as $author) {
    ?>
            <div data-elementor-type="loop-item" data-elementor-id="1798" class="elementor elementor-1798 e-loop-item e-loop-item-1268 post-1268 page type-page status-publish hentry" data-elementor-post-type="elementor_library" data-custom-edit-handle="1">
                <div class="elementor-element elementor-element-43d42f4 e-con-full e-flex e-con e-parent" data-id="43d42f4" data-element_type="container">
                    <div class="elementor-element elementor-element-a9384dc elementor-widget elementor-widget-heading" data-id="a9384dc" data-element_type="widget" data-widget_type="heading.default">
                        <div class="elementor-widget-container">
                            <p class="elementor-heading-title elementor-size-default"><?php echo $author->display_name; ?></p>
                        </div>
                    </div>
                    <div class="elementor-element elementor-element-264c460 e-con-full e-flex e-con e-child" data-id="264c460" data-element_type="container">
                        <div class="elementor-element elementor-element-078944a elementor-widget elementor-widget-button" data-id="078944a" data-element_type="widget" data-widget_type="button.default">
                            <div class="elementor-widget-container">
                                <div class="elementor-button-wrapper">
                                    <a class="elementor-button elementor-size-sm" role="button" href="<?php echo home_url('/editar-corretores/?id=' . $author->ID) ?>">
                                        <span class="elementor-button-content-wrapper">
                                            <span class="elementor-button-icon elementor-align-icon-left">
                                                <svg aria-hidden="true" class="e-font-icon-svg e-fas-edit" viewBox="0 0 576 512" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M402.6 83.2l90.2 90.2c3.8 3.8 3.8 10 0 13.8L274.4 405.6l-92.8 10.3c-12.4 1.4-22.9-9.1-21.5-21.5l10.3-92.8L388.8 83.2c3.8-3.8 10-3.8 13.8 0zm162-22.9l-48.8-48.8c-15.2-15.2-39.9-15.2-55.2 0l-35.4 35.4c-3.8 3.8-3.8 10 0 13.8l90.2 90.2c3.8 3.8 10 3.8 13.8 0l35.4-35.4c15.2-15.3 15.2-40 0-55.2zM384 346.2V448H64V128h229.8c3.2 0 6.2-1.3 8.5-3.5l40-40c7.6-7.6 2.2-20.5-8.5-20.5H48C21.5 64 0 85.5 0 112v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V306.2c0-10.7-12.9-16-20.5-8.5l-40 40c-2.2 2.3-3.5 5.3-3.5 8.5z"></path>
                                                </svg> </span>
                                            <span class="elementor-button-text">Editar</span>
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="elementor-element elementor-element-02b4880 elementor-button-align-stretch elementor-widget elementor-widget-form" data-id="02b4880" data-element_type="widget" data-settings="{&quot;step_next_label&quot;:&quot;Next&quot;,&quot;step_previous_label&quot;:&quot;Previous&quot;,&quot;button_width&quot;:&quot;100&quot;,&quot;step_type&quot;:&quot;number_text&quot;,&quot;step_icon_shape&quot;:&quot;circle&quot;}" data-widget_type="form.default">
                            <div class="elementor-widget-container">
                                <form class="elementor-form" method="post" id="delete-broker">
                                    <input type="hidden" name="user_id" value="<?php echo $author->ID; ?>">
                                    <div class="elementor-form-fields-wrapper elementor-labels-above">
                                        <div class="elementor-field-type-hidden elementor-field-group elementor-column elementor-field-group-a elementor-col-100">
                                            <input size="1" type="hidden" name="form_fields[a]" id="form-field-a" class="elementor-field elementor-size-sm  elementor-field-textual" value="1268">
                                        </div>
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
    <?php
        }
    } else {
        echo "<p>Nenhum corretor encontrado.</p>";
    }
    ?>
</ul>
<div class="pagination">
    <?php
    echo paginate_links([
        'total' => $total_pages,
        'current' => $paged,
        'format' => '?paged=%#%',
        'prev_text' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="12" transform="matrix(1 0 0 -1 0 24)" fill="#1D4ED8"/><g clip-path="url(#clip0_2107_144)"><path d="M15.4153 12H8.58472" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 8.58478L8.58472 12.0001L12 15.4154" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_2107_144"><rect width="8.57143" height="8.57143" fill="white" transform="matrix(1 0 0 -1 7.71436 16.2858)"/></clipPath></defs></svg>',
        'next_text' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="12" transform="matrix(-1 0 0 1 24 0)" fill="#1D4ED8"/><g clip-path="url(#clip0_2107_144)"><path d="M8.58466 12H15.4153" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 15.4152L15.4153 11.9999L12 8.58459" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_2107_144"><rect width="8.57143" height="8.57143" fill="white" transform="matrix(-1 0 0 1 16.2856 7.71423)"/></clipPath></defs></svg>',
    ]);
    ?>
</div>