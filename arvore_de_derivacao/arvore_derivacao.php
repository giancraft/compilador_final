<?php

class ArvoreDerivacao {
    private array $pilha = [];
    private array $producoes;

    public function __construct(array $producoes) {
        $this->producoes = $producoes;
    }

    public function adicionarTerminal(Token $token): void {
        $this->pilha[] = [
            'tipo' => 'terminal',
            'valor' => $token,
            'linha' => $token->getLine()
        ];
    }

    public function reduzir(string $naoTerminal, int $tamanhoProducao): void {
        $filhos = [];
        for ($i = 0; $i < $tamanhoProducao; $i++) {
            $filhos[] = array_pop($this->pilha);
        }

        $this->pilha[] = [
            'tipo' => 'naoTerminal',
            'valor' => $naoTerminal,
            'filhos' => array_reverse($filhos)  // Reverter filhos para manter a ordem correta
        ];
    }

    public function getArvore(): array {
        return $this->pilha;
    }

    public function construir(array $acoes, array $tokens): void {
        $indiceToken = 0;

        foreach ($acoes as $acao) {
            if ($acao['type'] === 'SHIFT') {
                $this->adicionarTerminal($tokens[$indiceToken]);
                $indiceToken++;
            } elseif ($acao['type'] === 'REDUCE') {
                $regra = $this->producoes[$acao['rule']];
                $naoTerminal = $regra[0];
                $tamanhoProducao = $regra[1];
                $this->reduzir($naoTerminal, $tamanhoProducao);
            }
        }
    }

    public function imprimirHtml(array $arvore = null, int $nivel = 0): void {
        if ($arvore === null) {
            $arvore = $this->getArvore();
        }
        
        foreach ($arvore as $no) {
            $espacamento = str_repeat('&nbsp;', $nivel * 4);
            
            if ($no['tipo'] === 'naoTerminal') {
                // Usa htmlspecialchars para garantir que os símbolos < e > não sejam interpretados como tags
                $valorNaoTerminal = htmlspecialchars($no['valor']);
                echo "<div class = 'noTerminal'>{$espacamento}<span>{$valorNaoTerminal}</span></div>";
                if (isset($no['filhos'])) {
                    $this->imprimirHtml($no['filhos'], $nivel + 1);
                }
            } elseif ($no['tipo'] === 'terminal') {
                // Imprime o terminal com o nome e lexema
                $token = $no['valor'];
                echo "<div class = 'terminal'>{$espacamento}<span>{$token->getName()}(\"{$token->getLexeme()}\")</span></div>";
            }
        }
    }    
}
