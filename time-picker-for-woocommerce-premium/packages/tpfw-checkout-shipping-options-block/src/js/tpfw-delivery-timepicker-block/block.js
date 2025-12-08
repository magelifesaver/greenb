/**
 * External dependencies
 */


import { useEffect, useState, useCallback, useRef } from '@wordpress/element';
import { SelectControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { debounce } from 'lodash';


import { getSetting } from '@woocommerce/settings';

export const Block = ({ checkoutExtensionData, extensions }) => {
	const _ = require('lodash');
	

	const [date, setDate] = useState('');
	const [time, setTime] = useState('');

	

	// Get data analog 'localized'
	const {
		date_input,
		
	} = getSetting('tpfw-delivery-timepicker_data', '');

	

	const { setExtensionData } = checkoutExtensionData;

	const debouncedSetExtensionData = useCallback(
		debounce((namespace, key, value) => {
			setExtensionData(namespace, key, value);
		}, 1),
		[setExtensionData]
	);

	const debouncedSetDate = useCallback(
		debounce((value) => {
			setDate(value);
		}, 100),
		[setDate]
	);
	const debouncedSetTime = useCallback(
		debounce((value) => {
			setTime(value);
		}, 100),
		[setTime]
	);

	const validationErrorId = 'tpfw-delivery-time';

	const { setValidationErrors, clearValidationError } = useDispatch(
		'wc/store/validation'
	);

	const validationError = useSelect((select) => {
		const store = select('wc/store/validation');

		return store.getValidationError(validationErrorId);
	});

	useEffect(() => {
		
			jQuery(document).trigger('tpfw_loaded_blocks_timepicker_shipping');
		// Perform actions after the document has loaded
		const handleLoad = () => {
			
			jQuery(document).trigger('tpfw_loaded_blocks_timepicker_shipping');
		};

		window.addEventListener('load', handleLoad);

		return () => {
			
			setExtensionData('tpfw-deliverytime', 'delivery_time', '');
			setExtensionData('tpfw-deliverytime', 'delivery_date', '');
			window.removeEventListener('load', handleLoad);
		};
	},[]);

	// Set extensiondata when date and time updates
	useEffect(() => {
		
			setExtensionData('tpfw-deliverytime', 'delivery_time', time);
			setExtensionData('tpfw-deliverytime', 'delivery_date', date);
		
		
	
	}, [setExtensionData, time, date ]);

	
	
	const onTimeChange = function (event) {
		
		debouncedSetTime(event.target.value);
	};



	const onDateChange = function (newDate) {
		
		debouncedSetDate(newDate);
		debouncedSetTime('');
	};

	
	



	useEffect(() => {
	
		if (time != '' && time != null && time != 'undefined') {
			if (validationError) {
				clearValidationError(validationErrorId);
			}
			
		}else{
		

		setValidationErrors({
			[validationErrorId]: {
				message: __('Please pick a time.', 'time-picker-for-woocommerce'),
				hidden: true,
			},
		});
	}
		// Clean up when unmounting
		return () => {
			
			clearValidationError( validationErrorId );
		};
	}, [
		
		date,
		time,
		clearValidationError,
		
		setValidationErrors,
		validationErrorId,
	]);
	
	return (
		
		<div>
			
			{ (
				<>
					
			
					<div className="wc-block-components-checkout-step__heading">
						<h2 className="wc-block-components-title ">
							
							{__('Delivery time', 'time-picker-for-woocommerce')}
						</h2>
					</div>
					<p className="wc-block-components-checkout-step__description">
						{__(
							'Select the date and time when you like to receive your delivery.',
							'time-picker-for-woocommerce'
						)}
					</p>
					<div id="tpfw-date_field">
						<SelectControl
							id="tpfw-date"
							value={date}
							options={date_input.options}
							className="delivery"
							onChange={(newDate) => onDateChange(newDate)}
							__nextHasNoMarginBottom={ true }
						/>
						
					</div>
					<div className="wc-block-components-text-input">
						<input
							id="tpfw-time"
							type="text"
							value={time}
							required={true}
							placeholder={__('Time', 'time-picker-for-woocommerce')}
							className="tpfw-time delivery"
							onInput={(event) => {
								onTimeChange(event);
							}}
							
						/>
						
					</div>
				</>
			)}
		</div>
	);
};
