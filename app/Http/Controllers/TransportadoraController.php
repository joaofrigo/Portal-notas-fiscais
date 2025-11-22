<?php

namespace App\Http\Controllers;
use App\Models\NotaFiscal;

class TransportadoraController extends Controller
{
    public function index()
    {
        $notas = NotaFiscal::all();
        $dados = [];

        foreach ($notas as $nota) {
            $xml = simplexml_load_string($nota->xml_conteudo);

            // Registrar namespace do XML
            $xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');

            // Buscar transportadora usando XPath com namespace
            $transpNode = $xml->xpath('//nfe:infNFe/nfe:transp/nfe:transporta/nfe:xNome');

            $transportadora = $transpNode ? (string) $transpNode[0] : 'Transportadora Desconhecida';

            if (!isset($dados[$transportadora])) {
                $dados[$transportadora] = [];
            }

            $dados[$transportadora][] = $nota;
        }

        // Ordenar transportadoras alfabeticamente
        ksort($dados);

        // Reformatar para a view
        $dadosFormatados = [];
        foreach ($dados as $transportadora => $notas) {
            $dadosFormatados[] = [
                'transportadora' => $transportadora,
                'notas' => $notas
            ];
        }

        return view('transportadoras', ['dados' => $dadosFormatados]);
    }
}
