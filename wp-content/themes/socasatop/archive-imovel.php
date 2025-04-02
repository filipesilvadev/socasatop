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

<div class="imoveis-archive-container">
    <header class="page-header">
        <h1 class="page-title">Imóveis</h1>
        <div class="page-description">Confira nossa lista de imóveis disponíveis</div>
    </header>

    <div class="imoveis-filter">
        <form id="imoveis-filter-form" method="get">
            <div class="filter-row">
                <div class="filter-item">
                    <label for="filter-tipo">Tipo de Imóvel</label>
                    <select name="tipo" id="filter-tipo">
                        <option value="">Todos</option>
                        <option value="Apartamento">Apartamento</option>
                        <option value="Casa">Casa</option>
                        <option value="Terreno">Terreno</option>
                        <option value="Comercial">Comercial</option>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="filter-quartos">Quartos</label>
                    <select name="quartos" id="filter-quartos">
                        <option value="">Todos</option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                        <option value="4">4+</option>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="filter-preco-min">Preço Mínimo</label>
                    <input type="number" name="preco_min" id="filter-preco-min" placeholder="R$">
                </div>
                
                <div class="filter-item">
                    <label for="filter-preco-max">Preço Máximo</label>
                    <input type="number" name="preco_max" id="filter-preco-max" placeholder="R$">
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="filter-button">Filtrar</button>
                <button type="reset" class="reset-button">Limpar Filtros</button>
            </div>
        </form>
    </div>

    <div class="imoveis-grid">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <div class="imovel-card">
                    <div class="imovel-thumbnail">
                        <?php if (has_post_thumbnail()) : ?>
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('medium_large'); ?>
                            </a>
                        <?php else : ?>
                            <a href="<?php the_permalink(); ?>">
                                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/placeholder.jpg" alt="Imagem não disponível">
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="imovel-details">
                        <h2 class="imovel-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        
                        <div class="imovel-location"><?php echo get_post_meta(get_the_ID(), 'location', true); ?></div>
                        
                        <div class="imovel-price"><?php echo get_post_meta(get_the_ID(), 'amount', true); ?></div>
                        
                        <div class="imovel-meta">
                            <?php if ($size = get_post_meta(get_the_ID(), 'size', true)) : ?>
                                <div class="meta-item meta-size">
                                    <span class="meta-icon">📏</span>
                                    <span class="meta-text"><?php echo $size; ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($bedrooms = get_post_meta(get_the_ID(), 'bedrooms', true)) : ?>
                                <div class="meta-item meta-bedrooms">
                                    <span class="meta-icon">🛏️</span>
                                    <span class="meta-text"><?php echo $bedrooms; ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($property_type = get_post_meta(get_the_ID(), 'property_type', true)) : ?>
                                <div class="meta-item meta-type">
                                    <span class="meta-icon">🏠</span>
                                    <span class="meta-text"><?php echo $property_type; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <a href="<?php the_permalink(); ?>" class="view-details">Ver Detalhes</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else : ?>
            <div class="no-results">
                <p>Nenhum imóvel encontrado.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="pagination">
        <?php
        echo paginate_links(array(
            'prev_text' => '&laquo; Anterior',
            'next_text' => 'Próximo &raquo;',
        ));
        ?>
    </div>
</div>

<style>
.imoveis-archive-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    margin-bottom: 30px;
    text-align: center;
}

.page-title {
    font-size: 32px;
    margin-bottom: 10px;
}

.page-description {
    font-size: 16px;
    color: #666;
}

.imoveis-filter {
    background-color: #f7f7f7;
    padding: 20px;
    border-radius: 5px;
    margin-bottom: 30px;
}

.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.filter-item {
    display: flex;
    flex-direction: column;
}

.filter-item label {
    margin-bottom: 5px;
    font-weight: bold;
    font-size: 14px;
}

.filter-item select,
.filter-item input {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.filter-actions {
    display: flex;
    gap: 10px;
}

.filter-button,
.reset-button {
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}

.filter-button {
    background-color: #2c3e50;
    color: white;
}

.reset-button {
    background-color: #eee;
    color: #333;
}

.imoveis-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.imovel-card {
    border: 1px solid #eee;
    border-radius: 5px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.imovel-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.imovel-thumbnail img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.imovel-details {
    padding: 15px;
}

.imovel-title {
    font-size: 18px;
    margin-bottom: 10px;
}

.imovel-title a {
    color: #2c3e50;
    text-decoration: none;
}

.imovel-location {
    font-size: 14px;
    color: #666;
    margin-bottom: 10px;
}

.imovel-price {
    font-size: 18px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 10px;
}

.imovel-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}

.meta-item {
    display: flex;
    align-items: center;
    font-size: 14px;
    color: #555;
}

.meta-icon {
    margin-right: 5px;
}

.view-details {
    display: inline-block;
    background-color: #2c3e50;
    color: white;
    padding: 8px 15px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    font-weight: bold;
}

.view-details:hover {
    background-color: #1a252f;
}

.no-results {
    grid-column: 1 / -1;
    padding: 20px;
    text-align: center;
    background-color: #f9f9f9;
    border-radius: 5px;
}

.pagination {
    text-align: center;
}

.pagination .page-numbers {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 5px;
    border: 1px solid #ddd;
    border-radius: 3px;
    text-decoration: none;
    color: #333;
}

.pagination .current {
    background-color: #2c3e50;
    color: white;
    border-color: #2c3e50;
}

.pagination .prev,
.pagination .next {
    font-weight: bold;
}

@media (max-width: 768px) {
    .filter-row {
        grid-template-columns: 1fr;
    }
    
    .imoveis-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
}
get_footer();
?> 