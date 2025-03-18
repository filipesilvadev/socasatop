FROM node:20-alpine

WORKDIR /app

# Instalar dependências
COPY package*.json ./
RUN npm install

# Copiar arquivos do projeto
COPY . .

# Comando para executar os testes
CMD ["npm", "test"] 