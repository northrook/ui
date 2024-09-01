<?php

declare( strict_types = 1 );

namespace Northrook\UI\Latte\Extension;

use Latte\Compiler\Node;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Compiler\Nodes\Html\ElementNode;
use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\NodeTraverser;
use Latte\Compiler\PrintContext;
use Northrook\Latte\Compiler\CompilerPassExtension;
use Northrook\UI\Compiler\NodeCompilerTrait;
use Northrook\UI\IconPack;
use Northrook\UI\Latte\LatteRuntime;


final class ComponentExtension extends CompilerPassExtension
{
    use NodeCompilerTrait;

    public readonly IconPack     $iconPack;
    public readonly LatteRuntime $componentRuntime;

    public function __construct(
        ?LatteRuntime $componentRuntime = null,
        ?IconPack     $iconPack = null,
    ) {
        $this->componentRuntime = $componentRuntime ?? new LatteRuntime();
        $this->iconPack         = $iconPack ?? new IconPack();
        // dump( $this);
    }

    public function traverseNodes() : array {
        // dump( $this);
        return [
            [ $this, 'runtimeComponents' ],
        ];
    }

    public function runtimeComponents( Node $node ) : Node | int {

        if ( $node instanceof ExpressionNode ) {
            return NodeTraverser::DontTraverseChildren;
        }

        if ( !$node instanceof ElementNode ) {
            return $node;
        }

        if ( \str_starts_with( $node->name, 'ui:' ) ) {
            return $this->resolveLatteComponent( \substr( $node->name, 3 ), $node );
        }

        return match ( $node->name ) {
            // 'ui:breadcrumbs', => $this->resolveLatteComponent( 'breadcrumbs', $node ),
            // 'code', 'pre', 'code:block' => $this->resolveLatteComponent( 'highlighter', $node ),
            default           => $node
        };
    }

    protected function resolveLatteComponent( string $component, ElementNode $node ) : Node {
        if ( !\array_key_exists( $component, $this->componentRuntime::COMPONENTS ) ) {
            throw new \BadMethodCallException();
        }

        // dump( $this);
        $node = new AuxiliaryNode(
            function ( PrintContext $printContext ) use ( $component, $node ) : string {
                [ $attributes, $variables ] = $this->resolveNodeArguments( $printContext, $node );
                return 'echo $this->global->component->' . $component . '( ' . $attributes . ', ' . $variables . ' );';

            },
        );
        // dump( $node);
        return $node;
    }

    public function getProviders() : array {
        return [
            'component' => $this->componentRuntime,
        ];
    }
}