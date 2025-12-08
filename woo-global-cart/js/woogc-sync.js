    
    if (typeof WooGC_Sync === 'undefined') {
   
        var WooGC_Sync  = {
            
            init    :   function() {

                this.sync_done_check();
                
                this.reset();
                this.sync_check();
                
            },
            
            reset  : function () {
                            
                this.SyncHash       =   '';
            },
            
            sync_check  :   function () {

                this.SyncHash =   this.read_cookie('woogc_sync_run');
                
                if ( this.SyncHash == '' )
                    return false;
                    
                if ( typeof WooGC_Sites === 'undefined' ||  WooGC_Sites.length < 1 )
                    return false;
                    
                if (  navigator.userAgent.match( /bot|crawl|slurp|spider|mediapartners|gtmetrix|google|yahoo|yandex|facebook|bingbot|msnbot|chrome-lighthouse|curl|Redirect\sChecker/i ) )
                    return false;
                
                if ( window.location.href.indexOf("sync-done") > -1 )
                    return false;
                
                //this.sync_run();
                this.prefetch_domains();
            },
            
            device_require_bounce : function() {
                
                return true;
                   
            },
            
            
            prefetch_domains    :   function() {
            
                var woogc_sync_wrapper  =   document.getElementById('woogc_sync_wrapper');
                
                //clear the existing
                woogc_sync_wrapper.innerHTML    =   '';
                
                this.prefetch_start =   Date.now();
                for ( var key in WooGC_Sites ) 
                    {
                        var site_url    =   WooGC_Sites[key] + WooGC_Sync_Url + '/woogc-sync.php?prefetch=true';                          
                        woogc_sync_wrapper.innerHTML = woogc_sync_wrapper.innerHTML + '<img class="prefetch loading" onload="WooGC_Sync.prefetch_list_update( this ) " onerror="WooGC_Sync.prefetch_list_update( this ) " src="' +  site_url + '" alt="prefetch" />';
                    }
                
                setTimeout( this.prefetch_completed_check, 3000 );
                
                return true;
                
            },
            prefetch_list_update    :   function( element ) {
                element.classList.remove("loading");            
                element.classList.add("loaded");
                
                this.prefetch_completed_check();
            },
            prefetch_completed_check    :   function () {
            
                if ( Date.now() > ( WooGC_Sync.prefetch_start + 2999 ) )
                    {
                        WooGC_Sync.sync_run();
                        return;
                    }
                        
                var woogc_sync_wrapper  =   document.getElementById('woogc_sync_wrapper');
                var preloaders          =   woogc_sync_wrapper.getElementsByTagName('img');
                var still_loading       =   false;
                for(    var i = 0; i < preloaders.length; i++ )
                    {
                        if ( preloaders[i].classList.contains( 'loading' ) )
                            {
                                still_loading   =   true;
                            }
                    }
                    
                if ( still_loading === false )
                    WooGC_Sync.sync_run();
            },
            
            sync_run    :   function() {
                            
                this.remove_sync_cookie();
                                                        
                this.do_sync_bounce();  
                
            },
                
            
            do_sync_bounce  :   function () {
                
                document.cookie = "woogc_sites="+ WooGC_Sites.join('&') + ";path=/; SameSite=Lax";
                
                //set the return url
                var Return_Url          = window.location.href;
                
                var parser              = document.createElement('a');
                parser.href             = Return_Url; 
                var return_url_parts    = Return_Url.split("?");
                if ( return_url_parts.length > 1 )
                    {
                        var urlParams = new URLSearchParams( return_url_parts[1] );
                        
                        if ( urlParams.toString() != '' )
                            Return_Url  =   return_url_parts[0] + "?" + urlParams.toString() + '&sync-done';
                            else
                            Return_Url  =   return_url_parts[0] + '?sync-done';
                    }
                    else
                    Return_Url  +=   '?sync-done';
                    
                document.cookie = "woogc_return_url=" + Return_Url + "; path=/; SameSite=Lax";
                
                var url_query   =   'sync_run=true&sync_hash=' + this.SyncHash;
                
                setTimeout( function() {
                    window.open( '//' + parser.host + WooGC_Sync_Url + '/sync-hub.php?' + url_query ,"_self");
                }, 100) 
                
            },
            
                    
            
            remove_sync_cookie  :   function() {
                
                var Return_Url          = window.location.href;
                var parser              = document.createElement('a');
                parser.href             = Return_Url;
                var domain              = WooGC_sd ? "." +  parser.hostname :   parser.hostname;
                    
                document.cookie         = "woogc_sync_run=; path=/; domain=" + domain + "; expires=Thu, 01 Jan 1970 00:00:01 GMT;";    
                document.cookie         = 'woogc_sync_run=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
                
            },
                 
            read_cookie :   function( cookie_name ) {
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
                
            },
            
            sync_done_check :   function() {
                    var Return_Url          = window.location.href;
                    if ( Return_Url.indexOf("sync-done") > -1 )
                        {
                            window.addEventListener("load", (event) => {
                                const woogc_event = document.createEvent('Event');
                                woogc_event.initEvent( 'woogc/sync-done', true, true);
                                document.dispatchEvent( woogc_event );                        
                            });
                        }            
                } 
      
        }
        
        WooGC_Sync.init();

        
        (function() {
            var origOpen = XMLHttpRequest.prototype.send;
            
            XMLHttpRequest.prototype.realSend = XMLHttpRequest.prototype.send; 
            var newSend = function(vData) { 
                            
                var XMLHttpRequestPostVars =   ( 0 in arguments ) ? arguments[0] : "";
                
                this.addEventListener('load', function( args ) {
                    
                    var found = false;
                    
                    if( typeof (this.responseURL) !== "undefined"   &&  this.responseURL.indexOf("?wc-ajax=") !== -1    &&  this.responseURL.indexOf("get_refreshed_fragments") === -1 )
                        found = true;
                        
                    if ( found === false &&  XMLHttpRequestPostVars instanceof FormData) {
                        for (var pair of XMLHttpRequestPostVars.entries()) {
                          var fieldName = pair[0];
                          var fieldValue = pair[1];
                          
                          if ( fieldName.indexOf ( "add-to-cart" ) !== -1   ||  fieldName.indexOf ( "add_to_cart" ) !== -1 )
                            found = true;
                          if ( fieldValue.indexOf ( "add-to-cart" ) !== -1   ||  fieldValue.indexOf ( "add_to_cart" ) !== -1 )
                            found = true;
                        }
                    }
                    
                    if ( found === false && WooGC_on_PostVars.length    >   0 ) 
                        {
                            for (var i = 0; i < WooGC_on_PostVars.length; i++) 
                                {
                                    if ( XMLHttpRequestPostVars instanceof FormData )
                                        {
                                            for ( var value of XMLHttpRequestPostVars.entries() ) {
                                                if ( Array.isArray ( value ) )
                                                    {
                                                        var chunk   =   value[0] + '=' + value[1];   
                                                        if( chunk.localeCompare( WooGC_on_PostVars[i] )  === 0 )
                                                        {
                                                            found = true;
                                                            break;
                                                        }
                                                    }
                                            }    
                                            
                                        }
                                        else
                                        {
                                            if( XMLHttpRequestPostVars.indexOf( WooGC_on_PostVars[i] ) !== -1 )
                                                {
                                                    found = true;
                                                    break;
                                                }
                                        }
                                }   
                        }
                        
                    if ( /action=([\w]+)?add_to_cart([\w]+)?/gm.test( XMLHttpRequestPostVars ) ||   /action=([\w]+)?add_cart([\w]+)?/gm.test( XMLHttpRequestPostVars ) )
                        found = true;
                    
                    
                    if ( found   === true )
                        WooGC_Sync.init();    
                    
                });
                
                this.realSend(vData); 
            };
            XMLHttpRequest.prototype.send = newSend;
            
            
            // Create a reference to the original fetch function
            var originalFetch = window.fetch;

            // Override the fetch function
            window.fetch = function(url, options) {
              // Capture the options in a closure
              var capturedOptions = options;

              // Perform the AJAX call
              var promise = originalFetch.apply(this, arguments);

              // Intercept the response
              promise.then(function(response) {

                // Trigger your action based on the response and options
                if (response.ok) {
                  // Response was successful (status in the range 200-299)
                  
                    var XMLHttpRequestPostVars    =   capturedOptions.body;
                      
                    var found = false;
                    
                    if( typeof ( response.url ) !== "undefined"   &&  response.url.indexOf("?wc-ajax=") !== -1    &&  response.url.indexOf("get_refreshed_fragments") === -1 )
                        found = true;
                        
                    if ( found === false && WooGC_on_PostVars.length    >   0 ) 
                        {
                            for (var i = 0; i < WooGC_on_PostVars.length; i++) 
                                {
                                    if ( XMLHttpRequestPostVars instanceof FormData )
                                        {
                                            for ( var value of XMLHttpRequestPostVars.entries() ) {
                                                if ( Array.isArray ( value ) )
                                                    {
                                                        var chunk   =   value[0] + '=' + value[1];   
                                                        if( chunk.localeCompare( WooGC_on_PostVars[i] )  === 0 )
                                                        {
                                                            found = true;
                                                            break;
                                                        }
                                                    }
                                            }    
                                            
                                        }
                                        else
                                        {
                                            if( XMLHttpRequestPostVars.indexOf( WooGC_on_PostVars[i] ) !== -1 )
                                                {
                                                    found = true;
                                                    break;
                                                }
                                        }
                                }   
                        }
                        
                    if ( /action=([\w]+)?add_to_cart([\w]+)?/gm.test( XMLHttpRequestPostVars ) ||   /action=([\w]+)?add_cart([\w]+)?/gm.test( XMLHttpRequestPostVars ) ||   /cart\/add-item/gm.test( XMLHttpRequestPostVars ) )
                        found = true;


                    if ( found   === true )
                        WooGC_Sync.init();

                  
                } else {
                  // Response returned an error status
                }
              }).catch(function(error) {
                // Handle any errors that occurred during the request or response
              });

              // Return the original promise
              return promise;
            };
            
            
        })(); 
    
    }