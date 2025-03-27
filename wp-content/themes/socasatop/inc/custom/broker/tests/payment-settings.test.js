// Testes para o formulário de pagamento
describe('Payment Settings Form', () => {
    let mercadoPago;
    let cardForm;
    
    beforeEach(() => {
        // Mock do MercadoPago SDK
        mercadoPago = {
            cardForm: jest.fn().mockReturnValue({
                mount: jest.fn(),
                unmount: jest.fn(),
                createCardToken: jest.fn()
            })
        };
        
        window.Mercadopago = mercadoPago;
        
        // Setup do DOM
        document.body.innerHTML = `
            <div id="card-form-container"></div>
            <button id="add-card-button">Adicionar Cartão</button>
            <button id="cancel-card-button" style="display: none;">Cancelar</button>
        `;
        
        // Importar o script
        require('../assets/js/payment-settings.js');
    });
    
    afterEach(() => {
        jest.clearAllMocks();
        document.body.innerHTML = '';
    });
    
    test('deve inicializar o formulário do MercadoPago corretamente', () => {
        const addButton = document.getElementById('add-card-button');
        addButton.click();
        
        expect(mercadoPago.cardForm).toHaveBeenCalledWith({
            amount: expect.any(String),
            autoMount: false,
            form: {
                id: 'card-form',
                cardholderName: {
                    id: 'cardholderName',
                    placeholder: 'Nome no cartão'
                },
                cardNumber: {
                    id: 'cardNumber',
                    placeholder: 'Número do cartão'
                },
                cardExpirationMonth: {
                    id: 'cardExpirationMonth',
                    placeholder: 'MM'
                },
                cardExpirationYear: {
                    id: 'cardExpirationYear',
                    placeholder: 'YY'
                },
                securityCode: {
                    id: 'securityCode',
                    placeholder: 'CVV'
                }
            },
            callbacks: {
                onFormMounted: expect.any(Function),
                onSubmit: expect.any(Function),
                onFetching: expect.any(Function),
                onError: expect.any(Function)
            }
        });
    });
    
    test('deve mostrar/esconder botões corretamente ao adicionar cartão', () => {
        const addButton = document.getElementById('add-card-button');
        const cancelButton = document.getElementById('cancel-card-button');
        
        addButton.click();
        
        expect(addButton.style.display).toBe('none');
        expect(cancelButton.style.display).toBe('block');
        expect(document.getElementById('card-form-container').innerHTML).not.toBe('');
    });
    
    test('deve limpar formulário ao cancelar', () => {
        const addButton = document.getElementById('add-card-button');
        const cancelButton = document.getElementById('cancel-card-button');
        
        addButton.click();
        cancelButton.click();
        
        expect(addButton.style.display).toBe('block');
        expect(cancelButton.style.display).toBe('none');
        expect(document.getElementById('card-form-container').innerHTML).toBe('');
    });
    
    test('deve chamar createCardToken ao submeter formulário', async () => {
        const addButton = document.getElementById('add-card-button');
        addButton.click();
        
        const form = document.getElementById('card-form');
        const event = new Event('submit');
        form.dispatchEvent(event);
        
        expect(cardForm.createCardToken).toHaveBeenCalled();
    });
    
    test('deve exibir mensagem de erro quando createCardToken falha', async () => {
        const error = new Error('Erro ao gerar token do cartão');
        cardForm.createCardToken.mockRejectedValue(error);
        
        const addButton = document.getElementById('add-card-button');
        addButton.click();
        
        const form = document.getElementById('card-form');
        await form.dispatchEvent(new Event('submit'));
        
        expect(document.querySelector('.error-message')).not.toBeNull();
        expect(document.querySelector('.error-message').textContent).toContain('Erro ao gerar token do cartão');
    });
    
    test('deve fazer requisição AJAX ao salvar cartão com sucesso', async () => {
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: () => Promise.resolve({ success: true })
        });
        
        const cardToken = 'test_token_123';
        cardForm.createCardToken.mockResolvedValue({ token: cardToken });
        
        const addButton = document.getElementById('add-card-button');
        addButton.click();
        
        const form = document.getElementById('card-form');
        await form.dispatchEvent(new Event('submit'));
        
        expect(global.fetch).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'save_card',
                    card_token: cardToken
                })
            })
        );
    });
    
    test('deve atualizar UI após salvar cartão com sucesso', async () => {
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: () => Promise.resolve({
                success: true,
                card: {
                    id: '123',
                    last_four: '4242',
                    brand: 'visa'
                }
            })
        });
        
        const addButton = document.getElementById('add-card-button');
        addButton.click();
        
        const form = document.getElementById('card-form');
        await form.dispatchEvent(new Event('submit'));
        
        expect(document.querySelector('.success-message')).not.toBeNull();
        expect(document.getElementById('card-form-container').innerHTML).toBe('');
        expect(addButton.style.display).toBe('block');
    });
});

