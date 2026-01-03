jQuery( document ).ready(function() {
	
	if (!window.wc || !window.wc.blocksCheckout) {
		return ;
	}

	const { registerCheckoutFilters } = window.wc.blocksCheckout;

	const cleanString = (str) => str.toLowerCase().replace(/[^a-z0-9\s]/gi, '').split(/\s+/);

	function getMatchPercentage(str1, str2) {
		if (!str1 && !str2) return 0;
		if (!str1 || !str2) return 0;
		if (str1 === str2) return 100;
	
		const words1 = new Set(cleanString(str1));
		const words2 = new Set(cleanString(str2));
	
		if (words1.size === 0 && words2.size === 0) return 0;
		if (words1.size === 0 || words2.size === 0) return 100;
		const commonWordsCount = [...words1].filter(word => words2.has(word)).length;
		const totalUniqueWordsCount = new Set([...words1, ...words2]).size;
	
		return (commonWordsCount / totalUniqueWordsCount) * 100;
	}
	

	const decodeHTMLEntities = (text) => {
		const element = document.createElement('div');
		element.innerHTML = text;
		return element.textContent;
	}

	const modifyProceedToCheckoutButtonLink = (
		defaultValue,
		extensions,
		args
	) => {

		//ct-mpac-remaining-amount
		if ( args.cart && args.cart.errors && args.cart.errors.length ) {
			for (let i = 0; i < args.cart.errors.length; i++) {
				const errorMsg = decodeHTMLEntities(args.cart.errors[i].message);
				if (getMatchPercentage(errorMsg, ct_mpac_cart_obj.cartMessage) > 75) {
					return './#';
				}
				
			}
		}

		return defaultValue;
	};

	registerCheckoutFilters( 'ct-mpac', {
		proceedToCheckoutButtonLink: modifyProceedToCheckoutButtonLink,
	} );

});