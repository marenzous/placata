# PLACATA — site com painel (v2.0, PHP + MySQL)

Site **placata.com.br** com painel de edição em **/mzcentral**. Mesmo visual e mesmo conteúdo
do site aprovado (v1.02); a diferença é que agora produtos, textos, contato e perguntas
frequentes são trocados pelo painel, sem mexer em código.

Roda em hospedagem **Plesk comum** (PHP + MySQL). Não precisa de Node, Supabase nem Vercel.

## O que o servidor precisa ter

- **PHP 8.1 ou mais novo** (testado em 8.3)
- Extensões do PHP: **pdo_mysql**, **gd** (com suporte a WEBP), **fileinfo**, **mbstring**
  (no Plesk todas já vêm ligadas por padrão)
- **MySQL 5.7+** ou **MariaDB 10.3+**
- No Plesk, em *PHP Settings*: `upload_max_filesize` = **8M** e `post_max_size` = **10M**
  (para aceitar fotos de até 5 MB)

## Publicar no Plesk — passo a passo

1. **Criar o banco**
   Plesk > *Sites e Domínios* > placata.com.br > **Bancos de dados** > *Adicionar banco de dados*.
   - Nome do banco: ex. `placata_site`
   - Crie também o **usuário** do banco e uma **senha forte**. Anote os três.

2. **Preparar o arquivo de configuração** (no seu computador)
   - Copie `config.local.example.php` e renomeie a cópia para **`config.local.php`**.
   - Abra e preencha: `db_nome`, `db_usuario`, `db_senha` (os do passo 1).
     `db_host` normalmente fica `localhost`.
   - Em `chave_instalacao`, troque o texto por **um código só seu** (ex.: `jasmim-azul-2026`).
     Ele será pedido uma única vez, na instalação.
   - **Nunca** mande esse arquivo por WhatsApp/e-mail nem suba no GitHub.

3. **Subir os arquivos**
   Plesk > **Arquivos** > pasta **httpdocs**. Apague o que houver da versão antiga
   (site estático) e envie **todo o conteúdo** desta pasta, incluindo `config.local.php`
   e os arquivos que começam com ponto (`.htaccess`).
   Dica: compacte tudo em `.zip`, envie e use *Extrair arquivos* no próprio Plesk.

4. **Ativar o HTTPS**
   Plesk > *Sites e Domínios* > **SSL/TLS Certificates** > *Let's Encrypt* > marque
   o domínio e o `www`. (O site já redireciona sozinho para `https://` e tira o `www`.)

5. **Instalar o painel**
   Abra **https://placata.com.br/mzcentral/instalar.php**
   - Ele cria as tabelas e já carrega os 9 produtos, os textos e as 5 perguntas do site.
   - Digite o **código de instalação** (passo 2), o e-mail (já vem
     `elvilimaramos@gmail.com`) e a **senha** (mínimo 10 caracteres).
   - Ao terminar, o instalador **se apaga sozinho**. Se aparecer aviso de que não
     conseguiu, apague `mzcentral/instalar.php` pelo Plesk (*Arquivos*).
     Mesmo esquecido lá, ele não funciona mais depois que existe um acesso.

6. **Pronto.** Entre em **https://placata.com.br/mzcentral/** com o e-mail e a senha.

## O que dá para fazer no painel

| Tela | O que muda |
|---|---|
| **Produtos** | Adicionar, editar, esconder/mostrar, excluir (com confirmação) e mudar a ordem com as setas ↑↓. Foto JPG/PNG/WEBP até 5 MB — o painel diminui para no máximo 1200px e converte para WEBP sozinho. Categoria (Túmulo/Endereço), descrição curta, texto da foto para o Google e "fundo branco". |
| **Textos do topo** | Título grande e frase de apresentação. |
| **Contato** | Número do WhatsApp (pode digitar com máscara, ex. `(62) 99699-5138`), texto de atendimento e área atendida. O número vale para **todos** os botões do site, para o Google (schema) e para o `llms.txt`. |
| **Perguntas frequentes** | Adicionar, editar, ordem, esconder e excluir. Também alimentam o "Perguntas e respostas" que o Google lê. |
| **Trocar minha senha** | Troca a senha do acesso. |

Todos os botões de WhatsApp mandam a mesma mensagem, fixa:
*"Olá! Vim pelo site da Placata e gostaria de fazer um orçamento."*

## Segurança (resumo técnico)

- Senhas só como hash (`password_hash`); login com limite de **5 tentativas em 15 min**
  por IP + e-mail (e 20 por IP), registrado no banco (`tentativas_login`).
- Sessão com cookie `HttpOnly`, `Secure`, `SameSite=Lax`, ID novo a cada login, saída
  automática após 2h parado. Token CSRF em todo formulário.
- Banco só com consultas preparadas (PDO). Tudo que sai do banco é escapado.
- Fotos validadas pelo conteúdo real (`finfo`), reprocessadas com GD; a pasta `uploads/`
  não executa PHP e só entrega imagens.
- `/mzcentral` com `noindex` (meta + cabeçalho) e bloqueado no `robots.txt`.
- `config.local.php`, `.sql`, `.md`, `inc/` e `instalar/` bloqueados para a web.
- Se o banco cair, o site continua no ar com o conteúdo padrão (nunca tela de erro).

### Plesk em modo "somente nginx"

O padrão do Plesk (nginx na frente + Apache) usa o `.htaccess` e não precisa de nada.
Se o domínio estiver com **Apache desligado**, cole em *Apache & nginx Settings >
Additional nginx directives*:

```
location ~ ^/(inc|instalar)/ { deny all; }
location ~ (config\.local.*\.php|\.sql|\.md)$ { deny all; }
location ~ ^/uploads/.*\.php$ { deny all; }
location = /llms.txt { rewrite ^ /llms.php last; }
location ~ ^/assets/img/produtos/(.+)$ { return 301 /uploads/produtos/$1; }
error_page 404 /404.php;
```

## Estrutura

```
index.php, privacidade/index.php, 404.php, llms.php   site público
config.php                   inicialização (sem segredos)
config.local.example.php     modelo dos dados do banco
inc/                         código interno (conteúdo, layout, painel, fotos, WhatsApp)
instalar/schema.sql, seed.sql  estrutura e conteúdo inicial do banco
mzcentral/                   painel administrativo
uploads/produtos/            fotos dos produtos
assets/                      CSS, fontes, logos, imagem de compartilhamento
robots.txt, sitemap.xml      SEO
```

Versão no rodapé: **v2.0**. A cada alteração no código, subir a versão em
`config.php` (`PLACATA_VERSAO` e `PLACATA_ASSET_V`).
