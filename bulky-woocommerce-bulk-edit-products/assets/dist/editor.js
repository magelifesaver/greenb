/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ 341:
/***/ (function(module) {

/*! @license DOMPurify 3.0.2 | (c) Cure53 and other contributors | Released under the Apache license 2.0 and Mozilla Public License 2.0 | github.com/cure53/DOMPurify/blob/3.0.2/LICENSE */

(function (global, factory) {
   true ? module.exports = factory() :
  0;
})(this, (function () { 'use strict';

  const {
    entries,
    setPrototypeOf,
    isFrozen,
    getPrototypeOf,
    getOwnPropertyDescriptor
  } = Object;
  let {
    freeze,
    seal,
    create
  } = Object; // eslint-disable-line import/no-mutable-exports

  let {
    apply,
    construct
  } = typeof Reflect !== 'undefined' && Reflect;

  if (!apply) {
    apply = function apply(fun, thisValue, args) {
      return fun.apply(thisValue, args);
    };
  }

  if (!freeze) {
    freeze = function freeze(x) {
      return x;
    };
  }

  if (!seal) {
    seal = function seal(x) {
      return x;
    };
  }

  if (!construct) {
    construct = function construct(Func, args) {
      return new Func(...args);
    };
  }

  const arrayForEach = unapply(Array.prototype.forEach);
  const arrayPop = unapply(Array.prototype.pop);
  const arrayPush = unapply(Array.prototype.push);
  const stringToLowerCase = unapply(String.prototype.toLowerCase);
  const stringToString = unapply(String.prototype.toString);
  const stringMatch = unapply(String.prototype.match);
  const stringReplace = unapply(String.prototype.replace);
  const stringIndexOf = unapply(String.prototype.indexOf);
  const stringTrim = unapply(String.prototype.trim);
  const regExpTest = unapply(RegExp.prototype.test);
  const typeErrorCreate = unconstruct(TypeError);
  function unapply(func) {
    return function (thisArg) {
      for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
        args[_key - 1] = arguments[_key];
      }

      return apply(func, thisArg, args);
    };
  }
  function unconstruct(func) {
    return function () {
      for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
        args[_key2] = arguments[_key2];
      }

      return construct(func, args);
    };
  }
  /* Add properties to a lookup table */

  function addToSet(set, array, transformCaseFunc) {
    transformCaseFunc = transformCaseFunc ? transformCaseFunc : stringToLowerCase;

    if (setPrototypeOf) {
      // Make 'in' and truthy checks like Boolean(set.constructor)
      // independent of any properties defined on Object.prototype.
      // Prevent prototype setters from intercepting set as a this value.
      setPrototypeOf(set, null);
    }

    let l = array.length;

    while (l--) {
      let element = array[l];

      if (typeof element === 'string') {
        const lcElement = transformCaseFunc(element);

        if (lcElement !== element) {
          // Config presets (e.g. tags.js, attrs.js) are immutable.
          if (!isFrozen(array)) {
            array[l] = lcElement;
          }

          element = lcElement;
        }
      }

      set[element] = true;
    }

    return set;
  }
  /* Shallow clone an object */

  function clone(object) {
    const newObject = create(null);

    for (const [property, value] of entries(object)) {
      newObject[property] = value;
    }

    return newObject;
  }
  /* This method automatically checks if the prop is function
   * or getter and behaves accordingly. */

  function lookupGetter(object, prop) {
    while (object !== null) {
      const desc = getOwnPropertyDescriptor(object, prop);

      if (desc) {
        if (desc.get) {
          return unapply(desc.get);
        }

        if (typeof desc.value === 'function') {
          return unapply(desc.value);
        }
      }

      object = getPrototypeOf(object);
    }

    function fallbackValue(element) {
      console.warn('fallback value for', element);
      return null;
    }

    return fallbackValue;
  }

  const html$1 = freeze(['a', 'abbr', 'acronym', 'address', 'area', 'article', 'aside', 'audio', 'b', 'bdi', 'bdo', 'big', 'blink', 'blockquote', 'body', 'br', 'button', 'canvas', 'caption', 'center', 'cite', 'code', 'col', 'colgroup', 'content', 'data', 'datalist', 'dd', 'decorator', 'del', 'details', 'dfn', 'dialog', 'dir', 'div', 'dl', 'dt', 'element', 'em', 'fieldset', 'figcaption', 'figure', 'font', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hgroup', 'hr', 'html', 'i', 'img', 'input', 'ins', 'kbd', 'label', 'legend', 'li', 'main', 'map', 'mark', 'marquee', 'menu', 'menuitem', 'meter', 'nav', 'nobr', 'ol', 'optgroup', 'option', 'output', 'p', 'picture', 'pre', 'progress', 'q', 'rp', 'rt', 'ruby', 's', 'samp', 'section', 'select', 'shadow', 'small', 'source', 'spacer', 'span', 'strike', 'strong', 'style', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'template', 'textarea', 'tfoot', 'th', 'thead', 'time', 'tr', 'track', 'tt', 'u', 'ul', 'var', 'video', 'wbr']); // SVG

  const svg$1 = freeze(['svg', 'a', 'altglyph', 'altglyphdef', 'altglyphitem', 'animatecolor', 'animatemotion', 'animatetransform', 'circle', 'clippath', 'defs', 'desc', 'ellipse', 'filter', 'font', 'g', 'glyph', 'glyphref', 'hkern', 'image', 'line', 'lineargradient', 'marker', 'mask', 'metadata', 'mpath', 'path', 'pattern', 'polygon', 'polyline', 'radialgradient', 'rect', 'stop', 'style', 'switch', 'symbol', 'text', 'textpath', 'title', 'tref', 'tspan', 'view', 'vkern']);
  const svgFilters = freeze(['feBlend', 'feColorMatrix', 'feComponentTransfer', 'feComposite', 'feConvolveMatrix', 'feDiffuseLighting', 'feDisplacementMap', 'feDistantLight', 'feFlood', 'feFuncA', 'feFuncB', 'feFuncG', 'feFuncR', 'feGaussianBlur', 'feImage', 'feMerge', 'feMergeNode', 'feMorphology', 'feOffset', 'fePointLight', 'feSpecularLighting', 'feSpotLight', 'feTile', 'feTurbulence']); // List of SVG elements that are disallowed by default.
  // We still need to know them so that we can do namespace
  // checks properly in case one wants to add them to
  // allow-list.

  const svgDisallowed = freeze(['animate', 'color-profile', 'cursor', 'discard', 'fedropshadow', 'font-face', 'font-face-format', 'font-face-name', 'font-face-src', 'font-face-uri', 'foreignobject', 'hatch', 'hatchpath', 'mesh', 'meshgradient', 'meshpatch', 'meshrow', 'missing-glyph', 'script', 'set', 'solidcolor', 'unknown', 'use']);
  const mathMl$1 = freeze(['math', 'menclose', 'merror', 'mfenced', 'mfrac', 'mglyph', 'mi', 'mlabeledtr', 'mmultiscripts', 'mn', 'mo', 'mover', 'mpadded', 'mphantom', 'mroot', 'mrow', 'ms', 'mspace', 'msqrt', 'mstyle', 'msub', 'msup', 'msubsup', 'mtable', 'mtd', 'mtext', 'mtr', 'munder', 'munderover', 'mprescripts']); // Similarly to SVG, we want to know all MathML elements,
  // even those that we disallow by default.

  const mathMlDisallowed = freeze(['maction', 'maligngroup', 'malignmark', 'mlongdiv', 'mscarries', 'mscarry', 'msgroup', 'mstack', 'msline', 'msrow', 'semantics', 'annotation', 'annotation-xml', 'mprescripts', 'none']);
  const text = freeze(['#text']);

  const html = freeze(['accept', 'action', 'align', 'alt', 'autocapitalize', 'autocomplete', 'autopictureinpicture', 'autoplay', 'background', 'bgcolor', 'border', 'capture', 'cellpadding', 'cellspacing', 'checked', 'cite', 'class', 'clear', 'color', 'cols', 'colspan', 'controls', 'controlslist', 'coords', 'crossorigin', 'datetime', 'decoding', 'default', 'dir', 'disabled', 'disablepictureinpicture', 'disableremoteplayback', 'download', 'draggable', 'enctype', 'enterkeyhint', 'face', 'for', 'headers', 'height', 'hidden', 'high', 'href', 'hreflang', 'id', 'inputmode', 'integrity', 'ismap', 'kind', 'label', 'lang', 'list', 'loading', 'loop', 'low', 'max', 'maxlength', 'media', 'method', 'min', 'minlength', 'multiple', 'muted', 'name', 'nonce', 'noshade', 'novalidate', 'nowrap', 'open', 'optimum', 'pattern', 'placeholder', 'playsinline', 'poster', 'preload', 'pubdate', 'radiogroup', 'readonly', 'rel', 'required', 'rev', 'reversed', 'role', 'rows', 'rowspan', 'spellcheck', 'scope', 'selected', 'shape', 'size', 'sizes', 'span', 'srclang', 'start', 'src', 'srcset', 'step', 'style', 'summary', 'tabindex', 'title', 'translate', 'type', 'usemap', 'valign', 'value', 'width', 'xmlns', 'slot']);
  const svg = freeze(['accent-height', 'accumulate', 'additive', 'alignment-baseline', 'ascent', 'attributename', 'attributetype', 'azimuth', 'basefrequency', 'baseline-shift', 'begin', 'bias', 'by', 'class', 'clip', 'clippathunits', 'clip-path', 'clip-rule', 'color', 'color-interpolation', 'color-interpolation-filters', 'color-profile', 'color-rendering', 'cx', 'cy', 'd', 'dx', 'dy', 'diffuseconstant', 'direction', 'display', 'divisor', 'dur', 'edgemode', 'elevation', 'end', 'fill', 'fill-opacity', 'fill-rule', 'filter', 'filterunits', 'flood-color', 'flood-opacity', 'font-family', 'font-size', 'font-size-adjust', 'font-stretch', 'font-style', 'font-variant', 'font-weight', 'fx', 'fy', 'g1', 'g2', 'glyph-name', 'glyphref', 'gradientunits', 'gradienttransform', 'height', 'href', 'id', 'image-rendering', 'in', 'in2', 'k', 'k1', 'k2', 'k3', 'k4', 'kerning', 'keypoints', 'keysplines', 'keytimes', 'lang', 'lengthadjust', 'letter-spacing', 'kernelmatrix', 'kernelunitlength', 'lighting-color', 'local', 'marker-end', 'marker-mid', 'marker-start', 'markerheight', 'markerunits', 'markerwidth', 'maskcontentunits', 'maskunits', 'max', 'mask', 'media', 'method', 'mode', 'min', 'name', 'numoctaves', 'offset', 'operator', 'opacity', 'order', 'orient', 'orientation', 'origin', 'overflow', 'paint-order', 'path', 'pathlength', 'patterncontentunits', 'patterntransform', 'patternunits', 'points', 'preservealpha', 'preserveaspectratio', 'primitiveunits', 'r', 'rx', 'ry', 'radius', 'refx', 'refy', 'repeatcount', 'repeatdur', 'restart', 'result', 'rotate', 'scale', 'seed', 'shape-rendering', 'specularconstant', 'specularexponent', 'spreadmethod', 'startoffset', 'stddeviation', 'stitchtiles', 'stop-color', 'stop-opacity', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-opacity', 'stroke', 'stroke-width', 'style', 'surfacescale', 'systemlanguage', 'tabindex', 'targetx', 'targety', 'transform', 'transform-origin', 'text-anchor', 'text-decoration', 'text-rendering', 'textlength', 'type', 'u1', 'u2', 'unicode', 'values', 'viewbox', 'visibility', 'version', 'vert-adv-y', 'vert-origin-x', 'vert-origin-y', 'width', 'word-spacing', 'wrap', 'writing-mode', 'xchannelselector', 'ychannelselector', 'x', 'x1', 'x2', 'xmlns', 'y', 'y1', 'y2', 'z', 'zoomandpan']);
  const mathMl = freeze(['accent', 'accentunder', 'align', 'bevelled', 'close', 'columnsalign', 'columnlines', 'columnspan', 'denomalign', 'depth', 'dir', 'display', 'displaystyle', 'encoding', 'fence', 'frame', 'height', 'href', 'id', 'largeop', 'length', 'linethickness', 'lspace', 'lquote', 'mathbackground', 'mathcolor', 'mathsize', 'mathvariant', 'maxsize', 'minsize', 'movablelimits', 'notation', 'numalign', 'open', 'rowalign', 'rowlines', 'rowspacing', 'rowspan', 'rspace', 'rquote', 'scriptlevel', 'scriptminsize', 'scriptsizemultiplier', 'selection', 'separator', 'separators', 'stretchy', 'subscriptshift', 'supscriptshift', 'symmetric', 'voffset', 'width', 'xmlns']);
  const xml = freeze(['xlink:href', 'xml:id', 'xlink:title', 'xml:space', 'xmlns:xlink']);

  const MUSTACHE_EXPR = seal(/\{\{[\w\W]*|[\w\W]*\}\}/gm); // Specify template detection regex for SAFE_FOR_TEMPLATES mode

  const ERB_EXPR = seal(/<%[\w\W]*|[\w\W]*%>/gm);
  const TMPLIT_EXPR = seal(/\${[\w\W]*}/gm);
  const DATA_ATTR = seal(/^data-[\-\w.\u00B7-\uFFFF]/); // eslint-disable-line no-useless-escape

  const ARIA_ATTR = seal(/^aria-[\-\w]+$/); // eslint-disable-line no-useless-escape

  const IS_ALLOWED_URI = seal(/^(?:(?:(?:f|ht)tps?|mailto|tel|callto|sms|cid|xmpp):|[^a-z]|[a-z+.\-]+(?:[^a-z+.\-:]|$))/i // eslint-disable-line no-useless-escape
  );
  const IS_SCRIPT_OR_DATA = seal(/^(?:\w+script|data):/i);
  const ATTR_WHITESPACE = seal(/[\u0000-\u0020\u00A0\u1680\u180E\u2000-\u2029\u205F\u3000]/g // eslint-disable-line no-control-regex
  );
  const DOCTYPE_NAME = seal(/^html$/i);

  var EXPRESSIONS = /*#__PURE__*/Object.freeze({
    __proto__: null,
    MUSTACHE_EXPR: MUSTACHE_EXPR,
    ERB_EXPR: ERB_EXPR,
    TMPLIT_EXPR: TMPLIT_EXPR,
    DATA_ATTR: DATA_ATTR,
    ARIA_ATTR: ARIA_ATTR,
    IS_ALLOWED_URI: IS_ALLOWED_URI,
    IS_SCRIPT_OR_DATA: IS_SCRIPT_OR_DATA,
    ATTR_WHITESPACE: ATTR_WHITESPACE,
    DOCTYPE_NAME: DOCTYPE_NAME
  });

  const getGlobal = () => typeof window === 'undefined' ? null : window;
  /**
   * Creates a no-op policy for internal use only.
   * Don't export this function outside this module!
   * @param {?TrustedTypePolicyFactory} trustedTypes The policy factory.
   * @param {Document} document The document object (to determine policy name suffix)
   * @return {?TrustedTypePolicy} The policy created (or null, if Trusted Types
   * are not supported).
   */


  const _createTrustedTypesPolicy = function _createTrustedTypesPolicy(trustedTypes, document) {
    if (typeof trustedTypes !== 'object' || typeof trustedTypes.createPolicy !== 'function') {
      return null;
    } // Allow the callers to control the unique policy name
    // by adding a data-tt-policy-suffix to the script element with the DOMPurify.
    // Policy creation with duplicate names throws in Trusted Types.


    let suffix = null;
    const ATTR_NAME = 'data-tt-policy-suffix';

    if (document.currentScript && document.currentScript.hasAttribute(ATTR_NAME)) {
      suffix = document.currentScript.getAttribute(ATTR_NAME);
    }

    const policyName = 'dompurify' + (suffix ? '#' + suffix : '');

    try {
      return trustedTypes.createPolicy(policyName, {
        createHTML(html) {
          return html;
        },

        createScriptURL(scriptUrl) {
          return scriptUrl;
        }

      });
    } catch (_) {
      // Policy creation failed (most likely another DOMPurify script has
      // already run). Skip creating the policy, as this will only cause errors
      // if TT are enforced.
      console.warn('TrustedTypes policy ' + policyName + ' could not be created.');
      return null;
    }
  };

  function createDOMPurify() {
    let window = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : getGlobal();

    const DOMPurify = root => createDOMPurify(root);
    /**
     * Version label, exposed for easier checks
     * if DOMPurify is up to date or not
     */


    DOMPurify.version = '3.0.2';
    /**
     * Array of elements that DOMPurify removed during sanitation.
     * Empty if nothing was removed.
     */

    DOMPurify.removed = [];

    if (!window || !window.document || window.document.nodeType !== 9) {
      // Not running in a browser, provide a factory function
      // so that you can pass your own Window
      DOMPurify.isSupported = false;
      return DOMPurify;
    }

    const originalDocument = window.document;
    let {
      document
    } = window;
    const {
      DocumentFragment,
      HTMLTemplateElement,
      Node,
      Element,
      NodeFilter,
      NamedNodeMap = window.NamedNodeMap || window.MozNamedAttrMap,
      HTMLFormElement,
      DOMParser,
      trustedTypes
    } = window;
    const ElementPrototype = Element.prototype;
    const cloneNode = lookupGetter(ElementPrototype, 'cloneNode');
    const getNextSibling = lookupGetter(ElementPrototype, 'nextSibling');
    const getChildNodes = lookupGetter(ElementPrototype, 'childNodes');
    const getParentNode = lookupGetter(ElementPrototype, 'parentNode'); // As per issue #47, the web-components registry is inherited by a
    // new document created via createHTMLDocument. As per the spec
    // (http://w3c.github.io/webcomponents/spec/custom/#creating-and-passing-registries)
    // a new empty registry is used when creating a template contents owner
    // document, so we use that as our parent document to ensure nothing
    // is inherited.

    if (typeof HTMLTemplateElement === 'function') {
      const template = document.createElement('template');

      if (template.content && template.content.ownerDocument) {
        document = template.content.ownerDocument;
      }
    }

    const trustedTypesPolicy = _createTrustedTypesPolicy(trustedTypes, originalDocument);

    const emptyHTML = trustedTypesPolicy ? trustedTypesPolicy.createHTML('') : '';
    const {
      implementation,
      createNodeIterator,
      createDocumentFragment,
      getElementsByTagName
    } = document;
    const {
      importNode
    } = originalDocument;
    let hooks = {};
    /**
     * Expose whether this browser supports running the full DOMPurify.
     */

    DOMPurify.isSupported = typeof entries === 'function' && typeof getParentNode === 'function' && implementation && typeof implementation.createHTMLDocument !== 'undefined';
    const {
      MUSTACHE_EXPR,
      ERB_EXPR,
      TMPLIT_EXPR,
      DATA_ATTR,
      ARIA_ATTR,
      IS_SCRIPT_OR_DATA,
      ATTR_WHITESPACE
    } = EXPRESSIONS;
    let {
      IS_ALLOWED_URI: IS_ALLOWED_URI$1
    } = EXPRESSIONS;
    /**
     * We consider the elements and attributes below to be safe. Ideally
     * don't add any new ones but feel free to remove unwanted ones.
     */

    /* allowed element names */

    let ALLOWED_TAGS = null;
    const DEFAULT_ALLOWED_TAGS = addToSet({}, [...html$1, ...svg$1, ...svgFilters, ...mathMl$1, ...text]);
    /* Allowed attribute names */

    let ALLOWED_ATTR = null;
    const DEFAULT_ALLOWED_ATTR = addToSet({}, [...html, ...svg, ...mathMl, ...xml]);
    /*
     * Configure how DOMPUrify should handle custom elements and their attributes as well as customized built-in elements.
     * @property {RegExp|Function|null} tagNameCheck one of [null, regexPattern, predicate]. Default: `null` (disallow any custom elements)
     * @property {RegExp|Function|null} attributeNameCheck one of [null, regexPattern, predicate]. Default: `null` (disallow any attributes not on the allow list)
     * @property {boolean} allowCustomizedBuiltInElements allow custom elements derived from built-ins if they pass CUSTOM_ELEMENT_HANDLING.tagNameCheck. Default: `false`.
     */

    let CUSTOM_ELEMENT_HANDLING = Object.seal(Object.create(null, {
      tagNameCheck: {
        writable: true,
        configurable: false,
        enumerable: true,
        value: null
      },
      attributeNameCheck: {
        writable: true,
        configurable: false,
        enumerable: true,
        value: null
      },
      allowCustomizedBuiltInElements: {
        writable: true,
        configurable: false,
        enumerable: true,
        value: false
      }
    }));
    /* Explicitly forbidden tags (overrides ALLOWED_TAGS/ADD_TAGS) */

    let FORBID_TAGS = null;
    /* Explicitly forbidden attributes (overrides ALLOWED_ATTR/ADD_ATTR) */

    let FORBID_ATTR = null;
    /* Decide if ARIA attributes are okay */

    let ALLOW_ARIA_ATTR = true;
    /* Decide if custom data attributes are okay */

    let ALLOW_DATA_ATTR = true;
    /* Decide if unknown protocols are okay */

    let ALLOW_UNKNOWN_PROTOCOLS = false;
    /* Decide if self-closing tags in attributes are allowed.
     * Usually removed due to a mXSS issue in jQuery 3.0 */

    let ALLOW_SELF_CLOSE_IN_ATTR = true;
    /* Output should be safe for common template engines.
     * This means, DOMPurify removes data attributes, mustaches and ERB
     */

    let SAFE_FOR_TEMPLATES = false;
    /* Decide if document with <html>... should be returned */

    let WHOLE_DOCUMENT = false;
    /* Track whether config is already set on this instance of DOMPurify. */

    let SET_CONFIG = false;
    /* Decide if all elements (e.g. style, script) must be children of
     * document.body. By default, browsers might move them to document.head */

    let FORCE_BODY = false;
    /* Decide if a DOM `HTMLBodyElement` should be returned, instead of a html
     * string (or a TrustedHTML object if Trusted Types are supported).
     * If `WHOLE_DOCUMENT` is enabled a `HTMLHtmlElement` will be returned instead
     */

    let RETURN_DOM = false;
    /* Decide if a DOM `DocumentFragment` should be returned, instead of a html
     * string  (or a TrustedHTML object if Trusted Types are supported) */

    let RETURN_DOM_FRAGMENT = false;
    /* Try to return a Trusted Type object instead of a string, return a string in
     * case Trusted Types are not supported  */

    let RETURN_TRUSTED_TYPE = false;
    /* Output should be free from DOM clobbering attacks?
     * This sanitizes markups named with colliding, clobberable built-in DOM APIs.
     */

    let SANITIZE_DOM = true;
    /* Achieve full DOM Clobbering protection by isolating the namespace of named
     * properties and JS variables, mitigating attacks that abuse the HTML/DOM spec rules.
     *
     * HTML/DOM spec rules that enable DOM Clobbering:
     *   - Named Access on Window (§7.3.3)
     *   - DOM Tree Accessors (§3.1.5)
     *   - Form Element Parent-Child Relations (§4.10.3)
     *   - Iframe srcdoc / Nested WindowProxies (§4.8.5)
     *   - HTMLCollection (§4.2.10.2)
     *
     * Namespace isolation is implemented by prefixing `id` and `name` attributes
     * with a constant string, i.e., `user-content-`
     */

    let SANITIZE_NAMED_PROPS = false;
    const SANITIZE_NAMED_PROPS_PREFIX = 'user-content-';
    /* Keep element content when removing element? */

    let KEEP_CONTENT = true;
    /* If a `Node` is passed to sanitize(), then performs sanitization in-place instead
     * of importing it into a new Document and returning a sanitized copy */

    let IN_PLACE = false;
    /* Allow usage of profiles like html, svg and mathMl */

    let USE_PROFILES = {};
    /* Tags to ignore content of when KEEP_CONTENT is true */

    let FORBID_CONTENTS = null;
    const DEFAULT_FORBID_CONTENTS = addToSet({}, ['annotation-xml', 'audio', 'colgroup', 'desc', 'foreignobject', 'head', 'iframe', 'math', 'mi', 'mn', 'mo', 'ms', 'mtext', 'noembed', 'noframes', 'noscript', 'plaintext', 'script', 'style', 'svg', 'template', 'thead', 'title', 'video', 'xmp']);
    /* Tags that are safe for data: URIs */

    let DATA_URI_TAGS = null;
    const DEFAULT_DATA_URI_TAGS = addToSet({}, ['audio', 'video', 'img', 'source', 'image', 'track']);
    /* Attributes safe for values like "javascript:" */

    let URI_SAFE_ATTRIBUTES = null;
    const DEFAULT_URI_SAFE_ATTRIBUTES = addToSet({}, ['alt', 'class', 'for', 'id', 'label', 'name', 'pattern', 'placeholder', 'role', 'summary', 'title', 'value', 'style', 'xmlns']);
    const MATHML_NAMESPACE = 'http://www.w3.org/1998/Math/MathML';
    const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';
    const HTML_NAMESPACE = 'http://www.w3.org/1999/xhtml';
    /* Document namespace */

    let NAMESPACE = HTML_NAMESPACE;
    let IS_EMPTY_INPUT = false;
    /* Allowed XHTML+XML namespaces */

    let ALLOWED_NAMESPACES = null;
    const DEFAULT_ALLOWED_NAMESPACES = addToSet({}, [MATHML_NAMESPACE, SVG_NAMESPACE, HTML_NAMESPACE], stringToString);
    /* Parsing of strict XHTML documents */

    let PARSER_MEDIA_TYPE;
    const SUPPORTED_PARSER_MEDIA_TYPES = ['application/xhtml+xml', 'text/html'];
    const DEFAULT_PARSER_MEDIA_TYPE = 'text/html';
    let transformCaseFunc;
    /* Keep a reference to config to pass to hooks */

    let CONFIG = null;
    /* Ideally, do not touch anything below this line */

    /* ______________________________________________ */

    const formElement = document.createElement('form');

    const isRegexOrFunction = function isRegexOrFunction(testValue) {
      return testValue instanceof RegExp || testValue instanceof Function;
    };
    /**
     * _parseConfig
     *
     * @param  {Object} cfg optional config literal
     */
    // eslint-disable-next-line complexity


    const _parseConfig = function _parseConfig(cfg) {
      if (CONFIG && CONFIG === cfg) {
        return;
      }
      /* Shield configuration object from tampering */


      if (!cfg || typeof cfg !== 'object') {
        cfg = {};
      }
      /* Shield configuration object from prototype pollution */


      cfg = clone(cfg);
      PARSER_MEDIA_TYPE = // eslint-disable-next-line unicorn/prefer-includes
      SUPPORTED_PARSER_MEDIA_TYPES.indexOf(cfg.PARSER_MEDIA_TYPE) === -1 ? PARSER_MEDIA_TYPE = DEFAULT_PARSER_MEDIA_TYPE : PARSER_MEDIA_TYPE = cfg.PARSER_MEDIA_TYPE; // HTML tags and attributes are not case-sensitive, converting to lowercase. Keeping XHTML as is.

      transformCaseFunc = PARSER_MEDIA_TYPE === 'application/xhtml+xml' ? stringToString : stringToLowerCase;
      /* Set configuration parameters */

      ALLOWED_TAGS = 'ALLOWED_TAGS' in cfg ? addToSet({}, cfg.ALLOWED_TAGS, transformCaseFunc) : DEFAULT_ALLOWED_TAGS;
      ALLOWED_ATTR = 'ALLOWED_ATTR' in cfg ? addToSet({}, cfg.ALLOWED_ATTR, transformCaseFunc) : DEFAULT_ALLOWED_ATTR;
      ALLOWED_NAMESPACES = 'ALLOWED_NAMESPACES' in cfg ? addToSet({}, cfg.ALLOWED_NAMESPACES, stringToString) : DEFAULT_ALLOWED_NAMESPACES;
      URI_SAFE_ATTRIBUTES = 'ADD_URI_SAFE_ATTR' in cfg ? addToSet(clone(DEFAULT_URI_SAFE_ATTRIBUTES), // eslint-disable-line indent
      cfg.ADD_URI_SAFE_ATTR, // eslint-disable-line indent
      transformCaseFunc // eslint-disable-line indent
      ) // eslint-disable-line indent
      : DEFAULT_URI_SAFE_ATTRIBUTES;
      DATA_URI_TAGS = 'ADD_DATA_URI_TAGS' in cfg ? addToSet(clone(DEFAULT_DATA_URI_TAGS), // eslint-disable-line indent
      cfg.ADD_DATA_URI_TAGS, // eslint-disable-line indent
      transformCaseFunc // eslint-disable-line indent
      ) // eslint-disable-line indent
      : DEFAULT_DATA_URI_TAGS;
      FORBID_CONTENTS = 'FORBID_CONTENTS' in cfg ? addToSet({}, cfg.FORBID_CONTENTS, transformCaseFunc) : DEFAULT_FORBID_CONTENTS;
      FORBID_TAGS = 'FORBID_TAGS' in cfg ? addToSet({}, cfg.FORBID_TAGS, transformCaseFunc) : {};
      FORBID_ATTR = 'FORBID_ATTR' in cfg ? addToSet({}, cfg.FORBID_ATTR, transformCaseFunc) : {};
      USE_PROFILES = 'USE_PROFILES' in cfg ? cfg.USE_PROFILES : false;
      ALLOW_ARIA_ATTR = cfg.ALLOW_ARIA_ATTR !== false; // Default true

      ALLOW_DATA_ATTR = cfg.ALLOW_DATA_ATTR !== false; // Default true

      ALLOW_UNKNOWN_PROTOCOLS = cfg.ALLOW_UNKNOWN_PROTOCOLS || false; // Default false

      ALLOW_SELF_CLOSE_IN_ATTR = cfg.ALLOW_SELF_CLOSE_IN_ATTR !== false; // Default true

      SAFE_FOR_TEMPLATES = cfg.SAFE_FOR_TEMPLATES || false; // Default false

      WHOLE_DOCUMENT = cfg.WHOLE_DOCUMENT || false; // Default false

      RETURN_DOM = cfg.RETURN_DOM || false; // Default false

      RETURN_DOM_FRAGMENT = cfg.RETURN_DOM_FRAGMENT || false; // Default false

      RETURN_TRUSTED_TYPE = cfg.RETURN_TRUSTED_TYPE || false; // Default false

      FORCE_BODY = cfg.FORCE_BODY || false; // Default false

      SANITIZE_DOM = cfg.SANITIZE_DOM !== false; // Default true

      SANITIZE_NAMED_PROPS = cfg.SANITIZE_NAMED_PROPS || false; // Default false

      KEEP_CONTENT = cfg.KEEP_CONTENT !== false; // Default true

      IN_PLACE = cfg.IN_PLACE || false; // Default false

      IS_ALLOWED_URI$1 = cfg.ALLOWED_URI_REGEXP || IS_ALLOWED_URI;
      NAMESPACE = cfg.NAMESPACE || HTML_NAMESPACE;
      CUSTOM_ELEMENT_HANDLING = cfg.CUSTOM_ELEMENT_HANDLING || {};

      if (cfg.CUSTOM_ELEMENT_HANDLING && isRegexOrFunction(cfg.CUSTOM_ELEMENT_HANDLING.tagNameCheck)) {
        CUSTOM_ELEMENT_HANDLING.tagNameCheck = cfg.CUSTOM_ELEMENT_HANDLING.tagNameCheck;
      }

      if (cfg.CUSTOM_ELEMENT_HANDLING && isRegexOrFunction(cfg.CUSTOM_ELEMENT_HANDLING.attributeNameCheck)) {
        CUSTOM_ELEMENT_HANDLING.attributeNameCheck = cfg.CUSTOM_ELEMENT_HANDLING.attributeNameCheck;
      }

      if (cfg.CUSTOM_ELEMENT_HANDLING && typeof cfg.CUSTOM_ELEMENT_HANDLING.allowCustomizedBuiltInElements === 'boolean') {
        CUSTOM_ELEMENT_HANDLING.allowCustomizedBuiltInElements = cfg.CUSTOM_ELEMENT_HANDLING.allowCustomizedBuiltInElements;
      }

      if (SAFE_FOR_TEMPLATES) {
        ALLOW_DATA_ATTR = false;
      }

      if (RETURN_DOM_FRAGMENT) {
        RETURN_DOM = true;
      }
      /* Parse profile info */


      if (USE_PROFILES) {
        ALLOWED_TAGS = addToSet({}, [...text]);
        ALLOWED_ATTR = [];

        if (USE_PROFILES.html === true) {
          addToSet(ALLOWED_TAGS, html$1);
          addToSet(ALLOWED_ATTR, html);
        }

        if (USE_PROFILES.svg === true) {
          addToSet(ALLOWED_TAGS, svg$1);
          addToSet(ALLOWED_ATTR, svg);
          addToSet(ALLOWED_ATTR, xml);
        }

        if (USE_PROFILES.svgFilters === true) {
          addToSet(ALLOWED_TAGS, svgFilters);
          addToSet(ALLOWED_ATTR, svg);
          addToSet(ALLOWED_ATTR, xml);
        }

        if (USE_PROFILES.mathMl === true) {
          addToSet(ALLOWED_TAGS, mathMl$1);
          addToSet(ALLOWED_ATTR, mathMl);
          addToSet(ALLOWED_ATTR, xml);
        }
      }
      /* Merge configuration parameters */


      if (cfg.ADD_TAGS) {
        if (ALLOWED_TAGS === DEFAULT_ALLOWED_TAGS) {
          ALLOWED_TAGS = clone(ALLOWED_TAGS);
        }

        addToSet(ALLOWED_TAGS, cfg.ADD_TAGS, transformCaseFunc);
      }

      if (cfg.ADD_ATTR) {
        if (ALLOWED_ATTR === DEFAULT_ALLOWED_ATTR) {
          ALLOWED_ATTR = clone(ALLOWED_ATTR);
        }

        addToSet(ALLOWED_ATTR, cfg.ADD_ATTR, transformCaseFunc);
      }

      if (cfg.ADD_URI_SAFE_ATTR) {
        addToSet(URI_SAFE_ATTRIBUTES, cfg.ADD_URI_SAFE_ATTR, transformCaseFunc);
      }

      if (cfg.FORBID_CONTENTS) {
        if (FORBID_CONTENTS === DEFAULT_FORBID_CONTENTS) {
          FORBID_CONTENTS = clone(FORBID_CONTENTS);
        }

        addToSet(FORBID_CONTENTS, cfg.FORBID_CONTENTS, transformCaseFunc);
      }
      /* Add #text in case KEEP_CONTENT is set to true */


      if (KEEP_CONTENT) {
        ALLOWED_TAGS['#text'] = true;
      }
      /* Add html, head and body to ALLOWED_TAGS in case WHOLE_DOCUMENT is true */


      if (WHOLE_DOCUMENT) {
        addToSet(ALLOWED_TAGS, ['html', 'head', 'body']);
      }
      /* Add tbody to ALLOWED_TAGS in case tables are permitted, see #286, #365 */


      if (ALLOWED_TAGS.table) {
        addToSet(ALLOWED_TAGS, ['tbody']);
        delete FORBID_TAGS.tbody;
      } // Prevent further manipulation of configuration.
      // Not available in IE8, Safari 5, etc.


      if (freeze) {
        freeze(cfg);
      }

      CONFIG = cfg;
    };

    const MATHML_TEXT_INTEGRATION_POINTS = addToSet({}, ['mi', 'mo', 'mn', 'ms', 'mtext']);
    const HTML_INTEGRATION_POINTS = addToSet({}, ['foreignobject', 'desc', 'title', 'annotation-xml']); // Certain elements are allowed in both SVG and HTML
    // namespace. We need to specify them explicitly
    // so that they don't get erroneously deleted from
    // HTML namespace.

    const COMMON_SVG_AND_HTML_ELEMENTS = addToSet({}, ['title', 'style', 'font', 'a', 'script']);
    /* Keep track of all possible SVG and MathML tags
     * so that we can perform the namespace checks
     * correctly. */

    const ALL_SVG_TAGS = addToSet({}, svg$1);
    addToSet(ALL_SVG_TAGS, svgFilters);
    addToSet(ALL_SVG_TAGS, svgDisallowed);
    const ALL_MATHML_TAGS = addToSet({}, mathMl$1);
    addToSet(ALL_MATHML_TAGS, mathMlDisallowed);
    /**
     *
     *
     * @param  {Element} element a DOM element whose namespace is being checked
     * @returns {boolean} Return false if the element has a
     *  namespace that a spec-compliant parser would never
     *  return. Return true otherwise.
     */

    const _checkValidNamespace = function _checkValidNamespace(element) {
      let parent = getParentNode(element); // In JSDOM, if we're inside shadow DOM, then parentNode
      // can be null. We just simulate parent in this case.

      if (!parent || !parent.tagName) {
        parent = {
          namespaceURI: NAMESPACE,
          tagName: 'template'
        };
      }

      const tagName = stringToLowerCase(element.tagName);
      const parentTagName = stringToLowerCase(parent.tagName);

      if (!ALLOWED_NAMESPACES[element.namespaceURI]) {
        return false;
      }

      if (element.namespaceURI === SVG_NAMESPACE) {
        // The only way to switch from HTML namespace to SVG
        // is via <svg>. If it happens via any other tag, then
        // it should be killed.
        if (parent.namespaceURI === HTML_NAMESPACE) {
          return tagName === 'svg';
        } // The only way to switch from MathML to SVG is via`
        // svg if parent is either <annotation-xml> or MathML
        // text integration points.


        if (parent.namespaceURI === MATHML_NAMESPACE) {
          return tagName === 'svg' && (parentTagName === 'annotation-xml' || MATHML_TEXT_INTEGRATION_POINTS[parentTagName]);
        } // We only allow elements that are defined in SVG
        // spec. All others are disallowed in SVG namespace.


        return Boolean(ALL_SVG_TAGS[tagName]);
      }

      if (element.namespaceURI === MATHML_NAMESPACE) {
        // The only way to switch from HTML namespace to MathML
        // is via <math>. If it happens via any other tag, then
        // it should be killed.
        if (parent.namespaceURI === HTML_NAMESPACE) {
          return tagName === 'math';
        } // The only way to switch from SVG to MathML is via
        // <math> and HTML integration points


        if (parent.namespaceURI === SVG_NAMESPACE) {
          return tagName === 'math' && HTML_INTEGRATION_POINTS[parentTagName];
        } // We only allow elements that are defined in MathML
        // spec. All others are disallowed in MathML namespace.


        return Boolean(ALL_MATHML_TAGS[tagName]);
      }

      if (element.namespaceURI === HTML_NAMESPACE) {
        // The only way to switch from SVG to HTML is via
        // HTML integration points, and from MathML to HTML
        // is via MathML text integration points
        if (parent.namespaceURI === SVG_NAMESPACE && !HTML_INTEGRATION_POINTS[parentTagName]) {
          return false;
        }

        if (parent.namespaceURI === MATHML_NAMESPACE && !MATHML_TEXT_INTEGRATION_POINTS[parentTagName]) {
          return false;
        } // We disallow tags that are specific for MathML
        // or SVG and should never appear in HTML namespace


        return !ALL_MATHML_TAGS[tagName] && (COMMON_SVG_AND_HTML_ELEMENTS[tagName] || !ALL_SVG_TAGS[tagName]);
      } // For XHTML and XML documents that support custom namespaces


      if (PARSER_MEDIA_TYPE === 'application/xhtml+xml' && ALLOWED_NAMESPACES[element.namespaceURI]) {
        return true;
      } // The code should never reach this place (this means
      // that the element somehow got namespace that is not
      // HTML, SVG, MathML or allowed via ALLOWED_NAMESPACES).
      // Return false just in case.


      return false;
    };
    /**
     * _forceRemove
     *
     * @param  {Node} node a DOM node
     */


    const _forceRemove = function _forceRemove(node) {
      arrayPush(DOMPurify.removed, {
        element: node
      });

      try {
        // eslint-disable-next-line unicorn/prefer-dom-node-remove
        node.parentNode.removeChild(node);
      } catch (_) {
        node.remove();
      }
    };
    /**
     * _removeAttribute
     *
     * @param  {String} name an Attribute name
     * @param  {Node} node a DOM node
     */


    const _removeAttribute = function _removeAttribute(name, node) {
      try {
        arrayPush(DOMPurify.removed, {
          attribute: node.getAttributeNode(name),
          from: node
        });
      } catch (_) {
        arrayPush(DOMPurify.removed, {
          attribute: null,
          from: node
        });
      }

      node.removeAttribute(name); // We void attribute values for unremovable "is"" attributes

      if (name === 'is' && !ALLOWED_ATTR[name]) {
        if (RETURN_DOM || RETURN_DOM_FRAGMENT) {
          try {
            _forceRemove(node);
          } catch (_) {}
        } else {
          try {
            node.setAttribute(name, '');
          } catch (_) {}
        }
      }
    };
    /**
     * _initDocument
     *
     * @param  {String} dirty a string of dirty markup
     * @return {Document} a DOM, filled with the dirty markup
     */


    const _initDocument = function _initDocument(dirty) {
      /* Create a HTML document */
      let doc;
      let leadingWhitespace;

      if (FORCE_BODY) {
        dirty = '<remove></remove>' + dirty;
      } else {
        /* If FORCE_BODY isn't used, leading whitespace needs to be preserved manually */
        const matches = stringMatch(dirty, /^[\r\n\t ]+/);
        leadingWhitespace = matches && matches[0];
      }

      if (PARSER_MEDIA_TYPE === 'application/xhtml+xml' && NAMESPACE === HTML_NAMESPACE) {
        // Root of XHTML doc must contain xmlns declaration (see https://www.w3.org/TR/xhtml1/normative.html#strict)
        dirty = '<html xmlns="http://www.w3.org/1999/xhtml"><head></head><body>' + dirty + '</body></html>';
      }

      const dirtyPayload = trustedTypesPolicy ? trustedTypesPolicy.createHTML(dirty) : dirty;
      /*
       * Use the DOMParser API by default, fallback later if needs be
       * DOMParser not work for svg when has multiple root element.
       */

      if (NAMESPACE === HTML_NAMESPACE) {
        try {
          doc = new DOMParser().parseFromString(dirtyPayload, PARSER_MEDIA_TYPE);
        } catch (_) {}
      }
      /* Use createHTMLDocument in case DOMParser is not available */


      if (!doc || !doc.documentElement) {
        doc = implementation.createDocument(NAMESPACE, 'template', null);

        try {
          doc.documentElement.innerHTML = IS_EMPTY_INPUT ? emptyHTML : dirtyPayload;
        } catch (_) {// Syntax error if dirtyPayload is invalid xml
        }
      }

      const body = doc.body || doc.documentElement;

      if (dirty && leadingWhitespace) {
        body.insertBefore(document.createTextNode(leadingWhitespace), body.childNodes[0] || null);
      }
      /* Work on whole document or just its body */


      if (NAMESPACE === HTML_NAMESPACE) {
        return getElementsByTagName.call(doc, WHOLE_DOCUMENT ? 'html' : 'body')[0];
      }

      return WHOLE_DOCUMENT ? doc.documentElement : body;
    };
    /**
     * _createIterator
     *
     * @param  {Document} root document/fragment to create iterator for
     * @return {Iterator} iterator instance
     */


    const _createIterator = function _createIterator(root) {
      return createNodeIterator.call(root.ownerDocument || root, root, // eslint-disable-next-line no-bitwise
      NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_COMMENT | NodeFilter.SHOW_TEXT, null, false);
    };
    /**
     * _isClobbered
     *
     * @param  {Node} elm element to check for clobbering attacks
     * @return {Boolean} true if clobbered, false if safe
     */


    const _isClobbered = function _isClobbered(elm) {
      return elm instanceof HTMLFormElement && (typeof elm.nodeName !== 'string' || typeof elm.textContent !== 'string' || typeof elm.removeChild !== 'function' || !(elm.attributes instanceof NamedNodeMap) || typeof elm.removeAttribute !== 'function' || typeof elm.setAttribute !== 'function' || typeof elm.namespaceURI !== 'string' || typeof elm.insertBefore !== 'function' || typeof elm.hasChildNodes !== 'function');
    };
    /**
     * _isNode
     *
     * @param  {Node} obj object to check whether it's a DOM node
     * @return {Boolean} true is object is a DOM node
     */


    const _isNode = function _isNode(object) {
      return typeof Node === 'object' ? object instanceof Node : object && typeof object === 'object' && typeof object.nodeType === 'number' && typeof object.nodeName === 'string';
    };
    /**
     * _executeHook
     * Execute user configurable hooks
     *
     * @param  {String} entryPoint  Name of the hook's entry point
     * @param  {Node} currentNode node to work on with the hook
     * @param  {Object} data additional hook parameters
     */


    const _executeHook = function _executeHook(entryPoint, currentNode, data) {
      if (!hooks[entryPoint]) {
        return;
      }

      arrayForEach(hooks[entryPoint], hook => {
        hook.call(DOMPurify, currentNode, data, CONFIG);
      });
    };
    /**
     * _sanitizeElements
     *
     * @protect nodeName
     * @protect textContent
     * @protect removeChild
     *
     * @param   {Node} currentNode to check for permission to exist
     * @return  {Boolean} true if node was killed, false if left alive
     */


    const _sanitizeElements = function _sanitizeElements(currentNode) {
      let content;
      /* Execute a hook if present */

      _executeHook('beforeSanitizeElements', currentNode, null);
      /* Check if element is clobbered or can clobber */


      if (_isClobbered(currentNode)) {
        _forceRemove(currentNode);

        return true;
      }
      /* Now let's check the element's type and name */


      const tagName = transformCaseFunc(currentNode.nodeName);
      /* Execute a hook if present */

      _executeHook('uponSanitizeElement', currentNode, {
        tagName,
        allowedTags: ALLOWED_TAGS
      });
      /* Detect mXSS attempts abusing namespace confusion */


      if (currentNode.hasChildNodes() && !_isNode(currentNode.firstElementChild) && (!_isNode(currentNode.content) || !_isNode(currentNode.content.firstElementChild)) && regExpTest(/<[/\w]/g, currentNode.innerHTML) && regExpTest(/<[/\w]/g, currentNode.textContent)) {
        _forceRemove(currentNode);

        return true;
      }
      /* Remove element if anything forbids its presence */


      if (!ALLOWED_TAGS[tagName] || FORBID_TAGS[tagName]) {
        /* Check if we have a custom element to handle */
        if (!FORBID_TAGS[tagName] && _basicCustomElementTest(tagName)) {
          if (CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof RegExp && regExpTest(CUSTOM_ELEMENT_HANDLING.tagNameCheck, tagName)) return false;
          if (CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof Function && CUSTOM_ELEMENT_HANDLING.tagNameCheck(tagName)) return false;
        }
        /* Keep content except for bad-listed elements */


        if (KEEP_CONTENT && !FORBID_CONTENTS[tagName]) {
          const parentNode = getParentNode(currentNode) || currentNode.parentNode;
          const childNodes = getChildNodes(currentNode) || currentNode.childNodes;

          if (childNodes && parentNode) {
            const childCount = childNodes.length;

            for (let i = childCount - 1; i >= 0; --i) {
              parentNode.insertBefore(cloneNode(childNodes[i], true), getNextSibling(currentNode));
            }
          }
        }

        _forceRemove(currentNode);

        return true;
      }
      /* Check whether element has a valid namespace */


      if (currentNode instanceof Element && !_checkValidNamespace(currentNode)) {
        _forceRemove(currentNode);

        return true;
      }
      /* Make sure that older browsers don't get noscript mXSS */


      if ((tagName === 'noscript' || tagName === 'noembed') && regExpTest(/<\/no(script|embed)/i, currentNode.innerHTML)) {
        _forceRemove(currentNode);

        return true;
      }
      /* Sanitize element content to be template-safe */


      if (SAFE_FOR_TEMPLATES && currentNode.nodeType === 3) {
        /* Get the element's text content */
        content = currentNode.textContent;
        content = stringReplace(content, MUSTACHE_EXPR, ' ');
        content = stringReplace(content, ERB_EXPR, ' ');
        content = stringReplace(content, TMPLIT_EXPR, ' ');

        if (currentNode.textContent !== content) {
          arrayPush(DOMPurify.removed, {
            element: currentNode.cloneNode()
          });
          currentNode.textContent = content;
        }
      }
      /* Execute a hook if present */


      _executeHook('afterSanitizeElements', currentNode, null);

      return false;
    };
    /**
     * _isValidAttribute
     *
     * @param  {string} lcTag Lowercase tag name of containing element.
     * @param  {string} lcName Lowercase attribute name.
     * @param  {string} value Attribute value.
     * @return {Boolean} Returns true if `value` is valid, otherwise false.
     */
    // eslint-disable-next-line complexity


    const _isValidAttribute = function _isValidAttribute(lcTag, lcName, value) {
      /* Make sure attribute cannot clobber */
      if (SANITIZE_DOM && (lcName === 'id' || lcName === 'name') && (value in document || value in formElement)) {
        return false;
      }
      /* Allow valid data-* attributes: At least one character after "-"
          (https://html.spec.whatwg.org/multipage/dom.html#embedding-custom-non-visible-data-with-the-data-*-attributes)
          XML-compatible (https://html.spec.whatwg.org/multipage/infrastructure.html#xml-compatible and http://www.w3.org/TR/xml/#d0e804)
          We don't need to check the value; it's always URI safe. */


      if (ALLOW_DATA_ATTR && !FORBID_ATTR[lcName] && regExpTest(DATA_ATTR, lcName)) ; else if (ALLOW_ARIA_ATTR && regExpTest(ARIA_ATTR, lcName)) ; else if (!ALLOWED_ATTR[lcName] || FORBID_ATTR[lcName]) {
        if ( // First condition does a very basic check if a) it's basically a valid custom element tagname AND
        // b) if the tagName passes whatever the user has configured for CUSTOM_ELEMENT_HANDLING.tagNameCheck
        // and c) if the attribute name passes whatever the user has configured for CUSTOM_ELEMENT_HANDLING.attributeNameCheck
        _basicCustomElementTest(lcTag) && (CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof RegExp && regExpTest(CUSTOM_ELEMENT_HANDLING.tagNameCheck, lcTag) || CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof Function && CUSTOM_ELEMENT_HANDLING.tagNameCheck(lcTag)) && (CUSTOM_ELEMENT_HANDLING.attributeNameCheck instanceof RegExp && regExpTest(CUSTOM_ELEMENT_HANDLING.attributeNameCheck, lcName) || CUSTOM_ELEMENT_HANDLING.attributeNameCheck instanceof Function && CUSTOM_ELEMENT_HANDLING.attributeNameCheck(lcName)) || // Alternative, second condition checks if it's an `is`-attribute, AND
        // the value passes whatever the user has configured for CUSTOM_ELEMENT_HANDLING.tagNameCheck
        lcName === 'is' && CUSTOM_ELEMENT_HANDLING.allowCustomizedBuiltInElements && (CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof RegExp && regExpTest(CUSTOM_ELEMENT_HANDLING.tagNameCheck, value) || CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof Function && CUSTOM_ELEMENT_HANDLING.tagNameCheck(value))) ; else {
          return false;
        }
        /* Check value is safe. First, is attr inert? If so, is safe */

      } else if (URI_SAFE_ATTRIBUTES[lcName]) ; else if (regExpTest(IS_ALLOWED_URI$1, stringReplace(value, ATTR_WHITESPACE, ''))) ; else if ((lcName === 'src' || lcName === 'xlink:href' || lcName === 'href') && lcTag !== 'script' && stringIndexOf(value, 'data:') === 0 && DATA_URI_TAGS[lcTag]) ; else if (ALLOW_UNKNOWN_PROTOCOLS && !regExpTest(IS_SCRIPT_OR_DATA, stringReplace(value, ATTR_WHITESPACE, ''))) ; else if (!value) ; else {
        return false;
      }

      return true;
    };
    /**
     * _basicCustomElementCheck
     * checks if at least one dash is included in tagName, and it's not the first char
     * for more sophisticated checking see https://github.com/sindresorhus/validate-element-name
     * @param {string} tagName name of the tag of the node to sanitize
     */


    const _basicCustomElementTest = function _basicCustomElementTest(tagName) {
      return tagName.indexOf('-') > 0;
    };
    /**
     * _sanitizeAttributes
     *
     * @protect attributes
     * @protect nodeName
     * @protect removeAttribute
     * @protect setAttribute
     *
     * @param  {Node} currentNode to sanitize
     */


    const _sanitizeAttributes = function _sanitizeAttributes(currentNode) {
      let attr;
      let value;
      let lcName;
      let l;
      /* Execute a hook if present */

      _executeHook('beforeSanitizeAttributes', currentNode, null);

      const {
        attributes
      } = currentNode;
      /* Check if we have attributes; if not we might have a text node */

      if (!attributes) {
        return;
      }

      const hookEvent = {
        attrName: '',
        attrValue: '',
        keepAttr: true,
        allowedAttributes: ALLOWED_ATTR
      };
      l = attributes.length;
      /* Go backwards over all attributes; safely remove bad ones */

      while (l--) {
        attr = attributes[l];
        const {
          name,
          namespaceURI
        } = attr;
        value = name === 'value' ? attr.value : stringTrim(attr.value);
        lcName = transformCaseFunc(name);
        /* Execute a hook if present */

        hookEvent.attrName = lcName;
        hookEvent.attrValue = value;
        hookEvent.keepAttr = true;
        hookEvent.forceKeepAttr = undefined; // Allows developers to see this is a property they can set

        _executeHook('uponSanitizeAttribute', currentNode, hookEvent);

        value = hookEvent.attrValue;
        /* Did the hooks approve of the attribute? */

        if (hookEvent.forceKeepAttr) {
          continue;
        }
        /* Remove attribute */


        _removeAttribute(name, currentNode);
        /* Did the hooks approve of the attribute? */


        if (!hookEvent.keepAttr) {
          continue;
        }
        /* Work around a security issue in jQuery 3.0 */


        if (!ALLOW_SELF_CLOSE_IN_ATTR && regExpTest(/\/>/i, value)) {
          _removeAttribute(name, currentNode);

          continue;
        }
        /* Sanitize attribute content to be template-safe */


        if (SAFE_FOR_TEMPLATES) {
          value = stringReplace(value, MUSTACHE_EXPR, ' ');
          value = stringReplace(value, ERB_EXPR, ' ');
          value = stringReplace(value, TMPLIT_EXPR, ' ');
        }
        /* Is `value` valid for this attribute? */


        const lcTag = transformCaseFunc(currentNode.nodeName);

        if (!_isValidAttribute(lcTag, lcName, value)) {
          continue;
        }
        /* Full DOM Clobbering protection via namespace isolation,
         * Prefix id and name attributes with `user-content-`
         */


        if (SANITIZE_NAMED_PROPS && (lcName === 'id' || lcName === 'name')) {
          // Remove the attribute with this value
          _removeAttribute(name, currentNode); // Prefix the value and later re-create the attribute with the sanitized value


          value = SANITIZE_NAMED_PROPS_PREFIX + value;
        }
        /* Handle attributes that require Trusted Types */


        if (trustedTypesPolicy && typeof trustedTypes === 'object' && typeof trustedTypes.getAttributeType === 'function') {
          if (namespaceURI) ; else {
            switch (trustedTypes.getAttributeType(lcTag, lcName)) {
              case 'TrustedHTML':
                value = trustedTypesPolicy.createHTML(value);
                break;

              case 'TrustedScriptURL':
                value = trustedTypesPolicy.createScriptURL(value);
                break;
            }
          }
        }
        /* Handle invalid data-* attribute set by try-catching it */


        try {
          if (namespaceURI) {
            currentNode.setAttributeNS(namespaceURI, name, value);
          } else {
            /* Fallback to setAttribute() for browser-unrecognized namespaces e.g. "x-schema". */
            currentNode.setAttribute(name, value);
          }

          arrayPop(DOMPurify.removed);
        } catch (_) {}
      }
      /* Execute a hook if present */


      _executeHook('afterSanitizeAttributes', currentNode, null);
    };
    /**
     * _sanitizeShadowDOM
     *
     * @param  {DocumentFragment} fragment to iterate over recursively
     */


    const _sanitizeShadowDOM = function _sanitizeShadowDOM(fragment) {
      let shadowNode;

      const shadowIterator = _createIterator(fragment);
      /* Execute a hook if present */


      _executeHook('beforeSanitizeShadowDOM', fragment, null);

      while (shadowNode = shadowIterator.nextNode()) {
        /* Execute a hook if present */
        _executeHook('uponSanitizeShadowNode', shadowNode, null);
        /* Sanitize tags and elements */


        if (_sanitizeElements(shadowNode)) {
          continue;
        }
        /* Deep shadow DOM detected */


        if (shadowNode.content instanceof DocumentFragment) {
          _sanitizeShadowDOM(shadowNode.content);
        }
        /* Check attributes, sanitize if necessary */


        _sanitizeAttributes(shadowNode);
      }
      /* Execute a hook if present */


      _executeHook('afterSanitizeShadowDOM', fragment, null);
    };
    /**
     * Sanitize
     * Public method providing core sanitation functionality
     *
     * @param {String|Node} dirty string or DOM node
     * @param {Object} configuration object
     */
    // eslint-disable-next-line complexity


    DOMPurify.sanitize = function (dirty) {
      let cfg = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      let body;
      let importedNode;
      let currentNode;
      let returnNode;
      /* Make sure we have a string to sanitize.
        DO NOT return early, as this will return the wrong type if
        the user has requested a DOM object rather than a string */

      IS_EMPTY_INPUT = !dirty;

      if (IS_EMPTY_INPUT) {
        dirty = '<!-->';
      }
      /* Stringify, in case dirty is an object */


      if (typeof dirty !== 'string' && !_isNode(dirty)) {
        // eslint-disable-next-line no-negated-condition
        if (typeof dirty.toString !== 'function') {
          throw typeErrorCreate('toString is not a function');
        } else {
          dirty = dirty.toString();

          if (typeof dirty !== 'string') {
            throw typeErrorCreate('dirty is not a string, aborting');
          }
        }
      }
      /* Return dirty HTML if DOMPurify cannot run */


      if (!DOMPurify.isSupported) {
        return dirty;
      }
      /* Assign config vars */


      if (!SET_CONFIG) {
        _parseConfig(cfg);
      }
      /* Clean up removed elements */


      DOMPurify.removed = [];
      /* Check if dirty is correctly typed for IN_PLACE */

      if (typeof dirty === 'string') {
        IN_PLACE = false;
      }

      if (IN_PLACE) {
        /* Do some early pre-sanitization to avoid unsafe root nodes */
        if (dirty.nodeName) {
          const tagName = transformCaseFunc(dirty.nodeName);

          if (!ALLOWED_TAGS[tagName] || FORBID_TAGS[tagName]) {
            throw typeErrorCreate('root node is forbidden and cannot be sanitized in-place');
          }
        }
      } else if (dirty instanceof Node) {
        /* If dirty is a DOM element, append to an empty document to avoid
           elements being stripped by the parser */
        body = _initDocument('<!---->');
        importedNode = body.ownerDocument.importNode(dirty, true);

        if (importedNode.nodeType === 1 && importedNode.nodeName === 'BODY') {
          /* Node is already a body, use as is */
          body = importedNode;
        } else if (importedNode.nodeName === 'HTML') {
          body = importedNode;
        } else {
          // eslint-disable-next-line unicorn/prefer-dom-node-append
          body.appendChild(importedNode);
        }
      } else {
        /* Exit directly if we have nothing to do */
        if (!RETURN_DOM && !SAFE_FOR_TEMPLATES && !WHOLE_DOCUMENT && // eslint-disable-next-line unicorn/prefer-includes
        dirty.indexOf('<') === -1) {
          return trustedTypesPolicy && RETURN_TRUSTED_TYPE ? trustedTypesPolicy.createHTML(dirty) : dirty;
        }
        /* Initialize the document to work on */


        body = _initDocument(dirty);
        /* Check we have a DOM node from the data */

        if (!body) {
          return RETURN_DOM ? null : RETURN_TRUSTED_TYPE ? emptyHTML : '';
        }
      }
      /* Remove first element node (ours) if FORCE_BODY is set */


      if (body && FORCE_BODY) {
        _forceRemove(body.firstChild);
      }
      /* Get node iterator */


      const nodeIterator = _createIterator(IN_PLACE ? dirty : body);
      /* Now start iterating over the created document */


      while (currentNode = nodeIterator.nextNode()) {
        /* Sanitize tags and elements */
        if (_sanitizeElements(currentNode)) {
          continue;
        }
        /* Shadow DOM detected, sanitize it */


        if (currentNode.content instanceof DocumentFragment) {
          _sanitizeShadowDOM(currentNode.content);
        }
        /* Check attributes, sanitize if necessary */


        _sanitizeAttributes(currentNode);
      }
      /* If we sanitized `dirty` in-place, return it. */


      if (IN_PLACE) {
        return dirty;
      }
      /* Return sanitized string or DOM */


      if (RETURN_DOM) {
        if (RETURN_DOM_FRAGMENT) {
          returnNode = createDocumentFragment.call(body.ownerDocument);

          while (body.firstChild) {
            // eslint-disable-next-line unicorn/prefer-dom-node-append
            returnNode.appendChild(body.firstChild);
          }
        } else {
          returnNode = body;
        }

        if (ALLOWED_ATTR.shadowroot || ALLOWED_ATTR.shadowrootmod) {
          /*
            AdoptNode() is not used because internal state is not reset
            (e.g. the past names map of a HTMLFormElement), this is safe
            in theory but we would rather not risk another attack vector.
            The state that is cloned by importNode() is explicitly defined
            by the specs.
          */
          returnNode = importNode.call(originalDocument, returnNode, true);
        }

        return returnNode;
      }

      let serializedHTML = WHOLE_DOCUMENT ? body.outerHTML : body.innerHTML;
      /* Serialize doctype if allowed */

      if (WHOLE_DOCUMENT && ALLOWED_TAGS['!doctype'] && body.ownerDocument && body.ownerDocument.doctype && body.ownerDocument.doctype.name && regExpTest(DOCTYPE_NAME, body.ownerDocument.doctype.name)) {
        serializedHTML = '<!DOCTYPE ' + body.ownerDocument.doctype.name + '>\n' + serializedHTML;
      }
      /* Sanitize final string template-safe */


      if (SAFE_FOR_TEMPLATES) {
        serializedHTML = stringReplace(serializedHTML, MUSTACHE_EXPR, ' ');
        serializedHTML = stringReplace(serializedHTML, ERB_EXPR, ' ');
        serializedHTML = stringReplace(serializedHTML, TMPLIT_EXPR, ' ');
      }

      return trustedTypesPolicy && RETURN_TRUSTED_TYPE ? trustedTypesPolicy.createHTML(serializedHTML) : serializedHTML;
    };
    /**
     * Public method to set the configuration once
     * setConfig
     *
     * @param {Object} cfg configuration object
     */


    DOMPurify.setConfig = function (cfg) {
      _parseConfig(cfg);

      SET_CONFIG = true;
    };
    /**
     * Public method to remove the configuration
     * clearConfig
     *
     */


    DOMPurify.clearConfig = function () {
      CONFIG = null;
      SET_CONFIG = false;
    };
    /**
     * Public method to check if an attribute value is valid.
     * Uses last set config, if any. Otherwise, uses config defaults.
     * isValidAttribute
     *
     * @param  {string} tag Tag name of containing element.
     * @param  {string} attr Attribute name.
     * @param  {string} value Attribute value.
     * @return {Boolean} Returns true if `value` is valid. Otherwise, returns false.
     */


    DOMPurify.isValidAttribute = function (tag, attr, value) {
      /* Initialize shared config vars if necessary. */
      if (!CONFIG) {
        _parseConfig({});
      }

      const lcTag = transformCaseFunc(tag);
      const lcName = transformCaseFunc(attr);
      return _isValidAttribute(lcTag, lcName, value);
    };
    /**
     * AddHook
     * Public method to add DOMPurify hooks
     *
     * @param {String} entryPoint entry point for the hook to add
     * @param {Function} hookFunction function to execute
     */


    DOMPurify.addHook = function (entryPoint, hookFunction) {
      if (typeof hookFunction !== 'function') {
        return;
      }

      hooks[entryPoint] = hooks[entryPoint] || [];
      arrayPush(hooks[entryPoint], hookFunction);
    };
    /**
     * RemoveHook
     * Public method to remove a DOMPurify hook at a given entryPoint
     * (pops it from the stack of hooks if more are present)
     *
     * @param {String} entryPoint entry point for the hook to remove
     * @return {Function} removed(popped) hook
     */


    DOMPurify.removeHook = function (entryPoint) {
      if (hooks[entryPoint]) {
        return arrayPop(hooks[entryPoint]);
      }
    };
    /**
     * RemoveHooks
     * Public method to remove all DOMPurify hooks at a given entryPoint
     *
     * @param  {String} entryPoint entry point for the hooks to remove
     */


    DOMPurify.removeHooks = function (entryPoint) {
      if (hooks[entryPoint]) {
        hooks[entryPoint] = [];
      }
    };
    /**
     * RemoveAllHooks
     * Public method to remove all DOMPurify hooks
     *
     */


    DOMPurify.removeAllHooks = function () {
      hooks = {};
    };

    return DOMPurify;
  }

  var purify = createDOMPurify();

  return purify;

}));
//# sourceMappingURL=purify.js.map


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
// This entry needs to be wrapped in an IIFE because it needs to be in strict mode.
(() => {
"use strict";

;// ./src/templates.js
const Templates = {
    modal(data = {}) {
        let {header = '', content = '', actionsHtml = ''} = data;
        return `<div class="vi-wbe-modal-container">
                    <div class="vi-wbe-modal-main vi-ui form small">
                        <i class="close icon"></i>
                        <div class="vi-wbe-modal-wrapper">
                            <h3 class="header">${header}</h3>
                            <div class="content">${content}</div>
                            <div class="actions">${actionsHtml}</div>
                        </div>
                    </div>
                </div>`;
    },

    defaultAttributes(data = {}) {
        let {html} = data;
        return `<table class="vi-ui celled table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Attribute</th>
                    </tr>
                    </thead>
                    <tbody>
                    ${html}
                    </tbody>
                </table>`;
    },

};
/* harmony default export */ const templates = (Templates);
;// ./src/custom-column.js




const customColumn = {};
const columnFilter = {};

jQuery(document).ready(function ($) {
    window.viIsEditing = false;
    const mediaMultiple = wp.media({multiple: true});
    const mediaSingle = wp.media({multiple: false});

    const tmpl = {
        galleryImage(src, id) {
            let tmp = $(document).triggerHandler('bulky_gallery_attachment_render',[src, id]);
            if (!tmp){
                tmp = `<li class="vi-wbe-gallery-image" data-id="${id}"><i class="vi-wbe-remove-image dashicons dashicons-no-alt"> </i><img src="${src}"></li>`;
            }
            return tmp;
        },

        fileDownload($_file = {}) {
            let {id, name, file} = $_file;
            let row = $(`<tr>
                        <td><i class="bars icon"></i><input type="text" class="vi-wbe-file-name" value="${name || ''}"></td>
                        <td>
                            <input type="text" class="vi-wbe-file-url" value="${file || ''}">
                            <input type="hidden" class="vi-wbe-file-hash" value="${id || ''}">
                            <span class="vi-ui button mini vi-wbe-choose-file">${functions.text('Choose file')}</span>
                            <i class="vi-wbe-remove-file dashicons dashicons-no-alt"> </i>
                        </td>
                    </tr>`);

            row.on('click', '.vi-wbe-remove-file', function () {
                row.remove();
            });

            return row;
        }
    };

    customColumn.textEditor = {
        type: 'textEditor',

        createCell(cell, i, value, obj) {
            cell.innerHTML = functions.stripHtml(value).slice(0, $('#vi-wbe-spreadsheet').hasClass('vibulky-spreadsheet-wrap-mode')? 500 :50);
            return cell;
        },

        closeEditor(cell, save) {
            window.viIsEditing = false;

            let content = '';
            if (save === true) {
                content = wp.editor.getContent('vi-wbe-text-editor');
                if (!this.isEditing) {
                    // wp.editor.remove('vi-wbe-text-editor');
                    // $('.vi-ui.modal').modal('hide');
                    setTimeout(function (){
                        $('.vi-ui.modal').find('.close.icon').trigger('click');
                    },100);
                }
                this.isEditing = false;
            }else {
                wp.editor.remove('vi-wbe-text-editor');
            }

            $( "#vi-wbe-text-editor" ).val("");

            return content;
        },

        openEditor(cell, el, obj) {
            window.viIsEditing = true;
            let y = cell.getAttribute('data-y'),
                x = cell.getAttribute('data-x'),
                content = obj.options.data[y][x],
                $this = this,
                modalClose = $('.vi-ui.modal .close.icon');
            $('.vi-ui.modal').modal('show');
            this.tinymceInit(content);

            modalClose.off('click');

            $('.vi-wbe-text-editor-save').off('click').on('click', function () {
                $(this).addClass('loading').removeClass('vi-wbe-text-editor-save-clicking');
                if ($('#vi-wbe-text-editor').is(':visible')){
                    $(this).addClass('vi-wbe-text-editor-save-clicking');
                    $('#vi-wbe-text-editor-tmce').trigger('click');
                    return;
                }
                $(this).removeClass('primary loading');
                $this.isEditing = !$(this).hasClass('vi-wbe-close');
                obj.closeEditor(cell, true);
            });

            modalClose.on('click', function () {
                obj.closeEditor(cell, false);
            });

            let modal = $('.vi-ui.modal').parent();
            modal.on('click', function (e) {
                if (e.target === e.delegateTarget) {
                    obj.closeEditor(cell, false);
                }
            })
        },

        updateCell(cell, value, force) {
            let editorValue = wp.editor.getContent('vi-wbe-text-editor');
            if ( editorValue.trim().length > 0 ) {
                value = editorValue;
            }
            cell.innerHTML = functions.stripHtml(value).slice(0, 50);
            return value;
        },

        tinymceInit(content = '') {
            content = wp.editor.autop(content);
            if (tinymce.get('vi-wbe-text-editor') === null) {
                $('#vi-wbe-text-editor').val(content);

                Attributes.tinyMceOptions.tinymce.setup = function (editor) {
                    editor.on('keyup', function (e) {
                        $('.vi-wbe-text-editor-save:not(.vi-wbe-close)').addClass('primary');
                    });
                    editor.on('change', function (e) {
                        setTimeout(function (){
                            if ($('.vi-wbe-text-editor-save-clicking').length){
                                $('.vi-wbe-text-editor-save-clicking').trigger('click');
                            }
                        },100);
                    });
                };

                wp.editor.initialize('vi-wbe-text-editor', Attributes.tinyMceOptions);

            }

            tinymce.get('vi-wbe-text-editor').setContent(content)
        },
    };

    customColumn.image = {
        createCell(cell, i, value, obj) {
            if (value) {
                let url = Attributes.imgStorage[value], tmp = '';
                tmp = $(document).triggerHandler('bulky_gallery_attachment_render', [url, value, false]);
                if (!tmp && functions.isUrl(url)) {
                    tmp = `<img width="40" src="${url}" data-id="${value}">`;
                }
                $(cell).html(tmp);
            }
            return cell;
        },

        closeEditor(cell, save) {
            let id = '';
            if ($(cell).find('.vargal-thumb-video').length){
                id = $(cell).find('.vargal-thumb-video').data('id');
            }
            if (!id && $(cell).find('img').length){
                id = $(cell).find('img').data('id');
            }
            return id;
        },

        openEditor(cell, el, obj) {
            function openMedia() {
                mediaSingle.open().off('select').on('select', function (e) {
                    let uploadedImages = mediaSingle.state().get('selection').first();
                    let selectedImages = uploadedImages.toJSON();
                    let allow_attachment = $(document).triggerHandler('bulky_gallery_approve_attachment',[cell,obj, selectedImages]);
                    let attachment_url = selectedImages?._exmage_external_oembed_src ||selectedImages?._exmage_external_url || selectedImages.url,
                        tmp = '';
                    if (allow_attachment || functions.isUrl(attachment_url)) {
                        Attributes.imgStorage[selectedImages.id] = attachment_url;
                        tmp = $(document).triggerHandler('bulky_gallery_attachment_render', [attachment_url, selectedImages.id, false]);
                        if (!tmp ) {
                            tmp = `<img width="40" src="${attachment_url}" data-id="${selectedImages.id}">`;
                        }
                        $(cell).html(tmp);
                        obj.closeEditor(cell, true);
                    }
                    // if (_f.isUrl(selectedImages.url)) {
                    //     $(cell).html(`<img width="40" src="${selectedImages.url}" data-id="${selectedImages.id}">`);
                    //     Attributes.imgStorage[selectedImages.id] = selectedImages.url;
                    //     obj.closeEditor(cell, true);
                    // }
                });
            }

            $(cell).on('dblclick', openMedia);

            openMedia();
        },

        updateCell(cell, value, force) {
            value = parseInt(value) || '';
            let url = Attributes.imgStorage[value], tmp= '';
            tmp = $(document).triggerHandler('bulky_gallery_attachment_render', [url, value, false]);
            if (!tmp && functions.isUrl(url)) {
                tmp = `<img width="40" src="${url}" data-id="${value}">`;
            }
            $(cell).html(tmp);
            return value;
        },
    };

    customColumn.gallery = {
        type: 'gallery',

        saveData(cell) {
            let newIds = [];
            $(cell).find('.vi-wbe-gallery-image').each(function () {
                newIds.push($(this).data('id'));
            });
            $(cell).find('.vi-wbe-ids-list').val(newIds.join(','));
        },

        createCell(cell, i, value) {
            let hasItem = value.length ? 'vi-wbe-gallery-has-item' : '';
            $(cell).addClass('vi-wbe-gallery');
            $(cell).html(`<div class="vi-wbe-gallery ${hasItem}"><i class="images outline icon"> </i></div>`);
            return cell;
        },

        closeEditor(cell, save) {
            window.viIsEditing = false;

            let selected = [];
            if (save) {
                let child = $(cell).children();
                child.find('.vi-wbe-gallery-image').each(function () {
                    selected.push($(this).data('id'));
                });
            }
            $(cell).find('.vi-wbe-cell-popup').remove();
            return selected;
        },

        openEditor(cell, el, obj) {
            window.viIsEditing = true;

            let y = cell.getAttribute('data-y'),
                x = cell.getAttribute('data-x');

            let ids = obj.options.data[y][x],
                images = '', cacheEdition;

            if (ids.length) {
                for (let id of ids) {
                    let src = Attributes.imgStorage[id];
                    images += tmpl.galleryImage(src, id);
                }
            }

            let galleryPopup = $(`<div class="vi-wbe-cell-popup-inner">
                                    <ul class="vi-wbe-gallery-images">${images}</ul>
                                    <span class="vi-ui button tiny vi-wbe-add-image">${functions.text('Add image')}</span>
                                    <span class="vi-ui button tiny vi-wbe-remove-gallery">${functions.text('Remove all')}</span>
                                </div>`);

            functions.createEditor(cell, 'div', galleryPopup);

            galleryPopup.find('.vi-wbe-gallery-images').sortable({
                items: 'li.vi-wbe-gallery-image',
                cursor: 'move',
                scrollSensitivity: 40,
                forcePlaceholderSize: true,
                forceHelperSize: false,
                helper: 'clone',
                placeholder: 'vi-wbe-sortable-placeholder',
                tolerance: "pointer",
            });

            galleryPopup.on('click', '.vi-wbe-remove-image', function () {
                $(this).parent().remove();
            });

            galleryPopup.on('click', '.vi-wbe-add-image', function () {
                mediaMultiple.open().off('select close')
                    .on('select', function (e) {
                        var selection = mediaMultiple.state().get('selection');
                        selection.each(function (attachment) {
                            attachment = attachment.toJSON();
                            let allow_attachment = $(document).triggerHandler('bulky_gallery_approve_attachment',[cell,obj, attachment]);
                            if (allow_attachment || attachment.type === 'image') {
                                let attachment_url = attachment?._exmage_external_oembed_src ||attachment?._exmage_external_url || attachment.url ;
                                Attributes.imgStorage[attachment.id] = attachment_url;
                                galleryPopup.find('.vi-wbe-gallery-images').append(tmpl.galleryImage(attachment_url, attachment.id));
                            }
                        });
                    });
            });

            galleryPopup.on('click', '.vi-wbe-remove-gallery', function () {
                galleryPopup.find('.vi-wbe-gallery-images').empty();
            });

            if (ids.length === 0) {
                galleryPopup.find('.vi-wbe-add-image').trigger('click');
            }
        },

        updateCell(cell, value, force) {
            let icon = $(cell).find('.vi-wbe-gallery');
            value.length ? icon.addClass('vi-wbe-gallery-has-item') : icon.removeClass('vi-wbe-gallery-has-item');
            return value;
        },
    };

    customColumn.download = {
        createCell(cell, i, value) {
            $(cell).html(`<div><i class="download icon"> </i></div>`);
            return cell;
        },

        closeEditor(cell, save) {
            let data = [];
            if (save) {
                let child = $(cell).children();
                child.find('table.vi-wbe-files-download tbody tr').each(function () {
                    let row = $(this);
                    data.push({
                        id: row.find('.vi-wbe-file-hash').val(),
                        file: row.find('.vi-wbe-file-url').val(),
                        name: row.find('.vi-wbe-file-name').val()
                    });
                });

                child.remove();
            }
            return data;
        },

        openEditor(cell, el, obj) {

            let y = cell.getAttribute('data-y'),
                x = cell.getAttribute('data-x');

            let files = obj.options.data[y][x],
                cacheEdition, tbody = $('<tbody></tbody>');

            if (Array.isArray(files)) {
                for (let file of files) {
                    tbody.append(tmpl.fileDownload(file));
                }
            }

            let fileDownloadPopup = $(`<div class="">
                                        <table class="vi-wbe-files-download vi-ui celled table">
                                            <thead>
                                            <tr>
                                                <th>${functions.text('Name')}</th>
                                                <th>${functions.text('File URL')}</th>
                                            </tr>
                                            </thead>
                                        </table>
                                        <span class="vi-ui button tiny vi-wbe-add-file">${functions.text('Add file')}</span>
                                    </div>`);

            fileDownloadPopup.find('.vi-wbe-files-download').append(tbody);

            functions.createEditor(cell, 'div', fileDownloadPopup);

            tbody.sortable();

            fileDownloadPopup.on('click', '.vi-wbe-add-file', () => fileDownloadPopup.find('.vi-wbe-files-download tbody').append(tmpl.fileDownload()));

            fileDownloadPopup.on('click', '.vi-wbe-choose-file', function () {
                cacheEdition = obj.edition;
                obj.edition = null;
                let row = $(this).closest('tr');

                mediaSingle.open().off('select close')
                    .on('select', function (e) {
                        let selected = mediaSingle.state().get('selection').first().toJSON();
                        if (selected.url) row.find('.vi-wbe-file-url').val(selected.url).trigger('change');
                    })
                    .on('close', () => obj.edition = cacheEdition);
            });

            if (!files.length) {
                fileDownloadPopup.find('.vi-wbe-add-file').trigger('click');
            }
        },

        updateCell(cell, value, force) {
            $(cell).html(`<div><i class="download icon"> </i></div>`);
            return value;
        },
    };

    customColumn.tags = {
        type: 'tags',
        createCell(cell, i, value, obj) {
            functions.formatText(cell, value);
            return cell;
        },

        openEditor(cell, el, obj) {
            let y = cell.getAttribute('data-y'),
                x = cell.getAttribute('data-x');

            let value = obj.options.data[y][x],
                select = $('<select class="bulky-select-tag" />'),
                addBtn = $('<span class="vi-ui button mini basic">Add</span>'),
                editor = functions.createEditor(cell, 'div', '<div style="display: flex; gap: 10px;"><div class="bulky-select-tag-wrapper" style="width: 100%;"></div><div class="bulky-add-select-tag"></div></div>');

            let searchKey;

            $(editor).find('.bulky-select-tag-wrapper').append(select);
            $(editor).find('.bulky-add-select-tag').append(addBtn);

            select.select2({
                data: value,
                multiple: true,
                minimumInputLength: 3,
                placeholder: functions.text('Search tags...'),
                ajax: {
                    url: Attributes.ajaxUrl,
                    type: 'post',
                    data: function (params) {
                        return {
                            ...Attributes.ajaxData,
                            sub_action: 'search_tags',
                            search: params.term,
                            type: 'public'
                        };
                    },
                    processResults: function (data) {
                        return {results: data};
                    }
                }
            }).on('select2:select', function (e) {
                searchKey = '';
            });

            select.find('option').attr('selected', true).parent().trigger('change');

            // $(editor).find('.select2-search__field').trigger('click');

            $('body').on('change', '.select2-search__field', function () {
                searchKey = $(this).val();
            });

            addBtn.on('click', function () {
                if (searchKey) {
                    let newOption = new Option(searchKey, searchKey, true, true);
                    $(editor).find('.bulky-select-tag').append(newOption).trigger('change');
                    searchKey = '';
                }
            })
        },

        closeEditor(cell, save) {
            let child = $(cell).children(),
                data = child.find('select').select2('data'),
                selected = [];

            if (data.length) {
                for (let item of data) {
                    selected.push({id: item.id, text: item.text})
                }
            }
            child.remove();
            $('.select2-container').remove();
            return selected;
        },

        updateCell(cell, value, force, obj, x) {
            functions.formatText(cell, value);
            return value;
        }
    };

    customColumn.link_products = {
        createCell(cell, i, value, obj) {
            functions.formatText(cell, value);
            return cell;
        },

        closeEditor(cell, save) {
            let child = $(cell).children(), selected = [];

            if (save) {
                let data = child.find('select').select2('data');

                if (data.length) {
                    for (let item of data) {
                        selected.push({id: item.id, text: item.text})
                    }
                }
            }

            child.remove();
            $('.select2-container').remove();
            return selected;
        },

        openEditor(cell, el, obj) {
            let y = cell.getAttribute('data-y'),
                x = cell.getAttribute('data-x');

            let value = obj.options.data[y][x],
                select = $('<select/>');

            let editor = functions.createEditor(cell, 'div', select);

            select.select2({
                data: value,
                multiple: true,
                minimumInputLength: 3,
                placeholder: functions.text('Search products...'),
                ajax: {
                    url: Attributes.ajaxUrl,
                    type: 'post',
                    delay: 250,
                    dataType: 'json',
                    data: function (params) {
                        return {
                            ...Attributes.ajaxData,
                            sub_action: 'search_products',
                            search: params.term,
                            type: 'public'
                        };
                    },
                    processResults: function (data) {
                        var terms = [];
                        if (data) {
                            $.each(data, function (id, text) {
                                terms.push({id: id, text: text});
                            });
                        }
                        return {
                            results: terms
                        };
                    }
                }
            });

            select.find('option').attr('selected', true).parent().trigger('change');
            $(editor).find('.select2-search__field').trigger('click');
        },

        updateCell(cell, value, force, obj, x) {
            functions.formatText(cell, value);
            return value;
        }
    };

    customColumn.product_attributes = {
        type: 'product_attributes',

        createCell(cell, i, value, obj) {
            let hasItem = Object.keys(value).length ? 'vi-wbe-has-attrs' : '';
            $(cell).html(`<div class="vi-wbe-product-attrs ${hasItem}"><i class="icon edit"/></div>`);
            return cell;
        },

        updateCell(cell, value, force, obj, x) {
            let icon = $(cell).find('.vi-wbe-product-attrs');
            Object.keys(value).length ? icon.addClass('vi-wbe-has-attrs') : icon.removeClass('vi-wbe-has-attrs');

            return value;
        },

        openEditor(cell, el, obj) {
            let data = functions.getDataFromCell(obj, cell),
                productType = functions.getProductTypeFromCell(cell),
                $this = this, html = '';

            this.productType = productType;

            let modal = functions.createModal({
                header: functions.text('Edit attributes'),
                content: '',
                actions: [{class: 'save-attributes', text: functions.text('Save')}],
                silent: true,
            });

            $(cell).append(modal);

            if (productType !== 'variation') {
                let {attributes} = Attributes;
                let addAttribute = `<option value="">${functions.text('Custom product attribute')}</option>`;

                for (let attr in attributes) {
                    addAttribute += `<option value="${attr}">${attributes[attr].data.attribute_label}</option>`;
                }

                addAttribute = `<div class="vi-wbe-taxonomy-header">
                                    <select class="vi-wbe-select-taxonomy">${addAttribute}</select>
                                    <span class="vi-ui button tiny vi-wbe-add-taxonomy">${functions.text('Add')}</span>
                                </div>`;

                if (Array.isArray(data) && data.length) {
                    for (let item of data) {
                        html += $this.createRowTable(item);
                    }
                }

                html = `${addAttribute}
                        <table class="vi-ui celled table">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>Attributes</th>
                                <th width="1">Actions</th>
                            </tr>
                            </thead>
                            <tbody>${html}</tbody>
                        </table>`;

                modal.find('.content').append(html);
                modal.find('table select').select2({multiple: true});
                modal.find('tbody').sortable({
                    items: 'tr',
                    cursor: 'move',
                    axis: 'y',
                    scrollSensitivity: 40,
                    forcePlaceholderSize: true,
                    helper: 'clone',
                    handle: '.icon.move',
                });

                const setOptionDisable = () => {
                    modal.find('select.vi-wbe-select-taxonomy option').removeAttr('disabled');
                    modal.find('input[type=hidden]').each(function (i, el) {
                        let tax = $(el).val();
                        modal.find(`select.vi-wbe-select-taxonomy option[value='${tax}']`).attr('disabled', 'disabled');
                    });
                };

                setOptionDisable();

                modal.on('click', function (e) {
                    let $thisTarget = $(e.target);
                    if ($thisTarget.hasClass('trash')) {
                        $thisTarget.closest('tr').remove();
                        setOptionDisable();
                    }

                    if ($thisTarget.hasClass('vi-wbe-add-taxonomy')) {
                        let taxSelect = $('.vi-wbe-select-taxonomy'), tax = taxSelect.val(),
                            item = {name: tax, options: []};
                        if (tax) item.is_taxonomy = 1;

                        let row = $($this.createRowTable(item));
                        modal.find('table tbody').append(row);
                        row.find('select').select2({multiple: true});
                        setOptionDisable();
                        taxSelect.val('').trigger('change');
                    }

                    if ($thisTarget.hasClass('vi-wbe-select-all-attributes')) {
                        let td = $thisTarget.closest('td');
                        let select = td.find('select');
                        select.find('option').attr('selected', true);
                        select.trigger('change');
                    }

                    if ($thisTarget.hasClass('vi-wbe-select-no-attributes')) {
                        let td = $thisTarget.closest('td');
                        let select = td.find('select');
                        select.find('option').attr('selected', false);
                        select.trigger('change');
                    }

                    if ($thisTarget.hasClass('vi-wbe-add-new-attribute')) {
                        let newAttr = prompt(functions.text('Enter a name for the new attribute term:'));

                        if (!newAttr) return;

                        let tr = $thisTarget.closest('tr.vi-wbe-attribute-row'),
                            taxAttr = tr.attr('data-attr');

                        if (taxAttr) {
                            taxAttr = JSON.parse(taxAttr);
                            functions.ajax({
                                data: {
                                    sub_action: 'add_new_attribute',
                                    taxonomy: taxAttr.name,
                                    term: newAttr
                                },
                                beforeSend() {
                                    $thisTarget.addClass('loading')
                                },
                                success(res) {
                                    if (res.success) {
                                        let select = tr.find('select');
                                        select.append(`<option value="${res.data.term_id}" selected>${res.data.name}</option>`);
                                        select.trigger('change');
                                        Attributes.attributes[taxAttr.name].terms[res.data.term_id] = {slug: res.data.slug, text: res.data.name}
                                    } else {
                                        alert(res.data.message)
                                    }
                                },
                                error(res) {
                                    console.log(res);
                                    alert(res.statusText + res.responseText);
                                },
                                complete() {
                                    $thisTarget.removeClass('loading');
                                }
                            });
                        }
                    }
                });

            } else {
                //Variation attributes
                let y = cell.getAttribute('data-y');
                let parentId = obj.options.data[y][1],
                    allProducts = obj.getData(), parentAttributes;

                for (let _y in allProducts) {
                    let productId = allProducts[_y][0];
                    if (parentId == productId) {
                        let x = Attributes.idMappingFlip.attributes;
                        parentAttributes = obj.options.data[_y][x];
                        break;
                    }
                }

                if (parentAttributes) {
                    for (let attr of parentAttributes) {
                        let options = `<option value="">${functions.text('Any...')}</option>`, name = attr.name, label;
                        if (attr.is_taxonomy) {
                            let attrData = Attributes.attributes[name];
                            for (let id of attr.options) {
                                let term = attrData.terms[id];
                                let selected = term.slug === data[name] ? 'selected' : '';
                                options += `<option value="${term.slug}" ${selected}>${term.text}</option>`;
                            }
                            label = attrData.data.attribute_label
                        } else {
                            for (let value of attr.options) {
                                let selected = value === data[name] ? 'selected' : '';
                                options += `<option value="${value}" ${selected}>${value}</option>`;
                            }
                            label = name;
                        }
                        html += `<tr><td>${label}</td><td><select name="${name}">${options}</select></td></tr>`;
                    }
                }

                html = `<table class="vi-ui celled table">
                            <thead>
                            <tr>
                                <th>${functions.text('Attribute')}</th>
                                <th>${functions.text('Option')}</th>
                            </tr>
                            </thead>
                            <tbody>
                            ${html}
                            </tbody>
                        </table>`;

                modal.find('.content').append(html);
            }

            modal.on('click', function (e) {
                let thisTarget = $(e.target);
                if (thisTarget.hasClass('close') || thisTarget.hasClass('vi-wbe-modal-container')) obj.closeEditor(cell, false);
                if (thisTarget.hasClass('save-attributes')) obj.closeEditor(cell, true);
            });
        },

        closeEditor(cell, save) {
            let data = [];
            if (save === true) {
                if (this.productType !== 'variation') {
                    $(cell).find('.vi-wbe-attribute-row').each(function (i, row) {
                        let pAttr = $(row).data('attr');
                        if (pAttr.is_taxonomy) {
                            pAttr.options = $(row).find('select').val().map(Number);
                        } else {
                            pAttr.name = $(row).find('input.custom-attr-name').val();
                            let value = $(row).find('textarea.custom-attr-val').val();
                            pAttr.value = value.trim().replace(/[^\S\n]+/g, ' ');
                            pAttr.options = value.split('|').map(item => item.trim().replace(/[^\S\n]+/g, ' '));
                        }
                        pAttr.visible = !!$(row).find('.attr-visibility:checked').length;
                        pAttr.variation = !!$(row).find('.attr-variation:checked').length;
                        pAttr.position = i;
                        data.push(pAttr)
                    })
                } else {
                    data = {};
                    $(cell).find('select').each(function (i, row) {
                        data[$(row).attr('name')] = $(row).val();
                    });
                }
            }
            functions.removeModal(cell);
            return data;
        },

        createRowTable(item) {
            let attrName = '', value = '';

            if (item.is_taxonomy) {
                let attribute = Attributes.attributes[item.name],
                    terms = attribute.terms || [], options = '';

                attrName = `${attribute.data.attribute_label}<input type="hidden" value="${item.name}"/>`;

                if (Object.keys(terms).length) {
                    for (let id in terms) {
                        let selected = item.options.includes(parseInt(id)) ? 'selected' : '';
                        options += `<option value="${id}" ${selected}>${terms[id].text}</option>`;
                    }
                }
                value = `<select multiple>${options}</select>
                        <div class="vi-wbe-attributes-button-group">
                            <span class="vi-ui button mini vi-wbe-select-all-attributes">${functions.text('Select all')}</span>
                            <span class="vi-ui button mini vi-wbe-select-no-attributes">${functions.text('Select none')}</span>
                            <span class="vi-ui button mini vi-wbe-add-new-attribute">${functions.text('Add new')}</span>
                        </div>`;
            } else {
                attrName = `<input type="text" class="custom-attr-name" value="${item.name}" placeholder="${functions.text('Custom attribute name')}"/>`;
                value = `<textarea class="custom-attr-val" placeholder="${functions.text('Enter some text, or some attributes by "|" separating values.')}">${item.value || ''}</textarea>`;
            }

            attrName = `<div class="vi-wbe-attribute-name-label">${attrName}</div>`;

            attrName += `<div>
                            <input type="checkbox" class="attr-visibility" ${item.visible ? 'checked' : ''} value="1">
                            <label>${functions.text('Visible on the product page')}</label>
                        </div>`;

            if (this.productType === 'variable') {
                attrName += `<div>
                                <input type="checkbox" class="attr-variation" ${item.variation ? 'checked' : ''} value="1">
                                <label>${functions.text('Used for variations')}</label>
                            </div>`;
            }

            return `<tr class="vi-wbe-attribute-row" data-attr='${JSON.stringify(item)}'>
                        <td class="vi-wbe-left">${attrName}</td>
                        <td>${value}</td>
                        <td class="vi-wbe-right"><i class="icon trash"> </i> <i class="icon move"> </i></td>
                    </tr>`;
        }

    };

    customColumn.default_attributes = {
        createCell(cell, i, value, obj) {
            if (value) $(cell).text(Object.values(value).filter(Boolean).join('; '));
            return cell;
        },

        updateCell(cell, value, force, obj, x) {
            if (value) {
                $(cell).text(Object.values(value).filter(Boolean).join('; '));
            } else {
                $(cell).text('');
            }

            return value;
        },

        openEditor(cell, el, obj) {
            let data = functions.getDataFromCell(obj, cell),
                productType = functions.getProductTypeFromCell(cell),
                html = '';

            this.productType = productType;
            if (productType === 'variable') {
                let modal = functions.createModal({header: functions.text('Set default attributes'), content: '', actions: [{class: 'save-attributes', text: functions.text('Save')}]});
                $(cell).append(modal);

                let y = cell.getAttribute('data-y'),
                    x = Attributes.idMappingFlip.attributes,
                    pAttributes = obj.options.data[y][x];

                if (Array.isArray(pAttributes) && pAttributes.length) {
                    for (let attr of pAttributes) {
                        if (attr.options.length === 0) continue;

                        let attrName = '', selectHtml = '';

                        if (attr.is_taxonomy) {
                            let attrData = Attributes.attributes[attr.name];

                            attrName = attrData.data.attribute_label;
                            for (let termId of attr.options) {
                                let term = attrData.terms[termId],
                                    selected = term.slug === data[attr.name] ? 'selected' : '';
                                let termValue = functions.htmlEncode(term.slug);

                                selectHtml += `<option value="${termValue}" ${selected}>${term.text}</option>`;
                            }

                        } else {
                            attrName = attr.attribute_label;
                            for (let term of attr.options) {
                                let selected = term === data[attr.name] ? 'selected' : '';
                                let termValue = functions.htmlEncode(term);
                                selectHtml += `<option value="${termValue}" ${selected}>${term}</option>`;
                            }
                        }
                        selectHtml = `<option value="">No default ${attrName}</option> ${selectHtml}`;

                        html += `<tr><td>${attrName}</td><td><select name="${attr.name}" class="vi-wbe-default-attribute">${selectHtml}</select></td></tr>`;
                    }
                }

                modal.find('.content').append(templates.defaultAttributes({html}));

                modal.on('click', function (e) {
                    let thisTarget = $(e.target);
                    if (thisTarget.hasClass('close') || thisTarget.hasClass('vi-wbe-modal-container')) obj.closeEditor(cell, false);
                    if (thisTarget.hasClass('save-attributes')) obj.closeEditor(cell, true);
                });
            }
        },

        closeEditor(cell, save) {
            let data = {};
            if (save === true) $(cell).find('.vi-wbe-default-attribute').each((i, el) => data[$(el).attr('name')] = $(el).val());
            functions.removeModal(cell);
            return data;
        },

    };

    customColumn.array = {
        createCell(cell, i, value, obj) {
            $(cell).html(value ? JSON.stringify(value) : value);
            // $(cell).html('<i class="icon edit"/>');
            return cell;
        },

        closeEditor(cell, save) {
            let metadata = [];
            if (save === true) {
                metadata = this.editor.get();
            }

            functions.removeModal(cell);

            return metadata;
        },

        openEditor(cell, el, obj) {
            let data = functions.getDataFromCell(obj, cell);
            let modal = functions.createModal({
                header: functions.text('Edit metadata'),
                content: '',
                actions: [{class: 'save-metadata', text: functions.text('Save')}],
            });

            $(cell).append(modal);
            modal.find('.content').html('<div id="vi-wbe-jsoneditor"></div>');
            let container = modal.find('#vi-wbe-jsoneditor').get(0);
            this.editor = new JSONEditor(container, {enableSort: false, search: false, enableTransform: false});
            this.editor.set(data);

            modal.on('click', function (e) {
                let thisTarget = $(e.target);
                if (thisTarget.hasClass('close') || thisTarget.hasClass('vi-wbe-modal-container')) obj.closeEditor(cell, false);
                if (thisTarget.hasClass('save-metadata')) obj.closeEditor(cell, true);
            });
        },

        updateCell(cell, value, force) {

            $(cell).html(value ? JSON.stringify(value) : value);
            return value;
        },
    };

    customColumn.order_notes = {

        createCell(cell, i, value, obj) {
            let hasItem = value.length ? 'vi-wbe-gallery-has-item' : '';

            $(cell).html(`<div class="${hasItem}"><i class="icon eye"/></div>`);
            this.obj = obj;

            return cell;
        },

        closeEditor(cell, save) {
            $(cell).find('.vi-wbe-cell-popup').remove();
            return this.notes;
        },

        openEditor(cell, el, obj) {
            let y = cell.getAttribute('data-y'),
                x = cell.getAttribute('data-x');

            let notes = obj.options.data[y][x],
                _note = '';

            this.notes = notes;

            if (notes.length) {
                for (let note of notes) {
                    let content = note.content.replace(/(?:\r\n|\r|\n)/g, '<br>'),
                        classColor = note.customer_note ? 'customer' : (note.added_by === 'system' ? 'system' : 'private');

                    _note += `<div class="vi-wbe-note-row">
                                <div class="vi-wbe-note-row-content ${classColor}">${content}</div>
                                <span class="vi-wbe-note-row-meta">
                                    ${note.date}
                                    <a href="#" data-comment_id="${note.id}" class="vi-wbe-note-row-delete">${functions.text('Delete')}</a>
                                </span>
                            </div>`;
                }
            }

            let galleryPopup = $(`<div class="vi-wbe-cell-popup-inner">${_note}</div>`);

            functions.createEditor(cell, 'div', galleryPopup);

            galleryPopup.on('click', '.vi-wbe-note-row-delete', function () {
                let $thisBtn = $(this),
                    id = $thisBtn.data('comment_id');

                if (!id) return;

                functions.ajax({
                    data: {sub_action: 'delete_order_note', id},
                    beforeSend() {
                        functions.loading()
                    },
                    success(res) {
                        if (res.success) {
                            let index = notes.findIndex(note => note.id === id);
                            notes.splice(index, 1);
                            $thisBtn.closest('.vi-wbe-note-row').remove();
                        }
                    },
                    error(res) {
                        console.log(res);
                        alert(res.statusText + res.responseText);
                    },
                    complete() {
                        functions.removeLoading();
                    }
                })
            })
        },

        updateCell(cell, value, force) {
            return value;
        },
    };

    customColumn.select2 = {
        type: 'select2',

        createCell(cell, i, value, obj) {
            let {source} = obj.options.columns[i], newValue = [];
            if (!Array.isArray(value)) value = Object.values(value);
            if (Array.isArray(source) && source.length) newValue = source.filter(item => value.includes(item.id));

            functions.formatText(cell, newValue);
            return cell;
        },

        openEditor(cell, el, obj) {
            let y = cell.getAttribute('data-y'),
                x = cell.getAttribute('data-x');

            let value = obj.options.data[y][x],
                select = $('<select/>'),
                {source, multiple, placeholder} = obj.options.columns[x],
                editor = functions.createEditor(cell, 'div', select);

            select.select2({
                data: source || [],
                multiple: multiple,
                placeholder: placeholder,
            });

            select.val(value).trigger('change');
            $(editor).find('.select2-search__field').trigger('click');
        },

        closeEditor(cell, save) {
            let child = $(cell).children(),
                data = child.find('select').val();

            data = data.map(item => !isNaN(item) ? +item : item);

            child.remove();
            $('.select2-container').remove();

            return data;
        },

        updateCell(cell, value, force, obj, x) {
            let {source} = obj.options.columns[x], newValue = [];

            if (Array.isArray(source) && source.length) newValue = source.filter(item => value.includes(item.id));

            functions.formatText(cell, newValue);

            return value;
        }
    };

//--------------------------------------------------------------------//
    columnFilter.sourceForVariation = (el, cell, x, y, obj) => {
        let source = obj.options.columns[x].source;
        let productType = functions.getProductTypeFromCell(cell);
        if (productType === 'variation') {
            source = obj.options.columns[x].subSource;
        }
        return source;
    };

});


;// ./src/attributes.js


const Attributes = {
    ...wbeParams,
    productTypes: {},
    filterKey: Date.now(),
    selectPage: 1,
    ajaxData: {action: 'vi_wbe_ajax', vi_wbe_nonce: wbeParams.nonce},
    tinyMceOptions: {
        tinymce: {
            theme: "modern",
            skin: "lightgray",
            language: "en",
            formats: {
                alignleft: [
                    {selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li", styles: {textAlign: "left"}},
                    {selector: "img,table,dl.wp-caption", classes: "alignleft"}
                ],
                aligncenter: [
                    {selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li", styles: {textAlign: "center"}},
                    {selector: "img,table,dl.wp-caption", classes: "aligncenter"}
                ],
                alignright: [
                    {selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li", styles: {textAlign: "right"}},
                    {selector: "img,table,dl.wp-caption", classes: "alignright"}
                ],
                strikethrough: {inline: "del"}
            },
            relative_urls: false,
            remove_script_host: false,
            convert_urls: false,
            browser_spellcheck: true,
            fix_list_elements: true,
            entities: "38,amp,60,lt,62,gt",
            entity_encoding: "raw",
            keep_styles: false,
            cache_suffix: "wp-mce-49110-20201110",
            resize: "vertical",
            menubar: false,
            branding: false,
            preview_styles: "font-family font-size font-weight font-style text-decoration text-transform",
            end_container_on_empty_block: true,
            wpeditimage_html5_captions: true,
            wp_lang_attr: "en-US",
            wp_keep_scroll_position: false,
            wp_shortcut_labels: {
                "Heading 1": "access1",
                "Heading 2": "access2",
                "Heading 3": "access3",
                "Heading 4": "access4",
                "Heading 5": "access5",
                "Heading 6": "access6",
                "Paragraph": "access7",
                "Blockquote": "accessQ",
                "Underline": "metaU",
                "Strikethrough": "accessD",
                "Bold": "metaB",
                "Italic": "metaI",
                "Code": "accessX",
                "Align center": "accessC",
                "Align right": "accessR",
                "Align left": "accessL",
                "Justify": "accessJ",
                "Cut": "metaX",
                "Copy": "metaC",
                "Paste": "metaV",
                "Select all": "metaA",
                "Undo": "metaZ",
                "Redo": "metaY",
                "Bullet list": "accessU",
                "Numbered list": "accessO",
                "Insert\/edit image": "accessM",
                "Insert\/edit link": "metaK",
                "Remove link": "accessS",
                "Toolbar Toggle": "accessZ",
                "Insert Read More tag": "accessT",
                "Insert Page Break tag": "accessP",
                "Distraction-free writing mode": "accessW",
                "Add Media": "accessM",
                "Keyboard Shortcuts": "accessH"
            },
            // content_css: "http://localhost:8000/wp-includes/css/dashicons.min.css?ver=5.6.2,http://localhost:8000/wp-includes/js/tinymce/skins/wordpress/wp-content.css?ver=5.6.2,https://fonts.googleapis.com/css?family=Source+Sans+Pro:400%2C300%2C300italic%2C400italic%2C600%2C700%2C900&subset=latin%2Clatin-ext,http://localhost:8000/wp-content/themes/storefront/assets/css/base/gutenberg-editor.css",
            plugins: "charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern,wpview",
            selector: "#vi-wbe-text-editor",
            wpautop: true,
            indent: false,
            toolbar1: "formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,wp_more,spellchecker,fullscreen,wp_adv",
            toolbar2: "strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help",
            tabfocus_elements: ":prev,:next",
            body_class: "excerpt post-type-product post-status-publish page-template-default locale-en-us",
        },
        mediaButtons: true,
        quicktags: true
    },
    setColumns(raw) {
        try {
            let columns = JSON.parse(raw);
            Attributes.columns = columns.map((col) => {
                if (col && col.editor && customColumn[col.editor]) col.editor = customColumn[col.editor];
                if (col && col.filter && columnFilter[col.filter]) col.filter = columnFilter[col.filter];
                return col;
            });

        } catch (e) {
            console.log(e);
        }
    }
};


window.Attributes = Attributes;
const I18n = wbeI18n.i18n;

;// ./src/functions.js



const $ = jQuery;
const _f = {
    setJexcel(obj) {
        this.jexcel = obj;
    },

    text(key) {
        return I18n[key] || key;
    },

    isUrl: (url) => {
        return /^(http(s?):)\/\/.*\.(?:jpg|jpeg|gif|png|webp|avif)$/i.test(url);
    },

    formatText(cell, value) {
        let text = '';
        if (value.length) {
            for (let k = 0; k < value.length; k++) {
                if (value[k]) text += value[k].text + '; ';
            }
        }
        cell.innerText = text;
    },

    createEditor(cell, type, content = '', display = true) {
        let editor = document.createElement(type);

        if (type === 'div') {
            $(editor).append(content);
        }

        editor.style.minWidth = '300px';

        let popupHeight = $(editor).innerHeight(),
            stage = $(cell).offset(),
            x = stage.left,
            y = stage.top,
            cellWidth = $(cell).innerWidth(),
            info = cell.getBoundingClientRect();

        if (y > (window.innerHeight/2)){
            y = window.innerHeight/3;
        }
        if (display) {
            editor.style.minHeight = (info.height - 2) + 'px';
            editor.style.maxHeight = (window.innerHeight - y - 50) + 'px';
        } else {
            editor.style.opacity = 0;
            editor.style.fontSize = 0;
        }

        editor.classList.add('vi-ui', 'segment', 'vi-wbe-cell-popup', 'vi-wbe-editing');
        cell.classList.add('editor');
        cell.appendChild(editor);

        let popupWidth = $(editor).innerWidth();

        if ($(this.jexcel.el).innerWidth() < x + popupWidth + cellWidth) {
            let left = x - popupWidth > 0 ? x - popupWidth : 10;
            $(editor).css('left', left + 'px');
        } else {
            $(editor).css('left', (x + cellWidth) + 'px');
        }
        if (window.innerHeight < y + popupHeight) {
            let h = y - popupHeight < 0 ? 0 : y - popupHeight;
            $(editor).css('top', h + 'px');
        } else {
            $(editor).css('top', y + 'px');
        }

        return editor;
    },

    createModal(data = {}) {
        let {actions} = data;
        let actionsHtml = '';

        if (Array.isArray(actions)) {
            for (let item of actions) {
                actionsHtml += `<span class="${item.class} vi-ui button tiny">${item.text}</span>`;
            }
        }

        return $(templates.modal({...data, actionsHtml}));
    },

    removeModal(cell) {
        $(cell).find('.vi-wbe-modal-container').remove();
        $('.select2-container--open').remove();
    },

    getColFromColumnType(colType) {
        return Attributes.idMappingFlip[colType] || '';
    },

    getProductTypeFromCell(cell) {
        let y = cell.getAttribute('data-y');
        let x = this.getColFromColumnType('product_type');
        return this.jexcel.options.data[y] ? this.jexcel.options.data[y][x] : null;
    },

    getProductTypeFromY(y) {
        let x = this.getColFromColumnType('product_type');
        return this.jexcel.options.data[y] ? this.jexcel.options.data[y][x] : null;
    },

    getReviewTypeFrom(y) {
        let x = this.getColFromColumnType('comment_type');
        return this.jexcel.options.data[y] ? this.jexcel.options.data[y][x] : null;
    },

    getColumnType(x) {
        return Attributes.idMapping[x]
    },

    stripHtml(content) {
        return $(`<div>${content}</div>`).text();
    },

    getDataFromCell(obj, cell) {
        let y = cell.getAttribute('data-y'),
            x = cell.getAttribute('data-x');
        return obj.options.data[y][x];
    },

    getProductIdOfCell(obj, target) {
        if (typeof target === 'object') {
            let y = target.getAttribute('data-y');
            return obj.options.data[y][0];
        } else {
            return obj.options.data[target][0];
        }
    },

    ajax(args = {}) {
        let options = Object.assign({
            url: wbeParams.ajaxUrl,
            type: 'post',
            dataType: 'json',
        }, args);

        options.data.action = 'vi_wbe_ajax';
        options.data.vi_wbe_nonce = wbeParams.nonce;
        options.data.type = wbeParams.editType;
        $.ajax(options);
    },

    pagination(maxPage, currentPage) {
        currentPage = parseInt(currentPage);
        maxPage = parseInt(maxPage);
        let pagination = '',
            previousArrow = `<a class="item ${currentPage === 1 ? 'disabled' : ''}" data-page="${currentPage - 1}"><i class="icon angle left"> </i></a>`,
            nextArrow = `<a class="item ${currentPage === maxPage ? 'disabled' : ''}" data-page="${currentPage + 1}"><i class="icon angle right"> </i></a>`,
            goToPage = `<input type="number" class="vi-wbe-go-to-page" value="${currentPage}" min="1" max="${maxPage}"/>`;

        for (let i = 1; i <= maxPage; i++) {
            if ([1, currentPage - 1, currentPage, currentPage + 1, maxPage].includes(i)) {
                pagination += `<a class="item ${currentPage === i ? 'active' : ''}" data-page="${i}">${i}</a>`;
            }
            if (i === currentPage - 2 && currentPage - 2 > 1) pagination += `<a class="item disabled">...</a>`;
            if (i === currentPage + 2 && currentPage + 2 < maxPage) pagination += `<a class="item disabled">...</a>`;
        }

        return `<div class="vi-ui pagination menu">${previousArrow} ${pagination} ${nextArrow} </div> ${goToPage}`;
    },

    spinner() {
        return $('<span class="vi-wbe-spinner"><span class="vi-wbe-spinner-inner"> </span></span>')
    },

    is_loading() {
        return !!this._spinner;
    },

    loading() {
        this._spinner = this.spinner();
        $('.vi-wbe-menu-bar-center').html(this._spinner);
    },

    removeLoading() {
        this._spinner = null;
        $('.vi-wbe-menu-bar-center').html('');
    },

    notice(text, color = 'black') {
        let content = $(`<div class="vi-wbe-notice" style="color:${color}">${text}</div>`);
        $('.vi-wbe-menu-bar-center').html(content);
        setTimeout(function () {
            content.remove();
        }, 5000);
    },

    generateCouponCode() {
        let $result = '';
        for (var i = 0; i < Attributes.couponGenerate.char_length; i++) {
            $result += Attributes.couponGenerate.characters.charAt(
                Math.floor(Math.random() * Attributes.couponGenerate.characters.length)
            );
        }
        $result = Attributes.couponGenerate.prefix + $result + Attributes.couponGenerate.suffix;
        return $result;
    },

    htmlEncode(str) {
        return str.replace(/&/g, "&amp;")
            .replace(/>/g, "&gt;")
            .replace(/</g, "&lt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g,"&apos;");
    },

    showMessage({title = '', message = '', type = 'positive', duration = 3000}) {
    const main = $( "#vi-wbe-container" ).find( "#vi-hui-toast" );
    if ( main.get(0) ) {
        let toast_class = 'bulky-message-'+ Date.now();
        let toast = $( "<div class='"+toast_class+"'></div>" );
        setTimeout( function (toast_class) {
            $( '.'+toast_class+' .icon.close' ).trigger('click');
        }, duration + 1000 , toast_class);

        toast.on( "click", ".icon.close", function (e) {
            $(this).closest( ".vi-ui.message" ).remove();
        } );

        if ( main.children().length > 0 ) {
            main.find( ".vi-hui-toast" ).first().remove();
        }
        const delay = (duration / 1000).toFixed(2);

        if ($('html').attr('dir') === 'rtl') {
            toast.css( { "animation": `slideInRight ease .3s, fadeOut linear 1s ${delay}s forwards` } );
        } else {
            toast.css( { "animation": `slideInLeft ease .3s, fadeOut linear 1s ${delay}s forwards` } );
        }

        toast.addClass( `vi-ui ${type} message` );
        toast.html(
            `<i class="close icon"></i>
                          <div class="header">
                            ${title}
                          </div>
                          <p>${message}</p>`
        );

        if ( main.children().length > 0 ) {
            let firstEleType = main.find( ".vi-ui.message" ).first().attr( "class" ).split(/\s+/)[1];
            if ( type !== firstEleType ) {
                main.append( toast );
            }
        }else {
            main.append( toast );
        }
    }
}
};

/* harmony default export */ const functions = (_f);
;// ./src/modal-popup.js
const modal_popup_$ = jQuery;

class Modal {
    constructor() {

    }
}

let popupInstance = null;

class Popup {
    constructor(content, cell) {
        if (!popupInstance) {
            modal_popup_$('body').on('mousedown keydown', this.mousedown);
        }

        popupInstance = this;

        this.popup = modal_popup_$('.vi-wbe-context-popup');

        this.render(content, modal_popup_$(cell));
    }

    mousedown(e) {
        let thisTarget = modal_popup_$(e.target),
            popup = modal_popup_$('.vi-wbe-context-popup');

        if (e.which === 27
            || !thisTarget.hasClass('vi-wbe-context-popup')
            && thisTarget.closest('.vi-wbe-context-popup').length === 0
            && popup.hasClass('vi-wbe-popup-active')
            && !thisTarget.hasClass('select2-search__field')
        ) {
            popup.empty().removeClass('vi-wbe-popup-active');
            modal_popup_$('.select2-container.select2-container--default.select2-container--open').remove();
        }
    }

    render(content, cell) {
        let {popup} = this,
            stage = cell.offset(),
            x = stage.left,
            y = stage.top,
            cellWidth = cell.innerWidth();

        popup.empty();
        popup.addClass('vi-wbe-popup-active').html(content);

        let popupWidth = popup.innerWidth(),
            popupHeight = popup.innerHeight();

        if (window.innerWidth < x + popupWidth + cellWidth) {
            let left = x - popupWidth > 0 ? x - popupWidth : 10;
            popup.css('left', left + 'px');
        } else {
            popup.css('left', (x + cellWidth) + 'px');
        }

        let windowInnerHeight = modal_popup_$('#vi-wbe-editor').innerHeight();
        if (windowInnerHeight < y + popupHeight) {
            let h = y - popupHeight < 0 ? 0 : y - popupHeight;
            popup.css('top', h + 'px');
        } else {
            popup.css('top', y + 'px');
        }
    }

    hide() {
        this.popup.removeClass('vi-wbe-popup-active');
    }
}


;// ./src/calculator.js



const calculator_$ = jQuery;

class Calculator {
    constructor(obj, x, y, e) {
        this._data = {};
        this._data.jexcel = obj;
        this._data.x = parseInt(x);
        this._data.y = parseInt(y);
        this.run();
    }

    get(id) {
        return this._data[id] || ''
    }

    run() {
        let formulaHtml = this.content();
        let cell = calculator_$(`td[data-x=${this.get('x') || 0}][data-y=${this.get('y') || 0}]`);
        new Popup(formulaHtml, cell);
        formulaHtml.on('click', '.vi-wbe-apply-formula', this.applyFormula.bind(this));
        formulaHtml.on('change', '.vi-wbe-rounded', this.toggleDecimalValue);
    }

    content() {
        return calculator_$(`<div class="vi-wbe-formula-container" style="display: flex;">
                    <select class="vi-wbe-operator">
                        <option value="+">+</option>
                        <option value="-">-</option>
                    </select>
                    <input type="number" min="0" class="vi-wbe-value">
                    <select class="vi-wbe-unit">
                        <option value="fixed">n</option>
                        <option value="percentage">%</option>
                    </select>
                    <select class="vi-wbe-rounded">
                        <option value="no_round">${functions.text('No round')}</option>
                        <option value="round">${functions.text('Round with decimal')}</option>
                        <option value="round_up">${functions.text('Round up')}</option>
                        <option value="round_down">${functions.text('Round down')}</option>
                    </select>
                    <input type="number" min="0" max="10" class="vi-wbe-decimal" value="0">
                    <button type="button" class="vi-ui button mini vi-wbe-apply-formula">${functions.text('OK')}</button>
                </div>`);
    }

    applyFormula(e) {
        let form = calculator_$(e.target).closest('.vi-wbe-formula-container'),
            operator = form.find('.vi-wbe-operator').val(),
            fValue = parseFloat(form.find('.vi-wbe-value').val()),
            unit = form.find('.vi-wbe-unit').val(),
            rounded = form.find('.vi-wbe-rounded').val(),
            decimal = parseInt(form.find('.vi-wbe-decimal').val()),
            excelObj = this.get('jexcel');

        if (!fValue) return;

        let breakControl = false, records = [];
        let h = excelObj.selectedContainer;
        let start = h[1], end = h[3], x = h[0];

        function formula(oldValue) {
            oldValue = parseFloat(oldValue.toString().replace(',', '.'));

            let extraValue = unit === 'percentage' ? oldValue * fValue / 100 : fValue;
            let newValue = operator === '-' ? oldValue - extraValue : oldValue + extraValue;

            switch (rounded) {
                case 'round':
                    newValue = newValue.toFixed(decimal);
                    break;

                case 'round_up':
                    newValue = Math.ceil(newValue);
                    break;

                case 'round_down':
                    newValue = Math.floor(newValue);
                    break;
            }

            return newValue;
        }

        let current_field = excelObj.options?.columns[x]?.id, regular_field_index=null;
        if (current_field ==='sale_price') {
            for (let column of excelObj.options.columns) {
                if (column?.id && column.id === 'regular_price'){
                    regular_field_index = column.name;
                }
            }
        }
        for (let y = start; y <= end; y++) {
            if (excelObj.records[y][x] && !excelObj.records[y][x].classList.contains('readonly') && excelObj.records[y][x].style.display !== 'none' && breakControl === false) {
                let value = formula(excelObj.options.data[y][x] || 0);
                if (regular_field_index) {
                    let regularPrice = parseFloat((excelObj.options.data[y][regular_field_index]||0).toString().replace(',', '.'));
                    if (regularPrice < value) {
                        let message = {
                            title:excelObj.options.data[y][2],
                            message: 'Please ensure the sale price is less than the regular price',
                            type: "error sale-price-error-"+y,
                            duration: 3000000
                        };
                        functions.showMessage(message);
                        continue;
                    }
                }
                records.push(excelObj.updateCell(x, y, value));
                excelObj.updateFormulaChain(x, y, records);
            }
        }
        if (!records.length){
            return;
        }
        // Update history
        excelObj.setHistory({
            action: 'setValue',
            records: records,
            selection: excelObj.selectedCell,
        });

        // Update table with custom configuration if applicable
        excelObj.updateTable();
    }

    toggleDecimalValue() {
        let form = calculator_$(this).closest('.vi-wbe-formula-container');
        form.find('.vi-wbe-decimal').hide();
        if (calculator_$(this).val() === 'round') form.find('.vi-wbe-decimal').show();
    }
}

class CalculatorBaseOnRegularPrice {
    constructor(obj, x, y, e) {
        this._data = {};
        this._data.jexcel = obj;
        this._data.x = parseInt(x);
        this._data.y = parseInt(y);
        this.run();
    }

    get(id) {
        return this._data[id] || ''
    }

    run() {
        let formulaHtml = this.content();
        let cell = calculator_$(`td[data-x=${this.get('x') || 0}][data-y=${this.get('y') || 0}]`);
        new Popup(formulaHtml, cell);
        formulaHtml.on('click', '.vi-wbe-apply-formula', this.applyFormula.bind(this));
        formulaHtml.on('change', '.vi-wbe-rounded', this.toggleDecimalValue);
    }

    content() {
        return calculator_$(`<div class="vi-wbe-formula-container" style="display: flex;">
                    <span class="vi-wbe-operator vi-ui button basic small icon"><i class="icon minus"> </i></span>
                    <input type="number" min="0" class="vi-wbe-value">
                    <select class="vi-wbe-unit">
                        <option value="percentage">%</option>
                        <option value="fixed">n</option>
                    </select>
                    <select class="vi-wbe-rounded">
                        <option value="no_round">${functions.text('No round')}</option>
                        <option value="round">${functions.text('Round with decimal')}</option>
                        <option value="round_up">${functions.text('Round up')}</option>
                        <option value="round_down">${functions.text('Round down')}</option>
                    </select>
                    <input type="number" min="0" max="10" class="vi-wbe-decimal" value="0">
                    <button type="button" class="vi-ui button mini vi-wbe-apply-formula">${functions.text('OK')}</button>
                </div>`);
    }

    applyFormula(e) {
        let form = calculator_$(e.target).closest('.vi-wbe-formula-container'),
            fValue = parseFloat(form.find('.vi-wbe-value').val()),
            unit = form.find('.vi-wbe-unit').val(),
            rounded = form.find('.vi-wbe-rounded').val(),
            decimal = parseInt(form.find('.vi-wbe-decimal').val()),
            excelObj = this.get('jexcel');

        if (!fValue) return;

        let breakControl = false, records = [];
        let h = excelObj.selectedContainer;
        let start = h[1], end = h[3], x = h[0];

        function formula(regularPrice) {
            let extraValue = unit === 'percentage' ? regularPrice * fValue / 100 : fValue;
            let newValue = regularPrice - extraValue;
            newValue = newValue > 0 ? newValue : 0;

            switch (rounded) {
                case 'round':
                    newValue = newValue.toFixed(decimal);
                    break;
                case 'round_up':
                    newValue = Math.ceil(newValue);
                    break;
                case 'round_down':
                    newValue = Math.floor(newValue);
                    break;
            }

            return newValue;
        }

        for (let y = start; y <= end; y++) {
            if (excelObj.records[y][x] && !excelObj.records[y][x].classList.contains('readonly') && excelObj.records[y][x].style.display !== 'none' && breakControl === false) {
                let value = excelObj.options.data[y][x - 1] || 0;
                let regularPrice = parseFloat(value.replace(',', '.'));
                value = formula(regularPrice)
                if (regularPrice < value) {
                    let message = {
                        title:excelObj.options.data[y][2],
                        message: 'Please ensure the sale price is less than the regular price',
                        type: "error sale-price-error-"+y,
                        duration: 3000000
                    };
                    functions.showMessage(message);
                    continue;
                }
                records.push(excelObj.updateCell(x, y, value));
                excelObj.updateFormulaChain(x, y, records);
            }
        }

        // Update history
        excelObj.setHistory({
            action: 'setValue',
            records: records,
            selection: excelObj.selectedCell,
        });

        // Update table with custom configuration if applicable
        excelObj.updateTable();
    }

    toggleDecimalValue() {
        let form = calculator_$(this).closest('.vi-wbe-formula-container');
        form.find('.vi-wbe-decimal').hide();
        if (calculator_$(this).val() === 'round') form.find('.vi-wbe-decimal').show();
    }
}

class FillNumber {

    constructor(obj, x, y, e) {
        this._data = {};
        this._data.jexcel = obj;
        this._data.x = parseInt(x);
        this._data.y = parseInt(y);
        this.run();
    }

    get(id) {
        return this._data[id] || ''
    }

    run() {
        let formulaHtml = this.content();
        let cell = calculator_$(`td[data-x=${this.get('x') || 0}][data-y=${this.get('y') || 0}]`);
        new Popup(formulaHtml, cell);
        formulaHtml.on('click', '.vi-wbe-apply-formula', this.applyFormula.bind(this));
    }

    content() {
        return calculator_$(`<div class="vi-wbe-formula-container" style="display: flex; align-items: center; gap: 10px;">
                    <label for="">${functions.text('From')}</label>
                    <input type="number" min="0" max="10" class="vi-wbe-fill-from" value="0">
                    <label for="">${functions.text('Step')}</label>
                    <input type="number" min="0" max="10" class="vi-wbe-fill-step" value="1">
                    <button type="button" class="vi-ui button mini vi-wbe-apply-formula">${functions.text('Fill')}</button>
                </div>`);
    }

    applyFormula(e) {
        let form = calculator_$(e.target).closest('.vi-wbe-formula-container'),
            from = parseFloat(form.find('.vi-wbe-fill-from').val()),
            step = parseFloat(form.find('.vi-wbe-fill-step').val()),
            i = 0,
            excelObj = this.get('jexcel');

        // if (!fValue) return;

        let breakControl = false, records = [];
        let h = excelObj.selectedContainer;
        let start = h[1], end = h[3], x = h[0];

        let current_field = excelObj.options?.columns[x]?.id, regular_field_index;
        if (current_field ==='sale_price') {
            for (let column of excelObj.options.columns) {
                if (column?.id && column.id === 'regular_price'){
                    regular_field_index = column.name;
                }
            }
        }
        for (let y = start; y <= end; y++) {
            if (excelObj.records[y][x] && !excelObj.records[y][x].classList.contains('readonly') && excelObj.records[y][x].style.display !== 'none' && breakControl === false) {
                let value = from + step * i;
                if (regular_field_index) {
                    let regularPrice = parseFloat((excelObj.options.data[y][regular_field_index]||0).toString().replace(',', '.'));
                    if (regularPrice < value) {
                        let message = {
                            title:excelObj.options.data[y][2],
                            message: 'Please ensure the sale price is less than the regular price',
                            type: "error sale-price-error-"+y,
                            duration: 3000000
                        };
                        functions.showMessage(message);
                        continue;
                    }
                }
                records.push(excelObj.updateCell(x, y, value.toString()));
                excelObj.updateFormulaChain(x, y, records);
                i++;
            }
        }

        // Update history
        excelObj.setHistory({
            action: 'setValue',
            records: records,
            selection: excelObj.selectedCell,
        });

        // Update table with custom configuration if applicable
        excelObj.updateTable();
    }
}

// export default Calculator;
;// ./src/sidebar.js



const sidebar_$ = jQuery;

const Sidebar = {
    init() {
        sidebar_$('.vi-ui.menu .item').vi_tab();
        sidebar_$('.bulky-sort-fields-accordion').vi_accordion();
        sidebar_$('#bulky-sort-fields').sortable({axis: "y", containment: "parent"});

        this.revision = {};
        this.sidebar = sidebar_$('#vi-wbe-sidebar');
        this.historyBodyTable = sidebar_$('#vi-wbe-history-points-list tbody');

        this.sidebar.on('click', '.vi-wbe-apply-filter', this.applyFilter.bind(this));
        this.sidebar.on('click', '.vi-wbe-filter-label', this.filterInputLabelFocus);
        this.sidebar.on('focus', '.vi-wbe-filter-input', this.filterInputFocus);
        this.sidebar.on('blur', '.vi-wbe-filter-input', this.filterInputBlur);
        this.sidebar.on('click', '.vi-wbe-get-meta-fields', this.getMetaFields.bind(this));
        this.sidebar.on('click', '.vi-wbe-save-meta-fields:not(.loading)', this.saveMetaFields.bind(this));
        this.sidebar.on('click', '.vi-wbe-add-new-meta-field', this.addNewMetaField.bind(this));
        this.sidebar.find('table.vi-wbe-meta-fields-container tbody').sortable({axis: 'y',});
        this.sidebar.find('table.vi-wbe-meta-fields-container').on('click', '.vi-wbe-remove-meta-row', this.removeMetaRow);

        this.sidebar.on('click', '.vi-wbe-save-taxonomy-fields:not(.loading)', this.saveTaxonomyFields);

        this.sidebar.on('click', '.vi-wbe-save-settings', this.saveSettings.bind(this));

        this.sidebar.on('click', '.vi-wbe-view-history-point', this.viewHistoryPoint.bind(this));
        this.sidebar.on('click', '.vi-wbe-recover', this.recover.bind(this));
        this.sidebar.on('click', '.vi-wbe-revert-this-point', this.revertAllProducts.bind(this));
        this.sidebar.on('click', '.vi-wbe-revert-this-key', this.revertProductAttribute.bind(this));
        this.sidebar.on('click', '.vi-wbe-pagination a.item', this.changePage.bind(this));
        this.sidebar.on('change', '.vi-wbe-go-to-page', this.changePageByInput.bind(this));
        this.sidebar.on('click', '.vi-wbe-multi-select-clear', this.clearMultiSelect);

        this.sidebar.on('change', '.vi-wbe-meta-column-type', this.metaFieldChangeType);
        this.sidebar.on('keyup', '.vi-wbe-search-metakey', this.searchMetaKey);
        this.filter();
        this.settings();
        this.metafields();
        this.history();

        return this.sidebar;
    },

    filter() {
        let sidebar= this,
            filterForm = sidebar_$('#vi-wbe-products-filter'),
            filterInput = sidebar_$('.vi-wbe-filter-input'),
            cssTop = {top: -2},
            cssMiddle = {top: '50%'};
        filterInput.each((i, el) => {
            if (sidebar_$(el).val()) sidebar_$(el).parent().prev().css(cssTop);
        });

        filterInput.on('focus', function () {
            let label = sidebar_$(this).prev();
            label.css(cssTop);
            sidebar_$(this).on('blur', function () {
                if (!sidebar_$(this).val()) label.css(cssMiddle);
            })
        });

        this.sidebar.on('click', '.vi-wbe-filter-label', function () {
            sidebar_$(this).next().trigger('focus');
        });

        let compactFilter = filterForm.find('.vi-ui.compact.dropdown').dropdown();
        filterForm.find('.vi-wbe.vi-ui.dropdown:not(.vi-wbe-filter-select2)').dropdown({clearable: true, fullTextSearch: true});
        filterForm.find('.vi-wbe.vi-ui.dropdown.vi-wbe-filter-select2').each(function (k,v){
            let tmp_placeholder = sidebar_$(v).data('placeholder'),taxonomy_type = sidebar_$(v).attr('id').replace('vi-wbe-','');
            sidebar_$(v).select2({
                placeholder: tmp_placeholder,
                closeOnSelect: false,
                multiple: true,
                minimumInputLength: 2,
                ajax: {
                    url: wbeParams.ajaxUrl,
                    dataType: 'json',
                    type: "POST",
                    quietMillis: 50,
                    delay: 250,
                    data: function (params) {
                        return {
                            action: 'vi_wbe_ajax',
                            vi_wbe_nonce: wbeParams.nonce,
                            sub_action: 'search_taxonomy',
                            taxonomy_type: taxonomy_type,
                            search: params.term
                        };
                    },
                    processResults: function (data) {
                        return {results: data};
                    },
                },
            });
        });

        filterForm.find('.vi-wbe-search-select2:not(.vi-wbe-search-select2-init)').each(function () {
            let select = sidebar_$(this);
            let close_on_select = !select.prop('multiple'), min_input = 2, placeholder = 'select', data_send={}, type_select2 = select.data('type_select2');
            switch (type_select2) {
                case 'category':
                    placeholder = 'Please enter category name to search';
                    data_send = {
                        action: 'woocommerce_json_search_categories',
                        show_empty: true,
                        security: wc_enhanced_select_params.search_categories_nonce,
                    }
                    break;
                case 'tag':
                    placeholder = 'Please enter tag to search';
                    data_send = {
                        action: 'woocommerce_json_search_taxonomy_terms',
                        security: wc_enhanced_select_params.search_taxonomy_terms_nonce,
                        limit: 50,
                        taxonomy: 'product_tag',
                    }
                    break;
                case 'product':
                    placeholder = 'Please enter product title to search';
                    data_send = {
                        action: 'woocommerce_json_search_products_and_variations',
                        security: wc_enhanced_select_params.search_products_nonce,
                    }
                    break;
                default:
                    data_send={not_ajax: 1, data:[], select:select};
                    select.find('option').each( function (k,v){
                        let remove = true;
                        data_send.data.push({
                            id: sidebar_$(v).val(),
                            text: sidebar_$(v).text()
                        });
                        if (sidebar_$(v).prop('selected')){
                            remove = false;
                        }
                        if (remove){
                            sidebar_$(v).remove();
                        }
                    });
            }
            select.addClass('vi-wbe-search-select2-init').select2(sidebar.select2_params(placeholder, data_send, close_on_select, min_input));
        });
        this.sidebar.on('click', '.vi-wbe-clear-filter', function () {
            sidebar_$('.vi-wbe-filter-label').css(cssMiddle);
            filterInput.val('');
            filterForm.find('div.vi-wbe.vi-ui.dropdown').dropdown('clear');
            filterForm.find('select.vi-wbe.vi-ui.dropdown').val(null).trigger('change');
            compactFilter.find('.menu .item:first').trigger('click');
            filterForm.find('.vi-wbe-search-select2').val(null).trigger('change');
        });

        this.sidebar.on('change', '#vi-wbe-has_expire_date', function () {
            let expireDateGroup = sidebar_$('.vi-wbe-expire-date-group');
            sidebar_$(this).val() === 'yes' ? expireDateGroup.show() : expireDateGroup.hide();
        });

        this.sidebar.find('#vi-wbe-has_expire_date').trigger('change')
    },
    select2_params(placeholder, data_send, close_on_select, min_input) {
        let result = {
            width: '100%',
            closeOnSelect: close_on_select,
            placeholder: placeholder,
            allowClear: true,
            cache: true
        };
        if (Object.keys(data_send).length) {
            result['minimumInputLength'] = min_input;
            result['escapeMarkup'] = function (markup) {
                return markup;
            };
            if (data_send?.not_ajax){
                let data_check = data_send.data,$select=sidebar_$(data_send.select),old_term, found={};
                $select.on('select2:open', function() {
                    let $search = sidebar_$('.select2-search__field'), val = $select.val();
                    $search.off('input blur').on('input blur', function() {
                        let term = sidebar_$(this).val().trim().toLowerCase(), terms=[];
                        if (term.length < min_input || old_term === term ){
                            return;
                        }
                        old_term = term;
                        if (found[term]){
                            terms = found[term];
                        }else {
                            terms = data_check.filter(item =>
                                item.text.toLowerCase().includes(term)
                            );
                            found[term] = terms;
                        }
                        $select.find('option:not([value="'+val+'"])').remove();
                        terms.forEach(item => {
                            if (val != item.id) {
                                $select.append(new Option(item.text, item.id, false, false));
                            }
                        });
                        $select.trigger('change.select2');
                    });
                });
            }else {
                result['ajax'] = {
                    url: wbeParams.ajaxUrl,
                    dataType: 'json',
                    type: "GET",
                    quietMillis: 50,
                    delay: 250,
                    data: function (params) {
                        let data = sidebar_$.extend(data_send, {
                            term: params.term,
                        });
                        return data;
                    },
                    processResults: function (data) {
                        let terms = [];
                        if (data) {
                            sidebar_$.each(data, function (id, text) {
                                terms.push({
                                    id:  text?.term_id||id,
                                    text: text?.formatted_name || text?.name || text
                                });
                            });
                        }
                        return {results: terms};
                    },
                    cache: true
                };
            }
        }
        return result;
    },

    settings() {
        let settingsForm = sidebar_$('.vi-wbe-settings-tab');
        settingsForm.find('select.dropdown').dropdown({fullTextSearch: true});
    },

    metafields() {
        this.renderMetaFieldsTable(Attributes.metaFields);
    },

    history() {
        this.pagination(1);
        // this.saveRevision();
    },

    pagination(currentPage, maxPage = Attributes.historyPages) {
        this.sidebar.find('.vi-wbe-pagination').html(functions.pagination(maxPage, currentPage));
    },

    applyFilter(e) {
        let $this = this, thisBtn = sidebar_$(e.target);

        if (thisBtn.hasClass('loading')) return;

        functions.ajax({
            data: {
                sub_action: 'add_filter_data',
                filter_data: sidebar_$('#vi-wbe-products-filter').serialize(),
                filter_key: Attributes.filterKey
            },
            beforeSend() {
                thisBtn.addClass('loading');
            },
            success(res) {
                $this.sidebar.trigger('afterAddFilter', [res.data]);
                functions.showMessage( {title:"Success", message: 'Filtered successfully', type: "positive", duration: 3000} );
            },
            error(res) {
                console.log(res);
                alert(res.statusText + res.responseText);
            },
            complete() {
                thisBtn.removeClass('loading');
            }
        });
    },

    limitProductPerPage() {
        let value = sidebar_$(this).val();
        if (value > 50) sidebar_$(this).val(50);
        if (value < 0) sidebar_$(this).val(0);
    },

    saveSettings(e) {
        let $this = this, thisBtn = sidebar_$(e.target);

        if (thisBtn.hasClass('loading')) return;

        functions.ajax({
            data: {
                sub_action: 'save_settings',
                fields: sidebar_$('form.vi-wbe-settings-tab').serialize()
            },
            beforeSend() {
                thisBtn.addClass('loading')
            },
            success(res) {
                if (res.success) {
                    Attributes.settings = res.data.settings;
                    // clearInterval($this.autoSaveRevision);
                    // $this.saveRevision();
                    $this.sidebar.trigger('afterSaveSettings', [res.data]);
                }
            },
            error(res) {
                console.log(res);
                alert(res.statusText + res.responseText);
            },
            complete() {
                thisBtn.removeClass('loading')
            }
        });
    },

    filterInputLabelFocus() {
        sidebar_$(this).next().find('input').trigger('focus');
    },

    filterInputFocus() {
        sidebar_$(this).parent().prev().css({top: -2});
    },

    filterInputBlur() {
        if (!sidebar_$(this).val()) sidebar_$(this).parent().prev().css({top: '50%'});
    },

    getMetaFields(e) {
        let $this = this, thisBtn = sidebar_$(e.target);

        if (thisBtn.hasClass('loading')) return;

        functions.ajax({
            data: {sub_action: 'get_meta_fields', current_meta_fields: $this.getCurrentMetaFields()},
            beforeSend() {
                thisBtn.addClass('loading');
            },
            success(res) {
                $this.renderMetaFieldsTable(res.data);
                Attributes.metaFields = res.data;
            },
            error(res) {
                console.log(res);
                alert(res.statusText + res.responseText);
            },
            complete() {
                thisBtn.removeClass('loading');
            }
        });
    },

    renderMetaFieldsTable(data) {
        let html = '';

        for (let metaKey in data) {
            html += this.renderRow(metaKey, data);
        }

        sidebar_$('.vi-wbe-meta-fields-container tbody').html(html);
    },

    renderRow(metaKey, data) {
        let meta = data[metaKey] || {},
            optionHtml = '',
            inputType = meta.input_type || '',
            options = {
                textinput: 'Text input',
                texteditor: 'Text editor',
                numberinput: 'Number input',
                array: 'Array',
                json: 'JSON',
                checkbox: 'Checkbox',
                calendar: 'Calendar',
                image: 'Image',
                select: 'Select',
                multiselect: 'Multiselect',
                gallery: 'Gallery',
            },
            metaValue = meta.meta_value || '',
            shortValue = metaValue.slice(0, 15),
            fullValueHtml = metaValue.length > 16 ? `<div class="vi-wbe-full-meta-value">${metaValue}</div>` : '',
            selectSource = '';

        for (let optionValue in options) {
            optionHtml += `<option value="${optionValue}" ${optionValue === inputType ? 'selected' : ''}>${options[optionValue]}</option>`;
        }

        shortValue += shortValue.length < metaValue.length ? '...' : '';

        if (inputType === 'select' || inputType === 'multiselect') {
            selectSource += `<textarea class="vi-wbe-select-options">${meta.select_options}</textarea>`
        }

        return `<tr>
                    <td class="vi-wbe-meta-key">${metaKey}</td>
                    <td><input type="text" class="vi-wbe-meta-column-name" value="${meta.column_name || ''}"></td>
                    <td>
                        <div class="vi-wbe-display-meta-value">
                            <div class="vi-wbe-short-meta-value">${shortValue}</div>
                            ${fullValueHtml}
                        </div>
                    </td>
                    <td>
                        <select class="vi-wbe-meta-column-type">${optionHtml}</select>
                        ${selectSource}
                    </td>
                    <td class="vi-wbe-meta-field-active-column">
                        <div class="vi-ui toggle checkbox">
                          <input type="checkbox" class="vi-wbe-meta-column-active" ${parseInt(meta.active) ? 'checked' : ''}>
                          <label> </label>
                        </div>  
                    </td>
                    <td>
                        <div class="vi-wbe-meta-field-actions">
                            <span class="vi-ui button basic mini vi-wbe-remove-meta-row"><i class="icon trash"> </i></span>
                            <span class="vi-ui button basic mini"><i class="icon move"> </i></span>
                        </div>
                    </td>
                </tr>`;
    },

    metaFieldChangeType() {
        let selectTypeOptions = sidebar_$('<textarea class="vi-wbe-select-options"></textarea>');
        let val = sidebar_$(this).val();
        let siblings = sidebar_$(this).siblings();
        if (val === 'select' || val === 'multiselect') {
            if (!siblings.length) sidebar_$(this).after(selectTypeOptions);
        } else {
            siblings.remove();
        }
    },

    searchMetaKey() {
        let filter = sidebar_$(this).val().toLowerCase();
        sidebar_$('.vi-wbe-meta-fields-container tbody tr').each(function (i, tr) {
            let metaKey = sidebar_$(tr).find('.vi-wbe-meta-key').text().trim().toLowerCase();
            if (metaKey.indexOf(filter) > -1) {
                sidebar_$(tr).show();
            } else {
                sidebar_$(tr).hide();
            }
        });
    },

    saveMetaFields(e) {
        let thisBtn = sidebar_$(e.target);

        if (thisBtn.hasClass('loading')) return;

        functions.ajax({
            data: {sub_action: 'save_meta_fields', meta_fields: this.getCurrentMetaFields()},
            beforeSend() {
                thisBtn.addClass('loading');
            },
            success(res) {
                if ( false === res.success ) {
                    alert( res.data.message );
                }else {
                    location.reload();
                }
            },
            error(res) {
                console.log(res);
                alert(res.statusText + res.responseText);
            },
            complete() {
                thisBtn.removeClass('loading');
            }
        });
    },

    getCurrentMetaFields() {
        let meta_fields = {};
        let metaArr = Attributes.metaFields;
        sidebar_$('table.vi-wbe-meta-fields-container tbody tr').each(function (i, row) {
            let metaKey = sidebar_$(row).find('.vi-wbe-meta-key').text();
            meta_fields[metaKey] = {
                column_name: sidebar_$(row).find('.vi-wbe-meta-column-name').val(),
                input_type: sidebar_$(row).find('.vi-wbe-meta-column-type').val(),
                active: sidebar_$(row).find('.vi-wbe-meta-column-active:checked').length,
                meta_value: metaArr[metaKey] ? metaArr[metaKey].meta_value : '',
                select_options: sidebar_$(row).find('.vi-wbe-select-options').val(),
            };
        });

        return meta_fields;
    },

    addNewMetaField(e) {
        let input = sidebar_$(e.currentTarget).prev(),
            metaKey = input.val(),
            validate = metaKey.match(/^[\w\d_-]*$/g);

        if (!metaKey || !validate || Attributes.metaFields[metaKey]) return;

        let newRow = this.renderRow(metaKey, {});
        if (newRow) {
            input.val('');
            sidebar_$('table.vi-wbe-meta-fields-container tbody').append(newRow);
        }
    },

    removeMetaRow() {
        sidebar_$(this).closest('tr').remove();
    },

    saveTaxonomyFields(e) {
        let thisBtn = sidebar_$(e.target);
        let taxonomyFields = [];

        sidebar_$('table.vi-wbe-taxonomy-fields .vi-wbe-taxonomy-active:checked').each(function (i, row) {
            let taxKey = sidebar_$(this).closest('tr').find('.vi-wbe-taxonomy-key').text();
            taxonomyFields.push(taxKey);
        });

        functions.ajax({
            data: {sub_action: 'save_taxonomy_fields', taxonomy_fields: taxonomyFields},
            beforeSend() {
                thisBtn.addClass('loading');
            },
            success(res) {
                thisBtn.removeClass('loading');
                location.reload();
            },
            error(res) {
                console.log(res);
                alert(res.statusText + res.responseText);
            },
            complete() {
                thisBtn.removeClass('loading');
            }
        });

    },

    viewHistoryPoint(e) {
        let thisBtn = sidebar_$(e.currentTarget),
            historyiD = thisBtn.data('id'),
            $this = this;

        if (thisBtn.hasClass('loading')) return;

        functions.ajax({
            data: {sub_action: 'view_history_point', id: historyiD},
            beforeSend() {
                thisBtn.addClass('loading');
            },
            success(res) {
                if (res.success && res.data) {
                    let products = res.data.compare;
                    let html = '';
                    for (let id in products) {
                        let item = products[id];
                        html += `<div class="vi-wbe-history-product" data-product_id="${id}">
                                        <div class="title">
                                            <i class="dropdown icon"></i>
                                            ${item.name}
                                            <span class="vi-ui button mini basic vi-wbe-revert-this-product">
                                                <i class="icon undo"> </i>
                                            </span>
                                            
                                        </div>`;

                        let table = '';
                        for (let key in item.fields) {
                            let currentVal = typeof item.current[key] === 'string' ? item.current[key] : JSON.stringify(item.current[key]);
                            let historyVal = typeof item.history[key] === 'string' ? item.history[key] : JSON.stringify(item.history[key]);
                            table += `<tr>
                                            <td>${item.fields[key]}</td>
                                            <td>${currentVal}</td>
                                            <td>${historyVal}</td>
                                            <td class="">
                                                <span class="vi-ui button basic mini vi-wbe-revert-this-key" data-product_id="${id}" data-product_key="${key}">
                                                    <i class="icon undo"> </i>
                                                </span>
                                            </td>
                                        </tr>`;
                        }

                        table = `<table id="vi-wbe-history-point-detail" class="vi-ui celled table">
                                    <thead>
                                    <tr>
                                        <th>Attribute</th>
                                        <th>Current</th>
                                        <th>History</th>
                                        <th class="">Revert</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    ${table}
                                    </tbody>
                                </table>`;

                        html += `<div class="content">${table}</div></div>`
                    }

                    html = sidebar_$(`<div class="vi-ui styled fluid accordion">${html}</div>`);

                    sidebar_$('.vi-wbe-history-review')
                        .html(html).attr('data-history_id', historyiD)
                        .prepend(`<h4>History point: ${res.data.date}</h4>`)
                        .append(`<div class="vi-ui button tiny vi-wbe-revert-this-point">
                                    ${functions.text('Revert all product in this point')}
                                </div>
                                <p> ${functions.text('The current value is the value of the records in database')}</p>`);

                    html.find('.title').on('click', (e) => $this.revertSingleProduct(e));

                    html.vi_accordion();
                    html.find('.title:first').trigger('click');
                }
            },
            error(res) {
                console.log(res);
                alert(res.statusText + res.responseText);
            },
            complete() {
                thisBtn.removeClass('loading');
            }
        })
    },

    recover(e) {
        let thisBtn = sidebar_$(e.currentTarget),
            historyID = thisBtn.data('id');

        if (thisBtn.hasClass('loading')) return;

        functions.ajax({
            data: {sub_action: 'revert_history_all_products', history_id: historyID},
            beforeSend() {
                thisBtn.addClass('loading')
            },
            success(res) {
                console.log(res);
                functions.showMessage( {title:"Success", message: 'Reverted successfully', type: "positive", duration: 3000} );
            },
            error(res) {
                console.log(res);
                alert(res.statusText + res.responseText);
            },
            complete() {
                thisBtn.removeClass('loading');
            }
        });
    },

    revertSingleProduct(e) {
        let thisBtn;
        if (sidebar_$(e.target).hasClass('vi-wbe-revert-this-product')) thisBtn = sidebar_$(e.target);
        if (sidebar_$(e.target).parent().hasClass('vi-wbe-revert-this-product')) thisBtn = sidebar_$(e.target).parent();

        if (thisBtn) {
            e.stopImmediatePropagation();

            let pid = thisBtn.closest('.vi-wbe-history-product').data('product_id'),
                historyID = thisBtn.closest('.vi-wbe-history-review').data('history_id');

            if (thisBtn.hasClass('loading')) return;

            functions.ajax({
                data: {sub_action: 'revert_history_single_product', history_id: historyID, pid: pid},
                beforeSend() {
                    thisBtn.addClass('loading')
                },
                success(res) {
                    console.log(res);
                    functions.showMessage( {title:"Success", message: 'Reverted successfully', type: "positive", duration: 3000} );
                },
                error(res) {
                    console.log(res);
                    alert(res.statusText + res.responseText);
                },
                complete() {
                    thisBtn.removeClass('loading');
                }
            });
        }
    },

    revertAllProducts(e) {
        let thisBtn = sidebar_$(e.target);
        let historyID = thisBtn.closest('.vi-wbe-history-review').data('history_id');

        if (thisBtn.hasClass('loading')) return;

        functions.ajax({
            data: {sub_action: 'revert_history_all_products', history_id: historyID},
            beforeSend() {
                thisBtn.addClass('loading')
            },
            success(res) {
                console.log(res);
                functions.showMessage( {title:"Success", message: 'Reverted successfully', type: "positive", duration: 3000} );
            },
            error(res) {
                console.log(res);
                alert(res.statusText + res.responseText);
            },
            complete() {
                thisBtn.removeClass('loading');
            }
        });
    },

    revertProductAttribute(e) {
        let thisBtn = sidebar_$(e.currentTarget),
            attribute = thisBtn.data('product_key'),
            pid = thisBtn.closest('.vi-wbe-history-product').data('product_id'),
            historyID = thisBtn.closest('.vi-wbe-history-review').data('history_id');

        if (thisBtn.hasClass('loading')) return;

        functions.ajax({
            data: {sub_action: 'revert_history_product_attribute', attribute: attribute, history_id: historyID, pid: pid},
            beforeSend() {
                thisBtn.addClass('loading')
            },
            success(res) {
                console.log(res);
                functions.showMessage( {title:"Success", message: 'Reverted Attribute successfully', type: "positive", duration: 3000} );
            },
            error(res) {
                console.log(res);
                alert(res.statusText + res.responseText);
            },
            complete() {
                thisBtn.removeClass('loading');
            }
        });
    },

    changePage(e) {
        let page = parseInt(sidebar_$(e.currentTarget).attr('data-page'));
        if (sidebar_$(e.currentTarget).hasClass('active') || sidebar_$(e.currentTarget).hasClass('disabled') || !page) return;
        this.loadHistoryPage(page);
    },

    changePageByInput(e) {
        let page = parseInt(sidebar_$(e.target).val());
        let max = parseInt(sidebar_$(e.target).attr('max'));

        if (page <= max && page > 0) this.loadHistoryPage(page);
    },

    clearMultiSelect() {
        sidebar_$(this).parent().find('.vi-ui.dropdown').dropdown('clear');
    },

    loadHistoryPage(page) {
        let loading = functions.spinner(),
            $this = this;

        if (page) {
            functions.ajax({
                dataType: 'text',
                data: {sub_action: 'load_history_page', page: page},
                beforeSend() {
                    $this.sidebar.find('.vi-wbe-pagination').prepend(loading);
                },
                success(res) {
                    $this.pagination(page);
                    sidebar_$('#vi-wbe-history-points-list tbody').html(res);
                },
                error(res) {
                    console.log(res);
                    alert(res.statusText + res.responseText);
                },
                complete() {
                    loading.remove();
                }
            });
        }
    },

    // saveRevision() {
    //     let autoSaveTime = parseInt(Attributes.settings.auto_save_revision);
    //     if (autoSaveTime === 0) return;
    //     let $this = this;
    //
    //     this.autoSaveRevision = setInterval(function () {
    //         if (Object.keys($this.revision).length) {
    //             let currentPage = $this.sidebar.find('.vi-wbe-pagination a.item.active').data('page') || 1;
    //             _f.ajax({
    //                 data: {sub_action: 'auto_save_revision', data: $this.revision, page: currentPage || 1},
    //                 success(res) {
    //                     if (res.success) {
    //                         if (res.data.pages) Attributes.historyPages = res.data.pages;
    //                         if (res.data.updatePage) $this.historyBodyTable.html(res.data.updatePage);
    //                         $this.revision = {};
    //                         $this.pagination(currentPage);
    //                     }
    //                 }
    //             });
    //         }
    //
    //     }, autoSaveTime * 1000)
    // }
};

;// ./src/find-and-replace.js



const find_and_replace_$ = jQuery;
class FindAndReplace {
    constructor(obj, x, y, e) {
        this._data = {};
        this._data.jexcel = obj;
        this._data.x = parseInt(x);
        this._data.y = parseInt(y);
        this.run();
    }

    get(id) {
        return this._data[id] || '';
    }

    run() {
        let formulaHtml = this.content();
        let cell = find_and_replace_$(`td[data-x=${this.get('x') || 0}][data-y=${this.get('y') || 0}]`);
        new Popup(formulaHtml, cell);
        formulaHtml.on('click', '.vi-wbe-apply-formula', this.applyFormula.bind(this));
    }

    content() {
        return find_and_replace_$(`<div class="vi-wbe-formula-container">
                    <div class="field">
                        <input type="text" placeholder="${functions.text('Find')}" class="vi-wbe-find-string">
                    </div>
                    <div class="field">
                        <input type="text" placeholder="${functions.text('Replace')}" class="vi-wbe-replace-string">
                    </div>
                    <button type="button" class="vi-ui button mini vi-wbe-apply-formula">${functions.text('Replace')}</button>
                </div>`);
    }

    applyFormula(e) {
        let form = find_and_replace_$(e.target).closest('.vi-wbe-formula-container'),
            findString = form.find('.vi-wbe-find-string').val(),
            replaceString = form.find('.vi-wbe-replace-string').val(),
            excelObj = this.get('jexcel');

        if (!findString) return;

        let breakControl = false, records = [];
        let h = excelObj.selectedContainer;
        let start = h[1], end = h[3], x = h[0];

        for (let y = start; y <= end; y++) {
            if (excelObj.records[y][x] && !excelObj.records[y][x].classList.contains('readonly') && excelObj.records[y][x].style.display !== 'none' && breakControl === false) {
                let value = excelObj.options.data[y][x];
                let newValue = value.replaceAll(findString, replaceString);
                records.push(excelObj.updateCell(x, y, newValue));
                excelObj.updateFormulaChain(x, y, records);
            }
        }

        // Update history
        excelObj.setHistory({
            action: 'setValue',
            records: records,
            selection: excelObj.selectedCell,
        });

        // Update table with custom configuration if applicable
        excelObj.updateTable();
    }

}
;// ./src/text-multi-cells-edit.js



const text_multi_cells_edit_$ = jQuery;

class TextMultiCellsEdit {
    constructor(obj, x, y, e, wordWrap) {
        this._data = {};
        this._data.jexcel = obj;
        this._data.x = parseInt(x);
        this._data.y = parseInt(y);
        this._wordWrap = wordWrap;
        this.run();
    }

    get(id) {
        return this._data[id] || '';
    }

    run() {
        let formulaHtml = this.content();
        let cell = text_multi_cells_edit_$(`td[data-x=${this.get('x') || 0}][data-y=${this.get('y') || 0}]`);
        new Popup(formulaHtml, cell);
        formulaHtml.on('click', '.vi-wbe-apply-formula', this.applyFormula.bind(this));
        // formulaHtml.on('change', '.vi-wbe-text-input', this.applyFormula.bind(this));
    }

    content() {
        let input = this._wordWrap ? `<textarea class="vi-wbe-text-input" rows="3"></textarea>` : `<input type="text" placeholder="${functions.text('Content')}" class="vi-wbe-text-input">`;
        return text_multi_cells_edit_$(`<div class="vi-wbe-formula-container">
                    <div class="field">
                        ${input}
                    </div>
                    <button type="button" class="vi-ui button mini vi-wbe-apply-formula">${functions.text('Save')}</button>
                </div>`);
    }

    applyFormula(e) {
        let form = text_multi_cells_edit_$(e.target).closest('.vi-wbe-formula-container'),
            value = form.find('.vi-wbe-text-input').val(),
            excelObj = this.get('jexcel');

        let breakControl = false, records = [];
        let h = excelObj.selectedContainer;
        let start = h[1], end = h[3], x = h[0];

        for (let y = start; y <= end; y++) {
            if (excelObj.records[y][x] && !excelObj.records[y][x].classList.contains('readonly') && excelObj.records[y][x].style.display !== 'none' && breakControl === false) {
                records.push(excelObj.updateCell(x, y, value));
                excelObj.updateFormulaChain(x, y, records);
            }
        }

        // Update history
        excelObj.setHistory({
            action: 'setValue',
            records: records,
            selection: excelObj.selectedCell,
        });

        // Update table with custom configuration if applicable
        excelObj.updateTable();
    }

}
;// ./src/find-and-replace-tags.js




const find_and_replace_tags_$ = jQuery;
class FindAndReplaceTags {
    constructor(obj, cells, x, y, e) {
        this.cells = cells;
        this.obj = obj;
        this.x = parseInt(x);
        this.y = parseInt(y);
        this.searchData = [];

        this.run();
    }

    run() {
        let $this = this;
        let formulaHtml = this.content();
        let y1 = this.cells[1], y2 = this.cells[3];
        let selectData = [{id: '', text: ''}];
        for (let i = y1; i <= y2; i++) {
            let value = this.obj.options.data[i][this.x];
            selectData.push(...value);
        }

        selectData = selectData.filter((item, index, self) =>
            index === self.findIndex((t) => (
                t.id === item.id && t.text === item.text
            ))
        );

        let cell = find_and_replace_tags_$(`td[data-x=${this.x || 0}][data-y=${this.y || 0}]`);
        new Popup(formulaHtml, cell);

        formulaHtml.find('.vi-wbe-find-string').select2({
            data: selectData
        });

        formulaHtml.find('.vi-wbe-replace-string').select2({
            multiple: false,
            minimumInputLength: 3,
            ajax: {
                url: Attributes.ajaxUrl,
                type: 'post',
                data: function (params) {
                    return {
                        ...Attributes.ajaxData,
                        sub_action: 'search_tags',
                        search: params.term,
                        type: 'public'
                    };
                },
                processResults: function (data) {
                    $this.searchData = data;
                    return {results: data};
                }
            }
        });

        formulaHtml.on('click', '.vi-wbe-apply-formula', this.applyFormula.bind(this));
    }

    content() {
        return find_and_replace_tags_$(`<div class="vi-wbe-formula-container">
                    <div class="field">
                        <div>${functions.text('Find')}</div>
                        <select placeholder="" class="vi-wbe-find-string"> </select>
                    </div>
                    <div class="field">
                        <div>${functions.text('Replace')}</div>
                        <select placeholder="" class="vi-wbe-replace-string"> </select>
                    </div>
                    <button type="button" class="vi-ui button mini vi-wbe-apply-formula">${functions.text('Replace')}</button>
                    <p>If 'Find' value is empty, add to selected cells with 'Replace' value.</p>
                    <p>If 'Replace' value is empty, remove from selected cells with 'Find' value.</p>
                </div>`);
    }

    applyFormula(e) {
        let form = find_and_replace_tags_$(e.target).closest('.vi-wbe-formula-container'),
            findString = form.find('.vi-wbe-find-string').val(),
            replaceString = form.find('.vi-wbe-replace-string').val(),
            excelObj = this.obj;

        if (!findString && !replaceString) return;

        let replace = this.searchData.filter((item) => item.id === +replaceString);

        let breakControl = false, records = [];
        let h = this.cells;
        let start = h[1], end = h[3], x = h[0];

        for (let y = start; y <= end; y++) {
            if (excelObj.records[y][x] && !excelObj.records[y][x].classList.contains('readonly') && excelObj.records[y][x].style.display !== 'none' && breakControl === false) {
                let value = excelObj.options.data[y][x];
                if (!value) value = [];
                let newValue = value.filter((item) => item.id !== +findString);

                if (value.length !== newValue.length || !findString) {
                    newValue.push(...replace);
                }

                newValue = newValue.filter((item, index, self) =>
                    index === self.findIndex((t) => (t.id === item.id && t.text === item.text))
                );

                records.push(excelObj.updateCell(x, y, newValue));
                excelObj.updateFormulaChain(x, y, records);
            }
        }

        // Update history
        excelObj.setHistory({
            action: 'setValue',
            records: records,
            selection: excelObj.selectedCell,
        });

        // Update table with custom configuration if applicable
        excelObj.updateTable();
    }

}
;// ./src/find-and-replace-options.js



const find_and_replace_options_$ = jQuery;
class FindAndReplaceOptions {
    constructor(obj, cells, x, y, e) {
        this.cells = cells;
        this.obj = obj;
        this.x = parseInt(x);
        this.y = parseInt(y);
        this.searchData = [];
        this.source = obj.options.columns[x].source || [];

        this.run();
    }

    run() {
        let $this = this;
        let formulaHtml = this.content();

        let cell = find_and_replace_options_$(`td[data-x=${this.x || 0}][data-y=${this.y || 0}]`);
        new Popup(formulaHtml, cell);

        formulaHtml.find('.vi-wbe-find-string').select2({
            data: [{id: '', text: ''}, ...$this.source]
        });

        formulaHtml.find('.vi-wbe-replace-string').select2({
            data: [{id: '', text: ''}, ...$this.source]
        });

        formulaHtml.on('click', '.vi-wbe-apply-formula', this.applyFormula.bind(this));
    }

    content() {
        return find_and_replace_options_$(`<div class="vi-wbe-formula-container">
                    <div class="field">
                        <div>${functions.text('Find')}</div>
                        <select placeholder="" class="vi-wbe-find-string"> </select>
                    </div>
                    <div class="field">
                        <div>${functions.text('Replace')}</div>
                        <select placeholder="" class="vi-wbe-replace-string"> </select>
                    </div>
                    <button type="button" class="vi-ui button mini vi-wbe-apply-formula">${functions.text('Replace')}</button>
                    <p>If 'Find' value is empty, add to selected cells with 'Replace' value.</p>
                    <p>If 'Replace' value is empty, remove from selected cells with 'Find' value.</p>
                </div>`);
    }

    applyFormula(e) {
        let form = find_and_replace_options_$(e.target).closest('.vi-wbe-formula-container'),
            findValue = form.find('.vi-wbe-find-string').val(),
            replaceValue = form.find('.vi-wbe-replace-string').val(),
            excelObj = this.obj;

        if (!findValue && !replaceValue) return;

        findValue = !isNaN(findValue) ? +findValue : findValue;
        replaceValue = !isNaN(replaceValue) ? +replaceValue : replaceValue;

        let breakControl = false, records = [];
        let h = this.cells;
        let start = h[1], end = h[3], x = h[0];

        for (let y = start; y <= end; y++) {
            if (excelObj.records[y][x] && !excelObj.records[y][x].classList.contains('readonly') && excelObj.records[y][x].style.display !== 'none' && breakControl === false) {
                let value = excelObj.options.data[y][x];

                if (!value) value = [];

                let newValue = value.filter((item) => item !== findValue);

                if (value.length !== newValue.length || !findValue) {
                    newValue.push(replaceValue);
                }

                newValue = [...new Set(newValue)];

                records.push(excelObj.updateCell(x, y, newValue));
                excelObj.updateFormulaChain(x, y, records);
            }
        }

        // Update history
        excelObj.setHistory({
            action: 'setValue',
            records: records,
            selection: excelObj.selectedCell,
        });

        // Update table with custom configuration if applicable
        excelObj.updateTable();
    }

}
;// ./src/add-image-to-multi-gallery.js


class AddImageToMultiGallery {
    constructor(obj, cells, x, y, e) {
        this.cells = cells;
        this.obj = obj;
        this.x = parseInt(x);
        this.y = parseInt(y);

        this.run();
    }

    run() {
        let $this = this;
        const mediaMultiple = wp.media({multiple: true});
        mediaMultiple.open().off('select close')
            .on('select', function (e) {
                var selection = mediaMultiple.state().get('selection');
                selection.each(function (attachment) {
                    attachment = attachment.toJSON();
                    if (attachment.type === 'image') {
                        // galleryPopup.find('.vi-wbe-gallery-images').append(tmpl.galleryImage(attachment.url, attachment.id));
                        let imgId = attachment.id;
                        Attributes.imgStorage[imgId] = attachment.url;
                        $this.addImage(imgId);
                    }
                });
            });
    }

    addImage(imgId) {

        let excelObj = this.obj;
        let breakControl = false, records = [];
        let h = this.cells;
        let start = h[1], end = h[3], x = h[0];

        for (let y = start; y <= end; y++) {
            if (excelObj.records[y][x] && !excelObj.records[y][x].classList.contains('readonly') && excelObj.records[y][x].style.display !== 'none' && breakControl === false) {
                let value = excelObj.options.data[y][x];
                if (!value) value = [];

                let newValue = [...new Set(value)];
                newValue.push(imgId);

                records.push(excelObj.updateCell(x, y, newValue));
                excelObj.updateFormulaChain(x, y, records);
            }
        }

        // Update history
        excelObj.setHistory({
            action: 'setValue',
            records: records,
            selection: excelObj.selectedCell,
        });

        // Update table with custom configuration if applicable
        excelObj.updateTable();
    }
}
;// ./src/multiple-product-attributes.js



const multiple_product_attributes_$ = jQuery;

class MultipleProductAttributes {
    constructor(obj, cells, x, y, e) {
        this.cells = cells;
        this.obj = obj;
        this.x = parseInt(x);
        this.y = parseInt(y);

        this.run();
    }

    run() {
        let cell = multiple_product_attributes_$(`td[data-x=${this.x || 0}][data-y=${this.y || 0}]`);

        let $this = this, html = '';

        let modal = functions.createModal({
            header: functions.text('Attributes'),
            content: '',
            actions: [{class: 'save-attributes', text: functions.text('Apply')}],
        });

        this.content(modal);
        multiple_product_attributes_$(cell).append(modal);

        modal.on('click', function (e) {
            let thisTarget = multiple_product_attributes_$(e.target);
            if (thisTarget.hasClass('close') || thisTarget.hasClass('vi-wbe-modal-container')) modal.remove();
            if (thisTarget.hasClass('save-attributes')) {
                $this.addAttributes(modal);
            }
        });
    }

    addImage(imgId) {

        let excelObj = this.obj;
        let breakControl = false, records = [];
        let h = this.cells;
        let start = h[1], end = h[3], x = h[0];

        for (let y = start; y <= end; y++) {
            if (excelObj.records[y][x] && !excelObj.records[y][x].classList.contains('readonly') && excelObj.records[y][x].style.display !== 'none' && breakControl === false) {
                let value = excelObj.options.data[y][x];
                if (!value) value = [];

                let newValue = [...new Set(value)];
                newValue.push(imgId);

                records.push(excelObj.updateCell(x, y, newValue));
                excelObj.updateFormulaChain(x, y, records);
            }
        }

        // Update history
        excelObj.setHistory({
            action: 'setValue',
            records: records,
            selection: excelObj.selectedCell,
        });

        // Update table with custom configuration if applicable
        excelObj.updateTable();
    }

    addAttributes(modal) {
        let newAttributes = [],
            addAttrOpt = modal.find('.vi-wbe-add-attributes-option').val();

        modal.find('.vi-wbe-attribute-row').each(function (i, row) {
            let pAttr = multiple_product_attributes_$(row).data('attr');
            if (pAttr.is_taxonomy) {
                pAttr.options = multiple_product_attributes_$(row).find('select').val().map(Number);
            } else {
                pAttr.name = multiple_product_attributes_$(row).find('input.custom-attr-name').val();
                let value = multiple_product_attributes_$(row).find('textarea.custom-attr-val').val();
                pAttr.value = value.trim().replace(/\s+/g, ' ');
                pAttr.options = value.split('|').map(item => item.trim().replace(/\s+/g, ' '));
            }
            pAttr.visible = !!multiple_product_attributes_$(row).find('.attr-visibility:checked').length;
            pAttr.variation = !!multiple_product_attributes_$(row).find('.attr-variation:checked').length;
            pAttr.position = i;
            newAttributes.push(pAttr)
        });

        if (newAttributes.length) {
            let excelObj = this.obj;
            let breakControl = false, records = [];
            let h = this.cells;
            let start = h[1], end = h[3], x = h[0];

            const findExist = (productAttrs = [], attrName) => {
                if (productAttrs.length) {
                    for (let index in productAttrs) {
                        let attr = productAttrs[index];
                        if (attr.name === attrName) {
                            return index;
                        }
                    }
                }
                return false;
            };

            for (let y = start; y <= end; y++) {
                if (excelObj.records[y][x] && !excelObj.records[y][x].classList.contains('readonly') && excelObj.records[y][x].style.display !== 'none' && breakControl === false && excelObj.options.data[y][1] === 0) {
                    let value = excelObj.options.data[y][x];
                    if (!value) value = [];
                    let newValue = [...new Set(value)];
                    let positionIndex = 0;

                    for (let attr of newAttributes) {
                        let attrName = attr.name;
                        let key = findExist(newValue, attrName);

                        if (key === false) {
                            attr.position = newValue.length + positionIndex++;
                            // positionIndex++;
                            newValue.push(attr);
                        } else {
                            switch (addAttrOpt) {
                                case 'replace':
                                    attr.position = newValue[key].position;
                                    newValue[key] = attr;
                                    break;

                                case 'merge_terms':
                                    let currentTerms = newValue[key].options || [];
                                    let newTerms = attr.options || [];
                                    let terms = [...currentTerms, ...newTerms];
                                    newValue[key].options = [...new Set(terms)];
                                    excelObj.dispatch('onchange', excelObj.ele, (excelObj.records[y] && excelObj.records[y][x] ? excelObj.records[y][x] : null), x, y, 1, 0);
                                    break;
                            }
                        }
                    }

                    records.push(excelObj.updateCell(x, y, newValue));
                    excelObj.updateFormulaChain(x, y, records);
                }
            }

            // Update history
            excelObj.setHistory({
                action: 'setValue',
                records: records,
                selection: excelObj.selectedCell,
            });

            // Update table with custom configuration if applicable
            excelObj.updateTable();
        }
        modal.remove();
    }

    content(modal) {
        let $this = this, html = '';

        let {attributes} = Attributes;
        let addAttribute = `<option value="">${functions.text('Custom product attribute')}</option>`;

        for (let attr in attributes) {
            addAttribute += `<option value="${attr}">${attributes[attr].data.attribute_label}</option>`;
        }

        addAttribute = `<div class="vi-wbe-taxonomy-header">
                            <select class="vi-wbe-select-taxonomy">${addAttribute}</select>
                            <span class="vi-ui button tiny vi-wbe-add-taxonomy">${functions.text('Add')}</span>
                        </div>`;

        html = `${addAttribute}
                <table class="vi-ui celled table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Attributes</th>
                        <th width="1">Actions</th>
                    </tr>
                    </thead>
                    <tbody>${html}</tbody>
                </table>`;

        let addAttributeOptions = `<div>
                                        <div class="vi-wbe-add-attributes-option-label">
                                            Select action if exist attribute in product
                                        </div>
                                        <select class="vi-wbe-add-attributes-option">
                                            <option value="none">Don't add</option>
                                            <option value="replace">Replace existed attribute</option>
                                            <option value="merge_terms">Merge terms</option>
                                        </select>
                                    </div>`;

        modal.find('.content').append(html);
        modal.find('.actions').append(addAttributeOptions);
        modal.find('table select').select2({multiple: true});
        modal.find('tbody').sortable({
            items: 'tr',
            cursor: 'move',
            axis: 'y',
            scrollSensitivity: 40,
            forcePlaceholderSize: true,
            helper: 'clone',
            handle: '.icon.move',
        });

        const setOptionDisable = () => {
            modal.find('select.vi-wbe-select-taxonomy option').removeAttr('disabled');
            modal.find('input[type=hidden]').each(function (i, el) {
                let tax = multiple_product_attributes_$(el).val();
                modal.find(`select.vi-wbe-select-taxonomy option[value='${tax}']`).attr('disabled', 'disabled');
            });
        };

        setOptionDisable();

        modal.on('click', function (e) {
            let $thisTarget = multiple_product_attributes_$(e.target);
            if ($thisTarget.hasClass('trash')) {
                $thisTarget.closest('tr').remove();
                setOptionDisable();
            }

            if ($thisTarget.hasClass('vi-wbe-add-taxonomy')) {
                let taxSelect = multiple_product_attributes_$('.vi-wbe-select-taxonomy'), tax = taxSelect.val(),
                    item = {name: tax, options: []};
                if (tax) item.is_taxonomy = 1;

                let row = multiple_product_attributes_$($this.createRowTable(item));
                modal.find('table tbody').append(row);
                row.find('select').select2({multiple: true});
                setOptionDisable();
                taxSelect.val('').trigger('change');
            }

            if ($thisTarget.hasClass('vi-wbe-select-all-attributes')) {
                let td = $thisTarget.closest('td');
                let select = td.find('select');
                select.find('option').attr('selected', true);
                select.trigger('change');
            }

            if ($thisTarget.hasClass('vi-wbe-select-no-attributes')) {
                let td = $thisTarget.closest('td');
                let select = td.find('select');
                select.find('option').attr('selected', false);
                select.trigger('change');
            }

            if ($thisTarget.hasClass('vi-wbe-add-new-attribute')) {
                let newAttr = prompt(functions.text('Enter a name for the new attribute term:'));

                if (!newAttr) return;

                let tr = $thisTarget.closest('tr.vi-wbe-attribute-row'),
                    taxAttr = tr.attr('data-attr');

                if (taxAttr) {
                    taxAttr = JSON.parse(taxAttr);
                    functions.ajax({
                        data: {
                            sub_action: 'add_new_attribute',
                            taxonomy: taxAttr.name,
                            term: newAttr
                        },
                        beforeSend() {
                            $thisTarget.addClass('loading')
                        },
                        success(res) {
                            if (res.success) {
                                let select = tr.find('select');
                                select.append(`<option value="${res.data.term_id}" selected>${res.data.name}</option>`);
                                select.trigger('change');
                                Attributes.attributes[taxAttr.name].terms[res.data.term_id] = {slug: res.data.slug, text: res.data.name}
                            } else {
                                alert(res.data.message)
                            }
                        },
                        complete() {
                            $thisTarget.removeClass('loading');
                        }
                    });
                }
            }
        });
    }

    createRowTable(item) {
        let attrName = '', value = '';

        if (item.is_taxonomy) {
            let attribute = Attributes.attributes[item.name],
                terms = attribute.terms || [], options = '';

            attrName = `${attribute.data.attribute_label}<input type="hidden" value="${item.name}"/>`;

            if (Object.keys(terms).length) {
                for (let id in terms) {
                    let selected = item.options.includes(parseInt(id)) ? 'selected' : '';
                    options += `<option value="${id}" ${selected}>${terms[id].text}</option>`;
                }
            }

            value = `<select multiple>${options}</select>
                    <div class="vi-wbe-attributes-button-group">
                        <span class="vi-ui button mini vi-wbe-select-all-attributes">${functions.text('Select all')}</span>
                        <span class="vi-ui button mini vi-wbe-select-no-attributes">${functions.text('Select none')}</span>
                        <span class="vi-ui button mini vi-wbe-add-new-attribute">${functions.text('Add new')}</span>
                    </div>`;
        } else {
            attrName = `<input type="text" class="custom-attr-name" value="${item.name}" placeholder="${functions.text('Custom attribute name')}"/>`;
            value = `<textarea class="custom-attr-val" placeholder="${functions.text('Enter some text, or some attributes by "|" separating values.')}">${item.value || ''}</textarea>`;
        }

        attrName = `<div class="vi-wbe-attribute-name-label">${attrName}</div>`;

        attrName += `<div>
                        <input type="checkbox" class="attr-visibility" ${item.visible ? 'checked' : ''} value="1">
                        <label>${functions.text('Visible on the product page')}</label>
                    </div>`;

        attrName += `<div>
                        <input type="checkbox" class="attr-variation" ${item.variation ? 'checked' : ''} value="1">
                        <label>${functions.text('Used for variations (apply for variable)')}</label>
                    </div>`;

        return `<tr class="vi-wbe-attribute-row" data-attr='${JSON.stringify(item)}'>
                    <td class="vi-wbe-left">${attrName}</td>
                    <td>${value}</td>
                    <td class="vi-wbe-right"><i class="icon trash"> </i> <i class="icon move"> </i></td>
                </tr>`;
    }

}
;// ./src/remove-product-attributes.js



const remove_product_attributes_$ = jQuery;

class RemoveProductAttributes {
    constructor(obj, cells, x, y, e) {
        this.cells = cells;
        this.obj = obj;
        this.x = parseInt(x);
        this.y = parseInt(y);

        this.run();
    }

    run() {
        let cell = remove_product_attributes_$(`td[data-x=${this.x || 0}][data-y=${this.y || 0}]`);

        let $this = this;

        let modal = functions.createModal({
            header: functions.text('Remove attributes'),
            content: '',
            actions: [{class: 'save-attributes', text: functions.text('Apply')}],
        });

        this.content(modal);
        remove_product_attributes_$(cell).append(modal);

        modal.on('click', function (e) {
            let thisTarget = remove_product_attributes_$(e.target);
            if (thisTarget.hasClass('close') || thisTarget.hasClass('vi-wbe-modal-container')) modal.remove();
            if (thisTarget.hasClass('save-attributes')) {
                // $this.addAttributes(modal);
                $this.removeAttributes(modal);
            }
        });
    }

    removeAttributes(modal) {
        let removeAttributes = modal.find('.vi-wbe-select-taxonomy').dropdown('get values');

        if (removeAttributes.length) {
            let excelObj = this.obj;
            let breakControl = false, records = [];
            let h = this.cells;
            let start = h[1], end = h[3], x = h[0];

            for (let y = start; y <= end; y++) {
                if (excelObj.records[y][x] && !excelObj.records[y][x].classList.contains('readonly') && excelObj.records[y][x].style.display !== 'none' && breakControl === false) {
                    let value = excelObj.options.data[y][x];

                    if (!value || !Array.isArray(value)) continue;

                    let newValue = value.filter(attr => {
                        return !removeAttributes.includes(attr.name);
                    });

                    records.push(excelObj.updateCell(x, y, newValue));
                    excelObj.updateFormulaChain(x, y, records);
                }
            }

            // Update history
            excelObj.setHistory({
                action: 'setValue',
                records: records,
                selection: excelObj.selectedCell,
            });

            // Update table with custom configuration if applicable
            excelObj.updateTable();
        }
        modal.remove();
    }

    content(modal) {
        let {attributes} = Attributes;
        let addAttribute = `<option value="">${functions.text('Select attributes to remove')}</option>`;

        for (let attr in attributes) {
            addAttribute += `<option value="${attr}">${attributes[attr].data.attribute_label}</option>`;
        }

        let html = `<div class="vi-wbe-taxonomy-header">
                        <select class="vi-wbe-select-taxonomy fluid vi-ui selection" multiple>${addAttribute}</select>
                    </div>`;

        modal.find('.content').append(html);
        modal.find('table select').select2({multiple: true});
        modal.find('tbody').sortable({
            items: 'tr',
            cursor: 'move',
            axis: 'y',
            scrollSensitivity: 40,
            forcePlaceholderSize: true,
            helper: 'clone',
            handle: '.icon.move',
        });

        modal.find('.vi-wbe-select-taxonomy').dropdown();
    }

}
// EXTERNAL MODULE: ./src/purify.js
var purify = __webpack_require__(341);
;// ./src/editor.js














jQuery(document).ready(function ($) {

    class BulkEdit {
        constructor() {
            this.sidebar = Sidebar.init();
            this.compare = [];
            this.trash = [];
            this.unTrash = [];
            this.revision = {};
            this.isAdding = false;

            this.editor = $('#vi-wbe-container');
            this.menubar = $('#vi-wbe-menu-bar');

            this.menubar.on('click', '.vi-wbe-open-sidebar', this.openMenu.bind(this));
            this.menubar.on('click', 'a.item:not(.vi-wbe-open-sidebar)', this.closeMenu.bind(this));

            this.menubar.on('click', '.vi-wbe-new-products', this.addNewProduct.bind(this));
            this.menubar.on('click', '.vi-wbe-new-coupons', this.addNewCoupon.bind(this));
            this.menubar.on('click', '.vi-wbe-new-orders', this.addNewOrder.bind(this));

            this.menubar.on('click', '.vi-wbe-full-screen-btn', this.toggleFullScreen.bind(this));
            this.menubar.on('click', '.vi-wbe-save-button', this.save.bind(this));
            this.menubar.on('click', '.vi-wbe-pagination a.item', this.changePage.bind(this));
            this.menubar.on('click', '.vi-wbe-get-product', this.reloadCurrentPage.bind(this));
            this.menubar.on('change', '.vi-wbe-go-to-page', this.changePageByInput.bind(this));

            this.editor.on('cellonchange', 'tr', this.cellOnChange.bind(this));
            this.editor.on('click', '.jexcel_content', this.removeExistingEditor.bind(this));
            this.editor.on('dblclick', this.removeContextPopup);

            this.sidebar.on('afterAddFilter', this.afterAddFilter.bind(this));
            this.sidebar.on('afterSaveSettings', this.afterSaveSettings.bind(this));
            this.sidebar.on('click', '.vi-wbe-close-sidebar', this.closeMenu.bind(this));
            this.sidebar.on('change', '#vi-wbe-edit_fields, #vi-wbe-exclude_edit_fields', this.toggleSortFields.bind(this));

            this.init();

            $(document).on('keydown', this.keyDownControl.bind(this));
            $(document).on('keyup', this.keyUpControl.bind(this));
        }

        removeExistingEditor(e) {
            if (e.target === e.currentTarget) {
                if (this.WorkBook && this.WorkBook.edition) {
                    this.WorkBook.closeEditor(this.WorkBook.edition[0], true);
                }
            }
        }

        keyDownControl(e) {
            if ((e.ctrlKey || e.metaKey) && !e.shiftKey) {
                if (e.which === 83) {
                    e.preventDefault();
                    this.save();
                }
            }

            switch (e.which) {
                case 27:
                    this.sidebar.removeClass('vi-wbe-open');
                    break;
            }
        }

        keyUpControl(e) {
            if (e.target && !e.target.getAttribute('readonly')) {
                let decimal = e.target.getAttribute('data-currency');
                if (decimal) {
                    let currentValue = e.target.value;
                    if (currentValue) {
                        let decimalExist = currentValue.indexOf(decimal);

                        if (decimalExist < 1) {
                            let value = currentValue.match(/\d/g);
                            e.target.value = value ? value.join('') : '';
                        } else {
                            let split = currentValue.split(decimal);
                            let integer, fraction = '';
                            integer = split[0].match(/[\d]/g).join('');

                            if (split[1]) {
                                fraction = split[1].match(/[\d]/g);
                                fraction = fraction ? fraction.join('') : '';
                            }

                            e.target.value = fraction ? `${integer}${decimal}${fraction}` : `${integer}${decimal}`;
                        }
                    }
                }
            }
        }

        removeContextPopup() {
            $('.vi-wbe-context-popup').removeClass('vi-wbe-popup-active')
        }

        init() {
            if (wbeParams.columns) Attributes.setColumns(wbeParams.columns);
            this.pagination(1, 1);
            this.workBookInit();
            this.loadProducts();
            functions.setJexcel(this.WorkBook);
        }

        cellOnChange(e, data) {
            let {col = ''} = data;

            if (!col) return;

            let type = Attributes.idMapping[col];
            let thisRow = $(e.target);

            switch (type) {
                case 'product_type':
                    thisRow.find('td').each(function (i, el) {
                        let x = $(el).data('x');
                        if (x && x !== 0 && x !== 1) {
                            $(el).removeClass('readonly');
                        }
                    });

                    let dependArr = Attributes.cellDependType[data.value];
                    if (Array.isArray(dependArr)) {
                        dependArr.forEach(function (el) {
                            let pos = Attributes.idMappingFlip[el];
                            thisRow.find(`td[data-x='${pos}']`).addClass('readonly');
                        });
                    }

                    break;

                case 'post_date':
                    let x = functions.getColFromColumnType('status');
                    if (!thisRow.find(`td[data-x='${x}']`).length){
                        break;
                    }
                    let value = data.value,
                        cell = thisRow.find(`td[data-x='${x}']`).get(0),
                        time = (new Date(value)).getTime(),
                        now = Date.now(),
                        status = time > now ? 'future' : 'publish';
                    this.WorkBook.setValue(cell, status);

                    break;
            }
        }

        workBookInit() {
            let $this = this,
                countCol = 0,
                deleteSelectedRows = functions.text('Delete rows with selected cells'),
                oncreaterow = null,
                contextMenuItems,
                onselection = null,
                onresizecolumn = function (instance, cell, width) {
                    functions.ajax({
                        async: true,
                        data: {
                            sub_action: 'resize_column',
                            column_id: Attributes.columns[cell].id,
                            column_width: width <= 55 ? 55 : width,
                        },
                        beforeSend() {},
                        success(res) {
                            if (res.success) {
                                console.log(res.data);
                            }
                        },
                        error(res) {
                            console.log(res.data);
                            alert(res.data);
                        },
                        complete() {}
                    });

                };

            function setValueToCell(obj, value) {
                let breakControl = false, records = [], h = obj.selectedContainer, start = h[1], end = h[3], x = h[0];

                for (let y = start; y <= end; y++) {
                    if (obj.records[y][x] && !obj.records[y][x].classList.contains('readonly') && obj.records[y][x].style.display !== 'none' && breakControl === false) {
                        records.push(obj.updateCell(x, y, value));
                        obj.updateFormulaChain(x, y, records);
                    }
                }

                obj.setHistory({action: 'setValue', records: records, selection: obj.selectedCell});
                obj.updateTable();
            }

            switch (Attributes.editType) {
                case 'products':
                    deleteSelectedRows = `${functions.text('Delete rows with selected cells')} 
                                            <span class="vi-wbe-context-menu-note">
                                                (${functions.text('Variations cannot revert after save')})
                                            </span>`;

                    oncreaterow = function (row, j) {
                        let productType = functions.getProductTypeFromY(j);
                        let dependArr = Attributes.cellDependType[productType];

                        if (Array.isArray(dependArr)) {
                            dependArr.forEach(function (el) {
                                let pos = Attributes.idMappingFlip[el];
                                $(row).find(`td[data-x='${pos}']`).addClass('readonly');
                            });
                        }
                    };

                    onselection = function (el, x1, y1, x2, y2, origin) {
                        if (x1 === x2 && y1 === y2) {
                            let cell = this.getCellFromCoords(x1, y1),
                                child = $(cell).children();

                            // if (child.length && child.hasClass('vi-wbe-gallery-has-item')) {
                            //     let ids = this.options.data[y1][x1],
                            //         images = '';
                            //
                            //     if (ids.length) {
                            //         for (let id of ids) {
                            //             let src = Attributes.imgStorage[id];
                            //             images += `<li class="vi-wbe-gallery-image"><img src="${src}"></li>`;
                            //         }
                            //     }
                            //
                            //     new Popup(`<ul class="vi-wbe-gallery-images">${images}</ul>`, $(cell));
                            // }
                        }
                    };

                    contextMenuItems = function (items, obj, x, y, e) {
                        $this.removeContextPopup();

                        let cells = obj.selectedContainer;
                        x = parseInt(x);
                        y = parseInt(y);

                        if (cells[0] === cells[2] && x !== null) {
                            switch (obj.options.columns[x].type) {
                                case 'checkbox':
                                    items.push({
                                        title: functions.text('Check'),
                                        onclick(e) {
                                            setValueToCell(obj, true);
                                        }
                                    });

                                    items.push({
                                        title: functions.text('Uncheck'),
                                        onclick(e) {
                                            setValueToCell(obj, false);
                                        }
                                    });
                                    break;

                                case 'number':
                                    items.push({
                                        title: functions.text('Calculator'),
                                        onclick(e) {
                                            new Calculator(obj, x, y, e);
                                        }
                                    });

                                    items.push({
                                        title: functions.text('Fill'),
                                        onclick(e) {
                                            new FillNumber(obj, x, y, e);
                                        }
                                    });

                                    if (x > 1 && obj.options.columns[x].id === 'sale_price' && obj.options.columns[x - 1].id === 'regular_price') {
                                        items.push({
                                            title: functions.text('Calculator base on Regular price'),
                                            onclick(e) {
                                                new CalculatorBaseOnRegularPrice(obj, x, y, e);
                                            }
                                        });
                                    }

                                    break;

                                case 'text':
                                    items.push({
                                        title: functions.text('Edit multiple cells'),
                                        onclick(e) {
                                            new TextMultiCellsEdit(obj, x, y, e, obj.options.columns[x].wordWrap);
                                        }
                                    });

                                    items.push({
                                        title: functions.text('Find and Replace'),
                                        onclick(e) {
                                            new FindAndReplace(obj, x, y, e);
                                        }
                                    });
                                    break;

                                case 'calendar':
                                    let cell = $(`td[data-x=${x}][data-y=${y}]`).get(0);
                                    if (!$(cell).hasClass('readonly')) {
                                        items.push({
                                            title: functions.text('Open date picker'),
                                            onclick() {
                                                let value = obj.options.data[y][x];

                                                var editor = functions.createEditor(cell, 'input', '', false);
                                                editor.value = value;
                                                editor.style.left = 'unset';

                                                let h = obj.selectedContainer;
                                                let start = h[1], end = h[3];

                                                if (obj.options.tableOverflow == true || obj.options.fullscreen == true) {
                                                    obj.options.columns[x].options.position = true;
                                                }
                                                obj.options.columns[x].options.value = obj.options.data[y][x];
                                                obj.options.columns[x].options.opened = true;
                                                obj.options.columns[x].options.onclose = function (el, value) {
                                                    let records = [];
                                                    value = el.value;

                                                    for (let y = start; y <= end; y++) {
                                                        if (obj.records[y][x] && !obj.records[y][x].classList.contains('readonly') && obj.records[y][x].style.display !== 'none') {
                                                            records.push(obj.updateCell(x, y, value));
                                                            obj.updateFormulaChain(x, y, records);
                                                        }
                                                    }
                                                    // obj.closeEditor(cell, true);

                                                    // Update history
                                                    obj.setHistory({
                                                        action: 'setValue',
                                                        records: records,
                                                        selection: obj.selectedCell,
                                                    });

                                                    // Update table with custom configuration if applicable
                                                    obj.updateTable();
                                                };
                                                // Current value
                                                jSuites.calendar(editor, obj.options.columns[x].options);
                                                // Focus on editor
                                                editor.focus();
                                            }
                                        });
                                    }

                                    break;

                                case 'custom':

                                    switch (obj.options.columns[x].editor.type) {
                                        case 'textEditor':
                                            items.push({
                                                title: functions.text('Edit multiple cells'),
                                                onclick() {
                                                    $('.vi-ui.modal').modal('show');
                                                    $('.vi-ui.modal .close.icon').off('click');

                                                    if (tinymce.get('vi-wbe-text-editor') === null) {
                                                        $('#vi-wbe-text-editor').val('');
                                                        wp.editor.initialize('vi-wbe-text-editor', Attributes.tinyMceOptions);
                                                    } else {
                                                        tinymce.get('vi-wbe-text-editor').setContent('')
                                                    }

                                                    $('.vi-wbe-text-editor-save').off('click').on('click', function () {
                                                        let content = wp.editor.getContent('vi-wbe-text-editor');
                                                        setValueToCell(obj, content);
                                                        if ($(this).hasClass('vi-wbe-close')) $('.vi-ui.modal').modal('hide');
                                                    });
                                                }
                                            });
                                            break;

                                        case 'tags':
                                            items.push({
                                                title: functions.text('Find and replace tags'),
                                                onclick(e) {
                                                    new FindAndReplaceTags(obj, cells, x, y, e);
                                                }
                                            });
                                            break;

                                        case 'select2':
                                            items.push({
                                                title: functions.text('Find and replace options'),
                                                onclick(e) {
                                                    new FindAndReplaceOptions(obj, cells, x, y, e);
                                                }
                                            });
                                            break;

                                        case 'gallery':
                                            items.push({
                                                title: functions.text('Add image to selected cells'),
                                                onclick(e) {
                                                    new AddImageToMultiGallery(obj, cells, x, y, e);
                                                }
                                            });

                                            break;

                                        case 'product_attributes':
                                            items.push({
                                                title: functions.text('Add attributes to products'),
                                                onclick(e) {
                                                    new MultipleProductAttributes(obj, cells, x, y, e);
                                                }
                                            });
                                            items.push({
                                                title: functions.text('Remove multiple product attribute'),
                                                onclick(e) {
                                                    new RemoveProductAttributes(obj, cells, x, y, e);
                                                }
                                            });
                                            break;
                                    }

                                    break;

                            }
                        }

                        if (items.length) items.push({type: 'line'});

                        if (cells[1] === cells[3] && y !== null && ! isNaN(y)) {
                            let productType = functions.getProductTypeFromY(y);
                            if (productType === 'variable') {
                                items.push({
                                    title: functions.text('Add variation'),
                                    onclick() {
                                        if (functions.is_loading()) return;

                                        functions.ajax({
                                                data: {
                                                    sub_action: 'add_variation',
                                                    pid: functions.getProductIdOfCell(obj, y)
                                                },
                                                beforeSend() {
                                                    functions.loading();
                                                },
                                                success(res) {
                                                    if (res.success) {
                                                        obj.insertRow(0, y, false, true);
                                                        obj.setRowData(y + 1, res.data, true);
                                                    }
                                                },
                                                error(res) {
                                                    console.log(res);
                                                    alert(res.statusText + res.responseText);
                                                },
                                                complete() {
                                                    functions.removeLoading();
                                                }
                                            }
                                        );
                                    }
                                });

                                items.push({
                                    title: `${functions.text('Create variations from all attributes')} <span class="vi-wbe-context-menu-note">(${functions.text('Save new attributes before')})</span>`,
                                    onclick() {
                                        if (functions.is_loading()) return;

                                        functions.ajax({
                                            data: {
                                                sub_action: 'link_all_variations',
                                                pid: functions.getProductIdOfCell(obj, y)
                                            },
                                            beforeSend() {
                                                functions.loading();
                                            },
                                            success(res) {
                                                if (!res.success) return;
                                                if (res.data.length) {
                                                    res.data.forEach(function (item, i) {
                                                        obj.insertRow(0, y + i, false, true);
                                                        obj.setRowData(y + i + 1, item, true);
                                                    })
                                                }

                                                functions.removeLoading();
                                                functions.notice(`${res.data.length} ${functions.text('variations are added')}`)
                                            },
                                            error(res) {
                                                console.log(res);
                                                alert(res.statusText + res.responseText);
                                            },
                                            complete() {
                                                functions.removeLoading();
                                            }
                                        });
                                    }
                                });

                                items.push({type: 'line'});
                            }

                            if (productType !== 'variation') {
                                let pid = functions.getProductIdOfCell(obj, y);

                                items.push({
                                    title: functions.text('Duplicate'),
                                    onclick() {
                                        functions.ajax({
                                            data: {sub_action: 'duplicate_product', product_id: pid},
                                            beforeSend() {
                                                functions.loading();
                                            },
                                            success(res) {
                                                if (res.data.length) {
                                                    res.data.forEach(function (item, i) {
                                                        obj.insertRow(0, y + i, true, true);
                                                        obj.setRowData(y + i, item, true);
                                                    })
                                                }
                                            },
                                            error(res) {
                                                console.log(res);
                                                alert(res.statusText + res.responseText);
                                            },
                                            complete() {
                                                functions.removeLoading();
                                            }
                                        });
                                    }
                                });

                                items.push({
                                    title: functions.text('Go to edit product page'),
                                    onclick() {
                                        window.open(`${Attributes.adminUrl}post.php?post=${pid}&action=edit`, '_blank');
                                    }
                                });

                                items.push({
                                    title: functions.text('View on Single product page'),
                                    onclick() {
                                        window.open(`${Attributes.frontendUrl}?p=${pid}&post_type=product&preview=true`, '_blank');
                                    }
                                });

                            }

                        }

                        return items;
                    };

                    break;

                case 'orders':
                    contextMenuItems = function (items, obj, x, y, e) {
                        let cells = obj.selectedContainer;
                        x = parseInt(x);
                        y = parseInt(y);

                        if (x !== null && y !== null) {

                            for (let action in Attributes.orderActions) {
                                items.push({
                                    title: Attributes.orderActions[action],
                                    onclick() {
                                        let order_ids = [];

                                        for (let i = cells[1]; i <= cells[3]; i++) {
                                            order_ids.push(functions.getProductIdOfCell(obj, i))
                                        }

                                        functions.ajax({
                                            data: {sub_action: action, order_ids},
                                            beforeSend() {
                                                functions.loading();
                                            },
                                            success(res) {
                                            },
                                            error(res) {
                                                console.log(res);
                                                alert(res.statusText + res.responseText);
                                            },
                                            complete() {
                                                functions.removeLoading();
                                            }
                                        });
                                    }
                                });
                            }

                            if (items.length) items.push({type: 'line'});

                            const addNote = function (is_customer_note = 0) {
                                let cell = obj.getCellFromCoords(cells[0], cells[1]),
                                    control = $(`<div>
                                                    <div class="field"> 
                                                        <textarea rows="3"></textarea>
                                                    </div>
                                                    <div class="field"> 
                                                        <span class="vi-wbe-add-note vi-ui button tiny">
                                                            ${functions.text('Add')}
                                                        </span>
                                                    </div>
                                                </div>`);

                                let popup = new Popup(control, $(cell));

                                control.on('click', '.vi-wbe-add-note', function () {
                                    let note = control.find('textarea').val();

                                    if (!note) return;

                                    let h = obj.selectedContainer;
                                    let start = h[1], end = h[3], x = h[0];
                                    let ids = [];

                                    for (let y = start; y <= end; y++) {
                                        ids.push(obj.options.data[y][0])
                                    }

                                    popup.hide();

                                    functions.ajax({
                                        data: {sub_action: 'add_order_note', ids, note, is_customer_note},
                                        beforeSend() {
                                            functions.loading();
                                        },
                                        success(res) {
                                        },
                                        error(res) {
                                            console.log(res);
                                            alert(res.statusText + res.responseText);
                                        },
                                        complete() {
                                            functions.removeLoading();
                                        }
                                    })
                                });
                            };

                            items.push({
                                title: functions.text('Add private note'),
                                onclick() {
                                    addNote(0);
                                }
                            });

                            items.push({
                                title: functions.text('Add note to customer'),
                                onclick() {
                                    addNote(1);
                                }
                            });

                            if (items.length) items.push({type: 'line'});

                            if (cells[1] === cells[3]) {
                                let order_id = functions.getProductIdOfCell(obj, y);

                                items.push({
                                    title: functions.text('Go to edit order page'),
                                    onclick() {
                                        window.open(`${Attributes.adminUrl}post.php?post=${order_id}&action=edit`, '_blank');
                                    }
                                });
                                if (items.length) items.push({type: 'line'});
                            }

                        }
                        return items;
                    };
                    break;

                case 'coupons':
                    contextMenuItems = function (items, obj, x, y, e) {
                        let cells = obj.selectedContainer;
                        x = parseInt(x);
                        y = parseInt(y);

                        if (x !== null && y !== null) {

                            if (cells[0] === cells[2]) {
                                let colType = functions.getColumnType(x);
                                if (colType === 'code') {
                                    items.push({
                                        title: functions.text('Generate coupon code'),
                                        onclick() {
                                            let breakControl = false, records = [],
                                                h = obj.selectedContainer, start = h[1], end = h[3], x = h[0];

                                            for (let y = start; y <= end; y++) {
                                                if (obj.records[y][x] && !obj.records[y][x].classList.contains('readonly') && obj.records[y][x].style.display !== 'none' && breakControl === false) {
                                                    let value = functions.generateCouponCode();
                                                    records.push(obj.updateCell(x, y, value));
                                                    obj.updateFormulaChain(x, y, records);
                                                }
                                            }
                                            obj.setHistory({action: 'setValue', records: records, selection: obj.selectedCell});
                                            obj.updateTable();
                                        }
                                    });
                                }

                                if (obj.options.columns[x].type === 'text') {
                                    items.push({
                                        title: functions.text('Edit multiple cells'),
                                        onclick(e) {
                                            new TextMultiCellsEdit(obj, x, y, e, obj.options.columns[x].wordWrap);
                                        }
                                    });

                                    items.push({
                                        title: functions.text('Find and Replace'),
                                        onclick(e) {
                                            new FindAndReplace(obj, x, y, e);
                                        }
                                    });
                                }

                                if (obj.options.columns[x].type === 'checkbox') {

                                    items.push({
                                        title: functions.text('Check'),
                                        onclick(e) {
                                            setValueToCell(obj, true);
                                        }
                                    });

                                    items.push({
                                        title: functions.text('Uncheck'),
                                        onclick(e) {
                                            setValueToCell(obj, false);
                                        }
                                    });
                                }

                                if (items.length) items.push({type: 'line'});
                            }
                        }
                        return items;
                    };

                    break;

                case 'reviews':
                    oncreaterow = function (row, j) {
                        let reviewType = functions.getReviewTypeFrom(j);
                        let dependArr = Attributes.cellDependType[reviewType];

                        if (Array.isArray(dependArr)) {
                            dependArr.forEach(function (el) {
                                let pos = Attributes.idMappingFlip[el];
                                $(row).find(`td[data-x='${pos}']`).addClass('readonly');
                            });
                        }
                    };

                    contextMenuItems = function (items, obj, x, y, e) {
                        let cells = obj.selectedContainer;
                        x = parseInt(x);
                        y = parseInt(y);
                        if (x !== null && y !== null) {

                            if (cells[0] === cells[2]) {
                                switch (obj.options.columns[x].type) {
                                    case 'text':
                                        items.push({
                                            title: functions.text('Edit multiple cells'),
                                            onclick(e) {
                                                new TextMultiCellsEdit(obj, x, y, e, obj.options.columns[x].wordWrap);
                                            }
                                        });

                                        items.push({
                                            title: functions.text('Find and Replace'),
                                            onclick(e) {
                                                new FindAndReplace(obj, x, y, e);
                                            }
                                        });
                                        break;
                                    case 'number':
                                        items.push({
                                           title: functions.text('Find and Replace'),
                                            onclick(e) {
                                                new FindAndReplace(obj, x, y, e);
                                            }
                                        });
                                        break;
                                    case 'checkbox':
                                        items.push({
                                            title: functions.text('Check'),
                                            onclick(e) {
                                                setValueToCell(obj, true);
                                            }
                                        });

                                        items.push({
                                            title: functions.text('Uncheck'),
                                            onclick(e) {
                                                setValueToCell(obj, false);
                                            }
                                        });
                                        break;

                                    case 'calendar':
                                        let cell = $(`td[data-x=${x}][data-y=${y}]`).get(0);
                                        if (!$(cell).hasClass('readonly')) {
                                            items.push({
                                                title: functions.text('Open date picker'),
                                                onclick() {
                                                    let value = obj.options.data[y][x];

                                                    var editor = functions.createEditor(cell, 'input', '', false);
                                                    editor.value = value;
                                                    editor.style.left = 'unset';

                                                    let h = obj.selectedContainer;
                                                    let start = h[1], end = h[3];

                                                    if (obj.options.tableOverflow == true || obj.options.fullscreen == true) {
                                                        obj.options.columns[x].options.position = true;
                                                    }
                                                    obj.options.columns[x].options.value = obj.options.data[y][x];
                                                    obj.options.columns[x].options.opened = true;
                                                    obj.options.columns[x].options.onclose = function (el, value) {
                                                        let records = [];
                                                        value = el.value;

                                                        for (let y = start; y <= end; y++) {
                                                            if (obj.records[y][x] && !obj.records[y][x].classList.contains('readonly') && obj.records[y][x].style.display !== 'none') {
                                                                records.push(obj.updateCell(x, y, value));
                                                                obj.updateFormulaChain(x, y, records);
                                                            }
                                                        }
                                                        // obj.closeEditor(cell, true);

                                                        // Update history
                                                        obj.setHistory({
                                                            action: 'setValue',
                                                            records: records,
                                                            selection: obj.selectedCell,
                                                        });

                                                        // Update table with custom configuration if applicable
                                                        obj.updateTable();
                                                    };
                                                    // Current value
                                                    jSuites.calendar(editor, obj.options.columns[x].options);
                                                    // Focus on editor
                                                    editor.focus();
                                                }
                                            });
                                        }

                                        break;
                                    case 'custom':
                                        if (obj.options.columns[x].editor.type === 'textEditor') {
                                            items.push({
                                                title: functions.text('Edit multiple cells'),
                                                onclick() {
                                                    $('.vi-ui.modal').modal('show');
                                                    $('.vi-ui.modal .close.icon').off('click');

                                                    if (tinymce.get('vi-wbe-text-editor') === null) {
                                                        $('#vi-wbe-text-editor').val('');
                                                        wp.editor.initialize('vi-wbe-text-editor', Attributes.tinyMceOptions);
                                                    } else {
                                                        tinymce.get('vi-wbe-text-editor').setContent('')
                                                    }

                                                    $('.vi-wbe-text-editor-save').off('click').on('click', function () {
                                                        let content = wp.editor.getContent('vi-wbe-text-editor');
                                                        setValueToCell(obj, content);
                                                        if ($(this).hasClass('vi-wbe-close')) $('.vi-ui.modal').modal('hide');
                                                    });
                                                }
                                            });
                                        }
                                        break;
                                }
                            }

                            items.push({type: 'line'});

                            items.push({
                                title: functions.text('Reply' ),
                                onclick() {
                                    $('.vi-ui.modal').modal('show');
                                    $('.vi-ui.modal .close.icon').off('click');

                                    if (tinymce.get('vi-wbe-text-editor') === null) {
                                        $('#vi-wbe-text-editor').val('');
                                        wp.editor.initialize('vi-wbe-text-editor', Attributes.tinyMceOptions);
                                    } else {
                                        tinymce.get('vi-wbe-text-editor').setContent('')
                                    }

                                    $('.vi-wbe-text-editor-save').off('click').on('click', function () {

                                        let h = obj.selectedContainer, start = h[1], end = h[3], x = h[0];
                                        let content = wp.editor.getContent('vi-wbe-text-editor');
                                        let new_comments = [];

                                        for (let y = start; y <= end; y++) {
                                            new_comments.push( {
                                                comment_id : functions.getDataFromCell(obj, obj.records[y][0]),
                                                product_id : functions.getDataFromCell(obj, obj.records[y][1]),
                                            } );
                                        }

                                        $this.addNewReply( content, new_comments );

                                        if ($(this).hasClass('vi-wbe-close')) $('.vi-ui.modal').modal('hide');
                                    });
                                }
                            });
                            if (cells[1] === cells[3]) {
                                items.push({
                                    title: functions.text('Go to edit review page'),
                                    onclick() {
                                        window.open(`${Attributes.adminUrl}/comment.php?action=editcomment&c=${(obj.options.data[y][0])}`, '_blank');
                                    }
                                });
                                let pid = null, product_id_index;
                                for (let column of obj.options.columns) {
                                    if (column?.id && column.id === 'product_id') {
                                        product_id_index = column.name;
                                    }
                                }
                                if (product_id_index) {
                                    if (typeof y === 'object') {
                                        let y = y.getAttribute('data-y');
                                        pid = obj.options.data[y][product_id_index];
                                    } else {
                                        pid = obj.options.data[y][product_id_index];
                                    }
                                }
                                if (pid) {
                                    items.push({
                                        title: functions.text('View on Single product page'),
                                        onclick() {
                                            window.open(`${Attributes.frontendUrl}?p=${pid}&post_type=product&preview=true`, '_blank');
                                        }
                                    });
                                }
                            }
                        }
                        return items;
                    };
                    break;
            }

            this.WorkBook = $('#vi-wbe-spreadsheet').jexcel({
                allowInsertRow: false,
                allowInsertColumn: false,
                about: false,
                freezeColumns: 3,
                tableOverflow: true,
                tableWidth: '100%',
                tableHeight: '100%',
                columns: Attributes.columns,
                stripHTML: false,
                allowExport: false,
                allowDeleteColumn: false,
                allowRenameColumn: false,
                autoIncrement: false,
                allowXCopy: false,
                lazyLoading: true,
                loadingSpin: true,
                fullscreen: true,
                text: {deleteSelectedRows},
                oncreaterow,
                contextMenuItems,
                onselection,
                onresizecolumn,
                rowDrag: wbeParams?.settings?.load_variations !== 'yes',

                onchange(instance, cell, col, row, value, oldValue) {
                    let x = $(cell).data('x');
                    if (x && this.options?.columns && this.options.columns[x]?.id === 'sale_price'){
                        let regular_field_index = '';
                        for (let column of this.options.columns) {
                            if (column?.id && column.id === 'regular_price'){
                                regular_field_index = column.name;
                            }
                        }
                        if (regular_field_index) {
                            let regularPrice = parseFloat((this.options.data[row][regular_field_index]||0).toString().replace(',', '.'));
                            if (regularPrice < value) {
                                let message = {
                                    title:this.options.data[row][2],
                                    message: 'Please ensure the sale price is less than the regular price',
                                    type: "error sale-price-error-"+row,
                                    duration: 3000000
                                };
                                functions.showMessage(message);
                                $this.menubar.find('.vi-wbe-save-button').removeClass('vi-wbe-saveable');
                                return;
                            }
                        }
                    }
                    if (JSON.stringify(value) !== JSON.stringify(oldValue)) {
                        // if (c == 0) {
                        //     var columnName = jexcel.getColumnNameFromId([c + 1, r]);
                        //     instance.jexcel.setValue(columnName, '');
                        // }
                        $(cell).parent().trigger('cellonchange', {cell, col, row, value});

                        let pid = this.options.data[row][0];
                        $this.compare.push(pid);
                        $this.compare = [...new Set($this.compare)];
                        // console.log($this.compare)
                        $this.menubar.find('.vi-wbe-save-button').addClass('vi-wbe-saveable');

                        if (!$this.isAdding) {
                            if (!$this.revision[pid]) $this.revision[pid] = {};
                            let columnType = functions.getColumnType(col);
                            $this.revision[pid][columnType] = oldValue;
                        }
                    }
                },

                onbeforechange(instance, cell, col, row, value) {
                    if (typeof value !== 'object') {
                        value = purify.sanitize(value);
                    }
                    return value;
                },

                ondeleterow(el, rowNumber, numOfRows, rowRecords) {
                    for (let row of rowRecords) {
                        $this.trash.push(row[0].innerText);
                    }

                    if ($this.trash.length) $this.menubar.find('.vi-wbe-save-button').addClass('vi-wbe-saveable');
                },

                onundo(el, historyRecord) {
                    if (historyRecord && historyRecord.action === 'deleteRow') {
                        for (let row of historyRecord.rowData) {
                            $this.unTrash.push(row[0]);
                        }
                    }
                },

                onbeforecopy() {
                    countCol = 0;
                    $this.firstCellCopy = null;
                },

                oncopying(value, x, y) {
                    if (!$this.firstCellCopy) $this.firstCellCopy = [x, y];
                    if ($this.firstCellCopy[0] !== x) countCol++;
                },

                onbeforepaste(data, selectedCell) {
                    if (typeof data !== 'string') return data;
                    data = data.trim();

                    let x = selectedCell[0];
                    let cellType = this.columns[x].type;

                    if (typeof $this.firstCellCopy === 'undefined') {
                        if (['text', 'number', 'custom'].includes(cellType)) {
                            if (cellType === 'custom') {
                                let editorType = this.columns[x].editor.type;
                                return editorType === 'textEditor' ? data : '';
                            } else {
                                return data;
                            }
                        }
                        return '';
                    }

                    let sX = +$this.firstCellCopy[0],
                        tX = +selectedCell[0],
                        sXType = this.columns[sX].type,
                        tXType = this.columns[tX].type;

                    if (+$this.firstCellCopy[0] !== +selectedCell[0]) {

                        if (countCol > 0) {
                            alert('Copy single column each time.');
                            return '';
                        }

                        if (sXType !== tXType) {
                            alert('Can not paste data with different column type.');
                            return '';
                        }
                    }

                    return data;
                },

                onscroll(el) {
                    let selectOpening = $(el).find('select.select2-hidden-accessible');
                    if (selectOpening.length) selectOpening.select2('close')
                },

                oncreateeditor(el, cell, x, y, editor) {
                    if (this.options.columns[x].currency) {
                        editor.setAttribute('data-currency', this.options.columns[x].currency);
                    }
                },

            });
        }

        closeMenu(e) {
            this.sidebar.removeClass('vi-wbe-open')
        }

        openMenu(e) {
            let tab = $(e.currentTarget).data('menu_tab');
            let currentTab = this.sidebar.find(`a.item[data-tab='${tab}']`);
            if (currentTab.hasClass('active') && this.sidebar.hasClass('vi-wbe-open')) {
                this.sidebar.removeClass('vi-wbe-open');
            } else {
                this.sidebar.addClass('vi-wbe-open');
                currentTab.trigger('click');
            }
        }
        toggleSortFields(e) {
            let $sort_fields = this.sidebar.find('#bulky-sort-fields'),
                exclude = this.sidebar.find('#vi-wbe-exclude_edit_fields').val(),
                include = this.sidebar.find('#vi-wbe-edit_fields').val();
            if (include.length){
                $sort_fields.find('.bulky-sort-field').addClass('vi-wbe-hidden');
                $.each(include, function (k,v) {
                    $sort_fields.find('.bulky-sort-field-'+v).removeClass('vi-wbe-hidden');
                });
            }else {
                $sort_fields.find('.bulky-sort-field').removeClass('vi-wbe-hidden');
            }
            if (exclude.length){
                $.each(exclude, function (k,v) {
                    $sort_fields.find('.bulky-sort-field-'+v).addClass('vi-wbe-hidden');
                });
            }
        }

        addNewProduct() {
            if (functions.is_loading()) return;
            let productName = prompt(functions.text('Please enter new product name'));

            if (productName) {
                let $this = this;
                functions.ajax({
                    data: {sub_action: 'add_new_product', product_name: productName},
                    beforeSend() {
                        functions.loading();
                    },
                    success(res) {
                        $this.isAdding = true;
                        $this.WorkBook.insertRow(0, 0, true, true);
                        $this.WorkBook.setRowData(0, res.data, true);
                    },
                    error(res) {
                        console.log(res);
                        alert(res.statusText + res.responseText);
                    },
                    complete() {
                        $this.isAdding = false;
                        functions.removeLoading();
                    }
                })
            }
        }

        addNewCoupon() {
            if (functions.is_loading()) return;

            let $this = this;

            functions.ajax({
                data: {sub_action: 'add_new_coupon'},
                beforeSend() {
                    functions.loading();
                },
                success(res) {
                    $this.isAdding = true;
                    $this.WorkBook.insertRow(0, 0, true, true);
                    $this.WorkBook.setRowData(0, res.data, true);
                },
                error(res) {
                    console.log(res);
                    alert(res.statusText + res.responseText);
                },
                complete() {
                    $this.isAdding = false;
                    functions.removeLoading();
                }
            })
        }

        addNewOrder() {
            window.open('post-new.php?post_type=shop_order');
        }

        addNewReply( content, new_comments ) {
            if (functions.is_loading()) return;

            let $this = this;
            functions.ajax({
                data: {sub_action: 'add_new_reply', content, new_comments },
                beforeSend() {
                    functions.loading();
                },
                success(res) {
                    console.log(res.data);
                    $this.isAdding = false;
                    functions.removeLoading();
                    $this.reloadCurrentPage();
                },
                error(res) {
                    console.log(res);
                    alert(res.statusText + res.responseText);
                },
                complete() {
                    $this.isAdding = false;
                    functions.removeLoading();
                }
            })
        }

        toggleFullScreen(e) {
            let body = $('.wp-admin'), screenBtn = $(e.currentTarget);
            body.toggleClass('vi-wbe-full-screen');

            if (body.hasClass('vi-wbe-full-screen')) {
                screenBtn.find('i.icon').removeClass('external alternate').addClass('window close outline');
                screenBtn.attr('title', 'Exit full screen');
            } else {
                screenBtn.find('i.icon').removeClass('window close outline').addClass('external alternate');
                screenBtn.attr('title', 'Full screen');
            }

            $.ajax({
                url: Attributes.ajaxUrl,
                type: 'post',
                dataType: 'json',
                data: {
                    ...Attributes.ajaxData,
                    sub_action: 'set_full_screen_option',
                    status: body.hasClass('vi-wbe-full-screen')
                }
            });
        }

        getAllRows() {
            return this.WorkBook.getData(false, true);
        }

        save() {
            $('td.error').removeClass('error');

            let $this = this,
                products = this.getAllRows(),
                productsForSave = [], skuErrors = [];

            for (let pid of this.compare) {
                for (let product of products) {
                    if (parseInt(product[0]) === parseInt(pid)) {
                        productsForSave.push(product);
                    }
                }
            }

            if (functions.is_loading()) return;

            function saveStep(step = 0) {
                let range = 20,
                    start = step * range,
                    end = start + range,
                    products = productsForSave.slice(start, end),
                    lastStep = step * range >= productsForSave.length;

                if ( products.length === 0 && $this.trash.length === 0 && $this.unTrash.length === 0 && step === 0 ) {
                    functions.notice(functions.text('Nothing change to save'));
                    return;
                }

                if (lastStep && step > 0) {
                    if (skuErrors.length) {
                        functions.notice(functions.text('Invalid or duplicated SKU'));

                        let x = functions.getColFromColumnType('sku');
                        let allRows = $this.WorkBook.getData();
                        if (allRows.length) {
                            for (let y in allRows) {
                                let row = allRows[y];
                                if (skuErrors.includes(row[0])) {
                                    let cell = $this.WorkBook.getCellFromCoords(x, y);
                                    $(cell).addClass('error');
                                }
                            }
                        }
                    }

                    let histories = $this.WorkBook.history;
                    if (histories.length) {
                        for (let history of histories) {
                            if (history.action !== 'deleteRow') continue;
                            let iForDel = [];
                            for (let i in history.rowData) {
                                if (history.rowData[i][1] > 0) {
                                    iForDel.push(parseInt(i));
                                }
                            }

                            if (iForDel.length) {
                                history.rowData = history.rowData.filter((item, i) => !iForDel.includes(i));
                                history.rowNode = history.rowNode.filter((item, i) => !iForDel.includes(i));
                                history.rowRecords = history.rowRecords.filter((item, i) => !iForDel.includes(i));
                                history.numOfRows = history.numOfRows - iForDel.length;
                            }
                        }
                    }

                    $this.saveRevision();
                    functions.showMessage( {title:"Success", message: 'Saved successfully', type: "positive", duration: 3000} );
                    functions.removeLoading();

                    return;
                }

                functions.ajax({
                    data: {
                        sub_action: 'save_products',
                        products: JSON.stringify(products),
                        trash: $this.trash,
                        untrash: $this.unTrash,
                    },
                    beforeSend() {
                        functions.loading();
                    },
                    success(res) {
                        $this.trash = [];
                        $this.unTrash = [];
                        $this.compare = [];
                        $this.menubar.find('.vi-wbe-save-button').removeClass('vi-wbe-saveable');

                        if (res.data.skuErrors) {
                            skuErrors = [...new Set([...skuErrors, ...res.data.skuErrors])];
                        }

                        saveStep(step + 1);
                    },
                    error(res) {
                        console.log(res);
                        functions.showMessage( {title:"Error", message: res.statusText + res.responseText, type: "positive", duration: 3000} );
                        alert(res.statusText + res.responseText);
                    },
                    complete() {
                    }
                });
            }

            saveStep();
        }
        textWrapMode(enable) {
            if (enable){
                $('#vi-wbe-spreadsheet').addClass('vibulky-spreadsheet-wrap-mode');
            }else {
                $('#vi-wbe-spreadsheet').removeClass('vibulky-spreadsheet-wrap-mode');
            }
        }

        loadProducts(page = 1, reCreate = false) {
            let $this = this;

            if (functions.is_loading()) return;
            $this.textWrapMode($this.sidebar.find('input[name="wrap_mode"]').prop('checked'));
            functions.ajax({
                data: {
                    sub_action: 'load_products',
                    page: page,
                    re_create: reCreate
                },
                beforeSend() {
                    functions.loading();
                },
                success(res) {
                    if (res.success) {
                        Attributes.imgStorage = res.data.img_storage;

                        if (reCreate) {
                            $this.WorkBook.destroy();

                            if (res.data.columns) Attributes.setColumns(res.data.columns);
                            if (res.data.idMapping) Attributes.idMapping = res.data.idMapping;
                            if (res.data.idMappingFlip) Attributes.idMappingFlip = res.data.idMappingFlip;

                            $this.workBookInit();
                        }

                        $this.WorkBook.options.data = res.data.products;
                        $this.WorkBook.setData();
                        $this.pagination(res.data.max_num_pages, page);
                        $this.WorkBook.orderAfterLoad();

                        if (!res.data.products.length) {
                            functions.notice(functions.text('No item was found'));
                        }
                    }
                },
                error(res) {
                    console.log(res);
                    alert(res.statusText + res.responseText);
                },
                complete() {
                    functions.removeLoading();
                }
            });
        }

        pagination(maxPage, currentPage) {
            this.menubar.find('.vi-wbe-pagination').html(functions.pagination(maxPage, currentPage));
        }

        changePage(e) {
            let page = parseInt($(e.currentTarget).attr('data-page'));
            if ($(e.currentTarget).hasClass('active') || $(e.currentTarget).hasClass('disabled') || !page) return;
            this.loadProducts(page);
        }

        changePageByInput(e) {
            let page = parseInt($(e.target).val());
            let max = parseInt($(e.target).attr('max'));

            if (page <= max && page > 0) this.loadProducts(page);
        }

        reloadCurrentPage() {
            this.loadProducts(this.getCurrentPage())
        }

        getCurrentPage() {
            return this.menubar.find('.vi-wbe-pagination .item.active').data('page') || 1;
        }

        afterAddFilter(ev, data) {
            Attributes.imgStorage = data.img_storage;
            this.WorkBook.options.data = data.products;
            this.WorkBook.setData();
            this.pagination(data.max_num_pages, 1);
            this.WorkBook.orderAfterLoad();
            this.sidebar.find('.vi-wbe-close-sidebar').trigger('click');
            if (!data.products.length) functions.notice(functions.text('No item was found'))
        }

        afterSaveSettings(ev, data) {
            if (data.fieldsChange) {
                this.loadProducts(this.getCurrentPage(), true);
            }
            if (data?.fieldsRefresh){
                this.menubar.find('.vi-wbe-get-product').trigger('click');
            }else {
                this.sidebar.find('.vi-wbe-close-sidebar').trigger('click');
            }
        }

        saveRevision() {
            let $this = this;
            if (Object.keys($this.revision).length) {
                let currentPage = $this.sidebar.find('.vi-wbe-pagination a.item.active').data('page') || 1;
                functions.ajax({
                    data: {sub_action: 'auto_save_revision', data: $this.revision, page: currentPage || 1},
                    success(res) {
                        if (res.success) {
                            if (res.data.updatePage) $('#vi-wbe-history-points-list tbody').html(res.data.updatePage);
                            $this.revision = {};
                            $this.sidebar.find('.vi-wbe-pagination').html(functions.pagination(res.data.pages, currentPage));
                        }
                    },
                    error(res) {
                        console.log(res);
                    }
                });
            }
        }

    }

    new BulkEdit();
});

})();

/******/ })()
;