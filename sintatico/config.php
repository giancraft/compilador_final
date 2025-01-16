<?php

// Gramática com produções
$gramatica = [
    ['cabeca' => '<PROGRAMA>', 'corpo' => 'PROGRAM ID ABRE_PAR <VARS> FECHA_PAR ABRE_CHAVES <COMANDOS> FECHA_CHAVES'],
    ['cabeca' => '<VARS>', 'corpo' => '<VAR> <VARS>'],
    ['cabeca' => '<VARS>', 'corpo' => 'î'],
    ['cabeca' => '<VAR>', 'corpo' => '<TIPO> ID PV'],
    ['cabeca' => '<TIPO>', 'corpo' => 'INT'],
    ['cabeca' => '<TIPO>', 'corpo' => 'CHAR'],
    ['cabeca' => '<TIPO>', 'corpo' => 'FLOAT'],
    ['cabeca' => '<TIPO>', 'corpo' => 'ARRAY'],
    ['cabeca' => '<COMANDOS>', 'corpo' => '<COMANDO> <COMANDOS>'],
    ['cabeca' => '<COMANDOS>', 'corpo' => 'î'],
    ['cabeca' => '<COMANDO>', 'corpo' => '<ATRIBUICAO>'],
    ['cabeca' => '<COMANDO>', 'corpo' => '<LEITURA>'],
    ['cabeca' => '<COMANDO>', 'corpo' => '<IMPRESSAO>'],
    ['cabeca' => '<COMANDO>', 'corpo' => '<RETORNO>'],
    ['cabeca' => '<COMANDO>', 'corpo' => '<CHAMADA_FUNCAO>'],
    ['cabeca' => '<COMANDO>', 'corpo' => '<IF>'],
    ['cabeca' => '<COMANDO>', 'corpo' => '<FOR>'],
    ['cabeca' => '<COMANDO>', 'corpo' => '<WHILE>'],
    ['cabeca' => '<ATRIBUICAO>', 'corpo' => 'ID ATR <EXPRESSAO> PV'],
    ['cabeca' => '<LEITURA>', 'corpo' => 'READ ABRE_PAR ID FECHA_PAR PV'],
    ['cabeca' => '<IMPRESSAO>', 'corpo' => 'PRINT ABRE_PAR <EXPRESSAO> FECHA_PAR PV'],
    ['cabeca' => '<RETORNO>', 'corpo' => 'RETURN <EXPRESSAO> PV'],
    ['cabeca' => '<CHAMADA_FUNCAO>', 'corpo' => 'ID ABRE_PAR <EXPRESSAO> FECHA_PAR PV'],
    ['cabeca' => '<IF>', 'corpo' => 'IF ABRE_PAR <EXPRESSAO> FECHA_PAR ABRE_CHAVES <COMANDOS> FECHA_CHAVES'],
    ['cabeca' => '<FOR>', 'corpo' => 'FOR ABRE_PAR <ATRIBUICAO> <EXPRESSAO> PV <ATRIBUICAO> FECHA_PAR ABRE_CHAVES <COMANDOS> FECHA_CHAVES'],
    ['cabeca' => '<WHILE>', 'corpo' => 'WHILE ABRE_PAR <EXPRESSAO> FECHA_PAR ABRE_CHAVES <COMANDOS> FECHA_CHAVES'],
    ['cabeca' => '<EXPRESSAO>', 'corpo' => '<TERMO> <OPERADORES_PRIMARIOS> <EXPRESSAO>'],
    ['cabeca' => '<OPERADORES_PRIMARIOS>', 'corpo' => 'SOMA'],
    ['cabeca' => '<OPERADORES_PRIMARIOS>', 'corpo' => 'SUBTRACAO'],
    ['cabeca' => '<OPERADORES_PRIMARIOS>', 'corpo' => '<OPERADOR_LOGICO>'],
    ['cabeca' => '<OPERADOR_LOGICO>', 'corpo' => 'COMP'],
    ['cabeca' => '<OPERADOR_LOGICO>', 'corpo' => 'DIF'],
    ['cabeca' => '<OPERADOR_LOGICO>', 'corpo' => 'MENOR'],
    ['cabeca' => '<OPERADOR_LOGICO>', 'corpo' => 'MAIOR'],
    ['cabeca' => '<OPERADOR_LOGICO>', 'corpo' => 'MENORIGUAL'],
    ['cabeca' => '<OPERADOR_LOGICO>', 'corpo' => 'MAIORIGUAL'],
    ['cabeca' => '<OPERADOR_LOGICO>', 'corpo' => 'NEGACAO'],
    ['cabeca' => '<TERMO>', 'corpo' => '<FATOR> <OPERADORES_SECUNDARIOS> <TERMO>'],
    ['cabeca' => '<OPERADORES_SECUNDARIOS>', 'corpo' => 'MULTIPLICACAO'],
    ['cabeca' => '<OPERADORES_SECUNDARIOS>', 'corpo' => 'DIVISAO'],
    ['cabeca' => '<FATOR>', 'corpo' => 'ID'],
    ['cabeca' => '<FATOR>', 'corpo' => 'CONST'],
    ['cabeca' => '<FATOR>', 'corpo' => 'ABRE_PAR <EXPRESSAO> FECHA_PAR']
];

