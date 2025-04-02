<?php
/**
 * Template para exibição de um imóvel
 */
get_header();
?>

<div class="imovel-container">
    <div class="imovel-header">
        <h1 class="imovel-title"><?php the_title(); ?></h1>
        <div class="imovel-location"><?php echo get_post_meta(get_the_ID(), 'location', true); ?></div>
    </div>

    <div class="imovel-gallery">
        <?php 
        // Exibir galeria de imagens
        $gallery_ids = get_post_meta(get_the_ID(), 'gallery_images', true);
        if (!empty($gallery_ids) && is_array($gallery_ids)) {
            echo '<div class="imovel-gallery-container">';
            foreach ($gallery_ids as $attachment_id) {
                $image_url = wp_get_attachment_image_url($attachment_id, 'large');
                $image_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                echo '<div class="gallery-item">';
                echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '" />';
                echo '</div>';
            }
            echo '</div>';
        } elseif (has_post_thumbnail()) {
            // Se não tiver galeria, mas tiver thumbnail
            echo '<div class="imovel-featured-image">';
            the_post_thumbnail('large');
            echo '</div>';
        }
        ?>
    </div>
    
    <div class="imovel-details">
        <div class="imovel-price">
            <h2>Preço</h2>
            <div class="detail-value"><?php echo get_post_meta(get_the_ID(), 'amount', true); ?></div>
        </div>
        
        <div class="imovel-info">
            <div class="info-item">
                <span class="info-label">Área:</span>
                <span class="info-value"><?php echo get_post_meta(get_the_ID(), 'size', true); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Quartos:</span>
                <span class="info-value"><?php echo get_post_meta(get_the_ID(), 'bedrooms', true); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Tipo de Imóvel:</span>
                <span class="info-value"><?php echo get_post_meta(get_the_ID(), 'property_type', true); ?></span>
            </div>
            
            <?php if ($condominium = get_post_meta(get_the_ID(), 'condominium', true)): ?>
            <div class="info-item">
                <span class="info-label">Condomínio:</span>
                <span class="info-value"><?php echo $condominium; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($financing = get_post_meta(get_the_ID(), 'financing', true)): ?>
            <div class="info-item">
                <span class="info-label">Financiamento:</span>
                <span class="info-value"><?php echo $financing; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($facade = get_post_meta(get_the_ID(), 'facade', true)): ?>
            <div class="info-item">
                <span class="info-label">Fachada:</span>
                <span class="info-value"><?php echo $facade; ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="imovel-description">
        <h2>Descrição</h2>
        <?php the_content(); ?>
    </div>
    
    <?php if ($url_original = get_post_meta(get_the_ID(), 'url_original', true)): ?>
    <div class="imovel-source">
        <p>Fonte: <a href="<?php echo esc_url($url_original); ?>" target="_blank" rel="noopener noreferrer">Ver imóvel no site original</a></p>
    </div>
    <?php endif; ?>
</div>

<style>
.imovel-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.imovel-header {
    margin-bottom: 20px;
}

.imovel-title {
    font-size: 28px;
    margin-bottom: 10px;
}

.imovel-location {
    font-size: 16px;
    color: #666;
}

.imovel-gallery-container {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 30px;
}

.gallery-item img {
    width: 100%;
    height: auto;
    border-radius: 5px;
    object-fit: cover;
}

.imovel-featured-image img {
    width: 100%;
    height: auto;
    border-radius: 5px;
    margin-bottom: 30px;
}

.imovel-details {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    margin-bottom: 30px;
    background-color: #f7f7f7;
    padding: 20px;
    border-radius: 5px;
}

.imovel-price {
    flex: 1;
    min-width: 200px;
}

.imovel-price h2 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 18px;
}

.detail-value {
    font-size: 24px;
    font-weight: bold;
    color: #2c3e50;
}

.imovel-info {
    flex: 2;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-label {
    font-weight: bold;
    margin-bottom: 5px;
    color: #666;
}

.info-value {
    font-size: 16px;
}

.imovel-description {
    margin-bottom: 30px;
    line-height: 1.6;
}

.imovel-description h2 {
    margin-bottom: 15px;
    font-size: 20px;
}

.imovel-source {
    font-size: 14px;
    color: #777;
    border-top: 1px solid #eee;
    padding-top: 20px;
}

@media (max-width: 768px) {
    .imovel-gallery-container {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .imovel-details {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .imovel-gallery-container {
        grid-template-columns: 1fr;
    }
    
    .imovel-info {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
get_footer(); 