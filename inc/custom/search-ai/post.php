<?php
if (!defined('ABSPATH')) {
    exit;
}

class Search_AI {
    private $location_variations = [
    'asa norte' => ['asa norte', 'shn', 'setor habitacional norte'],
    'asa sul' => ['asa sul', 'shs', 'setor habitacional sul'],
    'noroeste' => ['noroeste', 'nw', 'setor noroeste'],
    'sudoeste' => ['sudoeste', 'sw', 'setor sudoeste'],
    'octogonal' => ['octogonal', 'ahn', 'área octogonal'],
    'cruzeiro' => ['cruzeiro', 'cruzeiro novo', 'cruzeiro velho'],
    'lago norte' => ['lago norte', 'ln', 'setor de habitações individuais norte'],
    'lago sul' => ['lago sul', 'ls', 'setor de habitações individuais sul'],
    'vicente pires' => ['vicente pires', 'vp', 'setor habitacional vicente pires'],
    'águas claras' => ['aguas claras', 'águas claras', 'ac'],
    'taguatinga' => ['taguatinga', 'tag', 'taguatinga norte', 'taguatinga sul'],
    'guará' => ['guará', 'guara', 'guará i', 'guará ii', 'guara i', 'guara ii'],
    'ceilândia' => ['ceilandia', 'ceilândia', 'cei', 'ceilândia norte', 'ceilândia sul'],
    'samambaia' => ['samambaia', 'samambaia norte', 'samambaia sul'],
    'recanto das emas' => ['recanto das emas', 'recanto'],
    'riacho fundo' => ['riacho fundo', 'riacho fundo i'],
    'riacho fundo ii' => ['riacho fundo ii', 'riacho fundo 2'],
    'núcleo bandeirante' => ['núcleo bandeirante', 'nb', 'bandeirante'],
    'candangolândia' => ['candangolândia', 'candangolandia'],
    'park way' => ['park way', 'pw', 'setor de mansões park way', 'parkway'],
    'brasília' => ['brasília', 'plano piloto', 'df', 'capital'],
    'paranoá' => ['paranoá', 'paranoa'],
    'itapoã' => ['itapoã', 'itapoa'],
    'varjão' => ['varjão', 'varjao'],
    'sobradinho' => ['sobradinho', 'sobradinho i'],
    'sobradinho ii' => ['sobradinho ii', 'sobradinho 2'],
    'planaltina' => ['planaltina', 'planaltina df'],
    'santa maria' => ['santa maria', 'sm'],
    'gama' => ['gama', 'setor sul gama', 'setor norte gama', 'setor central gama'],
    'brazlândia' => ['brazlândia', 'brazlandia'],
    'estrutural' => ['estrutural', 'cidade estrutural'],
    'jardim botânico' => ['jardim botânico', 'jb'],
    'são sebastião' => ['são sebastião', 'sao sebastiao'],
    'fercal' => ['fercal'],
    'sol nascente' => ['sol nascente', 'sol nascente trecho 1', 'sol nascente trecho 2', 'sol nascente trecho 3'],
    'pôr do sol' => ['pôr do sol', 'por do sol']
    ];
    
