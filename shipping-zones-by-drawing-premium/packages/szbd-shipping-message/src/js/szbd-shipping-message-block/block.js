/**
 * External dependencies
 */

import { __ } from "@wordpress/i18n";
import { useSelect, useDispatch } from "@wordpress/data";
import _  from "lodash";
import { store as noticesStore } from '@wordpress/notices';


import { sprintf } from "@wordpress/i18n";

import { createInterpolateElement, renderToString } from "@wordpress/element";



export const Block = ({ checkoutExtensionData, extensions }) => {

 

  const { cartStore } = window.wc.wcBlocksData;

 
  
  


   //****************************    Section about method validation under develop phase                     ********************************************** */
  /*const { createNotice } =
  useDispatch( noticesStore );

  const { setErrorData } =
  useDispatch( CART_STORE_KEY );

  const [methodError, setMethodError] = useState(false);
  
  const prefersCollection = useSelect( ( select ) => {
		let store = select( CHECKOUT_STORE_KEY );

		return store.prefersCollection();
	} );


	const isBeforeProcessing  = useSelect( ( select ) => {
		let store = select( CHECKOUT_STORE_KEY );

		return store.isBeforeProcessing();
	} );
  const selectedRate = useSelect((select) => {
    const store = select(CART_STORE_KEY);

    const getShippingRates = store.getShippingRates();

    const selected = _.find(
      getShippingRates[ 0 ].shipping_rates,
      {  selected: true }
    );

   
   return selected.method_id;
    
  });

  const errorMessage1 = __('The shipping method selected can not be used.','szbd');

  useEffect( () => {

    if(!isBeforeProcessing){return;}

    if(prefersCollection && selectedRate != 'pickup_location'){
      setMethodError(true);
    }else if(!prefersCollection && selectedRate == 'pickup_location'){
      setMethodError(true);

    }else{
      setMethodError(false);

    }

    console.debug(selectedRate);


  },[isBeforeProcessing]);

  useEffect( () => {

    if(methodError){
     // Here we want to add error message if prefersCollection does not pairs with selected shipping rate
    }
   


  },[methodError]);*/


  //****************************                         ********************************************** */

  const shippingMessage = useSelect((select) => {
    try{
     
    const store = select(cartStore);
    

    return store.getCartData().extensions["szbd-shipping-message"].message;
    }catch(e){
      return '';
    }
  });

  const noShippingmethods = useSelect((select) => {

   try{
    
     const store = select(cartStore);

    const shippingPackages = store.getShippingRates();
    

   const rates_ =  !_.isUndefined(_.first(shippingPackages)
      .shipping_rates) ? _.first(shippingPackages)
      .shipping_rates : [];

  
   
   
    const rates = _.reject(rates_, function (p) {

        return p.method_id == 'pickup_location';


      });



    return _.isEmpty(rates);
   }catch(e){
    return false;
   }
  });

  /*if (false && methodError) {
    return (
      <div className="wc-block-components-shipping-rates-control__no-results-notice wc-block-components-notice-banner is-warning">
        {errorMessage1}
      </div>
    );
  }

 else 
  */
 if (noShippingmethods && shippingMessage != '') {
    return (
      <div className="wc-block-components-shipping-rates-control__no-results-notice wc-block-components-notice-banner is-warning">
        {shippingMessage}
      </div>
    );
  }

  
};
