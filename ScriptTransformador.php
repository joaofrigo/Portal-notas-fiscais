<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$options = getopt('', ['option::','input::','output::','help::']);
if(isset($options['help'])||!isset($options['option'])){
    echo "Uso: php transform_options.php --option=<A|B|C|D|E|ALL> [--input=path] [--output=path]\n";
    exit(0);
}

$option = strtoupper($options['option']);
$inputDir = $options['input'] ?? (__DIR__ . DIRECTORY_SEPARATOR . 'xmls_anonimizados');
$outputDir = $options['output'] ?? (__DIR__ . DIRECTORY_SEPARATOR . 'out_transforms');

if(!is_dir($inputDir)){ fwrite(STDERR,"Diretório de entrada não existe: {$inputDir}\n"); exit(1); }
if(!is_dir($outputDir)&&!mkdir($outputDir,0755,true)){ fwrite(STDERR,"Falha ao criar diretório de saída: {$outputDir}\n"); exit(1); }

$NAMESPACE_NFE = 'http://www.portalfiscal.inf.br/nfe';
$XMLDSIG_NS   = 'http://www.w3.org/2000/09/xmldsig#';

/** ----------------- Funções auxiliares ----------------- */

/** Cria diretório se não existir */
function ensureDir(string $dir){ if(!is_dir($dir)) mkdir($dir,0777,true); }

/** Cria novo DOMDocument pronto para uso */
function createDom(): DOMDocument {
    $dom = new DOMDocument('1.0','UTF-8');
    $dom->preserveWhiteSpace=false;
    $dom->formatOutput=true;
    return $dom;
}

/** Carrega XML em DOMDocument */
function loadDomFile(string $file): ?DOMDocument {
    libxml_use_internal_errors(true);
    $dom = createDom();
    if(!$dom->load($file)){ libxml_clear_errors(); return null; }
    libxml_clear_errors();
    return $dom;
}

/** Converte DOMNode em array (para JSON) */
function domNodeToArray(DOMNode $node){
    $output=[];
    if($node->nodeType===XML_TEXT_NODE) return trim($node->textContent);

    if($node instanceof DOMElement && $node->hasAttributes()){
        foreach($node->attributes as $attr) $output['@'.$attr->nodeName]=$attr->nodeValue;
    }

    foreach($node->childNodes as $child){
        $val=domNodeToArray($child);
        if($child->nodeType===XML_TEXT_NODE){
            if($val!=='') $output['#text']=$val;
        }else{
            $output[$child->nodeName][]=$val;
        }
    }
    return $output;
}

/** Retorna texto do primeiro nó XPath */
function getTextContentByPath(DOMXPath $xpath,DOMElement $context,string $path,array $nsmap=[]){
    foreach($nsmap as $p=>$uri) $xpath->registerNamespace($p,$uri);
    $nodes=$xpath->query($path,$context);
    return ($nodes && $nodes->length>0)?trim($nodes->item(0)->textContent):null;
}

/** Converte string numérica em float */
function parseNumber($s): ?float{
    if($s===null) return null;
    $s=str_replace(',','.',trim((string)$s));
    return is_numeric($s)?floatval($s):null;
}

/** Importa lista de <det> para DOM com nItem sequencial */
function importDetsToDom(DOMDocument $dom,DOMElement $parent,array $dets){
    $index=1;
    foreach($dets as $d){
        $det=$dom->importNode($d,true);
        if($det instanceof DOMElement) $det->setAttribute('nItem',(string)$index++);
        $parent->appendChild($det);
    }
}

/**
 * Encontra o infNFe, define onde inserir os novos itens
 * e remove os itens antigos para limpar a lista.
 */
function prepareDomForInsertion(DOMDocument $dom, DOMXPath $xpath): ?array {
    $infNFe = $xpath->query('//nfe:infNFe')->item(0);
    if (!$infNFe) return null;

    // Identifica onde inserir os produtos (logo após o último item existente)
    // Geralmente é antes de <total>, <transp>, etc.
    $detList = $xpath->query('./nfe:det', $infNFe);
    $insertionPoint = null;

    if ($detList->length > 0) {
        $lastDet = $detList->item($detList->length - 1);
        $insertionPoint = $lastDet->nextSibling;

        // Remove os nós antigos para evitar duplicação
        foreach ($detList as $oldDet) {
            $infNFe->removeChild($oldDet);
        }
    }

    return [
        'infNFe' => $infNFe,
        'insertionPoint' => $insertionPoint
    ];
}

/**
 * Recebe uma lista de nós <det>, renumera o nItem sequencialmente
 * e os insere no DOM de destino.
 */
function insertOrderedProducts(DOMDocument $targetDom, DOMElement $infNFe, ?DOMNode $insertionPoint, array $detNodes): void {
    $nItem = 1;
    
    foreach ($detNodes as $node) {
        // Se o nó vier de outro arquivo (caso E), precisamos importá-lo.
        // Se for do mesmo arquivo (caso D), o importNode apenas clona ou move se não for deep copy.
        // Usamos importNode com true (deep copy) para garantir que traz tudo (<prod>, <imposto>, etc)
        $importedNode = $targetDom->importNode($node, true);

        if ($importedNode instanceof DOMElement) {
            // REGRA DE OURO: Renumerar sequencialmente para validação da SEFAZ
            $importedNode->setAttribute('nItem', (string)$nItem);
        }

        // Insere na posição correta (reconstituindo o "miolo" da nota)
        if ($insertionPoint) {
            $infNFe->insertBefore($importedNode, $insertionPoint);
        } else {
            $infNFe->appendChild($importedNode);
        }
        
        $nItem++;
    }
}

