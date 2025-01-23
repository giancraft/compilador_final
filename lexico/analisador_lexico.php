<?php

require_once 'token.php';

class AnalisadorLexico {
    private $tabelaTransicoes;
    private $entrada;
    private $posicao;
    private $tokens;
    private $estadoAtual;

    public function __construct(string $entrada, string $jsonPath) {
        $this->entrada = $entrada;
        $this->posicao = 0;
        $this->tokens = [];
        $this->estadoAtual = 0;
        $this->tabelaTransicoes = $this->carregarTabelaTransicoes($jsonPath);
    }

    private function carregarTabelaTransicoes(string $jsonPath): array {
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

    public function analisar(): array {
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

            if (ctype_space($caractere)) {
                if ($caractere === "\n") {
                    $linha++;
                    $coluna = 1;
                } else {
                    $coluna++;
                }

                if (!empty($buffer) && isset($this->tabelaTransicoes[$estadoAtual]['token'])) {
                    $this->adicionarToken($estadoAtual, $buffer, $linha, $coluna - strlen($buffer));
                    $buffer = '';
                    $estadoAtual = 0;
                }

                $this->posicao++;
                continue;
            }

            $proxEstado = $transicoes[$caractere] ?? $transicoes['DEFAULT'] ?? null;

            if ($proxEstado === null) {
                if (!empty($buffer) && isset($this->tabelaTransicoes[$estadoAtual]['token'])) {
                    $this->adicionarToken($estadoAtual, $buffer, $linha, $coluna - strlen($buffer));
                    $buffer = '';
                    $estadoAtual = 0;
                    continue;
                }

                throw new Exception("Erro léxico: caractere inesperado '{$caractere}' na linha {$linha}, coluna {$coluna}.");
            }

            $estadoAtual = $proxEstado;
            $buffer .= $caractere;
            $this->posicao++;
            $coluna++;
        }

        if (!empty($buffer) && isset($this->tabelaTransicoes[$estadoAtual]['token'])) {
            $this->adicionarToken($estadoAtual, $buffer, $linha, $coluna - strlen($buffer));
        }

        return $this->tokens;
    }

    private function adicionarToken($estadoAtual, string $valor, int $linha, int $coluna): void {
        $tokenInfo = $this->tabelaTransicoes[$estadoAtual]['token'] ?? null;

        if ($tokenInfo !== null) {
            $this->tokens[] = new Token($tokenInfo, $valor, $coluna, $linha);
        }
    }
}
