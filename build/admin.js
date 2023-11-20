!function(){"use strict";var e=window.React,t=window.wp.element,n=window.wp.hooks,s=window.wp.components,a=window.wp.coreData,l=window.wp.data;const i={hasAccessControlAllowCredentials:!1,hasSiteAddressInOrigin:!1,additionalAuthorizedDomains:[],shouldBlockUnauthorizedDomains:!1,customHeaders:[]},o=(0,t.createContext)({showAdvancedSettings:!1,setShowAdvancedSettings:()=>{},accessControlSettings:{},updateAccessControlSettings:()=>{}}),r=({children:n})=>{const{saveEditedEntityRecord:s}=(0,l.useDispatch)(a.store),[r,c]=(0,a.useEntityProp)("root","site","wpgraphql_login_settings_show_advanced_settings"),[p,g]=(0,a.useEntityProp)("root","site","wpgraphql_login_access_control"),d=(0,t.useCallback)((e=>{g({...p,...e})}),[g,p]);return(0,t.useEffect)((()=>{void 0!==p&&0===Object.keys(p||{})?.length&&g(i)}),[p,g]),(0,e.createElement)(o.Provider,{value:{showAdvancedSettings:!!r,setShowAdvancedSettings:e=>{c(!!e),s("root","site",void 0,{wpgraphql_login_settings_show_advanced_settings:!!e})},accessControlSettings:p,updateAccessControlSettings:d}},n)},c=()=>(0,t.useContext)(o),p={string:s.TextControl,select:s.SelectControl,boolean:s.ToggleControl,array:s.FormTokenField};function g({type:t,description:n,value:s,required:a,label:l,onChange:i,help:o,...r}){const c={label:l||n,required:a||!1,help:o||null};let g;switch(t){case"string":g=r?.enum?.length?p.select:p.string,c.value=s||"",c.onChange=e=>i(e),r?.enum?.length&&(c.options=r.enum.map((e=>({label:e.charAt(0).toUpperCase()+e.slice(1),value:e}))));break;case"integer":g=p.string,c.value=s?parseInt(s):"",c.onChange=e=>i(parseInt(e)),c.type="number";break;case"boolean":g=p.boolean,c.checked=s||!1,c.onChange=e=>i(e);break;case"array":g=p.array,c.onChange=e=>i(e),c.tokenizeOnSpace=!0,c.value=s||[]}const d=g;return(0,e.createElement)(d,{...c})}function d({schema:t,currentValue:n,setValue:a}){const{showAdvancedSettings:l}=c();return t?.hidden||!l&&t?.advanced?null:(0,e.createElement)(s.PanelRow,null,(0,e.createElement)(g,{...t,value:n,onChange:a}))}function h({excludedProperties:t,options:n,optionsSchema:s,setOption:a}){const l=t||["id","order"],i=Object.keys(s)?.sort(((e,t)=>(s[e]?.order||0)>(s[t]?.order||0)?1:-1));return(0,e.createElement)(e.Fragment,null,i?.map((t=>l.includes(t)?null:(0,e.createElement)(d,{key:t,schema:s[t],currentValue:n?.[t],setValue:e=>{a({[t]:e})}}))))}var m,u,_,E=window.wp.i18n;function v(){return v=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var s in n)Object.prototype.hasOwnProperty.call(n,s)&&(e[s]=n[s])}return e},v.apply(this,arguments)}var w=function(t){return e.createElement("svg",v({xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 256 256"},t),m||(m=e.createElement("defs",null,e.createElement("filter",{id:"logo_svg__luminosity-invert-noclip",width:132.09,height:32766,x:64.02,y:-8591,colorInterpolationFilters:"sRGB",filterUnits:"userSpaceOnUse"},e.createElement("feColorMatrix",{result:"invert",values:"-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0 0 1 0"}),e.createElement("feFlood",{floodColor:"#fff",result:"bg"}),e.createElement("feBlend",{in:"invert",in2:"bg"})),e.createElement("style",null,".logo_svg__cls-6{fill:#f9921e}"),e.createElement("mask",{id:"logo_svg__mask",width:132.09,height:32766,x:64.02,y:-8591,maskUnits:"userSpaceOnUse"},e.createElement("g",{filter:"url(#logo_svg__luminosity-invert-noclip)"})))),u||(u=e.createElement("g",{id:"logo_svg__Banner"},e.createElement("path",{id:"logo_svg__Background",fill:"#dedede",d:"M186.03 164.84h1179.35v543.5H186.03z",opacity:.2,transform:"rotate(-30 775.697 436.587)"}))),_||(_=e.createElement("g",{id:"logo_svg__Logo"},e.createElement("path",{fill:"#43646b",d:"M189.12 106.23V71.3a61.45 61.45 0 0 0-122.89 0v1.22A4.74 4.74 0 0 0 71 77.26h16.46a4.74 4.74 0 0 0 4.74-4.74V71.3a35.48 35.48 0 1 1 71 0v34.94"}),e.createElement("path",{fill:"#0e2339",d:"M163.16 106.24H58.32a4.75 4.75 0 0 0-4.74 4.76l.1 114a4.74 4.74 0 0 0 4.74 4.74l137.22-.1a4.74 4.74 0 0 0 4.73-4.74v-114a4.76 4.76 0 0 0-1.4-3.36 4.7 4.7 0 0 0-3.34-1.37h-6.48"}),e.createElement("g",{mask:"url(#logo_svg__mask)"},e.createElement("path",{d:"M191.9 191.75a8.26 8.26 0 0 0-9.73 1.18c-1.55 1.54-2.08 2.7-2.75 5.95-.85 4-2.72 5.81-6.11 5.81a6 6 0 0 1-5.17-2.74c-.74-1.27-.78-2.22-1-26.33-.18-22.26-.27-25.33-.9-27.68-4.15-16-16.52-26.88-33-28.93-8.21-1-19 2.28-26.37 8.14a35.76 35.76 0 0 0-12.23 17.72l-1.15 3.32-4 1.45a33.68 33.68 0 0 0-14.35 8.84A35.93 35.93 0 0 0 66 172.9c-1.84 5.28-2 7.1-2 25.36v17.18l.88 1.71a9.36 9.36 0 0 0 5.35 4.22c.74.16 4.82.33 9.11.35 5.86 0 8.26-.12 9.74-.53A8.76 8.76 0 0 0 95 213c0-2.59 3.18-5.59 5.91-5.59 3.46 0 5.81 2.33 6.27 6.16a8.56 8.56 0 0 0 4.71 7.16c1.47.8 2 .85 10.84.85s9.39-.05 10.91-.85c1.85-1 3.9-3.19 4.29-4.71.21-.58.33-8.37.33-17.33v-16.28l-1-1.71c-1.64-2.86-3.51-3.88-8.19-4.5-6.37-.81-10-2.52-13.73-6.28a22 22 0 0 1-5.63-11c-1.24-7.33 2.4-15.55 8.82-19.82a18.26 18.26 0 0 1 11.35-3.5 16.37 16.37 0 0 1 6.43 1 20.54 20.54 0 0 1 11.22 9c2.74 4.66 2.67 3.42 2.9 31.61.21 23.05.33 25.58.95 27.52 2.05 6.37 6.32 11.56 11.51 14.1 7.13 3.48 14.12 3.71 20.33.69s11.28-10.24 12.59-18.18c.87-4.93-.33-7.81-3.91-9.59Zm-73.13-.85 2.84 1 .11 5.63a30 30 0 0 1-.18 5.63 9.26 9.26 0 0 1-1.82-2.33c-4-6.25-12-10.06-20-9.57a22.38 22.38 0 0 0-17.63 10.04l-1.39 2v-10.7c0-11.77.19-13.2 2.36-17.49 1.89-3.74 6.94-8.37 10.29-9.39.71-.23 1 .12 2.23 3.3a37.36 37.36 0 0 0 23.19 21.88Z",className:"logo_svg__cls-6"})),e.createElement("path",{d:"M132.84 170.69c1.69-1 2.34-1.87 3.22-4.45a12.86 12.86 0 0 0-.94-10.78 5.3 5.3 0 0 0-5.21-2.81 4.33 4.33 0 0 0-3.55 1.3c-4.23 3.65-4 13.36.39 16.43a6.2 6.2 0 0 0 6.09.31Z",className:"logo_svg__cls-6"}),e.createElement("path",{d:"M137.16 159.52a6.21 6.21 0 0 1-3.83 3.87 9.88 9.88 0 0 1-7.27-.25c-5.24-2.68-5.49-11.14-.46-14.33a6.37 6.37 0 0 1 4.22-1.1c3 0 4.61.6 6.21 2.42a8.63 8.63 0 0 1 1.13 9.39Z",className:"logo_svg__cls-6"}))))},y=function(){const{showAdvancedSettings:t,setShowAdvancedSettings:n}=c();return(0,e.createElement)("div",{className:"wp-graphql-headless-login__header"},(0,e.createElement)("h1",{className:"wp-graphql-headless-login__title"},(0,e.createElement)(s.Icon,{icon:(0,e.createElement)(w,null)}),(0,E.__)("Headless Login Settings","wp-graphql-headless-login")," "),void 0!==t&&(0,e.createElement)(s.ToggleControl,{className:"wp-graphql-headless-login__advanced-settings-toggle",label:(0,E.__)("Show advanced settings","wp-graphql-headless-login"),checked:t,onChange:e=>n(e)}),(0,e.createElement)("div",{className:"wp-graphql-headless-login__documentation-link"},(0,e.createElement)("a",{href:"https://github.com/AxeWP/wp-graphql-headless-login-beta/blob/main/docs/settings.md",target:"_blank",rel:"noreferrer"},(0,E.__)("Documentation (WIP)","wp-graphql-headless-login"),(0,e.createElement)(s.Icon,{icon:"external",className:"wp-graphql-headless-login__documentation-link-icon"}))))},b=window.wp.notices,f=function(){const t=(0,l.useSelect)((e=>e(b.store)?.getNotices().filter((e=>"snackbar"===e.type))),[]),{removeNotice:n}=(0,l.useDispatch)(b.store);return t?.length?(0,e.createElement)(s.SnackbarList,{className:"edit-site-notices",notices:t,onRemove:n}):(0,e.createElement)(e.Fragment,null)};function C(){const{accessControlSettings:n,updateAccessControlSettings:i}=c(),{saveEditedEntityRecord:o}=(0,l.useDispatch)(a.store),{lastError:r,isSaving:p,hasEdits:g}=(0,l.useSelect)((e=>({lastError:e(a.store)?.getLastEntitySaveError("root","site"),isSaving:e(a.store)?.isSavingEntityRecord("root","site"),hasEdits:e(a.store)?.hasEditsForEntityRecord("root","site")})),[]),d=wpGraphQLLogin?.settings?.accessControl||{};return(0,t.useEffect)((()=>{r&&(0,l.dispatch)("core/notices").createErrorNotice((0,E.sprintf)(
// translators: %s: Error message.
(0,E.__)("Error saving settings: %s","wp-graphql-headless-login"),r?.message),{type:"snackbar",isDismissible:!0})}),[r]),(0,e.createElement)(e.Fragment,null,(0,e.createElement)(s.PanelBody,null,(0,e.createElement)(s.PanelRow,null,(0,e.createElement)("h2",{className:"components-panel__body-title"},(0,E.__)("Access Control Settings","wp-graphql-headless-login"))),(0,e.createElement)(h,{optionsSchema:d,options:n,setOption:i,excludedProperties:[]})),(0,e.createElement)(s.Button,{variant:"primary",isPrimary:!0,disabled:!g,isBusy:p,onClick:()=>{(async()=>{await o("root","site",void 0,{wpgraphql_login_access_control:n})&&(0,l.dispatch)("core/notices").createNotice("success","Settings saved",{type:"snackbar",isDismissible:!0})})()}},(0,E.__)("Save Access Control Settings","wp-graphql-headless-login"),p&&(0,e.createElement)(s.Spinner,null)))}function S({clientSlug:t,optionsKey:n,options:s,setOption:a}){const l=wpGraphQLLogin?.settings?.providers?.[t]?.[n]?.properties||{};return(0,e.createElement)(h,{optionsSchema:l,options:s,setOption:a,excludedProperties:["id","order"]})}const k={name:"",order:0,isEnabled:!1,clientOptions:{},loginOptions:{useAuthenticationCookie:!1}},q=(0,t.createContext)({activeClient:"",setActiveClient:()=>{},clientConfig:void 0,setClientConfig:()=>{},updateClient:()=>{},setClientOption:()=>{},setLoginOption:()=>{}}),L=({children:n})=>{const[s,l]=(0,t.useState)(Object.keys(wpGraphQLLogin?.settings.providers)?.[0]||""),[i,o]=(0,a.useEntityProp)("root","site",s),r=(0,t.useCallback)(((e,t)=>{const n={...i,[e]:t};o(n)}),[i,o]),c=(0,t.useCallback)((e=>{r("clientOptions",{...i?.clientOptions,...e})}),[i,r]),p=(0,t.useCallback)((e=>{r("loginOptions",{...i?.loginOptions,...e})}),[i,r]);return(0,t.useEffect)((()=>{void 0!==s&&void 0!==i&&0===Object.keys(i||{})?.length&&o({...k,slug:s.replace("wpgraphql_login_provider_","")})}),[i,o,s]),(0,e.createElement)(q.Provider,{value:{activeClient:s,setActiveClient:l,clientConfig:i,setClientConfig:o,updateClient:r,setClientOption:c,setLoginOption:p}},n)},O=()=>(0,t.useContext)(q);function N(){const{accessControlSettings:n}=c(),{activeClient:i,clientConfig:o,setClientConfig:r,updateClient:p,setClientOption:g,setLoginOption:d}=O(),{saveEditedEntityRecord:m}=(0,l.useDispatch)(a.store),{lastError:u,isSaving:_,hasEdits:v}=(0,l.useSelect)((e=>({lastError:e(a.store)?.getLastEntitySaveError("root","site"),isSaving:e(a.store)?.isSavingEntityRecord("root","site"),hasEdits:e(a.store)?.hasEditsForEntityRecord("root","site")})),[]);return(0,t.useEffect)((()=>{u&&(0,l.dispatch)("core/notices").createErrorNotice((0,E.sprintf)(
// translators: %s: Error message.
(0,E.__)("Error saving settings: %s","wp-graphql-headless-login"),u?.data?.params?.[i]||u?.message),{type:"snackbar",isDismissible:!0,explicitDismiss:!0})}),[u,i]),(0,t.useEffect)((()=>{!n?.shouldBlockUnauthorizedDomains&&"wpgraphql_login_provider_siteToken"===i&&o?.isEnabled&&(p("isEnabled",!1),(0,l.dispatch)("core/notices").createErrorNotice((0,E.__)("The Site Token provider can only be enabled if `Access Control Settings: Block unauthorized domains` is enabled.","wp-graphql-headless-login"),{type:"snackbar",isDismissible:!0,explicitDismiss:!0}))}),[n,i,o,p]),i&&o?(0,e.createElement)(e.Fragment,null,(0,e.createElement)(s.PanelBody,null,(0,e.createElement)(s.PanelRow,null,(0,e.createElement)("h2",{className:"components-panel__body-title"},(0,E.sprintf)(
// translators: %s: Client slug.
(0,E.__)("%s Settings","wp-graphql-headless-login"),wpGraphQLLogin?.settings?.providers?.[i]?.name?.default||"Provider"))),(0,e.createElement)(h,{excludedProperties:["loginOptions","clientOptions","order"],options:o,optionsSchema:wpGraphQLLogin?.settings?.providers?.[i],setOption:e=>{r({...o,...e})}}),(0,e.createElement)(S,{clientSlug:i,optionsKey:"clientOptions",options:o?.clientOptions,setOption:g})),(0,e.createElement)(s.PanelBody,null,(0,e.createElement)(s.PanelRow,null,(0,e.createElement)("h2",{className:"components-panel__body-title"},(0,E.__)("Login Settings","wp-graphql-headless-login"),(0,e.createElement)(s.Icon,{icon:"admin-users",className:"components-panel__icon",size:20}))),(0,e.createElement)(S,{clientSlug:i,optionsKey:"loginOptions",options:o?.loginOptions,setOption:d})),(0,e.createElement)((()=>wpGraphQLLogin.hooks.applyFilters("graphql_login_custom_client_settings",(0,e.createElement)(e.Fragment,null),i,o)),null),(0,e.createElement)(s.Button,{isPrimary:!0,variant:"primary",onClick:()=>{(async()=>{await m("root","site",void 0,{[i]:o})&&(0,l.dispatch)("core/notices").createNotice("success","Settings saved",{type:"snackbar",isDismissible:!0})})()},disabled:!v,isBusy:_},(0,E.__)("Save Providers","wp-graphql-headless-login"),_&&(0,e.createElement)(s.Spinner,null))):(0,e.createElement)(s.Placeholder,{icon:(0,e.createElement)(s.Icon,{icon:(0,e.createElement)(w,null)}),title:(0,E.__)("Loading…","wp-graphql-headless-login"),instructions:(0,E.__)("Please wait while the settings are loaded.","wp-graphql-headless-login")})}function P(){const[n,i]=(0,a.useEntityProp)("root","site","wpgraphql_login_settings_jwt_secret_key"),{saveEditedEntityRecord:o}=(0,l.useDispatch)(a.store),{lastError:r,isSaving:c}=(0,l.useSelect)((e=>({lastError:e(a.store)?.getLastEntitySaveError("root","site"),isSaving:e(a.store)?.isSavingEntityRecord("root","site"),hasEdits:e(a.store)?.hasEditsForEntityRecord("root","site")})),[]);(0,t.useEffect)((()=>{r&&(0,l.dispatch)("core/notices").createErrorNotice((0,E.__)("The JWT secret could not be regenerated. Please try again later.","wp-graphql-headless-login"),{type:"snackbar",isDismissible:!0})}),[r,n]);const p=wpGraphQLLogin?.secret||{};return(0,e.createElement)(e.Fragment,null,(0,e.createElement)(s.BaseControl,{className:"wp-graphql-headless-login__secret",id:"wp-graphql-headless-login__secret--control",help:(0,E.__)("The JWT Secret is used to sign the JWT tokens that are used to authenticate requests to the GraphQL API. Changing this secret will invalidate all previously-authenticated requests.","wp-graphql-headless-login")},(0,e.createElement)(s.Button,{isTertiary:!0,text:(0,E.__)("Regenerate JWT secret","wp-graphql-headless-login"),icon:"admin-network",disabled:!!p?.isConstant,isDestructive:!0,isBusy:c,iconSize:16,variant:"tertiary",onClick:()=>{i(""),(async()=>{await o("root","site",void 0,{wpgraphql_login_settings_jwt_secret_key:n})&&(0,l.dispatch)("core/notices").createNotice("success",(0,E.__)("The old JWT secret has been invalidated.","wp-graphql-headless-login"),{type:"snackbar",isDismissible:!0})})()}}),!!p?.isConstant&&(0,e.createElement)("p",null,(0,e.createElement)("strong",null,(0,E.__)("The JWT secret is set in wp-config.php and cannot be changed on the backend.","wp-graphql-headless-login")))))}function A({optionKey:t}){const[n,s]=(0,a.useEntityProp)("root","site",t),l=wpGraphQLLogin?.settings?.plugin?.[t]||{};return(0,e.createElement)(d,{key:t,schema:l,currentValue:n,setValue:e=>s(e)})}const x=()=>wpGraphQLLogin.hooks.applyFilters("graphql_login_custom_plugin_options",(0,e.createElement)(e.Fragment,null));function D(){const{showAdvancedSettings:t}=c(),n=wpGraphQLLogin?.settings?.plugin||{},a=Object.keys(n)?.sort(((e,t)=>(n[e]?.order||0)>(n[t]?.order||0)?1:-1)).filter((e=>!n[e]?.hidden));return(0,e.createElement)(s.PanelBody,null,(0,e.createElement)(s.PanelRow,null,(0,e.createElement)("h2",{className:"components-panel__body-title"},(0,E.__)("Plugin Settings","wp-graphql-headless-login"),(0,e.createElement)(s.Icon,{icon:"admin-tools",className:"components-panel__icon",size:20}))),t&&(0,e.createElement)(P,null),a.map((t=>(0,e.createElement)(A,{optionKey:t,key:t}))),(0,e.createElement)(x,null))}function B({provider:t}){var n;const[s]=(0,a.useEntityProp)("root","site",t),l=null!==(n=s?.isEnabled)&&void 0!==n&&n,i=l?(0,E.__)("Enabled","wp-graphql-headless-login"):(0,E.__)("Disabled","wp-graphql-headless-login");return(0,e.createElement)("div",{className:"wp-graphql-headless-login__menu__status-badge"},(0,e.createElement)("span",{className:"wp-graphql-headless-login__menu__status-badge--"+(l?"enabled":"disabled"),"aria-label":i,title:i}))}function F(){const t=Object.keys(wpGraphQLLogin?.settings?.providers||{}),{activeClient:n,setActiveClient:a}=O();return(0,e.createElement)(s.__experimentalNavigation,{activeItem:n},(0,e.createElement)(s.__experimentalNavigationMenu,{title:(0,E.__)("Providers","wp-graphql-headless-login")},t.length>0&&t.map((t=>(0,e.createElement)(s.__experimentalNavigationItem,{className:"wp-graphql-headless-login__menu__item",key:t,item:t,title:wpGraphQLLogin?.settings?.providers?.[t]?.name?.default,icon:(0,e.createElement)(B,{provider:t}),onClick:()=>a(t)})))))}function R(){return(0,e.createElement)(s.Flex,{className:"wp-graphql-headless-login__main",align:"flex-start"},(0,e.createElement)(L,null,(0,e.createElement)(s.FlexItem,{className:"wp-graphql-headless-login__sidebar"},(0,e.createElement)(F,null)),(0,e.createElement)(s.FlexBlock,null,(0,e.createElement)(s.Panel,{className:"wp-graphql-headless-login__client"},(0,e.createElement)(N,null)))))}var T=function(){return(0,e.createElement)(r,null,(0,e.createElement)(y,null),(0,e.createElement)(R,null),(0,e.createElement)(s.Panel,{className:"wp-graphql-headless-login__plugin-settings"},(0,e.createElement)(D,null)),(0,e.createElement)(s.Panel,{className:"wp-graphql-headless-login__ac-settings"},(0,e.createElement)(C,null)),(0,e.createElement)("div",{className:"wp-graphql-headless-login__notices"},(0,e.createElement)(f,null)))};const G=(0,n.createHooks)();document.addEventListener("DOMContentLoaded",(()=>{const n=document.getElementById("wpgraphql_login_settings");n&&(0,t.render)((0,e.createElement)(T,null),n)})),wpGraphQLLogin.hooks=G}();