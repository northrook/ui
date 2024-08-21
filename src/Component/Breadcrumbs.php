<?php

namespace Northrook\UI\Component;

use Latte\Runtime\Html;
use Latte\Runtime\HtmlStringable;
use Northrook\UI\Compiler\Component;
use Northrook\UI\Component\Breadcrumbs\Item;
use Northrook\Trait\PropertyAccessor;
use Northrook\UI\Component\Breadcrumbs\Trail;
use function Northrook\normalizePath;

/**
 * @link https://webmasters.stackexchange.com/a/79400 // HTML markup
 * @link https://www.smashingmagazine.com/2020/01/html5-article-section
 * @link http://microformats.org/wiki/breadcrumbs-formats
 * @link https://www.aditus.io/patterns/breadcrumbs
 * @link https://www.w3.org/WAI/ARIA/apg/patterns/breadcrumb/examples/breadcrumb
 */
class Breadcrumbs extends Component
{
    protected const string   SCHEMA = 'RDFa';
    protected const ?string  TYPE   = 'breadcrumbs';

    final public function __construct(
        array                          $attributes = [],
        private readonly array | Trail $breadcrumbs = [],
    ) {
        parent::__construct( $attributes );
        $this->attributes->class->add( 'breadcrumbs' );
    }

    protected function render() : string {
        return $this->latte( __DIR__ . '/Breadcrumbs/breadcrumbs.latte' );
    }

    final protected function getBreadcrumbTrail() : array {
        if ( $this->breadcrumbs instanceof Trail ) {
            return $this->breadcrumbs->getBreadcrumbs();
        }
        return $this->breadcrumbs;
    }

    final public function list() : array {
        $trail = [ 0 => '0th', ...$this->getBreadcrumbTrail() ];
        unset( $trail[ 0 ] );
        return $trail;
    }

    // final public static function trail() : Trail{
    //     return new Trail();
    // }

    static public function getAssets() : array {
        return [
            __DIR__ . '/Breadcrumbs/breadcrumbs.css',
            __DIR__ . '/Breadcrumbs/breadcrumbs.js',
        ];
    }

    protected function templatePath() : string {
        return normalizePath( __DIR__ . '/Breadcrumbs/breadcrumbs.latte' );
    }
}