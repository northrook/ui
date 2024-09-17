<?php

namespace Northrook\UI\Compiler;

use JetBrains\PhpStorm\Language;


final class Template
{

    public function __construct(
        #[Language( 'HTML' )]
        private string $template,
        private mixed  $content,
    ) {}

    public function render(): string
    {
        $content = $this->content;
        return $this->template;
    }
}