// Tabela de Ações (SHIFT, REDUCE, ACCEPT, ERROR)
$tabelaAcao = [
    0 => ['PROGRAM' => 'SHIFT 1'],
    1 => ['ID' => 'SHIFT 2'],
    2 => ['ABRE_PAR' => 'SHIFT 3'],
    3 => ['INT' => 'SHIFT 4', 'CHAR' => 'SHIFT 5', 'FLOAT' => 'SHIFT 6', 'ARRAY' => 'SHIFT 7', 'FECHA_PAR' => 'REDUCE 3'],
    4 => ['ID' => 'SHIFT 8'],
    5 => ['ID' => 'SHIFT 8'],
    6 => ['ID' => 'SHIFT 8'],
    7 => ['ID' => 'SHIFT 8'],
    8 => ['PV' => 'SHIFT 9'],
    9 => ['INT' => 'REDUCE 2', 'CHAR' => 'REDUCE 2', 'FLOAT' => 'REDUCE 2', 'ARRAY' => 'REDUCE 2', 'FECHA_PAR' => 'REDUCE 2'],
    10 => ['FECHA_PAR' => 'SHIFT 11'],
    11 => ['ABRE_CHAVES' => 'SHIFT 12'],
    12 => ['ID' => 'SHIFT 13', 'IF' => 'SHIFT 14', 'WHILE' => 'SHIFT 15', 'FOR' => 'SHIFT 16', 'FECHA_CHAVES' => 'REDUCE 9'],
    13 => ['ATR' => 'SHIFT 17'],
    14 => ['ABRE_PAR' => 'SHIFT 18'],
    15 => ['ABRE_PAR' => 'SHIFT 19'],
    16 => ['ABRE_PAR' => 'SHIFT 20'],
    17 => ['ID' => 'SHIFT 21', 'CONST' => 'SHIFT 22', 'ABRE_PAR' => 'SHIFT 23'],
    18 => ['ID' => 'SHIFT 24', 'CONST' => 'SHIFT 25', 'ABRE_PAR' => 'SHIFT 26'],
    // Continue preenchendo o restante dos estados...
];

// Tabela Ir Para (transições entre estados para não-terminais)
$tabelaIrPara = [
    0 => ['<PROGRAMA>' => 1],
    3 => ['<VARS>' => 10],
    12 => ['<COMANDOS>' => 27],
    13 => ['<ATRIBUICAO>' => 28],
    14 => ['<IF>' => 29],
    15 => ['<WHILE>' => 30],
    16 => ['<FOR>' => 31],
    17 => ['<EXPRESSAO>' => 32],
    // Continue preenchendo o restante das transições...
];

return [$gramatica, $tabelaAcao, $tabelaIrPara];
