<?php

class AnalisadorSemantico
{
    private array $tabelaSimbolos;
    private array $tokens;
    private array $erros = [];
    private array $pilhaEscopos = ['global'];
    private ?string $funcaoAtual = null;
    private array $escoposHierarquia = [];

    private ?string $contextoDeclaracaoFuncao = null;
    private array $pilhaFuncoes = [];
    private ?string $ultimoControle = null;
    private int $contadorBlocos = 0;

    public function __construct(array $tabelaSimbolos, array $tokens)
    {
        $this->tabelaSimbolos = $tabelaSimbolos;
        $this->tokens = $tokens;
        $this->construirHierarquiaEscopos();
    }

    public function realizarAnaliseSemantica(): bool
    {
        $this->verificarDeclaracaoDuplicada();
        $this->verificarEscopoEVariaveis();
        $this->verificarChamadasFuncoes();
        $this->verificarRetornos();
        return empty($this->erros);
    }

    private function construirHierarquiaEscopos(): void
    {
        foreach ($this->tabelaSimbolos as $simbolo) {
            $escopo = $simbolo['escopo'] ?: 'global';
            if (!isset($this->escoposHierarquia[$escopo])) {
                $this->escoposHierarquia[$escopo] = [];
            }
            $this->escoposHierarquia[$escopo][] = $simbolo;
        }
    }


    private function verificarDeclaracaoDuplicada(): void
    {
        $declaracoes = [];
        foreach ($this->tabelaSimbolos as $simbolo) {
            $chave = "{$simbolo['nome']}@{$simbolo['escopo']}";
            if (isset($declaracoes[$chave])) {
                $this->erros[] = "Erro semântico: '{$simbolo['nome']}' já declarado no escopo '{$simbolo['escopo']}'";
            } else {
                $declaracoes[$chave] = true;
            }
        }
    }

    private function verificarEscopoEVariaveis(): void
    {
        foreach ($this->tokens as $indice => $token) {
            switch ($token->getName()) {
                case 'FOR':
                case 'IF':
                case 'WHILE':
                    $this->ultimoControle = $token->getName();
                    break;

                case 'FUNCAO':
                    $this->contextoDeclaracaoFuncao = 'tipo';
                    break;

                case 'INT':
                case 'FLOAT':
                case 'CHAR':
                case 'ARRAY':
                    if ($this->contextoDeclaracaoFuncao === 'tipo') {
                        $this->contextoDeclaracaoFuncao = 'nome';
                    }
                    break;

                case 'ID':
                    if ($this->contextoDeclaracaoFuncao === 'nome') {
                        $nomeFuncao = $token->getLexeme();
                        $this->contextoDeclaracaoFuncao = null;
                        $this->pilhaFuncoes[] = $nomeFuncao;
                        $this->funcaoAtual = $nomeFuncao;
                        break;
                    }

                    if (isset($this->tokens[$indice + 1]) && $this->tokens[$indice + 1]->getName() === 'ABRE_PAR') {
                        $funcao = $this->buscarSimbolo($token->getLexeme(), 'funcao');
                        if ($funcao) {
                            $this->funcaoAtual = $token->getLexeme();
                        }
                    } else {
                        $this->verificarUsoVariavel($token, $indice);
                        // Verificar se há uma atribuição
                        if (isset($this->tokens[$indice + 1]) && $this->tokens[$indice + 1]->getName() === 'ATR') {
                            $this->verificarTipoAtribuicao($token, $indice);
                        }
                    }
                    break;

                case 'ABRE_CHAVES':
                    $ultimoEscopo = end($this->pilhaEscopos);

                    if ($this->ultimoControle) {
                        $this->contadorBlocos++;
                        $novoEscopo = "{$ultimoEscopo}_{$this->ultimoControle}_{$this->contadorBlocos}";
                        $this->ultimoControle = null;
                    } elseif ($this->funcaoAtual) {
                        // Se estamos dentro de uma função, associamos o escopo ao nome da função
                        $novoEscopo = "{$this->funcaoAtual}_bloco{$this->contadorBlocos}";
                        $this->contadorBlocos++;
                    } else {
                        // Bloco genérico sem função associada
                        $novoEscopo = "{$ultimoEscopo}_bloco";
                    }

                    array_push($this->pilhaEscopos, $novoEscopo);
                    break;

                case 'FECHA_CHAVES':
                    array_pop($this->pilhaEscopos);

                    // Se o escopo for de uma função e voltarmos para o escopo global, remover função atual
                    if (!empty($this->pilhaFuncoes) && end($this->pilhaEscopos) === 'global') {
                        array_pop($this->pilhaFuncoes);
                        $this->funcaoAtual = end($this->pilhaFuncoes) ?: null;
                    }
                    break;
            }
        }
    }

