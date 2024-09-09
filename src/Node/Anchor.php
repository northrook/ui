<?php

namespace Northrook\UI\Node;

use Latte\Compiler\Node;
use Latte\Compiler\Nodes\Html\ElementNode;
use Northrook\UI\Compiler\Element;
use Northrook\UI\Compiler\Latte\NodeCompilerInterface;


class Anchor extends Element implements NodeCompilerInterface
{
    public static function nodeCompiler( ElementNode $node ) : Node
    {
        return $node;
    }
}