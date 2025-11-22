<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fornecedores - Portal de Notas Fiscais</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        a { color: #007BFF; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<h1>Fornecedores</h1>

<table>
    <thead>
        <tr>
            <th>Fornecedor</th>
            <th>Notas Fiscais</th>
        </tr>
    </thead>
    <tbody>
        @foreach($dados as $item)
            <tr>
                <td>{{ $item['fornecedor'] }}</td>
                <td>
                    @foreach($item['notas'] as $nota)
                        <a href="{{ route('nota.show', ['id' => $nota->id]) }}">
                            {{ $nota->nome_arquivo }}
                        </a>
                        @if(!$loop->last)
                            , 
                        @endif
                    @endforeach
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<a href="{{ route('home') }}"><button>Voltar ao Dashboard</button></a>

</body>
</html>
