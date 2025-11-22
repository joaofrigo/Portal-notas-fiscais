<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Portal de Notas Fiscais</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .top-buttons { display: flex; gap: 10px; margin-bottom: 20px; }
        .dashboard { display: flex; gap: 20px; margin-bottom: 40px; }
        .card { padding: 20px; background: #f2f2f2; border-radius: 8px; flex: 1; text-align: center; }
        .card h3 { margin-bottom: 10px; font-size: 16px; }
        .card p { font-size: 20px; font-weight: bold; margin: 0; }
        .notas-list { margin-top: 40px; }
        .notas-list table { width: 100%; border-collapse: collapse; }
        .notas-list th, .notas-list td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .notas-list th { background-color: #f2f2f2; }
        .notas-list a { color: #007BFF; text-decoration: none; }
        .notas-list a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="top-buttons">
    <a href="{{ route('nota.impostos') }}">
        <button type="button">Total Impostos</button>
    </a>
    <a href="{{ route('fornecedores.index') }}">
        <button type="button">Fornecedores</button>
    </a>
    <a href="{{ route('transportadoras.index') }}">
    <button type="button">Transportadoras</button>
    </a>
</div>

<h1>Dashboard Geral</h1>
<div class="dashboard">
    <div class="card">
        <h3>Total de Notas Fiscais</h3>
        <p>{{ $totalNFs }}</p>
    </div>
    <div class="card">
        <h3>Total de Produtos</h3>
        <p>{{ $totalProdutos }}</p>
    </div>
    <div class="card">
        <h3>Valor Total das NFs</h3>
        <p>R$ {{ number_format($valorTotalNFs, 2, ',', '.') }}</p>
    </div>
    <div class="card">
        <h3>Valor Total de Impostos</h3>
        <p>R$ {{ number_format($valorTotalImpostos, 2, ',', '.') }}</p>
    </div>
    <div class="card">
        <h3>Total de Itens de Produtos</h3>
        <p>{{ $totalItensProdutos }}</p>
    </div>
    <div class="card">
        <h3>Valor Total dos Produtos</h3>
        <p>R$ {{ number_format($valorTotalProdutos, 2, ',', '.') }}</p>
    </div>
</div>

<h2>Enviar Notas Fiscais (XML)</h2>

@if(session('msg'))
    <pre>{{ session('msg') }}</pre>
@endif

<form action="{{ route('upload.xml') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="xmls[]" accept=".xml" multiple required>
    <button type="submit">Enviar</button>
</form>

<div class="notas-list">
    <h2>Lista de Notas Fiscais</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Arquivo</th>
                <th>Valor NF</th>
                <th>Total Produtos</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notas as $nota)
            <tr>
                <td>{{ $nota['id'] }}</td>
                <td>
                    <a href="{{ route('nota.show', ['id' => $nota['id']]) }}">
                        {{ $nota['nome_arquivo'] }}
                    </a>
                </td>
                <td>R$ {{ number_format($nota['vNF'], 2, ',', '.') }}</td>
                <td>{{ number_format($nota['total_produtos']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

</body>
</html>
