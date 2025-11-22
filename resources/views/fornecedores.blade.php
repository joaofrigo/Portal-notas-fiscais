<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fornecedores - Portal de Notas Fiscais</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f8;
            color: #333;
            margin: 20px 40px;
        }

        h1 {
            color: #222;
            margin-bottom: 25px;
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

        /* Espaçamento entre múltiplas notas */
        td a + a {
            margin-left: 6px;
        }
    </style>
</head>
<body>

<h1>Fornecedores</h1>

<a href="{{ route('home') }}"><button>Voltar ao Dashboard</button></a>

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

</body>
</html>
