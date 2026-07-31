import { useState } from "react";
import { PaymentSheet } from "./PaymentSheet";
import { ReceiptResult } from "./ReceiptResult";

export default { title: "Retail/Organisms", parameters: { layout: "fullscreen" } };
const cart = { total_net:250, subtotal:250, discount_total:0, items:[{ id:"1" }] };
export const Payment = { render: () => { const [payment,setPayment]=useState({payment_method:"cash",received:"500"}); return <PaymentSheet open cart={cart} payment={payment} online busy={false} onChange={v=>setPayment(p=>({...p,...v}))} onClose={()=>{}} onConfirm={()=>{}} />; } };
export const Receipt = { render: () => <ReceiptResult sale={{folio:"F-000104",total_net:250}} onClose={()=>{}} /> };
