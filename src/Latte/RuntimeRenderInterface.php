<?php

namespace Northrook\UI\Latte;

use Latte\Runtime\HtmlStringable;

interface RuntimeRenderInterface extends HtmlStringable {

    public static function runtimeRender(
        array $attributes = [],
    ) : self;

    /** in HTML format */
    public function __toString() : string;
}