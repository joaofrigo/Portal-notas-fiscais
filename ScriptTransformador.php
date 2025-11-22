<?php
/**
 * transform_options.php
 *
 * CLI tool with 6 options (only D and E implemented).
 *
 * Usage:
 *   php transform_options.php --option=<A|B|C|D|E|ALL> [--input=path] [--output=path] [--help]
 *
 * Defaults:
 *   input  = ./xmls_anonimizados
 *   output = ./out_transforms
 *
 * Notes:
 * - Only XML outputs are produced (user did not choose JSON).
 * - Option D: per-NF XML with products ordered alphabetically by prod/xProd.
 * - Option E: single aggregated XML (one NF) with all products from all NFs ordered by unit price ascending.
 * - Other options (A, B, C, ALL) are placeholders.
 *
 * Important: reordering invalidates digital signatures. Script preserves structural Signature element
 * for DTD conformity but does not attempt to resign documents.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$options = getopt('', ['option::', 'input::', 'output::', 'help::']);

if (isset($options['help']) || !isset($options['option'])) {
    echo "Uso: php transform_options.php --option=<A|B|C|D|E|ALL> [--input=path] [--output=path]\n";
    echo "Exemplo: php transform_options.php --option=D --input=./nfs --output=./out\n";
    exit(0);
}

$option = strtoupper($options['option']);
$inputDir = $options['input'] ?? (__DIR__ . DIRECTORY_SEPARATOR . 'xmls_anonimizados');
$outputDir = $options['output'] ?? __DIR__ . DIRECTORY_SEPARATOR . 'out_transforms';

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

function domNodeToArray(DOMNode $node) {
    $output = [];

    if ($node->nodeType === XML_TEXT_NODE) {
        return trim($node->textContent);
    }

    if ($node->hasAttributes()) {
        foreach ($node->attributes as $attr) {
            $output['@' . $attr->nodeName] = $attr->nodeValue;
        }
    }

    foreach ($node->childNodes as $child) {
        $value = domNodeToArray($child);
        if ($child->nodeType === XML_TEXT_NODE) {
            if ($value !== '') {
                $output['#text'] = $value;
            }
        } else {
            $output[$child->nodeName][] = $value;
        }
    }

    return $output;
}

function loadDomFile(string $file) : ?DOMDocument {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    if (!$dom->load($file)) {
        // optionally log errors
        libxml_clear_errors();
        return null;
    }
    libxml_clear_errors();
    return $dom;
}

function getTextContentByPath(DOMXPath $xpath, DOMElement $context, string $path, array $nsmap = []) {
    // $path uses 'nfe:prod/nfe:vUnTrib' style; caller must register prefixes in $nsmap
    foreach ($nsmap as $p => $uri) {
        $xpath->registerNamespace($p, $uri);
    }
    $nodes = $xpath->query($path, $context);
    if ($nodes === false || $nodes->length === 0) return null;
    return trim($nodes->item(0)->textContent);
}

function parseNumber($s): ?float {
    if ($s === null) return null;
    $s = trim((string)$s);
    if ($s === '') return null;
    $s = str_replace(',', '.', $s);
    if (!is_numeric($s)) return null;
    return floatval($s);
}

function handleOptionA(string $inputDir, string $outputDir) {

    // diretório de saída para JSON
    $outJsonDir = rtrim($outputDir, DIRECTORY_SEPARATOR)
                   . DIRECTORY_SEPARATOR
                   . 'JsonNotaFiscal';

    if (!is_dir($outJsonDir)) {
        mkdir($outJsonDir, 0777, true);
    }

    $files = glob(rtrim($inputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.xml');
    if (!$files) {
        echo "A: nenhum .xml encontrado em {$inputDir}\n";
        return;
    }

    foreach ($files as $file) {

        echo "A: convertendo {$file}\n";

        $dom = loadDomFile($file);
        if (!$dom) {
            echo " A: falha ao carregar XML. Pulando.\n";
            continue;
        }

        // conversão XML → array → json
        $array = domNodeToArray($dom->documentElement);
        $json = json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // gerar nome limpo baseado no próprio XML
        $base = pathinfo($file, PATHINFO_FILENAME);
        $outFile = $outJsonDir . DIRECTORY_SEPARATOR . $base . '.json';

        file_put_contents($outFile, $json);

        echo " A: gerado {$outFile}\n";
    }
}

function handleOptionB(string $inputDir, string $outputDir, string $NAMESPACE_NFE) {

    // diretório de saída para XMLs de produtos
    $outProdDir = rtrim($outputDir, DIRECTORY_SEPARATOR)
                    . DIRECTORY_SEPARATOR
                    . 'NotaFiscalApenasProdutos';

    if (!is_dir($outProdDir)) {
        mkdir($outProdDir, 0777, true);
    }

    $files = glob(rtrim($inputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.xml');
    if (!$files) {
        echo "B: nenhum .xml encontrado em {$inputDir}\n";
        return;
    }

    foreach ($files as $file) {

        echo "B: processando {$file}\n";

        $dom = loadDomFile($file);
        if (!$dom) {
            echo " B: falha ao carregar XML. Pulando.\n";
            continue;
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('nfe', $NAMESPACE_NFE);

        $detList = $xpath->query('//nfe:infNFe/nfe:det');
        if ($detList === false || $detList->length === 0) {
            echo " B: nenhum det encontrado. Pulando.\n";
            continue;
        }

        // criar XML contendo apenas os produtos
        $outDom = new DOMDocument('1.0', 'UTF-8');
        $outDom->preserveWhiteSpace = false;
        $outDom->formatOutput = true;

        // raiz artificial
        $root = $outDom->createElementNS($NAMESPACE_NFE, 'ProdutosNF');
        $root->setAttribute('versao', '4.00');
        $outDom->appendChild($root);

        foreach ($detList as $d) {
            $root->appendChild($outDom->importNode($d, true));
        }

        // nome baseado no arquivo
        $base = pathinfo($file, PATHINFO_FILENAME);
        $outFile = $outProdDir . DIRECTORY_SEPARATOR . $base . '_produtos.xml';

        $outDom->save($outFile);

        echo " B: gerado {$outFile}\n";
    }
}

function handleOptionC(string $inputDir, string $outputDir, string $NAMESPACE_NFE) {

    // Diretório específico para saída
    $outAllDir = rtrim($outputDir, DIRECTORY_SEPARATOR)
                  . DIRECTORY_SEPARATOR
                  . 'TODOS_produtos';

    if (!is_dir($outAllDir)) {
        mkdir($outAllDir, 0777, true);
    }

    $files = glob(rtrim($inputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.xml');
    if (!$files) {
        echo "C: nenhum .xml encontrado em {$inputDir}\n";
        return;
    }

    $items = [];
    $totalFound = 0;

    foreach ($files as $file) {

        echo "C: lendo {$file}\n";

        $dom = loadDomFile($file);
        if (!$dom) {
            echo " C: falha ao carregar XML. Pulando.\n";
            continue;
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('nfe', $NAMESPACE_NFE);

        // capturar nodes <det>
        $detList = $xpath->query('//nfe:infNFe/nfe:det');
        if ($detList === false || $detList->length === 0) {
            continue;
        }

        foreach ($detList as $d) {
            $items[] = $d;
            $totalFound++;
        }
    }

    if ($totalFound === 0) {
        echo "C: nenhum produto encontrado.\n";
        return;
    }

    echo "C: total de produtos coletados: {$totalFound}\n";

    // Criar XML final contendo apenas dets
    $outDom = new DOMDocument('1.0', 'UTF-8');
    $outDom->preserveWhiteSpace = false;
    $outDom->formatOutput = true;

    // raiz artificial para armazenar todos os dets
    $root = $outDom->createElementNS($NAMESPACE_NFE, 'TodosProdutos');
    $root->setAttribute('versao', '4.00');
    $outDom->appendChild($root);

    $index = 1;

    foreach ($items as $d) {
        $detImported = $outDom->importNode($d, true);

        // garantir numerador nItem consistente
        if ($detImported instanceof DOMElement) {
            $detImported->setAttribute('nItem', (string)$index);
        }

        $root->appendChild($detImported);
        $index++;
    }

    // Nome final
    $outFile = $outAllDir . DIRECTORY_SEPARATOR . "todos_produtos.xml";
    $outDom->save($outFile);

    echo "C: arquivo gerado: {$outFile}\n";
}


// ------------------------- Option handlers -------------------------

/**
 * Option D: per-NF: recreate XML for each NF with <det> sorted by prod/xProd (alphabetical).
 */
