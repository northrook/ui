<?php

declare( strict_types = 1 );

namespace Northrook\UI\Compiler\Latte;

use Latte\Runtime\HtmlStringable;


/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
interface RuntimeRenderInterface extends HtmlStringable, NodeCompilerInterface
{

    /**
     *
     * @return string
     */
    public static function runtimeRender() : string;

    /** in HTML format */
    public function __toString() : string;
}