// Testes para gerenciamento de cartões salvos
describe('Saved Cards Management', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <div class="cards-grid">
                <div class="card-item" data-card-id="123">
                    <button class="set-default-card">Definir como Principal</button>
                    <button class="delete-card">Excluir</button>
                </div>
            </div>
        `;
        
        require('../assets/js/payment-settings.js');
    });
    
    afterEach(() => {
        jest.clearAllMocks();
        document.body.innerHTML = '';
    });
    
    test('deve fazer requisição AJAX ao definir cartão como principal', async () => {
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: () => Promise.resolve({ success: true })
        });
        
        const setDefaultButton = document.querySelector('.set-default-card');
        await setDefaultButton.click();
        
        expect(global.fetch).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'set_default_card',
                    card_id: '123'
                })
            })
        );
    });
    
    test('deve fazer requisição AJAX ao excluir cartão', async () => {
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: () => Promise.resolve({ success: true })
        });
        
        global.confirm = jest.fn().mockReturnValue(true);
        
        const deleteButton = document.querySelector('.delete-card');
        await deleteButton.click();
        
        expect(global.fetch).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete_card',
                    card_id: '123'
                })
            })
        );
    });
    
    test('não deve excluir cartão se usuário cancelar confirmação', async () => {
        global.fetch = jest.fn();
        global.confirm = jest.fn().mockReturnValue(false);
        
        const deleteButton = document.querySelector('.delete-card');
        await deleteButton.click();
        
        expect(global.fetch).not.toHaveBeenCalled();
    });
    
    test('deve atualizar UI após definir cartão como principal', async () => {
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: () => Promise.resolve({ success: true })
        });
        
        const cardItem = document.querySelector('.card-item');
        const setDefaultButton = document.querySelector('.set-default-card');
        await setDefaultButton.click();
        
        expect(cardItem.classList.contains('default-card')).toBe(true);
        expect(document.querySelector('.default-badge')).not.toBeNull();
    });
    
    test('deve remover cartão do DOM após exclusão bem-sucedida', async () => {
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: () => Promise.resolve({ success: true })
        });
        
        global.confirm = jest.fn().mockReturnValue(true);
        
        const deleteButton = document.querySelector('.delete-card');
        await deleteButton.click();
        
        expect(document.querySelector('.card-item')).toBeNull();
    });
    
    test('deve exibir mensagem de erro quando operação falha', async () => {
        global.fetch = jest.fn().mockResolvedValue({
            ok: false,
            json: () => Promise.resolve({
                success: false,
                message: 'Erro ao processar operação'
            })
        });
        
        const setDefaultButton = document.querySelector('.set-default-card');
        await setDefaultButton.click();
        
        expect(document.querySelector('.error-message')).not.toBeNull();
        expect(document.querySelector('.error-message').textContent).toContain('Erro ao processar operação');
    });
}); 