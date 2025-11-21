<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotaFiscal;

class NFUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'xmls.*' => 'required|file|mimes:xml|max:2048'
        ]);

        $arquivos = $request->file('xmls');
        $mensagens = [];

        foreach ($arquivos as $file) {
            $filename = time() . '_' . $file->getClientOriginalName();
            $conteudo = $file->get(); // lê conteúdo XML

            libxml_use_internal_errors(true);

            $dom = new \DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = false;
            $dom->loadXML($conteudo);

            if (strpos($conteudo, '<nfeProc') !== false) {
                $dtdPath = str_replace('\\', '/', base_path('validadores/nfe.dtd'));
            } elseif (strpos($conteudo, '<ns3:RetornoConsulta') !== false) {
                $dtdPath = str_replace('\\', '/', base_path('validadores/nfse.dtd'));
            } else {
                $mensagens[] = "Tipo de XML não reconhecido: $filename";
                continue;
            }

            if (!file_exists($dtdPath)) {
                $mensagens[] = "DTD não encontrado para: $filename";
                continue;
            }

            $xmlWithDoctype = preg_replace(
                '/<\?xml.*\?>/',
                "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<!DOCTYPE {$dom->documentElement->tagName} SYSTEM \"{$dtdPath}\">",
                $conteudo
            );

            $dom = new \DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = false;
            $dom->loadXML($xmlWithDoctype);

            if (!$dom->validate()) {
                $errors = libxml_get_errors();
                $mensagemErro = "Falha na validação DTD: $filename\n";
                foreach ($errors as $error) {
                    $mensagemErro .= $this->formatLibxmlError($error, "    "); // recuo de 4 espaços
                }
                libxml_clear_errors();
                $mensagens[] = $mensagemErro;
                continue;
            }

            $registro = NotaFiscal::create([
                'nome_arquivo' => $filename,
                'xml_conteudo' => $conteudo,
            ]);

            $mensagens[] = "Nota salva no banco: $filename (ID: {$registro->id})";
            usleep(1000);
        }

        return redirect('/')
            ->with('msg', implode("\n", $mensagens));
    }

    private function formatLibxmlError($error, $indent = "  ")
    {
        $return = $indent;
        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "Warning {$error->code}: ";
                break;
            case LIBXML_ERR_ERROR:
                $return .= "Error {$error->code}: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "Fatal Error {$error->code}: ";
                break;
        }
        $return .= trim($error->message);
        if ($error->file) {
            $return .= " in {$error->file}";
        }
        $return .= " on line {$error->line}, column {$error->column}\n";
        return $return;
    }
}
