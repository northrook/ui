<?php

namespace Northrook\UI;

use Northrook\Assets\{Script, Style};
use Northrook\Resource\Path;
use Support\Normalize;

final class AssetHandler
{
    // Alternative locations for Component Assets
    private static array $calledComponents = [];

    private array $assetDirectories = [];

    public function __construct(
        string|array $assetDirectories = [],
    ) {
        $this->addDirectory( $assetDirectories );
    }

    public static function register( string $name, string $component ) : void
    {
        if ( ! isset( AssetHandler::$calledComponents[$name] ) ) {
            AssetHandler::$calledComponents[$name] = $component;
        }
    }

    private function addDirectory( string|array $directory ) : void
    {
        foreach ( (array) $directory as $file ) {
            if ( ! \is_dir( $file ) ) {
                continue;
            }
            $this->assetDirectories[] = Normalize::path( $file );
        }
    }

    public function getComponentAssets( array $filter = [] ) : array
    {
        $assets = [];

        foreach ( RenderRuntime::getCalledInvocations() as $className => $component ) {
            if ( ! \method_exists( $className, 'getAssets' ) ) {
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

                $id = 'ui:'.\strtolower( $component );

                $assets[] = match ( $asset->extension ) {
                    'css'   => new Style( $asset, $id ),
                    'js'    => new Script( $asset, $id ),
                    default => $asset,
                };
            }
        }

        return $assets;
    }
}
