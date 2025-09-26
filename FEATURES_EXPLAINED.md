# NetHive Features Explained - For Beginners

_Understanding NetHive's functionality in simple terms_

## 🏠 Dashboard - Your Network Control Center

**What it does:** Think of it as your car's dashboard - shows you everything important at a glance.

### Real-time Monitoring

**Simple explanation:** Like checking your phone's battery percentage, but for your router.

```javascript
// Every 5 seconds, we ask the router: "How are you doing?"
setInterval(() => {
  getCpuUsage(); // "Are you working hard?" (CPU)
  getMemoryUsage(); // "Are you remembering too much?" (RAM)
  getDiskUsage(); // "Is your storage full?" (HDD)
}, 5000);
```

**What you see:**

- CPU: 25% (Router is relaxed)
- Memory: 60% (Router is thinking normally)
- Disk: 80% (Router's storage is getting full)

### Interface Traffic Charts

**Think of it like:** Watching cars on a highway - you can see how much data is going in and out.

**Real example:**

- Download: 50 Mbps (Data coming to users)
- Upload: 10 Mbps (Data going from users)

---

## 🔥 Hotspot Management - Your WiFi Customer Manager

**What it does:** Like being a hotel manager - you create room keys (accounts) for guests (customers).

### User Management

**The old way (manual):**

1. Customer calls: "I want internet"
2. You manually type username/password in router
3. Customer gets frustrated waiting

**NetHive way (smart):**

```php
// One click creates a user
createHotspotUser([
    'username' => 'customer123',
    'password' => 'pass456',
    'time_limit' => '24h',
    'data_limit' => '5GB'
]);
```

### Voucher Generation

**Think of it like:** Printing movie tickets in bulk.

**Example:** Generate 100 vouchers for a conference:

- Each voucher: 2 hours internet
- Speed: 10 Mbps
- Auto-expires after use

**What happens:**

1. Click "Generate 100 vouchers"
2. System creates: USER001, USER002... USER100
3. Print voucher cards
4. Hand them out to conference attendees

---

## 🚦 Queue Management - Traffic Control

**What it does:** Like being a traffic cop - you decide who gets to drive fast and who has to slow down.

### Bandwidth Allocation

**Real-world example:**

- VIP customer: Gets 50 Mbps (fast lane)
- Regular customer: Gets 10 Mbps (normal lane)
- Free user: Gets 1 Mbps (slow lane)

### Drag & Drop Reordering

**Think of it like:** Rearranging people in a line at the bank.

```javascript
// Higher position = higher priority
// Drag "VIP Customer" to top = they get served first
sortableList.addEventListener("drop", (event) => {
  updateQueuePriority(newOrder);
});
```

**Visual example:**

```
Before:          After (drag VIP to top):
1. Regular User  →  1. VIP Customer (50 Mbps)
2. Free User     →  2. Regular User (10 Mbps)
3. VIP Customer  →  3. Free User (1 Mbps)
```

---

## 🚫 Web Blocking - Internet Bouncer

**What it does:** Like having a bouncer at a club - decides which websites can enter and which get blocked.

### Category-based Filtering

**Think of it like:** Having different "No Entry" signs.

```php
$blockedCategories = [
    'social_media' => ['facebook.com', 'instagram.com'],
    'adult_content' => ['blocked automatically'],
    'malware' => ['dangerous sites blocked'],
    'ads' => ['annoying ads removed']
];
```

**Real example:**

- Office network: Block social media during work hours
- School network: Block adult content always
- Cafe network: Block malware but allow everything else

### DNS Configuration

**Simple explanation:** Like changing your phone's contact list - when someone asks for "facebook.com", we say "number not found."

**What happens:**

1. User types "facebook.com"
2. Router checks: "Is this blocked?"
3. If blocked: Shows "Access Denied" page
4. If allowed: Shows Facebook normally

---

## 🛡️ Internet Control - The Smart Switch

**What it does:** Like having a smart electrical panel where you can turn off power to specific rooms, but keep the lights on for one special room.

### Selective Internet Blocking

**Real scenario:** Internet cafe with 50 computers, but one customer didn't pay.

**Traditional way (dumb):**

1. Find customer's computer
2. Manually disconnect cable
3. Customer moves to different computer
4. Repeat process

**NetHive way (smart):**

```php
// Block entire pool (all 50 computers)
blockPool('192.168.1.1-192.168.1.50');

// But allow one specific user who paid
allowUser('192.168.1.25'); // This computer still works
```

### Firewall Rule Automation

**Think of it like:** Having a smart security system that automatically locks all doors except for people with the right key card.

**What happens behind the scenes:**

1. You click "Block Pool, Allow User"
2. System creates firewall rule: "Block 192.168.1.1-50"
3. System creates exception rule: "Allow 192.168.1.25"
4. Router applies rules instantly
5. 49 computers lose internet, 1 computer keeps working

---

## 📊 User Logs - The Security Camera System

**What it does:** Like having security cameras that record everything, but smart enough to find exactly what you need.

### High-Performance Log Viewer

**The problem:** Imagine trying to watch 1000 hours of security footage at once - your TV would explode!

**Our solution:**

```php
// Instead of loading entire 5GB log file:
$file = new SplFileObject('huge-log.txt');
$file->seek(1000); // Jump to line 1000
$oneLine = $file->current(); // Read just this line
```

**Think of it like:** Having a smart security guard who can instantly jump to "Tuesday 3 PM" instead of watching from Monday morning.

### Pagination System

**Real example:**

- Total logs: 1,000,000 entries
- Show per page: 50 entries
- Pages: 20,000 pages
- Load time per page: 100ms (instead of 60 seconds for all)

### Real-time Search with Debouncing

**Without debouncing (bad):**

```
User types "error":
'e' → Search! (1 server request)
'er' → Search! (2 server requests)
'err' → Search! (3 server requests)
'erro' → Search! (4 server requests)
'error' → Search! (5 server requests)
```

**With debouncing (smart):**

```
User types "error":
'e' → Wait...
'er' → Wait...
'err' → Wait...
'erro' → Wait...
'error' → Wait 300ms → Search! (1 server request)
```

---

## 📈 Reports - Your Business Intelligence

**What it does:** Like having a smart accountant who can instantly tell you how your business is doing.

### Voucher Analytics

**Example questions it answers:**

- "How many vouchers were sold this month?" → 1,250 vouchers
- "Which time limit is most popular?" → 2-hour vouchers (60%)
- "What's our revenue?" → $3,750 this month

### Bandwidth Reports

**Think of it like:** A smart electricity meter that shows:

- Peak usage time: 8 PM (everyone streaming Netflix)
- Heaviest user: Customer #123 (downloaded 50GB)
- Average usage: 2GB per customer per day

---

## ⚙️ Settings Management - The Control Room

### NAS Management

**What it does:** Like having a remote control for multiple TVs in different rooms.

**Example setup:**

```json
{
  "router1": {
    "name": "Main Office Router",
    "ip": "192.168.1.1",
    "username": "admin",
    "password": "secret123"
  },
  "router2": {
    "name": "Branch Office Router",
    "ip": "10.0.0.1",
    "username": "admin",
    "password": "secret456"
  }
}
```

**What you can do:**

- Switch between routers with dropdown menu
- Manage all routers from one interface
- No need to log into each router separately

### API User Management

**Think of it like:** Managing employee access cards in an office building.

**User roles explained:**

- **Admin**: Master key (can do everything)
- **Operator**: Department key (can manage users, view reports)
- **Viewer**: Visitor pass (can only view, no changes)

---

## 🚀 Performance Magic - Why It's So Fast

### Conditional Asset Loading

**Bad way (loads everything):**

```html
<!-- Every page loads ALL JavaScript files -->
<script src="dashboard.js"></script>
<!-- 50KB -->
<script src="hotspot.js"></script>
<!-- 75KB -->
<script src="queue.js"></script>
<!-- 30KB -->
<script src="reports.js"></script>
<!-- 40KB -->
<!-- Total: 195KB on every page! -->
```

**Smart way (loads only what's needed):**

```php
// Dashboard page only loads dashboard.js (50KB)
// Hotspot page only loads hotspot.js (75KB)
// 75% less loading time!
```

### File Streaming

**Traditional way (memory killer):**

```php
$bigFile = file_get_contents('5GB-log.txt'); // Loads entire 5GB into memory!
// Result: Server crashes
```

**Smart way (memory friendly):**

```php
$file = new SplFileObject('5GB-log.txt');
$file->seek(1000);           // Jump to line 1000
$line = $file->current();    // Read only this line (few bytes)
// Result: Uses 5MB instead of 5GB memory
```

---

## 🔒 Security Features - Your Digital Bodyguard

### Complete API Protection

**The Problem (Before):**
```
Anyone could access:
- yoursite.com/api/user_operations.php → Create admin accounts!
- yoursite.com/api/nas_operations.php → Steal router passwords!
- yoursite.com/api/hotspot_voucher.php → Generate unlimited vouchers!
```

**The Solution (Now):**
```php
// Every API file now starts with:
require_once 'auth_check.php';
checkAuth(['admin', 'operator']); // Only these roles allowed

// If not logged in → 401 Unauthorized
// If wrong role → 403 Forbidden
```

### Directory Protection
**Think of it like:** Having security guards at every door.

**What we blocked:**
- `yoursite.com/data/` → 403 Forbidden (contains passwords)
- `yoursite.com/api/` → 403 Forbidden (no browsing allowed)
- `yoursite.com/views/` → 403 Forbidden (no direct access)
- All subdirectories → No browsing allowed

### Password Hashing

**Secure way (safe):**

```json
{
  "username": "admin",
  "password": "$2y$10$abcd1234..." // Encrypted, impossible to reverse
}
```

### Role-based Access

**Example scenario:**

- **Admin** logs in → Sees everything, can change everything
- **Operator** logs in → Sees most things, can manage users
- **Viewer** logs in → Sees reports only, cannot change anything

**How it works:**

```php
if ($userRole === 'admin') {
    showAllPages(); // Full access
} elseif ($userRole === 'operator') {
    showOperatorPages(); // Limited access
} else {
    showViewerPages(); // Read-only access
}
```

---

## 🎯 The Bottom Line

**NetHive is like having:**

- A smart hotel manager (Hotspot)
- A traffic cop (Queue Management)
- A security guard (Web Blocking)
- A surveillance system (User Logs)
- An electrical engineer (Internet Control)
- A business analyst (Reports)
- A building manager (Settings)
- A fullscreen control center (Fullscreen Support)
- A digital fortress (Enterprise Security)

**All controlled from one simple web interface, designed to handle thousands of users without breaking a sweat!**

### 🖥️ Fullscreen Support
**What it does:** Click the fullscreen button in navbar to enter/exit fullscreen mode.

**How it works:**
```javascript
// Click button → Enter fullscreen
if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen();
    // Icon changes to compress icon
}
// Click again → Exit fullscreen
else {
    document.exitFullscreen();
    // Icon changes back to square
}
```

### 🔐 Enterprise Security Updates
**Complete API Protection:**
```
Before: Anyone could access APIs directly
Now: All APIs require proper authentication and role permissions
```

**Directory Protection:**
```
Before: Could browse /data/, /api/, /views/ directories
Now: All directories return 403 Forbidden - no browsing allowed
```

---

_This system transforms complex network management into simple point-and-click operations, making advanced ISP management accessible to everyone._
