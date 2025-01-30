<?php

class NoArvore {
    public string $simbolo;
    public array $filhos;

    public function __construct(string $simbolo) {
        $this->simbolo = $simbolo;
        $this->filhos = [];
    }

    public function adicionarFilho(NoArvore $filho): void {
        $this->filhos[] = $filho;
    }
}