/* ------------------ Option D: per-NF alphabetical (one output per input NF) ------------------ */
function handleOptionD(string $inputDir, string $outputDir, string $NAMESPACE_NFE, string $XMLDSIG_NS) {

    // Diretório específico para saída da opção D
    $outAlphaDir = rtrim($outputDir, DIRECTORY_SEPARATOR)
                   . DIRECTORY_SEPARATOR
                   . 'NF_ordem_alfabetica_produtos';

    if (!is_dir($outAlphaDir)) {
        mkdir($outAlphaDir, 0777, true);
    }

    $files = glob(rtrim($inputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.xml');
    if (!$files) {
        echo "D: nenhum .xml encontrado em {$inputDir}\n";
        return;
    }

    $processed = 0;
    $skipped = 0;

    foreach ($files as $file) {

        echo "D: processando {$file}\n";
        $dom = loadDomFile($file);
        if (!$dom) {
            echo " D: falha ao carregar XML. Pulando.\n";
            $skipped++;
            continue;
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('nfe', $NAMESPACE_NFE);

        $infNFeNodes = $xpath->query('//nfe:infNFe');
        if ($infNFeNodes === false || $infNFeNodes->length === 0) {
            echo " D: infNFe não encontrado. Pulando.\n";
            $skipped++;
            continue;
        }

        foreach ($infNFeNodes as $infNFe) {
            /** @var DOMElement $infNFe */
            $idAttr = $infNFe->getAttribute('Id') ?: 'unknown';
            $chave = preg_replace('/^NFe/i', '', $idAttr);

            // Contagem de produtos
            $detList = $xpath->query('./nfe:det', $infNFe);
            $detCount = ($detList === false) ? 0 : $detList->length;
            if ($detCount < 2) {
                echo " D: NF {$chave} tem menos que 2 produtos (detCount={$detCount}). Pulando.\n";
                $skipped++;
                continue;
            }

            // Lista de itens
            $items = [];
            foreach ($detList as $d) {
                $xProd = getTextContentByPath($xpath, $d, './nfe:prod/nfe:xProd', ['nfe' => $NAMESPACE_NFE]) ?? '';
                $items[] = ['node' => $d, 'xProd' => $xProd];
            }

            // Ordenação alfabética por nome do produto
            usort($items, function ($a, $b) {
                $cmp = strcasecmp($a['xProd'], $b['xProd']);
                return $cmp !== 0 ? $cmp : strcmp($a['xProd'], $b['xProd']);
            });

            // Novo documento XML
            $outDom = new DOMDocument('1.0', 'UTF-8');
            $outDom->preserveWhiteSpace = false;
            $outDom->formatOutput = true;

            $rootOut = $outDom->createElementNS($NAMESPACE_NFE, 'nfeProc');

            $origRoot = $dom->documentElement;
            if ($origRoot && $origRoot->hasAttribute('versao')) {
                $rootOut->setAttribute('versao', $origRoot->getAttribute('versao'));
            } else {
                $rootOut->setAttribute('versao', '4.00');
            }

            $outDom->appendChild($rootOut);

            $nfeOut = $outDom->createElementNS($NAMESPACE_NFE, 'NFe');
            $rootOut->appendChild($nfeOut);

            $infNFeOut = $outDom->importNode($infNFe, false);
            $nfeOut->appendChild($infNFeOut);

            // Agrupamento dos filhos originais
            $childrenByName = [];
            foreach ($infNFe->childNodes as $c) {
                if ($c instanceof DOMElement) {
                    $childrenByName[$c->localName][] = $c;
                }
            }

            $order = [
                'ide', 'emit', 'dest', 'entrega', 'det', 'total',
                'transp', 'cobr', 'pag', 'infIntermed',
                'infAdic', 'compra', 'infRespTec'
            ];

            foreach ($order as $name) {

                if ($name === 'det') {
                    $index = 1;

                    foreach ($items as $it) {

                        $detImported = $outDom->importNode($it['node'], true);

                        if ($detImported instanceof DOMElement) {
                            $detImported->setAttribute('nItem', (string)$index);
                        }

                        $infNFeOut->appendChild($detImported);
                        $index++;
                    }
                    continue;
                }

                if (!isset($childrenByName[$name])) {
                    continue;
                }

                foreach ($childrenByName[$name] as $orig) {
                    $infNFeOut->appendChild($outDom->importNode($orig, true));
                }
            }

            // Signature
            $signature = null;
            $parentNFe = $infNFe->parentNode;

            if ($parentNFe) {
                foreach ($parentNFe->childNodes as $sib) {
                    if ($sib instanceof DOMElement && $sib->localName === 'Signature') {
                        $signature = $sib;
                        break;
                    }
                }
            }

            if ($signature) {
                $nfeOut->appendChild($outDom->importNode($signature, true));
            } else {
                $nfeOut->appendChild($outDom->createElementNS($XMLDSIG_NS, 'Signature'));
            }

            // protNFe
            $protNodes = $xpath->query('//nfe:protNFe');
            if ($protNodes && $protNodes->length > 0) {
                foreach ($protNodes as $p) {
                    $outDom->documentElement->appendChild($outDom->importNode($p, true));
                }
            }

            // Nome do arquivo
            $safeChave = preg_replace('/[^0-9A-Za-z_-]/', '_', $chave);
            $outFile = $outAlphaDir . DIRECTORY_SEPARATOR . "nfe-{$safeChave}-sorted-alpha.xml";

            $outDom->save($outFile);

            echo " D: gerado {$outFile}\n";
            $processed++;
        }
    }

    echo "\nD: resumo - processadas={$processed}, puladas={$skipped}\n";
}


/**
 * Option E: aggregate ALL products from all NFs into a single XML (esquema da Receita)
 * with products ordered by unit price ascending.
 */
function handleOptionE(string $inputDir, string $outputDir, string $NAMESPACE_NFE, string $XMLDSIG_NS) {
    $files = glob(rtrim($inputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.xml');
    if (!$files) {
        echo "E: nenhum .xml encontrado em {$inputDir}\n";
        return;
    }

    $allItems = []; // ['det' => DOMElement, 'price' => float, 'source' => filename]
    $firstInfNFe = null;
    $firstDom = null;
    $firstProt = null;

    foreach ($files as $file) {
        echo "E: lendo {$file}\n";
        $dom = loadDomFile($file);
        if (!$dom) { echo " E: falha ao carregar XML. Pulando.\n"; continue; }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('nfe', $NAMESPACE_NFE);

        $infNodes = $xpath->query('//nfe:infNFe');
        if ($infNodes === false || $infNodes->length === 0) {
            echo " E: infNFe não encontrado em {$file}. Pulando.\n";
            continue;
        }

        if ($firstInfNFe === null) {
            $firstInfNFe = $infNodes->item(0);
            $firstDom = $dom;
            $prot = $xpath->query('//nfe:protNFe');
            if ($prot && $prot->length > 0) $firstProt = $prot->item(0);
        }

        // coletar todos os dets neste arquivo (independente de quantos por NF)
        $detNodes = $xpath->query('//nfe:det');
        if ($detNodes === false) continue;
        foreach ($detNodes as $d) {
            // extrair preço unitário: vUnTrib > vUnCom > fallback(vProd / qCom)
            $vUnTrib = getTextContentByPath($xpath, $d, './nfe:prod/nfe:vUnTrib', ['nfe'=>$NAMESPACE_NFE]);
            $vUnCom = getTextContentByPath($xpath, $d, './nfe:prod/nfe:vUnCom', ['nfe'=>$NAMESPACE_NFE]);
            $vProd  = getTextContentByPath($xpath, $d, './nfe:prod/nfe:vProd',  ['nfe'=>$NAMESPACE_NFE]);
            $qCom   = getTextContentByPath($xpath, $d, './nfe:prod/nfe:qCom',   ['nfe'=>$NAMESPACE_NFE]);

            $price = null;
            if ($vUnTrib !== null && $vUnTrib !== '') {
                $price = parseNumber($vUnTrib);
            } elseif ($vUnCom !== null && $vUnCom !== '') {
                $price = parseNumber($vUnCom);
            } elseif ($vProd !== null && $vProd !== '' && $qCom !== null && $qCom !== '') {
                $numV = parseNumber($vProd);
                $numQ = parseNumber($qCom);
                if ($numV !== null && $numQ !== null && $numQ > 0) $price = $numV / $numQ;
            }
            if ($price === null) $price = 0.0;

            $allItems[] = ['det' => $d, 'price' => $price, 'source' => $file];
        }
    }

    if (count($allItems) === 0) {
        echo "E: nenhum produto encontrado em todas as NFs.\n";
        return;
    }

    // ordenar por price asc
    usort($allItems, function($a, $b) {
        if ($a['price'] == $b['price']) return 0;
        return ($a['price'] < $b['price']) ? -1 : 1;
    });

    // construir documento de saída baseado no firstInfNFe (headers copiados)
    $outDom = new DOMDocument('1.0','UTF-8');
    $outDom->preserveWhiteSpace = false;
    $outDom->formatOutput = true;

    $rootOut = $outDom->createElementNS($NAMESPACE_NFE, 'nfeProc');
    $rootOut->setAttribute('versao', '4.00');
    $outDom->appendChild($rootOut);

    $nfeOut = $outDom->createElementNS($NAMESPACE_NFE, 'NFe');
    $rootOut->appendChild($nfeOut);

    // importar infNFe base sem filhos e ajustar Id
    $infBase = $firstInfNFe;
    $infOut = $outDom->importNode($infBase, false);
    if ($infOut instanceof DOMElement) {
        $newId = 'NFeAGG' . gmdate('YmdHis');
        $infOut->setAttribute('Id', $newId);
    }
    $nfeOut->appendChild($infOut);

    // mapear filhos originais da base por nome
    $childrenByName = [];
    foreach ($infBase->childNodes as $c) {
        if (!($c instanceof DOMElement)) continue;
        $childrenByName[$c->localName][] = $c;
    }
    $order = ['ide','emit','dest','entrega','det','total','transp','cobr','pag','infIntermed','infAdic','compra','infRespTec'];

    foreach ($order as $name) {
        if ($name === 'det') {
            $index = 1;
            foreach ($allItems as $it) {
                $detImported = $outDom->importNode($it['det'], true);
                if ($detImported instanceof DOMElement) {
                    $detImported->setAttribute('nItem', (string)$index);
                } else {
                    foreach ($detImported->childNodes as $child) {
                        if ($child instanceof DOMElement && $child->localName === 'det') {
                            $child->setAttribute('nItem', (string)$index);
                            break;
                        }
                    }
                }
                $infOut->appendChild($detImported);
                $index++;
            }
            continue;
        }

        if (!isset($childrenByName[$name])) continue;
        foreach ($childrenByName[$name] as $orig) {
            $imp = $outDom->importNode($orig, true);
            $infOut->appendChild($imp);
        }
    }

    // Signature placeholder
    $sigEl = $outDom->createElementNS($XMLDSIG_NS, 'Signature');
    $nfeOut->appendChild($sigEl);

    // protNFe from first doc if present (structural only)
    if ($firstProt !== null) {
        $outDom->documentElement->appendChild($outDom->importNode($firstProt, true));
    }

    $outFile = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "nfe-aggregated-all-products-sorted-by-price.xml";
    $outDom->save($outFile);
    echo "E: gerado aggregated XML (price asc): {$outFile}\n";
    echo "header/totais/impostos são copiados da primeira NF; não foram recalculados.\n";
}

// ------------------------- Main dispatch -------------------------

switch ($option) {

    case 'A':
        handleOptionA($inputDir, $outputDir);
        break;

    case 'B':
        handleOptionB($inputDir, $outputDir, $NAMESPACE_NFE);
        break;

    case 'C':
        handleOptionC($inputDir, $outputDir, $NAMESPACE_NFE);
        break;

    case 'D':
        handleOptionD($inputDir, $outputDir, $NAMESPACE_NFE, $XMLDSIG_NS);
        break;

    case 'E':
        handleOptionE($inputDir, $outputDir, $NAMESPACE_NFE, $XMLDSIG_NS);
        break;

    case 'F':
        handleOptionA($inputDir, $outputDir);
        handleOptionB($inputDir, $outputDir, $NAMESPACE_NFE);
        handleOptionC($inputDir, $outputDir, $NAMESPACE_NFE);
        handleOptionD($inputDir, $outputDir, $NAMESPACE_NFE, $XMLDSIG_NS);
        handleOptionE($inputDir, $outputDir, $NAMESPACE_NFE, $XMLDSIG_NS);
        break;

    default:
        echo "Opção inválida.\n";
}


exit(0);
