<?php

namespace Northrook\UI;

use Northrook\Asset\Script;
use Northrook\Asset\Style;
use Northrook\Resource\Path;
use Northrook\UI\Latte\RenderRuntime;
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

        foreach ( RenderRuntime::getCalledInvocations() as $className => $component ) {

            if ( !\method_exists( $className, 'getAssets' ) ) {
                continue;
            }

            // Do not get assets for these components
            if ( \in_array( $className, $filter, false ) ) {
                continue;
            }

            // Prepare each Component Asset
            foreach ( $className::getAssets() as $asset ) {
                $asset = new Path( $asset );

                if ( ! $asset->exists ) {
                    continue;
                }

                $assets[] = match ( $asset->extension ) {
                    'css'   => new Style( $asset, [ 'component' => $component ] ),
                    'js'    => new Script( $asset, [ 'component' => $component ] ),
                    default => $asset,
                };
            }
        }

        return $assets;
    }
}