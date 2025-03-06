<?php
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$search_query = isset($_GET['lead_search']) ? sanitize_text_field($_GET['lead_search']) : '';

$args = [
    'post_type' => 'lead',
    'posts_per_page' => 30,
    'paged' => $paged
];

if (!empty($search_query)) {
    $args['s'] = $search_query;
}

$brokers = new WP_Query($args);
?>

<form method="get" class="lead-search-form">
    <input type="text" name="lead_search" value="<?php echo esc_attr($search_query); ?>" placeholder="Pesquisar lead...">
    <button type="submit">Buscar</button>
</form>

<ul class="list-posts">
    <?php
    if ($brokers->have_posts()) :
        while ($brokers->have_posts()) :
            $brokers->the_post();
            $post_ID = get_the_ID();
    ?>
        <li class="item">
            <a href="<?php the_permalink(); ?>" class="btn-text">
                <?php the_title(); ?>
            </a>
        </li>
    <?php
        endwhile;
    else :
        echo '<li>Nenhum lead encontrado.</li>';
    endif;
    ?>
    <div class="pagination">
        <?php
        echo paginate_links(array(
            'total' => $brokers->max_num_pages,
            'current' => max(1, $paged),
        )); ?>
    </div>
</ul>