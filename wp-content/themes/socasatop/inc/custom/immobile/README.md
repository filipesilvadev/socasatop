# Sistema de Aprovação de Imóveis - SoCasaTop

Este módulo implementa um sistema completo de aprovação de imóveis para o tema SoCasaTop, permitindo que administradores revisem e aprovem/rejeitem imóveis enviados por corretores antes de serem publicados no site.

## Funcionalidades

### Para Corretores
- Ao criar um novo imóvel, ele é automaticamente enviado para aprovação
- Recebimento de notificações por e-mail quando o imóvel é aprovado ou rejeitado
- Em caso de rejeição, o corretor recebe o motivo detalhado para fazer as correções necessárias

### Para Administradores
- Interface dedicada para gerenciar imóveis pendentes de aprovação
- Filtros por corretor e data para facilitar a gestão
- Visualização prévia do imóvel antes de aprovar/rejeitar
- Contador de imóveis pendentes no menu administrativo
- Log de atividades para rastrear todas as aprovações e rejeições

## Como Usar

### Página de Aprovação
1. Acesse a página "Aprovação de Imóveis" no painel administrativo
2. Use os filtros para encontrar imóveis específicos
3. Clique em "Visualizar" para ver o imóvel antes de tomar uma decisão
4. Clique em "Aprovar" para publicar o imóvel no site
5. Clique em "Reprovar" para rejeitar o imóvel e fornecer um motivo

### Logs de Atividade
1. Acesse "Imóveis" > "Logs de Atividade" no painel administrativo
2. Visualize o histórico completo de aprovações e rejeições
3. Filtre por corretor, data ou ação

## Shortcode

O sistema inclui um shortcode para exibir a página de aprovação em qualquer lugar do site:

```
[immobile_approval_page]
```

Este shortcode só exibirá o conteúdo para administradores logados.

## Arquivos do Sistema

- `admin-approval.php`: Implementação principal do sistema
- `assets/approval.css`: Estilos da interface de aprovação
- `assets/approval.js`: Scripts para manipulação das ações de aprovação

## Fluxo de Trabalho

1. Corretor cria um novo imóvel
2. Sistema define o status como "pendente" e marca para aprovação
3. Administrador recebe notificação por e-mail
4. Administrador revisa, visualiza e aprova/rejeita o imóvel
5. Corretor recebe notificação sobre a decisão
6. Todas as ações são registradas no log de atividades

## Requisitos

- WordPress 5.0+
- Tema SoCasaTop
- Tipo de post personalizado "immobile" configurado
- Função de e-mail do WordPress configurada corretamente

## Suporte

Para suporte ou dúvidas sobre este módulo, entre em contato com a equipe de desenvolvimento do SoCasaTop. 