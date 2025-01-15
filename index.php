<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisador Léxico e Sintático</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        textarea { width: 100%; height: 200px; }
        button { margin-top: 10px; }
        pre { background-color: #f4f4f4; padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>

<h1>Analisador Léxico e Sintático</h1>
<form method="post">
    <label for="sourceCode">Digite o código fonte:</label>
    <textarea name="sourceCode" id="sourceCode"><?php echo isset($_POST['sourceCode']) ? htmlspecialchars($_POST['sourceCode']) : ''; ?></textarea>
    <button type="submit">Analisar</button>
</form>

<?php 
    require_once("./lexico/analisador_lexico.php");

    // Define o caminho para o JSON do autômato
    $jsonPath = './automato/tabela.json';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['sourceCode'])) {
        $sourceCode = $_POST['sourceCode'];

        try {
            // Cria o analisador léxico
            $analisador = new AnalisadorLexico($sourceCode, $jsonPath);

            // Realiza a análise léxica
            $tokens = $analisador->analisar();

            // Exibe os tokens encontrados
            echo "<h2>Tokens Reconhecidos:</h2><pre>";
            foreach ($tokens as $token) {
                echo "Token: {$token['token']}, Lexema: '{$token['valor']}'\n";
            }
            echo "</pre>";

            // Aqui você pode integrar a análise sintática, se necessário
            // Por exemplo, passando $tokens para um AnalisadorSintatico
            // ...

        } catch (Exception $e) {
            // Exibe erros encontrados durante a análise
            echo "<h2>Erro</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        }
    }
?>

</body>
</html>