/**
 * Remove a assinatura antiga (que ficou inválida) e cria um placeholder.
 */
function resetSignature(DOMDocument $dom, string $XMLDSIG_NS): void {
    // Remove assinatura antiga
    $signatures = $dom->getElementsByTagName('Signature');
    if ($signatures->length > 0) {
        $sigNode = $signatures->item(0);
        $sigNode->parentNode->removeChild($sigNode);
    }

    // Adiciona estrutura básica de nova assinatura (opcional, mas mantém visual de NFe)
    $rootNFe = $dom->getElementsByTagName('NFe')->item(0);
    if ($rootNFe) {
        $newSig = $dom->createElementNS($XMLDSIG_NS, 'Signature');
        $rootNFe->appendChild($newSig);
    }
}

/** ----------------- Opções ----------------- */

/** A: Cada NF XML um JSON */
function handleOptionA(string $inputDir,string $outputDir){
    $outDir=$outputDir.DIRECTORY_SEPARATOR.'JsonNotaFiscal';
    ensureDir($outDir);

    $files=glob(rtrim($inputDir,DIRECTORY_SEPARATOR).'/*.xml');
    foreach($files as $file){
        // Carrega XML
        $dom=loadDomFile($file);
        if(!$dom) continue;

        // Converte DOM em array
        $arr=domNodeToArray($dom->documentElement);

        // Serializa em JSON
        $json=json_encode($arr,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);

        // Salva arquivo JSON
        $base=pathinfo($file,PATHINFO_FILENAME);
        file_put_contents($outDir.DIRECTORY_SEPARATOR.$base.'.json',$json);
    }
}

/** B: Cada NF um XML apenas com produtos */
function handleOptionB(string $inputDir,string $outputDir,string $NAMESPACE_NFE){
    $outDir=$outputDir.DIRECTORY_SEPARATOR.'NotaFiscalApenasProdutos';
    ensureDir($outDir);

    $files=glob(rtrim($inputDir,DIRECTORY_SEPARATOR).'/*.xml');
    foreach($files as $file){
        $dom=loadDomFile($file); if(!$dom) continue;

        // XPath para produtos
        $xpath=new DOMXPath($dom);
        $xpath->registerNamespace('nfe',$NAMESPACE_NFE);
        $detList=$xpath->query('//nfe:infNFe/nfe:det');
        if(!$detList||$detList->length===0) continue;

        // Novo DOM e nó raiz
        $outDom=createDom();
        $root=$outDom->createElementNS($NAMESPACE_NFE,'ProdutosNF');
        $root->setAttribute('versao','4.00');
        $outDom->appendChild($root);

        // Importa produtos
        importDetsToDom($outDom,$root,iterator_to_array($detList));

        // Salva XML
        $base=pathinfo($file,PATHINFO_FILENAME);
        $outDom->save($outDir.DIRECTORY_SEPARATOR.$base.'_produtos.xml');
    }
}

/** C: Único XML com todos produtos de todas NFs */
function handleOptionC(string $inputDir, string $outputDir, string $NAMESPACE_NFE) {
    $outDir = $outputDir . DIRECTORY_SEPARATOR . 'TODOS_produtos';
    ensureDir($outDir);

    $files = glob(rtrim($inputDir, DIRECTORY_SEPARATOR) . '/*.xml');
    $items = []; // Onde guardaremos todos os produtos de todas as notas

    // Para cada arquivo XML de NF:
    // Carrega o DOM do XML
    // Cria XPath com namespace
    // Busca todos os elementos <det> (produtos)
    // Adiciona todos os produtos encontrados ao array $items
    foreach ($files as $file) {
        $dom = loadDomFile($file);
        if (!$dom) continue;
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('nfe', $NAMESPACE_NFE);
        $detList = $xpath->query('//nfe:infNFe/nfe:det');
        if ($detList && $detList->length > 0) {
            $items = array_merge($items, iterator_to_array($detList));
        }
    }
    if (!$items) return;

    // Cria novo DOM e nó raiz
    $outDom = createDom();
    $root = $outDom->createElementNS($NAMESPACE_NFE, 'TodosProdutos');
    $root->setAttribute('versao', '4.00');
    $outDom->appendChild($root);

    // Importa todos os produtos coletados para o novo DOM
    importDetsToDom($outDom, $root, $items);

    // Salva XML final
    $outDom->save($outDir . DIRECTORY_SEPARATOR . 'todos_produtos.xml');
}


/** * D: Cada NF um XML com produtos ordenados alfabeticamente. 
 * Mantém toda a estrutura original da NFe, apenas reordenando os itens.
 */
