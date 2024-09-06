<?php

namespace Northrook\UI\Compiler\Node;

use Latte\Compiler\Node;
use Northrook\UI\Compiler\NodeCompiler;
use Northrook\UI\Compiler\NodeCompilerMethods;



/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
final class ChildNode extends NodeCompiler
{
    
    public function __construct(
        protected Node        $node,
        private ?NodeCompiler $parent = null,
    ) {}
}