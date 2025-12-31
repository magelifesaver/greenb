/**
 * @package PublishPress
 * @author PublishPress
 *
 * PublishPress Status Filter for Gutenberg
 * This file manages checklist visibility based on post status in the Gutenberg editor.
 */

/**
 * Class PPChStatusFilter manages which checklists are shown or hidden based on post status.
 * 
 * @class PPChStatusFilter
 */
class PPChStatusFilter {
  /**
   * DOM element selectors used throughout the class.
   * 
   * @static
   * @type {Object}
   */
  static SELECTORS = {
    checklistBox: '#pp-checklists-sidebar-req-box',
    sidebarContent: '#pp-checklists-sidebar-content',
    metaBox: '#pp-checklists-req-box',
    metaBoxContainer: '#pp_checklist_meta-meta-box',
    panelArea: '#publishpress-checklists-panel\:checklists-sidebar',
    editorSidebar: '.interface-interface-skeleton__sidebar'
  };

  /**
   * Event names for communication with other components.
   * 
   * @static
   * @type {Object}
   */
  static EVENTS = {
    TIC: 'pp-checklists:tic',
    VALIDATE: 'pp-checklists:validate_requirements',
    UPDATE_STATE: 'pp-checklists:update_requirement_state',
    UPDATE_FAILED: 'pp-checklists.update-failed-requirements',
    REQUIREMENTS_UPDATED: 'pp-checklists.requirements-updated'
  };

  /**
   * Post status constants for readable code.
   * 
   * @static
   * @type {Object}
   */
  static POST_STATUS = {
    DRAFT: 'draft',
    AUTO_DRAFT: 'auto-draft',
    PUBLISH: 'publish',
    PENDING: 'pending',
    INITIAL_LOAD: 'initial_load_no_items'
  };

  /**
   * Constructs the status filter instance.
   * 
   * @param {Object} wp - WordPress global object containing data, hooks, and domReady.
   * @param {Function} $ - jQuery instance for DOM manipulation.
   * @param {Object} data - Localized config data (hideOnStatuses, debug_exclusion_options).
   */
  constructor(wp, $, data) {
    this.wp = wp;
    this.$ = $;
    this.data = data || {};
    this.select = wp.data.select;
    this.dispatch = wp.data.dispatch;
    this.domReady = wp.domReady;

    // State tracking
    this.previousStatus = null;
    this.currentExclusionOptions = {};
    this.currentVisibleStatus = '';

    // Throttle timer for subscribe callback
    this._subscribeThrottleTimer = null;
  }

  /**
   * Initializes the component when DOM is ready.
   * 
   * @returns {void}
   */
  init() {
    this.domReady(() => {
      this._trackPostStatus();
      this._setupEventListeners();
      
      // Initial visibility update after a short delay
      setTimeout(() => {
        const noVisibleItems = this.$('li.pp-checklists-req:visible').length === 0;
        this.updateNoTasksMessage(noVisibleItems);
      }, 500);
    });
  }

  /**
   * Sets up event listeners for the component.
   * 
   * @private
   * @returns {void}
   */
  _setupEventListeners() {
    // Listen for periodic ticks to update visibility
    this.$(document).on(PPChStatusFilter.EVENTS.TIC, () => {
      this.applyVisibilityRules();
    });
  }



  /**
   * Gets current post status from the editor.
   * 
   * @returns {string} The current post status
   */
  getCurrentPostStatus() {
    const editorStore = this.select('core/editor');

    const editedStatus = editorStore.getEditedPostAttribute?.('status');
    if (typeof editedStatus === 'string' && editedStatus) {
      return editedStatus;
    }

    const postEdits = editorStore.getPostEdits?.();
    if (postEdits && typeof postEdits.status === 'string' && postEdits.status) {
      return postEdits.status;
    }

    return editorStore.getCurrentPost?.()?.status;
  }

  /**
   * Applies visibility rules to checklist items based on current post status.
   * Shows or hides items according to configuration.
   * 
   * @returns {void}
   */
  applyVisibilityRules() {
    // Skip if no current status context
    if (!this.currentVisibleStatus) return;

    const $items = this.$('li.pp-checklists-req');
    if ($items.length === 0 && this.currentVisibleStatus !== PPChStatusFilter.POST_STATUS.INITIAL_LOAD) return;
    
    // Process each checklist item
    $items.each((_, element) => {
      const $element = this.$(element);
      const id = $element.data('id');
      if (!id) return;
      
      const excluded = this.currentExclusionOptions[id] || [];
      const shouldHide = this._shouldHideItem(excluded);
      
      // Apply visibility change only if needed
      const isCurrentlyHidden = $element.css('display') === 'none';
      if (shouldHide && !isCurrentlyHidden) {
        $element.hide();
      } else if (!shouldHide && isCurrentlyHidden) {
        $element.show();
      }
    });

    // Update UI based on visible items
    const noVisibleItems = this.$('li.pp-checklists-req:visible').length === 0;
    this.updateNoTasksMessage(noVisibleItems);
    this.updateWarningIcon();
  }

