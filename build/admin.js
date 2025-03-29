(()=>{"use strict";var e,t,n={3:(e,t,n)=>{n.d(t,{Z:()=>d,t:()=>h});var s=n(609);const r=window.wp.apiFetch;var a=n.n(r),i=n(723),o=n(790);const l="wp-graphql-login/v1/settings",c=(0,s.createContext)({isConditionMet:()=>!0,settings:void 0,updateSettings:()=>{},saveSettings:async()=>!1,isDirty:!1,isSaving:!1,isComplete:!1,errorMessage:void 0,showAdvancedSettings:!1}),d=({children:e})=>{const[t,n]=(0,s.useState)(void 0),[r,d]=(0,s.useState)(),[h,g]=(0,s.useState)(void 0),[p,u]=(0,s.useState)(void 0),v=h&&JSON.stringify(h)!==JSON.stringify(p)||!1,m="saving"===t,w="complete"===t,f=!!h?.wpgraphql_login_settings?.show_advanced_settings;(0,s.useEffect)((()=>{try{a()({path:l}).then((e=>{u(e),g(e)}))}catch(e){e instanceof Error?d(e.message):d((0,i.__)("Unable to save settings. An unknown error occurred","wp-graphql-headless-login"))}finally{n("complete")}}),[]);const _=({settingKey:e,field:t})=>{const n=wpGraphQLLogin?.settings?.[e]?.fields?.[t]?.conditionalLogic;return!n||(Array.isArray(n)?n:[n]).every((t=>{const{slug:n,operator:s,value:r}=t,[a,i]=n.includes(".")?n.split("."):[e,n],o=h?.[a]?.[i];if(!o)return!1;if(wpGraphQLLogin?.settings?.[a]?.fields?.[i]?.conditionalLogic&&!_({settingKey:a,field:i}))return!1;switch(s){case"==":return o===r;case"!=":return o!==r;case">":return o>r;case"<":return o<r;case">=":return o>=r;case"<=":return o<=r;default:return!0}}))};return(0,o.jsx)(c.Provider,{value:{settings:h,isConditionMet:_,updateSettings:({slug:e,values:t})=>{g((n=>n?{...n,[e]:t}:{[e]:t}))},saveSettings:async e=>{n("saving");try{const t=await a()({path:l,method:"POST",data:{slug:e,values:h?.[e]}});return u(t),g(t),d(void 0),n("complete"),!0}catch(e){return e instanceof Error&&d(e.message),n("complete"),!1}},isComplete:w,isDirty:v,isSaving:m,errorMessage:r,showAdvancedSettings:f},children:e})},h=()=>{if(!c)throw new Error("useSettings must be used within a SettingsProvider");return(0,s.useContext)(c)}},93:(e,t,n)=>{n.d(t,{C:()=>v});var s=n(427);const r=window.wp.compose;var a=n(164);var i=n(790),o=n(609),l=n(143),c=n(723),d=n(3);const h={text:s.TextControl,toggle:s.ToggleControl,select:s.SelectControl,formTokenField:function({help:e,...t}){const n=(0,r.useInstanceId)(s.FormTokenField);return(0,i.jsxs)("fieldset",{className:"components-form-token-field-control",children:[(0,i.jsx)(s.FormTokenField,{...t}),e&&(0,i.jsx)("p",{id:`components-form-token-additional-help-${n}`,className:(0,a.A)("help components-form-token-field__help","BxlpBjGYk_tWlzwOf33E"),dangerouslySetInnerHTML:{__html:e}})]})},jwtSecret:function({label:e,help:t}){const{updateSettings:n,saveSettings:r,errorMessage:a,isSaving:h}=(0,d.t)();(0,o.useEffect)((()=>{!h&&a&&(0,l.dispatch)("core/notices").createErrorNotice((0,c.sprintf)(
// translators: %s: error message
// translators: %s: error message
(0,c.__)("The JWT secret could not be regenerated. Please try again later. Error: %s","wp-graphql-headless-login"),a),{type:"snackbar",isDismissible:!0})}),[h,a]);const g=wpGraphQLLogin?.secret||{};return(0,i.jsx)(i.Fragment,{children:(0,i.jsxs)(s.BaseControl,{className:"wp-graphql-headless-login__secret",id:"wp-graphql-headless-login__secret--control",help:t,children:[(0,i.jsx)(s.Button,{text:e,icon:"admin-network",disabled:!!g?.isConstant,isDestructive:!0,isBusy:h,iconSize:16,variant:"secondary",onClick:()=>{(async()=>{await n({slug:"wpgraphql_login_settings",values:{jwt_secret_key:""}}),await r("wpgraphql_login_settings")&&(0,l.dispatch)("core/notices").createNotice("success",(0,c.__)("The old JWT secret has been invalidated.","wp-graphql-headless-login"),{type:"snackbar",isDismissible:!0})})()}}),!!g?.isConstant&&(0,i.jsx)("p",{children:(0,i.jsx)("strong",{children:(0,c.__)("The JWT secret is set in wp-config.php and cannot be changed on the backend.","wp-graphql-headless-login")})})]})})}},g=({controlType:e,description:t,disabled:n,help:s,isAdvanced:r,label:a,onChange:o,required:l,type:c,value:d,...g})=>{const p=e||(e=>{switch(e){case"string":case"integer":return"text";case"boolean":return"toggle";case"array":return"formTokenField";default:return console.warn(`Unknown field type: ${e}`),"text"}})(c),u=h?.[p]||void 0;if(!u)return null;const v=null!=d?d:g?.default;let m={label:a||t,required:l||!1,help:s||void 0,disabled:n||!1};switch(p){case"text":if("string"===c){m={...m,value:v||"",onChange:o};break}"integer"===c&&(m={...m,value:v?parseInt(v):"",onChange:e=>o(parseInt(e)),type:"number"});break;case"toggle":m={...m,checked:!!v||!1,onChange:e=>o(!!e)};break;case"select":m={...m,value:v||"",onChange:o,options:g?.enum?.map((e=>({label:e.charAt(0).toUpperCase()+e.slice(1),value:e})))||[]};break;case"formTokenField":m={...m,onChange:o,tokenizeOnSpace:!0,value:v||[]};break;case"jwtSecret":m={help:s||"",label:a||""}}return(0,i.jsx)(u,{...m})},p=({isAdvanced:e,children:t})=>{const{showAdvancedSettings:n}=(0,d.t)();return!n&&e?null:(0,i.jsx)(s.PanelRow,{children:t})},u=({field:e,value:t,setValue:n,isConditionMet:s=!0})=>(0,i.jsx)(p,{isAdvanced:!!e.isAdvanced,children:(0,i.jsx)(g,{...e,value:t,onChange:e=>{n(e)},disabled:!s})}),v=({excludedProperties:e,fields:t,values:n,setValue:s,validateConditionalLogic:r})=>{if(!n)return null;const a=e||["id","order"],o=Object.keys(t)?.sort(((e,n)=>(t[e]?.order||0)>(t[n]?.order||0)?1:-1));return(0,i.jsx)(i.Fragment,{children:o?.map((e=>{if(a.includes(e))return null;if(t[e]?.hidden)return null;const o=!r||r(e);return(0,i.jsx)(u,{field:t[e],value:n[e],isConditionMet:o,setValue:t=>{s({...n,[e]:t})}},e)}))})}},143:e=>{e.exports=window.wp.data},164:(e,t,n)=>{function s(e){var t,n,r="";if("string"==typeof e||"number"==typeof e)r+=e;else if("object"==typeof e)if(Array.isArray(e)){var a=e.length;for(t=0;t<a;t++)e[t]&&(n=s(e[t]))&&(r&&(r+=" "),r+=n)}else for(n in e)e[n]&&(r&&(r+=" "),r+=n);return r}n.d(t,{A:()=>r});const r=function(){for(var e,t,n=0,r="",a=arguments.length;n<a;n++)(e=arguments[n])&&(t=s(e))&&(r&&(r+=" "),r+=t);return r}},427:e=>{e.exports=window.wp.components},582:e=>{e.exports=window.wp.coreData},609:e=>{e.exports=window.React},713:(e,t,n)=>{n.d(t,{h:()=>l});var s,r,a,i=n(609);function o(){return o=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var s in n)({}).hasOwnProperty.call(n,s)&&(e[s]=n[s])}return e},o.apply(null,arguments)}var l=function(e){return i.createElement("svg",o({xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 256 256"},e),s||(s=i.createElement("defs",null,i.createElement("filter",{id:"logo_svg__luminosity-invert-noclip",width:132.09,height:32766,x:64.02,y:-8591,colorInterpolationFilters:"sRGB",filterUnits:"userSpaceOnUse"},i.createElement("feColorMatrix",{result:"invert",values:"-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0 0 1 0"}),i.createElement("feFlood",{floodColor:"#fff",result:"bg"}),i.createElement("feBlend",{in:"invert",in2:"bg"})),i.createElement("style",null,".logo_svg__cls-6{fill:#f9921e}"),i.createElement("mask",{id:"logo_svg__mask",width:132.09,height:32766,x:64.02,y:-8591,maskUnits:"userSpaceOnUse"},i.createElement("g",{filter:"url(#logo_svg__luminosity-invert-noclip)"})))),r||(r=i.createElement("g",{id:"logo_svg__Banner"},i.createElement("path",{id:"logo_svg__Background",fill:"#dedede",d:"M186.03 164.84h1179.35v543.5H186.03z",opacity:.2,transform:"rotate(-30 775.697 436.587)"}))),a||(a=i.createElement("g",{id:"logo_svg__Logo"},i.createElement("path",{fill:"#43646b",d:"M189.12 106.23V71.3a61.45 61.45 0 0 0-122.89 0v1.22A4.74 4.74 0 0 0 71 77.26h16.46a4.74 4.74 0 0 0 4.74-4.74V71.3a35.48 35.48 0 1 1 71 0v34.94"}),i.createElement("path",{fill:"#0e2339",d:"M163.16 106.24H58.32a4.75 4.75 0 0 0-4.74 4.76l.1 114a4.74 4.74 0 0 0 4.74 4.74l137.22-.1a4.74 4.74 0 0 0 4.73-4.74v-114a4.76 4.76 0 0 0-1.4-3.36 4.7 4.7 0 0 0-3.34-1.37h-6.48"}),i.createElement("g",{mask:"url(#logo_svg__mask)"},i.createElement("path",{d:"M191.9 191.75a8.26 8.26 0 0 0-9.73 1.18c-1.55 1.54-2.08 2.7-2.75 5.95-.85 4-2.72 5.81-6.11 5.81a6 6 0 0 1-5.17-2.74c-.74-1.27-.78-2.22-1-26.33-.18-22.26-.27-25.33-.9-27.68-4.15-16-16.52-26.88-33-28.93-8.21-1-19 2.28-26.37 8.14a35.76 35.76 0 0 0-12.23 17.72l-1.15 3.32-4 1.45a33.7 33.7 0 0 0-14.35 8.84A35.9 35.9 0 0 0 66 172.9c-1.84 5.28-2 7.1-2 25.36v17.18l.88 1.71a9.36 9.36 0 0 0 5.35 4.22c.74.16 4.82.33 9.11.35 5.86 0 8.26-.12 9.74-.53A8.76 8.76 0 0 0 95 213c0-2.59 3.18-5.59 5.91-5.59 3.46 0 5.81 2.33 6.27 6.16a8.56 8.56 0 0 0 4.71 7.16c1.47.8 2 .85 10.84.85s9.39-.05 10.91-.85c1.85-1 3.9-3.19 4.29-4.71.21-.58.33-8.37.33-17.33v-16.28l-1-1.71c-1.64-2.86-3.51-3.88-8.19-4.5-6.37-.81-10-2.52-13.73-6.28a22 22 0 0 1-5.63-11c-1.24-7.33 2.4-15.55 8.82-19.82a18.26 18.26 0 0 1 11.35-3.5 16.4 16.4 0 0 1 6.43 1 20.54 20.54 0 0 1 11.22 9c2.74 4.66 2.67 3.42 2.9 31.61.21 23.05.33 25.58.95 27.52 2.05 6.37 6.32 11.56 11.51 14.1 7.13 3.48 14.12 3.71 20.33.69s11.28-10.24 12.59-18.18c.87-4.93-.33-7.81-3.91-9.59m-73.13-.85 2.84 1 .11 5.63a30 30 0 0 1-.18 5.63 9.3 9.3 0 0 1-1.82-2.33c-4-6.25-12-10.06-20-9.57a22.38 22.38 0 0 0-17.63 10.04l-1.39 2v-10.7c0-11.77.19-13.2 2.36-17.49 1.89-3.74 6.94-8.37 10.29-9.39.71-.23 1 .12 2.23 3.3a37.36 37.36 0 0 0 23.19 21.88",className:"logo_svg__cls-6"})),i.createElement("path",{d:"M132.84 170.69c1.69-1 2.34-1.87 3.22-4.45a12.86 12.86 0 0 0-.94-10.78 5.3 5.3 0 0 0-5.21-2.81 4.33 4.33 0 0 0-3.55 1.3c-4.23 3.65-4 13.36.39 16.43a6.2 6.2 0 0 0 6.09.31",className:"logo_svg__cls-6"}),i.createElement("path",{d:"M137.16 159.52a6.21 6.21 0 0 1-3.83 3.87 9.88 9.88 0 0 1-7.27-.25c-5.24-2.68-5.49-11.14-.46-14.33a6.37 6.37 0 0 1 4.22-1.1c3 0 4.61.6 6.21 2.42a8.63 8.63 0 0 1 1.13 9.39",className:"logo_svg__cls-6"}))))}},723:e=>{e.exports=window.wp.i18n},790:e=>{e.exports=window.ReactJSXRuntime}},s={};function r(e){var t=s[e];if(void 0!==t)return t.exports;var a=s[e]={exports:{}};return n[e](a,a.exports,r),a.exports}r.m=n,r.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return r.d(t,{a:t}),t},r.d=(e,t)=>{for(var n in t)r.o(t,n)&&!r.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},r.f={},r.e=e=>Promise.all(Object.keys(r.f).reduce(((t,n)=>(r.f[n](e,t),t)),[])),r.u=e=>e+".js?ver=6693199b12228bb31bce",r.miniCssF=e=>e+".css",r.g=function(){if("object"==typeof globalThis)return globalThis;try{return this||new Function("return this")()}catch(e){if("object"==typeof window)return window}}(),r.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),e={},t="wp-graphql-headless-login:",r.l=(n,s,a,i)=>{if(e[n])e[n].push(s);else{var o,l;if(void 0!==a)for(var c=document.getElementsByTagName("script"),d=0;d<c.length;d++){var h=c[d];if(h.getAttribute("src")==n||h.getAttribute("data-webpack")==t+a){o=h;break}}o||(l=!0,(o=document.createElement("script")).charset="utf-8",o.timeout=120,r.nc&&o.setAttribute("nonce",r.nc),o.setAttribute("data-webpack",t+a),o.src=n),e[n]=[s];var g=(t,s)=>{o.onerror=o.onload=null,clearTimeout(p);var r=e[n];if(delete e[n],o.parentNode&&o.parentNode.removeChild(o),r&&r.forEach((e=>e(s))),t)return t(s)},p=setTimeout(g.bind(null,void 0,{type:"timeout",target:o}),12e4);o.onerror=g.bind(null,o.onerror),o.onload=g.bind(null,o.onload),l&&document.head.appendChild(o)}},r.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},(()=>{var e;r.g.importScripts&&(e=r.g.location+"");var t=r.g.document;if(!e&&t&&(t.currentScript&&"SCRIPT"===t.currentScript.tagName.toUpperCase()&&(e=t.currentScript.src),!e)){var n=t.getElementsByTagName("script");if(n.length)for(var s=n.length-1;s>-1&&(!e||!/^http(s?):/.test(e));)e=n[s--].src}if(!e)throw new Error("Automatic publicPath is not supported in this browser");e=e.replace(/^blob:/,"").replace(/#.*$/,"").replace(/\?.*$/,"").replace(/\/[^\/]+$/,"/"),r.p=e})(),(()=>{if("undefined"!=typeof document){var e={884:0};r.f.miniCss=(t,n)=>{e[t]?n.push(e[t]):0!==e[t]&&{767:1}[t]&&n.push(e[t]=(e=>new Promise(((t,n)=>{var s=r.miniCssF(e),a=r.p+s;if(((e,t)=>{for(var n=document.getElementsByTagName("link"),s=0;s<n.length;s++){var r=(i=n[s]).getAttribute("data-href")||i.getAttribute("href");if("stylesheet"===i.rel&&(r===e||r===t))return i}var a=document.getElementsByTagName("style");for(s=0;s<a.length;s++){var i;if((r=(i=a[s]).getAttribute("data-href"))===e||r===t)return i}})(s,a))return t();((e,t,n,s,a)=>{var i=document.createElement("link");i.rel="stylesheet",i.type="text/css",r.nc&&(i.nonce=r.nc),i.onerror=i.onload=n=>{if(i.onerror=i.onload=null,"load"===n.type)s();else{var r=n&&n.type,o=n&&n.target&&n.target.href||t,l=new Error("Loading CSS chunk "+e+" failed.\n("+r+": "+o+")");l.name="ChunkLoadError",l.code="CSS_CHUNK_LOAD_FAILED",l.type=r,l.request=o,i.parentNode&&i.parentNode.removeChild(i),a(l)}},i.href=t,document.head.appendChild(i)})(e,a,0,t,n)})))(t).then((()=>{e[t]=0}),(n=>{throw delete e[t],n})))}}})(),(()=>{var e={884:0};r.f.j=(t,n)=>{var s=r.o(e,t)?e[t]:void 0;if(0!==s)if(s)n.push(s[2]);else{var a=new Promise(((n,r)=>s=e[t]=[n,r]));n.push(s[2]=a);var i=r.p+r.u(t),o=new Error;r.l(i,(n=>{if(r.o(e,t)&&(0!==(s=e[t])&&(e[t]=void 0),s)){var a=n&&("load"===n.type?"missing":n.type),i=n&&n.target&&n.target.src;o.message="Loading chunk "+t+" failed.\n("+a+": "+i+")",o.name="ChunkLoadError",o.type=a,o.request=i,s[1](o)}}),"chunk-"+t,t)}};var t=(t,n)=>{var s,a,[i,o,l]=n,c=0;if(i.some((t=>0!==e[t]))){for(s in o)r.o(o,s)&&(r.m[s]=o[s]);l&&l(r)}for(t&&t(n);c<i.length;c++)a=i[c],r.o(e,a)&&e[a]&&e[a][0](),e[a]=0},n=globalThis.webpackChunkwp_graphql_headless_login=globalThis.webpackChunkwp_graphql_headless_login||[];n.forEach(t.bind(null,0)),n.push=t.bind(null,n.push.bind(n))})();const a=window.wp.domReady;var i=r.n(a);const o=window.wp.element,l=window.wp.hooks;var c=r(609),d=r(790);class h extends c.Component{constructor(e){super(e),this.state={hasError:!1}}static getDerivedStateFromError(e){return{hasError:!0}}componentDidCatch(e,t){console.error("Uncaught error:",e,t),this.setState({error:e,errorInfo:t})}render(){return this.state.hasError?this.props.fallback||(0,d.jsxs)("div",{children:[(0,d.jsx)("h1",{children:"Something went wrong."}),this.props.showErrorInfo&&this.state.errorInfo&&(0,d.jsxs)("details",{style:{whiteSpace:"pre-wrap"},children:[this.state.error&&this.state.error.toString(),(0,d.jsx)("br",{}),this.state.errorInfo.componentStack]})]}):this.props.children}}var g=r(723),p=r(713),u=r(164);const v=(0,c.forwardRef)(((e,t)=>{const{size:n=24,name:s="headless-login-logo",onClick:r,classNames:a,icon:i,...o}=e,l=`svg-icon-${s}`,c=(0,u.A)("ftjQzMCOi3nyOhVCafcP",l,a);return(0,d.jsx)(i,{xmlns:"http://www.w3.org/2000/svg",className:c,height:n,width:n,onClick:r,ref:t,...o})})),m=(0,c.memo)(v),w=({size:e})=>(0,d.jsx)(d.Fragment,{children:(0,d.jsx)(m,{icon:p.h,size:e,className:"headless-login-logo"})});var f=r(3),_=r(427);const x=()=>{const{showAdvancedSettings:e,updateSettings:t,saveSettings:n,isSaving:s,settings:r}=(0,f.t)(),[a,i]=(0,o.useState)(!1);(0,o.useEffect)((()=>{(async()=>{a&&!s&&(await n("wpgraphql_login_settings"),i(!1))})()}),[a,s,n]);const l=(0,g.__)("Show advanced settings","wp-graphql-headless-login");return(0,d.jsx)(_.ToggleControl,{className:"wp-graphql-headless-login__advanced-settings-toggle",checked:e,label:l,disabled:s,onChange:async e=>{await t({slug:"wpgraphql_login_settings",values:{...r?.wpgraphql_login_settings,show_advanced_settings:e}}),i(!0)}})},b=window.wp.primitives,y=(0,d.jsx)(b.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24",children:(0,d.jsx)(b.Path,{d:"M10 17.389H8.444A5.194 5.194 0 1 1 8.444 7H10v1.5H8.444a3.694 3.694 0 0 0 0 7.389H10v1.5ZM14 7h1.556a5.194 5.194 0 0 1 0 10.39H14v-1.5h1.556a3.694 3.694 0 0 0 0-7.39H14V7Zm-4.5 6h5v-1.5h-5V13Z"})}),j=e=>("wpgraphql_login_"+e.replace(/-/g,"_")).toLowerCase(),S=(0,c.createContext)({currentScreen:"providers",setCurrentScreen:()=>{}}),C=({children:e})=>{const[t,n]=(0,c.useState)("providers");return(0,c.useEffect)((()=>{(0,c.startTransition)((()=>{const e=new URL(window.location.href).searchParams.get("screen");e&&(e=>{const t=Object.keys(wpGraphQLLogin.settings),n=j(e);return t.includes(n)})(e)&&n(e)}))}),[]),(0,d.jsx)(S.Provider,{value:{currentScreen:t,setCurrentScreen:n},children:e})},k=()=>{const e=(0,c.useContext)(S);if(!e)throw new Error("useCurrentScreen must be used within a ScreenProvider");return e},E=()=>(0,d.jsx)(_.Icon,{icon:y,className:"h_MlOFn6npizLjVSGKTw",size:16}),q=({screen:e,title:t,currentScreen:n,handleMenuClick:s,isDirty:r,isSaving:a})=>(0,d.jsx)("li",{role:"menuitem",children:(0,d.jsxs)(_.Button,{className:n===e?"Imo7qRxqF81yxZJ6tWwN":"",variant:"tertiary",onClick:()=>s(e),disabled:a,children:[t,r&&e===n&&(0,d.jsx)("span",{className:"Prtzu3hPBp5xAS7XSKxZ","aria-label":(0,g.__)("Unsaved changes","wp-graphql-headless-login")})]},e)},e),N=({handleSaveAndContinue:e,handleCancel:t})=>(0,d.jsxs)(_.Modal,{title:(0,g.__)("Unsaved Changes","wp-graphql-headless-login"),onRequestClose:t,children:[(0,d.jsx)("p",{children:(0,g.__)("You have unsaved changes. Do you want to save them before switching screens?","wp-graphql-headless-login")}),(0,d.jsxs)(_.Flex,{direction:"row",justify:"flex-end",children:[(0,d.jsx)(_.Button,{variant:"tertiary",onClick:t,children:(0,g.__)("Cancel","wp-graphql-headless-login")}),(0,d.jsx)(_.Button,{variant:"primary",onClick:e,children:(0,g.__)("Save and continue","wp-graphql-headless-login")})]})]}),A=()=>{const{currentScreen:e,setCurrentScreen:t}=k(),{isDirty:n,isSaving:s,saveSettings:r}=(0,f.t)(),[a,i]=(0,c.useState)(!1),[o,l]=(0,c.useState)(null),h=e=>{n?(l(e),i(!0)):t(e)},p=(()=>{const e=wpGraphQLLogin.settings,t={providers:""};for(const n in e){const s=e[n].label||(0,g.__)("Providers","wp-graphql-headless-login");t[n.replace("wpgraphql_login_","").replace(/_/g,"-")]=s}return t})();return(0,d.jsxs)(d.Fragment,{children:[(0,d.jsx)(_.NavigableMenu,{orientation:"horizontal",children:(0,d.jsxs)("ul",{role:"menubar",className:"X33eI7IqIu9vA70aUBYr",children:[Object.entries(p).map((([t,r])=>(0,d.jsx)(q,{screen:t,title:r,currentScreen:e,handleMenuClick:h,isDirty:n,isSaving:s},t))),(0,d.jsx)("li",{role:"menuitem",children:(0,d.jsx)(_.Button,{href:"https://github.com/AxeWP/wp-graphql-headless-login/blob/main/docs/reference/settings.md",variant:"tertiary",target:"_blank",rel:"noreferrer",className:"wp-graphql-headless-login__menu-item",icon:E,iconPosition:"right",label:(0,g.__)("Docs","wp-graphql-headless-login"),children:(0,g.__)("Docs","wp-graphql-headless-login")})})]})}),a&&(0,d.jsx)(N,{handleSaveAndContinue:async()=>{if(o){const n=j(e);await r(n),t(o),i(!1),l(null)}},handleCancel:()=>{i(!1),l(null)}})]})},L=()=>(0,d.jsxs)("header",{className:"lghOuE3jVD20udRv8ib7",children:[(0,d.jsx)(w,{size:90}),(0,d.jsxs)("div",{className:"KrbMjTYAeZBM3uoVq0mO",children:[(0,d.jsx)("h1",{children:(0,g.__)("Headless Login Settings","wp-graphql-headless-login")}),(0,d.jsx)(A,{})]}),(0,d.jsx)("div",{className:"eQCzBDGD0s6DcAiwfhSo",children:(0,d.jsx)(x,{})})]});var P=r(143);const T=window.wp.notices,M=()=>{const e=(0,P.useSelect)((e=>e(T.store)?.getNotices().filter((e=>"snackbar"===e.type))),[]),{removeNotice:t}=(0,P.useDispatch)(T.store);return e?.length?(0,d.jsx)("div",{className:"yMxOV1PEJdGbJZTp3kP8",children:(0,d.jsx)(_.SnackbarList,{className:"edit-site-notices",notices:e,onRemove:t})}):(0,d.jsx)(d.Fragment,{})},B=({className:e,...t})=>{const n=(0,u.A)("wp-graphql-headless-login__loading",e);return(0,d.jsx)(_.Spinner,{className:n,...t})};var O=r(93);const D=({settingKey:e})=>{const{settings:t,updateSettings:n,saveSettings:s,isComplete:r,isSaving:a,isDirty:i,errorMessage:o,isConditionMet:l}=(0,f.t)(),h=wpGraphQLLogin?.settings?.[e]?.fields||void 0,p=t?.[e]||{};return(0,c.useEffect)((()=>{o&&!a&&(0,P.dispatch)("core/notices").createErrorNotice((0,g.sprintf)(
// translators: %s: Error message.
// translators: %s: Error message.
(0,g.__)("Error saving settings: %s","wp-graphql-headless-login"),o),{type:"snackbar",isDismissible:!0})}),[o,a]),t&&h?(0,d.jsxs)(d.Fragment,{children:[(0,d.jsx)(_.PanelBody,{children:(0,d.jsx)(O.C,{fields:h,values:p,setValue:t=>{n({slug:e,values:t})},excludedProperties:void 0,validateConditionalLogic:t=>l({settingKey:e,field:t})})}),(0,d.jsxs)(_.Button,{isBusy:a,onClick:async()=>{a||(await s(e),r&&!o&&(0,P.dispatch)("core/notices").createNotice("success",(0,g.__)("Settings saved","wp-graphql-headless-login"),{type:"snackbar",isDismissible:!0}))},disabled:!i||a,variant:"primary",children:[(0,g.__)("Save","wp-graphql-headless-login"),a&&(0,d.jsx)(_.Spinner,{})]})]}):null},F=(0,c.lazy)((()=>r.e(767).then(r.bind(r,767)))),I=({title:e,children:t,className:n,description:s})=>{const r=(0,u.A)("avlawdsXKOLmp_izGG8Q",n);return(0,d.jsxs)(_.Panel,{className:r,children:[(0,d.jsxs)(_.PanelBody,{children:[(0,d.jsx)(_.PanelRow,{children:(0,d.jsx)("h2",{className:"components-panel__body-title",children:e})}),s&&(0,d.jsx)("div",{dangerouslySetInnerHTML:{__html:s}})]}),t]})},G=()=>{const{currentScreen:e}=k(),t=j(e),n=wpGraphQLLogin?.settings[t]?.title||(0,g.__)("Login Providers","wp-graphql-headless-login"),s=wpGraphQLLogin?.settings[t]?.description||(0,g.__)("Configure the Authentication Providers that are available to users.","wp-graphql-headless-login");return(0,d.jsx)(c.Suspense,{fallback:(0,d.jsx)(B,{}),children:(0,d.jsx)(I,{title:n,description:s,children:"providers"===e?(0,d.jsx)(F,{}):(0,d.jsx)(D,{settingKey:t})})})},z=()=>(0,d.jsx)(h,{showErrorInfo:!0,children:(0,d.jsxs)(f.Z,{children:[(0,d.jsxs)(C,{children:[(0,d.jsx)(L,{}),(0,d.jsx)(G,{})]}),(0,d.jsx)("div",{className:"wp-graphql-headless-login__notices",children:(0,d.jsx)(M,{})})]})}),R=(0,l.createHooks)();i()((()=>{const e=document.getElementById("wpgraphql_login_settings");e&&(0,o.createRoot)(e).render((0,d.jsx)(c.StrictMode,{children:(0,d.jsx)(z,{})}))})),wpGraphQLLogin.hooks=R})();