<?php

namespace Northrook\UI\Component;

use Latte\Compiler\Node;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Northrook\UI\Compiler\Component;
use Northrook\UI\Compiler\NodeCompiler;
use Northrook\UI\Component\Breadcrumbs\Trail;
use Northrook\UI\Latte\RenderRuntime;


/**
 * @link https://webmasters.stackexchange.com/a/79400 // HTML markup
 * @link https://www.smashingmagazine.com/2020/01/html5-article-section
 * @link http://microformats.org/wiki/breadcrumbs-formats
 * @link https://www.aditus.io/patterns/breadcrumbs
 * @link https://www.w3.org/WAI/ARIA/apg/patterns/breadcrumb/examples/breadcrumb
 */
class Breadcrumbs extends Component
{

    public const string      SCHEMA = 'RDFa';
    protected const ?string  TYPE   = 'breadcrumbs';

    final public function __construct(
        array                          $attributes = [],
        private readonly array | Trail $breadcrumbs = [],
    )
    {
        parent::__construct( $attributes )
            ->attributes->add( 'class', 'breadcrumbs' );
    }

    protected function render() : string
    {
        return $this->latte( __DIR__ . '/Breadcrumbs/breadcrumbs.latte' );
    }

    final protected function getBreadcrumbTrail() : array
    {
        if ( $this->breadcrumbs instanceof Trail ) {
            return $this->breadcrumbs->getBreadcrumbs();
        }
        return $this->breadcrumbs;
    }

    final public function list() : array
    {
        $trail = [ 0 => '0th', ...$this->getBreadcrumbTrail() ];
        unset( $trail[ 0 ] );
        return $trail;
    }

    static public function getAssets() : array
    {
        return [
            __DIR__ . '/Breadcrumbs/breadcrumbs.css',
            __DIR__ . '/Breadcrumbs/breadcrumbs.js',
        ];
    }

    public static function nodeCompiler( Node $node ) : AuxiliaryNode
    {
        $node = new NodeCompiler( $node );
        [ $attributes, $variables ] = $node->resolveComponentArguments();
        return RenderRuntime::auxiliaryNode(
            Breadcrumbs::class,
            [
                $attributes,
                $variables,
            ],
        );
    }

    public static function runtimeRender( array $attributes = [], array | Trail $breadcrumbs = [] ) : string
    {
        return (string) new self( $attributes, ...$breadcrumbs );
    }
}