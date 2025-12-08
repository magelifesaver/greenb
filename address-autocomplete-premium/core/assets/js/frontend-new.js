function wps_aa() {
	console.log('WPSAA: wps_aa() function called');
	console.log('WPSAA: wps_aa_vars:', wps_aa_vars);
	
	for ( const address_group of wps_aa_vars.instances ) {
		console.log('WPSAA: Processing address group:', address_group);
		if ( address_group.init ) {
			if ( ! address_group.delay ) {
				console.log('WPSAA: Initializing immediately for:', address_group.init);
				wps_aa_init_autocomplete( address_group );
			} else if ( address_group.delay ) {
				console.log('WPSAA: Scheduling delayed initialization for:', address_group.init, 'delay:', address_group.delay);
				setTimeout( wps_aa_init_autocomplete, address_group.delay * 1000, address_group );
			}
		} else {
			console.log('WPSAA: Skipping address group - no init selector:', address_group);
		}
	}
}

async function wps_aa_init_autocomplete( address_group ) {
	console.log('WPSAA: wps_aa_init_autocomplete() called with:', address_group);
	
	try {
		// Check if google.maps is available
		if (typeof google === 'undefined') {
			console.error('WPSAA: google object is not defined!');
			return;
		}
		
		if (typeof google.maps === 'undefined') {
			console.error('WPSAA: google.maps is not defined!');
			return;
		}
		
		console.log('WPSAA: google.maps object:', google.maps);
		console.log('WPSAA: Available google.maps properties:', Object.keys(google.maps));
		
		// Request needed libraries
		console.log('WPSAA: Attempting to import places library...');
		await google.maps.importLibrary("places");
		console.log('WPSAA: Successfully imported places library');
		
		// Check if PlaceAutocompleteElement is available
		if (typeof google.maps.places === 'undefined') {
			console.error('WPSAA: google.maps.places is not defined after import!');
			return;
		}
		
		if (typeof google.maps.places.PlaceAutocompleteElement === 'undefined') {
			console.error('WPSAA: PlaceAutocompleteElement is not available!');
			console.log('WPSAA: Available places properties:', Object.keys(google.maps.places));
			return;
		}
		
		console.log('WPSAA: PlaceAutocompleteElement is available');
		
		// Query all matching selectors, not just the first one
		const init_selectors = document.querySelectorAll( address_group.init );
		console.log('WPSAA: Found selectors:', init_selectors.length, 'for selector:', address_group.init);
		console.log('WPSAA: Selector elements:', init_selectors);
		
		if ( init_selectors.length === 0 ) {
			console.log( 'WPSAA: Could not find address autocomplete initial selector: ' + address_group.init );
			return;
		}

		// Initialize autocomplete for each matching element
		init_selectors.forEach(async (init_selector, index) => {
			console.log(`WPSAA: Processing selector ${index + 1}:`, init_selector);
			
			try {
				// Create a wrapper div to contain the original input and the new Places input
				const wrapper = document.createElement('div');
				wrapper.className = 'wps-aa-wrapper';
				wrapper.style.position = 'relative';
				wrapper.style.display = 'inline-block';
				wrapper.style.width = '100%';
				console.log('WPSAA: Created wrapper:', wrapper);
				
				// Insert wrapper before the original input
				init_selector.parentNode.insertBefore(wrapper, init_selector);
				console.log('WPSAA: Inserted wrapper before original input');
				
				// Move the original input into the wrapper (keep it visible and functional)
				wrapper.appendChild(init_selector);
				console.log('WPSAA: Moved original input into wrapper');
				
				// Create the new Places input element using Google's API
				console.log('WPSAA: Creating PlaceAutocompleteElement...');
				//@ts-ignore
				const placeAutocomplete = new google.maps.places.PlaceAutocompleteElement();
				console.log('WPSAA: Successfully created PlaceAutocompleteElement:', placeAutocomplete);
				
				// Configure the PlaceAutocompleteElement
				if ( address_group.allowed_countries ) {
					console.log('WPSAA: Setting component restrictions:', address_group.allowed_countries);
					placeAutocomplete.componentRestrictions = { country: address_group.allowed_countries };
				}
				
				// Set fields to retrieve
				console.log('WPSAA: Setting fields to retrieve');
				placeAutocomplete.fields = ['address_components', 'geometry', 'name'];
				
				// Style the PlaceAutocompleteElement to be invisible but functional
				console.log('WPSAA: Styling PlaceAutocompleteElement for overlay approach');
				placeAutocomplete.style.position = 'absolute';
				placeAutocomplete.style.top = '0';
				placeAutocomplete.style.left = '0';
				placeAutocomplete.style.width = '100%';
				placeAutocomplete.style.height = '100%';
				placeAutocomplete.style.opacity = '0';
				placeAutocomplete.style.pointerEvents = 'none';
				placeAutocomplete.style.zIndex = '1';
				
				// Copy relevant attributes
				if (init_selector.id) {
					placeAutocomplete.id = init_selector.id + '-places';
					console.log('WPSAA: Set ID:', placeAutocomplete.id);
				}
				if (init_selector.name) {
					placeAutocomplete.name = init_selector.name;
					console.log('WPSAA: Set name:', placeAutocomplete.name);
				}
				if (init_selector.placeholder) {
					placeAutocomplete.placeholder = init_selector.placeholder;
					console.log('WPSAA: Set placeholder:', placeAutocomplete.placeholder);
				}
				if (init_selector.required) {
					placeAutocomplete.required = init_selector.required;
					console.log('WPSAA: Set required:', placeAutocomplete.required);
				}
				if (init_selector.disabled) {
					placeAutocomplete.disabled = init_selector.disabled;
					console.log('WPSAA: Set disabled:', placeAutocomplete.disabled);
				}
				
				// Add the Places input to the wrapper
				wrapper.appendChild(placeAutocomplete);
				console.log('WPSAA: Added PlaceAutocompleteElement to wrapper');
				
				// Store reference to original input for later use
				placeAutocomplete.dataset.originalInput = init_selector;
				
				// Add custom suggestions header to autocomplete dropdown
				console.log('WPSAA: Adding suggestions header...');
				wps_aa_add_suggestions_header(placeAutocomplete);
				
				// Listen for place selection
				console.log('WPSAA: Adding gmp-placeselect event listener...');
				placeAutocomplete.addEventListener('gmp-placeselect', async (event) => {
					console.log('WPSAA: gmp-placeselect event fired!', event);
					console.log('WPSAA: Event detail:', event.detail);
					
					const place = event.detail.place;
					console.log( 'WPSAA Address found:', place );
					
					// ... existing place processing code remains the same ...
					let values = {};
					let replacements = [];
					let final_data = [];

					// Build all possible replacement values
					for ( const place_component of place.address_components ) {
						// Find the first type that is not "political".
						const primaryType = place_component.types.find(type => type !== 'political') || place_component.types[0];
						values[ primaryType ] = place_component;
					}

					if ( place.hasOwnProperty( 'name' ) ) {
						values['name'] = { long_name: place.name, short_name: place.name };
					}

					// ... rest of your existing logic for building replacements ...
					if ( ! values['locality'] ) {
						// Loop through "sublocality_level_" fields and use first one that exists
						for ( const key in values ) {
							if ( key.startsWith( 'sublocality_level_' ) ) {
								values['locality'] = values[key];
								break;
							}
						}
					}

					if ( ! values['postal_code'] && values['postal_code_prefix'] ) {
						values['postal_code'] = values['postal_code_prefix'];
					}

					// Populate replacement array
					for ( const k in values ) {
						let short_replacement = { search: '{' + k + ':short_name}', replace: values[k].short_name || '' };
						let long_replacement = { search: '{' + k + ':long_name}', replace: values[k].long_name || '' };
						replacements.push( short_replacement, long_replacement );
					}

					// Latitude and Longitude replacements
					replacements.push( { search: '{lat}', replace: place.geometry.location.lat() } );
					replacements.push( { search: '{lng}', replace: place.geometry.location.lng() } );

					// Address 1 formatting based on country
					let address1_short = { search: '{address1:short_name}', replace: '' };
					let address1_long = { search: '{address1:long_name}', replace: '' };

					if ( values.street_number && values.route && values.country ) {
						let address1_format = wps_aa_address1_format( values.country.short_name );
						if ( address1_format === 'standard' ) {
							address1_short.replace = values.street_number?.short_name || '';
							address1_long.replace = values.street_number?.long_name || '';
							address1_short.replace += ( address1_short.replace && values.route?.short_name ) ? ' ' + values.route.short_name : values.route.short_name;
							address1_long.replace += ( address1_long.replace && values.route?.long_name ) ? ' ' + values.route.long_name : values.route.long_name;
						} else {
							address1_short.replace = values.route?.short_name || '';
							address1_long.replace = values.route?.long_name || '';
							address1_short.replace += ( address1_short.replace && values.street_number?.short_name ) ? ' ' + values.street_number.short_name : values.street_number.short_name;
							address1_short.replace += ( address1_short.replace && values.street_number?.long_name ) ? ' ' + values.street_number.long_name : values.street_number.long_name;
						}
					} else if ( values.route && ! values.street_number ) {
						address1_short.replace = values.route.short_name;
						address1_long.replace = values.route.long_name;
					} else if ( values.street_number && ! values.route ) {
						address1_short.replace = values.street_number.short_name;
						address1_long.replace = values.street_number.long_name;
					} else if ( values.name ) {
						address1_short.replace = values.name.short_name;
						address1_long.replace = values.name.long_name;
					} 
					replacements.push( address1_short, address1_long );

					// Address 2
					let address2_short = { search: '{address2:short_name}', replace: '' };
					let address2_long = { search: '{address2:long_name}', replace: '' };

					const address2_parts = [];

					if ( values.premise ) {
						address2_parts.push( values.premise.long_name );
					}
					if ( values.floor ) {
						address2_parts.push( values.floor.long_name );
					}
					if ( values.subpremise ) {
						address2_parts.push( values.subpremise.long_name );
					}
					if ( values.room ) {
						address2_parts.push( values.room.long_name );
					}

					address2_short.replace = address2_parts.join( ', ' );
					address2_long.replace = address2_parts.join( ', ' );
					replacements.push( address2_short, address2_long );

					// Convert replacements array to a map for easier lookup
					const replacements_map = replacements.reduce((map, obj) => {
						map[obj.search] = obj.replace;
						return map;
					}, {});

					console.log( 'WPSAA Replacements:', replacements );

					// Go through all available fields and apply replacements
					for ( const key in address_group.fields ) {
						let selector = address_group.fields[key].selector;
						let data = address_group.fields[key].data.toString();
						let result = data;
						let attributes = wps_aa_parse_atts(result);

						// Loop through each placeholder with its attributes
						for (const attr of attributes) {
							let replace_val = replacements_map[attr.key] || '';

							// Handle fallback if main value is empty
							if ( ! replace_val && attr.hasOwnProperty('fallback') ) {
								const fallback_keys = attr.fallback.split(',');
								for (const fallback_key of fallback_keys) {
									const full_fallback_key = `{${fallback_key.trim()}}`;
									if (replacements_map[full_fallback_key]) {
										replace_val = replacements_map[full_fallback_key];
										break;
									}
								}
							}

							let final_replace = replace_val;

							// Add before/after strings if a value was found
							if (final_replace) {
								if (attr.hasOwnProperty('before')) {
									final_replace = attr.before + final_replace;
								}
								if (attr.hasOwnProperty('after')) {
									final_replace = final_replace + attr.after;
								}
							}

							// Replace original placeholder with final value
							result = result.replace(attr.original, final_replace);
						}

						// Replace any remaining placeholders with empty string
						result = result.replace(/{[^{}]+}/g, '');

						wps_aa_change_value( selector, result );

						final_data.push( { selector: selector, result: result } );
					}

					// Update the original visible input with the selected place name
					// This ensures form submission still works with the original field
					init_selector.value = placeAutocomplete.value;
					init_selector.dispatchEvent( new Event( 'change' ) );

					const wps_aa_event = new CustomEvent( 'wps_aa', { detail: { data: final_data, init: address_group.init } } );
					document.dispatchEvent( wps_aa_event );
				});

				// Sync the Places input with the original input for form validation
				console.log('WPSAA: Adding input event listener for sync...');
				placeAutocomplete.addEventListener('input', function() {
					console.log('WPSAA: Input event fired on PlaceAutocompleteElement');
					init_selector.value = this.value;
					init_selector.dispatchEvent( new Event( 'change' ) );
				});

				// Handle form submission to ensure the original input value is used
				const form = init_selector.closest('form');
				if (form) {
					console.log('WPSAA: Adding form submit listener...');
					form.addEventListener('submit', function() {
						console.log('WPSAA: Form submit event fired');
						init_selector.value = placeAutocomplete.value;
					});
				}
				
				// Enable pointer events on the PlaceAutocompleteElement when the original input is focused
				init_selector.addEventListener('focus', function() {
					console.log('WPSAA: Original input focused, enabling PlaceAutocompleteElement');
					placeAutocomplete.style.pointerEvents = 'auto';
					placeAutocomplete.style.opacity = '0.01'; // Very slight opacity to make it focusable
				});
				
				init_selector.addEventListener('blur', function() {
					console.log('WPSAA: Original input blurred, disabling PlaceAutocompleteElement');
					placeAutocomplete.style.pointerEvents = 'none';
					placeAutocomplete.style.opacity = '0';
				});
				
				// Additional debugging - check final state
				console.log('WPSAA: Final PlaceAutocompleteElement styles:', placeAutocomplete.style.cssText);
				console.log('WPSAA: Final PlaceAutocompleteElement computed styles:', window.getComputedStyle(placeAutocomplete));
				console.log('WPSAA: PlaceAutocompleteElement is visible:', placeAutocomplete.offsetWidth > 0 && placeAutocomplete.offsetHeight > 0);
				
				console.log('WPSAA: Successfully completed setup for selector:', address_group.init);
				
			} catch (error) {
				console.error('WPSAA: Error setting up PlaceAutocompleteElement for selector:', address_group.init, error);
			}
		});
		
	} catch (error) {
		console.error('WPSAA: Fatal error in wps_aa_init_autocomplete:', error);
	}
}

