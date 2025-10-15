// Load order from localStorage
let currentOrder = { tableId:null, tableName:null, customer:'Walk-in', items:[], notes:'', paymentMethod:'cash', subtotal:0, serviceCharge:0, tax:0, total:0, lastUpdated:null, orderCode:null };
function loadOrder() {
    const saved = localStorage.getItem('solieraCurrentOrder');
    if(saved) currentOrder = JSON.parse(saved);
    updateOrderDisplay();
}
function saveOrder() {
    currentOrder.lastUpdated = Date.now();
    localStorage.setItem('solieraCurrentOrder', JSON.stringify(currentOrder));
}

// Update totals
function updateTotals() {
    currentOrder.subtotal = currentOrder.items.reduce((s,i)=>s+i.price*i.quantity,0);
    currentOrder.serviceCharge = currentOrder.subtotal*0.08;
    currentOrder.tax = currentOrder.subtotal*0.12;
    currentOrder.total = currentOrder.subtotal + currentOrder.serviceCharge + currentOrder.tax;

    document.getElementById('subtotal')?.textContent = `₱ ${currentOrder.subtotal.toFixed(2)}`;
    document.getElementById('service-charge')?.textContent = `₱ ${currentOrder.serviceCharge.toFixed(2)}`;
    document.getElementById('tax')?.textContent = `₱ ${currentOrder.tax.toFixed(2)}`;
    document.getElementById('total')?.textContent = `₱ ${currentOrder.total.toFixed(2)}`;

    document.getElementById('total-amount-input')?.value = currentOrder.total.toFixed(2);
    document.getElementById('table-id-input')?.value = currentOrder.tableId || '';
    document.getElementById('mop-input')?.value = currentOrder.paymentMethod || 'cash';
    document.getElementById('order-items-json')?.value = JSON.stringify(currentOrder.items);
}

// Add item
function addToOrder(menuId,name,price,quantity=1) {
    const idx = currentOrder.items.findIndex(i=>i.menuId===menuId);
    if(idx>=0) currentOrder.items[idx].quantity += quantity;
    else currentOrder.items.push({menuId,name,price,quantity});
    updateOrderDisplay();
    saveOrder();
}

// Update display
function updateOrderDisplay() {
    const container = document.getElementById('order-items-container');
    if(!container) return;
    if(currentOrder.items.length===0){container.innerHTML='<p class="text-center">Cart empty</p>'; updateTotals(); return;}
    container.innerHTML = currentOrder.items.map((i,idx)=>`
        <div class="order-item">
            ${i.name} × ${i.quantity} - ₱ ${(i.price*i.quantity).toFixed(2)}
            <button onclick="updateItemQuantity(${idx},1)">+</button>
            <button onclick="updateItemQuantity(${idx},-1)">-</button>
            <button onclick="removeFromOrder(${idx})">x</button>
        </div>
    `).join('');
    updateTotals();
}

// Update item quantity
function updateItemQuantity(idx,change){
    if(!currentOrder.items[idx]) return;
    currentOrder.items[idx].quantity += change;
    if(currentOrder.items[idx].quantity<1) removeFromOrder(idx);
    else {updateOrderDisplay(); saveOrder();}
}

// Remove item
function removeFromOrder(idx){currentOrder.items.splice(idx,1); updateOrderDisplay(); saveOrder();}

// Select table
function selectTable(id,name){currentOrder.tableId=id; currentOrder.tableName=name; saveOrder(); updateOrderDisplay();}

// Submit order
document.getElementById('submit-order-btn')?.addEventListener('click', async ()=>{
    if(!currentOrder.tableId || currentOrder.items.length===0){alert('Select table & items'); return;}
    const orderCode='ORD-'+Date.now(); currentOrder.orderCode=orderCode;

    const formData = new FormData();
    formData.append('order_code',orderCode);
    formData.append('table_id',currentOrder.tableId);
    formData.append('customer_name',currentOrder.customer);
    formData.append('order_type','dine-in');
    formData.append('total_amount',currentOrder.total.toFixed(2));
    formData.append('MOP',currentOrder.paymentMethod);
    formData.append('notes',currentOrder.notes);
    formData.append('order_items_json',JSON.stringify(currentOrder.items));

    const res = await fetch('../M4/sub-modules/POS_ordering_form.php',{method:'POST',body:formData});
    const data = await res.json();
    if(data.status==='success'){
        alert('Order sent!'); 
        saveOrder(); 
        generatePDFReceipt(currentOrder);
        currentOrder.items=[]; currentOrder.tableId=null; saveOrder(); updateOrderDisplay();
    } else alert('Error: '+data.message);
});

// Initialize
document.addEventListener('DOMContentLoaded', loadOrder);
