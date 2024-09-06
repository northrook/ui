<?php

declare( strict_types = 1 );

namespace Northrook\UI\Latte;

use Latte\Compiler\Node;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Runtime\HtmlStringable;


/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
interface RuntimeRenderInterface extends HtmlStringable
{

    /**
     *
     * @param Node  $node
     *
     * @return Node
     */
    public static function nodeCompiler( Node $node ) : AuxiliaryNode;

    /**
     *
     * @return string
     */
    public static function runtimeRender() : string;

    /** in HTML format */
    public function __toString() : string;
}