function handleOptionD(string $inputDir, string $outputDir, string $NAMESPACE_NFE, string $XMLDSIG_NS) {
    $outDir = $outputDir . DIRECTORY_SEPARATOR . 'NF_ordem_alfabetica_produtos';
    ensureDir($outDir);
    $files = glob(rtrim($inputDir, DIRECTORY_SEPARATOR) . '/*.xml');

    foreach ($files as $file) {
        $dom = loadDomFile($file);
        if (!$dom) continue;

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('nfe', $NAMESPACE_NFE);

        // Coleta itens para ordenar
        $detList = $xpath->query('//nfe:infNFe/nfe:det');
        if (!$detList || $detList->length < 2) {
            // Se tem 0 ou 1 item, apenas salva cópia (já está ordenado)
            $base = pathinfo($file, PATHINFO_FILENAME);
            $dom->save($outDir . DIRECTORY_SEPARATOR . "nfe-{$base}-Ordem_alfabetica.xml");
            continue;
        }

        $items = [];
        foreach ($detList as $d) {
            $xProd = getTextContentByPath($xpath, $d, './nfe:prod/nfe:xProd', ['nfe' => $NAMESPACE_NFE]) ?? '';
            $items[] = ['node' => $d, 'sortKey' => $xProd];
        }

        // Prepara o DOM (encontra local de inserção e limpa atuais)
        $context = prepareDomForInsertion($dom, $xpath);
        if (!$context) continue;

        // Ordena Alfabeticamente
        usort($items, fn($a, $b) => strcasecmp($a['sortKey'], $b['sortKey']));

        // Reinsere e Renumera (usando função auxiliar)
        insertOrderedProducts(
            $dom, 
            $context['infNFe'], 
            $context['insertionPoint'], 
            array_column($items, 'node')
        );

        // Trata Assinatura e Salva
        resetSignature($dom, $XMLDSIG_NS);
        
        $base = pathinfo($file, PATHINFO_FILENAME);
        $dom->save($outDir . DIRECTORY_SEPARATOR . "nfe-{$base}-Ordem_alfabetica.xml");
    }
}


/** * E: Único XML com todos produtos ordenados por preço crescente. 
 * Baseia-se na estrutura completa da primeira NF encontrada.
 */
function handleOptionE(string $inputDir, string $outputDir, string $NAMESPACE_NFE, string $XMLDSIG_NS) {
    $files = glob(rtrim($inputDir, DIRECTORY_SEPARATOR) . '/*.xml');
    if (empty($files)) return;

    $allItems = [];
    /** @var DOMDocument|null $masterDom */
    $masterDom = null;

    // Loop para carregar TUDO
    foreach ($files as $file) {
        $dom = loadDomFile($file);
        if (!$dom) continue;

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('nfe', $NAMESPACE_NFE);

        // Se é o primeiro arquivo válido, ele será o nosso "Mestre"
        if ($masterDom === null) {
            $masterDom = $dom; // Guarda referência do DOM completo
        }

        // Extrai produtos e calcula preço
        $detNodes = $xpath->query('//nfe:infNFe/nfe:det');
        foreach ($detNodes as $d) {
            $vUnTrib = getTextContentByPath($xpath, $d, './nfe:prod/nfe:vUnTrib', ['nfe'=>$NAMESPACE_NFE]);
            $vUnCom  = getTextContentByPath($xpath, $d, './nfe:prod/nfe:vUnCom',  ['nfe'=>$NAMESPACE_NFE]);
            $vProd   = getTextContentByPath($xpath, $d, './nfe:prod/nfe:vProd',   ['nfe'=>$NAMESPACE_NFE]);
            $qCom    = getTextContentByPath($xpath, $d, './nfe:prod/nfe:qCom',    ['nfe'=>$NAMESPACE_NFE]);

            $price = parseNumber($vUnTrib) 
                  ?? parseNumber($vUnCom) 
                  ?? ((parseNumber($vProd) / (parseNumber($qCom) ?: 1)) ?: 0.0);

            $allItems[] = ['node' => $d, 'sortKey' => $price];
        }
    }

    if (!$masterDom || empty($allItems)) return;

    // Prepara o DOM Mestre (limpa os produtos originais dele)
    $xpathMaster = new DOMXPath($masterDom);
    $xpathMaster->registerNamespace('nfe', $NAMESPACE_NFE);
    
    $context = prepareDomForInsertion($masterDom, $xpathMaster);
    if (!$context) return; // Erro estrutural no mestre

    // Ordena por Preço (Crescente)
    usort($allItems, fn($a, $b) => $a['sortKey'] <=> $b['sortKey']);

    // Reinsere TUDO no Mestre (Usando Helper)
    insertOrderedProducts(
        $masterDom, 
        $context['infNFe'], 
        $context['insertionPoint'], 
        array_column($allItems, 'node')
    );

    // Trata Assinatura e ID e Salva
    resetSignature($masterDom, $XMLDSIG_NS);

    ensureDir($outputDir);
    $masterDom->save($outputDir . DIRECTORY_SEPARATOR . 'nfe-agregada-ordenada-por-preco.xml');
}

/** ----------------- Dispatch ----------------- */
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
