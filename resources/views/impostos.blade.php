<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Total de Impostos - Portal de Notas Fiscais</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f8;
            color: #333;
            margin: 20px 40px;
        }

        h1 {
            color: #222;
            margin-bottom: 15px;
        }

        /* Botão de voltar */
        a button {
            padding: 10px 18px;
            background-color: #6c757d;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-bottom: 20px;
            transition: background 0.2s;
        }

        a button:hover {
            background-color: #5a6268;
        }

        /* Tabela */
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-top: 20px;
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

        p strong {
            color: #007BFF;
        }
    </style>
</head>
<body>

<a href="{{ route('home') }}"><button>← Voltar ao Dashboard</button></a>

<h1>Total de Impostos</h1>
<p>Imposto total de todas as notas: <strong>R$ {{ number_format($totalImpostos, 2, ',', '.') }}</strong></p>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Arquivo</th>
            <th>ICMS</th>
            <th>IPI</th>
            <th>PIS</th>
            <th>COFINS</th>
            <th>Total Impostos</th>
        </tr>
    </thead>
    <tbody>
        @foreach($listaImpostos as $nota)
        <tr>
            <td>{{ $nota['id'] }}</td>
            <td>
                <a href="{{ route('nota.show', ['id' => $nota['id']]) }}">
                    {{ $nota['nome_arquivo'] }}
                </a>
            </td>
            <td>R$ {{ number_format($nota['vICMS'], 2, ',', '.') }}</td>
            <td>R$ {{ number_format($nota['vIPI'], 2, ',', '.') }}</td>
            <td>R$ {{ number_format($nota['vPIS'], 2, ',', '.') }}</td>
            <td>R$ {{ number_format($nota['vCOFINS'], 2, ',', '.') }}</td>
            <td>R$ {{ number_format($nota['total'], 2, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
