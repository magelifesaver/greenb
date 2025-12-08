"use strict";

var CmaAutoComplete_shipping = CmaAutoComplete_shipping || {};
CmaAutoComplete_shipping.method = {
  IdSeparator: "",
  autocomplete: "",
  initialize: function initialize() {
    var _ref, Map, _ref2, Geo, _ref3, Geocode, _ref4, Autocomplete, $this, cma_bshipping_address, cma_bshipping_country;

    return regeneratorRuntime.async(function initialize$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return regeneratorRuntime.awrap(google.maps.importLibrary("maps"));

          case 2:
            _ref = _context.sent;
            Map = _ref.Map;
            _context.next = 6;
            return regeneratorRuntime.awrap(google.maps.importLibrary("geometry"));

          case 6:
            _ref2 = _context.sent;
            Geo = _ref2.Geo;
            _context.next = 10;
            return regeneratorRuntime.awrap(google.maps.importLibrary("geocoding"));

          case 10:
            _ref3 = _context.sent;
            Geocode = _ref3.Geocode;
            _context.next = 14;
            return regeneratorRuntime.awrap(google.maps.importLibrary("places"));

          case 14:
            _ref4 = _context.sent;
            Autocomplete = _ref4.Autocomplete;
            this.autocomplete = new google.maps.places.Autocomplete(document.getElementById('autocomplete_cma'), {
              types: _.toArray(cmadel.autocomplete_types),
              fields: ["types"]
            });
            $this = this;
            google.maps.event.addListener(this.autocomplete, 'place_changed', function (event) {
              var response = $this.autocomplete.getPlace();

              if (response.types !== undefined && response.types.length) {
                document.getElementById('cma_bshipping_address_1').value = 'ok';
                jQuery(document.body).trigger("cma_new_shipping_address");
              }
            });
            cma_bshipping_address = document.getElementById("autocomplete_cma");

            if (cma_bshipping_address != null) {
              cma_bshipping_address.addEventListener("focus", function (event) {
                CmaAutoComplete_shipping.method.setAutocompleteCountry();

                if (cmadel.geolocate_user == 1) {
                  CmaAutoComplete_shipping.method.geolocate();
                }
              }, true);
            }

            cma_bshipping_country = document.getElementById("cma_bshipping_country");

            if (cma_bshipping_country != null) {
              cma_bshipping_country.addEventListener("change", function (event) {
                CmaAutoComplete_shipping.method.setAutocompleteCountry();
              }, true);
            }

          case 23:
          case "end":
            return _context.stop();
        }
      }
    }, null, this);
  },
  geolocate: function geolocate() {
    var auto = this.autocomplete;

    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function (position) {
        var geolocation = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        };
        var circle = new google.maps.Circle({
          center: geolocation,
          radius: position.coords.accuracy
        });
        auto.setBounds(circle.getBounds());
      });
    }
  },
  setAutocompleteCountry: function setAutocompleteCountry() {
    var country;

    if (document.getElementById('cma_bshipping_country') === null) {
      country = '';
    } else {
      country = document.getElementById('cma_bshipping_country').value;
    }

    if (cmadel.restrict_country == 'no' && country != '') {
      this.autocomplete.setComponentRestrictions({
        'country': country
      });
    }
  },
  disableDefault: function disableDefault() {
    if (document.getElementById('autocomplete_cma') != null) {
      var shipaddr = document.getElementById('autocomplete_cma');
      shipaddr.addEventListener('keydown', function (e) {
        if (e.keyCode == 13 || e.which == 13) {
          e.preventDefault();
        }
      });
    }
  }
};
window.addEventListener('load', function () {
  if (jQuery("#autocomplete_cma").length && !jQuery("#autocomplete_cma").parents('div[data-elementor-type="popup"]').length) {
    CmaAutoComplete_shipping.method.initialize();
    CmaAutoComplete_shipping.method.disableDefault();
  }
});
window.addEventListener('elementor/popup/show', function () {
  var popupelement = jQuery('div[data-elementor-type="popup"]').find("#autocomplete_cma");

  if (popupelement.length) {
    CmaAutoComplete_shipping.method.initialize();
    CmaAutoComplete_shipping.method.disableDefault();
  }
});