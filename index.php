<?php
// Load security helpers
require_once __DIR__ . '/security.php';

// Start secure session
startSecureSession();

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Get cartItems from request (POST or GET) - supports both JSON and array formats
$cartItems = [];
if (isset($_POST['cartItems']) && is_array($_POST['cartItems'])) {
    $cartItems = $_POST['cartItems'];
} elseif (isset($_GET['cartItems']) && is_array($_GET['cartItems'])) {
    $cartItems = $_GET['cartItems'];
} elseif (isset($_POST['items'])) {
    // Support JSON string format (e.g., from Next.js)
    $decodedItems = json_decode($_POST['items'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedItems)) {
        $cartItems = $decodedItems;
    }
} elseif (isset($_GET['items'])) {
    // Support JSON string in GET request
    $decodedItems = json_decode($_GET['items'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedItems)) {
        $cartItems = $decodedItems;
    }
} elseif (isset($_SESSION['cartItems']) && is_array($_SESSION['cartItems'])) {
    $cartItems = $_SESSION['cartItems'];
}

// Calculate amount from cartItems or from parameter
$amount = 0;
if (!empty($cartItems)) {
    foreach ($cartItems as $item) {
        if (isset($item['price']) && isset($item['quantity'])) {
            $amount += floatval($item['price']) * intval($item['quantity']);
        }
    }
}
if ($amount == 0) {
    $amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 12.34;
}
$amount_minor = str_pad((int)($amount * 100), 4, '0', STR_PAD_LEFT); // Convert to minor currency (pence)

// Store cartItems in session for processing
if (!empty($cartItems)) {
    $_SESSION['cartItems'] = $cartItems;
}

