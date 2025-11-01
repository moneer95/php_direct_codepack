<?php
/**
 * Example usage of the payment form with cartItems
 */

// Example 1: Pass cartItems via POST
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cart Payment Example</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .example { background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 8px; }
        code { background: #fff; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Payment Form - CartItems Examples</h1>

    <div class="example">
        <h2>Example 1: POST cartItems to index.php</h2>
        <form method="POST" action="index.php">
            <input type="hidden" name="cartItems[0][name]" value="Dental Cleaning">
            <input type="hidden" name="cartItems[0][price]" value="75.00">
            <input type="hidden" name="cartItems[0][quantity]" value="1">
            
            <input type="hidden" name="cartItems[1][name]" value="X-Ray Examination">
            <input type="hidden" name="cartItems[1][price]" value="75.00">
            <input type="hidden" name="cartItems[1][quantity]" value="2">
            
            <button type="submit">Go to Payment with Cart Items</button>
        </form>
    </div>

    <div class="example">
        <h2>Example 2: GET with cartItems query string</h2>
        <p>Navigate to: <code>index.php?cartItems[0][name]=Product1&cartItems[0][price]=25.00&cartItems[0][quantity]=2&cartItems[1][name]=Product2&cartItems[1][price]=50.00&cartItems[1][quantity]=1</code></p>
    </div>

    <div class="example">
        <h2>Example 3: JavaScript example</h2>
        <pre><code>
// Your cartItems array
const cartItems = [
    { name: "Dental Cleaning", price: 75.00, quantity: 1 },
    { name: "X-Ray", price: 150.00, quantity: 2 },
    { name: "Consultation", price: 50.00, quantity: 1 }
];

// Method 1: POST to index.php
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

// Method 2: Build query string for GET
const params = new URLSearchParams();
cartItems.forEach((item, index) => {
    params.append(`cartItems[${index}][name]`, item.name);
    params.append(`cartItems[${index}][price]`, item.price);
    params.append(`cartItems[${index}][quantity]`, item.quantity);
});
window.location.href = `index.php?${params.toString()}`;
        </code></pre>
    </div>

    <div class="example">
        <h2>Example 4: PHP session example</h2>
        <pre><code>
// Start session and set cartItems
session_start();
$_SESSION['cartItems'] = [
    ['name' => 'Service 1', 'price' => 100, 'quantity' => 1],
    ['name' => 'Service 2', 'price' => 200, 'quantity' => 2]
];

// Redirect to payment form
header('Location: index.php');
exit;
        </code></pre>
    </div>

    <div class="example">
        <h2>CartItems Array Format</h2>
        <pre><code>
[
    {
        "name": "Product/Service Name",
        "price": 25.00,      // Float value
        "quantity": 2        // Integer value
    },
    {
        "name": "Another Product",
        "price": 50.00,
        "quantity": 1
    }
]
        </code></pre>
    </div>

    <div class="example">
        <h2>What Happens</h2>
        <ul>
            <li><strong>index.php</strong> receives cartItems and calculates total</li>
            <li><strong>Order Summary</strong> shows individual items with quantities</li>
            <li><strong>Total</strong> is calculated automatically</li>
            <li><strong>process.php</strong> processes payment</li>
            <li><strong>Success page</strong> shows cart items in order details</li>
        </ul>
    </div>
</body>
</html>

