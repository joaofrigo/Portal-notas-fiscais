# README

Esse aplicativo web que usa php, laravel e analisa XMLS, tem como objetivo principal ler notas fiscais do modelo 4.0 e extrair informações relevantes para o usuário. A aplicação é bem simples de configurar para uso próprio, mas existem algumas etapas importantes que podem passar despercebidas. Aqui está o passo a passo:

## Para conseguir acessar o framework
Para conseguir acessar o framework, primeiro precisamos de **PHP** e do **Composer**. PHP é só baixar no site oficial, composer também, no site oficial. A diferença é que o composer tem instalador.
(precisamos ter anteriormente 7-zip instalado e no path, o php e composer usam ele para descompactar arquivos)

---

## Configuração PHP:

- Criar pasta pro php e colocar no path. Colocar dentro da pasta a versão do php que quer, baixada diretamente do site.  
  Essa aplicação usou a versão:  
  **VS17 x64 Non Thread Safe - 2025-Nov-18 08:24:22 UTC**

- Temos como ponto central dentro da pasta do php, o `php.ini`. Nele iremos configurar a inicialização do php corretamente para que nosso aplicativo funcione.  
  Encontre as seguintes extensões e retire o ";" detrás delas (";" significa comentário no php.ini):

```
- extension=xsl
- extension=fileinfo
- extension=openssl
- extension=mbstring
- extension=curl
```

Onde o arquivo estará localizado (se ele estiver vazio, copie e cole as configurações dentro do `.ini development` para dentro do `.ini vazio`):

<img width="618" height="299" alt="image" src="https://github.com/user-attachments/assets/870c7715-6ea0-4c27-9a3e-c8f5e710c055" />

Agora configurado corretamente o php, configuramos o composer. Ele é bem mais simples, no site oficial contém um instalador e é só usa-lo. No meu caso, não precisei configurar o path do composer.

---

## Executando a aplicação

Temos tudo pronto para iniciar agora o servidor web localmente.  
É só baixar o repositório e ir onde está o arquivo **artisan**, lá, use o comando:

```
php artisan serve
```

E pronto, o aplicativo está de pé, é só acessar pelo seu navegador.

---

## Scripts adicionais

Existem dois scripts adicionais que não estão no aplicativo web, mas que são igualmente úteis em manipular XML.

### 1. anonimizar_XML.php
É só chama-lo com:

```
php anonimizar_XML.php
```

Ele vai tentar anonimizar todos os arquivos xml da pasta onde ele se encontra.  
Ele só funciona porém, para anonimizar notas fiscais do modelo 4.0, como é de se esperar.

---

### 2. ScriptTransformador.php
Ele tem algumas opções de manipulação de xml, que são:

- **Opção A** : Gera, para cada NF em XML, um arquivo JSON equivalente.  
- **Opção B** : Gera, para cada NF, um XML contendo somente os seus produtos.  
- **Opção C** : Gera um único XML consolidado contendo todos os produtos de todas as NFs.  
- **Opção D** : Para cada NF, gera um novo XML completo da NF com os produtos ordenados alfabeticamente.  
- **Opção E** : Gera um único XML da NF com todos os produtos de todas as notas, ordenados por preço crescente.  
- **Opção F** : Executa todas as transformações (A, B, C, D, E) em sequência.


## Chamada do script

Uso:

```
php transform_options.php --option=<A|B|C|D|E|ALL> [--input=path] [--output=path]
```

Exemplo:

```
php transform_options.php --option=D --input=./nfs --output=./out
```

Dentro da pasta de output, haverá uma pasta para cada opção.
