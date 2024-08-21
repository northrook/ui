<?php

declare( strict_types = 1 );

namespace Northrook\UI\Compiler;

/**
 *
 */
interface ComponentInterface
{

    public function __construct(
        array $config = [],
    );
}