<?php if (!defined('ABSPATH')) exit; ?>

<div class="sct-container">
    <header class="sct-header">
        <h1>REVOLUCIONAMOS A MANEIRA DE BUSCAR IMÓVEIS EM BRASÍLIA</h1>
        <h2>FAÇA PARTE DA REVOLUÇÃO DO MERCADO IMOBILIÁRIO!</h2>
        <nav>
            <a href="<?php echo home_url('/'); ?>" class="sct-nav-link">SÓ CASA TOP</a>
            <a href="<?php echo home_url('/blog'); ?>" class="sct-nav-link">BLOG</a>
            <a href="<?php echo home_url('/ajuda'); ?>" class="sct-nav-link">AJUDA</a>
        </nav>
    </header>

    <section class="sct-hero">
        <h2>Use o modelo de IA do Só Casa Top para atrair leads muito mais qualificados</h2>
        <a href="#cadastro" class="sct-btn sct-btn-primary">TESTE GRATUITAMENTE POR 1 MÊS</a>
    </section>

    <div class="sct-stats-grid">
        <div class="sct-stat-card">
            <h3>+ 1200 leads</h3>
            <p>gerados entre julho e dezembro de 2024 em nosso sistema de busca tradicional.</p>
        </div>
        <div class="sct-stat-card">
            <h3>Métricas por rede:</h3>
            <ul>
                <li>Instagram: + 50 mil seguidores</li>
                <li>Visualizações: + 700 mil/30 dias</li>
                <li>Youtube: 3,4 mil</li>
                <li>Tiktok: 2,8 mil</li>
            </ul>
        </div>
    </div>

    <section class="sct-features">
        <h2>Vantagens Exclusivas</h2>
        <div class="sct-features-grid">
            <div class="sct-feature-card">
                <h3>Exposição Inteligente</h3>
                <p>Seu portfólio apresentado de forma estratégica e direcionada em um só lugar.</p>
            </div>
            <div class="sct-feature-card">
                <h3>Economia de Tempo</h3>
                <p>Chega de leads desqualificados – nossa IA garante conexões certeiras.</p>
            </div>
            <div class="sct-feature-card">
                <h3>Parceria Inovadora</h3>
                <p>Faça parte de um ecossistema imobiliário que valoriza o seu trabalho e otimiza os resultados.</p>
            </div>
        </div>
    </section>

    <section class="sct-how-it-works">
        <h2>Como Funciona?</h2>
        <div class="sct-steps">
            <div class="sct-step">
                <span class="sct-step-number">1</span>
                <h3>Cadastre seus imóveis</h3>
                <p>Basta adicionar suas propriedades ao nosso sistema, com descrição completa e detalhada. Quanto mais informação melhor.</p>
            </div>
            <div class="sct-step">
                <span class="sct-step-number">2</span>
                <h3>Conexão com clientes ideais</h3>
                <p>Nossa IA interpreta os desejos dos clientes, seja por voz ou texto, e exibe suas propriedades como as opções ideais.</p>
            </div>
            <div class="sct-step">
                <span class="sct-step-number">3</span>
                <h3>Resultados na palma da mão</h3>
                <p>Mais leads qualificados e maior taxa de conversão para suas vendas.</p>
            </div>
        </div>
    </section>

    <section class="sct-cta" id="cadastro">
        <h2>Transforme seu jeito de vender imóveis</h2>
        <?php 
        $cadastro_url = home_url('/cadastro');
        echo "<a href='{$cadastro_url}' class='sct-btn sct-btn-primary'>RECEBA 30 DIAS DE TESTE GRATUITO</a>";
        ?>
    </section>
</div>