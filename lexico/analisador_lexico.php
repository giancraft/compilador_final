<?php

class AnalisadorLexico
{
    private $tabelaTransicoes;
    private $entrada;
    private $posicao;
    private $tokens;
    private $estadoAtual;

    public function __construct($entrada, $jsonPath)
    {
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
        $buffer = '';
        $estadoAtual = 0;

        while ($this->posicao < strlen($this->entrada)) {
            $caractere = $this->entrada[$this->posicao];
            $transicoes = $this->tabelaTransicoes[$estadoAtual] ?? null;

            if ($transicoes === null) {
                throw new Exception("Erro léxico: estado inválido '{$estadoAtual}' na linha {$linha}, coluna {$coluna}.");
            }

            // Ignorar espaços e quebras de linha
            if (ctype_space($caractere)) {
                if ($caractere === "\n") {
                    $linha++;
                    $coluna = 1;
                } else {
                    $coluna++;
                }

                // Processar token no buffer, se houver
                if (!empty($buffer) && isset($this->tabelaTransicoes[$estadoAtual]['token'])) {
                    $this->adicionarToken($estadoAtual, $buffer, $linha, $coluna - strlen($buffer));
                    $buffer = '';
                    $estadoAtual = 0;
                }

                $this->posicao++;
                continue;
            }

            // Verifica o próximo estado com base no caractere
            $proxEstado = $transicoes[$caractere] ?? $transicoes['DEFAULT'] ?? null;

            if ($proxEstado === null) {
                // Processa o token acumulado no buffer
                if (!empty($buffer) && isset($this->tabelaTransicoes[$estadoAtual]['token'])) {
                    $this->adicionarToken($estadoAtual, $buffer, $linha, $coluna - strlen($buffer));
                    $buffer = '';
                    $estadoAtual = 0;
                    continue;
                }

                throw new Exception("Erro léxico: caractere inesperado '{$caractere}' na linha {$linha}, coluna {$coluna}.");
            }

            // Continua no próximo estado
            $estadoAtual = $proxEstado;
            $buffer .= $caractere;
            $this->posicao++;
            $coluna++;
        }

        // Processa o último token, se houver
        if (!empty($buffer) && isset($this->tabelaTransicoes[$estadoAtual]['token'])) {
            $this->adicionarToken($estadoAtual, $buffer, $linha, $coluna - strlen($buffer));
        }

        return $this->tokens;
    }

    private function adicionarToken($estadoAtual, $valor, $linha, $coluna)
    {
        $tokenInfo = $this->tabelaTransicoes[$estadoAtual]['token'] ?? null;

        if ($tokenInfo !== null) {
            $this->tokens[] = [
                'token' => $tokenInfo,
                'valor' => $valor,
                'linha' => $linha,
                'coluna' => $coluna,
            ];
        }
    }
}