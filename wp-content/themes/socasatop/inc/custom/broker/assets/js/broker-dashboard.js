(() => {
  document.addEventListener('DOMContentLoaded', () => {
    console.log('🔍 Broker Dashboard: DOMContentLoaded');
    
    // Verificar se as bibliotecas necessárias estão disponíveis
    const reactAvailable = typeof React !== 'undefined';
    const reactDomAvailable = typeof ReactDOM !== 'undefined';
    const chartJsAvailable = typeof Chart !== 'undefined';
    
    console.log('✅ Chart.js disponível:', chartJsAvailable);
    console.log('✅ React disponível:', reactAvailable);
    console.log('✅ ReactDOM disponível:', reactDomAvailable);
    
    // Se o Chart.js estiver disponível, mas não o React, apenas renderizar o gráfico
    if (chartJsAvailable && !reactAvailable) {
      renderChartWithoutReact();
      setupJQueryHandlers();
      return;
    }
    
    // Se as bibliotecas necessárias não estiverem disponíveis, usar a interface padrão
    if (!reactAvailable || !reactDomAvailable) {
      console.error('❌ React ou ReactDOM não estão disponíveis. Usando interface padrão.');
      setupJQueryHandlers();
      return;
    }
    
    // Se tudo estiver disponível, inicializar o componente React
    const { useState, useEffect } = React;
    
    // Função para carregar métricas sem React
    function renderChartWithoutReact() {
      console.log('🔍 Tentando renderizar o gráfico de métricas');
      
      // Verificar se o elemento canvas existe
      const chartCanvas = document.getElementById('broker-metrics-chart');
      if (!chartCanvas) {
        console.error('❌ Elemento do canvas não encontrado');
        return;
      }
      
      fetch(`${site.ajax_url}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_broker_metrics&nonce=${site.nonce}`
      })
      .then(response => response.json())
      .then(data => {
        console.log('✅ Dados das métricas recebidos:', data);
        if (data.success && data.data && data.data.metrics) {
          const metricsData = data.data.metrics;
          
          if (chartCanvas) {
            // Limpar qualquer gráfico existente
            Chart.getChart(chartCanvas)?.destroy();
            
            const ctx = chartCanvas.getContext('2d');
            new Chart(ctx, {
              type: 'line',
              data: {
                labels: metricsData.map(m => m.date),
                datasets: [
                  {
                    label: 'Exibições',
                    data: metricsData.map(m => m.views),
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    fill: false
                  },
                  {
                    label: 'Acessos',
                    data: metricsData.map(m => m.clicks),
                    borderColor: 'rgb(54, 162, 235)',
                    tension: 0.1,
                    fill: false
                  },
                  {
                    label: 'Conversões',
                    data: metricsData.map(m => m.conversions),
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1,
                    fill: false
                  }
                ]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                  y: {
                    beginAtZero: true
                  }
                }
              }
            });
            
            console.log('✅ Gráfico renderizado com sucesso');
          } else {
            console.error('❌ Elemento do canvas não encontrado');
          }
        } else {
          console.error('❌ Dados de métricas inválidos:', data);
        }
      })
      .catch(error => {
        console.error('❌ Erro ao carregar métricas:', error);
      });
    }
    
    // Configuração de handlers jQuery para a interface sem React
    function setupJQueryHandlers() {
      if (typeof jQuery === 'undefined') {
        console.error('jQuery não está disponível');
        return;
      }
      
      const $ = jQuery;
      
      // Manipulador para o checkbox "Selecionar todos"
      $('#select-all-properties').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.property-checkbox').prop('checked', isChecked);
        
        // Exibir ou ocultar os botões de ação em massa
        if (isChecked || $('.property-checkbox:checked').length > 0) {
          $('.bulk-actions').show();
        } else {
          $('.bulk-actions').hide();
        }
      });
      
      // Manipulador para os checkboxes individuais
      $(document).on('change', '.property-checkbox', function() {
        // Verificar se algum checkbox está selecionado
        const anyChecked = $('.property-checkbox:checked').length > 0;
        
        // Exibir ou ocultar os botões de ação em massa
        if (anyChecked) {
          $('.bulk-actions').show();
        } else {
          $('.bulk-actions').hide();
        }
        
        // Atualizar o estado do checkbox "Selecionar todos"
        const allChecked = $('.property-checkbox:checked').length === $('.property-checkbox').length;
        $('#select-all-properties').prop('checked', allChecked);
      });
      
      // Manipulador para o botão de exclusão em massa
      $('#bulk-delete-btn').on('click', function() {
        const selectedIds = [];
        $('.property-checkbox:checked').each(function() {
          selectedIds.push($(this).data('id'));
        });
        
        if (selectedIds.length > 0) {
          if (confirm(`Tem certeza que deseja excluir ${selectedIds.length} imóveis?`)) {
            bulkDeleteProperties(selectedIds);
          }
        }
      });
      
      // Manipulador para botão de exclusão individual
      $(document).on('click', '.delete-button', function() {
        const propertyId = $(this).data('id');
        
        if (!propertyId) return;
        
        if (confirm('Tem certeza que deseja excluir este imóvel?')) {
          deleteProperty(propertyId);
        }
      });
      
      // Manipulador para botão de pausar destaque
      $(document).on('click', '.pause-highlight-button', function() {
        const propertyId = $(this).data('id');
        
        if (!propertyId) return;
        
        if (confirm('Tem certeza que deseja pausar o destaque deste imóvel? Ele não aparecerá mais como destacado.')) {
          pauseHighlight(propertyId);
        }
      });
      
      // Função para excluir um imóvel
      function deleteProperty(propertyId) {
        $.ajax({
          url: site.ajax_url,
          type: 'POST',
          data: {
            action: 'delete_immobile',
            nonce: site.nonce,
            property_id: propertyId
          },
          success: function(response) {
            if (response.success) {
              $('.property-item[data-property-id="' + propertyId + '"]').fadeOut(300, function() {
                $(this).remove();
                
                // Verificar se não há mais imóveis
                if ($('.property-item').length === 0) {
                  $('.property-list').html(
                    '<div class="no-properties-message">' +
                    '<p>Você ainda não tem imóveis cadastrados.</p>' +
                    '<p><a href="/corretores/novo-imovel/" class="add-property-button">Adicionar seu primeiro imóvel</a></p>' +
                    '</div>'
                  );
                }
              });
            } else {
              alert(response.data);
            }
          },
          error: function() {
            alert('Erro ao processar a solicitação. Tente novamente.');
          }
        });
      }
      
      // Função para pausar destaque do imóvel
      function pauseHighlight(propertyId) {
        $.ajax({
          url: site.ajax_url,
          type: 'POST',
          data: {
            action: 'pause_immobile_highlight',
            nonce: site.nonce,
            property_id: propertyId
          },
          success: function(response) {
            if (response.success) {
              const $propertyItem = $('.property-item[data-property-id="' + propertyId + '"]');
              
              // Remover tag de destaque
              $propertyItem.find('.sponsored-tag').remove();
              
              // Substituir botão de pausar por botão de destacar
              const $actionButtons = $propertyItem.find('.property-actions');
              $actionButtons.find('.pause-highlight-button').remove();
              
              const highlightUrl = '/corretores/destacar-imovel/?immobile_id=' + propertyId;
              const highlightButton = '<a href="' + highlightUrl + '" class="action-button highlight-button" title="Reativar Destaque"><i class="fas fa-star"></i></a>';
              
              $actionButtons.find('.edit-button').after(highlightButton);
              
              alert('Destaque do imóvel pausado com sucesso!');
            } else {
              alert(response.data);
            }
          },
          error: function() {
            alert('Erro ao processar a solicitação. Tente novamente.');
          }
        });
      }
      
      // Função para excluir imóveis em massa
      function bulkDeleteProperties(propertyIds) {
        $.ajax({
          url: site.ajax_url,
          type: 'POST',
          data: {
            action: 'bulk_delete_immobiles',
            nonce: site.nonce,
            property_ids: propertyIds
          },
          success: function(response) {
            if (response.success) {
              // Remover imóveis da lista
              $.each(propertyIds, function(index, id) {
                $('.property-item[data-property-id="' + id + '"]').fadeOut(300, function() {
                  $(this).remove();
                });
              });
              
              // Resetar checkboxes
              $('#select-all-properties').prop('checked', false);
              
              // Verificar se não há mais imóveis
              setTimeout(function() {
                if ($('.property-item').length === 0) {
                  $('.property-list').html(
                    '<div class="no-properties-message">' +
                    '<p>Você ainda não tem imóveis cadastrados.</p>' +
                    '<p><a href="/corretores/novo-imovel/" class="add-property-button">Adicionar seu primeiro imóvel</a></p>' +
                    '</div>'
                  );
                }
              }, 300);
              
              alert('Imóveis excluídos com sucesso!');
            } else {
              alert(response.data);
            }
          },
          error: function() {
            alert('Erro ao processar a solicitação. Tente novamente.');
          }
        });
      }
    }
    
    const BrokerDashboard = () => {
      const [metrics, setMetrics] = useState([]);
      const [properties, setProperties] = useState([]);
      const [selectedProperties, setSelectedProperties] = useState([]);
      const [loading, setLoading] = useState(true);
      const [chartData, setChartData] = useState(null);
      const [showBulkActions, setShowBulkActions] = useState(false);

      const createChartData = (metricsData) => {
        return {
          labels: metricsData.map(m => m.date),
          datasets: [
            {
              label: 'Exibições',
              data: metricsData.map(m => m.views),
              borderColor: 'rgb(75, 192, 192)',
              tension: 0.1
            },
            {
              label: 'Acessos',
              data: metricsData.map(m => m.clicks),
              borderColor: 'rgb(54, 162, 235)',
              tension: 0.1
            },
            {
              label: 'Conversões',
              data: metricsData.map(m => m.conversions),
              borderColor: 'rgb(255, 99, 132)',
              tension: 0.1
            }
          ]
        };
      };

      const fetchMetrics = async () => {
        try {
          const response = await fetch(`${site.ajax_url}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_broker_metrics&nonce=${site.nonce}`
          });
          const data = await response.json();
          if (data.success) {
            setMetrics(data.data.metrics);
            setChartData(createChartData(data.data.metrics));
          }
        } catch (error) {
          console.error('Erro ao carregar métricas:', error);
        }
      };

      const fetchProperties = async () => {
        try {
          const response = await fetch(`${site.ajax_url}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_broker_properties&nonce=${site.nonce}`
          });
          const data = await response.json();
          if (data.success) {
            setProperties(data.data.properties);
          }
          setLoading(false);
        } catch (error) {
          console.error('Erro ao carregar imóveis:', error);
          setLoading(false);
        }
      };

      useEffect(() => {
        fetchMetrics();
        fetchProperties();
      }, []);

      useEffect(() => {
        if (chartData) {
          const timer = setTimeout(() => {
            const chartCanvas = document.getElementById('broker-metrics-chart');
            
            if (chartCanvas) {
              const ctx = chartCanvas.getContext('2d');
              new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  scales: {
                    y: {
                      beginAtZero: true
                    }
                  }
                }
              });
            } else {
              console.error('Elemento do canvas não encontrado');
            }
          }, 100);

          return () => clearTimeout(timer);
        }
      }, [chartData]);

      // Atualizar o estado de ações em massa quando a seleção de propriedades mudar
      useEffect(() => {
        setShowBulkActions(selectedProperties.length > 0);
      }, [selectedProperties]);

      const handlePropertySelect = (propertyId) => {
        setSelectedProperties(prev => 
          prev.includes(propertyId) 
            ? prev.filter(id => id !== propertyId)
            : [...prev, propertyId]
        );
      };

      const handleCheckout = () => {
        if (selectedProperties.length === 0) {
          alert('Selecione pelo menos um imóvel para destacar');
          return;
        }
        window.location.href = `/checkout?properties=${selectedProperties.join(',')}`;
      };

      // Função para lidar com a pausa/ativação de um imóvel
      const handleToggleStatus = async (propertyId, currentStatus) => {
        const action = currentStatus === 'draft' ? 'activate' : 'pause';
        
        try {
          const response = await fetch(`${site.ajax_url}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=toggle_immobile_status&immobile_id=${propertyId}&status_action=${action}&nonce=${site.nonce}`
          });
          
          const data = await response.json();
          
          if (data.success) {
            // Atualizar estado local
            setProperties(prevProperties => 
              prevProperties.map(property => 
                property.id === propertyId 
                  ? { ...property, status: data.data.status } 
                  : property
              )
            );
            
            alert(data.data.message);
          } else {
            alert('Erro ao alterar o status do imóvel.');
          }
        } catch (error) {
          console.error('Erro ao alterar status:', error);
          alert('Erro ao alterar o status do imóvel.');
        }
      };

      // Função para pausar o destaque (assinatura) de um imóvel
      const handlePauseHighlight = async (propertyId) => {
        if (!confirm('Tem certeza que deseja pausar o destaque deste imóvel? Sua assinatura no Mercado Pago será cancelada.')) {
          return;
        }
        
        try {
          const response = await fetch(`${site.ajax_url}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=pause_immobile_highlight&immobile_id=${propertyId}&nonce=${site.nonce}`
          });
          
          const data = await response.json();
          
          if (data.success) {
            // Atualizar estado local
            setProperties(prevProperties => 
              prevProperties.map(property => 
                property.id === propertyId 
                  ? { ...property, sponsored: false } 
                  : property
              )
            );
            
            alert(data.data.message);
          } else {
            alert('Erro ao pausar o destaque do imóvel.');
          }
        } catch (error) {
          console.error('Erro ao pausar destaque:', error);
          alert('Erro ao pausar o destaque do imóvel.');
        }
      };

      // Função para lidar com a ação de destacar um imóvel
      const handleHighlightProperty = (propertyId) => {
        setSelectedProperties([propertyId]);
        window.location.href = `/checkout?properties=${propertyId}`;
      };

      // Função para lidar com a exclusão de um imóvel
      const handleDeleteProperty = async (propertyId) => {
        if (!confirm('Tem certeza que deseja excluir este imóvel? Esta ação não pode ser desfeita.')) {
          return;
        }
        
        try {
          const response = await fetch(`${site.ajax_url}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_immobile&immobile_id=${propertyId}&nonce=${site.nonce}`
          });
          
          const data = await response.json();
          
          if (data.success) {
            // Remover o imóvel da lista
            setProperties(prevProperties => 
              prevProperties.filter(property => property.id !== propertyId)
            );
            
            // Se o imóvel estava selecionado, remover da seleção
            if (selectedProperties.includes(propertyId)) {
              setSelectedProperties(prev => prev.filter(id => id !== propertyId));
            }
            
            alert(data.data.message);
          } else {
            alert('Erro ao excluir o imóvel.');
          }
        } catch (error) {
          console.error('Erro ao excluir imóvel:', error);
          alert('Erro ao excluir o imóvel.');
        }
      };

      // Função para excluir múltiplos imóveis
      const handleBulkDelete = async () => {
        if (selectedProperties.length === 0) {
          alert('Selecione pelo menos um imóvel para excluir');
          return;
        }
        
        if (!confirm(`Tem certeza que deseja excluir ${selectedProperties.length} imóvel(is)? Esta ação não pode ser desfeita.`)) {
          return;
        }
        
        try {
          const response = await fetch(`${site.ajax_url}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=bulk_delete_immobiles&immobile_ids=${selectedProperties.join(',')}&nonce=${site.nonce}`
          });
          
          const data = await response.json();
          
          if (data.success) {
            // Remover os imóveis da lista
            setProperties(prevProperties => 
              prevProperties.filter(property => !selectedProperties.includes(property.id))
            );
            
            // Limpar a seleção
            setSelectedProperties([]);
            
            alert(data.data.message);
          } else {
            alert('Erro ao excluir os imóveis selecionados.');
          }
        } catch (error) {
          console.error('Erro ao excluir imóveis:', error);
          alert('Erro ao excluir os imóveis selecionados.');
        }
      };

      // Formatar o slug do título para usar na URL
      const formatTitleSlug = (title) => {
        return title
          .toLowerCase()
          .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // Remove acentos
          .replace(/[^\w\s-]/g, '') // Remove caracteres especiais
          .replace(/\s+/g, '-') // Substitui espaços por hífens
          .replace(/--+/g, '-'); // Remove hífens duplicados
      };

      if (loading) {
        return React.createElement('div', { className: 'text-center p-8' }, 'Carregando...');
      }

      return React.createElement('div', { className: 'w-full max-w-6xl mx-auto p-4' }, []);
    };
    
    // Tentar inicializar o componente React se o contêiner existir
    const reactContainer = document.getElementById('react-broker-dashboard');
    if (reactContainer && reactAvailable && reactDomAvailable) {
      ReactDOM.render(React.createElement(BrokerDashboard), reactContainer);
    } else {
      // Se não for possível inicializar o React, usar a interface padrão com jQuery
      if (chartJsAvailable) {
        renderChartWithoutReact();
      }
      setupJQueryHandlers();
    }
  });
})();