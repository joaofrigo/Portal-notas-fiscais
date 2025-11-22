<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Portal de Notas Fiscais</title>
    <style>
        /* Reset básico e fonte */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f8;
            color: #333;
            margin: 0;
            padding: 20px 40px;
        }

        h1, h2 {
            color: #222;
        }

        /* Top buttons */
        .top-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .top-buttons a button {
            padding: 10px 18px;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }

        .top-buttons a button:hover {
            background-color: #0056b3;
        }

        /* Dashboard cards */
        .dashboard {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 50px;
        }

        .card {
            flex: 1 1 200px;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.12);
        }

        .card h3 {
            font-size: 16px;
            margin-bottom: 12px;
            color: #555;
        }

        .card p {
            font-size: 22px;
            font-weight: bold;
            margin: 0;
            color: #111;
        }

        /* Form de upload */
        form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 40px;
        }

        form input[type="file"] {
            padding: 6px;
        }

        form button {
            background-color: #28a745;
        }

        form button:hover {
            background-color: #1e7e34;
        }

        /* Tabelas */
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
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
            color: #333;
        }

        a {
            color: #007BFF;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        pre {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 6px;
            overflow-x: auto;
        }
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
