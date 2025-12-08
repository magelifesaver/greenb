/**
 * External dependencies
 */
import {
	extensionCartUpdate
} from '@woocommerce/blocks-checkout';
import {
	useEffectOnce,
	useUpdateEffect
} from 'usehooks-ts';

import {
	useIsMounted
} from 'usehooks-ts';

export const Block = ({
	checkoutExtensionData,
	extensions
}) => {
	useEffectOnce(() => {
		const handleUpdate = () => {
			extensionCartUpdate({
				namespace: 'cma-new-address-set',
				data: {
					dummy: null,
				},
			});
		};
		window.addEventListener('cma_customer_address_set', handleUpdate);
	});
	// Return the block
	return null;
};