  /**
   * Determines if a checklist item should be hidden based on status rules.
   * 
   * @private
   * @param {Array} excludedStatuses - List of statuses where this item should be hidden
   * @returns {boolean} True if the item should be hidden
   */
  _shouldHideItem(excludedStatuses) {
    if (!Array.isArray(excludedStatuses)) return false;

    // currentVisibleStatus is already normalized (auto-draft → draft)
    return excludedStatuses.includes(this.currentVisibleStatus);
  }

  /**
   * Toggles the "no tasks" message in both Gutenberg sidebar and classic editor.
   * 
   * @param {boolean} show - Whether to show the message when no items remain
   * @returns {void}
   */
  updateNoTasksMessage(show) {
    // Update Gutenberg sidebar
    this._updateSidebarNoTasksMessage(show);
    
    // Update Gutenberg metabox
    this._updateMetaboxNoTasksMessage(show);
  }

  /**
   * Updates the "no tasks" message in the Gutenberg sidebar.
   * 
   * @private
   * @param {boolean} show - Whether to show the message
   * @returns {void}
   */
  _updateSidebarNoTasksMessage(show) {
    const $sidebarContentContainer = this.$(PPChStatusFilter.SELECTORS.sidebarContent);
    if (!$sidebarContentContainer.length) return;

    // Remove any existing message
    $sidebarContentContainer.find('.pp-no-tasks-message').remove();
    
    // Toggle visibility of "required" text
    $sidebarContentContainer.find('em').filter(function() {
      return this.textContent.toLowerCase().indexOf('required') !== -1;
    })[show ? 'hide' : 'show']();
    
    if (show && window.i18n?.noTaskLabel) {
      // Hide the checklist box and show the message
      this.$(PPChStatusFilter.SELECTORS.checklistBox).hide();
      $sidebarContentContainer.prepend(`<p class="pp-no-tasks-message"><em>${window.i18n.noTaskLabel}</em></p>`);
    } else {
      // Show the checklist box
      this.$(PPChStatusFilter.SELECTORS.checklistBox).show();
    }
  }

  /**
   * Updates the "no tasks" message in the Classic editor metabox.
   * 
   * @private
   * @param {boolean} show - Whether to show the message
   * @returns {void}
   */
  _updateMetaboxNoTasksMessage(show) {
    const $meta = this.$(PPChStatusFilter.SELECTORS.metaBox);
    if (!$meta.length) return;

    const $box = this.$(PPChStatusFilter.SELECTORS.metaBoxContainer);
    
    // Find the existing server-rendered "no tasks" message inside the metabox
    const $existingMessage = $meta.find('p').filter(function() {
      return this.textContent.toLowerCase().indexOf('checklist tasks') !== -1;
    });
    
    if (show) {
      // Hide requirement elements
      $meta.find('.required, .pp-checklists-required-legend').hide();
      $box.find('em').filter(function() {
        return this.textContent.toLowerCase().indexOf('required') !== -1;
      }).hide();
      
      // If server already rendered a message, just show it; otherwise add our own
      if ($existingMessage.length) {
        $existingMessage.show();
      } else if (!$meta.siblings('.pp-no-tasks-message').length) {
        const message = window.ppChecklists?.noTaskLabel || "You don't have to complete any Checklist tasks.";
        $meta.before(`<p class="pp-no-tasks-message"><em>${message}</em></p>`);
      }
    } else {
      // Hide server-rendered message
      $existingMessage.hide();
      
      // Only show requirement elements if there are blocks
      if ($meta.find('.pp-checklists-block').length) {
        $meta.find('.required, .pp-checklists-required-legend').show();
        $box.find('em').filter(function() {
          return this.textContent.toLowerCase().indexOf('required') !== -1;
        }).show();
      }
      
      // Remove the JS-added message
      $meta.siblings('.pp-no-tasks-message').remove();
    }
  }

