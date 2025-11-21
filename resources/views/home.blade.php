<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Upload de Notas Fiscais</title>
</head>
<body>

<h2>Enviar Notas Fiscais (XML)</h2>

@if(session('msg'))
    <pre>{{ session('msg') }}</pre>
@endif

<form action="{{ route('upload.xml') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="xmls[]" accept=".xml" multiple required>
    <button type="submit">Enviar</button>
</form>

</body>
</html>
