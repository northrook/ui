<?php

declare(strict_types=1);

namespace Northrook\UI\Compiler\Latte;

use JetBrains\PhpStorm\Deprecated;
use Latte\Compiler\Node;
use Latte\Runtime\HtmlStringable;
use Northrook\UI\Compiler\NodeCompiler;

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
interface RuntimeRenderInterface extends HtmlStringable
{
    /**
     * @param NodeCompiler $node
     *
     * @return Node
     */
    public static function nodeCompiler( NodeCompiler $node ) : Node;

    /**
     * Returns HTML.
     *
     * - Handles provided arguments.
     * - Instantiates the parent {@see __construct} method.
     * - Returns the {@see __toString} function.
     *
     * @return string
     */
    public static function runtimeRender() : string;

    /**
     * # ❗
     * Ensure all HTML is properly escaped and valid before returning this method.
     *
     * @return string of valid HTML
     */
    public function __toString() : string;
}
