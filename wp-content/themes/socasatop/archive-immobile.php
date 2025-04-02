<?php 
/**
 * Template para exibição da lista de imóveis
 */
get_header(); 

$is_elementor_theme_exist = function_exists('elementor_theme_do_location');

// Verificar se o Elementor está gerenciando este template
if ($is_elementor_theme_exist && elementor_theme_do_location('archive')) {
    // O Elementor gerencia o template e o conteúdo é exibido automaticamente
} else {
    // Template padrão caso o Elementor não esteja gerenciando
?>

<div class="container immobile-archive-container">
    <div class="immobile-list-header">
        <h1><?php echo is_tax() ? single_term_title() : 'Imóveis'; ?></h1>
        <?php
        if (is_tax()) {
            $term_description = term_description();
            if (!empty($term_description)) {
                echo '<div class="term-description">' . $term_description . '</div>';
            }
        }
        ?>
    </div>

    <div class="immobile-grid">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <div class="immobile-card">
                    <div class="immobile-thumbnail">
                        <?php
                        $gallery = get_post_meta(get_the_ID(), 'immobile_gallery', true);
                        $gallery_ids = $gallery ? explode(',', $gallery) : [];
                        $image_url = !empty($gallery_ids) ? wp_get_attachment_image_url($gallery_ids[0], 'medium_large') : '';
                        if ($image_url) {
                            echo '<img src="' . esc_url($image_url) . '" alt="' . get_the_title() . '">';
                        } else {
                            echo '<div class="no-image">Sem imagem</div>';
                        }
                        ?>
                    </div>
                    <div class="immobile-card-content">
                        <h2><?php the_title(); ?></h2>
                        <p class="immobile-location"><?php echo get_post_meta(get_the_ID(), 'location', true); ?></p>
                        <div class="immobile-features">
                            <span><i class="fas fa-bed"></i> <?php echo get_post_meta(get_the_ID(), 'bedrooms', true); ?> quartos</span>
                            <span><i class="fas fa-ruler-combined"></i> <?php echo get_post_meta(get_the_ID(), 'size', true); ?>m²</span>
                        </div>
                        <div class="immobile-price">
                            <span>R$ <?php echo number_format(get_post_meta(get_the_ID(), 'amount', true), 2, ',', '.'); ?></span>
                        </div>
                        <a href="<?php the_permalink(); ?>" class="immobile-link">Ver detalhes</a>
                    </div>
                </div>
            <?php endwhile; ?>
            
            <div class="pagination">
                <?php 
                echo paginate_links(array(
                    'prev_text' => '<i class="fas fa-chevron-left"></i> Anterior',
                    'next_text' => 'Próximo <i class="fas fa-chevron-right"></i>',
                )); 
                ?>
            </div>
        <?php else : ?>
            <div class="no-results">
                <p>Nenhum imóvel encontrado.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.immobile-archive-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.immobile-list-header {
    margin-bottom: 30px;
    text-align: center;
}

.immobile-list-header h1 {
    font-size: 32px;
    color: #333;
    margin-bottom: 10px;
}

.term-description {
    color: #666;
    max-width: 800px;
    margin: 0 auto;
}

.immobile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
}

.immobile-card {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.immobile-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15);
}

.immobile-thumbnail {
    height: 200px;
    overflow: hidden;
}

.immobile-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.immobile-thumbnail img:hover {
    transform: scale(1.1);
}

.no-image {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    color: #999;
}

.immobile-card-content {
    padding: 20px;
}

.immobile-card-content h2 {
    font-size: 18px;
    margin: 0 0 10px;
    color: #333;
    font-weight: bold;
}

.immobile-location {
    color: #666;
    margin-bottom: 10px;
    font-size: 14px;
}

.immobile-features {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    color: #555;
    font-size: 14px;
}

.immobile-price {
    font-size: 18px;
    font-weight: bold;
    color: #0056b3;
    margin-bottom: 15px;
}

.immobile-link {
    display: inline-block;
    background: #0056b3;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    transition: background 0.3s ease;
}

.immobile-link:hover {
    background: #003d7a;
    color: white;
}

.pagination {
    margin-top: 40px;
    text-align: center;
}

.pagination .page-numbers {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 5px;
    border-radius: 4px;
    background: #f5f5f5;
    color: #333;
    text-decoration: none;
}

.pagination .current {
    background: #0056b3;
    color: white;
}

.pagination .prev,
.pagination .next {
    background: transparent;
}

.no-results {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    color: #666;
}

@media (max-width: 768px) {
    .immobile-grid {
        grid-template-columns: 1fr;
    }
    
    .immobile-list-header h1 {
        font-size: 24px;
    }
}
</style>

<?php 
}
get_footer(); 
?> 