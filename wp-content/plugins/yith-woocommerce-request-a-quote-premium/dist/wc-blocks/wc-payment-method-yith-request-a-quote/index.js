(()=>{"use strict";var e={};let t;function n(e){if("string"!=typeof e||-1===e.indexOf("&"))return e;void 0===t&&(t=document.implementation&&document.implementation.createHTMLDocument?document.implementation.createHTMLDocument("").createElement("textarea"):document.createElement("textarea")),t.innerHTML=e;const n=t.textContent;return t.innerHTML="",n}(e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})})(e);var a=wc.wcBlocksRegistry.registerPaymentMethod,r=n(ywraq_gateway_settings.title),o=function(){return n(ywraq_gateway_settings.description||"")},i=function(e){var t=e.components.PaymentMethodLabel;return React.createElement(t,{text:ywraq_gateway_settings.title})};for(var c in a({name:"yith-request-a-quote",label:React.createElement(i,null),content:React.createElement(o,null),edit:React.createElement(o,null),canMakePayment:function(){return!0},ariaLabel:r,supports:{features:["products"]}}),e)this[c]=e[c];e.__esModule&&Object.defineProperty(this,"__esModule",{value:!0})})();