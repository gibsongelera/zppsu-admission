# ZPPSU Admission System - Deployment Guide

## Deploying to Render + Supabase

### Step 1: Set Up Supabase Database

1. **Go to Supabase SQL Editor**
   - Open your Supabase project: https://supabase.com/dashboard
   - Click on "SQL Editor" in the left sidebar

2. **Run the Schema Script**
   - Copy the contents of `database/supabase_schema.sql`
   - Paste into the SQL Editor
   - Click "Run" to create all tables

3. **Get Your Database Password**
   - Go to Project Settings → Database
   - Copy the database password (you set this when creating the project)
   - If you forgot it, reset it in the settings

### Step 2: Configure Render Environment Variables

1. **Go to Render Dashboard**
   - Open: https://dashboard.render.com
   - Select your `zppsu-admission` service
   - Click on "Environment" tab

2. **Add These Environment Variables:**

   | Key | Value |
   |-----|-------|
   | `DB_HOST` | `db.nvojdxaektltusfprjaq.supabase.co` |
   | `DB_PORT` | `6543` ⚠️ **Use connection pooler port** |
   | `DB_NAME` | `postgres` |
   | `DB_USER` | `postgres` |
   | `DB_PASS` | `[Your Supabase Database Password]` |
   | `DB_TYPE` | `pgsql` |
   | `RENDER` | `true` |

   **Important:** Use port `6543` (connection pooler) instead of `5432` (direct connection). The pooler:
   - Avoids IPv6 connection issues
   - Is optimized for serverless/cloud deployments
   - Provides better connection management

3. **Save Changes**
   - Click "Save Changes" after adding all variables

### Step 3: Deploy

1. **Trigger New Deploy**
   - In Render, go to "Manual Deploy"
   - Click "Deploy latest commit"
   - Wait for the build to complete (5-10 minutes)

2. **Check Logs**
   - Click on "Logs" tab to see deployment progress
   - Look for any errors

### Step 4: Verify Deployment

1. Open your site: https://zppsu-admission.onrender.com
2. Try logging in with:
   - Username: `admin`
   - Password: `admin123`

---

## Troubleshooting

### "Internal Server Error"
- Check Render logs for specific PHP errors
- Verify all environment variables are set
- Make sure Supabase database is accessible

### "Connection failed" or "Network is unreachable"
- **Use Connection Pooler (Port 6543)**: Make sure `DB_PORT` is set to `6543`, not `5432`
  - The connection pooler avoids IPv6 issues and works better on cloud platforms
  - Direct connection (5432) may fail with IPv6 addresses
- **Check Supabase Firewall Settings**:
  1. Go to Supabase Dashboard → Project Settings → Database
  2. Scroll to "Connection Pooling" section
  3. Make sure "Connection Pooler" is enabled
  4. Check "Network Restrictions" - ensure it allows connections from all IPs (0.0.0.0/0) or add Render's IP ranges
  5. If using IPv6, you may need to enable IPv6 support in Supabase settings
- **Verify `DB_PASS` is correct**
- The code automatically resolves hostname to IPv4 when possible
- **If IPv6 errors persist**:
  - Try using port `5432` (direct connection) instead of `6543` (pooler)
  - Check Supabase project settings for IPv6/IPv4 preferences
  - Contact Supabase support if Render's network can't reach Supabase
- Try connecting via psql from your computer first:
  ```
  # Test connection pooler (recommended)
  psql -h db.nvojdxaektltusfprjaq.supabase.co -p 6543 -d postgres -U postgres
  
  # Or test direct connection
  psql -h db.nvojdxaektltusfprjaq.supabase.co -p 5432 -d postgres -U postgres
  ```

### "Table does not exist"
- Run the `database/supabase_schema.sql` script in Supabase SQL Editor

---

## Your Supabase Connection Details

```
Host: db.nvojdxaektltusfprjaq.supabase.co
Port: 5432
Database: postgres
User: postgres
Password: [Set in Supabase Dashboard]
```

## Files Modified for Deployment

1. `Dockerfile` - Docker configuration for PHP + Apache
2. `initialize.php` - Environment variable support
3. `admin/inc/db_connect.php` - PDO wrapper for PostgreSQL
4. `classes/DBConnection.php` - Updated for PDO
5. `inc/db_connect.php` - Updated for PDO
6. `render.yaml` - Render deployment blueprint
7. `database/supabase_schema.sql` - PostgreSQL schema

---

## Local Development

The system still works with MySQL locally. Just keep your XAMPP MySQL running.
The code auto-detects:
- If `DB_PORT=5432` → Uses PostgreSQL (Supabase)
- If `DB_PORT=3306` → Uses MySQL (XAMPP)

