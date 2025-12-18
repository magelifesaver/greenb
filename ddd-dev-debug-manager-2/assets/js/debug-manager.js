/*
 * JavaScript for the Dev Debug Manager admin page.
 *
 * Handles live tailing of the debug.log file by polling the server for new
 * content. A stateful offset ensures only new bytes are fetched. Users can
 * toggle the tail on and off using the provided button. This script also
 * provides UI handlers for clearing the cached snapshot and clearing the
 * underlying debug.log file itself. An optional duplicate filter can be
 * toggled when tailing to remove duplicate lines from each fetched chunk.
 */

( function ( $ ) {
    'use strict';

    // Track the current file offset and whether tailing is active.
    var offset  = 0;
    var tailing = false;
    var timerId = null;

    // Cache references to elements to avoid repeated lookups.
    var content      = $( '#ddd-debug-content' );
    var tailBtn      = $( '#ddd-debug-tail-btn' );
    var uniqueChk    = $( '#ddd-tail-unique' );
    var clearLogBtn  = $( '#ddd-clear-log-btn' );
    var clearLogMsg  = $( '#ddd-clear-log-message' );
    var clearSnapBtn = $( '#ddd-clear-snapshot-btn' );
    var snapMsg      = $( '#ddd-clear-snapshot-message' );

    /**
     * Fetch new log content from the server.
     *
     * Sends the current offset and duplicate filtering flag. On success
     * appends the returned text to the log area and updates the offset.
     */
    function fetchTail() {
        $.ajax( {
            url: DDD_Debug_Manager.ajax_url,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'ddd_debug_manager_tail',
                nonce: DDD_Debug_Manager.nonce_tail,
                offset: offset,
                unique: uniqueChk.is( ':checked' ) ? 1 : 0
            },
            success: function ( response ) {
                if ( response && response.success ) {
                    // Update the offset from the response so future calls continue from here.
                    offset = response.data.offset;
                    var text = response.data.content;
                    if ( text ) {
                        // Append as plain text to avoid HTML injection.
                        content.append( document.createTextNode( text ) );
                        // Auto-scroll to the bottom to keep the latest lines in view.
                        content.scrollTop( content[ 0 ].scrollHeight );
                    }
                }
            }
        } );
    }

    /**
     * Toggle live tailing on button click.
     */
    tailBtn.on( 'click', function ( e ) {
        e.preventDefault();
        if ( tailing ) {
            // Stop tailing.
            tailing = false;
            clearInterval( timerId );
            timerId = null;
            tailBtn.text( DDD_Debug_Manager.start_text || 'Start Live Tail' );
        } else {
            // Start tailing immediately and then poll regularly.
            tailing = true;
            tailBtn.text( DDD_Debug_Manager.stop_text || 'Stop Live Tail' );
            fetchTail();
            timerId = setInterval( fetchTail, 3000 );
        }
    } );

    /**
     * AJAX helper to clear a snapshot file. Displays the response message.
     */
    clearSnapBtn.on( 'click', function ( e ) {
        e.preventDefault();
        var url = clearSnapBtn.data( 'clear-url' );
        if ( ! url ) {
            return;
        }
        $.ajax( {
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function ( resp ) {
                if ( resp && resp.success && resp.data && resp.data.message ) {
                    snapMsg.text( resp.data.message );
                } else {
                    snapMsg.text( 'Failed to clear snapshot.' );
                }
                // Remove message after a few seconds.
                setTimeout( function () {
                    snapMsg.text( '' );
                }, 5000 );
            },
            error: function () {
                snapMsg.text( 'Error clearing snapshot.' );
                setTimeout( function () {
                    snapMsg.text( '' );
                }, 5000 );
            }
        } );
    } );

    /**
     * AJAX helper to clear the underlying debug.log file. Displays the response message.
     */
    clearLogBtn.on( 'click', function ( e ) {
        e.preventDefault();
        var url = clearLogBtn.data( 'clear-log-url' );
        if ( ! url ) {
            return;
        }
        $.ajax( {
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function ( resp ) {
                if ( resp && resp.success && resp.data && resp.data.message ) {
                    clearLogMsg.text( resp.data.message );
                } else {
                    clearLogMsg.text( 'Failed to clear log.' );
                }
                // Reset offset and clear visible content if successful.
                if ( resp && resp.success ) {
                    offset = 0;
                    content.text( '' );
                }
                setTimeout( function () {
                    clearLogMsg.text( '' );
                }, 5000 );
            },
            error: function () {
                clearLogMsg.text( 'Error clearing log.' );
                setTimeout( function () {
                    clearLogMsg.text( '' );
                }, 5000 );
            }
        } );
    } );
} )( jQuery );