<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Transportadoras - Portal de Notas Fiscais</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        a { color: #007BFF; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .btn { margin-bottom: 20px; padding: 8px 16px; background-color: #007BFF; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background-color: #0056b3; }
    </style>
</head>
<body>

<a href="{{ url()->previous() }}" class="btn">Voltar</a>

<h1>Transportadoras</h1>

<table>
    <thead>
        <tr>
            <th>Transportadora</th>
            <th>Notas Fiscais</th>
        </tr>
    </thead>
    <tbody>
        @foreach($dados as $item)
        <tr>
            <td>{{ $item['transportadora'] }}</td>
            <td>
                @foreach($item['notas'] as $nota)
                    <a href="{{ route('nota.show', ['id' => $nota->id]) }}">
                        {{ $nota->nome_arquivo }}
                    </a>
                    @if(!$loop->last), @endif
                @endforeach
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
