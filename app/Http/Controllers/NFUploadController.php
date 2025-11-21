<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotaFiscal;

class NFUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'xmls.*' => 'required|file|mimes:xml|max:2048' // valida cada arquivo
        ]);

        $arquivos = $request->file('xmls'); // array de arquivos
        $mensagens = [];

        foreach ($arquivos as $file) {
            $filename = time() . '_' . $file->getClientOriginalName();
            $conteudo = $file->get(); // lê conteúdo

            // grava no banco
            $registro = NotaFiscal::create([
                'nome_arquivo' => $filename,
                'xml_conteudo' => $conteudo,
            ]);

            $mensagens[] = "Nota salva no banco: $filename (ID: {$registro->id})";

            // pequeno delay para nomes únicos
            usleep(1000);
        }

        // redireciona com todas as mensagens
        return redirect('/')
            ->with('msg', implode('<br>', $mensagens));
    }
}
