<?php

class AnalisadorSintatico {
    private array $tabelaGoto;
    private array $tabelaAcoes;
    private array $acoesExecutadas = [];
    private array $pilhaEscopos = ['global'];  // Pilha de escopos
    private array $erros = [];
    private ArvoreDerivacao $arvoreDerivacao;
    private array $tabelaSimbolos = [];
    private array $producoes = [
        ["<PROGRAMA>", 7], ["<VARS>", 2], ["<VARS>", 0], ["<VAR>", 3], ["<VAR>", 2], ["<TIPO>", 1], ["<TIPO>", 1], ["<TIPO>", 1], ["<TIPO>", 1],
        ["<COMANDOS>", 2], ["<COMANDOS>", 0], ["<COMANDO>", 1], ["<COMANDO>", 1], ["<COMANDO>", 1], ["<COMANDO>", 1], ["<COMANDO>", 1], ["<COMANDO>", 1], ["<COMANDO>", 1],
        ["<COMANDO>", 1], ["<COMANDO>", 1], ["<ATRIBUICAO>", 4], ["<LEITURA>", 5], ["<IMPRESSAO>", 5], ["<RETORNO>", 3], ["<CHAMADA_FUNCAO>", 10], ["<LISTA_ARGUMENTOS>", 2],
        ["<LISTA_ARGUMENTOS>", 0], ["<LISTA_ARGUMENTOS_REST>", 3], ["<LISTA_ARGUMENTOS_REST>", 0], ["<IF>", 7], ["<IF>", 11], ["<FOR>", 10], ["<CONDICAO>", 1], ["<WHILE>", 7],
        ["<EXPRESSAO>", 2], ["<EXPRESSAO_REST>", 2], ["<EXPRESSAO_REST>", 0], ["<OPERADOR_LOGICO>", 1], ["<OPERADOR_LOGICO>", 1], ["<OPERADOR_LOGICO>", 1], ["<OPERADOR_LOGICO>", 1], ["<OPERADOR_LOGICO>", 1],
        ["<OPERADOR_LOGICO>", 1], ["<TERMO>", 2], ["<TERMO_REST>", 2], ["<TERMO_REST>", 0], ["<OPERADOR_ARITMETICO>", 1], ["<OPERADOR_ARITMETICO>", 1], ["<OPERADOR_ARITMETICO>", 1], ["<OPERADOR_ARITMETICO>", 1],
        ["<OPERADOR_ARITMETICO>", 1], ["<FATOR>", 1], ["<FATOR>", 1], ["<FATOR>", 3], ["<FATOR>", 1], ["<FATOR>", 1], ["<PARAMETROS>", 3], ["<PARAMETROS>", 2], ["<INCREMENTA>", 5], ["<INCREMENTA>", 5]
    ];

    private int $contadorEscopos = 0;
    private ?string $proximoTipoEscopo = null;

    private bool $ignorarProximoAbreChaves = false;
    private string $ultimoEscopo = 'global';
    private int $contadorBlocos = 0;
    private ?string $contextoAtual = null;

    public function __construct(string $caminhoArquivoJson) {
        $this->carregarTabela($caminhoArquivoJson);
        $this->arvoreDerivacao = new ArvoreDerivacao($this->producoes);
    }

    private function carregarTabela(string $caminhoArquivo): void {
        $dados = json_decode(file_get_contents($caminhoArquivo), true);
        if ($dados === null) {
            throw new Exception("Arquivo JSON inválido.");
        }
        $this->tabelaGoto = $dados['goto'];
        $this->tabelaAcoes = $dados['actionTable'];
    }

