# NetHive - MikroTik Network Management System

## Project Overview

**NetHive** is a comprehensive web-based management system for MikroTik routers, designed for ISPs and network administrators to manage multiple NAS (Network Access Server) devices efficiently.

## Architecture & Structure

### Core Architecture
```
MVC-like Pattern:
├── Frontend (Views + Assets)
├── API Layer (Business Logic)
├── Data Layer (JSON + Log Files)
└── External Integration (MikroTik API)
```

### Authentication & Authorization
- **Session-based authentication** with role-based access control
- **3 User Roles**: Admin, Operator, Viewer
- **Page-level permissions** defined in `index.php`
- **JSON-based user storage** with bcrypt password hashing

### File Structure Analysis

**Core Files:**
- `index.php` - Main router with authentication & authorization
- `login.php` - Authentication interface
- `logout.php` - Session cleanup

**Layout System:**
- `includes/header.php` - CSS, meta tags, page titles
- `includes/navbar.php` - Global NAS selector & connection status
- `includes/sidebar.php` - Navigation menu
- `includes/footer.php` - JavaScript loading & page-specific scripts

**Data Storage:**
- `data/nas_details.json` - NAS device configurations
- `data/users.json` - User accounts & roles
- `data/webBlocking.json` - Web filtering rules
- `/var/log/remotelogs/` - External log storage

## Core Functionality

### 1. Dashboard (`dashboard.php`)
- **Real-time monitoring**: CPU, Memory, HDD usage
- **System information**: Uptime, model, RouterOS version
- **Interface traffic charts** with Chart.js
- **Live logs**: Application & hotspot logs
- **User statistics**: Active/total users

### 2. Hotspot Management (`hotspot.php`)
- **User management**: Create, edit, delete hotspot users
- **Voucher generation**: Bulk voucher creation with customizable parameters
- **User profiles**: Bandwidth limits, time restrictions, expiry settings
- **Active sessions**: Monitor connected users
- **Host management**: MAC address binding

### 3. Queue Management (`queue.php`)
- **Simple queues**: Bandwidth allocation per IP/user
- **Drag & drop reordering** with SortableJS
- **Real-time editing**: Modify queue parameters
- **Active client detection**: Auto-populate target IPs

### 4. Web Blocking (`webBlocking.php`)
- **Category-based filtering**: Adware, malware, social media, etc.
- **Custom domain blocking**: Manual domain additions
- **DNS configuration**: Automatic router DNS setup
- **Multiple filter sources**: Steven Black's hosts lists

### 5. User Logs (`userlogs.php`)
- **High-performance log viewer**: Handles GB-sized files
- **Pagination system**: 50-1000 records per page
- **Real-time search**: Debounced filtering
- **CSV export**: Data export functionality
- **Logging configuration**: Remote syslog setup

### 6. Reports (`reports.php`)
- **Voucher analytics**: Usage statistics & charts
- **Bandwidth reports**: Download/upload metrics
- **System logs**: Router log analysis
- **Visual dashboards**: Chart.js integration

### 7. Settings Management
- **NAS Management**: Add/edit/delete router configurations
- **API User Management**: User account administration
- **System configuration**: Future expansion ready

## Technical Implementation

### Frontend Technologies
- **Bootstrap 5**: Responsive UI framework
- **Font Awesome**: Icon library
- **Chart.js**: Data visualization
- **DataTables**: Advanced table features
- **SortableJS**: Drag & drop functionality
- **Custom CSS**: Page-specific styling

### Backend Technologies
- **PHP 7.4+**: Server-side logic
- **MikroTik API**: RouterOS integration via `routeros_api.class.php`
- **JSON storage**: Lightweight data persistence
- **Session management**: Secure authentication
- **File streaming**: Efficient log processing

### Performance Optimizations
- **Conditional asset loading**: Page-specific JS/CSS
- **Lazy loading**: Pagination for large datasets
- **File streaming**: `SplFileObject` for log processing
- **Debounced search**: Reduced server requests
- **Async operations**: Non-blocking UI updates

## API Architecture

### Modular API Design
Each module has dedicated API endpoints:
- `dashboard_operations.php` - System monitoring
- `hotspot_operations.php` - Hotspot management
- `queue_operations.php` - Bandwidth queues
- `webBlocking_operations.php` - Content filtering
- `userlogs_operations.php` - Log management
- `nas_operations.php` - Device management
- `user_operations.php` - User administration

### MikroTik Integration
- **RouterOS API Class**: Direct router communication
- **Multi-device support**: Manage multiple routers
- **Real-time operations**: Live configuration changes
- **Error handling**: Robust connection management

## Security Features

### Authentication Security
- **Password hashing**: bcrypt with salt
- **Session management**: Secure session handling
- **Role-based access**: Granular permissions
- **CSRF protection**: Form token validation (ready for implementation)

### Input Validation
- **Parameter sanitization**: XSS prevention
- **SQL injection protection**: Parameterized queries (when applicable)
- **File path validation**: Directory traversal prevention

## Future Possibilities & Expansion

### Immediate Enhancements
1. **Real-time notifications**: WebSocket integration
2. **Multi-language support**: i18n implementation
3. **Dark mode**: Theme switching
4. **Mobile app**: React Native/Flutter companion
5. **API documentation**: Swagger/OpenAPI integration

### Advanced Features
1. **Network topology mapping**: Visual network diagrams
2. **Automated backups**: Configuration versioning
3. **Alert system**: Email/SMS notifications
4. **Load balancing**: Multi-router failover
5. **Bandwidth analytics**: Historical usage patterns

### Enterprise Features
1. **Multi-tenant support**: ISP customer isolation
2. **Billing integration**: Payment gateway connectivity
3. **RADIUS integration**: Centralized authentication
4. **SNMP monitoring**: Network device monitoring
5. **API rate limiting**: Enterprise-grade API management

### Scalability Improvements
1. **Database migration**: MySQL/PostgreSQL support
2. **Caching layer**: Redis/Memcached integration
3. **Microservices**: Service-oriented architecture
4. **Container deployment**: Docker/Kubernetes ready
5. **CDN integration**: Asset optimization

### Integration Possibilities
1. **Third-party APIs**: Payment processors, SMS gateways
2. **Monitoring tools**: Grafana, Prometheus integration
3. **Ticketing systems**: Support desk integration
4. **Cloud storage**: AWS S3, Google Cloud integration
5. **VPN management**: OpenVPN, WireGuard support

## Current Strengths

1. **Performance**: Optimized for large-scale operations
2. **Modularity**: Easy to extend and maintain
3. **User Experience**: Intuitive interface design
4. **Scalability**: Handles multiple routers efficiently
5. **Security**: Role-based access control
6. **Flexibility**: Configurable for various ISP needs

## Deployment Readiness

The system is **production-ready** with:
- Robust error handling
- Performance optimizations
- Security implementations
- Scalable architecture
- Comprehensive functionality

**NetHive** represents a mature, enterprise-grade solution for MikroTik network management with significant potential for expansion into a comprehensive ISP management platform.

## Installation & Setup

### Requirements
- PHP 7.4 or higher
- Web server (Apache/Nginx)
- MikroTik RouterOS devices
- Network connectivity to managed routers

### Quick Start
1. Clone/download project files
2. Configure web server document root
3. Set up NAS devices in `data/nas_details.json`
4. Create user accounts in `data/users.json`
5. Access via web browser and login

### Default Credentials
- Username: `admin`
- Password: `admin123`
- Role: `admin`

*Change default credentials immediately after installation*

## License & Support

This project is designed for network administrators and ISP management. For support and customization requests, contact the development team.