<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisador Sintático e Semântico</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #fffff0;
        }

        textarea {
            width: 100%;
            height: 200px;
        }

        button {
            margin-top: 10px;
        }

        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border: 1px solid #ccc;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }

        .arvore {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            margin-top: 20px;
        }

        .arvore .noTerminal {
            font-weight: bold;
            color: #00796b;
            margin-bottom: 10px;
        }

        .arvore .noTerminal span {
            background-color: #e0f7fa;
            border: 2px solid #00796b;
            border-radius: 6px;
            padding: 2px 6px;
            color: #00796b;
        }

        .arvore .terminal {
            font-style: italic;
            color: #f57c00;
            margin: 5px 0;
        }

        .arvore .terminal span {
            background-color: #fff3e0;
            border: 2px solid #f57c00;
            border-radius: 6px;
            padding: 2px 6px;
            color: #f57c00;
        }

        .erro {
            color: red;
            font-weight: bold;
        }

        .sucesso {
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body>

<h1>Compilador Final</h1>
<form method="post">
    <label for="sourceCode">Digite o código fonte:</label>
    <textarea name="sourceCode" id="sourceCode"><?php echo isset($_POST['sourceCode']) ? htmlspecialchars($_POST['sourceCode']) : ''; ?></textarea>
    <button type="submit">Analisar</button>
</form>

<?php
require_once './lexico/analisador_lexico.php';
require_once './sintatico/analisador_sintatico.php';
require_once './semantico/analisador_semantico.php';
require_once './arvore_de_derivacao/nodo.php';
require_once './arvore_de_derivacao/arvore_derivacao.php';

$jsonPath = './automato/tabela.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['sourceCode'])) {
    $sourceCode = $_POST['sourceCode'];

    try {
        // Análise Léxica
        $analisadorLexico = new AnalisadorLexico($sourceCode, $jsonPath);
        $tokens = $analisadorLexico->analisar();

        echo "<h2>Tokens Reconhecidos:</h2><pre>";
        foreach ($tokens as $token) {
            echo "Token: {$token->getName()}, Lexema: '{$token->getLexeme()}', Linha: {$token->getLine()}, Coluna: {$token->getInicio()}\n";
        }
        echo "</pre>";

        // Análise Sintática
        $analisadorSintatico = new AnalisadorSintatico('./tabela_sintatica/tabela_sintatica.json');
        $resultadoSintatico = $analisadorSintatico->analisar($tokens);
        $erros = $analisadorSintatico->getErros();

        echo "<h2>Resultado da Análise Sintática:</h2><pre>";
        if (empty($erros)) {
            echo "<span class='sucesso'>Análise sintática concluída com sucesso!</span>";
        } else {
            echo "<span class='erro'>Erros de sintaxe encontrados:</span>\n";
            foreach ($erros as $erro) {
                echo htmlspecialchars($erro) . "\n";
            }
        }
        echo "</pre>";

        // Exibição da Tabela de Símbolos
        $tabelaSimbolos = $analisadorSintatico->getTabelaDeSimbolos();

        if (!empty($tabelaSimbolos)) {
            echo "<h2>Tabela de Símbolos:</h2>";
            echo "<table>";
            echo "<tr><th>Nome</th><th>Tipo</th><th>Escopo</th><th>Categoria</th></tr>";
            foreach ($tabelaSimbolos as $simbolo) {
                // Garantir valores padrão para campos não definidos
                $nome = $simbolo['nome'] ?? '';
                $tipo = $simbolo['tipo'] ?? '';
                $escopo = $simbolo['escopo'] ?? '';
                $categoria = $simbolo['categoria'] ?? '';
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($nome) . "</td>";
                echo "<td>" . htmlspecialchars($tipo) . "</td>";
                echo "<td>" . htmlspecialchars($escopo) . "</td>";
                echo "<td>" . htmlspecialchars($categoria) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<h2>Tabela de Símbolos</h2><p class='erro'>Tabela de símbolos está vazia ou não foi gerada.</p>";
        }
        

        // Análise Semântica
        $analisadorSemantico = new AnalisadorSemantico(
            $analisadorSintatico->getTabelaDeSimbolos(),
            $tokens
        );
        $analisadorSemantico->realizarAnaliseSemantica();
        $errosSemanticos = $analisadorSemantico->getErros();

        echo "<h2>Erros Semânticos:</h2>";
        if (empty($errosSemanticos)) {
            echo "<pre class='sucesso'>Nenhum erro semântico encontrado.</pre>";
        } else {
            echo "<pre class='erro'>";
            foreach ($errosSemanticos as $erro) {
                echo "{$erro}\n";
            }
            echo "</pre>";
        }

        // Construção e Exibição da Árvore de Derivação
        if ($resultadoSintatico) {
            $arvoreDerivacao = new ArvoreDerivacao($analisadorSintatico->getProducoes());
            $arvoreDerivacao->construir(
                $analisadorSintatico->getAcoesExecutadas(),
                $tokens
            );

            echo "<h2>Árvore de Derivação:</h2><div class='arvore'>";
            $arvoreDerivacao->imprimirHtml(); // Exibe a árvore de derivação
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<h2 class='erro'>Erro:</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    }
}
?>

</body>
</html>
