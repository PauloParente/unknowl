# Configuração do Pusher para Chat em Tempo Real

## Visão Geral

O sistema de chat foi configurado para usar o Pusher para comunicação em tempo real. Isso permite que as mensagens sejam enviadas e recebidas instantaneamente sem necessidade de recarregar a página.

## Configuração Necessária

### 1. Conta do Pusher

1. Acesse [pusher.com](https://pusher.com) e crie uma conta
2. Crie um novo app no dashboard do Pusher
3. Anote as credenciais:
   - App ID
   - Key
   - Secret
   - Cluster

### 2. Configuração do Laravel

Adicione as seguintes variáveis ao arquivo `.env`:

```env
# Pusher Configuration
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1
```

### 3. Configuração do Frontend

As configurações do Pusher são automaticamente injetadas no frontend através do arquivo `resources/views/app.blade.php`.

## Funcionalidades Implementadas

### Eventos de Broadcast

1. **MessageSent**: Disparado quando uma nova mensagem é enviada
   - Canal: `private-chat.{chat_id}`
   - Evento: `message.sent`
   - Dados: Informações completas da mensagem

2. **MessageRead**: Disparado quando uma mensagem é marcada como lida
   - Canal: `private-chat.{chat_id}`
   - Evento: `message.read`
   - Dados: ID da mensagem, usuário e timestamp

### Composable usePusher

O composable `usePusher` fornece:

- `initializePusher()`: Inicializa a conexão com o Pusher
- `subscribeToChat()`: Subscreve a um chat específico
- `unsubscribeFromChat()`: Remove a subscrição de um chat
- `disconnect()`: Desconecta do Pusher
- `isConnected`: Estado da conexão

### Componente Chat/Show.vue

O componente de chat foi atualizado para:

- Conectar automaticamente ao Pusher quando carregado
- Escutar novas mensagens em tempo real
- Adicionar mensagens recebidas à interface
- Desconectar quando o componente é destruído

## Como Funciona

1. **Envio de Mensagem**:
   - Usuário envia mensagem via formulário
   - MessageController processa e salva a mensagem
   - Evento `MessageSent` é disparado
   - Pusher envia a mensagem para todos os participantes do chat

2. **Recebimento de Mensagem**:
   - Pusher recebe o evento `message.sent`
   - Composable `usePusher` processa o evento
   - Nova mensagem é adicionada à interface
   - Scroll automático para a nova mensagem

3. **Marcação como Lida**:
   - Usuário visualiza mensagens
   - Evento `MessageRead` é disparado
   - Outros participantes são notificados

## Testando a Funcionalidade

1. Configure as credenciais do Pusher no `.env`
2. Execute `php artisan config:cache` para limpar o cache
3. Abra o chat em duas abas/janelas diferentes
4. Envie uma mensagem de uma aba
5. A mensagem deve aparecer instantaneamente na outra aba

## Troubleshooting

### Mensagens não aparecem em tempo real

1. Verifique se as credenciais do Pusher estão corretas
2. Confirme se `BROADCAST_DRIVER=pusher` no `.env`
3. Verifique o console do navegador para erros
4. Confirme se o Pusher está conectado (verifique `isConnected`)

### Erro de autenticação

1. Verifique se a rota `/broadcasting/auth` está funcionando
2. Confirme se o usuário está autenticado
3. Verifique se o CSRF token está sendo enviado

### Performance

- O Pusher automaticamente gerencia reconexões
- Canais são limpos quando componentes são destruídos
- Apenas um canal por chat é mantido ativo

## Próximos Passos

- Implementar notificações push
- Adicionar indicadores de digitação
- Implementar status de presença online/offline
- Adicionar suporte a mensagens de voz/imagem
