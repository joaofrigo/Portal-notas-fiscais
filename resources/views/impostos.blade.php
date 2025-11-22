<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Total de Impostos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        a { color: #007BFF; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<a href="{{ route('home') }}">← Voltar ao Dashboard</a>
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
