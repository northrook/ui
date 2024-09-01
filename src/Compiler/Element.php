<?php

declare( strict_types = 1 );

namespace Northrook\UI\Compiler;

use Northrook\HTML\AbstractElement;
use Northrook\UI\Latte\RuntimeRenderInterface;


/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
abstract class Element extends AbstractElement implements RuntimeRenderInterface {
}