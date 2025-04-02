<?php get_header(); ?>

<div class="container immobile-single-container">
    <?php 
    while (have_posts()) : 
        the_post();
        echo do_shortcode('[immobile_profile]');
    endwhile; 
    ?>
</div>

<style>
/* Garantir que o conteúdo do imóvel seja exibido corretamente */
.immobile-single-container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px 15px;
}

@media (max-width: 768px) {
    .immobile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .price-value {
        float: none;
        margin-top: 10px;
    }
    
    .tab-buttons {
        flex-wrap: wrap;
    }
    
    .tab-button {
        flex: 1 0 100%;
        text-align: center;
    }
    
    .brokers-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>