  /**
   * Updates the warning icon based on unfulfilled checklist requirements.
   * Triggers validation and updates the UI accordingly.
   * 
   * @returns {void}
   */
  updateWarningIcon() {
    // Try to validate requirements if core plugin available
    this._validateRequirements();

    // Count failed requirements not excluded for current status
    const failedRequirementCount = this._countFailedRequirements();

    // Update body class for warning icon
    this.$('body')[failedRequirementCount > 0 ? 'addClass' : 'removeClass']('ppch-show-publishing-warning-icon');
    
    // Update warning component with failed requirements
    this.updatePublishPressWarningComponent();
  }

  /**
   * Validates requirements using the core checklists plugin.
   * 
   * @private
   * @returns {void}
   */
  _validateRequirements() {
    if (!window.PP_Checklists?.validate_requirements) return;
    
    try {
      // Temporarily set publishing state to false to avoid triggering
      // the publishing flow during validation
      const previousPublishingState = window.PP_Checklists.state.is_publishing;
      window.PP_Checklists.state.is_publishing = false;
      
      // Trigger validation
      this.$(document).trigger(PPChStatusFilter.EVENTS.VALIDATE);
      
      // Restore previous state
      window.PP_Checklists.state.is_publishing = previousPublishingState;
    } catch (e) {
      console.error('Error during checklist validation for warning icon:', e);
    }
  }

  /**
   * Counts failed requirements that are visible for the current status.
   * 
   * @private 
   * @returns {number} Count of failed requirements
   */
  _countFailedRequirements() {
    let failedCount = 0;
    
    // Skip counting if the entire panel should be hidden for this status
    const hidePanelOnThisStatus = this._shouldHidePanelForCurrentStatus();
    if (hidePanelOnThisStatus || !this.currentVisibleStatus) return failedCount;

    // Count visible failed requirements
    this.$('li.pp-checklists-req.status-no').each((_, element) => {
      const $element = this.$(element);
      const id = $element.data('id');
      if (!id) return;

      // Check if this requirement is excluded for current status
      const isHidden = this._isItemExcludedForCurrentStatus(id);
      
      // Count if not hidden
      if (!isHidden) {
        failedCount++;
      }
    });

    return failedCount;
  }

  /**
   * Checks if a specific item should be excluded for the current status.
   * 
   * @private
   * @param {string} itemId - Item identifier
   * @returns {boolean} True if the item should be excluded
   */
  _isItemExcludedForCurrentStatus(itemId) {
    const itemExclusionRules = this.currentExclusionOptions[itemId] || [];
    return Array.isArray(itemExclusionRules) && 
           itemExclusionRules.includes(this.currentVisibleStatus);
  }

  /**
   * Checks if the entire checklist panel should be hidden for current status.
   * 
   * @private
   * @returns {boolean} True if panel should be hidden
   */
  _shouldHidePanelForCurrentStatus() {
    return Array.isArray(this.data.hideOnStatuses) && 
           this.data.hideOnStatuses.includes(this.currentVisibleStatus);
  }

  /**
   * Updates the PublishPress warning component with failed requirements.
   * Uses wp.hooks to communicate with other components.
   * 
   * @returns {void}
   */
  updatePublishPressWarningComponent() {
    if (!this.wp.hooks?.doAction) return;

    // Prepare containers for failed requirements
    const items = { block: [], warning: [] };
    
    // Skip if panel should be hidden or no status is set
    const hidePanelOnThisStatus = this._shouldHidePanelForCurrentStatus();
    if (hidePanelOnThisStatus || !this.currentVisibleStatus) {
      this.wp.hooks.doAction(PPChStatusFilter.EVENTS.UPDATE_FAILED, items);
      return;
    }

    // Collect visible failed requirements
    this._collectFailedRequirements(items);
    
    // Notify other components about failed requirements
    this.wp.hooks.doAction(PPChStatusFilter.EVENTS.UPDATE_FAILED, items);
  }

  /**
   * Collects failed requirements and categorizes them by type (block/warning).
   * Prioritizes sidebar items when available to prevent duplication.
   * 
   * @private
   * @param {Object} items - Container for failed requirements
   * @returns {void}
   */
  _collectFailedRequirements(items) {
    // Prioritize sidebar items when available to prevent duplication
    const $sidebarItems = this.$('#pp-checklists-sidebar-req-box li.pp-checklists-req.status-no');
    const $items = $sidebarItems.length > 0 ? $sidebarItems : this.$('#pp-checklists-req-box li.pp-checklists-req.status-no');
    
    $items.each((_, element) => {
      const $element = this.$(element);
      const id = $element.data('id');
      if (!id) return;

      // Skip if the item is excluded for current status
      if (this._isItemExcludedForCurrentStatus(id)) return;

      // Find the label and add to appropriate category
      const $label = $element.find('.status-label');
      if ($label.length) {
        const labelText = $label.html().trim();
        
        if ($element.hasClass('pp-checklists-block')) {
          items.block.push(labelText);
        } else if ($element.hasClass('pp-checklists-warning')) {
          items.warning.push(labelText);
        }
      }
    });
  }

