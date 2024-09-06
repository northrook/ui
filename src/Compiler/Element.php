<?php

declare( strict_types = 1 );

namespace Northrook\UI\Compiler;

use Latte\Compiler\Node;
use Latte\Compiler\Nodes\Html\ElementNode;
use Northrook\HTML\AbstractElement;
use Northrook\UI\Latte\RuntimeRenderInterface;


/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
abstract class Element
    extends AbstractElement
    implements RuntimeRenderInterface
{
    use NodeCompilerMethods;
}