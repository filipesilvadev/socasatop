# Testes do Formulário de Pagamento

Este diretório contém os testes automatizados para o formulário de pagamento do MercadoPago.

## Estrutura dos Testes

Os testes estão organizados em duas suítes principais:

1. **Payment Settings Form**: Testa a funcionalidade do formulário de adição de cartão
   - Inicialização do formulário do MercadoPago
   - Manipulação da UI (mostrar/esconder botões)
   - Geração de token do cartão
   - Salvamento do cartão via AJAX
   - Tratamento de erros

2. **Saved Cards Management**: Testa o gerenciamento de cartões salvos
   - Definir cartão como principal
   - Excluir cartão
   - Atualização da UI
   - Tratamento de erros

## Configuração do Ambiente

Para executar os testes, você precisa ter instalado:

- Node.js 20.x ou superior
- npm 11.x ou superior

### Dependências

```bash
npm install --save-dev jest jest-environment-jsdom @testing-library/jest-dom babel-jest @babel/core @babel/preset-env
```

### Configuração do Jest

O arquivo `jest.config.js` configura o ambiente de teste:

```javascript
module.exports = {
    testEnvironment: 'jsdom',
    setupFilesAfterEnv: ['./tests/setup.js'],
    moduleNameMapper: {
        '\\.(css|less|scss|sass)$': 'identity-obj-proxy'
    },
    testMatch: [
        '**/tests/**/*.test.js'
    ],
    transform: {
        '^.+\\.js$': 'babel-jest'
    }
};
```

### Configuração do Babel

O arquivo `babel.config.js` configura o transpilador:

```javascript
module.exports = {
    presets: [
        [
            '@babel/preset-env',
            {
                targets: {
                    node: 'current'
                }
            }
        ]
    ]
};
```

## Executando os Testes

Para executar os testes localmente:

```bash
npm test
```

Para executar os testes em modo de observação:

```bash
npm run test:watch
```

## Docker (Recomendado)

Para garantir um ambiente consistente, recomendamos usar Docker:

1. Crie um `Dockerfile`:

```dockerfile
FROM node:20-alpine

WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .

CMD ["npm", "test"]
```

2. Crie um arquivo `docker-compose.yml`:

```yaml
version: '3'
services:
  tests:
    build: .
    volumes:
      - .:/app
      - /app/node_modules
```

3. Execute os testes:

```bash
docker-compose up
```

## CI/CD

Recomendamos configurar um pipeline de CI/CD (por exemplo, GitHub Actions) para executar os testes automaticamente a cada push:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
      - run: npm ci
      - run: npm test
```

## Cobertura de Testes

Os testes cobrem os seguintes cenários:

### Formulário de Pagamento
- [x] Inicialização do formulário do MercadoPago
- [x] Exibição/ocultação de elementos da UI
- [x] Limpeza do formulário
- [x] Geração de token do cartão
- [x] Tratamento de erros na geração do token
- [x] Requisição AJAX para salvar cartão
- [x] Atualização da UI após salvar cartão

### Gerenciamento de Cartões
- [x] Definir cartão como principal
- [x] Excluir cartão
- [x] Confirmação antes de excluir
- [x] Atualização da UI após definir cartão principal
- [x] Remoção do cartão do DOM após exclusão
- [x] Tratamento de erros nas operações

## Mocks

Os testes utilizam os seguintes mocks:

- **MercadoPago SDK**: Mock das funções do SDK
- **jQuery**: Mock das operações do DOM e AJAX
- **SweetAlert2**: Mock das notificações
- **fetch**: Mock das requisições HTTP
- **console**: Mock dos logs

## Contribuindo

1. Crie uma branch para sua feature
2. Adicione ou modifique os testes
3. Verifique se todos os testes passam
4. Faça o push e abra um Pull Request

## Troubleshooting

Se os testes falharem:

1. Verifique se todas as dependências estão instaladas
2. Limpe o cache do Jest: `jest --clearCache`
3. Verifique se o ambiente JSDOM está configurado corretamente
4. Verifique se os mocks estão funcionando como esperado

## Recursos Adicionais

- [Documentação do Jest](https://jestjs.io/)
- [Documentação do Testing Library](https://testing-library.com/docs/)
- [Documentação do MercadoPago](https://www.mercadopago.com.br/developers/) 