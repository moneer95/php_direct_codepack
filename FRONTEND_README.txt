===============================================================================
                    FRONTEND INTEGRATION - Quick Reference
===============================================================================

HOW TO START PAYMENT FROM YOUR FRONTEND
========================================

OPTION 1: JavaScript Function (Copy & Paste Ready)
---------------------------------------------------

Add this to your JavaScript:

```javascript
function startPayment(cartItems) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'index.php';
    
    cartItems.forEach((item, index) => {
        ['name', 'price', 'quantity'].forEach(field => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `cartItems[${index}][${field}]`;
            input.value = item[field];
            form.appendChild(input);
        });
    });
    
    document.body.appendChild(form);
    form.submit();
}
```

Then call it:
```javascript
startPayment([
    { name: "Product 1", price: 25.00, quantity: 2 },
    { name: "Product 2", price: 50.00, quantity: 1 }
]);
```

OPTION 2: Direct Link (For Testing)
------------------------------------

If you just want to test, create a simple link:

index.php?cartItems[0][name]=Test+Product&cartItems[0][price]=100&cartItems[0][quantity]=1

OPTION 3: Full HTML Form
-------------------------

Add to your checkout page:

<form method="POST" action="index.php">
    <!-- Your cart items -->
    <input type="hidden" name="cartItems[0][name]" value="Service 1">
    <input type="hidden" name="cartItems[0][price]" value="100.00">
    <input type="hidden" name="cartItems[0][quantity]" value="1">
    
    <button type="submit">Pay Now</button>
</form>


WHAT HAPPENS
============

1. You POST cartItems → index.php
2. index.php calculates total automatically
3. User sees payment form with order summary
4. User enters card details
5. Payment processed via process.php
6. Success page shows all cart items


EXAMPLES TO TRY
===============

1. Open: frontend_example.html (working demo with cart)
2. Open: example_usage.php (multiple methods)
3. Check: INTEGRATION_GUIDE.txt (detailed guide)


TECH STACK COMPATIBLE
======================

Works with:
- Plain JavaScript
- jQuery
- React
- Vue
- Angular
- Next.js
- Any frontend framework


REQUIREMENTS
============

cartItems format:
[
    {
        name: "Service Name",  // String
        price: 100.00,          // Number/Float
        quantity: 2             // Integer
    }
]

===============================================================================

