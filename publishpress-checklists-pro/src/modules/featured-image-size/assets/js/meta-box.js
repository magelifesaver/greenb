/**
 * @package PublishPress
 * @author PublishPress
 *
 * Copyright (C) 2021 PublishPress
 *
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * This file is part of PublishPress
 *
 * PublishPress is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */

(function ($, document, PP_Checklists, wpApiSettings) {
    'use strict';

    $(function () {
        let featuredImageId   = 0;
        let featuredImageData = {
            'width': 0,
            'height': 0
        };
        let cacheFeaturedImageSize = {
            width: 0,
            height: 0
        };

        function getFeaturedImageSizeFromApi()
        {
            let imageId = parseInt($('#_thumbnail_id').val());

            if (0 === imageId || imageId === featuredImageId) {
                return cacheFeaturedImageSize;
            }

            featuredImageId = imageId;

            $.ajax( {
                url: wpApiSettings.root + 'wp/v2/media/' + featuredImageId,
                sync: true,
                method: 'GET',
                beforeSend: function ( xhr ) {
                    xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
                }
            } ).done( function ( response ) {
                cacheFeaturedImageSize = {
                    'width': response.media_details.width,
                    'height': response.media_details.height
                }
            } );

            return cacheFeaturedImageSize;
        }

        function getFeaturedImageSizeFromEditor()
        {
            let editor = wp.data.select('core/editor');
            let imageId = editor.getEditedPostAttribute('featured_media');
            if (imageId > 0) {
                let imageObj = wp.data.select('core').getMedia(imageId);

                if (typeof imageObj !== 'undefined') {
                    return {
                        'width': imageObj.media_details.width,
                        'height': imageObj.media_details.height
                    }
                } else {

                    if (0 === imageId || imageId === featuredImageId) {
                        return cacheFeaturedImageSize;
                    }
                    
                    // let fetch the image for users without edit_other_posts capabilities
                    wp.media.attachment(imageId).fetch().then((image) => {
                        if (typeof image === 'object') {
                            featuredImageId = imageId;
                            cacheFeaturedImageSize = {
                                'width': image.width,
                                'height': image.height
                            }
                            featuredImageData = {
                                'width': image.width,
                                'height': image.height
                            };
                        }
                      });
                      return featuredImageData;
                }
            }

            return {
                width: 0,
                height: 0
            }
        }

        /**
         *
         * Featured image width
         *
         */
        var $featuredImageWidthElements = $('[id^="pp-checklists-req-featured_image_width"]');
        if ($featuredImageWidthElements.length > 0) {
            $(document).on(PP_Checklists.EVENT_TIC, function (event) {
                let imageSize;

                if (PP_Checklists.is_gutenberg_active()) {
                    imageSize = getFeaturedImageSizeFromEditor();
                } else {
                    imageSize = getFeaturedImageSizeFromApi();
                }

                $featuredImageWidthElements.each(function () {
                    var $element = $(this);
                    var requirementId = $element.attr('id').replace('pp-checklists-req-', '');

                    // Get config for this specific requirement (try specific first, then fallback to original)
                    var config = (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements[requirementId])
                      ? objectL10n_checklist_requirements.requirements[requirementId]
                      : (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements['featured_image_width'])
                        ? objectL10n_checklist_requirements.requirements['featured_image_width']
                        : null;

                    if (config && config.value) {
                        let min_value = parseInt(config.value[0]);
                        let max_value = parseInt(config.value[1]);

                        $element.trigger(
                            PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                            PP_Checklists.check_valid_quantity(imageSize.width, min_value, max_value)
                        );
                    }
                });
            });
        }

        /**
         *
         * Featured image height
         *
         */
        var $featuredImageHeightElements = $('[id^="pp-checklists-req-featured_image_height"]');
        if ($featuredImageHeightElements.length > 0) {
            $(document).on(PP_Checklists.EVENT_TIC, function (event) {
                let imageSize;

                if (PP_Checklists.is_gutenberg_active()) {
                    imageSize = getFeaturedImageSizeFromEditor();
                } else {
                    imageSize = getFeaturedImageSizeFromApi();
                }

                $featuredImageHeightElements.each(function () {
                    var $element = $(this);
                    var requirementId = $element.attr('id').replace('pp-checklists-req-', '');

                    // Get config for this specific requirement (try specific first, then fallback to original)
                    var config = (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements[requirementId])
                      ? objectL10n_checklist_requirements.requirements[requirementId]
                      : (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements['featured_image_height'])
                        ? objectL10n_checklist_requirements.requirements['featured_image_height']
                        : null;

                    if (config && config.value) {
                        let min_value = parseInt(config.value[0]);
                        let max_value = parseInt(config.value[1]);

                        $element.trigger(
                            PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                            PP_Checklists.check_valid_quantity(imageSize.height, min_value, max_value)
                        );
                    }
                });
            });
        }
    });

})(jQuery, document, PP_Checklists, wpApiSettings);