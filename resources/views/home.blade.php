<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Upload de Notas Fiscais</title>
</head>
<body>

<h2>Enviar Notas Fiscais (XML)</h2>

@if(session('msg'))
    <p>{{ session('msg') }}</p>
@endif

<form action="{{ route('upload.xml') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <!-- name com [] e atributo multiple para vários arquivos -->
    <input type="file" name="xmls[]" accept=".xml" multiple required>
    <button type="submit">Enviar</button>
</form>

</body>
</html>
