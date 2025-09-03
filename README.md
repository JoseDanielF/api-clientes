# API Clientes

Este projeto é uma API desenvolvida com o framework CodeIgniter.

## Requisitos
- PHP >= 7.4.33
- Composer
- Servidor web (Apache recomendado, pode usar XAMPP)

## Instalação
1. Clone o repositório ou baixe os arquivos do projeto.
2. Instale as dependências do Composer:
   ```
   composer install
   ```
3. Configure o servidor web para apontar para a pasta do projeto (`c:/xampp7/htdocs/api-clientes`).

## Configuração
- O arquivo de configuração principal está em `application/config/config.php`.
- O `base_url` padrão está configurado para `http://localhost/api-clientes/`.
- Ajuste as configurações de banco de dados em `application/config/database.php` conforme necessário.

## Executando o Projeto
1. Inicie o Apache pelo XAMPP.
2. Acesse no navegador:
   ```
   http://localhost/api-clientes/
   ```
3. Para acessar a API, utilize as rotas definidas nos controllers em `application/controllers/api/`.

## Estrutura do Projeto
- `application/` - Código principal da aplicação
- `system/` - Arquivos do framework CodeIgniter
- `vendor/` - Dependências instaladas pelo Composer

## Suporte
- Documentação do CodeIgniter: https://codeigniter.com/userguide3/
- Fórum: http://forum.codeigniter.com/

---

