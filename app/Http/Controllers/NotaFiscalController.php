<?php

namespace App\Http\Controllers;

use App\Models\NotaFiscal;
use Illuminate\Http\Request;

class NotaFiscalController extends Controller
{
    // Mostra os detalhes de uma nota fiscal
    public function show($id)
    {
        $nota = NotaFiscal::findOrFail($id);

        // Parseia o XML
        $xml = simplexml_load_string($nota->xml_conteudo);
        $xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');

        $infNFe = $xml->NFe->infNFe;

        // Dados gerais da NF
        $dadosNF = [
            'id' => $nota->id,
            'nome_arquivo' => $nota->nome_arquivo,
            'vNF' => (float) ($infNFe->total->ICMSTot->vNF ?? 0),
            'vICMS' => (float) ($infNFe->total->ICMSTot->vICMS ?? 0),
            'vIPI' => (float) ($infNFe->total->ICMSTot->vIPI ?? 0),
            'vPIS' => (float) ($infNFe->total->ICMSTot->vPIS ?? 0),
            'vCOFINS' => (float) ($infNFe->total->ICMSTot->vCOFINS ?? 0),
            'total_produtos' => 0,
            'produtos' => [],
            'frete' => [
                'modFrete' => (string) ($infNFe->transp->modFrete ?? '0'),
                'transportadora' => null,
            ],
        ];

        // Transportadora, se existir
        if (!empty($infNFe->transp->transporta)) {
            $transp = $infNFe->transp->transporta;
            $dadosNF['frete']['transportadora'] = [
                'CNPJ' => (string) ($transp->CNPJ ?? ''),
                'CPF' => (string) ($transp->CPF ?? ''),
                'xNome' => (string) ($transp->xNome ?? ''),
                'IE' => (string) ($transp->IE ?? ''),
                'xEnder' => (string) ($transp->xEnder ?? ''),
                'xMun' => (string) ($transp->xMun ?? ''),
                'UF' => (string) ($transp->UF ?? ''),
            ];
        }

        // Produtos da NF
        foreach ($infNFe->det as $det) {
            $produto = [
                'cProd' => (string) ($det->prod->cProd ?? ''),
                'xProd' => (string) ($det->prod->xProd ?? ''),
                'qCom' => (float) ($det->prod->qCom ?? 0),
                'vUnCom' => (float) ($det->prod->vUnCom ?? 0),
                'vProd' => (float) ($det->prod->vProd ?? 0),
            ];

            $dadosNF['total_produtos'] += $produto['vProd'];
            $dadosNF['produtos'][] = $produto;
        }

        return view('show', compact('nota', 'dadosNF'));
    }

    // Nova função: lista detalhada de impostos
    public function impostos()
    {
        $notas = NotaFiscal::all();
        $listaImpostos = [];
        $totalImpostos = 0;

        foreach ($notas as $nota) {
            $xml = simplexml_load_string($nota->xml_conteudo);
            $xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
            $infNFe = $xml->NFe->infNFe;

            $vICMS = (float) ($infNFe->total->ICMSTot->vICMS ?? 0);
            $vIPI = (float) ($infNFe->total->ICMSTot->vIPI ?? 0);
            $vPIS = (float) ($infNFe->total->ICMSTot->vPIS ?? 0);
            $vCOFINS = (float) ($infNFe->total->ICMSTot->vCOFINS ?? 0);

            $totalNota = $vICMS + $vIPI + $vPIS + $vCOFINS;
            $totalImpostos += $totalNota;

            $listaImpostos[] = [
                'id' => $nota->id,
                'nome_arquivo' => $nota->nome_arquivo,
                'vICMS' => $vICMS,
                'vIPI' => $vIPI,
                'vPIS' => $vPIS,
                'vCOFINS' => $vCOFINS,
                'total' => $totalNota,
            ];
        }

        // Ordena notas por total de impostos decrescente
        usort($listaImpostos, fn($a, $b) => $b['total'] <=> $a['total']);

        return view('impostos', compact('listaImpostos', 'totalImpostos'));
    }
}
