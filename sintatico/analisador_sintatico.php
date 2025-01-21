<?php

class AnalisadorSintatico {
    private array $gotoTable;
    private array $actionTable;
    private array $producoes = [
        ["<PROGRAMA>", 8], ["<VARS>", 2], ["<VARS>", 0], ["<VAR>", 3], ["<TIPO>", 1], ["<TIPO>", 1], ["<TIPO>", 1], ["<TIPO>", 1],
        ["<COMANDOS>", 2], ["<COMANDOS>", 0], ["<COMANDO>", 1], ["<COMANDO>", 1], ["<COMANDO>", 1], ["<COMANDO>", 1], ["<COMANDO>", 1], ["<COMANDO>", 1],
        ["<COMANDO>", 1], ["<COMANDO>", 1], ["<ATRIBUICAO>", 4], ["<LEITURA>", 5], ["<IMPRESSAO>", 5], ["<RETORNO>", 3], ["<CHAMADA_FUNCAO>", 5], ["<LISTA_ARGUMENTOS>", 2],
        ["<LISTA_ARGUMENTOS>", 0], ["<LISTA_ARGUMENTOS_REST>", 3], ["<LISTA_ARGUMENTOS_REST>", 0], ["<IF>", 7], ["<IF>", 11], ["<FOR>", 10], ["<CONDICAO>", 1], ["<WHILE>", 7],
        ["<EXPRESSAO>", 2], ["<EXPRESSAO_REST>", 2], ["<EXPRESSAO_REST>", 0], ["<OPERADOR_LOGICO>", 1], ["<OPERADOR_LOGICO>", 1], ["<OPERADOR_LOGICO>", 1], ["<OPERADOR_LOGICO>", 1], ["<OPERADOR_LOGICO>", 1],
        ["<OPERADOR_LOGICO>", 1], ["<TERMO>", 2], ["<TERMO_REST>", 2], ["<TERMO_REST>", 0], ["<OPERADOR_ARITMETICO>", 1], ["<OPERADOR_ARITMETICO>", 1], ["<OPERADOR_ARITMETICO>", 1], ["<OPERADOR_ARITMETICO>", 1],
        ["<OPERADOR_ARITMETICO>", 1], ["<FATOR>", 1], ["<FATOR>", 1], ["<FATOR>", 3]
    ];

    public function __construct(string $jsonFilePath) {
        $this->carregarTabela($jsonFilePath);
    }

    private function carregarTabela(string $filePath): void {
        $data = json_decode(file_get_contents($filePath), true);
        if ($data === null) {
            throw new Exception("Arquivo JSON inválido.");
        }

        $this->gotoTable = $data['goto'];
        $this->actionTable = $data['actionTable'];
    }

    public function analisar(array $tokens): bool {
        $pilha = [0];
        $indice = 0;

        while (true) {
            $estadoAtual = end($pilha);
            $tokenAtual = $tokens[$indice] ?? new Token('$', '$', 0, 0);
            $nomeToken = $tokenAtual->getName();

            if (!isset($this->actionTable[$estadoAtual][$nomeToken])) {
                throw new Exception("Erro de sintaxe na linha " . $tokenAtual->getLine() . ", token inesperado: " . $nomeToken);
            }

            $acao = $this->actionTable[$estadoAtual][$nomeToken];

            if ($acao['type'] === 'SHIFT') {
                $pilha[] = $acao['state'];
                $indice++;
            } elseif ($acao['type'] === 'REDUCE') {
                $regra = $this->producoes[$acao['rule']];

                for ($i = 0; $i < $regra[1]; $i++) {
                    array_pop($pilha);
                }

                $estadoTopo = end($pilha);
                $estadoGoto = $this->gotoTable[$estadoTopo][$regra[0]] ?? null;

                if ($estadoGoto === null) {
                    throw new Exception("Erro ao aplicar redução na linha " . $tokenAtual->getLine());
                }

                $pilha[] = $estadoGoto;
            } elseif ($acao['type'] === 'ACCEPT') {
                return true;
            }
        }
    }
}

?>
