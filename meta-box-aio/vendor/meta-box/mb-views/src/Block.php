<?php
namespace MBViews;

class Block {
    private $renderer;

    public function __construct( $renderer ) {
        $this->renderer = $renderer;

        add_filter( 'mbb_block_renderer', [ $this, 'renderer' ] );
    }

    public function renderer() {
        return $this->renderer;
    } 
}
