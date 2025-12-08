"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.Block = void 0;

var _blockData = require("@woocommerce/block-data");

var _blocksCheckout = require("@woocommerce/blocks-checkout");

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _blocks = require("@wordpress/blocks");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _settings = require("@woocommerce/settings");

/**
 * External dependencies
 */
var Block = function Block(_ref) {
  var checkoutExtensionData = _ref.checkoutExtensionData,
      extensions = _ref.extensions;

  var _getSetting = (0, _settings.getSetting)('szbd-method-selection_data', ''),
      map_feature_active = _getSetting.map_feature_active;

  var _ = require('lodash');

  var setExtensionData = checkoutExtensionData.setExtensionData;
  var validationErrorId = 'szbd-no-picked-location';

  var _useDispatch = (0, _data.useDispatch)('wc/store/validation'),
      setValidationErrors = _useDispatch.setValidationErrors,
      clearValidationError = _useDispatch.clearValidationError,
      showValidationError = _useDispatch.showValidationError;

  var validationError = (0, _data.useSelect)(function (select) {
    var store = select('wc/store/validation');
    return store.getValidationError(validationErrorId);
  });
  var prefersCollection = (0, _data.useSelect)(function (select) {
    var store = select(_blockData.CHECKOUT_STORE_KEY);
    return store.prefersCollection();
  });
  var getShippingRates = (0, _data.useSelect)(function (select) {
    var store = select(_blockData.CART_STORE_KEY);
    return store.getShippingRates();
  });

  var _useDispatch2 = (0, _data.useDispatch)(_blockData.CART_STORE_KEY),
      selectShippingRate = _useDispatch2.selectShippingRate,
      setIsCartDataStale = _useDispatch2.setIsCartDataStale,
      setCartData = _useDispatch2.setCartData,
      shippingRatesBeingSelected = _useDispatch2.shippingRatesBeingSelected;

  (0, _element.useEffect)(function () {
    //When shipping rates changes, check if it is not collection and then select neew method
    if (prefersCollection === false && !shippingRatesBeingSelected) {
      if (_.isUndefined(getShippingRates[0])) {
        return;
      }

      var localPickupIsSelected = _.find(getShippingRates[0].shipping_rates, {
        method_id: 'pickup_location',
        selected: true
      });

      var legal_method = _.find(getShippingRates[0].shipping_rates, function (rate) {
        return rate.method_id != 'pickup_location';
      });

      if (!_.isUndefined(localPickupIsSelected) && !_.isUndefined(legal_method)) {
        // If mode is shipping and there are valid shipping rates -> select first one
        selectShippingRate(legal_method.rate_id, getShippingRates[0].package_id);
      }
    }
  }, [getShippingRates, shippingRatesBeingSelected]);
  (0, _element.useEffect)(function () {
    // Clear shipping data when it is pickup location mode
    if (prefersCollection) {
      try {
        if (map_feature_active) {
          setExtensionData('szbd', 'point', null);
          setExtensionData('szbd', 'pluscode', '');
          (0, _blocksCheckout.extensionCartUpdate)({
            namespace: 'szbd-shipping-map-update',
            data: {
              lat: null,
              lng: null
            }
          }).then(function () {});
        }
      } catch (e) {}

      if (validationError) {
        clearValidationError(validationErrorId);
      }
    }
  }, [prefersCollection, validationError, setValidationErrors, validationErrorId, clearValidationError]);
  return null;
};

exports.Block = Block;