// Display any errors from payment processing
$errors = $_SESSION['payment_errors'] ?? [];
unset($_SESSION['payment_errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Complete Your Payment - EA Dental</title>
  <style>
    /* Layout + reset */
    *, *::before, *::after { box-sizing: border-box; }
    html, body { margin:0; padding:0; }
    body { background:#f3f6fb; color:#0f172a; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; }

    :root{
      --bg:#f3f6fb;
      --card:#fff;
      --primary:#22207E;
      --muted:#6b7280;
      --border:#e5e7eb;
      --shadow:0 10px 25px rgba(0,0,0,.06), 0 2px 8px rgba(0,0,0,.04);
      --radius:16px;
    }

    /* Header */
    .site-header{
      background:#fff;
      border-bottom:1px solid var(--border);
      position:sticky; top:0; z-index:10;
    }
    .header-inner{
      max-width:1100px; margin:0 auto; padding:14px 20px;
      display:flex; align-items:center; gap:12px;
    }
    .brand{
      display:flex; align-items:center; gap:12px; text-decoration:none;
    }
    .brand img{ height:36px; width:auto; display:block; }
    .brand span{ font-weight:800; letter-spacing:.2px; color:#111827; }

    /* Wrapper */
    .wrap { padding:28px 16px 56px; }
    .container { max-width:1100px; margin:0 auto; }

    .heading{ text-align:center; margin:0 0 24px; }
    .heading h1{ margin:0 0 6px; font-size:28px; }
    .heading p{ margin:0; color:var(--muted); font-size:14px; }

    /* Grid */
    .checkout {
      display:grid; grid-template-columns:360px 1fr; gap:24px;
      align-items:start;
    }
    @media (max-width: 920px){
      .checkout { grid-template-columns:1fr; }
    }

    /* Cards */
    .card{
      background:var(--card);
      border:1px solid var(--border);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      overflow:hidden;
    }
    .card-head{
      padding:16px 18px; background:#eef2ff; color:var(--primary);
      font-weight:700; font-size:16px;
      border-bottom:1px solid var(--border);
    }
    .card-body{ padding:18px; }

    /* Order summary */
    .summary-row{
      display:flex; justify-content:space-between; align-items:flex-start;
      gap:12px; padding:10px 0; font-size:15px; word-break:break-word;
    }
    .summary-row + .summary-row{ border-top:1px dashed var(--border); }
    .summary-title{ font-weight:600; color:#111827; }
    .summary-muted{ color:var(--muted); font-size:13px; }
    .summary-total{
      margin-top:8px; padding-top:12px; border-top:1px solid var(--border);
      display:flex; justify-content:space-between; font-weight:800; font-size:18px;
    }
    .badge-info{
      margin-top:14px; background:#eef2ff; color:var(--primary);
      border:1px solid #e0e7ff; padding:10px 12px; border-radius:12px; font-size:13px;
    }

    /* Form */
    form { width:100%; }
    .section-title{ margin:0 0 12px; font-size:16px; font-weight:700; color:#111827; }
    .grid{ display:grid; gap:14px; }
    .row-2{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    @media (max-width:520px){ .row-2{ grid-template-columns:1fr; } }

    .field{ display:flex; flex-direction:column; gap:8px; }
    .label{ font-size:13px; font-weight:600; color:#111827; }
    .input{
      appearance:none; width:100%; max-width:100%;
      padding:12px 14px; border:1px solid var(--border); border-radius:12px;
      background:#fff; outline:none; font-size:16px; /* 16px avoids iOS zoom */
      transition:border .15s, box-shadow .15s;
      min-height:44px; /* touch target */
    }
    .input:focus{ border-color:#c7d2fe; box-shadow:0 0 0 4px rgba(99,102,241,.12); }

    .btn{
      width:100%; border:0; border-radius:12px; cursor:pointer;
      padding:14px 16px; font-weight:800; font-size:16px;
      background:var(--primary); color:#fff;
      box-shadow:0 8px 18px rgba(34,32,126,.25);
      transition:filter .15s, transform .05s;
    }
    .btn:hover{ filter:brightness(1.05); }
    .btn:active{ transform:translateY(1px); }
    .disclaimer{ font-size:12px; color:var(--muted); margin-top:6px; }

    /* Ensure no overflow on tiny screens */
    .card, .header-inner, .container { min-width:0; }
    input, button { min-width:0; }
  </style>
</head>
<body>

  <header class="site-header">
    <div class="header-inner">
      <a class="brand" href="/">
        <img src="https://ea-dental.com/imgs/logo.png" alt="Brand Logo" onerror="this.style.display='none'" />
        <span>EA Dental</span>
      </a>
    </div>
  </header>

  <div class="wrap">
    <div class="container">
      <div class="heading">
        <h1>Complete Your Payment</h1>
        <p>Secure checkout powered by industry-leading encryption</p>
      </div>

      <div class="checkout">
        <!-- Order Summary -->
        <aside class="card">
          <div class="card-head">Order Summary</div>
          <div class="card-body">
            <?php if (!empty($cartItems)): ?>
              <?php foreach ($cartItems as $item): ?>
                <?php
                  $itemName = htmlspecialchars($item['name'] ?? 'Item');
                  $itemPrice = floatval($item['price'] ?? 0);
                  $itemQty = intval($item['quantity'] ?? 1);
                  $itemTotal = $itemPrice * $itemQty;
                ?>
                <div class="summary-row">
                  <div>
                    <strong><?php echo $itemName; ?></strong>
                    <div class="summary-muted">× <?php echo $itemQty; ?></div>
                  </div>
                  <div>£<?php echo number_format($itemTotal, 2); ?></div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="summary-row">
                <div class="summary-muted">Item</div>
                <div>£<?php echo number_format($amount, 2); ?></div>
              </div>
            <?php endif; ?>

            <div class="summary-total">
              <span>Total</span><span>£<?php echo number_format($amount, 2); ?> GBP</span>
            </div>

            <div class="badge-info">🔒 Your payment is secured with 256-bit SSL encryption</div>
          </div>
        </aside>

        <!-- Payment Form -->
        <section class="card">
          <div class="card-head">Payment Information</div>
          <div class="card-body">
            <?php if (!empty($errors)): ?>
              <div style="background:#fee;border:1px solid #fcc;border-radius:12px;padding:12px;margin-bottom:20px;">
                <strong style="color:#c00;">Please fix the following errors:</strong>
                <ul style="margin:8px 0 0 20px;color:#c00;">
                  <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
            
            <form method="post" action="process.php">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
              <input type="hidden" name="Amount" value="<?php echo $amount_minor; ?>" />

              <h3 class="section-title">Card Details</h3>
              <div class="grid">
                <div class="field">
                  <label class="label" for="cardNumber">Card Number</label>
                  <input class="input" id="cardNumber" type="text" name="CardNumber" placeholder="1234 5678 9012 3456" maxlength="19" required />
                </div>

                <div class="row-2">
                  <div class="field">
                    <label class="label" for="cardExpiryMonth">Expiry Month</label>
                    <input class="input" id="cardExpiryMonth" type="text" name="cardExpiryMonth" placeholder="MM" maxlength="2" required />
                  </div>
                  <div class="field">
                    <label class="label" for="cardExpiryYear">Expiry Year</label>
                    <input class="input" id="cardExpiryYear" type="text" name="cardExpiryYear" placeholder="YY" maxlength="2" required />
                  </div>
                </div>

                <div class="field">
                  <label class="label" for="CVV">CVV</label>
                  <input class="input" id="CVV" type="text" name="CVV" placeholder="123" maxlength="4" required />
                </div>

                <h3 class="section-title">Billing Address</h3>

                <div class="field">
                  <label class="label" for="customerName">Cardholder Name</label>
                  <input class="input" id="customerName" type="text" name="customerName" placeholder="John Doe" required />
                </div>

                <div class="field">
                  <label class="label" for="customerEmail">Email Address</label>
                  <input class="input" id="customerEmail" type="email" name="customerEmail" placeholder="john@example.com" required />
                </div>

                <div class="field">
                  <label class="label" for="customerPhone">Phone</label>
                  <input class="input" id="customerPhone" type="tel" name="customerPhone" placeholder="+44 7123 456789" required />
                </div>

                <div class="field">
                  <label class="label" for="customerAddress">Street Address</label>
                  <input class="input" id="customerAddress" type="text" name="customerAddress" placeholder="123 Main Street" required />
                </div>

                <div class="field">
                  <label class="label" for="customerPostCode">Post Code</label>
                  <input class="input" id="customerPostCode" type="text" name="customerPostCode" placeholder="SW1A 1AA" required />
                </div>

                <button class="btn" type="submit">Pay £<?php echo number_format($amount, 2); ?></button>
                <div class="disclaimer">By clicking pay you agree to our Terms &amp; Privacy Policy.</div>
              </div>
            </form>
          </div>
        </section>
      </div>
    </div>
  </div>

  <script>
    // Format card number with spaces
    document.getElementById('cardNumber').addEventListener('input', function(e) {
      let value = e.target.value.replace(/\s+/g, '');
      let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
      e.target.value = formattedValue;
    });
  </script>

</body>
</html>
