# Workplace Roast - Showcase Readiness Checklist

This document outlines what needs to be verified and tested before showcasing the platform to potential clients.

## 📋 Overview

The platform has **3 main interfaces** that need to be working:

1. **Master Admin Dashboard** (`admin.html`) - Platform-wide management
2. **Cafe Admin Dashboard** (`cafe-admin.html`) - Individual cafe management
3. **Cafe Client Interface** (`index.html`) - Customer ordering experience

**Dummy Companies for Demo:**
- **Acme Hair Salon** (ID: 1, Basic Tier) - Path: `/pwa/acmehairsalon`
- **Acme Law Firm** (ID: 2, Premium Tier) - Path: `/pwa/acmelawfirm`

---

## ✅ 1. Master Admin Dashboard (`admin.html`)

### Access Information
- **URL:** `https://workplaceroast.com/pwa/admin.html`
- **Login Credentials:**
  - Username: `admin`
  - Password: `WorkplaceRoast2024`

### Features to Verify:

#### ✅ Overview Dashboard
- [ ] Total Products count displays correctly
- [ ] Total Orders count displays correctly
- [ ] Total Revenue calculates correctly
- [ ] Recent Orders (last 5) display properly

#### ✅ Products Management
- [ ] View all products in grid/list
- [ ] Add new product (all fields: name, category, prices, description)
- [ ] Edit existing product
- [ ] Delete product (with confirmation)
- [ ] Products sync with database
- [ ] "Test Backend" button works
- [ ] "Reload Data" button refreshes data
- [ ] "Refresh Storefront" button clears cache

#### ✅ Categories Management
- [ ] View all categories
- [ ] Add new category
- [ ] Edit category
- [ ] Delete category
- [ ] Categories display correctly in product forms

#### ✅ Discounts Management
- [ ] View all discounts
- [ ] Add new discount (percentage/fixed amount)
- [ ] Edit discount
- [ ] Delete discount
- [ ] Link discount to corporate client
- [ ] Checkout Auto-Apply Discount works

#### ✅ Corporate Clients (Stores) Management
- [ ] View all stores/cafes
- [ ] View Access Codes for each store
- [ ] Add new corporate client/store
- [ ] Access Code auto-generates when adding new client
- [ ] Edit store name and active status
- [ ] Delete store (if no discounts linked)
- [ ] Verify dummy companies exist:
  - [ ] Acme Hair Salon (ID: 1)
  - [ ] Acme Law Firm (ID: 2)

#### ✅ Orders Management
- [ ] View all orders in table
- [ ] Order details display correctly (ID, customer, items, total, date)
- [ ] Payment status shows correctly
- [ ] Orders are immutable (cannot edit - as designed)

#### ✅ Security
- [ ] Admin token authentication works
- [ ] Unauthorized access is blocked
- [ ] Password can be changed (in admin.html code)

---

## ✅ 2. Cafe Admin Dashboard (`cafe-admin.html`)

### Access Information
- **URL:** `https://workplaceroast.com/pwa/cafe-admin.html`
- **Login Method:** Access Code + Password
  - **Access Code:** Unique code for each cafe (e.g., `BASIC1234` for Acme Hair Salon)
  - **Default Password:** `CafeOwner2024` (should be changed per cafe)

### How to Get Access Code:
1. Login to Master Admin Dashboard
2. Go to "Corporate Clients" section
3. Find the cafe (e.g., "Acme Hair Salon")
4. View the Access Code displayed on the card

### Features to Verify:

#### ✅ Login
- [ ] Access Code login works
- [ ] Password authentication works
- [ ] Invalid credentials show error
- [ ] Successful login redirects to dashboard

#### ✅ Dashboard Overview
- [ ] Cafe name displays correctly
- [ ] Order statistics show correctly
- [ ] Recent orders display

#### ✅ Orders Management
- [ ] View all orders for this cafe
- [ ] Order status updates work
- [ ] Order notifications work
- [ ] "Test Notification" button works
- [ ] "Simulate Order" button works (for testing)

#### ✅ Menu Management
- [ ] View products for this cafe
- [ ] Add/edit/delete products (if permissions allow)
- [ ] Categories display correctly

#### ✅ Analytics
- [ ] Revenue charts display
- [ ] Order trends show
- [ ] Product popularity stats

#### ✅ Settings
- [ ] Cafe profile can be edited
- [ ] Password can be changed
- [ ] Settings save correctly

---

## ✅ 3. Cafe Client Interface (`index.html`)

### Access Information
- **Direct URLs:**
  - Acme Hair Salon: `https://workplaceroast.com/pwa/acmehairsalon`
  - Acme Law Firm: `https://workplaceroast.com/pwa/acmelawfirm`
- **Generic URL:** `https://workplaceroast.com/pwa/` (requires Access Code)

### Access Methods:

#### Method 1: Direct URL (Recommended for Demo)
- Customer visits specific cafe URL
- Automatically loads that cafe's menu
- No login required for browsing

#### Method 2: Access Code Login
- Customer visits generic PWA URL
- Clicks "Corporate Login" or "Enter Access Code"
- Enters Access Code (e.g., `BASIC1234`)
- Accesses cafe-specific menu and discounts

### Features to Verify:

#### ✅ Menu Display
- [ ] Products load correctly from database
- [ ] Categories display properly
- [ ] Product images show (if available)
- [ ] Prices display correctly (regular/large)
- [ ] Product descriptions show
- [ ] Menu is responsive (mobile/desktop)

