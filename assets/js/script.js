let cart = [];
let currentOrderNumber = '';

document.addEventListener('DOMContentLoaded', function () {
    console.log('🚀 System Initialized');

    initializePaymentForm();

    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function (e) {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.menu-item').forEach(item => {
                const name = item.querySelector('.card-title').innerText.toLowerCase();
                item.style.display = name.includes(term) ? 'block' : 'none';
            });
        });
    }

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const category = btn.getAttribute('data-category');

            document.querySelectorAll('.menu-item').forEach(item => {
                if (category === 'all' || item.getAttribute('data-category') === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            const price = parseFloat(btn.getAttribute('data-price'));

            cart.push({ id, name, price });
            updateCartModal();

            const cartModal = new bootstrap.Modal(document.getElementById('cartModal'));
            cartModal.show();
        });
    });

    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => {
            if (cart.length === 0) {
                alert("Your cart is empty!");
                return;
            }

            const cartModalEl = document.getElementById('cartModal');
            const cartModal = bootstrap.Modal.getInstance(cartModalEl);
            if (cartModal) cartModal.hide();

            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            paymentModal.show();
        });
    }

    const payNowBtn = document.getElementById('payNowBtn');
    if (payNowBtn) {
        payNowBtn.addEventListener('click', function (e) {
            e.preventDefault();

            const cardName = document.getElementById('cardName').value.trim();
            const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
            const cardMonth = document.getElementById('cardMonth').value;
            const cardYear = document.getElementById('cardYear').value;
            const cvv = document.getElementById('cardCvv').value;

            if (!cardName || !cardNumber || !cardMonth || !cardYear || !cvv) {
                alert("⚠️ Please fill in all card details.");
                return;
            }

            if (cardNumber.length !== 16) {
                alert("⚠️ Card number must be 16 digits.");
                return;
            }

            const originalText = this.innerText;
            this.disabled = true;
            this.innerText = "Processing...";

            setTimeout(() => {
                currentOrderNumber = 'ORD-' + Math.floor(Math.random() * 900000 + 100000);

                const orderData = {
                    order_number: currentOrderNumber,
                    items: cart.map(item => ({
                        id: item.id,
                        name: item.name,
                        price: item.price,
                        quantity: 1
                    })),
                    total: cart.reduce((sum, item) => sum + item.price, 0),
                    customer_name: cardName,
                    customer_email: '', 
                };

                fetch('api/save_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(orderData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('✅ Order saved successfully:', data.order_id);
                    } else {
                        console.error('❌ Failed to save order:', data.message);
                    }
                })
                .catch(error => {
                    console.error('❌ Error saving order:', error);
                });

                alert(`✅ Payment Successful!\nOrder Confirmed: ${currentOrderNumber}`);

                const paymentModalEl = document.getElementById('paymentModal');
                const paymentModal = bootstrap.Modal.getInstance(paymentModalEl);
                if (paymentModal) paymentModal.hide();

                document.getElementById('paymentForm').reset();

                const btn = document.getElementById('payNowBtn');
                btn.disabled = false;
                btn.innerText = originalText;

                generateReceipt(currentOrderNumber);

                cart = [];
                updateCartModal();

            }, 1500);
        });
    }
});


function initializePaymentForm() {
    const yearSelect = document.getElementById('cardYear');
    if (yearSelect) {
        const currentYear = new Date().getFullYear();
        for (let i = 0; i < 10; i++) {
            const year = currentYear + i;
            const shortYear = year.toString().slice(-2);
            const option = document.createElement('option');
            option.value = shortYear;
            option.textContent = year;
            yearSelect.appendChild(option);
        }
    }

    const cardNumberInput = document.getElementById('cardNumber');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\s/g, '');
            value = value.replace(/\D/g, '');

            if (value.length > 16) value = value.slice(0, 16);

            let formattedValue = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) formattedValue += ' ';
                formattedValue += value[i];
            }
            e.target.value = formattedValue;
        });
    }

    const cvvInput = document.getElementById('cardCvv');
    if (cvvInput) {
        cvvInput.addEventListener('input', function (e) {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 3);
        });
    }
}

function updateCartModal() {
    const list = document.getElementById('cartItems');
    if (!list) return;

    list.innerHTML = '';
    let total = 0;

    if (cart.length === 0) {
        list.innerHTML = '<li class="list-group-item text-muted">Your cart is empty</li>';
    } else {
        cart.forEach((item, index) => {
            total += item.price;
            list.innerHTML += `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    ${item.name} - $${item.price.toFixed(2)}
                    <button class="btn btn-danger btn-sm remove-item" data-index="${index}">&times;</button>
                </li>`;
        });
    }

    const totalEl = document.getElementById('cartTotal');
    if (totalEl) totalEl.innerText = '$' + total.toFixed(2);

    document.querySelectorAll('.remove-item').forEach(b => {
        b.addEventListener('click', (e) => {
            cart.splice(e.target.getAttribute('data-index'), 1);
            updateCartModal();
        });
    });
}

function generateReceipt(orderNum) {
    if (cart.length === 0) return;

    let total = 0;
    let itemsHTML = '';

    cart.forEach(item => {
        total += item.price;
        itemsHTML += `
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span>${item.name}</span>
                <span>$${item.price.toFixed(2)}</span>
            </div>
        `;
    });

    const dateStr = new Date().toLocaleString();
    document.getElementById('r-date').innerText = dateStr;
    document.getElementById('r-order').innerText = orderNum;
    document.getElementById('r-items').innerHTML = itemsHTML;
    document.getElementById('r-total').innerText = '$' + total.toFixed(2);

    const template = document.getElementById('receipt-template');
    template.style.display = 'block';

    html2canvas(template, {
        scale: 2,
        backgroundColor: '#ffffff',
        logging: false
    }).then(canvas => {
        const imgURL = canvas.toDataURL('image/png');

        const link = document.createElement('a');
        link.download = `Receipt_${orderNum}.png`;
        link.href = imgURL;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        template.style.display = 'none';
    }).catch(err => {
        console.error("Error generating receipt:", err);
        alert("Could not generate image receipt. Falling back to text.");
        template.style.display = 'none';
    });
}