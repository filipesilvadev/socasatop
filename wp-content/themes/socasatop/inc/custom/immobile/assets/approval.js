jQuery(document).ready(function($) {
    // Mostrar o modal de rejeição quando o botão for clicado
    $('.reject-button').on('click', function() {
        const immobileId = $(this).data('id');
        $('#immobile_id').val(immobileId);
        $('#reject-modal').css('display', 'block');
    });

    // Fechar o modal quando o X for clicado
    $('.close-modal').on('click', function() {
        $('#reject-modal').css('display', 'none');
    });

    // Fechar o modal quando o botão cancelar for clicado
    $('.cancel-button').on('click', function() {
        $('#reject-modal').css('display', 'none');
    });

    // Fechar o modal quando clicar fora dele
    $(window).on('click', function(event) {
        if (event.target == document.getElementById('reject-modal')) {
            $('#reject-modal').css('display', 'none');
        }
    });

    // Botão de aprovação
    $('.approve-button').on('click', function() {
        const immobileId = $(this).data('id');
        
        if (confirm('Confirma a aprovação deste imóvel? Ele será publicado no site.')) {
            approveImmobile(immobileId);
        }
    });

    // Função para aprovar imóvel
    function approveImmobile(immobileId) {
        $.ajax({
            url: approval_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'approve_immobile',
                immobile_id: immobileId,
                security: approval_vars.nonce
            },
            beforeSend: function() {
                // Adicionar indicador de carregamento
                $('body').append('<div class="loading-overlay"><div class="spinner"></div></div>');
            },
            success: function(response) {
                $('.loading-overlay').remove();
                
                if (response.success) {
                    alert('Imóvel aprovado com sucesso!');
                    // Recarregar a página para atualizar a lista
                    location.reload();
                } else {
                    alert('Erro ao aprovar imóvel: ' + response.data);
                }
            },
            error: function() {
                $('.loading-overlay').remove();
                alert('Ocorreu um erro na comunicação com o servidor.');
            }
        });
    }

    // Envio do formulário de rejeição
    $('#rejection-form').on('submit', function(e) {
        e.preventDefault();
        
        const immobileId = $('#immobile_id').val();
        const rejectionReason = $('#rejection_reason').val();
        
        if (!rejectionReason.trim()) {
            alert('Por favor, informe o motivo da reprovação.');
            return false;
        }
        
        rejectImmobile(immobileId, rejectionReason);
    });

    // Função para rejeitar imóvel
    function rejectImmobile(immobileId, rejectionReason) {
        $.ajax({
            url: approval_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'reject_immobile',
                immobile_id: immobileId,
                rejection_reason: rejectionReason,
                security: approval_vars.nonce
            },
            beforeSend: function() {
                // Adicionar indicador de carregamento
                $('body').append('<div class="loading-overlay"><div class="spinner"></div></div>');
            },
            success: function(response) {
                $('.loading-overlay').remove();
                $('#reject-modal').css('display', 'none');
                
                if (response.success) {
                    alert('Imóvel reprovado com sucesso!');
                    // Recarregar a página para atualizar a lista
                    location.reload();
                } else {
                    alert('Erro ao reprovar imóvel: ' + response.data);
                }
            },
            error: function() {
                $('.loading-overlay').remove();
                alert('Ocorreu um erro na comunicação com o servidor.');
            }
        });
    }

    // Estilo para o loading overlay
    $('<style>')
        .text(`
            .loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
            }
            .spinner {
                width: 50px;
                height: 50px;
                border: 5px solid #f3f3f3;
                border-top: 5px solid #4CAF50;
                border-radius: 50%;
                animation: spin 2s linear infinite;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `)
        .appendTo('head');

    // Limpar campos do formulário quando o modal for fechado
    $('.close-modal, .cancel-button').on('click', function() {
        $('#rejection_reason').val('');
    });

    // Funções de filtragem
    $('#reset-filters').on('click', function(e) {
        e.preventDefault();
        $('#broker_filter').val('');
        $('#date_start').val('');
        $('#date_end').val('');
        $('#approval-filter-form').submit();
    });
}); 