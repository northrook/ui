<?php

namespace Northrook\UI;

use Northrook\UI\Latte\ComponentRuntime;
use function Northrook\normalizePath;

final class AssetHandler
{
    // Alternative locations for Component Assets
    private static array $calledComponents = [];

    private array $assetDirectories = [];

    public function __construct(
        string | array $assetDirectories = [],
    ) {
        $this->addDirectory( $assetDirectories );
    }

    public static function register( string $name, string $component ) : void {
        if ( !isset( AssetHandler::$calledComponents[ $name ] ) ) {
            AssetHandler::$calledComponents[ $name ] = $component;
        }
    }

    private function addDirectory( string | array $directory ) : void {
        foreach ( (array) $directory as $file ) {
            if ( !\is_dir( $file ) ) {
                continue;
            }
            $this->assetDirectories[] = normalizePath( $file );
        }
    }

    public function getComponentAssets( array $filter = [] ) : array {
        $assets = [];

        $called = $this::$calledComponents;
        
        // filter out both [type => className] from $filter

        foreach ( $called as $component => $className ) {
            $assets += $className::getAssets();
        }

        return $assets;
    }
}