    private $property_variations = [
        'apartamento' => ['apartamento', 'apt', 'apto', 'apartamentos', 'flat'],
        'sobrado' => ['sobrado'],
        'terreo' => ['casa', 'cada', 'casa térrea', 'casa terrea', 'térreo', 'terreo', 'caza'],
        'terreno' => ['terreno', 'lote', 'terra']
    ];

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'register_scripts']);
        add_shortcode('smart_search', [$this, 'render_smart_search']);
        add_shortcode('property_details', [$this, 'render_property_details']);
    }

    public function register_scripts() {
        if (!is_admin()) {
            wp_register_script(
                'smart-search-script',
                get_stylesheet_directory_uri() . '/inc/custom/search-ai/assets/js/smart-search.js',
                ['wp-element'],
                '1.0.0',
                true
            );
    
            wp_localize_script('smart-search-script', 'smartSearchData', [
                'restUrl' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest')
            ]);
        }
    }

    public function render_smart_search($atts = [], $content = null) {
        if (!is_admin()) {
            wp_enqueue_script('smart-search-script');
        }
        return '<div id="smart-search-root" class="smart-search-container"></div>';
    }

    private function normalize_text($text) {
        $text = mb_strtolower($text);
        $text = str_replace(
            ['três', 'tres', '3'],
            ['3', '3', '3'],
            $text
        );
        return $text;
    }

    private function extract_search_keywords($search_text) {
        $normalized_text = $this->normalize_text($search_text);
        
        $keywords = [
            'tipo' => null,
            'quartos' => null,
            'bairro' => null,
            'valor_max' => null,
            'size' => null,
            'termos_relevantes' => []
        ];
        
        foreach ($this->property_variations as $tipo => $variantes) {
            foreach ($variantes as $variante) {
                if (mb_strpos($normalized_text, $variante) !== false) {
                    $keywords['tipo'] = $tipo;
                    break 2;
                }
            }
        }
        
        foreach ($this->location_variations as $location => $variations) {
            foreach ($variations as $variation) {
                if (mb_strpos($normalized_text, $variation) !== false) {
                    $keywords['bairro'] = $location;
                    break 2;
                }
            }
        }
        
        if (preg_match('/(\d+)\s*(?:quartos?|dormitórios?|dorms?|suites?|suítes?)/i', $normalized_text, $matches)) {
            $keywords['quartos'] = $matches[1];
        }
        
        $caracteristicas = [
            'novo', 'reformado', 'mobiliado', 'garagem', 'piscina', 'churrasqueira', 
            'varanda', 'vista', 'sauna', 'jardim', 'quintal', 'academia', 'salão',
            'playground', 'segurança', 'portaria', 'lazer'
        ];
        
        foreach ($caracteristicas as $carac) {
            if (mb_strpos($normalized_text, $carac) !== false) {
                $keywords['termos_relevantes'][] = $carac;
            }
        }
        
        return $keywords;
    }

    public function search_properties($search_text) {
      try {
          error_log("=== INÍCIO DEBUG SEARCH PROPERTIES ===");
          error_log("Search Text: " . $search_text);
          
          $keywords = $this->extract_search_keywords($search_text);
          error_log("Keywords extraídos: " . print_r($keywords, true));
          
          global $wpdb;
          
          // Debug da tabela sponsored_listings
          $check_table = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}sponsored_listings'");
          error_log("Tabela sponsored_listings existe? " . print_r($check_table, true));
          
          // Verifica se existem patrocinados ativos
          $sponsored_check = $wpdb->get_results("
              SELECT COUNT(*) as total 
              FROM {$wpdb->prefix}sponsored_listings 
              WHERE status = 'active' 
              AND end_date >= CURDATE()
          ");
          error_log("Total de patrocinados ativos: " . print_r($sponsored_check, true));
          
          // Query principal
          $query = "
              SELECT DISTINCT 
                  p.*,
                  type.meta_value as property_type,
                  location.meta_value as location,
                  bedrooms.meta_value as bedrooms,
                  size.meta_value as size,
                  amount.meta_value as amount,
                  pm_gallery.meta_value as gallery_images,
                  CASE 
                      WHEN sl.id IS NOT NULL AND sl.status = 'active' AND sl.end_date >= CURDATE() THEN 1 
                      ELSE 0 
                  END as is_sponsored
              FROM {$wpdb->posts} p
              LEFT JOIN {$wpdb->postmeta} type ON p.ID = type.post_id AND type.meta_key = 'property_type'
              LEFT JOIN {$wpdb->postmeta} location ON p.ID = location.post_id AND location.meta_key = 'location'
              LEFT JOIN {$wpdb->postmeta} bedrooms ON p.ID = bedrooms.post_id AND bedrooms.meta_key = 'bedrooms'
              LEFT JOIN {$wpdb->postmeta} size ON p.ID = size.post_id AND size.meta_key = 'size'
              LEFT JOIN {$wpdb->postmeta} amount ON p.ID = amount.post_id AND amount.meta_key = 'amount'
              LEFT JOIN {$wpdb->postmeta} pm_gallery ON p.ID = pm_gallery.post_id AND pm_gallery.meta_key = 'immobile_gallery'
              LEFT JOIN {$wpdb->prefix}sponsored_listings sl ON p.ID = sl.property_id
              WHERE p.post_type = 'immobile' 
              AND p.post_status = 'publish'
              ORDER BY 
                  CASE 
                      WHEN sl.id IS NOT NULL AND sl.status = 'active' AND sl.end_date >= CURDATE() THEN 0
                      ELSE 1
                  END, 
                  sl.start_date DESC,
                  p.post_date DESC
              LIMIT 100";
  
          error_log("Query executada: " . $query);
          
          $results = $wpdb->get_results($query);
          error_log("Total de resultados: " . count($results));
          
          if (empty($results)) {
              error_log("Nenhum resultado encontrado");
              return [];
          }
  
          // Debug dos primeiros 5 resultados
          $first_five = array_slice($results, 0, 5);
          error_log("Primeiros 5 resultados: " . print_r($first_five, true));
          
          $sponsored_results = [];
          $regular_results = [];
  
          foreach ($results as $property) {
              $score = $this->calculate_property_score($property, $keywords);
              
              if ($score >= 20) {
                  // Debug propriedade e score
                  error_log("Property ID {$property->ID} - Score: {$score} - Is Sponsored: {$property->is_sponsored}");
                  
                  $gallery_ids = explode(',', $property->gallery_images);
                  $first_image_id = !empty($gallery_ids[0]) ? $gallery_ids[0] : 0;
                  
                  $property_data = [
                      'id' => $property->ID,
                      'title' => $property->post_title,
                      'permalink' => get_permalink($property->ID),
                      'thumbnail' => $first_image_id ? wp_get_attachment_url($first_image_id) : '',
                      'location' => $property->location,
                      'property_type' => $property->property_type,
                      'bedrooms' => $property->bedrooms,
                      'size' => $property->size,
                      'amount' => $property->amount,
                      'is_sponsored' => $property->is_sponsored == 1,
                      'relevance' => $score
                  ];
  
                  if ($property->is_sponsored == 1) {
                      $sponsored_results[] = $property_data;
                  } else {
                      $regular_results[] = ['score' => $score, 'data' => $property_data];
                  }
              }
          }
  
          error_log("Total sponsored_results antes do slice: " . count($sponsored_results));
          
          // Limitar a 3 patrocinados no topo
          $sponsored_results = array_slice($sponsored_results, 0, 3);
          
          error_log("Total sponsored_results após slice: " . count($sponsored_results));
  
          // Ordenar resultados regulares por relevância
          usort($regular_results, function($a, $b) {
              return $b['score'] - $a['score'];
          });
  
          $regular_results = array_map(function($item) {
              return $item['data'];
          }, $regular_results);
  
          // Combinar resultados mantendo patrocinados no topo
          $final_results = array_merge($sponsored_results, $regular_results);
          
          error_log("Total final_results antes do slice: " . count($final_results));
          
          // Limitar total de resultados
          $final_results = array_slice($final_results, 0, 30);
          
          error_log("Total final_results após slice: " . count($final_results));
          error_log("=== FIM DEBUG SEARCH PROPERTIES ===");
  
          return $final_results;
  
      } catch (Exception $e) {
          error_log('Erro na busca: ' . $e->getMessage());
          throw $e;
      }
  }

    private function calculate_property_score($property, $keywords) {
        $score = 0;
        $max_score = 100;
        
        if ($keywords['bairro'] && stripos($property->location, $keywords['bairro']) !== false) {
            $score += 35;
        }
        
        if ($keywords['tipo'] && stripos($property->property_type, $keywords['tipo']) !== false) {
            $score += 25;
        }
        
        if ($keywords['quartos'] && $property->bedrooms) {
            $diff = abs((int)$property->bedrooms - (int)$keywords['quartos']);
            if ($diff === 0) {
                $score += 20;
            } elseif ($diff === 1) {
                $score += 15;
            } elseif ($diff === 2) {
                $score += 10;
            }
        }
        
        if ($keywords['size'] && $property->size) {
            $size_diff_percentage = abs(((int)$property->size - (int)$keywords['size']) / (int)$keywords['size']);
            if ($size_diff_percentage <= 0.1) {
                $score += 10;
            } elseif ($size_diff_percentage <= 0.2) {
                $score += 5;
            }
        }
        
        foreach ($keywords['termos_relevantes'] as $termo) {
            if (stripos($property->post_title . ' ' . $property->post_content, $termo) !== false) {
                $score += 2;
            }
        }
        
        return min($score, $max_score);
    }

    public function render_property_details($atts = [], $content = null) {
        $post_id = get_the_ID();
        
        if (!$post_id || get_post_type($post_id) !== 'immobile') {
            return '<p>Imóvel não encontrado.</p>';
        }

        $property = [
            'title' => get_the_title($post_id),
            'location' => get_post_meta($post_id, 'location', true),
            'property_type' => get_post_meta($post_id, 'property_type', true),
            'bedrooms' => get_post_meta($post_id, 'bedrooms', true),
            'size' => get_post_meta($post_id, 'size', true),
            'amount' => get_post_meta($post_id, 'amount', true),
            'condominium' => get_post_meta($post_id, 'condominium', true),
            'financing' => get_post_meta($post_id, 'financing', true),
            'details' => get_post_meta($post_id, 'details', true),
            'facade' => get_post_meta($post_id, 'facade', true),
            'gallery' => get_post_meta($post_id, 'immobile_gallery', true)
        ];

        ob_start();
        ?>
        <div class="property-details-container">
            <h1 class="property-title"><?php echo esc_html($property['title']); ?></h1>
            
            <?php if ($property['gallery']): ?>
            <div class="property-gallery">
                <?php echo do_shortcode('[gallery ids="' . $property['gallery'] . '"]'); ?>
            </div>
            <?php endif; ?>

            <div class="property-info">
                <div class="main-details">
                    <h2>Detalhes do Imóvel</h2>
                    <ul>
                        <li><strong>Localização:</strong> <?php echo esc_html($property['location']); ?></li>
                        <li><strong>Tipo:</strong> <?php echo esc_html($property['property_type']); ?></li>
                        <li><strong>Quartos:</strong> <?php echo esc_html($property['bedrooms']); ?></li>
                        <li><strong>Área:</strong> <?php echo esc_html($property['size']); ?>m²</li>
                        <li><strong>Valor:</strong> R$ <?php echo number_format($property['amount'], 2, ',', '.'); ?></li>
                    </ul>
                </div>

                <div class="additional-info">
                    <h2>Informações Adicionais</h2>
                    <ul>
                        <li><strong>Condomínio:</strong> <?php echo $property['condominium'] === 'Sim' ? 'Sim' : 'Não'; ?></li>
                        <li><strong>Financiamento:</strong> <?php echo $property['financing'] === 'Sim' ? 'Aceita' : 'Não aceita'; ?></li>
                    </ul>
                </div>

                <?php if ($property['details']): ?>
                <div class="property-description">
                    <h2>Descrição</h2>
                    <div class="description-content">
                        <?php echo wp_kses_post($property['details']); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <style>
            .property-details-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }

            .property-title {
                font-size: 2em;
                margin-bottom: 20px;
            }

            .property-gallery {
                margin-bottom: 30px;
            }

            .property-info {
                display: grid;
                grid-template-columns: 1fr;
                gap: 30px;
            }

            @media (min-width: 768px) {
                .property-info {
                    grid-template-columns: 2fr 1fr;
                }
            }

            .property-info h2 {
                font-size: 1.5em;
                margin-bottom: 15px;
            }

            .property-info ul {
                list-style: none;
                padding: 0;
            }

            .property-info li {
                margin-bottom: 10px;
            }

            .property-description {
              grid-column: 1 / -1;
            }
        </style>
        <?php
        return ob_get_clean();
    }
}

new Search_AI();