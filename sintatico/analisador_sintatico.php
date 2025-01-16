<?php

class AnalisadorSintatico
{
    private $tabelaAcao; // Tabela de Ações (SHIFT, REDUCE, ACCEPT, ERROR)
    private $tabelaIrPara; // Tabela Ir Para (transições entre estados)
    private $pilha; // Pilha para os estados e símbolos
    private $tokens; // Tokens recebidos do analisador léxico
    private $gramatica; // Produções da gramática

    public function __construct($tabelaAcao, $tabelaIrPara, $gramatica)
    {
        $this->tabelaAcao = $tabelaAcao;
        $this->tabelaIrPara = $tabelaIrPara;
        $this->gramatica = $gramatica;
        $this->pilha = [0]; // Inicia com estado 0 na pilha
    }

    public function analisar($tokens)
    {
        $this->tokens = $tokens;
        $tokens[] = ['token' => '$', 'valor' => '']; // Adiciona marcador de fim de entrada
        $indiceToken = 0;

        while (true) {
            $estadoAtual = end($this->pilha);
            $tokenAtual = $tokens[$indiceToken]['token'];

            $acao = $this->tabelaAcao[$estadoAtual][$tokenAtual] ?? null;

            if ($acao === null) {
                throw new Exception("Erro sintático: token inesperado '{$tokenAtual}'");
            }

            if (strpos($acao, 'SHIFT') === 0) {
                // SHIFT: adiciona estado na pilha e avança no token
                $novoEstado = intval(substr($acao, 6));
                $this->pilha[] = $tokenAtual;
                $this->pilha[] = $novoEstado;
                $indiceToken++;
            } elseif (strpos($acao, 'REDUCE') === 0) {
                // REDUCE: aplica uma produção e reduz
                $producaoIndex = intval(substr($acao, 7));
                $producao = $this->gramatica[$producaoIndex];
                $simbolos = explode(' ', $producao['corpo']);

                for ($i = 0; $i < count($simbolos) * 2; $i++) {
                    array_pop($this->pilha);
                }

                $estadoReduzido = end($this->pilha);
                $this->pilha[] = $producao['cabeca'];

                $novoEstado = $this->tabelaIrPara[$estadoReduzido][$producao['cabeca']] ?? null;

                if ($novoEstado === null) {
                    throw new Exception("Erro ao reduzir: símbolo não esperado '{$producao['cabeca']}'");
                }

                $this->pilha[] = $novoEstado;
            } elseif ($acao === 'ACCEPT') {
                // ACCEPT: análise concluída com sucesso
                return "Análise sintática concluída com sucesso.";
            } else {
                throw new Exception("Erro sintático: ação desconhecida '{$acao}'");
            }
        }
    }
}