function wps_aa_add_suggestions_header(input_element) {
	if (!wps_aa_vars.results_title) {
		return;
	}

	if (!input_element) {
		return;
	}

	// Generate a unique ID for this input if it doesn't have one
	if (!input_element.id) {
		input_element.id = 'wps-aa-field-' + Math.random().toString(36).substring(7);
	}
	
	// Use a simpler approach by directly targeting Google's appended pac-container
	const checkForPacContainer = () => {
		// Get all pac containers
		const pacContainers = document.querySelectorAll('.pac-container');
		
		// If none found, try again later
		if (pacContainers.length === 0) {
			return;
		}
		
		// Find the container associated with this input
		// Google adds the pac-container right after the input in the DOM
		// or it adds a data-target attribute matching the input id
		let targetContainer = null;
		
		pacContainers.forEach(container => {
			// Skip if this container already has our header
			if (container.querySelector('.wps-aa-results-title')) {
				return;
			}
			
			// Check if the container has a data attribute pointing to our input
			if (container.dataset && container.dataset.target === input_element.id) {
				targetContainer = container;
			}
			
			// Also check if it's positioned near our input as a fallback
			if (!targetContainer) {
				const inputRect = input_element.getBoundingClientRect();
				const containerRect = container.getBoundingClientRect();
				
				// Simplified position check - just check if it's roughly below the input
				if (Math.abs(containerRect.left - inputRect.left) < 50 && 
					containerRect.top > inputRect.top) {
					targetContainer = container;
				}
			}
		});
		
		// If we found a matching container, add our header
		if (targetContainer && !targetContainer.querySelector('.wps-aa-results-title')) {
			// Add our custom header
			const header = document.createElement('div');
			header.className = 'wps-aa-results-title';
			header.innerHTML = '<span>' + wps_aa_vars.results_title + '</span><button type="button" class="wps-aa-close-results">×</button>';
			header.style.cssText = 'display: flex; justify-content: space-between; padding: 8px 12px; background-color: #f5f5f5; border-bottom: 1px solid #e0e0e0; font-weight: bold; line-height: 1;';
			
			const closeBtn = header.querySelector('.wps-aa-close-results');
			closeBtn.style.cssText = 'background: none; border: none; font-size: 18px; cursor: pointer; padding: 0; min-height: auto; line-height: 1;';
			
			// Add the header as the first child
			targetContainer.insertBefore(header, targetContainer.firstChild);
			
			// Store input ID on container for future reference
			targetContainer.setAttribute('data-wps-aa-for', input_element.id);
			
			// Add click handler to close button
			closeBtn.addEventListener('click', (e) => {
				e.preventDefault();
				e.stopPropagation();
				targetContainer.style.display = 'none';
				input_element.blur();
			});
		}
	};
	
	// Set up multiple ways to detect when the dropdown is added
	
	// 1. Regular polling for a short period after initialization
	let attempts = 0;
	const maxAttempts = 10;
	const checkInterval = setInterval(() => {
		checkForPacContainer();
		attempts++;
		if (attempts >= maxAttempts) {
			clearInterval(checkInterval);
		}
	}, 500);
	
	// 2. On input focus
	input_element.addEventListener('focus', function() {
		// Wait a moment for Google to add its dropdown
		setTimeout(checkForPacContainer, 300);
	});
	
	// 3. When typing
	input_element.addEventListener('input', function() {
		// Wait a moment for Google to add its dropdown
		setTimeout(checkForPacContainer, 300);
	});
	
	// 4. Watch for DOM changes
	const observer = new MutationObserver(function(mutations) {
		checkForPacContainer();
	});
	
	// Start observing the document body for changes
	observer.observe(document.body, { childList: true, subtree: true });
	
	// Cleanup when input is removed
	const cleanupObserver = new MutationObserver((mutations) => {
		for (const mutation of mutations) {
			for (const node of mutation.removedNodes) {
				if (node === input_element) {
					observer.disconnect();
					cleanupObserver.disconnect();
					clearInterval(checkInterval);
				}
			}
		}
	});
	
	// Watch for the input being removed
	if (input_element.parentNode) {
		cleanupObserver.observe(input_element.parentNode, { childList: true });
	}
}

