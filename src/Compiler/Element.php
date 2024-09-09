<?php

declare( strict_types = 1 );

namespace Northrook\UI\Compiler;

use Northrook\HTML\AbstractElement;
use Northrook\UI\Compiler\Latte\NodeCompilerInterface;
use Northrook\UI\RenderRuntime;


/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
abstract class Element
    extends AbstractElement
    implements NodeCompilerInterface
{
    use NodeCompilerMethods;


    final public function __toString() : string
    {
        RenderRuntime::registerInvocation( $this::class );
        return parent::__toString();
    }
}