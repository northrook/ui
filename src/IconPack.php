<?php

namespace Northrook\UI;

// :: The current icon pack - statically accessible

use Northrook\HTML\Element;
use Northrook\Trait\SingletonClass;


final class IconPack
{
    use SingletonClass;

    private const array DEFAULT = [
        'success' => '<path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>',
        'info'    => '<path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>',
        'danger'  => '<path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>',
        'warning' => '<path fill-rule="evenodd" clip-rule="evenodd" d="M9.336.757c-.594-1.01-2.078-1.01-2.672 0L.21 11.73C-.385 12.739.357 14 1.545 14h12.91c1.188 0 1.93-1.261 1.336-2.27L9.336.757ZM9 4.5C9 4 9 4 8 4s-1 0-1 .5l.383 3.538c.103.505.103.505.617.505s.514 0 .617-.505L9 4.5Zm-1 7.482c1.028 0 1.028 0 1.028-1.01 0-1.009 0-1.009-1.028-1.009s-1.028.094-1.028 1.01c0 1.008 0 1.008 1.028 1.008Z"/>',
        'notice'  => '<path fill-rule="evenodd" clip-rule="evenodd" d="M6.983 1.006a.776.776 0 0 1 .667.634l1.781 9.967 1.754-3.925a.774.774 0 0 1 .706-.46h3.335c.427 0 .774.348.774.778 0 .43-.347.778-.774.778h-2.834L9.818 14.54a.774.774 0 0 1-1.468-.181L6.569 4.393 4.816 8.318a.774.774 0 0 1-.707.46H.774A.776.776 0 0 1 0 8c0-.43.347-.778.774-.778h2.834L6.182 1.46a.774.774 0 0 1 .8-.453Z"/>',
    ];

    private array $defaultAttributes = [
        'class'   => 'icon',
        'fill'    => 'none',
        'viewbox' => '0 0 16 16',
    ];

    public function __construct()
    {
        $this->instantiationCheck();
        $this::$instance = $this;
    }

    public static function get( string $icon, ?string $fallback = null, bool $asElement = false,
    ) : null | string | Element
    {
        return IconPack::getInstance( true )->getIcon( $icon, $fallback, $asElement );
    }

    public function getIcon( string $icon, ?string $fallback, bool $asElement = false ) : null | string | Element
    {
        $vector = $this->resolveIconAsset( $icon )
                  ?? IconPack::DEFAULT[ $icon ]
                     ?? IconPack::DEFAULT[ $fallback ]
                        ?? null;

        if ( !$vector ) {
            return null;
        }

        $svg = new Element( 'svg', $this->defaultAttributes, $vector );

        return $asElement ? $svg : $svg->toString();
    }

    private function resolveIconAsset( string $icon ) : ?string
    {
        return null;
    }

}