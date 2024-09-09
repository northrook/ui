<?php

declare( strict_types = 1 );

namespace Northrook\UI;

use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Cache\CacheItem;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Northrook\UI\Compiler\NodeExporter;
use Northrook\Logger\Log;
use Northrook\Minify;
use function Northrook\{classBasename, hashKey, normalizeKey};
use const Cache\{AUTO, DISABLED, EPHEMERAL};


/**
 * @internal
 */
final class RenderRuntime
{
    private const string METHOD = 'runtimeRender';

    /**
     * `[ className => componentName ]`
     *
     * @var array<class-string, non-empty-string>
     */
    private static array $called;

    private static array $argumentCache = [];

    /**
     * @param ?CacheInterface          $cache
     * @param array{string, callable}  $argumentCallback
     */
    public function __construct(
        private readonly ?CacheInterface $cache = null,
        private array                    $argumentCallback = [],
    )
    {
        // Cleared on instantiation for concurrency compatability
        $this::$called = [];

        foreach ( $this->argumentCallback as $renderName => $argumentCallback ) {
            $this->addArgumentCallback( $renderName, $argumentCallback );
        }
    }

    public static function auxiliaryNode(
        string $renderName,
        array  $arguments = [],
        ?int   $cache = AUTO,
    ) : AuxiliaryNode
    {
        return new AuxiliaryNode(
            static fn() : string => 'echo $this->global->render->__invoke(
                className: ' . NodeExporter::string( $renderName ) . ',
                arguments: ' . NodeExporter::arguments( $arguments ) . ',
                cache    : ' . NodeExporter::cacheConstant( $cache ) . '
             );',
        );
    }

    public function __invoke(
        string $className,
        array  $arguments = [],
        ?int   $cache = AUTO,
    ) : ?string
    {
        if ( !$this->validate( $className ) ) {
            return null;
        }

        $this::registerInvocation( $className );

        $arguments = $this->invokedArguments( $className, $arguments );

        if ( $cache === EPHEMERAL || $cache === DISABLED || !$this->cache ) {
            return [ $className, $this::METHOD ]( ...$arguments );
        }

        try {
            return $this->cache->get(
                normalizeKey( [ $className, hashKey( $arguments ) ], '.' ),
                function( CacheItem $item ) use ( $className, $arguments, $cache ) : string
                {
                    $item->expiresAfter( $cache );
                    $string = [ $className, $this::METHOD ]( ...$arguments );

                    return Minify::HTML( $string );
                },
            );
        }
        catch ( InvalidArgumentException $exception ) {
            Log::exception( $exception );
            return null;
        }
    }

    /**
     *
     *
     * @param class-string  $className
     * @param callable      $callback
     *
     * @return void
     */
    public function addArgumentCallback( string $className, callable $callback ) : void
    {
        $this->argumentCallback[ $className ] = $callback;
    }

    private function validate( string $className ) : bool
    {
        if ( !\method_exists( $className, $this::METHOD ) ) {
            Log::error(
                'Runtime invocation of {className} aborted; the class does not have the {method} method.',
                [
                    'className' => $className,
                    'method'    => $this::METHOD,
                ],
            );
            return false;
        }
        return true;
    }

    public static function getCalledInvocations() : array
    {
        return RenderRuntime::$called;
    }

    public static function registerInvocation( string $className ) : void
    {
        if ( isset( RenderRuntime::$called[ $className ] ) ) {
            return;
        }
        RenderRuntime::$called[ $className ] = classBasename( $className );
    }

    private function invokedArguments( string $className, array $arguments ) : array
    {
        if ( \array_key_exists( $className, $this->argumentCallback ) ) {
            $cacheKey  = "$className:" . hashKey( $arguments );
            $arguments = self::$argumentCache[ $cacheKey ] ??= ( $this->argumentCallback[ $className ] )( $arguments );
        }

        return $arguments;
    }
}