<?php
/**
 * transform_options.php
 *
 * CLI tool with 5 main options (A–E). Only XML outputs are produced by default.
 *
 * Usage:
 *   php transform_options.php --option=<A|B|C|D|E|ALL> [--input=path] [--output=path] [--help]
 *
 * Defaults:
 *   input  = ./xmls_anonimizados
 *   output = ./out_transforms
 *
 * Notes:
 * - Option A: per-NF XML → JSON
 * - Option B: per-NF XML/JSON with only product data
 * - Option C: single XML/JSON with all products from all NFs
 * - Option D: per-NF XML with products ordered alphabetically
 * - Option E: single XML with all products ordered by price ascending
 *
 * Important: reordering invalidates digital signatures.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$options = getopt('', ['option::', 'input::', 'output::', 'help::']);

if (isset($options['help']) || !isset($options['option'])) {
    echo "Uso: php transform_options.php --option=<A|B|C|D|E|ALL> [--input=path] [--output=path]\n";
    exit(0);
}

$option = strtoupper($options['option']);
$inputDir = $options['input'] ?? (__DIR__ . DIRECTORY_SEPARATOR . 'xmls_anonimizados');
$outputDir = $options['output'] ?? (__DIR__ . DIRECTORY_SEPARATOR . 'out_transforms');

if (!is_dir($inputDir)) {
    fwrite(STDERR, "Diretório de entrada não existe: {$inputDir}\n");
    exit(1);
}
if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true)) {
    fwrite(STDERR, "Falha ao criar diretório de saída: {$outputDir}\n");
    exit(1);
}

$NAMESPACE_NFE = 'http://www.portalfiscal.inf.br/nfe';
$XMLDSIG_NS = 'http://www.w3.org/2000/09/xmldsig#';

/**
 * Converte um nó DOM (e seus filhos) em um array PHP.
 * Inclui atributos (@), texto (#text) e elementos repetidos.
 * Útil para gerar JSON.
 */
function domNodeToArray(DOMNode $node) {
    $output = [];

    if ($node->nodeType === XML_TEXT_NODE) {
        return trim($node->textContent);
    }

    if ($node instanceof DOMElement && $node->hasAttributes()) {
        foreach ($node->attributes as $attr) {
            $output['@' . $attr->nodeName] = $attr->nodeValue;
        }
    }

    foreach ($node->childNodes as $child) {
        $value = domNodeToArray($child);
        if ($child->nodeType === XML_TEXT_NODE) {
            if ($value !== '') $output['#text'] = $value;
        } else {
            $output[$child->nodeName][] = $value;
        }
    }

    return $output;
}

/** Carrega XML em DOMDocument com tratamento de erros */
function loadDomFile(string $file) : ?DOMDocument {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    if (!$dom->load($file)) {
        libxml_clear_errors();
        return null;
    }
    libxml_clear_errors();
    return $dom;
}

/** Consulta XPath e retorna conteúdo do primeiro nó ou null */
function getTextContentByPath(DOMXPath $xpath, DOMElement $context, string $path, array $nsmap = []) {
    foreach ($nsmap as $p => $uri) {
        $xpath->registerNamespace($p, $uri);
    }
    $nodes = $xpath->query($path, $context);
    if ($nodes === false || $nodes->length === 0) return null;
    return trim($nodes->item(0)->textContent);
}

/** Converte string numérica em float seguro */
function parseNumber($s): ?float {
    if ($s === null) return null;
    $s = trim((string)$s);
    if ($s === '') return null;
    $s = str_replace(',', '.', $s);
    if (!is_numeric($s)) return null;
    return floatval($s);
}

// ------------------------- Option Handlers -------------------------

/**
 * A: Para cada NF em XML, gerar uma NF em JSON equivalente
 */