#### ✅ Ordering Flow
- [ ] Add items to cart
- [ ] Cart updates correctly
- [ ] Quantity adjustments work
- [ ] Remove items from cart
- [ ] Cart total calculates correctly
- [ ] Size selection (regular/large) works

#### ✅ Corporate Login/Access Code
- [ ] "Corporate Login" button/link visible
- [ ] Access Code entry form works
- [ ] Valid Access Code logs in successfully
- [ ] Invalid Access Code shows error
- [ ] After login, customer sees cafe-specific menu
- [ ] Corporate discounts apply after login

#### ✅ Checkout Process
- [ ] Checkout form displays
- [ ] Customer information fields work
- [ ] Delivery/collection options work
- [ ] Discount codes can be applied
- [ ] Auto-apply discount works (if configured)
- [ ] Total calculation is correct
- [ ] Payment integration (Stripe) works
- [ ] Test payment succeeds
- [ ] Order confirmation displays
- [ ] Order saved to database

#### ✅ Dummy Companies Testing

**Acme Hair Salon (ID: 1, Basic Tier):**
- [ ] URL `/pwa/acmehairsalon` loads correctly
- [ ] Menu displays products
- [ ] Access Code login works
- [ ] Orders can be placed
- [ ] Basic tier features work

**Acme Law Firm (ID: 2, Premium Tier):**
- [ ] URL `/pwa/acmelawfirm` loads correctly
- [ ] Menu displays products
- [ ] Access Code login works
- [ ] Orders can be placed
- [ ] Premium tier features work (bespoke pricing, bulk orders)

#### ✅ PWA Features
- [ ] Installable as PWA
- [ ] Works offline (service worker)
- [ ] Responsive design (mobile/tablet/desktop)
- [ ] QR code scanning works (if implemented)
- [ ] Theme/branding applies correctly

---

## 🔧 Technical Configuration Checks

### Database
- [ ] Database connection works
- [ ] Tables exist and are populated
- [ ] Dummy companies exist in `corporate_clients` table
- [ ] Products exist in `products` table
- [ ] Orders table is accessible

### API Configuration
- [ ] `api/config.php` has correct database credentials
- [ ] `config.js` has correct API base URL
- [ ] Admin token matches between `config.js` and `api/config.php`
- [ ] Stripe keys are configured (test mode for demo)

### File Structure
- [ ] All files uploaded to server
- [ ] Path routing works (`/pwa/acmehairsalon`, `/pwa/acmelawfirm`)
- [ ] Assets (images, CSS, JS) load correctly
- [ ] API endpoints accessible

---

## 🎯 Demo Flow Recommendations

### For Potential Clients:

1. **Start with Master Admin Dashboard**
   - Show platform-wide management
   - Demonstrate adding a new cafe/store
   - Show corporate client management
   - Display analytics and orders

2. **Show Cafe Admin Dashboard**
   - Login as cafe owner
   - Show order management
   - Demonstrate menu management
   - Show cafe-specific analytics

3. **Demonstrate Customer Experience**
   - Show direct URL access (Acme Hair Salon)
   - Show Access Code login method
   - Complete a test order
   - Show corporate discount application
   - Demonstrate mobile responsiveness

4. **Highlight Key Features**
   - Multi-store management
   - Corporate account management
   - Bespoke pricing (Premium tier)
   - Bulk ordering
   - Custom branding per cafe

---

## ⚠️ Known Issues / Things to Check

### Security
- [ ] Admin password should be changed from default
- [ ] Cafe owner passwords should be changed from default
- [ ] Access Codes are secure and not publicly visible
- [ ] API endpoints are protected

### Data
- [ ] Dummy companies have realistic data
- [ ] Products have proper pricing
- [ ] Test orders can be created
- [ ] Sample orders exist for demo

### Performance
- [ ] Pages load quickly
- [ ] Images are optimized
- [ ] API responses are fast
- [ ] No console errors

### Browser Compatibility
- [ ] Works in Chrome
- [ ] Works in Firefox
- [ ] Works in Safari
- [ ] Works in Edge
- [ ] Mobile browsers work correctly

---

## 📝 Quick Reference

### Login Credentials Summary:

**Master Admin:**
- URL: `https://workplaceroast.com/pwa/admin.html`
- Username: `admin`
- Password: `WorkplaceRoast2024`

**Cafe Admin:**
- URL: `https://workplaceroast.com/pwa/cafe-admin.html`
- Access Code: (Get from Master Admin → Corporate Clients)
- Password: `CafeOwner2024` (default, should be changed)

**Customer Access:**
- Direct: `https://workplaceroast.com/pwa/acmehairsalon` or `/pwa/acmelawfirm`
- Access Code: Enter at `https://workplaceroast.com/pwa/`

### Dummy Companies:
- **Acme Hair Salon** - ID: 1, Basic Tier, Path: `/pwa/acmehairsalon`
- **Acme Law Firm** - ID: 2, Premium Tier, Path: `/pwa/acmelawfirm`

---

## ✅ Final Checklist Before Showcase

- [ ] All three interfaces tested and working
- [ ] Dummy companies accessible and functional
- [ ] Test orders can be placed successfully
- [ ] Payment processing works (test mode)
- [ ] All features demonstrated in demo flow
- [ ] No critical errors in console
- [ ] Mobile responsiveness verified
- [ ] Documentation ready for client handoff

---

**Last Updated:** [Current Date]
**Status:** Ready for Review