    public function analisar(array $tokens): bool {
        $pilha = [0];
        $indice = 0;

        while (true) {
            $estadoAtual = end($pilha);
            $tokenAtual = $tokens[$indice] ?? new Token('$', '$', 0, 0);
            $nomeToken = $tokenAtual->getName();

            if (!isset($this->tabelaAcoes[$estadoAtual][$nomeToken])) {
                $this->erros[] = "Erro de sintaxe: Token inesperado '{$nomeToken}' na linha " . $tokenAtual->getLine();
                return false;
            }

            $acao = $this->tabelaAcoes[$estadoAtual][$nomeToken];
            $this->acoesExecutadas[] = $acao;

            if ($acao['type'] === 'SHIFT') {
                $this->processarShift($tokenAtual, $tokens, $indice);
                $pilha[] = $acao['state'];
                $indice++;
            } elseif ($acao['type'] === 'REDUCE') {
                $this->processarReduce($acao['rule'], $pilha);
            } elseif ($acao['type'] === 'ACCEPT') {
                return true;
            }
        }
    }

    private function processarShift(Token $token, array $tokens, int &$indice): void {
        $this->arvoreDerivacao->adicionarTerminal($token);
        
        switch ($token->getName()) {
            case 'IF':
            case 'FOR':
            case 'WHILE':
                $this->ultimoEscopo = $token->getName();
                break;
                
            case 'ABRE_CHAVES':
                $this->entrarEscopo();
                break;
                
            case 'FECHA_CHAVES':
                $this->sairEscopo();
                break;
                
            case 'ID':
                $this->processarIdentificador($token, $tokens, $indice);
                break;
        }
    }

    private function processarIdentificador(Token $token, array $tokens, int $indice): void {
        // Verificar se é declaração de variável/função
        if ($indice > 0 && in_array($tokens[$indice-1]->getName(), ['INT', 'FLOAT', 'CHAR', 'ARRAY'])) {
            $tipo = $tokens[$indice-1]->getName();
            $categoria = 'variavel';
            
            // Verificar se é função
            if (isset($tokens[$indice+1]) && $tokens[$indice+1]->getName() === 'ABRE_PAR') {
                $categoria = 'funcao';
                $this->entrarEscopo($token->getLexeme());
            }
            
            $this->adicionarSimbolo(
                $token->getLexeme(),
                $tipo,
                $categoria,
                end($this->pilhaEscopos),
                $token->getLine()
            );
        }
    }

    private function entrarEscopo(string $nomePersonalizado = null): void {
        $nomeEscopo = $nomePersonalizado ?? $this->ultimoEscopo;
        $this->pilhaEscopos[] = "{$nomeEscopo}_" . ++$this->contadorBlocos;
    }

    private function sairEscopo(): void {
        if (count($this->pilhaEscopos) > 1) {
            array_pop($this->pilhaEscopos);
        }
    }

    private function processarReduce(int $regra, array &$pilha): void {
        $producao = $this->producoes[$regra];
        $naoTerminal = $producao[0];
        $tamanho = $producao[1];

        for ($i = 0; $i < $tamanho; $i++) {
            array_pop($pilha);
        }

        $estado = end($pilha);
        $novoEstado = $this->tabelaGoto[$estado][$naoTerminal] ?? null;
        
        if ($novoEstado === null) {
            throw new Exception("Erro de redução: Estado não encontrado para $naoTerminal");
        }
        
        $pilha[] = $novoEstado;
        $this->arvoreDerivacao->reduzir(str_replace(['<','>'], '', $naoTerminal), $tamanho);
    }

    private function adicionarSimbolo(string $nome, string $tipo, string $categoria, string $escopo, int $linha): void {
        $this->tabelaSimbolos[] = [
            'nome' => $nome,
            'tipo' => $tipo,
            'categoria' => $categoria,
            'escopo' => $escopo,
            'linha' => $linha
        ];
    }

    public function getErros(): array
    {
        return $this->erros;
    }

    public function getTabelaDeSimbolos(): array
    {
        return $this->tabelaSimbolos;
    }

    public function getProducoes(): array
    {
        return $this->producoes;
    }

    public function getAcoesExecutadas(): array
    {
        return $this->acoesExecutadas;
    }
}
