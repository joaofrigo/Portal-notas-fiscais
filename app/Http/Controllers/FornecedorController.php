<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotaFiscal;

class FornecedorController extends Controller
{
    public function index()
    {
        $notas = NotaFiscal::all();
        $dados = [];

        foreach ($notas as $nota) {
            $xml = simplexml_load_string($nota->xml_conteudo);

            // Registrar namespace do XML
            $xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');

            // Buscar emit usando XPath com namespace
            $emitNode = $xml->xpath('//nfe:infNFe/nfe:emit/nfe:xNome');

            $fornecedor = $emitNode ? (string) $emitNode[0] : 'Fornecedor Desconhecido';

            if (!isset($dados[$fornecedor])) {
                $dados[$fornecedor] = [];
            }

            $dados[$fornecedor][] = $nota;
        }

        // Ordenar fornecedores alfabeticamente
        ksort($dados);

        // Reformatar para a view
        $dadosFormatados = [];
        foreach ($dados as $fornecedor => $notas) {
            $dadosFormatados[] = [
                'fornecedor' => $fornecedor,
                'notas' => $notas
            ];
        }

        return view('fornecedores', ['dados' => $dadosFormatados]);
    }
}
