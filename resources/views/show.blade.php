<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Detalhes da Nota Fiscal #{{ $dadosNF['id'] }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f8;
            color: #333;
            margin: 20px 40px;
        }

        h1, h2 {
            color: #222;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px 10px;
            border: 1px solid #e0e0e0;
            text-align: left;
            font-size: 14px;
        }

        th {
            background-color: #f5f5f5;
            font-weight: 600;
        }

        a {
            color: #007BFF;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Botões de voltar */
        .back-links {
            margin-bottom: 20px;
        }

        .back-links a button {
            padding: 10px 18px;
            background-color: #6c757d;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-right: 15px;
            transition: background 0.2s;
        }

        .back-links a button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>

<div class="back-links">
    <a href="{{ url('/') }}"><button>← Voltar ao Dashboard Principal</button></a>
    <a href="{{ route('nota.impostos') }}"><button>← Voltar ao Dashboard de Impostos</button></a>
</div>

<h1>Nota Fiscal #{{ $dadosNF['id'] }} ({{ $dadosNF['nome_arquivo'] }})</h1>

<h2>Resumo da NF</h2>
<table>
    <tr><th>Valor Total da NF (vNF)</th><td>R$ {{ number_format($dadosNF['vNF'], 2, ',', '.') }}</td></tr>
    <tr><th>Valor ICMS</th><td>R$ {{ number_format($dadosNF['vICMS'], 2, ',', '.') }}</td></tr>
    <tr><th>Valor IPI</th><td>R$ {{ number_format($dadosNF['vIPI'], 2, ',', '.') }}</td></tr>
    <tr><th>Valor PIS</th><td>R$ {{ number_format($dadosNF['vPIS'], 2, ',', '.') }}</td></tr>
    <tr><th>Valor COFINS</th><td>R$ {{ number_format($dadosNF['vCOFINS'], 2, ',', '.') }}</td></tr>
    <tr><th>Total Impostos</th>
        <td>R$ {{ number_format($dadosNF['vICMS'] + $dadosNF['vIPI'] + $dadosNF['vPIS'] + $dadosNF['vCOFINS'], 2, ',', '.') }}</td>
    </tr>
    <tr><th>Total Produtos</th><td>R$ {{ number_format($dadosNF['total_produtos'], 2, ',', '.') }}</td></tr>
    <tr><th>Modalidade de Frete</th>
        <td>
            @switch($dadosNF['frete']['modFrete'])
                @case('0') Por conta do emitente @break
                @case('1') Por conta do destinatário @break
                @case('2') Sem frete / entrega própria @break
                @case('9') Outros @break
                @default Não informado @break
            @endswitch
        </td>
    </tr>
</table>

<h2>Transportadora</h2>
@if($dadosNF['frete']['transportadora'])
<table>
    <tr><th>CNPJ</th><td>{{ $dadosNF['frete']['transportadora']['CNPJ'] ?: 'Não informado' }}</td></tr>
    <tr><th>CPF</th><td>{{ $dadosNF['frete']['transportadora']['CPF'] ?: 'Não informado' }}</td></tr>
    <tr><th>Nome</th><td>{{ $dadosNF['frete']['transportadora']['xNome'] ?: 'Não informado' }}</td></tr>
    <tr><th>IE</th><td>{{ $dadosNF['frete']['transportadora']['IE'] ?: 'Não informado' }}</td></tr>
    <tr><th>Endereço</th><td>{{ $dadosNF['frete']['transportadora']['xEnder'] ?: 'Não informado' }}</td></tr>
    <tr><th>Cidade</th><td>{{ $dadosNF['frete']['transportadora']['xMun'] ?: 'Não informado' }}</td></tr>
    <tr><th>UF</th><td>{{ $dadosNF['frete']['transportadora']['UF'] ?: 'Não informado' }}</td></tr>
</table>
@else
<p>Sem transportadora (frete próprio ou não informado)</p>
@endif

<h2>Produtos</h2>
<table>
    <thead>
        <tr>
            <th>Código</th>
            <th>Descrição</th>
            <th>Quantidade</th>
            <th>Valor Unitário</th>
            <th>Valor Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($dadosNF['produtos'] as $produto)
        <tr>
            <td>{{ $produto['cProd'] }}</td>
            <td>{{ $produto['xProd'] }}</td>
            <td>{{ $produto['qCom'] }}</td>
            <td>R$ {{ number_format($produto['vUnCom'], 2, ',', '.') }}</td>
            <td>R$ {{ number_format($produto['vProd'], 2, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
