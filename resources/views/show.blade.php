<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Detalhes da Nota Fiscal #{{ $dadosNF['id'] }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .back-links { margin-bottom: 20px; }
        .back-links a { margin-right: 20px; text-decoration: none; color: #007BFF; }
        .back-links a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="back-links">
    <a href="{{ url('/') }}">← Voltar ao Dashboard Principal</a>
    <a href="{{ route('nota.impostos') }}">← Voltar ao Dashboard de Impostos</a>
</div>

<h1>Nota Fiscal #{{ $dadosNF['id'] }} ({{ $dadosNF['nome_arquivo'] }})</h1>

<h2>Resumo da NF</h2>
<table>
    <tr>
        <th>Valor Total da NF (vNF)</th>
        <td>R$ {{ number_format($dadosNF['vNF'], 2, ',', '.') }}</td>
    </tr>
    <tr>
        <th>Valor ICMS</th>
        <td>R$ {{ number_format($dadosNF['vICMS'], 2, ',', '.') }}</td>
    </tr>
    <tr>
        <th>Valor IPI</th>
        <td>R$ {{ number_format($dadosNF['vIPI'], 2, ',', '.') }}</td>
    </tr>
    <tr>
        <th>Valor PIS</th>
        <td>R$ {{ number_format($dadosNF['vPIS'], 2, ',', '.') }}</td>
    </tr>
    <tr>
        <th>Valor COFINS</th>
        <td>R$ {{ number_format($dadosNF['vCOFINS'], 2, ',', '.') }}</td>
    </tr>
    <tr>
        <th>Total Impostos</th>
        <td>R$ {{ number_format($dadosNF['vICMS'] + $dadosNF['vIPI'] + $dadosNF['vPIS'] + $dadosNF['vCOFINS'], 2, ',', '.') }}</td>
    </tr>
    <tr>
        <th>Total Produtos</th>
        <td>R$ {{ number_format($dadosNF['total_produtos'], 2, ',', '.') }}</td>
    </tr>
    <tr>
        <th>Modalidade de Frete</th>
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
