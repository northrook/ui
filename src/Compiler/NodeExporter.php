<?php

declare( strict_types = 1 );

namespace Northrook\UI\Compiler;

use Symfony\Component\VarExporter\VarExporter;
use function Northrook\stringStartsWith;
use const Northrook\EMPTY_STRING;
use const Northrook\WHITESPACE;


final class NodeExporter
{
    private string $value = EMPTY_STRING;

    public function __construct() {}

    /**
     * @param class-string  $class
     * @param               ...$arguments
     *
     * @return $this
     */
    public function newCall( string $class, ...$arguments ) : NodeExporter
    {
        return $this
            ->append( '( new ', $class, '( ' )
            ->handleCallArguments( $arguments )
            ->append( ' ))' )
        ;
    }

    /**
     * @param class-string     $class
     * @param callable-string  $method
     * @param                  ...$args
     *
     * @return $this
     */
    public function staticCall( string $class, string $method, ...$args ) : NodeExporter
    {
        return $this;
    }

    public function getValue() : string
    {
        return $this->value;
    }

    public function toEcho() : string
    {
        return 'echo ' . $this->getValue() . ';';
    }

    public function append( string ...$value ) : NodeExporter
    {
        foreach ( $value as $append ) {
            $this->value .= $append;
        }
        return $this;
    }

    public function prepend( string $value ) : NodeExporter
    {
        $this->value = $value . $this->value;
        return $this;
    }

    private function handleCallArguments( array $arguments ) : NodeExporter
    {
        foreach ( $arguments as $name => $argument ) {
            if ( \is_string( $name ) ) {
                $this->append( "$name: " );
            }
            $this->append( $this->handleArgument( $argument ), ", " );
        }
        return $this;
    }


    private function handleArgument( mixed $argument ) : string
    {
        if ( \is_string( $argument ) || $argument instanceof \Stringable ) {
            return (string) "'{$argument}'";
        }

        if ( \is_array( $argument ) && \array_filter( $argument, 'is_string' ) ) {
            $string = '[ ';
            foreach ( $argument as $key => $value ) {
                $key    = \trim( $key, " \t\n\r\0\x0B'" );
                $value  = \trim( $value, " \t\n\r\0\x0B'" );
                $string .= "'{$key}' => '{$value}', ";
            }
            return $string .= "]";
        }

        return __FUNCTION__;
    }

    public static function arguments( array $arguments ) : string
    {
        $export = [];

        foreach ( $arguments as $name => $value ) {
            $argument = \is_string( $name ) ? "$name: " : '';
            $argument .= match ( \gettype( $value ) ) {
                'string' => self::string( $value ),
                'array'  => self::array( $value ),
            };
            $export[] = $argument;
        }

        $string = implode( ', ' . PHP_EOL, $export ) . PHP_EOL;

        return "[ $string ]";
    }


    public static function string( string $value ) : string
    {
        $value = \trim( $value, " \t\n\r\0\x0B'" );

        if ( !stringStartsWith( $value, [ '$', 'LR\Filters' ] ) ) {
            $value = "'{$value}'";
        }
        return $value;
    }


    public static function array( array $array ) : string
    {
        $argument = \array_filter( $array, 'is_string' );

        if ( !$argument ) {
            return "[]";
        }

        $string = '[' . PHP_EOL;
        foreach ( $argument as $key => $value ) {
            if ( \is_string( $key ) ) {
                $key = "'" . \trim( $key, " \t\n\r\0\x0B'" ) . "'";
            }

            if ( \is_string( $value ) ) {
                $value = NodeExporter::string( $value );
            }

            if ( \is_array( $value ) ) {
                $value = self::array( $value );
            }

            $string .= "$key => $value," . PHP_EOL;
        }

        return $string .= "]";
    }

    public static function boolean( bool $bool ) : string
    {
        return $bool ? 'true' : 'false';
    }
}