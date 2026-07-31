import { CartLine } from "./CartLine";
import { ConnectivityBanner } from "./ConnectivityBanner";
import { OrderTotals } from "./OrderTotals";
import { ScanInput } from "./ScanInput";

export default { title: "Retail/Molecules", parameters: { layout: "padded" } };
export const Search = { render: () => <ScanInput value="" onChange={() => {}} onSubmit={() => {}} /> };
export const Offline = { render: () => <ConnectivityBanner online={false} /> };
export const Line = { render: () => <CartLine item={{ id:"1", quantity:2, unit_price:42, subtotal:84, product:{ sku:"CAF-01",short_description:"Café de altura 500 g" } }} onChange={() => {}} onRemove={() => {}} /> };
export const Totals = { render: () => <OrderTotals cart={{ subtotal:420, discount_total:20, total_net:400 }} /> };
