<?php

namespace Northrook\UI\Element;

use Latte\Compiler\Node;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Compiler\PrintContext;
use Northrook\Minify;
use Northrook\UI\Compiler\Element;
use Northrook\UI\Compiler\NodeCompiler;


class Image extends Element
{

    public static function nodeCompiler( Node $node ) : AuxiliaryNode
    {
        $node = new NodeCompiler( $node );
        return new AuxiliaryNode(
            function() use ( $node )
            {
                return "echo '" . Minify::HTML( $node->printNode() ) . "'; /* From UI */";
            },
        );
    }

    public static function runtimeRender() : string
    {
        return __CLASS__;
    }
}