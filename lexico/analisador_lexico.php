<?php

class AnalisadorLexico
{
    private $patterns;
    private $tabelaTransicoes;
    private $entrada;
    private $posicao;
    private $tokens;
    private $estadoAtual;

    public function __construct($entrada, $jsonPath)
    {
        $this->patterns = [
            // Define padrões de tokens
            'CONST' => '/^[0-9]+/',
            'ATR' => '/^=/',
            'NEGACAO' => '/^!/',
            'ABRE_PAR' => '/^\(/',
            'FECHA_PAR' => '/^\)/',
            'ABRE_COL' => '/^\[/',
            'FECHA_COL' => '/^\]/',
            'ABRE_CHAVES' => '/^\{/',
            'FECHA_CHAVES' => '/^\}/',
            'ASPAS' => '/^\'/',
            'PV' => '/^;/',
            'VIRGULA' => '/^,/',
            'DEC' => '/^(var|VAR)/',
            'INT' => '/^(int)/',
            'CHAR' => '/^(char)/',
            'FLOAT' => '/^(float)/',
            'ARRAY' => '/^(array)/',
            'COMP' => '/^==/',
            'DIF' => '/^!=/',
            'MAIOR' => '/^>/',
            'MENOR' => '/^</',
            'MAIORIGUAL' => '/^>=/',
            'MENORIGUAL' => '/^<=/',
            'SOMA' => '/^\+/',
            'SUBTRACAO' => '/^-/',
            'DIVISAO' => '/^\//',
            'MULTIPLICACAO' => '/^\*/',
            'MODULO' => '/^%/',
            'IF' => '/^(se|SE)/',
            'ELSE' => '/^(senao|SENAO)/',
            'WHILE' => '/^(enquanto|ENQUANTO)/',
            'FOR' => '/^(para|PARA)/',
            'DO' => '/^(faca|FACA)/',
            'PRINT' => '/^(imprima|IMPRIMA)/',
            'READ' => '/^(leia|LEIA)/',
            'WRITE' => '/^(escreva|ESCREVA)/', // Atualizado para considerar variações de maiúsculas/minúsculas
            'PROGRAM' => '/^(programa|PROGRAMA)/',
            'RETURN' => '/^(retorno|RETORNO)/',
            'ID' => '/^[a-zA-Z]+[a-zA-Z0-9]*/',
            'ESPACO' => '/^[\ \n\r\t\s]+/',
        ];

        $this->entrada = $entrada;
        $this->posicao = 0;
        $this->tokens = [];
        $this->estadoAtual = 0;
        $this->tabelaTransicoes = $this->carregarTabelaTransicoes($jsonPath);
    }

    private function carregarTabelaTransicoes($jsonPath)
    {
        if (!file_exists($jsonPath)) {
            throw new Exception("Tabela JSON não encontrada.");
        }

        $json = file_get_contents($jsonPath);
        $tabela = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erro ao decodificar o arquivo JSON.");
        }

        return $tabela;
    }

    public function analisar()
    {
        $linha = 1;
        $coluna = 1;
        $estadoAtual = 0;
        $buffer = '';
    
        while ($this->posicao < strlen($this->entrada)) {
            $caractere = $this->entrada[$this->posicao];
            $transicoes = $this->tabelaTransicoes[$estadoAtual] ?? null;
    
            // Se o caractere for espaço ou nova linha, trata corretamente
            if (ctype_space($caractere)) {
                if (!empty($buffer)) {
                    // Processa o token anterior (se existir)
                    $this->processarToken($buffer, $linha, $coluna);
                    $buffer = ''; // Limpa o buffer
                }
    
                // Se for uma quebra de linha, aumenta o número da linha e reinicia a coluna
                if ($caractere === "\n") {
                    $linha++;
                    $coluna = 1;
                } else {
                    $coluna++;
                }
    
                // Avança para o próximo caractere
                $this->posicao++;
                continue; // Ignora o espaço
            }
    
            // Se for um símbolo especial (como operadores, pontuação), processa diretamente
            if ($this->verificarSimbolosEspeciais($caractere)) {
                // Processa o token do símbolo
                $this->processarToken($caractere, $linha, $coluna);
                $this->posicao++;
                $coluna++;
                continue;
            }
    
            // Verifica primeiro palavras-chave, que devem ter prioridade
            foreach (['escreva', 'imprima', 'leia', 'se', 'enquanto', 'para', 'faca'] as $palavraChave) {
                if (substr($this->entrada, $this->posicao, strlen($palavraChave)) === $palavraChave) {
                    // Encontrou uma palavra-chave, processa
                    $this->processarToken($palavraChave, $linha, $coluna);
                    $this->posicao += strlen($palavraChave);
                    $coluna += strlen($palavraChave);
                    continue 2; // Vai para o próximo ciclo de análise
                }
            }
    
            // Se não for uma palavra-chave, verifica o próximo padrão
            foreach ($this->patterns as $tipo => $padrao) {
                if (preg_match($padrao, $caractere)) {
                    $buffer .= $caractere; // Adiciona ao buffer
                    $this->posicao++;
                    $coluna++;
                    continue 2; // Vai para o próximo ciclo de análise
                }
            }
    
            // Se nenhum padrão foi reconhecido, é um erro léxico
            throw new Exception("Erro léxico: caractere inesperado '{$caractere}' na linha {$linha}, coluna {$coluna}.");
        }
    
        // Processa o último token, se houver
        if (!empty($buffer)) {
            $this->processarToken($buffer, $linha, $coluna);
        }
    
        return $this->tokens;
    }
        
    private function verificarSimbolosEspeciais($caractere)
    {
        $simbolosEspeciais = ['(', ')', '{', '}', ';', ',', '+', '-', '*', '/', '%', '=', '!', '<', '>', '==', '!=', '>=', '<=', "'"];
        return in_array($caractere, $simbolosEspeciais);
    }    
    
    private function processarToken($buffer, $linha, $coluna)
    {
        $tokenAtual = null;
        foreach ($this->patterns as $tipo => $padrao) {
            if (preg_match($padrao, $buffer)) {
                $tokenAtual = $tipo;
                break;
            }
        }
        
        if ($tokenAtual !== null && $tokenAtual !== 'ESPACO') {
            $this->tokens[] = [
                'token' => $tokenAtual,
                'valor' => $buffer,
                'linha' => $linha,
                'coluna' => $coluna - strlen($buffer),
            ];
        }
    }
}