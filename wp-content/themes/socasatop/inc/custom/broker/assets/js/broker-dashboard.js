(function($) {
    $(document).ready(function() {
        // Variável para controlar exibição dos botões de ações em massa
        let showBulkActions = false;
        
        // Função para formatar o slug do título para URL
        function formatTitleSlug(title) {
            return title.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // Remove acentos
                .replace(/[^\w\s-]/g, '') // Remove caracteres especiais
                .replace(/\s+/g, '-') // Substitui espaços por hífens
                .replace(/--+/g, '-'); // Remove hífens duplicados
        }
        
        // Verificar se alguma propriedade está selecionada
        function checkSelectedProperties() {
            const hasSelected = $('.property-checkbox:checked').length > 0;
            
            if (hasSelected && !showBulkActions) {
                showBulkActions = true;
                $('.bulk-actions').show();
            } else if (!hasSelected && showBulkActions) {
                showBulkActions = false;
                $('.bulk-actions').hide();
            }
        }
        
        // Atualizar links dos títulos para usar o slug correto
        $('.property-title a').each(function() {
            const title = $(this).text().trim();
            const slug = formatTitleSlug(title);
            const url = '/imovel/' + slug + '/';
            $(this).attr('href', url);
        });
        
        // Selecionar/Deselecionar todos os imóveis
        $('#select-all-properties').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.property-checkbox').prop('checked', isChecked);
            checkSelectedProperties();
        });
        
        // Checkboxes individuais
        $('.property-checkbox').on('change', function() {
            checkSelectedProperties();
            
            // Verificar se todos estão selecionados para marcar o "selecionar todos"
            const totalCheckboxes = $('.property-checkbox').length;
            const totalChecked = $('.property-checkbox:checked').length;
            
            $('#select-all-properties').prop('checked', totalCheckboxes === totalChecked);
        });
        
        // Excluir imóvel
        $('.delete-button').on('click', function() {
            const propertyId = $(this).data('id');
            
            if (!propertyId) return;
            
            if (confirm('Tem certeza que deseja excluir este imóvel?')) {
                deleteProperty(propertyId);
            }
        });
        
        // Pausar destaque do imóvel
        $('.pause-highlight-button').on('click', function() {
            const propertyId = $(this).data('id');
            
            if (!propertyId) return;
            
            if (confirm('Tem certeza que deseja pausar o destaque deste imóvel? Ele não aparecerá mais como destacado.')) {
                pauseHighlight(propertyId);
            }
        });
        
        // Excluir imóveis em massa
        $('#bulk-delete-btn').on('click', function() {
            const selectedIds = [];
            
            $('.property-checkbox:checked').each(function() {
                const propertyId = $(this).data('id');
                if (propertyId) {
                    selectedIds.push(propertyId);
                }
            });
            
            if (selectedIds.length === 0) {
                alert('Selecione pelo menos um imóvel para excluir.');
                return;
            }
            
            if (confirm('Tem certeza que deseja excluir ' + selectedIds.length + ' imóveis selecionados?')) {
                bulkDeleteProperties(selectedIds);
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
                        // Inicializar as variáveis
                        var propertyItem = $('.property-item[data-property-id="' + propertyId + '"]');
                        
                        // Remover tag de destaque
                        propertyItem.find('.sponsored-tag').remove();
                        
                        // Substituir botão de pausar por botão de destacar
                        var actionButtons = propertyItem.find('.property-actions');
                        actionButtons.find('.pause-highlight-button').remove();
                        
                        const highlightUrl = '/corretores/destacar-imovel/?immobile_id=' + propertyId;
                        const highlightButton = '<a href="' + highlightUrl + '" class="action-button highlight-button" title="Reativar Destaque"><i class="fas fa-star"></i></a>';
                        
                        actionButtons.find('.edit-button').after(highlightButton);
                        
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
                        checkSelectedProperties();
                        
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
    });
})(jQuery);