<?php

namespace Northrook\UI;

use Northrook\UI\Latte\ComponentRuntime;
use function Northrook\normalizePath;

final class AssetHandler
{
    private array $assetDirectories = []; // Alternative locations for Component Assets

    public function __construct(
        string | array $assetDirectories = [],
    ) {
        $this->addDirectory( $assetDirectories );
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

        $called = ComponentRuntime::getCalled();

        // filter out both [type => className] from $filter

        foreach ( $called as $component => $className ) {
            $assets += $className::getAssets();
        }

        return $assets;
    }
}