<?php

ob_start();

?><!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
p{font-family: arial;
    font-size: 20px;
    text-align: center;
    font-weight: bold;
    padding-top: 120px;
}
#loader-wrapper {
    position: fixed;
    top: 0px;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1000;
}
#loader {
    display: block;
    position: relative;
    left: 50%;
    top: 50%;
    width: 150px;
    height: 150px;
    margin: -150px 0 0 -75px;
    border-radius: 50%;
    border: 4px solid transparent;
    border-top-color: #3498db;

    -webkit-animation: spin 2s linear infinite; /* Chrome, Opera 15+, Safari 5+ */
          animation: spin 2s linear infinite; /* Chrome, Firefox 16+, IE 10+, Opera */
}

    #loader:before {
        content: "";
        position: absolute;
        top: 5px;
        left: 5px;
        right: 5px;
        bottom: 5px;
        border-radius: 50%;
        border: 4px solid transparent;
        border-top-color: #e74c3c;

        -webkit-animation: spin 3s linear infinite; /* Chrome, Opera 15+, Safari 5+ */
          animation: spin 3s linear infinite; /* Chrome, Firefox 16+, IE 10+, Opera */
    }

    #loader:after {
        content: "";
        position: absolute;
        top: 15px;
        left: 15px;
        right: 15px;
        bottom: 15px;
        border-radius: 50%;
        border: 4px solid transparent;
        border-top-color: #f9c922;

        -webkit-animation: spin 1.5s linear infinite; /* Chrome, Opera 15+, Safari 5+ */
          animation: spin 1.5s linear infinite; /* Chrome, Firefox 16+, IE 10+, Opera */
    }

    @-webkit-keyframes spin {
        0%   { 
            -webkit-transform: rotate(0deg);  /* Chrome, Opera 15+, Safari 3.1+ */
            -ms-transform: rotate(0deg);  /* IE 9 */
            transform: rotate(0deg);  /* Firefox 16+, IE 10+, Opera */
        }
        100% {
            -webkit-transform: rotate(360deg);  /* Chrome, Opera 15+, Safari 3.1+ */
            -ms-transform: rotate(360deg);  /* IE 9 */
            transform: rotate(360deg);  /* Firefox 16+, IE 10+, Opera */
        }
    }
    @keyframes spin {
        0%   { 
            -webkit-transform: rotate(0deg);  /* Chrome, Opera 15+, Safari 3.1+ */
            -ms-transform: rotate(0deg);  /* IE 9 */
            transform: rotate(0deg);  /* Firefox 16+, IE 10+, Opera */
        }
        100% {
            -webkit-transform: rotate(360deg);  /* Chrome, Opera 15+, Safari 3.1+ */
            -ms-transform: rotate(360deg);  /* IE 9 */
            transform: rotate(360deg);  /* Firefox 16+, IE 10+, Opera */
        }
    }
