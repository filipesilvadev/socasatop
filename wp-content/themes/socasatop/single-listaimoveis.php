<?php
/**
 * Template para exibição de lista de imóveis
 */
get_header();

$is_elementor_theme_exist = function_exists('elementor_theme_do_location');

// Verificar se o Elementor está gerenciando este template
if ($is_elementor_theme_exist && elementor_theme_do_location('single')) {
    // O Elementor gerencia o template e o conteúdo é exibido automaticamente
} else {
    // Template padrão caso o Elementor não esteja gerenciando
?>

<div class="container lista-imoveis-container">
    <div class="lista-header">
        <h1><?php the_title(); ?></h1>
        <div class="lista-description">
            <?php the_content(); ?>
        </div>
    </div>

    <?php
    // Buscar os imóveis associados a esta lista
    $imoveis_ids = get_post_meta(get_the_ID(), 'immobile_ids', true);
    
    if (!empty($imoveis_ids)) {
        // Converter string de IDs em array
        $imoveis_ids = explode(',', $imoveis_ids);
        
        // Consulta para buscar os imóveis
        $args = array(
            'post_type' => 'immobile',
            'post__in' => $imoveis_ids,
            'posts_per_page' => -1,
            'orderby' => 'post__in', // Mantém a ordem dos IDs
        );
        
        $imoveis_query = new WP_Query($args);
        
        $total_imoveis = $imoveis_query->found_posts;
        
        // Adiciona o total de imóveis encontrados
        echo '<div class="total-imoveis"><h2>Total de (' . $total_imoveis . ') Imóveis</h2></div>';
        
        if ($imoveis_query->have_posts()) :
    ?>
        <div class="imoveis-grid">
            <?php 
            while ($imoveis_query->have_posts()) : 
                $imoveis_query->the_post();
                
                // Obter informações do imóvel
                $post_id = get_the_ID();
                $location = get_post_meta($post_id, 'location', true);
                $amount = get_post_meta($post_id, 'amount', true);
                $bedrooms = get_post_meta($post_id, 'bedrooms', true);
                $bathrooms = get_post_meta($post_id, 'bathrooms', true);
                $size = get_post_meta($post_id, 'size', true);
                $property_type = get_post_meta($post_id, 'property_type', true);
                
                // Obter a galeria de imagens
                $gallery = get_post_meta($post_id, 'immobile_gallery', true);
                $gallery_ids = $gallery ? explode(',', $gallery) : [];
                $featured_image = !empty($gallery_ids) ? wp_get_attachment_image_url($gallery_ids[0], 'medium_large') : '';
            ?>
                <div class="immobile-card">
                    <div class="immobile-thumbnail">
                        <?php if ($featured_image) : ?>
                            <img src="<?php echo esc_url($featured_image); ?>" alt="<?php the_title(); ?>">
                        <?php else : ?>
                            <div class="no-image">Sem imagem</div>
                        <?php endif; ?>
                    </div>
                    <div class="immobile-card-content">
                        <h3><?php the_title(); ?></h3>
                        <?php if ($location) : ?>
                            <p class="immobile-location"><?php echo esc_html($location); ?></p>
                        <?php endif; ?>
                        <div class="immobile-features">
                            <?php if ($bedrooms) : ?>
                                <span><i class="fas fa-bed"></i> <?php echo esc_html($bedrooms); ?> quartos</span>
                            <?php endif; ?>
                            <?php if ($bathrooms) : ?>
                                <span><i class="fas fa-bath"></i> <?php echo esc_html($bathrooms); ?> banheiros</span>
                            <?php endif; ?>
                            <?php if ($size) : ?>
                                <span><i class="fas fa-ruler-combined"></i> <?php echo esc_html($size); ?>m²</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($amount) : ?>
                            <div class="immobile-price">
                                <span>R$ <?php echo number_format(floatval($amount), 2, ',', '.'); ?></span>
                            </div>
                        <?php endif; ?>
                        <a href="<?php the_permalink(); ?>" class="immobile-link">Ver detalhes</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php
        else:
    ?>
        <div class="no-imoveis">
            <p>Nenhum imóvel encontrado nesta lista.</p>
        </div>
    <?php
        endif;
        wp_reset_postdata();
    } else {
    ?>
        <div class="no-imoveis">
            <p>Nenhum imóvel associado a esta lista.</p>
        </div>
    <?php
    }
    ?>
</div>

<style>
.lista-imoveis-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.lista-header {
    margin-bottom: 40px;
    text-align: center;
}

.lista-header h1 {
    font-size: 32px;
    color: #333;
    margin-bottom: 15px;
}

.lista-description {
    color: #666;
    max-width: 800px;
    margin: 0 auto;
}

.total-imoveis {
    text-align: center;
    margin-bottom: 30px;
}

.total-imoveis h2 {
    font-size: 24px;
    color: #333;
}

.imoveis-grid {
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

.immobile-card-content h3 {
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

.no-imoveis {
    text-align: center;
    padding: 40px;
    color: #666;
    background: #f9f9f9;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .imoveis-grid {
        grid-template-columns: 1fr;
    }
    
    .lista-header h1 {
        font-size: 24px;
    }
}
</style>

<?php 
}
get_footer(); 
?> 