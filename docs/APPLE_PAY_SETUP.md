# Apple Pay Setup Guide

## Amazon Payment Services (APS)

### Prerequisites
1. Apple Developer Account (paid membership required)
2. APS Merchant Account with Apple Pay enabled

### Step 1: Create Merchant ID in Apple Developer Portal

1. Go to [Apple Developer Portal](https://developer.apple.com)
2. Navigate to **Certificates, Identifiers & Profiles**
3. Select **Identifiers** → **Merchant IDs**
4. Click **+** to create a new Merchant ID
5. Enter a description and identifier (e.g., `merchant.com.yourcompany.app`)
6. Click **Continue** and **Register**

### Step 2: Create Apple Pay Certificate

1. In Apple Developer Portal, select your Merchant ID
2. Under **Apple Pay Payment Processing Certificate**, click **Create Certificate**
3. You'll need to create a Certificate Signing Request (CSR):
   - Open **Keychain Access** on Mac
   - Go to **Keychain Access** → **Certificate Assistant** → **Request a Certificate from a Certificate Authority**
   - Enter your email, select **Saved to disk**, check **Let me specify key pair information**
   - Save the CSR file
4. Upload the CSR to Apple Developer Portal
5. Download the generated certificate (.cer file)
6. Double-click to install in Keychain Access
7. Export as .p12 file (right-click → Export)

### Step 3: Configure APS Merchant Portal

1. Log into [APS Merchant Portal](https://fort.payfort.com)
2. Go to **Integration Settings** → **Apple Pay**
3. Upload your Apple Pay certificate
4. Note down your **Merchant Identifier**
5. APS will validate and enable Apple Pay

### Step 4: Configure in Admin Panel

In Oliv Admin → Payment Gateways → Edit APS:

| Field | Value |
|-------|-------|
| Apple Pay Merchant ID | `merchant.com.yourcompany.app` |
| Apple Pay Certificate | Contents of your .pem certificate |
| Apple Pay Private Key | Contents of your private key |
| Verified Domain | Your domain verified with Apple |

### Domain Verification

1. Download the domain verification file from Apple Developer Portal
2. Host it at: `https://yourdomain.com/.well-known/apple-developer-merchantid-domain-association`
3. Verify the domain in Apple Developer Portal

---

## Tap Payments

### Prerequisites
1. Tap Payments merchant account
2. Apple Developer Account

### Step 1: Contact Tap Support

Tap Payments handles most of the Apple Pay setup. Contact Tap support to:
1. Enable Apple Pay on your account
2. Get your Apple Pay Merchant ID (provided by Tap)

### Step 2: Configure in Admin Panel

In Oliv Admin → Payment Gateways → Edit Tap:

| Field | Value |
|-------|-------|
| Apple Pay Merchant ID | Provided by Tap (e.g., `merchant.ae.tap.test`) |
| Verified Domain | Your domain registered with Tap |

### Domain Verification for Tap

1. Contact Tap support to verify your domain
2. They will provide instructions for domain verification

---

## Testing Apple Pay

### Sandbox Testing
1. Use Apple Pay sandbox environment
2. Add test cards to Wallet on a test device
3. Use sandbox credentials in gateway config

### Test Cards (Sandbox)
- Visa: `4761 1200 1000 0492`
- Mastercard: `5204 2477 5000 1471`
- Amex: `3456 789012 34564`

---

## Troubleshooting

### Common Issues

1. **"Merchant validation failed"**
   - Check certificate is valid and not expired
   - Verify domain is properly configured
   - Ensure merchant ID matches exactly

2. **"Payment not completed"**
   - Check API credentials
   - Verify webhook URLs are accessible
   - Check server logs for detailed errors

3. **"Apple Pay button not showing"**
   - Device must support Apple Pay
   - Must be on Safari or Apple Pay-enabled browser
   - HTTPS required (no localhost testing)

---

## References

- [Apple Pay Documentation](https://developer.apple.com/apple-pay/)
- [APS Apple Pay Integration](https://paymentservices.amazon.com/docs/EN/index.html)
- [Tap Payments Documentation](https://developers.tap.company/)