function wps_aa_parse_atts(inputString) {
    const regex = /{([^{}]+)}/g;
    const matches = inputString.match(regex);
    const attributes = [];

    if (matches) {
        for (const match of matches) {
            let attributeString = match.slice(1, -1);
            const attributePairs = attributeString.match(/[\w-]+=".*?"/g);

            const attributeObj = {
                original: match
            };

            if (attributePairs) {

                attributePairs.forEach(function(attribute) {
                    attributeString = attributeString.replace(attribute, '');
                });
                attributeString = attributeString.replace(/\s/g, '');
                attributeObj.key = '{' + attributeString + '}';

                for (const pair of attributePairs) {
                    const [attrKey, attrValue] = pair.split('=');
                    const trimmedKey = attrKey.trim();
                    const trimmedValue = attrValue.slice(1, -1);
                    attributeObj[trimmedKey] = trimmedValue;
                }

                attributes.push(attributeObj);
            } else {
                // No attributes found, add the entire string as the key
                attributeObj.key = match;
                attributes.push(attributeObj);
            }
        }
    }

    return attributes;
}

function wps_aa_change_value( selector, data ) {
	// Use querySelectorAll to find all matching elements, not just the first one
	const elements = document.querySelectorAll( selector );
	if ( elements.length > 0 ) {
		elements.forEach(element => {
			if ( element.tagName === 'SELECT' ) {
				element.value = data;
				if ( element.value !== data ) {
					for ( let i = 0; i < element.options.length; i++ ) {
						if ( element.options[i].text === data ) {
							element.selectedIndex = i;
							break;
						}
					}
				}
			} else {
				element.value = data;
			}
			element.dispatchEvent( new Event( 'change' ) );
		});
		
		if ( typeof jQuery !== 'undefined' ) {
			jQuery( selector ).trigger( 'change' );
		}
	} else {
		console.error( 'Cannot find selector to attach address autocomplete data', selector, data );
	}
}

function wps_aa_address1_format( country ) {
	const reverse_countries = [
		'DE', // Germany
		'AT', // Austria
		'CH', // Switzerland
		'NL', // Netherlands
		'MX', // Mexico
		'DK', // Denmark
		'NO', // Norway
		'SE', // Sweden
		'FI', // Finland
		'CZ', // Czech Republic
		'HU', // Hungary
		'SI', // Slovenia
		'HR', // Croatia
		'PL', // Poland (partial)
		'SK', // Slovakia
		'RU', // Russia (partial)
		'BE', // Belgium
		'LU', // Luxembourg
		'FR', // France (partial)
		'IT', // Italy (partial)
		'ES', // Spain (partial)
		'PT', // Portugal
		'AR', // Argentina
		'CL', // Chile
		'UY', // Uruguay
		'EE', // Estonia
		'LV', // Latvia
		'LT'  // Lithuania
	];

	return reverse_countries.includes( country ) ? 'reverse' : 'standard';
}