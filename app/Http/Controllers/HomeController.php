<?php

namespace App\Http\Controllers;

use App\Models\NotaFiscal;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $notasDB = NotaFiscal::all();

        $totalNFs = $notasDB->count();
        $totalProdutos = 0;
        $valorTotalNFs = 0;
        $valorTotalImpostos = 0;
        $totalItensProdutos = 0;
        $valorTotalProdutos = 0;

        $notas = [];

        foreach ($notasDB as $nota) {
            $xml = simplexml_load_string($nota->xml_conteudo);
            $xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');

            $infNFe = $xml->NFe->infNFe;

            // Valor total da NF
            $vNF = (float) ($infNFe->total->ICMSTot->vNF ?? 0);
            $valorTotalNFs += $vNF;

            // Impostos
            $vICMS = (float) ($infNFe->total->ICMSTot->vICMS ?? 0);
            $vPIS = (float) ($infNFe->total->ICMSTot->vPIS ?? 0);
            $vCOFINS = (float) ($infNFe->total->ICMSTot->vCOFINS ?? 0);
            $valorTotalImpostos += $vICMS + $vPIS + $vCOFINS;

            // Produtos da NF
            $totalProdutosNF = 0;
            $totalItensNF = 0;
            $valorProdutosNF = 0;

            foreach ($infNFe->det as $det) {
                $totalProdutos++; // total de produtos no sistema
                $totalItensProdutos += (float) ($det->prod->qCom ?? 0);
                $valorTotalProdutos += (float) ($det->prod->vProd ?? 0);

                $totalProdutosNF++;
                $totalItensNF += (float) ($det->prod->qCom ?? 0);
                $valorProdutosNF += (float) ($det->prod->vProd ?? 0);
            }

            $notas[] = [
                'id' => $nota->id,
                'nome_arquivo' => $nota->nome_arquivo,
                'vNF' => $vNF,
                'total_produtos' => $totalProdutosNF,
                'total_itens' => $totalItensNF,
                'valor_produtos' => $valorProdutosNF
            ];
        }

        return view('home', compact(
            'totalNFs',
            'totalProdutos',
            'valorTotalNFs',
            'valorTotalImpostos',
            'totalItensProdutos',
            'valorTotalProdutos',
            'notas'
        ));
    }
}
