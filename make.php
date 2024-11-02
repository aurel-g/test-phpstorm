<?php

require __DIR__ . '/vendor/autoload.php';

const NSPACE = 'AurelG\\TestPhpStorm';
const TRAITS_DIR = __DIR__ . '/src/Traits/';
const TRAITS_NUMBER = 200;
const TRAITS_METHODS_NUMBER = 20;
const CLASS_NAME = 'ClassUsingManyTraits';

$f = new PhpParser\BuilderFactory();
$pp = new PhpParser\PrettyPrinter\Standard();

$class = $f->class(CLASS_NAME);
$script = $f->namespace(NSPACE.'BuggyScript')
    ->addStmt(new \PhpParser\Node\Expr\Assign($f->var('obj'), $f->new('\\'.NSPACE.'\\'.CLASS_NAME)))
    ->addStmt(new \PhpParser\Node\Expr\Assign($f->var('n'), $f->val(0)));

for ($i=1; $i<=TRAITS_NUMBER; $i++) {
    $trait = $f->trait('Trait'.$i);
    for ($j=1; $j<=TRAITS_METHODS_NUMBER; $j++) {
        $methodName = 'Trait'.$i.'Method'.$j;
        $trait->addStmt(
            $f->method($methodName)
                ->makePublic()
                ->setReturnType('int')
                ->addStmt(new \PhpParser\Node\Stmt\Return_($f->val(1)))
        );
        $script->addStmt(
            new \PhpParser\Node\Expr\AssignOp\Plus(
                $f->var('n'),
                $f->methodCall($f->var('obj'), $methodName)
            )
        );
    }
    file_put_contents(TRAITS_DIR.'Trait'.$i.'.php', $pp->prettyPrintFile(
        [$f->namespace(NSPACE . '\\Traits')->addStmt($trait)->getNode()]
    ));
    $class->addStmt($f->useTrait('Traits\\Trait'.$i));
}

$script->addStmt($f->funcCall('var_dump', [$f->var('n')]));

file_put_contents(__DIR__ . '/src/'.CLASS_NAME.'.php', $pp->prettyPrintFile(
    [$f->namespace(NSPACE)->addStmt($class)->getNode()]
));

file_put_contents(
    __DIR__ . '/buggy_script/example.php',
    preg_replace(
        '/namespace (.+)/',
        '$0'.PHP_EOL.PHP_EOL."require __DIR__ . '/../vendor/autoload.php';".PHP_EOL.PHP_EOL,
        $pp->prettyPrintFile([$script->getNode()])
    )
);
