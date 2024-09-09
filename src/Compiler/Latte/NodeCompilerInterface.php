<?php

namespace Northrook\UI\Compiler\Latte;

use Latte\Compiler\Node;
use Latte\Compiler\Nodes\Html\ElementNode;


interface NodeCompilerInterface
{

    /**
     *
     * @param Node  $node
     *
     * @return Node
     */
    public static function nodeCompiler( ElementNode $node ) : Node;
}