    // Função para validar tipos na atribuição
    private function verificarTipoAtribuicao(Token $variavelToken, int $indice): void {
        $nomeVar = $variavelToken->getLexeme();
        $variavelSimbolo = $this->buscarSimbolo($nomeVar, 'variavel');
    
        if (!$variavelSimbolo) {
            $this->erros[] = "Variável '{$nomeVar}' não declarada (Linha {$variavelToken->getLine()})";
            return;
        }
    
        // Encontra todos os tokens da expressão à direita até o próximo ';' ou fim
        $expressaoTokens = [];
        $nivel = 0;
        for ($i = $indice + 2; $i < count($this->tokens); $i++) {
            $token = $this->tokens[$i];
            if ($token->getName() === 'PV' && $nivel === 0) break;
            if ($token->getName() === 'ABRE_PAR') $nivel++;
            if ($token->getName() === 'FECHA_PAR') $nivel--;
            $expressaoTokens[] = $token;
        }
    
        $tipoExpressao = $this->determinarTipoExpressao($expressaoTokens);
    
        if ($tipoExpressao && !$this->tiposCompativeis($variavelSimbolo['tipo'], $tipoExpressao)) {
            $this->erros[] = "Erro semântico: '{$nomeVar}' ({$variavelSimbolo['tipo']}) recebe tipo incompatível ($tipoExpressao) (Linha {$variavelToken->getLine()})";
        }
    }

    private function determinarTipoExpressao(array $tokens): ?string {
        $tipos = [];
        foreach ($tokens as $token) {
            if ($token->getName() === 'ID') {
                $simbolo = $this->buscarSimbolo($token->getLexeme(), 'variavel');
                $tipos[] = $simbolo['tipo'] ?? 'DESCONHECIDO';
            } elseif ($token->getName() === 'CONST') {
                $tipos[] = 'INT';
            } elseif ($token->getName() === 'DECIMAL') {
                $tipos[] = 'FLOAT';
            } elseif ($token->getName() === 'CARAC') {
                $tipos[] = 'CHAR';
            }
        }
    
        // Lógica simplificada: retorna o tipo mais abrangente
        if (in_array('FLOAT', $tipos)) return 'FLOAT';
        if (in_array('INT', $tipos)) return 'INT';
        if (in_array('CHAR', $tipos)) return 'CHAR';
        return null;
    }


    private function tiposCompativeis(string $tipoVariavel, string $tipoExpressao): bool {
        $compatibilidade = [
            'INT' => ['INT'],
            'FLOAT' => ['FLOAT', 'INT'],
            'CHAR' => ['CHAR'],
            'ARRAY' => ['ARRAY']
        ];
    
        return in_array($tipoExpressao, $compatibilidade[$tipoVariavel] ?? []);
    }

    private function verificarUsoVariavel(Token $token, int $indice): void
    {
        $nomeVar = $token->getLexeme();
        $escopoEncontrado = null;

        if ($this->funcaoAtual !== null) {
            foreach ($this->escoposHierarquia[$this->funcaoAtual] ?? [] as $simbolo) {
                if ($simbolo['nome'] === $nomeVar && $simbolo['categoria'] === 'parametro') {
                    $escopoEncontrado = $this->funcaoAtual;
                    break;
                }
            }
        }

        if (!$escopoEncontrado) {
            foreach (array_reverse($this->pilhaEscopos) as $escopo) {
                if (isset($this->escoposHierarquia[$escopo])) {
                    foreach ($this->escoposHierarquia[$escopo] as $simbolo) {
                        if ($simbolo['nome'] === $nomeVar && $simbolo['categoria'] !== 'funcao') {
                            $escopoEncontrado = $escopo;
                            break 2;
                        }
                    }
                }
            }
        }

        // Verifica escopo global
        if (!$escopoEncontrado && isset($this->escoposHierarquia['global'])) {
            foreach ($this->escoposHierarquia['global'] as $simbolo) {
                if ($simbolo['nome'] === $nomeVar && $simbolo['categoria'] !== 'funcao') {
                    $escopoEncontrado = 'global';
                    break;
                }
            }
        }

        if (!$escopoEncontrado) {
            $this->erros[] = "Variável '{$nomeVar}' não declarada (Linha {$token->getLine()})";
        }
    }


