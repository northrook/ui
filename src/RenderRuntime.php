<?php

declare(strict_types=1);

namespace Northrook\UI;

use Psr\Cache\InvalidArgumentException;
use Support\Normalize;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Cache\CacheItem;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Northrook\UI\Compiler\NodeExporter;
use Northrook\Logger\Log;
use function String\hashKey;
use function Support\classBasename;
use const Cache\{AUTO, DISABLED, EPHEMERAL};

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
final class RenderRuntime
{
    private const string METHOD = 'runtimeRender';

    /**
     * `[ className => componentName ]`.
     *
     * @var array<class-string, non-empty-string>
     */
    private static array $called;

    private static array $argumentCache = [];

    /**
     * @param ?CacheInterface         $cache
     * @param array{string, callable} $argumentCallback
     */
    public function __construct(
        private readonly ?CacheInterface $cache = null,
        private array                    $argumentCallback = [],
    ) {
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
    ) : AuxiliaryNode {
        return new AuxiliaryNode(
            static fn() : string => <<<'EOD'
                echo $this->global->render->__invoke(
                                className: 
                EOD.NodeExporter::string( $renderName ).<<<'EOD'
                ,
                                arguments: 
                EOD.NodeExporter::arguments( $arguments ).<<<'EOD'
                ,
                                cache    : 
                EOD.NodeExporter::cacheConstant( $cache ).<<<'EOD'

                             );
                EOD,
        );
    }

    public function __invoke(
        string $className,
        array  $arguments = [],
        ?int   $cache = AUTO,
    ) : ?string {

        if ( ! $this->validate( $className ) ) {
            return null;
        }

        // DEBUGGING
        $cache = DISABLED;

        $this::registerInvocation( $className );

        $arguments = $this->invokedArguments( $className, $arguments );

        if ( EPHEMERAL <= $cache || ! $this->cache ) {
            return [$className, $this::METHOD]( ...$arguments );
        }

        try {
            return $this->cache->get(
                Normalize::key( [$className, hashKey( $arguments )], '.' ),
                function( CacheItem $item ) use ( $className, $arguments, $cache ) : string {
                    $item->expiresAfter( $cache );
                    return [$className, $this::METHOD]( ...$arguments );
                },
            );
        }
        catch ( InvalidArgumentException $exception ) {
            Log::exception( $exception );
            return null;
        }
    }

    /**
     * @param class-string $className
     * @param callable     $callback
     *
     * @return void
     */
    public function addArgumentCallback( string $className, callable $callback ) : void
    {
        $this->argumentCallback[$className] = $callback;
    }

    private function validate( string $className ) : bool
    {
        if ( ! \method_exists( $className, $this::METHOD ) ) {
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
        if ( isset( RenderRuntime::$called[$className] ) ) {
            return;
        }
        RenderRuntime::$called[$className] = classBasename( $className );
    }

    private function invokedArguments( string $className, array $arguments ) : array
    {
        if ( \array_key_exists( $className, $this->argumentCallback ) ) {
            $cacheKey  = "{$className}:".hashKey( $arguments );
            $arguments = self::$argumentCache[$cacheKey] ??= ( $this->argumentCallback[$className] )( $arguments );
        }

        return $arguments;
    }
}