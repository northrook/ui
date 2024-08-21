<?php

declare( strict_types = 1 );

namespace Northrook\UI\Latte;

use Latte\Runtime\Html;
use Latte\Runtime\HtmlStringable;
use Northrook\UI\Component\Breadcrumbs;
use Northrook\UI\Component\Highlighter;
use Northrook\UI\Component\Menu;
use Northrook\UI\Component\Notification;
use Northrook\Logger\Log;
use Northrook\Trait\SingletonClass;
use Psr\Log\LoggerInterface;

/**
 */
final class ComponentRuntime
{
    use SingletonClass;

    /**
     * @var array{non-empty-string: class-string}
     */
    public const array COMPONENTS = [
        'breadcrumbs'  => Breadcrumbs::class,
        'notification' => Notification::class,
        // 'highlighter'  => Highlighter::class,
        // 'menu'         => Menu::class,
    ];

    private array $calledComponents = [];

    public function __construct(
        private readonly array $componentCallback = [],
    ) {
        $this->instantiationCheck();
        $this::$instance = $this;
    }

    public static function getCalled() : array {
        return ComponentRuntime::getInstance()->calledComponents;
    }

    public function __call( string $name, array $arguments ) : ?HtmlStringable {

        // Retrieve the component classname, return null on failure
        if ( !$component = $this->registeredComponent( $name ) ) {
            return null;
        }

        if ( \array_key_exists( $name, $this->componentCallback ) ) {
            $arguments = ( $this->componentCallback[ $name ] )( $arguments );
        }
        $render = new ( $component )( ...$arguments );

        if ( !$render instanceof HtmlStringable ) {
            Log::error(
                'Unable to call the {name} component {component}, it does not implement the {interface}.',
                [
                    'name'      => $name,
                    'component' => $component,
                    'interface' => HtmlStringable::class,
                ],
            );
            return null;
        }

        if ( !isset( $this->calledComponents[ $component ] ) ) {
            $this->calledComponents[ $name ] = $component;
        }

        return new Html( (string) $render );
        // return $component;
    }

    private function registeredComponent( string $name ) : false | string {

        if ( !\array_key_exists( $name, self::COMPONENTS ) ) {
            Log::notice(
                'Call to undefined component {name}.',
                [ 'name' => $name ],
            );
            return false;
        }

        $component = ComponentRuntime::COMPONENTS[ $name ] ?? null;

        if ( !\class_exists( $component ) ) {
            Log::alert(
                'Component {name} could not be rendered, the registered class {class} does not exist.',
                [ 'name' => $name, 'class' => $component ],
            );
            return false;
        }

        return $component;
    }
}