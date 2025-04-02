<?php
/**
 * Script para forçar uma atualização e resincronização dos templates
 */

// Exibir informações sobre a versão do WordPress e do tema
echo "Versão do WordPress: " . get_bloginfo('version') . "\n";
echo "Tema atual: " . get_stylesheet() . "\n";
echo "Diretório do tema: " . get_stylesheet_directory() . "\n";

// Limpar o cache de templates
echo "Limpando cache de templates...\n";
global $wp_filter;
if (isset($wp_filter['template_include'])) {
    echo "Existem " . count($wp_filter['template_include']) . " filtros em template_include.\n";
    print_r($wp_filter['template_include']);
} else {
    echo "Nenhum filtro em template_include.\n";
}

// Verificar todos os arquivos de template
$template_files = array(
    'single-immobile.php' => get_stylesheet_directory() . '/inc/custom/immobile/templates/single-immobile.php',
    'single-listaimoveis.php' => get_stylesheet_directory() . '/single-listaimoveis.php',
    'archive-immobile.php' => get_stylesheet_directory() . '/archive-immobile.php'
);

echo "\nVerificando arquivos de template:\n";
foreach ($template_files as $name => $path) {
    echo "$name: " . (file_exists($path) ? "Existe" : "NÃO EXISTE") . " em $path\n";
    if (file_exists($path)) {
        echo "Permissões: " . substr(sprintf('%o', fileperms($path)), -4) . "\n";
        echo "Tamanho: " . filesize($path) . " bytes\n";
        echo "Conteúdo (primeiras 3 linhas):\n";
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        for ($i = 0; $i < min(3, count($lines)); $i++) {
            echo $lines[$i] . "\n";
        }
    }
}

// Forçar a atualização das regras de rewrite
echo "\nForçando atualização das regras de rewrite...\n";
flush_rewrite_rules();
echo "Regras de rewrite atualizadas!\n";

// Verificar se os tipos de post estão registrados corretamente
echo "\nVerificando tipos de post registrados:\n";
$post_types = get_post_types(array(), 'objects');
foreach ($post_types as $post_type) {
    if (in_array($post_type->name, array('immobile', 'listaimoveis'))) {
        echo "Post type: " . $post_type->name . "\n";
        echo "  - Slug: " . $post_type->rewrite['slug'] . "\n";
        echo "  - Has archive: " . ($post_type->has_archive ? 'Sim' : 'Não') . "\n";
        echo "  - Public: " . ($post_type->public ? 'Sim' : 'Não') . "\n";
        echo "  - Publicly queryable: " . ($post_type->publicly_queryable ? 'Sim' : 'Não') . "\n";
    }
}

// Testar a correspondência da URL
echo "\nTestando correspondência de URL para single-immobile:\n";
$test_url = home_url('/imovel/oportunidade-unica-na-ql-22-lago-sul/');
echo "URL de teste: $test_url\n";

// Verifica se a URL corresponde ao single-immobile
global $wp_rewrite;
$matched = $wp_rewrite->url_to_postid($test_url);
echo "ID do post correspondente: " . ($matched ? $matched : 'Nenhum') . "\n";

if ($matched) {
    $post = get_post($matched);
    echo "Tipo de post: " . $post->post_type . "\n";
}

// Escrever ou atualizar os arquivos de template
echo "\nAtualizando os templates importantes:\n";

// 1. Atualizar o arquivo single-immobile.php na raiz do tema
$root_single_immobile = get_stylesheet_directory() . '/single-immobile.php';
$single_immobile_content = <<<'PHP'
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
PHP;

file_put_contents($root_single_immobile, $single_immobile_content);
echo "Arquivo single-immobile.php criado/atualizado na raiz do tema.\n";

echo "\nProcesso concluído!\n"; 