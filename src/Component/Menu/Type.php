<?php

namespace Northrook\UI\Component\Menu;

enum Type : string
{
    case Navigation = 'navigation'; // primary type; sidebar, header, mega etc
    case Dropdown = 'dropdown';     // list of options
}