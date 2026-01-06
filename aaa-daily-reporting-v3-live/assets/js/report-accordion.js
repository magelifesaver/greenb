( function( $ ) {
  $( function() {
    // append a Minimize link to every section
    $( '.aaa-accordion .content' ).each( function() {
      $( this ).append(
        '<p class="aaa-minimize" style="text-align:right;margin-top:8px;">' +
          '<a href="#" class="aaa-minimize-link">Minimize</a>' +
        '</p>'
      );
    } );

    // when clicked, close its parent <details>
    $( document ).on( 'click', '.aaa-minimize-link', function( e ) {
      e.preventDefault();
      $( this ).closest( 'details.aaa-accordion' ).prop( 'open', false );
    } );
  } );
} )( jQuery );
