<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisador Sintático SLR</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        textarea { width: 100%; height: 200px; }
        button { margin-top: 10px; }
        pre { background-color: #f4f4f4; padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>

<h1>Analisador Sintático SLR</h1>
<form method="post">
    <label for="sourceCode">Digite o código fonte:</label>
    <textarea name="sourceCode" id="sourceCode"><?php echo isset($_POST['sourceCode']) ? htmlspecialchars($_POST['sourceCode']) : ''; ?></textarea>
    <button type="submit">Analisar</button>
</form>

<?php
require_once './lexico/analisador_lexico.php';
require_once './sintatico/analisador_sintatico.php';

$jsonPath = './automato/tabela.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['sourceCode'])) {
    $sourceCode = $_POST['sourceCode'];

    try {
        // Analisador léxico para obter tokens
        $analisadorLexico = new AnalisadorLexico($sourceCode, $jsonPath);
        $tokens = $analisadorLexico->analisar();

        echo "<h2>Tokens Reconhecidos:</h2><pre>";
        foreach ($tokens as $token) {
            echo "Token: {$token->getName()}, Lexema: '{$token->getLexeme()}', Linha: {$token->getLine()}, Coluna: {$token->getInicio()}\n";
        }
        echo "</pre>";

        // Passando os objetos de tokens para o analisador sintático
        $tokensArray = array_map(function($token) {
            return $token; // Passando o objeto Token inteiro
        }, $tokens);

        $analisadorSintatico = new AnalisadorSintatico('./tabela_sintatica/tabela_sintatica.json');
        $resultado = $analisadorSintatico->analisar($tokensArray); // Aqui, os tokens passados são objetos

        echo "<h2>Resultado da Análise Sintática:</h2><pre>";
        echo $resultado ? "Análise sintática concluída com sucesso!" : "Erro de sintaxe encontrado.";
        echo "</pre>";
        
    } catch (Exception $e) {
        echo "<h2>Erro</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    }
}
?>

</body>
</html>
