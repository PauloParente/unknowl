# Middleware EnsureUserCanVote

## Visão Geral

O middleware `EnsureUserCanVote` é responsável por validar se um usuário pode votar em posts ou comentários antes de processar a requisição. Ele implementa múltiplas camadas de validação para garantir a integridade e segurança do sistema de votação.

## Funcionalidades

### 1. Autenticação
- Verifica se o usuário está logado
- Retorna erro 401 se não autenticado

### 2. Verificação de Email
- Verifica se o email foi confirmado (se configurado)
- Retorna erro 403 se email não verificado

### 3. Status da Conta
- Verifica se a conta não está banida ou suspensa
- Retorna erro 403 se conta suspensa

### 4. Validação de Posts
- Verifica se o post existe
- Impede votação em posts próprios
- Impede votação em posts deletados (soft delete)

### 5. Validação de Comentários
- Verifica se o comentário existe
- Impede votação em comentários próprios
- Impede votação em comentários deletados
- Impede votação em comentários de posts deletados

### 6. Rate Limiting
- Limita a 100 votos por hora por usuário
- Retorna erro 429 se limite excedido
- Usa cache para controle de tentativas

## Aplicação

O middleware é aplicado às seguintes rotas:

```php
// Posts
POST   /posts/{post}/vote
DELETE /posts/{post}/vote

// Comentários
POST   /comments/{comment}/vote
DELETE /comments/{comment}/vote
```

## Códigos de Resposta

| Código | Descrição |
|--------|-----------|
| 200    | Voto processado com sucesso |
| 401    | Usuário não autenticado |
| 403    | Usuário não autorizado (email não verificado, conta suspensa, auto-voto, etc.) |
| 404    | Post/comentário não encontrado |
| 429    | Rate limit excedido |

## Configuração

### Registro do Middleware

```php
// bootstrap/app.php
$middleware->alias([
    'can.vote' => \App\Http\Middleware\EnsureUserCanVote::class,
]);
```

### Aplicação nas Rotas

```php
// routes/web.php
Route::post('posts/{post}/vote', [PostVoteController::class, 'vote'])
    ->middleware('can.vote')
    ->name('posts.vote');
```

## Exemplos de Uso

### Resposta de Sucesso
```json
{
    "success": true,
    "action": "created",
    "score": 5,
    "user_vote": "like"
}
```

### Resposta de Erro
```json
{
    "success": false,
    "message": "Você não pode votar em seu próprio post"
}
```

## Segurança

O middleware implementa várias medidas de segurança:

1. **Validação de Autenticação**: Garante que apenas usuários logados possam votar
2. **Prevenção de Auto-voto**: Impede que usuários votem em seus próprios conteúdos
3. **Rate Limiting**: Previne spam e ataques de força bruta
4. **Validação de Existência**: Verifica se posts/comentários existem antes de processar
5. **Validação de Estado**: Impede votação em conteúdos deletados

## Performance

- Usa cache para rate limiting (Redis/Memcached recomendado)
- Validações otimizadas com early returns
- Verificações condicionais para recursos opcionais (soft delete, banimento)

## Extensibilidade

O middleware pode ser facilmente estendido para incluir:

- Verificações de permissões baseadas em roles
- Validações de comunidade (ex: apenas membros podem votar)
- Limites de votação por período
- Logs de auditoria
- Notificações de voto