function handleOptionA(string $inputDir, string $outputDir) {
    $outJsonDir = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'JsonNotaFiscal';
    if (!is_dir($outJsonDir)) mkdir($outJsonDir, 0777, true);

    $files = glob(rtrim($inputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.xml');
    if (!$files) return;

    foreach ($files as $file) {
        $dom = loadDomFile($file);
        if (!$dom) continue;

        $array = domNodeToArray($dom->documentElement);
        $json = json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $base = pathinfo($file, PATHINFO_FILENAME);
        $outFile = $outJsonDir . DIRECTORY_SEPARATOR . $base . '.json';
        file_put_contents($outFile, $json);
    }
}

/**
 * B: Para cada NF, gerar XML contendo apenas produtos
 */
function handleOptionB(string $inputDir, string $outputDir, string $NAMESPACE_NFE) {
    $outProdDir = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'NotaFiscalApenasProdutos';
    if (!is_dir($outProdDir)) mkdir($outProdDir, 0777, true);

    $files = glob(rtrim($inputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.xml');
    if (!$files) return;

    foreach ($files as $file) {
        $dom = loadDomFile($file);
        if (!$dom) continue;

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('nfe', $NAMESPACE_NFE);

        $detList = $xpath->query('//nfe:infNFe/nfe:det');
        if (!$detList || $detList->length === 0) continue;

        $outDom = new DOMDocument('1.0', 'UTF-8');
        $outDom->preserveWhiteSpace = false;
        $outDom->formatOutput = true;

        $root = $outDom->createElementNS($NAMESPACE_NFE, 'ProdutosNF');
        $root->setAttribute('versao', '4.00');
        $outDom->appendChild($root);

        foreach ($detList as $d) {
            $root->appendChild($outDom->importNode($d, true));
        }

        $base = pathinfo($file, PATHINFO_FILENAME);
        $outFile = $outProdDir . DIRECTORY_SEPARATOR . $base . '_produtos.xml';
        $outDom->save($outFile);
    }
}

/**
 * C: Gerar um único XML contendo todos os produtos de todas as NFs
 */
function handleOptionC(string $inputDir, string $outputDir, string $NAMESPACE_NFE) {
    $outAllDir = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'TODOS_produtos';
    if (!is_dir($outAllDir)) mkdir($outAllDir, 0777, true);

    $files = glob(rtrim($inputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.xml');
    if (!$files) return;

    $items = [];
    foreach ($files as $file) {
        $dom = loadDomFile($file);
        if (!$dom) continue;

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('nfe', $NAMESPACE_NFE);

        $detList = $xpath->query('//nfe:infNFe/nfe:det');
        if (!$detList || $detList->length === 0) continue;

        foreach ($detList as $d) $items[] = $d;
    }

    if (!$items) return;

    $outDom = new DOMDocument('1.0', 'UTF-8');
    $outDom->preserveWhiteSpace = false;
    $outDom->formatOutput = true;

    $root = $outDom->createElementNS($NAMESPACE_NFE, 'TodosProdutos');
    $root->setAttribute('versao', '4.00');
    $outDom->appendChild($root);

    $index = 1;
    foreach ($items as $d) {
        $detImported = $outDom->importNode($d, true);
        if ($detImported instanceof DOMElement) $detImported->setAttribute('nItem', (string)$index);
        $root->appendChild($detImported);
        $index++;
    }

    $outFile = $outAllDir . DIRECTORY_SEPARATOR . "todos_produtos.xml";
    $outDom->save($outFile);
}

/**
 * D: Para cada NF, gerar XML seguindo esquema da Receita com produtos ordenados alfabeticamente
 */
function handleOptionD(string $inputDir, string $outputDir, string $NAMESPACE_NFE, string $XMLDSIG_NS) {
    $outAlphaDir = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'NF_ordem_alfabetica_produtos';
    if (!is_dir($outAlphaDir)) mkdir($outAlphaDir, 0777, true);

    $files = glob(rtrim($inputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.xml');
    if (!$files) return;

    foreach ($files as $file) {
        $dom = loadDomFile($file);
        if (!$dom) continue;

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('nfe', $NAMESPACE_NFE);

        $infNFeNodes = $xpath->query('//nfe:infNFe');
        if (!$infNFeNodes || $infNFeNodes->length === 0) continue;

        foreach ($infNFeNodes as $infNFe) {
            $detList = $xpath->query('./nfe:det', $infNFe);
            if ($detList === false || $detList->length < 2) continue;

            $items = [];
            foreach ($detList as $d) {
                $xProd = getTextContentByPath($xpath, $d, './nfe:prod/nfe:xProd', ['nfe' => $NAMESPACE_NFE]) ?? '';
                $items[] = ['node'=>$d,'xProd'=>$xProd];
            }

            usort($items, fn($a,$b)=>strcasecmp($a['xProd'],$b['xProd']));

            $outDom = new DOMDocument('1.0','UTF-8');
            $outDom->preserveWhiteSpace = false;
            $outDom->formatOutput = true;

            $rootOut = $outDom->createElementNS($NAMESPACE_NFE,'nfeProc');
            $rootOut->setAttribute('versao',$dom->documentElement->getAttribute('versao') ?: '4.00');
            $outDom->appendChild($rootOut);

            $nfeOut = $outDom->createElementNS($NAMESPACE_NFE,'NFe');
            $rootOut->appendChild($nfeOut);

            $infNFeOut = $outDom->importNode($infNFe,false);
            $nfeOut->appendChild($infNFeOut);

            $index = 1;
            foreach ($items as $it) {
                $detImported = $outDom->importNode($it['node'],true);
                if ($detImported instanceof DOMElement) $detImported->setAttribute('nItem',(string)$index++);
                $infNFeOut->appendChild($detImported);
            }

            $sigEl = $outDom->createElementNS($XMLDSIG_NS,'Signature');
            $nfeOut->appendChild($sigEl);

            $base = pathinfo($file, PATHINFO_FILENAME);
            $outFile = $outAlphaDir . DIRECTORY_SEPARATOR . "nfe-{$base}-sorted-alpha.xml";
            $outDom->save($outFile);
        }
    }
}

/**
 * E: Gerar um único XML com todos produtos de todas NFs, ordenados por preço crescente
 */
function handleOptionE(string $inputDir, string $outputDir, string $NAMESPACE_NFE, string $XMLDSIG_NS) {
    $files = glob(rtrim($inputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.xml');
    if (!$files) return;

    $allItems = [];
    $firstInfNFe = null;
    $firstDom = null;

    foreach ($files as $file) {
        $dom = loadDomFile($file);
        if (!$dom) continue;

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('nfe', $NAMESPACE_NFE);

        $infNodes = $xpath->query('//nfe:infNFe');
        if (!$infNodes || $infNodes->length === 0) continue;
        if ($firstInfNFe===null) $firstInfNFe=$infNodes->item(0);

        $detNodes = $xpath->query('//nfe:det');
        if ($detNodes===false) continue;

        foreach ($detNodes as $d) {
            $vUnTrib = getTextContentByPath($xpath,$d,'./nfe:prod/nfe:vUnTrib',['nfe'=>$NAMESPACE_NFE]);
            $vUnCom  = getTextContentByPath($xpath,$d,'./nfe:prod/nfe:vUnCom',['nfe'=>$NAMESPACE_NFE]);
            $vProd   = getTextContentByPath($xpath,$d,'./nfe:prod/nfe:vProd',['nfe'=>$NAMESPACE_NFE]);
            $qCom    = getTextContentByPath($xpath,$d,'./nfe:prod/nfe:qCom',['nfe'=>$NAMESPACE_NFE]);

            $price = parseNumber($vUnTrib) ?? parseNumber($vUnCom) ?? ((parseNumber($vProd)/parseNumber($qCom)) ?? 0.0);
            $allItems[]=['det'=>$d,'price'=>$price];
        }
    }

    if (!$allItems) return;

    usort($allItems, fn($a,$b)=>$a['price']<=>$b['price']);

    $outDom = new DOMDocument('1.0','UTF-8');
    $outDom->preserveWhiteSpace=false;
    $outDom->formatOutput=true;

    $rootOut=$outDom->createElementNS($NAMESPACE_NFE,'nfeProc');
    $rootOut->setAttribute('versao','4.00');
    $outDom->appendChild($rootOut);

    $nfeOut=$outDom->createElementNS($NAMESPACE_NFE,'NFe');
    $rootOut->appendChild($nfeOut);

    $infOut=$outDom->importNode($firstInfNFe,false);
    if ($infOut instanceof DOMElement) $infOut->setAttribute('Id','NFeAGG'.gmdate('YmdHis'));
    $nfeOut->appendChild($infOut);

    $index=1;
    foreach ($allItems as $it) {
        $detImported=$outDom->importNode($it['det'],true);
        if ($detImported instanceof DOMElement) $detImported->setAttribute('nItem',(string)$index++);
        $infOut->appendChild($detImported);
    }

    $sigEl=$outDom->createElementNS($XMLDSIG_NS,'Signature');
    $nfeOut->appendChild($sigEl);

    $outFile=rtrim($outputDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR."nfe-aggregated-all-products-sorted-by-price.xml";
    $outDom->save($outFile);
}

// ------------------------- Dispatch -------------------------
switch($option){
    case 'A': handleOptionA($inputDir,$outputDir); break;
    case 'B': handleOptionB($inputDir,$outputDir,$NAMESPACE_NFE); break;
    case 'C': handleOptionC($inputDir,$outputDir,$NAMESPACE_NFE); break;
    case 'D': handleOptionD($inputDir,$outputDir,$NAMESPACE_NFE,$XMLDSIG_NS); break;
    case 'E': handleOptionE($inputDir,$outputDir,$NAMESPACE_NFE,$XMLDSIG_NS); break;
    case 'ALL':
        handleOptionA($inputDir,$outputDir);
        handleOptionB($inputDir,$outputDir,$NAMESPACE_NFE);
        handleOptionC($inputDir,$outputDir,$NAMESPACE_NFE);
        handleOptionD($inputDir,$outputDir,$NAMESPACE_NFE,$XMLDSIG_NS);
        handleOptionE($inputDir,$outputDir,$NAMESPACE_NFE,$XMLDSIG_NS);
        break;
    default: echo "Opção inválida.\n"; exit(1);
}
exit(0);
