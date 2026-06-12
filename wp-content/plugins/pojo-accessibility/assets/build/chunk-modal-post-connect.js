"use strict";(globalThis.webpackChunkpojo_accessibility=globalThis.webpackChunkpojo_accessibility||[]).push([[1032],{62312(e,o,t){t.r(o),t.d(o,{default:()=>u});var i=t(33494),a=t(41300),n=t(85848),c=t(95231),s=t(96989),r=t(9626),l=t(50438),d=t(67159),p=t(66231),x=t(86087),h=t(27723),b=t(10790);const m=(0,c.I)(i.A)`
	display: flex;
	flex-direction: column;
	justify-content: center;
	align-items: center;

	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);

	width: 600px;
	max-width: 100%;
	height: 530px;
	background-color: ${({theme:e})=>e.palette.background.paper};
	padding: 20px;
	text-align: center;
	border-radius: ${({theme:e})=>e.shape.borderRadius}px;
`,u=()=>{const{isOpen:e,close:o}=(0,r.hS)(),{save:t}=(0,r.rX)();(0,x.useEffect)(()=>{e&&d.K.sendEvent(p.m.connectSuccess)},[e]);const i=async()=>{await t({ea11y_close_post_connect_modal:!0}),o()};return(0,b.jsx)(a.A,{open:e,onClose:i,children:(0,b.jsxs)(m,{container:!0,sx:{boxShadow:24},role:"dialog","aria-modal":"true","aria-labelledby":"post-connect-modal-title","aria-describedby":"post-connect-modal-description",children:[(0,b.jsx)(l.Zw,{}),(0,b.jsx)(n.A,{variant:"h5",color:"text.primary",marginBlockStart:5,marginBlockEnd:1,id:"post-connect-modal-title",children:(0,h.__)("You're all set","pojo-accessibility")}),(0,b.jsx)(n.A,{variant:"body2",sx:{width:"500px",maxWidth:"100%"},color:"text.primary",marginBlockEnd:5,id:"post-connect-modal-description",children:(0,h.__)("Ally - Web Accessibility is now connected and ready to use on your site.","pojo-accessibility")}),(0,b.jsx)(s.A,{variant:"contained",sx:{padding:"8px 22px",width:"300px"},onClick:i,color:"primary",children:(0,h.__)("Done","pojo-accessibility")})]})})}}}]);