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

        foreach ( $this::$calledComponents as $component => $className ) {

            // Do not get assets for these components
            if ( \in_array( $component, $filter, false ) ) {
                continue;
            }

            $assets = [ ...$assets, ... $className::getAssets() ];
        }

        return $assets;
    }
}