!function(){"use strict";function t(e){return t="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},t(e)}function e(e,r,n){return(r=function(e){var r=function(e){if("object"!=t(e)||!e)return e;var r=e[Symbol.toPrimitive];if(void 0!==r){var n=r.call(e,"string");if("object"!=t(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(e)}(e);return"symbol"==t(r)?r:r+""}(r))in e?Object.defineProperty(e,r,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[r]=n,e}function r(t,e){(null==e||e>t.length)&&(e=t.length);for(var r=0,n=Array(e);r<e;r++)n[r]=t[r];return n}var n=window.React,o=window.wp.i18n,i=window.wc.wcBlocksRegistry,a=window.wp.htmlEntities,c=window.wc.wcSettings,l=window.wp.hooks;function u(t,e){var r=Object.keys(t);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(t);e&&(n=n.filter((function(e){return Object.getOwnPropertyDescriptor(t,e).enumerable}))),r.push.apply(r,n)}return r}inpsydeGateways.forEach((function(t){var f=(0,c.getSetting)("".concat(t,"_data"),{}),s="".concat(t,"_checkout_fields"),y=(0,o.__)("Syde Payment Gateway","syde-payment-gateway"),p=(0,a.decodeEntities)(f.title)||y,m=function(t){var o,i,c=(o=(0,n.useState)([]),i=2,function(t){if(Array.isArray(t))return t}(o)||function(t,e){var r=null==t?null:"undefined"!=typeof Symbol&&t[Symbol.iterator]||t["@@iterator"];if(null!=r){var n,o,i,a,c=[],l=!0,u=!1;try{if(i=(r=r.call(t)).next,0===e){if(Object(r)!==r)return;l=!1}else for(;!(l=(n=i.call(r)).done)&&(c.push(n.value),c.length!==e);l=!0);}catch(t){u=!0,o=t}finally{try{if(!l&&null!=r.return&&(a=r.return(),Object(a)!==a))return}finally{if(u)throw o}}return c}}(o,i)||function(t,e){if(t){if("string"==typeof t)return r(t,e);var n={}.toString.call(t).slice(8,-1);return"Object"===n&&t.constructor&&(n=t.constructor.name),"Map"===n||"Set"===n?Array.from(t):"Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?r(t,e):void 0}}(o,i)||function(){throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()),y=c[0],p=c[1];return(0,n.useEffect)((function(){p(l.defaultHooks.applyFilters(s,[]))}),[]),Array.isArray(y)&&y.length?(0,n.createElement)(n.Fragment,null,y.map((function(r){return(0,n.createElement)(r,function(t){for(var r=1;r<arguments.length;r++){var n=null!=arguments[r]?arguments[r]:{};r%2?u(Object(n),!0).forEach((function(r){e(t,r,n[r])})):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(n)):u(Object(n)).forEach((function(e){Object.defineProperty(t,e,Object.getOwnPropertyDescriptor(n,e))}))}return t}({},t))}))):(0,n.createElement)((function(){return(0,a.decodeEntities)(f.description||"")}),null)},b={name:t,label:(0,n.createElement)((function(t){var e=t.components,r=e.PaymentMethodLabel,o=e.PaymentMethodIcons;return(0,n.createElement)(n.Fragment,null,(0,n.createElement)(r,{text:p}),(0,n.createElement)(o,{icons:f.icons}))}),null),content:(0,n.createElement)(m,null),edit:(0,n.createElement)(m,null),icons:f.icons,canMakePayment:function(){return!0},ariaLabel:p,supports:{features:f.supports}};f.placeOrderButtonLabel&&(b.placeOrderButtonLabel=f.placeOrderButtonLabel),(0,i.registerPaymentMethod)(b)}))}();