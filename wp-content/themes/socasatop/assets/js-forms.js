jQuery(document).ready(function($) {
    // Verificar parâmetros da URL para exibir os formulários
    function verificarParametrosURL() {
        const hash = window.location.hash;
        
        if(hash === '#otimizar') {
            abrirFormularioPublicacao();
        } else if(hash === '#assessoria') {
            abrirFormularioAssessoria();
        }
    }
    
    // Monitorar mudanças no hash da URL
    $(window).on('hashchange', function() {
        verificarParametrosURL();
    });
    
    // Verificar no carregamento inicial
    verificarParametrosURL();
    
    // Criar e abrir o modal do formulário de Publicação Otimizada
    function abrirFormularioPublicacao() {
        // Criar o HTML do formulário
        const formularioHTML = `
            <div class="form-popup-overlay" id="publicacao-form-overlay">
                <div class="form-popup-container">
                    <div class="form-popup-content">
                        <h2>Publicação Otimizada</h2>
                        <div class="form-divider"></div>
                        <p>Adicione aqui todos os links dos imóveis que deseja publicar:</p>
                        <textarea id="links-imoveis" placeholder="Exemplo:&#10;https://www.dlimoveis.com.br/imovel/...&#10;https://www.wimoveis.com.br/propriedades/..."></textarea>
                        <button type="button" id="enviar-publicacao" class="btn-form-popup">Contratar assessoria</button>
                        <div id="mensagem-sucesso" class="mensagem-sucesso" style="display:none;">
                            <p>Obrigado! Recebemos sua solicitação. Confira seu WhatsApp para darmos continuidade ao processo.</p>
                            <button type="button" id="fechar-sucesso" class="btn-form-popup">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Adicionar formulário ao body
        $('body').append(formularioHTML);
        
        // Manipular envio do formulário
        $('#enviar-publicacao').on('click', function() {
            const linksImoveis = $('#links-imoveis').val();
            
            // Obter dados do usuário com fallback para valores fixos
            let nomeCorretor = "Teste Corretor"; 
            let emailCorretor = "teste@corretor.com";
            let telefoneCorretor = "11999999999";
            
            // Tentar obter dados do usuário real
            try {
                if (typeof site !== 'undefined') {
                    // Nome: tentar primeiro display_name, depois firstname, depois manter o padrão
                    if (site.user_display_name) {
                        nomeCorretor = site.user_display_name;
                    } else if (site.user_firstname) {
                        nomeCorretor = site.user_firstname;
                    }
                    
                    // Email
                    if (site.user_email) {
                        emailCorretor = site.user_email;
                    }
                    
                    // Telefone: tentar primeiro phone, depois whatsapp
                    if (site.user_phone) {
                        telefoneCorretor = site.user_phone.replace(/\D/g, '');
                    } else if (site.user_whatsapp) {
                        telefoneCorretor = site.user_whatsapp.replace(/\D/g, '');
                    }
                }
            } catch (error) {
                console.error("Erro ao obter dados do usuário:", error);
            }
            
            console.log("Dados do usuário (publicação):", {
                nome: nomeCorretor,
                email: emailCorretor,
                telefone: telefoneCorretor,
                site: site || "Não definido"
            });
            
            // Enviar dados para webhook usando formato de formulário tradicional
            $.ajax({
                url: 'https://zion.digitalestudio.com.br/webhook/bot-imoveis',
                method: 'POST',
                data: {
                    links: linksImoveis,
                    nome_corretor: nomeCorretor,
                    email_corretor: emailCorretor,
                    telefone_corretor: telefoneCorretor
                },
                success: function(response) {
                    console.log("Resposta do webhook (publicação):", response);
                    // Mostrar mensagem de sucesso
                    $('#publicacao-form-overlay .form-popup-content > *:not(#mensagem-sucesso)').hide();
                    $('#mensagem-sucesso').show();
                },
                error: function(xhr, status, error) {
                    console.error("Erro no webhook (publicação):", error, xhr.responseText);
                    alert('Erro ao enviar formulário. Por favor, tente novamente.');
                }
            });
        });
        
        // Fechar mensagem de sucesso e o modal
        $('#fechar-sucesso').on('click', function() {
            $('#publicacao-form-overlay').remove();
            window.location.hash = '';
        });
        
        // Fechar modal ao clicar fora
        $('#publicacao-form-overlay').on('click', function(e) {
            if($(e.target).is('#publicacao-form-overlay')) {
                $(this).remove();
                window.location.hash = '';
            }
        });
    }
    
    // Criar e abrir o modal do formulário de Assessoria
    function abrirFormularioAssessoria() {
        // Criar o HTML do formulário
        const formularioHTML = `
            <div class="form-popup-overlay" id="assessoria-form-overlay">
                <div class="form-popup-container">
                    <div class="form-popup-content">
                        <h2>Assessoria Só Casa Top</h2>
                        <div class="form-divider"></div>
                        <p>Deseja Assessoria para quantos Imóveis?</p>
                        <input type="number" id="quantidade-imoveis" min="1" value="1">
                        
                        <p>Selecione os Impulsionamentos que deseja contratar:</p>
                        <div class="checkbox-container">
                            <label><input type="checkbox" name="impulsionamento" value="Patrocinado"> Patrocinado</label>
                            <label><input type="checkbox" name="impulsionamento" value="Assessoria"> Assessoria</label>
                            <label><input type="checkbox" name="impulsionamento" value="Colab"> Colab</label>
                            <label><input type="checkbox" name="impulsionamento" value="Captação + Colab"> Captação + Colab</label>
                        </div>
                        
                        <button type="button" id="enviar-assessoria" class="btn-form-popup">Contratar assessoria</button>
                        <div id="mensagem-sucesso-assessoria" class="mensagem-sucesso" style="display:none;">
                            <p>Obrigado! Recebemos sua solicitação. Confira seu WhatsApp para darmos continuidade ao processo.</p>
                            <button type="button" id="fechar-sucesso-assessoria" class="btn-form-popup">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Adicionar formulário ao body
        $('body').append(formularioHTML);
        
        // Manipular envio do formulário
        $('#enviar-assessoria').on('click', function() {
            const quantidadeImoveis = $('#quantidade-imoveis').val();
            const impulsionamentosSelecionados = [];
            
            $('input[name="impulsionamento"]:checked').each(function() {
                impulsionamentosSelecionados.push($(this).val());
            });
            
            // Obter dados do usuário diretamente do servidor
            $.ajax({
                url: site.ajax_url,
                method: 'POST',
                data: {
                    action: 'get_corretor_data',
                    nonce: site.nonce
                },
                success: function(response) {
                    if (response.success) {
                        console.log("Dados obtidos do servidor:", response.data);
                        
                        // Usar os dados retornados do servidor
                        enviarFormularioAssessoria(
                            quantidadeImoveis, 
                            impulsionamentosSelecionados, 
                            response.data.nome, 
                            response.data.email, 
                            response.data.telefone
                        );
                    } else {
                        console.error("Erro ao obter dados do corretor:", response.data);
                        // Fallback para valores padrão
                        enviarFormularioAssessoria(
                            quantidadeImoveis, 
                            impulsionamentosSelecionados, 
                            "Teste Corretor", 
                            "teste@corretor.com", 
                            "11999999999"
                        );
                    }
                },
                error: function() {
                    console.error("Erro na requisição AJAX para obter dados do corretor");
                    // Fallback para valores padrão
                    enviarFormularioAssessoria(
                        quantidadeImoveis, 
                        impulsionamentosSelecionados, 
                        "Teste Corretor", 
                        "teste@corretor.com", 
                        "11999999999"
                    );
                }
            });
        });
        
        // Função para enviar o formulário de assessoria
        function enviarFormularioAssessoria(quantidadeImoveis, impulsionamentos, nome, email, telefone) {
            console.log("Enviando formulário com dados:", {
                quantidade_imoveis: quantidadeImoveis,
                impulsionamentos: impulsionamentos,
                nome_corretor: nome,
                email_corretor: email,
                telefone_corretor: telefone
            });
            
            // Enviar dados para webhook
            $.ajax({
                url: 'https://zion.digitalestudio.com.br/webhook/assessoria',
                method: 'POST',
                data: {
                    quantidade_imoveis: quantidadeImoveis,
                    impulsionamentos: impulsionamentos,
                    nome_corretor: nome,
                    email_corretor: email,
                    telefone_corretor: telefone
                },
                success: function(response) {
                    console.log("Resposta do webhook:", response);
                    // Mostrar mensagem de sucesso
                    $('#assessoria-form-overlay .form-popup-content > *:not(#mensagem-sucesso-assessoria)').hide();
                    $('#mensagem-sucesso-assessoria').show();
                },
                error: function(xhr, status, error) {
                    console.error("Erro no webhook:", error, xhr.responseText);
                    alert('Erro ao enviar formulário. Por favor, tente novamente.');
                }
            });
        }
        
        // Fechar mensagem de sucesso e o modal
        $('#fechar-sucesso-assessoria').on('click', function() {
            $('#assessoria-form-overlay').remove();
            window.location.hash = '';
        });
        
        // Fechar modal ao clicar fora
        $('#assessoria-form-overlay').on('click', function(e) {
            if($(e.target).is('#assessoria-form-overlay')) {
                $(this).remove();
                window.location.hash = '';
            }
        });
    }
    
    // Função para obter dados do corretor logado
    function obterDadosCorretor() {
        console.log("Objeto site:", site);
        
        // Verificar se o objeto site existe
        if (typeof site === 'undefined') {
            console.error("Objeto 'site' não está definido");
            return {
                nome: '',
                email: '',
                telefone: ''
            };
        }
        
        // Obter nome do corretor - primeiro tentar display_name, depois first_name
        const nome = site.user_display_name || site.user_firstname || '';
        
        // Obter email do corretor
        const email = site.user_email || '';
        
        // Obter telefone - primeiro tentar phone, depois whatsapp
        let telefone = site.user_phone || site.user_whatsapp || '';
        
        // Formatar telefone se necessário (remover caracteres não numéricos)
        if (telefone) {
            telefone = telefone.replace(/\D/g, '');
        }
        
        return {
            nome: nome,
            email: email,
            telefone: telefone
        };
    }
}); 