    private function verificarChamadasFuncoes(): void
    {
        foreach ($this->tokens as $indice => $token) {
            if (
                $token->getName() === 'ID' && isset($this->tokens[$indice + 1]) &&
                $this->tokens[$indice + 1]->getName() === 'ABRE_PAR'
            ) {

                $funcao = $this->buscarSimbolo($token->getLexeme(), 'funcao');

                if (!$funcao) {
                    $this->erros[] = "Função '{$token->getLexeme()}' não declarada (Linha {$token->getLine()})";
                    continue;
                }

                $parametros = $this->buscarParametrosFuncao($funcao['escopo']);
                $argumentos = $this->extrairArgumentosChamada($indice + 2);

                $this->validarArgumentos($funcao, $parametros, $argumentos, $token->getLine());
            }
        }
    }

    private function buscarParametrosFuncao(string $escopoFuncao): array
    {
        return array_filter(
            $this->tabelaSimbolos,
            fn($s) =>
            $s['escopo'] === $escopoFuncao && $s['categoria'] === 'parametro'
        );
    }

    private function extrairArgumentosChamada(int $inicio): array
    {
        $argumentos = [];
        $nivelParenteses = 1;

        for ($i = $inicio; $i < count($this->tokens); $i++) {
            $token = $this->tokens[$i];

            if ($token->getName() === 'ABRE_PAR') $nivelParenteses++;
            if ($token->getName() === 'FECHA_PAR') $nivelParenteses--;

            if ($nivelParenteses === 0) break;

            if ($token->getName() === 'VIRGULA' && $nivelParenteses === 1) {
                $argumentos[] = [];
                continue;
            }

            $argumentos[count($argumentos) - 1][] = $token;
        }

        return $argumentos;
    }

    private function validarArgumentos(array $funcao, array $parametros, array $argumentos, int $linha): void
    {
        if (count($parametros) !== count($argumentos)) {
            $this->erros[] = "Número de argumentos inválido para '{$funcao['nome']}' (Esperado: " .
                count($parametros) . ", Recebido: " . count($argumentos) . ") Linha {$linha}";
            return;
        }

        foreach ($parametros as $i => $param) {
            $tipoArgumento = $this->determinarTipoArgumento($argumentos[$i]);

            if ($tipoArgumento !== $param['tipo']) {
                $numeroArgumento = $i + 1;
                $this->erros[] = "Tipo inválido para argumento {$numeroArgumento} de '{$funcao['nome']}' " . "(Esperado: {$param['tipo']}, Recebido: {$tipoArgumento}) Linha {$linha}";
            }
        }
    }

    private function determinarTipoArgumento(array $tokens): string
    {
        $primeiroToken = $tokens[0];

        if ($primeiroToken->getName() === 'INT') return 'INT';
        if ($primeiroToken->getName() === 'FLOAT') return 'FLOAT';
        if ($primeiroToken->getName() === 'ID') {
            $var = $this->buscarSimbolo($primeiroToken->getLexeme(), 'variavel');
            return $var['tipo'] ?? 'DESCONHECIDO';
        }

        return 'DESCONHECIDO';
    }

    private function verificarRetornos(): void
    {
        foreach ($this->tokens as $indice => $token) {
            if ($token->getName() === 'RETURN') {
                if ($this->funcaoAtual === null) {
                    $this->erros[] = "Erro semântico: Return fora de função (Linha {$token->getLine()})";
                    continue;
                }

                // Pega o próximo token após RETURN (ignorando possíveis espaços/comentários)
                $proximoToken = $this->tokens[$indice + 1] ?? null;
                $valorRetorno = ($proximoToken && !in_array($proximoToken->getName(), ['FECHA_CHAVES', 'PONTO_VIRGULA']))
                    ? $proximoToken->getLexeme()
                    : '';

                $funcao = $this->buscarSimbolo($this->funcaoAtual, 'funcao');

                if (!$funcao) {
                    $this->erros[] = "Função '{$this->funcaoAtual}' não declarada (Linha {$token->getLine()})";
                    continue;
                }

                if ($funcao['tipo'] !== 'VOID' && empty($valorRetorno)) {
                    $this->erros[] = "Função '{$funcao['nome']}' deve retornar um valor (Linha {$token->getLine()})";
                } elseif ($funcao['tipo'] === 'VOID' && !empty($valorRetorno)) {
                    $this->erros[] = "Função '{$funcao['nome']}' não deve retornar um valor (Linha {$token->getLine()})";
                }
            }
        }
    }

    private function buscarSimbolo(string $nome, string $categoria = null): ?array
    {
        foreach ($this->tabelaSimbolos as $simbolo) {
            if ($simbolo['nome'] === $nome && (!$categoria || $simbolo['categoria'] === $categoria)) {
                return $simbolo;
            }
        }
        return null;
    }

    public function getErros(): array
    {
        return $this->erros;
    }
}
