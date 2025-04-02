// Funções para a página de detalhes de imóveis
jQuery(document).ready(function($) {
    // Inicializar o carrossel de imagens se existir
    if ($('.swiper-container').length > 0) {
        const gallerySwiper = new Swiper('.swiper-container', {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true
            }
        });
    }

    // Funcionalidade de abas
    $('.tab-button').on('click', function() {
        const $this = $(this);
        const tab = $this.data('tab');
        
        // Desativar todas as abas e painéis
        $('.tab-button').removeClass('active');
        $('.tab-panel').removeClass('active');
        
        // Ativar aba e painel clicados
        $this.addClass('active');
        $('#' + tab).addClass('active');
    });

    // Função para abrir o formulário de contato do corretor
    window.openContactForm = function(brokerId, immobileId) {
        // Verificar se o usuário está logado
        if (typeof site !== 'undefined' && site.is_logged_in) {
            // Usuário logado, mostrar informações de contato
            Swal.fire({
                title: 'Contato do Corretor',
                html: 'Carregando informações de contato...',
                didOpen: () => {
                    // Fazer uma requisição AJAX para obter os dados do corretor
                    $.ajax({
                        url: site.ajax_url,
                        method: 'POST',
                        data: {
                            action: 'get_broker_contact',
                            broker_id: brokerId,
                            immobile_id: immobileId,
                            nonce: site.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                // Atualizar o modal com os dados do corretor
                                Swal.update({
                                    html: `
                                        <div class="broker-contact-info">
                                            <p><strong>Nome:</strong> ${response.data.name}</p>
                                            <p><strong>Email:</strong> ${response.data.email}</p>
                                            <p><strong>Telefone:</strong> ${response.data.phone}</p>
                                            <p><strong>WhatsApp:</strong> ${response.data.whatsapp}</p>
                                        </div>
                                        <div class="broker-contact-buttons">
                                            <a href="https://wa.me/${response.data.whatsapp_clean}" target="_blank" class="whatsapp-btn">
                                                <i class="fab fa-whatsapp"></i> WhatsApp
                                            </a>
                                            <a href="mailto:${response.data.email}" class="email-btn">
                                                <i class="far fa-envelope"></i> Email
                                            </a>
                                        </div>
                                    `
                                });
                            } else {
                                Swal.update({
                                    icon: 'error',
                                    title: 'Erro',
                                    html: response.data.message || 'Erro ao obter dados do corretor'
                                });
                            }
                        },
                        error: function() {
                            Swal.update({
                                icon: 'error',
                                title: 'Erro',
                                html: 'Erro ao conectar com o servidor'
                            });
                        }
                    });
                }
            });
        } else {
            // Usuário não logado, mostrar formulário de contato
            Swal.fire({
                title: 'Entre em contato',
                html: `
                    <form id="contact-broker-form">
                        <div class="form-group">
                            <label for="contact-name">Nome</label>
                            <input type="text" id="contact-name" class="swal2-input" placeholder="Seu nome" required>
                        </div>
                        <div class="form-group">
                            <label for="contact-email">Email</label>
                            <input type="email" id="contact-email" class="swal2-input" placeholder="Seu email" required>
                        </div>
                        <div class="form-group">
                            <label for="contact-phone">Telefone</label>
                            <input type="tel" id="contact-phone" class="swal2-input" placeholder="Seu telefone" required>
                        </div>
                        <div class="form-group">
                            <label for="contact-message">Mensagem</label>
                            <textarea id="contact-message" class="swal2-textarea" placeholder="Sua mensagem" required></textarea>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: 'Enviar',
                cancelButtonText: 'Cancelar',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const name = $('#contact-name').val();
                    const email = $('#contact-email').val();
                    const phone = $('#contact-phone').val();
                    const message = $('#contact-message').val();
                    
                    if (!name || !email || !phone || !message) {
                        Swal.showValidationMessage('Por favor, preencha todos os campos');
                        return false;
                    }
                    
                    return $.ajax({
                        url: site.ajax_url,
                        method: 'POST',
                        data: {
                            action: 'send_broker_message',
                            broker_id: brokerId,
                            immobile_id: immobileId,
                            name: name,
                            email: email,
                            phone: phone,
                            message: message,
                            nonce: site.nonce
                        }
                    }).then(response => {
                        if (response.success) {
                            return response;
                        } else {
                            throw new Error(response.data.message || 'Erro ao enviar mensagem');
                        }
                    }).catch(error => {
                        Swal.showValidationMessage(`Erro: ${error.message}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Mensagem enviada!',
                        text: 'O corretor entrará em contato com você em breve.'
                    });
                }
            });
        }
    };
}); 