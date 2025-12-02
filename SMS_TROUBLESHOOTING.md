# SMS Not Receiving - Troubleshooting Guide

## Problem
API shows "success" with batch ID, but SMS is not arriving on your phone.

## Why This Happens
The TextBee API accepts your request and adds it to a queue. However, the actual SMS delivery depends on your **gateway device** (the phone/Android device running the TextBee app) being online and connected.

**API Success ≠ SMS Delivered**
- ✅ API Success = Request accepted and queued
- 📱 SMS Delivered = Gateway device actually sends it

---

## Solution Steps

### Step 1: Check Gateway Device Status

**Run the diagnostic tool:**
```
http://localhost/zppsu_admission/admin/test_sms_detailed.php
```

Look for "Device Status" section - it must show:
- ✅ **ONLINE** (green badge)
- If it shows ❌ **OFFLINE** (red badge), your gateway device is not connected

### Step 2: Check Your Gateway Device (Phone/Tablet)

Your gateway device is the phone/tablet running the TextBee app that actually sends the SMS.

**Checklist:**
- [ ] Device is connected to **WiFi or Mobile Data**
- [ ] **TextBee app is installed** and running
- [ ] **Logged into TextBee** with correct account
- [ ] App is **not killed** by battery optimization
- [ ] Device has **mobile signal** (not in airplane mode)
- [ ] Device has **active SIM card** with SMS capability
- [ ] Device is **not in Do Not Disturb** mode

### Step 3: Fix Battery Optimization (Android)

Android might be killing the TextBee app to save battery.

**Fix:**
1. Go to **Settings** → **Apps** → **TextBee**
2. Tap **Battery** → **Battery optimization**
3. Select **Don't optimize** or **Unrestricted**
4. **Allow background activity**

### Step 4: Check TextBee App Settings

1. Open **TextBee app** on your gateway device
2. Check if it shows **"Connected"** or **"Online"**
3. Go to **Device Settings** in the app
4. Verify **Device ID matches**: `68edf6f3bf50e7762d9d4a9d`
5. Check if there are **pending messages in queue**

### Step 5: Test Manual Send

**From TextBee App:**
1. Try sending a test SMS manually from the app
2. If manual send fails → **SIM/Signal problem**
3. If manual send works → **API/Connection problem**

---

## Common Issues & Fixes

### Issue 1: Device Shows OFFLINE
**Cause:** Gateway device not connected to internet or app not running

**Fix:**
1. Connect device to WiFi/mobile data
2. Open TextBee app
3. Make sure it's logged in
4. Wait 30 seconds for connection
5. Refresh diagnostic page

### Issue 2: Device ONLINE but SMS Not Arriving
**Cause:** SMS queued but not processed by device

**Fix:**
1. Check TextBee app → View message queue
2. Force close and restart TextBee app
3. Check if SIM card has SMS credit/capability
4. Try rebooting the gateway device
5. Wait 2-3 minutes (queue processing time)

### Issue 3: Wrong Phone Number Receiving SMS
**Cause:** Device ID might be linked to different phone

**Fix:**
1. In TextBee dashboard online → Check which phone is linked to Device ID
2. Make sure Device ID `68edf6f3bf50e7762d9d4a9d` is your phone
3. If wrong, you may need to create a new device or update the device ID in `admin/inc/api_config.php`

### Issue 4: API Key or Device ID Wrong
**Cause:** Credentials don't match TextBee account

**Fix:**
1. Login to TextBee dashboard (https://textbee.dev)
2. Go to **Devices** section
3. Find your device and copy:
   - Device ID
   - API Key
4. Update `admin/inc/api_config.php`:

```php
const SMS_DEVICE_ID = 'YOUR_DEVICE_ID_HERE';
const SMS_API_KEY = 'YOUR_API_KEY_HERE';
```

---

## Quick Test

### Test 1: Check Device Status
```
http://localhost/zppsu_admission/admin/test_sms_detailed.php
```
Should show: **ONLINE** ✓

### Test 2: Send Test SMS
1. Open diagnostic tool above
2. Enter your phone number: `+639971545203`
3. Click "Send Test SMS"
4. Wait 1-2 minutes
5. Check your phone

### Test 3: Check Queue in TextBee App
1. Open TextBee app on gateway device
2. Look for "Pending Messages" or "Queue"
3. See if your test SMS is there
4. If stuck, restart the app

---

## Understanding the Flow

```
Your System → TextBee API → Message Queue → Gateway Device → Mobile Network → Your Phone
   (OK)          (OK)           (OK)            (CHECK!)        (CHECK!)        (CHECK!)
```

**If API shows success but you don't receive SMS:**
- ✅ Your System = Working
- ✅ TextBee API = Working
- ✅ Message Queue = Working
- ❌ **Gateway Device** = NOT WORKING (most likely)
- ❌ **Mobile Network** = Issue (less likely)

---

## Alternative SMS Provider (If TextBee Issues Continue)

If TextBee gateway device keeps going offline, consider these alternatives:

### Option 1: Semaphore (Philippines)
- **Website:** https://semaphore.co/
- **Setup:** Sign up → Get API key → Update `api_config.php`
- **Pros:** Cloud-based (no gateway device needed), reliable
- **Cons:** Paid service (~₱0.80 per SMS)

### Option 2: Movider (Philippines)
- **Website:** https://movider.co/
- **Setup:** Similar to Semaphore
- **Pros:** Local provider, good rates
- **Cons:** Paid service

### Option 3: Keep TextBee
- **Pros:** FREE!
- **Cons:** Requires gateway device to be online 24/7
- **Best for:** Testing/Development, Small scale

---

## For Production Use

**Recommendations:**
1. **Testing/Development:** TextBee is fine (free)
2. **Production:** Use paid service (Semaphore/Movider)
   - More reliable
   - No device maintenance
   - Better deliverability
   - Professional sender ID

---

## Emergency Workaround

If you need OTP now and can't fix TextBee:

### Option 1: Test Mode
Temporarily bypass OTP for testing:
1. In registration, accept any 6-digit code
2. **ONLY FOR TESTING - NOT FOR PRODUCTION**

### Option 2: Email OTP Instead
1. Send OTP via email instead of SMS
2. Update `send_otp.php` to use email

### Option 3: Use Different Provider
1. Sign up for Semaphore (https://semaphore.co)
2. Get free trial credits
3. Update API config

---

## Still Not Working?

**Contact TextBee Support:**
- Email: support@textbee.dev
- Check if there are service outages
- Verify your account is active

**Check System:**
1. Run diagnostic: `http://localhost/zppsu_admission/admin/test_sms_detailed.php`
2. Screenshot the device status
3. Check gateway device logs in TextBee app

---

## Summary Checklist

Before giving up, verify ALL of these:

- [ ] Gateway device is powered on
- [ ] Gateway device connected to internet
- [ ] TextBee app is installed and logged in
- [ ] TextBee app shows "Connected/Online"
- [ ] Battery optimization disabled for TextBee
- [ ] Device has active SIM with signal
- [ ] Device ID matches in config
- [ ] API key is correct
- [ ] Phone number format is correct (+639XXXXXXXXX)
- [ ] Waited at least 2 minutes for delivery
- [ ] Checked TextBee app message queue
- [ ] Tried restarting TextBee app
- [ ] Diagnostic tool shows ONLINE

**If ALL checked and still not working:**
→ Consider switching to paid SMS provider (Semaphore/Movider)

---

**Last Updated:** November 18, 2025
**Issue:** OTP shows success but not received
**Status:** Diagnostic tool created ✅

