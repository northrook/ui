<?php

namespace Northrook\UI\Component;

use Latte\Compiler\Node;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Compiler\Nodes\Html\ElementNode;
use Northrook\HTML\Element;
use Northrook\UI\Compiler\AbstractComponent;
use Northrook\UI\Compiler\NodeCompiler;
use Northrook\UI\Component\Breadcrumbs\Trail;
use Northrook\UI\RenderRuntime;


/**
 * @link https://webmasters.stackexchange.com/a/79400 // HTML markup
 * @link https://www.smashingmagazine.com/2020/01/html5-article-section
 * @link http://microformats.org/wiki/breadcrumbs-formats
 * @link https://www.aditus.io/patterns/breadcrumbs
 * @link https://www.w3.org/WAI/ARIA/apg/patterns/breadcrumb/examples/breadcrumb
 */
class Breadcrumbs extends AbstractComponent
{

    public const string SCHEMA = 'RDFa';

    private readonly ?Element $component;
    public readonly Trail     $breadcrumbs;

    final public function __construct(
        ?Trail                   $breadcrumbs = null,
        array                    $attributes = [],
        private readonly ?string $parent = null,
    )
    {
        $this->component = $parent ? new Element( $parent, $attributes ) : new Element( 'ol', $attributes );
        $this->component->class( 'breadcrumbs' );
        $this->breadcrumbs = $breadcrumbs ?? new Trail();
    }

    protected function build() : string
    {
        $breadcrumbs = [];

        foreach ( $this->breadcrumbs as $index => $item ) {
            $label = "{$item->icon}<span property=\"name\">{$item->title}</span>";
            if ( $item->href ) {
                $trail = Element::a( $label, $item->href, target : '_self', property : 'item', typeOf : 'WebPage' );
            }
            else {
                $trail = $label;
            }

            $breadcrumbs[] = Element::li(
                content  : [
                               $trail,
                               Element::meta( property : 'position', content : $index + 1 ),
                           ],
                class    : $item->classes,
                property : 'itemListElement',
                typeof   : 'ListItem',
            )->toString();
        }

        $attributes = [
            'class'  => 'breadcrumbs',
            'vocab'  => 'https://schema.org/',
            'typeof' => 'BreadcrumbList',
        ];

        if ( $this->parent ) {
            $this->component->content( new Element( 'ol', $attributes, $breadcrumbs ) );
        }
        else {
            $this->component
                ->content( $breadcrumbs )
                ->attributes( $attributes )
            ;
        }

        return $this->component;
    }

    static public function getAssets() : array
    {
        return [
            __DIR__ . '/Breadcrumbs/breadcrumbs.css',
            __DIR__ . '/Breadcrumbs/breadcrumbs.js',
        ];
    }

    public static function nodeCompiler( NodeCompiler $node ) : AuxiliaryNode
    {
        return RenderRuntime::auxiliaryNode(
            renderName : Breadcrumbs::class,
            arguments  : [
                             $node->arguments()[ 'breadcrumbs' ] ?? [],
                             $node->attributes(),
                             $node->tag( 'nav' ),
                         ],
        );
    }

    public static function runtimeRender(
        array | Trail $breadcrumbs = [],
        array         $attributes = [],
        ?string       $tag = null,
    ) : string
    {
        return (string) new Breadcrumbs( $breadcrumbs, $attributes, $tag );
    }
}