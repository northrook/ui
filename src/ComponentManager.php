<?php

namespace Northrook\UI;

class ComponentManager
{

    public readonly IconPack $iconPack;
    public readonly string   $codeHighlightTheme;

    public function __construct(
        ?IconPack $iconPack = null,
        ?string   $codeHighlightTheme = null,
    )
    {
        $this->iconPack           = $iconPack ?? new IconPack();
        $this->codeHighlightTheme = 'C:\laragon\www\ui\vendor\tempest\highlight\src\Themes\Css\nord.css';
    }
}