</style>
 <script type="text/javascript">
            
    
    var canReturn       =   <?php
    
    $protocol   =   isset( $_SERVER['HTTPS'] )  ?   'https' :   'http';
    
    $wooGC_sites        =   isset ( $_COOKIE['woogc_sites'] ) ?     $_COOKIE['woogc_sites'] :   '';
    $Network_Sites      =   array ( );
    
    if ( empty ( $wooGC_sites ) )
        echo 'true';
        else
        {
            $Network_Sites  =   explode ( "&", $_COOKIE['woogc_sites'] );
                        
            //reindex
            $Network_Sites  =   array_values ( array_filter ( $Network_Sites ) );
            
            if ( count ( $Network_Sites ) < 1 )
                echo 'true';
                else
                echo 'false';
        }
    ?>;
    
    if ( canReturn )
        {
            var WooGC_Bouncer_Return        =   read_cookie('woogc_return_url');
            
            document.cookie = "woogc_return_url=; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT; <?php  if ( $protocol == 'https' ) { echo 'Secure;'; }  ?>";
            document.cookie = "woogc_sites=; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT; <?php  if ( $protocol == 'https' ) { echo 'Secure;'; }  ?>";
            
            if ( WooGC_Bouncer_Return !== false )
                {
                    WooGC_Bouncer_Return    =   decodeURIComponent ( WooGC_Bouncer_Return );
                        
                    window.open( WooGC_Bouncer_Return ,"_self");
                }
        }
        else
        {
            <?php
                
                $sync_hash          =   isset ( $_GET['sync_hash'] ) ?   preg_replace("/[^A-Za-z0-9\.]/", '', $_GET['sync_hash'])  :   '';

                if (  ! empty ( $sync_hash ) &&    count  ( $Network_Sites ) > 0 )
                    {                       
                        $current_url    =   $protocol . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                        $current_url_parsed =   parse_url ( $current_url );
                        $current_url_path   =   explode ( "/",  $current_url_parsed['path'] );
                        unset ( $current_url_path[ count ( $current_url_path ) - 1 ] );
                        
                        $return_url     =   "//" .  $current_url_parsed['host'] . $current_url_parsed['path'];
                        
                        $url_query  =   'bounce=true&sync_run=true&sync_hash=' . $sync_hash;
                            
                        $url_query  .=  '&return_url=' . urldecode( $return_url );
                        
                        $woogc_sync_url =   $protocol   .   ':' .  $Network_Sites[ 0 ] . implode('/', $current_url_path ) . '/woogc-sync.php?' . $url_query ;
                        
                        //remove processed site
                        unset ( $Network_Sites[ 0 ] );
                        
                        set_cookie ("woogc_sites", implode("&", $Network_Sites ), '', '/', '', true, '');
                                                         
                        ?>
                            var WooGC_Url       =   '<?php  echo $woogc_sync_url; ?>';
                            
                            setTimeout( function() {
                                window.open( WooGC_Url ,"_self");
                            }, 20)  
                        <?php
                    }
                
                
            ?>
               
        }
          
    function read_cookie( cookie_name )    
        {
            var CookiesPairs = document.cookie.split(';');
            for(var i = 0; i < CookiesPairs.length; i++) 
                {
                    var name    = CookiesPairs[i].substring(0, CookiesPairs[i].indexOf('='));
                    var value   = CookiesPairs[i].substring(CookiesPairs[i].indexOf('=')+1);
                    
                    name        =   name.trim();
                    value       =   value.trim();
                        
                    if(name == cookie_name)
                        {
                            return value;
                        }
                }   
            
            return false;
        }
    
 </script>
    
    <style type="text/css">
    <?php
    
    
        /**
        * Load custom styls if exists
        * 
        */
        $style_file_path    =   dirname(__FILE__) . '/../../../uploads/woogc/sync-page-styles.css';
        If ( file_exists ( $style_file_path )   &&  filesize(  $style_file_path   ) > 0 )
            {
                $handle     = fopen(    $style_file_path, "r"  );
                $contents   = fread(    $handle, filesize(  $style_file_path   )   );
                fclose( $handle );
                
                $contents   =   str_replace( array( '<?php', '?>', '<', '>'  ), "", $contents );
                echo $contents;
            }
            
    
    ?>
    </style>
    </head>
    
    <body>
        <p>Please Wait while Synchronizing...</p>
       
        <div id="loader-wrapper"><div id="loader"></div></div>
    </body>
</html><?php


    function set_cookie(    $CookieName, $CookieValue = '', $CookieMaxAge = 0, $CookiePath = '', $CookieDomain = '', $CookieSecure = false, $CookieHTTPOnly = false, $CookieSameSite = 'none') 
        {
            header( 'Set-Cookie: ' . rawurlencode( $CookieName ) . '=' . rawurlencode( $CookieValue )
                                . ( empty($CookieMaxAge )   ? '' : '; Max-Age=' . $CookieMaxAge)
                                . ( empty($CookiePath )     ? '' : '; path=' . $CookiePath)
                                . ( empty($CookieDomain )   ? '' : '; domain=' . $CookieDomain)
                                . ( !$CookieSecure          ? '' : '; secure')
                                . ( !$CookieHTTPOnly        ? '' : '; HttpOnly')
                                . ( empty($CookieSameSite)  ? '' : '; SameSite=' . $CookieSameSite )
                                ,false);
        }

        
    ob_end_flush();
        
?>