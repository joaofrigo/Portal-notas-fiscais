<?php

$inputDir  = __DIR__ . '/.';
$outputDir = __DIR__ . '/xml_anonimizados';

echo "Input dir: $inputDir\n";
echo "Output dir: $outputDir\n";

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
    echo "Diretório de saída criado.\n";
}

$files = glob($inputDir . '/*.xml');

echo "Arquivos encontrados: " . count($files) . "\n\n";

foreach ($files as $filePath) {

    echo "--------------------------------------------------------\n";
    echo "Processando arquivo: $filePath\n";

    $xmlContent = file_get_contents($filePath);
    if ($xmlContent === false) {
        echo "ERRO: não conseguiu ler o arquivo!\n";
        continue;
    }

    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->preserveWhiteSpace = false;
    $xml->formatOutput = true;

    if (!$xml->loadXML($xmlContent)) {
        echo "ERRO: XML inválido!\n";
        continue;
    }

    echo "XML carregado com sucesso.\n";

    $xpath = new DOMXPath($xml);

    // Lista de tags sensíveis a serem anonimizadas
    $sensitiveTags = [
        'CPF',
        'CNPJ',
        'xNome',
        'xFant',
        'xLgr',
        'xBairro',
        'xCpl',
        'nro',
        'fone',
        'email',
        'CEP', 
        'RazaoSocialPrestador',
        'RazaoSocialTomador',
        'Logradouro',
        'NumeroEndereco',
        'ComplementoEndereco',
        'Bairro',
        'Cidade',
        'UF',
        'InscricaoPrestador',
        'NumeroNFe',
        'CodigoVerificacao',
        'NumeroRPS',
        'SerieRPS',
    ];

    foreach ($sensitiveTags as $tag) {

        $query = "//*[local-name()='{$tag}']";
        $nodes = $xpath->query($query);

        if ($nodes === false) {
            echo "ERRO no XPath para tag {$tag}\n";
            continue;
        }

        echo "Tag {$tag}: encontrados " . $nodes->length . " nós\n";

        foreach ($nodes as $node) {
            if ($tag === 'CPF' || $tag === 'CNPJ') {
                $node->nodeValue = 'REMOVIDO';
            } else {
                $node->nodeValue = 'ANONIMO';
            }
        }
    }

    // Blocos de endereço
    $addressBlocks = ['enderDest', 'enderEmit', 'enderReme', 'enderExped', 'EnderecoPrestador','EnderecoTomador'];

    foreach ($addressBlocks as $block) {

        $query = "//*[local-name()='{$block}']/*";
        $nodes = $xpath->query($query);

        if ($nodes === false) {
            echo "ERRO no XPath para bloco {$block}\n";
            continue;
        }

        echo "Bloco {$block}: " . $nodes->length . " elementos filhos encontrados\n";

        foreach ($nodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $child->nodeValue = 'ANONIMO';
            }
        }
    }

    // Grava arquivo anonimizado
    $filename = basename($filePath);
    $saved    = $xml->save($outputDir . '/' . $filename);

    if ($saved === false) {
        echo "ERRO: não conseguiu salvar o arquivo!\n";
    } else {
        echo "Anonimizado e salvo como: $filename ({$saved} bytes)\n";
    }

    echo "--------------------------------------------------------\n\n";
}

echo "Processo concluído.\n";