  /**
   * Updates the visibility of checklist items based on post status.
   * Controls both panel-level and item-level visibility.
   * 
   * @param {string} newStatus - The updated post status
   * @returns {void}
   */
  updateChecklistItemsVisibility(newStatus) {
    // Normalize status with special handling for auto-draft
    const isAutoDraft = newStatus === PPChStatusFilter.POST_STATUS.AUTO_DRAFT;
    const normalizedStatus = isAutoDraft ? PPChStatusFilter.POST_STATUS.DRAFT : newStatus;
    
    // Update current status tracker
    this.currentVisibleStatus = normalizedStatus;
    
    // Check if entire panel should be hidden for this status
    if (this._shouldHideEntirePanelForStatus(normalizedStatus)) {
      return;
    }
    
    // Update exclusion options and apply visibility rules
    this._updateExclusionOptions();
    this.applyVisibilityRules();
  }

  /**
   * Checks if the entire checklist panel should be hidden for a given status.
   * Updates UI accordingly.
   * 
   * @private
   * @param {string} status - Post status to check
   * @returns {boolean} True if panel was hidden
   */
  _shouldHideEntirePanelForStatus(status) {
    const hideOn = Array.isArray(this.data.hideOnStatuses) ? this.data.hideOnStatuses : [];
    
    if (hideOn.includes(status)) {
      // Hide both sidebar and metabox
      this.$(PPChStatusFilter.SELECTORS.checklistBox).hide();
      this.$(PPChStatusFilter.SELECTORS.metaBox).hide();
      return true;
    } else {
      // Show both sidebar and metabox
      this.$(PPChStatusFilter.SELECTORS.checklistBox).show();
      this.$(PPChStatusFilter.SELECTORS.metaBox).show();
      return false;
    }
  }

  /**
   * Updates the exclusion options from configuration data.
   * 
   * @private
   * @returns {void}
   */
  _updateExclusionOptions() {
    const opts = this.data.debug_exclusion_options_for_post_type || {};
    
    if (typeof opts !== 'object') {
      // Invalid options, reset and show all
      this.currentExclusionOptions = {};
      this.$('li.pp-checklists-req').show();
      return;
    }
    
    this.currentExclusionOptions = opts;
  }

  /**
   * Initializes post status tracking and updates visibility accordingly.
   * 
   * @private
   * @returns {void}
   */
  _trackPostStatus() {
    try {
      // Get initial post status
      const initialPostStatus = this.getCurrentPostStatus() || PPChStatusFilter.POST_STATUS.INITIAL_LOAD;
      
      // Set status and update visibility
      this.previousStatus = initialPostStatus;
      this.updateChecklistItemsVisibility(initialPostStatus);
      
      // Subscribe to status changes
      this._subscribeStatusChanges();
    } catch (e) {
      console.error('PPChStatusFilter init status tracking error:', e);
    }
  }

  /**
   * Subscribes to post status changes in the editor.
   * Throttled to avoid excessive updates on every store change.
   * 
   * @private
   * @returns {void}
   */
  _subscribeStatusChanges() {
    this.wp.data.subscribe(() => {
      // Throttle: only check status every 100ms max
      if (this._subscribeThrottleTimer) return;
      
      this._subscribeThrottleTimer = setTimeout(() => {
        this._subscribeThrottleTimer = null;
        
        // Get current post status
        const currentPostStatus = this.getCurrentPostStatus();
        
        // Update visibility if status changed
        if (currentPostStatus && currentPostStatus !== this.previousStatus) {
          this.previousStatus = currentPostStatus;
          this.updateChecklistItemsVisibility(currentPostStatus);
        }
      }, 100);
    });
  }

}

// Initialize the component when DOM is ready
window.wp.domReady(() => {
  // Only initialize if we have the required data
  if (typeof window.ppcStatusFilterGutenbergData !== 'undefined') {
    new PPChStatusFilter(window.wp, window.jQuery, window.ppcStatusFilterGutenbergData).init();
  }
});